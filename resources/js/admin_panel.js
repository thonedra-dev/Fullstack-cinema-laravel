/**
 * admin_panel.js
 * Place at: resources/js/admin_panel.js
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // 1. Dynamic Time-based Greeting
    const updateGreeting = () => {
        const titleEl = document.getElementById('greeting-title');
        if (!titleEl) return;

        const hour = new Date().getHours();
        let greeting = 'Welcome back';

        if (hour >= 5 && hour < 12) {
            greeting = 'Good morning';
        } else if (hour >= 12 && hour < 17) {
            greeting = 'Good afternoon';
        } else if (hour >= 17 && hour < 22) {
            greeting = 'Good evening';
        }

        titleEl.textContent = `${greeting}, Admin.`;
    };

    // 2. Staggered Card Entrance Animation
    const animateCards = () => {
        const cards = document.querySelectorAll('.ap-card');
        
        // Initial state
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
        });

        // Trigger animations with a slight delay per card
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
                
                // Remove inline transition after entrance to allow CSS hover effects to take over
                setTimeout(() => {
                    card.style.transition = '';
                    card.style.transform = '';
                }, 500);
            }, index * 75); // 75ms stagger effect
        });
    };

    // Initialize
    updateGreeting();
    
    // Small delay to ensure CSS is painted before animating
    setTimeout(animateCards, 100);
});