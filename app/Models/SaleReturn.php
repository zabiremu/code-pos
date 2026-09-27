<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    public const REFUND_METHODS = [
        'cash' => 'Cash refund',
        'card' => 'Card refund',
        'mobile_wallet' => 'Mobile pay refund',
        'other' => 'Other refund',
        'adjust_due' => 'Take off what they owe',
    ];

    protected $fillable = ['return_no', 'sale_id', 'bill_id', 'customer_id', 'return_date', 'refund_method', 'restock', 'total', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['return_date' => 'date', 'restock' => 'boolean', 'total' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function refundLabel(): string
    {
        return self::REFUND_METHODS[$this->refund_method] ?? ucfirst($this->refund_method);
    }
}
