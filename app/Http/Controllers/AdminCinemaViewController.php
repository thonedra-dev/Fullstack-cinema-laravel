<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\Theatre;

class AdminCinemaViewController extends Controller
{
    /**
     * Display all cinemas. Movies section only shows movies with at least
     * one approved showtime. Pending proposals do NOT appear as movie cards.
     *
     * Also passes all master theatre types so the "Assign Theatre" modal
     * in view_cinema.blade.php can filter out already-assigned ones per cinema.
     *
     * GET /admin/cinema
     */
    public function index()
    {
        $cinemas = Cinema::with([
            'city',
            'halls.theatre',
            'theatres',          // already-assigned theatre types (via halls pivot)
            'movies.genres',
        ])
        ->orderBy('cinema_id', 'desc')
        ->get();

        // Filter each cinema's movies to only those with at least one approved showtime
        $cinemas->each(function ($cinema) {
            $hallIds = $cinema->halls->pluck('hall_id');

            $activeMovieIds = Showtime::whereIn('hall_id', $hallIds)
                ->distinct()
                ->pluck('movie_id');

            $cinema->setRelation(
                'movies',
                $cinema->movies->whereIn('movie_id', $activeMovieIds->toArray())->values()
            );
        });

        // All master theatre types — used in the "Assign Theatre" modal.
        // The blade filters out already-assigned ones per cinema.
        $allTheatres = Theatre::orderBy('theatre_name')->get();

        return view('admin.view_cinema', compact('cinemas', 'allTheatres'));
    }
}