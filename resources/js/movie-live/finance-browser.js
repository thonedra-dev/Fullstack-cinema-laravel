// resources/js/movie-live/finance-browser.js
// Owns the finance rollup ladder: L1 cinemas -> L2 theatres -> L3 movie
// -> L4 showtimes -> L5 (delegated to finance-table.js). Keeps its OWN
// back-stack, separate from the sidebar's Cinema/Theatre/Date/Showtime
// navigation — expanding/collapsing a row here never touches the sidebar.
//
// render(container, context) is called by demo-section.js every time the
// finance tab is active. `context` describes the ROOT level this ladder
// should start at, given what the sidebar currently has selected — see
// demo-section.js's resolveFinanceContext(). If the context is unchanged
// from last time, the current drill position is preserved; if it changed
// (user picked a different cinema/theatre/date), the stack resets.

import { loadingHtml, emptyHtml, escapeHtml, formatMoney, parseTimestamp } from './utils.js';
import { renderBookingFinanceTable } from './finance-table.js';

export function initFinanceBrowser(root) {
    const cinemasFinancialsTpl   = root.dataset.cinemasFinancialsUrlTemplate;
    const theatresFinancialsTpl  = root.dataset.theatresFinancialsUrlTemplate;
    const moviesFinancialsTpl    = root.dataset.moviesFinancialsUrlTemplate;
    const showtimesFinancialsTpl = root.dataset.showtimesFinancialsUrlTemplate;
    const financialsTpl          = root.dataset.financialsUrlTemplate; // L5, already existed

    let container = null;
    let lastContextKey = null;
    let stack = []; // [{ level, params }, ...] — last entry is what's currently shown

    function render(targetContainer, context) {
        container = targetContainer;
        const key = contextKey(context);
        if (key !== lastContextKey) {
            lastContextKey = key;
            stack = [{ level: context.level, params: context }];
        }
        renderTop();
    }

    function contextKey(ctx) {
        return [ctx.level, ctx.cinemaId, ctx.theatreId, ctx.dateKey, ctx.showtimeId].join('|');
    }

    function pushLevel(level, params) {
        stack.push({ level, params });
        renderTop();
    }

    function popLevel() {
        if (stack.length > 1) stack.pop();
        renderTop();
    }

    function renderTop() {
        if (!container) return;
        const frame = stack[stack.length - 1];
        const showBack = stack.length > 1;

        container.innerHTML = `
            ${showBack ? `<button type="button" class="mld-finance-back-btn" id="mldFinanceBackBtn">← Back</button>` : ''}
            <div id="mldFinanceBrowserBody" class="mld-finance-browser-body mld-view-fade"></div>
        `;

        if (showBack) {
            document.getElementById('mldFinanceBackBtn').addEventListener('click', popLevel);
        }

        const body = document.getElementById('mldFinanceBrowserBody');

        switch (frame.level) {
            case 'L1': return loadCinemas(body);
            case 'L2': return loadTheatres(body, frame.params);
            case 'L3': return loadMovies(body, frame.params);
            case 'L4': return loadShowtimes(body, frame.params);
            case 'L5': return renderBookingFinanceTable(body, financialsTpl, frame.params.showtimeId);
        }
    }

    /* ── L1: cinemas ──────────────────────────────────────────────── */
    function loadCinemas(body) {
        body.innerHTML = loadingHtml('Loading cinema totals…');
        fetchJson(cinemasFinancialsTpl)
            .then((rows) => {
                if (!rows.length) return void (body.innerHTML = emptyHtml('No confirmed bookings yet.'));
                body.innerHTML = rollupTableHtml(
                    rows.map((r) => ({ id: r.cinema_id, label: r.cinema_name, ...r })),
                    'Cinema'
                );
                bindExpand(body, (id) => pushLevel('L2', { cinemaId: id }));
            })
            .catch(() => { body.innerHTML = emptyHtml('Failed to load cinema totals.'); });
    }

    /* ── L2: theatres under a cinema ─────────────────────────────── */
    function loadTheatres(body, params) {
        body.innerHTML = loadingHtml('Loading theatre totals…');
        const url = theatresFinancialsTpl.replace('__CINEMA_ID__', params.cinemaId);
        fetchJson(url)
            .then((rows) => {
                if (!rows.length) return void (body.innerHTML = emptyHtml('No confirmed bookings yet.'));
                body.innerHTML = rollupTableHtml(
                    rows.map((r) => ({ id: r.theatre_id, label: r.theatre_name, ...r })),
                    'Theatre'
                );
                bindExpand(body, (id) => pushLevel('L3', { cinemaId: params.cinemaId, theatreId: id }));
            })
            .catch(() => { body.innerHTML = emptyHtml('Failed to load theatre totals.'); });
    }

    /* ── L3: movie row(s), tall row w/ portrait poster ──────────────
       Always 1 row on this page (already movie-scoped), kept
       list-shaped so nothing here has to special-case it. */
    function loadMovies(body, params) {
        body.innerHTML = loadingHtml('Loading movie totals…');
        const url = moviesFinancialsTpl
            .replace('__CINEMA_ID__', params.cinemaId)
            .replace('__THEATRE_ID__', params.theatreId);
        fetchJson(url)
            .then((rows) => {
                if (!rows.length) return void (body.innerHTML = emptyHtml('No confirmed bookings yet.'));
                body.innerHTML = movieRowsHtml(rows);
                bindExpand(body, () => pushLevel('L4', { cinemaId: params.cinemaId, theatreId: params.theatreId }));
            })
            .catch(() => { body.innerHTML = emptyHtml('Failed to load movie totals.'); });
    }

    /* ── L4: showtimes, optionally scoped to one date ───────────────
       Reached either as a ROOT (sidebar already picked a date) or by
       expanding an L3 movie row (all dates, no filter). */
    function loadShowtimes(body, params) {
        body.innerHTML = loadingHtml('Loading showtime totals…');
        let url = showtimesFinancialsTpl
            .replace('__CINEMA_ID__', params.cinemaId)
            .replace('__THEATRE_ID__', params.theatreId);
        if (params.dateKey) url += '?date=' + encodeURIComponent(params.dateKey);

        fetchJson(url)
            .then((rows) => {
                if (!rows.length) return void (body.innerHTML = emptyHtml('No confirmed bookings for this selection.'));
                const heading = params.dateLabel ? `<div class="mld-group-title">📅 ${escapeHtml(params.dateLabel)}</div>` : '';
                body.innerHTML = heading + rollupTableHtml(
                    rows.map((r) => {
                        const t = parseTimestamp(r.start_time);
                        return { id: r.showtime_id, label: `${t.timeLabel} · ${t.dateLabel}`, ...r };
                    }),
                    'Showtime'
                );
                bindExpand(body, (id) => pushLevel('L5', { showtimeId: id }));
            })
            .catch(() => { body.innerHTML = emptyHtml('Failed to load showtime totals.'); });
    }

    /* ── Shared row renderers ────────────────────────────────────── */
    function rollupTableHtml(rows, labelHeader) {
        const trs = rows.map((r) => `
            <tr>
                <td>${escapeHtml(String(r.label))}</td>
                <td>${r.ticket_count}</td>
                <td>${r.booking_count}</td>
                <td>${formatMoney(r.total_ticket_revenue)}</td>
                <td>${formatMoney(r.total_payments)}</td>
                <td><button type="button" class="mld-finance-expand-btn" data-id="${r.id}" title="Expand">⌄</button></td>
            </tr>
        `).join('');

        return `
            <div class="mld-finance-wrap">
                <table class="mld-finance-table">
                    <thead>
                        <tr>
                            <th>${escapeHtml(labelHeader)}</th>
                            <th>Tickets</th>
                            <th>Bookings</th>
                            <th>Ticket Revenue</th>
                            <th>Payments</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${trs}</tbody>
                </table>
            </div>
        `;
    }

    function movieRowsHtml(rows) {
        const trs = rows.map((r) => `
            <tr class="mld-finance-movie-row">
                <td class="mld-finance-movie-row__poster-cell">
                    ${r.portrait_poster
                        ? `<img src="${escapeHtml(r.portrait_poster)}" alt="" class="mld-finance-movie-row__poster">`
                        : `<div class="mld-finance-movie-row__poster mld-finance-movie-row__poster-ph">🎬</div>`}
                </td>
                <td>
                    <p class="mld-finance-movie-row__title">${escapeHtml(r.movie_name)}</p>
                    <p class="mld-finance-movie-row__meta">${r.ticket_count} tickets · ${r.booking_count} bookings</p>
                </td>
                <td>${formatMoney(r.total_ticket_revenue)}</td>
                <td>${formatMoney(r.total_payments)}</td>
                <td><button type="button" class="mld-finance-expand-btn" data-id="${r.movie_id}" title="Expand">⌄</button></td>
            </tr>
        `).join('');

        return `
            <div class="mld-finance-wrap">
                <table class="mld-finance-table mld-finance-table--movies">
                    <thead>
                        <tr><th>Poster</th><th>Movie</th><th>Ticket Revenue</th><th>Payments</th><th></th></tr>
                    </thead>
                    <tbody>${trs}</tbody>
                </table>
            </div>
        `;
    }

    function bindExpand(body, onExpand) {
        body.querySelectorAll('.mld-finance-expand-btn').forEach((btn) => {
            btn.addEventListener('click', () => onExpand(btn.dataset.id));
        });
    }

    function fetchJson(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((res) => { if (!res.ok) throw new Error(); return res.json(); });
    }

    return { render };
}