<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserMovieDetailsController extends Controller
{
    /**
     * Show the public movie detail page.
     * GET /movie/{movieId}
     *
     * Data source rules:
     * - Cinemas shown on this page must come from finalized showtimes
     * - Cities must come only from those cinemas
     * - No cinema should appear unless it has an actual approved showtime
     */
    public function show(int $movieId)
    {
        $movie = Movie::with('genres')->findOrFail($movieId);

        $showtimeRows = DB::table('showtimes as s')
            ->join('cinemas as c', 's.cinema_id', '=', 'c.cinema_id')
            ->join('cities as ct', 'c.city_id', '=', 'ct.city_id')
            ->join('theatres as t', 's.theatre_id', '=', 't.theatre_id')
            ->where('s.movie_id', $movieId)
            ->select(
                's.cinema_id',
                'c.cinema_name',
                'ct.city_name',
                'ct.city_state',
                's.start_time',
                't.theatre_name'
            )
            ->orderBy('ct.city_state')
            ->orderBy('c.cinema_name')
            ->orderBy('s.start_time')
            ->get();

        $stateGroups = [];

        foreach ($showtimeRows as $row) {
            $state = $row->city_state;
            $cinemaId = $row->cinema_id;
            $dateTime = Carbon::parse($row->start_time);
            $dayKey = $dateTime->format('Y-m-d');
            $timeLabel = $dateTime->format('h:i A');

            if (!isset($stateGroups[$state])) {
                $stateGroups[$state] = [
                    'state' => $state,
                    'cinemas' => [],
                ];
            }

            if (!isset($stateGroups[$state]['cinemas'][$cinemaId])) {
                $stateGroups[$state]['cinemas'][$cinemaId] = [
                    'cinema_id' => $cinemaId,
                    'cinema_name' => $row->cinema_name,
                    'city' => $row->city_name,
                    'dateGroups' => [],
                ];
            }

            if (!isset($stateGroups[$state]['cinemas'][$cinemaId]['dateGroups'][$dayKey])) {
                $stateGroups[$state]['cinemas'][$cinemaId]['dateGroups'][$dayKey] = [
                    'date' => $dayKey,
                    'label_day' => $dateTime->isToday() ? 'Today' : $dateTime->format('D'),
                    'label_num' => $dateTime->format('j'),
                    'label_month' => $dateTime->format('M'),
                    'theatres' => [],
                ];
            }

            $theatres = &$stateGroups[$state]['cinemas'][$cinemaId]['dateGroups'][$dayKey]['theatres'];
            $theatreIndex = null;

            foreach ($theatres as $index => $theatre) {
                if ($theatre['name'] === $row->theatre_name) {
                    $theatreIndex = $index;
                    break;
                }
            }

            if ($theatreIndex === null) {
                $theatres[] = [
                    'name' => $row->theatre_name,
                    'times' => [],
                ];
                $theatreIndex = array_key_last($theatres);
            }

            if (!in_array($timeLabel, $theatres[$theatreIndex]['times'], true)) {
                $theatres[$theatreIndex]['times'][] = $timeLabel;
            }

            unset($theatres);
        }

        $stateGroups = array_map(function (array $stateGroup) {
            $stateGroup['cinemas'] = array_values(array_map(function (array $cinema) {
                $cinema['dateGroups'] = array_values($cinema['dateGroups']);
                return $cinema;
            }, $stateGroup['cinemas']));

            return $stateGroup;
        }, array_values($stateGroups));

        return view('users.movie_details', [
            'movie' => $movie,
            'stateGroups' => json_encode($stateGroups, JSON_UNESCAPED_UNICODE),
        ]);
    }
}