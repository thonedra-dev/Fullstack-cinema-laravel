/**
 * admin_panel.js
 * Cinema Admin Launchpad – 5 time‑period greeting pop‑up,
 * side‑by‑side comic layout, female character,
 * staggered card entrance animation.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ---------- 1. Time‑period mapping ----------
    const getTimePeriod = (hour) => {
        if (hour >= 0 && hour <= 4)  return 'midnight';   // 12AM–4:59AM
        if (hour >= 5 && hour <= 8)  return 'morning';    // 5AM–8:59AM
        if (hour >= 9 && hour <= 13) return 'forenoon';   // 9AM–1:59PM
        if (hour >= 14 && hour <= 17) return 'evening';   // 2PM–5:59PM
        return 'nightcity';                                // 6PM–11:59PM
    };

    // ---------- 2. Greeting messages (playful, cinema‑themed) ----------
    const messages = {
        midnight:  "Even though the world is asleep, the cinema never rests, right? 🌙<br><strong>Welcome back, Admin.</strong>",
        morning:   "A new day is dawning, just like the opening scene of a masterpiece. ☀️<br><strong>Good morning, Admin!</strong>",
        forenoon:  "The reels are spinning, the day is in full swing. 🎬<br><strong>Hello, Admin!</strong>",
        evening:   "The golden hour is here – perfect for a matinee or a premiere. 🌇<br><strong>Good evening, Admin!</strong>",
        nightcity: "The city lights up, the night screenings begin! 🌃<br><strong>Let's make magic, Admin!</strong>"
    };

    const now = new Date();
    const hour = now.getHours();
    const period = getTimePeriod(hour);

    // ---------- 3. Set atmosphere & character classes ----------
    const overlay   = document.getElementById('greeting-overlay');
    const popup     = document.getElementById('greeting-popup');
    const charContainer = document.getElementById('character-container');
    const messageEl = document.getElementById('greeting-message');
    const closeBtn  = document.getElementById('greeting-close');

    if (popup && charContainer && messageEl && overlay && closeBtn) {
        // Apply time‑based atmosphere (celestial background + sky colour)
        popup.classList.add(`atmo-${period}`);
        // Apply time‑based character expression and prop
        charContainer.classList.add(`period-${period}`);

        // Insert the greeting text
        messageEl.innerHTML = messages[period];

        // Reveal the pop‑up (CSS entrance animation)
        overlay.style.display = 'flex';
    }

    // ---------- 4. Close behaviour ----------
    const closeGreeting = () => {
        if (!overlay) return;
        overlay.style.transition = 'opacity 0.4s ease';
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
            overlay.style.transition = '';
        }, 400);
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeGreeting);
    }

    // Click outside the popup to close
    if (overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeGreeting();
            }
        });
    }

    // ---------- 5. Staggered card entrance (original effect) ----------
    const animateCards = () => {
        const cards = document.querySelectorAll('.ap-card');
        if (!cards.length) return;

        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
        });

        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';

                setTimeout(() => {
                    card.style.transition = '';
                    card.style.transform = '';
                }, 500);
            }, index * 75);
        });
    };

    // Start card animation shortly after pop‑up appears
    setTimeout(animateCards, 200);
});