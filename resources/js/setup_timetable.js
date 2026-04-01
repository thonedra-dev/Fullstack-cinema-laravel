/**
 * resources/js/setup_timetable.js
 *
 * Multi-slot showtime scheduler.
 *
 * FLOW:
 *   1. Pick a theatre (sidebar)         → seat layout renders
 *   2. Set a start time (clock widget)
 *   3. Pick one or more dates (calendar)
 *   4. Click "Add to Schedule"
 *      → two-level conflict check:
 *          a. Against approved DB showtimes (same theatre)
 *          b. Against already-staged slots in current session (same theatre)
 *      → if clean: stage the slot group into the preview panel
 *   5. Repeat with same or different theatre / time / dates
 *   6. "Submit Proposal" POSTs schedule_json to the controller
 *
 * STATE MODEL:
 *   schedule = [
 *     {
 *       theatreId   : number,
 *       theatreName : string,
 *       slotGroups  : [
 *         {
 *           hour       : number,   // 1-12
 *           minute     : number,   // 0-55
 *           ampm       : 'AM'|'PM',
 *           timeKey    : string,   // "02:00 PM" — deduplication key
 *           endDisplay : string,   // "04:08 PM" — pre-computed for preview
 *           dates      : string[]  // sorted ISO dates
 *         }
 *       ]
 *     }
 *   ]
 */
(function () {
    'use strict';

    /* ================================================================
       BOOT
    ================================================================ */
    var configEl = document.getElementById('smt-seat-data-json');
    if (!configEl) return;

    var allTheatres       = JSON.parse(configEl.dataset.theatres             || '[]');
    var existingShowtimes = JSON.parse(configEl.dataset.existingShowtimes    || '[]');
    var mode              = configEl.dataset.preselectedMode;
    var preTheatreId      = configEl.dataset.preselectedTheatreId  || '';
    var preMovieId        = configEl.dataset.preselectedMovieId    || '';
    var preRuntime        = parseInt(configEl.dataset.preselectedRuntime || '0', 10);

    /* ── Working state ────────────────────────────────────── */
    var selectedTheatreId   = preTheatreId ? parseInt(preTheatreId, 10) : null;
    var selectedMovieId     = preMovieId   ? parseInt(preMovieId,   10) : null;
    var selectedRuntime     = preRuntime   || 0;
    var selectedDates       = [];   // dates staged in the current editing session (not yet committed)

    var clockHour   = 7;
    var clockMinute = 0;
    var clockAmPm   = 'AM';

    var calDate = new Date();
    calDate.setDate(1);

    // The committed schedule
    var schedule = [];

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

    /* ================================================================
       HELPERS
    ================================================================ */
    function pad(n) { return String(n).padStart(2, '0'); }

    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden'); }

    function makeTimeKey(h, m, ap) { return pad(h) + ':' + pad(m) + ' ' + ap; }

    /**
     * Convert a date ISO + 12-hour clock → { startMs, endMs } in milliseconds.
     */
    function toSlotMs(dateIso, hour, minute, ampm, runtime) {
        var h24 = hour % 12;
        if (ampm === 'PM') h24 += 12;
        var startMs = new Date(dateIso + 'T' + pad(h24) + ':' + pad(minute) + ':00').getTime();
        return { startMs: startMs, endMs: startMs + runtime * 60000 };
    }

    /**
     * Compute and format the end time string.
     */
    function computeEndDisplay(hour, minute, ampm, runtime) {
        var h24     = hour % 12;
        if (ampm === 'PM') h24 += 12;
        var endMin  = h24 * 60 + minute + runtime;
        var endH24  = Math.floor(endMin / 60) % 24;
        var endM    = endMin % 60;
        var endAp   = endH24 >= 12 ? 'PM' : 'AM';
        var dispH   = endH24 % 12 || 12;
        return pad(dispH) + ':' + pad(endM) + ' ' + endAp;
    }

    function findTheatreData(id) {
        return allTheatres.find(function (t) { return t.id === id; });
    }

    /** Collect all staged dates (all theatres, all slot groups) for a given theatre. */
    function stagedDatesForTheatre(theatreId) {
        var result = [];
        var entry  = schedule.find(function (s) { return s.theatreId === theatreId; });
        if (!entry) return result;
        entry.slotGroups.forEach(function (sg) {
            sg.dates.forEach(function (d) { result.push({ date: d, sg: sg }); });
        });
        return result;
    }

    /* ================================================================
       CONFLICT DETECTION
       Returns an array of human-readable conflict strings.
       Empty array = no conflicts.
    ================================================================ */
    function detectConflicts(theatreId, hour, minute, ampm, dates, runtime) {
        var msgs = [];

        dates.forEach(function (iso) {
            var t = toSlotMs(iso, hour, minute, ampm, runtime);

            // ── Level 1: approved showtimes in DB ─────────────
            existingShowtimes.forEach(function (st) {
                if (st.theatre_id !== theatreId) return;
                var exS = new Date(st.start).getTime();
                var exE = new Date(st.end).getTime();
                if (t.startMs < exE && t.endMs > exS) {
                    msgs.push(
                        iso + ' at ' + makeTimeKey(hour, minute, ampm) +
                        ' clashes with an existing approved showtime.'
                    );
                }
            });

            // ── Level 2: already-staged slots (same theatre) ──
            var entry = schedule.find(function (s) { return s.theatreId === theatreId; });
            if (entry) {
                entry.slotGroups.forEach(function (sg) {
                    // Skip the slot group that has the exact same timeKey
                    // (adding more dates to it is handled by merge — still conflict-check
                    //  the new date against OTHER slot groups only)
                    var isCurrentTimeKey = (sg.timeKey === makeTimeKey(hour, minute, ampm));
                    sg.dates.forEach(function (existingDate) {
                        if (isCurrentTimeKey && existingDate === iso) {
                            // Duplicate date in same time slot — flag as duplicate
                            msgs.push(
                                iso + ' is already staged at ' + sg.timeKey + '.'
                            );
                            return;
                        }
                        // Cross-slot overlap check
                        if (!isCurrentTimeKey) {
                            var ex = toSlotMs(existingDate, sg.hour, sg.minute, sg.ampm, runtime);
                            if (t.startMs < ex.endMs && t.endMs > ex.startMs) {
                                msgs.push(
                                    iso + ' at ' + makeTimeKey(hour, minute, ampm) +
                                    ' overlaps with staged slot on ' +
                                    existingDate + ' at ' + sg.timeKey + '.'
                                );
                            }
                        }
                    });
                });
            }
        });

        return msgs;
    }

    /* ================================================================
       SCHEDULE MUTATIONS
    ================================================================ */
    function addToSchedule() {
        // ── Guards ────────────────────────────────────────────
        if (!selectedTheatreId) {
            showError(['Please select a theatre first.']); return;
        }
        if (!selectedRuntime) {
            showError(['Please select a movie first.']); return;
        }
        if (selectedDates.length === 0) {
            showError(['Please select at least one date on the calendar.']); return;
        }

        // ── Conflict check ────────────────────────────────────
        var conflicts = detectConflicts(
            selectedTheatreId, clockHour, clockMinute, clockAmPm,
            selectedDates, selectedRuntime
        );
        if (conflicts.length > 0) { showError(conflicts); return; }

        clearError();

        var theatreData = findTheatreData(selectedTheatreId);
        var theatreName = theatreData ? theatreData.name : 'Theatre ' + selectedTheatreId;
        var timeKey     = makeTimeKey(clockHour, clockMinute, clockAmPm);
        var endDisplay  = computeEndDisplay(clockHour, clockMinute, clockAmPm, selectedRuntime);

        // ── Find or create theatre entry ──────────────────────
        var entry = schedule.find(function (s) { return s.theatreId === selectedTheatreId; });
        if (!entry) {
            entry = { theatreId: selectedTheatreId, theatreName: theatreName, slotGroups: [] };
            schedule.push(entry);
        }

        // ── Find or create slot group ─────────────────────────
        var sg = entry.slotGroups.find(function (g) { return g.timeKey === timeKey; });
        if (!sg) {
            sg = {
                hour: clockHour, minute: clockMinute, ampm: clockAmPm,
                timeKey: timeKey, endDisplay: endDisplay, dates: []
            };
            entry.slotGroups.push(sg);
        }

        // ── Merge dates (skip duplicates) ─────────────────────
        selectedDates.forEach(function (iso) {
            if (sg.dates.indexOf(iso) === -1) sg.dates.push(iso);
        });
        sg.dates.sort();

        // ── Clear staging area ────────────────────────────────
        selectedDates = [];
        renderCalendar();
        renderDateChips();

        // ── Sync + re-render ──────────────────────────────────
        renderPreview();
        syncScheduleJson();
        updateSubmitBtn();

        // Scroll to preview
        var previewEl = document.getElementById('smt-preview-section');
        if (previewEl) previewEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function removeDate(theatreId, timeKey, iso) {
        var entry = schedule.find(function (s) { return s.theatreId === theatreId; });
        if (!entry) return;
        var sg = entry.slotGroups.find(function (g) { return g.timeKey === timeKey; });
        if (!sg) return;

        sg.dates = sg.dates.filter(function (d) { return d !== iso; });
        pruneEmpty(entry, timeKey);
        afterMutate();
    }

    function removeSlotGroup(theatreId, timeKey) {
        var entry = schedule.find(function (s) { return s.theatreId === theatreId; });
        if (!entry) return;
        entry.slotGroups = entry.slotGroups.filter(function (g) { return g.timeKey !== timeKey; });
        if (entry.slotGroups.length === 0) {
            schedule = schedule.filter(function (s) { return s.theatreId !== theatreId; });
        }
        afterMutate();
    }

    function removeTheatre(theatreId) {
        schedule = schedule.filter(function (s) { return s.theatreId !== theatreId; });
        afterMutate();
    }

    function clearAll() {
        schedule = [];
        afterMutate();
    }

    function pruneEmpty(entry, timeKey) {
        entry.slotGroups = entry.slotGroups.filter(function (g) { return g.dates.length > 0; });
        if (entry.slotGroups.length === 0) {
            schedule = schedule.filter(function (s) { return s.theatreId !== entry.theatreId; });
        }
    }

    function afterMutate() {
        renderPreview();
        renderCalendar(); // refresh staged-day highlights
        syncScheduleJson();
        updateSubmitBtn();
    }

    /* ================================================================
       PREVIEW RENDERER (fully dynamic)
    ================================================================ */
    function renderPreview() {
        var body    = document.getElementById('smt-preview-body');
        var emptyEl = document.getElementById('smt-preview-empty');
        var counter = document.getElementById('smt-preview-counter');
        var cntSpan = document.getElementById('smt-preview-count');
        if (!body) return;

        // Count total slots
        var total = 0;
        schedule.forEach(function (e) {
            e.slotGroups.forEach(function (sg) { total += sg.dates.length; });
        });

        if (schedule.length === 0) {
            body.innerHTML = '';
            show(emptyEl);
            hide(counter);
            return;
        }

        hide(emptyEl);
        show(counter);
        if (cntSpan) cntSpan.textContent = total;

        // Re-render body
        body.innerHTML = '';

        schedule.forEach(function (entry) {

            // ── Theatre block ──────────────────────────────────
            var tBlock = document.createElement('div');
            tBlock.className = 'smt-preview-theatre';

            var tHeader = document.createElement('div');
            tHeader.className = 'smt-preview-theatre-header';

            var tName = document.createElement('span');
            tName.className   = 'smt-preview-theatre-name';
            tName.textContent = '🏟 ' + entry.theatreName;

            var tRemoveBtn = document.createElement('button');
            tRemoveBtn.type      = 'button';
            tRemoveBtn.className = 'smt-preview-remove-btn smt-preview-remove-btn--theatre';
            tRemoveBtn.textContent = '✕ Remove Theatre';
            tRemoveBtn.addEventListener('click', (function (tid) {
                return function () { removeTheatre(tid); };
            })(entry.theatreId));

            tHeader.appendChild(tName);
            tHeader.appendChild(tRemoveBtn);
            tBlock.appendChild(tHeader);

            // ── Slot groups ────────────────────────────────────
            entry.slotGroups.forEach(function (sg) {

                var sgEl = document.createElement('div');
                sgEl.className = 'smt-preview-time-group';

                var sgHeader = document.createElement('div');
                sgHeader.className = 'smt-preview-time-header';

                var timeLabel = document.createElement('span');
                timeLabel.className   = 'smt-preview-time-label';
                timeLabel.textContent = '🕐 ' + sg.timeKey + ' → ' + sg.endDisplay;

                var sgRemoveBtn = document.createElement('button');
                sgRemoveBtn.type      = 'button';
                sgRemoveBtn.className = 'smt-preview-remove-btn smt-preview-remove-btn--slot';
                sgRemoveBtn.textContent = '✕ Remove time';
                sgRemoveBtn.title = 'Remove all dates for this time slot';
                sgRemoveBtn.addEventListener('click', (function (tid, tk) {
                    return function () { removeSlotGroup(tid, tk); };
                })(entry.theatreId, sg.timeKey));

                sgHeader.appendChild(timeLabel);
                sgHeader.appendChild(sgRemoveBtn);
                sgEl.appendChild(sgHeader);

                // Date chips
                var datesWrap = document.createElement('div');
                datesWrap.className = 'smt-preview-dates';

                sg.dates.forEach(function (iso) {
                    var chip = document.createElement('span');
                    chip.className = 'smt-preview-date-chip';

                    var lbl = document.createElement('span');
                    lbl.textContent = iso;

                    var rm = document.createElement('span');
                    rm.className   = 'smt-date-chip__remove';
                    rm.textContent = '✕';
                    rm.title = 'Remove this date';
                    rm.addEventListener('click', (function (tid, tk, d) {
                        return function () { removeDate(tid, tk, d); };
                    })(entry.theatreId, sg.timeKey, iso));

                    chip.appendChild(lbl);
                    chip.appendChild(rm);
                    datesWrap.appendChild(chip);
                });

                sgEl.appendChild(datesWrap);
                tBlock.appendChild(sgEl);
            });

            body.appendChild(tBlock);
        });
    }

    /* ================================================================
       SUBMIT BUTTON STATE
    ================================================================ */
    function updateSubmitBtn() {
        var btn = document.getElementById('smt-submit-btn');
        if (!btn) return;

        var total = 0;
        schedule.forEach(function (e) {
            e.slotGroups.forEach(function (sg) { total += sg.dates.length; });
        });

        btn.disabled = total === 0;
        btn.textContent = total > 0
            ? '🗓 Submit Proposal (' + total + ' slot' + (total !== 1 ? 's' : '') + ')'
            : '🗓 Submit Proposal';
    }

    /* ================================================================
       SYNC JSON HIDDEN FIELD
    ================================================================ */
    function syncScheduleJson() {
        var field = document.getElementById('smt-schedule-json');
        if (field) field.value = JSON.stringify(schedule);
    }

    /* ================================================================
       ERROR / CONFLICT UI
    ================================================================ */
    function showError(msgs) {
        var area = document.getElementById('smt-conflict-area');
        var list = document.getElementById('smt-conflict-list');
        if (!area || !list) return;
        list.innerHTML = '';
        msgs.forEach(function (msg) {
            var item = document.createElement('div');
            item.className   = 'smt-conflict-item';
            item.textContent = '⚠ ' + msg;
            list.appendChild(item);
        });
        show(area);
        area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearError() {
        hide(document.getElementById('smt-conflict-area'));
    }

    /* ================================================================
       THEATRE SIDEBAR (movie-first mode)
    ================================================================ */
    function initTheatreSidebar() {
        document.querySelectorAll('.smt-theatre-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                selectedTheatreId = parseInt(this.value, 10);

                document.querySelectorAll('.smt-theatre-row').forEach(function (row) {
                    row.classList.remove('is-selected');
                });
                this.closest('.smt-theatre-row').classList.add('is-selected');

                renderSeats(selectedTheatreId);
                clearError();
                // Clear pending dates when switching theatre
                selectedDates = [];
                renderCalendar();
                renderDateChips();
            });
        });

        if (selectedTheatreId) renderSeats(selectedTheatreId);
    }

    /* ================================================================
       MOVIE SIDEBAR (theatre-first mode)
    ================================================================ */
    function initMovieSidebar() {
        document.querySelectorAll('.smt-movie-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var row = this.closest('.smt-movie-pick-row');
                selectedMovieId = parseInt(this.value, 10);
                selectedRuntime = parseInt(row.dataset.runtime || '0', 10);

                document.getElementById('smt-hidden-movie').value = selectedMovieId;

                document.querySelectorAll('.smt-movie-pick-row').forEach(function (r) {
                    r.classList.remove('is-selected');
                });
                row.classList.add('is-selected');

                updateEndTimePreview();
                clearError();
            });
        });

        // Theatre-first: preselected theatre is shown in banner, render seats now
        if (mode === 'theatre' && selectedTheatreId) {
            renderSeats(selectedTheatreId);
        }
    }

    /* ================================================================
       SEAT LAYOUT RENDERER
    ================================================================ */
    function renderSeats(theatreId) {
        var theatre = findTheatreData(theatreId);
        var preview = document.getElementById('smt-seat-preview');
        if (!preview) return;

        preview.innerHTML = '';

        if (!theatre || !theatre.seats || theatre.seats.length === 0) {
            preview.innerHTML = '<p class="smt-seat-preview__hint">No seats defined for this theatre.</p>';
            return;
        }

        theatre.seats.forEach(function (rowData) {
            var rowEl = document.createElement('div');
            rowEl.className = 'smt-row';

            var lblL = document.createElement('span');
            lblL.className   = 'smt-row__label';
            lblL.textContent = rowData.label;

            var seatsEl = document.createElement('div');
            seatsEl.className = 'smt-row__seats';

            rowData.seats.forEach(function (seat, i) {
                var type  = seat.seat_type;
                var isLg  = (type === 'Premium' || type === 'Family');
                var s     = document.createElement('span');
                s.className = 'sb-seat sb-seat--' + type.toLowerCase() + (isLg ? ' sb-seat--lg' : '');
                seatsEl.appendChild(s);

                if (type === 'Couple' && (i + 1) % 2 === 0 && i + 1 < rowData.seats.length) {
                    var gap = document.createElement('span');
                    gap.className = 'sb-couple-gap';
                    seatsEl.appendChild(gap);
                }
            });

            var lblR = document.createElement('span');
            lblR.className   = 'smt-row__label';
            lblR.textContent = rowData.label;

            rowEl.appendChild(lblL);
            rowEl.appendChild(seatsEl);
            rowEl.appendChild(lblR);
            preview.appendChild(rowEl);
        });
    }

    /* ================================================================
       CLOCK WIDGET
    ================================================================ */
    function updateClockDisplay() {
        var hEl = document.getElementById('smt-hour-display');
        var mEl = document.getElementById('smt-minute-display');
        var aEl = document.getElementById('smt-ampm-display');
        if (hEl) hEl.textContent = pad(clockHour);
        if (mEl) mEl.textContent = pad(clockMinute);
        if (aEl) aEl.textContent = clockAmPm;
        updateEndTimePreview();
    }

    function updateEndTimePreview() {
        var el = document.getElementById('smt-end-time-val');
        if (!el) return;
        if (!selectedRuntime) { el.textContent = '—'; return; }
        el.textContent = computeEndDisplay(clockHour, clockMinute, clockAmPm, selectedRuntime);
    }

    function initClockButtons() {
        document.querySelectorAll('.smt-clock__arrow').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = this.dataset.target;
                var dir    = this.dataset.dir;

                if (target === 'hour') {
                    clockHour = dir === 'up'
                        ? (clockHour % 12) + 1
                        : ((clockHour - 2 + 12) % 12) + 1;
                } else if (target === 'minute') {
                    clockMinute = dir === 'up'
                        ? (clockMinute + 5) % 60
                        : (clockMinute - 5 + 60) % 60;
                } else if (target === 'ampm') {
                    clockAmPm = clockAmPm === 'AM' ? 'PM' : 'AM';
                }

                updateClockDisplay();
            });
        });
    }

    /* ================================================================
       CALENDAR
    ================================================================ */
    function getStagedDatesForActiveTheatre() {
        if (!selectedTheatreId) return [];
        var result = [];
        var entry  = schedule.find(function (s) { return s.theatreId === selectedTheatreId; });
        if (!entry) return result;
        entry.slotGroups.forEach(function (sg) {
            sg.dates.forEach(function (d) { result.push(d); });
        });
        return result;
    }

    function renderCalendar() {
        var grid  = document.getElementById('smt-cal-grid');
        var label = document.getElementById('smt-cal-month');
        if (!grid || !label) return;

        var year  = calDate.getFullYear();
        var month = calDate.getMonth();
        label.textContent = MONTHS[month] + ' ' + year;
        grid.innerHTML = '';

        var first = new Date(year, month, 1).getDay();
        var days  = new Date(year, month + 1, 0).getDate();
        var today = new Date(); today.setHours(0, 0, 0, 0);

        var stagedDates = getStagedDatesForActiveTheatre();

        for (var e = 0; e < first; e++) {
            var empty = document.createElement('div');
            empty.className = 'smt-cal-day smt-cal-day--empty';
            grid.appendChild(empty);
        }

        for (var d = 1; d <= days; d++) {
            var thisDate = new Date(year, month, d);
            var iso      = year + '-' + pad(month + 1) + '-' + pad(d);
            var isPast   = thisDate < today;
            var isSel    = selectedDates.indexOf(iso)  !== -1;
            var isStaged = stagedDates.indexOf(iso)    !== -1;

            var dayEl = document.createElement('div');
            dayEl.className = 'smt-cal-day' +
                (isPast   ? ' smt-cal-day--past'     : '') +
                (isSel    ? ' smt-cal-day--selected' : '') +
                (isStaged ? ' smt-cal-day--staged'   : '');
            dayEl.textContent = d;
            dayEl.dataset.iso = iso;

            if (!isPast && !isStaged) {
                dayEl.addEventListener('click', (function (isoVal) {
                    return function () { toggleDate(isoVal); };
                })(iso));
            }

            grid.appendChild(dayEl);
        }
    }

    function toggleDate(iso) {
        var idx = selectedDates.indexOf(iso);
        if (idx === -1) selectedDates.push(iso);
        else            selectedDates.splice(idx, 1);
        selectedDates.sort();
        renderCalendar();
        renderDateChips();
    }

    function renderDateChips() {
        var container = document.getElementById('smt-dates-preview');
        if (!container) return;
        container.innerHTML = '';

        selectedDates.forEach(function (iso) {
            var chip = document.createElement('span');
            chip.className = 'smt-date-chip';

            var txt = document.createElement('span');
            txt.textContent = iso;

            var rm  = document.createElement('span');
            rm.className   = 'smt-date-chip__remove';
            rm.textContent = '✕';
            rm.addEventListener('click', (function (isoVal) {
                return function () { toggleDate(isoVal); };
            })(iso));

            chip.appendChild(txt);
            chip.appendChild(rm);
            container.appendChild(chip);
        });
    }

    /* ================================================================
       CALENDAR NAV
    ================================================================ */
    function initCalendarNav() {
        var prev = document.getElementById('smt-cal-prev');
        var next = document.getElementById('smt-cal-next');
        if (prev) prev.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() - 1); renderCalendar();
        });
        if (next) next.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() + 1); renderCalendar();
        });
    }

    /* ================================================================
       "ADD TO SCHEDULE" BUTTON
    ================================================================ */
    function initAddSlotBtn() {
        var btn = document.getElementById('smt-add-slot-btn');
        if (btn) btn.addEventListener('click', addToSchedule);
    }

    /* ================================================================
       "CLEAR ALL" BUTTON
    ================================================================ */
    function initClearAllBtn() {
        var btn = document.getElementById('smt-clear-all-btn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (schedule.length === 0) return;
            if (confirm('Clear all staged slots?')) clearAll();
        });
    }

    /* ================================================================
       FORM SUBMIT
    ================================================================ */
    function initFormSubmit() {
        var form = document.getElementById('smt-form');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            if (schedule.length === 0) {
                e.preventDefault();
                showError(['Please add at least one slot before submitting.']);
                return;
            }
            // Ensure JSON is written right before submit
            syncScheduleJson();
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initTheatreSidebar();
        initMovieSidebar();
        initClockButtons();
        updateClockDisplay();
        initCalendarNav();
        renderCalendar();
        initAddSlotBtn();
        initClearAllBtn();
        initFormSubmit();
        renderPreview();
        updateSubmitBtn();
    });

})();