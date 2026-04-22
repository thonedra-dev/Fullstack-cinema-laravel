<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\ShowtimeProposal;
use App\Models\ShowtimeProposalStatus;
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
        $proposals = ShowtimeProposalStatus::with(['manager', 'cinema', 'movie'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($proposals as $p) {
            $p->first_id = $p->id;

            $children = ShowtimeProposal::with('theatre')
                ->where('manager_id', $p->manager_id)
                ->where('cinema_id', $p->cinema_id)
                ->where('movie_id', $p->movie_id)
                ->get();

            $p->slot_count = $children->count();
            $p->start_time = $children->pluck('start_datetime')->min();

            $uniqueTheatres = $children->unique('theatre_id');
            if ($uniqueTheatres->count() > 1) {
                $p->theatre = (object) ['theatre_name' => $uniqueTheatres->count() . ' Theatres'];
            } else {
                $p->theatre = $uniqueTheatres->first()?->theatre;
            }
        }

        return view('admin.movie_proposals', compact('proposals'));
    }

    /* ─────────────────────────────────────────────────────────────
       DETAIL
       GET /admin/proposals/{id}
    ───────────────────────────────────────────────────────────── */
    public function show(int $id)
    {
        $first = ShowtimeProposalStatus::with(['manager', 'cinema', 'movie.genres'])->findOrFail($id);

        $groupRows = ShowtimeProposal::with('theatre')
            ->where('manager_id', $first->manager_id)
            ->where('cinema_id', $first->cinema_id)
            ->where('movie_id', $first->movie_id)
            ->orderBy('start_datetime')
            ->get();

        $city = City::find($first->cinema?->city_id);

        $quota = DB::table('cinema_movie_quotas')
            ->where('movie_id', $first->movie_id)
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
       APPROVE
       POST /admin/proposals/{id}/approve
    ───────────────────────────────────────────────────────────── */
    public function approve(int $id)
    {
        $statusRecord = ShowtimeProposalStatus::findOrFail($id);

        if ($statusRecord->status !== 'pending') {
            return redirect()->route('admin.proposals.index')
                ->with('error', 'This proposal has already been processed.');
        }

        $groupRows = ShowtimeProposal::where('manager_id', $statusRecord->manager_id)
            ->where('cinema_id', $statusRecord->cinema_id)
            ->where('movie_id', $statusRecord->movie_id)
            ->get();

        if ($groupRows->isEmpty()) {
            return redirect()->route('admin.proposals.show', $id)
                ->with('error', 'No proposal rows found for this approval group.');
        }

        $conflicts = [];
        $approved = [];

        foreach ($groupRows as $row) {
            $conflict = Showtime::where('theatre_id', $row->theatre_id)
                ->where(function ($q) use ($row) {
                    $q->where('start_time', '<', $row->end_datetime)
                        ->where('end_time', '>', $row->start_datetime);
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

        DB::transaction(function () use ($approved, $statusRecord) {
            foreach ($approved as $row) {
                Showtime::create([
                    'theatre_id' => $row->theatre_id,
                    'movie_id' => $row->movie_id,
                    'cinema_id' => $row->cinema_id,
                    'start_time' => $row->start_datetime,
                    'end_time' => $row->end_datetime,
                ]);
            }

            $statusRecord->update([
                'status' => 'approved',
            ]);
        });

        // ── Approval notification ─────────────────────────────
        $approvedMovie = Movie::find($statusRecord->movie_id);
        $movieName = $approvedMovie?->movie_name ?? 'Movie';
        $slotCount = count($approved);

        DB::table('manager_notifications')->insert([
            'manager_id' => $statusRecord->manager_id,
            'noti_picture' => $approvedMovie?->portrait_poster,
            'noti_message' => '"' . $movieName . '" — ' . $slotCount .
                ' showtime(s) have been approved and are now live on the schedule.',
            'tag' => 'Showtime Approved',
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.proposals.index')
            ->with(
                'success',
                count($approved) . ' showtime(s) approved for "' .
                ($statusRecord->movie?->movie_name ?? 'movie') . '".'
            );
    }

    /* ─────────────────────────────────────────────────────────────
       REJECT
       POST /admin/proposals/{id}/reject
    ───────────────────────────────────────────────────────────── */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'admin_note' => 'required|string|min:5|max:1000',
        ]);

        $statusRecord = ShowtimeProposalStatus::findOrFail($id);

        $statusRecord->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        $movie = Movie::find($statusRecord->movie_id);

        $supervisorRow = DB::table('cinema_movie_quotas')
            ->leftJoin('supervisors', 'cinema_movie_quotas.supervisor_id', '=', 'supervisors.supervisor_id')
            ->where('cinema_movie_quotas.movie_id', $statusRecord->movie_id)
            ->where('cinema_movie_quotas.cinema_id', $statusRecord->cinema_id)
            ->select('supervisors.supervisor_name')
            ->first();

        $supervisorName = $supervisorRow?->supervisor_name ?? 'Admin';
        $movieName = $movie?->movie_name ?? 'Movie';

        $notiMessage = '"' . $movieName . '" proposal rejected by ' .
            $supervisorName . ': ' . $request->admin_note;

        DB::table('manager_notifications')->insert([
            'manager_id' => $statusRecord->manager_id,
            'noti_picture' => $movie?->portrait_poster,
            'noti_message' => $notiMessage,
            'tag' => 'Movie Rejection By Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.proposals.index')
            ->with('success', 'Proposal rejected. Note sent to branch manager.');
    }
}
