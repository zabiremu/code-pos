{{--
    Laid out like the classic WordPress wp-admin/profile.php screen: a
    profile-picture row, then label+description on the left / field on the
    right for every row (WP's "form-table"), a divider between rows instead
    of a card per field, and a left-aligned submit button under its own
    top border rather than a floating one - not a copy of WP's markup,
    just the same information-dense settings-page shape.
--}}
<x-layouts.admin :title="'My Profile'">
    <div class="max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- Profile information --}}
        <div class="card">
            <div class="card-body !pb-0">
                <h2 class="text-base font-semibold">Profile</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Your account details as seen across the POS.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="px-5">
                    {{-- Profile picture: read-only initials avatar, WP's "Profile Picture" row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <p class="text-sm font-medium text-zinc-700">Profile picture</p>
                            <p class="text-xs text-zinc-400 mt-1">Generated from your initials.</p>
                        </div>
                        <div>
                            <span class="w-16 h-16 rounded-full bg-primary-600 text-white font-semibold text-xl flex items-center justify-center">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="name" class="text-sm font-medium text-zinc-700">Name</label>
                            <p class="text-xs text-zinc-400 mt-1">Displayed in the sidebar and on tickets you handle.</p>
                        </div>
                        <div>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full input">
                            @error('name', 'profileUpdate')
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
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full input">
                            @error('email', 'profileUpdate')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="phone" class="text-sm font-medium text-zinc-700">Phone</label>
                            <p class="text-xs text-zinc-400 mt-1">Optional - for shift or emergency contact.</p>
                        </div>
                        <div>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full input">
                            @error('phone', 'profileUpdate')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-zinc-100">
                    <button class="btn-primary">Update profile</button>
                </div>
            </form>
        </div>

        {{-- Account security --}}
        <div id="password" class="card">
            <div class="card-body !pb-0">
                <h2 class="text-base font-semibold">Account security</h2>
                <p class="text-sm text-zinc-500 mt-0.5">Change your password. You'll stay logged in on this device.</p>
            </div>

            <form method="POST" action="{{ route('profile.password') }}"
                  x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                @csrf
                @method('PUT')

                <div class="px-5">
                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="current_password" class="text-sm font-medium text-zinc-700">Current password</label>
                            <p class="text-xs text-zinc-400 mt-1">Confirms it's really you.</p>
                        </div>
                        <div>
                            <div class="relative w-full">
                                <input id="current_password" :type="showCurrent ? 'text' : 'password'" name="current_password" required
                                       class="w-full input pr-10" autocomplete="current-password">
                                <button type="button" x-on:click="showCurrent = !showCurrent"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-400 hover:text-zinc-600">
                                    <svg x-show="!showCurrent" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showCurrent" x-cloak class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.83 2.83M9.5 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-3.2 4.1M6.6 6.6A13.6 13.6 0 0 0 2 12s3.5 7 10 7a10.4 10.4 0 0 0 3.4-.56" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            @error('current_password', 'passwordUpdate')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="password" class="text-sm font-medium text-zinc-700">New password</label>
                            <p class="text-xs text-zinc-400 mt-1">At least 8 characters.</p>
                        </div>
                        <div>
                            <div class="relative w-full">
                                <input id="password" :type="showNew ? 'text' : 'password'" name="password" required
                                       class="w-full input pr-10" autocomplete="new-password">
                                <button type="button" x-on:click="showNew = !showNew"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-400 hover:text-zinc-600">
                                    <svg x-show="!showNew" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showNew" x-cloak class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.83 2.83M9.5 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-3.2 4.1M6.6 6.6A13.6 13.6 0 0 0 2 12s3.5 7 10 7a10.4 10.4 0 0 0 3.4-.56" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            @error('password', 'passwordUpdate')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
                        <div>
                            <label for="password_confirmation" class="text-sm font-medium text-zinc-700">Confirm new password</label>
                            <p class="text-xs text-zinc-400 mt-1">Re-type it to make sure.</p>
                        </div>
                        <div>
                            <div class="relative w-full">
                                <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                                       class="w-full input pr-10" autocomplete="new-password">
                                <button type="button" x-on:click="showConfirm = !showConfirm"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-400 hover:text-zinc-600">
                                    <svg x-show="!showConfirm" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showConfirm" x-cloak class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.83 2.83M9.5 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a13.4 13.4 0 0 1-3.2 4.1M6.6 6.6A13.6 13.6 0 0 0 2 12s3.5 7 10 7a10.4 10.4 0 0 0 3.4-.56" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-zinc-100">
                    <button class="btn-primary">Update password</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
