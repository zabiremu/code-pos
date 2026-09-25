<x-layouts.admin :title="'Staff'">
    <form method="POST" action="{{ route('admin.staff.store') }}" class="bg-white rounded-lg shadow p-5 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
        @csrf
        <input type="text" name="name" placeholder="Name" required class="rounded border-gray-300 text-sm">
        <input type="email" name="email" placeholder="Email" required class="rounded border-gray-300 text-sm">
        <input type="password" name="password" placeholder="Password" required class="rounded border-gray-300 text-sm">
        <select name="role" required class="rounded border-gray-300 text-sm">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ $role->label() }}</option>
            @endforeach
        </select>
        <button class="bg-gray-900 text-white text-sm rounded px-4 py-2">Add staff</button>
    </form>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($staff as $user)
            <div class="flex items-center justify-between px-5 py-3">
                <div>
                    <p class="font-medium">{{ $user->name }} <span class="text-xs text-gray-400">{{ $user->email }}</span></p>
                    <p class="text-xs text-gray-500">{{ $user->roles->pluck('name')->join(', ') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.staff.destroy', $user) }}" onsubmit="return confirm('Remove this staff member?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600">Remove</button>
                </form>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
