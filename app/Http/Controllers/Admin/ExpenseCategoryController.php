<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.expense-categories.index', [
            'categories' => ExpenseCategory::withCount('expenses')->withSum('expenses', 'amount')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:expense_categories,name']], ['name.unique' => 'That category already exists.']);
        ExpenseCategory::create($data);

        return back()->with('status', "Category \"{$data['name']}\" added.");
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'name')->ignore($expenseCategory)]], ['name.unique' => 'That category already exists.']);
        $expenseCategory->update($data);

        return back()->with('status', 'Category renamed.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return back()->withErrors(['category' => "\"{$expenseCategory->name}\" has expenses in it. Move them to another category first."]);
        }
        $expenseCategory->delete();

        return back()->with('status', "Category \"{$expenseCategory->name}\" deleted.");
    }
}
