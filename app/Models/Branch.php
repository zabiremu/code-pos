<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'tax_rate', 'currency', 'is_active'];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
