/**
 * movie_creation.js
 * Place at: resources/js/movie_creation.js
 *
 * Responsibilities:
 *   1. Form ↔ Cinema-selection view switching
 *   2. Cinema card click → quota modal (new or edit)
 *   3. Quota modal — field validation and confirm
 *   4. Remove assignment from modal
 *   5. Assignment summary: chips row + expandable detail cards
 *   6. Overlay state on cinema grid cards
 *   7. Serialize / restore selectedCinemas ↔ hidden cinemas_json
 */

(function () {
    'use strict';

    /* ================================================================
       STATE
       selectedCinemas: Array of {cinemaId, name, img, city, state,
                                   startDate, endDate, slots}
    ================================================================ */
    var selectedCinemas = [];
    var pendingCinemaId = null;   // cinema being configured in modal

    /* ================================================================
       HELPERS
    ================================================================ */
    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden');    }

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

    function formatDate(iso) {
        if (!iso) return '—';
        var parts = iso.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];   // DD/MM/YYYY
    }

    /* ================================================================
       1. VIEW SWITCHING
    ================================================================ */
    function initViewSwitching() {
        var openBtn  = document.getElementById('mc-select-cinemas-btn');
        var backBtn  = document.getElementById('mc-select-back');
        var doneBtn  = document.getElementById('mc-done-selecting');

        if (openBtn) openBtn.addEventListener('click', function () {
            switchView('mc-cinema-select-view');
        });

        if (backBtn) backBtn.addEventListener('click', function () {
            switchView('mc-form-view');
        });

        if (doneBtn) doneBtn.addEventListener('click', function () {
            switchView('mc-form-view');
            renderSummary();
        });
    }

    /* ================================================================
       2. CINEMA CARD CLICK → OPEN MODAL
    ================================================================ */
    function initCinemaCards() {
        var grid = document.getElementById('mc-cinema-grid');
        if (!grid) return;

        grid.addEventListener('click', function (e) {
            var card = e.target.closest('.mc-cinema-card');
            if (!card) return;
            openModal(card);
        });

        grid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.mc-cinema-card');
            if (!card) return;
            e.preventDefault();
            openModal(card);
        });
    }

    /* ================================================================
       3. QUOTA MODAL
    ================================================================ */
    function initModal() {
        var modal      = document.getElementById('mc-quota-modal');
        var confirmBtn = document.getElementById('mc-quota-confirm');
        var cancelBtn  = document.getElementById('mc-quota-cancel');
        var removeBtn  = document.getElementById('mc-quota-remove');

        if (!modal) return;

        confirmBtn.addEventListener('click', confirmAssignment);
        cancelBtn.addEventListener('click',  closeModal);
        removeBtn.addEventListener('click',  removeAssignment);

        // Click backdrop to close
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('vc-hidden')) closeModal();
        });
    }

    function openModal(card) {
        var cinemaId = card.dataset.cinemaId;
        pendingCinemaId = cinemaId;

        // Populate strip
        var name  = card.dataset.cinemaName;
        var img   = card.dataset.cinemaImg;
        var city  = card.dataset.cinemaCity;
        var state = card.dataset.cinemaState;

        document.getElementById('mc-modal-cinema-name').textContent = name;
        document.getElementById('mc-modal-cinema-loc').textContent  = city + ', ' + state;

        var imgEl = document.getElementById('mc-modal-img');
        var imgPh = document.getElementById('mc-modal-img-ph');
        if (img) {
            imgEl.src = img;
            imgEl.alt = name;
            show(imgEl);
            hide(imgPh);
        } else {
            hide(imgEl);
            show(imgPh);
        }

        // Pre-fill if already assigned
        var existing = findAssignment(cinemaId);
        var removeBtn = document.getElementById('mc-quota-remove');
        var confirmBtn = document.getElementById('mc-quota-confirm');

        if (existing) {
            document.getElementById('mc-start-date').value = existing.startDate;
            document.getElementById('mc-end-date').value   = existing.endDate;
            document.getElementById('mc-slots').value      = existing.slots;
            show(removeBtn);
            confirmBtn.textContent = '✓ Update Assignment';
        } else {
            document.getElementById('mc-start-date').value = '';
            document.getElementById('mc-end-date').value   = '';
            document.getElementById('mc-slots').value      = '';
            hide(removeBtn);
            confirmBtn.textContent = '✓ Assign Cinema';
        }

        // Clear errors
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

        hide(document.getElementById('mc-start-err'));
        hide(document.getElementById('mc-end-err'));
        hide(document.getElementById('mc-slots-err'));

        if (!startDate) {
            show(document.getElementById('mc-start-err'));
            valid = false;
        }
        if (!endDate || (startDate && endDate <= startDate)) {
            document.getElementById('mc-end-err').textContent =
                !endDate ? 'Required.' : 'Must be after start date.';
            show(document.getElementById('mc-end-err'));
            valid = false;
        }
        if (!slots || slots < 1) {
            show(document.getElementById('mc-slots-err'));
            valid = false;
        }
        if (!valid) return;

        // Upsert into selectedCinemas
        var existing = findAssignment(pendingCinemaId);
        var cinemaData = (window.MC_CINEMAS || []).find(function (c) {
            return String(c.id) === String(pendingCinemaId);
        }) || {};

        if (existing) {
            existing.startDate = startDate;
            existing.endDate   = endDate;
            existing.slots     = slots;
        } else {
            selectedCinemas.push({
                cinemaId:  pendingCinemaId,
                name:      cinemaData.name  || '—',
                img:       cinemaData.img   || '',
                city:      cinemaData.city  || '—',
                state:     cinemaData.state || '—',
                startDate: startDate,
                endDate:   endDate,
                slots:     slots,
            });
        }

        closeModal();
        syncHidden();
        updateOverlays();
        updateCountBadge();
    }

    function removeAssignment() {
        selectedCinemas = selectedCinemas.filter(function (c) {
            return String(c.cinemaId) !== String(pendingCinemaId);
        });
        closeModal();
        syncHidden();
        updateOverlays();
        updateCountBadge();
    }

    function closeModal() {
        hide(document.getElementById('mc-quota-modal'));
        pendingCinemaId = null;
    }

    /* ================================================================
       4. OVERLAYS ON GRID CARDS
    ================================================================ */
    function updateOverlays() {
        var cards = document.querySelectorAll('.mc-cinema-card');
        cards.forEach(function (card) {
            var overlay = card.querySelector('.mc-assigned-overlay');
            if (!overlay) return;
            var isAssigned = !!findAssignment(card.dataset.cinemaId);
            if (isAssigned) {
                show(overlay);
                card.classList.add('mc-card--assigned');
            } else {
                hide(overlay);
                card.classList.remove('mc-card--assigned');
            }
        });
    }

    /* ================================================================
       5. COUNT BADGE IN SELECTION FOOTER
    ================================================================ */
    function updateCountBadge() {
        var badge = document.getElementById('mc-count-badge');
        if (!badge) return;
        var n = selectedCinemas.length;
        badge.textContent = n + ' cinema' + (n !== 1 ? 's' : '') + ' assigned';
    }

    /* ================================================================
       6. SUMMARY IN MAIN FORM
          Chips row (collapsed) + expandable detail cards
    ================================================================ */
    function renderSummary() {
        var summary    = document.getElementById('mc-assigned-summary');
        var noMsg      = document.getElementById('mc-no-cinemas');
        var chipsRow   = document.getElementById('mc-chips-row');
        var cardsWrap  = document.getElementById('mc-cards-expanded');
        var toggleBtn  = document.getElementById('mc-toggle-expand');
        var selectBtn  = document.getElementById('mc-select-cinemas-btn');

        if (selectedCinemas.length === 0) {
            hide(summary);
            show(noMsg);
            if (selectBtn) selectBtn.textContent = '🏢 Select Cinemas';
            return;
        }

        show(summary);
        hide(noMsg);
        if (selectBtn) selectBtn.textContent = '✎  Edit Assignments';

        // Chips row
        chipsRow.innerHTML = '';
        selectedCinemas.forEach(function (c) {
            var chip = document.createElement('span');
            chip.className   = 'mc-chip';
            chip.textContent = c.name;
            chipsRow.appendChild(chip);
        });

        // Expanded detail cards
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
                    '<p class="mc-assignment-card__name">' + c.name + '</p>' +
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
            cardsWrap.appendChild(card);
        });

        // Toggle button wiring
        if (toggleBtn) {
            toggleBtn.onclick = function () {
                var isOpen = !cardsWrap.classList.contains('vc-hidden');
                if (isOpen) {
                    hide(cardsWrap);
                    toggleBtn.textContent = '▾ Show details';
                } else {
                    show(cardsWrap);
                    toggleBtn.textContent = '▴ Hide details';
                }
            };
        }
    }

    /* ================================================================
       7. SERIALIZE / RESTORE
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
        updateOverlays();
        updateCountBadge();
        renderSummary();
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initViewSwitching();
        initCinemaCards();
        initModal();
        restoreFromOld();
    });

})();