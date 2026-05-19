/**
 * admin_food_drink.js
 * Location: resources/js/admin_food_drink.js
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const uploadZone = document.getElementById('uploadZone');
    const imageInput = document.getElementById('imageInput');
    const stateEmpty = document.getElementById('uploadStateEmpty');
    const statePreview = document.getElementById('uploadStatePreview');
    const imagePreview = document.getElementById('imagePreview');

    if (!uploadZone || !imageInput) return;

    // Trigger file dialog window on clicking container bounding box area
    uploadZone.addEventListener('click', () => {
        imageInput.click();
    });

    // Intercept target changes inside raw input elements
    imageInput.addEventListener('change', function() {
        handleFileSelection(this.files);
    });

    // Handle drag and drop interaction logic
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('is-dragging');
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => {
            uploadZone.classList.remove('is-dragging');
        });
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            imageInput.files = e.dataTransfer.files;
            handleFileSelection(e.dataTransfer.files);
        }
    });

    /**
     * Process file stream chunks to mount standard preview paths
     */
    function handleFileSelection(files) {
        if (!files || !files[0]) return;

        const file = files[0];

        // Ensure the attached file is an image asset container
        if (!file.type.match('image.*')) {
            alert('Please attach an image asset file extension (PNG, JPG, JPEG, WEBP).');
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            // Bind base64 string directly onto viewport canvas framework
            imagePreview.src = e.target.result;
            
            // Adjust CSS presentation flags seamlessly
            stateEmpty.classList.add('d-none');
            statePreview.classList.remove('d-none');
        };

        reader.readAsDataURL(file);
    }
});