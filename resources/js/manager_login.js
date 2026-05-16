/**
 * resources/js/manager_login.js
 * Cinema X — Branch Manager Login
 * Sci-fi grid · Roaming bg elements · Slide rotation+counter · Particles · PW toggle · Submit slide
 */
(function () {
    'use strict';

    /* ══ 1. SLIDE ROTATION + COUNTER ═══════════════════════ */
    var slides    = Array.from(document.querySelectorAll('.ml-slide'));
    var current   = 0;
    var elCurrent = document.getElementById('ml-slide-current');

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function nextSlide() {
        if (slides.length <= 1) return;
        slides[current].classList.remove('ml-slide--active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('ml-slide--active');
        if (elCurrent) elCurrent.textContent = pad(current + 1);
    }

    if (slides.length > 1) {
        window.setInterval(nextSlide, 5200);
    }

    /* ══ 2. PARTICLES on slideshow left panel ═══════════════ */
    var particles = document.getElementById('ml-particles');
    if (particles) {
        for (var i = 0; i < 22; i++) {
            var p        = document.createElement('span');
            var size     = Math.random() * 2.2 + 0.8;
            var delay    = Math.random() * -20;
            var duration = Math.random() * 12 + 10;
            p.className               = 'ml-particle';
            p.style.left              = (Math.random() * 100) + '%';
            p.style.top               = (Math.random() * 100) + '%';
            p.style.width             = size + 'px';
            p.style.height            = size + 'px';
            p.style.animationDelay    = delay + 's';
            p.style.animationDuration = duration + 's';
            p.style.opacity           = String(Math.random() * 0.5 + 0.15);
            particles.appendChild(p);
        }
    }

    /* ══ 3. SCI-FI GRID CANVAS ═════════════════════════════ */
    var canvas = document.getElementById('ml-grid-canvas');
    if (canvas) {
        var ctx  = canvas.getContext('2d');
        var W, H;
        var CELL   = 52;
        var offset = 0;

        function resize() {
            W = canvas.width  = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        function drawGrid() {
            ctx.clearRect(0, 0, W, H);
            var step = offset % CELL;

            ctx.strokeStyle = 'rgba(34,197,94,0.10)';
            ctx.lineWidth   = 0.5;

            for (var x = step; x < W; x += CELL) {
                ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke();
            }
            for (var y = H - step; y > 0; y -= CELL) {
                ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke();
            }

            /* Intersection dots */
            ctx.fillStyle = 'rgba(34,197,94,0.28)';
            for (var gx = step; gx < W; gx += CELL) {
                for (var gy = H - step; gy > 0; gy -= CELL) {
                    ctx.beginPath(); ctx.arc(gx, gy, 1.2, 0, Math.PI * 2); ctx.fill();
                }
            }

            /* Horizontal scan-beam */
            var beamY = (Date.now() / 20) % H;
            var grad  = ctx.createLinearGradient(0, beamY - 60, 0, beamY + 60);
            grad.addColorStop(0,   'rgba(34,197,94,0)');
            grad.addColorStop(0.5, 'rgba(34,197,94,0.055)');
            grad.addColorStop(1,   'rgba(34,197,94,0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, beamY - 60, W, 120);

            offset += 0.25;
        }

        (function loop() { drawGrid(); requestAnimationFrame(loop); })();
    }

    /* ══ 4. ROAMING BRIGHT ELEMENTS across full background ══
       Three types: floating dot, horizontal streak, vertical pillar
    ════════════════════════════════════════════════════════ */
    var roamerContainer = document.getElementById('ml-roamers');
    if (roamerContainer) {

        var VW = window.innerWidth;
        var VH = window.innerHeight;
        window.addEventListener('resize', function () {
            VW = window.innerWidth;
            VH = window.innerHeight;
        });

        /* Helper: random between a and b */
        function rnd(a, b) { return a + Math.random() * (b - a); }

        /* -- Floating glowing dots (13) -- */
        for (var d = 0; d < 13; d++) {
            var dot      = document.createElement('span');
            var dotSize  = rnd(3, 9);
            var dotDur   = rnd(8, 22);
            var dotDelay = rnd(-20, 0);
            /* random travel vector stored as CSS custom properties */
            var rx = (Math.random() < 0.5 ? -1 : 1) * rnd(60, 220);
            var ry = (Math.random() < 0.5 ? -1 : 1) * rnd(60, 180);

            dot.className = 'ml-roamer';
            dot.style.cssText = [
                'width:'             + dotSize + 'px',
                'height:'            + dotSize + 'px',
                'left:'              + rnd(0, 100) + '%',
                'top:'               + rnd(0, 100) + '%',
                'animation-duration:'+ dotDur + 's',
                'animation-delay:'   + dotDelay + 's',
                '--rx:'              + rx + 'px',
                '--ry:'              + ry + 'px',
            ].join(';');
            roamerContainer.appendChild(dot);
        }

        /* -- Horizontal shooting streaks (7) -- */
        for (var s = 0; s < 7; s++) {
            var streak     = document.createElement('span');
            var strkLen    = rnd(80, 240);
            var strkH      = rnd(1, 2.5);
            var strkDur    = rnd(6, 16);
            var strkDelay  = rnd(-16, 0);
            var strkTop    = rnd(5, 95);
            var strkDist   = VW + strkLen + 40;

            streak.className = 'ml-roamer ml-roamer--streak';
            streak.style.cssText = [
                'width:'              + strkLen + 'px',
                'height:'             + strkH + 'px',
                'top:'                + strkTop + '%',
                'left:0',
                'animation-duration:' + strkDur + 's',
                'animation-delay:'    + strkDelay + 's',
                '--streak-dist:'      + strkDist + 'px',
            ].join(';');
            roamerContainer.appendChild(streak);
        }

        /* -- Vertical pulse pillars (5) -- */
        for (var pil = 0; pil < 5; pil++) {
            var pillar    = document.createElement('span');
            var pilH      = rnd(60, 180);
            var pilW      = rnd(1, 2);
            var pilDur    = rnd(7, 18);
            var pilDelay  = rnd(-18, 0);
            var pilLeft   = rnd(5, 95);
            var pilDist   = VH + pilH + 40;

            pillar.className = 'ml-roamer ml-roamer--pillar';
            pillar.style.cssText = [
                'width:'              + pilW + 'px',
                'height:'             + pilH + 'px',
                'left:'               + pilLeft + '%',
                'top:0',
                'animation-duration:' + pilDur + 's',
                'animation-delay:'    + pilDelay + 's',
                '--pillar-dist:'      + pilDist + 'px',
            ].join(';');
            roamerContainer.appendChild(pillar);
        }
    }

    /* ══ 5. PASSWORD VISIBILITY TOGGLE ═════════════════════ */
    var toggleBtn = document.getElementById('ml-toggle-pw');
    var pwInput   = document.getElementById('password');
    var eyeIcon   = document.getElementById('ml-eye-icon');

    var eyeOpen   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>';
    var eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>';

    if (toggleBtn && pwInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            var isText = pwInput.type === 'text';
            pwInput.type      = isText ? 'password' : 'text';
            eyeIcon.innerHTML = isText ? eyeOpen : eyeClosed;
        });
    }

    /* ══ 6. LOGIN BUTTON — text slides left → right on submit
       Speed is deliberately slow (8s linear).
       - Fast server response  → page navigates away mid-animation (text in middle)
       - Slow server response  → text reaches right before navigation
       This gives a natural real-time loading feel.
    ════════════════════════════════════════════════════════ */
    var submitBtn   = document.getElementById('ml-submit-btn');
    var primaryInner = document.getElementById('ml-primary-inner');

    if (submitBtn && primaryInner) {
        submitBtn.addEventListener('click', function () {
            /* Measure available travel distance */
            var btnWidth   = submitBtn.offsetWidth;
            var innerWidth = primaryInner.offsetWidth;
            /* target left = btn right edge minus inner width minus same 20px margin */
            var targetLeft = btnWidth - innerWidth - 20;

            /* Apply slow linear transition — speed represents time remaining */
            primaryInner.style.transition = 'left 8s linear';
            primaryInner.style.left       = targetLeft + 'px';
        });
    }

})();