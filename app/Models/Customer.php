<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'address', 'credit_limit', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function bills(): HasManyThrough
    {
        return $this->hasManyThrough(Bill::class, Sale::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    /** Bills with money still owed, oldest first. */
    public function openBills()
    {
        return $this->bills()->with(['payments', 'saleReturns'])->whereIn('bills.status', ['unpaid', 'partially_paid'])
            ->orderBy('bills.created_at')->orderBy('bills.id')->get()
            ->filter(fn (Bill $b) => $b->balanceDue() > 0.004)->values();
    }

    /** Total the customer owes across all their bills. */
    public function due(): float
    {
        return round($this->openBills()->sum(fn (Bill $b) => $b->balanceDue()), 2);
    }

    public function label(): string
    {
        return $this->phone ? "{$this->name} ({$this->phone})" : $this->name;
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like));
    }
}
