<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Hall;
use App\Models\Theatre;
use App\Models\ShowtimeProposalStatus;
use Illuminate\Support\Facades\Auth;

class BranchManagerReviewProposalController extends Controller
{
    /**
     * GET /manager/proposal/review/{movieId}
     *
     * Loads the read-only proposal review page for a given movie.
     *
     * Route to add in your manager routes file:
     *   Route::get('/manager/proposal/review/{movieId}',
     *              [BranchManagerReviewProposalController::class, 'show'])
     *          ->name('manager.proposal.review');
     *
     * ── What it passes to the blade ────────────────────────────────────
     *
     *   $cinema       — Cinema model
     *   $movie        — Movie model (with genres eager-loaded)
     *   $proposal     — ShowtimeProposalStatus model
     *                   (with ->proposals->hall->theatre eager-loaded)
     *   $groupedSlots — Collection keyed by 'Y-m-d' date strings.
     *                   Each value is an array of associative arrays:
     *                   [
     *                     'theatre_name' => 'IMAX',
     *                     'start'        => '07:00 AM',
     *                     'end'          => '09:05 AM',
     *                   ]
     *                   Sorted: dates ascending, times ascending within each date.
     */
    public function show(int $movieId)
    {
        // ── Auth guard ──────────────────────────────────────────────────
        if (!Auth::guard('manager')->check() || !session('bm_cinema_id')) {
            return redirect()->route('manager.login');
        }

        $cinemaId  = (int) session('bm_cinema_id');
        $managerId = (int) Auth::guard('manager')->id();

        $cinema = Cinema::findOrFail($cinemaId);
        $movie  = Movie::with('genres')->findOrFail($movieId);

        // ── Fetch the latest proposal for this movie/cinema/manager ─────
        // There should only ever be one active row (the system deletes the
        // old one on re-submit), but we use first() defensively.
        $proposal = ShowtimeProposalStatus::where('movie_id',  $movieId)
            ->where('cinema_id',  $cinemaId)
            ->where('manager_id', $managerId)
            ->with([
                // Each individual slot row
                'proposals',
                // The hall the slot is assigned to
                'proposals.hall',
                // The master theatre type (name lives on Theatre, not Hall)
                'proposals.hall.theatre',
            ])
            ->latest()   // created_at DESC — gives us the most recent batch
            ->firstOrFail();

        // ── Build $groupedSlots ─────────────────────────────────────────
        // Group all proposal slot rows by their calendar date, then format
        // start/end as 12-hour strings for display.
        //
        // showtime_proposals columns used:
        //   start_datetime  — Carbon datetime (cast in ShowtimeProposal)
        //   end_datetime    — Carbon datetime
        //   hall            → theatre → theatre_name

        $groupedSlots = $proposal->proposals
            // Sort by start time so dates and times appear in order
            ->sortBy('start_datetime')
            ->groupBy(function ($slot) {
                // Key = 'YYYY-MM-DD'
                return $slot->start_datetime->format('Y-m-d');
            })
            ->map(function ($slotsForDate) {
                return $slotsForDate->map(function ($slot) {
                    return [
                        'theatre_name' => $slot->hall?->theatre?->theatre_name ?? 'Unknown Theatre',
                        'start'        => $slot->start_datetime->format('h:i A'),
                        'end'          => $slot->end_datetime->format('h:i A'),
                    ];
                })->values()->all();   // re-index to a plain array
            });

        return view('branch_manager.bm_review_proposals',
            compact('cinema', 'movie', 'proposal', 'groupedSlots'));
    }
}