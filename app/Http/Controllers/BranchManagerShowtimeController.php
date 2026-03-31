<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\ShowtimeProposal;
use App\Models\ShowtimeProposalStatus; // <-- Added missing model
use App\Models\Theatre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchManagerShowtimeController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       GUARD
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

        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $movieId)
            ->where('cinema_id', $cinemaId)
            ->first();

        if (!$quota) {
            return redirect()->route('manager.upcoming')
                ->with('bm_error', 'This movie is not assigned to your cinema.');
        }

        return $this->renderSetupPage(
            cinema:          $cinema,
            movie:           $movie,
            quota:           $quota,
            preselectedMode: 'movie'
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

        if ((int) $theatre->cinema_id !== (int) $cinemaId) {
            return redirect()->route('manager.resources')
                ->with('bm_error', 'This theatre does not belong to your cinema.');
        }

        return $this->renderSetupPage(
            cinema:             $cinema,
            movie:              null,
            quota:              null,
            preselectedMode:    'theatre',
            preselectedTheatre: $theatre
        );
    }

    /* ─────────────────────────────────────────────────────────────
       SHARED RENDER
    ───────────────────────────────────────────────────────────── */
    private function renderSetupPage(
        Cinema   $cinema,
        ?Movie   $movie,
        ?object  $quota,
        string   $preselectedMode,
        ?Theatre $preselectedTheatre = null
    ) {
        $cinemaId = $cinema->cinema_id;

        $theatres = Theatre::with(['seats' => function ($q) {
            $q->orderBy('row_label')->orderBy('seat_number');
        }])
        ->where('cinema_id', $cinemaId)
        ->orderBy('theatre_name')
        ->get();

        $assignedMovies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->select('movies.*', 'cmq.showtime_slots', 'cmq.start_date', 'cmq.maximum_end_date')
            ->get();

        // Existing APPROVED showtimes for conflict preview in JS
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
       STORE — insert Parent Grand Plan AND ONE row per selected date
       POST /manager/showtimes
    ───────────────────────────────────────────────────────────── */
    public function store(Request $request)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId  = session('bm_cinema_id');
        $managerId = session('bm_manager_id');

        $validated = $request->validate([
            'movie_id'   => 'required|integer|exists:movies,movie_id',
            'theatre_id' => 'required|integer|exists:theatres,theatre_id',
            'dates'      => 'required|array|min:1',
            'dates.*'    => 'date_format:Y-m-d',
            'hour'       => 'required|integer|min:1|max:12',
            'minute'     => 'required|integer|min:0|max:59',
            'ampm'       => 'required|in:AM,PM',
        ]);

        // Security: theatre must belong to this cinema
        $theatre = Theatre::findOrFail($validated['theatre_id']);
        if ((int) $theatre->cinema_id !== (int) $cinemaId) {
            return back()->with('bm_error', 'Invalid theatre selection.');
        }

        // Movie must be assigned to this cinema
        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $validated['movie_id'])
            ->where('cinema_id', $cinemaId)
            ->first();

        if (!$quota) {
            return back()->with('bm_error', 'This movie is not assigned to your cinema.');
        }

        $movie = Movie::findOrFail($validated['movie_id']);

        // ── Convert 12-hour clock to 24-hour ──────────────────
        $h24 = (int) $validated['hour'] % 12;
        if ($validated['ampm'] === 'PM') $h24 += 12;
        $timeStr = sprintf('%02d:%02d:00', $h24, (int) $validated['minute']);

        // ── Conflict check + build inserts ────────────────────
        $conflicts = [];
        $inserts   = [];

        foreach ($validated['dates'] as $date) {
            $startDatetime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeStr);
            $endDatetime   = $startDatetime->copy()->addMinutes($movie->runtime);

            // Check against already-approved showtimes only
            $conflict = Showtime::where('theatre_id', $validated['theatre_id'])
                ->where(function ($q) use ($startDatetime, $endDatetime) {
                    $q->where('start_time', '<', $endDatetime)
                      ->where('end_time',   '>', $startDatetime);
                })
                ->first();

            if ($conflict) {
                $conflicts[] = $date;
            } else {
                $inserts[] = [
                    'start_datetime' => $startDatetime,
                    'end_datetime'   => $endDatetime,
                ];
            }
        }

        if (!empty($conflicts)) {
            return back()
                ->withInput()
                ->with('bm_error', 'Time conflicts on: ' . implode(', ', $conflicts) .
                    '. Please choose different dates or times.');
        }

        // ── 1. Create or Find Parent Grand Plan (Status) ──────
        // If a branch manager submits multiple theatres for the same movie, 
        // they get grouped under the same pending grand plan.
        $statusRecord = ShowtimeProposalStatus::firstOrCreate(
            [
                'manager_id' => $managerId,
                'cinema_id'  => $cinemaId,
                'movie_id'   => $validated['movie_id'],
            ],
            [
                'status'     => 'pending',
                'admin_note' => null,
            ]
        );

        // If a previously rejected plan is being resubmitted, reset it to pending
        if ($statusRecord->status === 'rejected') {
            $statusRecord->update([
                'status'     => 'pending',
                'admin_note' => null,
            ]);
        }

        // ── 2. Insert one proposal row per date (Children) ────
        foreach ($inserts as $slot) {
            ShowtimeProposal::create([
                'manager_id'     => $managerId,
                'cinema_id'      => $cinemaId,
                'theatre_id'     => $validated['theatre_id'],
                'movie_id'       => $validated['movie_id'],
                'start_datetime' => $slot['start_datetime'],
                'end_datetime'   => $slot['end_datetime'],
            ]);
        }

        return redirect()->route('manager.upcoming')
            ->with('bm_success', count($inserts) . ' showtime slot(s) for "' .
                $movie->movie_name . '" submitted for admin approval.');
    }
}