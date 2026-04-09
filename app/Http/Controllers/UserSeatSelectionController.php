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
     * GET /seats?movie_id=&cinema_id=&theatre_name=&date=&time=
     *
     * Finds the theatre by name within the given cinema, then fetches
     * the real seats from the seats table ordered by row_label, seat_number.
     *
     * Passes to view:
     *   $movie        – Movie model (landscape_poster, movie_name, etc.)
     *   $cinema       – cinema row (cinema_id, cinema_name)
     *   $theatreName  – string
     *   $theatreId    – int
     *   $date         – string Y-m-d
     *   $time         – string "07:00 PM"
     *   $seatRows     – collection keyed by row_label, each item is a collection of seat objects
     *                   seat object has: seat_id, seat_number, seat_type, status ('available')
     *                   (status is always 'available' for now — booking table not yet wired)
     */
    public function index(Request $request)
    {
        $movieId     = (int) $request->query('movie_id',     0);
        $cinemaId    = (int) $request->query('cinema_id',    0);
        $theatreName = $request->query('theatre_name',       'DELUXE');
        $date        = $request->query('date',               '');
        $time        = $request->query('time',               '');

        // ── Fetch Movie ───────────────────────────────────────
        $movie = Movie::find($movieId);

        // ── Fetch Cinema ──────────────────────────────────────
        $cinema = DB::table('cinemas')->where('cinema_id', $cinemaId)->first();

        // ── Resolve Theatre by name within this cinema ────────
        $theatre = DB::table('theatres')
            ->where('cinema_id', $cinemaId)
            ->whereRaw('LOWER(theatre_name) = ?', [strtolower($theatreName)])
            ->first();

        // If not found by exact (case-insensitive) match, try partial
        if (!$theatre) {
            $theatre = DB::table('theatres')
                ->where('cinema_id', $cinemaId)
                ->where('theatre_name', 'ILIKE', '%' . $theatreName . '%')
                ->first();
        }

        $theatreId = $theatre?->theatre_id ?? null;

        // ── Fetch Real Seats ──────────────────────────────────
        // Grouped by row_label, ordered by row_label then seat_number
        $seatRows = collect();

        if ($theatreId) {
            $seats = Seat::where('theatre_id', $theatreId)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get()
                ->map(function ($seat) {
                    // Status is always available for now
                    // When a bookings table exists, join here to mark sold seats
                    $seat->status = 'available';
                    return $seat;
                });

            $seatRows = $seats->groupBy('row_label');
        }

        return view('users.select_seats', compact(
            'movie',
            'cinema',
            'theatreName',
            'theatreId',
            'date',
            'time',
            'seatRows'
        ));
    }
}