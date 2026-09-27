<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Goods Received Note: stock arriving into a warehouse. */
class Grn extends Model
{
    protected $fillable = [
        'grn_no', 'purchase_id', 'supplier_id', 'warehouse_id', 'received_date',
        'supplier_invoice_no', 'total', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['received_date' => 'date', 'total' => 'decimal:2'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
        return $this->hasMany(GrnItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(GrnReturn::class);
    }

    /** Once anything has been returned from it, a GRN is locked. */
    public function isEditable(): bool
    {
        return ! $this->returns()->exists();
    }

    public function hasReturnableItems(): bool
    {
        return $this->items->contains(fn (GrnItem $item) => $item->returnableQuantity() > 0);
    }
}
