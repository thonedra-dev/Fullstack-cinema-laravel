{{--
    resources/views/users/movie_details.blade.php
    Public movie detail + showtime selection page.
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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700;900&display=swap" rel="stylesheet">
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

{{-- ══════════════════════════════════════════════════════════
     HERO — full-width landscape poster with back link overlay
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
        {{-- Back link positioned top-left inside overlay --}}
        <a href="{{ route('home') }}" class="md-back">← Back</a>

        <div class="md-hero__content">
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
</div>

{{-- ══════════════════════════════════════════════════════════
     SHOWTIME ENGINE
══════════════════════════════════════════════════════════ --}}

{{--
    Data-attribute bridge for JS.
    - data-groups: initial JSON (same as before)
    - data-available-dates: list of all date strings (for carousel)
    - data-active-date: currently selected date
--}}
<div
    id="md-data"
    class="md-hidden"
    data-groups='{!! $stateGroups !!}'
    data-available-dates='{!! $availableDates->map(fn($d) => $d->toDateString())->toJson() !!}'
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
         DATE STRIP — carousel with arrows
         Rendered by JS from the data-available-dates list.
    ══════════════════════════════════════════════════ --}}
    <div class="md-date-strip-wrap">
        <button class="md-date-arrow md-date-arrow--prev" id="mdDatePrev" aria-label="Previous dates">‹</button>
        <div class="md-date-strip-container" id="mdDateStripContainer">
            <div class="md-date-strip" id="mdDateStrip">
                {{-- JS will populate this --}}
            </div>
        </div>
        <button class="md-date-arrow md-date-arrow--next" id="mdDateNext" aria-label="Next dates">›</button>
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

            {{-- Showtime section will be replaced by JS --}}
            <div class="md-showtime-section" id="md-showtime-section">
                {{-- Initial render from server (first cinema) --}}
                @php
                    $groups = json_decode($stateGroups, true) ?? [];
                    $firstCinema = $groups[0]['cinemas'][0] ?? null;
                @endphp

                @if ($firstCinema)
                    @foreach ($firstCinema['theatres'] as $theatre)
                        <div class="md-theatre-block">
                            <div class="md-theatre-name">{{ $theatre['name'] }}</div>
                            <div class="md-time-pills">
                                @forelse ($theatre['times'] as $timeEntry)
                                    @if ($timeEntry['is_bookable'])
                                        <button type="button" class="md-time-pill"
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
                                        <button type="button" class="md-time-pill md-time-pill--expired" disabled
                                            style="opacity:0.4;cursor:not-allowed;color:#ef4444;border:1px solid #991b1b;pointer-events:none;filter:blur(0.4px);background:transparent;">
                                            {{ $timeEntry['time'] }}
                                        </button>
                                    @endif
                                @empty
                                    <span style="font-size:0.78rem;color:var(--md-text-muted);">No times available.</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="md-select-hint">Select a cinema from the left to view showtimes.</p>
                @endif
            </div>

        </div>{{-- /.md-main-panel --}}

    </div>{{-- /.md-showtimes-layout --}}

</div>{{-- /.md-showtimes-shell --}}

@endif

</body>
</html>