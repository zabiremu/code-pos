<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Floor;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR self-ordering (CodeCanyon differentiator feature) - no auth required,
 * so this deliberately does NOT use CreatesStaff/actingAs. Covers: menu
 * page access by qr_code (not by numeric id), cart submission creating a
 * pending order, and reusing an already-open tab instead of opening a
 * second one.
 */
class PublicOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function makeTableWithMenu(): array
    {
        $branch = Branch::create(['name' => 'Main Branch', 'tax_rate' => 10, 'currency' => 'USD', 'is_active' => true]);
        $floor = Floor::create(['branch_id' => $branch->id, 'name' => 'Ground Floor', 'sort_order' => 1]);
        $table = DiningTable::create(['floor_id' => $floor->id, 'label' => 'T1', 'seats' => 4, 'status' => 'free']);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $menuItem = MenuItem::create([
            'category_id' => $category->id, 'name' => 'Grilled Chicken', 'base_price' => 12.50, 'is_available' => true,
        ]);

        return [$table, $menuItem];
    }

    public function test_table_gets_a_qr_code_automatically_on_creation(): void
    {
        [$table] = $this->makeTableWithMenu();

        $this->assertNotNull($table->qr_code);
        $this->assertSame(32, strlen($table->qr_code));
    }

    public function test_the_menu_page_is_reachable_without_logging_in(): void
    {
        [$table] = $this->makeTableWithMenu();

        $this->get("/order/{$table->qr_code}")
            ->assertOk()
            ->assertSee('Grilled Chicken');
    }

    public function test_the_menu_page_404s_for_a_wrong_qr_code(): void
    {
        $this->makeTableWithMenu();

        $this->get('/order/not-a-real-token')->assertNotFound();
    }

    public function test_submitting_a_cart_creates_a_pending_order_and_occupies_the_table(): void
    {
        [$table, $menuItem] = $this->makeTableWithMenu();

        $response = $this->post("/order/{$table->qr_code}", [
            'cart' => json_encode([
                ['menu_item_id' => $menuItem->id, 'quantity' => 2],
            ]),
            'guest_name' => 'Rahim',
        ]);

        $response->assertRedirect(route('order.menu', $table));

        $order = Order::firstOrFail();
        $this->assertSame($table->id, $order->table_id);
        $this->assertSame('open', $order->status);
        $this->assertNull($order->waiter_id);
        $this->assertSame('occupied', $table->fresh()->status);

        $item = $order->items()->firstOrFail();
        $this->assertSame('pending', $item->status);
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(12.50, (float) $item->unit_price);
    }

    public function test_a_second_scan_adds_to_the_same_open_order_instead_of_opening_a_new_one(): void
    {
        [$table, $menuItem] = $this->makeTableWithMenu();

        $this->post("/order/{$table->qr_code}", [
            'cart' => json_encode([['menu_item_id' => $menuItem->id, 'quantity' => 1]]),
        ]);
        $this->post("/order/{$table->qr_code}", [
            'cart' => json_encode([['menu_item_id' => $menuItem->id, 'quantity' => 1]]),
        ]);

        $this->assertSame(1, Order::count());
        $this->assertSame(2, Order::first()->items()->count());
    }

    public function test_an_empty_cart_is_rejected(): void
    {
        [$table] = $this->makeTableWithMenu();

        $this->post("/order/{$table->qr_code}", ['cart' => json_encode([])])
            ->assertSessionHasErrors();

        $this->assertSame(0, Order::count());
    }
}
