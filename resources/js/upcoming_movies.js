document.addEventListener('DOMContentLoaded', function () {

    // ── DOM elements ───────────────────────────────────────
    const toggles        = document.querySelectorAll('.um-layout-toggle');
    const compactView    = document.getElementById('um-compact-view');
    const expandedView   = document.getElementById('um-expanded-view');
    const dustbinBtn     = document.querySelector('.um-dustbin-toggle');
    const body           = document.body;

    const modal          = document.getElementById('um-delete-modal');
    const cancelBtn      = document.getElementById('um-modal-cancel');
    const deleteBtn      = document.getElementById('um-modal-delete');

    // ── Layout toggle logic ───────────────────────────────
    toggles.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.dataset.view;

            toggles.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');

            if (view === 'compact') {
                compactView.style.display = '';
                expandedView.style.display = 'none';
            } else {
                compactView.style.display = 'none';
                expandedView.style.display = '';
            }
        });
    });

    // ── Dustbin toggle (select mode) ──────────────────────
    dustbinBtn.addEventListener('click', function () {
        if (body.classList.contains('select-mode-active')) {
            // If already in select mode, check if any selected cards
            const selected = document.querySelectorAll('.um-compact-card.selected, .um-card.selected');
            if (selected.length > 0) {
                showModal();
            } else {
                exitSelectMode();
            }
        } else {
            enterSelectMode();
        }
    });

    function enterSelectMode() {
        body.classList.add('select-mode-active');
        dustbinBtn.classList.add('um-dustbin-toggle--active');
    }

    function exitSelectMode() {
        body.classList.remove('select-mode-active');
        dustbinBtn.classList.remove('um-dustbin-toggle--active');
        clearAllSelections();
    }

    function clearAllSelections() {
        document.querySelectorAll('.um-compact-card.selected, .um-card.selected').forEach(card => {
            card.classList.remove('selected');
        });
    }

    // ── Card selection logic ──────────────────────────────
    // Global click listener, only active when select mode is on
    document.addEventListener('click', function (e) {
        if (!body.classList.contains('select-mode-active')) return;

        // Don't select if clicking on interactive elements
        if (e.target.closest('a, button, input, select, textarea, .um-dustbin-toggle')) return;

        const compactCard = e.target.closest('.um-compact-card');
        const expandedCard = e.target.closest('.um-card');

        if (compactCard) {
            compactCard.classList.toggle('selected');
        } else if (expandedCard) {
            expandedCard.classList.toggle('selected');
        }
    });

    // ── Modal logic ───────────────────────────────────────
    function showModal() {
        modal.style.display = 'flex';
    }

    function hideModal() {
        modal.style.display = 'none';
    }

    cancelBtn.addEventListener('click', function () {
        hideModal();
        // Keep selections and stay in select mode (user might want to adjust)
    });

    deleteBtn.addEventListener('click', function () {
        // Remove all selected cards from DOM
        document.querySelectorAll('.um-compact-card.selected, .um-card.selected').forEach(card => {
            card.remove();
        });
        hideModal();
        exitSelectMode();
    });

    // Close modal when clicking overlay background
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            hideModal();
        }
    });

});