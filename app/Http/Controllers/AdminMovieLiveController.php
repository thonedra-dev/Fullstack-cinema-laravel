<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Theatre;
use App\Models\Showtime;
use App\Models\Ticket; // ⚠️ ASSUMED to have: booking_id, showtime_id, seat_id

class AdminMovieLiveController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       NOW SHOWING (tab)  — unchanged
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
       MOVIE LIVE DETAIL PAGE — unchanged
    ───────────────────────────────────────────────────────────── */
    public function show(Movie $movie)
    {
        $movie->load('genres');

        $cinemas = Cinema::whereHas('showtimes', function ($q) use ($movie) {
                $q->where('movie_id', $movie->movie_id)
                  ->where('end_time', '>', now());
            })
            ->orderBy('cinema_name')
            ->get(['cinema_id', 'cinema_name']);

        return view('admin.admin_movie_live_detail', compact('movie', 'cinemas'));
    }

    /* ─────────────────────────────────────────────────────────────
       JSON — theatres under a cinema with a live showtime for this movie
       GET /admin/movies/now-showing/{movie}/cinemas/{cinema}/theatres
    ───────────────────────────────────────────────────────────── */
    public function theatresJson(Movie $movie, Cinema $cinema)
    {
        $theatres = Theatre::whereHas('halls', function ($q) use ($cinema, $movie) {
                $q->where('cinema_id', $cinema->cinema_id)
                  ->whereHas('showtimes', function ($q2) use ($movie) {
                      $q2->where('movie_id', $movie->movie_id)
                         ->where('end_time', '>', now());
                  });
            })
            ->orderBy('theatre_name')
            ->get(['theatre_id', 'theatre_name']);

        return response()->json($theatres);
    }

    /* ─────────────────────────────────────────────────────────────
       NEW — JSON: showtimes under a theatre (+cinema+movie) that
       are still live (end_time > now).
       GET /admin/movies/now-showing/{movie}/cinemas/{cinema}/theatres/{theatre}/showtimes
    ───────────────────────────────────────────────────────────── */
    public function showtimesJson(Movie $movie, Cinema $cinema, Theatre $theatre)
    {
        $showtimes = Showtime::where('movie_id', $movie->movie_id)
            ->where('cinema_id', $cinema->cinema_id)
            ->where('end_time', '>', now())
            ->whereHas('hall', function ($q) use ($theatre) {
                $q->where('theatre_id', $theatre->theatre_id);
            })
            ->orderBy('start_time')
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

        // ⚠️ ASSUMES Ticket has: showtime_id, seat_id, booking_id
        // and Booking has: booking_status ('pending' | 'confirmed' | ...)
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
}