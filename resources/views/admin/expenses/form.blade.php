<x-layouts.admin :title="$expense->exists ? 'Edit '.$expense->expense_no : 'Add expense'">
    <x-back-link :href="route('admin.expenses.index')">All expenses</x-back-link>

    <form method="POST" action="{{ $expense->exists ? route('admin.expenses.update', $expense) : route('admin.expenses.store') }}" class="card max-w-2xl">
        @csrf
        @if ($expense->exists) @method('PUT') @endif
        <div class="px-5">
            <x-form-row label="Category" for="expense_category_id" :required="true">
                <div class="flex flex-wrap items-center gap-3">
                    <select id="expense_category_id" name="expense_category_id" required class="input" style="max-width:20rem">
                        <option value="">Choose a category</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.expense-categories.index') }}" class="text-sm text-primary-600 hover:underline underline-offset-4">Manage categories</a>
                </div>
            </x-form-row>
            <x-form-row label="Amount" for="amount" :required="true">
                <input id="amount" type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount', $expense->amount) }}" class="input" style="max-width:12rem" autofocus>
            </x-form-row>
            <x-form-row label="Date" for="expense_date" :required="true">
                <input id="expense_date" type="date" name="expense_date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" class="input" style="max-width:12rem">
            </x-form-row>
            <x-form-row label="Paid by" for="method" :required="true">
                <select id="method" name="method" class="input" style="max-width:16rem">
                    @foreach (\App\Models\Expense::METHODS as $key => $label)
                        <option value="{{ $key }}" @selected(old('method', $expense->method) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-form-row>
            <x-form-row label="Paid to" for="paid_to" hint="e.g. landlord, DESCO, staff name">
                <input id="paid_to" name="paid_to" maxlength="150" value="{{ old('paid_to', $expense->paid_to) }}" class="input">
            </x-form-row>
            <x-form-row label="Reference" for="reference" hint="Bill number, transaction ID">
                <input id="reference" name="reference" maxlength="100" value="{{ old('reference', $expense->reference) }}" class="input">
            </x-form-row>
            <x-form-row label="Notes" for="notes">
                <textarea id="notes" name="notes" rows="2" class="input">{{ old('notes', $expense->notes) }}</textarea>
            </x-form-row>
        </div>
        <div class="px-5 py-4 border-t border-zinc-100 flex gap-2">
            <button class="btn-primary">{{ $expense->exists ? 'Save changes' : 'Add expense' }}</button>
            <a href="{{ route('admin.expenses.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>

    @if ($expense->exists)
        <div class="card mt-6 p-5 flex flex-wrap items-center justify-between gap-4 max-w-2xl">
            <p class="text-sm text-zinc-500">Recorded{{ $expense->creator ? ' by '.$expense->creator->name : '' }} on {{ $expense->created_at->format('d M Y') }}.</p>
            <x-delete-button variant="danger" label="Delete expense" :action="route('admin.expenses.destroy', $expense)" :confirm="'Delete '.$expense->expense_no.'?'" />
        </div>
    @endif
</x-layouts.admin>
