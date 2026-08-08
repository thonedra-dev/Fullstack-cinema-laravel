// resources/js/movie-live/info-card-view.js
// Renders the compact "demonstration" info cards (cinema-only, or
// cinema+hall side-by-side) into #mldSeatArea. This is the OTHER thing
// that can occupy that container besides the seat/finance view — the two
// never render at the same time, navigation.js decides which one is active.

import { escapeHtml } from './utils.js';

export function initInfoCardView() {
    const seatArea = document.getElementById('mldSeatArea');

    function showCinemaOnly(cinema) {
        seatArea.innerHTML = `
            <div class="mld-info-card mld-view-fade">
                ${posterBlock(cinema.picture, '🏢')}
                <div class="mld-info-card__body">
                    <p class="mld-info-card__title">${escapeHtml(cinema.name || '—')}</p>
                    ${row('City', cinema.cityName)}
                    ${row('Address', cinema.address)}
                    ${row('Contact', cinema.contact)}
                    ${cinema.description ? row('Notes', cinema.description) : ''}
                </div>
            </div>
        `;
    }

    function showCinemaAndHall(cinema, theatre) {
        seatArea.innerHTML = `
            <div class="mld-info-card mld-info-card--split mld-view-fade">
                <div class="mld-info-card__col">
                    ${posterBlock(cinema.picture, '🏢')}
                    <div class="mld-info-card__body">
                        <p class="mld-info-card__title">${escapeHtml(cinema.name || '—')}</p>
                        ${row('City', cinema.cityName)}
                        ${row('Address', cinema.address)}
                        ${row('Contact', cinema.contact)}
                    </div>
                </div>
                <div class="mld-info-card__divider"></div>
                <div class="mld-info-card__col">
                    ${posterBlock(theatre.poster, '🎭', theatre.icon)}
<div class="mld-info-card__body">
    <p class="mld-info-card__title">${escapeHtml(theatre.name || '—')}</p>
</div>
                </div>
            </div>
        `;
    }

   function posterBlock(picturePath, fallbackIcon, badgeIconPath) {
    const badge = badgeIconPath
        ? `<img src="${escapeHtml(badgeIconPath)}" alt="" class="mld-info-card__badge">`
        : '';

    if (picturePath) {
        return `
            <div class="mld-info-card__poster-wrap">
                <img src="${escapeHtml(picturePath)}" alt="" class="mld-info-card__poster">
                ${badge}
            </div>
        `;
    }
    return `<div class="mld-info-card__poster-wrap mld-info-card__poster-ph">${fallbackIcon}${badge}</div>`;
}

    function row(label, value) {
        if (!value) return '';
        return `
            <div class="mld-info-card__row">
                <span class="mld-info-card__label">${escapeHtml(label)}</span>
                <span class="mld-info-card__value">${escapeHtml(value)}</span>
            </div>
        `;
    }

    return { showCinemaOnly, showCinemaAndHall };
}