<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Theatre;
use Illuminate\Http\Request;

class BranchManagerResourceController extends Controller
{
    /**
     * Show theatres and movies for the manager's assigned cinema.
     *
     * GET /manager/resources
     *
     * Eager-loads:
     *   theatres.seats       — seat count per theatre
     *   movies.genres        — genres for each movie card
     *   movies pivot columns — start_date, maximum_end_date, showtime_slots
     */
    public function index()
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = session('bm_cinema_id');

        $cinema = Cinema::findOrFail($cinemaId);

        // Theatres filtered by cinema_id
        $theatres = Theatre::with('seats')
            ->where('cinema_id', $cinemaId)
            ->orderBy('theatre_name')
            ->get();

        // Movies assigned to this cinema via cinema_movie_quotas
        // Include pivot data for start/end dates and slots
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas', 'movies.movie_id', '=', 'cinema_movie_quotas.movie_id')
            ->where('cinema_movie_quotas.cinema_id', $cinemaId)
            ->select(
                'movies.*',
                'cinema_movie_quotas.start_date',
                'cinema_movie_quotas.maximum_end_date',
                'cinema_movie_quotas.showtime_slots'
            )
            ->get()
            ->map(function ($movie) {
                // Attach quota data as pivot-like object for Blade access
                $movie->pivot = (object) [
                    'start_date'       => $movie->start_date,
                    'maximum_end_date' => $movie->maximum_end_date,
                    'showtime_slots'   => $movie->showtime_slots,
                ];
                return $movie;
            });

        return view('branch_manager.bm_resources', compact('cinema', 'theatres', 'movies'));
    }
}