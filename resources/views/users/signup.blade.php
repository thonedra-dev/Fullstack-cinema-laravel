<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Up | CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/signup.css', 'resources/js/signup.js'])
</head>
<body
    class="su-body"
    data-start-url="{{ route('users.signup.start') }}"
    data-verify-url="{{ route('users.signup.verify') }}"
    data-resend-url="{{ route('users.signup.resend') }}"
    data-complete-url="{{ route('users.signup.complete') }}"
>

<a href="{{ route('home') }}" class="su-brand" aria-label="CinemaX homepage">
    <span class="su-brand__mark"></span>
    <span>CinemaX</span>
</a>

<div class="su-scene" aria-hidden="true">
    <span class="su-light su-light--left"></span>
    <span class="su-light su-light--right"></span>
    <span class="su-reel su-reel--left"></span>
    <span class="su-reel su-reel--right"></span>
    <span class="su-clap"></span>
    <span class="su-ticket su-ticket--one"></span>
    <span class="su-ticket su-ticket--two"></span>
</div>

<main class="su-shell">
    <a href="{{ route('home') }}" class="su-close" aria-label="Back to homepage">x</a>

    <section class="su-panel" aria-label="Create your CinemaX account">
        <div class="su-stepper" aria-label="Signup progress">
            <div class="su-step su-step--active" data-step-dot="1">
                <span class="su-step__num">1</span>
                <span class="su-step__label">Enter Info</span>
            </div>
            <div class="su-step" data-step-dot="2">
                <span class="su-step__num">2</span>
                <span class="su-step__label">Verify Email</span>
            </div>
            <div class="su-step" data-step-dot="3">
                <span class="su-step__num">3</span>
                <span class="su-step__label">Set Password</span>
            </div>
        </div>

        <div class="su-copy">
            <p class="su-eyebrow">CinemaX MovieClub</p>
            <h1>Sign Up</h1>
            <p class="su-subtitle" id="su-subtitle">Enjoy exclusive rewards, faster bookings, and a sharper night at the movies.</p>
        </div>

        <div class="su-alert" id="su-alert" role="status" aria-live="polite"></div>

        <form class="su-form su-form--active" id="su-info-form" data-step-panel="1">
            <a href="{{ route('google.redirect', ['intent' => 'signup']) }}" class="su-google">
                <span class="su-google__mark">G</span>
                <span>Sign up with Google</span>
            </a>

            <div class="su-divider"><span>or continue manually</span></div>

            <label class="su-field" for="name">
                <span>Username</span>
                <input type="text" id="name" name="name" autocomplete="name" required>
            </label>

            <label class="su-field" for="email_address">
                <span>Email address</span>
                <input type="email" id="email_address" name="email_address" autocomplete="email" required>
            </label>

            <label class="su-check">
                <input type="checkbox" name="terms" value="1" required>
                <span>I have read and agreed to the Terms of Use and Privacy Policy.</span>
            </label>

            <button type="submit" class="su-primary">Next</button>

            <p class="su-footnote">Have an account? <a href="{{ route('users.login') }}">Sign in</a></p>
        </form>

        <form class="su-form" id="su-code-form" data-step-panel="2">
            <div class="su-code-copy">
                <p>We sent a six digit code to</p>
                <strong id="su-email-target">your email</strong>
            </div>

            <label class="su-field" for="verification_code">
                <span>Verification code</span>
                <input
                    type="text"
                    id="verification_code"
                    name="verification_code"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    autocomplete="one-time-code"
                    required
                >
            </label>

            <div class="su-code-row">
                <span id="su-countdown">Code expires in 03:00</span>
                <button type="button" class="su-link-btn" id="su-resend-btn">Resend code</button>
            </div>

            <button type="submit" class="su-primary">Verify Code</button>
            <button type="button" class="su-secondary" data-back-to="1">Back</button>
        </form>

        <form class="su-form" id="su-password-form" data-step-panel="3" enctype="multipart/form-data">
            <label class="su-field" for="password">
                <span>Password</span>
                <input type="password" id="password" name="password" autocomplete="new-password" required>
            </label>

            <label class="su-field" for="password_confirmation">
                <span>Re-enter password</span>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </label>

            <label class="su-file" for="avatar">
                <span class="su-file__label">Profile picture</span>
                <span class="su-file__hint" id="su-file-name">Optional JPG, PNG, or WEBP</span>
                <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp">
            </label>

            <button type="submit" class="su-primary">Create Account</button>
            <button type="button" class="su-secondary" data-back-to="1">Change email</button>
        </form>
    </section>
</main>

</body>
</html>
