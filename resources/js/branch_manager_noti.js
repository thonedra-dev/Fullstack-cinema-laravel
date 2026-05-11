/**
 * resources/js/branch_manager_noti.js
 * ──────────────────────────────────────────────
 * Handles navigation clicking on notification cards
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const notiCards = document.querySelectorAll('.bmn-card[data-movie-id]');

        notiCards.forEach(card => {
            // Style card as pointer to indicate clickability
            card.style.cursor = 'pointer';

            card.addEventListener('click', function (e) {
                // Prevent navigation if the user is clicking on an active button or link inside the card (if any exist)
                if (e.target.closest('a') || e.target.closest('button')) {
                    return;
                }

                const movieId = this.dataset.movieId;
                const tag = this.dataset.tag;

                if (!movieId) return;

                let targetUrl = '';

                if (tag === 'Showtime Approved') {
                    // Navigate to movie details/exploration page
                    targetUrl = `/manager/movie/${movieId}`;
                } else if (tag === 'Movie Assigned' || tag === 'Showtime Rejected') {
                    // Navigate to setup/resubmit page
                    targetUrl = `/manager/setup/movie/${movieId}`;
                }

                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            });
        });
    });
})();