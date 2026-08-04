{{--
    resources/views/admin/admin_movies.blade.php
    ────────────────────────────────────────────────
    Pure navigator/chrome for two tabs, one controller each:
      - Now Showing → AdminMovieLiveController@nowShowing   ($activeTab = 'now_showing') [DEFAULT]
      - Proposals   → AdminMovieProposalController@index    ($activeTab = 'proposals')
      - Expired     → (not built yet — static disabled tab)

    Tab body markup lives in:
      - admin.partials.now_showing_content  (expects $nowShowingMovies)
      - admin.partials.proposals_content    (expects $proposals)

    These are the same views the controllers return standalone for
    AJAX tab-switch requests.
--}}
@extends('admin.admin_team')

@section('page_title', $activeTab === 'now_showing' ? 'Now Showing Movies' : 'Movie Proposals')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite([
    'resources/css/admin_movies.css',
    'resources/css/now_showing_movies.css',
    'resources/css/proposed_movies.css',
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
    <div id="mpPageHeaderText">
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
            aria-expanded="false"
        >
            ⚙
        </button>

        <div id="mpTabsBar" class="mp-tabs-bar">
            <a
                href="{{ route('admin.movies.now_showing') }}"
                data-tab="now_showing"
                class="mp-tab-item {{ $activeTab === 'now_showing' ? 'mp-tab-item--active' : '' }}"
            >
                Now Showing
            </a>

            <a
                href="{{ route('admin.proposals.index') }}"
                data-tab="proposals"
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

<div id="mpContentArea" data-active-tab="{{ $activeTab }}">
    @if ($activeTab === 'now_showing')
        @include('admin.partials.now_showing_movies', ['nowShowingMovies' => $nowShowingMovies])
    @else
        @include('admin.partials.proposed_movies', ['proposals' => $proposals])
    @endif
</div>

@endsection