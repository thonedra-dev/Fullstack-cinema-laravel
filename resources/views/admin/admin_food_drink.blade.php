{{--
    resources/views/admin/admin_food_drink.blade.php
    Extends central admin framework layout shell.
--}}
@extends('admin.admin_team')

@section('page_title', 'Add Food & Drink Item')

@section('head_extras')
    @vite(['resources/css/admin_food_drink.css', 'resources/js/admin_food_drink.js'])
@endsection

@section('content')
<div class="afd-container">
    <div class="afd-card">
        <div class="afd-card__header">
            <h1 class="afd-card__title">Create Master Item</h1>
            <p class="afd-card__subtitle">Define generic assets for the global cinema ecosystem menu catalogue.</p>
        </div>

        <form action="{{ route('admin.food_drink.store') }}" method="POST" enctype="multipart/form-data" class="afd-form">
            @csrf

            <div class="afd-form__grid">
                
                <div class="afd-form__fields">
                    <div class="afd-group">
                        <label for="name" class="afd-label">Product Name</label>
                        <input type="text" id="name" name="name" class="afd-input @error('name') is-invalid @enderror" placeholder="e.g., Caramel Popcorn XL" value="{{ old('name') }}" required autocomplete="off">
                        @error('name')
                            <span class="afd-error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="afd-group">
                        <label for="category_tag" class="afd-label">Menu Category Tag</label>
                        <select id="category_tag" name="category_tag" class="afd-input afd-select @error('category_tag') is-invalid @enderror" required>
                            <option value="" disabled selected>-- Select Cinema Category --</option>
                            <option value="popcorn_crunch" {{ old('category_tag') == 'popcorn_crunch' ? 'selected' : '' }}>🍿 Popcorn & Crunch</option>
                            <option value="hot_bites" {{ old('category_tag') == 'hot_bites' ? 'selected' : '' }}>🍔 Finger Food & Hot Bites</option>
                            <option value="beverages" {{ old('category_tag') == 'beverages' ? 'selected' : '' }}>🥤 Beverages & Refreshments</option>
                            <option value="combos" {{ old('category_tag') == 'combos' ? 'selected' : '' }}>🍱 Cinema Value Combos</option>
                            <option value="sweets" {{ old('category_tag') == 'sweets' ? 'selected' : '' }}>🍦 Sweet Treats & Desserts</option>
                            <option value="premium" {{ old('category_tag') == 'premium' ? 'selected' : '' }}>✨ VIP Luxury Platters</option>
                        </select>
                        @error('category_tag')
                            <span class="afd-error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="afd-group">
                        <label for="suggested_price" class="afd-label">Suggested Base Price ($)</label>
                        <input type="number" id="suggested_price" name="suggested_price" class="afd-input @error('suggested_price') is-invalid @enderror" placeholder="0.00" step="0.01" min="0" value="{{ old('suggested_price') }}">
                        <p class="afd-input-hint">Serves as a default baseline price to guide local branch managers during item activation.</p>
                        @error('suggested_price')
                            <span class="afd-error-text">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="afd-form__media">
                    <div class="afd-group">
                        <label class="afd-label">Product Image Asset</label>
                        <div class="afd-upload-zone" id="uploadZone">
                            <input type="file" id="imageInput" name="image" accept="image/*" class="afd-file-hidden">
                            
                            <div class="afd-upload-state" id="uploadStateEmpty">
                                <span class="afd-upload-icon">📷</span>
                                <p class="afd-upload-text">Click or drag image file here to upload</p>
                                <span class="afd-upload-subtext">Supports PNG, JPG, JPEG or WEBP (Max: 2MB)</span>
                            </div>

                            <div class="afd-preview-state d-none" id="uploadStatePreview">
                                <img src="" id="imagePreview" alt="Item Preview" class="afd-img-fluid">
                                <div class="afd-preview-overlay">
                                    <span class="afd-change-btn">Change Asset Image</span>
                                </div>
                            </div>
                        </div>
                        @error('image')
                            <span class="afd-error-text">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

            </div>

            <div class="afd-form__footer">
                <button type="submit" class="afd-btn afd-btn--primary">
                    <span class="afd-btn__icon">➕</span> Save Product to Catalog
                </button>
            </div>
        </form>
    </div>
</div>
@endsection