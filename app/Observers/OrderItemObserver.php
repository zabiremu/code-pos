<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\StockService;

class OrderItemObserver
{
    public function __construct(private StockService $stock)
    {
    }

    /** Deduct ingredient stock the moment an item is marked served (once, via the dirty check). */
    public function updated(OrderItem $orderItem): void
    {
        if ($orderItem->isDirty('status') && $orderItem->status === 'served') {
            $this->stock->deductForServedItem($orderItem);
        }
    }
}
