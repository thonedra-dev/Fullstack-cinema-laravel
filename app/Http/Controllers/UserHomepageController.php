<?php
// app/Http/Controllers/UserHomepageController.php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UserHomepageController extends Controller
{
    /**
     * Show the public user homepage.
     *
     * ─ heroMovies  : Movies shown in the hero carousel (landscape poster).
     * ─ nowShowing  : Movies shown in the horizontal card row (portrait poster).
     *
     * Both collections are pre-filtered through the nowShowing() scope so
     * expired movies (whose maximum_end_date across ALL assigned cinemas is
     * already in the past) are silently omitted on every fresh page load.
     */
    public function index()
    {
        // Sub-query: movie_ids that actually have at least one showtime row.
        // Reused for both hero and card collections to stay DRY.
        $showtimeMovieIds = DB::table('showtimes')
            ->select('movie_id')
            ->distinct();

        // ── Hero carousel ────────────────────────────────────────────────────
        $heroMovies = Movie::with(['genres', 'trailers'])
            ->nowShowing()                                   // ← scope applied
            ->whereIn('movie_id', $showtimeMovieIds)
            ->whereNotNull('landscape_poster')
            ->where('landscape_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Movie $movie) {
                $movie->trailer_embed_url = $movie->trailers->first()?->embed_url;
                return $movie;
            });

        // ── Horizontal card row ──────────────────────────────────────────────
        $nowShowing = Movie::with('genres')
            ->nowShowing()                                   // ← scope applied
            ->whereIn('movie_id', $showtimeMovieIds)
            ->whereNotNull('portrait_poster')
            ->where('portrait_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.homepage', compact('heroMovies', 'nowShowing'));
    }

    // =========================================================================
    //  API  ──  GET /api/live-movie-ids
    // =========================================================================
    /**
     * Return a lean JSON payload containing only the IDs of movies that are
     * currently active (not yet expired).
     *
     * Designed for frontend polling:
     *   • No model hydration — pluck() hits the DB with a single tiny query.
     *   • No eager-loading — we only need primary keys.
     *   • No auth guard — public endpoint, same as the homepage.
     *
     * Response shape:
     *   { "ids": [1, 4, 7, 12] }
     *
     * The frontend compares every rendered card's data-movie-id attribute
     * against this array and removes cards whose IDs are absent.
     */
    public function getLiveMovieIds(): JsonResponse
    {
        $ids = Movie::nowShowing()
            ->pluck('movie_id')   // SELECT movie_id FROM movies WHERE EXISTS(...)
            ->all();              // Convert Collection → plain PHP array for JSON

        return response()->json(['ids' => $ids]);
    }
}