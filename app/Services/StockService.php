<?php

namespace App\Services;

use App\Models\SaleItem;

/** Deducts a product's own stock the moment it's added to a sale. */
class StockService
{
    public function deductForSaleItem(SaleItem $saleItem): void
    {
        $product = $saleItem->product;

        if (! $product->track_stock) {
            return;
        }

        $product->decrement('stock_quantity', $saleItem->quantity);

        $product->stockMovements()->create([
            'user_id' => null,
            'type' => 'sale',
            'qty' => -$saleItem->quantity,
            'note' => "Sale item #{$saleItem->id}",
        ]);
    }

    /** Restores stock when a line item is removed from an open sale before it's billed. */
    public function restoreForRemovedSaleItem(SaleItem $saleItem): void
    {
        $product = $saleItem->product;

        if (! $product->track_stock) {
            return;
        }

        $product->increment('stock_quantity', $saleItem->quantity);

        $product->stockMovements()->create([
            'user_id' => null,
            'type' => 'adjustment',
            'qty' => $saleItem->quantity,
            'note' => "Removed sale item #{$saleItem->id}",
        ]);
    }
}
