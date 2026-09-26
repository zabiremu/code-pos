<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in &middot; {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gloock&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Login palette: vampire blood with bone, brass and ink. Self-contained so
           the page needs no asset rebuild. */
        :root {
            --blood: #6B0A14;      /* vampire blood: brand */
            --clot: #2A0307;       /* deepest red, panel shadow side */
            --arterial: #A3121F;   /* brighter red: hover, focus */
            --bone: #F3ECEC;       /* form panel background */
            --ink: #1F1416;        /* body text */
            --ash: #7A6A6C;        /* secondary text */
            --brass: #C49A5A;      /* fine accents on the dark panel */
            --line: #DDCFD0;       /* input borders */
            --display: 'Gloock', 'Times New Roman', Georgia, serif;
            --sans: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: var(--sans);
            color: var(--ink);
            background: var(--bone);
            -webkit-font-smoothing: antialiased;
        }

        .shell { min-height: 100%; display: grid; grid-template-columns: 1.1fr 1fr; }

        /* Left: the blood panel with the heartbeat line */
        .vein {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(2rem, 4vw, 3.5rem);
            color: var(--bone);
            background:
                radial-gradient(120% 90% at 15% 10%, var(--arterial) 0%, transparent 55%),
                linear-gradient(160deg, var(--blood) 0%, var(--clot) 100%);
        }
        .wordmark {
            display: flex; align-items: center; gap: .75rem;
            font-family: var(--display); font-size: 1.375rem; letter-spacing: .01em;
        }
        .wordmark svg { width: 2.25rem; height: 2.25rem; flex: none; }

        .pulse {
            position: absolute; left: 0; right: 0; top: 50%;
            width: 100%; height: 160px; transform: translateY(-50%);
            pointer-events: none;
        }
        .pulse .base { stroke: rgba(243, 236, 236, .14); stroke-width: 1; stroke-dasharray: 2 6; }
        .pulse .beat {
            stroke: var(--bone); stroke-width: 2.5; fill: none;
            stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 1400; stroke-dashoffset: 1400;
            animation: draw 2.4s cubic-bezier(.65, 0, .35, 1) .3s forwards;
            filter: drop-shadow(0 0 6px rgba(255, 90, 100, .55));
        }
        .pulse .dot { fill: var(--brass); opacity: 0; animation: appear .4s ease 2.6s forwards; }
        @keyframes draw { to { stroke-dashoffset: 0; } }
        @keyframes appear { to { opacity: 1; } }

        .vein h2 {
            position: relative;
            font-family: var(--display); font-weight: 400;
            font-size: clamp(2.5rem, 4.6vw, 4.25rem); line-height: 1.02;
            letter-spacing: -.015em; margin: 0; max-width: 11ch;
        }
        .vein p { position: relative; margin: 1rem 0 0; max-width: 34ch; line-height: 1.55; color: rgba(243, 236, 236, .72); }

        /* Right: the form */
        .gate { display: flex; align-items: center; justify-content: center; padding: clamp(2rem, 5vw, 4rem) 1.5rem; }
        .form-wrap { width: 100%; max-width: 23rem; }
        .form-wrap h1 { font-family: var(--display); font-weight: 400; font-size: 2.25rem; line-height: 1.1; margin: 0 0 .5rem; }
        .form-wrap .lede { margin: 0 0 2rem; color: var(--ash); line-height: 1.5; }

        .error {
            display: flex; gap: .625rem; align-items: flex-start;
            margin: 0 0 1.5rem; padding: .75rem .875rem;
            border-left: 3px solid var(--arterial); background: #F8E1E3;
            color: #5A0911; font-size: .9rem; line-height: 1.45; border-radius: 0 6px 6px 0;
        }

        .field { margin-bottom: 1.25rem; }
        .field label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .4rem; }
        .control { position: relative; }
        .control input {
            width: 100%; font: inherit; font-size: 1rem; color: var(--ink);
            padding: .8rem .95rem; background: #fff;
            border: 1px solid var(--line); border-radius: 8px;
            transition: border-color .15s, box-shadow .15s;
        }
        .control input:hover { border-color: #C7B3B5; }
        .control input:focus { outline: none; border-color: var(--blood); box-shadow: 0 0 0 3px rgba(107, 10, 20, .18); }
        .control input[aria-invalid="true"] { border-color: var(--arterial); }
        .control .has-toggle { padding-right: 4.5rem; }
        .reveal {
            position: absolute; right: .4rem; top: 50%; transform: translateY(-50%);
            font: inherit; font-size: .8125rem; font-weight: 600; color: var(--blood);
            background: none; border: 0; padding: .4rem .55rem; border-radius: 6px; cursor: pointer;
        }
        .reveal:hover { background: #F4E4E5; }
        .reveal:focus-visible { outline: 2px solid var(--blood); outline-offset: 1px; }

        .remember { display: flex; align-items: center; gap: .6rem; margin: .25rem 0 1.75rem; font-size: .9rem; color: var(--ash); cursor: pointer; }
        .remember input { width: 1.05rem; height: 1.05rem; margin: 0; accent-color: var(--blood); }

        .submit {
            width: 100%; font: inherit; font-size: 1rem; font-weight: 600; color: var(--bone);
            padding: .9rem 1rem; border: 0; border-radius: 8px; cursor: pointer;
            background: var(--blood);
            box-shadow: inset 0 -2px 0 var(--clot);
            transition: background-color .15s, transform .05s;
        }
        .submit:hover { background: var(--arterial); }
        .submit:active { transform: translateY(1px); }
        .submit:focus-visible { outline: 3px solid var(--brass); outline-offset: 3px; }

        .note { margin: 2rem 0 0; padding-top: 1.25rem; border-top: 1px solid var(--line); font-size: .85rem; color: var(--ash); line-height: 1.5; }

        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
            .vein { min-height: 13rem; padding: 1.5rem; }
            .vein h2, .vein p { display: none; }
            .pulse { top: 62%; height: 120px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .pulse .beat { animation: none; stroke-dashoffset: 0; }
            .pulse .dot { animation: none; opacity: 1; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="vein" >
        <div class="wordmark">
            <svg viewBox="0 0 36 36" fill="none" aria-hidden="true">
                <rect x=".75" y=".75" width="34.5" height="34.5" rx="9" stroke="#C49A5A" stroke-width="1.5"/>
                <path d="M6 19h6l3-8 5 14 3-6h7" stroke="#F3ECEC" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ config('app.name') }}</span>
        </div>

        <svg class="pulse" viewBox="0 0 1000 160" preserveAspectRatio="none" aria-hidden="true">
            <line class="base" x1="0" y1="80" x2="1000" y2="80"/>
            <path class="beat" d="M0 80 H300 L330 80 L350 40 L372 132 L398 18 L420 108 L436 80 H560 L580 64 L598 80 H1000"/>
            <circle class="dot" cx="598" cy="80" r="4"/>
        </svg>

        <div>
            <h2>Every sale keeps the shop alive.</h2>
            <p>Sales, stock and staff shifts in one till. Sign in to pick up where the last shift left off.</p>
        </div>
    </section>

    <section class="gate">
        <div class="form-wrap">
            <h1>Sign in</h1>
            <p class="lede">Use the staff account your admin created for you.</p>

            @if ($errors->any())
                <p class="error" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01" stroke-linecap="round"/></svg>
                    <span>{{ $errors->first() }}</span>
                </p>
            @endif

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
                    <label for="password">Password</label>
                    <div class="control">
                        <input id="password" class="has-toggle" type="password" name="password"
                               autocomplete="current-password" required
                               @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="reveal" aria-controls="password" aria-pressed="false"
                                onclick="const f=document.getElementById('password');const s=f.type==='password';f.type=s?'text':'password';this.textContent=s?'Hide':'Show';this.setAttribute('aria-pressed',s);">Show</button>
                    </div>
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Keep me signed in on this device
                </label>

                <button type="submit" class="submit">Sign in</button>
            </form>

            <p class="note">No account yet? Ask your store admin to add you under Staff.</p>
        </div>
    </section>
</main>
</body>
</html>
