/**
 * resources/js/movie_details.js
 *
 * Handles dynamic date carousel, AJAX showtime loading,
 * and cinema selection without page reload.
 */
(function () {
    'use strict';

    const dataEl = document.getElementById('md-data');
    if (!dataEl) return;

    // Read initial data from data attributes
    const initialGroups = JSON.parse(dataEl.dataset.groups || '[]');
    const allDates = JSON.parse(dataEl.dataset.availableDates || '[]');
    const activeDate = dataEl.dataset.activeDate || '';

    if (!allDates.length) return;

    // DOM refs
    const dateStrip = document.getElementById('mdDateStrip');
    const dateStripContainer = document.getElementById('mdDateStripContainer');
    const prevBtn = document.getElementById('mdDatePrev');
    const nextBtn = document.getElementById('mdDateNext');
    const sidebarEl = document.getElementById('md-sidebar');
    const showtimeSect = document.getElementById('md-showtime-section');
    const cinemaLabel = document.getElementById('md-cinema-label');

    // State
    let currentGroups = initialGroups;           // full state for current date
    let currentDate = activeDate || allDates[0];
    let activeCinema = null;
    let activeState = '';
    let visibleDates = [];                       // batch of 7 dates currently shown
    let currentBatchStart = 0;                   // index into allDates
    const BATCH_SIZE = 7;
    let isFetching = false;

    // ── Helpers ──────────────────────────────────────────────

    const EXPIRED_STYLE = [
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

    // ── Date Carousel ────────────────────────────────────────

    function renderDateStrip() {
        const total = allDates.length;
        // Ensure batchStart is within bounds
        if (currentBatchStart + BATCH_SIZE > total) {
            currentBatchStart = Math.max(0, total - BATCH_SIZE);
        }
        if (currentBatchStart < 0) currentBatchStart = 0;

        const end = Math.min(currentBatchStart + BATCH_SIZE, total);
        visibleDates = allDates.slice(currentBatchStart, end);

        dateStrip.innerHTML = '';
        visibleDates.forEach(dateStr => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'md-date-btn';
            if (dateStr === currentDate) btn.classList.add('md-date-btn--active');
            // Check if it's today
            const today = new Date().toISOString().split('T')[0];
            if (dateStr === today) btn.classList.add('md-date-btn--today');

            const d = new Date(dateStr + 'T00:00:00');
            const dayLabel = d.toLocaleDateString('en-US', { weekday: 'short' });
            const num = d.getDate();
            const month = d.toLocaleDateString('en-US', { month: 'short' });

            btn.innerHTML = `
                <span class="md-date-btn__day">${dayLabel}</span>
                <div class="md-date-btn__num-wrap">
                    <span class="md-date-btn__num">${num}</span>
                    <span class="md-date-btn__month">${month}</span>
                </div>
            `;
            btn.dataset.date = dateStr;
            btn.addEventListener('click', () => onDateClick(dateStr));
            dateStrip.appendChild(btn);
        });

        // Update arrows
        prevBtn.disabled = currentBatchStart === 0;
        nextBtn.disabled = currentBatchStart + BATCH_SIZE >= total;
    }

    function slideDateStrip(direction) {
        // direction: 1 for next, -1 for prev
        const newStart = currentBatchStart + direction * BATCH_SIZE;
        if (newStart < 0 || newStart >= allDates.length) return;
        currentBatchStart = newStart;
        renderDateStrip();
        // Smooth transition: we re-render and CSS transition handles the slide
        // We could also use transform, but simpler to re-render with transition on container.
        // Actually we need to animate the container transform. Let's use transform.
        // We'll implement a more refined slide later – for now, re-render with fade.
        // But we'll add a smooth class.
        dateStrip.style.transition = 'transform 0.3s ease';
        // We'll force reflow and then set transform? Simpler to just re-render.
        // Actually the container's overflow: hidden + transform will work.
        // We'll adjust this later.
    }

    // ── AJAX Fetch ──────────────────────────────────────────

    function fetchShowtimeData(dateStr) {
        if (isFetching) return;
        isFetching = true;

        // Show loading spinner in showtime section
        showtimeSect.innerHTML = `
            <div class="md-loading-spinner">
                <div class="md-spinner"></div>
                <span>Loading showtimes…</span>
            </div>
        `;

        const movieId = document.body.dataset.movieId;
        const url = window.location.pathname + '?date=' + encodeURIComponent(dateStr);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            currentGroups = data.stateGroups;
            currentDate = dateStr;
            // Update active date highlight
            document.querySelectorAll('.md-date-btn').forEach(btn => {
                btn.classList.toggle('md-date-btn--active', btn.dataset.date === dateStr);
            });
            // Rebuild sidebar and showtimes
            buildSidebar();
            // Auto-select first cinema (if any)
            const firstCinema = getFirstCinema();
            if (firstCinema) {
                selectCinema(firstCinema, getStateForCinema(firstCinema));
            } else {
                showtimeSect.innerHTML = '<p class="md-select-hint">No showtimes available for this date.</p>';
                cinemaLabel.textContent = 'No cinemas available';
            }
        })
        .catch(err => {
            console.error('Failed to fetch showtimes:', err);
            showtimeSect.innerHTML = '<p class="md-select-hint">Error loading showtimes. Please try again.</p>';
        })
        .finally(() => {
            isFetching = false;
        });
    }

    function getFirstCinema() {
        if (!currentGroups || !currentGroups.length) return null;
        const firstGroup = currentGroups[0];
        if (firstGroup.cinemas && firstGroup.cinemas.length) {
            return firstGroup.cinemas[0];
        }
        return null;
    }

    function getStateForCinema(cinema) {
        // find the state name from currentGroups
        for (let group of currentGroups) {
            if (group.cinemas.some(c => c.cinema_id === cinema.cinema_id)) {
                return group.state;
            }
        }
        return '';
    }

    // ── Sidebar Builder ──────────────────────────────────────

    function buildSidebar() {
        sidebarEl.innerHTML = '';
        if (!currentGroups || !currentGroups.length) {
            sidebarEl.innerHTML = '<p class="md-select-hint" style="padding:20px;">No cinemas available.</p>';
            return;
        }

        currentGroups.forEach((sg, stateIdx) => {
            const groupEl = document.createElement('div');
            groupEl.className = 'md-state-group' + (stateIdx === 0 ? ' md-state-group--open' : '');

            const toggle = document.createElement('button');
            toggle.className = 'md-state-toggle';
            toggle.innerHTML = `<span>${sg.state}</span><span class="md-state-toggle__chevron">▾</span>`;
            toggle.addEventListener('click', function () {
                const isOpen = groupEl.classList.contains('md-state-group--open');
                sidebarEl.querySelectorAll('.md-state-group').forEach(g => g.classList.remove('md-state-group--open'));
                if (!isOpen) groupEl.classList.add('md-state-group--open');
            });
            groupEl.appendChild(toggle);

            const list = document.createElement('div');
            list.className = 'md-cinema-list';
            sg.cinemas.forEach(cinema => {
                const item = document.createElement('div');
                item.className = 'md-cinema-item';
                item.dataset.cinemaId = cinema.cinema_id;
                item.dataset.state = sg.state;
                item.innerHTML = `
                    <div>
                        <div class="md-cinema-item__name">${cinema.cinema_name}</div>
                        <div class="md-cinema-item__city">${cinema.city || ''}</div>
                    </div>
                `;
                item.addEventListener('click', () => {
                    selectCinema(cinema, sg.state);
                });
                list.appendChild(item);
            });
            groupEl.appendChild(list);
            sidebarEl.appendChild(groupEl);
        });

        // After building, highlight the active cinema if any
        if (activeCinema) {
            highlightActiveCinema();
        }
    }

    function highlightActiveCinema() {
        sidebarEl.querySelectorAll('.md-cinema-item').forEach(item => {
            const isActive = parseInt(item.dataset.cinemaId) === activeCinema.cinema_id &&
                             item.dataset.state === activeState;
            item.classList.toggle('md-cinema-item--active', isActive);
        });
    }

    // ── Cinema Selection ──────────────────────────────────────

    function selectCinema(cinema, stateName) {
        activeCinema = cinema;
        activeState = stateName;
        highlightActiveCinema();
        updateCinemaHeader();
        renderShowtimes(cinema.theatres);
    }

    // ── Render Showtimes ──────────────────────────────────────

    function renderShowtimes(theatres) {
        showtimeSect.innerHTML = '';
        if (!theatres || !theatres.length) {
            showtimeSect.innerHTML = '<p class="md-select-hint">No showtimes available for this cinema.</p>';
            return;
        }

        theatres.forEach(theatre => {
            const block = document.createElement('div');
            block.className = 'md-theatre-block';

            const name = document.createElement('div');
            name.className = 'md-theatre-name';
            name.textContent = theatre.name;
            block.appendChild(name);

            const pillsWrap = document.createElement('div');
            pillsWrap.className = 'md-time-pills';

            if (!theatre.times || theatre.times.length === 0) {
                const hint = document.createElement('span');
                hint.style.cssText = 'font-size:0.78rem;color:var(--md-text-muted);';
                hint.textContent = 'No times available.';
                pillsWrap.appendChild(hint);
            } else {
                theatre.times.forEach(timeObj => {
                    const pill = document.createElement('button');
                    pill.type = 'button';
                    pill.className = 'md-time-pill';
                    pill.textContent = timeObj.time;

                    if (timeObj.is_bookable) {
                        pill.addEventListener('click', function () {
                            const seatRoute = document.body.dataset.seatRoute;
                            const movieId = document.body.dataset.movieId;
                            const url = seatRoute
                                + '?movie_id=' + encodeURIComponent(movieId)
                                + '&cinema_id=' + encodeURIComponent(activeCinema.cinema_id)
                                + '&hall_id=' + encodeURIComponent(theatre.hall_id || '')
                                + '&showtime_id=' + encodeURIComponent(timeObj.showtime_id || '')
                                + '&theatre_name=' + encodeURIComponent(theatre.name)
                                + '&date=' + encodeURIComponent(currentDate)
                                + '&time=' + encodeURIComponent(timeObj.time);
                            window.location.href = url;
                        });
                    } else {
                        pill.disabled = true;
                        pill.setAttribute('aria-disabled', 'true');
                        pill.title = 'This showtime is no longer available for booking';
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

    // ── Date Click Handler ────────────────────────────────────

    function onDateClick(dateStr) {
        if (dateStr === currentDate) return; // already loaded
        // Update active class immediately
        document.querySelectorAll('.md-date-btn').forEach(btn => {
            btn.classList.toggle('md-date-btn--active', btn.dataset.date === dateStr);
        });
        fetchShowtimeData(dateStr);
    }

    // ── Arrow Click Handlers ──────────────────────────────────

    prevBtn.addEventListener('click', () => {
        if (currentBatchStart > 0) {
            currentBatchStart -= BATCH_SIZE;
            renderDateStrip();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentBatchStart + BATCH_SIZE < allDates.length) {
            currentBatchStart += BATCH_SIZE;
            renderDateStrip();
        }
    });

    // ── Initialization ────────────────────────────────────────

    // Build date strip
    renderDateStrip();

    // Build sidebar with initial groups
    buildSidebar();

    // Select first cinema if exists
    const firstCinema = getFirstCinema();
    if (firstCinema) {
        activeCinema = firstCinema;
        activeState = getStateForCinema(firstCinema);
        updateCinemaHeader();
        // Render showtimes for the first cinema (from initialGroups)
        if (firstCinema.theatres) {
            renderShowtimes(firstCinema.theatres);
        }
        // Highlight it in sidebar (after build)
        highlightActiveCinema();
    } else {
        showtimeSect.innerHTML = '<p class="md-select-hint">Select a cinema from the left to view showtimes.</p>';
    }

    // ── Click guard for expired pills (belt-and-suspenders) ──
    showtimeSect.addEventListener('click', function (e) {
        const pill = e.target.closest('.md-time-pill--expired');
        if (pill) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

})();