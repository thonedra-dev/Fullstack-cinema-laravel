<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BranchManagerResourceController extends Controller
{
    /**
     * Show theatres and ACTIVE movies (those with at least one showtime).
     *
     * GET /manager/resources
     */
    public function index()
    {
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);

        // Halls for this cinema; services and seats still belong to the master theatre type.
        $halls = Hall::with('theatre.seats')
            ->where('cinema_id', $cinemaId)
            ->get();

        $theatres = $halls
            ->map(function ($hall) {
                $theatre = $hall->theatre;

                if ($theatre) {
                    $theatre->setAttribute('hall_id', $hall->hall_id);
                }

                return $theatre;
            })
            ->filter()
            ->sortBy('theatre_name')
            ->values();

        $hallIds = $halls->pluck('hall_id');

        // Movie IDs that have at least one showtime in this cinema's halls.
        $activeMovieIds = Showtime::whereIn('hall_id', $hallIds)
            ->distinct()
            ->pluck('movie_id');

        // Active movies = assigned to cinema AND have showtimes
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereIn('movies.movie_id', $activeMovieIds)
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        return view('branch_manager.bm_resources', compact('cinema', 'halls', 'theatres', 'movies'));
    }
}
