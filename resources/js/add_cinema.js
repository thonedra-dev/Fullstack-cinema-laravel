/**
 * add_cinema.js
 * Place at: resources/js/add_cinema.js
 *
 * Responsibilities:
 *   1. State → City dynamic dropdown
 *      Reads window.AC_CITIES_BY_STATE (inlined by Blade from controller data)
 *   2. Cinema picture upload preview label
 */

(function () {
    'use strict';

    /* ================================================================
       1. STATE → CITY DYNAMIC DROPDOWN
    ================================================================ */
    function initCityDropdown() {
        var stateSelect = document.getElementById('state_select');
        var citySelect  = document.getElementById('city_id');

        if (!stateSelect || !citySelect) return;

        var citiesByState  = window.AC_CITIES_BY_STATE || {};
        var previousCityId = citySelect.dataset.selected || '';   // Restore on validation error

        stateSelect.addEventListener('change', function () {
            populateCities(this.value, previousCityId);
            previousCityId = '';   // After manual change, clear preserved value
        });

        // On page load: re-populate if state already selected (validation error repopulate)
        if (stateSelect.value) {
            populateCities(stateSelect.value, previousCityId);
        }

        function populateCities(state, selectedId) {
            citySelect.innerHTML = '';

            if (!state) {
                appendOption(citySelect, '', '— Select state first —');
                citySelect.disabled = true;
                return;
            }

            var cities = citiesByState[state] || [];

            if (cities.length === 0) {
                appendOption(citySelect, '', '— No cities found —');
                citySelect.disabled = true;
                return;
            }

            appendOption(citySelect, '', '— Select city —');

            cities.forEach(function (city) {
                var opt = appendOption(citySelect, city.id, city.name);
                if (String(city.id) === String(selectedId)) {
                    opt.selected = true;
                }
            });

            citySelect.disabled = false;
        }

        function appendOption(select, value, text) {
            var opt = document.createElement('option');
            opt.value       = value;
            opt.textContent = text;
            select.appendChild(opt);
            return opt;
        }
    }

    /* ================================================================
       2. FILE UPLOAD PREVIEW
    ================================================================ */
    function initFilePreview() {
        var fileInput   = document.getElementById('cinema_picture');
        var filePreview = document.getElementById('file_preview_name');

        if (!fileInput || !filePreview) return;

        fileInput.addEventListener('change', function () {
            filePreview.textContent = (this.files && this.files.length > 0)
                ? '📎 ' + this.files[0].name
                : '';
        });
    }

    /* ================================================================
       INIT
    ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initCityDropdown();
        initFilePreview();
    });

})();