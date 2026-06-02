document.addEventListener('DOMContentLoaded', () => {

    // ===== PASSWORD TOGGLE =====
    const pwInput = document.getElementById('password');
    const togglePw = document.getElementById('toggle-pw');
    const eyeIcon = document.getElementById('eye-icon');
    if (togglePw && pwInput) {
        togglePw.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            eyeIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    }

    // ===== BUTTON RIPPLE =====
    const btn = document.getElementById('login-btn');
    const ripple = btn?.querySelector('.login-btn__ripple');
    btn?.addEventListener('click', (e) => {
        if (!ripple) return;
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.style.width = '200px';
        ripple.style.height = '200px';
        ripple.style.opacity = '1';
        setTimeout(() => {
            ripple.style.width = '0';
            ripple.style.height = '0';
            ripple.style.opacity = '0';
        }, 500);
    });

    // ===== CANVAS SETUP =====
    const canvas = document.getElementById('orb-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let W, H;

    function resize() {
        W = canvas.width = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // ===== BIOLUMINESCENT BUBBLE CLASS =====
    class Bubble {
        constructor() {
            this.r = Math.random() * 70 + 35;   // 35–105px
            this.x = Math.random() * (W - 2 * this.r) + this.r;
            this.y = Math.random() * (H - 2 * this.r) + this.r;
            // Constant speed (no decay) – between 0.3 and 1.0
            this.vx = (Math.random() - 0.5) * 1.2;
            this.vy = (Math.random() - 0.5) * 1.2;
            // Floating drift (sine wave) - each bubble has own phase
            this.floatPhase = Math.random() * Math.PI * 2;
            this.floatSpeed = 0.002 + Math.random() * 0.003;
            this.floatAmp = 0.8; // pixels per frame max
            // Color: alternating between cyan glow and electric purple
            const useCyan = Math.random() > 0.5;
            this.glowColor = useCyan ? { r: 0, g: 242, b: 254 } : { r: 168, g: 85, b: 247 };
            this.boostTimer = 0;
        }

        draw() {
            // Glassmorphism effect: radial gradient (transparent center → slightly tinted edge)
            const grad = ctx.createRadialGradient(
                this.x - this.r * 0.2,
                this.y - this.r * 0.2,
                this.r * 0.1,
                this.x,
                this.y,
                this.r
            );
            grad.addColorStop(0, 'rgba(255, 255, 255, 0.02)');   // almost invisible center
            grad.addColorStop(0.5, `rgba(${this.glowColor.r}, ${this.glowColor.g}, ${this.glowColor.b}, 0.05)`);
            grad.addColorStop(1, `rgba(${this.glowColor.r}, ${this.glowColor.g}, ${this.glowColor.b}, 0.02)`);

            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.fillStyle = grad;
            ctx.fill();

            // Thin bioluminescent border (1px stroke)
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
            ctx.strokeStyle = `rgba(${this.glowColor.r}, ${this.glowColor.g}, ${this.glowColor.b}, 0.35)`;
            ctx.lineWidth = 1.2;
            ctx.stroke();

            // Outer glow (soft shadow around the bubble)
            ctx.shadowColor = `rgba(${this.glowColor.r}, ${this.glowColor.g}, ${this.glowColor.b}, 0.4)`;
            ctx.shadowBlur = 12;
            ctx.stroke(); // re-stroke to apply shadow on the border
            ctx.shadowBlur = 0; // reset for other drawings

            // Inner highlight (small bright spot)
            ctx.beginPath();
            ctx.arc(this.x - this.r * 0.25, this.y - this.r * 0.25, this.r * 0.12, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, 0.2)`;
            ctx.fill();
        }

        update() {
            // Apply boost if active
            let speedX = this.vx;
            let speedY = this.vy;
            if (this.boostTimer > 0) {
                speedX *= 2.2;
                speedY *= 2.2;
                this.boostTimer--;
            }

            // Add floating sine‑wave motion (gentle up/down drift)
            const floatY = Math.sin(Date.now() * this.floatSpeed + this.floatPhase) * this.floatAmp;
            this.x += speedX;
            this.y += speedY + floatY * 0.1;  // subtle vertical drift

            // Bounce with corner boost
            let bounced = false;
            const margin = this.r;
            if (this.x - margin < 0) {
                this.x = margin;
                this.vx = Math.abs(this.vx);
                bounced = true;
            }
            if (this.x + margin > W) {
                this.x = W - margin;
                this.vx = -Math.abs(this.vx);
                bounced = true;
            }
            if (this.y - margin < 0) {
                this.y = margin;
                this.vy = Math.abs(this.vy);
                bounced = true;
            }
            if (this.y + margin > H) {
                this.y = H - margin;
                this.vy = -Math.abs(this.vy);
                bounced = true;
            }

            if (bounced) {
                this.boostTimer = 40;   // speed boost for ~0.7 sec
                // Slight random direction change for organic feel
                this.vx += (Math.random() - 0.5) * 0.3;
                this.vy += (Math.random() - 0.5) * 0.3;
                // Clamp speed to avoid hyperspeed
                const maxSpeed = 2.2;
                this.vx = Math.min(maxSpeed, Math.max(-maxSpeed, this.vx));
                this.vy = Math.min(maxSpeed, Math.max(-maxSpeed, this.vy));
            }

            // **FIX: No velocity decay!** Bubbles keep moving forever.
            // But if speed becomes extremely low (almost zero), give a tiny nudge.
            const speed = Math.hypot(this.vx, this.vy);
            if (speed < 0.08 && this.boostTimer === 0) {
                this.vx += (Math.random() - 0.5) * 0.2;
                this.vy += (Math.random() - 0.5) * 0.2;
            }
        }
    }

    // Create 9-12 bubbles
    const BUBBLE_COUNT = 11;
    let bubbles = [];

    function initBubbles() {
        bubbles = [];
        for (let i = 0; i < BUBBLE_COUNT; i++) {
            bubbles.push(new Bubble());
        }
    }
    initBubbles();

    // Optional: floating particles (tiny bioluminescent dust)
    const PARTICLE_COUNT = 60;
    const particles = Array.from({ length: PARTICLE_COUNT }, () => ({
        x: Math.random(),
        y: Math.random(),
        r: Math.random() * 1.8 + 0.6,
        vx: (Math.random() - 0.5) * 0.04,
        vy: (Math.random() - 0.5) * 0.04,
        alpha: Math.random() * 0.3 + 0.1,
        life: Math.random() * 0.8 + 0.2,
        decay: Math.random() * 0.002 + 0.001,
    }));

    function resetParticle(p) {
        p.x = Math.random();
        p.y = Math.random();
        p.life = 1;
        p.alpha = Math.random() * 0.3 + 0.1;
        p.vx = (Math.random() - 0.5) * 0.05;
        p.vy = (Math.random() - 0.5) * 0.05;
    }

    // ===== ANIMATION LOOP =====
    function draw() {
        if (!ctx || W === 0 || H === 0) {
            requestAnimationFrame(draw);
            return;
        }
        ctx.clearRect(0, 0, W, H);

        // Very subtle grid lines (deep sea sonar effect)
        ctx.strokeStyle = 'rgba(0, 242, 254, 0.03)';
        ctx.lineWidth = 0.5;
        const step = 55;
        for (let x = 0; x < W; x += step) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, H);
            ctx.stroke();
        }
        for (let y = 0; y < H; y += step) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(W, y);
            ctx.stroke();
        }

        // Draw and update bubbles
        bubbles.forEach(b => {
            b.update();
            b.draw();
        });

        // Draw faint neon connections between nearby bubbles
        const MAX_DIST = 200;
        for (let i = 0; i < bubbles.length; i++) {
            for (let j = i + 1; j < bubbles.length; j++) {
                const dx = bubbles[i].x - bubbles[j].x;
                const dy = bubbles[i].y - bubbles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < MAX_DIST) {
                    const intensity = (1 - dist / MAX_DIST) * 0.08;
                    ctx.beginPath();
                    ctx.moveTo(bubbles[i].x, bubbles[i].y);
                    ctx.lineTo(bubbles[j].x, bubbles[j].y);
                    ctx.strokeStyle = `rgba(0, 242, 254, ${intensity})`;
                    ctx.lineWidth = 0.8;
                    ctx.stroke();
                }
            }
        }

        // Bioluminescent particles (floating dust)
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.life -= p.decay;
            if (p.life <= 0 || p.x < 0 || p.x > 1 || p.y < 0 || p.y > 1) {
                resetParticle(p);
            }
            const px = p.x * W;
            const py = p.y * H;
            ctx.beginPath();
            ctx.arc(px, py, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 242, 254, ${p.alpha * p.life * 0.5})`;
            ctx.fill();
        });

        requestAnimationFrame(draw);
    }

    // Reposition bubbles when window resizes to keep them inside canvas
    window.addEventListener('resize', () => {
        setTimeout(() => {
            W = canvas.width;
            H = canvas.height;
            bubbles.forEach(b => {
                b.x = Math.min(Math.max(b.x, b.r), W - b.r);
                b.y = Math.min(Math.max(b.y, b.r), H - b.r);
            });
        }, 80);
    });

    draw();
});