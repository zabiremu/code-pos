<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Due collected from a customer, split across their bills as payments rows. */
class CustomerReceipt extends Model
{
    public const METHODS = ['cash' => 'Cash', 'card' => 'Card', 'mobile_wallet' => 'Mobile pay', 'other' => 'Other'];

    protected $fillable = ['receipt_no', 'customer_id', 'receipt_date', 'amount', 'method', 'reference', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }
}
