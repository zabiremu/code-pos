<?php

namespace App\Services;

use App\Models\OrderItem;

/** Deducts recipe ingredients from stock when an order item is served. */
class StockService
{
    public function deductForServedItem(OrderItem $orderItem): void
    {
        $menuItem = $orderItem->menuItem()->with('ingredients')->first();

        foreach ($menuItem->ingredients as $ingredient) {
            $qtyUsed = $ingredient->pivot->qty * $orderItem->quantity;

            $ingredient->decrement('stock_qty', $qtyUsed);

            $ingredient->stockMovements()->create([
                'user_id' => null,
                'type' => 'sale',
                'qty' => -$qtyUsed,
                'note' => "Order item #{$orderItem->id}",
            ]);
        }
    }
}
