/**
 * movie_quota_assignment.js
 * Cinema batch assignment – isolated module.
 * Exposed: window.MC_Quota
 */
(function () {
    'use strict';

    var selectedCinemas  = [];
    var pendingSelection = [];

    /* Re‑use show/hide from MC_ViewSwitcher if available */
    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden'); }

    function isAssigned(cinemaId) {
        return selectedCinemas.some(function (c) { return String(c.cinemaId) === String(cinemaId); });
    }

    function isPending(cinemaId) {
        return pendingSelection.indexOf(String(cinemaId)) !== -1;
    }

    function cinemaDataById(id) {
        return (window.MC_CINEMAS || []).find(function (c) { return String(c.id) === String(id); }) || {};
    }

    function formatDate(iso) {
        if (!iso) return '—';
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    /* ── Card visuals ───────────────────────────────────── */
    function refreshCardVisuals() {
        document.querySelectorAll('.mc-cinema-card').forEach(function (card) {
            var id         = card.dataset.cinemaId;
            var assignedOl = card.querySelector('.mc-assigned-overlay');
            var pendingOl  = card.querySelector('.mc-pending-overlay');
            var assigned   = isAssigned(id);
            var pending    = isPending(id);

            if (assignedOl) assigned ? show(assignedOl) : hide(assignedOl);
            card.classList.toggle('mc-card--assigned', assigned);

            if (pendingOl) pending ? show(pendingOl) : hide(pendingOl);
            card.classList.toggle('mc-card--pending', pending);
        });
    }

    function refreshSelectionBar() {
        var count    = pendingSelection.length;
        var countEl  = document.getElementById('mc-sel-count');
        var assignBtn = document.getElementById('mc-assign-quota-btn');
        if (countEl)  countEl.textContent = count + ' selected';
        if (assignBtn) assignBtn.disabled = count === 0;
    }

    function updateCountBadge() {
        var badge = document.getElementById('mc-count-badge');
        if (!badge) return;
        var n = selectedCinemas.length;
        badge.textContent = n + ' cinema' + (n !== 1 ? 's' : '') + ' assigned';
    }

    /* ── Summary rendering ──────────────────────────────── */
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

        chipsRow.innerHTML = '';
        selectedCinemas.forEach(function (c) {
            var chip = document.createElement('span');
            chip.className = 'mc-chip';
            chip.textContent = c.name;
            chipsRow.appendChild(chip);
        });

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
                        '<button type="button" class="mc-assignment-card__remove" data-id="' + c.cinemaId + '" title="Remove assignment">✕</button>' +
                    '</div>' +
                    '<p class="mc-assignment-card__loc">' + c.city + ', ' + c.state + '</p>' +
                    '<div class="mc-assignment-card__meta">' +
                        '<div class="mc-assignment-card__row"><span class="mc-assignment-card__label">Start</span><span class="mc-assignment-card__val">' + formatDate(c.startDate) + '</span></div>' +
                        '<div class="mc-assignment-card__row"><span class="mc-assignment-card__label">End</span><span class="mc-assignment-card__val">' + formatDate(c.endDate) + '</span></div>' +
                        '<div class="mc-assignment-card__row"><span class="mc-assignment-card__label">Slots</span><span class="mc-assignment-card__val">' + c.slots + ' / day</span></div>' +
                    '</div>' +
                '</div>';

            card.querySelector('.mc-assignment-card__remove').addEventListener('click', function () {
                removeAssignmentById(this.dataset.id);
            });
            cardsWrap.appendChild(card);
        });

        if (toggleBtn) {
            toggleBtn.onclick = function () {
                var isOpen = !cardsWrap.classList.contains('vc-hidden');
                isOpen ? hide(cardsWrap) : show(cardsWrap);
                toggleBtn.textContent = isOpen ? '▾ Show details' : '▴ Hide details';
            };
        }

        if (clearAll) clearAll.onclick = clearAllAssignments;
    }

    /* ── Remove / Clear All ─────────────────────────────── */
    function removeAssignmentById(cinemaId) {
        selectedCinemas = selectedCinemas.filter(function (c) { return String(c.cinemaId) !== String(cinemaId); });
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

    /* ── Modal (quota) ──────────────────────────────────── */
    function openModal() {
        var strip   = document.getElementById('mc-modal-strip');
        var subText = document.getElementById('mc-modal-sub');
        strip.innerHTML = '';
        pendingSelection.forEach(function (id) {
            var c = cinemaDataById(id);
            var pill = document.createElement('div');
            pill.className = 'mc-modal__cinema-pill';
            if (c.img) {
                var img = document.createElement('img');
                img.src = c.img; img.alt = c.name || '';
                img.className = 'mc-modal__pill-img';
                pill.appendChild(img);
            } else {
                var ph = document.createElement('div');
                ph.className = 'mc-modal__pill-ph';
                ph.textContent = '🎬';
                pill.appendChild(ph);
            }
            var info = document.createElement('div');
            info.className = 'mc-modal__pill-info';
            info.innerHTML = '<p class="mc-modal__pill-name">' + (c.name  || '—') + '</p>' +
                             '<p class="mc-modal__pill-loc">'  + (c.city  || '—') + ', ' + (c.state || '—') + '</p>';
            pill.appendChild(info);
            strip.appendChild(pill);
        });

        subText.textContent = 'This quota plan will be applied to ' + pendingSelection.length + ' cinema' + (pendingSelection.length !== 1 ? 's' : '') + '.';

        document.getElementById('mc-start-date').value = '';
        document.getElementById('mc-end-date').value   = '';
        document.getElementById('mc-slots').value      = '';
        ['mc-start-err','mc-end-err','mc-slots-err'].forEach(function (id) { hide(document.getElementById(id)); });
        show(document.getElementById('mc-quota-modal'));
    }

    function closeModal() {
        hide(document.getElementById('mc-quota-modal'));
    }

    function confirmAssignment() {
        var startDate = document.getElementById('mc-start-date').value;
        var endDate   = document.getElementById('mc-end-date').value;
        var slots     = parseInt(document.getElementById('mc-slots').value, 10);
        var valid     = true;

        ['mc-start-err','mc-end-err','mc-slots-err'].forEach(function (id) { hide(document.getElementById(id)); });

        if (!startDate) { show(document.getElementById('mc-start-err')); valid = false; }
        if (!endDate || (startDate && endDate <= startDate)) {
            var errEl = document.getElementById('mc-end-err');
            errEl.textContent = !endDate ? 'Required.' : 'Must be after start date.';
            show(errEl); valid = false;
        }
        if (!slots || slots < 1) { show(document.getElementById('mc-slots-err')); valid = false; }
        if (!valid) return;

        pendingSelection.forEach(function (id) {
            if (isAssigned(id)) return;
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
        pendingSelection = [];
        closeModal();
        syncHidden();
        refreshCardVisuals();
        refreshSelectionBar();
        updateCountBadge();
    }

    /* ── Serialization ──────────────────────────────────── */
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

    /* ── Public API ─────────────────────────────────────── */
    window.MC_Quota = {
        init: function () {
            try {
                // Card toggle
                var grid = document.getElementById('mc-cinema-grid');
                if (grid) {
                    grid.addEventListener('click', function (e) {
                        var card = e.target.closest('.mc-cinema-card');
                        if (!card) return;
                        var id = card.dataset.cinemaId;
                        if (isAssigned(id)) return;
                        if (isPending(id)) {
                            pendingSelection = pendingSelection.filter(function (x) { return x !== String(id); });
                        } else {
                            pendingSelection.push(String(id));
                        }
                        refreshCardVisuals();
                        refreshSelectionBar();
                    });
                    grid.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            var card = e.target.closest('.mc-cinema-card');
                            if (!card) return;
                            e.preventDefault();
                            card.click();
                        }
                    });
                }

                // Select All / Clear Selection / Assign Quota
                document.getElementById('mc-select-all-btn')?.addEventListener('click', function () {
                    (window.MC_CINEMAS || []).forEach(function (c) {
                        if (!isAssigned(c.id) && !isPending(c.id)) pendingSelection.push(String(c.id));
                    });
                    refreshCardVisuals();
                    refreshSelectionBar();
                });
                document.getElementById('mc-clear-selection-btn')?.addEventListener('click', function () {
                    pendingSelection = [];
                    refreshCardVisuals();
                    refreshSelectionBar();
                });
                document.getElementById('mc-assign-quota-btn')?.addEventListener('click', function () {
                    if (pendingSelection.length > 0) openModal();
                });

                // Modal buttons
                document.getElementById('mc-quota-confirm')?.addEventListener('click', confirmAssignment);
                document.getElementById('mc-quota-cancel')?.addEventListener('click', closeModal);
                document.getElementById('mc-quota-modal')?.addEventListener('click', function (e) {
                    if (e.target === this) closeModal();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !document.getElementById('mc-quota-modal').classList.contains('vc-hidden')) closeModal();
                });

                // View switching buttons – use shared function if exists
                var backBtn = document.getElementById('mc-select-back');
                var doneBtn = document.getElementById('mc-done-selecting');
                if (backBtn) backBtn.addEventListener('click', function () {
                    pendingSelection = [];
                    refreshCardVisuals();
                    refreshSelectionBar();
                    if (window.MC_ViewSwitcher && window.MC_ViewSwitcher.switchView) {
                        window.MC_ViewSwitcher.switchView('mc-form-view');
                    } else {
                        // fallback
                        document.getElementById('mc-form-view')?.classList.remove('vc-hidden');
                        document.getElementById('mc-cinema-select-view')?.classList.add('vc-hidden');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
                if (doneBtn) doneBtn.addEventListener('click', function () {
                    pendingSelection = [];
                    refreshCardVisuals();
                    refreshSelectionBar();
                    if (window.MC_ViewSwitcher && window.MC_ViewSwitcher.switchView) {
                        window.MC_ViewSwitcher.switchView('mc-form-view');
                    } else {
                        document.getElementById('mc-form-view')?.classList.remove('vc-hidden');
                        document.getElementById('mc-cinema-select-view')?.classList.add('vc-hidden');
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    renderSummary();
                });

                restoreFromOld();
            } catch (e) {
                console.error('MC_Quota init error:', e);
            }
        },
        hasAssignments: function () { return selectedCinemas.length > 0; },
        sync: syncHidden,
        renderSummary: renderSummary,
        refreshCardVisuals: refreshCardVisuals,
        updateCountBadge: updateCountBadge
    };

})();