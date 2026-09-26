<?php

namespace Tests\Feature\Pos;

use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * Covers the status-tab + search filtering on the sales list (?status=&q=,
 * plus the counts shown on each tab) - the WordPress list-table-style
 * screen.
 */
class SalesIndexTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_the_all_tab_shows_open_and_billed_but_not_closed(): void
    {
        Sale::create(['status' => 'open']);
        Sale::create(['status' => 'billed']);
        Sale::create(['status' => 'closed']);

        $response = $this->actingAs($this->staff('cashier'))->get('/pos/sales');

        $response->assertOk();
        $response->assertViewHas('counts', fn ($counts) => $counts['all'] === 2);
    }

    public function test_status_tab_filters_the_list(): void
    {
        $open = Sale::create(['status' => 'open']);
        $billed = Sale::create(['status' => 'billed']);

        $response = $this->actingAs($this->staff('cashier'))->get('/pos/sales?status=billed');

        $response->assertOk();
        $sales = $response->viewData('sales');
        $this->assertCount(1, $sales);
        $this->assertSame($billed->id, $sales->first()->id);
    }

    public function test_search_matches_by_sale_id(): void
    {
        $first = Sale::create(['status' => 'open']);
        Sale::create(['status' => 'open']);

        $response = $this->actingAs($this->staff('cashier'))->get("/pos/sales?q={$first->id}");

        $sales = $response->viewData('sales');
        $this->assertCount(1, $sales);
        $this->assertSame($first->id, $sales->first()->id);
    }
}
