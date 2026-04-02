{{--
    resources/views/branch_manager/bm_movie_formation.blade.php
    ─────────────────────────────────────────────────────────────
    TGV-style movie formation page for branch manager.
    Controller: BranchManagerMovieFormationController@show
    Data:
      $movie                – Movie with ->genres
      $cinema               – Cinema with ->city
      $hasApprovedShowtimes – bool
      $dateGroups           – array: [{date, label_day, label_num, label_month, theatres:[{name,times:[]}]}]
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', $movie->movie_name)

@section('bm_head_extras')
    @vite(['resources/css/bm_movie_formation.css', 'resources/js/bm_movie_formation.js'])
@endsection

@section('bm_content')

{{-- ── Back link ──────────────────────────────────────────── --}}
<a href="{{ route('manager.resources') }}" class="bm-back-link bmf-back">← Back to Resources</a>

{{-- ══════════════════════════════════════════════════════════
     FULL-WIDTH HERO POSTER
══════════════════════════════════════════════════════════ --}}
<div class="bmf-hero">
    @if (!empty($movie->landscape_poster))
        <img
            src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
            alt="{{ $movie->movie_name }}"
            class="bmf-hero__img"
        >
    @else
        <div class="bmf-hero__ph">🎬</div>
    @endif

    <div class="bmf-hero__overlay">
        <h1 class="bmf-hero__title">{{ $movie->movie_name }}</h1>
        <div class="bmf-hero__meta">
            @if ($movie->genres->isNotEmpty())
                <span>{{ $movie->genres->pluck('genre_name')->join(', ') }}</span>
                <span class="bmf-hero__sep">|</span>
            @endif
            @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
            <span>{{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} mins</span>
            <span class="bmf-hero__sep">|</span>
            <span>{{ $movie->language }}</span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SHOWTIMES SECTION — no card background (sits on page bg)
══════════════════════════════════════════════════════════ --}}
@if (!$hasApprovedShowtimes)

    <div class="bmf-no-showtimes">
        <span class="bmf-no-showtimes__icon">📋</span>
        <span class="bmf-no-showtimes__text">No approved showtimes yet for this movie at your cinema.</span>
    </div>

@else

    {{-- Hidden data bridge for JS --}}
    <div
        id="bmf-data"
        class="bmf-hidden"
        data-dates='{!! json_encode($dateGroups) !!}'
    ></div>

    {{-- ── Date strip ────────────────────────────────────── --}}
    <div class="bmf-date-strip-wrap">
        <div class="bmf-date-strip" id="bmf-date-strip">
            {{-- Populated by bm_movie_formation.js --}}
        </div>
    </div>

    {{-- ── Theatre + Showtimes panel ─────────────────────── --}}
    <div class="bmf-showtime-section" id="bmf-showtime-section">
        {{-- Populated by bm_movie_formation.js --}}
    </div>

@endif

@endsection