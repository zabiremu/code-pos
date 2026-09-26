<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Maps to the `tables` table — named DiningTable in PHP to avoid confusion with "database table". */
class DiningTable extends Model
{
    use HasFactory;

    protected $table = 'tables';

    protected $fillable = ['floor_id', 'label', 'seats', 'status', 'pos_x', 'pos_y', 'qr_code'];

    protected static function booted(): void
    {
        // Every table needs a QR token for the public ordering page - generate
        // one automatically so TableController::store doesn't have to know
        // this feature exists.
        static::creating(function (DiningTable $table) {
            $table->qr_code ??= Str::random(32);
        });
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
