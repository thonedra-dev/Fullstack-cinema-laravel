<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\MovieTicketPrice;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /* ══════════════════════════════════════════════════════════
       POST /booking/cart
    ══════════════════════════════════════════════════════════ */
    public function store(Request $request)
    {
        if (! auth('customer')->check()) {
            return redirect()->route('users.login');
        }

        $request->validate([
            'movie_id'           => 'required|integer|min:1',
            'cinema_id'          => 'required|integer|min:1',
            'hall_id'            => 'required|integer|min:1',
            'showtime_id'        => 'required|integer|min:1',
            'seat_ids'           => 'required|array|min:1|max:10',
            'seat_ids.*'         => 'integer|min:1',
            'seat_selection_url' => 'nullable|string|max:2000',
        ]);

        $movieId    = (int) $request->movie_id;
        $cinemaId   = (int) $request->cinema_id;
        $hallId     = (int) $request->hall_id;
        $showtimeId = (int) $request->showtime_id;
        $seatIds    = array_map('intval', $request->seat_ids);

        /* ── Seats still free? ──────────────────────────────── */
        $takenCount = DB::table('tickets as t')
            ->join('bookings as b', 't.booking_id', '=', 'b.booking_id')
            ->where('t.showtime_id', $showtimeId)
            ->whereIn('t.seat_id', $seatIds)
            ->where(function ($q) {
                $q->where('b.booking_status', 'confirmed')
                  ->orWhere(function ($q2) {
                      $q2->where('b.booking_status', 'pending')
                         ->where('b.expires_at', '>', now());
                  });
            })
            ->count();

        if ($takenCount > 0) {
            return back()->withErrors([
                'seats' => 'One or more selected seats were just taken. Please re-select.',
            ]);
        }

        /* ── Resolve theatre + day type ─────────────────────── */
        $hall     = Hall::find($hallId);
        $showtime = Showtime::find($showtimeId);

        if (! $hall || ! $showtime) {
            return back()->withErrors(['general' => 'Invalid showtime or hall.']);
        }

        $theatreId = $hall->theatre_id;
        $startTime = Carbon::parse($showtime->start_time);
        $dayType   = in_array($startTime->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
            ? 'weekend' : 'weekday';

        /* ── Create pending Booking with 5-min expiry ───────── */
        $booking = Booking::create([
            'user_id'                  => auth('customer')->id(),
            'cinema_id'                => $cinemaId,
            'booking_status'           => 'pending',
            'total_amount'             => 0,
            'stripe_payment_intent_id' => null,
            'expires_at'               => now()->addMinutes(5),
        ]);

        $totalAmount = 0.0;

        foreach ($seatIds as $seatId) {
            $seat = Seat::find($seatId);
            if (! $seat) continue;

            $ticketPrice = MovieTicketPrice::where('movie_id', $movieId)
                ->where('theatre_id', $theatreId)
                ->whereRaw('LOWER(seat_type) = ?', [strtolower($seat->seat_type)])
                ->whereRaw('LOWER(day_type)  = ?', [strtolower($dayType)])
                ->first();

            if (! $ticketPrice) {
                Ticket::where('booking_id', $booking->booking_id)->delete();
                $booking->delete();
                return back()->withErrors([
                    'pricing' => 'No price configured for seat type "' . $seat->seat_type
                        . '" (' . $dayType . '). Contact support.',
                ]);
            }

            Ticket::create([
                'booking_id'  => $booking->booking_id,
                'showtime_id' => $showtimeId,
                'seat_id'     => $seatId,
                'price_id'    => $ticketPrice->price_id,
                'price_paid'  => $ticketPrice->price,
            ]);

            $totalAmount += (float) $ticketPrice->price;
        }

        $booking->update(['total_amount' => round($totalAmount, 2)]);

        session([
            'current_booking_id' => $booking->booking_id,
            'seat_selection_url' => $request->input('seat_selection_url', route('home')),
        ]);

        return redirect()->route('booking.fnb');
    }

    /* ══════════════════════════════════════════════════════════
       GET /booking/fnb
    ══════════════════════════════════════════════════════════ */
    public function fnb()
    {
        if (! auth('customer')->check()) {
            return redirect()->route('users.login');
        }

        $bookingId = session('current_booking_id');
        if (! $bookingId) {
            return redirect()->route('home');
        }

        $booking = Booking::with([
            'tickets.seat',
            'tickets.showtime',
            'tickets.movieTicketPrice',
        ])->find($bookingId);

        if (! $booking || (int) $booking->user_id !== (int) auth('customer')->id()) {
            return redirect()->route('home');
        }

        // If already expired, clean up and redirect
        if ($booking->booking_status === 'pending'
            && $booking->expires_at
            && now()->gt($booking->expires_at)
        ) {
            Ticket::where('booking_id', $booking->booking_id)->delete();
            $booking->delete();
            session()->forget(['current_booking_id', 'seat_selection_url']);
            return redirect(session('seat_selection_url', route('home')))
                ->with('error', 'Your session expired. Please re-select your seats.');
        }

        $firstTicket = $booking->tickets->first();
        $showtime    = $firstTicket?->showtime;
        $movie       = $showtime ? Movie::find($showtime->movie_id) : null;
        $hall        = $showtime ? Hall::find($showtime->hall_id) : null;
        $theatre     = $hall
            ? DB::table('theatres')->where('theatre_id', $hall->theatre_id)->first()
            : null;
        $cinema    = DB::table('cinemas')->where('cinema_id', $booking->cinema_id)->first();
        $customer  = auth('customer')->user();
        $backUrl   = session('seat_selection_url', route('home'));
        $expiresAt = $booking->expires_at?->toIso8601String();

        return view('users.fnb', compact(
            'booking', 'movie', 'cinema', 'showtime', 'theatre',
            'customer', 'backUrl', 'expiresAt'
        ));
    }

    /* ══════════════════════════════════════════════════════════
       GET /booking/back-to-seats
    ══════════════════════════════════════════════════════════ */
    public function cancelAndBack()
    {
        $bookingId = session('current_booking_id');

        if ($bookingId) {
            $booking = Booking::find($bookingId);
            if ($booking
                && (int) $booking->user_id === (int) auth('customer')->id()
                && $booking->booking_status === 'pending'
            ) {
                Ticket::where('booking_id', $bookingId)->delete();
                $booking->delete();
            }
        }

        $seatUrl = session('seat_selection_url', route('home'));
        session()->forget(['current_booking_id', 'seat_selection_url']);

        return redirect($seatUrl);
    }
}