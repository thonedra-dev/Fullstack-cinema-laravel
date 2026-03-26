<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminCinemaController;
use App\Http\Controllers\AdminCinemaViewController;
use App\Http\Controllers\AdminCityController;
use App\Http\Controllers\AdminServiceController;
use App\Http\Controllers\AdminTheatreController;
use App\Http\Controllers\AdminTheatreResourceController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// ── Cinemas ──────────────────────────────────────────────────────────────
Route::get('/admin/cinema/create', [AdminCinemaController::class, 'create'])
    ->name('admin.cinema.create');

Route::post('/admin/cinema', [AdminCinemaController::class, 'store'])
    ->name('admin.cinema.store');

Route::get('/admin/cinema', [AdminCinemaViewController::class, 'index'])
    ->name('admin.cinema.index');

// ── Cities ───────────────────────────────────────────────────────────────
Route::get('/admin/city/create', [AdminCityController::class, 'create'])
    ->name('admin.city.create');

Route::post('/admin/city', [AdminCityController::class, 'store'])
    ->name('admin.city.store');

// ── Services ─────────────────────────────────────────────────────────────
Route::get('/admin/service/create', [AdminServiceController::class, 'create'])
    ->name('admin.service.create');

Route::post('/admin/service', [AdminServiceController::class, 'store'])
    ->name('admin.service.store');

// ── Theatres ─────────────────────────────────────────────────────────────
Route::get('/admin/theatre/create', [AdminTheatreController::class, 'create'])
    ->name('admin.theatre.create');

Route::post('/admin/theatre', [AdminTheatreController::class, 'store'])
    ->name('admin.theatre.store');

// ── Theatre seat layout viewer ────────────────────────────────────────────
Route::get('/admin/theatre/{id}/resources', [AdminTheatreResourceController::class, 'show'])
    ->name('admin.theatre.resources')
    ->where('id', '[0-9]+');