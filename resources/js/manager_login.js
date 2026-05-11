/**
 * resources/js/manager_login.js
 * Branch Manager login — slideshow + floating particles.
 * Mirrors user_login.js logic with green colour palette.
 */
(function () {
    'use strict';

    var slides    = Array.from(document.querySelectorAll('.ml-slide'));
    var scan      = document.getElementById('ml-slide-scan');
    var particles = document.getElementById('ml-particles');
    var current   = 0;

    /* ── Scan-line FX ──────────────────────────────────── */
    function runScan() {
        if (!scan) return;
        scan.classList.remove('ml-slide-scan--run');
        void scan.offsetWidth;   // force reflow to restart animation
        scan.classList.add('ml-slide-scan--run');
    }

    /* ── Slide rotation ────────────────────────────────── */
    function nextSlide() {
        if (slides.length <= 1) return;
        slides[current].classList.remove('ml-slide--active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('ml-slide--active');
        runScan();
    }

    if (slides.length > 1) {
        runScan();
        window.setInterval(nextSlide, 5200);
    }

    /* ── Floating particles ────────────────────────────── */
    if (particles) {
        var count = window.matchMedia('(max-width: 640px)').matches ? 24 : 44;

        for (var i = 0; i < count; i++) {
            var p        = document.createElement('span');
            var size     = Math.random() * 2.2 + 1.1;
            var delay    = Math.random() * -18;
            var duration = Math.random() * 13 + 15;

            p.className              = 'ml-particle';
            p.style.left             = (Math.random() * 100) + 'vw';
            p.style.top              = (Math.random() * 100) + 'vh';
            p.style.width            = size + 'px';
            p.style.height           = size + 'px';
            p.style.animationDelay    = delay + 's';
            p.style.animationDuration = duration + 's';
            p.style.opacity           = String(Math.random() * 0.44 + 0.16);

            particles.appendChild(p);
        }
    }

})();