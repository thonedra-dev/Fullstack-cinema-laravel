<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Showtime;
use App\Models\ShowtimeProposal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMovieProposalController extends Controller
{
    /* ─────────────────────────────────────────────────────────────
       LIST — one abstract notification card per unique proposal group
       A "group" = same (manager_id, cinema_id, theatre_id, movie_id)
       submitted within the same day.

       GET /admin/proposals
    ───────────────────────────────────────────────────────────── */
    public function index()
    {
        // Fetch all proposals, grouped in PHP for display.
        // Each row in the DB is one date-slot; we group them into a
        // single card per submission session.
        $rows = ShowtimeProposal::with(['manager', 'cinema', 'theatre', 'movie'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        // Group: key = "manager_id-theatre_id-movie_id-date(created_at)"
        // This naturally groups all rows submitted in the same session.
        $groups = $rows->groupBy(function ($row) {
            return $row->manager_id . '-' .
                   $row->theatre_id . '-' .
                   $row->movie_id   . '-' .
                   $row->created_at->format('Y-m-d');
        });

        // Build a summary object per group for the Blade
        $proposals = $groups->map(function ($rows) {
            $first = $rows->first();
            return (object) [
                // Use the first row's ID as the "group representative" for the detail link
                'group_key'    => $first->manager_id . '-' . $first->theatre_id . '-' .
                                  $first->movie_id   . '-' . $first->created_at->format('Y-m-d'),
                'first_id'     => $first->id,
                'manager'      => $first->manager,
                'cinema'       => $first->cinema,
                'theatre'      => $first->theatre,
                'movie'        => $first->movie,
                'slot_count'   => $rows->count(),
                'status'       => $rows->contains('status', 'pending') ? 'pending' : 'approved',
                'created_at'   => $first->created_at,
                'start_time'   => $first->start_datetime,  // same time for all rows in group
            ];
        })->values();

        return view('admin.movie_proposals', compact('proposals'));
    }

    /* ─────────────────────────────────────────────────────────────
       DETAIL — all rows for a given group (same manager+theatre+movie+day)
       GET /admin/proposals/{firstRowId}
    ───────────────────────────────────────────────────────────── */
    public function show(int $id)
    {
        // Load the representative row
        $first = ShowtimeProposal::with(['manager', 'cinema', 'theatre', 'movie.genres'])
            ->findOrFail($id);

        // Load all rows in the same group
        $groupRows = ShowtimeProposal::where('manager_id', $first->manager_id)
            ->where('theatre_id', $first->theatre_id)
            ->where('movie_id',   $first->movie_id)
            ->whereDate('created_at', $first->created_at->toDateString())
            ->orderBy('start_datetime')
            ->get();

        $city  = City::find($first->cinema?->city_id);

        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id',  $first->movie_id)
            ->where('cinema_id', $first->cinema_id)
            ->first();

        return view('admin.movie_proposal_detail', compact(
            'first',
            'groupRows',
            'city',
            'quota'
        ));
    }

    /* ─────────────────────────────────────────────────────────────
       APPROVE — approve all pending rows in the group,
                  create one Showtime per row
       POST /admin/proposals/{firstRowId}/approve
    ───────────────────────────────────────────────────────────── */
    public function approve(int $id)
    {
        $first = ShowtimeProposal::findOrFail($id);

        // Load all pending rows in the same submission group
        $groupRows = ShowtimeProposal::where('manager_id', $first->manager_id)
            ->where('theatre_id', $first->theatre_id)
            ->where('movie_id',   $first->movie_id)
            ->whereDate('created_at', $first->created_at->toDateString())
            ->where('status', 'pending')
            ->get();

        if ($groupRows->isEmpty()) {
            return redirect()
                ->route('admin.proposals.index')
                ->with('error', 'This proposal has already been processed.');
        }

        $conflicts = [];
        $approved  = [];

        foreach ($groupRows as $row) {
            // Final conflict check (another proposal may have been approved meanwhile)
            $conflict = Showtime::where('theatre_id', $row->theatre_id)
                ->where(function ($q) use ($row) {
                    $q->where('start_time', '<', $row->end_datetime)
                      ->where('end_time',   '>', $row->start_datetime);
                })
                ->first();

            if ($conflict) {
                $conflicts[] = $row->start_datetime->format('d M Y h:i A');
            } else {
                $approved[] = $row;
            }
        }

        if (!empty($conflicts)) {
            return redirect()
                ->route('admin.proposals.show', $id)
                ->with('error', 'Cannot approve — conflicts detected on: ' .
                    implode(', ', $conflicts) . '.');
        }

        // Insert showtimes + mark proposals approved
        foreach ($approved as $row) {
            Showtime::create([
                'theatre_id' => $row->theatre_id,
                'movie_id'   => $row->movie_id,
                'start_time' => $row->start_datetime,
                'end_time'   => $row->end_datetime,
            ]);

            $row->update(['status' => 'approved']);
        }

        $movieName = $first->movie?->movie_name ?? 'Movie';

        return redirect()
            ->route('admin.proposals.index')
            ->with('success', count($approved) . ' showtime(s) approved and created for "' .
                $movieName . '".');
    }
}