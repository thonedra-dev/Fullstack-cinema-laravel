{{--
    resources/views/users/movie_details.blade.php
    ─────────────────────────────────────────────
    Public movie detail + showtime selection page.
    Controller: UserMovieDetailsController@show
    Data:
      $movie       – Movie with ->genres
      $stateGroups – JSON string (see controller)
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
<body class="md-body">

{{-- ── Nav (static, consistent with homepage) ────────────── --}}
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
     Left: state/cinema sidebar  |  Right: date strip + times
══════════════════════════════════════════════════════════ --}}

{{-- JSON data bridge for JS --}}
<div
    id="md-data"
    class="md-hidden"
    data-groups='{!! $stateGroups !!}'
></div>

@if (json_decode($stateGroups, true) === [])

    <div class="md-no-showtimes">
        <span>📋</span>
        <span>No showtimes available for this movie yet.</span>
    </div>

@else

<div class="md-showtimes-layout">

    {{-- ── LEFT: State accordion + cinemas ───────────────── --}}
    <aside class="md-sidebar" id="md-sidebar">
        {{-- Populated by movie_details.js --}}
    </aside>

    {{-- ── RIGHT: Date strip + theatre blocks ────────────── --}}
    <div class="md-main-panel" id="md-main-panel">

        {{-- Cinema header --}}
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

        {{-- Date strip --}}
        <div class="md-date-strip-wrap">
            <div class="md-date-strip" id="md-date-strip">
                {{-- Populated by JS --}}
            </div>
        </div>

        {{-- Theatre blocks + time pills --}}
        <div class="md-showtime-section" id="md-showtime-section">
            <p class="md-select-hint">Select a cinema from the left to view showtimes.</p>
        </div>

    </div>{{-- /.md-main-panel --}}

</div>{{-- /.md-showtimes-layout --}}

@endif

</body>
</html>