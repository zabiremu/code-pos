<?php

namespace Tests\Feature\Admin;

use App\Models\Grn;
use App\Models\GrnReturn;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class PurchasingFlowTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function product(string $name = 'Rice 5kg'): Product
    {
        return Product::create(['name' => $name, 'base_price' => 500, 'track_stock' => true, 'is_available' => true]);
    }

    public function test_full_purchase_receive_and_return_flow(): void
    {
        $admin = $this->staff('admin');
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::default();
        $rice = $this->product();

        // 1. Purchase order: no stock moves yet.
        $this->actingAs($admin)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_date' => today()->toDateString(),
            'status' => 'ordered',
            'shipping' => '50',
            'items' => [['product_id' => $rice->id, 'quantity' => '10', 'unit_cost' => '400']],
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::firstOrFail();
        $this->assertStringStartsWith('PO-', $purchase->reference_no);
        $this->assertEquals(4050, (float) $purchase->total);
        $this->assertEquals(0, (float) $rice->fresh()->stock_quantity);

        // 2. Receive 6 of 10: stock +6, purchase partly received, cost updated.
        $this->actingAs($admin)->get(route('admin.grns.create', ['purchase_id' => $purchase->id]))->assertOk();
        $this->actingAs($admin)->post(route('admin.grns.store'), [
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'received_date' => today()->toDateString(),
            'items' => [['product_id' => $rice->id, 'purchase_item_id' => $purchase->items->first()->id, 'quantity' => '6', 'unit_cost' => '410']],
        ])->assertSessionHasNoErrors();

        $grn = Grn::firstOrFail();
        $this->assertEquals(6, (float) $rice->fresh()->stock_quantity);
        $this->assertEquals(410, (float) $rice->fresh()->purchase_price);
        $this->assertSame('partial', $purchase->fresh()->status);

        // 3. Return 2 damaged bags: stock -2, GRN tracks what was returned.
        $this->actingAs($admin)->post(route('admin.grn-returns.store'), [
            'grn_id' => $grn->id,
            'return_date' => today()->toDateString(),
            'reason' => 'Torn bags',
            'items' => [['grn_item_id' => $grn->items->first()->id, 'quantity' => '2']],
        ])->assertSessionHasNoErrors();

        $return = GrnReturn::firstOrFail();
        $this->assertEquals(4, (float) $rice->fresh()->stock_quantity);
        $this->assertEquals(820, (float) $return->total);
        $this->assertEquals(2, (float) $grn->items->first()->fresh()->returned_quantity);

        // 4. Can't return more than is left on the GRN.
        $this->actingAs($admin)->post(route('admin.grn-returns.store'), [
            'grn_id' => $grn->id,
            'return_date' => today()->toDateString(),
            'items' => [['grn_item_id' => $grn->items->first()->id, 'quantity' => '5']],
        ])->assertSessionHasErrors();

        // 5. A GRN with returns is locked.
        $this->actingAs($admin)->delete(route('admin.grns.destroy', $grn))->assertSessionHasErrors();

        // 6. Deleting the return puts stock back and unlocks the GRN.
        $this->actingAs($admin)->delete(route('admin.grn-returns.destroy', $return))->assertRedirect();
        $this->assertEquals(6, (float) $rice->fresh()->stock_quantity);

        // 7. Receive the other 4: purchase complete.
        $this->actingAs($admin)->post(route('admin.grns.store'), [
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'received_date' => today()->toDateString(),
            'items' => [['product_id' => $rice->id, 'purchase_item_id' => $purchase->items->first()->id, 'quantity' => '4', 'unit_cost' => '410']],
        ])->assertSessionHasNoErrors();
        $this->assertSame('received', $purchase->fresh()->status);

        // 8. Deleting the first GRN takes its 6 out and re-opens the purchase.
        $this->actingAs($admin)->delete(route('admin.grns.destroy', $grn))->assertRedirect(route('admin.grns.index'));
        $this->assertEquals(4, (float) $rice->fresh()->stock_quantity);
        $this->assertSame('partial', $purchase->fresh()->status);

        foreach ([route('admin.purchases.index'), route('admin.purchases.show', $purchase), route('admin.grns.index'), route('admin.grn-returns.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_editing_a_grn_replaces_its_stock(): void
    {
        $admin = $this->staff('admin');
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::default();
        $oil = $this->product('Soybean oil');

        $payload = fn (string $qty) => [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'received_date' => today()->toDateString(),
            'items' => [['product_id' => $oil->id, 'quantity' => $qty, 'unit_cost' => '150']],
        ];

        $this->actingAs($admin)->post(route('admin.grns.store'), $payload('10'))->assertSessionHasNoErrors();
        $grn = Grn::firstOrFail();

        $this->actingAs($admin)->put(route('admin.grns.update', $grn), $payload('7'))->assertSessionHasNoErrors();

        $this->assertEquals(7, (float) $oil->fresh()->stock_quantity);
        $this->assertEquals(7, (float) $grn->items()->first()->quantity);
    }

    public function test_purchase_with_goods_received_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->staff('admin');
        $supplier = Supplier::factory()->create();
        $product = $this->product();
        $warehouse = Warehouse::default();

        $this->actingAs($admin)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id,
            'purchase_date' => today()->toDateString(), 'status' => 'ordered',
            'items' => [['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '10']],
        ]);
        $purchase = Purchase::firstOrFail();

        $this->actingAs($admin)->post(route('admin.grns.store'), [
            'purchase_id' => $purchase->id, 'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id,
            'received_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'purchase_item_id' => $purchase->items->first()->id, 'quantity' => '5', 'unit_cost' => '10']],
        ]);

        $this->actingAs($admin)->get(route('admin.purchases.edit', $purchase))->assertRedirect(route('admin.purchases.show', $purchase));
        $this->actingAs($admin)->delete(route('admin.purchases.destroy', $purchase))->assertSessionHasErrors('purchase');
        $this->actingAs($admin)->delete(route('admin.suppliers.destroy', $supplier))->assertSessionHasErrors('supplier');
    }

    public function test_cashier_cannot_reach_purchasing(): void
    {
        $this->actingAs($this->staff('cashier'))->get(route('admin.purchases.index'))->assertForbidden();
    }
}
