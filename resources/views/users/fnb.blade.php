{{--
    resources/views/users/fnb.blade.php
    Step 2 — F&B Add-ons placeholder
    Controller: BookingController@fnb
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add-ons — CinemaX</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green: #22c55e;
            --purple: #a855f7;
            --pink:   #ec4899;
            --grad:   linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            --dark:   #0a0a0a;
            --surface:#111;
            --border: rgba(255,255,255,0.08);
            --text:   #f0f0f0;
            --muted:  rgba(240,240,240,0.45);
        }
        body { background: var(--dark); color: var(--text); font-family: 'DM Sans', sans-serif; min-height: 100vh; }

        /* ── Top nav brand ──────────────────────────────────── */
        .top-bar {
            padding: 18px 32px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand {
            display: flex; align-items: center; gap: 10px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.3rem; font-weight: 800;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }
        .brand__icon {
            width: 28px; height: 28px; flex-shrink: 0;
        }

        /* ── Timer ──────────────────────────────────────────── */
        .timer-wrap {
            display: flex; align-items: center; gap: 10px;
        }
        .timer-label {
            font-size: 0.75rem; font-weight: 600;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--muted);
        }
        .timer-display {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.05rem; font-weight: 700;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            min-width: 52px; text-align: right;
        }
        .timer-display--urgent {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
            -webkit-background-clip: text !important;
            background-clip: text !important;
        }
        .timer-bar-track {
            width: 120px; height: 3px;
            background: rgba(255,255,255,0.08);
            border-radius: 99px; overflow: hidden;
        }
        .timer-bar-fill {
            height: 100%;
            background: var(--grad);
            border-radius: 99px;
            transition: width 1s linear;
        }

        /* ── Steps ──────────────────────────────────────────── */
        .steps {
            display: flex; align-items: center; justify-content: center;
            padding: 24px 20px; background: var(--surface);
            border-bottom: 1px solid var(--border);
        }
        .step { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 500; color: var(--muted); }
        .step--active { color: var(--purple); }
        .step--done   { color: var(--text); }
        .step__num {
            width: 26px; height: 26px; border-radius: 50%;
            border: 1.5px solid currentColor;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem; font-weight: 700; flex-shrink: 0;
        }
        .step--done .step__num {
            background: var(--grad); border-color: transparent; color: #fff;
        }
        .step-div { width: 40px; height: 1px; background: var(--border); margin: 0 10px; }

        /* ── Layout ─────────────────────────────────────────── */
        .shell { max-width: 780px; margin: 0 auto; padding: 36px 20px 120px; }

        /* ── Summary card ───────────────────────────────────── */
        .card { background: var(--surface); border: 1px solid var(--border); padding: 24px 28px; margin-bottom: 24px; }
        .card__label {
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; margin-bottom: 14px;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }
        .summary__movie { font-size: 1.15rem; font-weight: 700; margin-bottom: 10px; }
        .summary__meta  { display: flex; flex-wrap: wrap; gap: 14px; font-size: 0.83rem; color: var(--muted); margin-bottom: 14px; }
        .divider { height: 1px; background: var(--border); margin: 14px 0; }
        .seats-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .seat-chip {
            background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.28);
            color: var(--purple); padding: 4px 12px; font-size: 0.78rem; font-weight: 600;
        }
        .total-row { display: flex; justify-content: space-between; align-items: baseline; }
        .total-row__label { font-size: 0.92rem; font-weight: 700; }
        .total-row__amt {
            font-family: 'Space Grotesk', sans-serif; font-size: 1.3rem; font-weight: 800;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Placeholder ────────────────────────────────────── */
        .placeholder {
            background: var(--surface); border: 1px solid var(--border);
            padding: 56px 28px; text-align: center;
        }
        .placeholder__icon { font-size: 2.8rem; margin-bottom: 14px; }
        .placeholder__title { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
        .placeholder__sub { font-size: 0.85rem; color: var(--muted); max-width: 340px; margin: 0 auto; line-height: 1.6; }

        /* ── Bottom bar ─────────────────────────────────────── */
        .bar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #0d0d0d; border-top: 1px solid var(--border);
            padding: 14px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            z-index: 100;
        }
        .bar__back {
            background: none; border: 1px solid var(--border); color: var(--muted);
            padding: 0 22px; height: 44px; font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }
        .bar__back:hover { border-color: rgba(255,255,255,0.3); color: var(--text); }
        .bar__right { display: flex; align-items: center; gap: 20px; }
        .bar__total { font-size: 0.82rem; color: var(--muted); }
        .bar__total strong { color: var(--text); }
        .bar__next {
            display: inline-flex; align-items: center; gap: 8px;
            height: 44px; padding: 0 28px;
            background: var(--grad); color: #fff; border: none;
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; font-weight: 700;
            letter-spacing: 0.06em; cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .bar__next:hover { opacity: 0.88; }

        /* ── Confirm-back modal ─────────────────────────────── */
        .confirm-modal {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.88);
            z-index: 9999;
            display: none; align-items: center; justify-content: center;
        }
        .confirm-modal.open { display: flex; }
        .confirm-modal__box {
            background: #111; border: 1px solid rgba(255,255,255,0.12);
            padding: 36px 32px; max-width: 400px; width: 90%; text-align: center;
        }
        .confirm-modal__icon { font-size: 2.2rem; margin-bottom: 14px; }
        .confirm-modal__title { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
        .confirm-modal__sub {
            font-size: 0.86rem; color: var(--muted); line-height: 1.55;
            max-width: 300px; margin: 0 auto 26px;
        }
        .confirm-modal__actions { display: flex; gap: 12px; }
        .confirm-modal__yes {
            flex: 1; height: 44px; background: #ef4444; color: #fff;
            border: none; font-weight: 700; font-size: 0.88rem;
            font-family: 'DM Sans', sans-serif; cursor: pointer;
            transition: background 0.2s;
        }
        .confirm-modal__yes:hover { background: #dc2626; }
        .confirm-modal__no {
            flex: 1; height: 44px; background: none;
            border: 1px solid var(--border); color: var(--muted);
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }
        .confirm-modal__no:hover { border-color: rgba(255,255,255,0.3); color: var(--text); }
    </style>
</head>
<body data-expires-at="{{ $expiresAt ?? '' }}">

{{-- Top nav --}}
<div class="top-bar">
    <a href="{{ route('home') }}" class="brand">
        <svg class="brand__icon" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="1" y="1" width="26" height="26" rx="3" stroke="url(#g)" stroke-width="1.5"/>
            <path d="M6 8h3v12H6zM19 8h3v12h-3z" fill="url(#g)"/>
            <path d="M9 11l10 3-10 3V11z" fill="url(#g)"/>
            <defs>
                <linearGradient id="g" x1="0" y1="0" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#a855f7"/><stop offset="1" stop-color="#ec4899"/>
                </linearGradient>
            </defs>
        </svg>
        Cinema<span style="-webkit-text-fill-color:transparent;background:linear-gradient(135deg,#a855f7,#ec4899);-webkit-background-clip:text;background-clip:text;">X</span>
    </a>
    <div class="timer-wrap">
        <span class="timer-label">Session expires in</span>
        <div>
            <div class="timer-bar-track"><div class="timer-bar-fill" id="timer-bar"></div></div>
        </div>
        <span class="timer-display" id="timer-display">5:00</span>
    </div>
</div>

{{-- Steps --}}
<div class="steps">
    <div class="step step--done"><span class="step__num">✓</span><span>Select Seats</span></div>
    <div class="step-div"></div>
    <div class="step step--active"><span class="step__num">2</span><span>F&amp;B Add-ons</span></div>
    <div class="step-div"></div>
    <div class="step"><span class="step__num">3</span><span>Payment</span></div>
</div>

<div class="shell">

    <div class="card">
        <div class="card__label">Your Booking</div>
        <div class="summary__movie">{{ $movie?->movie_name ?? '—' }}</div>
        <div class="summary__meta">
            <span>📍 {{ $cinema?->cinema_name ?? '—' }}</span>
            <span>🎬 {{ $theatre?->theatre_name ?? '—' }}</span>
            @if($showtime)
                <span>📅 {{ \Carbon\Carbon::parse($showtime->start_time)->format('d M Y') }}</span>
                <span>⏰ {{ \Carbon\Carbon::parse($showtime->start_time)->format('h:i A') }}</span>
            @endif
        </div>
        <div class="divider"></div>
        <div class="seats-list">
            @foreach($booking->tickets as $ticket)
                @if($ticket->seat)
                    <span class="seat-chip">
                        {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}
                        &nbsp;·&nbsp;{{ ucfirst($ticket->seat->seat_type) }}
                    </span>
                @endif
            @endforeach
        </div>
        <div class="total-row">
            <span class="total-row__label">Total</span>
            <span class="total-row__amt">${{ number_format($booking->total_amount, 2) }}</span>
        </div>
    </div>

    <div class="placeholder">
        <div class="placeholder__icon">🍿</div>
        <div class="placeholder__title">Food &amp; Beverage Add-ons</div>
        <div class="placeholder__sub">This module is coming soon. Skip and proceed to payment.</div>
    </div>

</div>

<div class="bar">
    <button class="bar__back" id="btn-back">← Back to Seats</button>
    <div class="bar__right">
        <span class="bar__total">Total: <strong>${{ number_format($booking->total_amount, 2) }}</strong></span>
        <a href="{{ route('booking.payment') }}" class="bar__next">Proceed to Payment →</a>
    </div>
</div>

{{-- Confirm-back modal --}}
<div class="confirm-modal" id="confirm-modal">
    <div class="confirm-modal__box">
        <div class="confirm-modal__icon">⚠️</div>
        <div class="confirm-modal__title">Cancel this booking?</div>
        <div class="confirm-modal__sub">
            Going back will release your held seats and delete this booking.
            You will need to start the seat selection again.
        </div>
        <div class="confirm-modal__actions">
            <button class="confirm-modal__yes" id="confirm-yes">Yes, go back</button>
            <button class="confirm-modal__no"  id="confirm-no">Stay here</button>
        </div>
    </div>
</div>

<script>
(function () {
    /* ── Back button intercept ───────────────────────────── */
    var backBtn   = document.getElementById('btn-back');
    var modal     = document.getElementById('confirm-modal');
    var confirmYes = document.getElementById('confirm-yes');
    var confirmNo  = document.getElementById('confirm-no');
    var cancelUrl  = @json(route('booking.back-to-seats'));

    function showModal() { modal.classList.add('open'); }
    function hideModal() { modal.classList.remove('open'); }

    backBtn.addEventListener('click', showModal);
    confirmNo.addEventListener('click', hideModal);
    confirmYes.addEventListener('click', function () {
        window.location.href = cancelUrl;
    });

    // Browser back button — push a dummy state so popstate fires
    history.pushState({ page: 'fnb' }, '');
    window.addEventListener('popstate', function (e) {
        showModal();
        // Re-push so the user stays on page if they cancel
        history.pushState({ page: 'fnb' }, '');
    });

    /* ── 5-minute countdown timer ────────────────────────── */
    var expiresAt  = document.body.dataset.expiresAt;
    var timerEl    = document.getElementById('timer-display');
    var barFill    = document.getElementById('timer-bar');
    var totalSecs  = 5 * 60;

    function tick() {
        if (!expiresAt) return;
        var remaining = Math.max(0, Math.floor((new Date(expiresAt) - Date.now()) / 1000));
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;

        timerEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;

        var pct = (remaining / totalSecs) * 100;
        barFill.style.width = pct + '%';

        if (remaining <= 60) {
            timerEl.classList.add('timer-display--urgent');
            barFill.style.background = 'linear-gradient(135deg,#ef4444,#f97316)';
        }

        if (remaining === 0) {
            timerEl.textContent = 'Expired';
            setTimeout(function () {
                window.location.href = cancelUrl;
            }, 1500);
            return;
        }
        setTimeout(tick, 1000);
    }
    tick();

})();
</script>

</body>
</html>