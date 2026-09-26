<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Floor;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * Covers the status-tab + search filtering added to the orders list
 * (?status=&q=, plus the counts shown on each tab) - the WordPress
 * list-table-style screen replacing the old flat list.
 */
class OrdersIndexTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    private function makeOrder(string $status, ?string $tableLabel = null): Order
    {
        $tableId = null;

        if ($tableLabel) {
            $branch = Branch::firstOrCreate(
                ['name' => 'Main Branch'],
                ['tax_rate' => 10, 'currency' => 'USD', 'is_active' => true],
            );
            $floor = Floor::firstOrCreate(['branch_id' => $branch->id, 'name' => 'Ground Floor'], ['sort_order' => 1]);
            $tableId = DiningTable::create([
                'floor_id' => $floor->id, 'label' => $tableLabel, 'seats' => 4, 'status' => 'occupied',
            ])->id;
        }

        return Order::create([
            'table_id' => $tableId,
            'type' => 'dine_in',
            'guest_count' => 2,
            'status' => $status,
        ]);
    }

    public function test_the_all_tab_shows_open_sent_and_served_but_not_closed(): void
    {
        $this->makeOrder('open');
        $this->makeOrder('sent');
        $this->makeOrder('served');
        $this->makeOrder('closed');

        $response = $this->actingAs($this->staff('waiter'))->get('/pos/orders');

        $response->assertOk();
        $response->assertViewHas('counts', fn ($counts) => $counts['all'] === 3);
    }

    public function test_status_tab_filters_the_list(): void
    {
        $open = $this->makeOrder('open');
        $sent = $this->makeOrder('sent');

        $response = $this->actingAs($this->staff('waiter'))->get('/pos/orders?status=sent');

        $response->assertOk();
        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertSame($sent->id, $orders->first()->id);
    }

    public function test_search_matches_by_table_label(): void
    {
        $this->makeOrder('open', 'T5');
        $this->makeOrder('open', 'T9');

        $response = $this->actingAs($this->staff('waiter'))->get('/pos/orders?q=T5');

        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertSame('T5', $orders->first()->table->label);
    }
}
