<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class UserSeatSelectionController extends Controller
{
    /**
     * Show the seat selection page.
     * GET /seats?movie_id=&cinema_id=&hall_id=&showtime_id=&theatre_name=&date=&time=
     */
    public function index(Request $request)
    {
        $movieId     = (int) $request->query('movie_id',     0);
        $cinemaId    = (int) $request->query('cinema_id',    0);
        $hallId      = (int) $request->query('hall_id',      0);
        $showtimeId  = (int) $request->query('showtime_id',  0);
        $theatreName = $request->query('theatre_name',       'DELUXE');
        $date        = $request->query('date',               '');
        $time        = $request->query('time',               '');

        // ── Fetch Movie ───────────────────────────────────────
        $movie = Movie::find($movieId);

        // ── Fetch Cinema ──────────────────────────────────────
        $cinema = DB::table('cinemas')->where('cinema_id', $cinemaId)->first();

        // ── Resolve Theatre ───────────────────────────────────
        $hall = null;

        if ($hallId > 0) {
            $hall = DB::table('halls as h')
                ->join('theatres as t', 'h.theatre_id', '=', 't.theatre_id')
                ->where('h.hall_id', $hallId)
                ->where('h.cinema_id', $cinemaId)
                ->select('h.hall_id', 'h.theatre_id', 't.theatre_name')
                ->first();
        }

        if (!$hall) {
            $hall = DB::table('halls as h')
                ->join('theatres as t', 'h.theatre_id', '=', 't.theatre_id')
                ->where('h.cinema_id', $cinemaId)
                ->whereRaw('LOWER(t.theatre_name) = ?', [strtolower($theatreName)])
                ->select('h.hall_id', 'h.theatre_id', 't.theatre_name')
                ->first();
        }

        if (!$hall) {
            $hall = DB::table('halls as h')
                ->join('theatres as t', 'h.theatre_id', '=', 't.theatre_id')
                ->where('h.cinema_id', $cinemaId)
                ->whereRaw('LOWER(t.theatre_name) LIKE ?', ['%' . strtolower($theatreName) . '%'])
                ->select('h.hall_id', 'h.theatre_id', 't.theatre_name')
                ->first();
        }

        $hallId      = $hall?->hall_id      ?? null;
        $theatreId   = $hall?->theatre_id   ?? null;
        $theatreName = $hall?->theatre_name ?? $theatreName;

        // ── Find showtime if not supplied directly ─────────────
        if (!$showtimeId && $hallId && $movieId && $date && $time) {
            $showtimeRow = DB::table('showtimes')
                ->where('hall_id',  $hallId)
                ->where('movie_id', $movieId)
                ->whereDate('start_time', $date)
                ->first();
            $showtimeId = $showtimeRow?->showtime_id ?? 0;
        }

        // ── Sold seat_ids for this showtime ──────────────────
        $soldSeatIds = collect();
        if ($showtimeId) {
            $soldSeatIds = DB::table('tickets as t')
                ->join('bookings as b', 't.booking_id', '=', 'b.booking_id')
                ->where('t.showtime_id', $showtimeId)
                ->whereIn('b.booking_status', ['confirmed', 'pending'])
                ->pluck('t.seat_id')
                ->flip(); // flip for O(1) lookup
        }

        // ── Fetch Real Seats ──────────────────────────────────
        $seatRows = collect();

        if ($theatreId) {
            $seats = Seat::where('theatre_id', $theatreId)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get()
                ->map(function ($seat) use ($soldSeatIds) {
                    $seat->status = $soldSeatIds->has($seat->seat_id)
                        ? 'sold'
                        : 'available';
                    return $seat;
                });

            $seatRows = $seats->groupBy('row_label');
        }

        return view('users.select_seats', compact(
            'movie',
            'cinema',
            'hall',
            'hallId',
            'theatreName',
            'theatreId',
            'showtimeId',
            'date',
            'time',
            'seatRows'
        ));
    }
}