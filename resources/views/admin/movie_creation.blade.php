{{--
    resources/views/admin/movie_creation.blade.php
    ──────────────────────────────────────────────
    Feature: Create a movie and assign to cinema branches with quotas.
    Controller: AdminMovieController@create / @store

    Cinema assignment flow:
      • Click cards to toggle selection (blue ring = pending selection)
      • "Select All" picks every unassigned cinema
      • "Assign Quota" opens the modal for the whole selection
      • Modal strip shows all selected cinemas in a horizontal scroll
      • One quota plan is applied to all selected cinemas simultaneously
      • Assigned cinemas get a ✓ overlay and cannot be re-selected
      • Individual delete buttons on each summary card
      • "Clear All" wipes every assignment at once
--}}
@extends('admin.admin_team')

@section('page_title', 'Create Movie')
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/movie_creation.css', 'resources/js/movie_creation.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     VIEW 1 — MAIN FORM
══════════════════════════════════════════════════════════ --}}
<div id="mc-form-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">Create a <span>Movie</span></h1>
        <p class="ac-page-header__sub">Define movie details, assign cinema branches, and confirm with supervisor.</p>
    </div>

    <form id="mc-main-form" action="{{ route('admin.movie.store') }}" method="POST"
          enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" id="mc-cinemas-json" name="cinemas_json" value="{{ old('cinemas_json', '[]') }}">
        <input type="hidden" id="mc-ticket-prices-json" name="ticket_prices_json" value="{{ old('ticket_prices_json', '[]') }}">

        {{-- ── 01: Movie Details ──────────────────────────── --}}
        <div class="mc-section">
            <div class="mc-section__header">
                <span class="mc-section__number">01</span>
                <span class="mc-section__title">Movie Details</span>
            </div>
            <div class="mc-details-grid">

                <div class="ac-field mc-field--full">
                    <label for="movie_name">Movie Name <span class="required">*</span></label>
                    <input type="text" id="movie_name" name="movie_name"
                           class="ac-input mc-input--compact @error('movie_name') is-invalid @enderror"
                           placeholder="e.g. Interstellar" value="{{ old('movie_name') }}" required>
                    @error('movie_name') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label for="runtime">Runtime <span class="required">*</span> <span class="optional">(minutes)</span></label>
                    <input type="number" id="runtime" name="runtime"
                           class="ac-input mc-input--compact @error('runtime') is-invalid @enderror"
                           placeholder="e.g. 148" value="{{ old('runtime') }}" min="1" max="600" required>
                    @error('runtime') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label for="language">Language <span class="required">*</span></label>
                    <input type="text" id="language" name="language"
                           class="ac-input mc-input--compact @error('language') is-invalid @enderror"
                           placeholder="e.g. English" value="{{ old('language') }}" required>
                    @error('language') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field mc-field--full">
                    <label for="production_name">Production <span class="required">*</span></label>
                    <input type="text" id="production_name" name="production_name"
                           class="ac-input mc-input--compact @error('production_name') is-invalid @enderror"
                           placeholder="e.g. Warner Bros." value="{{ old('production_name') }}" required>
                    @error('production_name') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field mc-field--full">
                    <label for="trailer_url">
                        Trailer YouTube URL
                        <span class="optional">(optional · YouTube embed or watch link)</span>
                    </label>
                    <input type="url" id="trailer_url" name="trailer_url"
                           class="ac-input mc-input--compact @error('trailer_url') is-invalid @enderror"
                           placeholder="e.g. https://www.youtube.com/watch?v=XXXXXX"
                           value="{{ old('trailer_url') }}">
                    <span style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;display:block;">
                        Accepts both <code>youtube.com/watch?v=</code> and <code>youtu.be/</code> links.
                    </span>
                    @error('trailer_url') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label>Landscape Poster <span class="optional">(optional · max 4 MB)</span></label>
                    <label class="ac-file-label" for="landscape_poster">
                        <span class="ac-file-label__icon">🖼</span>
                        <span class="ac-file-label__text"><strong>Click to upload</strong> landscape</span>
                        <input type="file" id="landscape_poster" name="landscape_poster"
                               accept="image/jpeg,image/png,image/webp">
                    </label>
                    <span id="landscape_preview" class="ac-file-preview"></span>
                    @error('landscape_poster') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-field">
                    <label>Portrait Poster <span class="optional">(optional · max 4 MB)</span></label>
                    <label class="ac-file-label" for="portrait_poster">
                        <span class="ac-file-label__icon">🎞</span>
                        <span class="ac-file-label__text"><strong>Click to upload</strong> portrait</span>
                        <input type="file" id="portrait_poster" name="portrait_poster"
                               accept="image/jpeg,image/png,image/webp">
                    </label>
                    <span id="portrait_preview" class="ac-file-preview"></span>
                    @error('portrait_poster') <span class="ac-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>

        {{-- ── 02: Genres ─────────────────────────────────── --}}
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
                        <label class="mc-genre-btn {{ in_array($genre->genre_id, old('genres', [])) ? 'is-selected' : '' }}"
                               for="genre_{{ $genre->genre_id }}">
                            <input type="checkbox" id="genre_{{ $genre->genre_id }}" name="genres[]"
                                   value="{{ $genre->genre_id }}"
                                   {{ in_array($genre->genre_id, old('genres', [])) ? 'checked' : '' }}>
                            {{ $genre->genre_name }}
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── 03: Cinema Assignments ─────────────────────── --}}
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

            {{-- Summary (populated by JS) --}}
            <div id="mc-assigned-summary" class="mc-assigned-summary vc-hidden">
                <div id="mc-chips-row" class="mc-chips-row"></div>
                <div class="mc-summary-actions">
                    <button type="button" id="mc-toggle-expand" class="mc-toggle-btn">▾ Show details</button>
                    <button type="button" id="mc-clear-all-btn" class="mc-clear-all-btn">🗑 Clear All</button>
                </div>
                {{-- Detail cards rendered by JS, each with its own Remove button --}}
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

        {{-- ── 04: Supervisor Confirmation ────────────────── --}}
        {{-- Ticket pricing rules --}}
        @php
            $pricingTheatres = ['Standard', 'Deluxe', '3D Hall', 'VIP lounge', 'IMAX'];
            $pricingSeats = [
                'standard' => 'Standard',
                'premium' => 'Premium',
                'family' => 'Family',
                'couple' => 'Couple',
            ];
            $pricingDays = [
                'weekday' => 'Weekdays',
                'weekend' => 'Weekends',
            ];
        @endphp
        <div class="mc-section mc-section--pricing">
            <div class="mc-section__header">
                <span class="mc-section__number">04</span>
                <span class="mc-section__title">Ticket Pricing Rules</span>
            </div>

            @error('ticket_prices_json')
                <div class="at-alert at-alert--error" style="margin-bottom:14px;">
                    <span class="at-alert__icon">x</span> {{ $message }}
                </div>
            @enderror

            <div class="mc-pricing-intro">
                <div>
                    <p class="mc-pricing-intro__title">One movie price map, applied to every assigned cinema theatre.</p>
                    <p class="mc-pricing-intro__copy">
                        Fill each theatre type by seat type and day type before the final movie creation submit.
                    </p>
                </div>
                <span class="mc-pricing-intro__badge">40 rules</span>
            </div>

            <div id="mc-pricing-status" class="mc-pricing-status vc-hidden" role="alert"></div>

            <div class="mc-pricing-grid" id="mc-pricing-grid">
                @foreach ($pricingTheatres as $theatreName)
                    <div class="mc-pricing-card">
                        <div class="mc-pricing-card__top">
                            <span class="mc-pricing-card__eyebrow">Theatre</span>
                            <h3 class="mc-pricing-card__title">{{ $theatreName }}</h3>
                        </div>

                        <div class="mc-pricing-table">
                            <div class="mc-pricing-row mc-pricing-row--head">
                                <span>Seat Type</span>
                                <span>Weekday</span>
                                <span>Weekend</span>
                            </div>

                            @foreach ($pricingSeats as $seatKey => $seatLabel)
                                <div class="mc-pricing-row">
                                    <span class="mc-pricing-seat">{{ $seatLabel }}</span>
                                    @foreach ($pricingDays as $dayKey => $dayLabel)
                                        <label class="mc-price-field" aria-label="{{ $theatreName }} {{ $seatLabel }} {{ $dayLabel }} price">
                                            <span>RM</span>
                                            <input
                                                type="number"
                                                class="mc-price-input"
                                                inputmode="decimal"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="0.00"
                                                data-theatre-name="{{ $theatreName }}"
                                                data-seat-type="{{ $seatKey }}"
                                                data-day-type="{{ $dayKey }}"
                                            >
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mc-section mc-section--auth">
            <div class="mc-section__header">
                <span class="mc-section__number">05</span>
                <span class="mc-section__title">Supervisor Confirmation</span>
            </div>
            <div class="mc-auth-grid">
                <div class="ac-field">
                    <label for="supervisor_id">Supervisor <span class="required">*</span></label>
                    <select id="supervisor_id" name="supervisor_id"
                            class="ac-select mc-input--compact @error('supervisor_id') is-invalid @enderror" required>
                        <option value="">— Select supervisor —</option>
                        @foreach ($supervisors as $sv)
                            <option value="{{ $sv->supervisor_id }}"
                                    {{ old('supervisor_id') == $sv->supervisor_id ? 'selected' : '' }}>
                                {{ $sv->supervisor_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supervisor_id') <span class="ac-error">{{ $message }}</span> @enderror
                </div>
                <div class="ac-field">
                    <label for="supervisor_password">Password <span class="required">*</span></label>
                    <input type="password" id="supervisor_password" name="supervisor_password"
                           class="ac-input mc-input--compact @error('supervisor_password') is-invalid @enderror"
                           placeholder="Supervisor password" required autocomplete="current-password">
                    @error('supervisor_password') <span class="ac-error">{{ $message }}</span> @enderror
                </div>
            </div>
            @if (session('auth_error'))
                <div class="at-alert at-alert--error" style="margin-top:12px;">
                    <span class="at-alert__icon">✕</span> {{ session('auth_error') }}
                </div>
            @endif
        </div>

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                🎬 Create Movie &amp; Assign
            </button>
        </div>

    </form>
</div>


{{-- ══════════════════════════════════════════════════════════
     VIEW 2 — CINEMA SELECTION GRID
══════════════════════════════════════════════════════════ --}}
<div id="mc-cinema-select-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="mc-select-back" type="button">← Back to Form</button>
        <span class="ct-selection-title">Assign Cinemas</span>
        <span class="mc-select-hint">Click cards to select · then Assign Quota</span>
    </div>

    {{-- Selection action bar ─────────────────────────────── --}}
    <div class="mc-selection-bar">
        <div class="mc-selection-bar__left">
            <button type="button" id="mc-select-all-btn"       class="mc-sel-btn mc-sel-btn--secondary">☑ Select All</button>
            <button type="button" id="mc-clear-selection-btn"  class="mc-sel-btn mc-sel-btn--ghost">✕ Clear Selection</button>
            <span class="mc-sel-count" id="mc-sel-count">0 selected</span>
        </div>
        <button type="button" id="mc-assign-quota-btn" class="mc-sel-btn mc-sel-btn--primary" disabled>
            📋 Assign Quota
        </button>
    </div>

    @if ($cinemas->isEmpty())
        <div class="ac-empty">
            <div class="ac-empty__icon">🏢</div>
            <p class="ac-empty__text">No cinemas available. <a href="{{ route('admin.cinema.create') }}">Add one first.</a></p>
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
                    aria-label="Select {{ $cinema->cinema_name }}"
                >
                    <div class="vc-card__img-wrap">
                        @if ($cinema->cinema_picture)
                            <img src="{{ asset('images/cinemas/' . $cinema->cinema_picture) }}"
                                 alt="{{ $cinema->cinema_name }}" class="vc-card__img">
                        @else
                            <div class="vc-card__img-placeholder">🎬</div>
                        @endif
                        {{-- Assigned overlay (permanently locked) --}}
                        <div class="mc-assigned-overlay vc-hidden">✓ Assigned</div>
                        {{-- Pending selection overlay (ephemeral, while picking) --}}
                        <div class="mc-pending-overlay vc-hidden">✓ Selected</div>
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

</div>


{{-- ══════════════════════════════════════════════════════════
     QUOTA MODAL  (multi-cinema batch assignment)
══════════════════════════════════════════════════════════ --}}
<div id="mc-quota-modal" class="mc-modal-overlay vc-hidden"
     role="dialog" aria-modal="true" aria-labelledby="mc-quota-label">
    <div class="mc-modal">

        {{-- Horizontal scroll strip of selected cinemas ────── --}}
        <div class="mc-modal__strip mc-modal__strip--scroll" id="mc-modal-strip">
            {{-- JS injects: one .mc-modal__cinema-pill per selected cinema --}}
        </div>

        <div class="mc-modal__body">
            <p class="mc-modal__label" id="mc-quota-label">Configure Quota</p>
            <p class="mc-modal__sub" id="mc-modal-sub"></p>

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
                        <span class="optional">(daily showtimes — unlimited)</span>
                    </label>
                    <input type="number" id="mc-slots" class="ac-input mc-input--compact"
                           min="1" placeholder="e.g. 4">
                    <span class="ac-error vc-hidden" id="mc-slots-err">Enter at least 1.</span>
                </div>
            </div>
        </div>

        <div class="mc-modal__actions">
            <button type="button" id="mc-quota-confirm" class="ac-btn ac-btn--primary">
                ✓ Assign to All Selected Cinemas
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
