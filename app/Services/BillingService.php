<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\Sale;
use Illuminate\Support\Collection;

/**
 * Turns a sale's items into one or more bills. Split-by-item support:
 * pass a subset of sale item IDs to bill only those (the rest stay
 * billable later); omit it to bill everything on the sale.
 */
class BillingService
{
    public function createBill(Sale $sale, ?Collection $itemIds = null, ?Discount $discount = null, float $serviceChargeRate = 0): Bill
    {
        $items = $sale->items()
            ->when($itemIds, fn ($q) => $q->whereIn('id', $itemIds))
            ->get();

        $subtotal = $items->sum(fn ($item) => $item->lineTotal());

        // Single-branch v1 (see the build plan): a product's own tax_rate
        // overrides the one branch's default rate when set.
        $defaultTaxRate = (float) (Branch::first()?->tax_rate ?? 0);

        $taxTotal = $items->sum(function ($item) use ($defaultTaxRate) {
            $rate = $item->product->tax_rate !== null ? (float) $item->product->tax_rate : $defaultTaxRate;

            return $item->lineTotal() * ($rate / 100);
        });

        $discountTotal = 0;
        if ($discount && $discount->isValidNow()) {
            $discountTotal = $discount->type === 'percent'
                ? $subtotal * ($discount->value / 100)
                : min($discount->value, $subtotal);
        }

        $serviceCharge = $subtotal * ($serviceChargeRate / 100);
        $grandTotal = $subtotal + $taxTotal + $serviceCharge - $discountTotal;

        return $sale->bills()->create([
            'discount_id' => $discount?->id,
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'service_charge' => round($serviceCharge, 2),
            'discount_total' => round($discountTotal, 2),
            'grand_total' => round(max($grandTotal, 0), 2),
            'status' => 'unpaid',
        ]);
    }

    public function recordPayment(Bill $bill, string $method, float $amount, ?string $reference, ?int $receivedBy): void
    {
        $bill->payments()->create([
            'method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'received_by' => $receivedBy,
        ]);

        $bill->refresh();
        $bill->update([
            'status' => $bill->balanceDue() <= 0 ? 'paid' : 'partially_paid',
        ]);
    }
}
