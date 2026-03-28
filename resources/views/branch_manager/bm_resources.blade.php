{{--
    resources/views/branch_manager/bm_resources.blade.php
    ───────────────────────────────────────────────────────
    Portal 2: Theatres and movies for this cinema branch.
    Controller: BranchManagerResourceController@index
    Data injected (logic-free blade):
      $cinema   – Cinema model {cinema_name}
      $theatres – Theatre collection for this cinema_id
      $movies   – Movie collection assigned to this cinema (via cinema_movie_quotas)
                  each with eager-loaded ->genres
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Resources')

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">← Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title"><span>Resources</span></h1>
    <p class="bm-page-header__sub">Theatres and movies for {{ $cinema->cinema_name }}.</p>
</div>

{{-- ── Theatres ──────────────────────────────────────────── --}}
<div class="bm-section-title">Theatres</div>

@if ($theatres->isEmpty())
    <div class="bm-empty" style="margin-bottom:32px;">
        <div class="bm-empty__icon">🏟</div>
        <p class="bm-empty__text">No theatres defined for this cinema yet.</p>
    </div>
@else
    <div class="bm-mini-grid" style="margin-bottom:36px;">
        @foreach ($theatres as $theatre)
            <div class="bm-mini-card">
                <div class="bm-mini-card__img-wrap">
                    @if ($theatre->theatre_poster)
                        <img
                            src="{{ asset('images/theatres/' . $theatre->theatre_poster) }}"
                            alt="{{ $theatre->theatre_name }}"
                            class="bm-mini-card__img"
                        >
                    @elseif ($theatre->theatre_icon)
                        <img
                            src="{{ asset('images/theatres/' . $theatre->theatre_icon) }}"
                            alt="{{ $theatre->theatre_name }}"
                            class="bm-mini-card__img"
                        >
                    @else
                        <div class="bm-mini-card__ph">🏟</div>
                    @endif
                </div>
                <div class="bm-mini-card__body">
                    <p class="bm-mini-card__name">{{ $theatre->theatre_name }}</p>
                    @php
                        $seatCount = $theatre->seats->count();
                    @endphp
                    @if ($seatCount > 0)
                        <p class="bm-mini-card__sub">{{ $seatCount }} seats</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Movies ────────────────────────────────────────────── --}}
<div class="bm-section-title">Movies</div>

@if ($movies->isEmpty())
    <div class="bm-empty">
        <div class="bm-empty__icon">🎬</div>
        <p class="bm-empty__text">No movies assigned to this cinema yet.</p>
    </div>
@else
    <div class="bm-movie-grid">
        @foreach ($movies as $movie)
            <div class="bm-movie-card">
                {{-- Portrait poster --}}
                <div class="bm-movie-card__poster-wrap">
                    @if (!empty($movie->portrait_poster))
                        <img
                            src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                            alt="{{ $movie->movie_name }}"
                            class="bm-movie-card__poster"
                        >
                    @else
                        <div class="bm-movie-card__poster-ph">🎬</div>
                    @endif
                </div>

                <div class="bm-movie-card__body">
                    <p class="bm-movie-card__title">{{ $movie->movie_name }}</p>

                    @if ($movie->genres->isNotEmpty())
                        <div class="bm-movie-card__genres">
                            @foreach ($movie->genres->take(3) as $genre)
                                <span class="bm-movie-card__genre">{{ $genre->genre_name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <p class="bm-movie-card__meta">
                        @php
                            $h = intdiv($movie->runtime, 60);
                            $m = $movie->runtime % 60;
                        @endphp
                        {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m · {{ $movie->language }}
                    </p>

                    {{-- Quota info for this cinema --}}
                    @php
                        $quota = $movie->pivot ?? null;
                    @endphp
                    @if ($quota)
                        <div class="bm-movie-card__quota">
                            <span>{{ $quota->start_date }} → {{ $quota->maximum_end_date }}</span>
                            <span>{{ $quota->showtime_slots }} slots/day</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
.bm-movie-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
}

.bm-movie-card {
    background: var(--bm-surface);
    border: 1px solid var(--bm-border);
    border-radius: var(--bm-radius-md);
    overflow: hidden;
    transition: border-color var(--bm-transition);
}

.bm-movie-card:hover { border-color: var(--bm-accent); }

.bm-movie-card__poster-wrap {
    width: 100%; aspect-ratio: 2/3;
    overflow: hidden; background: var(--bm-card);
}

.bm-movie-card__poster { width: 100%; height: 100%; object-fit: cover; display: block; }

.bm-movie-card__poster-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: var(--bm-text-muted);
}

.bm-movie-card__body { padding: 10px 12px; display: flex; flex-direction: column; gap: 5px; }

.bm-movie-card__title {
    font-family: var(--bm-font-head); font-size: 0.78rem; font-weight: 700;
    color: var(--bm-text); line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
}

.bm-movie-card__genres { display: flex; flex-wrap: wrap; gap: 3px; }

.bm-movie-card__genre {
    font-size: 0.6rem; font-weight: 700; font-family: var(--bm-font-head);
    padding: 2px 6px; border-radius: 10px;
    background: var(--bm-accent-glow); color: var(--bm-accent);
    border: 1px solid rgba(34,197,94,0.2);
    white-space: nowrap;
}

.bm-movie-card__meta { font-size: 0.65rem; color: var(--bm-text-muted); }

.bm-movie-card__quota {
    font-size: 0.62rem; color: var(--bm-text-muted);
    display: flex; flex-direction: column; gap: 1px;
    border-top: 1px solid var(--bm-border);
    padding-top: 6px; margin-top: 2px;
}
</style>

@endsection