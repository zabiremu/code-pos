{{--
    Attendance report: every employee's clock-in/out history, filterable by
    employee and by "currently clocked in" - same WordPress list-table shape
    (filter row, real table, zebra rows) as pos/orders/index.blade.php.
--}}
<x-layouts.admin :title="'Attendance'">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold">Attendance</h1>
            <p class="text-sm text-zinc-500 mt-0.5">Clock-in/out history across all employees.</p>
        </div>
        <a href="{{ route('admin.shifts.index', ['open' => 1]) }}"
           class="{{ $onlyOpen ? 'badge-dark' : 'badge-gray' }} badge">
            {{ $openCount }} currently clocked in
        </a>
    </div>

    <form method="GET" class="card p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs font-medium text-zinc-500 block mb-1">Employee</label>
            <select name="user_id" class="input" onchange="this.form.submit()">
                <option value="">All employees</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($userId == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($onlyOpen)
            <input type="hidden" name="open" value="1">
        @endif
        <button class="btn-secondary">Filter</button>
        @if ($userId !== '' || $onlyOpen)
            <a href="{{ route('admin.shifts.index') }}" class="text-sm text-zinc-500 hover:text-primary-600">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-zinc-400 bg-zinc-50/60">
                    <th class="px-5 py-2.5 font-medium">Employee</th>
                    <th class="px-5 py-2.5 font-medium">Clock in</th>
                    <th class="px-5 py-2.5 font-medium">Clock out</th>
                    <th class="px-5 py-2.5 font-medium text-right">Opening</th>
                    <th class="px-5 py-2.5 font-medium text-right">Closing</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr class="even:bg-zinc-50/60 hover:bg-primary-50/40">
                        <td class="px-5 py-2.5">
                            @if ($shift->user)
                                <a href="{{ route('admin.staff.show', $shift->user) }}" class="font-medium hover:text-primary-600">{{ $shift->user->name }}</a>
                            @else
                                <span class="text-zinc-400">Deleted user</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5">{{ $shift->clock_in->format('d M Y, g:i A') }}</td>
                        <td class="px-5 py-2.5">
                            @if ($shift->clock_out)
                                {{ $shift->clock_out->format('d M Y, g:i A') }}
                            @else
                                <span class="badge badge-dark">Still clocked in</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-right">{{ number_format($shift->opening_till, 2) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ $shift->closing_till !== null ? number_format($shift->closing_till, 2) : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-zinc-400">No shifts match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shifts->links() }}
    </div>
</x-layouts.admin>
