// resources/js/movie-live/navigation.js
// Owns the sidebar drill-down: Cinemas -> Theatres -> Dates -> Showtimes.
// Knows nothing about the seat map or financial report — it only reports
// the chosen showtime upward via the onShowtimeSelected callback.

import { loadingHtml, emptyHtml, escapeHtml, formatDateKey, parseTimestamp } from './utils.js';

export function initNavigation(root, { onShowtimeSelected }) {
    const backBtn        = document.getElementById('mldBackBtn');
    const backBtnLabel    = document.getElementById('mldBackBtnLabel');

    const panelCinemas    = document.getElementById('mldPanelCinemas');
    const panelTheatres   = document.getElementById('mldPanelTheatres');
    const panelDates      = document.getElementById('mldPanelDates');
    const panelShowtimes  = document.getElementById('mldPanelShowtimes');
    const allPanels       = [panelCinemas, panelTheatres, panelDates, panelShowtimes];

    const theatresTpl    = root.dataset.theatresUrlTemplate;
    const datesTpl        = root.dataset.datesUrlTemplate;
    const showtimesTpl   = root.dataset.showtimesUrlTemplate;

    const state = {
        cinemaId: null,
        cinemaName: null,
        theatreId: null,
        theatreName: null,
        dateKey: null,
        dateLabel: null,
        showtimeId: null,
    };

    /* ── STEP 1: Cinema Click Handler ────────────────────────────────── */
    panelCinemas.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--cinema');
        if (!btn) return;

        state.cinemaId   = btn.dataset.cinemaId;
        state.cinemaName = btn.dataset.cinemaName;

        setBack('Cinemas', () => {
            showPanel(panelCinemas);
            setBack(null);
        });

        loadTheatres();
    });

    function loadTheatres() {
        panelTheatres.innerHTML = loadingHtml('Loading theatres…');
        showPanel(panelTheatres);

        const url = theatresTpl.replace('__CINEMA_ID__', state.cinemaId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((theatres) => {
                if (!theatres || !theatres.length) {
                    panelTheatres.innerHTML = emptyHtml('No theatres found for this cinema.');
                    return;
                }
                panelTheatres.innerHTML = theatres.map((t) => `
                    <button type="button" class="mld-choice-btn mld-choice-btn--theatre"
                            data-theatre-id="${t.theatre_id}"
                            data-theatre-name="${escapeHtml(t.theatre_name)}">
                        <span class="mld-choice-btn__icon">🎭</span>
                        <span class="mld-choice-btn__label">${escapeHtml(t.theatre_name)}</span>
                        <span class="mld-choice-btn__chevron">›</span>
                    </button>
                `).join('');
            })
            .catch(() => { panelTheatres.innerHTML = emptyHtml('Failed to load theatres.'); });
    }

    /* ── STEP 2: Theatre Click Handler ───────────────────────────────── */
    panelTheatres.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--theatre');
        if (!btn) return;

        state.theatreId   = btn.dataset.theatreId;
        state.theatreName = btn.dataset.theatreName;

        setBack(state.cinemaName, () => {
            showPanel(panelTheatres);
            setBack('Cinemas', () => {
                showPanel(panelCinemas);
                setBack(null);
            });
        });

        loadDates();
    });

    function loadDates() {
        panelDates.innerHTML = loadingHtml('Loading dates…');
        showPanel(panelDates);

        const url = datesTpl
            .replace('__CINEMA_ID__', state.cinemaId)
            .replace('__THEATRE_ID__', state.theatreId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((dates) => {
                if (!dates || !dates.length) {
                    panelDates.innerHTML = emptyHtml('No showtimes recorded for this theatre.');
                    return;
                }
                panelDates.innerHTML = dates.map((d) => {
                    const label = formatDateKey(d.date_key);
                    const count = Number(d.showtime_count) || 0;
                    return `
                        <button type="button" class="mld-choice-btn mld-choice-btn--date"
                                data-date-key="${d.date_key}"
                                data-date-label="${escapeHtml(label)}">
                            <span class="mld-choice-btn__icon">📅</span>
                            <span class="mld-choice-btn__label">${escapeHtml(label)}</span>
                            <span class="mld-choice-btn__count">${count} showtime${count === 1 ? '' : 's'}</span>
                            <span class="mld-choice-btn__chevron">›</span>
                        </button>
                    `;
                }).join('');
            })
            .catch(() => { panelDates.innerHTML = emptyHtml('Failed to load dates.'); });
    }

    /* ── STEP 3: Date Click Handler ──────────────────────────────────── */
    panelDates.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--date');
        if (!btn) return;

        state.dateKey   = btn.dataset.dateKey;
        state.dateLabel = btn.dataset.dateLabel;

        setBack(state.theatreName, () => {
            showPanel(panelDates);
            setBack(state.cinemaName, () => {
                showPanel(panelTheatres);
                setBack('Cinemas', () => {
                    showPanel(panelCinemas);
                    setBack(null);
                });
            });
        });

        loadShowtimes();
    });

    function loadShowtimes() {
        panelShowtimes.innerHTML = loadingHtml('Loading showtimes…');
        showPanel(panelShowtimes);

        const url = showtimesTpl
            .replace('__CINEMA_ID__', state.cinemaId)
            .replace('__THEATRE_ID__', state.theatreId)
            + '?date=' + encodeURIComponent(state.dateKey);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((showtimes) => {
                if (!showtimes || !showtimes.length) {
                    panelShowtimes.innerHTML = emptyHtml('No showtimes on this date.');
                    return;
                }

                let html = `<div class="mld-group-title">📅 ${escapeHtml(state.dateLabel)}</div>`;
                showtimes.forEach((s) => {
                    const parsed = parseTimestamp(s.start_time);
                    html += `
                        <button type="button" class="mld-choice-btn mld-choice-btn--showtime"
                                data-showtime-id="${s.showtime_id}">
                            <span class="mld-choice-btn__icon">🕒</span>
                            <span class="mld-choice-btn__label">${parsed.timeLabel}</span>
                            <span class="mld-choice-btn__chevron">›</span>
                        </button>
                    `;
                });

                panelShowtimes.innerHTML = html;
            })
            .catch(() => { panelShowtimes.innerHTML = emptyHtml('Failed to load showtimes.'); });
    }

    /* ── STEP 4: Showtime Click Handler ──────────────────────────────── */
    panelShowtimes.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--showtime');
        if (!btn) return;

        panelShowtimes.querySelectorAll('.mld-choice-btn--active').forEach((el) => {
            el.classList.remove('mld-choice-btn--active');
        });
        btn.classList.add('mld-choice-btn--active');

        state.showtimeId = btn.dataset.showtimeId;
        onShowtimeSelected(state.showtimeId, { theatreName: state.theatreName });
    });

    /* ── Helper Functions ────────────────────────────────────────── */
    function showPanel(panelToShow) {
        allPanels.forEach((p) => {
            p.classList.toggle('mld-panel--active', p === panelToShow);
        });
        panelToShow.scrollTop = 0;
    }

    function setBack(label, onClick) {
        if (!label) {
            backBtn.hidden = true;
            backBtn.onclick = null;
            return;
        }
        backBtn.hidden = false;
        backBtnLabel.textContent = `Back to ${label}`;
        backBtn.onclick = onClick;
    }
}