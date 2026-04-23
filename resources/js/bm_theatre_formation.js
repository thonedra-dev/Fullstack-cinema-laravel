/**
 * resources/js/bm_theatre_formation.js
 *
 * Theatre formation page — three-panel interaction:
 *
 *   LEFT  – render expandable movie list from JSON
 *           clicking a movie sets the activeMovieId → triggers time refresh
 *
 *   RIGHT – mini calendar (single-date select)
 *           days that have at least one showtime for this theatre are highlighted
 *           clicking a date sets activeDate → triggers time refresh
 *
 *   RIGHT (below calendar) – time chips
 *           shown when both activeMovieId AND activeDate are set
 *           lists unique start_time values for that combo
 *
 * DATA (from #btf-data):
 *   data-movies     – [{movie_id, movie_name, runtime, language, production_name,
 *                       landscape_poster, portrait_poster}]
 *   data-showtimes  – [{movie_id, date:'YYYY-MM-DD', start:'HH:MM:SS',
 *                       start_fmt:'hh:mm AM', end:'HH:MM:SS', end_fmt:'hh:mm AM'}]
 */
(function () {
    'use strict';

    var dataEl = document.getElementById('btf-data');
    if (!dataEl) return;

    var allMovies    = JSON.parse(dataEl.dataset.movies    || '[]');
    var allShowtimes = JSON.parse(dataEl.dataset.showtimes || '[]');

    var activeMovieId = null;
    var activeDate    = null;

    var calDate = new Date();
    calDate.setDate(1);

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

    function pad(n) { return String(n).padStart(2, '0'); }

    /* ================================================================
       MOVIE LIST  (left panel)
    ================================================================ */
    function buildMovieList() {
        var container = document.getElementById('btf-movie-list');
        if (!container) return;

        if (allMovies.length === 0) {
            container.innerHTML = '<p class="btf-no-movies">No movies scheduled in this theatre yet.</p>';
            return;
        }

        allMovies.forEach(function (movie) {
            var item = document.createElement('div');
            item.className    = 'btf-movie-item';
            item.dataset.mid  = movie.movie_id;

            var h = Math.floor(movie.runtime / 60);
            var m = movie.runtime % 60;
            var runtimeStr = (h > 0 ? h + 'h ' : '') + m + 'm';

            /* Header row (always visible) */
            var header = document.createElement('div');
            header.className = 'btf-movie-item__header';
            header.innerHTML =
                '<div class="btf-movie-item__title-wrap">' +
                    '<span class="btf-movie-item__name">' + movie.movie_name + '</span>' +
                    '<span class="btf-movie-item__meta">' + runtimeStr + ' · ' + movie.language + '</span>' +
                '</div>' +
                '<button type="button" class="btf-expand-btn" aria-label="Expand" data-mid="' + movie.movie_id + '">▾</button>';
            item.appendChild(header);

            /* Expandable detail */
            var detail = document.createElement('div');
            detail.className = 'btf-movie-item__detail btf-hidden';

            var posterHtml = movie.portrait_poster
                ? '<img src="/images/movies/' + movie.portrait_poster + '" alt="' + movie.movie_name + '" class="btf-detail-poster">'
                : '<div class="btf-detail-poster-ph">🎬</div>';

            detail.innerHTML =
                '<div class="btf-detail-inner">' +
                    posterHtml +
                    '<div class="btf-detail-info">' +
                        '<p class="btf-detail-row"><span class="btf-detail-lbl">Production</span>' + (movie.production_name || '—') + '</p>' +
                        '<p class="btf-detail-row"><span class="btf-detail-lbl">Runtime</span>'    + runtimeStr + '</p>' +
                        '<p class="btf-detail-row"><span class="btf-detail-lbl">Language</span>'   + (movie.language || '—') + '</p>' +
                    '</div>' +
                '</div>';
            item.appendChild(detail);

            /* Click on the item row (not the expand btn) → set active movie */
            header.addEventListener('click', function (e) {
                var btn = e.target.closest('.btf-expand-btn');
                if (btn) {
                    /* Expand/collapse */
                    var isOpen = !detail.classList.contains('btf-hidden');
                    detail.classList.toggle('btf-hidden', isOpen);
                    btn.textContent = isOpen ? '▾' : '▴';
                    return;
                }
                /* Select as active movie */
                selectMovie(movie.movie_id);
            });

            container.appendChild(item);
        });
    }

    function selectMovie(mid) {
        activeMovieId = mid;
        /* Highlight */
        document.querySelectorAll('.btf-movie-item').forEach(function (el) {
            el.classList.toggle('btf-movie-item--active', String(el.dataset.mid) === String(mid));
        });
        renderTimes();
    }

    /* ================================================================
       CALENDAR  (right panel)
    ================================================================ */

    /* All unique dates that have ANY showtime in this theatre */
    function hasShowtimeOnDate(dateStr) {
        return allShowtimes.some(function (st) { return st.date === dateStr; });
    }

    function renderCalendar() {
        var grid  = document.getElementById('btf-cal-grid');
        var label = document.getElementById('btf-cal-month');
        if (!grid || !label) return;

        var year  = calDate.getFullYear();
        var month = calDate.getMonth();
        label.textContent = MONTHS[month] + ' ' + year;
        grid.innerHTML    = '';

        var firstDay = new Date(year, month, 1).getDay();
        var days     = new Date(year, month + 1, 0).getDate();

        /* Empty leading cells */
        for (var e = 0; e < firstDay; e++) {
            var empty = document.createElement('div');
            empty.className = 'btf-cal-day btf-cal-day--empty';
            grid.appendChild(empty);
        }

        for (var d = 1; d <= days; d++) {
            var iso     = year + '-' + pad(month + 1) + '-' + pad(d);
            var hasShow = hasShowtimeOnDate(iso);
            var isActive= activeDate === iso;

            var cell = document.createElement('div');
            cell.className = 'btf-cal-day' +
                (hasShow  ? ' btf-cal-day--has-show' : '') +
                (isActive ? ' btf-cal-day--active'   : '');
            cell.textContent = d;
            cell.dataset.date = iso;

            cell.addEventListener('click', (function (isoVal, el) {
                return function () { selectDate(isoVal); };
            })(iso, cell));

            grid.appendChild(cell);
        }
    }

    function selectDate(dateStr) {
        activeDate = dateStr;
        renderCalendar();
        renderTimes();
    }

    /* ================================================================
       TIME CHIPS  (below calendar)
    ================================================================ */
    function renderTimes() {
        var wrap   = document.getElementById('btf-times-wrap');
        var label  = document.getElementById('btf-times-label');
        var pills  = document.getElementById('btf-times-pills');
        if (!wrap || !label || !pills) return;

        pills.innerHTML = '';

        if (!activeMovieId || !activeDate) {
            label.textContent = 'Select a movie and a date to view showtimes.';
            return;
        }

        /* Filter showtimes: matching movie + date */
        var matching = allShowtimes.filter(function (st) {
            return String(st.movie_id) === String(activeMovieId) && st.date === activeDate;
        });

        /* Deduplicate by start time */
        var seen   = {};
        var unique = [];
        matching.forEach(function (st) {
            if (!seen[st.start]) {
                seen[st.start] = true;
                unique.push(st);
            }
        });

        /* Find movie name */
        var movieObj = allMovies.find(function (m) { return String(m.movie_id) === String(activeMovieId); });
        var movieName = movieObj ? movieObj.movie_name : '';
        label.textContent = unique.length > 0
            ? movieName + ' on ' + activeDate
            : 'No showtimes for ' + movieName + ' on ' + activeDate;

        unique.forEach(function (st) {
            var pill = document.createElement('button');
            pill.type      = 'button';
            pill.className = 'btf-time-pill';
            pill.textContent = st.start_fmt;
            pill.title       = st.start_fmt + ' → ' + st.end_fmt;
            /* Future: navigate to seat selection */
            pill.addEventListener('click', function () {
                pill.classList.toggle('btf-time-pill--selected');
            });
            pills.appendChild(pill);
        });
    }

    /* ================================================================
       CALENDAR NAV
    ================================================================ */
    function initCalendarNav() {
        var prev = document.getElementById('btf-cal-prev');
        var next = document.getElementById('btf-cal-next');
        if (prev) prev.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() - 1); renderCalendar();
        });
        if (next) next.addEventListener('click', function () {
            calDate.setMonth(calDate.getMonth() + 1); renderCalendar();
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        /* Seed calendar to the first month that has any showtime */
        if (allShowtimes.length > 0) {
            var first = new Date(allShowtimes[0].date);
            calDate = new Date(first.getFullYear(), first.getMonth(), 1);
        }

        buildMovieList();
        renderCalendar();
        initCalendarNav();
    });

})();