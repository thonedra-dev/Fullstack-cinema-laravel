<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;

class AdminMovieFormationController extends Controller
{
    /**
     * Display the movie formation (detail) page for a specific movie
     * in the context of a specific cinema.
     *
     * GET /admin/movie/{movieId}/cinema/{cinemaId}
     *
     * Eager-loads:
     *   movie.genres        — all genres for this movie
     *   cinema              — for breadcrumb / context display
     */
    public function show(int $movieId, int $cinemaId)
    {
        $movie  = Movie::with('genres')->findOrFail($movieId);
        $cinema = Cinema::with('city')->findOrFail($cinemaId);

        return view('admin.movie_cinema_formation', compact('movie', 'cinema'));
    }
}