<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StaffSeatMonitoringController extends Controller
{
    public function index(Request $request)
    {
        // ── 1. Parse incoming query parameters ───────────────────────────
        $movieId    = (int) $request->query('movie_id', 0);
        $cinemaId   = (int) $request->query('cinema_id', 0);
        $hallId     = (int) $request->query('hall_id', 0);
        $showtimeId = (int) $request->query('showtime_id', 0);

        // ── 2. Fetch basic entities & handle missing query params ───────
        $movie    = Movie::find($movieId);
        $showtime = DB::table('showtimes')->where('showtime_id', $showtimeId)->first();

        // Resolve Hall / Theatre context
        $hall = null;
        if ($hallId > 0) {
            $hall = DB::table('halls as h')
                ->join('theatres as t', 'h.theatre_id', '=', 't.theatre_id')
                ->where('h.hall_id', $hallId)
                ->select('h.hall_id', 'h.theatre_id', 'h.cinema_id', 't.theatre_name')
                ->first();
        }

        // Fallback: If cinema_id wasn't in URL parameters, grab it from the hall configuration
        if ($cinemaId === 0 && $hall) {
            $cinemaId = $hall->cinema_id;
        }

        $cinema = DB::table('cinemas')->where('cinema_id', $cinemaId)->first();

        // ── 3. Extract strings required by Blade headers ─────────────────
        $cinemaName  = $cinema?->cinema_name ?? 'Unknown Cinema';
        $theatreName = $hall?->theatre_name ?? 'Unknown Theatre';
        $theatreId   = $hall?->theatre_id;

        // Safely parse date and time from the showtime row timestamp
        $date = $showtime?->start_time ? date('Y-m-d', strtotime($showtime->start_time)) : 'N/A';
        $time = $showtime?->start_time ? date('h:i A', strtotime($showtime->start_time)) : 'N/A';

        // ── 4. Build seat status mapping array ───────────────────────────
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
                } elseif (
                    $row->booking_status === 'pending' &&
                    $row->expires_at &&
                    now()->lt($row->expires_at)
                ) {
                    $pendingSeatIds[$row->seat_id] = true;
                }
            }
        }

        // ── 5. Map seating layout grids and compile live metrics ─────────
        $seatRows       = collect();
        $totalCount     = 0;
        $soldCount      = 0;
        $pendingCount   = 0;
        $availableCount = 0;

        if ($theatreId) {
            $seats = Seat::where('theatre_id', $theatreId)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get()
                ->map(function ($seat) use ($soldSeatIds, $pendingSeatIds, &$soldCount, &$pendingCount, &$availableCount) {
                    // Evaluate and increment dashboard tally targets
                    if (isset($soldSeatIds[$seat->seat_id])) {
                        $seat->status = 'sold';
                        $soldCount++;
                    } elseif (isset($pendingSeatIds[$seat->seat_id])) {
                        $seat->status = 'pending';
                        $pendingCount++;
                    } else {
                        $seat->status = 'available';
                        $availableCount++;
                    }
                    return $seat;
                });

            $totalCount = $seats->count();
            $seatRows   = $seats->groupBy('row_label');
        }

        $hasPendingSeats = !empty($pendingSeatIds);


// ── 5. Resolve Staff Security Role Context ───────────────────────────
$role = null;

if (Auth::guard('supervisor')->check()) {
    $role = 'supervisor';
} elseif (Auth::guard('manager')->check()) { // ✨ FIXED: Check native manager guard instead of raw session
    $role = 'manager';
}

        // ── 6. Ship everything smoothly to the view template ───────────
        return view('staff.view_seats', compact(
            'role',
            'movie',
            'cinema',
            'cinemaName',
            'date',
            'time',
            'showtime',
            'hall',
            'hallId',
            'theatreId',
            'theatreName',
            'showtimeId',
            'seatRows',
            'hasPendingSeats',
            'totalCount',
            'soldCount',
            'pendingCount',
            'availableCount'
        ));
    }
}