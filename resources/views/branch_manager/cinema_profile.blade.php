{{--
    resources/views/branch_manager/cinema_profile.blade.php
    ────────────────────────────────────────────────────────
    Portal 1: Cinema profile for branch manager.
    Controller: BranchManagerDashboardController@cinemaProfile
    Data injected (logic-free blade):
      $cinema – Cinema model with eager-loaded ->city
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Cinema Profile')

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">← Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title">Cinema <span>Profile</span></h1>
    <p class="bm-page-header__sub">Details for your assigned cinema branch.</p>
</div>

<div class="bm-card" style="overflow:hidden;">

    {{-- Cinema image --}}
    @if ($cinema->cinema_picture)
        <img
            src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
            alt="{{ $cinema->cinema_name }}"
            style="width:100%;aspect-ratio:16/7;object-fit:cover;display:block;"
        >
    @else
        <div style="width:100%;aspect-ratio:16/7;background:var(--bm-surface);display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--bm-text-muted);">
            🏟
        </div>
    @endif

    <div style="padding:28px;">

        {{-- Name --}}
        <h2 style="font-family:var(--bm-font-head);font-size:1.5rem;font-weight:700;color:var(--bm-text);margin-bottom:24px;letter-spacing:-0.02em;">
            {{ $cinema->cinema_name }}
        </h2>

        {{-- Info grid --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <div class="bm-info-row">
                <span class="bm-info-label">City</span>
                <span class="bm-info-value">{{ $cinema->city?->city_name ?? '—' }}</span>
            </div>

            <div class="bm-info-row">
                <span class="bm-info-label">State</span>
                <span class="bm-info-value">
                    <span class="bm-badge">{{ $cinema->city?->city_state ?? '—' }}</span>
                </span>
            </div>

            <div class="bm-info-row" style="grid-column:1/-1;">
                <span class="bm-info-label">Address</span>
                <span class="bm-info-value">{{ $cinema->cinema_address }}</span>
            </div>

            <div class="bm-info-row">
                <span class="bm-info-label">Contact</span>
                <span class="bm-info-value">{{ $cinema->cinema_contact }}</span>
            </div>

            @if ($cinema->cinema_description)
            <div class="bm-info-row" style="grid-column:1/-1;">
                <span class="bm-info-label">Description</span>
                <span class="bm-info-value" style="font-size:0.875rem;color:var(--bm-text-sub);line-height:1.6;">
                    {{ $cinema->cinema_description }}
                </span>
            </div>
            @endif

        </div>

    </div>
</div>

@endsection