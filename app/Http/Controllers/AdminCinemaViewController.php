<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Showtime;
use App\Models\Theatre;
use Illuminate\Support\Facades\DB;

class AdminCinemaViewController extends Controller
{
    /**
     * Display all cinemas. Movies section only shows movies with at least
     * one approved showtime AND whose quota maximum_end_date has not passed.
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

        $today = now()->toDateString();

        // Filter each cinema's movies to active showtimes AND maximum_end_date >= today
        $cinemas->each(function ($cinema) use ($today) {
            $hallIds = $cinema->halls->pluck('hall_id');

            // 1. Movie IDs that have active showtimes in this cinema's halls
            $showtimeMovieIds = Showtime::whereIn('hall_id', $hallIds)
                ->distinct()
                ->pluck('movie_id');

            // 2. Filter to those whose quota maximum_end_date >= today
            $activeMovieIds = DB::table('cinema_movie_quotas')
                ->where('cinema_id', $cinema->cinema_id)
                ->whereIn('movie_id', $showtimeMovieIds)
                ->whereDate('maximum_end_date', '>=', $today)
                ->pluck('movie_id');

            $cinema->setRelation(
                'movies',
                $cinema->movies->whereIn('movie_id', $activeMovieIds->toArray())->values()
            );
        });

        // All master theatre types — used in the "Assign Theatre" modal.
        $allTheatres = Theatre::orderBy('theatre_name')->get();

        return view('admin.view_cinema', compact('cinemas', 'allTheatres'));
    }
}