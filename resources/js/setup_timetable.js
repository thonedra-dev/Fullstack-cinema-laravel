/**
 * resources/js/setup_timetable.js
 *
 * Multi-slot showtime scheduler.
 *
 * NAMING CONVENTION (was previously inconsistent):
 *   hallId / selectedHallId  — always refers to halls.hall_id
 *   theatreId in entry obj   — always refers to theatres.theatre_id (the master type)
 *   theatreName              — display name from the master theatre type
 *
 * FLOW:
 *   1. Pick a hall (sidebar)            → seat layout renders
 *   2. Set a start time (clock widget)
 *   3. Pick one or more dates (calendar)
 *   4. Click "Add to Schedule"
 *      → two-level conflict check:
 *          a. Against approved DB showtimes (same hall, same date)
 *          b. Against already-staged slots  (same hall, same date)
 *      → if clean: stage the slot group into the preview panel
 *   5. Repeat — same hall/different time OR different hall entirely.
 *      A date that is already staged CAN be clicked again for a different time.
 *   6. "Submit Proposal" POSTs schedule_json to the controller.
 *
 * CALENDAR VISUAL STATES:
 *   .smt-cal-day--past     → before today, NOT clickable
 *   .smt-cal-day--staged   → green tint (has committed slot for active hall)
 *                            STILL CLICKABLE — user can add more times on same date
 *   .smt-cal-day--selected → bright green fill (currently ticked in staging area)
 *
 * CONFLICT RULES (checked before committing each "Add to Schedule"):
 *   Rule A — DB:     new [start,end) must NOT overlap any existing approved Showtime
 *                    in the same hall on the same calendar date.
 *   Rule B — Staged: new [start,end) must NOT overlap any already-staged slot
 *                    in the same hall on the same calendar date.
 *   Duplicate:       exact same date + exact same timeKey already staged → blocked.
 */
(function () {
    'use strict';

    /* ================================================================
       BOOT — read inline data from the config element
    ================================================================ */
    var configEl = document.getElementById('smt-seat-data-json');
    if (!configEl) return;

    var allHalls          = JSON.parse(configEl.dataset.theatres          || '[]');
    var existingShowtimes = JSON.parse(configEl.dataset.existingShowtimes || '[]');
    var mode              = configEl.dataset.preselectedMode;
    var preHallId         = configEl.dataset.preselectedHallId            || '';
    var preMovieId        = configEl.dataset.preselectedMovieId           || '';
    var preRuntime        = parseInt(configEl.dataset.preselectedRuntime  || '0', 10);
    var hasRejectedProposal      = configEl.dataset.hasRejectedProposal === '1';
    var rejectedReplaceConfirmed = false;

    /* ── Working state ─────────────────────────────────────── */
    var selectedHallId  = preHallId   ? parseInt(preHallId,   10) : null;
    var selectedMovieId = preMovieId  ? parseInt(preMovieId,  10) : null;
    var selectedRuntime = preRuntime  || 0;
    var selectedDates   = [];   // dates currently in the staging area (not yet committed)

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
    function pad(n)  { return String(n).padStart(2, '0'); }
    function show(e) { if (e) e.classList.remove('vc-hidden'); }
    function hide(e) { if (e) e.classList.add('vc-hidden'); }

    function makeTimeKey(h, m, ap) {
        return pad(h) + ':' + pad(m) + ' ' + ap;
    }

    /**
     * Convert a date ISO string + 12-h clock values → { startMs, endMs } in ms.
     * runtime is in minutes.
     */
    function toSlotMs(dateIso, hour, minute, ampm, runtime) {
        var h24     = hour % 12;
        if (ampm === 'PM') h24 += 12;
        var startMs = new Date(dateIso + 'T' + pad(h24) + ':' + pad(minute) + ':00').getTime();
        return { startMs: startMs, endMs: startMs + runtime * 60000 };
    }

    function computeEndDisplay(hour, minute, ampm, runtime) {
        var h24    = hour % 12;
        if (ampm === 'PM') h24 += 12;
        var endMin = h24 * 60 + minute + runtime;
        var endH24 = Math.floor(endMin / 60) % 24;
        var endM   = endMin % 60;
        var endAp  = endH24 >= 12 ? 'PM' : 'AM';
        return pad(endH24 % 12 || 12) + ':' + pad(endM) + ' ' + endAp;
    }

    /**
     * Find the hall data object from the allHalls array by hall_id.
     * Each item has: { id: hall_id, hall_id, theatre_id, name, seats }
     */
    function findHallData(hallId) {
        return allHalls.find(function (h) { return parseInt(h.id, 10) === hallId; });
    }

    /* ================================================================
       CONFLICT DETECTION
       Returns an array of human-readable error strings.
       Empty array = no conflicts.

       For each proposed date:
         Rule A — check against existingShowtimes (DB approved, same hall, same date)
         Rule B — check against already-staged slots (same hall, same date)
    ================================================================ */
    function detectConflicts(hallId, hour, minute, ampm, dates, runtime) {
        var msgs = [];

        dates.forEach(function (iso) {
            var t = toSlotMs(iso, hour, minute, ampm, runtime);

            /* ── Rule A: DB approved showtimes ───────────────── */
            existingShowtimes.forEach(function (st) {
                // Only compare against rows for the same hall
                var stHallId = parseInt(st.hall_id || 0, 10);
                if (stHallId !== hallId) return;

                // st.start is a full ISO timestamp — extract the date portion
                var exDate = st.start.substring(0, 10);   // 'YYYY-MM-DD'
                if (exDate !== iso) return;               // different date, skip

                var exStart = new Date(st.start).getTime();
                var exEnd   = new Date(st.end).getTime();

                // Overlap: new_start < ex_end  AND  new_end > ex_start
                if (t.startMs < exEnd && t.endMs > exStart) {
                    msgs.push(
                        iso + ' at ' + makeTimeKey(hour, minute, ampm) +
                        ' overlaps an approved showtime in this hall.'
                    );
                }
            });

            /* ── Rule B: already-staged slots ────────────────── */
            var entry = schedule.find(function (s) { return s.hallId === hallId; });
            if (!entry) return;   // no staged slots for this hall yet

            var currentTimeKey = makeTimeKey(hour, minute, ampm);

            entry.slotGroups.forEach(function (sg) {
                // Only compare against staged slots that include this exact date
                if (sg.dates.indexOf(iso) === -1) return;

                // Exact duplicate: same date + same time already staged
                if (sg.timeKey === currentTimeKey) {
                    msgs.push(
                        iso + ' is already staged at ' + sg.timeKey + '.'
                    );
                    return;
                }

                // Different time on the same date — check window overlap
                var ex = toSlotMs(iso, sg.hour, sg.minute, sg.ampm, runtime);
                if (t.startMs < ex.endMs && t.endMs > ex.startMs) {
                    msgs.push(
                        iso + ' at ' + makeTimeKey(hour, minute, ampm) +
                        ' overlaps staged slot at ' + sg.timeKey +
                        ' → ' + sg.endDisplay + '.'
                    );
                }
            });
        });

        return msgs;
    }

    /* ================================================================
       SCHEDULE MUTATIONS
    ================================================================ */
    function addToSchedule() {
        /* ── Guards ──────────────────────────────────────────── */
        if (!selectedHallId) {
            showError(['Please select a hall first.']); return;
        }
        if (!selectedRuntime) {
            showError(['Please select a movie first.']); return;
        }
        if (selectedDates.length === 0) {
            showError(['Please select at least one date on the calendar.']); return;
        }

        /* ── Conflict check ──────────────────────────────────── */
        var conflicts = detectConflicts(
            selectedHallId, clockHour, clockMinute, clockAmPm,
            selectedDates, selectedRuntime
        );
        if (conflicts.length > 0) { showError(conflicts); return; }

        clearError();

        var hallData    = findHallData(selectedHallId);
        var theatreName = hallData ? hallData.name : 'Hall ' + selectedHallId;
        var timeKey     = makeTimeKey(clockHour, clockMinute, clockAmPm);
        var endDisplay  = computeEndDisplay(clockHour, clockMinute, clockAmPm, selectedRuntime);

        /* ── Find or create hall entry in schedule ───────────── */
        var entry = schedule.find(function (s) { return s.hallId === selectedHallId; });
        if (!entry) {
            entry = {
                hallId:      selectedHallId,
                theatreId:   hallData ? hallData.theatre_id : null,  // master theatre_id
                theatreName: theatreName,
                slotGroups:  []
            };
            schedule.push(entry);
        }

        /* ── Find or create slot group ───────────────────────── */
        var sg = entry.slotGroups.find(function (g) { return g.timeKey === timeKey; });
        if (!sg) {
            sg = {
                hour: clockHour, minute: clockMinute, ampm: clockAmPm,
                timeKey: timeKey, endDisplay: endDisplay, dates: []
            };
            entry.slotGroups.push(sg);
        }

        /* ── Merge dates (skip exact duplicates) ─────────────── */
        selectedDates.forEach(function (iso) {
            if (sg.dates.indexOf(iso) === -1) sg.dates.push(iso);
        });
        sg.dates.sort();

        /* ── Do NOT clear staging area — keep dates selected so user
               can immediately change the clock and add another slot ── */
        // selectedDates remains unchanged

        /* ── Sync + re-render ────────────────────────────────── */
        renderPreview();
        renderCalendar();   // refresh staged tints while keeping selection
        renderDateChips();
        syncScheduleJson();
        updateSubmitBtn();

        var previewEl = document.getElementById('smt-preview-section');
        if (previewEl) previewEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function removeDate(hallId, timeKey, iso) {
        var entry = schedule.find(function (s) { return s.hallId === hallId; });
        if (!entry) return;
        var sg = entry.slotGroups.find(function (g) { return g.timeKey === timeKey; });
        if (!sg) return;
        sg.dates = sg.dates.filter(function (d) { return d !== iso; });
        pruneEmpty(entry);
        afterMutate();
    }

    function removeSlotGroup(hallId, timeKey) {
        var entry = schedule.find(function (s) { return s.hallId === hallId; });
        if (!entry) return;
        entry.slotGroups = entry.slotGroups.filter(function (g) { return g.timeKey !== timeKey; });
        pruneEmpty(entry);
        afterMutate();
    }

    function removeHall(hallId) {
        schedule = schedule.filter(function (s) { return s.hallId !== hallId; });
        afterMutate();
    }

    function clearAll() {
        schedule = [];
        afterMutate();
    }

    function pruneEmpty(entry) {
        entry.slotGroups = entry.slotGroups.filter(function (g) { return g.dates.length > 0; });
        if (entry.slotGroups.length === 0) {
            schedule = schedule.filter(function (s) { return s.hallId !== entry.hallId; });
        }
    }

    function afterMutate() {
        renderPreview();
        renderCalendar();   // refresh staged-day tint
        syncScheduleJson();
        updateSubmitBtn();
    }

    /* ================================================================
       PREVIEW RENDERER
    ================================================================ */
    function renderPreview() {
        var body    = document.getElementById('smt-preview-body');
        var emptyEl = document.getElementById('smt-preview-empty');
        var counter = document.getElementById('smt-preview-counter');
        var cntSpan = document.getElementById('smt-preview-count');
        if (!body) return;

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

        body.innerHTML = '';

        schedule.forEach(function (entry) {
            var tBlock  = document.createElement('div');
            tBlock.className = 'smt-preview-theatre';

            var tHeader = document.createElement('div');
            tHeader.className = 'smt-preview-theatre-header';

            var tName  = document.createElement('span');
            tName.className   = 'smt-preview-theatre-name';
            tName.textContent = '🏟 ' + entry.theatreName;

            var tRm = document.createElement('button');
            tRm.type      = 'button';
            tRm.className = 'smt-preview-remove-btn smt-preview-remove-btn--theatre';
            tRm.textContent = '✕ Remove Hall';
            tRm.addEventListener('click', (function (hid) {
                return function () { removeHall(hid); };
            })(entry.hallId));

            tHeader.appendChild(tName);
            tHeader.appendChild(tRm);
            tBlock.appendChild(tHeader);

            entry.slotGroups.forEach(function (sg) {
                var sgEl = document.createElement('div');
                sgEl.className = 'smt-preview-time-group';

                var sgHeader = document.createElement('div');
                sgHeader.className = 'smt-preview-time-header';

                var tLabel = document.createElement('span');
                tLabel.className   = 'smt-preview-time-label';
                tLabel.textContent = '🕐 ' + sg.timeKey + ' → ' + sg.endDisplay;

                var sgRm = document.createElement('button');
                sgRm.type      = 'button';
                sgRm.className = 'smt-preview-remove-btn smt-preview-remove-btn--slot';
                sgRm.textContent = '✕ Remove time';
                sgRm.addEventListener('click', (function (hid, tk) {
                    return function () { removeSlotGroup(hid, tk); };
                })(entry.hallId, sg.timeKey));

                sgHeader.appendChild(tLabel);
                sgHeader.appendChild(sgRm);
                sgEl.appendChild(sgHeader);

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
                    rm.addEventListener('click', (function (hid, tk, d) {
                        return function () { removeDate(hid, tk, d); };
                    })(entry.hallId, sg.timeKey, iso));

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
       ERROR / CONFLICT UI  (inline area, shown below "Add" button)
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
       HALL SIDEBAR (movie-first mode)
       Radio value = hall_id (set in blade as value="{{ $theatre->hall_id }}")
    ================================================================ */
    function initHallSidebar() {
        document.querySelectorAll('.smt-theatre-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                selectedHallId = parseInt(this.value, 10);

                document.querySelectorAll('.smt-theatre-row').forEach(function (row) {
                    row.classList.remove('is-selected');
                });
                this.closest('.smt-theatre-row').classList.add('is-selected');

                renderSeats(selectedHallId);
                clearError();
                // Clear pending staging when switching hall
                selectedDates = [];
                renderCalendar();
                renderDateChips();
            });
        });

        if (selectedHallId) renderSeats(selectedHallId);
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

        if (mode === 'theatre' && selectedHallId) {
            renderSeats(selectedHallId);
        }
    }

    /* ================================================================
       SEAT LAYOUT RENDERER
    ================================================================ */
    function renderSeats(hallId) {
        var hallData = findHallData(hallId);
        var preview  = document.getElementById('smt-seat-preview');
        if (!preview) return;
        preview.innerHTML = '';

        if (!hallData || !hallData.seats || hallData.seats.length === 0) {
            preview.innerHTML = '<p class="smt-seat-preview__hint">No seats defined for this hall.</p>';
            return;
        }

        hallData.seats.forEach(function (rowData) {
            var rowEl = document.createElement('div');
            rowEl.className = 'smt-row';

            var lblL = document.createElement('span');
            lblL.className   = 'smt-row__label';
            lblL.textContent = rowData.label;

            var seatsEl = document.createElement('div');
            seatsEl.className = 'smt-row__seats';

            rowData.seats.forEach(function (seat, i) {
                var type = seat.seat_type;
                var isLg = (type === 'Premium' || type === 'Family');
                var s    = document.createElement('span');
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

       KEY FIX:
         Dates are blocked from clicking ONLY when isPast === true.
         .smt-cal-day--staged is a VISUAL hint only — the date stays
         clickable so the user can add a second (non-overlapping) showtime
         on the same date.
    ================================================================ */

    /** All dates that have at least one committed slot for the currently active hall. */
    function getStagedDatesForActiveHall() {
        if (!selectedHallId) return [];
        var result = [];
        var entry  = schedule.find(function (s) { return s.hallId === selectedHallId; });
        if (!entry) return result;
        entry.slotGroups.forEach(function (sg) {
            sg.dates.forEach(function (d) {
                if (result.indexOf(d) === -1) result.push(d);
            });
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
        grid.innerHTML    = '';

        var first = new Date(year, month, 1).getDay();
        var days  = new Date(year, month + 1, 0).getDate();
        var today = new Date(); today.setHours(0, 0, 0, 0);

        var stagedDates = getStagedDatesForActiveHall();

        for (var e = 0; e < first; e++) {
            var empty = document.createElement('div');
            empty.className = 'smt-cal-day smt-cal-day--empty';
            grid.appendChild(empty);
        }

        for (var d = 1; d <= days; d++) {
            var thisDate = new Date(year, month, d);
            var iso      = year + '-' + pad(month + 1) + '-' + pad(d);
            var isPast   = thisDate < today;
            var isSel    = selectedDates.indexOf(iso) !== -1;
            var isStaged = !isSel && stagedDates.indexOf(iso) !== -1;

            var dayEl = document.createElement('div');
            dayEl.className = 'smt-cal-day' +
                (isPast    ? ' smt-cal-day--past'     : '') +
                (isSel     ? ' smt-cal-day--selected' : '') +
                (isStaged  ? ' smt-cal-day--staged'   : '');
            dayEl.textContent = d;
            dayEl.dataset.iso = iso;

            // ── IMPORTANT: only block past dates, NOT staged ones ──
            if (!isPast) {
                dayEl.addEventListener('click', (function (isoVal) {
                    return function () { toggleDate(isoVal); };
                })(iso));
            }

            grid.appendChild(dayEl);
        }
    }

    function toggleDate(iso) {
        var idx = selectedDates.indexOf(iso);
        if (idx === -1) {
            selectedDates.push(iso);
            fetchExistingShowtimes(iso);
        } else {
            selectedDates.splice(idx, 1);
        }
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
            chip.title = 'Click to deselect';
            chip.style.cursor = 'pointer';

            var txt = document.createElement('span');
            txt.textContent = iso;

            // Clicking the chip itself deselects the date (no ✕ button needed)
            chip.addEventListener('click', (function (isoVal) {
                return function () { toggleDate(isoVal); };
            })(iso));

            chip.appendChild(txt);
            container.appendChild(chip);
        });
    }

    /* ================================================================
       FETCH EXISTING SHOWTIMES FOR A DATE
    ================================================================ */
    function fetchExistingShowtimes(isoDate) {
        var container = document.getElementById('smt-existing-list');
        if (!container) return;

        if (!selectedHallId) {
            container.innerHTML = '<p class="smt-existing-hint">Select a hall first to see existing showtimes.</p>';
            return;
        }

        container.innerHTML = '<p class="smt-existing-hint">Loading...</p>';

        fetch('/manager/showtimes/by-date?date=' + isoDate + '&hall_id=' + selectedHallId)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.error) {
                    container.innerHTML = '<p class="smt-existing-hint">' + data.error + '</p>';
                    return;
                }
                if (!data.showtimes || data.showtimes.length === 0) {
                    container.innerHTML = '<p class="smt-existing-hint">No approved showtimes on this date for this hall.</p>';
                    return;
                }
                var html = '';
                data.showtimes.forEach(function (st) {
                    html += '<div class="smt-existing-item">' +
                        '<strong>' + escapeHtml(st.movie_name) + '</strong><br>' +
                        st.start_time + ' → ' + st.end_time +
                        '</div>';
                });
                container.innerHTML = html;
            })
            .catch(function () {
                container.innerHTML = '<p class="smt-existing-hint">Failed to load existing showtimes.</p>';
            });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function (m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
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
       SERVER-SIDE CONFLICT MODAL
       Auto-opens when the blade renders it with data-auto-open="1"
    ================================================================ */
    function initConflictModal() {
        var overlay = document.getElementById('smt-cflct-overlay');
        if (!overlay) return;

        if (overlay.dataset.autoOpen === '1') {
            overlay.style.display = 'flex';
        }

        function closeModal() { overlay.style.display = 'none'; }

        document.querySelectorAll('.smt-cflct-close').forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
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
            syncScheduleJson();

            if (hasRejectedProposal && !rejectedReplaceConfirmed) {
                e.preventDefault();
                openRejectedReplaceModal();
            }
        });
    }

    function openRejectedReplaceModal() {
        var overlay = document.getElementById('smt-resubmit-overlay');
        if (!overlay) return;
        overlay.style.display = 'flex';
    }

    function closeRejectedReplaceModal() {
        var overlay = document.getElementById('smt-resubmit-overlay');
        if (!overlay) return;
        overlay.style.display = 'none';
    }

    function initRejectedReplaceModal() {
        var overlay = document.getElementById('smt-resubmit-overlay');
        if (!overlay) return;

        var denyBtn      = document.getElementById('smt-resubmit-deny');
        var acceptBtn    = document.getElementById('smt-resubmit-accept');
        var replaceField = document.getElementById('smt-replace-rejected');
        var form         = document.getElementById('smt-form');

        if (denyBtn) {
            denyBtn.addEventListener('click', closeRejectedReplaceModal);
        }

        if (acceptBtn && form) {
            acceptBtn.addEventListener('click', function () {
                rejectedReplaceConfirmed = true;
                if (replaceField) replaceField.value = '1';
                syncScheduleJson();
                closeRejectedReplaceModal();
                form.submit();
            });
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeRejectedReplaceModal();
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initHallSidebar();
        initMovieSidebar();
        initClockButtons();
        updateClockDisplay();
        initCalendarNav();
        renderCalendar();
        initAddSlotBtn();
        initClearAllBtn();
        initFormSubmit();
        initRejectedReplaceModal();
        initConflictModal();
        renderPreview();
        updateSubmitBtn();
    });

})();