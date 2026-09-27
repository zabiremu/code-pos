<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    public const METHODS = [
        'cash' => 'Cash',
        'bank' => 'Bank transfer',
        'mobile' => 'Mobile banking (bKash, Nagad...)',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    protected $fillable = ['payment_no', 'supplier_id', 'grn_id', 'payment_date', 'amount', 'method', 'reference', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }
}
