<x-layouts.auth title="Set a new password">
    <h1>Set a new password</h1>
    <p class="lede">Choose a password you haven't used here before. You'll sign in with it right after.</p>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">Email</label>
            <div class="control">
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                       autocomplete="username" required
                       @error('email') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="field">
            <label for="password">New password</label>
            <div class="control">
                <input id="password" class="has-toggle" type="password" name="password"
                       autocomplete="new-password" required autofocus aria-describedby="password-hint"
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="reveal" data-reveal aria-controls="password" aria-pressed="false">Show</button>
            </div>
            <p class="hint" id="password-hint">At least 8 characters.</p>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="control">
                <input id="password_confirmation" class="has-toggle" type="password" name="password_confirmation"
                       autocomplete="new-password" required>
                <button type="button" class="reveal" data-reveal aria-controls="password_confirmation" aria-pressed="false">Show</button>
            </div>
        </div>

        <button type="submit" class="submit">Save new password</button>
    </form>

    <a class="text-link back" href="{{ route('login') }}">Back to sign in</a>
</x-layouts.auth>
