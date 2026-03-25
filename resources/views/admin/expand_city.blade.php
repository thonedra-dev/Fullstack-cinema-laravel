{{--
    resources/views/admin/expand_city.blade.php
    ───────────────────────────────────────────
    Feature: Register a new city under an existing state.
    Controller: AdminCityController@create / @store
    Data injected by controller (logic-free blade):
      $states – array/collection of existing state name strings (for datalist)
--}}
@extends('admin.admin_team')

@section('page_title', 'Add City')

@section('head_extras')
    @vite(['resources/css/expand_city.css', 'resources/js/expand_city.js'])
@endsection

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">Add a <span>New City</span></h1>
    <p class="ac-page-header__sub">Extend coverage by registering a city under its state.</p>
</div>

<div class="ac-card">

    <form
        action="{{ route('admin.city.store') }}"
        method="POST"
        novalidate
    >
        @csrf

        <div class="ac-form-grid">

            {{-- City Name --}}
            <div class="ac-field">
                <label for="city_name">
                    City Name <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="city_name"
                    name="city_name"
                    class="ac-input @error('city_name') is-invalid @enderror"
                    placeholder="e.g. Ipoh"
                    value="{{ old('city_name') }}"
                    required
                >
                @error('city_name')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- State — free-text with datalist of existing states --}}
            <div class="ac-field">
                <label for="city_state">
                    State <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="city_state"
                    name="city_state"
                    class="ac-input @error('city_state') is-invalid @enderror"
                    placeholder="e.g. Perak"
                    value="{{ old('city_state') }}"
                    list="state_datalist"
                    autocomplete="off"
                    required
                >
                {{-- Datalist provides autocomplete suggestions from existing states --}}
                <datalist id="state_datalist">
                    @foreach ($states as $state)
                        <option value="{{ $state }}">
                    @endforeach
                </datalist>
                @error('city_state')
                    <span class="ac-error">{{ $message }}</span>
                @enderror
            </div>

        </div>{{-- /.ac-form-grid --}}

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                <span>📍</span> Save City
            </button>
        </div>

    </form>
</div>{{-- /.ac-card --}}

@endsection