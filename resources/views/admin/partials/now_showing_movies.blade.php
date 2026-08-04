{{--
    resources/views/admin/partials/now_showing_content.blade.php
    ────────────────────────────────────────────────
    Rendered by AdminMovieLiveController@nowShowing.
    Expects: $nowShowingMovies
--}}

@if ($nowShowingMovies->isEmpty())
    <div class="ac-empty" style="padding:80px 20px;">
        <div class="ac-empty__icon">🎬</div>
        <p class="ac-empty__text" style="font-size:1rem;">No movies currently showing.</p>
    </div>
@else
    <div class="mp-nowshowing-grid">
        @foreach ($nowShowingMovies as $movie)
            <a href="#" class="mp-nowshowing-card" data-movie-id="{{ $movie->movie_id }}">
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
                        @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
                        <span>⏱ {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m</span>
                        <span>·</span>
                        <span>🌐 {{ $movie->language }}</span>
                    </p>
                </div>
            </a>
        @endforeach
    </div>
@endif