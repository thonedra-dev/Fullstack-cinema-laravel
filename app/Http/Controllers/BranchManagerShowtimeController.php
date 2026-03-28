<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Theatre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchManagerShowtimeController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       GUARD HELPER
    ───────────────────────────────────────────────────────────── */
    private function guard()
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }
        return null;
    }

    /* ─────────────────────────────────────────────────────────────
       ENTRY A — from upcoming_movies (movie pre-selected)
       GET /manager/setup/movie/{movieId}
    ───────────────────────────────────────────────────────────── */
    public function fromMovie(int $movieId)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId = session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);
        $movie    = Movie::with('genres')->findOrFail($movieId);

        // Verify this movie is actually assigned to this cinema
        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $movieId)
            ->where('cinema_id', $cinemaId)
            ->first();

        if (!$quota) {
            return redirect()->route('manager.upcoming')
                ->with('bm_error', 'This movie is not assigned to your cinema.');
        }

        return $this->renderSetupPage(
            cinema:         $cinema,
            movie:          $movie,
            quota:          $quota,
            preselectedMode: 'movie'   // theatre must be chosen on the page
        );
    }

    /* ─────────────────────────────────────────────────────────────
       ENTRY B — from bm_resources theatre card (theatre pre-selected)
       GET /manager/setup/theatre/{theatreId}
    ───────────────────────────────────────────────────────────── */
    public function fromTheatre(int $theatreId)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId = session('bm_cinema_id');
        $cinema   = Cinema::findOrFail($cinemaId);
        $theatre  = Theatre::findOrFail($theatreId);

        // Verify this theatre belongs to this cinema
        if ((int) $theatre->cinema_id !== (int) $cinemaId) {
            return redirect()->route('manager.resources')
                ->with('bm_error', 'This theatre does not belong to your cinema.');
        }

        return $this->renderSetupPage(
            cinema:          $cinema,
            movie:           null,
            quota:           null,
            preselectedMode: 'theatre',  // movie must be chosen on the page
            preselectedTheatre: $theatre
        );
    }

    /* ─────────────────────────────────────────────────────────────
       SHARED RENDER — builds data for BOTH entry modes
    ───────────────────────────────────────────────────────────── */
    private function renderSetupPage(
        Cinema  $cinema,
        ?Movie  $movie,
        ?object $quota,
        string  $preselectedMode,
        ?Theatre $preselectedTheatre = null
    ) {
        $cinemaId = $cinema->cinema_id;

        // All theatres in this cinema with their seats
        $theatres = Theatre::with(['seats' => function ($q) {
            $q->orderBy('row_label')->orderBy('seat_number');
        }])
        ->where('cinema_id', $cinemaId)
        ->orderBy('theatre_name')
        ->get();

        // All movies assigned to this cinema (for theatre-first entry mode dropdown)
        $assignedMovies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        // Existing showtimes for all theatres in this cinema — used for conflict preview in JS
        $existingShowtimes = Showtime::whereIn(
            'theatre_id',
            $theatres->pluck('theatre_id')
        )
        ->select('showtime_id', 'theatre_id', 'movie_id', 'start_time', 'end_time')
        ->get()
        ->map(function ($st) {
            return [
                'theatre_id' => $st->theatre_id,
                'movie_id'   => $st->movie_id,
                'start'      => $st->start_time->toIso8601String(),
                'end'        => $st->end_time->toIso8601String(),
            ];
        });

        return view('branch_manager.setup_movie_timetable', compact(
            'cinema',
            'movie',
            'quota',
            'theatres',
            'assignedMovies',
            'existingShowtimes',
            'preselectedMode',
            'preselectedTheatre'
        ));
    }

    /* ─────────────────────────────────────────────────────────────
       STORE — insert showtimes (called by BOTH entry modes)
       POST /manager/showtimes
    ───────────────────────────────────────────────────────────── */
    public function store(Request $request)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId = session('bm_cinema_id');

        $validated = $request->validate([
            'movie_id'   => 'required|integer|exists:movies,movie_id',
            'theatre_id' => 'required|integer|exists:theatres,theatre_id',
            'dates'      => 'required|array|min:1',
            'dates.*'    => 'date_format:Y-m-d',
            'hour'       => 'required|integer|min:1|max:12',
            'minute'     => 'required|integer|min:0|max:59',
            'ampm'       => 'required|in:AM,PM',
        ]);

        // Security: verify theatre belongs to this cinema
        $theatre = Theatre::findOrFail($validated['theatre_id']);
        if ((int) $theatre->cinema_id !== (int) $cinemaId) {
            return back()->with('bm_error', 'Invalid theatre selection.');
        }

        // Get runtime from cinema_movie_quotas
        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $validated['movie_id'])
            ->where('cinema_id', $cinemaId)
            ->first();

        if (!$quota) {
            return back()->with('bm_error', 'This movie is not assigned to your cinema.');
        }

        // ── Build start/end for each date + check conflicts ──
        $conflicts = [];
        $inserts   = [];

        foreach ($validated['dates'] as $date) {
            $timeStr = sprintf(
                '%s %02d:%02d %s',
                $date,
                $validated['hour'],
                $validated['minute'],
                $validated['ampm']
            );

            $startTime = Carbon::createFromFormat('Y-m-d h:i A', $timeStr);
            $endTime   = $startTime->copy()->addMinutes((int) $quota->showtime_slots * 60);
            // Note: showtime_slots = number of showtime slots. Runtime for end_time:
            // We use the movie's runtime from movies table for exact duration.
            // Re-fetch runtime:
        }

        // Get actual movie runtime
        $movie = Movie::findOrFail($validated['movie_id']);

        $conflicts = [];
        $inserts   = [];

        foreach ($validated['dates'] as $date) {
            $timeStr = sprintf(
                '%s %02d:%02d %s',
                $date,
                $validated['hour'],
                $validated['minute'],
                $validated['ampm']
            );

            $startTime = Carbon::createFromFormat('Y-m-d h:i A', $timeStr);
            $endTime   = $startTime->copy()->addMinutes($movie->runtime);

            // Conflict check: same theatre, overlapping time
            $conflict = Showtime::where('theatre_id', $validated['theatre_id'])
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time',   '>', $startTime);
                })
                ->first();

            if ($conflict) {
                $conflicts[] = $date . ' ' . $validated['hour'] . ':' .
                    sprintf('%02d', $validated['minute']) . ' ' . $validated['ampm'] .
                    ' conflicts with an existing showtime.';
            } else {
                $inserts[] = [
                    'theatre_id' => $validated['theatre_id'],
                    'movie_id'   => $validated['movie_id'],
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ];
            }
        }

        if (!empty($conflicts)) {
            return back()
                ->withInput()
                ->with('bm_error', 'Time conflicts detected: ' . implode(' | ', $conflicts));
        }

        // ── Insert all ──
        foreach ($inserts as $insert) {
            Showtime::create($insert);
        }

        $count = count($inserts);

        return redirect()->route('manager.resources')
            ->with('bm_success', $count . ' showtime(s) scheduled successfully for "' . $movie->movie_name . '".');
    }
}