{{--
    resources/views/branch_manager/manager_login.blade.php
    ───────────────────────────────────────────────────────
    Branch Manager login page. Standalone — no layout shell.
    Controller: BranchManagerAuthController@showLogin / @login
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Manager Login</title>
    @vite(['resources/css/branch_manager.css'])
    <style>
        /* Login-specific centering — not needed in shared layout */
        .bm-login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .bm-login-card {
            width: 100%;
            max-width: 380px;
            padding: 36px 32px;
        }

        .bm-login-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .bm-login-brand__icon { font-size: 2.4rem; line-height: 1; }

        .bm-login-brand__title {
            font-family: var(--bm-font-head);
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--bm-text);
            margin-top: 10px;
            letter-spacing: -0.02em;
        }

        .bm-login-brand__sub {
            font-size: 0.78rem;
            color: var(--bm-text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body class="bm-body">

<div class="bm-login-wrap">
    <div class="bm-card bm-login-card">

        {{-- Brand --}}
        <div class="bm-login-brand">
            <div class="bm-login-brand__icon">🏢</div>
            <p class="bm-login-brand__title">Branch Manager</p>
            <p class="bm-login-brand__sub">Cinema Management Portal</p>
        </div>

        {{-- Error --}}
        @if (session('bm_login_error'))
            <div class="bm-alert bm-alert--error" style="margin-bottom:20px;">
                <span>✕</span> {{ session('bm_login_error') }}
            </div>
        @endif

        {{-- Login form --}}
        <form action="{{ route('manager.login.post') }}" method="POST" novalidate>
            @csrf

            <div class="bm-field">
                <label for="manager_email">Email <span class="required">*</span></label>
                <input
                    type="email"
                    id="manager_email"
                    name="manager_email"
                    class="bm-input"
                    placeholder="you@example.com"
                    value="{{ old('manager_email') }}"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="bm-field" style="margin-bottom:24px;">
                <label for="password">Password <span class="required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="bm-input"
                    placeholder="Your password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="bm-btn bm-btn--primary">
                🔑 Sign In
            </button>
        </form>

    </div>
</div>

</body>
</html>