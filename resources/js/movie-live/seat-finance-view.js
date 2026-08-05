// resources/js/movie-live/seat-finance-view.js
// Owns the left-hand panel: seat map <-> financial report flip view.
// Knows nothing about the sidebar — it's just told "show this showtime".

import { loadingHtml, emptyHtml, escapeHtml, slugify, formatMoney, parseTimestamp } from './utils.js';

export function initSeatFinanceView(root) {
    const seatArea = document.getElementById('mldSeatArea');

    const seatsTpl       = root.dataset.seatsUrlTemplate;
    const financialsTpl  = root.dataset.financialsUrlTemplate;

    let showtimeId = null;
    let fallbackTheatreName = null;
    let seatCache = null;      // last /seats JSON for showtimeId
    let financeCache = null;   // last /financials JSON for showtimeId
    let seatView = 'seats';    // 'seats' | 'finance'

    // Delegated click handler for the flip toggle (header is re-rendered each time)
    seatArea.addEventListener('click', (e) => {
        const btn = e.target.closest('.mld-view-toggle-btn');
        if (!btn) return;
        const nextView = btn.dataset.view;
        if (nextView === seatView) return;
        seatView = nextView;
        renderSeatArea();
    });

    function showShowtime(newShowtimeId, { theatreName } = {}) {
        showtimeId = newShowtimeId;
        fallbackTheatreName = theatreName || fallbackTheatreName;

        // fresh showtime → reset flip-view caches
        seatCache = null;
        financeCache = null;
        seatView = 'seats';

        loadSeats();
    }

    function loadSeats() {
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
        const titleText = `${escapeHtml(seatCache.theatre_name || fallbackTheatreName || '')} — ${parsedTime.dateLabel}, ${parsedTime.timeLabel}`;

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
            renderFinanceInto(body);
        } else {
            body.innerHTML = renderSeatMap(seatCache);
        }
    }

    function renderFinanceInto(body) {
        if (financeCache) {
            body.innerHTML = renderFinanceTable(financeCache);
            return;
        }

        body.innerHTML = loadingHtml('Loading financial report…');
        const requestedShowtimeId = showtimeId;

        fetch(financialsTpl.replace('__SHOWTIME_ID__', showtimeId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((data) => {
                // Guard against the user switching showtimes/views mid-flight
                if (requestedShowtimeId !== showtimeId || seatView !== 'finance') return;
                financeCache = data;
                body.innerHTML = renderFinanceTable(financeCache);
            })
            .catch(() => {
                if (requestedShowtimeId !== showtimeId || seatView !== 'finance') return;
                body.innerHTML = emptyHtml('Failed to load financial report.');
            });
    }

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

    return { showShowtime };
}