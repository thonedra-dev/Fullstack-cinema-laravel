// resources/js/movie-live/demo-section.js
// The orchestrator for #mldSeatArea. Owns the header + 💺/💰 toggle.
// Decides what "primary" mode shows, based ONLY on which sidebar panel
// is active + what's selected (never on direction — see table below).
// Decides what finance depth to hand the finance-browser, again purely
// from nav state. This is the one file that knows about all the other
// view modules; none of them know about each other.
//
//   Sidebar panel      | Primary shows          | Finance opens at
//   -------------------|-------------------------|------------------
//   Cinemas            | cinema-only card         | L1 (all cinemas)
//   Theatres           | cinema-only or +hall     | L2 (theatres)
//   Dates               | cinema+hall               | L3 (movie row)
//   Showtimes, no click | untouched (neutral)      | L4 (date's showtimes)
//   Showtimes, clicked  | seat map                  | L5 (that showtime)

export function initDemoSection(root, { infoCardView, seatView, financeBrowser }) {
    const seatArea = document.getElementById('mldSeatArea');

    let navState = { panel: 'cinemas', cinema: null, theatre: null, dateKey: null, dateLabel: null };
    let activeShowtimeId = null;
    let mode = 'primary'; // 'primary' | 'finance'

    ensureShell();
    render();

    function ensureShell() {
        seatArea.innerHTML = `
            <div class="mld-seat-area__header">
                <h2 class="mld-seat-area__title mld-seat-layout__title" id="mldDemoTitle"></h2>
                <div class="mld-view-toggle">
                    <button type="button" class="mld-view-toggle-btn" data-mode="primary" title="View">💺</button>
                    <button type="button" class="mld-view-toggle-btn" data-mode="finance" title="Financial report">💰</button>
                </div>
            </div>
            <div id="mldDemoBody" class="mld-view-fade"></div>
        `;
        seatArea.addEventListener('click', (e) => {
            const btn = e.target.closest('.mld-view-toggle-btn');
            if (!btn || btn.dataset.mode === mode) return;
            mode = btn.dataset.mode;
            render();
        });
    }

    /* ── Public API, called by navigation.js ─────────────────────── */
    function onNavStateChange(snapshot) {
        navState = snapshot;
        render();
    }

    function onShowtimeSelected(showtimeId) {
        activeShowtimeId = showtimeId;
        mode = 'primary'; // a fresh showtime click always surfaces the seat map first
        render();
    }

    /* ── Rendering ────────────────────────────────────────────────── */
    function render() {
        highlightToggle();
        const body = document.getElementById('mldDemoBody');

        if (mode === 'finance') {
            setTitle('Financial Report');
            financeBrowser.render(body, resolveFinanceContext());
            return;
        }

        renderPrimary(body);
    }

    function renderPrimary(body) {
        if (navState.panel === 'showtimes') {
            if (activeShowtimeId) {
                seatView.render(body, activeShowtimeId)
                    .then((data) => setTitle(seatView.titleFor(data, navState.theatre?.name)))
                    .catch(() => {});
            }
            // no showtime clicked yet in this panel visit: neutral, leave body as-is
            return;
        }

        if (navState.panel === 'cinemas') {
            if (navState.cinema) {
                setTitle(navState.cinema.name);
                infoCardView.renderCinemaOnly(body, navState.cinema);
            } else {
                setTitle('');
                body.innerHTML = placeholderHtml();
            }
            return;
        }

        if (navState.panel === 'theatres') {
            if (!navState.cinema) return;
            if (navState.theatre) {
                setTitle(`${navState.cinema.name} · ${navState.theatre.name}`);
                infoCardView.renderCinemaAndHall(body, navState.cinema, navState.theatre);
            } else {
                setTitle(navState.cinema.name);
                infoCardView.renderCinemaOnly(body, navState.cinema);
            }
            return;
        }

        if (navState.panel === 'dates') {
            if (navState.cinema && navState.theatre) {
                setTitle(`${navState.cinema.name} · ${navState.theatre.name}`);
                infoCardView.renderCinemaAndHall(body, navState.cinema, navState.theatre);
            }
            return;
        }
    }

    function resolveFinanceContext() {
        if (activeShowtimeId && navState.panel === 'showtimes') {
            return { level: 'L5', showtimeId: activeShowtimeId };
        }
        if (navState.panel === 'showtimes' && navState.cinema && navState.theatre && navState.dateKey) {
            return {
                level: 'L4',
                cinemaId: navState.cinema.id,
                theatreId: navState.theatre.id,
                dateKey: navState.dateKey,
                dateLabel: navState.dateLabel,
            };
        }
        if (navState.panel === 'dates' && navState.cinema && navState.theatre) {
            return { level: 'L3', cinemaId: navState.cinema.id, theatreId: navState.theatre.id };
        }
        if (navState.panel === 'theatres' && navState.cinema) {
            return { level: 'L2', cinemaId: navState.cinema.id };
        }
        return { level: 'L1' };
    }

    function highlightToggle() {
        seatArea.querySelectorAll('.mld-view-toggle-btn').forEach((b) => {
            b.classList.toggle('mld-view-toggle-btn--active', b.dataset.mode === mode);
        });
    }

    function setTitle(text) {
        const el = document.getElementById('mldDemoTitle');
        if (el) el.textContent = text;
    }

    function placeholderHtml() {
        return `
            <div class="ac-empty" style="padding:80px 20px;">
                <div class="ac-empty__icon">💺</div>
                <p class="ac-empty__text">Pick a cinema, theatre, date, and showtime to view the seat map — or open the 💰 tab any time for financial totals.</p>
            </div>
        `;
    }

    return { onNavStateChange, onShowtimeSelected };
}