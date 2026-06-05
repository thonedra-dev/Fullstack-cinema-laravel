{{--
    resources/views/users/movie_details.blade.php
    ─────────────────────────────────────────────
    Public movie detail + showtime selection page.
    Controller: UserMovieDetailsController@show
    Data:
      $movie          – Movie with ->genres
      $availableDates – Collection<Carbon>  (future/today dates only)
      $activeDate     – Carbon|null         (currently selected date)
      $stateGroups    – JSON string (state → cinema → theatre → times)
                        Each time entry carries  is_bookable: bool
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $movie->movie_name }} — CinemaX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/movie_details.css', 'resources/js/movie_details.js'])
</head>
<body class="md-body" data-seat-route="{{ route('user.seats') }}" data-movie-id="{{ $movie->movie_id }}">

{{-- ── Nav ──────────────────────────────────────────────────── --}}
<nav class="md-nav">
    <a href="{{ route('home') }}" class="md-nav__brand">🎬 CinemaX</a>
    <div class="md-nav__links">
        <a href="{{ route('home') }}" class="md-nav__link">Movies</a>
        <a href="#" class="md-nav__link">Cinemas</a>
        <a href="#" class="md-nav__link">Food &amp; Drinks</a>
        <a href="#" class="md-nav__link">Promotions</a>
    </div>
    <button class="md-nav__signin">Sign In</button>
</nav>

{{-- ── Back link ───────────────────────────────────────────── --}}
<a href="{{ route('home') }}" class="md-back">← Back</a>

{{-- ══════════════════════════════════════════════════════════
     HERO — full-width landscape poster
══════════════════════════════════════════════════════════ --}}
<div class="md-hero">
    @if (!empty($movie->landscape_poster))
        <img
            src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
            alt="{{ $movie->movie_name }}"
            class="md-hero__img"
        >
    @else
        <div class="md-hero__ph">🎬</div>
    @endif

    <div class="md-hero__overlay">
        <h1 class="md-hero__title">{{ $movie->movie_name }}</h1>
        <div class="md-hero__meta">
            @if ($movie->genres->isNotEmpty())
                <span class="md-hero__rating">PG</span>
                <span>{{ $movie->genres->pluck('genre_name')->join(', ') }}</span>
                <span class="md-hero__sep">|</span>
            @endif
            @php $h = intdiv($movie->runtime, 60); $mrt = $movie->runtime % 60; @endphp
            <span>{{ $h > 0 ? $h . ' hr ' : '' }}{{ $mrt }} mins</span>
            <span class="md-hero__sep">|</span>
            <span>{{ $movie->language }}</span>
        </div>
        <div class="md-hero__actions">
            <button class="md-btn md-btn--primary">MORE INFO</button>
            <button class="md-btn md-btn--outline">Watch Trailer</button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SHOWTIME ENGINE
══════════════════════════════════════════════════════════ --}}

{{--
    JSON bridge consumed by movie_details.js.
    The is_bookable flag on each time entry drives the JS pill renderer.
    The Blade date strip below is the server-side source of truth;
    the JS date strip (inside md-date-strip) is hidden when Blade renders
    dates — we keep the JS strip div present so the JS doesn't error.
--}}
<div
    id="md-data"
    class="md-hidden"
    data-groups='{!! $stateGroups !!}'
    data-active-date="{{ $activeDate?->toDateString() }}"
></div>

@if ($availableDates->isEmpty())

    <div class="md-no-showtimes">
        <span>📋</span>
        <span>No showtimes available for this movie yet.</span>
    </div>

@else

<div class="md-showtimes-shell">

    {{-- ══════════════════════════════════════════════════
         DATE STRIP — server-rendered
         Only dates >= today are present in $availableDates
         (the controller already filtered expired dates out).
         The active date receives the md-date-btn--active class.
    ══════════════════════════════════════════════════ --}}
    <div class="md-date-strip-wrap md-date-strip-wrap--top">
        <div class="md-date-strip" id="md-date-strip">
            @foreach ($availableDates as $dateCarbon)
                @php
                    $dateStr   = $dateCarbon->toDateString();
                    $isActive  = $activeDate && $activeDate->toDateString() === $dateStr;
                    $isToday   = $dateCarbon->isToday();
                    $labelDay  = $isToday ? 'Today' : $dateCarbon->format('D');
                    $labelNum  = $dateCarbon->format('j');
                    $labelMonth= $dateCarbon->format('M');

                    // Build the URL for this date so the button is a real link
                    // — no JS required for date switching (progressive enhancement).
                    $dateUrl = route('user.movie.details', $movie->movie_id)
                               . '?date=' . $dateStr;
                @endphp

                <a
                    href="{{ $dateUrl }}"
                    class="md-date-btn {{ $isActive ? 'md-date-btn--active' : '' }}"
                    data-date="{{ $dateStr }}"
                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                >
                    <span class="md-date-btn__day">{{ $labelDay }}</span>
                    <div class="md-date-btn__num-wrap">
                        <span class="md-date-btn__num">{{ $labelNum }}</span>
                        <span class="md-date-btn__month">{{ $labelMonth }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="md-showtimes-layout">

        {{-- ── LEFT: State accordion + cinemas (JS-populated) ── --}}
        <aside class="md-sidebar" id="md-sidebar"></aside>

        {{-- ── RIGHT: Cinema header + showtime pills ──────────── --}}
        <div class="md-main-panel" id="md-main-panel">

            <div class="md-cinema-header" id="md-cinema-header">
                <span class="md-cinema-header__name" id="md-cinema-label">
                    Select a cinema
                </span>
                <div class="md-availability-legend">
                    <span class="md-avail md-avail--available">🟩 Available</span>
                    <span class="md-avail md-avail--fast">🟨 Selling fast</span>
                    <span class="md-avail md-avail--sold">🟥 Sold out</span>
                </div>
            </div>

            <div class="md-showtime-section" id="md-showtime-section">

                @php
                    // Decode the JSON string the controller built so Blade can
                    // render the initial state without waiting for JS.
                    $groups = json_decode($stateGroups, true) ?? [];
                @endphp

                @if (empty($groups))
                    <p class="md-select-hint">Select a cinema from the left to view showtimes.</p>
                @else
                    {{--
                        Server-side initial render of theatre blocks.
                        JS will re-render this section when the user switches
                        cinemas or dates; this gives a meaningful first paint
                        with zero JS dependency.

                        We render the first cinema of the first state group
                        as the default visible selection — matching the JS
                        behaviour in buildSidebar() which also selects
                        stateIdx=0, cinemaIdx=0 on first load.
                    --}}
                    @php
                        $firstCinema = $groups[0]['cinemas'][0] ?? null;
                    @endphp

                    @if ($firstCinema)
                        @foreach ($firstCinema['theatres'] as $theatre)
                            <div class="md-theatre-block">
                                <div class="md-theatre-name">{{ $theatre['name'] }}</div>
                                <div class="md-time-pills">
                                    @forelse ($theatre['times'] as $timeEntry)

                                        @if ($timeEntry['is_bookable'])
                                            {{-- ── BOOKABLE: normal clickable pill ── --}}
                                            <button
                                                type="button"
                                                class="md-time-pill"
                                                data-showtime-id="{{ $timeEntry['showtime_id'] }}"
                                                data-time="{{ $timeEntry['time'] }}"
                                                data-cinema-id="{{ $firstCinema['cinema_id'] }}"
                                                data-hall-id="{{ $theatre['hall_id'] }}"
                                                data-theatre-name="{{ $theatre['name'] }}"
                                                data-date="{{ $activeDate?->toDateString() }}"
                                            >
                                                {{ $timeEntry['time'] }}
                                            </button>
                                        @else
                                            {{-- ── NOT BOOKABLE: disabled, red, blurred pill ──
                                                 pointer-events:none  = no JS click
                                                 disabled attr        = no keyboard activation
                                                 inline styles        = self-contained, no extra CSS class needed
                                            --}}
                                            <button
                                                type="button"
                                                class="md-time-pill md-time-pill--expired"
                                                disabled
                                                aria-disabled="true"
                                                title="This showtime is no longer available for booking"
                                                style="
                                                    opacity: 0.4;
                                                    cursor: not-allowed;
                                                    color: #ef4444;
                                                    border: 1px solid #991b1b;
                                                    pointer-events: none;
                                                    filter: blur(0.4px);
                                                    background: transparent;
                                                "
                                            >
                                                {{ $timeEntry['time'] }}
                                            </button>
                                        @endif

                                    @empty
                                        <span style="font-size:0.78rem;color:var(--md-text-muted);">
                                            No times available.
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif

            </div>{{-- /#md-showtime-section --}}

        </div>{{-- /.md-main-panel --}}

    </div>{{-- /.md-showtimes-layout --}}

</div>{{-- /.md-showtimes-shell --}}

@endif

</body>
</html>