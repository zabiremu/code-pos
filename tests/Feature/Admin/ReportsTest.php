<?php

namespace Tests\Feature\Admin;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_expenses_crud(): void
    {
        $admin = $this->staff('admin');
        $rent = ExpenseCategory::firstOrCreate(['name' => 'Rent']);

        $this->actingAs($admin)->post(route('admin.expenses.store'), [
            'expense_category_id' => $rent->id, 'amount' => '15000', 'expense_date' => today()->toDateString(), 'method' => 'bank', 'paid_to' => 'Landlord',
        ])->assertRedirect(route('admin.expenses.index'));

        $expense = Expense::firstOrFail();
        $this->assertStringStartsWith('EXP-', $expense->expense_no);

        $this->actingAs($admin)->get(route('admin.expenses.index'))->assertOk()->assertSee('15,000.00');
        $this->actingAs($admin)->put(route('admin.expenses.update', $expense), [
            'expense_category_id' => $rent->id, 'amount' => '16000', 'expense_date' => today()->toDateString(), 'method' => 'bank',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(16000, (float) $expense->fresh()->amount);

        // A category in use can't be deleted.
        $this->actingAs($admin)->delete(route('admin.expense-categories.destroy', $rent))->assertSessionHasErrors('category');
        $this->actingAs($admin)->delete(route('admin.expenses.destroy', $expense))->assertRedirect();
        $this->assertSame(0, Expense::count());
    }

    public function test_profit_and_loss_uses_cost_at_time_of_sale(): void
    {
        $admin = $this->staff('admin');
        Branch::create(['name' => 'Main', 'tax_rate' => 10, 'currency' => 'BDT', 'is_active' => true]);
        $product = Product::create(['name' => 'Bag', 'base_price' => 100, 'purchase_price' => 60, 'track_stock' => true, 'is_available' => true]);
        app(StockService::class)->adjust($product, Warehouse::default()->id, 10, 'opening');

        // Sell 3 at 100 (+10% tax): sales 300, cost 180.
        $this->actingAs($admin)->post(route('pos.register.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 3]], 'method' => 'card',
        ])->assertSessionHasNoErrors();

        // Cost goes up later - the sale above must still count at 60.
        $product->update(['purchase_price' => 90]);

        Expense::create(['expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Rent'])->id, 'expense_date' => today(), 'amount' => 50, 'method' => 'cash']);

        $response = $this->actingAs($admin)->get(route('admin.reports.profit-loss', ['range' => 'today']))->assertOk();
        $response->assertViewHas('sales', fn ($v) => abs($v - 300) < 0.01);
        $response->assertViewHas('cogs', fn ($v) => abs($v - 180) < 0.01);
        $response->assertViewHas('grossProfit', fn ($v) => abs($v - 120) < 0.01);
        $response->assertViewHas('netProfit', fn ($v) => abs($v - 70) < 0.01);
        $response->assertViewHas('tax', fn ($v) => abs($v - 30) < 0.01);

        $this->assertEquals(330, (float) Bill::firstOrFail()->grand_total);
    }

    public function test_stock_value_report(): void
    {
        $product = Product::create(['name' => 'Bag', 'base_price' => 100, 'purchase_price' => 60, 'track_stock' => true, 'is_available' => true]);
        app(StockService::class)->adjust($product, Warehouse::default()->id, 5, 'opening');

        $this->actingAs($this->staff('admin'))->get(route('admin.reports.stock-value'))
            ->assertOk()
            ->assertViewHas('cost', 300.0)
            ->assertViewHas('retail', 500.0);
    }

    public function test_all_report_pages_render_for_each_period(): void
    {
        $admin = $this->staff('manager');
        foreach (['today', 'this_week', 'last_month', 'this_year'] as $range) {
            foreach (['admin.reports.sales', 'admin.reports.profit-loss', 'admin.reports.purchases'] as $route) {
                $this->actingAs($admin)->get(route($route, ['range' => $range]))->assertOk();
            }
        }
        $this->actingAs($admin)->get(route('admin.reports.sales', ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-31']))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.low-stock'))->assertOk();
        $this->actingAs($admin)->get(route('admin.expense-categories.index'))->assertOk();
        $this->actingAs($this->staff('cashier'))->get(route('admin.reports.profit-loss'))->assertForbidden();
    }
}
