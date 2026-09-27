<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnReturnItem extends Model
{
    protected $fillable = ['grn_return_id', 'grn_item_id', 'product_id', 'quantity', 'unit_cost', 'line_total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2'];
    }

    public function grnReturn(): BelongsTo
    {
        return $this->belongsTo(GrnReturn::class);
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GrnItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
