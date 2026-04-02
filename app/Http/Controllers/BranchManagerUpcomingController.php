<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Cinema;
use App\Models\Theatre;
use App\Models\Showtime;
use Illuminate\Support\Facades\DB;

class BranchManagerUpcomingController extends Controller
{
    /**
     * Show movies assigned to this cinema that have NO showtimes yet.
     * These are "upcoming" — the manager needs to set up their timetables.
     *
     * GET /manager/upcoming
     */
   public function index()
{
    if (!session('bm_manager_id') || !session('bm_cinema_id')) {
        return redirect()->route('manager.login');
    }

    $cinemaId  = session('bm_cinema_id');
    $managerId = session('bm_manager_id');
    $cinema    = Cinema::findOrFail($cinemaId);

    $theatreIds = Theatre::where('cinema_id', $cinemaId)->pluck('theatre_id');

    $activeMovieIds = Showtime::whereIn('theatre_id', $theatreIds)
                              ->distinct()
                              ->pluck('movie_id');

    // Proposal statuses for this manager+cinema, keyed by movie_id
    $proposalStatuses = \App\Models\ShowtimeProposalStatus::where('cinema_id', $cinemaId)
        ->where('manager_id', $managerId)
        ->pluck('status', 'movie_id')
        ->toArray();

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
        ->map(function ($movie) use ($proposalStatuses) {
            $movie->quota_info = (object) [
                'start_date'       => $movie->start_date,
                'maximum_end_date' => $movie->maximum_end_date,
                'showtime_slots'   => $movie->showtime_slots,
                'supervisor_name'  => $movie->supervisor_name,
            ];
            // 'pending' | 'rejected' | null (not yet submitted)
            $movie->proposal_status = $proposalStatuses[$movie->movie_id] ?? null;
            return $movie;
        });

    return view('branch_manager.upcoming_movies', compact('cinema', 'movies'));
}
}