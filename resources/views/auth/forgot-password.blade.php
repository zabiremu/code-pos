<x-layouts.auth title="Reset your password">
    <h1>Reset your password</h1>
    <p class="lede">Enter the email on your staff account and we'll send you a link to set a new password.</p>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <div class="control">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       autocomplete="email" required autofocus
                       @error('email') aria-invalid="true" @enderror>
            </div>
        </div>

        <button type="submit" class="submit">Send reset link</button>
    </form>

    <a class="text-link back" href="{{ route('login') }}">Back to sign in</a>

    <p class="note">No email arriving? Check spam, or ask your store admin to set a new password for you under Staff.</p>
</x-layouts.auth>
