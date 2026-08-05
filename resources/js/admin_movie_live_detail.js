// resources/js/admin_movie_live_detail.js
// Entry point only — wires navigation.js (sidebar drill-down) to
// seat-finance-view.js (left panel) via a single callback. Keep this
// file thin; real logic lives in resources/js/movie-live/*.

import { initNavigation } from './movie-live/navigation.js';
import { initSeatFinanceView } from './movie-live/seat-finance-view.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mldRoot');
    if (!root) return;

    const seatFinanceView = initSeatFinanceView(root);

    initNavigation(root, {
        onShowtimeSelected: (showtimeId, meta) => seatFinanceView.showShowtime(showtimeId, meta),
    });
});