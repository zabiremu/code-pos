<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_units_brands_and_warehouses_crud(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->post(route('admin.units.store'), ['name' => 'Kilogram', 'short_name' => 'kg', 'allow_decimal' => '1', 'is_active' => '1'])
            ->assertRedirect(route('admin.units.index'));
        $this->actingAs($admin)->post(route('admin.brands.store'), ['name' => 'Pran', 'is_active' => '1'])
            ->assertRedirect(route('admin.brands.index'));
        $this->actingAs($admin)->post(route('admin.warehouses.store'), ['name' => 'Godown 2', 'code' => 'GDN2', 'is_active' => '1'])
            ->assertRedirect(route('admin.warehouses.index'));

        $this->assertTrue(Unit::where('short_name', 'kg')->first()->allow_decimal);
        $this->assertDatabaseHas('brands', ['name' => 'Pran']);
        $this->assertDatabaseHas('warehouses', ['code' => 'GDN2', 'is_default' => false]);

        foreach (['admin.units.index', 'admin.brands.index', 'admin.warehouses.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_duplicate_brand_names_are_rejected(): void
    {
        Brand::create(['name' => 'Pran', 'is_active' => true]);

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.brands.store'), ['name' => 'Pran'])
            ->assertSessionHasErrors('name');
    }

    public function test_making_a_warehouse_default_unsets_the_old_one(): void
    {
        $old = Warehouse::default();

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.warehouses.store'), ['name' => 'New main', 'is_default' => '1', 'is_active' => '1']);

        $this->assertFalse($old->fresh()->is_default);
        $this->assertSame('New main', Warehouse::default()->name);
    }

    public function test_default_warehouse_cannot_be_deleted(): void
    {
        $this->actingAs($this->staff('admin'))
            ->delete(route('admin.warehouses.destroy', Warehouse::default()))
            ->assertSessionHasErrors('warehouse');
    }

    public function test_product_with_prices_and_opening_stock(): void
    {
        $warehouse = Warehouse::default();
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs', 'is_active' => true]);

        $this->actingAs($this->staff('admin'))
            ->post(route('admin.products.store'), [
                'name' => 'Mango juice 1L',
                'sku' => 'MJ-1L',
                'unit_id' => $unit->id,
                'purchase_price' => '80',
                'base_price' => '100',
                'regular_price' => '120',
                'track_stock' => '1',
                'is_available' => '1',
                'opening_stock' => '24',
                'opening_warehouse_id' => $warehouse->id,
            ])
            ->assertSessionHasNoErrors();

        $product = Product::where('sku', 'MJ-1L')->firstOrFail();
        $this->assertEquals(24, (float) $product->stock_quantity);
        $this->assertEquals(80, (float) $product->purchase_price);
        $this->assertEquals(120, (float) $product->regular_price);
        $this->assertDatabaseHas('warehouse_stocks', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'opening']);

        $this->actingAs($this->staff('admin'))->get(route('admin.products.edit', $product))->assertOk()->assertSee('Mango juice 1L');
    }

    public function test_regular_price_cannot_be_below_sale_price(): void
    {
        $this->actingAs($this->staff('admin'))
            ->post(route('admin.products.store'), ['name' => 'X', 'base_price' => '100', 'regular_price' => '90'])
            ->assertSessionHasErrors('regular_price');
    }
}
