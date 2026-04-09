/**
 * resources/js/select_seats.js
 * Handles seat toggling and updating the checkout bar.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var countEl    = document.getElementById('ss-selected-count');
        var listEl     = document.getElementById('ss-selected-list');
        var btnNext    = document.getElementById('ss-btn-next');
        var selectedSeats = [];

        /* Attach click to every available seat */
        document.querySelectorAll('.ss-seat--available').forEach(function (seat) {
            seat.addEventListener('click', function () {
                var id = this.dataset.seat;
                if (!id) return;

                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(function (s) { return s !== id; });
                } else {
                    this.classList.add('selected');
                    selectedSeats.push(id);
                }

                updateBar();
            });
        });

        function updateBar() {
            selectedSeats.sort();
            var count = selectedSeats.length;

            if (countEl) countEl.textContent = count;
            if (listEl)  listEl.textContent  = count > 0 ? selectedSeats.join(', ') : '—';
            if (btnNext) btnNext.disabled     = count === 0;
        }

        updateBar();
    });
})();