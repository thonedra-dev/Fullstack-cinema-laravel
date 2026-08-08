<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Theatre;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\Booking;
use App\Models\Payment;

class AdminMovieLiveController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       NOW SHOWING (tab)  — unchanged
       "Now showing" is a MOVIE-level concept (hasLiveShowtime scope).
       Everything below this no longer filters by end_time > now(),
       so supervisors can see & report on past showtimes/finances too.
    ───────────────────────────────────────────────────────────── */
    public function nowShowing(Request $request)
    {
        $activeTab = 'now_showing';

        $nowShowingMovies = Movie::with('genres')
            ->hasLiveShowtime()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->ajax()) {
            return view('admin.partials.now_showing_movies', compact('nowShowingMovies'));
        }

        return view('admin.admin_movies', compact('nowShowingMovies', 'activeTab'));
    }

    /* ─────────────────────────────────────────────────────────────
       MOVIE LIVE DETAIL PAGE
       Cinemas list is now ALL cinemas that have ever run this movie,
       not just ones with a currently-live showtime.
    ───────────────────────────────────────────────────────────── */
    public function show(Movie $movie)
    {
        $movie->load('genres');

        $cinemas = Cinema::with('city')
    ->whereHas('showtimes', function ($q) use ($movie) {
        $q->where('movie_id', $movie->movie_id);
    })
    ->orderBy('cinema_name')
    ->get(['cinema_id', 'cinema_name', 'cinema_address', 'cinema_contact', 'cinema_description', 'cinema_picture', 'city_id'])
    ->map(function ($cinema) {
        return (object) [
            'cinema_id'          => $cinema->cinema_id,
            'cinema_name'        => $cinema->cinema_name,
            'cinema_address'     => $cinema->cinema_address,
            'cinema_contact'     => $cinema->cinema_contact,
            'cinema_description' => $cinema->cinema_description,
            'cinema_picture'     => $cinema->cinema_picture,
            // ⚠️ ASSUMED City model exposes `city_name` — rename here if yours differs
            'city_name'          => optional($cinema->city)->city_name,
        ];
    });

        return view('admin.admin_movie_live_detail', compact('movie', 'cinemas'));
    }

    /* ─────────────────────────────────────────────────────────────
       JSON — theatres under a cinema that have EVER run this movie
       GET /admin/movies/now-showing/{movie}/cinemas/{cinema}/theatres
    ───────────────────────────────────────────────────────────── */
    public function theatresJson(Movie $movie, Cinema $cinema)
    {
        $theatres = Theatre::whereHas('halls', function ($q) use ($cinema, $movie) {
                $q->where('cinema_id', $cinema->cinema_id)
                  ->whereHas('showtimes', function ($q2) use ($movie) {
                      $q2->where('movie_id', $movie->movie_id);
                  });
            })
            ->orderBy('theatre_name')
->get(['theatre_id', 'theatre_name', 'theatre_icon', 'theatre_poster'])
->map(function ($theatre) {
    return [
        'theatre_id'     => $theatre->theatre_id,
        'theatre_name'   => $theatre->theatre_name,
        // ⚠️ ASSUMED path — adjust to wherever these files actually live on disk
        'theatre_icon'   => $theatre->theatre_icon ? asset('images/theatres/' . $theatre->theatre_icon) : null,
        'theatre_poster' => $theatre->theatre_poster ? asset('images/theatres/' . $theatre->theatre_poster) : null,
    ];
});

return response()->json($theatres);
    }

    /* ─────────────────────────────────────────────────────────────
       NEW — JSON: distinct dates (no time filter) that have a
       showtime for this movie/cinema/theatre. Feeds the new
       "Dates" sidebar panel.
       GET /admin/movies/now-showing/{movie}/cinemas/{cinema}/theatres/{theatre}/dates
    ───────────────────────────────────────────────────────────── */
    public function datesJson(Movie $movie, Cinema $cinema, Theatre $theatre)
    {
        $dates = Showtime::where('movie_id', $movie->movie_id)
            ->where('cinema_id', $cinema->cinema_id)
            ->whereHas('hall', function ($q) use ($theatre) {
                $q->where('theatre_id', $theatre->theatre_id);
            })
            ->selectRaw('DATE(start_time) as date_key, COUNT(*) as showtime_count')
            ->groupBy('date_key')
            ->orderBy('date_key', 'desc')
            ->get();

        return response()->json($dates);
    }

    /* ─────────────────────────────────────────────────────────────
       JSON — showtimes under a theatre (+cinema+movie), scoped to
       a single calendar date. No end_time filter — past dates stay
       visible for reporting.
       GET /admin/movies/now-showing/{movie}/cinemas/{cinema}/theatres/{theatre}/showtimes?date=YYYY-MM-DD
    ───────────────────────────────────────────────────────────── */
    public function showtimesJson(Request $request, Movie $movie, Cinema $cinema, Theatre $theatre)
    {
        $query = Showtime::where('movie_id', $movie->movie_id)
            ->where('cinema_id', $cinema->cinema_id)
            ->whereHas('hall', function ($q) use ($theatre) {
                $q->where('theatre_id', $theatre->theatre_id);
            });

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->query('date'));
        }

        $showtimes = $query->orderBy('start_time')
            ->get(['showtime_id', 'start_time', 'end_time']);

        return response()->json($showtimes);
    }

    /* ─────────────────────────────────────────────────────────────
       JSON — seat layout for a theatre, ANNOTATED with live booking
       state for one specific showtime.
       GET /admin/showtimes/{showtime}/seats
    ───────────────────────────────────────────────────────────── */
    public function seatsForShowtimeJson(Showtime $showtime)
    {
        $showtime->load('hall.theatre');
        $theatre = $showtime->hall->theatre;

        $seats = $theatre->seats()->get(['seat_id', 'row_label', 'seat_number', 'seat_type']);

        $bookedSeatStatus = Ticket::where('showtime_id', $showtime->showtime_id)
            ->join('bookings', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->whereIn('bookings.booking_status', ['pending', 'confirmed'])
            ->pluck('bookings.booking_status', 'tickets.seat_id');

        $seats = $seats->map(function ($seat) use ($bookedSeatStatus) {
            $status = $bookedSeatStatus->get($seat->seat_id); // null | 'pending' | 'confirmed'
            $seat->booking_state = match ($status) {
                'pending'   => 'held',
                'confirmed' => 'sold',
                default     => 'available',
            };
            return $seat;
        });

        return response()->json([
            'showtime_id'  => $showtime->showtime_id,
            'theatre_id'   => $theatre->theatre_id,
            'theatre_name' => $theatre->theatre_name,
            'start_time'   => $showtime->start_time,
            'end_time'     => $showtime->end_time,
            'seats'        => $seats,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       NEW — JSON: financial report for a showtime. One row per
       ticket, joined out to its seat label, booking, and payment.
       GET /admin/showtimes/{showtime}/financials
    ───────────────────────────────────────────────────────────── */
    public function financialsJson(Showtime $showtime)
    {
        $rows = Ticket::query()
            ->where('tickets.showtime_id', $showtime->showtime_id)
            ->join('bookings', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->join('seats', 'seats.seat_id', '=', 'tickets.seat_id')
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.booking_id')
            ->orderBy('seats.row_label')
            ->orderBy('seats.seat_number')
            ->get([
                'tickets.ticket_id',
                'tickets.price_paid',
                'bookings.booking_id',
                'bookings.booking_status',
                'seats.row_label',
                'seats.seat_number',
                'payments.payment_id',
                'payments.amount_paid',
                'payments.payment_status',
            ]);

        $data = $rows->map(function ($r) {
            return [
                'ticket_id'      => $r->ticket_id,
                'booking_id'     => $r->booking_id,
                'booking_status' => $r->booking_status,
                'seat_label'     => $r->row_label . $r->seat_number,
                'price_paid'     => $r->price_paid,
                'payment_id'     => $r->payment_id,
                'amount_paid'    => $r->amount_paid,
                'payment_status' => $r->payment_status,
            ];
        });

        return response()->json([
            'showtime_id' => $showtime->showtime_id,
            'rows'        => $data,
            'totals'      => [
                'ticket_count'      => $data->count(),
                'total_price_paid'  => $data->sum(fn ($r) => (float) $r['price_paid']),
                // payments are per-booking, so dedupe before summing
                'total_amount_paid' => $rows->unique('booking_id')->sum(fn ($r) => (float) $r->amount_paid),
            ],
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       FINANCE ROLLUP LADDER (all scoped to $movie, confirmed
       bookings only — this is revenue, not "seats currently held").
       L1 cinemas -> L2 theatres -> L3 movie (always 1 row here)
       -> L4 showtimes -> L5 financialsJson() (already exists above).
    ───────────────────────────────────────────────────────────── */

    // L1 — per-cinema totals for this movie
    public function cinemasFinancialsJson(Movie $movie)
    {
        $rows = $this->confirmedTicketRows($movie)
            ->join('cinemas', 'cinemas.cinema_id', '=', 'showtimes.cinema_id')
            ->addSelect('cinemas.cinema_name')
            ->get();

        $paymentTotals = $this->paymentTotalsByBookingIds($rows->pluck('booking_id')->unique());

        $data = $rows->groupBy('cinema_id')->map(function ($group) use ($paymentTotals) {
            $first = $group->first();
            $bookingIds = $group->pluck('booking_id')->unique();
            return [
                'cinema_id'           => $first->cinema_id,
                'cinema_name'         => $first->cinema_name,
                'ticket_count'        => $group->count(),
                'booking_count'       => $bookingIds->count(),
                'total_ticket_revenue'=> (float) $group->sum('price_paid'),
                'total_payments'      => (float) $bookingIds->sum(fn ($id) => $paymentTotals->get($id, 0)),
            ];
        })->values();

        return response()->json($data);
    }

    // L2 — per-theatre totals under one cinema, this movie
    public function theatresFinancialsJson(Movie $movie, Cinema $cinema)
    {
        $rows = $this->confirmedTicketRows($movie, $cinema->cinema_id)
            ->join('theatres', 'theatres.theatre_id', '=', 'halls.theatre_id')
            ->addSelect('theatres.theatre_name')
            ->get();

        $paymentTotals = $this->paymentTotalsByBookingIds($rows->pluck('booking_id')->unique());

        $data = $rows->groupBy('theatre_id')->map(function ($group) use ($paymentTotals) {
            $first = $group->first();
            $bookingIds = $group->pluck('booking_id')->unique();
            return [
                'theatre_id'           => $first->theatre_id,
                'theatre_name'         => $first->theatre_name,
                'ticket_count'         => $group->count(),
                'booking_count'        => $bookingIds->count(),
                'total_ticket_revenue' => (float) $group->sum('price_paid'),
                'total_payments'       => (float) $bookingIds->sum(fn ($id) => $paymentTotals->get($id, 0)),
            ];
        })->values();

        return response()->json($data);
    }

    // L3 — movie row(s) w/ portrait poster, under one cinema+theatre.
    // Always exactly 1 row on this page since it's already movie-scoped,
    // kept list-shaped so the frontend doesn't special-case it.
    public function moviesFinancialsJson(Movie $movie, Cinema $cinema, Theatre $theatre)
    {
        $rows = $this->confirmedTicketRows($movie, $cinema->cinema_id, $theatre->theatre_id)->get();

        $paymentTotals = $this->paymentTotalsByBookingIds($rows->pluck('booking_id')->unique());
        $bookingIds = $rows->pluck('booking_id')->unique();

        return response()->json([[
            'movie_id'              => $movie->movie_id,
            'movie_name'            => $movie->movie_name,
            // ⚠️ ASSUMED path — matches your images/movies/{...}_portrait_....jpg convention
            'portrait_poster'       => $movie->portrait_poster ? asset('images/movies/' . $movie->portrait_poster) : null,
            'ticket_count'          => $rows->count(),
            'booking_count'         => $bookingIds->count(),
            'total_ticket_revenue'  => (float) $rows->sum('price_paid'),
            'total_payments'        => (float) $bookingIds->sum(fn ($id) => $paymentTotals->get($id, 0)),
        ]]);
    }

    // L4 — per-showtime totals under one cinema+theatre(+movie),
    // optionally scoped to a single calendar date.
    public function showtimesFinancialsJson(Request $request, Movie $movie, Cinema $cinema, Theatre $theatre)
    {
        $query = $this->confirmedTicketRows($movie, $cinema->cinema_id, $theatre->theatre_id);

        if ($request->filled('date')) {
            $query->whereDate('showtimes.start_time', $request->query('date'));
        }

        $rows = $query->get();

        $paymentTotals = $this->paymentTotalsByBookingIds($rows->pluck('booking_id')->unique());

        $data = $rows->groupBy('showtime_id')->map(function ($group) use ($paymentTotals) {
            $first = $group->first();
            $bookingIds = $group->pluck('booking_id')->unique();
            return [
                'showtime_id'           => $first->showtime_id,
                'start_time'            => $first->start_time,
                'ticket_count'          => $group->count(),
                'booking_count'         => $bookingIds->count(),
                'total_ticket_revenue'  => (float) $group->sum('price_paid'),
                'total_payments'        => (float) $bookingIds->sum(fn ($id) => $paymentTotals->get($id, 0)),
            ];
        })->values()->sortBy('start_time')->values();

        return response()->json($data);
    }

    /* ── Private helpers for the rollup ladder ───────────────────── */

    // Base query: confirmed-booking ticket rows for $movie, optionally
    // narrowed to a cinema and/or theatre. Callers add their own
    // ->addSelect(...) for the name column they need to group by.
    private function confirmedTicketRows(Movie $movie, ?int $cinemaId = null, ?int $theatreId = null)
    {
        $query = Ticket::query()
            ->join('showtimes', 'showtimes.showtime_id', '=', 'tickets.showtime_id')
            ->join('bookings', 'bookings.booking_id', '=', 'tickets.booking_id')
            ->join('halls', 'halls.hall_id', '=', 'showtimes.hall_id')
            ->where('showtimes.movie_id', $movie->movie_id)
            ->where('bookings.booking_status', 'confirmed');

        if ($cinemaId) {
            $query->where('showtimes.cinema_id', $cinemaId);
        }
        if ($theatreId) {
            $query->where('halls.theatre_id', $theatreId);
        }

        return $query->select(
            'tickets.ticket_id',
            'tickets.price_paid',
            'bookings.booking_id',
            'showtimes.showtime_id',
            'showtimes.cinema_id',
            'showtimes.start_time',
            'halls.theatre_id'
        );
    }

    // Payments are per-booking, not per-ticket — dedupe before summing
    // so a booking with 4 seats doesn't get its payment counted 4x.
    private function paymentTotalsByBookingIds($bookingIds)
    {
        if ($bookingIds->isEmpty()) {
            return collect();
        }

        return Payment::whereIn('booking_id', $bookingIds)
            ->get(['booking_id', 'amount_paid'])
            ->unique('booking_id')
            ->pluck('amount_paid', 'booking_id')
            ->map(fn ($v) => (float) $v);
    }
}