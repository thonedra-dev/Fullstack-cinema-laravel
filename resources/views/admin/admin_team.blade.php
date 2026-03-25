{{--
    resources/views/admin/admin_team.blade.php
    ──────────────────────────────────────────
    Central admin layout shell.
    All feature Blades extend this via @extends('admin.admin_team').

    Slots:
      @section('page_title')        — browser <title> suffix           (optional)
      @section('hide_topbar_title') — define this section to hide the  (optional)
                                      topbar title bar (inline heading
                                      acts as the page title instead)
      @section('head_extras')       — extra <link>/<script> in <head>  (optional)
      @section('content')           — main feature body                (required)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — @yield('page_title', 'Dashboard')</title>
    @vite(['resources/css/admin_team.css', 'resources/js/admin_team.js'])
    @yield('head_extras')
</head>
<body>

{{-- ════════════════════════════════════════════════════════
     SIDEBAR NAV
════════════════════════════════════════════════════════ --}}
<aside class="at-sidebar" id="at-sidebar">

    <div class="at-sidebar__brand">
        <span class="at-sidebar__brand-icon">🎬</span>
        <span class="at-sidebar__brand-name">AdminPanel</span>
    </div>

    <nav class="at-nav">

        <div class="at-nav__group-label">Cinemas</div>
        <a href="{{ route('admin.cinema.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.cinema.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">＋</span> Add Cinema
        </a>
        <a href="{{ route('admin.cinema.index') }}"
           class="at-nav__link {{ request()->routeIs('admin.cinema.index') ? 'is-active' : '' }}">
            <span class="at-nav__icon">☰</span> View Cinemas
        </a>

        <div class="at-nav__group-label">Locations</div>
        <a href="{{ route('admin.city.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.city.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">📍</span> Add City
        </a>

        <div class="at-nav__group-label">Theatres</div>
        <a href="{{ route('admin.theatre.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.theatre.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">🏟</span> Create Theatre
        </a>

        <div class="at-nav__group-label">Services</div>
        <a href="{{ route('admin.service.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.service.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">⚙</span> Add Service
        </a>

    </nav>

    <div class="at-sidebar__footer">
        <span class="at-sidebar__footer-text">v1.0 · Admin</span>
    </div>

</aside>

{{-- ════════════════════════════════════════════════════════
     MAIN CONTENT AREA
════════════════════════════════════════════════════════ --}}
<div class="at-layout">

    {{-- Top bar --}}
    <header class="at-topbar">
        <button class="at-topbar__toggle" id="at-sidebar-toggle" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        {{-- Hide topbar title if feature page has its own prominent inline heading --}}
        @unless(View::hasSection('hide_topbar_title'))
            <div class="at-topbar__title">@yield('page_title', 'Dashboard')</div>
        @endunless
    </header>

    {{-- Flash messages --}}
    <div class="at-messages">
        @if (session('success'))
            <div class="at-alert at-alert--success">
                <span class="at-alert__icon">✓</span>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="at-alert at-alert--error">
                <span class="at-alert__icon">✕</span>
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Feature content --}}
    <main class="at-main">
        @yield('content')
    </main>

</div>{{-- /.at-layout --}}

</body>
</html>