<x-layouts.install :step="5" title="Admin Account">
    <div class="card p-8">
        <h2 class="text-lg font-semibold mb-1">Create your admin account</h2>
        <p class="text-sm text-zinc-600 mb-4">This is the login you'll use to manage the shop. You can add managers and cashiers later from Staff.</p>

        @if ($errors->any())
            <p class="alert-error mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('install.admin.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="field-label" for="name">Your name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="w-full input">
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="w-full input">
            </div>
            <div>
                <label class="field-label" for="password">Password</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full input">
                <p class="text-xs text-zinc-500 mt-1">At least 8 characters.</p>
            </div>
            <div>
                <label class="field-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full input">
            </div>
            <label class="flex items-start gap-2 text-sm text-zinc-700 pt-1">
                <input type="checkbox" name="sample_data" value="1" @checked(old('sample_data')) class="mt-0.5">
                <span>Add sample products and categories <span class="text-zinc-500">(handy for trying things out; you can delete them later)</span></span>
            </label>
            <button class="w-full btn-primary">Create account &amp; finish</button>
        </form>
    </div>
</x-layouts.install>
