/**
 * setup_timetable.js
 * Place at: resources/js/setup_timetable.js
 *
 * Responsibilities:
 *   1. Read inline data (theatres+seats, existing showtimes, entry mode)
 *   2. Theatre/movie sidebar selection → highlight + update hidden inputs
 *   3. Seat layout renderer (same icon CSS as admin system)
 *   4. Alarm-clock time picker (hour, minute, AM/PM scroll)
 *   5. End-time preview calculation
 *   6. Multi-date calendar with chip preview
 *   7. Client-side conflict detection (pre-validation before submit)
 */

(function () {
    'use strict';

    /* ================================================================
       BOOT — read data from the data attributes on the config div
    ================================================================ */
    var configEl = document.getElementById('smt-seat-data-json');
    if (!configEl) return;

    var allTheatres      = JSON.parse(configEl.dataset.theatres      || '[]');
    var existingShowtimes = JSON.parse(configEl.dataset.existingShowtimes || '[]');
    var mode             = configEl.dataset.preselectedMode;    // 'movie' | 'theatre'
    var preTheatreId     = configEl.dataset.preselectedTheatreId || '';
    var preMovieId       = configEl.dataset.preselectedMovieId   || '';
    var preRuntime       = parseInt(configEl.dataset.preselectedRuntime || '0', 10);

    // Working state
    var selectedTheatreId = preTheatreId  ? parseInt(preTheatreId, 10)  : null;
    var selectedMovieId   = preMovieId    ? parseInt(preMovieId, 10)    : null;
    var selectedRuntime   = preRuntime    || 0;
    var selectedDates     = [];

    // Time state
    var clockHour   = 7;
    var clockMinute = 0;
    var clockAmPm   = 'AM';

    // Calendar state
    var calDate = new Date();
    calDate.setDate(1);

    /* ================================================================
       HELPERS
    ================================================================ */
    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden');    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function findTheatre(id) {
        return allTheatres.find(function (t) { return t.id === id; });
    }

    function toMinutes(iso) {
        return new Date(iso).getTime() / 60000;
    }

    /* ================================================================
       1. SIDEBAR — theatre selection (movie-first mode)
    ================================================================ */
    function initTheatreSidebar() {
        document.querySelectorAll('.smt-theatre-radio').forEach(function (radio) {
            radio.addEventListener('change', function () {
                selectedTheatreId = parseInt(this.value, 10);
                document.getElementById('smt-hidden-theatre').value = selectedTheatreId;

                document.querySelectorAll('.smt-theatre-row').forEach(function (row) {
                    row.classList.remove('is-selected');
                });
                this.closest('.smt-theatre-row').classList.add('is-selected');

                renderSeats(selectedTheatreId);
                updateEndTimePreview();
                runConflictCheck();
            });
        });

        // If theatre pre-selected (theatre-first mode), render immediately
        if (selectedTheatreId) {
            renderSeats(selectedTheatreId);
        }
    }

    /* ================================================================
       2. SIDEBAR — movie selection (theatre-first mode)
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
                runConflictCheck();
            });
        });

        // If theatre-first, render the preselected theatre's seats immediately
        if (mode === 'theatre' && selectedTheatreId) {
            renderSeats(selectedTheatreId);
        }
    }

    /* ================================================================
       3. SEAT LAYOUT RENDERER
    ================================================================ */
    function renderSeats(theatreId) {
        var theatre  = findTheatre(theatreId);
        var preview  = document.getElementById('smt-seat-preview');
        var hint     = document.getElementById('smt-seat-hint');

        if (!preview) return;

        // Clear
        preview.innerHTML = '';

        if (!theatre || !theatre.seats || theatre.seats.length === 0) {
            preview.innerHTML = '<p class="smt-seat-preview__hint">No seats defined for this theatre.</p>';
            return;
        }

        theatre.seats.forEach(function (rowData) {
            var rowEl = document.createElement('div');
            rowEl.className = 'smt-row';

            var lblEl = document.createElement('span');
            lblEl.className   = 'smt-row__label';
            lblEl.textContent = rowData.label;
            rowEl.appendChild(lblEl);

            var seatsEl = document.createElement('div');
            seatsEl.className = 'smt-row__seats';

            rowData.seats.forEach(function (seat, i) {
                var type    = seat.seat_type;
                var seatEl  = document.createElement('span');
                var isLarge = (type === 'Premium' || type === 'Family');

                seatEl.className = 'sb-seat sb-seat--' + type.toLowerCase() +
                    (isLarge ? ' sb-seat--lg' : '');

                seatsEl.appendChild(seatEl);

                // Gap after every 2nd couple seat
                if (type === 'Couple' && (i + 1) % 2 === 0 &&
                    i + 1 < rowData.seats.length) {
                    var gap = document.createElement('span');
                    gap.className = 'sb-couple-gap';
                    seatsEl.appendChild(gap);
                }
            });

            rowEl.appendChild(seatsEl);

            // Right label mirror
            var lblR = document.createElement('span');
            lblR.className   = 'smt-row__label';
            lblR.textContent = rowData.label;
            rowEl.appendChild(lblR);

            preview.appendChild(rowEl);
        });
    }

    /* ================================================================
       4 & 5. ALARM-CLOCK TIME PICKER + END TIME PREVIEW
    ================================================================ */
    function updateClockDisplay() {
        var hEl = document.getElementById('smt-hour-display');
        var mEl = document.getElementById('smt-minute-display');
        var aEl = document.getElementById('smt-ampm-display');

        if (hEl) hEl.textContent = pad(clockHour);
        if (mEl) mEl.textContent = pad(clockMinute);
        if (aEl) aEl.textContent = clockAmPm;

        document.getElementById('smt-input-hour').value   = clockHour;
        document.getElementById('smt-input-minute').value = clockMinute;
        document.getElementById('smt-input-ampm').value   = clockAmPm;

        updateEndTimePreview();
        runConflictCheck();
    }

    function updateEndTimePreview() {
        var el = document.getElementById('smt-end-time-val');
        if (!el) return;

        if (!selectedRuntime) { el.textContent = '—'; return; }

        // Convert to 24h for calculation
        var h24 = clockHour % 12;
        if (clockAmPm === 'PM') h24 += 12;

        var startMin = h24 * 60 + clockMinute;
        var endMin   = startMin + selectedRuntime;

        var endH = Math.floor(endMin / 60) % 24;
        var endM = endMin % 60;
        var ampm = endH >= 12 ? 'PM' : 'AM';
        var displayH = endH % 12 || 12;

        el.textContent = pad(displayH) + ':' + pad(endM) + ' ' + ampm;
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
       6. MULTI-DATE CALENDAR
    ================================================================ */
    var monthNames = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];

    function renderCalendar() {
        var grid    = document.getElementById('smt-cal-grid');
        var label   = document.getElementById('smt-cal-month');
        if (!grid || !label) return;

        label.textContent = monthNames[calDate.getMonth()] + ' ' + calDate.getFullYear();
        grid.innerHTML    = '';

        var year  = calDate.getFullYear();
        var month = calDate.getMonth();
        var first = new Date(year, month, 1).getDay();  // 0=Sun
        var days  = new Date(year, month + 1, 0).getDate();
        var today = new Date();
        today.setHours(0,0,0,0);

        // Empty cells before first day
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

            var dayEl = document.createElement('div');
            dayEl.className = 'smt-cal-day' +
                (isPast ? ' smt-cal-day--past' : '') +
                (isSel  ? ' smt-cal-day--selected' : '');
            dayEl.textContent = d;
            dayEl.dataset.iso = iso;

            if (!isPast) {
                dayEl.addEventListener('click', function () {
                    toggleDate(this.dataset.iso);
                });
            }

            grid.appendChild(dayEl);
        }
    }

    function toggleDate(iso) {
        var idx = selectedDates.indexOf(iso);
        if (idx === -1) {
            selectedDates.push(iso);
        } else {
            selectedDates.splice(idx, 1);
        }
        selectedDates.sort();
        renderCalendar();
        renderDateChips();
        renderHiddenDates();
        runConflictCheck();
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

            var rm = document.createElement('span');
            rm.className   = 'smt-date-chip__remove';
            rm.textContent = '✕';
            rm.setAttribute('data-iso', iso);
            rm.addEventListener('click', function () {
                toggleDate(this.getAttribute('data-iso'));
            });

            chip.appendChild(txt);
            chip.appendChild(rm);
            container.appendChild(chip);
        });
    }

    function renderHiddenDates() {
        var container = document.getElementById('smt-hidden-dates');
        if (!container) return;
        container.innerHTML = '';

        selectedDates.forEach(function (iso) {
            var input       = document.createElement('input');
            input.type      = 'hidden';
            input.name      = 'dates[]';
            input.value     = iso;
            container.appendChild(input);
        });
    }

    function initCalendarNav() {
        var prev = document.getElementById('smt-cal-prev');
        var next = document.getElementById('smt-cal-next');

        if (prev) prev.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() - 1);
            renderCalendar();
        });

        if (next) next.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() + 1);
            renderCalendar();
        });
    }

    /* ================================================================
       7. CLIENT-SIDE CONFLICT CHECK (preview only)
       Shows warnings inline; server also validates before insert.
    ================================================================ */
    function toStartEndMinutes(dateIso) {
        // Convert clock state to minutes since epoch for comparison
        var h24 = clockHour % 12;
        if (clockAmPm === 'PM') h24 += 12;

        var startMs = new Date(dateIso + 'T' +
            pad(h24) + ':' + pad(clockMinute) + ':00').getTime();
        var endMs   = startMs + selectedRuntime * 60000;

        return { startMs: startMs, endMs: endMs };
    }

    function runConflictCheck() {
        var area = document.getElementById('smt-conflict-area');
        var list = document.getElementById('smt-conflict-list');
        if (!area || !list) return;

        list.innerHTML = '';

        if (!selectedTheatreId || !selectedRuntime || selectedDates.length === 0) {
            hide(area);
            return;
        }

        var conflicts = [];

        selectedDates.forEach(function (iso) {
            var times = toStartEndMinutes(iso);

            var conflict = existingShowtimes.find(function (st) {
                if (st.theatre_id !== selectedTheatreId) return false;
                var exStart = new Date(st.start).getTime();
                var exEnd   = new Date(st.end).getTime();
                // Overlap: new_start < ex_end AND new_end > ex_start
                return times.startMs < exEnd && times.endMs > exStart;
            });

            if (conflict) {
                conflicts.push(iso);
            }
        });

        if (conflicts.length === 0) {
            hide(area);
        } else {
            conflicts.forEach(function (iso) {
                var item = document.createElement('div');
                item.className   = 'smt-conflict-item';
                item.textContent = '⚠ ' + iso + ' overlaps with an existing showtime in this theatre.';
                list.appendChild(item);
            });
            show(area);
        }
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
    });

})();