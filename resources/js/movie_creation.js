/**
 * movie_creation.js
 * Main orchestrator – genre chips, file previews, view switching,
 * and delegates cinema/pricing logic.
 */
(function () {
    'use strict';

    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden'); }

    /* ── Shared view switcher ────────────────────────────── */
    window.MC_ViewSwitcher = {
        switchView: function (viewId) {
            ['mc-form-view', 'mc-cinema-select-view'].forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.id === viewId ? show(el) : hide(el);
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    /* ── Genre chips ──────────────────────────────────────── */
    function initGenreChips() {
        document.querySelectorAll('.mc-genre-btn').forEach(function (label) {
            var cb = label.querySelector('input[type="checkbox"]');
            if (!cb) return;
            label.addEventListener('click', function () {
                setTimeout(function () {
                    label.classList.toggle('is-selected', cb.checked);
                }, 0);
            });
        });
    }

    /* ── File previews ───────────────────────────────────── */
    function initFilePreview(inputId, previewId) {
        var el = document.getElementById(inputId);
        var pv = document.getElementById(previewId);
        if (!el || !pv) return;
        el.addEventListener('change', function () {
            pv.textContent = (this.files && this.files.length > 0) ? '📎 ' + this.files[0].name : '';
        });
    }

    /* ── View switching button ───────────────────────────── */
    function initViewSwitching() {
        var openBtn = document.getElementById('mc-select-cinemas-btn');
        if (openBtn) {
            openBtn.addEventListener('click', function () {
                window.MC_ViewSwitcher.switchView('mc-cinema-select-view');
            });
        }
    }

    /* ── Form submit ──────────────────────────────────────── */
    function initFormSubmit() {
        var form = document.getElementById('mc-main-form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            if (window.MC_Quota && typeof window.MC_Quota.sync === 'function') {
                window.MC_Quota.sync();
            }

            if (window.MC_Quota && !window.MC_Quota.hasAssignments()) {
                e.preventDefault();
                alert('Assign at least one cinema before submitting.');
                return;
            }

            if (window.MC_Pricing && !window.MC_Pricing.sync(true)) {
                e.preventDefault();
                window.MC_ViewSwitcher.switchView('mc-form-view');
                return;
            }
        });
    }

    /* ── Initialisation ──────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        initGenreChips();
        initFilePreview('landscape_poster', 'landscape_preview');
        initFilePreview('portrait_poster', 'portrait_preview');
        initViewSwitching();

        if (window.MC_Quota && window.MC_Quota.init) window.MC_Quota.init();
        if (window.MC_Pricing && window.MC_Pricing.init) window.MC_Pricing.init();

        initFormSubmit();
    });

})();