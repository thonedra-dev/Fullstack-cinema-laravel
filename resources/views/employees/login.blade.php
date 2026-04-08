<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/employee_login.css', 'resources/js/employee_login.js'])
</head>
<body class="ul-body">

{{-- ── Left panel: generative canvas art ─────────────── --}}
<div class="ul-left" aria-hidden="true">
    <canvas id="ul-canvas"></canvas>

    <div class="ul-left__wordmark">🎬 CinemaX</div>

    <div class="ul-left__headline">
        <span class="ul-left__headline-line">Experience</span>
        <span class="ul-left__headline-line ul-left__headline-line--accent">the night</span>
        <span class="ul-left__headline-line">differently.</span>
    </div>

    <p class="ul-left__sub">
        Premium cinema. Curated showtimes.<br>Your seat, your world.
    </p>

    {{-- Floating film-strip decoration --}}
    <div class="ul-filmstrip" id="ul-filmstrip">
        <div class="ul-filmstrip__track">
            @for ($i = 0; $i < 12; $i++)
                <div class="ul-filmstrip__frame"></div>
            @endfor
        </div>
    </div>
</div>

{{-- ── Right panel: login form ────────────────────────── --}}
<div class="ul-right">

    {{-- Snake border wrapper --}}
    <div class="ul-form-wrap" id="ul-form-wrap">

        {{-- SVG snake border — drawn by JS --}}
        <svg class="ul-snake-svg" id="ul-snake-svg" aria-hidden="true">
            <rect id="ul-snake-path" class="ul-snake-path"
                  x="1" y="1" rx="23" ry="23"
                  width="calc(100% - 2px)" height="calc(100% - 2px)"/>
        </svg>

        <div class="ul-form-inner">
            <div class="ul-form-header">
                <p class="ul-form-eyebrow">Welcome back</p>
                <h2 class="ul-form-title">Sign in to continue</h2>
            </div>

            {{-- Error flash --}}
            @if (session('error'))
                <div class="ul-alert">
                    <span class="ul-alert__icon">✕</span>
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="ul-form" novalidate>
                @csrf

                <div class="ul-field">
                    <label for="email_address" class="ul-label">Email address</label>
                    <div class="ul-input-wrap">
                        <span class="ul-input-icon">✉</span>
                        <input
                            type="email"
                            id="email_address"
                            name="email_address"
                            class="ul-input"
                            placeholder="you@example.com"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="ul-field">
                    <label for="password" class="ul-label">Password</label>
                    <div class="ul-input-wrap">
                        <span class="ul-input-icon">🔒</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="ul-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>

                <div class="ul-form-foot">
                    <a href="#" class="ul-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="ul-submit">
                    <span class="ul-submit__text">Continue</span>
                    <span class="ul-submit__arrow">→</span>
                </button>
            </form>

            <div class="ul-register">
                <span class="ul-register__text">New here?</span>
                <a href="#" class="ul-register__link">Create an account</a>
            </div>
        </div>
    </div>

</div>

</body>
</html>