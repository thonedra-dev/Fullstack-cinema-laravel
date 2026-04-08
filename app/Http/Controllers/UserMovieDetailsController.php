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
     * Passes to view:
     *   $movie        – Movie with ->genres
     *   $stateGroups  – JSON-encoded array for JS sidebar + showtime engine:
     *     [
     *       {
     *         "state": "Selangor",
     *         "cinemas": [
     *           {
     *             "cinema_id": 1,
     *             "cinema_name": "TGV Sunway Pyramid",
     *             "city": "Petaling Jaya",
     *             "dateGroups": [
     *               {
     *                 "date": "2026-04-09",
     *                 "label_day": "Today",
     *                 "label_num": "9",
     *                 "label_month": "Apr",
     *                 "theatres": [
     *                   { "name": "DELUXE", "times": ["07:00 PM", "09:30 PM"] }
     *                 ]
     *               }
     *             ]
     *           }
     *         ]
     *       }
     *     ]
     */
    public function show(int $movieId)
    {
        $movie = Movie::with('genres')->findOrFail($movieId);

        // ── 1. Fetch all cinemas assigned to this movie ────────
        // cinema_movie_quotas links movie_id → cinema_id
        $quotaRows = DB::table('cinema_movie_quotas as cmq')
            ->join('cinemas as c',  'cmq.cinema_id', '=', 'c.cinema_id')
            ->join('cities as ct',  'c.city_id',     '=', 'ct.city_id')
            ->where('cmq.movie_id', $movieId)
            ->select(
                'c.cinema_id',
                'c.cinema_name',
                'ct.city_name',
                'ct.city_state'
            )
            ->orderBy('ct.city_state')
            ->orderBy('c.cinema_name')
            ->get();

        // ── 2. Fetch all showtime_proposals rows for this movie ─
        // One row per slot: cinema_id, theatre_id, start_datetime
        // We only show proposals for display — the theatre name comes from theatres table
        $proposals = DB::table('showtime_proposals as sp')
            ->join('theatres as t', 'sp.theatre_id', '=', 't.theatre_id')
            ->where('sp.movie_id', $movieId)
            ->select(
                'sp.cinema_id',
                'sp.start_datetime',
                't.theatre_name'
            )
            ->orderBy('sp.start_datetime')
            ->get();

        // ── 3. Build a per-cinema dateGroups map ───────────────
        // cinemaDateGroups[cinema_id] = [ dateGroup, … ]
        $cinemaDateGroups = [];

        foreach ($proposals as $row) {
            $cid = $row->cinema_id;
            $dt  = Carbon::parse($row->start_datetime);
            $day = $dt->format('Y-m-d');

            if (!isset($cinemaDateGroups[$cid])) {
                $cinemaDateGroups[$cid] = [];
            }

            if (!isset($cinemaDateGroups[$cid][$day])) {
                $cinemaDateGroups[$cid][$day] = [
                    'date'        => $day,
                    'label_day'   => $dt->isToday() ? 'Today' : $dt->format('D'),
                    'label_num'   => $dt->format('j'),
                    'label_month' => $dt->format('M'),
                    'theatres'    => [],       // keyed by theatre_name temporarily
                ];
            }

            $tName = $row->theatre_name;
            if (!isset($cinemaDateGroups[$cid][$day]['theatres'][$tName])) {
                $cinemaDateGroups[$cid][$day]['theatres'][$tName] = [];
            }

            $cinemaDateGroups[$cid][$day]['theatres'][$tName][] = $dt->format('h:i A');
        }

        // Flatten theatre maps → arrays and sort
        foreach ($cinemaDateGroups as $cid => &$days) {
            foreach ($days as $day => &$dg) {
                $theatreArr = [];
                foreach ($dg['theatres'] as $tName => $times) {
                    $theatreArr[] = [
                        'name'  => $tName,
                        'times' => array_values(array_unique($times)),
                    ];
                }
                $dg['theatres'] = $theatreArr;
            }
            unset($dg);
            $days = array_values($days);
        }
        unset($days);

        // ── 4. Build stateGroups ────────────────────────────────
        $stateGroups = [];

        foreach ($quotaRows as $row) {
            $state   = $row->city_state;
            $cinemaId = $row->cinema_id;

            if (!isset($stateGroups[$state])) {
                $stateGroups[$state] = [
                    'state'   => $state,
                    'cinemas' => [],
                ];
            }

            $stateGroups[$state]['cinemas'][] = [
                'cinema_id'   => $cinemaId,
                'cinema_name' => $row->cinema_name,
                'city'        => $row->city_name,
                'dateGroups'  => $cinemaDateGroups[$cinemaId] ?? [],
            ];
        }

        // Re-index as array and remove duplicate cinemas per state
        $stateGroups = array_map(function ($sg) {
            // Deduplicate cinemas by cinema_id
            $seen = [];
            $unique = [];
            foreach ($sg['cinemas'] as $c) {
                if (!in_array($c['cinema_id'], $seen)) {
                    $seen[]   = $c['cinema_id'];
                    $unique[] = $c;
                }
            }
            $sg['cinemas'] = $unique;
            return $sg;
        }, array_values($stateGroups));

        return view('users.movie_details', [
            'movie'       => $movie,
            'stateGroups' => json_encode($stateGroups, JSON_UNESCAPED_UNICODE),
        ]);
    }
}