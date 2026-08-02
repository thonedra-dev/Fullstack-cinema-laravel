{{--
    resources/views/admin/admin_movies.blade.php
    ────────────────────────────────────────────────
    Shared page for three tabs, one controller per tab:
      - Now Showing → AdminMovieLiveController@nowShowing      ($activeTab = 'now_showing') [DEFAULT]
      - Proposals   → AdminMovieProposalController@index   ($activeTab = 'proposals')
      - Expired     → (not built yet — static disabled tab)

    Data:
      $activeTab        – 'now_showing' | 'proposals'
      $proposals        – (proposals tab) ShowtimeProposalStatus collection
                           decorated with: first_id, slot_count, start_time, theatre
      $nowShowingMovies – (now showing tab) Movie collection w/ genres,
                           filtered via Movie::hasLiveShowtime()
--}}
@extends('admin.admin_team')

@section('page_title', $activeTab === 'now_showing' ? 'Now Showing Movies' : 'Movie Proposals')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite([
        'resources/css/admin_movies.css',
        'resources/css/movie_proposals.css',
        'resources/js/admin_movies.js',
    ])
@endsection

@section('content')

@php
    $activeTab = $activeTab ?? 'now_showing';
    $nowShowingMovies = $nowShowingMovies ?? collect();
    $proposals = $proposals ?? collect();
@endphp

<div class="ac-page-header mp-header">
    <div>
        @if ($activeTab === 'now_showing')
            <h1 class="ac-page-header__title">Now <span>Showing</span></h1>
            <p class="ac-page-header__sub">
                Movies currently live and scheduled across active cinema halls.
            </p>
        @else
            <h1 class="ac-page-header__title">Movie <span>Proposals</span></h1>
            <p class="ac-page-header__sub">
                Showtime proposals submitted by branch managers. Click to review and approve.
            </p>
        @endif
    </div>

    <div class="mp-tab-switcher">
        <button
            type="button"
            id="mpTabToggleBtn"
            class="mp-tab-toggle-btn"
            aria-label="Switch view"
            aria-expanded="{{ $activeTab !== 'now_showing' ? 'true' : 'false' }}"
        >
            ⚙
        </button>

        <div id="mpTabsBar" class="mp-tabs-bar {{ $activeTab !== 'now_showing' ? 'mp-tabs-bar--open' : '' }}">
            <a
                href="{{ route('admin.movies.now_showing') }}"
                class="mp-tab-item {{ $activeTab === 'now_showing' ? 'mp-tab-item--active' : '' }}"
            >
                Now Showing
            </a>

            <a
                href="{{ route('admin.proposals.index') }}"
                class="mp-tab-item {{ $activeTab === 'proposals' ? 'mp-tab-item--active' : '' }}"
            >
                Proposals
            </a>

            <span class="mp-tab-item mp-tab-item--disabled" title="Coming soon">
                Expired
            </span>
        </div>
    </div>
</div>

@if ($activeTab === 'now_showing')

    {{-- ── NOW SHOWING TAB (AdminMovieLiveController@nowShowing) ─── --}}
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
                            <span>•</span>
                            <span>🌐 {{ $movie->language }}</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

@else

    {{-- ── PROPOSALS TAB (AdminMovieProposalController@index) ── --}}
    @if ($proposals->isEmpty())

        <div class="ac-empty" style="padding:80px 20px;">
            <div class="ac-empty__icon">📩</div>
            <p class="ac-empty__text" style="font-size:1rem;">No proposals yet.</p>
            <p class="ac-empty__text" style="margin-top:6px;">
                Branch managers will submit proposals after configuring showtimes.
            </p>
        </div>

    @else

        @php
            $pending  = $proposals->where('status', 'pending');
            $approved = $proposals->where('status', 'approved');
            $rejected = $proposals->where('status', 'rejected');
        @endphp

        @if ($pending->isNotEmpty())
            <div class="mp-group-label">
                <span class="mp-group-dot mp-group-dot--pending"></span>
                Pending Review ({{ $pending->count() }})
            </div>
            <div class="mp-list">
                @foreach ($pending as $p)
                    @include('admin.partials.proposal_card', ['p' => $p])
                @endforeach
            </div>
        @endif

        @if ($approved->isNotEmpty())
            <div class="mp-group-label" style="margin-top:32px;">
                <span class="mp-group-dot mp-group-dot--approved"></span>
                Approved ({{ $approved->count() }})
            </div>
            <div class="mp-list">
                @foreach ($approved as $p)
                    @include('admin.partials.proposal_card', ['p' => $p])
                @endforeach
            </div>
        @endif

        @if ($rejected->isNotEmpty())
            <div class="mp-group-label" style="margin-top:32px;">
                <span class="mp-group-dot mp-group-dot--rejected"></span>
                Rejected ({{ $rejected->count() }})
            </div>
            <div class="mp-list">
                @foreach ($rejected as $p)
                    @include('admin.partials.proposal_card', ['p' => $p])
                @endforeach
            </div>
        @endif

    @endif

@endif

@endsection