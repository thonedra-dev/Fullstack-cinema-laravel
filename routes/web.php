<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminCinemaController;
use App\Http\Controllers\AdminCinemaViewController;
use App\Http\Controllers\AdminCityController;
use App\Http\Controllers\AdminManagerController;
use App\Http\Controllers\AdminMovieController;
use App\Http\Controllers\AdminMovieFormationController;
use App\Http\Controllers\AdminMovieProposalController;
use App\Http\Controllers\AdminServiceController;
use App\Http\Controllers\AdminTheatreController;
use App\Http\Controllers\AdminTheatreResourceController;
use App\Http\Controllers\BranchManagerAuthController;
use App\Http\Controllers\BranchManagerDashboardController;
use App\Http\Controllers\BranchManagerMovieFormationController;
use App\Http\Controllers\BranchManagerResourceController;
use App\Http\Controllers\BranchManagerShowtimeController;
use App\Http\Controllers\BranchManagerUpcomingController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/admin/cinema/create', [AdminCinemaController::class, 'create'])->name('admin.cinema.create');
Route::post('/admin/cinema',       [AdminCinemaController::class, 'store'])->name('admin.cinema.store');
Route::get('/admin/cinema',        [AdminCinemaViewController::class, 'index'])->name('admin.cinema.index');

Route::get('/admin/city/create', [AdminCityController::class, 'create'])->name('admin.city.create');
Route::post('/admin/city',       [AdminCityController::class, 'store'])->name('admin.city.store');

Route::get('/admin/service/create', [AdminServiceController::class, 'create'])->name('admin.service.create');
Route::post('/admin/service',       [AdminServiceController::class, 'store'])->name('admin.service.store');

Route::get('/admin/theatre/create', [AdminTheatreController::class, 'create'])->name('admin.theatre.create');
Route::post('/admin/theatre',       [AdminTheatreController::class, 'store'])->name('admin.theatre.store');

Route::get('/admin/theatre/{id}/resources', [AdminTheatreResourceController::class, 'show'])
    ->name('admin.theatre.resources')->where('id', '[0-9]+');

Route::get('/admin/movie/create', [AdminMovieController::class, 'create'])->name('admin.movie.create');
Route::post('/admin/movie',       [AdminMovieController::class, 'store'])->name('admin.movie.store');

Route::get('/admin/movie/{movieId}/cinema/{cinemaId}', [AdminMovieFormationController::class, 'show'])
    ->name('admin.movie.formation')
    ->where(['movieId' => '[0-9]+', 'cinemaId' => '[0-9]+']);

Route::get('/admin/managers',           [AdminManagerController::class, 'index'])->name('admin.managers.index');
Route::post('/admin/managers/assign',   [AdminManagerController::class, 'assign'])->name('admin.managers.assign');
Route::post('/admin/managers/unassign', [AdminManagerController::class, 'unassign'])->name('admin.managers.unassign');

// ── Movie Proposals ───────────────────────────────────────────────────────
Route::get('/admin/proposals',               [AdminMovieProposalController::class, 'index'])->name('admin.proposals.index');
Route::get('/admin/proposals/{id}',          [AdminMovieProposalController::class, 'show'])->name('admin.proposals.show')->where('id', '[0-9]+');
Route::post('/admin/proposals/{id}/approve', [AdminMovieProposalController::class, 'approve'])->name('admin.proposals.approve')->where('id', '[0-9]+');
Route::post('/admin/proposals/{id}/reject',  [AdminMovieProposalController::class, 'reject'])->name('admin.proposals.reject')->where('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| Branch Manager Routes
|--------------------------------------------------------------------------
*/

Route::get('/manager/login',   [BranchManagerAuthController::class, 'showLogin'])->name('manager.login');
Route::post('/manager/login',  [BranchManagerAuthController::class, 'login'])->name('manager.login.post');
Route::post('/manager/logout', [BranchManagerAuthController::class, 'logout'])->name('manager.logout');

Route::get('/manager/home',           [BranchManagerDashboardController::class, 'home'])->name('manager.home');
Route::get('/manager/cinema/profile', [BranchManagerDashboardController::class, 'cinemaProfile'])->name('manager.cinema.profile');

Route::get('/manager/resources', [BranchManagerResourceController::class, 'index'])->name('manager.resources');
Route::get('/manager/upcoming',  [BranchManagerUpcomingController::class, 'index'])->name('manager.upcoming');

// Setup timetable — dual entry
Route::get('/manager/setup/movie/{movieId}',     [BranchManagerShowtimeController::class, 'fromMovie'])
    ->name('manager.setup.movie')->where('movieId', '[0-9]+');
Route::get('/manager/setup/theatre/{theatreId}', [BranchManagerShowtimeController::class, 'fromTheatre'])
    ->name('manager.setup.theatre')->where('theatreId', '[0-9]+');
Route::post('/manager/showtimes', [BranchManagerShowtimeController::class, 'store'])
    ->name('manager.showtimes.store');

// Movie formation page (BM version)
Route::get('/manager/movie/{movieId}', [BranchManagerMovieFormationController::class, 'show'])
    ->name('manager.movie.formation')->where('movieId', '[0-9]+');