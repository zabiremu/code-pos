<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Maps to the `tables` table — named DiningTable in PHP to avoid confusion with "database table". */
class DiningTable extends Model
{
    use HasFactory;

    protected $table = 'tables';

    protected $fillable = ['floor_id', 'label', 'seats', 'status', 'pos_x', 'pos_y'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
