@php $money = fn ($v) => number_format((float) $v, 2); @endphp
<x-layouts.admin title="Expenses">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-zinc-500">Running costs: rent, salaries, bills. They come off profit in the Profit &amp; loss report.</p>
        <div class="flex gap-2">
            <a href="{{ route('admin.expense-categories.index') }}" class="btn-secondary">Categories</a>
            <a href="{{ route('admin.expenses.create') }}" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                Add expense
            </a>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap items-center gap-2 mb-5" x-data="{ preset: @js($range->preset) }">
        <select name="range" x-model="preset" class="input w-auto" aria-label="Period" @change="preset !== 'custom' && $el.form.submit()">
            @foreach (\App\Support\DateRange::PRESETS as $key => $label)
                <option value="{{ $key }}" @selected($range->preset === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <template x-if="preset === 'custom'">
            <span class="flex items-center gap-2">
                <input type="date" name="from" value="{{ $range->from->toDateString() }}" class="input w-auto" aria-label="From">
                <input type="date" name="to" value="{{ $range->to->toDateString() }}" class="input w-auto" aria-label="To">
            </span>
        </template>
        <select name="category" class="input w-auto" aria-label="Category" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected($categoryId === $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <button class="btn-secondary" x-show="preset === 'custom'">Apply</button>
    </form>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        <div class="xl:col-span-2">
            <div class="card overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-zinc-500 border-b border-zinc-100 bg-zinc-50/60">
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Expense</th>
                            <th class="px-5 py-3 font-medium">Paid to</th>
                            <th class="px-5 py-3 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($expenses as $e)
                            <tr class="group hover:bg-primary-50/40 transition-colors">
                                <td class="px-5 py-3 text-zinc-600 whitespace-nowrap align-top">{{ $e->expense_date->format('d M Y') }}</td>
                                <td class="px-5 py-3 align-top">
                                    <a href="{{ route('admin.expenses.edit', $e) }}" class="font-medium text-zinc-900 hover:text-primary-600">{{ $e->category?->name }}</a>
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ $e->expense_no }} &middot; {{ $e->methodLabel() }}{{ $e->notes ? ' - '.\Illuminate\Support\Str::limit($e->notes, 60) : '' }}</div>
                                    <div class="text-xs mt-1 flex gap-3 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
                                        <a href="{{ route('admin.expenses.edit', $e) }}" class="text-zinc-500 hover:text-primary-600">Edit</a>
                                        <x-delete-button :action="route('admin.expenses.destroy', $e)" :confirm="'Delete '.$e->expense_no.'?'" />
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-zinc-600 align-top">{{ $e->paid_to ?: '—' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-medium align-top">{{ $money($e->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-14 text-center text-zinc-500">No expenses in this period. <a href="{{ route('admin.expenses.create') }}" class="text-primary-600 hover:underline underline-offset-4">Add one</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $expenses->links() }}</div>
        </div>

        <aside class="space-y-6">
            <x-stat label="Total spent" :value="$money($total)" :hint="$range->label()" />
            @if ($byCategory->isNotEmpty())
                <section class="card p-5" aria-labelledby="bycat">
                    <h2 id="bycat" class="font-display text-xl mb-3">By category</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($byCategory as $name => $sum)
                            <li>
                                <div class="flex justify-between gap-3"><span>{{ $name }}</span><span class="tabular-nums">{{ $money($sum) }}</span></div>
                                <div class="mt-1.5 h-1.5 rounded-full bg-primary-100 overflow-hidden" aria-hidden="true">
                                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $total > 0 ? max($sum / $total * 100, 2) : 0 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>
