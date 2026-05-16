/**
 * resources/js/manager_login.js
 */
(function () {
    'use strict';

    var slides    = Array.from(document.querySelectorAll('.ml-slide'));
    var particles = document.getElementById('ml-particles');
    var current   = 0;

    /* ── Slide rotation ────────────────────────────────── */
    function nextSlide() {
        if (slides.length <= 1) return;
        slides[current].classList.remove('ml-slide--active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('ml-slide--active');
    }

    if (slides.length > 1) {
        window.setInterval(nextSlide, 5200);
    }

    /* ── Floating particles ────────────────────────────── */
    if (particles) {
        var count = 30; // Cleaned up count
        var containerWidth = particles.offsetWidth;
        var containerHeight = particles.offsetHeight;

        for (var i = 0; i < count; i++) {
            var p        = document.createElement('span');
            var size     = Math.random() * 2.5 + 1;
            var delay    = Math.random() * -20;
            var duration = Math.random() * 10 + 10;

            p.className               = 'ml-particle';
            p.style.left              = (Math.random() * 100) + '%';
            p.style.top               = (Math.random() * 100) + '%';
            p.style.width             = size + 'px';
            p.style.height            = size + 'px';
            p.style.animationDelay    = delay + 's';
            p.style.animationDuration = duration + 's';
            p.style.opacity           = String(Math.random() * 0.5 + 0.2);

            particles.appendChild(p);
        }
    }
})();