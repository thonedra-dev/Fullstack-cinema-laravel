/**
 * resources/js/movie_creation.js
 *
 * Cinema assignment — multi-select batch flow:
 *
 *   pendingSelection  = Set of cinema IDs the admin has clicked (blue ring,
 *                       not yet assigned).  Cleared after "Assign Quota" is
 *                       confirmed.
 *
 *   selectedCinemas   = Array of assignment objects already confirmed
 *                       (shows ✓ Assigned overlay, locked from re-selection).
 *
 * Flow:
 *   1. Click cinema card → toggle pendingSelection (skip if already assigned)
 *   2. "Select All"      → add all non-assigned cinemas to pendingSelection
 *   3. "Clear Selection" → empty pendingSelection
 *   4. "Assign Quota"    → open modal; strip shows every cinema in pendingSelection
 *   5. Confirm modal     → create one assignment per pending cinema, clear pendingSelection
 *   6. Summary cards each have a "✕ Remove" button for individual deletion
 *   7. "Clear All"       → wipe selectedCinemas entirely
 *   8. Slots: unlimited (min 1, no max)
 */

(function () {
    'use strict';

    /* ── State ─────────────────────────────────────────────── */
    var selectedCinemas  = [];   // confirmed assignments
    var pendingSelection = [];   // cinema IDs currently ticked but not yet assigned

    /* ── Helpers ───────────────────────────────────────────── */
    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden'); }

    function switchView(id) {
        ['mc-form-view', 'mc-cinema-select-view'].forEach(function (v) {
            var el = document.getElementById(v);
            if (!el) return;
            el.id === id ? show(el) : hide(el);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function findAssignment(cinemaId) {
        return selectedCinemas.find(function (c) {
            return String(c.cinemaId) === String(cinemaId);
        });
    }

    function isAssigned(cinemaId) {
        return !!findAssignment(cinemaId);
    }

    function isPending(cinemaId) {
        return pendingSelection.indexOf(String(cinemaId)) !== -1;
    }

    function formatDate(iso) {
        if (!iso) return '—';
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function cinemaDataById(id) {
        return (window.MC_CINEMAS || []).find(function (c) {
            return String(c.id) === String(id);
        }) || {};
    }

    /* ================================================================
       1. GENRE CHIP TOGGLE
    ================================================================ */
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

    /* ================================================================
       2. POSTER FILE UPLOAD PREVIEWS
    ================================================================ */
    function initFilePreview(inputId, previewId) {
        var el = document.getElementById(inputId);
        var pv = document.getElementById(previewId);
        if (!el || !pv) return;
        el.addEventListener('change', function () {
            pv.textContent = (this.files && this.files.length > 0)
                ? '📎 ' + this.files[0].name : '';
        });
    }

    /* ================================================================
       3. VIEW SWITCHING
    ================================================================ */
    function initViewSwitching() {
        var openBtn = document.getElementById('mc-select-cinemas-btn');
        var backBtn = document.getElementById('mc-select-back');
        var doneBtn = document.getElementById('mc-done-selecting');

        if (openBtn) openBtn.addEventListener('click', function () {
            switchView('mc-cinema-select-view');
        });
        if (backBtn) backBtn.addEventListener('click', function () {
            // Discard any uncommitted selection when going back
            pendingSelection = [];
            refreshCardVisuals();
            refreshSelectionBar();
            switchView('mc-form-view');
        });
        if (doneBtn) doneBtn.addEventListener('click', function () {
            pendingSelection = [];
            refreshCardVisuals();
            refreshSelectionBar();
            switchView('mc-form-view');
            renderSummary();
        });
    }

    /* ================================================================
       4. CINEMA CARD TOGGLE (click = select/deselect)
          Assigned cards are non-interactive.
    ================================================================ */
    function initCinemaCards() {
        var grid = document.getElementById('mc-cinema-grid');
        if (!grid) return;

        grid.addEventListener('click', function (e) {
            var card = e.target.closest('.mc-cinema-card');
            if (!card) return;
            var id = card.dataset.cinemaId;

            if (isAssigned(id)) return;   // locked — already confirmed

            if (isPending(id)) {
                // Deselect
                pendingSelection = pendingSelection.filter(function (x) { return x !== String(id); });
            } else {
                // Select
                pendingSelection.push(String(id));
            }

            refreshCardVisuals();
            refreshSelectionBar();
        });

        grid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.mc-cinema-card');
            if (!card) return;
            e.preventDefault();
            card.click();
        });
    }

    /* ================================================================
       5. SELECT ALL / CLEAR SELECTION
    ================================================================ */
    function initSelectionBarButtons() {
        var selectAllBtn   = document.getElementById('mc-select-all-btn');
        var clearSelBtn    = document.getElementById('mc-clear-selection-btn');
        var assignQuotaBtn = document.getElementById('mc-assign-quota-btn');

        if (selectAllBtn) selectAllBtn.addEventListener('click', function () {
            // Add every non-assigned cinema to pendingSelection
            (window.MC_CINEMAS || []).forEach(function (c) {
                if (!isAssigned(c.id) && !isPending(c.id)) {
                    pendingSelection.push(String(c.id));
                }
            });
            refreshCardVisuals();
            refreshSelectionBar();
        });

        if (clearSelBtn) clearSelBtn.addEventListener('click', function () {
            pendingSelection = [];
            refreshCardVisuals();
            refreshSelectionBar();
        });

        if (assignQuotaBtn) assignQuotaBtn.addEventListener('click', function () {
            if (pendingSelection.length === 0) return;
            openModal();
        });
    }

    /* ================================================================
       6. MODAL — batch quota assignment
    ================================================================ */
    function initModal() {
        var modal      = document.getElementById('mc-quota-modal');
        var confirmBtn = document.getElementById('mc-quota-confirm');
        var cancelBtn  = document.getElementById('mc-quota-cancel');

        if (!modal) return;

        confirmBtn.addEventListener('click', confirmAssignment);
        cancelBtn.addEventListener('click',  closeModal);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('vc-hidden')) closeModal();
        });
    }

    function openModal() {
        var strip   = document.getElementById('mc-modal-strip');
        var subText = document.getElementById('mc-modal-sub');

        // Build horizontal scrollable cinema pill strip
        strip.innerHTML = '';
        pendingSelection.forEach(function (id) {
            var c   = cinemaDataById(id);
            var pill = document.createElement('div');
            pill.className = 'mc-modal__cinema-pill';

            if (c.img) {
                var img = document.createElement('img');
                img.src = c.img; img.alt = c.name || '';
                img.className = 'mc-modal__pill-img';
                pill.appendChild(img);
            } else {
                var ph = document.createElement('div');
                ph.className   = 'mc-modal__pill-ph';
                ph.textContent = '🎬';
                pill.appendChild(ph);
            }

            var info = document.createElement('div');
            info.className = 'mc-modal__pill-info';
            info.innerHTML =
                '<p class="mc-modal__pill-name">' + (c.name  || '—') + '</p>' +
                '<p class="mc-modal__pill-loc">'  + (c.city  || '—') + ', ' + (c.state || '—') + '</p>';
            pill.appendChild(info);
            strip.appendChild(pill);
        });

        // Sub-label
        var count = pendingSelection.length;
        if (subText) {
            subText.textContent = 'This quota plan will be applied to ' +
                count + ' cinema' + (count !== 1 ? 's' : '') + '.';
        }

        // Reset fields
        document.getElementById('mc-start-date').value = '';
        document.getElementById('mc-end-date').value   = '';
        document.getElementById('mc-slots').value      = '';
        ['mc-start-err', 'mc-end-err', 'mc-slots-err'].forEach(function (id) {
            hide(document.getElementById(id));
        });

        show(document.getElementById('mc-quota-modal'));
    }

    function confirmAssignment() {
        var startDate = document.getElementById('mc-start-date').value;
        var endDate   = document.getElementById('mc-end-date').value;
        var slots     = parseInt(document.getElementById('mc-slots').value, 10);
        var valid     = true;

        ['mc-start-err', 'mc-end-err', 'mc-slots-err'].forEach(function (id) {
            hide(document.getElementById(id));
        });

        if (!startDate) {
            show(document.getElementById('mc-start-err'));
            valid = false;
        }
        if (!endDate || (startDate && endDate <= startDate)) {
            var errEl = document.getElementById('mc-end-err');
            errEl.textContent = !endDate ? 'Required.' : 'Must be after start date.';
            show(errEl);
            valid = false;
        }
        if (!slots || slots < 1) {
            show(document.getElementById('mc-slots-err'));
            valid = false;
        }
        if (!valid) return;

        // Create one assignment per pending cinema
        pendingSelection.forEach(function (id) {
            if (isAssigned(id)) return;   // guard against duplicates
            var c = cinemaDataById(id);
            selectedCinemas.push({
                cinemaId:  id,
                name:      c.name  || '—',
                img:       c.img   || '',
                city:      c.city  || '—',
                state:     c.state || '—',
                startDate: startDate,
                endDate:   endDate,
                slots:     slots,
            });
        });

        // Clear pending selection
        pendingSelection = [];

        closeModal();
        syncHidden();
        refreshCardVisuals();
        refreshSelectionBar();
        updateCountBadge();
    }

    function closeModal() {
        hide(document.getElementById('mc-quota-modal'));
    }

    /* ================================================================
       7. VISUAL REFRESH — card overlays + selection bar state
    ================================================================ */
    function refreshCardVisuals() {
        document.querySelectorAll('.mc-cinema-card').forEach(function (card) {
            var id          = card.dataset.cinemaId;
            var assignedOl  = card.querySelector('.mc-assigned-overlay');
            var pendingOl   = card.querySelector('.mc-pending-overlay');
            var assigned    = isAssigned(id);
            var pending     = isPending(id);

            // Assigned overlay (permanent lock)
            if (assignedOl) assigned ? show(assignedOl) : hide(assignedOl);
            card.classList.toggle('mc-card--assigned', assigned);

            // Pending selection overlay (temporary highlight)
            if (pendingOl)  pending  ? show(pendingOl)  : hide(pendingOl);
            card.classList.toggle('mc-card--pending', pending);
        });
    }

    function refreshSelectionBar() {
        var count    = pendingSelection.length;
        var countEl  = document.getElementById('mc-sel-count');
        var assignBtn= document.getElementById('mc-assign-quota-btn');

        if (countEl)  countEl.textContent   = count + ' selected';
        if (assignBtn) assignBtn.disabled   = count === 0;
    }

    function updateCountBadge() {
        var badge = document.getElementById('mc-count-badge');
        if (!badge) return;
        var n = selectedCinemas.length;
        badge.textContent = n + ' cinema' + (n !== 1 ? 's' : '') + ' assigned';
    }

    /* ================================================================
       8. INDIVIDUAL REMOVE + CLEAR ALL
    ================================================================ */
    function removeAssignmentById(cinemaId) {
        selectedCinemas = selectedCinemas.filter(function (c) {
            return String(c.cinemaId) !== String(cinemaId);
        });
        syncHidden();
        refreshCardVisuals();
        updateCountBadge();
        renderSummary();
    }

    function clearAllAssignments() {
        if (!confirm('Remove all cinema assignments?')) return;
        selectedCinemas = [];
        syncHidden();
        refreshCardVisuals();
        updateCountBadge();
        renderSummary();
    }

    /* ================================================================
       9. SUMMARY IN MAIN FORM
    ================================================================ */
    function renderSummary() {
        var summary   = document.getElementById('mc-assigned-summary');
        var noMsg     = document.getElementById('mc-no-cinemas');
        var chipsRow  = document.getElementById('mc-chips-row');
        var cardsWrap = document.getElementById('mc-cards-expanded');
        var toggleBtn = document.getElementById('mc-toggle-expand');
        var clearAll  = document.getElementById('mc-clear-all-btn');
        var selectBtn = document.getElementById('mc-select-cinemas-btn');

        if (selectedCinemas.length === 0) {
            hide(summary);
            show(noMsg);
            if (selectBtn) selectBtn.textContent = '🏢 Select Cinemas';
            return;
        }

        show(summary);
        hide(noMsg);
        if (selectBtn) selectBtn.textContent = '✎ Edit Assignments';

        // Chips row
        chipsRow.innerHTML = '';
        selectedCinemas.forEach(function (c) {
            var chip = document.createElement('span');
            chip.className   = 'mc-chip';
            chip.textContent = c.name;
            chipsRow.appendChild(chip);
        });

        // Expandable detail cards with individual remove button
        cardsWrap.innerHTML = '';
        selectedCinemas.forEach(function (c) {
            var card = document.createElement('div');
            card.className = 'mc-assignment-card';
            card.innerHTML =
                '<div class="mc-assignment-card__img-wrap">' +
                    (c.img
                        ? '<img src="' + c.img + '" alt="' + c.name + '" class="mc-assignment-card__img">'
                        : '<div class="mc-assignment-card__img-ph">🎬</div>') +
                '</div>' +
                '<div class="mc-assignment-card__body">' +
                    '<div class="mc-assignment-card__name-row">' +
                        '<p class="mc-assignment-card__name">' + c.name + '</p>' +
                        '<button type="button" class="mc-assignment-card__remove" ' +
                            'data-id="' + c.cinemaId + '" title="Remove assignment">✕</button>' +
                    '</div>' +
                    '<p class="mc-assignment-card__loc">' + c.city + ', ' + c.state + '</p>' +
                    '<div class="mc-assignment-card__meta">' +
                        '<div class="mc-assignment-card__row">' +
                            '<span class="mc-assignment-card__label">Start</span>' +
                            '<span class="mc-assignment-card__val">' + formatDate(c.startDate) + '</span>' +
                        '</div>' +
                        '<div class="mc-assignment-card__row">' +
                            '<span class="mc-assignment-card__label">End</span>' +
                            '<span class="mc-assignment-card__val">' + formatDate(c.endDate) + '</span>' +
                        '</div>' +
                        '<div class="mc-assignment-card__row">' +
                            '<span class="mc-assignment-card__label">Slots</span>' +
                            '<span class="mc-assignment-card__val">' + c.slots + ' / day</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Wire individual remove button
            var rmBtn = card.querySelector('.mc-assignment-card__remove');
            if (rmBtn) {
                rmBtn.addEventListener('click', function () {
                    removeAssignmentById(this.dataset.id);
                });
            }

            cardsWrap.appendChild(card);
        });

        // Toggle expand/collapse
        if (toggleBtn) {
            toggleBtn.onclick = function () {
                var isOpen = !cardsWrap.classList.contains('vc-hidden');
                isOpen ? hide(cardsWrap) : show(cardsWrap);
                toggleBtn.textContent = isOpen ? '▾ Show details' : '▴ Hide details';
            };
        }

        // Clear all button
        if (clearAll) {
            clearAll.onclick = clearAllAssignments;
        }
    }

    /* ================================================================
       10. SERIALIZE / RESTORE
    ================================================================ */
    function syncHidden() {
        var input = document.getElementById('mc-cinemas-json');
        if (input) input.value = JSON.stringify(selectedCinemas);
    }

    function restoreFromOld() {
        var raw = window.MC_CINEMAS_OLD || '[]';
        var parsed;
        try { parsed = JSON.parse(typeof raw === 'string' ? raw : JSON.stringify(raw)); }
        catch (e) { return; }
        if (!Array.isArray(parsed) || parsed.length === 0) return;
        selectedCinemas = parsed;
        refreshCardVisuals();
        updateCountBadge();
        renderSummary();
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initGenreChips();
        initFilePreview('landscape_poster', 'landscape_preview');
        initFilePreview('portrait_poster',  'portrait_preview');
        initViewSwitching();
        initCinemaCards();
        initSelectionBarButtons();
        initModal();
        restoreFromOld();
    });

})();