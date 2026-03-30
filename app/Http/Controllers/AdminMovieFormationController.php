<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Theatre;

class AdminMovieFormationController extends Controller
{
    /**
     * Show the movie detail in context of a cinema.
     * Now fetches theatres that have approved showtimes for this movie.
     *
     * GET /admin/movie/{movieId}/cinema/{cinemaId}
     */
    public function show(int $movieId, int $cinemaId)
    {
        $movie  = Movie::with('genres')->findOrFail($movieId);
        $cinema = Cinema::with('city')->findOrFail($cinemaId);

        // Theatres belonging to this cinema that have showtimes for this movie
        $theatres = Theatre::where('cinema_id', $cinemaId)
            ->whereHas('showtimes', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            })
            ->get();

        // For each theatre, eager-load only its showtimes for this movie,
        // ordered chronologically
        $theatresWithShowtimes = $theatres->map(function ($theatre) use ($movieId) {
            $showtimes = Showtime::where('theatre_id', $theatre->theatre_id)
                ->where('movie_id', $movieId)
                ->orderBy('start_time')
                ->get();

            $theatre->setRelation('movieShowtimes', $showtimes);
            return $theatre;
        });

        // If no theatres have showtimes yet, this means not yet approved
        $hasApprovedShowtimes = $theatresWithShowtimes->isNotEmpty();

        return view('admin.movie_cinema_formation', compact(
            'movie',
            'cinema',
            'theatresWithShowtimes',
            'hasApprovedShowtimes'
        ));
    }
}