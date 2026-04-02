{{--
    resources/views/admin/movie_cinema_formation.blade.php
    ─────────────────────────────────────────────────────────────
    TGV-style movie formation page for Admin.
    Controller: AdminMovieFormationController@show
    Data:
      $movie                – Movie with ->genres
      $cinema               – Cinema with ->city
      $hasApprovedShowtimes – bool
      $dateGroups           – array: [{date, label_day, label_num, label_month, theatres:[{name,times:[]}]}]
--}}
@extends('admin.admin_team')

@section('page_title', $movie->movie_name)
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_cinema_formation.css', 'resources/js/movie_cinema_formation.js'])
@endsection

@section('content')

{{-- ── Admin Breadcrumb ───────────────────────────────────── --}}
<nav class="mcf-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.cinema.index') }}" class="mcf-breadcrumb__item">All Cinemas</a>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item">{{ $cinema->cinema_name }}</span>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item mcf-breadcrumb__item--current">{{ $movie->movie_name }}</span>
</nav>

{{-- ══════════════════════════════════════════════════════════
     FULL-WIDTH HERO POSTER (TGV Style)
══════════════════════════════════════════════════════════ --}}
<div class="mcf-hero">
    @if (!empty($movie->landscape_poster))
        <img
            src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
            alt="{{ $movie->movie_name }}"
            class="mcf-hero__img"
        >
    @else
        <div class="mcf-hero__ph">🎬</div>
    @endif

    <div class="mcf-hero__overlay">
        <h1 class="mcf-hero__title">{{ $movie->movie_name }}</h1>
        <div class="mcf-hero__meta">
            @if ($movie->genres->isNotEmpty())
                <span class="mcf-hero-rating">P12</span>
                <span>{{ $movie->genres->pluck('genre_name')->join(', ') }}</span>
                <span class="mcf-hero__sep">|</span>
            @endif
            @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
            <span>{{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} mins</span>
            <span class="mcf-hero__sep">|</span>
            <span>{{ $movie->language }}</span>
            <span class="mcf-hero__sep">|</span>
            <span style="color: var(--accent);">{{ $cinema->cinema_name }}</span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SHOWTIMES SECTION (TGV Style Mechanism)
══════════════════════════════════════════════════════════ --}}
@if (!$hasApprovedShowtimes)

    <div class="mcf-no-showtimes">
        <span class="mcf-no-showtimes__icon">📋</span>
        <span class="mcf-no-showtimes__text">No approved showtimes yet for this movie at {{ $cinema->cinema_name }}.</span>
    </div>

@else

    {{-- Hidden data bridge for JS --}}
    <div
        id="mcf-data"
        class="mcf-hidden"
        data-dates='{!! json_encode($dateGroups) !!}'
    ></div>

    {{-- ── Date strip ────────────────────────────────────── --}}
    <div class="mcf-date-strip-wrap">
        <div class="mcf-date-strip" id="mcf-date-strip">
            {{-- Populated by movie_cinema_formation.js --}}
        </div>
    </div>

    {{-- ── Theatre + Showtimes panel ─────────────────────── --}}
    <div class="mcf-showtime-section" id="mcf-showtime-section">
        {{-- Populated by movie_cinema_formation.js --}}
    </div>

@endif

@endsection