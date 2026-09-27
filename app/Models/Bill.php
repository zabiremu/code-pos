<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'discount_id', 'subtotal', 'tax_total',
        'service_charge', 'discount_total', 'grand_total', 'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    /** Returns taken off what's owed on this bill (not money handed back). */
    public function dueAdjustedByReturns(): float
    {
        return $this->relationLoaded('saleReturns')
            ? (float) $this->saleReturns->where('refund_method', 'adjust_due')->sum('total')
            : (float) $this->saleReturns()->where('refund_method', 'adjust_due')->sum('total');
    }

    public function amountPaid(): float
    {
        return $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return round((float) $this->grand_total - $this->amountPaid() - $this->dueAdjustedByReturns(), 2);
    }

    /** Re-derives unpaid / partially_paid / paid from payments and due adjustments. */
    public function refreshStatus(): void
    {
        $this->unsetRelation('payments');
        $this->unsetRelation('saleReturns');
        $due = $this->balanceDue();
        $settled = (float) $this->grand_total - $due;

        $this->update(['status' => $due <= 0.004 ? 'paid' : ($settled > 0.004 ? 'partially_paid' : 'unpaid')]);
    }
}
