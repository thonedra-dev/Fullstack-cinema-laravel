<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Theatre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchManagerTheatreFormationController extends Controller
{
    /**
     * Show the theatre formation page.
     * GET /manager/theatre/{theatreId}
     *
     * Passes to view:
     *   $cinema      – Cinema model
     *   $theatre     – Theatre model
     *   $seatRows    – Seats grouped by row_label (ordered row_label, seat_number)
     *   $seatStats   – [ 'total', 'rows', 'standard', 'couple', 'premium', 'family' ]
     *   $allMovies   – JSON: all unique movies ever showing in this theatre
     *                  [{movie_id, movie_name, runtime, language, production_name,
     *                    landscape_poster, portrait_poster}]
     *   $showtimesJson – JSON: all showtimes for this theatre, for JS date+time filtering
     *                  [{movie_id, date:'YYYY-MM-DD', start_time:'HH:MM:SS', end_time:'HH:MM:SS'}]
     */
    public function show(int $theatreId)
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = (int) session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);
        $theatre  = Theatre::where('theatre_id', $theatreId)
                           ->where('cinema_id', $cinemaId)
                           ->firstOrFail();

        // ── Seats grouped by row_label ─────────────────────────
        $seatRows = Seat::where('theatre_id', $theatreId)
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_label');

        // ── Seat stats for the header chips ───────────────────
        $allSeats = $seatRows->flatten();
        $seatStats = [
            'total'    => $allSeats->count(),
            'rows'     => $seatRows->count(),
            'standard' => $allSeats->where('seat_type', 'Standard')->count(),
            'couple'   => $allSeats->where('seat_type', 'Couple')->count(),
            'premium'  => $allSeats->where('seat_type', 'Premium')->count(),
            'family'   => $allSeats->where('seat_type', 'Family')->count(),
        ];

        // ── All showtimes for this theatre ─────────────────────
        $showtimes = Showtime::where('theatre_id', $theatreId)
            ->orderBy('start_time')
            ->get(['movie_id', 'start_time', 'end_time']);

        // ── Unique movies appearing in this theatre ────────────
        $movieIds = $showtimes->pluck('movie_id')->unique()->values();
        $moviesMap = Movie::whereIn('movie_id', $movieIds)->get()->keyBy('movie_id');

        $allMovies = $movieIds->map(function ($mid) use ($moviesMap) {
            $m = $moviesMap->get($mid);
            if (!$m) return null;
            return [
                'movie_id'        => $m->movie_id,
                'movie_name'      => $m->movie_name,
                'runtime'         => $m->runtime,
                'language'        => $m->language,
                'production_name' => $m->production_name,
                'landscape_poster'=> $m->landscape_poster,
                'portrait_poster' => $m->portrait_poster,
            ];
        })->filter()->values();

        // ── Compact showtime data for JS (date + time) ─────────
        $showtimesJson = $showtimes->map(function ($st) {
            return [
                'movie_id'  => $st->movie_id,
                'date'      => $st->start_time->format('Y-m-d'),
                'start'     => $st->start_time->format('H:i:s'),
                'start_fmt' => $st->start_time->format('h:i A'),
                'end'       => $st->end_time->format('H:i:s'),
                'end_fmt'   => $st->end_time->format('h:i A'),
            ];
        })->values();

        return view('branch_manager.bm_theatre_formation', [
            'cinema'        => $cinema,
            'theatre'       => $theatre,
            'seatRows'      => $seatRows,
            'seatStats'     => $seatStats,
            'allMovies'     => json_encode($allMovies, JSON_UNESCAPED_UNICODE),
            'showtimesJson' => json_encode($showtimesJson, JSON_UNESCAPED_UNICODE),
        ]);
    }
}