// resources/js/admin_movie_live_detail.js
// Entry point only. Wires:
//   navigation.js  -> reports sidebar state + showtime clicks
//   demo-section.js -> orchestrates what #mldSeatArea shows, using:
//     info-card-view.js  (cinema/theatre cards)
//     seat-view.js         (seat map)
//     finance-browser.js  (L1-L4 rollup ladder, delegates L5 to finance-table.js)
// Keep this file thin; real logic lives in resources/js/movie-live/*.

import { initNavigation } from './movie-live/navigation.js';
import { initInfoCardView } from './movie-live/info-card-view.js';
import { initSeatView } from './movie-live/seat-view.js';
import { initFinanceBrowser } from './movie-live/finance-browser.js';
import { initDemoSection } from './movie-live/demo-section.js';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mldRoot');
    if (!root) return;

    const infoCardView   = initInfoCardView();
    const seatView       = initSeatView(root);
    const financeBrowser = initFinanceBrowser(root);

    const demoSection = initDemoSection(root, { infoCardView, seatView, financeBrowser });

    initNavigation(root, {
        onStateChange: (snapshot) => demoSection.onNavStateChange(snapshot),
        onShowtimeSelected: (showtimeId) => demoSection.onShowtimeSelected(showtimeId),
    });
});