<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Theatre;
use App\Models\Showtime;
use App\Models\ShowtimeProposalStatus;
use Illuminate\Support\Facades\DB;

class BranchManagerUpcomingController extends Controller
{
    /**
     * Show movies assigned to this cinema that have NO approved showtimes yet.
     * Includes proposal state (pending / rejected + admin_note) for each movie.
     *
     * GET /manager/upcoming
     */
    public function index()
    {
        if (!session('bm_manager_id') || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId  = (int) session('bm_cinema_id');
        $managerId = (int) session('bm_manager_id');
        $cinema    = Cinema::findOrFail($cinemaId);

        // Theatre IDs belonging to this cinema
        $theatreIds = Theatre::where('cinema_id', $cinemaId)->pluck('theatre_id');

        // Movie IDs that already have at least one APPROVED showtime
        $activeMovieIds = Showtime::whereIn('theatre_id', $theatreIds)
                                  ->distinct()
                                  ->pluck('movie_id');

        // Proposal status rows for this manager+cinema, keyed by movie_id
        // We need both 'status' AND 'admin_note', so use keyBy instead of pluck
        $proposalData = ShowtimeProposalStatus::where('cinema_id', $cinemaId)
            ->where('manager_id', $managerId)
            ->get(['movie_id', 'status', 'admin_note'])
            ->keyBy('movie_id');

        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->leftJoin('supervisors', 'cmq.supervisor_id', '=', 'supervisors.supervisor_id')
            ->where('cmq.cinema_id', $cinemaId)
            ->whereNotIn('movies.movie_id', $activeMovieIds)
            ->select(
                'movies.*',
                'cmq.start_date',
                'cmq.maximum_end_date',
                'cmq.showtime_slots',
                'supervisors.supervisor_name'
            )
            ->get()
            ->map(function ($movie) use ($proposalData) {
                $movie->quota_info = (object) [
                    'start_date'       => $movie->start_date,
                    'maximum_end_date' => $movie->maximum_end_date,
                    'showtime_slots'   => $movie->showtime_slots,
                    'supervisor_name'  => $movie->supervisor_name,
                ];

                $proposal = $proposalData->get($movie->movie_id);

                // 'pending' | 'rejected' | null (not yet submitted)
                $movie->proposal_status     = $proposal?->status     ?? null;
                // Rejection note from admin — non-null only when status = 'rejected'
                $movie->proposal_admin_note = $proposal?->admin_note ?? null;

                return $movie;
            });

        return view('branch_manager.upcoming_movies', compact('cinema', 'movies'));
    }
}