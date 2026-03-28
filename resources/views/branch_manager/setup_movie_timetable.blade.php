{{--
    resources/views/branch_manager/setup_movie_timetable.blade.php
    ────────────────────────────────────────────────────────────────
    Dual-entry showtime setup page.
    Controller: BranchManagerShowtimeController@fromMovie / @fromTheatre
    Data:
      $cinema             – Cinema model
      $movie              – Movie model (null if theatre-first entry)
      $quota              – cinema_movie_quotas row (null if theatre-first)
      $theatres           – All Theatre models for this cinema with ->seats
      $assignedMovies     – All movies assigned to this cinema (for theatre-first picker)
      $existingShowtimes  – JSON array [{theatre_id, movie_id, start, end}]
      $preselectedMode    – 'movie' | 'theatre'
      $preselectedTheatre – Theatre model (null if movie-first)
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Setup Timetable')

@section('bm_head_extras')
    @vite(['resources/css/setup_timetable.css', 'resources/js/setup_timetable.js'])
@endsection

@section('bm_content')

{{-- Back link adapts by entry mode --}}
@if ($preselectedMode === 'movie')
    <a href="{{ route('manager.upcoming') }}" class="bm-back-link">← Back to Upcoming</a>
@else
    <a href="{{ route('manager.resources') }}" class="bm-back-link">← Back to Resources</a>
@endif

{{-- ══════════════════════════════════════════════════
     MOVIE HEADER (shown when movie is pre-selected)
     In theatre-first mode, a movie picker is shown instead.
══════════════════════════════════════════════════════ --}}
@if ($preselectedMode === 'movie' && $movie)
    <div class="smt-movie-hero">

        {{-- Landscape poster --}}
        <div class="smt-hero-img-wrap">
            @if (!empty($movie->landscape_poster))
                <img
                    src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                    alt="{{ $movie->movie_name }}"
                    class="smt-hero-img"
                >
            @else
                <div class="smt-hero-img-ph">🎬</div>
            @endif
            <div class="smt-hero-overlay">
                <h1 class="smt-hero-title">{{ $movie->movie_name }}</h1>
                <p class="smt-hero-meta">
                    @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
                    @if ($movie->genres->isNotEmpty())
                        {{ $movie->genres->pluck('genre_name')->join(', ') }} &nbsp;·&nbsp;
                    @endif
                    {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m &nbsp;·&nbsp; {{ $movie->language }}
                </p>
            </div>
        </div>

    </div>
@endif

{{-- ══════════════════════════════════════════════════
     SETUP FORM
══════════════════════════════════════════════════════ --}}
<div class="smt-layout">

    {{-- ── LEFT: Theatre list / selector ───────────── --}}
    <aside class="smt-sidebar">

        <div class="smt-sidebar__title">
            @if ($preselectedMode === 'theatre')
                🎬 Select Movie
            @else
                🏟 Select Theatre
            @endif
        </div>

        {{-- Theatre-first: movie picker --}}
        @if ($preselectedMode === 'theatre')
            <div class="smt-movie-picker">
                @if ($assignedMovies->isEmpty())
                    <p class="smt-empty-hint">No movies assigned to this cinema yet.</p>
                @else
                    @foreach ($assignedMovies as $am)
                        <label
                            class="smt-movie-pick-row"
                            for="pick_movie_{{ $am->movie_id }}"
                            data-movie-id="{{ $am->movie_id }}"
                            data-movie-name="{{ $am->movie_name }}"
                            data-runtime="{{ $am->runtime }}"
                        >
                            <input
                                type="radio"
                                id="pick_movie_{{ $am->movie_id }}"
                                name="smt_movie_pick"
                                value="{{ $am->movie_id }}"
                                class="smt-movie-radio"
                            >
                            <div class="smt-movie-pick-info">
                                <p class="smt-movie-pick-name">{{ $am->movie_name }}</p>
                                <p class="smt-movie-pick-meta">
                                    @php $h2 = intdiv($am->runtime, 60); $m2 = $am->runtime % 60; @endphp
                                    {{ $h2 > 0 ? $h2 . 'h ' : '' }}{{ $m2 }}m
                                </p>
                            </div>
                        </label>
                    @endforeach
                @endif
            </div>

        {{-- Movie-first: theatre list --}}
        @else
            <div class="smt-theatre-list">
                @foreach ($theatres as $theatre)
                    <label
                        class="smt-theatre-row"
                        for="theatre_{{ $theatre->theatre_id }}"
                        data-theatre-id="{{ $theatre->theatre_id }}"
                    >
                        <input
                            type="radio"
                            id="theatre_{{ $theatre->theatre_id }}"
                            name="smt_theatre_pick"
                            value="{{ $theatre->theatre_id }}"
                            class="smt-theatre-radio"
                        >
                        <div class="smt-theatre-info">
                            <p class="smt-theatre-name">{{ $theatre->theatre_name }}</p>
                            <p class="smt-theatre-seats">{{ $theatre->seats->count() }} seats</p>
                        </div>
                    </label>
                @endforeach
            </div>
        @endif

    </aside>

    {{-- ── RIGHT: Seat layout + time/date config ─── --}}
    <div class="smt-main">

        {{-- Theatre-first: also show theatre list here --}}
        @if ($preselectedMode === 'theatre' && $preselectedTheatre)
            <div class="smt-preselected-theatre-banner">
                🏟 <strong>{{ $preselectedTheatre->theatre_name }}</strong>
                &nbsp;·&nbsp; {{ $preselectedTheatre->seats->count() }} seats
            </div>
        @endif

        {{-- Seat layout preview --}}
        <div class="smt-seat-section" id="smt-seat-section">
            <div class="smt-seat-section__title">Seat Layout</div>

            {{-- Seat data as JSON for JS --}}
            <div
                id="smt-seat-data-json"
                class="smt-hidden"
                data-theatres='{!! json_encode(
                    $theatres->map(fn($t) => [
                        "id"   => $t->theatre_id,
                        "name" => $t->theatre_name,
                        "seats" => $t->seats->groupBy("row_label")->map(fn($seats, $row) => [
                            "label" => $row,
                            "seats" => $seats->map(fn($s) => [
                                "seat_id"     => $s->seat_id,
                                "seat_number" => $s->seat_number,
                                "seat_type"   => $s->seat_type,
                            ])->values(),
                        ])->values(),
                    ])->values()
                ) !!}'
                data-preselected-mode="{{ $preselectedMode }}"
                data-preselected-theatre-id="{{ $preselectedTheatre?->theatre_id ?? '' }}"
                data-preselected-movie-id="{{ $movie?->movie_id ?? '' }}"
                data-preselected-runtime="{{ $movie?->runtime ?? '' }}"
                data-existing-showtimes='{!! json_encode($existingShowtimes) !!}'
            ></div>

            {{-- Screen indicator --}}
            <div class="smt-screen-wrap">
                <div class="smt-screen"></div>
                <span class="smt-screen-label">SCREEN</span>
            </div>

            <div id="smt-seat-preview" class="smt-seat-preview">
                <p class="smt-seat-preview__hint" id="smt-seat-hint">
                    @if ($preselectedMode === 'movie')
                        Select a theatre to preview its seat layout.
                    @else
                        Select a movie, then the seat layout will appear.
                    @endif
                </p>
            </div>

            {{-- Legend --}}
            <div class="smt-seat-legend">
                <div class="smt-legend-item"><span class="sb-seat sb-seat--standard"></span> Standard</div>
                <div class="smt-legend-item"><span class="sb-seat sb-seat--couple"></span> Couple</div>
                <div class="smt-legend-item"><span class="sb-seat sb-seat--premium sb-seat--lg"></span> Premium</div>
                <div class="smt-legend-item"><span class="sb-seat sb-seat--family sb-seat--lg"></span> Family</div>
            </div>
        </div>

        {{-- ── Time + Date + Submit ─────────────────── --}}
        <form
            action="{{ route('manager.showtimes.store') }}"
            method="POST"
            id="smt-form"
            novalidate
        >
            @csrf

            {{-- Hidden fields — filled by JS or preselected --}}
            <input type="hidden" name="movie_id"   id="smt-hidden-movie"
                   value="{{ $movie?->movie_id ?? '' }}">
            <input type="hidden" name="theatre_id" id="smt-hidden-theatre"
                   value="{{ $preselectedTheatre?->theatre_id ?? '' }}">
            {{-- dates[] array — appended by JS --}}

            {{-- Error flash --}}
            @if (session('bm_error'))
                <div class="bm-alert bm-alert--error" style="margin-bottom:16px;">
                    <span>✕</span> {{ session('bm_error') }}
                </div>
            @endif

            {{-- ── Alarm clock time picker ──────────── --}}
            <div class="smt-time-section">
                <div class="smt-section-label">Showtime Start</div>

                <div class="smt-clock">

                    <div class="smt-clock__col" id="smt-hour-col">
                        <button type="button" class="smt-clock__arrow" data-target="hour" data-dir="up">▲</button>
                        <div class="smt-clock__digit" id="smt-hour-display">07</div>
                        <button type="button" class="smt-clock__arrow" data-target="hour" data-dir="down">▼</button>
                    </div>

                    <div class="smt-clock__sep">:</div>

                    <div class="smt-clock__col" id="smt-minute-col">
                        <button type="button" class="smt-clock__arrow" data-target="minute" data-dir="up">▲</button>
                        <div class="smt-clock__digit" id="smt-minute-display">00</div>
                        <button type="button" class="smt-clock__arrow" data-target="minute" data-dir="down">▼</button>
                    </div>

                    <div class="smt-clock__sep">:</div>

                    <div class="smt-clock__col">
                        <button type="button" class="smt-clock__arrow" data-target="ampm" data-dir="up">▲</button>
                        <div class="smt-clock__digit smt-clock__digit--ampm" id="smt-ampm-display">AM</div>
                        <button type="button" class="smt-clock__arrow" data-target="ampm" data-dir="down">▼</button>
                    </div>

                </div>{{-- /.smt-clock --}}

                {{-- Computed end time preview --}}
                <p class="smt-end-time-preview" id="smt-end-preview">
                    End time: <span id="smt-end-time-val">—</span>
                </p>

                {{-- Hidden time inputs --}}
                <input type="hidden" name="hour"   id="smt-input-hour"   value="7">
                <input type="hidden" name="minute" id="smt-input-minute" value="0">
                <input type="hidden" name="ampm"   id="smt-input-ampm"   value="AM">
            </div>

            {{-- ── Date picker ──────────────────────── --}}
            <div class="smt-date-section">
                <div class="smt-section-label">
                    📅 Select Date(s)
                    <span class="smt-section-hint">(click multiple to batch-schedule)</span>
                </div>

                <div class="smt-calendar-wrap">
                    <div class="smt-cal-header">
                        <button type="button" class="smt-cal-nav" id="smt-cal-prev">‹</button>
                        <span class="smt-cal-month-label" id="smt-cal-month"></span>
                        <button type="button" class="smt-cal-nav" id="smt-cal-next">›</button>
                    </div>
                    <div class="smt-cal-weekdays">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span>
                        <span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div class="smt-cal-grid" id="smt-cal-grid"></div>
                </div>

                {{-- Selected dates preview chips --}}
                <div class="smt-dates-preview" id="smt-dates-preview"></div>

                {{-- Hidden dates[] inputs injected by JS --}}
                <div id="smt-hidden-dates"></div>
            </div>

            {{-- ── Conflict preview area ──────────────── --}}
            <div class="smt-conflict-area vc-hidden" id="smt-conflict-area">
                <div class="smt-conflict-title">⚠ Time Conflicts Detected</div>
                <div id="smt-conflict-list" class="smt-conflict-list"></div>
            </div>

            {{-- ── Submit ──────────────────────────────── --}}
            <button
                type="submit"
                class="bm-btn bm-btn--primary smt-submit"
                id="smt-submit-btn"
                style="margin-top:24px;"
            >
                🗓 Schedule Showtimes
            </button>

        </form>

    </div>{{-- /.smt-main --}}

</div>{{-- /.smt-layout --}}

@endsection