{{--
    resources/views/admin/create_theatre.blade.php
    ───────────────────────────────────────────────
    Feature: Create a reusable master theatre type.
    Controller: AdminTheatreController@create / @store
    Data injected by controller (logic-free blade):
      $services     – Collection of Service models {service_id, service_name, service_icon}
      $seatsJsonOld – old('seats_json') passed through for validation-error restore
--}}
@extends('admin.admin_team')

@section('page_title', 'Create Theatre')

{{-- Hide the topbar title — inline heading below is sufficient --}}
@section('hide_topbar_title') @endsection

@section('head_extras')
    @vite(['resources/css/create_theatre.css', 'resources/js/create_theatre.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     VIEW 1 — MAIN FORM  (default visible)
══════════════════════════════════════════════════════════ --}}
<div id="ct-form-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">Create a <span>Theatre</span></h1>
        <p class="ac-page-header__sub">Add a reusable theatre type, attach services and define its universal seat layout.</p>
    </div>

    <div class="ac-card">
        <form
            action="{{ route('admin.theatre.store') }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            {{-- Hidden: serialised seat rows — written by JS seat builder --}}
            <input
                type="hidden"
                id="ct-seats-json"
                name="seats_json"
                value="{{ old('seats_json', '[]') }}"
            >

            <div class="ac-form-grid">

                {{-- Theatre Name --}}
                <div class="ac-field ac-field--full">
                    <label for="theatre_name">
                        Theatre Name <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="theatre_name"
                        name="theatre_name"
                        class="ac-input @error('theatre_name') is-invalid @enderror"
                        placeholder="e.g. Hall 3 — IMAX"
                        value="{{ old('theatre_name') }}"
                        required
                    >
                    @error('theatre_name')
                        <span class="ac-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Theatre Icon --}}
                <div class="ac-field">
                    <label>
                        Theatre Icon
                        <span class="optional">(optional · PNG / SVG / WEBP · max 1 MB)</span>
                    </label>
                    <label class="ac-file-label" for="theatre_icon">
                        <span class="ac-file-label__icon">🎭</span>
                        <span class="ac-file-label__text">
                            <strong>Click to upload</strong> icon
                        </span>
                        <input type="file" id="theatre_icon" name="theatre_icon"
                               accept="image/png,image/svg+xml,image/webp">
                    </label>
                    <span id="theatre_icon_preview" class="ac-file-preview"></span>
                    @error('theatre_icon')
                        <span class="ac-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Theatre Poster --}}
                <div class="ac-field">
                    <label>
                        Theatre Poster
                        <span class="optional">(optional · JPEG / PNG / WEBP · max 2 MB)</span>
                    </label>
                    <label class="ac-file-label" for="theatre_poster">
                        <span class="ac-file-label__icon">🖼</span>
                        <span class="ac-file-label__text">
                            <strong>Click to upload</strong> poster
                        </span>
                        <input type="file" id="theatre_poster" name="theatre_poster"
                               accept="image/jpeg,image/png,image/webp">
                    </label>
                    <span id="theatre_poster_preview" class="ac-file-preview"></span>
                    @error('theatre_poster')
                        <span class="ac-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Services --}}
                <div class="ac-field ac-field--full">
                    <label>Services <span class="optional">(select all that apply)</span></label>

                    @error('services')
                        <span class="ac-error">{{ $message }}</span>
                    @enderror

                    @if ($services->isEmpty())
                        <p class="ct-no-services">
                            No services defined yet.
                            <a href="{{ route('admin.service.create') }}">Add one first.</a>
                        </p>
                    @else
                        <div class="ct-service-grid">
                            @foreach ($services as $service)
                                <label
                                    class="ct-service-chip {{ in_array($service->service_id, old('services', [])) ? 'is-checked' : '' }}"
                                    for="service_{{ $service->service_id }}"
                                >
                                    <input
                                        type="checkbox"
                                        id="service_{{ $service->service_id }}"
                                        name="services[]"
                                        value="{{ $service->service_id }}"
                                        {{ in_array($service->service_id, old('services', [])) ? 'checked' : '' }}
                                    >
                                    @if ($service->service_icon)
                                        <img
                                            src="{{ asset('images/services/' . $service->service_icon) }}"
                                            alt="{{ $service->service_name }}"
                                            class="ct-service-chip__icon"
                                        >
                                    @else
                                        <span class="ct-service-chip__icon-fallback">⚙</span>
                                    @endif
                                    <span class="ct-service-chip__name">{{ $service->service_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Seat Structure summary (shown after builder is used) --}}
                <div class="ac-field ac-field--full">
                    <label>Seat Structure <span class="optional">(optional — define rows and types)</span></label>

                    @error('seats_json')
                        <span class="ac-error">{{ $message }}</span>
                    @enderror

                    {{-- Summary strip — hidden until rows are defined --}}
                    <div id="ct-seats-summary" class="ct-seats-summary vc-hidden"></div>

                    <button type="button" id="ct-define-seats-btn" class="ct-select-btn">
                        💺 Define Seat Structure
                    </button>
                </div>

            </div>{{-- /.ac-form-grid --}}

            <div class="ac-form-actions">
                <button type="submit" class="ac-btn ac-btn--primary">
                    🏟 Create Theatre
                </button>
            </div>

        </form>
    </div>{{-- /.ac-card --}}

</div>{{-- /#ct-form-view --}}


{{-- ══════════════════════════════════════════════════════════
     VIEW 2 — SEAT BUILDER  (hidden by default)
══════════════════════════════════════════════════════════ --}}
<div id="ct-seat-builder-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="ct-seat-builder-back" type="button">← Back to Form</button>
        <span class="ct-selection-title">Define Seat Structure</span>
    </div>

    <div class="sb-layout">

        {{-- ── LEFT: visual preview ── --}}
        <div class="sb-preview-panel">

            {{-- Screen indicator --}}
            <div class="sb-screen-wrap">
                <div class="sb-screen"></div>
                <span class="sb-screen-label">SCREEN</span>
            </div>

            {{-- Rows render here by JS --}}
            <div id="sb-preview" class="sb-preview">
                <p class="sb-preview__empty" id="sb-preview-empty">
                    No rows defined yet. Add a row →
                </p>
            </div>

        </div>{{-- /.sb-preview-panel --}}

        {{-- ── RIGHT: row builder form ── --}}
        <div class="sb-builder-panel">

            <div class="sb-builder-card">

                {{-- Next row label badge --}}
                <div class="sb-next-label-wrap">
                    <span class="sb-next-label-text">Next row:</span>
                    <span class="sb-next-label-badge" id="sb-next-label">A</span>
                </div>

                {{-- Seat count --}}
                <div class="ac-field">
                    <label for="sb-seat-count">Number of Seats <span class="required">*</span></label>
                    <input
                        type="number"
                        id="sb-seat-count"
                        class="ac-input"
                        min="1"
                        max="40"
                        placeholder="e.g. 12"
                    >
                    <span class="sb-count-hint" id="sb-count-hint"></span>
                </div>

                {{-- Seat type — styled radio cards --}}
                <div class="ac-field">
                    <label>Seat Type <span class="required">*</span></label>
                    <div class="sb-type-grid">

                        <label class="sb-type-card" data-type="Standard">
                            <input type="radio" name="sb_seat_type" value="Standard">
                            <div class="sb-type-card__icon">
                                <span class="sb-seat sb-seat--standard"></span>
                            </div>
                            <span class="sb-type-card__name">Standard</span>
                        </label>

                        <label class="sb-type-card" data-type="Couple">
                            <input type="radio" name="sb_seat_type" value="Couple">
                            <div class="sb-type-card__icon">
                                <span class="sb-seat sb-seat--couple"></span>
                                <span class="sb-seat sb-seat--couple"></span>
                            </div>
                            <span class="sb-type-card__name">Couple</span>
                        </label>

                        <label class="sb-type-card" data-type="Premium">
                            <input type="radio" name="sb_seat_type" value="Premium">
                            <div class="sb-type-card__icon">
                                <span class="sb-seat sb-seat--premium"></span>
                            </div>
                            <span class="sb-type-card__name">Premium</span>
                        </label>

                        <label class="sb-type-card" data-type="Family">
                            <input type="radio" name="sb_seat_type" value="Family">
                            <div class="sb-type-card__icon">
                                <span class="sb-seat sb-seat--family"></span>
                            </div>
                            <span class="sb-type-card__name">Family</span>
                        </label>

                    </div>
                </div>

                {{-- Live mini-preview of the row being built --}}
                <div class="sb-row-preview" id="sb-row-preview">
                    <span class="sb-row-preview__label">Preview</span>
                    <div class="sb-row-preview__seats" id="sb-row-preview-seats"></div>
                </div>

                {{-- Actions --}}
                <div class="sb-builder-actions">
                    <button type="button" id="sb-add-row-btn" class="ac-btn ac-btn--primary">
                        ＋ Add Row
                    </button>
                    <button type="button" id="sb-undo-btn" class="sb-undo-btn">
                        ↩ Undo Last
                    </button>
                </div>

                {{-- Error message --}}
                <p class="sb-error vc-hidden" id="sb-error"></p>

            </div>{{-- /.sb-builder-card --}}

            {{-- Seat type legend --}}
            <div class="sb-legend">
                <div class="sb-legend__item">
                    <span class="sb-seat sb-seat--standard"></span> Standard
                </div>
                <div class="sb-legend__item">
                    <span class="sb-seat sb-seat--couple"></span> Couple
                </div>
                <div class="sb-legend__item">
                    <span class="sb-seat sb-seat--premium"></span> Premium
                </div>
                <div class="sb-legend__item">
                    <span class="sb-seat sb-seat--family"></span> Family
                </div>
            </div>

        </div>{{-- /.sb-builder-panel --}}

    </div>{{-- /.sb-layout --}}

    {{-- Finalize button --}}
    <div class="sb-finalize-bar">
        <div class="sb-finalize-summary" id="sb-finalize-summary"></div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <button type="button" id="sb-clear-btn" class="sb-clear-btn">✕ Clear All</button>
            <button type="button" id="sb-finalize-btn" class="ac-btn ac-btn--primary">
                ✓ Finalize Seat Structure
            </button>
        </div>
    </div>

</div>{{-- /#ct-seat-builder-view --}}


{{-- Inline JSON for JS restore on validation error --}}
<script>
    window.CT_SEATS_JSON = {!! json_encode(old('seats_json', '[]')) !!};
</script>

@endsection
