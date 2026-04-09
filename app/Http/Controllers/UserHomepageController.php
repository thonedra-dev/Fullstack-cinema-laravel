<?php
// app/Http/Controllers/UserHomepageController.php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Support\Facades\DB;

class UserHomepageController extends Controller
{
    /**
     * Show the public user homepage.
     *
     * Only movies that exist in the showtimes table are allowed
     * to appear on the homepage.
     */
    public function index()
    {
        $showtimeMovieIds = DB::table('showtimes')
            ->select('movie_id')
            ->distinct();

        $heroMovies = Movie::with(['genres', 'trailers'])
            ->whereIn('movie_id', $showtimeMovieIds)
            ->whereNotNull('landscape_poster')
            ->where('landscape_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Movie $movie) {
                $movie->trailer_embed_url = $movie->trailers->first()?->embed_url;
                return $movie;
            });

        $nowShowing = Movie::with('genres')
            ->whereIn('movie_id', DB::table('showtimes')->select('movie_id')->distinct())
            ->whereNotNull('portrait_poster')
            ->where('portrait_poster', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.homepage', compact('heroMovies', 'nowShowing'));
    }
}