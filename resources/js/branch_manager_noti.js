/**
 * resources/js/branch_manager_noti.js
 * ──────────────────────────────────────────────
 * Handles click navigation on notification cards.
 *
 * TAG → ACTION MAP:
 *
 *   "Showtime Approved"
 *     → always navigate to /manager/movie/:id  (movie formation page)
 *
 *   "Movie Assigned"
 *     → if proposal_status is empty (not yet submitted)
 *         → navigate to /manager/setup/movie/:id
 *     → if proposal_status is set (pending / approved / rejected)
 *         → show inline "already submitted" message; do NOT navigate
 *           (user should use the rejection card to resubmit if needed)
 *
 *   "Movie Rejection By Admin"
 *     → always navigate to /manager/setup/movie/:id
 *       (setup page handles the replace-rejected flow internally)
 *
 *   "Movie Expired"
 *     → always navigate to /manager/movie/:id  (view movie details;
 *       raised by BranchManagerResourceController when a running movie
 *       passes its maximum_end_date and is pulled from Running Movies)
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var notiCards = document.querySelectorAll('.bmn-card[data-movie-id]');

        notiCards.forEach(function (card) {
            card.style.cursor = 'pointer';

            card.addEventListener('click', function (e) {
                // Don't hijack clicks on real links or buttons inside the card
                if (e.target.closest('a') || e.target.closest('button')) return;

                var movieId        = this.dataset.movieId;
                var tag            = this.dataset.tag;
                var proposalStatus = this.dataset.proposalStatus; // '' | 'pending' | 'approved' | 'rejected'

                if (!movieId) return;

                /* ── Showtime Approved ─────────────────────────────── */
                if (tag === 'Showtime Approved') {
                    window.location.href = '/manager/movie/' + movieId;
                    return;
                }

                /* ── Movie Expired ─────────────────────────────────── */
                if (tag === 'Movie Expired') {
                    window.location.href = '/manager/movie/' + movieId;
                    return;
                }

                /* ── Movie Rejection By Admin ─────────────────────── */
                if (tag === 'Movie Rejection By Admin') {
                    // Always let the manager go back to setup to resubmit.
                    // The setup_timetable JS will detect the existing rejected
                    // proposal and prompt before replacing it.
                    window.location.href = '/manager/setup/movie/' + movieId;
                    return;
                }

                /* ── Movie Assigned ───────────────────────────────── */
                if (tag === 'Movie Assigned') {
                    if (proposalStatus === 'pending') {
    // Only pending blocks navigation — show click message
    var msgEl = card.querySelector('.bmn-card__already-submitted');
    if (msgEl) {
        msgEl.style.display = 'block';
        setTimeout(function () { msgEl.style.display = 'none'; }, 4000);
    }
} else if (proposalStatus === 'approved') {
    // Already approved — go see the movie's schedule
    window.location.href = '/manager/movie/' + movieId;
} else {
    // null (not submitted yet) OR rejected — go to setup
    window.location.href = '/manager/setup/movie/' + movieId;
}
                    return;
                }
            });
        });
    });
})();