// resources/js/movie-live/utils.js
// Pure helpers shared by navigation.js and seat-finance-view.js. No state here.

export function loadingHtml(msg) {
    return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`;
}

export function emptyHtml(msg) {
    return `<p class="mld-empty-note">${escapeHtml(msg)}</p>`;
}

export function slugify(str) {
    return String(str).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

export function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

export function formatMoney(val) {
    const n = Number(val);
    if (val === null || val === undefined || Number.isNaN(n)) return '—';
    return n.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
}

export function formatDateKey(dateKey) {
    // dateKey is 'YYYY-MM-DD' from Postgres DATE()
    const [y, m, d] = String(dateKey).split('-').map(Number);
    const dt = new Date(y, (m || 1) - 1, d || 1);
    return dt.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}

export function parseTimestamp(iso) {
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