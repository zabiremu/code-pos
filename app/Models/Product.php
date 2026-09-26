<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'image_path',
        'base_price', 'tax_rate', 'track_stock', 'stock_quantity',
        'low_stock_threshold', 'is_available',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'track_stock' => 'boolean',
        'stock_quantity' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_quantity <= $this->low_stock_threshold;
    }
}
