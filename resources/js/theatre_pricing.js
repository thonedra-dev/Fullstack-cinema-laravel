/**
 * theatre_pricing.js
 * Ticket pricing matrix – bulk, quick‑fill, expand, clear.
 */
(function () {
    'use strict';

    var PRICING_THEATRES = window.MC_PRICING_THEATRES || [];
    var PRICING_SEATS    = ['standard', 'premium', 'family', 'couple'];
    var PRICING_DAYS     = ['weekday', 'weekend'];

    var bulkState = {};

    function show(el) { if (el) el.classList.remove('vc-hidden'); }
    function hide(el) { if (el) el.classList.add('vc-hidden'); }

    function pricingKey(seat, day) { return seat + '|' + day; }

    function getInputsForTheatre(theatreName) {
        return Array.prototype.slice.call(document.querySelectorAll(
            '.mc-price-input[data-theatre-name="' + theatreName.replace(/"/g, '\\"') + '"]'
        ));
    }

    function getAllInputs() {
        return Array.prototype.slice.call(document.querySelectorAll('.mc-price-input'));
    }

    function showPricingMessage(msg) {
        var s = document.getElementById('mc-pricing-status');
        if (s) { s.textContent = msg; show(s); }
    }

    function clearPricingMessage() {
        var s = document.getElementById('mc-pricing-status');
        if (s) { s.textContent = ''; hide(s); }
    }

    /* Quick‑fill per theatre */
    function initQuickFill() {
        document.querySelectorAll('.mc-pricing-card').forEach(function (card) {
            var theatre = card.dataset.theatre;
            var toggleBtn = card.querySelector('.mc-card-quickfill-btn');
            var quickfillRow = card.querySelector('.mc-pricing-card__quickfill');
            var input = card.querySelector('.mc-quickfill-input');
            var applyBtn = card.querySelector('.mc-quickfill-apply');

            if (!toggleBtn || !quickfillRow) return;

            toggleBtn.addEventListener('click', function () {
                quickfillRow.classList.toggle('vc-hidden');
                if (!quickfillRow.classList.contains('vc-hidden') && input) input.focus();
            });

            if (applyBtn && input) {
                applyBtn.addEventListener('click', function () {
                    var val = parseFloat(input.value);
                    if (isNaN(val) || val <= 0) {
                        alert('Please enter a valid positive price.');
                        return;
                    }
                    var priceStr = val.toFixed(2);
                    getInputsForTheatre(theatre).forEach(function (inp) {
                        inp.value = priceStr;
                        inp.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    quickfillRow.classList.add('vc-hidden');
                    input.value = '';
                });
            }
        });
    }

    function initLocalDelete() {
        document.querySelectorAll('.mc-card-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.mc-pricing-card');
                if (!card) return;
                getInputsForTheatre(card.dataset.theatre).forEach(function (inp) {
                    inp.value = '';
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        });
    }

    function initGlobalClear() {
        var btn = document.getElementById('mc-pricing-clear-all-btn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (!confirm('Clear ALL ticket prices across every theatre?')) return;
            getAllInputs().forEach(function (inp) {
                inp.value = '';
                inp.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }

    function initExpand() {
        var expandBtn = document.getElementById('mc-pricing-expand-btn');
        var collapseBtn = document.getElementById('mc-pricing-collapse-btn');
        var overlay = document.getElementById('mc-pricing-expand-overlay');
        var placeholder = document.getElementById('mc-pricing-expand-placeholder');
        var grid = document.getElementById('mc-pricing-grid');
        if (!expandBtn || !overlay || !placeholder || !grid) return;

        expandBtn.addEventListener('click', function () {
            placeholder.appendChild(grid);
            show(overlay);
        });

        function collapse() {
            var viewport = document.getElementById('mc-pricing-viewport');
            if (viewport && grid) viewport.appendChild(grid);
            hide(overlay);
        }

        collapseBtn.addEventListener('click', collapse);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) collapse();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.classList.contains('vc-hidden')) collapse();
        });
    }

    /* Bulk edit modal */
    /* Bulk edit modal */
function buildBulkFields() {
    var container = document.getElementById('mc-bulk-fields');
    if (!container) return;
    container.innerHTML = '';
    PRICING_SEATS.forEach(function (seat) {
        PRICING_DAYS.forEach(function (day) {
            var labelText = seat.charAt(0).toUpperCase() + seat.slice(1) + ' · ' + (day === 'weekday' ? 'Weekday' : 'Weekend');
            
            var group = document.createElement('div');
            group.className = 'mc-bulk-field-group';
            
            var label = document.createElement('span');
            label.className = 'mc-bulk-field-label';
            label.textContent = labelText;
            
            var field = document.createElement('label');
            field.className = 'mc-price-field';
            field.innerHTML = '<span>RM</span><input type="number" class="mc-price-input mc-bulk-input" min="0.01" step="0.01" placeholder="0.00" data-seat="' + seat + '" data-day="' + day + '">';
            
            group.appendChild(label);
            group.appendChild(field);
            container.appendChild(group);
        });
    });
}

    function openBulkModal() {
        var firstTheatre = PRICING_THEATRES[0] || '';
        var currentValues = {};
        if (firstTheatre) {
            getInputsForTheatre(firstTheatre).forEach(function (inp) {
                var k = pricingKey(inp.dataset.seatType, inp.dataset.dayType);
                currentValues[k] = inp.value;
            });
        }

        document.querySelectorAll('.mc-bulk-input').forEach(function (inp) {
            var k = pricingKey(inp.dataset.seat, inp.dataset.day);
            if (bulkState[k] !== undefined) {
                inp.value = bulkState[k];
            } else if (currentValues[k] !== undefined) {
                inp.value = currentValues[k];
            } else {
                inp.value = '';
            }
        });

        show(document.getElementById('mc-bulk-modal'));
    }

    function closeBulkModal() {
        hide(document.getElementById('mc-bulk-modal'));
    }

    function applyBulk() {
        var newBulkState = {};
        document.querySelectorAll('.mc-bulk-input').forEach(function (inp) {
            var k = pricingKey(inp.dataset.seat, inp.dataset.day);
            var val = inp.value.trim();
            newBulkState[k] = val;
            bulkState[k] = val;
        });

        getAllInputs().forEach(function (cardInput) {
            var k = pricingKey(cardInput.dataset.seatType, cardInput.dataset.dayType);
            if (newBulkState[k] !== '' && newBulkState[k] !== undefined) {
                cardInput.value = newBulkState[k];
                cardInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        closeBulkModal();
    }

    function initBulkModal() {
        buildBulkFields();
        document.getElementById('mc-pricing-bulk-btn')?.addEventListener('click', openBulkModal);
        document.getElementById('mc-bulk-apply')?.addEventListener('click', applyBulk);
        document.getElementById('mc-bulk-cancel')?.addEventListener('click', closeBulkModal);
        document.getElementById('mc-bulk-modal')?.addEventListener('click', function (e) {
            if (e.target === this) closeBulkModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !document.getElementById('mc-bulk-modal').classList.contains('vc-hidden')) {
                closeBulkModal();
            }
        });
    }

    function collectPricingRules(showErrors) {
        var inputs = getAllInputs();
        var rules = [];
        var firstInvalid = null;

        if (PRICING_THEATRES.length === 0) {
            if (showErrors) showPricingMessage('No theatre types available.');
            return null;
        }

        inputs.forEach(function (inp) { inp.classList.remove('is-invalid'); });

        PRICING_THEATRES.forEach(function (theatreName) {
            PRICING_SEATS.forEach(function (seat) {
                PRICING_DAYS.forEach(function (day) {
                    var sel = '.mc-price-input[data-theatre-name="' + theatreName.replace(/"/g, '\\"') + '"][data-seat-type="' + seat + '"][data-day-type="' + day + '"]';
                    var input = document.querySelector(sel);
                    var raw = input ? input.value.trim() : '';
                    var amount = Number(raw);
                    if (!input || raw === '' || !Number.isFinite(amount) || amount <= 0) {
                        if (showErrors && input) input.classList.add('is-invalid');
                        if (!firstInvalid) firstInvalid = input || true;
                        return;
                    }
                    rules.push({
                        theatreName: theatreName,
                        seatType: seat,
                        dayType: day,
                        price: amount.toFixed(2)
                    });
                });
            });
        });

        if (firstInvalid && showErrors) {
            showPricingMessage('Please fill every ticket price with an amount greater than 0.');
            if (firstInvalid !== true && firstInvalid.focus) firstInvalid.focus();
            return null;
        }
        return rules;
    }

    function syncPricingHidden(showErrors) {
        var input = document.getElementById('mc-ticket-prices-json');
        if (!input) return true;
        var rules = collectPricingRules(showErrors);
        if (!rules) return false;
        input.value = JSON.stringify(rules);
        clearPricingMessage();
        return true;
    }

    function restorePricingFromOld() {
        var input = document.getElementById('mc-ticket-prices-json');
        if (!input || !input.value) return;
        var parsed;
        try { parsed = JSON.parse(input.value); } catch (e) { return; }
        if (!Array.isArray(parsed)) return;
        var valuesByKey = {};
        parsed.forEach(function (rule) {
            if (!rule) return;
            valuesByKey[pricingKey(rule.seatType, rule.dayType) + '|' + rule.theatreName] = rule.price;
        });
        getAllInputs().forEach(function (inp) {
            var k = pricingKey(inp.dataset.seatType, inp.dataset.dayType) + '|' + inp.dataset.theatreName;
            if (valuesByKey[k] !== undefined) inp.value = valuesByKey[k];
        });
        syncPricingHidden(false);
    }

    function initInputListeners() {
        document.querySelectorAll('.mc-price-input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                this.classList.remove('is-invalid');
                syncPricingHidden(false);
            });
            inp.addEventListener('blur', function () {
                var v = Number(this.value);
                if (this.value.trim() !== '' && Number.isFinite(v) && v > 0) {
                    this.value = v.toFixed(2);
                }
                syncPricingHidden(false);
            });
        });
    }

    window.MC_Pricing = {
        init: function () {
            try {
                if (PRICING_THEATRES.length === 0) return;
                initInputListeners();
                initQuickFill();
                initLocalDelete();
                initGlobalClear();
                initExpand();
                initBulkModal();
                restorePricingFromOld();
            } catch (e) {
                console.error('MC_Pricing init error:', e);
            }
        },
        sync: syncPricingHidden,
        collect: collectPricingRules
    };

})();