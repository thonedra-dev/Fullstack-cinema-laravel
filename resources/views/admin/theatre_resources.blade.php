{{--
    resources/views/admin/edit_cinema_theatre_resources.blade.php
    ──────────────────────────────────────────────────────────────
    Feature: View the seat layout of a specific theatre.
    Controller: AdminTheatreResourceController@show
    Data injected by controller (logic-free blade):
      $theatre    – Theatre model with eager-loaded ->cinema->city and ->seats
      $seatsByRow – $theatre->seats grouped by row_label (Collection keyed by label)

    View-only page. No JS interaction. No editing.
--}}
@extends('admin.admin_team')

@section('page_title', $theatre->theatre_name . ' — Seat Layout')

@section('head_extras')
    @vite(['resources/css/theatre_resources.css'])
@endsection

@section('content')

{{-- ── Breadcrumb trail ───────────────────────────────────── --}}
<nav class="etr-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.cinema.index') }}" class="etr-breadcrumb__item">
        All Cinemas
    </a>
    <span class="etr-breadcrumb__sep">›</span>
    <span class="etr-breadcrumb__item etr-breadcrumb__item--cinema">
        {{ $theatre->cinema->cinema_name ?? '—' }}
    </span>
    <span class="etr-breadcrumb__sep">›</span>
    <span class="etr-breadcrumb__item etr-breadcrumb__item--current">
        {{ $theatre->theatre_name }}
    </span>
</nav>

{{-- ── Page header ────────────────────────────────────────── --}}
<div class="ac-page-header etr-header">
    <div>
        <h1 class="ac-page-header__title">
            {{ $theatre->theatre_name }} <span>Seat Layout</span>
        </h1>
        <p class="ac-page-header__sub">
            {{ $theatre->cinema->cinema_name ?? '—' }}
            @if ($theatre->cinema?->city)
                &middot; {{ $theatre->cinema->city->city_name }},
                {{ $theatre->cinema->city->city_state }}
            @endif
        </p>
    </div>

    {{-- Theatre poster / icon if available --}}
    @if ($theatre->theatre_poster || $theatre->theatre_icon)
        <img
            src="{{ asset('images/theatres/' . ($theatre->theatre_poster ?? $theatre->theatre_icon)) }}"
            alt="{{ $theatre->theatre_name }}"
            class="etr-header__thumb"
        >
    @endif
</div>

{{-- ── Stats bar ──────────────────────────────────────────── --}}
<div class="etr-stats">
    <div class="etr-stat">
        <span class="etr-stat__value">{{ $theatre->seats->count() }}</span>
        <span class="etr-stat__label">Total Seats</span>
    </div>
    <div class="etr-stat">
        <span class="etr-stat__value">{{ $seatsByRow->count() }}</span>
        <span class="etr-stat__label">Rows</span>
    </div>
    @foreach (['Standard','Couple','Premium','Family'] as $type)
        @php $typeCount = $theatre->seats->where('seat_type', $type)->count(); @endphp
        @if ($typeCount > 0)
            <div class="etr-stat etr-stat--{{ strtolower($type) }}">
                <span class="etr-stat__value">{{ $typeCount }}</span>
                <span class="etr-stat__label">{{ $type }}</span>
            </div>
        @endif
    @endforeach
</div>

{{-- ── Seat layout ─────────────────────────────────────────── --}}
<div class="etr-layout-wrap">

    {{-- Screen --}}
    <div class="etr-screen-wrap">
        <div class="etr-screen"></div>
        <span class="etr-screen-label">SCREEN</span>
    </div>

    {{-- Rows --}}
    @if ($seatsByRow->isEmpty())
        <div class="ac-empty">
            <div class="ac-empty__icon">💺</div>
            <p class="ac-empty__text">No seats have been defined for this theatre yet.</p>
        </div>
    @else
        <div class="etr-rows">
            @foreach ($seatsByRow as $rowLabel => $seats)
                {{--
                    All seats in a row share the same seat_type.
                    We read the type from the first seat.
                --}}
                @php $rowType = $seats->first()->seat_type; @endphp

                <div class="etr-row" data-row="{{ $rowLabel }}">

                    {{-- Left row label --}}
                    <span class="etr-row__label">{{ $rowLabel }}</span>

                    {{-- Seat icons --}}
                    <div class="etr-row__seats">
                        @foreach ($seats->sortBy('seat_number') as $seat)

                            @if ($rowType === 'Couple')
                                {{--
                                    Couple seats: render each seat icon normally.
                                    A gap is inserted after every second seat (pair boundary).
                                --}}
                                <span class="sb-seat sb-seat--couple"></span>
                                @if ($loop->iteration % 2 === 0 && !$loop->last)
                                    <span class="sb-couple-gap"></span>
                                @endif

                            @elseif ($rowType === 'Premium')
                                <span class="sb-seat sb-seat--premium sb-seat--lg"></span>

                            @elseif ($rowType === 'Family')
                                <span class="sb-seat sb-seat--family sb-seat--lg"></span>

                            @else
                                {{-- Standard --}}
                                <span class="sb-seat sb-seat--standard"></span>
                            @endif

                        @endforeach
                    </div>

                    {{-- Right row label (mirror) --}}
                    <span class="etr-row__label etr-row__label--right">{{ $rowLabel }}</span>

                </div>{{-- /.etr-row --}}
            @endforeach
        </div>{{-- /.etr-rows --}}
    @endif

</div>{{-- /.etr-layout-wrap --}}

{{-- ── Seat type legend ────────────────────────────────────── --}}
<div class="etr-legend">
    <div class="etr-legend__item">
        <span class="sb-seat sb-seat--standard"></span>
        <span>Standard</span>
    </div>
    <div class="etr-legend__item">
        <span class="sb-seat sb-seat--couple"></span>
        <span>Couple</span>
    </div>
    <div class="etr-legend__item">
        <span class="sb-seat sb-seat--premium sb-seat--lg"></span>
        <span>Premium</span>
    </div>
    <div class="etr-legend__item">
        <span class="sb-seat sb-seat--family sb-seat--lg"></span>
        <span>Family</span>
    </div>
</div>

{{-- ── Back button ─────────────────────────────────────────── --}}
<div class="etr-footer-nav">
    <a href="{{ route('admin.cinema.index') }}" class="vc-back-btn">
        ← Back to Cinemas
    </a>
</div>

@endsection