<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;

class BranchManagerMovieDetailsController extends Controller
{
    /**
     * Show the TGV-style movie formation page for the branch manager.
     * GET /manager/movie/{movieId}
     *
     * Passes to view:
     *   $movie              – Movie with ->genres
     *   $cinema             – Cinema with ->city
     *   $hasApprovedShowtimes – bool
     *   $dateGroups         – array of date-grouped showtime data for JS
     *                         [
     *                           'date'        => 'YYYY-MM-DD',
     *                           'label_day'   => 'Mon' | 'Today',
     *                           'label_num'   => '6',
     *                           'label_month' => 'Apr',
     *                           'theatres'    => [
     *                             ['name' => 'DELUXE', 'times' => ['07:00 PM', '09:30 PM']],
     *                             ...
     *                           ]
     *                         ]
     */
    public function show(int $movieId)
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId = (int) session('bm_cinema_id');

        $movie  = Movie::with('genres')->findOrFail($movieId);
        $cinema = Cinema::with('city')->findOrFail($cinemaId);

        // Hall map for this cinema, keyed by hall_id.
        $hallMap = Hall::with('theatre')
            ->where('cinema_id', $cinemaId)
            ->get()
            ->keyBy('hall_id');

        $hallIds = $hallMap->keys()->toArray();

        // All approved showtimes for this movie in this cinema
        $allShowtimes = Showtime::whereIn('hall_id', $hallIds)
            ->where('movie_id', $movieId)
            ->orderBy('start_time')
            ->get();

        $hasApprovedShowtimes = $allShowtimes->isNotEmpty();

        // Build date-grouped structure for JS rendering
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
                                'hall_id' => (int) $hallId,
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

        return view('branch_manager.bm_movie_details', compact(
            'movie',
            'cinema',
            'hasApprovedShowtimes',
            'dateGroups'
        ));
    }
}
