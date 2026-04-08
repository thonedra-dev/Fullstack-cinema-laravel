/**
 * resources/js/movie_details.js
 *
 * Drives the movie detail + showtime page.
 *
 * DATA (from #md-data data-groups):
 *   stateGroups = [
 *     {
 *       state: "Selangor",
 *       cinemas: [
 *         {
 *           cinema_id: 1,
 *           cinema_name: "TGV Sunway Pyramid",
 *           city: "Petaling Jaya",
 *           dateGroups: [
 *             {
 *               date: "2026-04-09",
 *               label_day: "Today",
 *               label_num: "9",
 *               label_month: "Apr",
 *               theatres: [
 *                 { name: "DELUXE", times: ["07:00 PM", "09:30 PM"] }
 *               ]
 *             }
 *           ]
 *         }
 *       ]
 *     }
 *   ]
 *
 * FLOW:
 *   1. Build state accordion in #md-sidebar
 *   2. Auto-select first state (expanded) + first cinema
 *   3. Render date strip for that cinema's dateGroups
 *   4. Auto-select first date and render theatre blocks + time pills
 *   5. Clicking a state toggle → expand/collapse (one open at a time)
 *   6. Clicking a cinema → switch active cinema, re-render dates+times
 *   7. Clicking a date  → re-render times only
 */
(function () {
    'use strict';

    /* ── Data ──────────────────────────────────────────────── */
    var dataEl = document.getElementById('md-data');
    if (!dataEl) return;

    var stateGroups = JSON.parse(dataEl.dataset.groups || '[]');
    if (!stateGroups || stateGroups.length === 0) return;

    /* ── DOM refs ──────────────────────────────────────────── */
    var sidebarEl       = document.getElementById('md-sidebar');
    var dateStripEl     = document.getElementById('md-date-strip');
    var showtimeSect    = document.getElementById('md-showtime-section');
    var cinemaLabel     = document.getElementById('md-cinema-label');

    if (!sidebarEl || !dateStripEl || !showtimeSect) return;

    /* ── Active state ──────────────────────────────────────── */
    var activeCinema  = null;   // cinema object
    var activeDateIdx = 0;

    /* ================================================================
       BUILD SIDEBAR — state accordion
    ================================================================ */
    function buildSidebar() {
        sidebarEl.innerHTML = '';

        stateGroups.forEach(function (sg, stateIdx) {
            var groupEl = document.createElement('div');
            groupEl.className = 'md-state-group' + (stateIdx === 0 ? ' md-state-group--open' : '');

            /* State toggle button */
            var toggleBtn = document.createElement('button');
            toggleBtn.className = 'md-state-toggle';
            toggleBtn.innerHTML =
                '<span>' + sg.state + '</span>' +
                '<span class="md-state-toggle__chevron">▾</span>';

            toggleBtn.addEventListener('click', function () {
                var isOpen = groupEl.classList.contains('md-state-group--open');
                // Close all
                sidebarEl.querySelectorAll('.md-state-group').forEach(function (g) {
                    g.classList.remove('md-state-group--open');
                });
                // Toggle clicked
                if (!isOpen) groupEl.classList.add('md-state-group--open');
            });

            groupEl.appendChild(toggleBtn);

            /* Cinema list */
            var listEl = document.createElement('div');
            listEl.className = 'md-cinema-list';

            sg.cinemas.forEach(function (cinema, cinemaIdx) {
                var item = document.createElement('div');
                item.className = 'md-cinema-item';
                item.dataset.cinemaId = cinema.cinema_id;
                item.innerHTML =
                    '<div>' +
                        '<div class="md-cinema-item__name">' + cinema.cinema_name + '</div>' +
                        '<div class="md-cinema-item__city">' + cinema.city + '</div>' +
                    '</div>';

                item.addEventListener('click', function () {
                    selectCinema(cinema);
                });

                listEl.appendChild(item);

                /* Auto-select very first cinema */
                if (stateIdx === 0 && cinemaIdx === 0) {
                    item.classList.add('md-cinema-item--active');
                    activeCinema = cinema;
                }
            });

            groupEl.appendChild(listEl);
            sidebarEl.appendChild(groupEl);
        });
    }

    /* ================================================================
       SELECT CINEMA  — marks it active, re-renders dates + times
    ================================================================ */
    function selectCinema(cinema) {
        activeCinema  = cinema;
        activeDateIdx = 0;

        /* Update sidebar highlight */
        sidebarEl.querySelectorAll('.md-cinema-item').forEach(function (item) {
            var active = parseInt(item.dataset.cinemaId, 10) === cinema.cinema_id;
            item.classList.toggle('md-cinema-item--active', active);
        });

        /* Update cinema header */
        if (cinemaLabel) {
            cinemaLabel.textContent = cinema.cinema_name;
        }

        /* Rebuild date strip and show first date */
        renderDateStrip(cinema.dateGroups);
    }

    /* ================================================================
       DATE STRIP
    ================================================================ */
    function renderDateStrip(dateGroups) {
        dateStripEl.innerHTML = '';

        if (!dateGroups || dateGroups.length === 0) {
            showtimeSect.innerHTML =
                '<p class="md-select-hint">No showtimes available for this cinema.</p>';
            return;
        }

        dateGroups.forEach(function (dg, idx) {
            var btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'md-date-btn' + (idx === activeDateIdx ? ' md-date-btn--active' : '');
            btn.dataset.idx = idx;

            btn.innerHTML =
                '<span class="md-date-btn__day">'   + dg.label_day   + '</span>' +
                '<div class="md-date-btn__num-wrap">' +
                    '<span class="md-date-btn__num">'   + dg.label_num   + '</span>' +
                    '<span class="md-date-btn__month">' + dg.label_month + '</span>' +
                '</div>';

            btn.addEventListener('click', function () {
                activeDateIdx = idx;
                /* Update active class */
                dateStripEl.querySelectorAll('.md-date-btn').forEach(function (b) {
                    b.classList.remove('md-date-btn--active');
                });
                btn.classList.add('md-date-btn--active');
                renderShowtimes(dateGroups[idx]);
            });

            dateStripEl.appendChild(btn);
        });

        /* Auto-render first date */
        renderShowtimes(dateGroups[activeDateIdx]);
    }

    /* ================================================================
       SHOWTIME SECTION — theatre blocks + time pills
    ================================================================ */
    function renderShowtimes(dateGroup) {
        showtimeSect.innerHTML = '';

        if (!dateGroup || !dateGroup.theatres || dateGroup.theatres.length === 0) {
            showtimeSect.innerHTML =
                '<p class="md-select-hint">No showtimes on this date.</p>';
            return;
        }

        dateGroup.theatres.forEach(function (theatre) {
            var block = document.createElement('div');
            block.className = 'md-theatre-block';

            /* Theatre name */
            var nameEl = document.createElement('div');
            nameEl.className   = 'md-theatre-name';
            nameEl.textContent = theatre.name;
            block.appendChild(nameEl);

            /* Time pills */
            var pillsWrap = document.createElement('div');
            pillsWrap.className = 'md-time-pills';

            if (!theatre.times || theatre.times.length === 0) {
                var hint = document.createElement('span');
                hint.style.cssText = 'font-size:0.78rem;color:var(--md-text-muted);';
                hint.textContent   = 'No times available.';
                pillsWrap.appendChild(hint);
            } else {
                theatre.times.forEach(function (time) {
                    var pill = document.createElement('button');
                    pill.type        = 'button';
                    pill.className   = 'md-time-pill';
                    pill.textContent = time;
                    /* Placeholder — booking action to be wired later */
                    pill.addEventListener('click', function () {
                        // future: open seat selection
                    });
                    pillsWrap.appendChild(pill);
                });
            }

            block.appendChild(pillsWrap);
            showtimeSect.appendChild(block);
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    buildSidebar();

    /* Trigger initial render with first cinema */
    if (activeCinema) {
        selectCinema(activeCinema);
    }

})();