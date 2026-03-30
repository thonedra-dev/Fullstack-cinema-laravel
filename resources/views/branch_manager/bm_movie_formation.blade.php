{{--
    resources/views/branch_manager/bm_movie_formation.blade.php
    ─────────────────────────────────────────────────────────────
    Branch Manager movie formation page.
    Same data/logic as admin version, uses BM layout.
    Controller: BranchManagerMovieFormationController@show
    Data:
      $movie                 – Movie with ->genres
      $cinema                – Cinema with ->city
      $theatresWithShowtimes – Theatre collection, each with ->movieShowtimes
      $hasApprovedShowtimes  – bool
--}}
@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', $movie->movie_name)

@section('bm_head_extras')
    <style>
    /* Hero */
    .bmf-hero {
        position: relative; width: 100%;
        aspect-ratio: 21 / 8; border-radius: var(--bm-radius-lg);
        overflow: hidden; background: var(--bm-surface);
        border: 1px solid var(--bm-border); margin-bottom: 20px;
    }

    .bmf-hero__img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .bmf-hero__ph {
        width: 100%; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 10px; color: var(--bm-text-muted); font-size: 2.5rem;
    }

    .bmf-hero__overlay {
        position: absolute; bottom: 0; left: 0; right: 0;
        padding: 20px 24px 18px;
        background: linear-gradient(to top, rgba(0,0,0,0.88) 0%, transparent 100%);
    }

    .bmf-hero__title {
        font-family: var(--bm-font-head); font-size: 1.7rem;
        font-weight: 700; color: #fff;
        letter-spacing: -0.03em; line-height: 1.1;
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }

    .bmf-hero-meta {
        display: flex; align-items: center; gap: 10px;
        font-size: 0.9rem; color: rgba(255,255,255,0.7);
        font-weight: 500; flex-wrap: wrap; margin-top: 6px;
    }

    .bmf-hero-meta-sep { color: rgba(255,255,255,0.3); }

    /* Info card */
    .bmf-info-card {
        padding: 24px;
        margin-bottom: 20px;
    }

    .bmf-info-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
        margin-bottom: 20px;
    }

    .bmf-info-block { display: flex; flex-direction: column; gap: 4px; }
    .bmf-info-block--full { grid-column: 1/-1; }

    .bmf-info-label {
        font-size: 0.67rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.08em; color: var(--bm-text-muted);
    }

    .bmf-info-value {
        font-family: var(--bm-font-head); font-size: 0.95rem;
        font-weight: 600; color: var(--bm-text);
    }

    .bmf-genre-tag {
        display: inline-block; padding: 4px 12px; border-radius: 20px;
        font-size: 0.72rem; font-weight: 700; font-family: var(--bm-font-head);
        background: var(--bm-accent-dim); color: var(--bm-accent);
        border: 1px solid rgba(34,197,94,0.2); margin-right: 5px; margin-bottom: 4px;
    }

    /* Theatre cards */
    .bmf-theatre-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; }

    .bmf-theatre-card {
        background: var(--bm-surface); border: 1px solid var(--bm-border);
        border-radius: var(--bm-radius-md); overflow: hidden;
        width: 148px; cursor: pointer;
        transition: border-color var(--bm-transition), transform var(--bm-transition);
        flex-shrink: 0;
    }

    .bmf-theatre-card:hover,
    .bmf-theatre-card.is-active { border-color: var(--bm-accent); }
    .bmf-theatre-card.is-active { transform: translateY(-2px); }

    .bmf-theatre-card__img { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
    .bmf-theatre-card__ph {
        width: 100%; aspect-ratio: 16/9;
        background: var(--bm-card); display: flex; align-items: center;
        justify-content: center; font-size: 1.5rem; color: var(--bm-text-muted);
    }
    .bmf-theatre-card__name {
        font-family: var(--bm-font-head); font-size: 0.75rem;
        font-weight: 700; color: var(--bm-text-sub);
        padding: 8px 10px; text-align: center; line-height: 1.3;
    }

    /* Showtime pills */
    .bmf-showtime-panel {
        margin-top: 18px; padding: 16px 18px;
        background: var(--bm-card); border: 1px solid var(--bm-border);
        border-radius: var(--bm-radius-md); min-height: 60px;
    }

    .bmf-showtime-panel__label {
        font-size: 0.67rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.08em; color: var(--bm-text-muted); margin-bottom: 10px;
    }

    .bmf-showtime-pills { display: flex; flex-wrap: wrap; gap: 8px; }

    .bmf-showtime-pill {
        display: inline-block; padding: 8px 16px;
        background: var(--bm-surface); border: 1px solid var(--bm-border);
        border-radius: 8px; font-family: monospace; font-size: 0.88rem;
        font-weight: 700; color: var(--bm-accent); white-space: nowrap;
    }

    .bmf-showtime-hint { font-size: 0.78rem; color: var(--bm-text-muted); }

    /* Placeholder */
    .bmf-placeholder {
        display: flex; align-items: center; justify-content: center;
        gap: 10px; padding: 22px;
        border: 1px dashed var(--bm-border); border-radius: var(--bm-radius-md);
        color: var(--bm-text-muted); margin-top: 20px;
    }

    .bmf-placeholder__text {
        font-family: var(--bm-font-head); font-size: 0.82rem;
        font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
    }

    @media (max-width: 600px) {
        .bmf-info-grid { grid-template-columns: 1fr; }
        .bmf-info-block--full { grid-column: auto; }
        .bmf-hero { aspect-ratio: 16/9; }
        .bmf-hero__title { font-size: 1.2rem; }
    }
    </style>
@endsection

@section('bm_content')

<a href="{{ route('manager.resources') }}" class="bm-back-link">← Back to Resources</a>

{{-- Hero --}}
<div class="bmf-hero">
    @if (!empty($movie->landscape_poster))
        <img src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
             alt="{{ $movie->movie_name }}" class="bmf-hero__img">
    @else
        <div class="bmf-hero__ph"><span>🎬</span></div>
    @endif
    <div class="bmf-hero__overlay">
        <h1 class="bmf-hero__title">{{ $movie->movie_name }}</h1>
        <div class="bmf-hero-meta">
            @if ($movie->genres->isNotEmpty())
                <span>{{ $movie->genres->pluck('genre_name')->join(', ') }}</span>
                <span class="bmf-hero-meta-sep">|</span>
            @endif
            @php $h = intdiv($movie->runtime, 60); $m = $movie->runtime % 60; @endphp
            <span>{{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} mins</span>
            <span class="bmf-hero-meta-sep">|</span>
            <span>{{ $movie->language }}</span>
        </div>
    </div>
</div>

{{-- Info card --}}
<div class="bm-card bmf-info-card">
    <div class="bmf-info-grid">

        <div class="bmf-info-block bmf-info-block--full">
            <span class="bmf-info-label">Genres</span>
            <div>
                @forelse ($movie->genres as $genre)
                    <span class="bmf-genre-tag">{{ $genre->genre_name }}</span>
                @empty
                    <span style="font-size:0.82rem;color:var(--bm-text-muted);">Not assigned</span>
                @endforelse
            </div>
        </div>

        <div class="bmf-info-block">
            <span class="bmf-info-label">Runtime</span>
            <span class="bmf-info-value">{{ $h > 0 ? $h . ' hr ' : '' }}{{ $m }} min</span>
        </div>

        <div class="bmf-info-block">
            <span class="bmf-info-label">Language</span>
            <span class="bmf-info-value">{{ $movie->language }}</span>
        </div>

        <div class="bmf-info-block bmf-info-block--full">
            <span class="bmf-info-label">Production</span>
            <span class="bmf-info-value">{{ $movie->production_name }}</span>
        </div>

    </div>

    {{-- Theatres + showtimes or placeholder --}}
    @if (!$hasApprovedShowtimes)
        <div class="bmf-placeholder">
            <span style="font-size:1.1rem;">📋</span>
            <span class="bmf-placeholder__text">No Approved Showtimes Yet</span>
        </div>
    @else
        <div style="padding-top:20px;border-top:1px solid var(--bm-border);">
            <p style="font-family:var(--bm-font-head);font-size:0.9rem;font-weight:700;color:var(--bm-text);margin-bottom:4px;">
                Theatres &amp; Showtimes
            </p>
            <p style="font-size:0.78rem;color:var(--bm-text-muted);margin-bottom:14px;">
                Click a theatre to view its scheduled showtimes.
            </p>

            <div class="bmf-theatre-grid">
                @foreach ($theatresWithShowtimes as $theatre)
                    <div
                        class="bmf-theatre-card"
                        data-showtimes='{!! json_encode(
                            $theatre->movieShowtimes->map(fn($s) => $s->start_time->format("h:i A"))
                        ) !!}'
                        data-name="{{ $theatre->theatre_name }}"
                    >
                        @php $img = $theatre->theatre_poster ?? $theatre->theatre_icon ?? null; @endphp
                        @if ($img)
                            <img src="{{ asset('images/theatres/' . $img) }}"
                                 alt="{{ $theatre->theatre_name }}" class="bmf-theatre-card__img">
                        @else
                            <div class="bmf-theatre-card__ph">🏟</div>
                        @endif
                        <div class="bmf-theatre-card__name">{{ $theatre->theatre_name }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bmf-showtime-panel" id="bmf-showtime-panel">
                <div class="bmf-showtime-panel__label" id="bmf-showtime-label">Showtimes</div>
                <div class="bmf-showtime-pills" id="bmf-showtime-pills">
                    <span class="bmf-showtime-hint">Select a theatre above.</span>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    document.querySelectorAll('.bmf-theatre-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.bmf-theatre-card').forEach(function (c) {
                c.classList.remove('is-active');
            });
            card.classList.add('is-active');

            var showtimes = JSON.parse(card.dataset.showtimes || '[]');
            var pane      = document.getElementById('bmf-showtime-pills');
            var label     = document.getElementById('bmf-showtime-label');

            label.textContent = card.dataset.name + ' — Showtimes';
            pane.innerHTML    = '';

            if (showtimes.length === 0) {
                var hint = document.createElement('span');
                hint.className   = 'bmf-showtime-hint';
                hint.textContent = 'No showtimes for this theatre yet.';
                pane.appendChild(hint);
                return;
            }

            showtimes.forEach(function (time) {
                var pill = document.createElement('span');
                pill.className   = 'bmf-showtime-pill';
                pill.textContent = time;
                pane.appendChild(pill);
            });
        });
    });

    // Auto-select first
    var first = document.querySelector('.bmf-theatre-card');
    if (first) first.click();
})();
</script>

@endsection