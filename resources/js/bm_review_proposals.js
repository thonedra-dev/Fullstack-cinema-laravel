/**
 * resources/js/bm_review_proposal.js
 *
 * Theatre tab switching, mini calendar, showtime list,
 * full-list popup, and PDF download (using browser print).
 */
(function () {
    'use strict';

    function init() {
        var dataEl = document.getElementById('rp-slot-data');
        if (!dataEl) return;

        var theatresData = JSON.parse(dataEl.dataset.theatres || '[]');
        if (theatresData.length === 0) return;

        var MONTHS = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];

        var activeIdx  = 0;
        var slotDates  = {};
        var calDate    = new Date();
        var activeDate = null;

        function pad(n) { return String(n).padStart(2, '0'); }
        function el(id) { return document.getElementById(id); }

        /* ── Showtime list ─────────────────────────── */
        function updateShowtimeList(date, slots) {
            var dateEl = el('rp-showtime-date');
            var listEl = el('rp-showtime-list');
            var countEl = el('rp-showtime-count');
            if (!listEl) return;

            listEl.innerHTML = '';

            if (!slots || slots.length === 0) {
                if (dateEl) dateEl.textContent = 'No date selected';
                if (countEl) countEl.textContent = '0 slots';
                var empty = document.createElement('li');
                empty.className = 'rp-showtime-item rp-showtime-item--empty';
                empty.textContent = 'Select a highlighted date to view proposed showtimes.';
                listEl.appendChild(empty);
                return;
            }

            if (dateEl) dateEl.textContent = slots[0].dateLabel || date;
            if (countEl) countEl.textContent = slots.length + ' slot' + (slots.length === 1 ? '' : 's');

            slots.forEach(function (slot, idx) {
                var item = document.createElement('li');
                item.className = 'rp-showtime-item';

                var dot = document.createElement('span');
                dot.className = 'rp-showtime-dot';

                var body = document.createElement('span');
                body.className = 'rp-showtime-copy';

                var label = document.createElement('span');
                label.className = 'rp-showtime-label';
                label.textContent = 'Showtime ' + (idx + 1);

                var time = document.createElement('span');
                time.className = 'rp-showtime-time';
                time.textContent = slot.start + ' to ' + slot.end;

                body.appendChild(label);
                body.appendChild(time);
                item.appendChild(dot);
                item.appendChild(body);
                listEl.appendChild(item);
            });
        }

        /* ── Calendar ──────────────────────────────── */
        function renderCal() {
            var grid  = el('rp-cal-grid');
            var label = el('rp-cal-month');
            if (!grid || !label) return;

            var year  = calDate.getFullYear();
            var month = calDate.getMonth();

            label.textContent = MONTHS[month] + ' ' + year;
            grid.innerHTML    = '';

            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();

            for (var e = 0; e < firstDay; e++) {
                var empty = document.createElement('div');
                empty.className = 'rp-cal-day rp-cal-day--empty';
                grid.appendChild(empty);
            }

            for (var d = 1; d <= daysInMonth; d++) {
                var iso     = year + '-' + pad(month + 1) + '-' + pad(d);
                var slots   = slotDates[iso] || [];
                var hasSlot = slots.length > 0;
                var cell    = document.createElement('div');

                cell.className = 'rp-cal-day'
                    + (hasSlot ? ' rp-cal-day--has-slot' : '')
                    + (iso === activeDate ? ' rp-cal-day--active' : '');
                cell.textContent = d;

                if (hasSlot) {
                    cell.title = slots[0].start + ' → ' + slots[slots.length - 1].end;
                    cell.addEventListener('click', (function (dateKey, daySlots) {
                        return function () {
                            grid.querySelectorAll('.rp-cal-day--active')
                                .forEach(function (x) { x.classList.remove('rp-cal-day--active'); });
                            this.classList.add('rp-cal-day--active');
                            activeDate = dateKey;
                            updateShowtimeList(dateKey, daySlots);
                        };
                    })(iso, slots));
                }

                grid.appendChild(cell);
            }
        }

        /* ── Theatre tab switch ────────────────────── */
        function loadTheatre(idx) {
            var t = theatresData[idx];
            if (!t) return;
            activeIdx = idx;

            var nameEl = el('rp-theatre-name');
            if (nameEl) nameEl.textContent = t.theatreName;

            var imgEl = el('rp-theatre-img');
            var phEl  = el('rp-theatre-ph');
            if (t.theatrePoster) {
                if (imgEl) { imgEl.src = t.theatrePoster; imgEl.style.display = 'block'; }
                if (phEl)  phEl.style.display = 'none';
            } else {
                if (imgEl) imgEl.style.display = 'none';
                if (phEl)  phEl.style.display = 'flex';
            }

            var cntEl = el('rp-slot-count');
            if (cntEl) cntEl.textContent = t.slots.length + ' slot(s)';

            slotDates = {};
            t.slots.forEach(function (s) {
                if (!slotDates[s.date]) slotDates[s.date] = [];
                slotDates[s.date].push(s);
            });

            if (t.slots.length > 0) {
                var parts = t.slots[0].date.split('-');
                calDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, 1);
                activeDate = t.slots[0].date;
            } else {
                calDate = new Date();
                activeDate = null;
            }

            renderCal();
            updateShowtimeList(activeDate, activeDate ? slotDates[activeDate] : []);
            renderPopupList(t);
        }

        /* ── Theatre tabs ──────────────────────────── */
        var tabs = document.querySelectorAll('.rp-theatre-tab');
        tabs.forEach(function (tab, idx) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('rp-theatre-tab--active'); });
                tab.classList.add('rp-theatre-tab--active');
                loadTheatre(idx);

                var ptn = el('rp-popup-theatre-name');
                if (ptn) ptn.textContent = theatresData[idx].theatreName;
            });
        });

        /* ── Calendar navigation ───────────────────── */
        var prevBtn = el('rp-cal-prev');
        var nextBtn = el('rp-cal-next');
        if (prevBtn) prevBtn.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() - 1); renderCal();
        });
        if (nextBtn) nextBtn.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() + 1); renderCal();
        });

        /* ── Full-list popup ───────────────────────── */
        function renderPopupList(theatre) {
            var list = el('rp-slots-list');
            var cnt  = el('rp-popup-count');
            if (!list) return;

            list.innerHTML = '';

            theatre.slots.forEach(function (s) {
                var row = document.createElement('div');
                row.className = 'rp-slot-row';

                var dateSpan = document.createElement('span');
                dateSpan.className = 'rp-slot-date';
                dateSpan.textContent = s.dateLabel;

                var timeSpan = document.createElement('span');
                timeSpan.className = 'rp-slot-time';
                timeSpan.textContent = s.start + ' → ' + s.end;

                row.appendChild(dateSpan);
                row.appendChild(timeSpan);
                list.appendChild(row);
            });

            if (cnt) cnt.textContent = theatre.slots.length;
        }

        var popupOverlay = el('rp-popup-overlay');
        var maxBtn       = el('rp-maximize-btn');
        var closeBtn     = el('rp-popup-close');

        if (maxBtn) maxBtn.addEventListener('click', function () {
            popupOverlay.style.display = 'flex';
        });
        if (closeBtn) closeBtn.addEventListener('click', function () {
            popupOverlay.style.display = 'none';
        });
        if (popupOverlay) popupOverlay.addEventListener('click', function (e) {
            if (e.target === popupOverlay) popupOverlay.style.display = 'none';
        });

        /* ── PDF download (using browser print) ───── */
        var pdfBtn = el('rp-pdf-btn');
        var nameEl = el('rp-movie-name');
        var movieName = nameEl ? (nameEl.dataset.name || 'proposal') : 'proposal';

        function sanitise(str) {
            return str.replace(/[^a-zA-Z0-9 \-_]/g, '').trim().replace(/\s+/g, '_');
        }

        if (pdfBtn) {
            pdfBtn.addEventListener('click', function () {
                // Trigger browser print dialog – the print stylesheet will hide nav, adjust layout
                window.print();
            });
        }

        /* ── Bootstrap ─────────────────────────────── */
        loadTheatre(0);
    }

    init();
})();