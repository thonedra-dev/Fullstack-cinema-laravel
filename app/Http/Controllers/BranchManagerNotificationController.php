<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;    

class BranchManagerNotificationController extends Controller
{
    /**
     * List all notifications for the authenticated branch manager.
     * GET /manager/notifications
     *
     * Each notification gets a `proposal_status` property attached so the
     * blade and JS can decide:
     *   - "Movie Assigned" card  → clickable only if proposal_status is null
     *                              (manager hasn't submitted yet)
     *   - "Movie Rejection By Admin" card → always clickable → setup page
     *   - "Showtime Approved" card → always clickable → movie formation page
     *
     * Both the "Movie Assigned" AND "Movie Rejection By Admin" cards for the
     * same movie get `proposal_status = 'rejected'` so the blade can tint
     * both cards bright red.
     */
    public function index()
    {
        if (!Auth::guard('manager')->check()) {
            return redirect()->route('manager.login');
        }

        $managerId = (int) session('bm_manager_id');

        $notifications = DB::table('manager_notifications')
            ->leftJoin('movies', 'manager_notifications.noti_picture', '=', 'movies.portrait_poster')
            ->where('manager_notifications.manager_id', $managerId)
            ->select('manager_notifications.*', 'movies.movie_id')
            ->orderBy('manager_notifications.created_at', 'desc')
            ->get();

        // ── Proposal status per movie for this manager ─────────────
        // keyed by movie_id so we can O(1) look up in the map below
        $proposalStatuses = DB::table('showtime_proposal_status')
            ->where('manager_id', $managerId)
            ->get(['movie_id', 'status'])
            ->keyBy('movie_id');

        // Attach proposal_status to every notification that has a movie_id
        $notifications = $notifications->map(function ($noti) use ($proposalStatuses) {
            $noti->proposal_status = $noti->movie_id
                ? ($proposalStatuses->get($noti->movie_id)?->status ?? null)
                : null;
            return $noti;
        });

        // Mark all unread as read now that the manager is viewing the page
        DB::table('manager_notifications')
            ->where('manager_id', $managerId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return view('branch_manager.branch_manager_noti', compact('notifications'));
    }
}