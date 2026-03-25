{{--
    resources/views/admin/create_theatre.blade.php
    ───────────────────────────────────────────────
    Feature: Create a new theatre inside an existing cinema.
    Controller: AdminTheatreController@create / @store
    Data injected by controller (logic-free blade):
      $cinemas  – Collection of Cinema models {cinema_id, cinema_name, cinema_picture, ->city}
      $services – Collection of Service models {service_id, service_name, service_icon}
--}}
@extends('admin.admin_team')

@section('page_title', 'Create Theatre')

@section('head_extras')
    @vite(['resources/css/create_theatre.css', 'resources/js/create_theatre.js'])
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════
     FORM VIEW  (default — visible on load)
══════════════════════════════════════════════════════════ --}}
<div id="ct-form-view">

    <div class="ac-page-header">
        <h1 class="ac-page-header__title">Create a <span>Theatre</span></h1>
        <p class="ac-page-header__sub">Add a new screen/hall and attach its services.</p>
    </div>

    <div class="ac-card">
        <form
            action="{{ route('admin.theatre.store') }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

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

                {{-- Cinema selector — button replaces dropdown --}}
                <div class="ac-field ac-field--full">
                    <label>Cinema <span class="required">*</span></label>

                    {{-- Hidden input carries the actual cinema_id for form submission --}}
                    <input
                        type="hidden"
                        id="ct-cinema-id-input"
                        name="cinema_id"
                        value="{{ old('cinema_id') }}"
                    >

                    {{-- Selected cinema display (hidden until a cinema is chosen) --}}
                    <div
                        id="ct-selected-cinema-display"
                        class="ct-selected-display {{ old('cinema_id') ? '' : 'vc-hidden' }}"
                    >
                        <span class="ct-selected-display__name" id="ct-selected-cinema-name">
                            {{-- Populated by JS on selection; on validation error, JS restores from old() --}}
                        </span>
                        <span class="ct-selected-display__hint">Selected cinema</span>
                    </div>

                    <button
                        type="button"
                        id="ct-select-cinema-btn"
                        class="ct-select-btn"
                    >
                        🎬 Select Cinema
                    </button>

                    @error('cinema_id')
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
                        <input
                            type="file"
                            id="theatre_icon"
                            name="theatre_icon"
                            accept="image/png,image/svg+xml,image/webp"
                        >
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
                        <input
                            type="file"
                            id="theatre_poster"
                            name="theatre_poster"
                            accept="image/jpeg,image/png,image/webp"
                        >
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
     SELECTION VIEW  (hidden by default — shown on button click)
══════════════════════════════════════════════════════════ --}}
<div id="ct-selection-view" class="vc-hidden">

    <div class="vc-detail-header">
        <button class="vc-back-btn" id="ct-selection-back" type="button">
            ← Back to Form
        </button>
        <span class="ct-selection-title">Choose a Cinema</span>
    </div>

    @if ($cinemas->isEmpty())
        <div class="ac-empty">
            <div class="ac-empty__icon">🎬</div>
            <p class="ac-empty__text">
                No cinemas available.
                <a href="{{ route('admin.cinema.create') }}">Add one first.</a>
            </p>
        </div>
    @else
        <div class="vc-card-grid">
            @foreach ($cinemas as $cinema)
                <div
                    class="vc-card ct-selectable-card"
                    data-cinema-id="{{ $cinema->cinema_id }}"
                    data-cinema-name="{{ $cinema->cinema_name }}"
                    data-cinema-img="{{ $cinema->cinema_picture ? asset('images/cinemas/' . $cinema->cinema_picture) : '' }}"
                    tabindex="0"
                    role="button"
                    aria-label="Select {{ $cinema->cinema_name }}"
                >
                    {{-- Reuse exact same card structure as view_cinema --}}
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
                    </div>
                    <div class="vc-card__body">
                        <span class="vc-card__name">{{ $cinema->cinema_name }}</span>
                        <div class="vc-card__location">
                            <span class="vc-card__city">{{ $cinema->city?->city_name ?? '—' }}</span>
                            <span class="vc-card__state">{{ $cinema->city?->city_state ?? '—' }}</span>
                        </div>
                    </div>
                </div>{{-- /.vc-card --}}
            @endforeach
        </div>
    @endif

</div>{{-- /#ct-selection-view --}}


{{-- ══════════════════════════════════════════════════════════
     CONFIRMATION MODAL  (hidden by default)
══════════════════════════════════════════════════════════ --}}
<div id="ct-confirm-modal" class="ct-modal-overlay vc-hidden" role="dialog" aria-modal="true" aria-labelledby="ct-modal-title">
    <div class="ct-modal">

        <img id="ct-modal-img" class="ct-modal__img vc-hidden" alt="">

        <div id="ct-modal-img-placeholder" class="ct-modal__img-placeholder">🎬</div>

        <p class="ct-modal__question" id="ct-modal-title">
            Use <strong id="ct-modal-name"></strong>?
        </p>

        <div class="ct-modal__actions">
            <button type="button" id="ct-modal-confirm" class="ac-btn ac-btn--primary">
                Yes, use this
            </button>
            <button type="button" id="ct-modal-cancel" class="ct-modal__cancel">
                No, go back
            </button>
        </div>

    </div>
</div>

{{-- Inline JSON: cinema names for restoring display on validation error --}}
<script>
    window.CT_CINEMAS = {!! json_encode(
        $cinemas->map(fn($c) => ['id' => $c->cinema_id, 'name' => $c->cinema_name])
                ->values()
    ) !!};
</script>

@endsection