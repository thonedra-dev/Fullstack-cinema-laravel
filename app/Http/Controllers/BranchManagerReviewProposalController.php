<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\ShowtimeProposalStatus;
use App\Models\ShowtimeProposal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchManagerReviewProposalController extends Controller
{
    /**
     * GET /manager/proposal/review/{movieId}
     */
    public function show(int $movieId)
    {
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId  = (int) session('bm_cinema_id');
        $managerId = (int) Auth::guard('manager')->id();

        $cinema = Cinema::findOrFail($cinemaId);
        $movie  = Movie::with('genres')->findOrFail($movieId);

        // ── Fetch the latest proposal for this movie/cinema/manager ─────
        $proposal = ShowtimeProposalStatus::where('movie_id',  $movieId)
            ->where('cinema_id',  $cinemaId)
            ->where('manager_id', $managerId)
            ->with(['proposals.hall.theatre'])
            ->latest()
            ->firstOrFail();

        // ── Group proposal slots, ordered by start_datetime ────────────
        $groupRows = ShowtimeProposal::with('hall.theatre')
            ->where('status_id', $proposal->id)
            ->orderBy('start_datetime')
            ->get();

        $groupRows->each(function ($row) {
            $row->setRelation('theatre', $row->hall?->theatre);
        });

        // ── Quota info ─────────────────────────────────────────────────
        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $movieId)
            ->where('cinema_id', $cinemaId)
            ->first();

        return view('branch_manager.bm_review_proposals', compact(
            'cinema',
            'movie',
            'proposal',
            'groupRows',
            'quota'
        ));
    }
}