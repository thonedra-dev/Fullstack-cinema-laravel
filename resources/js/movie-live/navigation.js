// resources/js/movie-live/navigation.js
// Owns the sidebar drill-down: Cinemas -> Theatres -> Dates -> Showtimes.
// ALSO decides what the demo section (left panel) shows, keyed only on
// which panel is currently active + what's currently selected — never on
// direction. This is what makes forward/back symmetric for free:
//   Cinemas panel   -> cinema-only card
//   Theatres panel  -> cinema-only if no theatre chosen yet, else cinema+hall
//   Dates panel     -> cinema+hall (theatre is always known by this point)
//   Showtimes panel -> untouched (neutral) — only an explicit showtime
//                       click hands control to the seat/finance view.

import { loadingHtml, emptyHtml, escapeHtml, formatDateKey, parseTimestamp } from './utils.js';

export function initNavigation(root, { onShowtimeSelected, infoCardView }) {
    const backBtn        = document.getElementById('mldBackBtn');
    const backBtnLabel   = document.getElementById('mldBackBtnLabel');

    const panelCinemas    = document.getElementById('mldPanelCinemas');
    const panelTheatres   = document.getElementById('mldPanelTheatres');
    const panelDates      = document.getElementById('mldPanelDates');
    const panelShowtimes  = document.getElementById('mldPanelShowtimes');
    const allPanels       = [panelCinemas, panelTheatres, panelDates, panelShowtimes];

    const theatresTpl   = root.dataset.theatresUrlTemplate;
    const datesTpl       = root.dataset.datesUrlTemplate;
    const showtimesTpl  = root.dataset.showtimesUrlTemplate;

    const state = {
        cinema: null,     // { id, name, address, contact, description, picture, cityName }
        theatre: null,    // { id, name, icon, poster }
        dateKey: null,
        dateLabel: null,
        showtimeId: null,
    };

    /* ── STEP 1: Cinema Click Handler ────────────────────────────────── */
    panelCinemas.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--cinema');
        if (!btn) return;

        state.cinema = readCinemaFromDataset(btn.dataset);
        state.theatre = null; // fresh cinema invalidates any previously chosen theatre

        setBack('Cinema Selection', () => {
            showPanel(panelCinemas);
            setBack(null);
        });

        loadTheatres();
    });

    function loadTheatres() {
        panelTheatres.innerHTML = panelHeader('Theatre Selection') + loadingHtml('Loading theatres…');
        showPanel(panelTheatres);

        const url = theatresTpl.replace('__CINEMA_ID__', state.cinema.id);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((theatres) => {
                if (!theatres || !theatres.length) {
                    panelTheatres.innerHTML = panelHeader('Theatre Selection') + emptyHtml('No theatres found for this cinema.');
                    return;
                }
                panelTheatres.innerHTML = panelHeader('Theatre Selection') + theatres.map((t) => `
                    <button type="button" class="mld-choice-btn mld-choice-btn--theatre"
                            data-theatre-id="${t.theatre_id}"
                            data-theatre-name="${escapeHtml(t.theatre_name)}"
                            data-theatre-icon="${escapeHtml(t.theatre_icon || '')}"
                            data-theatre-poster="${escapeHtml(t.theatre_poster || '')}">
                        <span class="mld-choice-btn__icon">🎭</span>
                        <span class="mld-choice-btn__label">${escapeHtml(t.theatre_name)}</span>
                        <span class="mld-choice-btn__chevron">›</span>
                    </button>
                `).join('');
            })
            .catch(() => { panelTheatres.innerHTML = panelHeader('Theatre Selection') + emptyHtml('Failed to load theatres.'); });
    }

    /* ── STEP 2: Theatre Click Handler ───────────────────────────────── */
    panelTheatres.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--theatre');
        if (!btn) return;

        state.theatre = readTheatreFromDataset(btn.dataset);

        setBack('Theatre Selection', () => {
            showPanel(panelTheatres);
            setBack('Cinema Selection', () => {
                showPanel(panelCinemas);
                setBack(null);
            });
        });

        loadDates();
    });

    function loadDates() {
        panelDates.innerHTML = panelHeader('Date Selection') + loadingHtml('Loading dates…');
        showPanel(panelDates);

        const url = datesTpl
            .replace('__CINEMA_ID__', state.cinema.id)
            .replace('__THEATRE_ID__', state.theatre.id);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((dates) => {
                if (!dates || !dates.length) {
                    panelDates.innerHTML = panelHeader('Date Selection') + emptyHtml('No showtimes recorded for this theatre.');
                    return;
                }
                panelDates.innerHTML = panelHeader('Date Selection') + dates.map((d) => {
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
            .catch(() => { panelDates.innerHTML = panelHeader('Date Selection') + emptyHtml('Failed to load dates.'); });
    }

    /* ── STEP 3: Date Click Handler ──────────────────────────────────── */
    panelDates.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--date');
        if (!btn) return;

        state.dateKey   = btn.dataset.dateKey;
        state.dateLabel = btn.dataset.dateLabel;

        setBack('Day Selection', () => {
            showPanel(panelDates);
            setBack('Theatre Selection', () => {
                showPanel(panelTheatres);
                setBack('Cinema Selection', () => {
                    showPanel(panelCinemas);
                    setBack(null);
                });
            });
        });

        loadShowtimes();
    });

    function loadShowtimes() {
        panelShowtimes.innerHTML = panelHeader('Showtime Selection') + loadingHtml('Loading showtimes…');
        showPanel(panelShowtimes);

        const url = showtimesTpl
            .replace('__CINEMA_ID__', state.cinema.id)
            .replace('__THEATRE_ID__', state.theatre.id)
            + '?date=' + encodeURIComponent(state.dateKey);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((showtimes) => {
                if (!showtimes || !showtimes.length) {
                    panelShowtimes.innerHTML = panelHeader('Showtime Selection') + emptyHtml('No showtimes on this date.');
                    return;
                }

                let html = panelHeader('Showtime Selection') + `<div class="mld-group-title">📅 ${escapeHtml(state.dateLabel)}</div>`;
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
            .catch(() => { panelShowtimes.innerHTML = panelHeader('Showtime Selection') + emptyHtml('Failed to load showtimes.'); });
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
        onShowtimeSelected(state.showtimeId, { theatreName: state.theatre?.name });
    });

    /* ── Demo-section driver ─────────────────────────────────────────
       The ONLY place that decides what the left panel shows for
       Cinemas / Theatres / Dates. Showtimes is intentionally absent:
       leaving it untouched is what keeps the seat/finance view alive
       when the user steps back from it.
    ──────────────────────────────────────────────────────────────── */
    function updateDemoSection(panel) {
        if (panel === panelCinemas) {
            if (state.cinema) infoCardView.showCinemaOnly(state.cinema);
            return;
        }
        if (panel === panelTheatres) {
            if (!state.cinema) return;
            if (state.theatre) {
                infoCardView.showCinemaAndHall(state.cinema, state.theatre);
            } else {
                infoCardView.showCinemaOnly(state.cinema);
            }
            return;
        }
        if (panel === panelDates) {
            if (state.cinema && state.theatre) {
                infoCardView.showCinemaAndHall(state.cinema, state.theatre);
            }
            return;
        }
        // panelShowtimes: no-op by design
    }

    /* ── Helper Functions ────────────────────────────────────────── */
    function showPanel(panelToShow) {
        allPanels.forEach((p) => {
            p.classList.toggle('mld-panel--active', p === panelToShow);
        });
        panelToShow.scrollTop = 0;
        updateDemoSection(panelToShow);
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

    function panelHeader(text) {
        return `<div class="mld-panel__title">${escapeHtml(text)}</div>`;
    }

    function readCinemaFromDataset(ds) {
        return {
            id: ds.cinemaId,
            name: ds.cinemaName,
            address: ds.cinemaAddress || '',
            contact: ds.cinemaContact || '',
            description: ds.cinemaDescription || '',
            picture: ds.cinemaPicture || '',
            cityName: ds.cityName || '',
        };
    }

    function readTheatreFromDataset(ds) {
        return {
            id: ds.theatreId,
            name: ds.theatreName,
            icon: ds.theatreIcon || '',
            poster: ds.theatrePoster || '',
        };
    }
}