/**
 * resources/js/movie_details.js
 */
(function () {
    'use strict';

    var dataEl = document.getElementById('md-data');
    if (!dataEl) return;

    var stateGroups = JSON.parse(dataEl.dataset.groups || '[]');
    if (!stateGroups || stateGroups.length === 0) return;

    var sidebarEl    = document.getElementById('md-sidebar');
    var dateStripEl  = document.getElementById('md-date-strip');
    var showtimeSect = document.getElementById('md-showtime-section');
    var cinemaLabel  = document.getElementById('md-cinema-label');

    if (!sidebarEl || !dateStripEl || !showtimeSect || !cinemaLabel) return;

    var activeCinema = null;
    var activeState  = '';
    var activeDateIdx = 0;

    function buildCinemaHeaderText() {
        if (!activeCinema) return 'Select a cinema';
        return activeState + ' - ' + activeCinema.cinema_name;
    }

    function updateCinemaHeader() {
        cinemaLabel.textContent = buildCinemaHeaderText();
    }

    function buildSidebar() {
        sidebarEl.innerHTML = '';

        stateGroups.forEach(function (sg, stateIdx) {
            var groupEl = document.createElement('div');
            groupEl.className = 'md-state-group' + (stateIdx === 0 ? ' md-state-group--open' : '');

            var toggleBtn = document.createElement('button');
            toggleBtn.className = 'md-state-toggle';
            toggleBtn.innerHTML =
                '<span>' + sg.state + '</span>' +
                '<span class="md-state-toggle__chevron">▾</span>';

            toggleBtn.addEventListener('click', function () {
                var isOpen = groupEl.classList.contains('md-state-group--open');

                sidebarEl.querySelectorAll('.md-state-group').forEach(function (g) {
                    g.classList.remove('md-state-group--open');
                });

                if (!isOpen) {
                    groupEl.classList.add('md-state-group--open');
                }
            });

            groupEl.appendChild(toggleBtn);

            var listEl = document.createElement('div');
            listEl.className = 'md-cinema-list';

            sg.cinemas.forEach(function (cinema, cinemaIdx) {
                var item = document.createElement('div');
                item.className = 'md-cinema-item';
                item.dataset.cinemaId = cinema.cinema_id;
                item.dataset.state = sg.state;

                item.innerHTML =
                    '<div>' +
                        '<div class="md-cinema-item__name">' + cinema.cinema_name + '</div>' +
                        '<div class="md-cinema-item__city">' + cinema.city + '</div>' +
                    '</div>';

                item.addEventListener('click', function () {
                    selectCinema(cinema, sg.state);
                });

                listEl.appendChild(item);

                if (stateIdx === 0 && cinemaIdx === 0) {
                    item.classList.add('md-cinema-item--active');
                    activeCinema = cinema;
                    activeState = sg.state;
                }
            });

            groupEl.appendChild(listEl);
            sidebarEl.appendChild(groupEl);
        });
    }

    function selectCinema(cinema, stateName) {
        activeCinema = cinema;
        activeState = stateName;
        activeDateIdx = 0;

        sidebarEl.querySelectorAll('.md-cinema-item').forEach(function (item) {
            var active = parseInt(item.dataset.cinemaId, 10) === cinema.cinema_id
                && item.dataset.state === stateName;

            item.classList.toggle('md-cinema-item--active', active);
        });

        updateCinemaHeader();
        renderDateStrip(cinema.dateGroups);
    }

    function renderDateStrip(dateGroups) {
        dateStripEl.innerHTML = '';

        if (!dateGroups || dateGroups.length === 0) {
            showtimeSect.innerHTML =
                '<p class="md-select-hint">No showtimes available for this cinema.</p>';
            return;
        }

        dateGroups.forEach(function (dg, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'md-date-btn' + (idx === activeDateIdx ? ' md-date-btn--active' : '');
            btn.dataset.idx = idx;

            btn.innerHTML =
                '<span class="md-date-btn__day">' + dg.label_day + '</span>' +
                '<div class="md-date-btn__num-wrap">' +
                    '<span class="md-date-btn__num">' + dg.label_num + '</span>' +
                    '<span class="md-date-btn__month">' + dg.label_month + '</span>' +
                '</div>';

            btn.addEventListener('click', function () {
                activeDateIdx = idx;

                dateStripEl.querySelectorAll('.md-date-btn').forEach(function (b) {
                    b.classList.remove('md-date-btn--active');
                });

                btn.classList.add('md-date-btn--active');
                renderShowtimes(dateGroups[idx]);
            });

            dateStripEl.appendChild(btn);
        });

        renderShowtimes(dateGroups[activeDateIdx]);
    }

    function renderShowtimes(dateGroup) {
        showtimeSect.innerHTML = '';

        if (!dateGroup || !dateGroup.theatres || dateGroup.theatres.length === 0) {
            showtimeSect.innerHTML =
                '<p class="md-select-hint">No showtimes on this date.</p>';
            return;
        }

        dateGroup.theatres.forEach(function (theatre) {
            var block = document.createElement('div');
            block.className = 'md-theatre-block';

            var nameEl = document.createElement('div');
            nameEl.className = 'md-theatre-name';
            nameEl.textContent = theatre.name;
            block.appendChild(nameEl);

            var pillsWrap = document.createElement('div');
            pillsWrap.className = 'md-time-pills';

            if (!theatre.times || theatre.times.length === 0) {
                var hint = document.createElement('span');
                hint.style.cssText = 'font-size:0.78rem;color:var(--md-text-muted);';
                hint.textContent = 'No times available.';
                pillsWrap.appendChild(hint);
            } else {
                theatre.times.forEach(function (time) {
                    var pill = document.createElement('button');
                    pill.type = 'button';
                    pill.className = 'md-time-pill';
                    pill.textContent = time;

                    pill.addEventListener('click', function () {
                        var seatRoute = document.body.dataset.seatRoute;
                        var movieId = document.body.dataset.movieId;

                        if (!seatRoute || !movieId || !activeCinema) return;

                        var url = seatRoute
                            + '?movie_id=' + encodeURIComponent(movieId)
                            + '&cinema_id=' + encodeURIComponent(activeCinema.cinema_id)
                            + '&theatre_name=' + encodeURIComponent(theatre.name)
                            + '&date=' + encodeURIComponent(dateGroup.date)
                            + '&time=' + encodeURIComponent(time);

                        window.location.href = url;
                    });

                    pillsWrap.appendChild(pill);
                });
            }

            block.appendChild(pillsWrap);
            showtimeSect.appendChild(block);
        });
    }

    buildSidebar();

    if (activeCinema) {
        updateCinemaHeader();
        selectCinema(activeCinema, activeState);
    }
})();