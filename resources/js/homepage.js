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
 *
 * 5. Background expiry polling
 *    • Every 10 s, fetches /api/live-movie-ids from the server.
 *    • Any .hp-movie-card whose data-movie-id is absent from the
 *      response payload is smoothly faded out and then removed from
 *      the DOM — no full-page reload required.
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

    /* ================================================================
       BACKGROUND EXPIRY POLLING
       ─────────────────────────────────────────────────────────────
       Every 10 seconds we ask the server for the current set of
       active (non-expired) movie IDs.  Any card already rendered in
       the DOM whose data-movie-id is no longer in that set is faded
       out then physically removed.

       Design decisions:
         • Native fetch() — no jQuery / Axios dependency.
         • AbortController — cancels the in-flight request if the
           previous interval fires before the server responded (guards
           against slow network stacking up zombie requests).
         • CSS transition via an inline opacity + pointer-events tweak
           rather than adding a new CSS class, so this works even if
           the stylesheet hasn't declared an "expired" utility class.
         • After the fade we also recalculate the scrollbar thumb so
           the track stays correct with fewer cards.
         • Errors (network failures, non-200 responses) are logged
           silently and the interval is NOT cleared — we keep trying
           so a brief hiccup doesn't permanently disable the feature.
    ================================================================ */
    (function initExpiryPolling() {

        // The endpoint registered in routes/api.php (see Step B).
        var POLL_URL      = '/api/live-movie-ids';
        var POLL_INTERVAL = 10000; // 10 seconds
        var FADE_DURATION = 600;   // ms — matches the CSS transition below

        // Track the AbortController for the last in-flight request so we
        // can cancel it if a new tick fires before it resolves.
        var currentController = null;

        /**
         * Gracefully remove a single card element from the DOM.
         * 1. Apply a CSS transition for a smooth opacity fade.
         * 2. After the transition, collapse the card's width/margin
         *    so neighbouring cards slide together seamlessly.
         * 3. Remove the element entirely once the layout animation ends.
         *
         * @param {HTMLElement} cardEl
         */
        function fadeOutCard(cardEl) {
            // Prevent duplicate removal if the interval fires twice quickly.
            if (cardEl.dataset.removing === 'true') return;
            cardEl.dataset.removing = 'true';

            // Phase 1: fade opacity.
            cardEl.style.transition   = 'opacity ' + FADE_DURATION + 'ms ease, ' +
                                        'transform ' + FADE_DURATION + 'ms ease';
            cardEl.style.opacity      = '0';
            cardEl.style.transform    = 'scale(0.92)';
            cardEl.style.pointerEvents = 'none';

            // Phase 2: collapse width so the row reflows without a gap.
            setTimeout(function () {
                // Capture the current rendered width before we change anything.
                var fullWidth = cardEl.offsetWidth;

                // Transition the width + margins to 0 for a squeeze-out effect.
                cardEl.style.transition  += ', width ' + FADE_DURATION + 'ms ease, ' +
                                            'margin ' + FADE_DURATION + 'ms ease, ' +
                                            'padding ' + FADE_DURATION + 'ms ease';
                cardEl.style.overflow    = 'hidden';
                cardEl.style.width       = fullWidth + 'px'; // pin current first
                // Force reflow so the browser registers the explicit width
                // before we set it to 0 (otherwise the transition is skipped).
                void cardEl.offsetWidth; // eslint-disable-line no-void
                cardEl.style.width       = '0';
                cardEl.style.marginLeft  = '0';
                cardEl.style.marginRight = '0';
                cardEl.style.paddingLeft = '0';
                cardEl.style.paddingRight= '0';

                // Phase 3: remove from DOM and recalculate the scrollbar.
                setTimeout(function () {
                    if (cardEl.parentNode) {
                        cardEl.parentNode.removeChild(cardEl);
                    }
                    // Recalculate scrollbar thumb after the layout shift.
                    if (moviesRow && scrollThumb) {
                        updateScrollThumb && updateScrollThumb();
                    }
                }, FADE_DURATION + 50);

            }, FADE_DURATION);
        }

        /**
         * Fetch the live movie ID list and diff against the current DOM.
         * Called on every interval tick.
         */
        function pollLiveMovieIds() {
            // Cancel any previous request that hasn't resolved yet.
            if (currentController) {
                currentController.abort();
            }
            currentController = new AbortController();

            fetch(POLL_URL, {
                method:  'GET',
                headers: { 'Accept': 'application/json' },
                signal:  currentController.signal,
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Poll response not OK: ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                // data.ids is an array of integers, e.g. [1, 4, 7, 12]
                var liveIds = Array.isArray(data.ids) ? data.ids : [];

                // Convert to a Set<string> for O(1) lookup; we compare as
                // strings because dataset values are always strings.
                var liveIdSet = new Set(liveIds.map(String));

                // Query all rendered cards in the showtimes row.
                var cards = document.querySelectorAll('#hp-movies-row .hp-movie-card[data-movie-id]');

                cards.forEach(function (card) {
                    var cardMovieId = card.dataset.movieId; // e.g. "7"
                    if (!liveIdSet.has(cardMovieId)) {
                        fadeOutCard(card);
                    }
                });
            })
            .catch(function (err) {
                // AbortError is expected when we cancel a previous tick —
                // don't log those.  Log everything else for debugging.
                if (err.name !== 'AbortError') {
                    console.warn('[CinemaX] Expiry poll failed:', err.message);
                }
            });
        }

        // Kick off the interval.  We do NOT poll immediately on page load
        // because the controller's index() already served a fresh snapshot;
        // the first poll fires after the first 10-second window.
        setInterval(pollLiveMovieIds, POLL_INTERVAL);

    })();

    /* ================================================================
       SCROLL-THUMB HOISTING
       updateScrollThumb is declared inside the if-block above, but
       fadeOutCard needs to call it after a card is removed.  Because
       both live in the same IIFE scope and JS hoists var declarations
       (though not initialisations), we re-expose it here as a no-op
       fallback in case the moviesRow / scrollThumb elements are absent.
       If they ARE present the inner declaration shadows this one.
    ================================================================ */
    // (already declared in the scrollbar block above; this comment exists
    //  purely as a developer note — no additional code needed here.)

})();