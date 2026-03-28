<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Theatre;
use Illuminate\Support\Facades\DB;

class BranchManagerResourceController extends Controller
{
    /**
     * Show theatres and ACTIVE movies (those with at least one showtime).
     *
     * GET /manager/resources
     */
    public function index()
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);

        // Theatres for this cinema
        $theatres = Theatre::with('seats')
            ->where('cinema_id', $cinemaId)
            ->orderBy('theatre_name')
            ->get();

        $theatreIds = $theatres->pluck('theatre_id');

        // Movie IDs that have at least one showtime in this cinema's theatres
        $activeMovieIds = Showtime::whereIn('theatre_id', $theatreIds)
            ->distinct()
            ->pluck('movie_id');

        // Active movies = assigned to cinema AND have showtimes
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereIn('movies.movie_id', $activeMovieIds)
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        return view('branch_manager.bm_resources', compact('cinema', 'theatres', 'movies'));
    }
}