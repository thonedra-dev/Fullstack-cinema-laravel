<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Cinema;
use App\Models\ShowtimeProposalStatus;
use Illuminate\Support\Facades\Auth;

class BranchManagerUpcomingController extends Controller
{
    /**
     * GET /manager/upcoming
     *
     * Shows every movie assigned to this cinema, always.
     * Nothing is ever hidden based on proposal status — even approved
     * proposals stay visible so the manager can review them as a reference.
     *
     * The ONLY thing that determines what ACTION BUTTON appears is the
     * status column in showtime_proposal_status for this cinema+manager:
     *
     *   No row found   → null      → [ Setup This Movie ]
     *   status=pending             → [ Review Proposal ]
     *   status=approved            → [ Review Proposal ]
     *   status=rejected            → [ Review Old Proposal ] + [ Re-submit ]
     *
     * We do NOT touch the showtimes table here at all.
     * The relationship between showtime_proposals and showtime_proposal_status
     * is purely through status_id FK — cinema_id / movie_id / manager_id
     * live only on showtime_proposal_status, not on showtime_proposals.
     */
    public function index()
    {
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $manager   = Auth::guard('manager')->user();
$managerId = $manager->manager_id;                          // from the authenticated model
$cinemaId  = $manager->cinema_id ?? (int) session('bm_cinema_id');  // fallback just in case
        $cinema    = Cinema::findOrFail($cinemaId);

 

        // ── Fetch all proposal status rows for this cinema + manager ─────
        // Keyed by (int) movie_id so the lookup below never fails due to
        // string-vs-integer type mismatch from the DB driver.
        $proposalData = ShowtimeProposalStatus::where('cinema_id',  $cinemaId)
            ->where('manager_id', $managerId)
            ->get(['id', 'movie_id', 'status', 'admin_note'])
            ->keyBy(fn($row) => (int) $row->movie_id);

        // ── All movies assigned to this cinema, no exclusions ────────────
        // We never remove a card based on proposal status — approved cards
        // stay so the manager can review them as a reference.
        $movies = Movie::with('genres')
            ->join('cinema_movie_quotas as cmq', 'movies.movie_id', '=', 'cmq.movie_id')
            ->leftJoin('supervisors', 'cmq.supervisor_id', '=', 'supervisors.supervisor_id')
            ->where('cmq.cinema_id', $cinemaId)
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

                // Cast to int — matches the int-keyed $proposalData collection
                $proposal = $proposalData->get((int) $movie->movie_id);

                // null | 'pending' | 'approved' | 'rejected'
                $movie->proposal_status     = $proposal?->status     ?? null;
                $movie->proposal_admin_note = $proposal?->admin_note ?? null;
                $movie->proposal_id         = $proposal?->id         ?? null;

                return $movie;
            });

        return view('branch_manager.upcoming_movies', compact('cinema', 'movies'));
    }
}