document.addEventListener('DOMContentLoaded', function () {
    // ── Layout toggles ──────────────────────────────────────
    const toggles = document.querySelectorAll('.um-layout-toggle');
    const compactView = document.getElementById('um-compact-view');
    const expandedView = document.getElementById('um-expanded-view');

    toggles.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.dataset.view;

            // Active state
            toggles.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');

            // Show / hide
            if (view === 'compact') {
                compactView.style.display = '';
                expandedView.style.display = 'none';
            } else {
                compactView.style.display = 'none';
                expandedView.style.display = '';
            }
        });
    });

    // ── Delete approved cards (expanded view only) ──────────
    // Using event delegation on the expanded view container
    if (expandedView) {
        expandedView.addEventListener('click', function (e) {
            const btn = e.target.closest('.um-delete-btn');
            if (!btn) return;

            const card = btn.closest('.um-card');
            if (card) {
                card.remove();
            }
        });
    }
});