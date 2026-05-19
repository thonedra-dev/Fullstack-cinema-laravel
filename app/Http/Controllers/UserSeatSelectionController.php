<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class UserSeatSelectionController extends Controller
{
    public function index(Request $request)
    {
        $movieId     = (int) $request->query('movie_id',    0);
        $cinemaId    = (int) $request->query('cinema_id',   0);
        $hallId      = (int) $request->query('hall_id',     0);
        $showtimeId  = (int) $request->query('showtime_id', 0);
        $theatreName = $request->query('theatre_name',      'DELUXE');
        $date        = $request->query('date',              '');
        $time        = $request->query('time',              '');

        $movie  = Movie::find($movieId);
        $cinema = DB::table('cinemas')->where('cinema_id', $cinemaId)->first();

        // ── Resolve Hall / Theatre ────────────────────────────
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

        // ── Fallback: find showtime_id from hall + movie + date ─
        if (!$showtimeId && $hallId && $movieId && $date) {
            $showtimeRow = DB::table('showtimes')
                ->where('hall_id',  $hallId)
                ->where('movie_id', $movieId)
                ->whereDate('start_time', $date)
                ->first();
            $showtimeId = $showtimeRow?->showtime_id ?? 0;
        }

        // ── Opportunistic cleanup: delete expired pending bookings ──
        // Runs on every page load — keeps the table clean without a scheduler
        $expiredIds = DB::table('bookings')
            ->where('booking_status', 'pending')
            ->where('expires_at', '<=', now())
            ->pluck('booking_id');

        if ($expiredIds->isNotEmpty()) {
            DB::table('tickets')->whereIn('booking_id', $expiredIds)->delete();
            DB::table('bookings')->whereIn('booking_id', $expiredIds)->delete();
        }

        // ── Build seat status map for this showtime ──────────
        // confirmed  → sold    (hard block)
        // pending + expires_at > now → pending (soft block, orange)
        $soldSeatIds    = [];
        $pendingSeatIds = [];

        if ($showtimeId) {
            $occupied = DB::table('tickets as t')
                ->join('bookings as b', 't.booking_id', '=', 'b.booking_id')
                ->where('t.showtime_id', $showtimeId)
                ->whereIn('b.booking_status', ['confirmed', 'pending'])
                ->select('t.seat_id', 'b.booking_status', 'b.expires_at')
                ->get();

            foreach ($occupied as $row) {
                if ($row->booking_status === 'confirmed') {
                    $soldSeatIds[$row->seat_id] = true;
                } elseif ($row->booking_status === 'pending'
                    && $row->expires_at
                    && now()->lt($row->expires_at)
                ) {
                    $pendingSeatIds[$row->seat_id] = true;
                }
            }
        }

        // ── Fetch seats and assign status ─────────────────────
        $seatRows = collect();

        if ($theatreId) {
            $seats = Seat::where('theatre_id', $theatreId)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get()
                ->map(function ($seat) use ($soldSeatIds, $pendingSeatIds) {
                    if (isset($soldSeatIds[$seat->seat_id])) {
                        $seat->status = 'sold';
                    } elseif (isset($pendingSeatIds[$seat->seat_id])) {
                        $seat->status = 'pending';
                    } else {
                        $seat->status = 'available';
                    }
                    return $seat;
                });

            $seatRows = $seats->groupBy('row_label');
        }

        // Does this showtime have any pending seats? (for the info banner)
        $hasPendingSeats = !empty($pendingSeatIds);

        return view('users.select_seats', compact(
            'movie', 'cinema', 'hall', 'hallId',
            'theatreName', 'theatreId', 'showtimeId',
            'date', 'time', 'seatRows', 'hasPendingSeats'
        ));
    }
}