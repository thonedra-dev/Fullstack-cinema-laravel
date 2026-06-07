@extends('branch_manager.branch_manager_layout')

@section('bm_page_title', 'Upcoming Movies')

@section('bm_head_extras')
    @vite(['resources/css/upcoming_movies.css'])
@endsection

@section('bm_content')

<a href="{{ route('manager.home') }}" class="bm-back-link">&larr; Back to Dashboard</a>

<div class="bm-page-header">
    <h1 class="bm-page-header__title">Upcoming <span>Movies</span></h1>
    <p class="bm-page-header__sub">
        Movies assigned to {{ $cinema->cinema_name }} that need their timetables configured.
    </p>
</div>

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

                {{-- ── Poster area ──────────────────────────────────── --}}
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
                </div>

                {{-- ── Card body ────────────────────────────────────── --}}
                <div class="um-card__body">

                    <div class="um-card__title-row">
                        <h3 class="um-card__title">{{ $movie->movie_name }}</h3>
                        <div class="um-card__actions">
                            @if (!empty($movie->landscape_poster))
                                <a
                                    href="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                                    download
                                    class="um-dl-btn"
                                    title="Download landscape poster"
                                >Landscape</a>
                            @endif
                            @if (!empty($movie->portrait_poster))
                                <a
                                    href="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                                    download
                                    class="um-dl-btn"
                                    title="Download portrait poster"
                                >Portrait</a>
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

                    {{-- ════════════════════════════════════════════════════
                         ACTION AREA
                         ────────────────────────────────────────────────────
                         $movie->proposal_status values:
                           null       No proposal exists yet
                                      → [ Setup This Movie ]

                           'pending'  Proposal submitted, admin reviewing
                                      → [ Review Proposal ]

                           'approved' Admin approved (showtimes not yet written
                                      to the showtimes table — edge case)
                                      → [ Review Proposal ]

                           'rejected' Admin rejected the proposal
                                      → [ Review Old Proposal ] + [ Re-submit ]
                    ════════════════════════════════════════════════════ --}}

                    @if (is_null($movie->proposal_status))

                        {{-- No proposal at all → send to setup page ─────── --}}
                        <a
                            href="{{ route('manager.setup.movie', $movie->movie_id) }}"
                            class="um-setup-btn"
                        >
                            Setup This Movie
                        </a>

                    @elseif (in_array($movie->proposal_status, ['pending', 'approved']))

                        {{-- Has an active proposal → Review only ────────── --}}
                        <div class="um-action-row">
                            @if ($movie->proposal_status === 'pending')
                                <div class="um-proposal-badge um-proposal-badge--pending">
                                    ⏳&ensp;Pending Admin Approval
                                </div>
                            @else
                                <div class="um-proposal-badge um-proposal-badge--approved">
                                    ✓&ensp;Proposal Approved
                                </div>
                            @endif

                            <a
                                href="{{ route('manager.proposal.review', $movie->movie_id) }}"
                                class="um-review-btn"
                            >
                                Review Proposal
                            </a>
                        </div>

                    @elseif ($movie->proposal_status === 'rejected')

                        {{-- Rejected → show note + both buttons ──────────── --}}
                        <div class="um-rejected-compact">
                            <div class="um-rejected-line">
                                <span class="um-rejected-line__status">Proposal Rejected</span>
                                <span class="um-rejected-line__divider"></span>
                                <span class="um-rejected-line__label">Admin note</span>
                                <span class="um-rejected-line__text">
                                    {{ $movie->proposal_admin_note ?: 'No admin note provided.' }}
                                </span>
                            </div>
                            <div class="um-rejected-btns">
                                {{-- Review the old (rejected) proposal ─────── --}}
                                <a
                                    href="{{ route('manager.proposal.review', $movie->movie_id) }}"
                                    class="um-review-btn um-review-btn--ghost"
                                >
                                    Review Old Proposal
                                </a>
                                {{-- Fresh submission on the setup page ──────── --}}
                                <a
                                    href="{{ route('manager.setup.movie', $movie->movie_id) }}"
                                    class="um-resubmit-btn"
                                >
                                    Re-submit
                                </a>
                            </div>
                        </div>

                    @endif
                    {{-- ════════════════ END ACTION AREA ═════════════════ --}}

                </div>{{-- /.um-card__body --}}
            </div>{{-- /.um-card --}}
        @endforeach
    </div>
@endif

@endsection