/**
 * admin_team.js
 * Place at: resources/js/admin_team.js
 */

(function () {
    'use strict';

    /* ================================================================
       1. DESKTOP COLLAPSIBLE SIDEBAR (via border toggle)
    ================================================================ */
    function initDesktopSidebar() {
        const app = document.querySelector('.at-app');
        const borderToggle = document.getElementById('borderToggleBtn');

        if (!app || !borderToggle) return;

        // Load saved state from localStorage (default: collapsed)
        const savedState = localStorage.getItem('admin_sidebar_state');
        if (savedState === 'expanded') {
            app.setAttribute('data-sidebar-state', 'expanded');
        } else {
            app.setAttribute('data-sidebar-state', 'collapsed');
        }

        // Update icon based on state
        function updateToggleIcon() {
            const icon = borderToggle.querySelector('i');
            if (!icon) return;
            const state = app.getAttribute('data-sidebar-state');
            if (state === 'expanded') {
                icon.className = 'fas fa-angle-double-right';
            } else {
                icon.className = 'fas fa-angle-double-left';
            }
        }

        // Toggle function
        function toggleSidebar() {
            const currentState = app.getAttribute('data-sidebar-state');
            const newState = currentState === 'collapsed' ? 'expanded' : 'collapsed';
            app.setAttribute('data-sidebar-state', newState);
            localStorage.setItem('admin_sidebar_state', newState);
            updateToggleIcon();
        }

        borderToggle.addEventListener('click', toggleSidebar);
        updateToggleIcon();
    }

    /* ================================================================
       2. MOBILE SIDEBAR TOGGLE (topbar hamburger)
    ================================================================ */
    function initMobileSidebar() {
        const mobileToggle = document.getElementById('at-sidebar-toggle');
        const app = document.querySelector('.at-app');

        if (!mobileToggle || !app) return;

        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isExpanded = app.getAttribute('data-sidebar-state') === 'expanded';
            if (isExpanded) {
                app.setAttribute('data-sidebar-state', 'collapsed');
                localStorage.setItem('admin_sidebar_state', 'collapsed');
            } else {
                app.setAttribute('data-sidebar-state', 'expanded');
                localStorage.setItem('admin_sidebar_state', 'expanded');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            const isMobile = window.innerWidth <= 900;
            if (!isMobile) return;

            const sidebar = document.getElementById('at-sidebar');
            const isExpanded = app.getAttribute('data-sidebar-state') === 'expanded';
            
            if (isExpanded && sidebar && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                app.setAttribute('data-sidebar-state', 'collapsed');
                localStorage.setItem('admin_sidebar_state', 'collapsed');
            }
        });
    }

    /* ================================================================
       3. AUTO-DISMISS FLASH ALERTS
    ================================================================ */
    function initAlertDismiss() {
        const alerts = document.querySelectorAll('.at-alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease';
                alert.style.opacity = '0';
                setTimeout(function () {
                    if (alert.parentNode) alert.remove();
                }, 420);
            }, 4000);
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initDesktopSidebar();
        initMobileSidebar();
        initAlertDismiss();
    });

})();