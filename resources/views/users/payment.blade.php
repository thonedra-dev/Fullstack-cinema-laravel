{{--
    resources/views/users/payment.blade.php
    Step 3 — Stripe Payment
    Controller: PaymentController@show
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — CinemaX</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green:   #22c55e;
            --purple:  #a855f7;
            --pink:    #ec4899;
            --grad:    linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            --dark:    #0a0a0a;
            --surface: #111;
            --border:  rgba(255,255,255,0.08);
            --text:    #f0f0f0;
            --muted:   rgba(240,240,240,0.45);
            --red:     #ef4444;
        }
        body { background: var(--dark); color: var(--text); font-family: 'DM Sans', sans-serif; min-height: 100vh; }

        /* ── Top nav ─────────────────────────────────────────── */
        .top-bar {
            padding: 18px 32px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand {
            display: flex; align-items: center; gap: 10px;
            font-family: 'Space Grotesk', sans-serif; font-size: 1.3rem; font-weight: 800;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; text-decoration: none;
        }
        .brand__icon { width: 28px; height: 28px; flex-shrink: 0; }

        /* ── Timer ───────────────────────────────────────────── */
        .timer-wrap { display: flex; align-items: center; gap: 10px; }
        .timer-label { font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); }
        .timer-display {
            font-family: 'Space Grotesk', sans-serif; font-size: 1.05rem; font-weight: 700;
            background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; min-width: 52px; text-align: right;
        }
        .timer-display--urgent {
            background: linear-gradient(135deg,#ef4444,#f97316) !important;
            -webkit-background-clip: text !important; background-clip: text !important;
        }
        .timer-bar-track { width: 120px; height: 3px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden; }
        .timer-bar-fill  { height: 100%; background: var(--grad); border-radius: 99px; transition: width 1s linear; }

        /* ── Steps ───────────────────────────────────────────── */
        .steps {
            display: flex; align-items: center; justify-content: center;
            padding: 24px 20px; background: var(--surface); border-bottom: 1px solid var(--border);
        }
        .step { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 500; color: var(--muted); }
        .step--active { color: var(--purple); }
        .step--done   { color: var(--text); }
        .step__num {
            width: 26px; height: 26px; border-radius: 50%; border: 1.5px solid currentColor;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.78rem; font-weight: 700; flex-shrink: 0;
        }
        .step--done .step__num { background: var(--grad); border-color: transparent; color: #fff; }
        .step-div { width: 40px; height: 1px; background: var(--border); margin: 0 10px; }

        /* ── Main grid ───────────────────────────────────────── */
        .shell {
            max-width: 960px; margin: 0 auto;
            padding: 36px 20px 80px;
            display: grid;
            grid-template-columns: 1fr 340px;
            grid-template-rows: auto auto;
            gap: 24px;
        }
        @media(max-width: 700px) { .shell { grid-template-columns: 1fr; } }

        /* ── Cards ───────────────────────────────────────────── */
        .card {
            background: var(--surface); border: 1px solid var(--border); padding: 24px;
        }
        .card__label {
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; margin-bottom: 18px;
            background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; display: inline-block;
        }

        /* ── Order summary card ──────────────────────────────── */
        .summary-inner { display: flex; gap: 20px; }
        .summary-poster {
            width: 90px; flex-shrink: 0;
            border-radius: 4px; overflow: hidden;
        }
        .summary-poster img { width: 100%; height: 130px; object-fit: cover; display: block; }
        .summary-poster__ph {
            width: 100%; height: 130px;
            background: rgba(168,85,247,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }
        .summary-info { flex: 1; min-width: 0; }
        .summary__movie { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
        .summary__meta  { display: flex; flex-wrap: wrap; gap: 10px; font-size: 0.8rem; color: var(--muted); margin-bottom: 14px; }
        .divider { height: 1px; background: var(--border); margin: 14px 0; }

        /* Ticket rows with seat icons */
        .ticket-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
        .ticket-row { display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem; }
        .ticket-row__left { display: flex; align-items: center; gap: 10px; }
        .ticket-row__price { font-weight: 700; color: var(--text); }

        /* Seat icon silhouette */
        .seat-icon {
            width: 22px; height: 20px; border-radius: 3px 3px 5px 5px;
            position: relative; flex-shrink: 0;
        }
        .seat-icon::before {
            content: ''; position: absolute;
            top: -5px; left: 1px; right: 1px; height: 7px;
            border-radius: 2px 2px 1px 1px; background: inherit; opacity: 0.6;
        }
        .seat-icon::after {
            content: ''; position: absolute;
            bottom: 0; left: -2px; right: -2px; height: 3px;
            border-radius: 2px; background: inherit; opacity: 0.4;
        }
        .seat-icon--standard { background: #4ade80; }
        .seat-icon--couple   { background: #f87171; }
        .seat-icon--premium  { background: #1a1a2e; outline: 1.5px solid #d4af37; outline-offset: -1px; }
        .seat-icon--family   { background: #f472b6; }

        .ticket-row__seat-label { font-weight: 600; color: var(--text); }
        .ticket-row__type       { font-size: 0.75rem; color: var(--muted); }

        .total-row { display: flex; justify-content: space-between; align-items: baseline; padding-top: 12px; border-top: 1px solid var(--border); }
        .total-row__label { font-size: 0.95rem; font-weight: 700; }
        .total-row__amt {
            font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 800;
            background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Customer card ───────────────────────────────────── */
        .customer-card { display: flex; align-items: center; gap: 18px; }
        .customer-avatar {
            width: 62px; height: 62px; border-radius: 50%; flex-shrink: 0;
            object-fit: cover;
            border: 2px solid rgba(168,85,247,0.4);
        }
        .customer-avatar-ph {
            width: 62px; height: 62px; border-radius: 50%; flex-shrink: 0;
            background: var(--grad); display: flex; align-items: center; justify-content: center;
            font-family: 'Space Grotesk', sans-serif; font-size: 1.4rem; font-weight: 800; color: #fff;
        }
        .customer-info { flex: 1; min-width: 0; }
        .customer-name { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
        .customer-email { font-size: 0.82rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .customer-badge {
            display: inline-flex; align-items: center; gap: 5px; margin-top: 6px;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3);
            color: var(--green); padding: 3px 10px; border-radius: 99px;
        }

        /* ── Stripe card ─────────────────────────────────────── */
        .stripe-label {
            display: block; font-size: 0.75rem; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted);
            margin-bottom: 10px;
        }
        #stripe-card-element {
            background: #0d0d0d; border: 1px solid rgba(255,255,255,0.12);
            padding: 14px 12px; transition: border-color 0.2s;
        }
        #stripe-card-element.StripeElement--focus  { border-color: rgba(168,85,247,0.5); }
        #stripe-card-element.StripeElement--invalid { border-color: var(--red); }
        #stripe-errors { color: var(--red); font-size: 0.82rem; min-height: 18px; margin: 10px 0; display: none; }
        #stripe-errors.visible { display: block; }

        #pay-btn {
            width: 100%; height: 52px; margin-top: 16px;
            background: var(--grad); color: #fff; border: none;
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem; font-weight: 700;
            letter-spacing: 0.08em; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: opacity 0.2s;
        }
        #pay-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        #pay-btn:hover:not(:disabled) { opacity: 0.88; }

        .pay-secure { font-size: 0.72rem; color: var(--muted); text-align: center; margin-top: 14px; }
        .pay-back {
            display: block; text-align: center; margin-top: 16px;
            font-size: 0.82rem; color: var(--muted); cursor: pointer;
            background: none; border: none; font-family: 'DM Sans', sans-serif;
            transition: color 0.2s; text-decoration: none;
        }
        .pay-back:hover { color: var(--text); }

        /* ── Confirm-back modal ──────────────────────────────── */
        .confirm-modal {
            position: fixed; inset: 0; background: rgba(0,0,0,0.88);
            z-index: 9999; display: none; align-items: center; justify-content: center;
        }
        .confirm-modal.open { display: flex; }
        .confirm-modal__box {
            background: #111; border: 1px solid rgba(255,255,255,0.12);
            padding: 36px 32px; max-width: 400px; width: 90%; text-align: center;
        }
        .confirm-modal__icon  { font-size: 2.2rem; margin-bottom: 14px; }
        .confirm-modal__title { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
        .confirm-modal__sub   { font-size: 0.86rem; color: var(--muted); line-height: 1.55; max-width: 300px; margin: 0 auto 26px; }
        .confirm-modal__actions { display: flex; gap: 12px; }
        .confirm-modal__yes {
            flex: 1; height: 44px; background: #ef4444; color: #fff;
            border: none; font-weight: 700; font-size: 0.88rem;
            font-family: 'DM Sans', sans-serif; cursor: pointer;
        }
        .confirm-modal__yes:hover { background: #dc2626; }
        .confirm-modal__no {
            flex: 1; height: 44px; background: none;
            border: 1px solid var(--border); color: var(--muted);
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; cursor: pointer;
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
        <div class="timer-bar-track"><div class="timer-bar-fill" id="timer-bar"></div></div>
        <span class="timer-display" id="timer-display">5:00</span>
    </div>
</div>

{{-- Steps --}}
<div class="steps">
    <div class="step step--done"><span class="step__num">✓</span><span>Select Seats</span></div>
    <div class="step-div"></div>
    <div class="step step--done"><span class="step__num">✓</span><span>F&amp;B Add-ons</span></div>
    <div class="step-div"></div>
    <div class="step step--active"><span class="step__num">3</span><span>Payment</span></div>
</div>

<div class="shell">

    {{-- Order Summary (top-left) --}}
    <div class="card" style="grid-column:1; grid-row:1;">
        <div class="card__label">Order Summary</div>
        <div class="summary-inner">
            <div class="summary-poster">
                @if($movie?->portrait_poster)
                    <img src="{{ asset('images/movies/' . $movie->portrait_poster) }}"
                         alt="{{ $movie->movie_name }}">
                @else
                    <div class="summary-poster__ph">🎬</div>
                @endif
            </div>
            <div class="summary-info">
                <div class="summary__movie">{{ $movie?->movie_name ?? '—' }}</div>
                <div class="summary__meta">
                    <span>📍 {{ $cinema?->cinema_name ?? '—' }}</span>
                    @if($theatre)<span>🎬 {{ $theatre->theatre_name }}</span>@endif
                    @if($showtime)
                        <span>📅 {{ \Carbon\Carbon::parse($showtime->start_time)->format('d M Y') }}</span>
                        <span>⏰ {{ \Carbon\Carbon::parse($showtime->start_time)->format('h:i A') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="ticket-list">
            @foreach($booking->tickets as $ticket)
                @if($ticket->seat)
                    @php $seatType = strtolower($ticket->seat->seat_type); @endphp
                    <div class="ticket-row">
                        <div class="ticket-row__left">
                            <div class="seat-icon seat-icon--{{ $seatType }}"></div>
                            <div>
                                <div class="ticket-row__seat-label">
                                    {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}
                                </div>
                                <div class="ticket-row__type">{{ ucfirst($ticket->seat->seat_type) }}</div>
                            </div>
                        </div>
                        <span class="ticket-row__price">${{ number_format($ticket->price_paid, 2) }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="total-row">
            <span class="total-row__label">Total</span>
            <span class="total-row__amt">${{ number_format($booking->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- Customer info (bottom-left) --}}
    <div class="card" style="grid-column:1; grid-row:2;">
        <div class="card__label">Booking For</div>
        <div class="customer-card">
            @if($customer?->avatar)
                <img src="{{ asset($customer->avatar) }}"
                     alt="{{ $customer->name }}"
                     class="customer-avatar">
            @else
                <div class="customer-avatar-ph">
                    {{ strtoupper(substr($customer?->name ?? 'U', 0, 1)) }}
                </div>
            @endif
            <div class="customer-info">
                <div class="customer-name">{{ $customer?->name ?? '—' }}</div>
                <div class="customer-email">{{ $customer?->email_address ?? '—' }}</div>
                @if($customer?->is_verified)
                    <span class="customer-badge">✓ Verified</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Stripe payment (right, spans both rows) --}}
    <div class="card" style="grid-column:2; grid-row:1 / span 2;">
        <div class="card__label">Payment Details</div>

        <label class="stripe-label">Card information</label>
        <div id="stripe-card-element"></div>
        <div id="stripe-errors" role="alert"></div>

        <button id="pay-btn" disabled>
            <span id="pay-btn-text">Pay ${{ number_format($booking->total_amount, 2) }}</span>
        </button>

        <p class="pay-secure">🔒 Payments secured by Stripe</p>
        <button class="pay-back" id="btn-back">← Back to Add-ons</button>
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
    'use strict';

    var cancelUrl  = @json(route('booking.back-to-seats'));
    var intentUrl  = @json(route('booking.payment.intent'));
    var confirmUrl = @json(route('booking.payment.confirm'));
    var csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
    var expiresAt  = document.body.dataset.expiresAt;
    var totalSecs  = 5 * 60;

    /* ── Confirm-back modal ─────────────────────────────── */
    var modal      = document.getElementById('confirm-modal');
    var confirmYes = document.getElementById('confirm-yes');
    var confirmNo  = document.getElementById('confirm-no');
    var backBtn    = document.getElementById('btn-back');

    function showModal() { modal.classList.add('open'); }
    function hideModal() { modal.classList.remove('open'); }

    backBtn.addEventListener('click', showModal);
    confirmNo.addEventListener('click', hideModal);
    confirmYes.addEventListener('click', function () {
        window.location.href = cancelUrl;
    });

    history.pushState({ page: 'payment' }, '');
    window.addEventListener('popstate', function () {
        showModal();
        history.pushState({ page: 'payment' }, '');
    });

    /* ── Timer ──────────────────────────────────────────── */
    var timerEl = document.getElementById('timer-display');
    var barFill = document.getElementById('timer-bar');

    function tick() {
        if (!expiresAt) return;
        var remaining = Math.max(0, Math.floor((new Date(expiresAt) - Date.now()) / 1000));
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;
        timerEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
        barFill.style.width = ((remaining / totalSecs) * 100) + '%';

        if (remaining <= 60) {
            timerEl.classList.add('timer-display--urgent');
            barFill.style.background = 'linear-gradient(135deg,#ef4444,#f97316)';
        }
        if (remaining === 0) {
            timerEl.textContent = 'Expired';
            setTimeout(function () { window.location.href = cancelUrl; }, 1500);
            return;
        }
        setTimeout(tick, 1000);
    }
    tick();

    /* ── Stripe ─────────────────────────────────────────── */
    var stripe      = Stripe(@json($stripeKey));
    var elements    = stripe.elements({ appearance: { theme: 'night' } });
    var cardElement = elements.create('card', {
        style: {
            base: {
                color: '#f0f0f0',
                fontFamily: '"DM Sans", sans-serif',
                fontSize: '15px',
                '::placeholder': { color: 'rgba(240,240,240,0.35)' },
            },
            invalid: { color: '#ef4444' },
        }
    });
    cardElement.mount('#stripe-card-element');

    var payBtn     = document.getElementById('pay-btn');
    var payBtnText = document.getElementById('pay-btn-text');
    var errorEl    = document.getElementById('stripe-errors');
    var clientSecret = null;

    function showError(msg)  { errorEl.textContent = msg; errorEl.classList.add('visible'); }
    function clearError()    { errorEl.textContent = '';  errorEl.classList.remove('visible'); }
    function setLoading(on)  {
        payBtn.disabled = on;
        payBtnText.textContent = on ? 'Processing…' : 'Pay ${{ number_format($booking->total_amount, 2) }}';
    }

    fetch(intentUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({}),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.clientSecret) { clientSecret = data.clientSecret; payBtn.disabled = false; }
        else showError(data.error || 'Could not initialise payment. Please refresh.');
    })
    .catch(function () { showError('Network error. Please refresh.'); });

    payBtn.addEventListener('click', function () {
        if (!clientSecret) return;
        clearError(); setLoading(true);

        stripe.confirmCardPayment(clientSecret, { payment_method: { card: cardElement } })
        .then(function (result) {
            if (result.error) { showError(result.error.message); setLoading(false); return; }
            if (result.paymentIntent.status === 'succeeded') {
                return fetch(confirmUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ payment_intent_id: result.paymentIntent.id }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.redirect) window.location.href = data.redirect;
                    else { showError(data.error || 'Confirmation failed. Contact support.'); setLoading(false); }
                });
            }
        })
        .catch(function () { showError('Unexpected error. Try again.'); setLoading(false); });
    });

})();
</script>

</body>
</html>