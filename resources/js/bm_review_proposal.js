/**
 * resources/js/bm_review_proposal.js
 *
 * Handles the single interactive feature on the read-only proposal
 * review page: Download as PDF.
 *
 * Dependencies (loaded via CDN in the blade head):
 *   - html2canvas  v1.4.1
 *   - jsPDF        v2.5.1  (window.jspdf.jsPDF)
 *
 * Strategy:
 *   1. Temporarily add `.rp-printing` to <body> — this CSS class hides
 *      the nav, sidebar, action bar, and back-link so they are NOT
 *      captured in the screenshot.
 *   2. Run html2canvas on the full <body> at 2× scale for crisp output.
 *   3. Slice the resulting canvas into A4-sized pages.
 *   4. Save the PDF with jsPDF.
 *   5. Remove `.rp-printing` to restore the page.
 */
(function () {
    'use strict';

    /* ── Wait for DOM ──────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {

        var btn       = document.getElementById('rp-pdf-btn');
        var nameEl    = document.getElementById('rp-movie-name');

        if (!btn) return;

        var movieName = nameEl ? (nameEl.dataset.name || 'proposal') : 'proposal';

        /* Sanitise filename — replace anything that isn't alphanumeric/space/dash */
        function sanitise(str) {
            return str.replace(/[^a-zA-Z0-9 \-_]/g, '').trim().replace(/\s+/g, '_');
        }

        btn.addEventListener('click', function () {
            /* ── Guard: libraries must be present ────────── */
            if (typeof html2canvas === 'undefined') {
                alert('PDF library (html2canvas) is still loading — please try again in a moment.');
                return;
            }
            if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                alert('PDF library (jsPDF) is still loading — please try again in a moment.');
                return;
            }

            /* ── UI: disable button + show progress text ─── */
            btn.disabled    = true;
            btn.textContent = '⏳  Generating PDF…';

            /* ── Apply print mode (hides nav/sidebar/actions) */
            document.body.classList.add('rp-printing');

            /* Small delay so the CSS class reflow completes  */
            setTimeout(function () {
                generatePdf(movieName, function () {
                    /* Restore page after capture */
                    document.body.classList.remove('rp-printing');
                    btn.disabled    = false;
                    btn.textContent = '⬇  Download as PDF';
                });
            }, 120);
        });
    });

    /* ── Core PDF generation ───────────────────────────────── */
    function generatePdf(movieName, onComplete) {

        var jsPDF   = window.jspdf.jsPDF;

        /* A4 dimensions in mm */
        var A4_W_MM = 210;
        var A4_H_MM = 297;
        var SCALE   = 2;   /* retina-quality capture */

        html2canvas(document.body, {
            scale:            SCALE,
            useCORS:          true,       /* cross-origin images (posters) */
            allowTaint:       false,
            backgroundColor:  '#0d0f16',  /* match the dark bg so no white flash */
            logging:          false,
        })
        .then(function (canvas) {

            var imgData   = canvas.toDataURL('image/jpeg', 0.92);
            var imgW_px   = canvas.width;
            var imgH_px   = canvas.height;

            /* Work out rendered image size in mm at A4 width */
            var imgW_mm   = A4_W_MM;
            var imgH_mm   = (imgH_px / imgW_px) * A4_W_MM;

            /* How many A4 pages do we need? */
            var pageCount = Math.ceil(imgH_mm / A4_H_MM);

            var pdf = new jsPDF({
                orientation: 'portrait',
                unit:        'mm',
                format:      'a4',
            });

            for (var i = 0; i < pageCount; i++) {

                if (i > 0) pdf.addPage();

                /* y offset (in mm) for the current page slice */
                var yOffset = -(i * A4_H_MM);

                pdf.addImage(
                    imgData,
                    'JPEG',
                    0,           /* x */
                    yOffset,     /* y — negative to scroll through the tall image */
                    imgW_mm,
                    imgH_mm
                );
            }

            /* Build filename: "MovieName_Proposal_2026-06-11.pdf" */
            var today    = new Date();
            var dateStr  = today.getFullYear() + '-' +
                           pad(today.getMonth() + 1) + '-' +
                           pad(today.getDate());
            var filename = sanitise(movieName) + '_Proposal_' + dateStr + '.pdf';

            pdf.save(filename);
        })
        .catch(function (err) {
            console.error('PDF generation failed:', err);
            alert('Could not generate the PDF. Please try again.');
        })
        .finally(function () {
            if (typeof onComplete === 'function') onComplete();
        });

        /* ── Helpers ───────────────────────────────────────── */
        function pad(n) { return String(n).padStart(2, '0'); }

        function sanitise(str) {
            return str.replace(/[^a-zA-Z0-9 \-_]/g, '').trim().replace(/\s+/g, '_');
        }
    }

})();