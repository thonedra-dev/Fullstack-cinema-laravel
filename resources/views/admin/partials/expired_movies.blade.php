{{--
    resources/views/admin/partials/expired_movies_content.blade.php
    ────────────────────────────────────────────────────────────────
    Rendered by AdminExpiredMoviesController@index.
    Expects: $expiredMovies
--}}

@if ($expiredMovies->isEmpty())
    <div class="ac-empty" style="padding:80px 20px;">
        <div class="ac-empty__icon">🎬</div>
        <p class="ac-empty__text" style="font-size:1rem;">No expired movies found.</p>
    </div>
@else
    <div class="mp-nowshowing-grid">
        @foreach ($expiredMovies as $movie)
            <div class="mp-nowshowing-card mp-nowshowing-card--expired" data-movie-id="{{ $movie->movie_id }}">
                <div class="mp-nowshowing-card__poster-wrap">
                    @if (!empty($movie->portrait_poster))
                        <img
                            src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                            alt="{{ $movie->movie_name }}"
                            class="mp-nowshowing-card__poster"
                        >
                    @else
                        <div class="mp-nowshowing-card__poster-ph">🎬</div>
                    @endif
                </div>
                <div class="mp-nowshowing-card__body">
                    <p class="mp-nowshowing-card__title">{{ $movie->movie_name }}</p>

                    @if ($movie->genres->isNotEmpty())
                        <div class="mp-nowshowing-card__genres">
                            @foreach ($movie->genres->take(3) as $genre)
                                <span class="mp-nowshowing-card__genre">{{ $genre->genre_name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <p class="mp-nowshowing-card__meta">
                        <span>📅 Ended: {{ \Carbon\Carbon::parse($movie->maximum_end_date)->format('M d, Y') }}</span>
                        @if(!empty($movie->cinema_name))
                            <span>·</span>
                            <span>🏟 {{ $movie->cinema_name }}</span>
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>
@endif