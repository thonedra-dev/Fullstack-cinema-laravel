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
    //  Anything in the app that needs to notify a manager should call one
    //  of the public "notifyX()" wrapper methods below — never insert into
    //  manager_notifications directly from another controller.
    //
    //  Adding a new reason to notify in the future = adding ONE small public
    //  wrapper method here that calls notify(). Nothing else in the app
    //  needs to know how manager_notifications rows are structured.
    // =====================================================================

    /**
     * Notify a manager that one of their running movies has expired
     * (its quota's maximum_end_date has passed) and was pulled from
     * Running Movies.
     *
     * Deduplicated — re-running this for the same movie won't spam a
     * second notification (this can be triggered on every Resources
     * page load, so dedup matters here).
     */
    public function notifyExpiredMovie(int $managerId, string $movieName, ?string $moviePoster): void
    {
        $this->notify(
            managerId: $managerId,
            tag: 'Movie Expired',
            message: "\"{$movieName}\" has passed its scheduled end date and was removed from Running Movies. You can find it under Expired Movies.",
            picture: $moviePoster,
            dedupe: true,
        );
    }

    /**
     * Notify a manager that admin approved their showtime proposal.
     * One-shot admin action — no dedup needed.
     */
    public function notifyShowtimeApproved(int $managerId, string $movieName, ?string $moviePoster, int $slotCount): void
    {
        $this->notify(
            managerId: $managerId,
            tag: 'Showtime Approved',
            message: "\"{$movieName}\" — {$slotCount} showtime(s) have been approved and are now live on the schedule.",
            picture: $moviePoster,
            dedupe: false,
        );
    }

    /**
     * Notify a manager that admin rejected their showtime proposal,
     * including the admin's note. One-shot admin action — no dedup needed.
     */
    public function notifyProposalRejected(int $managerId, string $movieName, ?string $moviePoster, string $supervisorName, string $adminNote): void
    {
        $this->notify(
            managerId: $managerId,
            tag: 'Movie Rejection By Admin',
            message: "\"{$movieName}\" proposal rejected by {$supervisorName}: {$adminNote}",
            picture: $moviePoster,
            dedupe: false,
        );
    }

    /**
     * Generic notification creator — the single place that actually
     * touches the manager_notifications table.
     *
     * @param  int     $managerId
     * @param  string  $tag       Short label shown on the card (e.g. "Movie Expired")
     * @param  string  $message   Full body text shown on the card
     * @param  string|null  $picture  Poster filename, used both for display AND as
     *                               the movie-linking key (matched against
     *                               movies.portrait_poster elsewhere) and as the
     *                               dedup key when $dedupe is true
     * @param  bool    $dedupe    If true, skips insert when an identical
     *                            (manager_id, tag, noti_picture) row already
     *                            exists. Use for reasons that can be triggered
     *                            repeatedly (e.g. a page-load check). Leave
     *                            false for one-shot events (admin approve/reject),
     *                            since those should always produce a fresh entry.
     * @return void
     */
    private function notify(int $managerId, string $tag, string $message, ?string $picture, bool $dedupe = true): void
    {
        if (!$managerId) {
            return;
        }

        if ($dedupe) {
            $alreadyNotified = DB::table('manager_notifications')
                ->where('manager_id', $managerId)
                ->where('tag', $tag)
                ->where('noti_picture', $picture)
                ->exists();

            if ($alreadyNotified) {
                return;
            }
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