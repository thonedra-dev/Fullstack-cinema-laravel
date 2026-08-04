@extends('admin.admin_team')

@section('page_title', $movie->movie_name)
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite([
        'resources/css/admin_movie_live_detail.css',
        'resources/js/admin_movie_live_detail.js',
    ])
@endsection

@section('content')

<div class="ac-page-header mp-header">
    <div>
        <h1 class="ac-page-header__title">{{ $movie->movie_name }}</h1>
        <p class="ac-page-header__sub">Live cinemas, theatres, and showtimes for this movie.</p>
    </div>
    <a href="{{ route('admin.movies.now_showing') }}" class="mld-back-link">&larr; Back to Now Showing</a>
</div>

<div
    id="mldRoot"
    class="mld-layout"
    data-movie-id="{{ $movie->movie_id }}"
    data-theatres-url-template="{{ route('admin.movies.now_showing.theatres', ['movie' => $movie->movie_id, 'cinema' => '__CINEMA_ID__']) }}"
    data-showtimes-url-template="{{ route('admin.movies.now_showing.showtimes', ['movie' => $movie->movie_id, 'cinema' => '__CINEMA_ID__', 'theatre' => '__THEATRE_ID__']) }}"
    data-seats-url-template="{{ route('admin.showtimes.seats', ['showtime' => '__SHOWTIME_ID__']) }}"
>
    {{-- LEFT (≈70%): seat layout renders here on showtime click --}}
    <div id="mldSeatArea" class="mld-seat-area">
        <div class="ac-empty" style="padding:80px 20px;">
            <div class="ac-empty__icon">💺</div>
            <p class="ac-empty__text">Pick a cinema, theatre, and showtime to view the seat map.</p>
        </div>
    </div>

    {{-- RIGHT (max 30%): single-panel drill-down, no header --}}
    <aside id="mldSidebar" class="mld-sidebar">

        <button type="button" id="mldBackBtn" class="mld-back-btn" hidden>
            <span class="mld-back-btn__arrow">←</span>
            <span id="mldBackBtnLabel">Back</span>
        </button>

        {{-- PANEL 1: cinemas (server-rendered, always the root) --}}
        <div id="mldPanelCinemas" class="mld-panel mld-panel--active">
            @if ($cinemas->isEmpty())
                <p class="mld-empty-note">No cinema is currently showing this movie.</p>
            @else
                @foreach ($cinemas as $cinema)
                    <button
                        type="button"
                        class="mld-choice-btn mld-choice-btn--cinema"
                        data-cinema-id="{{ $cinema->cinema_id }}"
                        data-cinema-name="{{ $cinema->cinema_name }}"
                    >
                        <span class="mld-choice-btn__icon">🏢</span>
                        <span class="mld-choice-btn__label">{{ $cinema->cinema_name }}</span>
                        <span class="mld-choice-btn__chevron">›</span>
                    </button>
                @endforeach
            @endif
        </div>

        {{-- PANEL 2: theatres (populated by JS) --}}
        <div id="mldPanelTheatres" class="mld-panel"></div>

        {{-- PANEL 3: dates & showtimes (populated by JS) --}}
        <div id="mldPanelShowtimes" class="mld-panel"></div>

    </aside>
</div>

@endsection