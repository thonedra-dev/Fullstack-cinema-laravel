<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Showtime;

class AdminCinemaViewController extends Controller
{
    /**
     * Display all cinemas. Movies section only shows movies with at least
     * one approved showtime (i.e. entries in the showtimes table for this
     * cinema's theatres). Pending proposals do NOT appear as movie cards.
     *
     * GET /admin/cinema
     */
    public function index()
    {
        $cinemas = Cinema::with([
            'city',
            'halls.theatre',
            'theatres',
            'movies.genres',
        ])
        ->orderBy('cinema_id', 'desc')
        ->get();

        // For each cinema, filter its movies collection to only those
        // that have at least one showtime in this cinema's halls.
        $cinemas->each(function ($cinema) {
            $hallIds = $cinema->halls->pluck('hall_id');

            $activeMovieIds = Showtime::whereIn('hall_id', $hallIds)
                ->distinct()
                ->pluck('movie_id');

            // Replace the relation with the filtered subset — no extra query
            $cinema->setRelation(
                'movies',
                $cinema->movies->whereIn('movie_id', $activeMovieIds->toArray())->values()
            );
        });

        return view('admin.view_cinema', compact('cinemas'));
    }
}
