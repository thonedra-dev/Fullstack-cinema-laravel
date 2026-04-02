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
            ->where('manager_id', $managerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('branch_manager.branch_manager_noti', compact('notifications'));
    }
}