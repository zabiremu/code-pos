<?php

namespace Tests\Feature\Admin;

use App\Models\Grn;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class StockOperationsTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function stocked(float $qty, ?Warehouse $warehouse = null): Product
    {
        $product = Product::create(['name' => 'Tea 200g', 'base_price' => 120, 'purchase_price' => 90, 'track_stock' => true, 'is_available' => true]);
        app(StockService::class)->adjust($product, ($warehouse ?? Warehouse::default())->id, $qty, 'opening');

        return $product;
    }

    public function test_transfer_moves_stock_between_warehouses_and_delete_moves_it_back(): void
    {
        $admin = $this->staff('admin');
        $main = Warehouse::default();
        $shop = Warehouse::create(['name' => 'Shop floor', 'is_active' => true]);
        $tea = $this->stocked(10);

        $this->actingAs($admin)->post(route('admin.stock-transfers.store'), [
            'from_warehouse_id' => $main->id, 'to_warehouse_id' => $shop->id, 'transfer_date' => today()->toDateString(),
            'items' => [['product_id' => $tea->id, 'quantity' => '4']],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(6, app(StockService::class)->available($tea, $main->id));
        $this->assertEquals(4, app(StockService::class)->available($tea, $shop->id));
        $this->assertEquals(10, (float) $tea->fresh()->stock_quantity);

        $this->actingAs($admin)->delete(route('admin.stock-transfers.destroy', StockTransfer::first()));
        $this->assertEquals(10, app(StockService::class)->available($tea, $main->id));
        $this->assertEquals(0, app(StockService::class)->available($tea, $shop->id));
    }

    public function test_cannot_transfer_more_than_is_in_the_source(): void
    {
        $shop = Warehouse::create(['name' => 'Shop floor', 'is_active' => true]);
        $tea = $this->stocked(3);

        $this->actingAs($this->staff('admin'))->post(route('admin.stock-transfers.store'), [
            'from_warehouse_id' => Warehouse::default()->id, 'to_warehouse_id' => $shop->id, 'transfer_date' => today()->toDateString(),
            'items' => [['product_id' => $tea->id, 'quantity' => '5']],
        ])->assertSessionHasErrors('items');

        $this->assertEquals(3, (float) $tea->fresh()->stock_quantity);
    }

    public function test_write_off_and_stock_count(): void
    {
        $admin = $this->staff('admin');
        $main = Warehouse::default();
        $tea = $this->stocked(20);

        // Write off 2 damaged.
        $this->actingAs($admin)->post(route('admin.stock-adjustments.store'), [
            'warehouse_id' => $main->id, 'adjustment_date' => today()->toDateString(), 'reason' => 'damaged',
            'items' => [['product_id' => $tea->id, 'direction' => 'remove', 'quantity' => '2']],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(18, (float) $tea->fresh()->stock_quantity);
        $this->assertEquals(-180, StockAdjustment::first()->load('items')->value());

        // Count finds 15 on the shelf: system goes 18 -> 15.
        $this->actingAs($admin)->post(route('admin.stock-adjustments.store'), [
            'warehouse_id' => $main->id, 'adjustment_date' => today()->toDateString(), 'reason' => 'count',
            'items' => [['product_id' => $tea->id, 'counted' => '15']],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(15, (float) $tea->fresh()->stock_quantity);

        // Deleting the write-off puts the 2 back.
        $this->actingAs($admin)->delete(route('admin.stock-adjustments.destroy', StockAdjustment::where('reason', 'damaged')->first()));
        $this->assertEquals(17, (float) $tea->fresh()->stock_quantity);
    }

    public function test_supplier_payments_reduce_the_balance_and_lock_the_grn(): void
    {
        $admin = $this->staff('admin');
        $supplier = Supplier::factory()->create();
        $tea = $this->stocked(0);

        $this->actingAs($admin)->post(route('admin.grns.store'), [
            'supplier_id' => $supplier->id, 'warehouse_id' => Warehouse::default()->id, 'received_date' => today()->toDateString(),
            'items' => [['product_id' => $tea->id, 'quantity' => '10', 'unit_cost' => '100']],
        ])->assertSessionHasNoErrors();
        $grn = Grn::firstOrFail();
        $this->assertEquals(1000, $supplier->balance());

        // Can't pay more than is due on the GRN.
        $this->actingAs($admin)->post(route('admin.supplier-payments.store'), [
            'supplier_id' => $supplier->id, 'grn_id' => $grn->id, 'amount' => '1200', 'method' => 'cash', 'payment_date' => today()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post(route('admin.supplier-payments.store'), [
            'supplier_id' => $supplier->id, 'grn_id' => $grn->id, 'amount' => '600', 'method' => 'mobile', 'reference' => 'TX123', 'payment_date' => today()->toDateString(),
        ])->assertRedirect(route('admin.suppliers.show', $supplier));

        $this->assertEquals(400, $supplier->balance());
        $this->assertEquals(400, $grn->fresh()->due());
        $this->assertFalse($grn->fresh()->isEditable());

        // An advance on account can exceed what's owed.
        $this->actingAs($admin)->post(route('admin.supplier-payments.store'), [
            'supplier_id' => $supplier->id, 'amount' => '500', 'method' => 'cash', 'payment_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertEquals(-100, $supplier->balance());

        $this->actingAs($admin)->get(route('admin.suppliers.show', $supplier))->assertOk()->assertSee('PAY-');
        $this->actingAs($admin)->get(route('admin.supplier-payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.grns.show', $grn))->assertOk();

        $this->actingAs($admin)->delete(route('admin.supplier-payments.destroy', SupplierPayment::first()));
        $this->assertEquals(500, $supplier->balance());
    }

    public function test_pages_render(): void
    {
        $admin = $this->staff('admin');
        Warehouse::create(['name' => 'Second', 'is_active' => true]);

        foreach (['admin.stock-transfers.index', 'admin.stock-transfers.create', 'admin.stock-adjustments.index', 'admin.stock-adjustments.create', 'admin.supplier-payments.create'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
