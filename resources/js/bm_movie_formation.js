/**
 * resources/js/bm_movie_formation.js
 *
 * TGV-style date/showtime renderer for bm_movie_formation.blade.php
 *
 * DATA (from #bmf-data data-dates attribute):
 *   dateGroups = [
 *     {
 *       date        : 'YYYY-MM-DD',
 *       label_day   : 'Mon' | 'Today',
 *       label_num   : '6',
 *       label_month : 'Apr',
 *       theatres    : [
 *         { name: 'DELUXE', times: ['07:00 PM', '09:30 PM'] },
 *         ...
 *       ]
 *     },
 *     ...
 *   ]
 *
 * BEHAVIOUR:
 *   1. Render date buttons in #bmf-date-strip
 *   2. On date click, render theatre blocks + time pills in #bmf-showtime-section
 *   3. Auto-select first date on load
 */
(function () {
    'use strict';

    var dataEl = document.getElementById('bmf-data');
    if (!dataEl) return;

    var dateGroups = JSON.parse(dataEl.dataset.dates || '[]');
    if (dateGroups.length === 0) return;

    var dateStrip    = document.getElementById('bmf-date-strip');
    var showtimeSection = document.getElementById('bmf-showtime-section');

    if (!dateStrip || !showtimeSection) return;

    /* ── Build date strip ─────────────────────────────────── */
    function buildDateStrip() {
        dateGroups.forEach(function (group, idx) {
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'bmf-date-btn';
            btn.dataset.idx = idx;

            var dayEl   = document.createElement('span');
            dayEl.className   = 'bmf-date-btn__day';
            dayEl.textContent = group.label_day;

            var numEl   = document.createElement('span');
            numEl.className   = 'bmf-date-btn__num';
            numEl.textContent = group.label_num;

            var monthEl = document.createElement('span');
            monthEl.className   = 'bmf-date-btn__month';
            monthEl.textContent = group.label_month;

            btn.appendChild(dayEl);
            btn.appendChild(numEl);
            btn.appendChild(monthEl);

            btn.addEventListener('click', function () {
                // Update active state
                dateStrip.querySelectorAll('.bmf-date-btn').forEach(function (b) {
                    b.classList.remove('bmf-date-btn--active');
                });
                btn.classList.add('bmf-date-btn--active');

                renderShowtimes(group);
            });

            dateStrip.appendChild(btn);
        });
    }

    /* ── Render theatres + time pills for a given date group ─ */
    function renderShowtimes(group) {
        showtimeSection.innerHTML = '';

        if (!group.theatres || group.theatres.length === 0) {
            var empty = document.createElement('p');
            empty.style.cssText = 'color:var(--bm-text-muted);font-size:0.82rem;padding:16px 0;';
            empty.textContent   = 'No showtimes available for this date.';
            showtimeSection.appendChild(empty);
            return;
        }

        group.theatres.forEach(function (theatre) {
            // Theatre block wrapper
            var block = document.createElement('div');
            block.className = 'bmf-theatre-block';

            // Theatre name heading
            var nameEl = document.createElement('div');
            nameEl.className   = 'bmf-theatre-name';
            nameEl.textContent = theatre.name;
            block.appendChild(nameEl);

            // Time pills row
            var pillsWrap = document.createElement('div');
            pillsWrap.className = 'bmf-time-pills';

            if (!theatre.times || theatre.times.length === 0) {
                var hint = document.createElement('span');
                hint.style.cssText = 'font-size:0.78rem;color:var(--bm-text-muted);';
                hint.textContent   = 'No times available.';
                pillsWrap.appendChild(hint);
            } else {
                theatre.times.forEach(function (time) {
                    var pill = document.createElement('button');
                    pill.type      = 'button';
                    pill.className = 'bmf-time-pill';
                    pill.textContent = time;
                    // Placeholder click — no action yet
                    pill.addEventListener('click', function () {
                        // future: trigger seat selection or booking
                    });
                    pillsWrap.appendChild(pill);
                });
            }

            block.appendChild(pillsWrap);
            showtimeSection.appendChild(block);
        });
    }

    /* ── Init ─────────────────────────────────────────────── */
    buildDateStrip();

    // Auto-select first date button
    var firstBtn = dateStrip.querySelector('.bmf-date-btn');
    if (firstBtn) firstBtn.click();

})();