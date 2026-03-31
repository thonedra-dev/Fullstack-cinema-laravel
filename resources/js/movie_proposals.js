/**
 * resources/js/movie_proposals.js
 * Detail page behaviour:
 *   - Theatre tab switching (updates theatre image, name, slot count)
 *   - Mini calendar render & navigation
 *   - Alarm-clock display (updates on calendar day click)
 *   - Full-list popup
 *   - Reject modal
 */
(function () {
    'use strict';

    // Only run on the detail page
    var dataEl = document.getElementById('mpd-slot-data');
    if (!dataEl) return;

    // ── Data ──────────────────────────────────────────────────
    var theatresData = JSON.parse(dataEl.dataset.theatres || '[]');
    if (theatresData.length === 0) return;

    var MONTHS = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];

    // Active state
    var activeIdx  = 0;
    var slotDates  = {};     // { 'YYYY-MM-DD': slotObj }
    var calDate    = new Date();

    // ── Helpers ───────────────────────────────────────────────
    function pad(n) { return String(n).padStart(2, '0'); }

    function el(id) { return document.getElementById(id); }

    // ── Clock ─────────────────────────────────────────────────
    function updateClock(slot) {
        var hEl    = el('mpd-clock-h');
        var mEl    = el('mpd-clock-m');
        var ampmEl = el('mpd-clock-ampm');
        var endEl  = el('mpd-clock-end');
        if (!hEl) return;

        if (!slot) {
            hEl.textContent    = '--';
            mEl.textContent    = '--';
            ampmEl.textContent = '--';
            endEl.textContent  = '—';
            return;
        }
        hEl.textContent    = pad(slot.start_h);
        mEl.textContent    = pad(slot.start_m);
        ampmEl.textContent = slot.start_ampm;
        endEl.textContent  = slot.end_display;
    }

    // ── Calendar ──────────────────────────────────────────────
    function renderCal() {
        var grid  = el('mpd-cal-grid');
        var label = el('mpd-cal-month');
        if (!grid || !label) return;

        var year  = calDate.getFullYear();
        var month = calDate.getMonth();

        label.textContent = MONTHS[month] + ' ' + year;
        grid.innerHTML    = '';

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();

        // Leading empty cells
        for (var e = 0; e < firstDay; e++) {
            var empty = document.createElement('div');
            empty.className = 'mpd-cal-day mpd-cal-day--empty';
            grid.appendChild(empty);
        }

        // Day cells
        for (var d = 1; d <= daysInMonth; d++) {
            var iso     = year + '-' + pad(month + 1) + '-' + pad(d);
            var hasSlot = Object.prototype.hasOwnProperty.call(slotDates, iso);
            var cell    = document.createElement('div');

            cell.className   = 'mpd-cal-day' + (hasSlot ? ' mpd-cal-day--has-slot' : '');
            cell.textContent = d;

            if (hasSlot) {
                var s = slotDates[iso];
                cell.title = s.start + ' → ' + s.end;

                cell.addEventListener('click', (function (slot) {
                    return function () {
                        grid.querySelectorAll('.mpd-cal-day--active')
                            .forEach(function (x) { x.classList.remove('mpd-cal-day--active'); });
                        this.classList.add('mpd-cal-day--active');
                        updateClock(slot);
                    };
                })(s));
            }

            grid.appendChild(cell);
        }
    }

    // ── Theatre tab switch ────────────────────────────────────
    function loadTheatre(idx) {
        var t = theatresData[idx];
        if (!t) return;
        activeIdx = idx;

        // Update name
        var nameEl = el('mpd-theatre-name');
        if (nameEl) nameEl.textContent = t.theatreName;

        // Update image / placeholder
        var imgEl = el('mpd-theatre-img');
        var phEl  = el('mpd-theatre-ph');
        if (t.theatrePoster) {
            if (imgEl) { imgEl.src = t.theatrePoster; imgEl.style.display = 'block'; }
            if (phEl)  phEl.style.display = 'none';
        } else {
            if (imgEl) imgEl.style.display = 'none';
            if (phEl)  phEl.style.display = 'flex';
        }

        // Update slot count label
        var cntEl = el('mpd-slot-count');
        if (cntEl) cntEl.textContent = t.slots.length + ' slot(s)';

        // Rebuild slot-date index
        slotDates = {};
        t.slots.forEach(function (s) { slotDates[s.date] = s; });

        // Navigate calendar to first slot month
        if (t.slots.length > 0) {
            var fd = new Date(t.slots[0].date);
            calDate = new Date(fd.getFullYear(), fd.getMonth(), 1);
        } else {
            calDate = new Date();
        }

        renderCal();
        updateClock(t.slots[0] || null);

        // Refresh popup list
        renderPopupList(t);
    }

    // ── Theatre tabs (if present) ─────────────────────────────
    var tabs = document.querySelectorAll('.mpd-theatre-tab');
    tabs.forEach(function (tab, idx) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('mpd-theatre-tab--active'); });
            tab.classList.add('mpd-theatre-tab--active');
            loadTheatre(idx);

            // Keep popup theatre name in sync
            var ptn = el('mpd-popup-theatre-name');
            if (ptn) ptn.textContent = theatresData[idx].theatreName;
        });
    });

    // ── Calendar navigation ───────────────────────────────────
    var prevBtn = el('mpd-cal-prev');
    var nextBtn = el('mpd-cal-next');
    if (prevBtn) prevBtn.addEventListener('click', function () {
        calDate.setMonth(calDate.getMonth() - 1); renderCal();
    });
    if (nextBtn) nextBtn.addEventListener('click', function () {
        calDate.setMonth(calDate.getMonth() + 1); renderCal();
    });

    // ── Full-list popup ───────────────────────────────────────
    function renderPopupList(theatre) {
        var list = el('mpd-slots-list');
        var cnt  = el('mpd-popup-count');
        if (!list) return;

        list.innerHTML = '';

        theatre.slots.forEach(function (s) {
            var row = document.createElement('div');
            row.className = 'mpd-slot-row';

            var dateSpan = document.createElement('span');
            dateSpan.className = 'mpd-slot-date';
            dateSpan.textContent = s.dateLabel;

            var timeSpan = document.createElement('span');
            timeSpan.className = 'mpd-slot-time';
            timeSpan.textContent = s.start + ' → ' + s.end;

            row.appendChild(dateSpan);
            row.appendChild(timeSpan);
            list.appendChild(row);
        });

        if (cnt) cnt.textContent = theatre.slots.length;
    }

    var popupOverlay = el('mpd-popup-overlay');
    var maxBtn       = el('mpd-maximize-btn');
    var closeBtn     = el('mpd-popup-close');

    if (maxBtn) maxBtn.addEventListener('click', function () {
        popupOverlay.style.display = 'flex';
    });
    if (closeBtn) closeBtn.addEventListener('click', function () {
        popupOverlay.style.display = 'none';
    });
    if (popupOverlay) popupOverlay.addEventListener('click', function (e) {
        if (e.target === popupOverlay) popupOverlay.style.display = 'none';
    });

    // ── Reject modal ──────────────────────────────────────────
    var rejectOverlay = el('mpd-reject-overlay');
    var triggerBtn    = el('mpd-reject-trigger');
    var cancelBtn     = el('mpd-reject-cancel');

    if (triggerBtn) triggerBtn.addEventListener('click', function () {
        rejectOverlay.style.display = 'flex';
    });
    if (cancelBtn) cancelBtn.addEventListener('click', function () {
        rejectOverlay.style.display = 'none';
    });
    if (rejectOverlay) rejectOverlay.addEventListener('click', function (e) {
        if (e.target === rejectOverlay) rejectOverlay.style.display = 'none';
    });

    // ── Bootstrap on page load ────────────────────────────────
    loadTheatre(0);

})();