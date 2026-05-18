{{--
    resources/views/users/payment.blade.php
    Step 3 — Payment via Stripe Elements
    Controller: PaymentController@show

    Setup you must do once:
      1. composer require stripe/stripe-php
      2. In config/services.php add:
            'stripe' => [
                'key'            => env('STRIPE_PUBLISHABLE_KEY'),
                'secret'         => env('STRIPE_SECRET_KEY'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            ],
      3. In .env add STRIPE_PUBLISHABLE_KEY and STRIPE_SECRET_KEY
         (from your Stripe dashboard → Developers → API keys)
      4. That's it — this page and PaymentController handle the rest.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — CinemaX</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    {{-- Stripe.js — must load from Stripe's CDN for PCI compliance --}}
    <script src="https://js.stripe.com/v3/"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --green:#22c55e; --dark:#0a0a0a; --surface:#111; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:rgba(240,240,240,0.45); --red:#ef4444; }
        body { background:var(--dark); color:var(--text); font-family:'DM Sans',sans-serif; min-height:100vh; }

        /* Steps */
        .steps { display:flex; align-items:center; justify-content:center; padding:28px 20px; background:var(--surface); border-bottom:1px solid var(--border); }
        .step { display:flex; align-items:center; gap:8px; font-size:0.82rem; font-weight:500; color:var(--muted); }
        .step--active { color:var(--green); }
        .step--done { color:var(--text); }
        .step__num { width:26px; height:26px; border-radius:50%; border:1.5px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:0.78rem; font-weight:700; flex-shrink:0; }
        .step--done .step__num { background:var(--green); border-color:var(--green); color:#000; }
        .step-div { width:40px; height:1px; background:var(--border); margin:0 10px; }

        /* Layout */
        .shell { max-width:820px; margin:0 auto; padding:40px 20px 80px; display:grid; grid-template-columns:1fr 340px; gap:28px; }
        @media(max-width:680px){ .shell{ grid-template-columns:1fr; } }

        /* Card */
        .card { background:var(--surface); border:1px solid var(--border); padding:28px; }
        .card__label { font-size:0.7rem; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:var(--green); margin-bottom:18px; }

        /* Summary */
        .summary__movie { font-size:1.15rem; font-weight:700; margin-bottom:10px; }
        .summary__meta { display:flex; flex-wrap:wrap; gap:14px; font-size:0.83rem; color:var(--muted); margin-bottom:16px; }
        .summary__divider { height:1px; background:var(--border); margin:16px 0; }
        .summary__seats { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
        .ticket-row { display:flex; justify-content:space-between; font-size:0.85rem; }
        .ticket-row__seat { color:var(--text); }
        .ticket-row__price { color:var(--green); font-weight:600; }
        .summary__total-row { display:flex; justify-content:space-between; align-items:baseline; padding-top:14px; border-top:1px solid var(--border); }
        .summary__total-label { font-size:0.9rem; font-weight:600; }
        .summary__total-amount { font-size:1.5rem; font-weight:800; color:var(--green); }

        /* Stripe card element container */
        .stripe-wrap { margin-bottom:20px; }
        .stripe-wrap label { display:block; font-size:0.78rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); margin-bottom:10px; }
        #stripe-card-element {
            background:#0d0d0d;
            border:1px solid rgba(255,255,255,0.12);
            padding:14px 12px;
            border-radius:2px;
            transition:border-color 0.2s;
        }
        #stripe-card-element.StripeElement--focus { border-color:rgba(34,197,94,0.5); }
        #stripe-card-element.StripeElement--invalid { border-color:var(--red); }

        /* Error message */
        #stripe-errors { color:var(--red); font-size:0.82rem; min-height:20px; margin-bottom:16px; display:none; }
        #stripe-errors.visible { display:block; }

        /* Pay button */
        #pay-btn {
            width:100%; height:52px; background:var(--green); color:#000; border:none;
            font-family:'DM Sans',sans-serif; font-size:0.95rem; font-weight:700;
            letter-spacing:0.08em; cursor:pointer; transition:background 0.2s, opacity 0.2s;
            display:flex; align-items:center; justify-content:center; gap:10px;
        }
        #pay-btn:hover:not(:disabled) { background:#16a34a; }
        #pay-btn:disabled { opacity:0.55; cursor:not-allowed; }

        .pay-secure { font-size:0.72rem; color:var(--muted); text-align:center; margin-top:14px; }
        .pay-back { display:block; text-align:center; margin-top:18px; font-size:0.82rem; color:var(--muted); text-decoration:none; transition:color 0.2s; }
        .pay-back:hover { color:var(--text); }
    </style>
</head>
<body>

<div class="steps">
    <div class="step step--done"><span class="step__num">✓</span><span>Select Seats</span></div>
    <div class="step-div"></div>
    <div class="step step--done"><span class="step__num">✓</span><span>F&amp;B Add-ons</span></div>
    <div class="step-div"></div>
    <div class="step step--active"><span class="step__num">3</span><span>Payment</span></div>
</div>

<div class="shell">

    {{-- Left: Booking summary --}}
    <div class="card">
        <div class="card__label">Order Summary</div>
        <div class="summary__movie">{{ $movie?->movie_name ?? '—' }}</div>
        <div class="summary__meta">
            <span>📍 {{ $cinema?->cinema_name ?? '—' }}</span>
            @if($theatre) <span>🎬 {{ $theatre->theatre_name }}</span> @endif
            @if($showtime)
                <span>📅 {{ \Carbon\Carbon::parse($showtime->start_time)->format('d M Y') }}</span>
                <span>⏰ {{ \Carbon\Carbon::parse($showtime->start_time)->format('h:i A') }}</span>
            @endif
        </div>
        <div class="summary__divider"></div>
        <div class="summary__seats">
            @foreach($booking->tickets as $ticket)
                @if($ticket->seat)
                    <div class="ticket-row">
                        <span class="ticket-row__seat">
                            {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}
                            &nbsp;·&nbsp;{{ ucfirst($ticket->seat->seat_type) }}
                        </span>
                        <span class="ticket-row__price">
                            ${{ number_format($ticket->price_paid, 2) }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="summary__total-row">
            <span class="summary__total-label">Total</span>
            <span class="summary__total-amount">${{ number_format($booking->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- Right: Stripe payment form --}}
    <div class="card">
        <div class="card__label">Payment Details</div>

        <div class="stripe-wrap">
            <label>Card information</label>
            <div id="stripe-card-element"></div>
        </div>

        <div id="stripe-errors" role="alert"></div>

        <button id="pay-btn" disabled>
            <span id="pay-btn-text">Pay ${{ number_format($booking->total_amount, 2) }}</span>
        </button>

        <p class="pay-secure">🔒 Payments secured by Stripe</p>
        <a href="{{ route('booking.fnb') }}" class="pay-back">← Back to Add-ons</a>
    </div>

</div>

<script>
(function () {
    'use strict';

    var stripeKey     = @json($stripeKey);
    var intentUrl     = @json(route('booking.payment.intent'));
    var confirmUrl    = @json(route('booking.payment.confirm'));
    var csrfToken     = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var stripe        = Stripe(stripeKey);
    var elements      = stripe.elements({ appearance: { theme: 'night' } });
    var cardElement   = elements.create('card', {
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

    var payBtn      = document.getElementById('pay-btn');
    var payBtnText  = document.getElementById('pay-btn-text');
    var errorEl     = document.getElementById('stripe-errors');
    var clientSecret = null;

    function showError(msg) {
        errorEl.textContent  = msg;
        errorEl.classList.add('visible');
    }
    function clearError() {
        errorEl.textContent  = '';
        errorEl.classList.remove('visible');
    }
    function setLoading(loading) {
        payBtn.disabled  = loading;
        payBtnText.textContent = loading ? 'Processing…' : 'Pay ${{ number_format($booking->total_amount, 2) }}';
    }

    /* ── Step 1: Create PaymentIntent on page load ──────── */
    fetch(intentUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.clientSecret) {
            clientSecret    = data.clientSecret;
            payBtn.disabled = false;
        } else {
            showError(data.error || 'Could not initialise payment. Please refresh.');
        }
    })
    .catch(function () {
        showError('Network error. Please refresh and try again.');
    });

    /* ── Step 2: Pay button click ───────────────────────── */
    payBtn.addEventListener('click', function () {
        if (!clientSecret) return;
        clearError();
        setLoading(true);

        stripe.confirmCardPayment(clientSecret, {
            payment_method: { card: cardElement },
        })
        .then(function (result) {
            if (result.error) {
                showError(result.error.message);
                setLoading(false);
                return;
            }

            if (result.paymentIntent.status === 'succeeded') {
                /* ── Step 3: Tell server to finalise booking ── */
                return fetch(confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_intent_id: result.paymentIntent.id,
                    }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showError(data.error || 'Payment recorded but confirmation failed. Contact support.');
                        setLoading(false);
                    }
                });
            }
        })
        .catch(function () {
            showError('Unexpected error. Please try again.');
            setLoading(false);
        });
    });

})();
</script>

</body>
</html>