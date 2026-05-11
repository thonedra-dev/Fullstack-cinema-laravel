<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BranchManagerNotificationController extends Controller
{
    /**
     * List all notifications for the authenticated branch manager.
     * GET /manager/notifications
     */
    public function index()
    {
        if (!session('bm_manager_id')) {
            return redirect()->route('manager.login');
        }

        $managerId = (int) session('bm_manager_id');

        $notifications = DB::table('manager_notifications')
            ->leftJoin('movies', 'manager_notifications.noti_picture', '=', 'movies.portrait_poster')
            ->where('manager_notifications.manager_id', $managerId)
            ->select('manager_notifications.*', 'movies.movie_id')
            ->orderBy('manager_notifications.created_at', 'desc')
            ->get();
            
        // Mark all unread as read now that the manager is viewing the page
        DB::table('manager_notifications')
            ->where('manager_id', $managerId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return view('branch_manager.branch_manager_noti', compact('notifications'));
    }
}