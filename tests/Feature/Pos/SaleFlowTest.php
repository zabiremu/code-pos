<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * End-to-end coverage of the core POS loop: open a sale, add an item (stock
 * deducts immediately), bill it, and pay it off - the path every shift
 * actually runs, exercised in one test so a change anywhere in that chain
 * (routes, controllers, BillingService, StockService) shows up here.
 */
class SaleFlowTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_full_sale_lifecycle_from_open_to_paid(): void
    {
        $cashier = $this->staff('cashier');

        Branch::create(['name' => 'Main Branch', 'tax_rate' => 10, 'currency' => 'USD', 'is_active' => true]);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Widget',
            'base_price' => 12.50,
            'track_stock' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_available' => true,
        ]);

        $this->actingAs($cashier);

        // 1. Open a new sale.
        $saleResponse = $this->post('/pos/sales', []);
        $sale = Sale::firstOrFail();
        $saleResponse->assertRedirect(route('pos.sales.show', $sale));
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertSame('open', $sale->status);

        // 2. Add a line item - unit_price is a snapshot of the product's
        // price, and stock is deducted immediately.
        $this->post("/pos/sales/{$sale->id}/items", [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect(route('pos.sales.show', $sale));

        $saleItem = $sale->items()->firstOrFail();
        $this->assertEquals(12.50, (float) $saleItem->unit_price);
        $this->assertSame(2, $saleItem->quantity);
        $this->assertEquals(8, (float) $product->fresh()->stock_quantity);

        // 3. Bill the sale - 10% tax from the branch default.
        $billResponse = $this->post("/pos/sales/{$sale->id}/bills", []);
        $bill = $sale->bills()->firstOrFail();
        $billResponse->assertRedirect(route('pos.bills.show', $bill));

        $this->assertEquals(25.00, (float) $bill->subtotal);   // 2 x 12.50
        $this->assertEquals(2.50, (float) $bill->tax_total);   // 10% of 25.00
        $this->assertEquals(27.50, (float) $bill->grand_total);
        $this->assertSame('unpaid', $bill->status);
        $this->assertSame('billed', $sale->fresh()->status);

        // 4. Pay it off in full - status flips to paid once balanceDue() hits 0.
        $this->post("/pos/bills/{$bill->id}/payments", [
            'method' => 'cash',
            'amount' => 27.50,
        ])->assertRedirect();
        $this->assertSame('paid', $bill->fresh()->status);

        // 5. Close the sale.
        $this->post("/pos/sales/{$sale->id}/close")->assertRedirect(route('pos.sales.index'));
        $this->assertSame('closed', $sale->fresh()->status);
    }

    public function test_a_sale_cannot_be_closed_while_its_bill_is_unpaid(): void
    {
        $cashier = $this->staff('cashier');
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Soup', 'base_price' => 5, 'is_available' => true]);

        $this->actingAs($cashier);
        $this->post('/pos/sales', []);
        $sale = Sale::firstOrFail();

        $this->post("/pos/sales/{$sale->id}/items", ['product_id' => $product->id, 'quantity' => 1]);
        $this->post("/pos/sales/{$sale->id}/bills", []);

        $response = $this->post("/pos/sales/{$sale->id}/close");

        $response->assertSessionHasErrors('sale');
        $this->assertNotSame('closed', $sale->fresh()->status);
    }

    public function test_removing_an_item_from_an_open_sale_restores_its_stock(): void
    {
        $cashier = $this->staff('cashier');
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Widget', 'base_price' => 5,
            'track_stock' => true, 'stock_quantity' => 10, 'is_available' => true,
        ]);

        $this->actingAs($cashier);
        $this->post('/pos/sales', []);
        $sale = Sale::firstOrFail();

        $this->post("/pos/sales/{$sale->id}/items", ['product_id' => $product->id, 'quantity' => 3]);
        $this->assertEquals(7, (float) $product->fresh()->stock_quantity);

        $saleItem = $sale->items()->firstOrFail();
        $this->delete("/pos/sale-items/{$saleItem->id}")->assertRedirect();

        $this->assertEquals(10, (float) $product->fresh()->stock_quantity);
        $this->assertSame(0, $sale->items()->count());
    }
}
