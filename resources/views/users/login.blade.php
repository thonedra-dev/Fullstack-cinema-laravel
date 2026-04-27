<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/user_login.css', 'resources/js/user_login.js'])
</head>
<body class="ul-body">

<a href="{{ route('home') }}" class="ul-brand" aria-label="CinemaX homepage">
    <span class="ul-brand__mark"></span>
    <span>CinemaX</span>
</a>

<div class="ul-particles" id="ul-particles" aria-hidden="true"></div>

<main class="ul-shell">
    <section class="ul-card" aria-label="Customer sign in">
        <div class="ul-cinema-strip">
            <div class="ul-slides" id="ul-slides">
                @forelse ($slides as $index => $slide)
                    <img
                        src="{{ $slide }}"
                        alt=""
                        class="ul-slide {{ $index === 0 ? 'ul-slide--active' : '' }}"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    >
                @empty
                    <div class="ul-slide-fallback">
                        <span class="ul-slide-fallback__mark"></span>
                    </div>
                @endforelse
            </div>
            <div class="ul-slide-scan" id="ul-slide-scan"></div>
            <div class="ul-strip-caption">
                <span>Tonight's reel</span>
                <strong>Changes frame by frame</strong>
            </div>
        </div>

        <div class="ul-form-panel">
            <p class="ul-eyebrow">MovieClub Access</p>
            <h1>Sign In</h1>
            <p class="ul-subtitle">Keep browsing freely, or sign in to save your customer identity for upcoming booking features.</p>

            @if (session('status') || request()->boolean('registered'))
                <div class="ul-alert ul-alert--success">
                    {{ session('status') ?? 'Signup complete. Sign in to continue.' }}
                </div>
            @endif

            @if ($errors->any())
                <div class="ul-alert ul-alert--error">
                    {{ $errors->first() }}
                </div>
            @endif

            <a href="{{ route('google.redirect', ['intent' => 'login']) }}" class="ul-google">
                <span class="ul-google__mark">G</span>
                <span>Sign in with Google</span>
            </a>

            <div class="ul-divider"><span>or use email</span></div>

            <form action="{{ route('users.login.post') }}" method="POST" class="ul-form">
                @csrf

                <label class="ul-field" for="email_address">
                    <span>Email address</span>
                    <input
                        type="email"
                        id="email_address"
                        name="email_address"
                        value="{{ old('email_address') }}"
                        autocomplete="email"
                        required
                    >
                </label>

                <label class="ul-field" for="password">
                    <span>Password</span>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </label>

                <div class="ul-row">
                    <label class="ul-remember">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <a href="{{ route('users.signup') }}">Create account</a>
                </div>

                <button type="submit" class="ul-primary">Sign In</button>
            </form>

            <a href="{{ route('home') }}" class="ul-browse">Continue to homepage without signing in</a>
        </div>
    </section>
</main>

</body>
</html>
