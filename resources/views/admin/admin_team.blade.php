{{--
    resources/views/admin/admin_team.blade.php
    Central admin layout shell with collapsible sidebar.
    All feature Blades extend this via @extends('admin.admin_team').

    Slots:
      @section('page_title')        — browser <title> suffix           (optional)
      @section('hide_topbar_title') — define this section to hide the  (optional)
                                      topbar title bar
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

<div class="at-app" data-sidebar-state="collapsed">

    {{-- ════════════════════════════════════════════════════════
         SIDEBAR (collapsible)
    ════════════════════════════════════════════════════════ --}}
    <aside class="at-sidebar" id="at-sidebar">

    {{-- Sidebar Header with Brand + Gradient Title --}}
    <div class="at-sidebar__header">
        <div class="at-sidebar__brand">
            <a href="{{ route('admin.panel') }}" class="at-sidebar__brand-icon" title="Back to Admin Panel">
                🎬
            </a>
            <span class="at-sidebar__brand-name gradient-text">Admin Panel</span>
        </div>
    </div>

    <nav class="at-nav">
        <a href="{{ route('admin.cinema.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.cinema.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">＋</span>
            <span class="at-nav__text">Add Cinema</span>
        </a>
        <a href="{{ route('admin.cinema.index') }}"
           class="at-nav__link {{ request()->routeIs('admin.cinema.index') ? 'is-active' : '' }}">
            <span class="at-nav__icon">☰</span>
            <span class="at-nav__text">View Cinemas</span>
        </a>

        <a href="{{ route('admin.city.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.city.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">📍</span>
            <span class="at-nav__text">Add City</span>
        </a>

        <a href="{{ route('admin.theatre.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.theatre.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">🏟</span>
            <span class="at-nav__text">Create Theatre</span>
        </a>

        <a href="{{ route('admin.service.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.service.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">⚙</span>
            <span class="at-nav__text">Add Service</span>
        </a>

        <a href="{{ route('admin.movie.create') }}"
           class="at-nav__link {{ request()->routeIs('admin.movie.create') ? 'is-active' : '' }}">
            <span class="at-nav__icon">🎬</span>
            <span class="at-nav__text">Create Movie</span>
        </a>

        <a href="{{ route('admin.managers.index') }}"
           class="at-nav__link {{ request()->routeIs('admin.managers.index') ? 'is-active' : '' }}">
            <span class="at-nav__icon">👤</span>
            <span class="at-nav__text">Managers</span>
        </a>

        <a href="{{ route('admin.proposals.index') }}"
           class="at-nav__link {{ request()->routeIs('admin.proposals.*') ? 'is-active' : '' }}">
            <span class="at-nav__icon">📩</span>
            <span class="at-nav__text">Movie Proposals</span>
        </a>
    </nav>

    <div class="at-sidebar__footer">
        <span class="at-sidebar__footer-text">v1.0 · Admin</span>
    </div>
</aside>

 {{-- Border Toggle Button --}}
<button class="at-border-toggle" id="borderToggleBtn" aria-label="Toggle sidebar">
    <i class="fas fa-angle-double-left"></i>
</button>

    {{-- ════════════════════════════════════════════════════════
         MAIN CONTENT AREA
    ════════════════════════════════════════════════════════ --}}
    <div class="at-layout">

        {{-- Top bar (mobile toggle only) --}}
        <header class="at-topbar">
            <button class="at-topbar__toggle" id="at-sidebar-toggle" aria-label="Toggle sidebar">
                <span></span><span></span><span></span>
            </button>
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

</div>{{-- /.at-app --}}

</body>
</html>
