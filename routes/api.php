<?php
/**
 * routes/api.php  — snippet to add
 *
 * Register the lightweight expiry-poll endpoint.
 * This sits under the /api prefix that Laravel applies to all api.php routes,
 * so the full URL is:  GET /api/live-movie-ids
 *
 * No auth middleware is applied — the homepage itself is public, so this
 * companion endpoint should be too.  If you later add rate-limiting via
 * Laravel's throttle middleware you can wrap it in a group:
 *
 *   Route::middleware('throttle:60,1')->group(function () {
 *       Route::get('/live-movie-ids', [UserHomepageController::class, 'getLiveMovieIds'])
 *            ->name('api.live-movie-ids');
 *   });
 */

use App\Http\Controllers\UserHomepageController;
use Illuminate\Support\Facades\Route;

// Add this line inside your existing routes/api.php file:
Route::get('/live-movie-ids', [UserHomepageController::class, 'getLiveMovieIds'])
    ->name('api.live-movie-ids');