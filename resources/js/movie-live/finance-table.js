// resources/js/movie-live/finance-table.js
// The bottom of the ladder (L5): per-booking financial table for ONE
// showtime. Reused by both entry paths — a showtime picked directly in
// the sidebar, or drilled down to via the L1->L4 rollups — so the leaf
// view never has two implementations to keep in sync.

import { loadingHtml, emptyHtml, escapeHtml, slugify, formatMoney } from './utils.js';

const cache = new Map(); // showtimeId -> financials JSON

export function renderBookingFinanceTable(container, financialsTpl, showtimeId) {
    if (cache.has(showtimeId)) {
        container.innerHTML = tableHtml(cache.get(showtimeId));
        return;
    }

    container.innerHTML = loadingHtml('Loading financial report…');

    fetch(financialsTpl.replace('__SHOWTIME_ID__', showtimeId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
        .then((res) => { if (!res.ok) throw new Error(); return res.json(); })
        .then((data) => {
            cache.set(showtimeId, data);
            container.innerHTML = tableHtml(data);
        })
        .catch(() => {
            container.innerHTML = emptyHtml('Failed to load financial report.');
        });
}

function tableHtml(data) {
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