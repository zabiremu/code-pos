<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Goods sent back to the supplier from a GRN. */
class GrnReturn extends Model
{
    protected $fillable = ['return_no', 'grn_id', 'return_date', 'reason', 'total', 'created_by'];

    protected function casts(): array
    {
        return ['return_date' => 'date', 'total' => 'decimal:2'];
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnReturnItem::class);
    }
}
