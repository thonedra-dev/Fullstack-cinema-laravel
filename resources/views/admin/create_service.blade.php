{{--
    resources/views/admin/create_service.blade.php
    ───────────────────────────────────────────────
    Feature: Add a new service (e.g. IMAX, Dolby, Recliners).
    Controller: AdminServiceController@create / @store
    No extra data variables required from controller.
--}}
@extends('admin.admin_team')

@section('page_title', 'Add Service')

@section('head_extras')
    @vite(['resources/css/create_service.css', 'resources/js/create_service.js'])
@endsection

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">Add a <span>Service</span></h1>
    <p class="ac-page-header__sub">Define a new cinema service type (e.g. IMAX, Dolby Atmos).</p>
</div>

<div class="ac-card">

    <form
        action="{{ route('admin.service.store') }}"
        method="POST"
        enctype="multipart/form-data"
        novalidate
    >
        @csrf

        <div class="ac-form-grid">

            {{-- Service Name --}}
            <div class="ac-field ac-field--full">
                <label for="service_name">
                    Service Name <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="service_name"
                    name="service_name"
                    class="ac-input @error('service_name') is-invalid @enderror"
                    placeholder="e.g. IMAX, Dolby Atmos, Gold Class"
                    value="{{ old('service_name') }}"
                    required
                >
                @error('service_name')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Service Icon --}}
            <div class="ac-field ac-field--full">
                <label>
                    Service Icon
                    <span class="optional">(optional · PNG / SVG / WEBP · max 1 MB)</span>
                </label>
                <label class="ac-file-label" for="service_icon">
                    <span class="ac-file-label__icon">🖼</span>
                    <span class="ac-file-label__text">
                        <strong>Click to upload</strong> or drag &amp; drop
                    </span>
                    <input
                        type="file"
                        id="service_icon"
                        name="service_icon"
                        accept="image/png,image/svg+xml,image/webp"
                    >
                </label>
                <span id="service_icon_preview" class="ac-file-preview"></span>
                @error('service_icon')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

        </div>{{-- /.ac-form-grid --}}

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                <span>⚙</span> Save Service
            </button>
        </div>

    </form>
</div>{{-- /.ac-card --}}

@endsection