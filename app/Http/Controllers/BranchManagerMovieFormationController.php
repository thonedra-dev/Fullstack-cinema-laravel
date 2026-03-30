<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Theatre;
use Illuminate\Support\Facades\DB;

class BranchManagerMovieFormationController extends Controller
{
    /**
     * Show the movie formation page for a branch manager.
     * Same data logic as admin version, different Blade template.
     * GET /manager/movie/{movieId}
     */
    public function show(int $movieId)
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = session('bm_cinema_id');

        $movie  = Movie::with('genres')->findOrFail($movieId);
        $cinema = Cinema::with('city')->findOrFail($cinemaId);


        // Theatres for this cinema with approved showtimes for this movie
        $theatres = Theatre::where('cinema_id', $cinemaId)
            ->whereHas('showtimes', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            })
            ->get();

        $theatresWithShowtimes = $theatres->map(function ($theatre) use ($movieId) {
            $showtimes = Showtime::where('theatre_id', $theatre->theatre_id)
                ->where('movie_id', $movieId)
                ->orderBy('start_time')
                ->get();
            $theatre->setRelation('movieShowtimes', $showtimes);
            return $theatre;
        });

        $hasApprovedShowtimes = $theatresWithShowtimes->isNotEmpty();

        return view('branch_manager.bm_movie_formation', compact(
            'movie',
            'cinema',
            'theatresWithShowtimes',
            'hasApprovedShowtimes'
        ));
    }
}