{{--
    resources/views/branch_manager/setup_movie_timetable.blade.php
    ────────────────────────────────────────────────────────────────
    Multi-slot showtime setup page.
    Controller: BranchManagerShowtimeController@fromMovie / @fromTheatre / @store
    Data:
      $cinema             – Cinema model
      $movie              – Movie model (null if theatre-first entry)
      $quota              – cinema_movie_quotas row (null if theatre-first)
      $theatres           – All Theatre models for this cinema with ->seats
      $assignedMovies     – All movies assigned to this cinema (for theatre-first picker)
      $existingShowtimes  – JSON array [{theatre_id, movie_id, start, end}]
      $preselectedMode    – 'movie' | 'theatre'
      $preselectedTheatre – Theatre model (null if movie-first)

    Flow:
      1. Pick a theatre (or movie in theatre-first mode)
      2. Set a start time on the clock
      3. Pick one or more dates
      4. Click "Add to Schedule" → slots staged in preview
      5. Repeat (same theatre/different time, or different theatre)
      6. Click "Submit Proposal" to POST schedule_json
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Setup Timetable')

@section('bm_head_extras')
    @vite(['resources/css/setup_timetable.css', 'resources/js/setup_timetable.js'])
@endsection

@section('bm_content')

{{-- ── Back link ─────────────────────────────────────────── --}}
@if ($preselectedMode === 'movie')
    <a href="{{ route('manager.upcoming') }}" class="bm-back-link">← Back to Upcoming</a>
@else
    <a href="{{ route('manager.resources') }}" class="bm-back-link">← Back to Resources</a>
@endif

{{-- ══════════════════════════════════════════════════════════
     MOVIE HERO (movie-first mode only)
══════════════════════════════════════════════════════════ --}}
@if ($preselectedMode === 'movie' && $movie)
    <div class="smt-movie-hero">
        <div class="smt-hero-img-wrap">
            @if (!empty($movie->landscape_poster))
                <img src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                     alt="{{ $movie->movie_name }}"
                     class="smt-hero-img">
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

{{-- ══════════════════════════════════════════════════════════
     MAIN FORM — wraps entire page so schedule_json reaches store()
══════════════════════════════════════════════════════════ --}}
<form
    action="{{ route('manager.showtimes.store') }}"
    method="POST"
    id="smt-form"
    novalidate
>
    @csrf

    {{-- Global hidden fields --}}
    <input type="hidden" name="movie_id"      id="smt-hidden-movie"
           value="{{ $movie?->movie_id ?? '' }}">
    <input type="hidden" name="schedule_json" id="smt-schedule-json" value="">
    <input type="hidden" name="replace_rejected" id="smt-replace-rejected" value="0">

    {{-- ── Inline data bridge for JS ─────────────────────── --}}
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
        data-has-rejected-proposal="{{ $rejectedProposal ? '1' : '0' }}"
        data-rejected-note="{{ e($rejectedProposal?->admin_note ?? '') }}"
        data-existing-showtimes='{!! json_encode($existingShowtimes) !!}'
    ></div>

    {{-- ══════════════════════════════════════════════════════
         TWO-PANEL LAYOUT: Sidebar + Main Editing Area
    ══════════════════════════════════════════════════════ --}}
    <div class="smt-layout">

        {{-- ── LEFT SIDEBAR ──────────────────────────────── --}}
        <aside class="smt-sidebar">
            <div class="smt-sidebar__title">
                @if ($preselectedMode === 'theatre') 🎬 Select Movie
                @else 🏟 Select Theatre
                @endif
            </div>

            {{-- Theatre-first: movie picker --}}
            @if ($preselectedMode === 'theatre')
                <div class="smt-movie-picker">
                    @if ($assignedMovies->isEmpty())
                        <p class="smt-empty-hint">No movies assigned to this cinema yet.</p>
                    @else
                        @foreach ($assignedMovies as $am)
                            @php $h2 = intdiv($am->runtime, 60); $m2 = $am->runtime % 60; @endphp
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

        {{-- ── RIGHT MAIN AREA ───────────────────────────── --}}
        <div class="smt-main">

            {{-- Theatre-first: preselected theatre banner --}}
            @if ($preselectedMode === 'theatre' && $preselectedTheatre)
                <div class="smt-preselected-theatre-banner">
                    🏟 <strong>{{ $preselectedTheatre->theatre_name }}</strong>
                    &nbsp;·&nbsp; {{ $preselectedTheatre->seats->count() }} seats
                </div>
            @endif

            {{-- ── Seat layout preview ─────────────────── --}}
            <div class="smt-seat-section">
                <div class="smt-seat-section__title">Seat Layout</div>
                <div class="smt-screen-wrap">
                    <div class="smt-screen"></div>
                    <span class="smt-screen-label">SCREEN</span>
                </div>
                <div id="smt-seat-preview" class="smt-seat-preview">
                    <p class="smt-seat-preview__hint" id="smt-seat-hint">
                        @if ($preselectedMode === 'movie')
                            Select a theatre to preview its seat layout.
                        @else
                            Select a movie to activate scheduling.
                        @endif
                    </p>
                </div>
                <div class="smt-seat-legend">
                    <div class="smt-legend-item"><span class="sb-seat sb-seat--standard"></span> Standard</div>
                    <div class="smt-legend-item"><span class="sb-seat sb-seat--couple"></span> Couple</div>
                    <div class="smt-legend-item"><span class="sb-seat sb-seat--premium sb-seat--lg"></span> Premium</div>
                    <div class="smt-legend-item"><span class="sb-seat sb-seat--family sb-seat--lg"></span> Family</div>
                </div>
            </div>

            {{-- ── Alarm clock time picker ──────────────── --}}
            <div class="smt-time-section">
                <div class="smt-section-label">🕐 Showtime Start</div>

                <div class="smt-clock">
                    <div class="smt-clock__col">
                        <button type="button" class="smt-clock__arrow" data-target="hour"   data-dir="up">▲</button>
                        <div class="smt-clock__digit" id="smt-hour-display">07</div>
                        <button type="button" class="smt-clock__arrow" data-target="hour"   data-dir="down">▼</button>
                    </div>
                    <div class="smt-clock__sep">:</div>
                    <div class="smt-clock__col">
                        <button type="button" class="smt-clock__arrow" data-target="minute" data-dir="up">▲</button>
                        <div class="smt-clock__digit" id="smt-minute-display">00</div>
                        <button type="button" class="smt-clock__arrow" data-target="minute" data-dir="down">▼</button>
                    </div>
                    <div class="smt-clock__sep">:</div>
                    <div class="smt-clock__col">
                        <button type="button" class="smt-clock__arrow" data-target="ampm"   data-dir="up">▲</button>
                        <div class="smt-clock__digit smt-clock__digit--ampm" id="smt-ampm-display">AM</div>
                        <button type="button" class="smt-clock__arrow" data-target="ampm"   data-dir="down">▼</button>
                    </div>
                </div>

                <p class="smt-end-time-preview">
                    Ends at: <span id="smt-end-time-val">—</span>
                </p>
            </div>

            {{-- ── Date picker ──────────────────────────── --}}
            {{-- ── Date picker ──────────────────────────── --}}
<div class="smt-date-section">
    <div class="smt-section-label">
        📅 Select Date(s)
        <span class="smt-section-hint">(click multiple to batch-schedule under this time)</span>
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

    {{-- Selected dates chips preview --}}
    <div class="smt-dates-preview" id="smt-dates-preview"></div>
</div>

{{-- ── NEW: Existing Showtimes on clicked date ── --}}
<div class="smt-existing-showtimes" id="smt-existing-showtimes">
    <div class="smt-section-label">📋 Existing Showtimes on this Date</div>
    <div id="smt-existing-list" class="smt-existing-list">
        <p class="smt-existing-hint">Click a date to see approved showtimes.</p>
    </div>
</div>

{{-- ── Add to Schedule button ───────────────── --}}
<button type="button" class="smt-add-slot-btn" id="smt-add-slot-btn">➕ Add to Schedule</button>

            {{-- ── Conflict / validation area ──────────── --}}
            <div class="smt-conflict-area vc-hidden" id="smt-conflict-area">
                <div class="smt-conflict-title">⚠ Cannot Add Slot</div>
                <div id="smt-conflict-list" class="smt-conflict-list"></div>
            </div>

        </div>{{-- /.smt-main --}}

    </div>{{-- /.smt-layout --}}

    {{-- ══════════════════════════════════════════════════════════
         SCHEDULE PREVIEW PANEL (populated by JS)
    ══════════════════════════════════════════════════════════ --}}
    @include('branch_manager.partials._schedule_preview')

   {{-- ══════════════════════════════════════════════════════════
     SERVER-SIDE CONFLICT POPUP
     Rendered only when store() flashes bm_conflicts.
     JS (initConflictModal) auto-opens it on DOMContentLoaded.
══════════════════════════════════════════════════════════ --}}
@if (session('bm_conflicts'))
<div class="smt-cflct-overlay" id="smt-cflct-overlay"
     style="display:none;" data-auto-open="1">
    <div class="smt-cflct-modal">

        <div class="smt-cflct-modal__header">
            <span class="smt-cflct-modal__title">⚠ Schedule Conflicts Detected</span>
            <button type="button" class="smt-cflct-close smt-cflct-modal__x">✕</button>
        </div>

        <p class="smt-cflct-modal__sub">
            The slots below clash with already-approved showtimes in the same theatre.
            Please adjust your dates or times and resubmit.
        </p>

        <div class="smt-cflct-list">
            @foreach (session('bm_conflicts') as $c)
            <div class="smt-cflct-card">
                <div class="smt-cflct-card__poster">
                    @if (!empty($c['movie_poster']))
                        <img src="{{ asset('images/movies/' . $c['movie_poster']) }}"
                             alt="{{ $c['movie_name'] }}">
                    @else
                        <div class="smt-cflct-card__poster-ph">🎬</div>
                    @endif
                </div>
                <div class="smt-cflct-card__body">
                    <p class="smt-cflct-card__movie">{{ $c['movie_name'] }}</p>
                    <p class="smt-cflct-card__theatre">🏟 {{ $c['theatre_name'] }}</p>
                    <div class="smt-cflct-card__rows">
                        <div class="smt-cflct-row smt-cflct-row--yours">
                            <span class="smt-cflct-row__label">Your proposed</span>
                            <span class="smt-cflct-row__val">
                                {{ $c['proposed_date'] }} · {{ $c['proposed_time'] }}
                            </span>
                        </div>
                        <div class="smt-cflct-row smt-cflct-row--existing">
                            <span class="smt-cflct-row__label">Clashes with</span>
                            <span class="smt-cflct-row__val">
                                {{ $c['conflict_start'] }} → {{ $c['conflict_end'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <button type="button" class="smt-cflct-close smt-cflct-modal__dismiss">
            Got it — I'll fix my schedule
        </button>

    </div>
</div>
@endif

@if ($rejectedProposal)
<div class="smt-resubmit-overlay" id="smt-resubmit-overlay" style="display:none;">
    <div class="smt-resubmit-modal" role="dialog" aria-modal="true" aria-labelledby="smt-resubmit-title">
        <div class="smt-resubmit-modal__mark">!</div>
        <div class="smt-resubmit-modal__body">
            <p class="smt-resubmit-modal__eyebrow">Rejected Proposal Replacement</p>
            <h2 id="smt-resubmit-title">Replace the old proposal?</h2>
            <p class="smt-resubmit-modal__text">
                Finalizing this revised timetable will delete the rejected proposal and submit this new schedule for admin review.
            </p>
            @if ($rejectedProposal->admin_note)
                <p class="smt-resubmit-modal__note">
                    <strong>Admin note:</strong> {{ $rejectedProposal->admin_note }}
                </p>
            @endif
        </div>
        <div class="smt-resubmit-modal__actions">
            <button type="button" class="smt-resubmit-modal__deny" id="smt-resubmit-deny">
                Keep Editing
            </button>
            <button type="button" class="smt-resubmit-modal__accept" id="smt-resubmit-accept">
                Replace and Submit
            </button>
        </div>
    </div>
</div>
@endif

    {{-- ── Final submit row ────────────────────────────────── --}}
    <div class="smt-submit-row">
        <button
            type="submit"
            class="bm-btn bm-btn--primary smt-submit-btn"
            id="smt-submit-btn"
            disabled
        >
            🗓 Submit Proposal
        </button>
    </div>

</form>

@endsection
