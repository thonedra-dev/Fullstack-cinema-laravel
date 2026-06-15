@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Upcoming Movies')

@section('bm_head_extras')
    @vite(['resources/css/upcoming_movies.css', 'resources/js/upcoming_movies.js'])
@endsection

@section('bm_content')

{{-- ════════════════════════════════════════════════════════
   TOP BAR : back arrow + layout toggles
═══════════════════════════════════════════════════════════ --}}
<div class="um-top-bar">
    <a href="{{ route('manager.home') }}" class="um-back-arrow" aria-label="Back to Dashboard">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>

    <div class="um-layout-toggles">
        <button class="um-layout-toggle is-active" data-view="compact" title="Card view" aria-label="Card view">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
            </svg>
        </button>
        <button class="um-layout-toggle" data-view="expanded" title="Expanded view" aria-label="Expanded view">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
        </button>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════
   COMPACT CARD VIEW  (shown first)
═══════════════════════════════════════════════════════════ --}}
<div id="um-compact-view">
    @if ($movies->isEmpty())
        <div class="bm-empty" style="padding:80px 20px;">
            <div class="bm-empty__icon">🎬</div>
            <p class="bm-empty__text" style="font-size:1rem;margin-bottom:8px;">No upcoming movies.</p>
            <p class="bm-empty__text">All assigned movies have been scheduled, or no movies have been assigned yet.</p>
        </div>
    @else
        <div class="um-compact-grid">
            @foreach ($movies as $movie)
                <div class="um-compact-card">

                    {{-- Poster --}}
                    <div class="um-compact-card__poster">
                        @if (!empty($movie->portrait_poster))
                            <img src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                                 alt="{{ $movie->movie_name }}"
                                 class="um-compact-card__img">
                        @else
                            <div class="um-compact-card__placeholder">🎬</div>
                        @endif

                        {{-- Status icon (approved / pending / rejected only) --}}
                        <div class="um-compact-card__status">
                            @if ($movie->proposal_status === 'approved')
                                <span class="um-status-icon um-status-icon--approved">✓</span>
                            @elseif ($movie->proposal_status === 'pending')
                                <span class="um-status-icon um-status-icon--pending">⏳</span>
                            @elseif ($movie->proposal_status === 'rejected')
                                <span class="um-status-icon um-status-icon--rejected">✕</span>
                            @endif
                        </div>
                    </div>

                    {{-- Movie name --}}
                    <h4 class="um-compact-card__title">{{ $movie->movie_name }}</h4>

                    {{-- Action buttons --}}
                    <div class="um-compact-card__actions">
                        @if (is_null($movie->proposal_status))
                            <a href="{{ route('manager.setup.movie', $movie->movie_id) }}" class="um-compact-btn um-compact-btn--primary">
                                Submit
                            </a>
                        @elseif (in_array($movie->proposal_status, ['pending', 'approved']))
                            <a href="{{ route('manager.proposal.review', $movie->movie_id) }}" class="um-compact-btn um-compact-btn--primary">
                                Review Proposal
                            </a>
                        @elseif ($movie->proposal_status === 'rejected')
                            <a href="{{ route('manager.proposal.review', $movie->movie_id) }}" class="um-compact-btn um-compact-btn--ghost">
                                Review Old
                            </a>
                            <a href="{{ route('manager.setup.movie', $movie->movie_id) }}" class="um-compact-btn um-compact-btn--primary">
                                Re-submit
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ════════════════════════════════════════════════════════
   EXPANDED VIEW  (hidden by default)
═══════════════════════════════════════════════════════════ --}}
<div id="um-expanded-view" style="display:none;">
    @if ($movies->isEmpty())
        <div class="bm-empty" style="padding:80px 20px;">
            <div class="bm-empty__icon">🎬</div>
            <p class="bm-empty__text" style="font-size:1rem;margin-bottom:8px;">No upcoming movies.</p>
            <p class="bm-empty__text">All assigned movies have been scheduled, or no movies have been assigned yet.</p>
        </div>
    @else
        <div class="um-grid">
            @foreach ($movies as $movie)
                <div class="um-card">

                    {{-- Poster area (same as before) --}}
                    <div class="um-card__landscape">
                        @if (!empty($movie->landscape_poster))
                            <img
                                src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                                alt="{{ $movie->movie_name }}"
                                class="um-card__landscape-img"
                            >
                        @else
                            <div class="um-card__landscape-ph">🎬</div>
                        @endif

                        @if (!empty($movie->portrait_poster))
                            <div class="um-card__portrait-wrap">
                                <img
                                    src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                                    alt="{{ $movie->movie_name }} portrait"
                                    class="um-card__portrait"
                                >
                            </div>
                        @endif

                        {{-- Delete icon (approved only) – handled by JS --}}
                        @if ($movie->proposal_status === 'approved')
                            <button class="um-delete-btn" title="Remove from view" aria-label="Remove card">✕</button>
                        @endif
                    </div>

                    {{-- Card body (unchanged) --}}
                    <div class="um-card__body">
                        {{-- ... exact same body as before ... --}}
                        <div class="um-card__title-row">
                            <h3 class="um-card__title">{{ $movie->movie_name }}</h3>
                            <div class="um-card__actions">
                                @if (!empty($movie->landscape_poster))
                                    <a href="{{ asset('images/movies/' . $movie->landscape_poster) }}" download class="um-dl-btn" title="Download landscape poster">Landscape</a>
                                @endif
                                @if (!empty($movie->portrait_poster))
                                    <a href="{{ asset('images/movies/' . $movie->portrait_poster) }}" download class="um-dl-btn" title="Download portrait poster">Portrait</a>
                                @endif
                            </div>
                        </div>

                        @if ($movie->genres->isNotEmpty())
                            <div class="um-card__genres">
                                @foreach ($movie->genres as $genre)
                                    <span class="bm-badge">{{ $genre->genre_name }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="um-card__meta">
                            @php
                                $h = intdiv($movie->runtime, 60);
                                $m = $movie->runtime % 60;
                            @endphp
                            <span class="um-meta-pill">{{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m</span>
                            <span class="um-meta-pill">{{ $movie->language }}</span>
                            <span class="um-meta-pill">{{ $movie->production_name }}</span>
                            @if (!empty($movie->quota_info->supervisor_name))
                                <span class="um-meta-pill">{{ $movie->quota_info->supervisor_name }}</span>
                            @endif
                        </div>

                        @if ($movie->quota_info)
                            <div class="um-card__quota">
                                <span>{{ $movie->quota_info->start_date }} → {{ $movie->quota_info->maximum_end_date }}</span>
                                <span>{{ $movie->quota_info->showtime_slots }} slots/day</span>
                            </div>
                        @endif

                        {{-- Action area (unchanged) --}}
                        @if (is_null($movie->proposal_status))
                            <a href="{{ route('manager.setup.movie', $movie->movie_id) }}" class="um-setup-btn">Setup This Movie</a>
                        @elseif (in_array($movie->proposal_status, ['pending', 'approved']))
                            <div class="um-action-row">
                                @if ($movie->proposal_status === 'pending')
                                    <div class="um-proposal-badge um-proposal-badge--pending">⏳&ensp;Pending Admin Approval</div>
                                @else
                                    <div class="um-proposal-badge um-proposal-badge--approved">✓&ensp;Proposal Approved</div>
                                @endif
                                <a href="{{ route('manager.proposal.review', $movie->movie_id) }}" class="um-review-btn">Review Proposal</a>
                            </div>
                        @elseif ($movie->proposal_status === 'rejected')
                            <div class="um-rejected-compact">
                                <div class="um-rejected-line">
                                    <span class="um-rejected-line__status">Proposal Rejected</span>
                                    <span class="um-rejected-line__divider"></span>
                                    <span class="um-rejected-line__label">Admin note</span>
                                    <span class="um-rejected-line__text">{{ $movie->proposal_admin_note ?: 'No admin note provided.' }}</span>
                                </div>
                                <div class="um-rejected-btns">
                                    <a href="{{ route('manager.proposal.review', $movie->movie_id) }}" class="um-review-btn um-review-btn--ghost">Review Old Proposal</a>
                                    <a href="{{ route('manager.setup.movie', $movie->movie_id) }}" class="um-resubmit-btn">Re-submit</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection