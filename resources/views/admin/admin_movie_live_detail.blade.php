@extends('admin.admin_team')

@section('page_title', $movie->movie_name)
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite([
        'resources/css/admin_movie_live_detail.css',
        'resources/js/admin_movie_live_detail.js',
    ])

    {{--
        Scoped additions for: sidebar-only scrolling, the new Dates panel,
        and the seat/financial flip header. Move these into
        admin_movie_live_detail.css whenever convenient — kept inline here
        so nothing in your existing stylesheet needs to be guessed at.
    --}}
    <style>
        /* Sidebar owns its own scroll — wheel no longer bubbles to the page */
        #mldSidebar {
            max-height: calc(100vh - 140px);
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
        }

        /* Dates panel buttons reuse .mld-choice-btn styling, just a new hook */
        .mld-choice-btn--date .mld-choice-btn__icon { opacity: .9; }
        .mld-choice-btn--date .mld-choice-btn__count {
            margin-left: auto;
            margin-right: 8px;
            font-size: .78rem;
            opacity: .65;
        }

        /* Seat-area header: title + flip toggle */
        .mld-seat-area__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .mld-seat-area__title { margin: 0; }

        .mld-view-toggle {
            display: inline-flex;
            gap: 4px;
            background: rgba(127,127,127,.12);
            border-radius: 10px;
            padding: 3px;
            flex-shrink: 0;
        }
        .mld-view-toggle-btn {
            border: none;
            background: transparent;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
            transition: background .15s ease, transform .1s ease;
            opacity: .6;
        }
        .mld-view-toggle-btn:hover { opacity: 1; }
        .mld-view-toggle-btn:active { transform: scale(.94); }
        .mld-view-toggle-btn--active {
            background: rgba(127,127,127,.28);
            opacity: 1;
        }

        /* Smooth swap between seat-map / financial views */
        .mld-view-fade {
            animation: mldFadeIn .18s ease both;
        }
        @keyframes mldFadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Financial report table */
        .mld-finance-wrap { overflow-x: auto; }
        .mld-finance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }
        .mld-finance-table th,
        .mld-finance-table td {
            padding: 9px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(127,127,127,.18);
            white-space: nowrap;
        }
        .mld-finance-table th {
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            opacity: .65;
        }
        .mld-finance-table tbody tr:hover { background: rgba(127,127,127,.06); }
        .mld-finance-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 600;
        }
        .mld-finance-status--confirmed,
        .mld-finance-status--succeeded { background: rgba(40,180,100,.16); color: #1e9e5c; }
        .mld-finance-status--pending   { background: rgba(230,170,30,.18); color: #b5850f; }
        .mld-finance-status--failed,
        .mld-finance-status--cancelled { background: rgba(220,60,60,.16); color: #c23838; }
        .mld-finance-status--none      { background: rgba(127,127,127,.14); color: #888; }

        .mld-finance-summary {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin: 4px 0 16px;
            padding: 12px 16px;
            border-radius: 10px;
            background: rgba(127,127,127,.08);
        }
        .mld-finance-summary__item { display: flex; flex-direction: column; gap: 2px; }
        .mld-finance-summary__label { font-size: .72rem; opacity: .6; text-transform: uppercase; letter-spacing: .03em; }
        .mld-finance-summary__value { font-size: 1.05rem; font-weight: 700; }
    </style>
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
            @if ($cinemas->isEmpty())
                <p class="mld-empty-note">No cinema has run this movie.</p>
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

        {{-- PANEL 3: dates (populated by JS) --}}
        <div id="mldPanelDates" class="mld-panel"></div>

        {{-- PANEL 4: showtimes for the chosen date (populated by JS) --}}
        <div id="mldPanelShowtimes" class="mld-panel"></div>

    </aside>
</div>

@endsection