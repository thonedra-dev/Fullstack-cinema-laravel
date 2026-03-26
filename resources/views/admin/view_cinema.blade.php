{{--
    resources/views/admin/view_cinema.blade.php
    ───────────────────────────────────────────
    Feature: Browse all registered cinemas.
    Controller: AdminCinemaViewController@index
    Data injected by controller (logic-free blade):
      $cinemas – Collection of Cinema models with eager-loaded ->city and ->theatres
    NOTE: Controller must eager-load: Cinema::with('city', 'theatres')
--}}
@extends('admin.admin_team')

@section('page_title', 'View Cinemas')

@section('head_extras')
    @vite(['resources/css/view_cinema.css', 'resources/js/view_cinema.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     GRID VIEW  (default — visible on load)
══════════════════════════════════════════════════════════ --}}
<div id="vc-grid-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">All <span>Cinemas</span></h1>
        <p class="ac-page-header__sub">Click any card to view details and theatres.</p>
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
                            <img
                                src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                                alt="{{ $cinema->cinema_name }}"
                                class="vc-card__img"
                            >
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
     DETAIL VIEW  (hidden by default — shown on card click)
══════════════════════════════════════════════════════════ --}}
<div id="vc-detail-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="vc-back-btn" type="button">
            ← Back to Cinemas
        </button>
    </div>

    @foreach ($cinemas as $cinema)
    <div class="vc-detail vc-hidden" id="vc-detail-{{ $cinema->cinema_id }}">

        {{-- ── LEFT PANEL — cinema metadata ── --}}
        <aside class="vc-detail__left">

            @if ($cinema->cinema_picture)
                <img
                    src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                    alt="{{ $cinema->cinema_name }}"
                    class="vc-detail__img"
                >
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

            </div>{{-- /.vc-detail__info --}}
        </aside>

        {{-- ── RIGHT PANEL — theatres ── --}}
        <div class="vc-detail__right">

            <div class="vc-detail__section-title">Theatres</div>

            @php $theatres = $cinema->theatres ?? collect(); @endphp

            @if ($theatres->isEmpty())
                <div class="ac-empty" style="padding:40px 0 20px;">
                    <div class="ac-empty__icon">🏟</div>
                    <p class="ac-empty__text">No theatres added for this cinema yet.</p>
                </div>
            @else
                <div class="vc-theatre-grid">
                    @foreach ($theatres as $theatre)
                        {{--
                            Each theatre card is a plain <a> link.
                            Navigates to the seat layout viewer for this theatre.
                            No JS needed — standard browser navigation.
                        --}}
                        <a
                            href="{{ route('admin.theatre.resources', $theatre->theatre_id) }}"
                            class="vc-theatre-card"
                            aria-label="View seat layout for {{ $theatre->theatre_name }}"
                        >
                            @if ($theatre->theatre_poster)
                                <img
                                    src="{{ asset('images/theatres/' . $theatre->theatre_poster) }}"
                                    alt="{{ $theatre->theatre_name }}"
                                    class="vc-theatre-card__img"
                                >
                            @elseif ($theatre->theatre_icon)
                                <img
                                    src="{{ asset('images/theatres/' . $theatre->theatre_icon) }}"
                                    alt="{{ $theatre->theatre_name }}"
                                    class="vc-theatre-card__img"
                                >
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

            {{-- Reserved for future features --}}
            <div class="vc-detail__reserved"></div>

        </div>{{-- /.vc-detail__right --}}

    </div>{{-- /.vc-detail --}}
    @endforeach

</div>{{-- /#vc-detail-view --}}

@endsection