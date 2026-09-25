<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'clock_in', 'clock_out', 'opening_till', 'closing_till'];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'opening_till' => 'decimal:2',
        'closing_till' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
