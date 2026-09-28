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

    @if (config('app.demo_mode'))
        {{-- Live demo only: one click fills the form and signs in. --}}
        <div class="demo-logins">
            <p class="demo-title">Live demo &mdash; sign in as:</p>
            <div class="demo-grid">
                @foreach ([
                    ['Admin', 'admin@example.com', 'Everything, incl. settings & staff'],
                    ['Manager', 'manager@example.com', 'Day-to-day running, no admin accounts'],
                    ['Cashier', 'cashier@example.com', 'Register & sales only'],
                ] as [$role, $email, $hint])
                    <button type="button" class="demo-login" data-demo-email="{{ $email }}" data-demo-password="password">
                        <strong>{{ $role }}</strong>
                        <span>{{ $hint }}</span>
                    </button>
                @endforeach
            </div>
            <p class="demo-foot">Password for all: <code>password</code> &middot; Data resets every hour.</p>
        </div>
    @else
        <p class="note">No account yet? Ask your store admin to add you under Staff.</p>
    @endif
</x-layouts.auth>
