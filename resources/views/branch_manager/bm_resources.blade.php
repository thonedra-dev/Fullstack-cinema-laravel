{{--
    resources/views/branch_manager/bm_resources.blade.php
    Controller: BranchManagerResourceController@index
    Data:
      $cinema   – Cinema model
      $theatres – Theatre collection (with ->seats)
      $movies   – ACTIVE movies only (have at least one approved showtime)
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Resources')

@section('bm_head_extras')
    @vite(['resources/css/bm_resources.css'])
@endsection

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">← Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title"><span>Resources</span></h1>
    <p class="bm-page-header__sub">
        Active theatres and running movies for {{ $cinema->cinema_name }}.
        <a href="{{ route('manager.upcoming') }}" class="bm-resources__upcoming-link">
            View Upcoming →
        </a>
    </p>
</div>

{{-- ── Theatres ────────────────────────────────────────────── --}}
<div class="bm-section-title">Theatres</div>

@if ($theatres->isEmpty())
    <div class="bm-empty bm-resources__empty-gap">
        <div class="bm-empty__icon">🏟</div>
        <p class="bm-empty__text">No theatres defined yet.</p>
    </div>
@else
    <div class="bm-mini-grid bm-resources__theatre-grid">
        @foreach ($theatres as $theatre)
            <a
                href="{{ route('manager.theatre.formation', $theatre->theatre_id) }}"
                class="bm-mini-card bm-theatre-entry"
                aria-label="View {{ $theatre->theatre_name }} formation"
            >
                <div class="bm-mini-card__img-wrap">
                    @if ($theatre->theatre_poster)
                        <img src="{{ asset('images/theatres/' . $theatre->theatre_poster) }}"
                             alt="{{ $theatre->theatre_name }}" class="bm-mini-card__img">
                    @elseif ($theatre->theatre_icon)
                        <img src="{{ asset('images/theatres/' . $theatre->theatre_icon) }}"
                             alt="{{ $theatre->theatre_name }}" class="bm-mini-card__img">
                    @else
                        <div class="bm-mini-card__ph">🏟</div>
                    @endif
                    <div class="bm-theatre-entry__overlay">View Formation</div>
                </div>
                <div class="bm-mini-card__body">
                    <p class="bm-mini-card__name">{{ $theatre->theatre_name }}</p>
                    @php $seatCount = $theatre->seats->count(); @endphp
                    @if ($seatCount > 0)
                        <p class="bm-mini-card__sub">{{ $seatCount }} seats</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

{{-- ── Running Movies ──────────────────────────────────────── --}}
<div class="bm-section-title">Running Movies</div>

@if ($movies->isEmpty())
    <div class="bm-empty">
        <div class="bm-empty__icon">🎬</div>
        <p class="bm-empty__text">
            No movies have been scheduled yet.
            <a href="{{ route('manager.upcoming') }}" class="bm-resources__upcoming-link">
                Set up upcoming movies →
            </a>
        </p>
    </div>
@else
    <div class="bm-movie-grid">
        @foreach ($movies as $movie)
            <a
                href="{{ route('manager.movie.formation', $movie->movie_id) }}"
                class="bm-movie-card"
                aria-label="View {{ $movie->movie_name }} details"
            >
                <div class="bm-movie-card__poster-wrap">
                    @if (!empty($movie->portrait_poster))
                        <img src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                             alt="{{ $movie->movie_name }}" class="bm-movie-card__poster">
                    @else
                        <div class="bm-movie-card__poster-ph">🎬</div>
                    @endif
                </div>
                <div class="bm-movie-card__body">
                    <p class="bm-movie-card__title">{{ $movie->movie_name }}</p>
                    @if ($movie->genres->isNotEmpty())
                        <div class="bm-movie-card__genres">
                            @foreach ($movie->genres->take(3) as $genre)
                                <span class="bm-movie-card__genre">{{ $genre->genre_name }}</span>
                            @endforeach
                        </div>
                    @endif
                    <p class="bm-movie-card__meta">
                        @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
                        {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m · {{ $movie->language }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection