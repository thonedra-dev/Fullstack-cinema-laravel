/**
 * resources/js/homepage.js
 *
 * Public user homepage behaviour:
 *   1. Hero carousel — one landscape poster at a time
 *      • Index persists across page reloads via sessionStorage
 *        so every reload / navigation-back advances one slide
 *      • Dot indicators sync with active slide
 *      • Title + meta overlay update per slide
 *   2. Trailer popup — fetches embed URL from data, opens modal
 *   3. Custom horizontal scrollbar thumb for the movie row
 */
(function () {
    'use strict';

    /* ================================================================
       HERO CAROUSEL
    ================================================================ */
    var dataEl = document.getElementById('hp-hero-data');
    if (!dataEl) return;

    var movies = JSON.parse(dataEl.dataset.movies || '[]');

    if (movies.length === 0) {
        // Hide the hero if there are no movies at all
        var heroEl = document.getElementById('hp-hero');
        if (heroEl) heroEl.style.display = 'none';
    } else {

        var SESSION_KEY  = 'hp_hero_idx';
        var slidesWrap   = document.getElementById('hp-hero-slides');
        var dotsWrap     = document.getElementById('hp-hero-dots');
        var titleEl      = document.getElementById('hp-hero-title');
        var metaEl       = document.getElementById('hp-hero-meta');
        var trailerBtn   = document.getElementById('hp-watch-trailer-btn');

        // ── Advance index once per page load ──────────────────
        var stored = parseInt(sessionStorage.getItem(SESSION_KEY) || '-1', 10);
        var currentIdx = (stored + 1) % movies.length;
        sessionStorage.setItem(SESSION_KEY, currentIdx);

        // ── Build slides + dots ───────────────────────────────
        movies.forEach(function (movie, i) {
            // Slide
            var slide = document.createElement('div');
            slide.className = 'hp-hero__slide' + (i === currentIdx ? ' hp-hero__slide--active' : '');
            slide.dataset.idx = i;

            var img = document.createElement('img');
            img.src       = movie.poster;
            img.alt       = movie.title;
            img.className = 'hp-hero__slide-img';
            slide.appendChild(img);
            slidesWrap.appendChild(slide);

            // Dot
            var dot = document.createElement('button');
            dot.className = 'hp-dot' + (i === currentIdx ? ' hp-dot--active' : '');
            dot.dataset.idx = i;
            dot.addEventListener('click', function () { goToSlide(parseInt(this.dataset.idx, 10)); });
            dotsWrap.appendChild(dot);
        });

        // ── Update text overlay for current slide ─────────────
        function updateOverlay(idx) {
            var m = movies[idx];
            if (!m) return;

            titleEl.textContent = m.title;

            var parts = [];
            if (m.genres)    parts.push(m.genres);
            var runtime = m.runtime_h > 0
                ? m.runtime_h + ' hr ' + m.runtime_m + ' mins'
                : m.runtime_m + ' mins';
            parts.push(runtime);
            if (m.language)  parts.push(m.language);
            metaEl.textContent = parts.join('  |  ');

            // Toggle trailer button visibility
            if (m.trailer_url) {
                trailerBtn.style.display = '';
            } else {
                trailerBtn.style.display = 'none';
            }
        }

        function goToSlide(idx) {
            // Deactivate current
            var activeSlide = slidesWrap.querySelector('.hp-hero__slide--active');
            var activeDot   = dotsWrap.querySelector('.hp-dot--active');
            if (activeSlide) activeSlide.classList.remove('hp-hero__slide--active');
            if (activeDot)   activeDot.classList.remove('hp-dot--active');

            // Activate target
            currentIdx = (idx + movies.length) % movies.length;
            var newSlide = slidesWrap.querySelector('[data-idx="' + currentIdx + '"]');
            var newDot   = dotsWrap.querySelector('[data-idx="' + currentIdx + '"]');
            if (newSlide) newSlide.classList.add('hp-hero__slide--active');
            if (newDot)   newDot.classList.add('hp-dot--active');

            updateOverlay(currentIdx);
            sessionStorage.setItem(SESSION_KEY, currentIdx);
        }

        // Initial overlay update
        updateOverlay(currentIdx);

        /* ================================================================
           TRAILER MODAL
        ================================================================ */
        var trailerOverlay = document.getElementById('hp-trailer-overlay');
        var trailerIframe  = document.getElementById('hp-trailer-iframe');
        var trailerClose   = document.getElementById('hp-trailer-close');

        function openTrailer(embedUrl) {
            if (!embedUrl) return;
            // Add ?autoplay=1 so the video plays immediately
            trailerIframe.src = embedUrl + '?autoplay=1&rel=0';
            trailerOverlay.classList.add('hp-trailer-overlay--open');
        }

        function closeTrailer() {
            trailerOverlay.classList.remove('hp-trailer-overlay--open');
            // Stop the video by clearing src
            trailerIframe.src = '';
        }

        if (trailerBtn) {
            trailerBtn.addEventListener('click', function () {
                var m = movies[currentIdx];
                if (m && m.trailer_url) openTrailer(m.trailer_url);
            });
        }

        if (trailerClose) {
            trailerClose.addEventListener('click', closeTrailer);
        }

        if (trailerOverlay) {
            trailerOverlay.addEventListener('click', function (e) {
                if (e.target === trailerOverlay) closeTrailer();
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeTrailer();
        });
    }

    /* ================================================================
       CUSTOM HORIZONTAL SCROLLBAR  (for the movie card row)
    ================================================================ */
    var moviesRow  = document.getElementById('hp-movies-row');
    var scrollThumb = document.getElementById('hp-scroll-thumb');

    if (moviesRow && scrollThumb) {
        function updateScrollThumb() {
            var scrollLeft   = moviesRow.scrollLeft;
            var scrollWidth  = moviesRow.scrollWidth;
            var clientWidth  = moviesRow.clientWidth;
            var trackWidth   = moviesRow.parentElement.clientWidth;

            if (scrollWidth <= clientWidth) {
                scrollThumb.style.display = 'none';
                return;
            }

            scrollThumb.style.display = '';

            var thumbWidth = Math.max(40, (clientWidth / scrollWidth) * trackWidth);
            var thumbLeft  = (scrollLeft / (scrollWidth - clientWidth)) * (trackWidth - thumbWidth);

            scrollThumb.style.width = thumbWidth + 'px';
            scrollThumb.style.left  = thumbLeft  + 'px';
        }

        moviesRow.addEventListener('scroll', updateScrollThumb);
        window.addEventListener('resize', updateScrollThumb);

        // Initial position
        updateScrollThumb();
    }

})();