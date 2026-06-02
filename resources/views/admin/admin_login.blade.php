<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaX | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/admin_login.css', 'resources/js/admin_login.js'])
</head>
<body>

<div class="login-split">
    <div class="login-left">
        <div class="login-left__inner">
            <div class="login-brand">
                <div class="login-brand__icon">
                    <i class="fas fa-clapperboard"></i>
                </div>
                <div class="login-brand__wordmark">
                    <span class="login-brand__cinemax">CINEMAX</span>
                    <span class="login-brand__admin">// ADMIN PANEL</span>
                </div>
            </div>
            <div class="login-heading">
                <h1 class="login-heading__title">Welcome back,<br><span class="login-heading__highlight">Supervisor.</span></h1>
                <p class="login-heading__sub">Sign in to manage the cinema matrix.</p>
            </div>
            <form method="POST" action="{{ route('admin.login.submit') }}" class="login-form" id="login-form">
                @csrf
                @if($errors->any())
                    <div class="login-alert" id="login-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
                <div class="login-field" id="field-email">
                    <input class="login-field__input"
                           type="email"
                           name="email"
                           id="email"
                           placeholder="Email address"
                           value="{{ old('email') }}"
                           required
                           autofocus>
                    <div class="login-field__bar"></div>
                </div>
                <div class="login-field" id="field-password">
                    <div class="login-field__pw-wrap">
                        <input class="login-field__input"
                               type="password"
                               name="password"
                               id="password"
                               placeholder="Password"
                               required>
                        <button type="button" class="login-field__eye" id="toggle-pw" tabindex="-1">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                        <div class="login-field__bar"></div>
                    </div>
                </div>
                <button type="submit" class="login-btn" id="login-btn">
                    <span class="login-btn__text">ACCESS PANEL</span>
                    <span class="login-btn__icon"><i class="fas fa-arrow-right"></i></span>
                    <span class="login-btn__ripple"></span>
                </button>
            </form>
            <p class="login-footer-note">
                <i class="fas fa-shield-alt"></i>
                Unauthorized access is monitored and logged.
            </p>
        </div>
    </div>

    <div class="login-right" aria-hidden="true">
        <canvas id="orb-canvas"></canvas>
        <div class="login-right__overlay-text">
            <p class="login-right__tagline">CINEMAX CONTROL</p>
            <p class="login-right__sub">Cinema Operations Matrix</p>
        </div>
    </div>
</div>

</body>
</html>