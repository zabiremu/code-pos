<x-layouts.admin :title="'My Profile'">
    <div class="max-w-xl space-y-6">
        <div class="card">
            <div class="card-body">
                <h2 class="font-semibold mb-1">Profile details</h2>
                <p class="text-sm text-zinc-500 mb-4">Update your name, email, and phone number.</p>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="field-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full input">
                        @error('name', 'profileUpdate')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full input">
                        @error('email', 'profileUpdate')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full input">
                        @error('phone', 'profileUpdate')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="btn-primary">Save changes</button>
                </form>
            </div>
        </div>

        <div id="password" class="card">
            <div class="card-body">
                <h2 class="font-semibold mb-1">Update password</h2>
                <p class="text-sm text-zinc-500 mb-4">Choose a new password. You'll need your current one to confirm.</p>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="field-label">Current password</label>
                        <input type="password" name="current_password" required class="w-full input">
                        @error('current_password', 'passwordUpdate')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">New password</label>
                        <input type="password" name="password" required class="w-full input">
                        @error('password', 'passwordUpdate')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="field-label">Confirm new password</label>
                        <input type="password" name="password_confirmation" required class="w-full input">
                    </div>

                    <button class="btn-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
