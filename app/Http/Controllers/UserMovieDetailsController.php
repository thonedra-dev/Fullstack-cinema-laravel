<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserMovieDetailsController extends Controller
{
    /**
     * Show the public movie detail page.
     * GET /movie/{movieId}
     *
     * Two distinct query pipelines are built from the showtimes table:
     *
     *  1. $availableDates  – distinct future/today dates for the date-strip.
     *                        Any date whose calendar day has fully elapsed is
     *                        excluded so the UI never shows past-date buttons.
     *
     *  2. $showtimeGroups  – full showtime rows for the selected date, enriched
     *                        with the is_bookable accessor, then structured as:
     *                        state → cinema → theatre → [showtime, …]
     *                        This mirrors the shape the Blade/JS already expects.
     */
    public function show(Request $request, int $movieId)
    {
        $movie = Movie::with('genres')->findOrFail($movieId);

        $today = Carbon::today();

        // ── Pipeline 1: Available date buttons ───────────────────────────────
        // Only include dates where DATE(start_time) >= today (entire day not
        // elapsed).  We use a raw DATE() cast so PostgreSQL can compare cleanly.
        $availableDates = DB::table('showtimes')
            ->where('movie_id', $movieId)
            ->whereRaw('DATE(start_time) >= ?', [$today->toDateString()])
            ->selectRaw('DISTINCT DATE(start_time) as show_date')
            ->orderBy('show_date')
            ->pluck('show_date')   // flat array of date strings: ['2025-06-01', …]
            ->map(fn($d) => Carbon::parse($d));   // Carbon instances for formatting

        // ── Resolve active date from query-string or default to today ─────────
        $requestedDate = $request->query('date');

        if ($requestedDate && $availableDates->contains(fn($d) => $d->toDateString() === $requestedDate)) {
            $activeDate = Carbon::parse($requestedDate);
        } elseif ($availableDates->isNotEmpty()) {
            $activeDate = $availableDates->first();
        } else {
            $activeDate = null;
        }

        // ── Pipeline 2: Showtimes for the active date ─────────────────────────
        // Load full Eloquent models so ->is_bookable accessor is available.
        // Join the denormalised columns needed by the grouping logic below.
        $showtimeRows = [];

        if ($activeDate) {
            $showtimeRows = DB::table('showtimes as s')
                ->join('cinemas as c',  's.cinema_id', '=', 'c.cinema_id')
                ->join('cities as ct',  'c.city_id',   '=', 'ct.city_id')
                ->join('halls as h',    's.hall_id',   '=', 'h.hall_id')
                ->join('theatres as t', 'h.theatre_id','=', 't.theatre_id')
                ->where('s.movie_id', $movieId)
                ->whereRaw('DATE(s.start_time) = ?', [$activeDate->toDateString()])
                ->select(
                    's.showtime_id',
                    's.start_time',
                    's.end_time',
                    'h.hall_id',
                    't.theatre_id',
                    't.theatre_name',
                    's.cinema_id',
                    'c.cinema_name',
                    'ct.city_name',
                    'ct.city_state'
                )
                ->orderBy('ct.city_state')
                ->orderBy('c.cinema_name')
                ->orderBy('s.start_time')
                ->get();
        }

        // ── Build stateGroups structure (state → cinema → theatre → times) ────
        // Each time entry includes is_bookable so Blade can render disabled pills.
        $cutoff      = now()->addMinutes(15);
        $stateGroups = [];

        foreach ($showtimeRows as $row) {
            $state    = $row->city_state;
            $cinemaId = $row->cinema_id;
            $dt       = Carbon::parse($row->start_time);

            // is_bookable: start_time must be > cutoff (now + 15 min)
            $isBookable = $dt->gt($cutoff);

            if (!isset($stateGroups[$state])) {
                $stateGroups[$state] = [
                    'state'   => $state,
                    'cinemas' => [],
                ];
            }

            if (!isset($stateGroups[$state]['cinemas'][$cinemaId])) {
                $stateGroups[$state]['cinemas'][$cinemaId] = [
                    'cinema_id'   => $cinemaId,
                    'cinema_name' => $row->cinema_name,
                    'city'        => $row->city_name,
                    'theatres'    => [],
                ];
            }

            $theatres    = &$stateGroups[$state]['cinemas'][$cinemaId]['theatres'];
            $theatreIdx  = null;

            foreach ($theatres as $i => $th) {
                if ($th['name'] === $row->theatre_name) {
                    $theatreIdx = $i;
                    break;
                }
            }

            if ($theatreIdx === null) {
                $theatres[] = [
                    'hall_id'    => $row->hall_id,
                    'theatre_id' => $row->theatre_id,
                    'name'       => $row->theatre_name,
                    'times'      => [],
                ];
                $theatreIdx = array_key_last($theatres);
            }

            $timeLabel = $dt->format('h:i A');

            // De-duplicate identical time labels within the same theatre.
            $alreadyAdded = false;
            foreach ($theatres[$theatreIdx]['times'] as $t) {
                if ($t['time'] === $timeLabel) { $alreadyAdded = true; break; }
            }

            if (!$alreadyAdded) {
                $theatres[$theatreIdx]['times'][] = [
                    'showtime_id'  => $row->showtime_id,
                    'time'         => $timeLabel,
                    'is_bookable'  => $isBookable,   // ← consumed by Blade
                ];
            }

            unset($theatres);
        }

        // Normalise associative keys to sequential arrays for JSON / Blade.
        $stateGroups = array_values(array_map(function (array $sg) {
            $sg['cinemas'] = array_values($sg['cinemas']);
            return $sg;
        }, $stateGroups));

        return view('users.movie_details', [
            'movie'          => $movie,
            'availableDates' => $availableDates,   // Collection<Carbon>
            'activeDate'     => $activeDate,        // Carbon|null
            'stateGroups'    => json_encode($stateGroups, JSON_UNESCAPED_UNICODE),
        ]);
    }
}