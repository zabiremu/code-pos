<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_LABELS = [
        'opening' => 'Opening stock',
        'sale' => 'Sale',
        'goods_received' => 'Goods received',
        'purchase_return' => 'Returned to supplier',
        'transfer' => 'Transfer',
        'sale_return' => 'Customer return',
        'purchase' => 'Purchase',
        'waste' => 'Waste',
        'adjustment' => 'Adjustment',
    ];

    protected $fillable = ['product_id', 'warehouse_id', 'user_id', 'type', 'qty', 'note', 'reference_type', 'reference_id'];

    protected $casts = ['qty' => 'decimal:3'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
