<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BranchManagerNotificationController extends Controller
{
    /**
     * Tags for which `proposal_status` is a meaningful concept.
     * Attaching it to every notification (e.g. "Movie Expired") makes
     * no sense, since expiry has nothing to do with proposal submission.
     */
    private const PROPOSAL_RELATED_TAGS = [
        'Movie Assigned',
        'Movie Rejection By Admin',
        'Showtime Approved',
    ];

    /**
     * List all notifications for the authenticated branch manager.
     * GET /manager/notifications
     *
     * Each notification gets a `proposal_status` property attached ONLY if
     * its tag is proposal-related, so the blade and JS can decide:
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

        $managerId = Auth::guard('manager')->id();

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

        // Attach proposal_status ONLY to notifications whose tag is
        // proposal-related. Other reasons (e.g. "Movie Expired") simply
        // don't carry this field at all.
        $notifications = $notifications->map(function ($noti) use ($proposalStatuses) {
            $isProposalRelated = in_array($noti->tag, self::PROPOSAL_RELATED_TAGS, true);

            $noti->proposal_status = ($isProposalRelated && $noti->movie_id)
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

    // =====================================================================
    //  NOTIFICATION CONSTRUCTION
    //  Single source of truth for creating manager_notifications rows.
    //  Any part of the app that needs to notify a manager should call one
    //  of these methods rather than inserting into the table directly.
    // =====================================================================

    /**
     * Notify a manager that one of their running movies has expired
     * (its quota's maximum_end_date has passed) and was pulled from
     * Running Movies.
     *
     * Deduplicated: won't insert a second "Movie Expired" notification
     * for the same movie poster if one already exists for this manager.
     *
     * @param  int     $managerId
     * @param  string  $movieName
     * @param  string|null  $moviePoster
     * @return void
     */
    public function notifyExpiredMovie(int $managerId, string $movieName, ?string $moviePoster): void
    {
        if (!$managerId) {
            return;
        }

        $this->createNotificationOnce(
            managerId: $managerId,
            tag: 'Movie Expired',
            message: "\"{$movieName}\" has passed its scheduled end date and was removed from Running Movies. You can find it under Expired Movies.",
            picture: $moviePoster,
        );
    }

    /**
     * Generic, deduplicated notification creator.
     *
     * Dedup key is (manager_id, tag, noti_picture) — same convention
     * already used for the existing proposal-related notifications,
     * which link a notification back to a movie via its poster filename.
     *
     * Add more public wrapper methods above (e.g. notifyX(), notifyY())
     * for each new reason to notify a manager, all funneling through here.
     *
     * @param  int     $managerId
     * @param  string  $tag
     * @param  string  $message
     * @param  string|null  $picture
     * @return void
     */
    private function createNotificationOnce(int $managerId, string $tag, string $message, ?string $picture): void
    {
        $alreadyNotified = DB::table('manager_notifications')
            ->where('manager_id', $managerId)
            ->where('tag', $tag)
            ->where('noti_picture', $picture)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        DB::table('manager_notifications')->insert([
            'manager_id'   => $managerId,
            'tag'          => $tag,
            'noti_message' => $message,
            'noti_picture' => $picture,
            'is_read'      => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}