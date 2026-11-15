import { initCinemaAperture } from './admin_login_visual.js';

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

    initCinemaAperture();
});
