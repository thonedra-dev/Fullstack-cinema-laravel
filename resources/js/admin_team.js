/**
 * admin_team.js
 * Place at: resources/js/admin_team.js
 *
 * Responsibilities:
 *   1. Mobile sidebar toggle
 *   2. Auto-dismiss flash alerts
 */

(function () {
    'use strict';

    /* ================================================================
       1. SIDEBAR TOGGLE  (mobile / tablet)
    ================================================================ */
    function initSidebarToggle() {
        var toggle  = document.getElementById('at-sidebar-toggle');
        var sidebar = document.getElementById('at-sidebar');

        if (!toggle || !sidebar) return;

        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (
                sidebar.classList.contains('is-open') &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)
            ) {
                sidebar.classList.remove('is-open');
            }
        });
    }

    /* ================================================================
       2. AUTO-DISMISS FLASH ALERTS
    ================================================================ */
    function initAlertDismiss() {
        var alerts = document.querySelectorAll('.at-alert');

        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease';
                alert.style.opacity = '0';
                setTimeout(function () {
                    alert.remove();
                }, 420);
            }, 4000);
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initSidebarToggle();
        initAlertDismiss();
    });

})();