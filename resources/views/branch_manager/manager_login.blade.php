{{--
    resources/views/branch_manager/manager_login.blade.php
    ───────────────────────────────────────────────────────
    Branch Manager login. Standalone — no layout shell.
    Full-page split: slideshow left / form right.
    Controller: BranchManagerAuthController@showLogin / @login
    Data: $slides – array of absolute image URLs (landscape movie posters)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Manager Login | CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700;800&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/manager_login.css', 'resources/js/manager_login.js'])
</head>
<body class="ml-body">

{{-- ── Particles ───────────────────────────────────────── --}}
<div class="ml-particles" id="ml-particles" aria-hidden="true"></div>

<main class="ml-shell">
    <section class="ml-card" aria-label="Branch Manager login">

        {{-- ══ LEFT — slideshow panel ══════════════════════════ --}}
        <div class="ml-cinema-strip">

            {{-- Slides --}}
            <div class="ml-slides" id="ml-slides">
                @forelse ($slides as $index => $slide)
                    <img
                        src="{{ $slide }}"
                        alt=""
                        class="ml-slide {{ $index === 0 ? 'ml-slide--active' : '' }}"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    >
                @empty
                    <div class="ml-slide-fallback">
                        <span class="ml-slide-fallback__mark"></span>
                    </div>
                @endforelse
            </div>

            {{-- Scan line FX --}}
            <div class="ml-slide-scan" id="ml-slide-scan"></div>

            {{-- Brand — pinned top-left, always visible over slides --}}
            <div class="ml-strip-brand">
                <div class="ml-strip-brand__icon">🎬</div>
                <div>
                    <p class="ml-strip-brand__name">Branch Manager</p>
                    <p class="ml-strip-brand__sub">Cinema Management Portal</p>
                </div>
            </div>

            {{-- Caption — bottom-left --}}
            <div class="ml-strip-caption">
                <span>Tonight's reel</span>
                <strong>Changes frame by frame</strong>
            </div>

        </div>{{-- /.ml-cinema-strip --}}

        {{-- ══ RIGHT — form panel ══════════════════════════════ --}}
        <div class="ml-form-panel">

            {{-- CinemaX branding above the form --}}
            <div class="ml-form-brand">
                <span class="ml-form-brand__mark"></span>
                <span class="ml-form-brand__name">CinemaX</span>
            </div>

            <p class="ml-eyebrow">Management Portal</p>
            <h1 class="ml-heading">Sign In</h1>
            <p class="ml-subtitle">
                Access your cinema branch tools, manage showtimes,
                and stay on top of your schedule.
            </p>

            {{-- Error --}}
            @if (session('bm_login_error'))
                <div class="ml-alert ml-alert--error">
                    {{ session('bm_login_error') }}
                </div>
            @endif

            {{-- Login form --}}
            <form action="{{ route('manager.login.post') }}" method="POST" class="ml-form" novalidate>
                @csrf

                <label class="ml-field" for="manager_email">
                    <span>Email address</span>
                    <input
                        type="email"
                        id="manager_email"
                        name="manager_email"
                        value="{{ old('manager_email') }}"
                        placeholder="you@cinemabranch.com"
                        autocomplete="email"
                        required
                    >
                </label>

                <label class="ml-field" for="password">
                    <span>Password</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Your password"
                        autocomplete="current-password"
                        required
                    >
                </label>

                <button type="submit" class="ml-primary">
                    Sign In
                </button>

            </form>

        </div>{{-- /.ml-form-panel --}}

    </section>
</main>

</body>
</html>