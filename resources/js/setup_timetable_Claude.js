/**
 * setup_timetable.js
 * Main entry point — coordinates clock, calendar, batches, seat layout.
 * Vite imports the sub-modules automatically.
 */

import {
    clockState, initClock, resetClock,
    getH24, getStartMinutes, getEndMinutes, formatMinutes
} from './setup_timetable_clock.js';

import {
    initCalendar, setMaxDate, getSelectedDates,
    clearSelectedDates, renderDateChips, renderCalendar
} from './setup_timetable_calendar.js';

import {
    initBatches, setContext, addBatch,
    getBatchCount, getRuntime, checkBatchOverlap, serializeBatches
} from './setup_timetable_batches.js';

/* ================================================================
   BOOT
================================================================ */
document.addEventListener('DOMContentLoaded', function () {

    const configEl = document.getElementById('smt-seat-data-json');
    if (!configEl) return;

    const allTheatres      = JSON.parse(configEl.dataset.theatres || '[]');
    const existingShowtimes = JSON.parse(configEl.dataset.existingShowtimes || '[]');
    const mode             = configEl.dataset.preselectedMode;
    const preTheatreId     = configEl.dataset.preselectedTheatreId || '';
    const preMovieId       = configEl.dataset.preselectedMovieId   || '';
    const preRuntime       = parseInt(configEl.dataset.preselectedRuntime || '0', 10);
    const preMaxDate       = configEl.dataset.preselectedMaxDate   || '';
    const quotaMap         = JSON.parse(configEl.dataset.quotaMap  || '{}');

    /* ── Init modules ─────────────────────────────────────── */
    initBatches(existingShowtimes, onBatchesChange);

    initClock(onClockChange);

    initCalendar(onDateChange, preMaxDate || null);

    /* ── Pre-populate if mode === 'movie' ─────────────────── */
    if (mode === 'movie' && preMovieId && preRuntime) {
        setContext(
            preTheatreId ? parseInt(preTheatreId, 10) : null,
            preRuntime
        );
        if (preMaxDate) setMaxDate(preMaxDate);
        renderCalendar();
        updateEndTimePreview();
    }

    /* ── Theatre selection (movie-first) ──────────────────── */
    if (mode === 'movie') {
        document.querySelectorAll('.smt-theatre-radio').forEach(radio => {
            radio.addEventListener('change', function () {
                const tid = parseInt(this.value, 10);
                document.getElementById('smt-hidden-theatre').value = tid;
                document.querySelectorAll('.smt-theatre-row').forEach(r => r.classList.remove('is-selected'));
                this.closest('.smt-theatre-row').classList.add('is-selected');

                setContext(tid, getRuntime() || preRuntime);
                renderSeats(tid);
                updateEndTimePreview();
                updateTimeConflictWarn();
            });
        });
    }

    /* ── Movie selection (theatre-first) ──────────────────── */
    if (mode === 'theatre') {
        document.querySelectorAll('.smt-movie-radio').forEach(radio => {
            radio.addEventListener('change', function () {
                const row     = this.closest('.smt-movie-pick-row');
                const mid     = parseInt(this.value, 10);
                const runtime = parseInt(row.dataset.runtime || '0', 10);
                const maxDate = row.dataset.maxDate || '';

                document.getElementById('smt-hidden-movie').value = mid;
                document.querySelectorAll('.smt-movie-pick-row').forEach(r => r.classList.remove('is-selected'));
                row.classList.add('is-selected');

                // Get theatre id from the preselected banner
                const preselectedTheatreInput = document.getElementById('smt-hidden-theatre');
                const tid = preselectedTheatreInput
                    ? parseInt(preselectedTheatreInput.value, 10) || null
                    : null;

                setContext(tid, runtime);
                if (maxDate) setMaxDate(maxDate);
                if (tid) renderSeats(tid);
                updateEndTimePreview();
                updateTimeConflictWarn();
            });
        });
    }

    /* ── Proceed button ───────────────────────────────────── */
    const proceedBtn  = document.getElementById('smt-proceed-btn');
    const proceedErr  = document.getElementById('smt-proceed-error');

    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            const dates   = getSelectedDates();
            const runtime = getRuntime();

            if (proceedErr) {
                proceedErr.textContent = '';
                proceedErr.classList.add('vc-hidden');
            }

            if (runtime === 0) {
                showProceedError('Select a movie first (runtime unknown).');
                return;
            }
            if (dates.length === 0) {
                showProceedError('Select at least one date.');
                return;
            }

            const h24    = getH24();
            const minute = clockState.minute;
            const ampm   = clockState.ampm;

            const err = addBatch(h24, minute, ampm, dates);
            if (err) {
                showProceedError(err);
                return;
            }

            // Success — reset calendar, leave clock for convenience
            clearSelectedDates();
            updateEndTimePreview();
            updateSubmitButton();
        });
    }

    /* ── Form submit guard ────────────────────────────────── */
    const form = document.getElementById('smt-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (getBatchCount() === 0) {
                e.preventDefault();
                showProceedError('Add at least one time slot before submitting.');
                return;
            }
            serializeBatches();
        });
    }

    /* ── Helpers ──────────────────────────────────────────── */
    function onClockChange() {
        updateEndTimePreview();
        updateTimeConflictWarn();
    }

    function onDateChange() {
        updateTimeConflictWarn();
    }

    function onBatchesChange() {
        updateSubmitButton();
        updateTimeConflictWarn();
    }

    function updateEndTimePreview() {
        const runtime = getRuntime();
        const el      = document.getElementById('smt-end-time-val');
        if (!el) return;
        if (runtime === 0) { el.textContent = '—'; return; }
        const endMin = getEndMinutes(runtime);
        el.textContent = formatMinutes(endMin % (24 * 60));
    }

    function updateTimeConflictWarn() {
        const warn    = document.getElementById('smt-time-conflict-warn');
        const runtime = getRuntime();
        if (!warn || runtime === 0) return;

        const startMin = getStartMinutes();
        const dates    = getSelectedDates();

        const conflicts = checkBatchOverlap(startMin, dates.length > 0 ? dates : ['__check__']);
        const hasConflict = conflicts.length > 0 && dates.length > 0;

        warn.classList.toggle('vc-hidden', !hasConflict);
    }

    function updateSubmitButton() {
        const btn = document.getElementById('smt-submit-btn');
        if (btn) btn.disabled = getBatchCount() === 0;
    }

    function showProceedError(msg) {
        const el = document.getElementById('smt-proceed-error');
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('vc-hidden');
    }

    /* ── Seat layout renderer ─────────────────────────────── */
    function renderSeats(theatreId) {
        const theatre = allTheatres.find(t => t.id === theatreId);
        const preview = document.getElementById('smt-seat-preview');
        if (!preview) return;

        preview.innerHTML = '';

        if (!theatre || !theatre.seats || theatre.seats.length === 0) {
            preview.innerHTML = '<p class="smt-seat-preview__hint">No seats defined for this theatre.</p>';
            return;
        }

        theatre.seats.forEach(rowData => {
            const rowEl = document.createElement('div');
            rowEl.className = 'smt-row';

            const lbl = document.createElement('span');
            lbl.className   = 'smt-row__label';
            lbl.textContent = rowData.label;
            rowEl.appendChild(lbl);

            const seatsEl = document.createElement('div');
            seatsEl.className = 'smt-row__seats';

            rowData.seats.forEach((seat, i) => {
                const type    = seat.seat_type;
                const isLarge = type === 'Premium' || type === 'Family';
                const seatEl  = document.createElement('span');
                seatEl.className = 'sb-seat sb-seat--' + type.toLowerCase() +
                    (isLarge ? ' sb-seat--lg' : '');
                seatsEl.appendChild(seatEl);

                if (type === 'Couple' && (i + 1) % 2 === 0 && i + 1 < rowData.seats.length) {
                    const gap = document.createElement('span');
                    gap.className = 'sb-couple-gap';
                    seatsEl.appendChild(gap);
                }
            });

            rowEl.appendChild(seatsEl);

            const lblR = document.createElement('span');
            lblR.className   = 'smt-row__label';
            lblR.textContent = rowData.label;
            rowEl.appendChild(lblR);

            preview.appendChild(rowEl);
        });
    }

    // Auto-render seat layout if theatre is preselected (movie-first)
    if (mode === 'movie' && preTheatreId) {
        renderSeats(parseInt(preTheatreId, 10));
    }
    // Auto-render if theatre-first
    if (mode === 'theatre' && preTheatreId) {
        renderSeats(parseInt(preTheatreId, 10));
    }

    updateSubmitButton();
});