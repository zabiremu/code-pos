{{--
    Full employee profile - same WordPress form-table shape as the
    self-service profile page (resources/views/profile/edit.blade.php),
    plus a shift/attendance history table below it so a manager can see
    someone's clock-in record without leaving their profile.
--}}
<x-layouts.admin :title="$staffMember->name">
    <div class="mb-5">
        <a href="{{ route('admin.staff.index') }}" class="text-sm text-zinc-500 hover:text-primary-600">&larr; All staff</a>
    </div>

    <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- Profile + role --}}
        <div class="card">
            <div class="card-body !pb-0">
                <h2 class="text-base font-semibold">Profile</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Contact details, branch, and role.</p>
            </div>

            <form method="POST" action="{{ route('admin.staff.update', $staffMember) }}">
                @csrf
                @method('PUT')

                <div class="px-5">
                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <span class="w-16 h-16 rounded-full bg-primary-600 text-white font-semibold text-xl flex items-center justify-center">
                                {{ strtoupper(substr($staffMember->name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <span class="badge {{ $staffMember->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $staffMember->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="name" class="text-sm font-medium text-zinc-700">Name</label>
                        </div>
                        <div>
                            <input id="name" type="text" name="name" value="{{ old('name', $staffMember->name) }}" required class="w-full input">
                            @error('name')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="email" class="text-sm font-medium text-zinc-700">Email address</label>
                            <p class="text-xs text-zinc-400 mt-1">Used to log in - must stay unique.</p>
                        </div>
                        <div>
                            <input id="email" type="email" name="email" value="{{ old('email', $staffMember->email) }}" required class="w-full input">
                            @error('email')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="phone" class="text-sm font-medium text-zinc-700">Phone</label>
                        </div>
                        <div>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $staffMember->phone) }}" class="w-full input">
                            @error('phone')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="branch_id" class="text-sm font-medium text-zinc-700">Branch</label>
                        </div>
                        <div>
                            <select id="branch_id" name="branch_id" class="w-full input">
                                <option value="">- Unassigned -</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $staffMember->branch_id) == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="role" class="text-sm font-medium text-zinc-700">Role</label>
                        </div>
                        <div>
                            <select id="role" name="role" required class="w-full input">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected(old('role', $staffMember->roles->first()?->name) === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label class="text-sm font-medium text-zinc-700">Account status</label>
                        </div>
                        <div>
                            <input type="hidden" name="is_active" value="0">
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-600">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-zinc-300" @checked(old('is_active', $staffMember->is_active))>
                                Active - can log in and work shifts
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-zinc-100">
                    <button class="btn-primary">Save changes</button>
                </div>
            </form>
        </div>

        {{-- Shift / attendance history --}}
        <div class="card">
            <div class="card-body !pb-0">
                <h2 class="text-base font-semibold">Shift history</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Clock-in/out records, most recent first.</p>
            </div>

            @if ($shifts->isEmpty())
                <p class="px-5 py-6 text-sm text-zinc-500">No shifts recorded yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-400 border-t border-zinc-100">
                            <th class="px-5 py-2 font-medium">Clock in</th>
                            <th class="px-5 py-2 font-medium">Clock out</th>
                            <th class="px-5 py-2 font-medium text-right">Opening</th>
                            <th class="px-5 py-2 font-medium text-right">Closing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shifts as $shift)
                            <tr class="border-t border-zinc-100">
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
                        @endforeach
                    </tbody>
                </table>
                <div class="px-5 py-4 border-t border-zinc-100">
                    {{ $shifts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
