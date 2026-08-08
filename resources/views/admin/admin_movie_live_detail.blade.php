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
        <p class="ac-page-header__sub">All cinemas, theatres, and showtimes for this movie (past &amp; upcoming).</p>
    </div>
    <a href="{{ route('admin.movies.now_showing') }}" class="mld-back-link">&larr; Back to Now Showing</a>
</div>

<div
    id="mldRoot"
    class="mld-layout"
    data-movie-id="{{ $movie->movie_id }}"
    data-theatres-url-template="{{ route('admin.movies.now_showing.theatres', ['movie' => $movie->movie_id, 'cinema' => '__CINEMA_ID__']) }}"
    data-dates-url-template="{{ route('admin.movies.now_showing.dates', ['movie' => $movie->movie_id, 'cinema' => '__CINEMA_ID__', 'theatre' => '__THEATRE_ID__']) }}"
    data-showtimes-url-template="{{ route('admin.movies.now_showing.showtimes', ['movie' => $movie->movie_id, 'cinema' => '__CINEMA_ID__', 'theatre' => '__THEATRE_ID__']) }}"
    data-seats-url-template="{{ route('admin.showtimes.seats', ['showtime' => '__SHOWTIME_ID__']) }}"
    data-financials-url-template="{{ route('admin.showtimes.financials', ['showtime' => '__SHOWTIME_ID__']) }}"
>
    {{-- LEFT (≈70%): seat layout / financial report flip renders here --}}
    <div id="mldSeatArea" class="mld-seat-area">
        <div class="ac-empty" style="padding:80px 20px;">
            <div class="ac-empty__icon">💺</div>
            <p class="ac-empty__text">Pick a cinema, theatre, date, and showtime to view the seat map.</p>
        </div>
    </div>

    {{-- RIGHT (max 30%): single-panel drill-down, own scroll region --}}
    <aside id="mldSidebar" class="mld-sidebar">

        <button type="button" id="mldBackBtn" class="mld-back-btn" hidden>
            <span class="mld-back-btn__arrow">←</span>
            <span id="mldBackBtnLabel">Back</span>
        </button>

        {{-- PANEL 1: cinemas (server-rendered, always the root) --}}
        <div id="mldPanelCinemas" class="mld-panel mld-panel--active">
            <div class="mld-panel__title">Cinema Selection</div>
            @if ($cinemas->isEmpty())
                <p class="mld-empty-note">No cinema has run this movie.</p>
            @else
               @foreach ($cinemas as $cinema)
    <button
        type="button"
        class="mld-choice-btn mld-choice-btn--cinema"
        data-cinema-id="{{ $cinema->cinema_id }}"
        data-cinema-name="{{ $cinema->cinema_name }}"
        data-cinema-address="{{ $cinema->cinema_address }}"
        data-cinema-contact="{{ $cinema->cinema_contact }}"
        data-cinema-description="{{ $cinema->cinema_description }}"
        data-cinema-picture="{{ $cinema->cinema_picture ? asset('images/cinemas/' . $cinema->cinema_picture) : '' }}"
        data-city-name="{{ $cinema->city_name }}"
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

        {{-- PANEL 3: dates (populated by JS) --}}
        <div id="mldPanelDates" class="mld-panel"></div>

        {{-- PANEL 4: showtimes for the chosen date (populated by JS) --}}
        <div id="mldPanelShowtimes" class="mld-panel"></div>

    </aside>
</div>

@endsection