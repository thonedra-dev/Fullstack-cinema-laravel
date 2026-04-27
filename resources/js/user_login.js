(function () {
    'use strict';

    var slides = Array.from(document.querySelectorAll('.ul-slide'));
    var scan = document.getElementById('ul-slide-scan');
    var particles = document.getElementById('ul-particles');
    var current = 0;

    function runScan() {
        if (!scan) return;

        scan.classList.remove('ul-slide-scan--run');
        void scan.offsetWidth;
        scan.classList.add('ul-slide-scan--run');
    }

    function nextSlide() {
        if (slides.length <= 1) return;

        slides[current].classList.remove('ul-slide--active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('ul-slide--active');
        runScan();
    }

    if (slides.length > 1) {
        runScan();
        window.setInterval(nextSlide, 5200);
    }

    if (particles) {
        var count = window.matchMedia('(max-width: 640px)').matches ? 28 : 48;

        for (var i = 0; i < count; i += 1) {
            var particle = document.createElement('span');
            var size = Math.random() * 2.4 + 1.2;
            var delay = Math.random() * -18;
            var duration = Math.random() * 13 + 15;

            particle.className = 'ul-particle';
            particle.style.left = (Math.random() * 100) + 'vw';
            particle.style.top = (Math.random() * 100) + 'vh';
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.animationDelay = delay + 's';
            particle.style.animationDuration = duration + 's';
            particle.style.opacity = String(Math.random() * 0.46 + 0.18);

            particles.appendChild(particle);
        }
    }
})();
