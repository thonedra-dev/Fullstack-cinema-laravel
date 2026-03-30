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
       LIST
       GET /admin/proposals
    ───────────────────────────────────────────────────────────── */
    public function index()
    {
        $rows = ShowtimeProposal::with(['manager', 'cinema', 'theatre', 'movie'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $groups = $rows->groupBy(function ($row) {
            return $row->manager_id . '-' .
                   $row->theatre_id . '-' .
                   $row->movie_id   . '-' .
                   $row->created_at->format('Y-m-d');
        });

        $proposals = $groups->map(function ($rows) {
            $first = $rows->first();
            // Group status: pending if ANY row is still pending
            $status = $rows->contains('status', 'pending') ? 'pending' :
                      ($rows->contains('status', 'approved') ? 'approved' : 'rejected');
            return (object) [
                'first_id'   => $first->id,
                'manager'    => $first->manager,
                'cinema'     => $first->cinema,
                'theatre'    => $first->theatre,
                'movie'      => $first->movie,
                'slot_count' => $rows->count(),
                'status'     => $status,
                'created_at' => $first->created_at,
                'start_time' => $first->start_datetime,
            ];
        })->values();

        return view('admin.movie_proposals', compact('proposals'));
    }

    /* ─────────────────────────────────────────────────────────────
       DETAIL
       GET /admin/proposals/{id}
    ───────────────────────────────────────────────────────────── */
    public function show(int $id)
    {
        $first = ShowtimeProposal::with(['manager', 'cinema', 'theatre', 'movie.genres'])
            ->findOrFail($id);

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
            'first', 'groupRows', 'city', 'quota'
        ));
    }

    /* ─────────────────────────────────────────────────────────────
       APPROVE
       POST /admin/proposals/{id}/approve
    ───────────────────────────────────────────────────────────── */
    public function approve(int $id)
    {
        $first = ShowtimeProposal::findOrFail($id);

        $groupRows = ShowtimeProposal::where('manager_id', $first->manager_id)
            ->where('theatre_id', $first->theatre_id)
            ->where('movie_id',   $first->movie_id)
            ->whereDate('created_at', $first->created_at->toDateString())
            ->where('status', 'pending')
            ->get();

        if ($groupRows->isEmpty()) {
            return redirect()->route('admin.proposals.index')
                ->with('error', 'This proposal has already been processed.');
        }

        $conflicts = [];
        $approved  = [];

        foreach ($groupRows as $row) {
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
            return redirect()->route('admin.proposals.show', $id)
                ->with('error', 'Conflicts on: ' . implode(', ', $conflicts) . '.');
        }

        foreach ($approved as $row) {
            Showtime::create([
                'theatre_id' => $row->theatre_id,
                'movie_id'   => $row->movie_id,
                'start_time' => $row->start_datetime,
                'end_time'   => $row->end_datetime,
            ]);
            $row->update(['status' => 'approved']);
        }

        return redirect()->route('admin.proposals.index')
            ->with('success', count($approved) . ' showtime(s) approved for "' .
                $first->movie?->movie_name . '".');
    }

    /* ─────────────────────────────────────────────────────────────
       REJECT — mark all group rows rejected, store admin_note
       POST /admin/proposals/{id}/reject
    ───────────────────────────────────────────────────────────── */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'admin_note' => 'required|string|min:5|max:1000',
        ]);

        $first = ShowtimeProposal::findOrFail($id);

        ShowtimeProposal::where('manager_id', $first->manager_id)
            ->where('theatre_id', $first->theatre_id)
            ->where('movie_id',   $first->movie_id)
            ->whereDate('created_at', $first->created_at->toDateString())
            ->where('status', 'pending')
            ->update([
                'status'     => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

        return redirect()->route('admin.proposals.index')
            ->with('success', 'Proposal rejected with note sent to branch manager.');
    }
}