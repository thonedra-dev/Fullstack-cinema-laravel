document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /* ── Boot messages — cinematic tone, no cyberpunk jargon ─── */
    const bootMessages = [
        "Welcome back. All systems running smoothly.",
        "Session restored. Ready for operations.",
        "Good to see you. The panel is yours."
    ];

    const randomMsg  = bootMessages[Math.floor(Math.random() * bootMessages.length)];
    const messageEl  = document.getElementById('greeting-message');
    const overlay    = document.getElementById('greeting-overlay');
    const closeBtn   = document.getElementById('greeting-close');

    /* ── Greeting typewriter ─────────────────────────────────── */
    if (overlay && messageEl) {
        overlay.style.display = 'flex';

        let i = 0;
        messageEl.innerHTML = '';

        const typeWriter = () => {
            if (i < randomMsg.length) {
                messageEl.innerHTML += randomMsg.charAt(i);
                i++;
                setTimeout(typeWriter, 42);
            }
        };
        setTimeout(typeWriter, 400);
    }

    /* ── Close overlay ───────────────────────────────────────── */
    const closeGreeting = () => {
        if (!overlay) return;
        overlay.style.opacity = '0';
        setTimeout(() => overlay.style.display = 'none', 300);
    };

    if (closeBtn) closeBtn.addEventListener('click', closeGreeting);

    /* ── Assign data-color to each card ──────────────────────────
       Colour order maps to the 9 cards in the grid left-to-right,
       top-to-bottom. Adjust the array order to taste.
    ─────────────────────────────────────────────────────────────*/
    const colorMap = [
        'purple',  // Add Cinema
        'blue',    // View Cinemas
        'green',   // Add City
        'orange',  // Create Theatre
        'purple',  // Add Service
        'blue',    // Create Movie
        'green',   // Managers
        'orange',  // Proposals
        'red',     // Food & Drinks
    ];

    document.querySelectorAll('.ap-card').forEach((card, index) => {
        const colour = colorMap[index] ?? 'purple';
        card.setAttribute('data-color', colour);
    });

    /* ── Staggered card entrance ─────────────────────────────── */
    const cards = document.querySelectorAll('.ap-card');

    cards.forEach(card => {
        card.style.opacity   = '0';
        card.style.transform = 'translateY(20px)';
    });

    setTimeout(() => {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                card.style.opacity    = '1';
                card.style.transform  = 'translateY(0)';
            }, index * 80);
        });
    }, 350);
});