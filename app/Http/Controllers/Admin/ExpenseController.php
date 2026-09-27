<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $range = DateRange::fromRequest($request);
        $categoryId = $request->integer('category') ?: null;

        $base = Expense::whereBetween('expense_date', $range->betweenDates())
            ->when($categoryId, fn ($q) => $q->where('expense_category_id', $categoryId));

        return view('admin.expenses.index', [
            'range' => $range,
            'categoryId' => $categoryId,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'expenses' => (clone $base)->with('category')->latest('expense_date')->latest('id')->paginate(25)->withQueryString(),
            'total' => (float) (clone $base)->sum('amount'),
            'byCategory' => (clone $base)->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
                ->groupBy('expense_categories.name')->selectRaw('expense_categories.name, sum(expenses.amount) as total')
                ->orderByDesc('total')->pluck('total', 'name'),
        ]);
    }

    public function create(): View
    {
        return view('admin.expenses.form', [
            'expense' => new Expense(['expense_date' => today(), 'method' => 'cash']),
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = DB::transaction(function () use ($request) {
            $expense = Expense::create($this->validated($request) + ['created_by' => auth()->id()]);
            $expense->update(['expense_no' => 'EXP-'.str_pad((string) $expense->id, 6, '0', STR_PAD_LEFT)]);

            return $expense;
        });

        return redirect()->route('admin.expenses.index')->with('status', "{$expense->expense_no}: ".number_format((float) $expense->amount, 2).' recorded.');
    }

    public function edit(Expense $expense): View
    {
        return view('admin.expenses.form', ['expense' => $expense, 'categories' => ExpenseCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('admin.expenses.index')->with('status', "{$expense->expense_no} updated.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('status', "{$expense->expense_no} deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'method' => ['required', Rule::in(array_keys(Expense::METHODS))],
            'paid_to' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'expense_category_id.required' => 'Choose a category.',
            'expense_date.before_or_equal' => 'The date can\'t be in the future.',
            'amount.min' => 'Enter an amount above zero.',
        ]);
    }
}
