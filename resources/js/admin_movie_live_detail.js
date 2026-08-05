document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('mldRoot');
    if (!root) return;

    const seatArea       = document.getElementById('mldSeatArea');
    const backBtn        = document.getElementById('mldBackBtn');
    const backBtnLabel   = document.getElementById('mldBackBtnLabel');

    const panelCinemas   = document.getElementById('mldPanelCinemas');
    const panelTheatres  = document.getElementById('mldPanelTheatres');
    const panelDates     = document.getElementById('mldPanelDates');
    const panelShowtimes = document.getElementById('mldPanelShowtimes');

    const allPanels = [panelCinemas, panelTheatres, panelDates, panelShowtimes];

    // Retrieve exact url templates defined in Blade data attributes
    const theatresTpl   = root.dataset.theatresUrlTemplate;
    const datesTpl       = root.dataset.datesUrlTemplate;
    const showtimesTpl  = root.dataset.showtimesUrlTemplate;
    const seatsTpl       = root.dataset.seatsUrlTemplate;
    const financialsTpl = root.dataset.financialsUrlTemplate;

    let state = {
        cinemaId: null,
        cinemaName: null,
        theatreId: null,
        theatreName: null,
        dateKey: null,
        dateLabel: null,
        showtimeId: null
    };

    // Caches for the seat-area flip view (per current showtime)
    let seatCache = null;      // last /seats JSON for state.showtimeId
    let financeCache = null;   // last /financials JSON for state.showtimeId
    let seatView = 'seats';    // 'seats' | 'finance'

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

        // fresh showtime → reset flip-view caches
        seatCache = null;
        financeCache = null;
        seatView = 'seats';

        loadSeats(state.showtimeId);
    });

    function loadSeats(showtimeId) {
        seatArea.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Loading seat layout…</p>`;

        const url = seatsTpl.replace('__SHOWTIME_ID__', showtimeId);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((data) => {
                seatCache = data;
                seatView = 'seats';
                renderSeatArea();
            })
            .catch(() => {
                seatArea.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Could not load seat configuration.</p>`;
            });
    }

    /* ── Seat area: header (title + flip toggle) + body ─────────────── */
    function renderSeatArea() {
        if (!seatCache) return;

        const parsedTime = parseTimestamp(seatCache.start_time);
        const titleText = `${escapeHtml(seatCache.theatre_name || state.theatreName)} — ${parsedTime.dateLabel}, ${parsedTime.timeLabel}`;

        const header = `
            <div class="mld-seat-area__header">
                <h2 class="mld-seat-area__title mld-seat-layout__title">${titleText}</h2>
                <div class="mld-view-toggle">
                    <button type="button" class="mld-view-toggle-btn ${seatView === 'seats' ? 'mld-view-toggle-btn--active' : ''}"
                            data-view="seats" title="Seat map">💺</button>
                    <button type="button" class="mld-view-toggle-btn ${seatView === 'finance' ? 'mld-view-toggle-btn--active' : ''}"
                            data-view="finance" title="Financial report">💰</button>
                </div>
            </div>
            <div id="mldSeatAreaBody" class="mld-view-fade"></div>
        `;

        seatArea.innerHTML = header;
        const body = document.getElementById('mldSeatAreaBody');

        if (seatView === 'finance') {
            if (financeCache) {
                body.innerHTML = renderFinanceTable(financeCache);
            } else {
                body.innerHTML = loadingHtml('Loading financial report…');
                fetch(financialsTpl.replace('__SHOWTIME_ID__', state.showtimeId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
                    .then((data) => {
                        financeCache = data;
                        // Only paint if the user is still on the finance view
                        if (seatView === 'finance') {
                            body.innerHTML = renderFinanceTable(financeCache);
                        }
                    })
                    .catch(() => {
                        body.innerHTML = emptyHtml('Failed to load financial report.');
                    });
            }
        } else {
            body.innerHTML = renderSeatMap(seatCache);
        }
    }

    // Delegated click handler for the flip toggle (header is re-rendered each time)
    seatArea.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-view-toggle-btn');
        if (!btn) return;
        const nextView = btn.dataset.view;
        if (nextView === seatView) return;
        seatView = nextView;
        renderSeatArea();
    });

    function renderSeatMap(data) {
        if (!data.seats || !data.seats.length) {
            return `
                <div class="ac-empty" style="padding:80px 20px;">
                    <div class="ac-empty__icon">💺</div>
                    <p class="ac-empty__text">No seat layout configured for this hall yet.</p>
                </div>
            `;
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

        return `
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

    function renderFinanceTable(data) {
        const rows = data.rows || [];
        const totals = data.totals || {};

        if (!rows.length) {
            return `
                <div class="ac-empty" style="padding:80px 20px;">
                    <div class="ac-empty__icon">💰</div>
                    <p class="ac-empty__text">No bookings recorded for this showtime.</p>
                </div>
            `;
        }

        const summary = `
            <div class="mld-finance-summary">
                <div class="mld-finance-summary__item">
                    <span class="mld-finance-summary__label">Tickets</span>
                    <span class="mld-finance-summary__value">${totals.ticket_count ?? rows.length}</span>
                </div>
                <div class="mld-finance-summary__item">
                    <span class="mld-finance-summary__label">Ticket Revenue</span>
                    <span class="mld-finance-summary__value">${formatMoney(totals.total_price_paid)}</span>
                </div>
                <div class="mld-finance-summary__item">
                    <span class="mld-finance-summary__label">Payments Received</span>
                    <span class="mld-finance-summary__value">${formatMoney(totals.total_amount_paid)}</span>
                </div>
            </div>
        `;

        const tableRows = rows.map((r) => {
            const statusKey = slugify(r.payment_status || (r.payment_id ? 'unknown' : 'none'));
            const statusLabel = r.payment_status ? r.payment_status : (r.payment_id ? '—' : 'No payment');
            return `
                <tr>
                    <td>${escapeHtml(String(r.booking_id))}</td>
                    <td>${escapeHtml(r.seat_label)}</td>
                    <td>${escapeHtml(r.payment_id !== null ? String(r.payment_id) : '—')}</td>
                    <td>${formatMoney(r.price_paid)}</td>
                    <td>${formatMoney(r.amount_paid)}</td>
                    <td><span class="mld-finance-status mld-finance-status--${statusKey}">${escapeHtml(statusLabel)}</span></td>
                </tr>
            `;
        }).join('');

        return `
            ${summary}
            <div class="mld-finance-wrap">
                <table class="mld-finance-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Seat</th>
                            <th>Payment ID</th>
                            <th>Ticket Price</th>
                            <th>Amount Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>
            </div>
        `;
    }

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

    function loadingHtml(msg) { return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`; }
    function emptyHtml(msg) { return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`; }
    function slugify(str) { return String(str).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatMoney(val) {
        const n = Number(val);
        if (val === null || val === undefined || Number.isNaN(n)) return '—';
        return n.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    }

    function formatDateKey(dateKey) {
        // dateKey is 'YYYY-MM-DD' from Postgres DATE()
        const [y, m, d] = String(dateKey).split('-').map(Number);
        const dt = new Date(y, (m || 1) - 1, d || 1);
        return dt.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
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