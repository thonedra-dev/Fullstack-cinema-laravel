{{--
    resources/views/branch_manager/manager_login.blade.php
    ───────────────────────────────────────────────────────
    Branch Manager login.
    Centered card: Cinematic slideshow left / Dark form right.
    Sci-fi ambient background surrounds the card.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Manager Login | Cinema X</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/manager_login.css', 'resources/js/manager_login.js'])
</head>
<body class="ml-body">

    {{-- ══ Sci-fi ambient background ══════════════════════════ --}}
    <div class="ml-bg" aria-hidden="true">
        <canvas class="ml-bg__grid" id="ml-grid-canvas"></canvas>
        <div class="ml-bg__orbs">
            <div class="ml-orb ml-orb--1"></div>
            <div class="ml-orb ml-orb--2"></div>
            <div class="ml-orb ml-orb--3"></div>
        </div>
        <div class="ml-bg__scanlines"></div>
        <div class="ml-bg__roamers" id="ml-roamers" aria-hidden="true"></div>
    </div>

    {{-- ══ Centered shell ══════════════════════════════════════ --}}
    <main class="ml-shell">
        <div class="ml-card">

            {{-- ── LEFT: slideshow ───────────────────────────── --}}
            <div class="ml-cinema-strip">
                <div class="ml-particles" id="ml-particles" aria-hidden="true"></div>

                <div class="ml-slides" id="ml-slides">
                    @forelse ($slides as $index => $slide)
                        <img
                            src="{{ $slide }}"
                            alt=""
                            class="ml-slide {{ $index === 0 ? 'ml-slide--active' : '' }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                        >
                    @empty
                        <div class="ml-slide-fallback"></div>
                    @endforelse
                </div>

                {{-- Corner tag --}}
                <div class="ml-strip-tag">
                    <span class="ml-strip-tag__line"></span>
                    <span class="ml-strip-tag__text">Branch Manager Portal</span>
                </div>

                {{-- Slide counter --}}
                <div class="ml-slide-counter" id="ml-slide-counter" aria-hidden="true">
                    <span id="ml-slide-current">01</span>
                    <span class="ml-slide-counter__sep"></span>
                    <span id="ml-slide-total">{{ str_pad(count($slides), 2, '0', STR_PAD_LEFT) }}</span>
                </div>
            </div>

            {{-- ── RIGHT: form panel ─────────────────────────── --}}
            <div class="ml-form-panel">
                <div class="ml-form-wrapper">

                    {{-- Brand --}}
                    <div class="ml-brand">
                        <svg class="ml-brand__icon" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="1" y="1" width="26" height="26" rx="3" stroke="#22c55e" stroke-width="1.5"/>
                            <path d="M6 8h3v12H6zM19 8h3v12h-3z" fill="#22c55e"/>
                            <path d="M9 11l10 3-10 3V11z" fill="#22c55e"/>
                        </svg>
                        <h1 class="ml-heading">Cinema <span>X</span></h1>
                    </div>
                    <p class="ml-eyebrow">
                        <span class="ml-eyebrow__dash"></span>
                        Manager Access
                        <span class="ml-eyebrow__dash"></span>
                    </p>

                    {{-- Error Alert --}}
                    @if (session('bm_login_error'))
                        <div class="ml-alert ml-alert--error" role="alert">
                            <svg class="ml-alert__icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 6v5M10 14v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            {{ session('bm_login_error') }}
                        </div>
                    @endif

                    {{-- Login form --}}
                    <form action="{{ route('manager.login.post') }}" method="POST" class="ml-form" novalidate>
                        @csrf

                        {{-- Email field --}}
                        <div class="ml-field">
                            <input
                                type="email"
                                id="manager_email"
                                name="manager_email"
                                value="{{ old('manager_email') }}"
                                autocomplete="email"
                                placeholder=" "
                                required
                                class="ml-field__input"
                            >
                            <label for="manager_email" class="ml-field__label">Username</label>
                            <span class="ml-field__line"></span>
                        </div>

                        {{-- Password field --}}
                        <div class="ml-field">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder=" "
                                required
                                class="ml-field__input"
                            >
                            <label for="password" class="ml-field__label">Password</label>
                            <span class="ml-field__line"></span>
                            <button type="button" class="ml-field__eye" id="ml-toggle-pw" aria-label="Toggle password visibility">
                                <svg id="ml-eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                </svg>
                            </button>
                        </div>

                        <button type="submit" class="ml-primary" id="ml-submit-btn">
                            <span class="ml-primary__inner" id="ml-primary-inner">
                                <span class="ml-primary__text">LOGIN</span>
                                <span class="ml-primary__arrow">→</span>
                            </span>
                        </button>

                    </form>

                    <p class="ml-footer-note">Authorised personnel only</p>

                </div>
            </div>

        </div>
    </main>

</body>
</html>