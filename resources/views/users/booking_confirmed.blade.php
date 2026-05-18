{{--
    resources/views/users/booking_confirmed.blade.php
    Controller: PaymentController@confirmed
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed — CinemaX</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --green:#22c55e; --dark:#0a0a0a; --surface:#111; --border:rgba(255,255,255,0.08); --text:#f0f0f0; --muted:rgba(240,240,240,0.45); }
        body { background:var(--dark); color:var(--text); font-family:'DM Sans',sans-serif; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px; }

        .confirm-wrap { max-width:540px; width:100%; }

        /* Success badge */
        .confirm-badge { width:72px; height:72px; border-radius:50%; background:rgba(34,197,94,0.12); border:2px solid var(--green); display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 28px; box-shadow:0 0 30px rgba(34,197,94,0.25); }

        .confirm-title { font-family:'Space Grotesk',sans-serif; font-size:1.9rem; font-weight:800; text-align:center; margin-bottom:6px; }
        .confirm-sub { text-align:center; font-size:0.9rem; color:var(--muted); margin-bottom:32px; }
        .confirm-ref { text-align:center; font-size:0.78rem; color:var(--muted); margin-top:-20px; margin-bottom:32px; letter-spacing:0.06em; }
        .confirm-ref span { color:var(--green); font-weight:600; }

        /* Detail card */
        .detail-card { background:var(--surface); border:1px solid var(--border); padding:24px 28px; margin-bottom:24px; }
        .detail-card__label { font-size:0.68rem; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:var(--green); margin-bottom:16px; }
        .detail-row { display:flex; justify-content:space-between; align-items:baseline; font-size:0.88rem; margin-bottom:10px; }
        .detail-row:last-child { margin-bottom:0; }
        .detail-row__key { color:var(--muted); }
        .detail-row__val { font-weight:600; color:var(--text); text-align:right; }
        .divider { height:1px; background:var(--border); margin:14px 0; }

        /* Ticket chips */
        .tickets-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
        .t-chip { background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.25); color:var(--green); padding:5px 14px; font-size:0.8rem; font-weight:600; letter-spacing:0.05em; }

        /* Total */
        .total-row { display:flex; justify-content:space-between; align-items:baseline; margin-top:20px; padding-top:16px; border-top:1px solid var(--border); }
        .total-row__label { font-size:0.95rem; font-weight:700; }
        .total-row__amount { font-size:1.6rem; font-weight:800; color:var(--green); font-family:'Space Grotesk',sans-serif; }

        /* Actions */
        .actions { display:flex; flex-direction:column; gap:12px; }
        .btn-home { display:block; text-align:center; height:48px; line-height:48px; background:var(--green); color:#000; font-weight:700; font-size:0.9rem; letter-spacing:0.08em; text-decoration:none; transition:background 0.2s; }
        .btn-home:hover { background:#16a34a; }
        .btn-browse { display:block; text-align:center; height:48px; line-height:48px; background:none; border:1px solid var(--border); color:var(--muted); font-size:0.85rem; text-decoration:none; transition:border-color 0.2s, color 0.2s; }
        .btn-browse:hover { border-color:rgba(255,255,255,0.25); color:var(--text); }
    </style>
</head>
<body>

<div class="confirm-wrap">

    <div class="confirm-badge">✓</div>
    <h1 class="confirm-title">Booking Confirmed!</h1>
    <p class="confirm-sub">Your tickets are secured. Enjoy the show.</p>
    <p class="confirm-ref">Booking ref: <span>#{{ str_pad($booking->booking_id, 8, '0', STR_PAD_LEFT) }}</span></p>

    <div class="detail-card">
        <div class="detail-card__label">Booking Details</div>

        <div class="detail-row">
            <span class="detail-row__key">Movie</span>
            <span class="detail-row__val">{{ $movie?->movie_name ?? '—' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__key">Cinema</span>
            <span class="detail-row__val">{{ $cinema?->cinema_name ?? '—' }}</span>
        </div>
        @if($showtime)
        <div class="detail-row">
            <span class="detail-row__key">Date</span>
            <span class="detail-row__val">{{ \Carbon\Carbon::parse($showtime->start_time)->format('d M Y') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-row__key">Time</span>
            <span class="detail-row__val">{{ \Carbon\Carbon::parse($showtime->start_time)->format('h:i A') }}</span>
        </div>
        @endif

        <div class="divider"></div>

        <div class="detail-row__key" style="font-size:0.78rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:8px;">Seats</div>
        <div class="tickets-grid">
            @foreach($booking->tickets as $ticket)
                @if($ticket->seat)
                    <span class="t-chip">
                        {{ $ticket->seat->row_label }}{{ $ticket->seat->seat_number }}
                    </span>
                @endif
            @endforeach
        </div>

        <div class="total-row">
            <span class="total-row__label">Total Paid</span>
            <span class="total-row__amount">${{ number_format($booking->total_amount, 2) }}</span>
        </div>
    </div>

    <div class="actions">
        <a href="{{ route('home') }}" class="btn-home">BACK TO HOME</a>
        <a href="{{ route('home') }}" class="btn-browse">Browse More Movies</a>
    </div>

</div>

</body>
</html>