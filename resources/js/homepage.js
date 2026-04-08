/**
 * resources/js/homepage.js
 *
 * 1. Hero carousel
 *    • sessionStorage-persistent index (advances each page load)
 *    • Dot indicators + click navigation
 *    • Title / meta overlay update
 *    • Watch Trailer button toggle
 *
 * 2. Parallax effect on hero poster
 *    • On scroll, each .hp-hero__slide-img gets a translateY
 *      proportional to how far the hero has scrolled off screen
 *    • Gives a layered depth effect as the user scrolls into Div 2
 *
 * 3. Trailer modal
 *    • Opens with autoplay, closes on ✕ / backdrop / Escape
 *
 * 4. Custom horizontal scrollbar for the movie card row
 */
(function () {
    'use strict';

    /* ================================================================
       DATA
    ================================================================ */
    var dataEl = document.getElementById('hp-hero-data');
    if (!dataEl) return;

    var movies = JSON.parse(dataEl.dataset.movies || '[]');

    /* ================================================================
       HERO CAROUSEL
    ================================================================ */
    if (movies.length === 0) {
        var heroEl = document.getElementById('hp-hero');
        if (heroEl) heroEl.style.display = 'none';
    } else {

        var SESSION_KEY  = 'hp_hero_idx';
        var slidesWrap   = document.getElementById('hp-hero-slides');
        var dotsWrap     = document.getElementById('hp-hero-dots');
        var titleEl      = document.getElementById('hp-hero-title');
        var metaEl       = document.getElementById('hp-hero-meta');
        var trailerBtn   = document.getElementById('hp-watch-trailer-btn');

        /* Advance index once per page load ────────────────── */
        var stored     = parseInt(sessionStorage.getItem(SESSION_KEY) || '-1', 10);
        var currentIdx = (stored + 1) % movies.length;
        sessionStorage.setItem(SESSION_KEY, currentIdx);

        /* Build slides + dots ─────────────────────────────── */
        movies.forEach(function (movie, i) {
            var slide = document.createElement('div');
            slide.className   = 'hp-hero__slide' + (i === currentIdx ? ' hp-hero__slide--active' : '');
            slide.dataset.idx = i;

            var img       = document.createElement('img');
            img.src       = movie.poster;
            img.alt       = movie.title;
            img.className = 'hp-hero__slide-img';
            slide.appendChild(img);
            slidesWrap.appendChild(slide);

            var dot       = document.createElement('button');
            dot.className = 'hp-dot' + (i === currentIdx ? ' hp-dot--active' : '');
            dot.dataset.idx = i;
            dot.addEventListener('click', function () {
                goToSlide(parseInt(this.dataset.idx, 10));
            });
            dotsWrap.appendChild(dot);
        });

        /* Update text overlay ─────────────────────────────── */
        function updateOverlay(idx) {
            var m = movies[idx];
            if (!m) return;

            titleEl.textContent = m.title;

            var parts   = [];
            if (m.genres) parts.push(m.genres);
            var runtime = m.runtime_h > 0
                ? m.runtime_h + ' hr ' + m.runtime_m + ' mins'
                : m.runtime_m + ' mins';
            parts.push(runtime);
            if (m.language) parts.push(m.language);
            metaEl.textContent = parts.join('  |  ');

            if (trailerBtn) {
                trailerBtn.style.display = m.trailer_url ? '' : 'none';
            }
        }

        /* Switch slide ────────────────────────────────────── */
        function goToSlide(idx) {
            var prevSlide = slidesWrap.querySelector('.hp-hero__slide--active');
            var prevDot   = dotsWrap.querySelector('.hp-dot--active');
            if (prevSlide) prevSlide.classList.remove('hp-hero__slide--active');
            if (prevDot)   prevDot.classList.remove('hp-dot--active');

            currentIdx    = (idx + movies.length) % movies.length;
            var newSlide  = slidesWrap.querySelector('[data-idx="' + currentIdx + '"]');
            var newDot    = dotsWrap.querySelector('[data-idx="' + currentIdx + '"]');
            if (newSlide) newSlide.classList.add('hp-hero__slide--active');
            if (newDot)   newDot.classList.add('hp-dot--active');

            updateOverlay(currentIdx);
            sessionStorage.setItem(SESSION_KEY, currentIdx);
        }

        updateOverlay(currentIdx);

        /* ================================================================
           PARALLAX — hero images shift on scroll
           ─────────────────────────────────────────────────────────────
           The image is 118% tall. As the user scrolls the hero off-screen,
           we translate the image upward by up to ~15% of the hero height.
           This creates a slow upward drift that gives depth without being
           distracting.
        ================================================================ */
        var heroSection = document.getElementById('hp-hero');

        function applyParallax() {
            if (!heroSection) return;

            var rect        = heroSection.getBoundingClientRect();
            var heroH       = heroSection.offsetHeight;
            // scrolled fraction: 0 = hero fully in view, 1 = hero fully above viewport
            var scrolled    = Math.max(0, -rect.top);
            var fraction    = Math.min(scrolled / heroH, 1);
            // max translateY offset in px (negative = move image up)
            var maxOffset   = heroH * 0.15;
            var offset      = -fraction * maxOffset;

            var imgs = heroSection.querySelectorAll('.hp-hero__slide-img');
            imgs.forEach(function (img) {
                img.style.transform = 'translateY(' + offset + 'px)';
            });
        }

        // Listen on scroll (passive for perf)
        window.addEventListener('scroll', applyParallax, { passive: true });
        applyParallax(); // initial call

        /* ================================================================
           TRAILER MODAL
        ================================================================ */
        var trailerOverlay = document.getElementById('hp-trailer-overlay');
        var trailerIframe  = document.getElementById('hp-trailer-iframe');
        var trailerClose   = document.getElementById('hp-trailer-close');

        function openTrailer(embedUrl) {
            if (!embedUrl) return;
            trailerIframe.src = embedUrl + '?autoplay=1&rel=0';
            trailerOverlay.classList.add('hp-trailer-overlay--open');
        }

        function closeTrailer() {
            trailerOverlay.classList.remove('hp-trailer-overlay--open');
            trailerIframe.src = '';
        }

        if (trailerBtn) {
            trailerBtn.addEventListener('click', function () {
                var m = movies[currentIdx];
                if (m && m.trailer_url) openTrailer(m.trailer_url);
            });
        }

        if (trailerClose)   trailerClose.addEventListener('click', closeTrailer);
        if (trailerOverlay) {
            trailerOverlay.addEventListener('click', function (e) {
                if (e.target === trailerOverlay) closeTrailer();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeTrailer();
        });
    }

    /* ================================================================
       CUSTOM HORIZONTAL SCROLLBAR
    ================================================================ */
    var moviesRow   = document.getElementById('hp-movies-row');
    var scrollThumb = document.getElementById('hp-scroll-thumb');

    if (moviesRow && scrollThumb) {
        function updateScrollThumb() {
            var scrollLeft  = moviesRow.scrollLeft;
            var scrollWidth = moviesRow.scrollWidth;
            var clientWidth = moviesRow.clientWidth;
            var trackWidth  = moviesRow.parentElement.clientWidth;

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

        moviesRow.addEventListener('scroll', updateScrollThumb, { passive: true });
        window.addEventListener('resize', updateScrollThumb);
        updateScrollThumb();
    }

})();