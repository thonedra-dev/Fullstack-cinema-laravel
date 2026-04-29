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
        <div class="bm-empty__icon">Cinema</div>
        <p class="bm-empty__text" style="font-size:1rem;margin-bottom:8px;">No upcoming movies.</p>
        <p class="bm-empty__text">All assigned movies have been scheduled, or no movies have been assigned yet.</p>
    </div>
@else
    <div class="um-grid">
        @foreach ($movies as $movie)
            <div class="um-card">
                <div class="um-card__landscape">
                    @if (!empty($movie->landscape_poster))
                        <img
                            src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
                            alt="{{ $movie->movie_name }}"
                            class="um-card__landscape-img"
                        >
                    @else
                        <div class="um-card__landscape-ph">CinemaX</div>
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
                            <span>{{ $movie->quota_info->start_date }} to {{ $movie->quota_info->maximum_end_date }}</span>
                            <span>{{ $movie->quota_info->showtime_slots }} slots/day</span>
                        </div>
                    @endif

                    @if ($movie->proposal_status === 'pending')
                        <div class="um-proposal-badge um-proposal-badge--pending">
                            Pending Admin Approval
                        </div>
                    @elseif ($movie->proposal_status === 'rejected')
                        <div class="um-rejected-compact">
                            <div class="um-rejected-line">
                                <span class="um-rejected-line__status">Proposal Rejected</span>
                                <span class="um-rejected-line__divider"></span>
                                <span class="um-rejected-line__label">Admin note</span>
                                <span class="um-rejected-line__text">
                                    {{ $movie->proposal_admin_note ?: 'No admin note provided.' }}
                                </span>
                            </div>
                            <a
                                href="{{ route('manager.setup.movie', $movie->movie_id) }}"
                                class="um-resubmit-btn"
                            >
                                Re-submit
                            </a>
                        </div>
                    @else
                        <a
                            href="{{ route('manager.setup.movie', $movie->movie_id) }}"
                            class="um-setup-btn"
                        >
                            Setup This Movie
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
