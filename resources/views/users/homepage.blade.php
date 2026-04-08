{{--
    resources/views/users/homepage.blade.php
    ─────────────────────────────────────────
    Public user homepage — cyberpunk cinema aesthetic.
    Controller: UserHomepageController@index
    Data:
      $heroMovies – Movie collection (with ->genres, ->trailer_embed_url)
                    Only movies with landscape_poster
      $nowShowing – Movie collection (with ->genres)
                    Only movies with portrait_poster
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaX — Movie Showtimes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/homepage.css', 'resources/js/homepage.js'])
</head>
<body class="hp-body">

{{-- ══════════════════════════════════════════════════════════
     NAVIGATION BAR  (static)
══════════════════════════════════════════════════════════ --}}
<nav class="hp-nav">
    <div class="hp-nav__brand">🎬 CinemaX</div>
    <div class="hp-nav__links">
        <a href="#" class="hp-nav__link hp-nav__link--active">Movies</a>
        <a href="#" class="hp-nav__link">Cinemas</a>
        <a href="#" class="hp-nav__link">Food &amp; Drinks</a>
        <a href="#" class="hp-nav__link">Promotions</a>
    </div>
    <button class="hp-nav__signin">Sign In</button>
</nav>

{{-- ══════════════════════════════════════════════════════════
     DIVISION 1 — HERO CAROUSEL
     JS advances the slide index on every page load/reload.
══════════════════════════════════════════════════════════ --}}
<section class="hp-hero" id="hp-hero">

    {{-- JSON data bridge for JS --}}
    <div
        id="hp-hero-data"
        class="hp-hidden"
        data-movies='{!! json_encode(
            $heroMovies->map(fn($m) => [
                "movie_id"    => $m->movie_id,
                "title"       => $m->movie_name,
                "poster"      => asset("images/movies/" . $m->landscape_poster),
                "genres"      => $m->genres->pluck("genre_name")->join(", "),
                "runtime_h"   => intdiv($m->runtime, 60),
                "runtime_m"   => $m->runtime % 60,
                "language"    => $m->language,
                "trailer_url" => $m->trailer_embed_url,
            ])->values()
        ) !!}'
    ></div>

    <div class="hp-hero__slides" id="hp-hero-slides"></div>

    <div class="hp-hero__overlay">
        <div class="hp-hero__content">
            <div class="hp-hero__meta" id="hp-hero-meta"></div>
            <h1 class="hp-hero__title" id="hp-hero-title"></h1>
            <div class="hp-hero__actions">
                <button class="hp-btn hp-btn--outline" id="hp-watch-trailer-btn" style="display:none;">
                    ▶ Watch Trailer
                </button>
                <button class="hp-btn hp-btn--ghost">Read More</button>
            </div>
        </div>
    </div>

    <div class="hp-hero__dots" id="hp-hero-dots"></div>

</section>

{{-- ══════════════════════════════════════════════════════════
     DIVISION 2 — MOVIE SHOWTIMES  (horizontal scroll)
══════════════════════════════════════════════════════════ --}}
<section class="hp-showtimes">

    <div class="hp-showtimes__header">
        <h2 class="hp-showtimes__title">Movie <span>Showtimes</span></h2>
    </div>

    <div class="hp-tabs">
        <button class="hp-tab hp-tab--active">Now Showing</button>
        <button class="hp-tab">Concerts</button>
        <button class="hp-tab">Kids</button>
        <button class="hp-tab">Coming Soon</button>
        <a href="#" class="hp-tab-view-all">View all →</a>
    </div>

    <div class="hp-movies-scroll-wrap">
        <div class="hp-movies-row" id="hp-movies-row">
            @forelse ($nowShowing as $movie)
                <div class="hp-movie-card">
                    <div class="hp-movie-card__poster-wrap">
                        <img
                            src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                            alt="{{ $movie->movie_name }}"
                            class="hp-movie-card__poster"
                            loading="lazy"
                        >
                    </div>
                    <div class="hp-movie-card__body">
                        <h3 class="hp-movie-card__title">{{ $movie->movie_name }}</h3>
                        @if ($movie->genres->isNotEmpty())
                            <div class="hp-movie-card__genres">
                                @foreach ($movie->genres->take(2) as $genre)
                                    <span class="hp-genre-tag">{{ $genre->genre_name }}</span>
                                @endforeach
                            </div>
                        @endif
                        <p class="hp-movie-card__meta">
                            @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
                            {{ $h > 0 ? $h . 'h ' . $m . 'm' : $m . 'm' }}
                            · {{ $movie->language }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="hp-no-movies">No movies available right now.</p>
            @endforelse
        </div>

        <div class="hp-scroll-track">
            <div class="hp-scroll-thumb" id="hp-scroll-thumb"></div>
        </div>
    </div>

</section>

{{-- ══════════════════════════════════════════════════════════
     TRAILER POPUP MODAL
══════════════════════════════════════════════════════════ --}}
<div class="hp-trailer-overlay" id="hp-trailer-overlay">
    <div class="hp-trailer-modal">
        <button class="hp-trailer-modal__close" id="hp-trailer-close">✕</button>
        <div class="hp-trailer-modal__frame-wrap">
            <iframe
                id="hp-trailer-iframe"
                class="hp-trailer-modal__iframe"
                src=""
                frameborder="0"
                allow="autoplay; encrypted-media"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</div>

</body>
</html>