// resources/js/movie-live/info-card-view.js
// Renders the compact "demonstration" info cards (cinema-only, or
// cinema+hall side-by-side) into a caller-supplied container.
// demo-section.js owns deciding WHEN this shows; this module only knows
// HOW to render it.

import { escapeHtml } from './utils.js';

export function initInfoCardView() {
    function renderCinemaOnly(container, cinema) {
        container.innerHTML = `
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

    function renderCinemaAndHall(container, cinema, theatre) {
        container.innerHTML = `
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

    // badgeIconPath renders as a small corner badge over the poster
    // (theatre_icon), rather than a text row — it's an image, not a label.
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

    return { renderCinemaOnly, renderCinemaAndHall };
}