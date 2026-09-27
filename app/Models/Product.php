<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prices: base_price is the SALE price the POS charges; purchase_price is
 * the cost (updated from the latest goods received); regular_price is the
 * optional MRP / price before discount. stock_quantity is the total across
 * all warehouses - per-warehouse numbers live in warehouse_stocks.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'unit_id', 'brand_id', 'name', 'sku', 'description', 'image_path',
        'base_price', 'purchase_price', 'regular_price', 'tax_rate', 'track_stock', 'stock_quantity',
        'low_stock_threshold', 'is_available',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'regular_price' => 'decimal:2',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /** asset() follows the current host, so a wrong APP_URL in .env can't break images. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('uploads/'.$this->image_path) : null;
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_quantity <= $this->low_stock_threshold;
    }

    /** Sale price minus cost, as a % of the sale price. Null when there's no cost yet. */
    public function marginPercent(): ?float
    {
        if ((float) $this->purchase_price <= 0 || (float) $this->base_price <= 0) {
            return null;
        }

        return ((float) $this->base_price - (float) $this->purchase_price) / (float) $this->base_price * 100;
    }

    public function stockIn(int $warehouseId): float
    {
        return (float) ($this->warehouseStocks->firstWhere('warehouse_id', $warehouseId)?->quantity ?? 0);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like));
    }
}
