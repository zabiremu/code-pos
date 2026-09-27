<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'mobile' => 'Mobile banking', 'cheque' => 'Cheque', 'other' => 'Other'];

    protected $fillable = ['expense_no', 'expense_category_id', 'expense_date', 'amount', 'method', 'paid_to', 'reference', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }
}
