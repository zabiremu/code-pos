<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'phone', 'address', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /** The default warehouse (sales deduct from it). Creates one if none exist. */
    public static function default(): self
    {
        return static::where('is_default', true)->first()
            ?? static::where('is_active', true)->orderBy('id')->first()
            ?? static::create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
    }

    public function label(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }
}
