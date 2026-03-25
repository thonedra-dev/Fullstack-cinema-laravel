/**
 * create_service.js
 * Place at: resources/js/create_service.js
 *
 * Responsibilities:
 *   1. Service icon upload preview label
 */

(function () {
    'use strict';

    function initIconPreview() {
        var fileInput   = document.getElementById('service_icon');
        var filePreview = document.getElementById('service_icon_preview');

        if (!fileInput || !filePreview) return;

        fileInput.addEventListener('change', function () {
            filePreview.textContent = (this.files && this.files.length > 0)
                ? '📎 ' + this.files[0].name
                : '';
        });
    }

    document.addEventListener('DOMContentLoaded', initIconPreview);

})();