/**
 * view_cinema.js
 * Place at: resources/js/view_cinema.js
 *
 * Responsibilities:
 *   1. Card grid → detail view switching
 *   2. Back button (detail → grid)
 *   3. Client-side card search/filter
 */

(function () {
    'use strict';

    /* ================================================================
       1. CARD ↔ DETAIL VIEW SWITCHING
    ================================================================ */
    function initDetailView() {
        var gridView   = document.getElementById('vc-grid-view');
        var detailView = document.getElementById('vc-detail-view');
        var backBtn    = document.getElementById('vc-back-btn');

        if (!gridView || !detailView || !backBtn) return;

        // Delegate clicks on every .vc-card inside the grid
        var cardGrid = document.getElementById('vc-cinema-grid');
        if (!cardGrid) return;

        cardGrid.addEventListener('click', function (e) {
            var card = e.target.closest('.vc-card');
            if (!card) return;
            openDetail(card.dataset.cinemaId);
        });

        // Keyboard: Enter or Space on a focused card
        cardGrid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.vc-card');
            if (!card) return;
            e.preventDefault();
            openDetail(card.dataset.cinemaId);
        });

        backBtn.addEventListener('click', closeDetail);

        function openDetail(cinemaId) {
            // Hide all detail panels first
            detailView.querySelectorAll('.vc-detail').forEach(function (panel) {
                panel.classList.add('vc-hidden');
            });

            var target = document.getElementById('vc-detail-' + cinemaId);
            if (!target) return;

            target.classList.remove('vc-hidden');

            gridView.classList.add('vc-hidden');
            detailView.classList.remove('vc-hidden');

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function closeDetail() {
            detailView.classList.add('vc-hidden');
            gridView.classList.remove('vc-hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    /* ================================================================
       2. CLIENT-SIDE CARD SEARCH
    ================================================================ */
    function initCardSearch() {
        var searchInput = document.getElementById('cinema_card_search');
        var cardGrid    = document.getElementById('vc-cinema-grid');

        if (!searchInput || !cardGrid) return;

        searchInput.addEventListener('input', function () {
            var query = this.value.toLowerCase().trim();
            var cards = cardGrid.querySelectorAll('.vc-card');

            cards.forEach(function (card) {
                var text = card.textContent.toLowerCase();
                card.style.display = (!query || text.includes(query)) ? '' : 'none';
            });
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initDetailView();
        initCardSearch();
    });

})();