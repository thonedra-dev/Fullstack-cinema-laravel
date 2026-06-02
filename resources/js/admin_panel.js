document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // Typewriter effect for the cyberpunk boot sequence
    const bootMessages = [
        "Uplink established. Grid is stable.",
        "Security protocols offline. Entering override mode.",
        "Data streams synchronized. Ready for input."
    ];
    
    // Pick a random system message
    const randomMsg = bootMessages[Math.floor(Math.random() * bootMessages.length)];
    const messageEl = document.getElementById('greeting-message');
    const overlay = document.getElementById('greeting-overlay');
    const closeBtn = document.getElementById('greeting-close');

    if (overlay && messageEl) {
        overlay.style.display = 'flex';
        
        let i = 0;
        messageEl.innerHTML = '';
        // Typewriter animation
        const typeWriter = () => {
            if (i < randomMsg.length) {
                messageEl.innerHTML += randomMsg.charAt(i);
                i++;
                setTimeout(typeWriter, 40);
            }
        };
        setTimeout(typeWriter, 500);
    }

    // Close logic
    const closeGreeting = () => {
        if (!overlay) return;
        overlay.style.opacity = '0';
        setTimeout(() => overlay.style.display = 'none', 300);
    };

    if (closeBtn) closeBtn.addEventListener('click', closeGreeting);

    // Staggered Cyber-card entrance
    const cards = document.querySelectorAll('.ap-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px) scale(0.95)';
    });

    setTimeout(() => {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
            }, index * 100);
        });
    }, 400);
});