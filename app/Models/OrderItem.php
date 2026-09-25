<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'menu_item_id', 'item_variant_id', 'quantity',
        'unit_price', 'kitchen_station', 'status', 'notes',
    ];

    protected $casts = ['unit_price' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function modifiers(): BelongsToMany
    {
        return $this->belongsToMany(Modifier::class, 'order_item_modifier')->withPivot('price_delta');
    }

    public function lineTotal(): float
    {
        $modifiersTotal = $this->modifiers->sum('pivot.price_delta');

        return ((float) $this->unit_price + $modifiersTotal) * $this->quantity;
    }
}
