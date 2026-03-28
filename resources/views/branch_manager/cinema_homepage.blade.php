{{--
    resources/views/branch_manager/cinema_homepage.blade.php
    Controller: BranchManagerDashboardController@home
    Data: $cinema – Cinema with ->city
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Home')

@section('bm_content')

<div class="bm-page-header">
    <h1 class="bm-page-header__title"><span>{{ $cinema->cinema_name }}</span></h1>
    <p class="bm-page-header__sub">
        {{ $cinema->city?->city_name ?? '—' }}, {{ $cinema->city?->city_state ?? '—' }}
        &nbsp;·&nbsp; Branch Manager Portal
    </p>
</div>

<div class="bm-portal-grid">

    {{-- 1: Cinema Profile --}}
    <a href="{{ route('manager.cinema.profile') }}" class="bm-portal-card">
        <div class="bm-portal-card__icon">🏟</div>
        <div class="bm-portal-card__body">
            <p class="bm-portal-card__title">Cinema Profile</p>
            <p class="bm-portal-card__desc">View cinema details, contact, and location.</p>
        </div>
        <span class="bm-portal-card__arrow">→</span>
    </a>

    {{-- 2: Resources --}}
    <a href="{{ route('manager.resources') }}" class="bm-portal-card">
        <div class="bm-portal-card__icon">📋</div>
        <div class="bm-portal-card__body">
            <p class="bm-portal-card__title">Resources</p>
            <p class="bm-portal-card__desc">Browse active theatres and running movies.</p>
        </div>
        <span class="bm-portal-card__arrow">→</span>
    </a>

    {{-- 3: Upcoming Movies --}}
    <a href="{{ route('manager.upcoming') }}" class="bm-portal-card">
        <div class="bm-portal-card__icon">🎬</div>
        <div class="bm-portal-card__body">
            <p class="bm-portal-card__title">Upcoming Movies</p>
            <p class="bm-portal-card__desc">Set up timetables for newly assigned movies.</p>
        </div>
        <span class="bm-portal-card__arrow">→</span>
    </a>

    {{-- 4: Notifications (static) --}}
    <div class="bm-portal-card bm-portal-card--static">
        <div class="bm-portal-card__icon">🔔</div>
        <div class="bm-portal-card__body">
            <p class="bm-portal-card__title">Notifications</p>
            <p class="bm-portal-card__desc">System notifications and announcements.</p>
        </div>
        <span class="bm-portal-card__coming">Coming Soon</span>
    </div>

    {{-- 5: Employees (static) --}}
    <div class="bm-portal-card bm-portal-card--static">
        <div class="bm-portal-card__icon">👥</div>
        <div class="bm-portal-card__body">
            <p class="bm-portal-card__title">Employees</p>
            <p class="bm-portal-card__desc">Manage cinema staff and shifts.</p>
        </div>
        <span class="bm-portal-card__coming">Coming Soon</span>
    </div>

</div>

<style>
.bm-portal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 18px;
}

.bm-portal-card {
    background: var(--bm-card);
    border: 1px solid var(--bm-border);
    border-radius: var(--bm-radius-lg);
    padding: 28px 24px;
    display: flex; flex-direction: column; gap: 12px;
    text-decoration: none;
    transition: border-color var(--bm-transition), transform var(--bm-transition), box-shadow var(--bm-transition);
    cursor: pointer; position: relative;
}

.bm-portal-card:not(.bm-portal-card--static):hover {
    border-color: var(--bm-accent);
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(34,197,94,0.12);
}

.bm-portal-card--static { opacity: 0.55; cursor: default; }
.bm-portal-card__icon { font-size: 2rem; line-height: 1; }
.bm-portal-card__body { flex: 1; }

.bm-portal-card__title {
    font-family: var(--bm-font-head); font-size: 1rem; font-weight: 700;
    color: var(--bm-text); margin-bottom: 4px;
}

.bm-portal-card__desc { font-size: 0.82rem; color: var(--bm-text-sub); line-height: 1.5; }

.bm-portal-card__arrow {
    position: absolute; top: 24px; right: 22px;
    font-size: 1rem; color: var(--bm-accent);
    opacity: 0; transform: translateX(-4px);
    transition: opacity var(--bm-transition), transform var(--bm-transition);
}

.bm-portal-card:not(.bm-portal-card--static):hover .bm-portal-card__arrow {
    opacity: 1; transform: translateX(0);
}

.bm-portal-card__coming {
    font-size: 0.65rem; font-weight: 700; font-family: var(--bm-font-head);
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--bm-text-muted); background: var(--bm-surface);
    border: 1px solid var(--bm-border); border-radius: var(--bm-radius-sm);
    padding: 3px 8px; width: fit-content;
}
</style>

@endsection