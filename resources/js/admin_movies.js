document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('mpTabToggleBtn');
    const tabsBar = document.getElementById('mpTabsBar');

    if (toggleBtn && tabsBar) {
        toggleBtn.addEventListener('click', () => {
            // Toggle the mobile tab menu
            tabsBar.classList.toggle('mp-tabs-bar--open');
            
            // Manage accessibility attributes
            const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', !isExpanded);
        });
    }
});