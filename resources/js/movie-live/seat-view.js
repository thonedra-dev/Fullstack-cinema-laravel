// resources/js/movie-live/seat-view.js
// Renders the seat map for one showtime into a caller-supplied container.
// Owns its own per-showtime cache. Knows nothing about finance or toggles —
// demo-section.js decides when this gets shown.

import { escapeHtml, slugify, parseTimestamp } from './utils.js';

export function initSeatView(root) {
    const seatsTpl = root.dataset.seatsUrlTemplate;
    const cache = new Map(); // showtimeId -> seats JSON

    // Returns a Promise resolving with the seats JSON (so callers can build a title).
    function render(container, showtimeId) {
        if (cache.has(showtimeId)) {
            const data = cache.get(showtimeId);
            container.innerHTML = seatMapHtml(data);
            return Promise.resolve(data);
        }

        container.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Loading seat layout…</p>`;

        const url = seatsTpl.replace('__SHOWTIME_ID__', showtimeId);

        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
            .then((data) => {
                cache.set(showtimeId, data);
                container.innerHTML = seatMapHtml(data);
                return data;
            })
            .catch((err) => {
                container.innerHTML = `<p class="mld-empty-note" style="padding:100px 0;">Could not load seat configuration.</p>`;
                throw err;
            });
    }

    function seatMapHtml(data) {
        if (!data.seats || !data.seats.length) {
            return `
                <div class="ac-empty" style="padding:80px 20px;">
                    <div class="ac-empty__icon">💺</div>
                    <p class="ac-empty__text">No seat layout configured for this hall yet.</p>
                </div>
            `;
        }

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

    // Exposed so demo-section.js can build the "Theatre — date, time" title
    function titleFor(data, fallbackTheatreName) {
        const parsed = parseTimestamp(data.start_time);
        return `${data.theatre_name || fallbackTheatreName || ''} — ${parsed.dateLabel}, ${parsed.timeLabel}`;
    }

    return { render, titleFor };
}