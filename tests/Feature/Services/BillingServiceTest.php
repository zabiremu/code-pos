<?php

namespace Tests\Feature\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Sale;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BillingService owns the actual money math, so it gets dedicated coverage
 * beyond the one happy-path bill in Feature\Pos\SaleFlowTest: per-item tax
 * overrides, discounts (percent and fixed, capped at the subtotal), service
 * charges, and split billing by a subset of sale items.
 */
class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function saleWithItem(float $basePrice, ?float $itemTaxRate = null, float $branchTaxRate = 10, int $quantity = 1): Sale
    {
        Branch::create(['name' => 'Main Branch', 'tax_rate' => $branchTaxRate, 'currency' => 'USD', 'is_active' => true]);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Item',
            'base_price' => $basePrice,
            'tax_rate' => $itemTaxRate,
            'is_available' => true,
        ]);

        $sale = Sale::create(['status' => 'open']);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $basePrice,
        ]);

        return $sale;
    }

    public function test_uses_the_branch_default_tax_rate_when_the_item_has_none(): void
    {
        $sale = $this->saleWithItem(basePrice: 100, itemTaxRate: null, branchTaxRate: 15);

        $bill = app(BillingService::class)->createBill($sale);

        $this->assertEquals(100.00, (float) $bill->subtotal);
        $this->assertEquals(15.00, (float) $bill->tax_total);
        $this->assertEquals(115.00, (float) $bill->grand_total);
    }

    public function test_a_products_own_tax_rate_overrides_the_branch_default(): void
    {
        // Guards the explicit `!== null` check in BillingService::createBill() -
        // a naive falsy check there would wrongly fall back to the branch rate
        // whenever a product's own tax_rate was set to exactly 0.
        $sale = $this->saleWithItem(basePrice: 100, itemTaxRate: 5, branchTaxRate: 15);

        $bill = app(BillingService::class)->createBill($sale);

        $this->assertEquals(5.00, (float) $bill->tax_total); // 5%, not the branch's 15%
    }

    public function test_percent_discount_reduces_the_grand_total(): void
    {
        $sale = $this->saleWithItem(basePrice: 100, itemTaxRate: 0, branchTaxRate: 0);
        $discount = Discount::create([
            'name' => '10% off', 'code' => 'TENOFF', 'type' => 'percent', 'value' => 10, 'is_active' => true,
        ]);

        $bill = app(BillingService::class)->createBill($sale, discount: $discount);

        $this->assertEquals(10.00, (float) $bill->discount_total);
        $this->assertEquals(90.00, (float) $bill->grand_total);
    }

    public function test_fixed_discount_never_takes_the_total_below_zero(): void
    {
        $sale = $this->saleWithItem(basePrice: 20, itemTaxRate: 0, branchTaxRate: 0);
        $discount = Discount::create([
            'name' => 'Big fixed discount', 'code' => 'BIG50', 'type' => 'fixed', 'value' => 50, 'is_active' => true,
        ]);

        $bill = app(BillingService::class)->createBill($sale, discount: $discount);

        $this->assertEquals(20.00, (float) $bill->discount_total); // capped at the subtotal
        $this->assertEquals(0.00, (float) $bill->grand_total);
    }

    public function test_an_inactive_discount_is_ignored(): void
    {
        $sale = $this->saleWithItem(basePrice: 100, itemTaxRate: 0, branchTaxRate: 0);
        $discount = Discount::create([
            'name' => 'Expired', 'code' => 'OLD', 'type' => 'percent', 'value' => 50, 'is_active' => false,
        ]);

        $bill = app(BillingService::class)->createBill($sale, discount: $discount);

        $this->assertEquals(0.00, (float) $bill->discount_total);
        $this->assertEquals(100.00, (float) $bill->grand_total);
    }

    public function test_service_charge_is_added_on_top_of_the_subtotal(): void
    {
        $sale = $this->saleWithItem(basePrice: 100, itemTaxRate: 0, branchTaxRate: 0);

        $bill = app(BillingService::class)->createBill($sale, serviceChargeRate: 5);

        $this->assertEquals(5.00, (float) $bill->service_charge);
        $this->assertEquals(105.00, (float) $bill->grand_total);
    }

    public function test_split_billing_only_charges_the_selected_items(): void
    {
        $sale = $this->saleWithItem(basePrice: 10, itemTaxRate: 0, branchTaxRate: 0, quantity: 1);
        // A second, separate line item on the same sale that we will leave unbilled.
        $sale->items()->create([
            'product_id' => $sale->items()->first()->product_id,
            'quantity' => 1,
            'unit_price' => 999,
        ]);

        $firstItemId = $sale->items()->first()->id;

        $bill = app(BillingService::class)->createBill($sale, itemIds: collect([$firstItemId]));

        $this->assertEquals(10.00, (float) $bill->subtotal); // the 999 item was excluded
    }

    public function test_recording_a_full_payment_marks_the_bill_paid(): void
    {
        $sale = $this->saleWithItem(basePrice: 50, itemTaxRate: 0, branchTaxRate: 0);
        $bill = app(BillingService::class)->createBill($sale);

        app(BillingService::class)->recordPayment($bill, 'cash', 50, null, null);

        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_recording_a_partial_payment_marks_the_bill_partially_paid(): void
    {
        $sale = $this->saleWithItem(basePrice: 50, itemTaxRate: 0, branchTaxRate: 0);
        $bill = app(BillingService::class)->createBill($sale);

        app(BillingService::class)->recordPayment($bill, 'cash', 20, null, null);

        $this->assertSame('partially_paid', $bill->fresh()->status);
        $this->assertEquals(30.00, $bill->fresh()->balanceDue());
    }
}
