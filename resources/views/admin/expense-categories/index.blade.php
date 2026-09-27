@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Expense categories">
    <x-back-link :href="route('admin.expenses.index')">All expenses</x-back-link>

    <div class="max-w-2xl space-y-6">
        <form method="POST" action="{{ route('admin.expense-categories.store') }}" class="card p-5 flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[12rem]">
                <label for="new-name" class="field-label">New category</label>
                <input id="new-name" name="name" required maxlength="100" class="input" placeholder="e.g. Advertising">
                @error('name')<p class="text-xs text-primary-600 mt-1.5">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary">Add</button>
        </form>

        <div class="card divide-y divide-zinc-100">
            @foreach ($categories as $c)
                <div class="px-5 py-3 flex flex-wrap items-center gap-3" x-data="{ editing: false }">
                    <div class="flex-1 min-w-0" x-show="!editing">
                        <p class="font-medium">{{ $c->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $c->expenses_count }} {{ \Illuminate\Support\Str::plural('expense', $c->expenses_count) }} &middot; {{ $money($c->expenses_sum_amount) }} in total</p>
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.expense-categories.update', $c) }}" class="flex-1 flex gap-2">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $c->name }}" required maxlength="100" class="input" aria-label="Category name">
                        <button class="btn-primary">Save</button>
                        <button type="button" @click="editing = false" class="btn-secondary">Cancel</button>
                    </form>
                    <div class="flex items-center gap-3 text-sm" x-show="!editing">
                        <button type="button" @click="editing = true" class="text-zinc-500 hover:text-primary-600">Rename</button>
                        @if ($c->expenses_count === 0)
                            <x-delete-button :action="route('admin.expense-categories.destroy', $c)" :confirm="'Delete the category '.$c->name.'?'" />
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
