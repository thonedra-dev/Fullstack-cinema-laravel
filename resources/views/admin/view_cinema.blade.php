{{--
    resources/views/admin/view_cinema.blade.php
    ───────────────────────────────────────────
    Controller: AdminCinemaViewController@index
    Data:
      $cinemas     – Cinema with eager-loaded ->city, ->theatres, ->halls, ->movies.genres
      $allTheatres – All Theatre models (for the "Assign Theatre" modal)
--}}
@extends('admin.admin_team')

@section('page_title', 'View Cinemas')

@section('head_extras')
    @vite(['resources/css/view_cinema.css', 'resources/js/view_cinema.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     GRID VIEW
══════════════════════════════════════════════════════════ --}}
<div id="vc-grid-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">All <span>Cinemas</span></h1>
        <p class="ac-page-header__sub">Click any card to view details, theatres and movies.</p>
    </div>

    <div class="vc-toolbar">
        <input
            type="text"
            id="cinema_card_search"
            class="ac-input vc-search"
            placeholder="🔍  Search cinemas…"
        >
        <span class="ac-badge">
            {{ $cinemas->count() }} {{ Str::plural('cinema', $cinemas->count()) }}
        </span>
    </div>

    @if ($cinemas->isEmpty())
        <div class="ac-empty">
            <div class="ac-empty__icon">🎬</div>
            <p class="ac-empty__text">
                No cinemas yet.
                <a href="{{ route('admin.cinema.create') }}">Add your first one.</a>
            </p>
        </div>
    @else
        <div class="vc-card-grid" id="vc-cinema-grid">
            @foreach ($cinemas as $cinema)
                <div
                    class="vc-card"
                    data-cinema-id="{{ $cinema->cinema_id }}"
                    tabindex="0"
                    role="button"
                    aria-label="View {{ $cinema->cinema_name }} details"
                >
                    <div class="vc-card__img-wrap">
                        @if ($cinema->cinema_picture)
                            <img src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                                 alt="{{ $cinema->cinema_name }}" class="vc-card__img">
                        @else
                            <div class="vc-card__img-placeholder">🎬</div>
                        @endif
                        <span class="vc-meta-btn" aria-hidden="true">Meta Data</span>
                    </div>
                    <div class="vc-card__body">
                        <span class="vc-card__name">{{ $cinema->cinema_name }}</span>
                        <div class="vc-card__location">
                            <span class="vc-card__city">{{ $cinema->city?->city_name ?? '—' }}</span>
                            <span class="vc-card__state">{{ $cinema->city?->city_state ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>{{-- /#vc-grid-view --}}


{{-- ══════════════════════════════════════════════════════════
     DETAIL VIEW
══════════════════════════════════════════════════════════ --}}
<div id="vc-detail-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="vc-back-btn" type="button">← Back to Cinemas</button>
    </div>

    @foreach ($cinemas as $cinema)
    @php
        // Theatre IDs already assigned to this cinema (via halls)
        $assignedTheatreIds = $cinema->theatres->pluck('theatre_id')->toArray();

        // Theatres NOT yet assigned to this cinema
        $availableTheatres = $allTheatres->whereNotIn('theatre_id', $assignedTheatreIds)->values();
    @endphp

    <div class="vc-detail vc-hidden" id="vc-detail-{{ $cinema->cinema_id }}">

        {{-- ── LEFT PANEL ── --}}
        <aside class="vc-detail__left">

            @if ($cinema->cinema_picture)
                <img src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                     alt="{{ $cinema->cinema_name }}" class="vc-detail__img">
            @else
                <div class="vc-detail__img-placeholder">🎬</div>
            @endif

            <div class="vc-detail__info">
                <p class="vc-detail__cinema-name">{{ $cinema->cinema_name }}</p>

                <div class="vc-detail__row">
                    <span class="vc-detail__label">Address</span>
                    <span class="vc-detail__value">{{ $cinema->cinema_address }}</span>
                </div>
                <div class="vc-detail__row">
                    <span class="vc-detail__label">Contact</span>
                    <span class="vc-detail__value">{{ $cinema->cinema_contact }}</span>
                </div>
                <div class="vc-detail__row">
                    <span class="vc-detail__label">City</span>
                    <span class="vc-detail__value">{{ $cinema->city?->city_name ?? '—' }}</span>
                </div>
                <div class="vc-detail__row">
                    <span class="vc-detail__label">State</span>
                    <span class="vc-detail__value">
                        <span class="ac-badge">{{ $cinema->city?->city_state ?? '—' }}</span>
                    </span>
                </div>
                @if ($cinema->cinema_description)
                <div class="vc-detail__row vc-detail__row--desc">
                    <span class="vc-detail__label">Description</span>
                    <span class="vc-detail__value">{{ $cinema->cinema_description }}</span>
                </div>
                @endif
            </div>
        </aside>

        {{-- ── RIGHT PANEL ── --}}
        <div class="vc-detail__right">

            {{-- ── Theatres section ─────────────────────────── --}}
            <div class="vc-detail__section-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border);">
                <div class="vc-detail__section-title" style="padding-bottom: 0; border-bottom: none;">Theatres</div>

                {{-- Assign Theatre button --}}
                @if ($availableTheatres->isNotEmpty())
                    <button
                        type="button"
                        class="ac-btn ac-btn--sm ac-btn--secondary vc-assign-theatre-btn"
                        data-cinema-id="{{ $cinema->cinema_id }}"
                        data-cinema-name="{{ $cinema->cinema_name }}"
                        aria-label="Assign a theatre to {{ $cinema->cinema_name }}"
                    >
                        ＋ Assign Theatre
                    </button>
                @else
                    <span class="vc-all-assigned-hint" style="font-size: 0.8rem; color: var(--text-muted);">All theatre types assigned ✓</span>
                @endif
            </div>

            @php $theatres = $cinema->theatres ?? collect(); @endphp

            @if ($theatres->isEmpty())
                <div class="ac-empty" style="padding:30px 0 16px;">
                    <div class="ac-empty__icon">🏟</div>
                    <p class="ac-empty__text">No theatres assigned yet.</p>
                </div>
            @else
                <div class="vc-theatre-grid">
                    @foreach ($theatres as $theatre)
                        <a
                            href="{{ route('admin.theatre.resources', $theatre->theatre_id) }}"
                            class="vc-theatre-card"
                            aria-label="View seat layout for {{ $theatre->theatre_name }}"
                        >
                            @if ($theatre->theatre_poster)
                                <img src="{{ asset('images/theatres/' . $theatre->theatre_poster) }}"
                                     alt="{{ $theatre->theatre_name }}" class="vc-theatre-card__img">
                            @elseif ($theatre->theatre_icon)
                                <img src="{{ asset('images/theatres/' . $theatre->theatre_icon) }}"
                                     alt="{{ $theatre->theatre_name }}" class="vc-theatre-card__img">
                            @else
                                <div class="vc-theatre-card__placeholder">🏟</div>
                            @endif
                            <div class="vc-theatre-card__footer">
                                <span class="vc-theatre-card__name">{{ $theatre->theatre_name }}</span>
                                <span class="vc-theatre-card__hint">View seats →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- ── Movies section ────────────────────────────── --}}
            @php $movies = $cinema->movies ?? collect(); @endphp

            @if ($movies->isNotEmpty())
                <div class="vc-detail__section-title" style="margin-top:28px;">Movies</div>

                <div class="vc-movie-grid">
                    @foreach ($movies as $movie)
                        <a
                            href="{{ route('admin.movie.details', [$movie->movie_id, $cinema->cinema_id]) }}"
                            class="vc-movie-card"
                            aria-label="View {{ $movie->movie_name }} details"
                        >
                            <div class="vc-movie-card__poster-wrap">
                                @if (!empty($movie->portrait_poster))
                                    <img
                                        src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                                        alt="{{ $movie->movie_name }}"
                                        class="vc-movie-card__poster"
                                    >
                                @else
                                    <div class="vc-movie-card__poster-ph">🎬</div>
                                @endif
                            </div>

                            <div class="vc-movie-card__body">
                                <p class="vc-movie-card__title">{{ $movie->movie_name }}</p>

                                @if ($movie->genres->isNotEmpty())
                                    <div class="vc-movie-card__genres">
                                        @foreach ($movie->genres->take(3) as $genre)
                                            <span class="vc-movie-card__genre">{{ $genre->genre_name }}</span>
                                        @endforeach
                                        @if ($movie->genres->count() > 3)
                                            <span class="vc-movie-card__genre vc-movie-card__genre--more">
                                                +{{ $movie->genres->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <p class="vc-movie-card__runtime">
                                    @php
                                        $h = intdiv($movie->runtime, 60);
                                        $m = $movie->runtime % 60;
                                    @endphp
                                    {{ $h > 0 ? $h . 'h ' : '' }}{{ $m }}m &middot; {{ $movie->language }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="vc-detail__reserved" style="margin-top:20px;"></div>

        </div>{{-- /.vc-detail__right --}}

        {{-- ══ ASSIGN THEATRE MODAL ════════════════════════════ --}}
        @if ($availableTheatres->isNotEmpty())
        <div
            class="vc-modal-overlay vc-hidden"
            id="vc-assign-modal-{{ $cinema->cinema_id }}"
            role="dialog"
            aria-modal="true"
            aria-label="Assign Theatre to {{ $cinema->cinema_name }}"
        >
            <div class="vc-modal">

                <div class="vc-modal__header">
                    <h2 class="vc-modal__title">Assign Theatre</h2>
                    <button
                        type="button"
                        class="vc-modal__close vc-assign-modal-close"
                        data-cinema-id="{{ $cinema->cinema_id }}"
                        aria-label="Close"
                    >✕</button>
                </div>

                <p class="vc-modal__hint">
                    Select the theatre types you want to assign to <strong>{{ $cinema->cinema_name }}</strong>.
                </p>

                <form action="{{ route('admin.cinema.halls.store', $cinema->cinema_id) }}" method="POST">
                    @csrf
                    
                    {{-- Changed from list to grid for a better layout --}}
                    <div class="vc-modal__grid">
                        @foreach ($availableTheatres as $theatre)
                            <label
                                class="vc-modal__theatre-card"
                                for="modal_theatre_{{ $cinema->cinema_id }}_{{ $theatre->theatre_id }}"
                            >
                                <input
                                    type="checkbox"
                                    id="modal_theatre_{{ $cinema->cinema_id }}_{{ $theatre->theatre_id }}"
                                    name="theatre_ids[]"
                                    value="{{ $theatre->theatre_id }}"
                                    class="vc-modal__checkbox"
                                >
                                
                                <div class="vc-modal__card-img-wrap">
                                    @if ($theatre->theatre_poster)
                                        <img src="{{ asset('images/theatres/' . $theatre->theatre_poster) }}" alt="{{ $theatre->theatre_name }}" class="vc-modal__card-img">
                                    @elseif ($theatre->theatre_icon)
                                        <img src="{{ asset('images/theatres/' . $theatre->theatre_icon) }}" alt="{{ $theatre->theatre_name }}" class="vc-modal__card-img">
                                    @else
                                        <div class="vc-modal__card-ph">🏟</div>
                                    @endif
                                </div>
                                
                                <div class="vc-modal__card-footer">
                                    <span class="vc-modal__card-name">{{ $theatre->theatre_name }}</span>
                                </div>
                                
                                <div class="vc-modal__check-indicator">✓</div>
                            </label>
                        @endforeach
                    </div>

                    <div class="vc-modal__actions">
                        <button
                            type="button"
                            class="ac-btn ac-btn--secondary vc-assign-modal-close"
                            data-cinema-id="{{ $cinema->cinema_id }}"
                        >Cancel</button>
                        <button type="submit" class="ac-btn ac-btn--primary">
                            Assign Selected
                        </button>
                    </div>

                </form>
            </div>
        </div>
        @endif

    </div>{{-- /.vc-detail --}}
    @endforeach

</div>{{-- /#vc-detail-view --}}

@endsection