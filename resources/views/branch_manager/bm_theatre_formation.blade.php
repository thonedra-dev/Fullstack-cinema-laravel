{{--
    resources/views/branch_manager/bm_theatre_formation.blade.php
    ──────────────────────────────────────────────────────────────
    Theatre formation page — three-column layout.
    Controller: BranchManagerTheatreFormationController@show

    Data:
      $cinema        – Cinema model
      $theatre       – Theatre model
      $seatRows      – Collection keyed by row_label
      $seatStats     – {total, rows, standard, couple, premium, family}
      $allMovies     – JSON string (array of movie objects)
      $showtimesJson – JSON string (array of {movie_id, date, start, start_fmt, end, end_fmt})

    Layout (left → middle → right):
      LEFT  : All movies that show in this theatre (expandable list)
              + when a movie is clicked: becomes the "active movie"
      MIDDLE: Seat formation for this theatre (read-only, always visible)
      RIGHT : Mini calendar (single date select)
              + when date chosen AND active movie chosen:
                display matching start times as clickable chips
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', $theatre->theatre_name)

@section('bm_head_extras')
    @vite(['resources/css/bm_theatre_formation.css', 'resources/js/bm_theatre_formation.js'])
@endsection

@section('bm_content')

{{-- Back link ─────────────────────────────────────────────── --}}
<a href="{{ route('manager.resources') }}" class="bm-back-link">← Back to Resources</a>

{{-- Page header ────────────────────────────────────────────── --}}
<div class="btf-header">
    <div class="btf-header__left">
        <h1 class="btf-header__title">{{ $theatre->theatre_name }}</h1>
        <p class="btf-header__sub">{{ $cinema->cinema_name }}</p>
    </div>
</div>

{{-- Seat stats chips ───────────────────────────────────────── --}}
<div class="btf-stats-row">
    <div class="btf-stat-chip">
        <span class="btf-stat-chip__num">{{ $seatStats['total'] }}</span>
        <span class="btf-stat-chip__label">Total Seats</span>
    </div>
    <div class="btf-stat-chip">
        <span class="btf-stat-chip__num">{{ $seatStats['rows'] }}</span>
        <span class="btf-stat-chip__label">Rows</span>
    </div>
    @if ($seatStats['standard'] > 0)
    <div class="btf-stat-chip btf-stat-chip--standard">
        <span class="btf-stat-chip__num">{{ $seatStats['standard'] }}</span>
        <span class="btf-stat-chip__label">Standard</span>
    </div>
    @endif
    @if ($seatStats['couple'] > 0)
    <div class="btf-stat-chip btf-stat-chip--couple">
        <span class="btf-stat-chip__num">{{ $seatStats['couple'] }}</span>
        <span class="btf-stat-chip__label">Couple</span>
    </div>
    @endif
    @if ($seatStats['premium'] > 0)
    <div class="btf-stat-chip btf-stat-chip--premium">
        <span class="btf-stat-chip__num">{{ $seatStats['premium'] }}</span>
        <span class="btf-stat-chip__label">Premium</span>
    </div>
    @endif
    @if ($seatStats['family'] > 0)
    <div class="btf-stat-chip btf-stat-chip--family">
        <span class="btf-stat-chip__num">{{ $seatStats['family'] }}</span>
        <span class="btf-stat-chip__label">Family</span>
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════
     THREE-COLUMN LAYOUT
══════════════════════════════════════════════════════════ --}}
<div class="btf-layout">

    {{-- ── LEFT: Movie list ───────────────────────────────── --}}
    <aside class="btf-col btf-col--left">
        <div class="btf-panel-label">Movies in this Theatre</div>
        <div class="btf-movie-list" id="btf-movie-list">
            {{-- Populated by JS from $allMovies JSON --}}
        </div>
    </aside>

    {{-- ── MIDDLE: Seat formation ─────────────────────────── --}}
    <div class="btf-col btf-col--middle">
        <div class="btf-panel-label">Seat Formation</div>

        {{-- Screen --}}
        <div class="btf-screen-wrap">
            <div class="btf-screen-glow"></div>
            <div class="btf-screen"></div>
            <p class="btf-screen-label">SCREEN</p>
        </div>

        {{-- Seat legend --}}
        <div class="btf-seat-legend">
            <div class="btf-legend-item"><span class="btf-seat-dot btf-seat-dot--standard"></span>Standard</div>
            <div class="btf-legend-item"><span class="btf-seat-dot btf-seat-dot--couple"></span>Couple</div>
            <div class="btf-legend-item"><span class="btf-seat-dot btf-seat-dot--premium"></span>Premium</div>
            <div class="btf-legend-item"><span class="btf-seat-dot btf-seat-dot--family"></span>Family</div>
        </div>

        {{-- Seat map —— rendered from PHP so it is always visible ──── --}}
        <div class="btf-seat-map" id="btf-seat-map">
            @forelse ($seatRows as $rowLabel => $seats)
                <div class="btf-seat-row">
                    <span class="btf-row-label">{{ $rowLabel }}</span>
                    <div class="btf-row-seats">
                        @php $seatList = $seats->values(); $i = 0; $total = $seatList->count(); @endphp
                        @while ($i < $total)
                            @php
                                $seat = $seatList[$i];
                                $type = strtolower($seat->seat_type);
                            @endphp

                            @if ($type === 'couple' && $i + 1 < $total && strtolower($seatList[$i+1]->seat_type) === 'couple')
                                <div class="btf-couple-pair">
                                    <span class="btf-seat btf-seat--couple" title="{{ $rowLabel }}{{ $seat->seat_number }}"></span>
                                    <span class="btf-seat btf-seat--couple" title="{{ $rowLabel }}{{ $seatList[$i+1]->seat_number }}"></span>
                                </div>
                                @php $i += 2; @endphp
                            @elseif ($type === 'premium')
                                <span class="btf-seat btf-seat--premium btf-seat--lg" title="{{ $rowLabel }}{{ $seat->seat_number }}"></span>
                                @php $i++; @endphp
                            @elseif ($type === 'family')
                                <span class="btf-seat btf-seat--family btf-seat--lg" title="{{ $rowLabel }}{{ $seat->seat_number }}"></span>
                                @php $i++; @endphp
                            @else
                                <span class="btf-seat btf-seat--standard" title="{{ $rowLabel }}{{ $seat->seat_number }}"></span>
                                @php $i++; @endphp
                            @endif
                        @endwhile
                    </div>
                    <span class="btf-row-label">{{ $rowLabel }}</span>
                </div>
            @empty
                <p class="btf-no-seats">No seats defined for this theatre.</p>
            @endforelse
        </div>

    </div>{{-- /.btf-col--middle --}}

    {{-- ── RIGHT: Calendar + Showtimes ──────────────────────── --}}
    <aside class="btf-col btf-col--right">

        <div class="btf-panel-label">Schedule</div>

        {{-- Mini calendar ─────────────────────────────────── --}}
        <div class="btf-calendar-wrap">
            <div class="btf-cal-header">
                <button type="button" class="btf-cal-nav" id="btf-cal-prev">‹</button>
                <span class="btf-cal-month" id="btf-cal-month"></span>
                <button type="button" class="btf-cal-nav" id="btf-cal-next">›</button>
            </div>
            <div class="btf-cal-weekdays">
                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span>
                <span>Th</span><span>Fr</span><span>Sa</span>
            </div>
            <div class="btf-cal-grid" id="btf-cal-grid"></div>
        </div>

        {{-- Time chips (shown when date + movie both chosen) ── --}}
        <div class="btf-times-wrap" id="btf-times-wrap">
            <div class="btf-times-label" id="btf-times-label">
                Select a movie and a date to view showtimes.
            </div>
            <div class="btf-times-pills" id="btf-times-pills"></div>
        </div>

    </aside>

</div>{{-- /.btf-layout --}}

{{-- ── JSON data bridges for JS ─────────────────────────────── --}}
<div id="btf-data"
     class="btf-hidden"
     data-movies='{!! $allMovies !!}'
     data-showtimes='{!! $showtimesJson !!}'
></div>

@endsection