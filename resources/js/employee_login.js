/**
 * resources/js/user_login.js
 *
 * 1. Left panel: generative canvas — deep space cinema night
 *    • Star field with depth (parallax on mouse move)
 *    • Drifting neon orbs (purple/pink/cyan auras)
 *    • Scanline grid perspective lines
 *    • Film-reel particle bursts
 *
 * 2. Right panel: SVG snake border
 *    • A gradient dash races around the form rectangle
 *    • Speed and glow pulse smoothly
 */
(function () {
    'use strict';

    /* ================================================================
       UTILITY
    ================================================================ */
    function rand(min, max) { return Math.random() * (max - min) + min; }
    function randInt(min, max) { return Math.floor(rand(min, max + 1)); }

    /* ================================================================
       1. CANVAS ART — left panel
    ================================================================ */
    var canvas = document.getElementById('ul-canvas');
    if (!canvas) return;

    var ctx    = canvas.getContext('2d');
    var W, H;
    var mouse  = { x: 0, y: 0 };

    function resize() {
        W = canvas.width  = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    canvas.parentElement.addEventListener('mousemove', function (e) {
        var r = canvas.getBoundingClientRect();
        mouse.x = e.clientX - r.left;
        mouse.y = e.clientY - r.top;
    });

    /* ── Stars ────────────────────────────────────────────── */
    var STAR_COUNT = 260;
    var stars = [];

    for (var s = 0; s < STAR_COUNT; s++) {
        stars.push({
            x:      rand(0, 1),
            y:      rand(0, 1),
            r:      rand(0.3, 1.8),
            alpha:  rand(0.2, 0.9),
            speed:  rand(0.00005, 0.0002),
            depth:  rand(0.1, 1.0),   // parallax depth
            pulse:  rand(0, Math.PI * 2),
        });
    }

    /* ── Neon orbs ────────────────────────────────────────── */
    var ORBS = [
        { x: 0.18, y: 0.30, r: 200, hue: '168,85,247',  speed: 0.00012, angle: 0    },
        { x: 0.72, y: 0.20, r: 140, hue: '236,72,153',  speed: 0.00018, angle: 1.2  },
        { x: 0.42, y: 0.75, r: 170, hue: '34,211,238',  speed: 0.00010, angle: 2.5  },
        { x: 0.85, y: 0.65, r: 110, hue: '245,158,11',  speed: 0.00022, angle: 4.1  },
    ];

    /* ── Grid lines (perspective floor) ──────────────────── */
    var GRID_LINES = 14;

    /* ── Ticker ───────────────────────────────────────────── */
    var t = 0;

    function draw() {
        t++;

        ctx.clearRect(0, 0, W, H);

        /* Background */
        ctx.fillStyle = '#080810';
        ctx.fillRect(0, 0, W, H);

        /* Orbs */
        ORBS.forEach(function (o) {
            o.angle += o.speed * t * 0.01;
            // Slow drift
            var ox = (o.x + Math.cos(o.angle) * 0.08) * W;
            var oy = (o.y + Math.sin(o.angle) * 0.06) * H;
            var g  = ctx.createRadialGradient(ox, oy, 0, ox, oy, o.r);
            g.addColorStop(0,   'rgba(' + o.hue + ',0.22)');
            g.addColorStop(0.5, 'rgba(' + o.hue + ',0.06)');
            g.addColorStop(1,   'rgba(' + o.hue + ',0)');
            ctx.beginPath();
            ctx.arc(ox, oy, o.r, 0, Math.PI * 2);
            ctx.fillStyle = g;
            ctx.fill();
        });

        /* Perspective grid */
        ctx.save();
        var vanishX = W * 0.5 + (mouse.x - W * 0.5) * 0.04;
        var vanishY = H * 0.42;
        for (var gl = 0; gl <= GRID_LINES; gl++) {
            var frac = gl / GRID_LINES;
            var baseX = frac * W;
            ctx.beginPath();
            ctx.moveTo(vanishX, vanishY);
            ctx.lineTo(baseX, H);
            var opacity = 0.04 + frac * (1 - frac) * 0.10;
            ctx.strokeStyle = 'rgba(168,85,247,' + opacity + ')';
            ctx.lineWidth   = 0.8;
            ctx.stroke();
        }
        // Horizontal grid lines
        for (var hl = 1; hl <= 8; hl++) {
            var ty  = vanishY + (H - vanishY) * Math.pow(hl / 8, 1.6);
            var xL  = vanishX + (0     - vanishX) * ((ty - vanishY) / (H - vanishY));
            var xR  = vanishX + (W     - vanishX) * ((ty - vanishY) / (H - vanishY));
            ctx.beginPath();
            ctx.moveTo(xL, ty);
            ctx.lineTo(xR, ty);
            ctx.strokeStyle = 'rgba(124,58,237,' + (0.03 + (hl / 8) * 0.08) + ')';
            ctx.lineWidth   = 0.7;
            ctx.stroke();
        }
        ctx.restore();

        /* Stars */
        var px = (mouse.x / W - 0.5);
        var py = (mouse.y / H - 0.5);

        stars.forEach(function (star) {
            star.pulse += 0.012;
            var pulse = (Math.sin(star.pulse) * 0.3 + 0.7);
            // Parallax offset
            var sx = (star.x + px * star.depth * 0.04) * W;
            var sy = (star.y + py * star.depth * 0.03) * H;
            ctx.beginPath();
            ctx.arc(sx, sy, star.r * pulse, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(240,238,255,' + (star.alpha * pulse) + ')';
            ctx.fill();
        });

        /* Scanline overlay (very subtle) */
        for (var sl = 0; sl < H; sl += 4) {
            ctx.fillStyle = 'rgba(0,0,0,0.06)';
            ctx.fillRect(0, sl, W, 1);
        }

        /* Vignette */
        var vig = ctx.createRadialGradient(W * 0.5, H * 0.5, H * 0.2, W * 0.5, H * 0.5, H * 0.85);
        vig.addColorStop(0,   'rgba(8,8,16,0)');
        vig.addColorStop(1,   'rgba(8,8,16,0.72)');
        ctx.fillStyle = vig;
        ctx.fillRect(0, 0, W, H);

        requestAnimationFrame(draw);
    }

    draw();

    /* ================================================================
       2. SNAKE BORDER — SVG gradient dash animation
    ================================================================ */
    var svgEl   = document.getElementById('ul-snake-svg');
    var rectEl  = document.getElementById('ul-snake-path');

    if (!svgEl || !rectEl) return;

    // Inject gradient def into SVG
    var defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    defs.innerHTML =
        '<linearGradient id="ul-snake-gradient" x1="0%" y1="0%" x2="100%" y2="100%">' +
            '<stop offset="0%"   stop-color="#a855f7"/>' +
            '<stop offset="50%"  stop-color="#ec4899"/>' +
            '<stop offset="100%" stop-color="#22d3ee"/>' +
        '</linearGradient>';
    svgEl.prepend(defs);

    // We need the perimeter. Measure it after a frame so layout is settled.
    function initSnake() {
        var perimeter = rectEl.getTotalLength
            ? rectEl.getTotalLength()
            : 2 * (svgEl.clientWidth + svgEl.clientHeight);

        if (!perimeter || perimeter < 10) {
            // Fallback: compute from bounding box
            var bb = svgEl.getBoundingClientRect();
            perimeter = 2 * (bb.width + bb.height);
        }

        var SNAKE_LEN  = perimeter * 0.28;   // 28% of border lights up
        var GAP        = perimeter - SNAKE_LEN;
        var offset     = 0;
        var speed      = perimeter / 220;    // full loop in ~220 frames @ 60fps

        rectEl.setAttribute('stroke-dasharray',  SNAKE_LEN + ' ' + GAP);

        function animateSnake() {
            offset = (offset - speed + perimeter) % perimeter;
            rectEl.setAttribute('stroke-dashoffset', offset);

            // Pulse glow
            var glow = 6 + Math.sin(Date.now() / 600) * 3;
            rectEl.style.filter = 'drop-shadow(0 0 ' + glow + 'px rgba(168,85,247,0.7)) ' +
                                  'drop-shadow(0 0 ' + (glow * 2) + 'px rgba(236,72,153,0.4))';
            requestAnimationFrame(animateSnake);
        }

        animateSnake();
    }

    // Wait one frame for SVG layout
    requestAnimationFrame(function () {
        requestAnimationFrame(initSnake);
    });

    /* ================================================================
       INPUT FOCUS MICRO-INTERACTION
       The orb nearest the input briefly brightens when focused.
    ================================================================ */
    document.querySelectorAll('.ul-input').forEach(function (input) {
        input.addEventListener('focus', function () {
            // Temporarily increase orb intensity — done via the canvas ORBS array
            if (ORBS[0]) ORBS[0].r = 260;
        });
        input.addEventListener('blur', function () {
            if (ORBS[0]) ORBS[0].r = 200;
        });
    });

})();