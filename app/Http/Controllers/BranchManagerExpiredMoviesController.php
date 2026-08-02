<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use Illuminate\Support\Facades\Auth;

class BranchManagerExpiredMoviesController extends Controller
{
    /**
     * Show EXPIRED movies for this cinema — i.e. movies whose quota
     * maximum_end_date has already passed (the inverse of Movie::scopeNowShowing()).
     *
     * GET /manager/expired-movies
     */
    public function index()
    {
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);

        // Expired = assigned to this cinema via cinema_movie_quotas,
        // but NOT currently "now showing" (maximum_end_date < today).
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereDate('cmq.maximum_end_date', '<', now()->toDateString())
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        return view('branch_manager.bm_expired_movies', compact('cinema', 'movies'));
    }
}