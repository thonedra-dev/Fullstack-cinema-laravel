/**
 * resources/js/select_seats.js
 *
 * Responsibilities:
 *  1. Seat toggle + bottom bar update
 *  2. Restore previously selected seats from sessionStorage (after login redirect)
 *  3. Auth gate: if not signed in, save selections to sessionStorage and
 *     redirect to login; on return, auto-restore selections
 *  4. On "Proceed": inject seat_ids into hidden form and submit → BookingController
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ss_saved_seats'; // sessionStorage key

    document.addEventListener('DOMContentLoaded', function () {

        /* ── Context from body data-attrs ──────────────────── */
        var body        = document.body;
        var isAuth      = body.dataset.auth === 'true';
        var loginUrl    = body.dataset.loginUrl;
        var currentUrl  = body.dataset.currentUrl;
        var cartUrl     = body.dataset.cartUrl;

        /* ── DOM refs ───────────────────────────────────────── */
        var countEl   = document.getElementById('ss-selected-count');
        var listEl    = document.getElementById('ss-selected-list');
        var btnNext   = document.getElementById('ss-btn-next');
        var form      = document.getElementById('ss-booking-form');
        var authModal = document.getElementById('ss-auth-modal');
        var authConfirm = document.getElementById('ss-auth-confirm');
        var authCancel  = document.getElementById('ss-auth-cancel');

        /*
         * selectedSeats: array of { label: "A3", seatId: 42 }
         */
        var selectedSeats = [];

        /* ── Restore saved seats after login redirect ─────── */
        var saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null'); } catch (e) {}

        if (saved && Array.isArray(saved) && isAuth) {
            // Reselect seats by seatId match
            saved.forEach(function (item) {
                var el = document.querySelector(
                    '.ss-seat--available[data-seat-id="' + item.seatId + '"]'
                );
                if (el) {
                    el.classList.add('selected');
                    selectedSeats.push({ label: item.label, seatId: item.seatId });
                }
            });
            sessionStorage.removeItem(STORAGE_KEY);
            updateBar();
        }

        /* ── Seat click handler ─────────────────────────────── */
        document.querySelectorAll('.ss-seat--available').forEach(function (seat) {
            seat.addEventListener('click', function () {
                var label  = this.dataset.seat;
                var seatId = parseInt(this.dataset.seatId, 10);
                if (!label || !seatId) return;

                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(function (s) {
                        return s.seatId !== seatId;
                    });
                } else {
                    this.classList.add('selected');
                    selectedSeats.push({ label: label, seatId: seatId });
                }

                updateBar();
            });
        });

        /* ── Bottom bar ─────────────────────────────────────── */
        function updateBar() {
            selectedSeats.sort(function (a, b) {
                return a.label.localeCompare(b.label);
            });
            var count = selectedSeats.length;
            if (countEl) countEl.textContent = count;
            if (listEl)  listEl.textContent  = count > 0
                ? selectedSeats.map(function (s) { return s.label; }).join(', ')
                : '—';
            if (btnNext) btnNext.disabled = count === 0;
        }

        updateBar();

        /* ── Proceed button ─────────────────────────────────── */
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (selectedSeats.length === 0) return;

                if (!isAuth) {
                    /* Show auth modal */
                    if (authModal) authModal.style.display = 'flex';
                    return;
                }

                submitBooking();
            });
        }

        /* ── Auth modal actions ─────────────────────────────── */
        if (authConfirm) {
            authConfirm.addEventListener('click', function () {
                /* Save selection to sessionStorage before leaving */
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(selectedSeats));
                /* Redirect to login with return URL */
                window.location.href = loginUrl
                    + '?redirect_url=' + encodeURIComponent(currentUrl);
            });
        }

        if (authCancel) {
            authCancel.addEventListener('click', function () {
                if (authModal) authModal.style.display = 'none';
            });
        }

        /* ── Submit hidden form ─────────────────────────────── */
        function submitBooking() {
            if (!form) return;

            /* Remove any previously injected seat_ids inputs */
            form.querySelectorAll('input[name="seat_ids[]"]').forEach(function (el) {
                el.parentNode.removeChild(el);
            });

            /* Inject one hidden input per seat_id */
            selectedSeats.forEach(function (s) {
                var input   = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'seat_ids[]';
                input.value = s.seatId;
                form.appendChild(input);
            });

            form.submit();
        }

    }); // end DOMContentLoaded

})();