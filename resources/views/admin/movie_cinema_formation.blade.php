{{--
    resources/views/admin/movie_cinema_formation.blade.php
    Controller: AdminMovieFormationController@show
    Data:
      $movie                  – Movie with ->genres
      $cinema                 – Cinema with ->city
      $theatresWithShowtimes  – Theatre collection, each with ->movieShowtimes
      $hasApprovedShowtimes   – bool
--}}
@extends('admin.admin_team')

@section('page_title', $movie->movie_name)
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_cinema_formation.css'])
    <style>
    /* Theatre cards + showtime pills */
    .mcf-theatre-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 14px;
    }

    .mcf-theatre-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        width: 150px;
        cursor: pointer;
        transition: border-color 0.2s, transform 0.2s;
        flex-shrink: 0;
    }

    .mcf-theatre-card:hover,
    .mcf-theatre-card.is-active { border-color: var(--accent); }
    .mcf-theatre-card.is-active { transform: translateY(-2px); }

    .mcf-theatre-card__img {
        width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block;
    }

    .mcf-theatre-card__ph {
        width: 100%; aspect-ratio: 16/9;
        background: var(--bg-surface);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; color: var(--text-muted);
    }

    .mcf-theatre-card__name {
        font-family: var(--font-heading); font-size: 0.75rem;
        font-weight: 700; color: var(--text-secondary);
        padding: 8px 10px; text-align: center; line-height: 1.3;
    }

    /* Showtime pills panel */
    .mcf-showtime-panel {
        margin-top: 20px;
        padding: 18px 20px;
        background: var(--bg-surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        min-height: 64px;
    }

    .mcf-showtime-panel__label {
        font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 12px;
    }

    .mcf-showtime-pills {
        display: flex; flex-wrap: wrap; gap: 8px;
    }

    .mcf-showtime-pill {
        display: inline-block;
        padding: 8px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: monospace;
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--accent-light);
        white-space: nowrap;
    }

    .mcf-showtime-hint {
        font-size: 0.82rem; color: var(--text-muted);
        padding: 8px 0;
    }

    /* TGV-style header metadata */
    .mcf-hero-meta-line {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        color: rgba(255,255,255,0.75);
        font-weight: 500;
        flex-wrap: wrap;
    }

    .mcf-hero-meta-sep { color: rgba(255,255,255,0.35); }

    .mcf-hero-rating {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
        background: #22c55e; color: #000;
        border-radius: 50%; font-size: 0.7rem;
        font-weight: 700; font-family: var(--font-heading);
        flex-shrink: 0;
    }
    </style>
@endsection

@section('content')

<nav class="mcf-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.cinema.index') }}" class="mcf-breadcrumb__item">All Cinemas</a>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item">{{ $cinema->cinema_name }}</span>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item mcf-breadcrumb__item--current">{{ $movie->movie_name }}</span>
</nav>

{{-- Hero landscape poster --}}
<div class="mcf-hero">
    @if (!empty($movie->landscape_poster))
        <img src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
             alt="{{ $movie->movie_name }}" class="mcf-hero__img">
    @else
        <div class="mcf-hero__placeholder">
            <span>🎬</span>
            <span class="mcf-hero__placeholder-text">No landscape poster</span>
        </div>
    @endif

    <div class="mcf-hero__overlay">
        <h1 class="mcf-hero__title">{{ $movie->movie_name }}</h1>

        {{-- TGV-style single-line meta under title --}}
        <div class="mcf-hero-meta-line">
            @if ($movie->genres->isNotEmpty())
                <span class="mcf-hero-rating">P12</span>
                <span>{{ $movie->genres->pluck('genre_name')->join(', ') }}</span>
                <span class="mcf-hero-meta-sep">|</span>
            @endif
            @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
            <span>{{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} mins</span>
            <span class="mcf-hero-meta-sep">|</span>
            <span>{{ $movie->language }}</span>
        </div>
    </div>
</div>

{{-- Movie info card --}}
<div class="mcf-info-card">
    <div class="mcf-info-grid">

        <div class="mcf-info-block mcf-info-block--full">
            <span class="mcf-info-label">Genres</span>
            <div class="mcf-genres">
                @forelse ($movie->genres as $genre)
                    <span class="mcf-genre-tag">{{ $genre->genre_name }}</span>
                @empty
                    <span class="mcf-info-value mcf-info-value--muted">Not assigned</span>
                @endforelse
            </div>
        </div>

        <div class="mcf-info-block">
            <span class="mcf-info-label">Runtime</span>
            <span class="mcf-info-value">
                {{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} min
            </span>
        </div>

        <div class="mcf-info-block">
            <span class="mcf-info-label">Language</span>
            <span class="mcf-info-value">{{ $movie->language }}</span>
        </div>

        <div class="mcf-info-block mcf-info-block--full">
            <span class="mcf-info-label">Production</span>
            <span class="mcf-info-value">{{ $movie->production_name }}</span>
        </div>

    </div>

    {{-- Theatres + showtimes section (replaces "NO Proposal Yet") --}}
    @if (!$hasApprovedShowtimes)
        <div class="mcf-proposal-placeholder">
            <span class="mcf-proposal-placeholder__icon">📋</span>
            <span class="mcf-proposal-placeholder__text">No Approved Proposal Yet</span>
        </div>
    @else
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
            <p style="font-family:var(--font-heading);font-size:0.9rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                Theatres &amp; Showtimes
            </p>
            <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:14px;">
                Click a theatre to see its scheduled showtimes.
            </p>

            {{-- Theatre cards --}}
            <div class="mcf-theatre-grid">
                @foreach ($theatresWithShowtimes as $theatre)
                    <div
                        class="mcf-theatre-card"
                        data-theatre-id="{{ $theatre->theatre_id }}"
                        data-showtimes='{!! json_encode(
                            $theatre->movieShowtimes->map(fn($s) => $s->start_time->format("h:i A"))
                        ) !!}'
                    >
                        @php $img = $theatre->theatre_poster ?? $theatre->theatre_icon ?? null; @endphp
                        @if ($img)
                            <img src="{{ asset('images/theatres/' . $img) }}"
                                 alt="{{ $theatre->theatre_name }}" class="mcf-theatre-card__img">
                        @else
                            <div class="mcf-theatre-card__ph">🏟</div>
                        @endif
                        <div class="mcf-theatre-card__name">{{ $theatre->theatre_name }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Showtime pills (populated by JS on theatre click) --}}
            <div class="mcf-showtime-panel" id="mcf-showtime-panel">
                <div class="mcf-showtime-panel__label" id="mcf-showtime-label">Showtimes</div>
                <div class="mcf-showtime-pills" id="mcf-showtime-pills">
                    <span class="mcf-showtime-hint">Select a theatre above to view its showtimes.</span>
                </div>
            </div>
        </div>
    @endif

</div>

<div style="margin-top:20px;">
    <a href="{{ route('admin.cinema.index') }}" class="vc-back-btn">← Back to Cinemas</a>
</div>

<script>
(function () {
    document.querySelectorAll('.mcf-theatre-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.mcf-theatre-card').forEach(function (c) {
                c.classList.remove('is-active');
            });
            card.classList.add('is-active');

            var showtimes = JSON.parse(card.dataset.showtimes || '[]');
            var pane      = document.getElementById('mcf-showtime-pills');
            var label     = document.getElementById('mcf-showtime-label');

            // Find theatre name from the card's text
            var name = card.querySelector('.mcf-theatre-card__name')?.textContent?.trim() || 'Theatre';
            label.textContent = name + ' — Showtimes';

            pane.innerHTML = '';

            if (showtimes.length === 0) {
                var hint = document.createElement('span');
                hint.className   = 'mcf-showtime-hint';
                hint.textContent = 'No showtimes for this theatre yet.';
                pane.appendChild(hint);
                return;
            }

            showtimes.forEach(function (time) {
                var pill = document.createElement('span');
                pill.className   = 'mcf-showtime-pill';
                pill.textContent = time;
                pane.appendChild(pill);
            });
        });
    });

    // Auto-select first theatre
    var first = document.querySelector('.mcf-theatre-card');
    if (first) first.click();
})();
</script>

@endsection