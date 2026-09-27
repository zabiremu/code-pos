<?php

namespace Tests\Feature\Pos;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class CustomerFlowTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function product(): Product
    {
        $product = Product::create(['name' => 'Rice 1kg', 'base_price' => 100, 'track_stock' => true, 'is_available' => true]);
        app(StockService::class)->adjust($product, Warehouse::default()->id, 50, 'opening');

        return $product->fresh();
    }

    private function creditSale(Customer $customer, Product $product, int $qty, float $paidNow = 0)
    {
        return $this->post(route('pos.register.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'method' => 'due',
            'customer_id' => $customer->id,
            'paid_now' => $paidNow,
        ]);
    }

    public function test_quick_add_customer_from_the_register(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->postJson(route('pos.register.customers.store'), ['name' => 'Karim', 'phone' => '01711000000'])
            ->assertCreated()->assertJsonPath('name', 'Karim');

        $this->actingAs($this->staff('cashier'))
            ->postJson(route('pos.register.customers.store'), ['name' => 'Other', 'phone' => '01711000000'])
            ->assertStatus(422);
    }

    public function test_credit_sale_collection_and_return_flow(): void
    {
        $admin = $this->staff('admin');
        $customer = Customer::create(['name' => 'Karim', 'phone' => '017', 'is_active' => true]);
        $rice = $this->product();
        $this->actingAs($admin);

        // Two credit sales: 500 (paid 200 now) and 300.
        $this->creditSale($customer, $rice, 5, 200)->assertSessionHasNoErrors();
        $this->creditSale($customer, $rice, 3)->assertSessionHasNoErrors();
        [$first, $second] = Bill::orderBy('id')->get()->all();

        $this->assertSame('partially_paid', $first->fresh()->status);
        $this->assertSame('unpaid', $second->fresh()->status);
        $this->assertEquals(600, $customer->due());
        $this->assertEquals(42, (float) $rice->fresh()->stock_quantity);

        // Can't collect more than is owed.
        $this->post(route('admin.customer-receipts.store'), [
            'customer_id' => $customer->id, 'receipt_date' => today()->toDateString(), 'amount' => '700', 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        // Collect 400: pays off the first bill (300 left on it) then 100 of the second.
        $this->post(route('admin.customer-receipts.store'), [
            'customer_id' => $customer->id, 'receipt_date' => today()->toDateString(), 'amount' => '400', 'method' => 'mobile_wallet',
        ])->assertRedirect(route('admin.customers.show', $customer));

        $this->assertSame('paid', $first->fresh()->status);
        $this->assertSame('partially_paid', $second->fresh()->status);
        $this->assertEquals(200, $customer->due());

        // Return 1 bag from the second bill against the due: owes 100.
        $this->post(route('admin.sale-returns.store'), [
            'bill_id' => $second->id, 'return_date' => today()->toDateString(), 'refund_method' => 'adjust_due', 'restock' => '1',
            'items' => [['sale_item_id' => $second->sale->items->first()->id, 'quantity' => '1']],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(100, $customer->due());
        $this->assertEquals(43, (float) $rice->fresh()->stock_quantity);

        // Can't return more than was bought.
        $this->post(route('admin.sale-returns.store'), [
            'bill_id' => $second->id, 'return_date' => today()->toDateString(), 'refund_method' => 'cash', 'restock' => '1',
            'items' => [['sale_item_id' => $second->sale->items->first()->id, 'quantity' => '5']],
        ])->assertSessionHasErrors();

        // Deleting the return puts the due and stock back.
        $this->delete(route('admin.sale-returns.destroy', SaleReturn::first()))->assertSessionHasNoErrors();
        $this->assertEquals(200, $customer->due());
        $this->assertEquals(42, (float) $rice->fresh()->stock_quantity);

        // Deleting the collection puts that back too.
        $this->delete(route('admin.customer-receipts.destroy', CustomerReceipt::first()));
        $this->assertEquals(600, $customer->due());

        foreach ([route('admin.customers.index'), route('admin.customers.index', ['filter' => 'due']), route('admin.customers.show', $customer),
            route('admin.customer-receipts.create', ['customer_id' => $customer->id]), route('admin.sale-returns.create', ['bill' => $second->id]),
            route('pos.register.receipt', $second), route('pos.register')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_credit_limit_is_enforced(): void
    {
        $customer = Customer::create(['name' => 'Limited', 'credit_limit' => 250, 'is_active' => true]);
        $rice = $this->product();

        $this->actingAs($this->staff('cashier'));
        $this->creditSale($customer, $rice, 2)->assertSessionHasNoErrors();   // owes 200
        $this->creditSale($customer, $rice, 1)->assertSessionHasErrors('paid_now'); // would be 300
        $this->creditSale($customer, $rice, 1, 50)->assertSessionHasNoErrors(); // 250 - fits
        $this->assertEquals(250, $customer->due());
    }

    public function test_pay_later_needs_a_customer(): void
    {
        $this->actingAs($this->staff('cashier'))->post(route('pos.register.checkout'), [
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]], 'method' => 'due',
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_cash_refund_on_a_paid_sale(): void
    {
        $rice = $this->product();
        $this->actingAs($this->staff('admin'));
        $this->post(route('pos.register.checkout'), ['items' => [['product_id' => $rice->id, 'quantity' => 2]], 'method' => 'card']);
        $bill = Bill::firstOrFail();

        $this->post(route('admin.sale-returns.store'), [
            'bill_id' => $bill->id, 'return_date' => today()->toDateString(), 'refund_method' => 'cash', 'restock' => '0',
            'items' => [['sale_item_id' => $bill->sale->items->first()->id, 'quantity' => '1']],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(100, (float) SaleReturn::first()->total);
        $this->assertEquals(48, (float) $rice->fresh()->stock_quantity); // not restocked
        $this->assertSame('paid', $bill->fresh()->status);
    }
}
