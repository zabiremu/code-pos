<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A purchase order. Stock only moves when goods are received against it (Grn). */
class Purchase extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'ordered' => 'Ordered',
        'partial' => 'Partly received',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'reference_no', 'supplier_id', 'warehouse_id', 'purchase_date', 'expected_date', 'status',
        'subtotal', 'discount', 'tax', 'shipping', 'total', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        return $this->hasMany(PurchaseItem::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(Grn::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'received' => 'badge-green',
            'partial' => 'badge-dark',
            'cancelled' => 'badge-red',
            default => 'badge-gray',
        };
    }

    /** Header fields and lines can only change before any goods arrive. */
    public function isEditable(): bool
    {
        return ! in_array($this->status, ['partial', 'received', 'cancelled'], true) && ! $this->grns()->exists();
    }

    public function canReceive(): bool
    {
        return in_array($this->status, ['draft', 'ordered', 'partial'], true);
    }
}
