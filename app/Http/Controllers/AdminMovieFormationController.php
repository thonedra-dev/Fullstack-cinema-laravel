<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;

class AdminMovieFormationController extends Controller
{
    /**
     * Show the TGV-style movie detail in context of a cinema for Admin.
     * GET /admin/movie/{movieId}/cinema/{cinemaId}
     */
    public function show(int $movieId, int $cinemaId)
    {
        $movie  = Movie::with('genres')->findOrFail($movieId);
        $cinema = Cinema::with('city')->findOrFail($cinemaId);

        // Hall map for this specific cinema, keyed by hall_id.
        $hallMap = Hall::with('theatre')
            ->where('cinema_id', $cinemaId)
            ->get()
            ->keyBy('hall_id');

        $hallIds = $hallMap->keys()->toArray();

        // All approved showtimes for this movie in this specific cinema
        $allShowtimes = Showtime::whereIn('hall_id', $hallIds)
            ->where('movie_id', $movieId)
            ->orderBy('start_time')
            ->get();

        $hasApprovedShowtimes = $allShowtimes->isNotEmpty();

        // Build date-grouped structure exactly like the Branch Manager version for the JS engine
        $dateGroups = $allShowtimes
            ->groupBy(fn($st) => $st->start_time->format('Y-m-d'))
            ->map(function ($dayShowtimes, $dateStr) use ($hallMap) {
                $dt = Carbon::parse($dateStr);

                return [
                    'date'        => $dateStr,
                    'label_day'   => $dt->isToday() ? 'Today' : $dt->format('D'),
                    'label_num'   => $dt->format('j'),
                    'label_month' => $dt->format('M'),
                    'theatres'    => $dayShowtimes
                        ->groupBy('hall_id')
                        ->map(function ($tShowtimes, $hallId) use ($hallMap) {
                            $hall = $hallMap->get((int) $hallId);
                            return [
                                'name'  => $hall?->theatre?->theatre_name ?? ('Hall ' . $hallId),
                                'times' => $tShowtimes
                                    ->sortBy('start_time')
                                    ->map(fn($s) => $s->start_time->format('h:i A'))
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return view('admin.movie_cinema_formation', compact(
            'movie',
            'cinema',
            'hasApprovedShowtimes',
            'dateGroups'
        ));
    }
}
