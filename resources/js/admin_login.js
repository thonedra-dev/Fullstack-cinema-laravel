document.addEventListener('DOMContentLoaded', () => {

    // ===== PASSWORD TOGGLE (UNCHANGED) =====
    const pwInput  = document.getElementById('password');
    const togglePw = document.getElementById('toggle-pw');
    const eyeIcon  = document.getElementById('eye-icon');
    if (togglePw && pwInput) {
        togglePw.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type   = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    }

    // ===== BUTTON RIPPLE (UNCHANGED) =====
    const btn    = document.getElementById('login-btn');
    const ripple = btn?.querySelector('.login-btn__ripple');
    btn?.addEventListener('click', (e) => {
        if (!ripple) return;
        const rect = btn.getBoundingClientRect();
        ripple.style.left   = (e.clientX - rect.left)  + 'px';
        ripple.style.top    = (e.clientY - rect.top)   + 'px';
        ripple.style.width  = '200px';
        ripple.style.height = '200px';
        ripple.style.opacity = '1';
        setTimeout(() => {
            ripple.style.width   = '0';
            ripple.style.height  = '0';
            ripple.style.opacity = '0';
        }, 500);
    });

    // =========================================================
    //  ASTRONAUT NIGHT-LIFE CANVAS
    // =========================================================
    const canvas = document.getElementById('orb-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let W, H, t = 0;

    function resize() {
        W = canvas.width  = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', () => { resize(); buildScene(); });

    // ---------------------------------------------------------
    //  BACKGROUND
    // ---------------------------------------------------------
    function drawBackground() {
        const bg = ctx.createLinearGradient(0, 0, W * 0.4, H);
        bg.addColorStop(0,   '#06040f');
        bg.addColorStop(0.4, '#0c0818');
        bg.addColorStop(0.8, '#0e0612');
        bg.addColorStop(1,   '#050310');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, W, H);
    }

    // ---------------------------------------------------------
    //  NEBULA WASH (static soft clouds)
    // ---------------------------------------------------------
    const nebulaSeeds = [
        { rx: 0.25, ry: 0.30, rr: 0, rg: 10, rb: 90,  a: 0.11 },
        { rx: 0.70, ry: 0.60, rr: 55, rg: 0, rb: 100, a: 0.09 },
        { rx: 0.45, ry: 0.82, rr: 0,  rg: 50, rb: 80,  a: 0.08 },
        { rx: 0.85, ry: 0.20, rr: 80, rg: 20, rb: 80,  a: 0.07 },
    ];

    function drawNebulae() {
        nebulaSeeds.forEach((n, i) => {
            const pulse = 0.93 + 0.07 * Math.sin(t * 0.09 + i * 1.7);
            ctx.save();
            ctx.translate(n.rx * W, n.ry * H);
            ctx.scale(pulse, pulse * 0.55);
            const size = Math.min(W, H) * 0.55;
            const g = ctx.createRadialGradient(0, 0, 0, 0, 0, size);
            g.addColorStop(0,   `rgba(${n.rr},${n.rg},${n.rb+30},${n.a})`);
            g.addColorStop(0.5, `rgba(${n.rr},${n.rg},${n.rb},${n.a * 0.45})`);
            g.addColorStop(1,   'rgba(0,0,0,0)');
            ctx.beginPath();
            ctx.ellipse(0, 0, size, size * 0.6, 0, 0, Math.PI * 2);
            ctx.fillStyle = g;
            ctx.fill();
            ctx.restore();
        });
    }

    // ---------------------------------------------------------
    //  STAR FIELD — two layers (distant + nearby)
    // ---------------------------------------------------------
    let starsDeep = [], starsNear = [];

    function buildStars() {
        starsDeep = Array.from({ length: 160 }, () => ({
            x: Math.random(), y: Math.random(),
            r: Math.random() * 0.9 + 0.2,
            a: Math.random() * 0.45 + 0.10,
            fl: Math.random() * Math.PI * 2,
            fs: Math.random() * 0.005 + 0.002,
            col: Math.random() < 0.12 ? [180, 200, 255] : Math.random() < 0.08 ? [255, 220, 160] : [230, 235, 255]
        }));
        starsNear = Array.from({ length: 35 }, () => ({
            x: Math.random(), y: Math.random(),
            r: Math.random() * 1.6 + 0.8,
            a: Math.random() * 0.55 + 0.25,
            fl: Math.random() * Math.PI * 2,
            fs: Math.random() * 0.012 + 0.005,
            col: Math.random() < 0.2 ? [200, 220, 255] : [255, 245, 220]
        }));
    }
    buildStars();

    function drawStars() {
        [...starsDeep, ...starsNear].forEach(s => {
            const f = 0.5 + 0.5 * Math.sin(t * s.fs * 60 + s.fl);
            ctx.globalAlpha = s.a * f;
            ctx.fillStyle = `rgb(${s.col[0]},${s.col[1]},${s.col[2]})`;
            ctx.beginPath();
            ctx.arc(s.x * W, s.y * H, s.r, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1;
    }

    // ---------------------------------------------------------
    //  SHOOTING STARS
    // ---------------------------------------------------------
    let shoots = [];

    function spawnShoot() {
        shoots.push({
            x:    Math.random() * W * 0.85,
            y:    Math.random() * H * 0.5,
            len:  Math.random() * 90 + 55,
            angle: Math.PI * 0.16 + Math.random() * 0.18,
            life: 0,
            maxLife: Math.random() * 0.65 + 0.35,
            spd:  Math.random() * 200 + 170,
            width: Math.random() * 1.2 + 0.8
        });
    }

    function updateShoots(dt) {
        if (Math.random() < dt * 0.55) spawnShoot();
        shoots = shoots.filter(s => {
            s.life += dt;
            const p = s.life / s.maxLife;
            if (p >= 1) return false;
            const fade = p < 0.35 ? p / 0.35 : 1 - ((p - 0.35) / 0.65);
            const tx = s.x + Math.cos(s.angle) * s.spd * s.life;
            const ty = s.y + Math.sin(s.angle) * s.spd * s.life;
            const tail = Math.min(p * 4, 1);
            const hx = tx - Math.cos(s.angle) * s.len * tail;
            const hy = ty - Math.sin(s.angle) * s.len * tail;
            const g = ctx.createLinearGradient(hx, hy, tx, ty);
            g.addColorStop(0,   'rgba(200,215,255,0)');
            g.addColorStop(0.6, `rgba(220,235,255,${0.4 * fade})`);
            g.addColorStop(1,   `rgba(255,250,240,${0.95 * fade})`);
            ctx.beginPath();
            ctx.moveTo(hx, hy);
            ctx.lineTo(tx, ty);
            ctx.strokeStyle = g;
            ctx.lineWidth = s.width;
            ctx.stroke();
            return true;
        });
    }

    // ---------------------------------------------------------
    //  AURORA SWEEP (two overlapping wide cones)
    // ---------------------------------------------------------
    function drawAurora() {
        const sweeps = [
            { ox: W, oy: H * 0.04, phase: 0,    spread: 0.26, cr: 'rgba(130,60,255,',  peak: 0.09 },
            { ox: W, oy: H * 0.08, phase: 1.3,  spread: 0.22, cr: 'rgba(255,200,80,',  peak: 0.10 },
            { ox: W, oy: H * 0.02, phase: 2.8,  spread: 0.18, cr: 'rgba(50,180,200,',  peak: 0.06 },
        ];
        sweeps.forEach(sw => {
            const cy = H * 0.5 + Math.sin(t * 0.28 + sw.phase) * H * 0.13;
            const sp = sw.spread * H;
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(sw.ox, sw.oy);
            ctx.lineTo(0, cy - sp);
            ctx.lineTo(0, cy + sp);
            ctx.closePath();
            const g = ctx.createLinearGradient(sw.ox, sw.oy, 0, cy);
            g.addColorStop(0,    sw.cr + (sw.peak * 1.4) + ')');
            g.addColorStop(0.35, sw.cr + (sw.peak * 0.9) + ')');
            g.addColorStop(0.7,  sw.cr + (sw.peak * 0.35) + ')');
            g.addColorStop(1,    sw.cr + '0)');
            ctx.fillStyle = g;
            ctx.fill();

            // lens flare dot at origin
            const flare = ctx.createRadialGradient(sw.ox, sw.oy, 0, sw.ox, sw.oy, 45);
            flare.addColorStop(0,   sw.cr + '0.55)');
            flare.addColorStop(0.5, sw.cr + '0.12)');
            flare.addColorStop(1,   sw.cr + '0)');
            ctx.beginPath();
            ctx.arc(sw.ox, sw.oy, 45, 0, Math.PI * 2);
            ctx.fillStyle = flare;
            ctx.fill();
            ctx.restore();
        });
    }

    // ---------------------------------------------------------
    //  ORBITING PLANETS / MOONS (small, varied, visible)
    // ---------------------------------------------------------
    class Orb {
        constructor(cfg) {
            this.cx     = cfg.cx;   // center x (0-1)
            this.cy     = cfg.cy;
            this.orbitW = cfg.orbitW;
            this.orbitH = cfg.orbitH;
            this.r      = cfg.r;
            this.phase  = cfg.phase;
            this.speed  = cfg.speed;
            this.col1   = cfg.col1;
            this.col2   = cfg.col2;
            this.tilt   = cfg.tilt || 0;
            this.hasMoon = cfg.hasMoon || false;
            this.moonOffset = cfg.moonOffset || 0;
            this.ring   = cfg.ring || false;
        }
        draw() {
            const angle = t * this.speed + this.phase;
            const px = this.cx * W + Math.cos(angle) * this.orbitW * W;
            const py = this.cy * H + Math.sin(angle) * this.orbitH * H;
            const r  = this.r;

            // Orbit path (faint ellipse)
            ctx.save();
            ctx.translate(this.cx * W, this.cy * H);
            ctx.rotate(this.tilt);
            ctx.beginPath();
            ctx.ellipse(0, 0, this.orbitW * W, this.orbitH * H, 0, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(180,180,255,0.06)';
            ctx.lineWidth = 0.7;
            ctx.stroke();
            ctx.restore();

            // Ring (Saturn-like) — drawn before planet so planet overlaps
            if (this.ring) {
                ctx.save();
                ctx.translate(px, py);
                ctx.rotate(0.5);
                ctx.scale(1, 0.28);
                ctx.beginPath();
                ctx.arc(0, 0, r * 2.1, 0, Math.PI * 2);
                ctx.strokeStyle = `rgba(${this.col1[0]},${this.col1[1]},${this.col1[2]},0.35)`;
                ctx.lineWidth = r * 0.55;
                ctx.stroke();
                ctx.restore();
            }

            // Body glow
            const glow = ctx.createRadialGradient(px, py, 0, px, py, r * 2.2);
            glow.addColorStop(0, `rgba(${this.col1[0]},${this.col1[1]},${this.col1[2]},0.22)`);
            glow.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.beginPath();
            ctx.arc(px, py, r * 2.2, 0, Math.PI * 2);
            ctx.fillStyle = glow;
            ctx.fill();

            // Planet body
            const body = ctx.createRadialGradient(px - r * 0.3, py - r * 0.3, r * 0.1, px, py, r);
            body.addColorStop(0,   `rgb(${this.col2[0]},${this.col2[1]},${this.col2[2]})`);
            body.addColorStop(0.6, `rgb(${this.col1[0]},${this.col1[1]},${this.col1[2]})`);
            body.addColorStop(1,   `rgba(${Math.max(0,this.col1[0]-40)},${Math.max(0,this.col1[1]-40)},${Math.max(0,this.col1[2]-40)},0.9)`);
            ctx.beginPath();
            ctx.arc(px, py, r, 0, Math.PI * 2);
            ctx.fillStyle = body;
            ctx.fill();

            // Tiny specular shine
            ctx.beginPath();
            ctx.arc(px - r * 0.32, py - r * 0.32, r * 0.28, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,0.18)';
            ctx.fill();

            // Moon
            if (this.hasMoon) {
                const ma = t * this.speed * 3.5 + this.moonOffset;
                const mr = r * 0.28;
                const mx = px + Math.cos(ma) * r * 2.2;
                const my = py + Math.sin(ma) * r * 2.2 * 0.4;
                ctx.beginPath();
                ctx.arc(mx, my, mr, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(200,210,235,0.75)';
                ctx.fill();
            }
        }
    }

    // ---------------------------------------------------------
    //  SATELLITE — small blinking craft
    // ---------------------------------------------------------
    class Satellite {
        constructor(cfg) {
            this.cx    = cfg.cx;
            this.cy    = cfg.cy;
            this.orbitW = cfg.orbitW;
            this.orbitH = cfg.orbitH;
            this.phase = cfg.phase;
            this.speed = cfg.speed;
            this.tilt  = cfg.tilt || 0;
        }
        draw() {
            const angle = t * this.speed + this.phase;
            const px = this.cx * W + Math.cos(angle) * this.orbitW * W;
            const py = this.cy * H + Math.sin(angle) * this.orbitH * H;

            ctx.save();
            ctx.translate(px, py);
            ctx.rotate(angle + Math.PI * 0.25);

            // Body
            ctx.fillStyle = 'rgba(210,220,240,0.85)';
            ctx.fillRect(-4, -2.5, 8, 5);

            // Solar panels
            ctx.fillStyle = 'rgba(80,140,220,0.8)';
            ctx.fillRect(-11, -1.8, 6, 3.6);
            ctx.fillRect(5,   -1.8, 6, 3.6);

            // Blink light
            const blink = Math.sin(t * 4.5 + this.phase * 3) > 0.6 ? 1 : 0;
            if (blink) {
                ctx.beginPath();
                ctx.arc(0, 0, 2, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255,100,100,0.95)';
                ctx.fill();
            }
            ctx.restore();
        }
    }

    // ---------------------------------------------------------
    //  COMET — slow drifting icy comet with dust tail
    // ---------------------------------------------------------
    class Comet {
        constructor(cfg) {
            this.reset(cfg.startX, cfg.startY, cfg.angle, cfg.speed);
            this.tailLen = cfg.tailLen || 80;
        }
        reset(x, y, angle, speed) {
            this.x     = x  !== undefined ? x * W : Math.random() * W;
            this.y     = y  !== undefined ? y * H : -30;
            this.angle = angle !== undefined ? angle : Math.PI * 0.35 + Math.random() * 0.4;
            this.speed = speed !== undefined ? speed : 15 + Math.random() * 20;
            this.r     = Math.random() * 2.5 + 2;
        }
        update(dt) {
            this.x += Math.cos(this.angle) * this.speed * dt;
            this.y += Math.sin(this.angle) * this.speed * dt;
            if (this.x > W + 120 || this.y > H + 120) {
                this.reset(Math.random() * 0.6, -0.05, undefined, this.speed);
            }
        }
        draw() {
            const { x, y, angle, r, tailLen } = this;
            // Dust tail
            for (let i = 0; i < 5; i++) {
                const li = (i + 1) / 5;
                const tx = x - Math.cos(angle) * tailLen * li;
                const ty = y - Math.sin(angle) * tailLen * li;
                ctx.beginPath();
                ctx.arc(tx + (Math.random() - 0.5) * 4, ty + (Math.random() - 0.5) * 4,
                         r * (1 - li * 0.7) + 0.5, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(200,230,255,${0.35 * (1 - li)})`;
                ctx.fill();
            }
            // Ion tail (line)
            const g = ctx.createLinearGradient(
                x - Math.cos(angle) * tailLen, y - Math.sin(angle) * tailLen, x, y
            );
            g.addColorStop(0, 'rgba(120,200,255,0)');
            g.addColorStop(1, 'rgba(180,230,255,0.5)');
            ctx.beginPath();
            ctx.moveTo(x - Math.cos(angle) * tailLen, y - Math.sin(angle) * tailLen);
            ctx.lineTo(x, y);
            ctx.strokeStyle = g;
            ctx.lineWidth = 1.5;
            ctx.stroke();
            // Head
            const headG = ctx.createRadialGradient(x, y, 0, x, y, r * 1.8);
            headG.addColorStop(0, 'rgba(240,255,255,0.95)');
            headG.addColorStop(0.5, 'rgba(150,230,255,0.5)');
            headG.addColorStop(1, 'rgba(100,200,255,0)');
            ctx.beginPath();
            ctx.arc(x, y, r * 1.8, 0, Math.PI * 2);
            ctx.fillStyle = headG;
            ctx.fill();
        }
    }

    // ---------------------------------------------------------
    //  FLOATING ASTRONAUT (simple silhouette paths)
    // ---------------------------------------------------------
    class Astronaut {
        constructor(cfg) {
            this.cx    = cfg.cx;
            this.cy    = cfg.cy;
            this.speed = cfg.speed;
            this.phase = cfg.phase;
            this.scale = cfg.scale || 1;
            this.drift = cfg.drift || 0.012;
        }
        draw() {
            const bobY  = Math.sin(t * this.drift * 60 + this.phase) * H * 0.018;
            const bobRot = Math.sin(t * this.drift * 40 + this.phase) * 0.08;
            const cx = (this.cx + Math.sin(t * this.speed + this.phase) * 0.04) * W;
            const cy = this.cy * H + bobY;
            const s  = this.scale * Math.min(W, H) * 0.028;

            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(bobRot);
            ctx.scale(s, s);

            // Suit body
            ctx.beginPath();
            ctx.ellipse(0, 1.2, 1.0, 1.3, 0, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(215,225,240,0.88)';
            ctx.fill();

            // Helmet
            ctx.beginPath();
            ctx.arc(0, -1.0, 1.0, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(200,215,240,0.9)';
            ctx.fill();

            // Visor
            ctx.beginPath();
            ctx.arc(0.1, -1.05, 0.55, Math.PI * 0.15, Math.PI * 0.85);
            ctx.fillStyle = 'rgba(255,190,60,0.75)';
            ctx.fill();

            // Left arm
            ctx.save();
            ctx.rotate(-0.4 + Math.sin(t * this.drift * 55 + this.phase) * 0.2);
            ctx.beginPath();
            ctx.ellipse(-1.15, 0.5, 0.38, 0.9, 0.3, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(200,215,235,0.85)';
            ctx.fill();
            ctx.restore();

            // Right arm
            ctx.save();
            ctx.rotate(0.4 - Math.sin(t * this.drift * 55 + this.phase + 0.5) * 0.2);
            ctx.beginPath();
            ctx.ellipse(1.15, 0.5, 0.38, 0.9, -0.3, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(200,215,235,0.85)';
            ctx.fill();
            ctx.restore();

            // Legs
            ctx.beginPath();
            ctx.ellipse(-0.45, 2.6, 0.38, 0.85, 0.1, 0, Math.PI * 2);
            ctx.ellipse(0.45,  2.6, 0.38, 0.85, -0.1, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(200,215,235,0.82)';
            ctx.fill();

            // Suit highlight
            ctx.beginPath();
            ctx.arc(-0.3, 0.6, 0.22, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,0.18)';
            ctx.fill();

            ctx.restore();

            // Tether line
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            const tx = (this.cx + 0.22) * W;
            const ty = (this.cy + 0.06) * H;
            const mid1x = cx + (tx - cx) * 0.3 + Math.sin(t * 0.6 + this.phase) * 8;
            const mid1y = cy + (ty - cy) * 0.5 + Math.cos(t * 0.5 + this.phase) * 6;
            ctx.quadraticCurveTo(mid1x, mid1y, tx, ty);
            ctx.strokeStyle = 'rgba(200,215,255,0.25)';
            ctx.lineWidth = 0.8;
            ctx.stroke();
        }
    }

    // ---------------------------------------------------------
    //  DUST MOTES
    // ---------------------------------------------------------
    let particles = [];
    function buildParticles() {
        const cols = [[255,245,200],[180,200,255],[255,200,150],[180,255,220],[255,180,220]];
        particles = Array.from({ length: 65 }, () => {
            const c = cols[Math.floor(Math.random() * cols.length)];
            return {
                x: Math.random() * W, y: Math.random() * H,
                r: Math.random() * 2.5 + 0.5,
                a: Math.random() * 0.55 + 0.2,
                dx: (Math.random() - 0.5) * 0.2,
                dy: -(Math.random() * 0.16 + 0.04),
                fl: Math.random() * Math.PI * 2,
                fs: Math.random() * 0.016 + 0.007,
                cr: c[0], cg: c[1], cb: c[2]
            };
        });
    }
    buildParticles();

    function updateAndDrawParticles() {
        particles.forEach(p => {
            p.x += p.dx; p.y += p.dy;
            if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
            if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
            const f = 0.4 + 0.6 * Math.sin(t * p.fs * 60 + p.fl);
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${p.cr},${p.cg},${p.cb},${p.a * f * 0.5})`;
            ctx.fill();
        });
    }

    // ---------------------------------------------------------
    //  VIGNETTE
    // ---------------------------------------------------------
    function drawVignette() {
        const g = ctx.createRadialGradient(W/2, H/2, H * 0.25, W/2, H/2, H * 1.0);
        g.addColorStop(0,    'rgba(0,0,0,0)');
        g.addColorStop(0.65, 'rgba(4,2,16,0.52)');
        g.addColorStop(1,    'rgba(2,1,10,0.90)');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, W, H);

        const lw = W * 0.09;
        const lg = ctx.createLinearGradient(0, 0, lw, 0);
        lg.addColorStop(0, 'rgba(30,5,55,0.70)');
        lg.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = lg; ctx.fillRect(0, 0, lw, H);

        const rg = ctx.createLinearGradient(W, 0, W - lw, 0);
        rg.addColorStop(0, 'rgba(30,5,55,0.70)');
        rg.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = rg; ctx.fillRect(W - lw, 0, lw, H);
    }

    // ---------------------------------------------------------
    //  SCENE INSTANCES
    // ---------------------------------------------------------
    let orbs, satellites, comets, astronauts;

    function buildScene() {
        orbs = [
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.30, orbitH: 0.20, r: 10, phase: 0,    speed: 0.18, col1: [90,60,180],  col2: [140,100,230], hasMoon: true,  moonOffset: 1.2 }),
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.20, orbitH: 0.28, r: 7,  phase: 2.1,  speed: 0.27, col1: [40,120,200], col2: [100,180,240], hasMoon: false }),
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.40, orbitH: 0.30, r: 13, phase: 3.8,  speed: 0.12, col1: [180,80,40],  col2: [240,140,80],  ring: true }),
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.12, orbitH: 0.16, r: 5,  phase: 5.2,  speed: 0.42, col1: [40,160,120], col2: [100,220,170] }),
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.48, orbitH: 0.18, r: 8,  phase: 1.0,  speed: 0.09, col1: [160,50,130], col2: [220,110,190], hasMoon: true,  moonOffset: 3.4, tilt: 0.3 }),
            new Orb({ cx: 0.5,  cy: 0.5, orbitW: 0.35, orbitH: 0.38, r: 6,  phase: 4.5,  speed: 0.22, col1: [60,160,200], col2: [120,220,240], tilt: -0.2 }),
        ];

        satellites = [
            new Satellite({ cx: 0.5, cy: 0.5, orbitW: 0.22, orbitH: 0.32, phase: 0.8,  speed: 0.55, tilt: 0.4 }),
            new Satellite({ cx: 0.5, cy: 0.5, orbitW: 0.38, orbitH: 0.22, phase: 3.5,  speed: 0.38 }),
            new Satellite({ cx: 0.5, cy: 0.5, orbitW: 0.44, orbitH: 0.34, phase: 1.9,  speed: 0.28, tilt: -0.3 }),
        ];

        comets = [
            new Comet({ startX: 0.05, startY: 0.0, angle: Math.PI * 0.3,  speed: 18, tailLen: 90 }),
            new Comet({ startX: 0.40, startY: 0.0, angle: Math.PI * 0.28, speed: 22, tailLen: 70 }),
        ];

        astronauts = [
            new Astronaut({ cx: 0.25, cy: 0.38, speed: 0.06, phase: 0,   scale: 0.9,  drift: 0.010 }),
            new Astronaut({ cx: 0.72, cy: 0.62, speed: 0.05, phase: 2.4, scale: 0.75, drift: 0.013 }),
            new Astronaut({ cx: 0.55, cy: 0.22, speed: 0.07, phase: 4.8, scale: 0.65, drift: 0.011 }),
        ];

        buildParticles();
        buildStars();
    }
    buildScene();

    // ---------------------------------------------------------
    //  LOOP
    // ---------------------------------------------------------
    let lastTime = 0;
    function loop(now) {
        const dt = Math.min((now - lastTime) / 1000, 0.05);
        lastTime = now;
        t += dt;

        if (!W || !H) { requestAnimationFrame(loop); return; }

        drawBackground();
        drawNebulae();
        drawStars();
        drawAurora();

        comets.forEach(c => { c.update(dt); c.draw(); });
        orbs.forEach(o => o.draw());
        satellites.forEach(s => s.draw());
        astronauts.forEach(a => a.draw());
        updateAndDrawParticles();
        updateShoots(dt);
        drawVignette();

        requestAnimationFrame(loop);
    }
    requestAnimationFrame(loop);

}); // end DOMContentLoaded