<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>

    @vite(['resources/css/signup.css', 'resources/js/signup.js'])
</head>
<body>

<div class="signup-container">
    <div class="signup-card">

        <h1 class="title">Create Account</h1>
        <p class="subtitle">Start your cinematic experience</p>

        <!-- Google Signup -->
        <a href="/auth/google/redirect" class="google-btn">
            Sign up with Google
        </a>

        <div class="divider">
            <span>OR</span>
        </div>

        <!-- (Manual signup later) -->
        <button class="manual-btn" disabled>
            Manual Sign-Up (Coming Soon)
        </button>

    </div>
</div>

</body>
</html>