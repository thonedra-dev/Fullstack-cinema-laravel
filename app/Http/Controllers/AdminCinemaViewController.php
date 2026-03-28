<?php

namespace App\Http\Controllers;

use App\Models\Cinema;

class AdminCinemaViewController extends Controller
{
    /**
     * Display all cinemas with their theatres and assigned movies.
     *
     * GET /admin/cinema
     *
     * Eager-loads:
     *   city               — for state/city display
     *   theatres           — for the theatres panel
     *   movies.genres      — for the movie cards panel (portrait_poster + genres)
     */
    public function index()
    {
        $cinemas = Cinema::with([
            'city',
            'theatres',
            'movies.genres',   // movies through cinema_movie_quotas, each with genres
        ])
        ->orderBy('cinema_id', 'desc')
        ->get();

        return view('admin.view_cinema', compact('cinemas'));
    }
}