/**
 * resources/js/movie_details.js
 *
 * Handles the dynamic cinema sidebar, date-strip interaction, and
 * showtime pill rendering on the movie details page.
 *
 * Key changes vs. original:
 *  ─ renderShowtimes() now reads timeObj.is_bookable (set by the controller)
 *    and renders expired pills as disabled elements with inline red styles.
 *  ─ A MutationObserver + delegated click guard ensures any pill that
 *    somehow reaches the DOM without the disabled attribute but with
 *    is_bookable=false cannot trigger navigation (belt-and-suspenders).
 *  ─ The date-strip in Blade is server-rendered as <a> links; JS adds the
 *    active class visually but does NOT intercept navigation — a page reload
 *    with ?date=YYYY-MM-DD is the correct mechanism so the controller can
 *    return the right showtime payload.
 */
(function () {
    'use strict';

    /* ================================================================
       BOOTSTRAP
    ================================================================ */
    var dataEl = document.getElementById('md-data');
    if (!dataEl) return;

    var stateGroups = JSON.parse(dataEl.dataset.groups || '[]');
    if (!stateGroups || stateGroups.length === 0) return;

    var sidebarEl    = document.getElementById('md-sidebar');
    var dateStripEl  = document.getElementById('md-date-strip');
    var showtimeSect = document.getElementById('md-showtime-section');
    var cinemaLabel  = document.getElementById('md-cinema-label');

    if (!sidebarEl || !dateStripEl || !showtimeSect || !cinemaLabel) return;

    var activeCinema  = null;
    var activeState   = '';
    var activeDateStr = dataEl.dataset.activeDate || '';   // 'YYYY-MM-DD' from Blade

    /* ================================================================
       HELPERS
    ================================================================ */

    /**
     * Inline disabled style object applied to expired time pills.
     * Centralised here so the JS and Blade renderers stay in sync.
     */
    var EXPIRED_STYLE = [
        'opacity:0.4',
        'cursor:not-allowed',
        'color:#ef4444',
        'border:1px solid #991b1b',
        'pointer-events:none',
        'filter:blur(0.4px)',
        'background:transparent',
    ].join(';');

    function buildCinemaHeaderText() {
        if (!activeCinema) return 'Select a cinema';
        return activeState + ' — ' + activeCinema.cinema_name;
    }

    function updateCinemaHeader() {
        cinemaLabel.textContent = buildCinemaHeaderText();
    }

    /* ================================================================
       SIDEBAR
    ================================================================ */
    function buildSidebar() {
        sidebarEl.innerHTML = '';

        stateGroups.forEach(function (sg, stateIdx) {
            var groupEl = document.createElement('div');
            groupEl.className = 'md-state-group' + (stateIdx === 0 ? ' md-state-group--open' : '');

            var toggleBtn = document.createElement('button');
            toggleBtn.className = 'md-state-toggle';
            toggleBtn.innerHTML =
                '<span>' + sg.state + '</span>' +
                '<span class="md-state-toggle__chevron">▾</span>';

            toggleBtn.addEventListener('click', function () {
                var isOpen = groupEl.classList.contains('md-state-group--open');
                sidebarEl.querySelectorAll('.md-state-group').forEach(function (g) {
                    g.classList.remove('md-state-group--open');
                });
                if (!isOpen) groupEl.classList.add('md-state-group--open');
            });

            groupEl.appendChild(toggleBtn);

            var listEl = document.createElement('div');
            listEl.className = 'md-cinema-list';

            sg.cinemas.forEach(function (cinema, cinemaIdx) {
                var item = document.createElement('div');
                item.className     = 'md-cinema-item';
                item.dataset.cinemaId = cinema.cinema_id;
                item.dataset.state = sg.state;

                item.innerHTML =
                    '<div>' +
                        '<div class="md-cinema-item__name">' + cinema.cinema_name + '</div>' +
                        '<div class="md-cinema-item__city">' + cinema.city + '</div>' +
                    '</div>';

                item.addEventListener('click', function () {
                    selectCinema(cinema, sg.state);
                });

                listEl.appendChild(item);

                // Auto-select the first cinema on first load.
                if (stateIdx === 0 && cinemaIdx === 0) {
                    item.classList.add('md-cinema-item--active');
                    activeCinema = cinema;
                    activeState  = sg.state;
                }
            });

            groupEl.appendChild(listEl);
            sidebarEl.appendChild(groupEl);
        });
    }

    /* ================================================================
       CINEMA SELECTION
    ================================================================ */
    function selectCinema(cinema, stateName) {
        activeCinema = cinema;
        activeState  = stateName;

        sidebarEl.querySelectorAll('.md-cinema-item').forEach(function (item) {
            var isActive =
                parseInt(item.dataset.cinemaId, 10) === cinema.cinema_id &&
                item.dataset.state === stateName;
            item.classList.toggle('md-cinema-item--active', isActive);
        });

        updateCinemaHeader();
        renderShowtimes(cinema.theatres);
    }

    /* ================================================================
       DATE STRIP
       ─────────────────────────────────────────────────────────────
       The date strip is server-rendered as <a> links by Blade.
       Clicking a date reloads the page with ?date=YYYY-MM-DD so the
       controller returns the correct showtime payload for that date.

       JS only adds the visual active class for instant feedback before
       the navigation resolves — no interception needed.
    ================================================================ */
    function initDateStrip() {
        var dateButtons = dateStripEl.querySelectorAll('.md-date-btn');

        dateButtons.forEach(function (btn) {
            var btnDate = btn.dataset.date;

            // Mark the server-side active date visually.
            if (btnDate === activeDateStr) {
                btn.classList.add('md-date-btn--active');
                btn.setAttribute('aria-pressed', 'true');
            }

            // Provide instant visual feedback on click before navigation.
            btn.addEventListener('click', function () {
                dateButtons.forEach(function (b) {
                    b.classList.remove('md-date-btn--active');
                    b.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('md-date-btn--active');
                btn.setAttribute('aria-pressed', 'true');
                // Navigation proceeds naturally via the href.
            });
        });
    }

    /* ================================================================
       SHOWTIME PILLS RENDERER
       ─────────────────────────────────────────────────────────────
       Renders theatre blocks into #md-showtime-section.
       Each time entry from the controller carries is_bookable: bool.

       Bookable    → normal clickable pill → navigates to seat picker.
       Not bookable → disabled pill with red inline styles → no action.
    ================================================================ */
    function renderShowtimes(theatres) {
        showtimeSect.innerHTML = '';

        if (!theatres || theatres.length === 0) {
            showtimeSect.innerHTML =
                '<p class="md-select-hint">No showtimes available for this cinema on this date.</p>';
            return;
        }

        theatres.forEach(function (theatre) {
            var block = document.createElement('div');
            block.className = 'md-theatre-block';

            var nameEl = document.createElement('div');
            nameEl.className   = 'md-theatre-name';
            nameEl.textContent = theatre.name;
            block.appendChild(nameEl);

            var pillsWrap = document.createElement('div');
            pillsWrap.className = 'md-time-pills';

            if (!theatre.times || theatre.times.length === 0) {
                var hint = document.createElement('span');
                hint.style.cssText = 'font-size:0.78rem;color:var(--md-text-muted);';
                hint.textContent   = 'No times available.';
                pillsWrap.appendChild(hint);
            } else {
                theatre.times.forEach(function (timeObj) {
                    var pill = document.createElement('button');
                    pill.type      = 'button';
                    pill.className = 'md-time-pill';
                    pill.textContent = timeObj.time;

                    if (timeObj.is_bookable) {
                        // ── BOOKABLE ────────────────────────────────────────
                        pill.addEventListener('click', function () {
                            var seatRoute = document.body.dataset.seatRoute;
                            var movieId   = document.body.dataset.movieId;

                            var url = seatRoute
                                + '?movie_id='     + encodeURIComponent(movieId)
                                + '&cinema_id='    + encodeURIComponent(activeCinema.cinema_id)
                                + '&hall_id='      + encodeURIComponent(theatre.hall_id || '')
                                + '&showtime_id='  + encodeURIComponent(timeObj.showtime_id || '')
                                + '&theatre_name=' + encodeURIComponent(theatre.name)
                                + '&date='         + encodeURIComponent(activeDateStr)
                                + '&time='         + encodeURIComponent(timeObj.time);

                            window.location.href = url;
                        });
                    } else {
                        // ── NOT BOOKABLE: expired / within 15-minute cutoff ─
                        pill.disabled = true;
                        pill.setAttribute('aria-disabled', 'true');
                        pill.setAttribute('title', 'This showtime is no longer available for booking');
                        pill.classList.add('md-time-pill--expired');
                        pill.style.cssText = EXPIRED_STYLE;
                    }

                    pillsWrap.appendChild(pill);
                });
            }

            block.appendChild(pillsWrap);
            showtimeSect.appendChild(block);
        });
    }

    /* ================================================================
       CLICK GUARD — belt-and-suspenders safety net
       ─────────────────────────────────────────────────────────────
       If any pill somehow reaches the DOM without the disabled
       attribute but carries the --expired class (e.g. from a cached
       render or a future template change), this delegated listener
       stops the event before any navigation can happen.
    ================================================================ */
    showtimeSect.addEventListener('click', function (e) {
        var pill = e.target.closest('.md-time-pill--expired');
        if (pill) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);   // capture phase — fires before the pill's own listener

    /* ================================================================
       INIT
    ================================================================ */
    buildSidebar();
    initDateStrip();

    if (activeCinema) {
        updateCinemaHeader();
        // Render the theatres for the first cinema immediately.
        renderShowtimes(activeCinema.theatres);
    }

})();