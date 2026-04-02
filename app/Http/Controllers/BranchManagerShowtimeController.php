<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\ShowtimeProposal;
use App\Models\ShowtimeProposalStatus;
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

        // Existing APPROVED showtimes for client-side conflict preview
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
       STORE — parse schedule_json, validate, conflict-check, insert
       POST /manager/showtimes
    ───────────────────────────────────────────────────────────── */
    public function store(Request $request)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId  = (int) session('bm_cinema_id');
        $managerId = (int) session('bm_manager_id');

        $request->validate([
            'movie_id'      => 'required|integer|exists:movies,movie_id',
            'schedule_json' => 'required|string',
        ]);

        $movieId = (int) $request->input('movie_id');
        $movie   = Movie::findOrFail($movieId);

        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $movieId)
            ->where('cinema_id', $cinemaId)
            ->first();

        if (!$quota) {
            return back()->with('bm_error', 'This movie is not assigned to your cinema.');
        }

        $schedule = json_decode($request->input('schedule_json'), true);

        if (!is_array($schedule) || empty($schedule)) {
            return back()->with('bm_error', 'No schedule data received. Please add at least one slot.');
        }

        $allConflicts = [];
        $allInserts   = [];

        foreach ($schedule as $theatreEntry) {
            $theatreId = (int) ($theatreEntry['theatreId'] ?? 0);
            $theatre   = Theatre::find($theatreId);

            if (!$theatre || (int) $theatre->cinema_id !== $cinemaId) {
                return back()->with('bm_error', 'Invalid theatre in submitted schedule.');
            }

            foreach ($theatreEntry['slotGroups'] ?? [] as $sg) {
                $hour   = (int) ($sg['hour']   ?? 0);
                $minute = (int) ($sg['minute'] ?? 0);
                $ampm   = in_array($sg['ampm'] ?? '', ['AM', 'PM']) ? $sg['ampm'] : 'AM';
                $dates  = is_array($sg['dates'] ?? null) ? $sg['dates'] : [];

                $h24     = $hour % 12;
                if ($ampm === 'PM') $h24 += 12;
                $timeStr = sprintf('%02d:%02d:00', $h24, $minute);

                foreach ($dates as $date) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $allConflicts[] = [
                            'theatre_name'   => $theatre->theatre_name,
                            'proposed_date'  => $date,
                            'proposed_time'  => sprintf('%02d:%02d %s', $hour, $minute, $ampm),
                            'conflict_start' => '—',
                            'conflict_end'   => '—',
                            'movie_name'     => 'Invalid date format',
                            'movie_poster'   => null,
                        ];
                        continue;
                    }

                    $startDatetime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $timeStr);
                    $endDatetime   = $startDatetime->copy()->addMinutes($movie->runtime);

                    $conflict = Showtime::with('movie')
                        ->where('theatre_id', $theatreId)
                        ->where(function ($q) use ($startDatetime, $endDatetime) {
                            $q->where('start_time', '<', $endDatetime)
                              ->where('end_time',   '>', $startDatetime);
                        })
                        ->first();

                    if ($conflict) {
                        $allConflicts[] = [
                            'theatre_name'   => $theatre->theatre_name,
                            'proposed_date'  => $date,
                            'proposed_time'  => sprintf('%02d:%02d %s', $hour, $minute, $ampm),
                            'conflict_start' => $conflict->start_time->format('d M Y, h:i A'),
                            'conflict_end'   => $conflict->end_time->format('h:i A'),
                            'movie_name'     => $conflict->movie?->movie_name  ?? 'Unknown Movie',
                            'movie_poster'   => $conflict->movie?->portrait_poster ?? null,
                        ];
                    } else {
                        $allInserts[] = [
                            'theatreId'      => $theatreId,
                            'start_datetime' => $startDatetime,
                            'end_datetime'   => $endDatetime,
                        ];
                    }
                }
            }
        }

        if (!empty($allConflicts)) {
            return back()->with('bm_conflicts', $allConflicts);
        }

        // Create / find Parent Status record
        $statusRecord = ShowtimeProposalStatus::firstOrCreate(
            [
                'manager_id' => $managerId,
                'cinema_id'  => $cinemaId,
                'movie_id'   => $movieId,
            ],
            ['status' => 'pending', 'admin_note' => null]
        );

        if ($statusRecord->status === 'rejected') {
            $statusRecord->update(['status' => 'pending', 'admin_note' => null]);
        }

        foreach ($allInserts as $slot) {
            ShowtimeProposal::create([
                'manager_id'     => $managerId,
                'cinema_id'      => $cinemaId,
                'theatre_id'     => $slot['theatreId'],
                'movie_id'       => $movieId,
                'start_datetime' => $slot['start_datetime'],
                'end_datetime'   => $slot['end_datetime'],
            ]);
        }

        return redirect()->route('manager.upcoming')
            ->with('bm_success',
                count($allInserts) . ' showtime slot(s) for "' .
                $movie->movie_name . '" submitted for admin approval.');
    }

    /* ─────────────────────────────────────────────────────────────
       REARRANGE — wipe a rejected proposal and return to fresh setup
       POST /manager/proposals/{movieId}/rearrange

       Deletes:
         • All ShowtimeProposal rows  for this movie_id + cinema_id
         • The ShowtimeProposalStatus for this movie_id + cinema_id + manager_id
       Then redirects to the movie-first setup entry so the manager
       can submit a completely fresh schedule.
    ───────────────────────────────────────────────────────────── */
    public function rearrange(int $movieId)
    {
        if ($r = $this->guard()) return $r;

        $cinemaId  = (int) session('bm_cinema_id');
        $managerId = (int) session('bm_manager_id');

        // Fetch the status record — only allow rearrange when rejected
        $statusRecord = ShowtimeProposalStatus::where('movie_id',   $movieId)
            ->where('cinema_id',  $cinemaId)
            ->where('manager_id', $managerId)
            ->first();

        if (!$statusRecord) {
            return redirect()->route('manager.upcoming')
                ->with('bm_error', 'No proposal found for this movie.');
        }

        if ($statusRecord->status !== 'rejected') {
            return redirect()->route('manager.upcoming')
                ->with('bm_error', 'Only rejected proposals can be rearranged.');
        }

        // Delete all child ShowtimeProposal rows for this movie + cinema
        ShowtimeProposal::where('movie_id',  $movieId)
            ->where('cinema_id', $cinemaId)
            ->delete();

        // Delete the parent ShowtimeProposalStatus record
        $statusRecord->delete();

        // Redirect to a fresh movie setup page
        return redirect()->route('manager.setup.movie', $movieId)
            ->with('bm_success', 'Previous proposal cleared. You can now build a fresh schedule.');
    }
}