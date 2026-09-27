<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'company_name', 'phone', 'email', 'tax_number', 'address', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function grns(): HasMany
    {
        return $this->hasMany(Grn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * What the shop owes this supplier: goods received, minus goods sent
     * back, minus payments. Negative means the supplier owes the shop
     * (overpaid / advance).
     */
    public function balance(): float
    {
        $received = (float) $this->grns()->sum('total');
        $returned = (float) GrnReturn::whereIn('grn_id', $this->grns()->select('id'))->sum('total');
        $paid = (float) $this->payments()->sum('amount');

        return round($received - $returned - $paid, 2);
    }

    /** Company name when there is one, otherwise the contact name. */
    public function displayName(): string
    {
        return $this->company_name ?: $this->name;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('company_name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->orWhere('email', 'like', $like));
    }
}
