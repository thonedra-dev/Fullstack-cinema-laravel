{{--
    resources/views/branch_manager/bm_expired_movies.blade.php
    Controller: BranchManagerExpiredMoviesController@index
    Data:
      $cinema – Cinema model
      $movies – EXPIRED movies only (maximum_end_date < today)
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Expired Movies')

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">← Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title"><span>Expired Movies</span></h1>
    <p class="bm-page-header__sub">
        Movies that are no longer active for {{ $cinema->cinema_name }}.
    </p>
</div>

<div class="bm-section-title">Expired</div>

@if ($movies->isEmpty())
    <div class="bm-empty">
        <div class="bm-empty__icon">🕒</div>
        <p class="bm-empty__text">No expired movies found.</p>
    </div>
@else
    <div class="bm-expired-grid">
        @foreach ($movies as $movie)
            <a
                href="{{ route('manager.movie.details', $movie->movie_id) }}"
                class="bm-expired-card"
                aria-label="View {{ $movie->movie_name }} details"
            >
                <span class="bm-expired-card__badge">Expired</span>
                <div class="bm-expired-card__poster-wrap">
                    @if (!empty($movie->portrait_poster))
                        <img src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                             alt="{{ $movie->movie_name }}" class="bm-expired-card__poster">
                    @else
                        <div class="bm-expired-card__poster-ph">🎬</div>
                    @endif
                </div>
                <div class="bm-expired-card__body">
                    <p class="bm-expired-card__title">{{ $movie->movie_name }}</p>
                    @if ($movie->genres->isNotEmpty())
                        <div class="bm-expired-card__genres">
                            @foreach ($movie->genres->take(3) as $genre)
                                <span class="bm-expired-card__genre">{{ $genre->genre_name }}</span>
                            @endforeach
                        </div>
                    @endif
                    <p class="bm-expired-card__meta">
                        @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
                        {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m · {{ $movie->language }}
                    </p>
                    @if ($movie->maximum_end_date)
                        <p class="bm-expired-card__expired-date">
                            Expired on {{ \Carbon\Carbon::parse($movie->maximum_end_date)->format('M d, Y') }}
                        </p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif

<style>
.bm-expired-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.bm-expired-card {
    position: relative;
    text-decoration: none;
    display: block;
    cursor: pointer;
    background: var(--bm-surface);
    border: 1px solid var(--bm-border);
    border-radius: var(--bm-radius-md);
    overflow: hidden;
    transition: border-color var(--bm-transition), transform var(--bm-transition),
                box-shadow var(--bm-transition);
    filter: saturate(0.85);
}

.bm-expired-card:hover {
    border-color: var(--bm-accent);
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(34,197,94,0.10);
}

.bm-expired-card__badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
    background: rgba(220, 38, 38, 0.92);
    color: #fff;
    font-family: var(--bm-font-head);
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 999px;
}

.bm-expired-card__poster-wrap {
    width: 100%;
    aspect-ratio: 2 / 3;
    overflow: hidden;
    background: var(--bm-card);
}

.bm-expired-card__poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    opacity: 0.8;
}

.bm-expired-card__poster-ph {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--bm-text-muted);
}

.bm-expired-card__body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.bm-expired-card__title {
    font-family: var(--bm-font-head);
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--bm-text);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.bm-expired-card__genres {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.bm-expired-card__genre {
    font-size: 0.65rem;
    font-weight: 700;
    font-family: var(--bm-font-head);
    padding: 3px 8px;
    border-radius: 10px;
    background: var(--bm-accent-glow);
    color: var(--bm-accent);
    border: 1px solid rgba(34,197,94,0.2);
    white-space: nowrap;
}

.bm-expired-card__meta {
    font-size: 0.72rem;
    color: var(--bm-text-muted);
}

.bm-expired-card__expired-date {
    font-size: 0.7rem;
    color: #f87171;
    font-weight: 600;
    margin-top: 2px;
}
</style>

<script>
    // Placeholder for future interactivity (e.g. filtering, sorting expired movies).
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('.bm-expired-card');
        cards.forEach(function (card) {
            card.addEventListener('click', function (e) {
                // Native <a> navigation already handles routing to manager.movie.details;
                // this listener is a hook point if you need to add analytics/logging later.
            });
        });
    });
</script>

@endsection