/**
 * create_theatre.js
 * Place at: resources/js/create_theatre.js
 *
 * Responsibilities:
 *   1. Form ↔ Selection mode switching
 *   2. Cinema card click → confirmation modal
 *   3. Modal confirm → fill cinema_id + restore cinema name in form
 *   4. Restore cinema name on validation error (using window.CT_CINEMAS)
 *   5. Theatre icon / poster file upload previews
 *   6. Service chip visual toggle
 */

(function () {
    'use strict';

    /* ================================================================
       1 & 2 & 3. CINEMA SELECTION FLOW
    ================================================================ */
    function initCinemaSelection() {
        var formView       = document.getElementById('ct-form-view');
        var selectionView  = document.getElementById('ct-selection-view');
        var selectBtn      = document.getElementById('ct-select-cinema-btn');
        var backBtn        = document.getElementById('ct-selection-back');
        var modal          = document.getElementById('ct-confirm-modal');
        var modalImg       = document.getElementById('ct-modal-img');
        var modalImgPH     = document.getElementById('ct-modal-img-placeholder');
        var modalName      = document.getElementById('ct-modal-name');
        var modalConfirm   = document.getElementById('ct-modal-confirm');
        var modalCancel    = document.getElementById('ct-modal-cancel');
        var cinemaIdInput  = document.getElementById('ct-cinema-id-input');
        var selectedDisplay = document.getElementById('ct-selected-cinema-display');
        var selectedName   = document.getElementById('ct-selected-cinema-name');

        if (!formView || !selectionView || !selectBtn) return;

        var pendingId   = null;
        var pendingName = null;

        // Open selection mode
        selectBtn.addEventListener('click', function () {
            formView.classList.add('vc-hidden');
            selectionView.classList.remove('vc-hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Back from selection → form
        backBtn.addEventListener('click', function () {
            selectionView.classList.add('vc-hidden');
            formView.classList.remove('vc-hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Click on a cinema card in selection view
        selectionView.addEventListener('click', function (e) {
            var card = e.target.closest('.ct-selectable-card');
            if (!card) return;
            openModal(card);
        });

        // Keyboard: Enter / Space on a cinema card
        selectionView.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var card = e.target.closest('.ct-selectable-card');
            if (!card) return;
            e.preventDefault();
            openModal(card);
        });

        function openModal(card) {
            pendingId   = card.dataset.cinemaId;
            pendingName = card.dataset.cinemaName;

            // Populate modal name
            modalName.textContent = pendingName;

            // Populate modal image
            var imgSrc = card.dataset.cinemaImg;
            if (imgSrc) {
                modalImg.src = imgSrc;
                modalImg.alt = pendingName;
                modalImg.classList.remove('vc-hidden');
                modalImgPH.classList.add('vc-hidden');
            } else {
                modalImg.classList.add('vc-hidden');
                modalImgPH.classList.remove('vc-hidden');
            }

            modal.classList.remove('vc-hidden');
        }

        // Confirm selection
        modalConfirm.addEventListener('click', function () {
            applySelection(pendingId, pendingName);
            closeModal();
            // Return to form
            selectionView.classList.add('vc-hidden');
            formView.classList.remove('vc-hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Cancel — stay on selection screen
        modalCancel.addEventListener('click', closeModal);

        // Click outside modal box closes it
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Escape key closes modal
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('vc-hidden')) {
                closeModal();
            }
        });

        function applySelection(id, name) {
            cinemaIdInput.value     = id;
            selectedName.textContent = name;
            selectedDisplay.classList.remove('vc-hidden');
            selectBtn.textContent   = '✎  Change Cinema';
        }

        function closeModal() {
            modal.classList.add('vc-hidden');
            pendingId   = null;
            pendingName = null;
        }
    }

    /* ================================================================
       4. RESTORE CINEMA NAME ON VALIDATION ERROR
       If the form was submitted with a cinema_id but failed validation,
       the hidden input keeps old('cinema_id') but the display name is
       empty. We restore it from the inline JSON window.CT_CINEMAS.
    ================================================================ */
    function restoreCinemaDisplay() {
        var cinemaIdInput   = document.getElementById('ct-cinema-id-input');
        var selectedDisplay = document.getElementById('ct-selected-cinema-display');
        var selectedName    = document.getElementById('ct-selected-cinema-name');
        var selectBtn       = document.getElementById('ct-select-cinema-btn');

        if (!cinemaIdInput || !cinemaIdInput.value) return;

        var cinemas = window.CT_CINEMAS || [];
        var match   = cinemas.find(function (c) {
            return String(c.id) === String(cinemaIdInput.value);
        });

        if (match) {
            selectedName.textContent = match.name;
            selectedDisplay.classList.remove('vc-hidden');
            if (selectBtn) selectBtn.textContent = '✎  Change Cinema';
        }
    }

    /* ================================================================
       5. FILE UPLOAD PREVIEWS
    ================================================================ */
    function initFilePreview(inputId, previewId) {
        var fileInput   = document.getElementById(inputId);
        var filePreview = document.getElementById(previewId);
        if (!fileInput || !filePreview) return;

        fileInput.addEventListener('change', function () {
            filePreview.textContent = (this.files && this.files.length > 0)
                ? '📎 ' + this.files[0].name
                : '';
        });
    }

    /* ================================================================
       6. SERVICE CHIP VISUAL TOGGLE
    ================================================================ */
    function initServiceChips() {
        document.querySelectorAll('.ct-service-chip').forEach(function (chip) {
            var checkbox = chip.querySelector('input[type="checkbox"]');
            if (!checkbox) return;

            chip.addEventListener('click', function () {
                setTimeout(function () {
                    chip.classList.toggle('is-checked', checkbox.checked);
                }, 0);
            });
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initCinemaSelection();
        restoreCinemaDisplay();
        initFilePreview('theatre_icon',   'theatre_icon_preview');
        initFilePreview('theatre_poster', 'theatre_poster_preview');
        initServiceChips();
    });

})();