/**
 * create_theatre.js
 * Place at: resources/js/create_theatre.js
 *
 * Responsibilities:
 *   1. Form ↔ Cinema-selection ↔ Seat-builder view switching
 *   2. Cinema card click → confirmation modal
 *   3. Modal confirm → fill cinema_id, display name
 *   4. Restore cinema name on validation error
 *   5. Seat builder — row management (add / undo / clear)
 *   6. Seat builder — live preview rendering with typed icons
 *   7. Seat builder — serialize rows to hidden seats_json input
 *   8. Restore seat rows from CT_SEATS_JSON on validation error
 *   9. Seat summary strip in main form
 *  10. File upload previews
 *  11. Service chip visual toggle
 */

(function () {
    'use strict';

    /* ================================================================
       SEAT TYPE CONFIG
       Single source of truth for type metadata used in rendering.
    ================================================================ */
    var SEAT_TYPES = {
        Standard: { cls: 'sb-seat--standard', size: 'sm', pairGap: false },
        Couple:   { cls: 'sb-seat--couple',   size: 'sm', pairGap: true  },
        Premium:  { cls: 'sb-seat--premium',  size: 'lg', pairGap: false },
        Family:   { cls: 'sb-seat--family',   size: 'lg', pairGap: false },
    };

    /* ================================================================
       HELPERS
    ================================================================ */
    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden');    }

    function switchView(showId) {
        ['ct-form-view', 'ct-selection-view', 'ct-seat-builder-view'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            id === showId ? show(el) : hide(el);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ================================================================
       BUILD A SINGLE SEAT ICON ELEMENT
    ================================================================ */
    function makeSeatEl(type) {
        var cfg  = SEAT_TYPES[type] || SEAT_TYPES.Standard;
        var span = document.createElement('span');
        span.className = 'sb-seat ' + cfg.cls + (cfg.size === 'lg' ? ' sb-seat--lg' : '');
        return span;
    }

    /* ================================================================
       RENDER A ROW OF SEAT ICONS
       Returns a DocumentFragment ready to append.
    ================================================================ */
    function buildRowSeats(count, type) {
        var cfg  = SEAT_TYPES[type] || SEAT_TYPES.Standard;
        var frag = document.createDocumentFragment();

        if (cfg.pairGap) {
            // Couple: render in adjacent pairs with a gap between pairs
            for (var i = 0; i < count; i++) {
                frag.appendChild(makeSeatEl(type));
                if ((i + 1) % 2 === 0 && i + 1 < count) {
                    var gap = document.createElement('span');
                    gap.className = 'sb-couple-gap';
                    frag.appendChild(gap);
                }
            }
        } else {
            for (var j = 0; j < count; j++) {
                frag.appendChild(makeSeatEl(type));
            }
        }

        return frag;
    }

    /* ================================================================
       1. VIEW SWITCHING — CINEMA SELECTION
    ================================================================ */
    function initCinemaSelection() {
        var selectBtn    = document.getElementById('ct-select-cinema-btn');
        var backBtn      = document.getElementById('ct-selection-back');
        var modal        = document.getElementById('ct-confirm-modal');
        var modalImg     = document.getElementById('ct-modal-img');
        var modalImgPH   = document.getElementById('ct-modal-img-placeholder');
        var modalName    = document.getElementById('ct-modal-name');
        var modalConfirm = document.getElementById('ct-modal-confirm');
        var modalCancel  = document.getElementById('ct-modal-cancel');
        var idInput      = document.getElementById('ct-cinema-id-input');
        var display      = document.getElementById('ct-selected-cinema-display');
        var displayName  = document.getElementById('ct-selected-cinema-name');

        if (!selectBtn) return;

        var pendingId   = null;
        var pendingName = null;

        selectBtn.addEventListener('click', function () {
            switchView('ct-selection-view');
        });

        backBtn.addEventListener('click', function () {
            switchView('ct-form-view');
        });

        // Delegate: cinema card click in selection view
        var selView = document.getElementById('ct-selection-view');
        selView.addEventListener('click', function (e) {
            var card = e.target.closest('.ct-selectable-card');
            if (!card) return;
            pendingId   = card.dataset.cinemaId;
            pendingName = card.dataset.cinemaName;
            populateModal(pendingName, card.dataset.cinemaImg);
            show(modal);
        });

        selView.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.ct-selectable-card');
            if (!card) return;
            e.preventDefault();
            pendingId   = card.dataset.cinemaId;
            pendingName = card.dataset.cinemaName;
            populateModal(pendingName, card.dataset.cinemaImg);
            show(modal);
        });

        modalConfirm.addEventListener('click', function () {
            idInput.value        = pendingId;
            displayName.textContent = pendingName;
            show(display);
            selectBtn.textContent = '✎  Change Cinema';
            closeModal();
            switchView('ct-form-view');
        });

        modalCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('vc-hidden')) closeModal();
        });

        function populateModal(name, imgSrc) {
            modalName.textContent = name;
            if (imgSrc) {
                modalImg.src = imgSrc;
                modalImg.alt = name;
                show(modalImg);
                hide(modalImgPH);
            } else {
                hide(modalImg);
                show(modalImgPH);
            }
        }

        function closeModal() {
            hide(modal);
            pendingId = pendingName = null;
        }
    }

    /* ================================================================
       4. RESTORE CINEMA DISPLAY ON VALIDATION ERROR
    ================================================================ */
    function restoreCinemaDisplay() {
        var idInput     = document.getElementById('ct-cinema-id-input');
        var display     = document.getElementById('ct-selected-cinema-display');
        var displayName = document.getElementById('ct-selected-cinema-name');
        var selectBtn   = document.getElementById('ct-select-cinema-btn');

        if (!idInput || !idInput.value) return;

        var match = (window.CT_CINEMAS || []).find(function (c) {
            return String(c.id) === String(idInput.value);
        });

        if (match) {
            displayName.textContent = match.name;
            show(display);
            if (selectBtn) selectBtn.textContent = '✎  Change Cinema';
        }
    }

    /* ================================================================
       5–9. SEAT BUILDER
    ================================================================ */
    function initSeatBuilder() {
        var openBtn     = document.getElementById('ct-define-seats-btn');
        var backBtn     = document.getElementById('ct-seat-builder-back');
        var addBtn      = document.getElementById('sb-add-row-btn');
        var undoBtn     = document.getElementById('sb-undo-btn');
        var clearBtn    = document.getElementById('sb-clear-btn');
        var finalizeBtn = document.getElementById('sb-finalize-btn');
        var countInput  = document.getElementById('sb-seat-count');
        var labelBadge  = document.getElementById('sb-next-label');
        var preview     = document.getElementById('sb-preview');
        var previewEmpty= document.getElementById('sb-preview-empty');
        var rowPreview  = document.getElementById('sb-row-preview-seats');
        var errorEl     = document.getElementById('sb-error');
        var summaryEl   = document.getElementById('sb-finalize-summary');
        var hiddenInput = document.getElementById('ct-seats-json');
        var seatsSum    = document.getElementById('ct-seats-summary');
        var defineBtn   = document.getElementById('ct-define-seats-btn');
        var countHint   = document.getElementById('sb-count-hint');

        if (!openBtn) return;

        var rows = []; // [{label, count, type}]

        // ── Open / close seat builder ──────────────────────────────
        openBtn.addEventListener('click', function () {
            switchView('ct-seat-builder-view');
        });

        backBtn.addEventListener('click', function () {
            switchView('ct-form-view');
        });

        // ── Type radio card selection ──────────────────────────────
        document.querySelectorAll('.sb-type-card').forEach(function (card) {
            card.addEventListener('click', function () {
                document.querySelectorAll('.sb-type-card').forEach(function (c) {
                    c.classList.remove('is-selected');
                });
                card.classList.add('is-selected');
                updateRowPreview();
                updateCountHint();
            });
        });

        // ── Live row preview as user types count ───────────────────
        countInput.addEventListener('input', updateRowPreview);

        // ── Add row ────────────────────────────────────────────────
        addBtn.addEventListener('click', function () {
            var count = parseInt(countInput.value, 10);
            var type  = getSelectedType();

            hide(errorEl);

            if (!type) {
                showError('Please select a seat type.');
                return;
            }
            if (!count || count < 1 || count > 40) {
                showError('Enter a seat count between 1 and 40.');
                return;
            }
            if (type === 'Couple' && count % 2 !== 0) {
                showError('Couple seats must be an even number (they come in pairs).');
                return;
            }
            if (rows.length >= 26) {
                showError('Maximum 26 rows (A–Z) allowed.');
                return;
            }

            rows.push({ label: getNextLabel(), count: count, type: type });
            countInput.value = '';
            document.querySelectorAll('.sb-type-card').forEach(function (c) {
                c.classList.remove('is-selected');
                c.querySelector('input[type="radio"]').checked = false;
            });
            rowPreview.innerHTML = '';
            renderPreview();
            updateNextLabel();
            syncHidden();
        });

        // ── Undo last row ──────────────────────────────────────────
        undoBtn.addEventListener('click', function () {
            if (rows.length === 0) return;
            rows.pop();
            renderPreview();
            updateNextLabel();
            updateRowPreview();
            syncHidden();
        });

        // ── Clear all ──────────────────────────────────────────────
        clearBtn.addEventListener('click', function () {
            if (rows.length === 0) return;
            if (!confirm('Clear all defined rows?')) return;
            rows = [];
            renderPreview();
            updateNextLabel();
            rowPreview.innerHTML = '';
            syncHidden();
        });

        // ── Finalize and return to form ────────────────────────────
        finalizeBtn.addEventListener('click', function () {
            syncHidden();
            renderSummaryStrip();
            switchView('ct-form-view');
        });

        // ── Helpers ────────────────────────────────────────────────
        function getNextLabel() {
            if (rows.length === 0) return 'A';
            return String.fromCharCode(65 + rows.length); // A=65
        }

        function updateNextLabel() {
            if (labelBadge) labelBadge.textContent = getNextLabel();
        }

        function getSelectedType() {
            var radio = document.querySelector('input[name="sb_seat_type"]:checked');
            return radio ? radio.value : null;
        }

        function showError(msg) {
            errorEl.textContent = msg;
            show(errorEl);
        }

        function updateCountHint() {
            var type = getSelectedType();
            if (type === 'Couple') {
                countHint.textContent = 'Must be even — each pair counts as 2 seats.';
            } else {
                countHint.textContent = '';
            }
        }

        // Render the full seat layout preview
        function renderPreview() {
            // Remove all existing row elements (not the empty message)
            preview.querySelectorAll('.sb-row').forEach(function (el) { el.remove(); });

            if (rows.length === 0) {
                show(previewEmpty);
                summaryEl.textContent = '';
                return;
            }
            hide(previewEmpty);

            rows.forEach(function (row) {
                var rowEl = document.createElement('div');
                rowEl.className = 'sb-row';

                var lbl = document.createElement('span');
                lbl.className   = 'sb-row__label';
                lbl.textContent = row.label;
                rowEl.appendChild(lbl);

                var seats = document.createElement('div');
                seats.className = 'sb-row__seats';
                seats.appendChild(buildRowSeats(row.count, row.type));
                rowEl.appendChild(seats);

                var badge = document.createElement('span');
                badge.className   = 'sb-row__type-badge sb-badge--' + row.type.toLowerCase();
                badge.textContent = row.type;
                rowEl.appendChild(badge);

                preview.appendChild(rowEl);
            });

            // Update finalize summary
            var total = rows.reduce(function (acc, r) { return acc + r.count; }, 0);
            summaryEl.textContent = rows.length + ' row(s) · ' + total + ' seat(s) defined';
        }

        // Live mini-preview in builder panel
        function updateRowPreview() {
            var count = parseInt(countInput.value, 10) || 0;
            var type  = getSelectedType();
            rowPreview.innerHTML = '';
            if (count > 0 && count <= 40 && type) {
                var frag = buildRowSeats(Math.min(count, 40), type);
                rowPreview.appendChild(frag);
            }
        }

        // Write rows array to hidden input
        function syncHidden() {
            if (hiddenInput) hiddenInput.value = JSON.stringify(rows);
        }

        // Render the compact summary strip in the main form
        function renderSummaryStrip() {
            if (!seatsSum) return;
            if (rows.length === 0) {
                hide(seatsSum);
                defineBtn.textContent = '💺 Define Seat Structure';
                return;
            }
            var total = rows.reduce(function (acc, r) { return acc + r.count; }, 0);
            seatsSum.innerHTML = '';

            rows.forEach(function (row) {
                var chip = document.createElement('span');
                chip.className   = 'ct-seat-chip ct-seat-chip--' + row.type.toLowerCase();
                chip.textContent = row.label + ' · ' + row.count + ' · ' + row.type;
                seatsSum.appendChild(chip);
            });

            var info = document.createElement('span');
            info.className   = 'ct-seat-chip ct-seat-chip--total';
            info.textContent = '= ' + total + ' seats';
            seatsSum.appendChild(info);

            show(seatsSum);
            defineBtn.textContent = '✎  Edit Seat Structure';
        }

        // ── Restore rows from CT_SEATS_JSON (validation error) ─────
        function restoreFromJson() {
            var raw = window.CT_SEATS_JSON || '[]';
            var parsed;
            try { parsed = JSON.parse(raw); } catch (e) { return; }
            if (!Array.isArray(parsed) || parsed.length === 0) return;

            rows = parsed;
            renderPreview();
            updateNextLabel();
            renderSummaryStrip();
        }

        // Run restore on init
        restoreFromJson();
    }

    /* ================================================================
       10. FILE UPLOAD PREVIEWS
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
       11. SERVICE CHIP TOGGLE
    ================================================================ */
    function initServiceChips() {
        document.querySelectorAll('.ct-service-chip').forEach(function (chip) {
            var cb = chip.querySelector('input[type="checkbox"]');
            if (!cb) return;
            chip.addEventListener('click', function () {
                setTimeout(function () {
                    chip.classList.toggle('is-checked', cb.checked);
                }, 0);
            });
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initCinemaSelection();
        restoreCinemaDisplay();
        initSeatBuilder();
        initFilePreview('theatre_icon',   'theatre_icon_preview');
        initFilePreview('theatre_poster', 'theatre_poster_preview');
        initServiceChips();
    });

})();