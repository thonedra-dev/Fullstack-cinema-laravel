{{--
    resources/views/users/fnb.blade.php
    Step 2 — F&B Add-ons (placeholder, module coming later)
    Controller: BookingController@fnb
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add-ons — CinemaX</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --green:#22c55e; --dark:#0a0a0a; --surface:#111; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:rgba(240,240,240,0.45); }
        body { background:var(--dark); color:var(--text); font-family:'DM Sans',sans-serif; min-height:100vh; }

        /* Steps */
        .steps { display:flex; align-items:center; justify-content:center; padding:28px 20px; background:var(--surface); border-bottom:1px solid var(--border); gap:0; }
        .step { display:flex; align-items:center; gap:8px; font-size:0.82rem; font-weight:500; color:var(--muted); }
        .step--active { color:var(--green); }
        .step--done { color:var(--text); }
        .step__num { width:26px; height:26px; border-radius:50%; border:1.5px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:0.78rem; font-weight:700; flex-shrink:0; }
        .step--done .step__num { background:var(--green); border-color:var(--green); color:#000; }
        .step-div { width:40px; height:1px; background:var(--border); margin:0 10px; }

        /* Layout */
        .shell { max-width:780px; margin:0 auto; padding:40px 20px 120px; }

        /* Summary */
        .summary { background:var(--surface); border:1px solid var(--border); padding:24px 28px; margin-bottom:28px; }
        .summary__label { font-size:0.7rem; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:var(--green); margin-bottom:14px; }
        .summary__movie { font-size:1.2rem; font-weight:700; margin-bottom:10px; }
        .summary__meta { display:flex; flex-wrap:wrap; gap:18px; font-size:0.84rem; color:var(--muted); margin-bottom:14px; }
        .summary__divider { height:1px; background:var(--border); margin:16px 0; }
        .summary__seats { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .seat-chip { background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.28); color:var(--green); padding:4px 12px; font-size:0.78rem; font-weight:600; }
        .summary__total { font-size:1rem; font-weight:700; }
        .summary__total span { color:var(--green); font-size:1.25rem; }

        /* Placeholder */
        .placeholder { background:var(--surface); border:1px solid var(--border); padding:64px 28px; text-align:center; }
        .placeholder__icon { font-size:3rem; margin-bottom:16px; }
        .placeholder__title { font-size:1.05rem; font-weight:700; margin-bottom:8px; }
        .placeholder__sub { font-size:0.85rem; color:var(--muted); max-width:340px; margin:0 auto; line-height:1.6; }

        /* Bottom bar */
        .bar { position:fixed; bottom:0; left:0; right:0; background:#0d0d0d; border-top:1px solid var(--border); padding:14px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; z-index:100; }
        .bar__back { background:none; border:1px solid var(--border); color:var(--muted); padding:0 24px; height:44px; font-family:'DM Sans',sans-serif; font-size:0.85rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; transition:border-color 0.2s,color 0.2s; }
        .bar__back:hover { border-color:rgba(255,255,255,0.3); color:var(--text); }
        .bar__right { display:flex; align-items:center; gap:20px; }
        .bar__total { font-size:0.82rem; color:var(--muted); }
        .bar__total strong { color:var(--text); }
        .bar__next { background:var(--green); color:#000; border:none; padding:0 28px; height:44px; font-family:'DM Sans',sans-serif; font-size:0.88rem; font-weight:700; letter-spacing:0.06em; cursor:pointer; transition:background 0.2s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .bar__next:hover { background:#16a34a; }
    </style>
</head>
<body>

<div class="steps">
    <div class="step step--done">
        <span class="step__num">✓</span><span>Select Seats</span>
    </div>
    <div class="step-div"></div>
    <div class="step step--active">
        <span class="step__num">2</span><span>F&amp;B Add-ons</span>
    </div>
    <div class="step-div"></div>
    <div class="step">
        <span class="step__num">3</span><span>Payment</span>
    </div>
</div>

<div class="shell">

    <div class="summary">
        <div class="summary__label">Your Booking</div>
        <div class="summary__movie">{{ $movie?->movie_name ?? '—' }}</div>
        <div class="summary__meta">
            <span>📍 {{ $cinema?->cinema_name ?? '—' }}</span>
            <span>🎬 {{ $theatre?->theatre_name ?? '—' }}</span>
            @if($showtime)
                <span>📅 {{ \Carbon\Carbon::parse($showtime->start_time)->format('d M Y') }}</span>
                <span>⏰ {{ \Carbon\Carbon::parse($showtime->start_time)->format('h:i A') }}</span>
            @endif
        </div>
        <div class="summary__divider"></div>
        <div class="summary__seats">
            @foreach($booking->tickets as $ticket)
                @if($ticket->seat)
                    <span class="seat-chip">
                        {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}
                        &nbsp;·&nbsp;{{ ucfirst($ticket->seat->seat_type) }}
                    </span>
                @endif
            @endforeach
        </div>
        <div class="summary__total">
            Seats total: <span>${{ number_format($booking->total_amount, 2) }}</span>
        </div>
    </div>

    <div class="placeholder">
        <div class="placeholder__icon">🍿</div>
        <div class="placeholder__title">Food &amp; Beverage Add-ons</div>
        <div class="placeholder__sub">
            This module is coming soon. Skip this step and proceed directly to payment.
        </div>
    </div>

</div>

<div class="bar">
    <a href="{{ route('booking.back-to-seats') }}" class="bar__back">← Back to Seats</a>
    <div class="bar__right">
        <span class="bar__total">Total: <strong>${{ number_format($booking->total_amount, 2) }}</strong></span>
        <a href="{{ route('booking.payment') }}" class="bar__next">Proceed to Payment →</a>
    </div>
</div>

</body>
</html>