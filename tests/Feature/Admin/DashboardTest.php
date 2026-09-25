<?php

namespace Tests\Feature\Admin;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Order;
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
        $menuItem = MenuItem::create(['category_id' => $category->id, 'name' => 'Item', 'base_price' => 10, 'is_available' => true]);

        $order = Order::create(['type' => 'takeaway', 'guest_count' => 1, 'status' => 'billed']);
        Bill::create([
            'order_id' => $order->id, 'subtotal' => 40, 'tax_total' => 0,
            'service_charge' => 0, 'discount_total' => 0, 'grand_total' => 40, 'status' => 'paid',
        ]);
        // An unpaid bill on the same day shouldn't count toward today's revenue.
        $order2 = Order::create(['type' => 'takeaway', 'guest_count' => 1, 'status' => 'billed']);
        Bill::create([
            'order_id' => $order2->id, 'subtotal' => 999, 'tax_total' => 0,
            'service_charge' => 0, 'discount_total' => 0, 'grand_total' => 999, 'status' => 'unpaid',
        ]);

        Ingredient::create(['name' => 'Low one', 'unit' => 'kg', 'stock_qty' => 1, 'low_stock_threshold' => 5]);
        Ingredient::create(['name' => 'Fine one', 'unit' => 'kg', 'stock_qty' => 10, 'low_stock_threshold' => 5]);

        $response = $this->actingAs($this->staff('admin'))->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('40.00');
        $response->assertDontSee('999.00');
        // assertViewHas checks the controller's actual data, not rendered
        // text - a bare "1" would match all over the page (order counts,
        // pagination, etc.) so it's not a safe thing to assertSee() for.
        $response->assertViewHas('todayRevenue', fn ($value) => (float) $value === 40.0);
        $response->assertViewHas('lowStockCount', 1);
    }
}
