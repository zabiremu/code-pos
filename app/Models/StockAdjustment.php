<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    public const REASONS = [
        'damaged' => 'Damaged',
        'expired' => 'Expired',
        'lost' => 'Lost or stolen',
        'count' => 'Stock count correction',
        'other' => 'Other',
    ];

    protected $fillable = ['adjustment_no', 'warehouse_id', 'adjustment_date', 'reason', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['adjustment_date' => 'date'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }

    /** Signed value of the change at cost: negative = stock value written off. */
    public function value(): float
    {
        return (float) $this->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_cost);
    }
}
