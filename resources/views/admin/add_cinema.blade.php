{{--
    resources/views/admin/add_cinema.blade.php
    ──────────────────────────────────────────
    Feature: Add a new Cinema.
    Controller: AdminCinemaController@create
    Data injected by controller (logic-free blade):
      $states        – array/collection of state name strings
      $citiesByState – collection keyed by state → [{id, name}, …]
--}}
@extends('admin.admin_team')

@section('page_title', 'Add Cinema')

@section('head_extras')
    @vite(['resources/css/add_cinema.css', 'resources/js/add_cinema.js'])
    {{-- Inline JSON for city dropdown — produced by controller, rendered here --}}
    <script>
        window.AC_CITIES_BY_STATE = {!! json_encode(
            $citiesByState->map(function ($cities) {
                return $cities->map(function ($city) {
                    return ['id' => $city->city_id, 'name' => $city->city_name];
                })->values();
            })
        ) !!};
    </script>
@endsection

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">Define a <span>New Cinema</span></h1>
    <p class="ac-page-header__sub">Fill in the details below to register a cinema location.</p>
</div>

<div class="ac-card">

    <form
        action="{{ route('admin.cinema.store') }}"
        method="POST"
        enctype="multipart/form-data"
        novalidate
    >
        @csrf

        <div class="ac-form-grid">

            {{-- Cinema Name --}}
            <div class="ac-field">
                <label for="cinema_name">
                    Cinema Name <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="cinema_name"
                    name="cinema_name"
                    class="ac-input @error('cinema_name') is-invalid @enderror"
                    placeholder="e.g. GSC Mid Valley"
                    value="{{ old('cinema_name') }}"
                    required
                >
                @error('cinema_name')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Cinema Contact --}}
            <div class="ac-field">
                <label for="cinema_contact">
                    Contact Number <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="cinema_contact"
                    name="cinema_contact"
                    class="ac-input @error('cinema_contact') is-invalid @enderror"
                    placeholder="e.g. +60 3-2282 8888"
                    value="{{ old('cinema_contact') }}"
                    required
                >
                @error('cinema_contact')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Cinema Address --}}
            <div class="ac-field ac-field--full">
                <label for="cinema_address">
                    Address <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="cinema_address"
                    name="cinema_address"
                    class="ac-input @error('cinema_address') is-invalid @enderror"
                    placeholder="e.g. Lingkaran Syed Putra, Mid Valley City"
                    value="{{ old('cinema_address') }}"
                    required
                >
                @error('cinema_address')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- State Dropdown --}}
            <div class="ac-field">
                <label for="state_select">
                    State <span class="required">*</span>
                </label>
                <select
                    id="state_select"
                    name="state_select"
                    class="ac-select @error('city_id') is-invalid @enderror"
                >
                    <option value="">— Select state —</option>
                    @foreach ($states as $state)
                        <option
                            value="{{ $state }}"
                            {{ old('state_select') === $state ? 'selected' : '' }}
                        >
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- City Dropdown (populated by add_cinema.js) --}}
            <div class="ac-field">
                <label for="city_id">
                    City <span class="required">*</span>
                </label>
                <select
                    id="city_id"
                    name="city_id"
                    class="ac-select @error('city_id') is-invalid @enderror"
                    data-selected="{{ old('city_id') }}"
                    disabled
                >
                    <option value="">— Select state first —</option>
                </select>
                @error('city_id')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Description --}}
            <div class="ac-field ac-field--full">
                <label for="cinema_description">
                    Description <span class="optional">(optional)</span>
                </label>
                <textarea
                    id="cinema_description"
                    name="cinema_description"
                    class="ac-textarea @error('cinema_description') is-invalid @enderror"
                    placeholder="Brief description of the cinema, facilities, etc."
                >{{ old('cinema_description') }}</textarea>
                @error('cinema_description')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Cinema Picture --}}
            <div class="ac-field ac-field--full">
                <label>
                    Cinema Picture
                    <span class="optional">(optional · JPEG / PNG / WEBP · max 2 MB)</span>
                </label>
                <label class="ac-file-label" for="cinema_picture">
                    <span class="ac-file-label__icon">🖼</span>
                    <span class="ac-file-label__text">
                        <strong>Click to upload</strong> or drag &amp; drop
                    </span>
                    <input
                        type="file"
                        id="cinema_picture"
                        name="cinema_picture"
                        accept="image/jpeg,image/png,image/webp"
                    >
                </label>
                <span id="file_preview_name" class="ac-file-preview"></span>
                @error('cinema_picture')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

        </div>{{-- /.ac-form-grid --}}

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                <span>＋</span> Add Cinema
            </button>
        </div>

    </form>
</div>{{-- /.ac-card --}}

@endsection