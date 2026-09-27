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

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /** GRN total less anything returned from it. */
    public function payable(): float
    {
        return round((float) $this->total - (float) $this->returns()->sum('total'), 2);
    }

    public function paid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function due(): float
    {
        return round($this->payable() - $this->paid(), 2);
    }

    /** Once anything has been returned from it or paid against it, a GRN is locked. */
    public function isEditable(): bool
    {
        return ! $this->returns()->exists() && ! $this->payments()->exists();
    }

    public function hasReturnableItems(): bool
    {
        return $this->items->contains(fn (GrnItem $item) => $item->returnableQuantity() > 0);
    }
}
