<?php

namespace Tests\Feature\Admin;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_dashboard_reports_todays_revenue_and_low_stock_count(): void
    {
        Branch::create(['name' => 'Main', 'tax_rate' => 0, 'currency' => 'USD', 'is_active' => true]);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        Product::create(['category_id' => $category->id, 'name' => 'Item', 'base_price' => 10, 'is_available' => true]);

        $sale = Sale::create(['status' => 'billed']);
        Bill::create([
            'sale_id' => $sale->id, 'subtotal' => 40, 'tax_total' => 0,
            'service_charge' => 0, 'discount_total' => 0, 'grand_total' => 40, 'status' => 'paid',
        ]);
        // An unpaid bill on the same day shouldn't count toward today's revenue.
        $sale2 = Sale::create(['status' => 'billed']);
        Bill::create([
            'sale_id' => $sale2->id, 'subtotal' => 999, 'tax_total' => 0,
            'service_charge' => 0, 'discount_total' => 0, 'grand_total' => 999, 'status' => 'unpaid',
        ]);

        Product::create([
            'category_id' => $category->id, 'name' => 'Low one', 'base_price' => 5,
            'track_stock' => true, 'stock_quantity' => 1, 'low_stock_threshold' => 5, 'is_available' => true,
        ]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Fine one', 'base_price' => 5,
            'track_stock' => true, 'stock_quantity' => 10, 'low_stock_threshold' => 5, 'is_available' => true,
        ]);

        $response = $this->actingAs($this->staff('admin'))->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('40.00');
        $response->assertDontSee('999.00');
        // assertViewHas checks the controller's actual data, not rendered
        // text - a bare "1" would match all over the page (sale counts,
        // pagination, etc.) so it's not a safe thing to assertSee() for.
        $response->assertViewHas('todayRevenue', fn ($value) => (float) $value === 40.0);
        $response->assertViewHas('lowStockCount', 1);
    }
}
