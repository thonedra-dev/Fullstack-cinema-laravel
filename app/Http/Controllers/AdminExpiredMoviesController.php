<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class AdminExpiredMoviesController extends Controller
{
    /**
     * Display expired movies.
     * GET /admin/movies/expired
     */
    public function index(Request $request)
    {
        $today = now()->toDateString();

        $expiredMovies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->join('cinemas as c', 'cmq.cinema_id', '=', 'c.cinema_id')
            ->whereDate('cmq.maximum_end_date', '<', $today)
            ->select(
                'movies.*',
                'cmq.cinema_id',
                'cmq.showtime_slots',
                'cmq.start_date',
                'cmq.maximum_end_date',
                'c.cinema_name'
            )
            ->orderBy('cmq.maximum_end_date', 'desc')
            ->get();

        // 1. If loaded via AJAX tab switch, return partial view only
        if ($request->ajax()) {
            return view('admin.partials.expired_movies_content', compact('expiredMovies'));
        }

        // 2. Direct page load — render main wrapper with active tab set to 'expired'
        $activeTab = 'expired';
        return view('admin.admin_movies', compact('expiredMovies', 'activeTab'));
    }
}