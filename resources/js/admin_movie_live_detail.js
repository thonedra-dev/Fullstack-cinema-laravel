// resources/js/admin_movie_live_detail.js
// Entry point only — wires navigation.js (sidebar drill-down) to
// info-card-view.js (cinema/theatre demo cards) and seat-finance-view.js
// (seat map + financial report). Keep this file thin; real logic lives
// in resources/js/movie-live/*.

import { initNavigation } from './movie-live/navigation.js';
import { initInfoCardView } from './movie-live/info-card-view.js';
import { initSeatFinanceView } from './movie-live/seat-finance-view.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mldRoot');
    if (!root) return;

    const infoCardView    = initInfoCardView(root);
    const seatFinanceView = initSeatFinanceView(root);

    initNavigation(root, {
        infoCardView,
        onShowtimeSelected: (showtimeId, meta) => seatFinanceView.showShowtime(showtimeId, meta),
    });
});