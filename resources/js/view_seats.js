/**
 * resources/js/view_seats.js
 *
 * Staff Read-Only Seat Monitoring — JavaScript
 * Responsibilities:
 * 1. Tooltip on hover for pending seats (held by customer)
 * No seat selection, no form submission — read-only only.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ── Pending seat tooltip ───────────────────────────────── */
        var tooltip = document.getElementById('sm-tooltip');

        document.querySelectorAll('.sm-seat--pending').forEach(function (seat) {
            seat.addEventListener('mouseenter', function () {
                if (!tooltip) return;
                tooltip.textContent = 'Held by a customer · Expires soon';
                tooltip.style.display = 'block';
            });
            seat.addEventListener('mousemove', function (e) {
                if (!tooltip) return;
                tooltip.style.left = (e.clientX + 14) + 'px';
                tooltip.style.top  = (e.clientY - 36) + 'px';
            });
            seat.addEventListener('mouseleave', function () {
                if (tooltip) tooltip.style.display = 'none';
            });
        });

    });

})();