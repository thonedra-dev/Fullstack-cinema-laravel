/**
 * view_cinema.js
 * Place at: resources/js/view_cinema.js
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
        var cardGrid   = document.getElementById('vc-cinema-grid');

        if (!gridView || !detailView || !backBtn || !cardGrid) return;

        cardGrid.addEventListener('click', function (e) {
            var card = e.target.closest('.vc-card');
            if (!card) return;
            openDetail(card.dataset.cinemaId);
        });

        cardGrid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.vc-card');
            if (!card) return;
            e.preventDefault();
            openDetail(card.dataset.cinemaId);
        });

        backBtn.addEventListener('click', closeDetail);

        function openDetail(cinemaId) {
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
            cardGrid.querySelectorAll('.vc-card').forEach(function (card) {
                var text = card.textContent.toLowerCase();
                card.style.display = (!query || text.includes(query)) ? '' : 'none';
            });
        });
    }

    /* ================================================================
       3. ASSIGN THEATRE MODAL
    ================================================================ */
    function initAssignTheatreModals() {

        // ── Open ──────────────────────────────────────────────────
        document.querySelectorAll('.vc-assign-theatre-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cinemaId = this.dataset.cinemaId;
                var modal    = document.getElementById('vc-assign-modal-' + cinemaId);
                if (!modal) return;
                modal.classList.remove('vc-hidden');
                document.body.style.overflow = 'hidden';   // prevent background scroll
            });
        });

        // ── Close (button or backdrop) ────────────────────────────
        document.querySelectorAll('.vc-assign-modal-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(this.dataset.cinemaId);
            });
        });

        document.querySelectorAll('.vc-modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                // Only close when clicking the dark backdrop, not the modal card itself
                if (e.target === overlay) {
                    var cinemaId = overlay.id.replace('vc-assign-modal-', '');
                    closeModal(cinemaId);
                }
            });
        });

        // ── Theatre card visual toggle inside the modal ───────────
        document.querySelectorAll('.vc-modal__theatre-card').forEach(function (card) {
            var cb = card.querySelector('.vc-modal__checkbox');
            if (!cb) return;

            card.addEventListener('click', function () {
                setTimeout(function () {
                    card.classList.toggle('is-checked', cb.checked);
                }, 0);
            });
        });

        // ── Escape key closes any open modal ─────────────────────
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.vc-modal-overlay:not(.vc-hidden)').forEach(function (overlay) {
                var cinemaId = overlay.id.replace('vc-assign-modal-', '');
                closeModal(cinemaId);
            });
        });

        function closeModal(cinemaId) {
            var modal = document.getElementById('vc-assign-modal-' + cinemaId);
            if (!modal) return;
            modal.classList.add('vc-hidden');
            document.body.style.overflow = '';
        }
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initDetailView();
        initCardSearch();
        initAssignTheatreModals();
    });

})();