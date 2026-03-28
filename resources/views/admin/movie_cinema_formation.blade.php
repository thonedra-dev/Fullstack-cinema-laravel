{{--
    resources/views/admin/movie_cinema_formation.blade.php
    ──────────────────────────────────────────────────────
    Feature: Movie detail in the context of a cinema.
    Controller: AdminMovieFormationController@show
    Data injected (logic-free blade):
      $movie  – Movie model with eager-loaded ->genres
      $cinema – Cinema model with eager-loaded ->city
--}}
@extends('admin.admin_team')

@section('page_title', $movie->movie_name)
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_cinema_formation.css'])
@endsection

@section('content')

{{-- Breadcrumb --}}
<nav class="mcf-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.cinema.index') }}" class="mcf-breadcrumb__item">All Cinemas</a>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item">{{ $cinema->cinema_name }}</span>
    <span class="mcf-breadcrumb__sep">›</span>
    <span class="mcf-breadcrumb__item mcf-breadcrumb__item--current">{{ $movie->movie_name }}</span>
</nav>

{{-- Landscape poster hero --}}
<div class="mcf-hero">
    @if (!empty($movie->landscape_poster))
        <img
            src="{{ asset('images/movies/' . $movie->landscape_poster) }}"
            alt="{{ $movie->movie_name }}"
            class="mcf-hero__img"
        >
    @else
        <div class="mcf-hero__placeholder">
            <span>🎬</span>
            <span class="mcf-hero__placeholder-text">No landscape poster</span>
        </div>
    @endif

    {{-- Overlay with movie title --}}
    <div class="mcf-hero__overlay">
        <h1 class="mcf-hero__title">{{ $movie->movie_name }}</h1>
        <p class="mcf-hero__cinema">@ {{ $cinema->cinema_name }}</p>
    </div>
</div>

{{-- Movie info card --}}
<div class="mcf-info-card">

    <div class="mcf-info-grid">

        {{-- Genres --}}
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

        {{-- Runtime --}}
        <div class="mcf-info-block">
            <span class="mcf-info-label">Runtime</span>
            @php
                $hours   = intdiv($movie->runtime, 60);
                $minutes = $movie->runtime % 60;
            @endphp
            <span class="mcf-info-value">
                {{ $hours > 0 ? $hours . ' hr ' : '' }}{{ $minutes }} min
            </span>
        </div>

        {{-- Language --}}
        <div class="mcf-info-block">
            <span class="mcf-info-label">Language</span>
            <span class="mcf-info-value">{{ $movie->language }}</span>
        </div>

        {{-- Production --}}
        <div class="mcf-info-block mcf-info-block--full">
            <span class="mcf-info-label">Production</span>
            <span class="mcf-info-value">{{ $movie->production_name }}</span>
        </div>

    </div>{{-- /.mcf-info-grid --}}

    {{-- Future: showtime proposals will go here --}}
    <div class="mcf-proposal-placeholder">
        <span class="mcf-proposal-placeholder__icon">📋</span>
        <span class="mcf-proposal-placeholder__text">NO Proposal Yet</span>
    </div>

</div>{{-- /.mcf-info-card --}}

{{-- Back button --}}
<div style="margin-top:20px;">
    <a href="{{ route('admin.cinema.index') }}" class="vc-back-btn">← Back to Cinemas</a>
</div>

@endsection