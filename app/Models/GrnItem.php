<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    protected $fillable = ['grn_id', 'purchase_item_id', 'product_id', 'quantity', 'unit_cost', 'line_total', 'returned_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
            'returned_quantity' => 'decimal:3',
        ];
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function returnableQuantity(): float
    {
        return max((float) $this->quantity - (float) $this->returned_quantity, 0);
    }
}
