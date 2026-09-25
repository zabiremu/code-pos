<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVariant extends Model
{
    use HasFactory;

    protected $fillable = ['menu_item_id', 'name', 'price_delta', 'is_default'];

    protected $casts = [
        'price_delta' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
