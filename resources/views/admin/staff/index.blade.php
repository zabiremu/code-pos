<x-layouts.admin :title="'Staff'">
    <form method="POST" action="{{ route('admin.staff.store') }}" class="card p-5 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
        @csrf
        <input type="text" name="name" placeholder="Name" required class="input">
        <input type="email" name="email" placeholder="Email" required class="input">
        <input type="password" name="password" placeholder="Password" required class="input">
        <select name="role" required class="input">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ $role->label() }}</option>
            @endforeach
        </select>
        <button class="btn-primary">Add staff</button>
    </form>

    <div class="card divide-y">
        @foreach ($staff as $user)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">{{ $user->name }} <span class="text-xs text-gray-400">{{ $user->email }}</span></p>
                    <p class="text-xs text-gray-500">{{ $user->roles->pluck('name')->join(', ') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.staff.destroy', $user) }}" onsubmit="return confirm('Remove this staff member?')">
                    @csrf @method('DELETE')
                    <button class="btn-ghost">Remove</button>
                </form>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
