<x-layouts.auth title="Sign in">
    <h1>Sign in</h1>
    <p class="lede">Use the staff account your admin created for you.</p>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <div class="control">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       autocomplete="username" required autofocus
                       @error('email') aria-invalid="true" @enderror>
            </div>
        </div>

        <div class="field">
            <div class="label-row">
                <label for="password">Password</label>
                <a class="text-link" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="control">
                <input id="password" class="has-toggle" type="password" name="password"
                       autocomplete="current-password" required
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="reveal" data-reveal aria-controls="password" aria-pressed="false">Show</button>
            </div>
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            Keep me signed in on this device
        </label>

        <button type="submit" class="submit">Sign in</button>
    </form>

    <p class="note">No account yet? Ask your store admin to add you under Staff.</p>
</x-layouts.auth>
