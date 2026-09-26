<x-layouts.admin :title="'Staff'">
    <form method="POST" action="{{ route('admin.staff.store') }}" class="card p-5 mb-6 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        @csrf
        <input type="text" name="name" placeholder="Name" required class="input">
        <input type="email" name="email" placeholder="Email" required class="input">
        <input type="text" name="phone" placeholder="Phone (optional)" class="input">
        <select name="branch_id" class="input">
            <option value="">- Branch -</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
        <input type="password" name="password" placeholder="Password" required class="input">
        <select name="role" required class="input">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ $role->label() }}</option>
            @endforeach
        </select>
        <button class="btn-primary col-span-2 md:col-span-1">Add staff</button>
    </form>

    <div class="card divide-y">
        @foreach ($staff as $user)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">
                        <a href="{{ route('admin.staff.show', $user) }}" class="hover:text-primary-600">{{ $user->name }}</a>
                        <span class="text-xs text-zinc-400">{{ $user->email }}</span>
                        @unless ($user->is_active)
                            <span class="badge badge-red ml-1">Inactive</span>
                        @endunless
                    </p>
                    <p class="text-xs text-zinc-500">
                        {{ $user->roles->pluck('name')->join(', ') }}
                        @if ($user->branch)
                            &middot; {{ $user->branch->name }}
                        @endif
                        @if ($user->phone)
                            &middot; {{ $user->phone }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('admin.staff.show', $user) }}" class="btn-ghost">Edit</a>
                    <form method="POST" action="{{ route('admin.staff.destroy', $user) }}" onsubmit="return confirm('Remove this staff member?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
