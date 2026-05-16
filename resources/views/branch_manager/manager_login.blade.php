{{--
    resources/views/branch_manager/manager_login.blade.php
    ───────────────────────────────────────────────────────
    Branch Manager login. 
    Full-page split: Cinematic slideshow left / Clean Form right.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Manager Login | CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/manager_login.css', 'resources/js/manager_login.js'])
</head>
<body class="ml-body">

<main class="ml-shell">
    <section class="ml-card" aria-label="Branch Manager login">

        {{-- ══ LEFT — slideshow panel ══════════════════════════ --}}
        <div class="ml-cinema-strip">
            
            {{-- Particles scoped only to the image side --}}
            <div class="ml-particles" id="ml-particles" aria-hidden="true"></div>

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
                    <div class="ml-slide-fallback"></div>
                @endforelse
            </div>

            {{-- Brand Overlay --}}
            <div class="ml-strip-caption">
                <span>CinemaX Management</span>
                <strong>Welcome Back</strong>
            </div>
        </div>

        {{-- ══ RIGHT — Clean Form panel ════════════════════════ --}}
        <div class="ml-form-panel">
            <div class="ml-form-wrapper">
                
                <h1 class="ml-heading">CINEMAX</h1>
                <p class="ml-eyebrow">MANAGER LOGIN</p>

                {{-- Error Alert --}}
                @if (session('bm_login_error'))
                    <div class="ml-alert ml-alert--error">
                        {{ session('bm_login_error') }}
                    </div>
                @endif

                {{-- Login form --}}
                <form action="{{ route('manager.login.post') }}" method="POST" class="ml-form" novalidate>
                    @csrf

                    <label class="ml-field" for="manager_email">
                        <span>Username:</span>
                        <input
                            type="email"
                            id="manager_email"
                            name="manager_email"
                            value="{{ old('manager_email') }}"
                            autocomplete="email"
                            required
                        >
                    </label>

                    <label class="ml-field" for="password">
                        <span>Password:</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </label>

                    <button type="submit" class="ml-primary">
                        LOGIN
                    </button>
                </form>

            </div>
        </div>

    </section>
</main>

</body>
</html>