{{--
    resources/views/admin/movie_creation.blade.php
    ──────────────────────────────────────────────
    Feature: Create a movie and assign to cinema branches with quotas.
    Controller: AdminMovieController@create / @store
    Data injected (logic-free blade):
      $cinemas     – Cinema collection with eager-loaded ->city
      $supervisors – Supervisor collection {supervisor_id, supervisor_name}
      $genres      – Genre collection {genre_id, genre_name}
--}}
@extends('admin.admin_team')

@section('page_title', 'Create Movie')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_creation.css', 'resources/js/movie_creation.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     VIEW 1 — MAIN FORM  (default visible)
══════════════════════════════════════════════════════════ --}}
<div id="mc-form-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">Create a <span>Movie</span></h1>
        <p class="ac-page-header__sub">Define movie details, assign cinema branches, and confirm with supervisor.</p>
    </div>

    <form
        id="mc-main-form"
        action="{{ route('admin.movie.store') }}"
        method="POST"
        enctype="multipart/form-data"
        novalidate
    >
        @csrf

        {{-- Serialised cinema assignments — written by JS --}}
        <input type="hidden" id="mc-cinemas-json" name="cinemas_json" value="{{ old('cinemas_json', '[]') }}">

        {{-- ── SECTION 1: Movie Details ────────────────────── --}}
        <div class="mc-section">
            <div class="mc-section__header">
                <span class="mc-section__number">01</span>
                <span class="mc-section__title">Movie Details</span>
            </div>
            <div class="mc-details-grid">

                <div class="ac-field mc-field--full">
                    <label for="movie_name">Movie Name <span class="required">*</span></label>
                    <input
                        type="text"
                        id="movie_name"
                        name="movie_name"
                        class="ac-input mc-input--compact @error('movie_name') is-invalid @enderror"
                        placeholder="e.g. Interstellar"
                        value="{{ old('movie_name') }}"
                        required
                    >
                    @error('movie_name') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label for="runtime">
                        Runtime <span class="required">*</span>
                        <span class="optional">(minutes)</span>
                    </label>
                    <input
                        type="number"
                        id="runtime"
                        name="runtime"
                        class="ac-input mc-input--compact @error('runtime') is-invalid @enderror"
                        placeholder="e.g. 148"
                        value="{{ old('runtime') }}"
                        min="1" max="600"
                        required
                    >
                    @error('runtime') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label for="language">Language <span class="required">*</span></label>
                    <input
                        type="text"
                        id="language"
                        name="language"
                        class="ac-input mc-input--compact @error('language') is-invalid @enderror"
                        placeholder="e.g. English"
                        value="{{ old('language') }}"
                        required
                    >
                    @error('language') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field mc-field--full">
                    <label for="production_name">Production <span class="required">*</span></label>
                    <input
                        type="text"
                        id="production_name"
                        name="production_name"
                        class="ac-input mc-input--compact @error('production_name') is-invalid @enderror"
                        placeholder="e.g. Warner Bros."
                        value="{{ old('production_name') }}"
                        required
                    >
                    @error('production_name') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                {{-- Posters ──────────────────────────────── --}}
                <div class="ac-field">
                    <label>
                        Landscape Poster
                        <span class="optional">(optional · JPEG/PNG/WEBP · max 4 MB)</span>
                    </label>
                    <label class="ac-file-label" for="landscape_poster">
                        <span class="ac-file-label__icon">🖼</span>
                        <span class="ac-file-label__text">
                            <strong>Click to upload</strong> landscape
                        </span>
                        <input
                            type="file"
                            id="landscape_poster"
                            name="landscape_poster"
                            accept="image/jpeg,image/png,image/webp"
                        >
                    </label>
                    <span id="landscape_preview" class="ac-file-preview"></span>
                    @error('landscape_poster') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label>
                        Portrait Poster
                        <span class="optional">(optional · JPEG/PNG/WEBP · max 4 MB)</span>
                    </label>
                    <label class="ac-file-label" for="portrait_poster">
                        <span class="ac-file-label__icon">🎞</span>
                        <span class="ac-file-label__text">
                            <strong>Click to upload</strong> portrait
                        </span>
                        <input
                            type="file"
                            id="portrait_poster"
                            name="portrait_poster"
                            accept="image/jpeg,image/png,image/webp"
                        >
                    </label>
                    <span id="portrait_preview" class="ac-file-preview"></span>
                    @error('portrait_poster') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

            </div>{{-- /.mc-details-grid --}}
        </div>

        {{-- ── SECTION 2: Genres ──────────────────────────── --}}
        <div class="mc-section">
            <div class="mc-section__header">
                <span class="mc-section__number">02</span>
                <span class="mc-section__title">Genres</span>
            </div>

            @error('genres')
                <div class="at-alert at-alert--error" style="margin-bottom:12px;">
                    <span class="at-alert__icon">✕</span> {{ $message }}
                </div>
            @enderror

            @if ($genres->isEmpty())
                <p class="mc-no-genres">No genres found in database.</p>
            @else
                <div class="mc-genre-grid">
                    @foreach ($genres as $genre)
                        <label
                            class="mc-genre-btn {{ in_array($genre->genre_id, old('genres', [])) ? 'is-selected' : '' }}"
                            for="genre_{{ $genre->genre_id }}"
                        >
                            <input
                                type="checkbox"
                                id="genre_{{ $genre->genre_id }}"
                                name="genres[]"
                                value="{{ $genre->genre_id }}"
                                {{ in_array($genre->genre_id, old('genres', [])) ? 'checked' : '' }}
                            >
                            {{ $genre->genre_name }}
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── SECTION 3: Cinema Assignments ──────────────── --}}
        <div class="mc-section">
            <div class="mc-section__header">
                <span class="mc-section__number">03</span>
                <span class="mc-section__title">Cinema Assignments</span>
            </div>

            @error('cinemas_json')
                <div class="at-alert at-alert--error" style="margin-bottom:14px;">
                    <span class="at-alert__icon">✕</span> {{ $message }}
                </div>
            @enderror

            <div id="mc-assigned-summary" class="mc-assigned-summary vc-hidden">
                <div id="mc-chips-row" class="mc-chips-row"></div>
                <button type="button" id="mc-toggle-expand" class="mc-toggle-btn">▾ Show details</button>
                <div id="mc-cards-expanded" class="mc-cards-expanded vc-hidden"></div>
            </div>

            <div id="mc-no-cinemas" class="mc-no-cinemas">
                <span class="mc-no-cinemas__icon">🏢</span>
                <span>No cinemas assigned yet.</span>
            </div>

            <button type="button" id="mc-select-cinemas-btn" class="ct-select-btn">
                🏢 Select Cinemas
            </button>
        </div>

        {{-- ── SECTION 4: Supervisor Confirmation ─────────── --}}
        <div class="mc-section mc-section--auth">
            <div class="mc-section__header">
                <span class="mc-section__number">04</span>
                <span class="mc-section__title">Supervisor Confirmation</span>
            </div>

            <div class="mc-auth-grid">

                <div class="ac-field">
                    <label for="supervisor_id">Supervisor <span class="required">*</span></label>
                    <select
                        id="supervisor_id"
                        name="supervisor_id"
                        class="ac-select mc-input--compact @error('supervisor_id') is-invalid @enderror"
                        required
                    >
                        <option value="">— Select supervisor —</option>
                        @foreach ($supervisors as $sv)
                            <option
                                value="{{ $sv->supervisor_id }}"
                                {{ old('supervisor_id') == $sv->supervisor_id ? 'selected' : '' }}
                            >
                                {{ $sv->supervisor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supervisor_id') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label for="supervisor_password">Password <span class="required">*</span></label>
                    <input
                        type="password"
                        id="supervisor_password"
                        name="supervisor_password"
                        class="ac-input mc-input--compact @error('supervisor_password') is-invalid @enderror"
                        placeholder="Supervisor password"
                        required
                        autocomplete="current-password"
                    >
                    @error('supervisor_password') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

            </div>

            @if (session('auth_error'))
                <div class="at-alert at-alert--error" style="margin-top:12px;">
                    <span class="at-alert__icon">✕</span>
                    {{ session('auth_error') }}
                </div>
            @endif
        </div>

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                🎬 Create Movie &amp; Assign
            </button>
        </div>

    </form>
</div>{{-- /#mc-form-view --}}


{{-- ══════════════════════════════════════════════════════════
     VIEW 2 — CINEMA SELECTION GRID  (hidden by default)
══════════════════════════════════════════════════════════ --}}
<div id="mc-cinema-select-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="mc-select-back" type="button">← Back to Form</button>
        <span class="ct-selection-title">Assign Cinemas</span>
        <span class="mc-select-hint">Click a card to configure its quota</span>
    </div>

    @if ($cinemas->isEmpty())
        <div class="ac-empty">
            <div class="ac-empty__icon">🏢</div>
            <p class="ac-empty__text">
                No cinemas available.
                <a href="{{ route('admin.cinema.create') }}">Add one first.</a>
            </p>
        </div>
    @else
        <div class="vc-card-grid" id="mc-cinema-grid">
            @foreach ($cinemas as $cinema)
                <div
                    class="vc-card mc-cinema-card"
                    data-cinema-id="{{ $cinema->cinema_id }}"
                    data-cinema-name="{{ $cinema->cinema_name }}"
                    data-cinema-img="{{ $cinema->cinema_picture ? asset('images/cinemas/' . $cinema->cinema_picture) : '' }}"
                    data-cinema-city="{{ $cinema->city?->city_name ?? '—' }}"
                    data-cinema-state="{{ $cinema->city?->city_state ?? '—' }}"
                    tabindex="0"
                    role="button"
                    aria-label="Assign {{ $cinema->cinema_name }}"
                >
                    <div class="vc-card__img-wrap">
                        @if ($cinema->cinema_picture)
                            <img src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                                 alt="{{ $cinema->cinema_name }}" class="vc-card__img">
                        @else
                            <div class="vc-card__img-placeholder">🎬</div>
                        @endif
                        <div class="mc-assigned-overlay vc-hidden">✓ Assigned</div>
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

    <div class="mc-select-footer">
        <span id="mc-count-badge" class="mc-count-badge">0 assigned</span>
        <button type="button" id="mc-done-selecting" class="ac-btn ac-btn--primary">
            ✓ Done — Return to Form
        </button>
    </div>

</div>{{-- /#mc-cinema-select-view --}}


{{-- ══════════════════════════════════════════════════════════
     QUOTA MODAL
══════════════════════════════════════════════════════════ --}}
<div id="mc-quota-modal" class="mc-modal-overlay vc-hidden"
     role="dialog" aria-modal="true" aria-labelledby="mc-quota-label">
    <div class="mc-modal">

        <div class="mc-modal__strip">
            <img id="mc-modal-img" class="mc-modal__img vc-hidden" alt="">
            <div id="mc-modal-img-ph" class="mc-modal__img-ph">🎬</div>
            <div class="mc-modal__cinema-info">
                <p class="mc-modal__label" id="mc-quota-label">Configure Quota</p>
                <p class="mc-modal__cinema-name" id="mc-modal-cinema-name"></p>
                <p class="mc-modal__cinema-loc"  id="mc-modal-cinema-loc"></p>
            </div>
            <button type="button" id="mc-quota-remove" class="mc-modal__remove vc-hidden">
                ✕ Remove
            </button>
        </div>

        <div class="mc-modal__body">
            <div class="mc-quota-grid">
                <div class="ac-field">
                    <label for="mc-start-date">Start Date <span class="required">*</span></label>
                    <input type="date" id="mc-start-date" class="ac-input mc-input--compact">
                    <span class="ac-error vc-hidden" id="mc-start-err">Required.</span>
                </div>
                <div class="ac-field">
                    <label for="mc-end-date">Max End Date <span class="required">*</span></label>
                    <input type="date" id="mc-end-date" class="ac-input mc-input--compact">
                    <span class="ac-error vc-hidden" id="mc-end-err">Must be after start date.</span>
                </div>
                <div class="ac-field mc-quota-full">
                    <label for="mc-slots">
                        Showtime Slots <span class="required">*</span>
                        <span class="optional">(daily showtimes)</span>
                    </label>
                    <input type="number" id="mc-slots" class="ac-input mc-input--compact"
                           min="1" max="20" placeholder="e.g. 4">
                    <span class="ac-error vc-hidden" id="mc-slots-err">Enter 1–20.</span>
                </div>
            </div>
        </div>

        <div class="mc-modal__actions">
            <button type="button" id="mc-quota-confirm" class="ac-btn ac-btn--primary">
                ✓ Assign Cinema
            </button>
            <button type="button" id="mc-quota-cancel" class="mc-modal__cancel">Cancel</button>
        </div>
    </div>
</div>


<script>
    window.MC_CINEMAS     = {!! json_encode(
        $cinemas->map(fn($c) => [
            'id'    => $c->cinema_id,
            'name'  => $c->cinema_name,
            'img'   => $c->cinema_picture ? asset('images/cinemas/' . $c->cinema_picture) : '',
            'city'  => $c->city?->city_name  ?? '—',
            'state' => $c->city?->city_state ?? '—',
        ])->values()
    ) !!};
    window.MC_CINEMAS_OLD = {!! json_encode(old('cinemas_json', '[]')) !!};
</script>

@endsection