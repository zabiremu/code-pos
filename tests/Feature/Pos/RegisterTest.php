<?php

namespace Tests\Feature\Pos;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function product(array $attrs = []): Product
    {
        return Product::create($attrs + ['name' => 'Biscuit', 'base_price' => 20, 'track_stock' => true, 'stock_quantity' => 50, 'is_available' => true]);
    }

    public function test_register_page_lists_available_products(): void
    {
        $this->product(['name' => 'Chanachur']);
        $this->product(['name' => 'Hidden thing', 'is_available' => false]);

        $this->actingAs($this->staff('cashier'))
            ->get(route('pos.register'))
            ->assertOk()
            ->assertSee('Chanachur')
            ->assertDontSee('Hidden thing');
    }

    public function test_register_is_wired_for_keyboard_only_selling(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->get(route('pos.register'))
            ->assertOk()
            // The key handlers the JS relies on (tests/js/register-keyboard.test.mjs covers the behaviour).
            ->assertSee('searchKeys($event)', false)
            ->assertSee('qtyKeys($event, line)', false)
            ->assertSee('discountKeys($event)', false)
            ->assertSee('customerKeys($event)', false)
            // Payment methods are passed in so PgUp/PgDn can cycle them.
            ->assertSee('methods: '.\Illuminate\Support\Js::from(array_keys(\App\Http\Controllers\POS\RegisterController::METHODS)), false)
            // The shortcut list a cashier gets with ? or F1.
            ->assertSee('Keyboard shortcuts')
            ->assertSee('Add the scanned or highlighted product');
    }

    public function test_cash_checkout_saves_everything_in_one_go(): void
    {
        Branch::create(['name' => 'Main', 'tax_rate' => 5, 'currency' => 'BDT', 'is_active' => true]);
        $biscuit = $this->product();
        $cashier = $this->staff('cashier');

        $response = $this->actingAs($cashier)->post(route('pos.register.checkout'), [
            'items' => [
                ['product_id' => $biscuit->id, 'quantity' => 2],
                ['product_id' => $biscuit->id, 'quantity' => 1], // merged into one line
            ],
            'discount' => '3',
            'method' => 'cash',
            'tendered' => '100',
        ]);

        $bill = Bill::firstOrFail();
        $response->assertRedirect(route('pos.register.receipt', $bill));
        $response->assertSessionHas('change', 100 - 60.0);

        // 3 x 20 = 60, +5% tax = 3, -3 discount = 60.
        $this->assertEquals(60, (float) $bill->subtotal);
        $this->assertEquals(3, (float) $bill->tax_total);
        $this->assertEquals(3, (float) $bill->discount_total);
        $this->assertEquals(60, (float) $bill->grand_total);
        $this->assertSame('paid', $bill->status);

        $sale = Sale::firstOrFail();
        $this->assertSame('closed', $sale->status);
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertCount(1, $sale->items);
        $this->assertEquals(47, (float) $biscuit->fresh()->stock_quantity);

        $this->actingAs($cashier)->get(route('pos.register.receipt', $bill))->assertOk()->assertSee('Biscuit');
    }

    public function test_server_uses_its_own_prices_not_the_browsers(): void
    {
        $biscuit = $this->product(['base_price' => 20]);

        $this->actingAs($this->staff('cashier'))->post(route('pos.register.checkout'), [
            'items' => [['product_id' => $biscuit->id, 'quantity' => 1, 'unit_price' => 1]],
            'method' => 'card',
        ]);

        $this->assertEquals(20, (float) Bill::firstOrFail()->grand_total);
    }

    public function test_short_cash_is_rejected_and_nothing_is_saved(): void
    {
        $biscuit = $this->product();

        $this->actingAs($this->staff('cashier'))->post(route('pos.register.checkout'), [
            'items' => [['product_id' => $biscuit->id, 'quantity' => 5]],
            'method' => 'cash',
            'tendered' => '50',
        ])->assertSessionHasErrors('tendered');

        $this->assertSame(0, Sale::count());
        $this->assertEquals(50, (float) $biscuit->fresh()->stock_quantity);
    }

    public function test_empty_cart_is_rejected(): void
    {
        $this->actingAs($this->staff('cashier'))
            ->post(route('pos.register.checkout'), ['items' => [], 'method' => 'cash'])
            ->assertSessionHasErrors('items');
    }
}
