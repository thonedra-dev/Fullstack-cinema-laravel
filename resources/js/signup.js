(function () {
    'use strict';

    var body = document.body;
    if (!body || !body.classList.contains('su-body')) return;

    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var routes = {
        start: body.dataset.startUrl,
        verify: body.dataset.verifyUrl,
        resend: body.dataset.resendUrl,
        complete: body.dataset.completeUrl,
    };

    var stepDots = Array.from(document.querySelectorAll('[data-step-dot]'));
    var panels = Array.from(document.querySelectorAll('[data-step-panel]'));
    var alertBox = document.getElementById('su-alert');
    var subtitle = document.getElementById('su-subtitle');
    var emailTarget = document.getElementById('su-email-target');
    var countdownEl = document.getElementById('su-countdown');
    var resendBtn = document.getElementById('su-resend-btn');
    var fileInput = document.getElementById('avatar');
    var fileName = document.getElementById('su-file-name');
    var codeInput = document.getElementById('verification_code');

    var subtitles = {
        1: 'Enjoy exclusive rewards, faster bookings, and a sharper night at the movies.',
        2: 'Enter the code while the timer is still running.',
        3: 'Lock in your account and add a profile picture if you want.',
    };

    var countdownTimer = null;
    var remainingSeconds = 0;

    function setStep(step) {
        stepDots.forEach(function (dot) {
            var dotStep = parseInt(dot.dataset.stepDot, 10);
            dot.classList.toggle('su-step--active', dotStep === step);
            dot.classList.toggle('su-step--done', dotStep < step);
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('su-form--active', parseInt(panel.dataset.stepPanel, 10) === step);
        });

        if (subtitle) {
            subtitle.textContent = subtitles[step] || subtitles[1];
        }

        showAlert('', '');
    }

    function showAlert(message, type) {
        if (!alertBox) return;

        alertBox.textContent = message || '';
        alertBox.className = 'su-alert';

        if (message) {
            alertBox.classList.add(type === 'success' ? 'su-alert--success' : 'su-alert--error');
        }
    }

    function firstError(data) {
        if (data && data.errors) {
            var messages = Object.values(data.errors).flat();
            if (messages.length) return messages[0];
        }

        return data?.message || 'Something went wrong. Please try again.';
    }

    async function postForm(url, formData) {
        var response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
        });

        var data = await response.json().catch(function () {
            return { message: 'The server returned an unexpected response.' };
        });

        if (!response.ok) {
            throw new Error(firstError(data));
        }

        return data;
    }

    function setBusy(form, busy) {
        Array.from(form.querySelectorAll('button, input')).forEach(function (el) {
            el.disabled = busy;
        });
        form.classList.toggle('su-form--busy', busy);
    }

    function startCountdown(seconds) {
        remainingSeconds = seconds || 180;

        if (countdownTimer) {
            clearInterval(countdownTimer);
        }

        renderCountdown();
        countdownTimer = setInterval(function () {
            remainingSeconds -= 1;
            renderCountdown();

            if (remainingSeconds <= 0) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        }, 1000);
    }

    function renderCountdown() {
        if (!countdownEl) return;

        var minutes = Math.max(0, Math.floor(remainingSeconds / 60));
        var seconds = Math.max(0, remainingSeconds % 60);
        var formatted = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

        countdownEl.textContent = remainingSeconds > 0
            ? 'Code expires in ' + formatted
            : 'Code expired';
    }

    var infoForm = document.getElementById('su-info-form');
    if (infoForm) {
        infoForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            var formData = new FormData(infoForm);
            setBusy(infoForm, true);
            showAlert('', '');

            try {
                var data = await postForm(routes.start, formData);
                if (emailTarget) emailTarget.textContent = data.email || 'your email';
                startCountdown(data.expires_in || 180);
                setStep(2);
                showAlert(data.message || 'Verification code sent.', 'success');
                if (codeInput) codeInput.focus();
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                setBusy(infoForm, false);
            }
        });
    }

    var codeForm = document.getElementById('su-code-form');
    if (codeForm) {
        codeForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            var formData = new FormData(codeForm);
            setBusy(codeForm, true);
            showAlert('', '');

            try {
                var data = await postForm(routes.verify, formData);
                setStep(3);
                showAlert(data.message || 'Email verified.', 'success');
                var password = document.getElementById('password');
                if (password) password.focus();
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                setBusy(codeForm, false);
            }
        });
    }

    if (resendBtn) {
        resendBtn.addEventListener('click', async function () {
            var formData = new FormData();
            resendBtn.disabled = true;
            showAlert('', '');

            try {
                var data = await postForm(routes.resend, formData);
                if (emailTarget) emailTarget.textContent = data.email || 'your email';
                startCountdown(data.expires_in || 180);
                showAlert(data.message || 'A new code has been sent.', 'success');
                if (codeInput) codeInput.value = '';
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                resendBtn.disabled = false;
            }
        });
    }

    var passwordForm = document.getElementById('su-password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            var formData = new FormData(passwordForm);
            setBusy(passwordForm, true);
            showAlert('', '');

            try {
                var data = await postForm(routes.complete, formData);
                showAlert(data.message || 'Signup complete.', 'success');

                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                setBusy(passwordForm, false);
            }
        });
    }

    document.querySelectorAll('[data-back-to]').forEach(function (button) {
        button.addEventListener('click', function () {
            setStep(parseInt(button.dataset.backTo, 10));
        });
    });

    if (codeInput) {
        codeInput.addEventListener('input', function () {
            codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    if (fileInput && fileName) {
        fileInput.addEventListener('change', function () {
            fileName.textContent = fileInput.files && fileInput.files[0]
                ? fileInput.files[0].name
                : 'Optional JPG, PNG, or WEBP';
        });
    }

    setStep(1);
})();
