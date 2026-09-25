<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['bill_id', 'received_by', 'method', 'amount', 'reference'];

    protected $casts = ['amount' => 'decimal:2'];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
