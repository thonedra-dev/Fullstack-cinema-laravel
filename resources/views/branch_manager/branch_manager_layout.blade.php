{{--
    resources/views/branch_manager/branch_manager_layout.blade.php
    ───────────────────────────────────────────────────────────────
    Shared layout for ALL branch manager pages.
    Completely separate from admin_team.blade.php.

    Slots:
      @section('bm_page_title')  — browser <title> suffix        (optional)
      @section('bm_head_extras') — extra CSS/JS in <head>        (optional)
      @section('bm_content')     — main page body                (required)

    Session keys used:
      bm_manager_id   — authenticated manager's ID
      bm_cinema_id    — manager's assigned cinema ID
      bm_manager_name — manager's name (display)
      bm_cinema_name  — cinema name (display in topbar)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Manager — @yield('bm_page_title', 'Dashboard')</title>
    @vite(['resources/css/branch_manager.css'])
    @yield('bm_head_extras')
</head>
<body class="bm-body">

{{-- ── Topbar ──────────────────────────────────────── --}}
<header class="bm-topbar">
    <div class="bm-topbar__brand">
        <span class="bm-topbar__brand-icon">🏢</span>
        Branch Manager
        @if (session('bm_cinema_name'))
            <span class="bm-topbar__cinema">{{ session('bm_cinema_name') }}</span>
        @endif
    </div>
    <div class="bm-topbar__right">
        @if (session('bm_manager_name'))
            <span style="font-size:0.78rem;color:var(--bm-text-sub);">
                {{ session('bm_manager_name') }}
            </span>
        @endif
        <form action="{{ route('manager.logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="bm-logout-btn">⎋ Logout</button>
        </form>
    </div>
</header>

{{-- ── Flash messages ──────────────────────────────── --}}
@if (session('bm_error'))
    <div style="max-width:1100px;margin:16px auto 0;padding:0 24px;">
        <div class="bm-alert bm-alert--error">
            <span>✕</span> {{ session('bm_error') }}
        </div>
    </div>
@endif

{{-- ── Content ─────────────────────────────────────── --}}
<div class="bm-page">
    @yield('bm_content')
</div>

</body>
</html>