<?php

use Illuminate\Support\Facades\Route;

// Auth Subdirectory Group
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\ManualSignupController;

// Core Admin Controllers
use App\Http\Controllers\AdminAuthController; 
use App\Http\Controllers\AdminCinemaController;
use App\Http\Controllers\AdminCinemaViewController;
use App\Http\Controllers\AdminCityController;
use App\Http\Controllers\AdminManagerController;
use App\Http\Controllers\AdminMovieController;
use App\Http\Controllers\AdminMovieDetailsController;
use App\Http\Controllers\AdminMovieProposalController;
use App\Http\Controllers\AdminServiceController;
use App\Http\Controllers\AdminTheatreController;
use App\Http\Controllers\AdminTheatreResourceController;
use App\Http\Controllers\AdminHallController;
use App\Http\Controllers\FoodDrinkController;
use App\Http\Controllers\AdminMovieLiveController;

// Branch Manager Controllers
use App\Http\Controllers\BranchManagerAuthController;
use App\Http\Controllers\BranchManagerDashboardController;
use App\Http\Controllers\BranchManagerMovieDetailsController;
use App\Http\Controllers\BranchManagerResourceController;
use App\Http\Controllers\BranchManagerShowtimeController;
use App\Http\Controllers\BranchManagerUpcomingController;
use App\Http\Controllers\BranchManagerReviewProposalController;
use App\Http\Controllers\BranchManagerNotificationController;
use App\Http\Controllers\BranchManagerTheatreFormationController;
use App\Http\Controllers\BranchManagerExpiredMoviesController; 

// Staff & Operations
use App\Http\Controllers\EmployeeAuthController;
use App\Http\Controllers\StaffSeatMonitoringController;

// Public Customer & Transaction Portal
use App\Http\Controllers\UserHomepageController;
use App\Http\Controllers\UserMovieDetailsController;
use App\Http\Controllers\UserSeatSelectionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| 1. Admin/Supervisor Authentication (Guest Core)
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->prefix('admin')->name('admin.')->group(function () {
    // Cyberpunk login page specifically for supervisors/admins
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});

/*
|--------------------------------------------------------------------------
| 2. Admin/Supervisor Panel & Protected Operations (Auth Capsule)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:supervisor'])->prefix('admin')->name('admin.')->group(function () {
    
    // Core Admin Panel Destination
    Route::get('/panel', function () { return view('admin.admin_panel'); })->name('panel');
    
    // Explicit Admin Sign-Out Stream
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Cinema Hub Operations
    Route::get('/cinema/create', [AdminCinemaController::class, 'create'])->name('cinema.create');
    Route::post('/cinema',       [AdminCinemaController::class, 'store'])->name('cinema.store');
    Route::get('/cinema',        [AdminCinemaViewController::class, 'index'])->name('cinema.index');

    // Grid Region Expansion
    Route::get('/city/create', [AdminCityController::class, 'create'])->name('city.create');
    Route::post('/city',       [AdminCityController::class, 'store'])->name('city.store');

    // Peripheral Services & Hardware Features
    Route::get('/service/create', [AdminServiceController::class, 'create'])->name('service.create');
    Route::post('/service',       [AdminServiceController::class, 'store'])->name('service.store');

    // Simulated Experience Theatres & Halls
    Route::get('/theatre/create', [AdminTheatreController::class, 'create'])->name('theatre.create');
    Route::post('/theatre',       [AdminTheatreController::class, 'store'])->name('theatre.store');
    Route::get('/theatre/{id}/resources', [AdminTheatreResourceController::class, 'show'])->name('theatre.resources')->where('id', '[0-9]+');
    Route::post('/cinema/{cinemaId}/halls', [AdminHallController::class, 'store'])->name('cinema.halls.store');

    // Cinematic Streams / Data Uploads
    Route::get('/movie/create', [AdminMovieController::class, 'create'])->name('movie.create');
    Route::post('/movie',       [AdminMovieController::class, 'store'])->name('movie.store');
    Route::get('/movie/{movieId}/cinema/{cinemaId}', [AdminMovieDetailsController::class, 'show'])
        ->name('movie.details')
        ->where(['movieId' => '[0-9]+', 'cinemaId' => '[0-9]+']);

    // Node & Operator Configurations (Staff Assignments)
    Route::get('/managers',           [AdminManagerController::class, 'index'])->name('managers.index');
    Route::post('/managers/assign',   [AdminManagerController::class, 'assign'])->name('managers.assign');
    Route::post('/managers/unassign', [AdminManagerController::class, 'unassign'])->name('managers.unassign');

    // Proposal Logging Review Chain
    Route::get('/proposals',               [AdminMovieProposalController::class, 'index'])->name('proposals.index');
    // CHANGED: Removed the 'admin.' prefix here so it inherits correctly from the group.
    Route::get('/movies/now-showing',      [AdminMovieLiveController::class, 'nowShowing'])->name('movies.now_showing');
    Route::get('/proposals/{id}',          [AdminMovieProposalController::class, 'show'])->name('proposals.show')->where('id', '[0-9]+');
    Route::post('/proposals/{id}/approve', [AdminMovieProposalController::class, 'approve'])->name('proposals.approve')->where('id', '[0-9]+');
    Route::post('/proposals/{id}/reject',  [AdminMovieProposalController::class, 'reject'])->name('proposals.reject')->where('id', '[0-9]+');

    // Synthesized Rations Inventory Systems
    Route::get('/food-drink/create', [FoodDrinkController::class, 'create'])->name('food_drink.create');
    Route::post('/food-drink/store', [FoodDrinkController::class, 'store'])->name('food_drink.store');
});

/*
|--------------------------------------------------------------------------
| 3. Branch Manager Network
|--------------------------------------------------------------------------
*/
Route::get('/manager/login',   [BranchManagerAuthController::class, 'showLogin'])->name('manager.login');
Route::post('/manager/login',  [BranchManagerAuthController::class, 'login'])->name('manager.login.post');
Route::post('/manager/logout', [BranchManagerAuthController::class, 'logout'])->name('manager.logout');

Route::get('/manager/home',           [BranchManagerDashboardController::class, 'home'])->name('manager.home');
Route::get('/manager/cinema/profile', [BranchManagerDashboardController::class, 'cinemaProfile'])->name('manager.cinema.profile');
Route::get('/manager/resources',      [BranchManagerResourceController::class, 'index'])->name('manager.resources');
Route::get('/manager/upcoming',       [BranchManagerUpcomingController::class, 'index'])->name('manager.upcoming');
Route::get('/manager/proposal/review/{movieId}', [BranchManagerReviewProposalController::class, 'show'])->name('manager.proposal.review');

Route::get('/manager/setup/movie/{movieId}', [BranchManagerShowtimeController::class, 'fromMovie'])->name('manager.setup.movie')->where('movieId', '[0-9]+');
Route::post('/manager/showtimes',            [BranchManagerShowtimeController::class, 'store'])->name('manager.showtimes.store');
Route::get('/manager/showtimes/by-date',     [BranchManagerShowtimeController::class, 'getShowtimesByDate'])->name('manager.showtimes.by-date');

Route::get('/manager/movie/{movieId}', [BranchManagerMovieDetailsController::class, 'show'])->name('manager.movie.details')->where('movieId', '[0-9]+');
Route::post('/proposals/{movieId}/rearrange', [BranchManagerShowtimeController::class, 'rearrange'])->name('manager.proposals.rearrange');
Route::get('/manager/notifications',          [BranchManagerNotificationController::class, 'index'])->name('manager.notifications');
Route::get('/manager/theatre/{theatreId}',    [BranchManagerTheatreFormationController::class, 'show'])->name('manager.theatre.formation');
Route::get('/manager/expired-movies', [BranchManagerExpiredMoviesController::class, 'index'])
    ->name('manager.expired.movies');
/*
|--------------------------------------------------------------------------
| 4. Separate Field Employee & Staff Operations (Isolated)
|--------------------------------------------------------------------------
*/
Route::get('/employees/login', [EmployeeAuthController::class, 'showLogin'])->name('employees.login');
Route::post('/employees/login', [EmployeeAuthController::class, 'login'])->name('employees.login.post');

Route::get('/staff/view-seats', [StaffSeatMonitoringController::class, 'index'])->name('staff.view_seats')->middleware('staff.role');

/*
|--------------------------------------------------------------------------
| 5. Public Customer Interfaces & Portal
|--------------------------------------------------------------------------
*/
Route::get('/users/homepage', [UserHomepageController::class, 'index'])->name('home');
Route::get('/api/live-movie-ids', [UserHomepageController::class, 'getLiveMovieIds'])->name('api.live-movie-ids');
Route::get('/users/login',    [CustomerLoginController::class, 'showLogin'])->name('users.login');
Route::post('/users/login',   [CustomerLoginController::class, 'login'])->name('users.login.post');
Route::post('/users/logout',  [CustomerLoginController::class, 'logout'])->name('users.logout');

Route::get('/movie/{movieId}', [UserMovieDetailsController::class, 'show'])->name('user.movie.details');
Route::get('/booking/seats',   [UserSeatSelectionController::class, 'index'])->name('user.seats');

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

Route::get('/users/sign-up',          function () { return view('users.signup'); })->name('users.signup');
Route::post('/users/sign-up/start',   [ManualSignupController::class, 'start'])->name('users.signup.start');
Route::post('/users/sign-up/verify',  [ManualSignupController::class, 'verify'])->name('users.signup.verify');
Route::post('/users/sign-up/resend',  [ManualSignupController::class, 'resend'])->name('users.signup.resend');
Route::post('/users/sign-up/complete', [ManualSignupController::class, 'complete'])->name('users.signup.complete');

/*
|--------------------------------------------------------------------------
| 6. Purchase Engine (Customer Security Group Layer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:customer'])->group(function () {
    Route::post('/booking/cart',            [BookingController::class, 'store'])->name('booking.cart');
    Route::get('/booking/fnb',              [BookingController::class, 'fnb'])->name('booking.fnb');
    Route::get('/booking/back-to-seats',    [BookingController::class, 'cancelAndBack'])->name('booking.back-to-seats');
    Route::get('/booking/payment',          [PaymentController::class, 'show'])->name('booking.payment');
    Route::post('/booking/payment/intent',  [PaymentController::class, 'createIntent'])->name('booking.payment.intent');
    Route::post('/booking/payment/confirm', [PaymentController::class, 'confirm'])->name('booking.payment.confirm');
    Route::get('/booking/confirmed/{bookingId}', [PaymentController::class, 'confirmed'])->name('booking.confirmed');
});

Route::post('/booking/stripe/webhook', [PaymentController::class, 'webhook'])->name('booking.stripe.webhook');