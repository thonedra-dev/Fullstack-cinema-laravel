document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mldRoot');
    if (!root) return;

    const seatArea       = document.getElementById('mldSeatArea');
    const backBtn        = document.getElementById('mldBackBtn');
    const backBtnLabel   = document.getElementById('mldBackBtnLabel');

    const panelCinemas   = document.getElementById('mldPanelCinemas');
    const panelTheatres  = document.getElementById('mldPanelTheatres');
    const panelShowtimes = document.getElementById('mldPanelShowtimes');

    // Retrieve exact url templates defined in Blade data attributes
    const theatresTpl  = root.dataset.theatresUrlTemplate;
    const showtimesTpl = root.dataset.showtimesUrlTemplate;
    const seatsTpl     = root.dataset.seatsUrlTemplate;

    let state = {
        cinemaId: null,
        cinemaName: null,
        theatreId: null,
        theatreName: null,
        showtimeId: null
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
                    panelTheatres.innerHTML = emptyHtml('No active theatres found for this cinema.');
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

        loadShowtimes();
    });

    function loadShowtimes() {
        panelShowtimes.innerHTML = loadingHtml('Loading showtimes…');
        showPanel(panelShowtimes);

        const url = showtimesTpl
            .replace('__CINEMA_ID__', state.cinemaId)
            .replace('__THEATRE_ID__', state.theatreId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((showtimes) => {
                if (!showtimes || !showtimes.length) {
                    panelShowtimes.innerHTML = emptyHtml('No live showtimes found.');
                    return;
                }

                // Group showtimes by date key (YYYY-MM-DD)
                const dateGroups = {};
                showtimes.forEach((s) => {
                    const parsed = parseTimestamp(s.start_time);
                    if (!dateGroups[parsed.dateKey]) {
                        dateGroups[parsed.dateKey] = { dateLabel: parsed.dateLabel, items: [] };
                    }
                    dateGroups[parsed.dateKey].items.push({ ...s, timeLabel: parsed.timeLabel });
                });

                let html = '';
                Object.keys(dateGroups).forEach((dateKey) => {
                    const group = dateGroups[dateKey];
                    html += `<div class="mld-group-title">📅 ${escapeHtml(group.dateLabel)}</div>`;
                    group.items.forEach((s) => {
                        html += `
                            <button type="button" class="mld-choice-btn mld-choice-btn--showtime"
                                    data-showtime-id="${s.showtime_id}">
                                <span class="mld-choice-btn__icon">🕒</span>
                                <span class="mld-choice-btn__label">${s.timeLabel}</span>
                                <span class="mld-choice-btn__chevron">›</span>
                            </button>
                        `;
                    });
                });

                panelShowtimes.innerHTML = html;
            })
            .catch(() => { panelShowtimes.innerHTML = emptyHtml('Failed to load showtimes.'); });
    }

    /* ── STEP 3: Showtime Click Handler ──────────────────────────────── */
    panelShowtimes.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-choice-btn--showtime');
        if (!btn) return;

        panelShowtimes.querySelectorAll('.mld-choice-btn--active').forEach((el) => {
            el.classList.remove('mld-choice-btn--active');
        });
        btn.classList.add('mld-choice-btn--active');

        state.showtimeId = btn.dataset.showtimeId;
        loadSeats(state.showtimeId);
    });

    function loadSeats(showtimeId) {
        seatArea.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Loading seat layout…</p>`;

        const url = seatsTpl.replace('__SHOWTIME_ID__', showtimeId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then(renderSeats)
            .catch(() => {
                seatArea.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Could not load seat configuration.</p>`;
            });
    }

    function renderSeats(data) {
        const parsedTime = parseTimestamp(data.start_time);

        if (!data.seats || !data.seats.length) {
            seatArea.innerHTML = `
                <h2 class="mld-seat-layout__title">${escapeHtml(data.theatre_name || state.theatreName)} — ${parsedTime.dateLabel} @ ${parsedTime.timeLabel}</h2>
                <div class="ac-empty" style="padding:80px 20px;">
                    <div class="ac-empty__icon">💺</div>
                    <p class="ac-empty__text">No seat layout configured for this hall yet.</p>
                </div>
            `;
            return;
        }

        // Group seats by row label
        const rows = {};
        data.seats.forEach((seat) => {
            if (!rows[seat.row_label]) rows[seat.row_label] = [];
            rows[seat.row_label].push(seat);
        });
        const rowLabels = Object.keys(rows).sort();

        const rowsHtml = rowLabels.map((label) => {
            const sortedSeats = rows[label].sort((a, b) => a.seat_number - b.seat_number);
            let seatsContentHtml = '';
            let i = 0;

            while (i < sortedSeats.length) {
                const seat = sortedSeats[i];
                const type = String(seat.seat_type || 'standard').toLowerCase();
                const stateClass = seat.booking_state !== 'available' ? `mld-seat--${seat.booking_state}` : '';

                // Couple Pair Aggregation
                if (type === 'couple' && i + 1 < sortedSeats.length && String(sortedSeats[i + 1].seat_type).toLowerCase() === 'couple') {
                    const seat2 = sortedSeats[i + 1];
                    const state2Class = seat2.booking_state !== 'available' ? `mld-seat--${seat2.booking_state}` : '';

                    seatsContentHtml += `
                        <div class="mld-couple-pair">
                            <span class="mld-seat mld-seat--couple ${stateClass}" title="${label}${seat.seat_number}">${seat.seat_number}</span>
                            <span class="mld-seat mld-seat--couple ${state2Class}" title="${label}${seat2.seat_number}">${seat2.seat_number}</span>
                        </div>
                    `;
                    i += 2;
                } else {
                    const typeClass = `mld-seat--${slugify(type)}`;
                    seatsContentHtml += `
                        <span class="mld-seat ${typeClass} ${stateClass}"
                              title="${label}${seat.seat_number} · ${escapeHtml(type)}${seat.booking_state !== 'available' ? ' (' + seat.booking_state + ')' : ''}">
                            ${seat.seat_number}
                        </span>
                    `;
                    i++;
                }
            }

            return `
                <div class="mld-seat-row">
                    <span class="mld-seat-row__label">${escapeHtml(label)}</span>
                    <div class="mld-seat-row__seats">${seatsContentHtml}</div>
                    <span class="mld-seat-row__label">${escapeHtml(label)}</span>
                </div>
            `;
        }).join('');

        seatArea.innerHTML = `
            <h2 class="mld-seat-layout__title">${escapeHtml(data.theatre_name || state.theatreName)} — ${parsedTime.dateLabel}, ${parsedTime.timeLabel}</h2>
            
            <div class="mld-screen-container">
                <div class="mld-screen-curve"></div>
                <div class="mld-screen-label">FRONT SCREEN AXIS</div>
            </div>

            <div class="mld-seat-legend">
                <div class="mld-legend-item"><span class="mld-seat mld-seat--standard"></span> Standard</div>
                <div class="mld-legend-item"><span class="mld-seat mld-seat--couple"></span> Couple</div>
                <div class="mld-legend-item"><span class="mld-seat mld-seat--premium"></span> Premium</div>
                <div class="mld-legend-item"><span class="mld-seat mld-seat--family"></span> Family</div>
                <div class="mld-legend-item"><span class="mld-seat mld-seat--held"></span> Held</div>
                <div class="mld-legend-item"><span class="mld-seat mld-seat--sold"></span> Sold</div>
            </div>

            <div class="mld-seat-grid">${rowsHtml}</div>
        `;
    }

    /* ── Helper Functions ────────────────────────────────────────── */
    function showPanel(panelToShow) {
        [panelCinemas, panelTheatres, panelShowtimes].forEach((p) => {
            p.classList.toggle('mld-panel--active', p === panelToShow);
        });
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

    function loadingHtml(msg) { return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`; }
    function emptyHtml(msg) { return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`; }
    function slugify(str) { return String(str).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function parseTimestamp(iso) {
        if (!iso) return { dateKey: '', dateLabel: '', timeLabel: '' };

        const parts = iso.split(/[ T]/);
        if (parts.length >= 2) {
            const dateParts = parts[0].split('-');
            const timeParts = parts[1].split(':');

            if (dateParts.length === 3 && timeParts.length >= 2) {
                const year  = parseInt(dateParts[0], 10);
                const month = parseInt(dateParts[1], 10) - 1;
                const day   = parseInt(dateParts[2], 10);
                const hour  = parseInt(timeParts[0], 10);
                const min   = parseInt(timeParts[1], 10);

                const d = new Date(year, month, day, hour, min);
                return {
                    dateKey: parts[0],
                    dateLabel: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                    timeLabel: d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
                };
            }
        }

        const fallback = new Date(iso);
        return {
            dateKey: fallback.toISOString().split('T')[0],
            dateLabel: fallback.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            timeLabel: fallback.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
        };
    }
});