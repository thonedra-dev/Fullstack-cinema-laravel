{{--
    resources/views/users/select_seats.blade.php
    Controller: UserSeatSelectionController@index
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats — {{ $movie?->movie_name ?? 'CinemaX' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/select_seats.css', 'resources/js/select_seats.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="ss-body"
      data-auth="{{ auth('customer')->check() ? 'true' : 'false' }}"
      data-login-url="{{ route('users.login') }}"
      data-movie-id="{{ $movie?->movie_id ?? '' }}"
      data-cinema-id="{{ $cinema?->cinema_id ?? '' }}"
      data-hall-id="{{ $hallId ?? '' }}"
      data-showtime-id="{{ $showtimeId ?? '' }}"
      data-date="{{ $date }}"
      data-time="{{ $time }}"
      data-cart-url="{{ route('booking.cart') }}"
      data-current-url="{{ url()->full() }}"
>

{{-- ══ Hero ═══════════════════════════════════════════════════ --}}
<div class="ss-hero"
     style="background-image: url('{{ $movie && $movie->landscape_poster ? asset('images/movies/' . $movie->landscape_poster) : '' }}');">
    <div class="ss-hero-overlay"></div>
    <div class="ss-hero-content">
        <div class="ss-steps">
            <div class="ss-step ss-step--active">
                <span class="ss-step-num">1</span>
                <span class="ss-step-text">Select Seats</span>
            </div>
            <div class="ss-step-divider"></div>
            <div class="ss-step ss-step--inactive">
                <span class="ss-step-num">2</span>
                <span class="ss-step-text">F&amp;B Add-ons</span>
            </div>
            <div class="ss-step-divider"></div>
            <div class="ss-step">
                <span class="ss-step-num">3</span>
                <span class="ss-step-text">Payment</span>
            </div>
        </div>
        <h1 class="ss-movie-title">{{ $movie?->movie_name ?? 'Unknown Movie' }}</h1>
        <div class="ss-info-banner">
            <div class="ss-info-item">
                <span class="ss-info-icon">📍</span>
                <span>{{ $cinema?->cinema_name ?? 'Cinema' }}</span>
            </div>
            <div class="ss-info-sep"></div>
            <div class="ss-info-item">
                <span class="ss-info-icon">🎬</span>
                <span>{{ $theatreName }}</span>
            </div>
            <div class="ss-info-sep"></div>
            <div class="ss-info-item">
                <span class="ss-info-icon">📅</span>
                <span>{{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}</span>
            </div>
            <div class="ss-info-sep"></div>
            <div class="ss-info-item">
                <span class="ss-info-icon">⏰</span>
                <span>{{ $time }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ══ Seat Map ════════════════════════════════════════════════ --}}
<div class="ss-main">

    {{-- Pending seats notice banner (shown only when relevant) --}}
    @if ($hasPendingSeats)
        <div class="ss-pending-notice">
            <span class="ss-pending-notice__dot"></span>
            <span>
                Some seats are temporarily held by other customers.
                They will be released automatically if not purchased within 5 minutes.
            </span>
        </div>
    @endif

    {{-- Legend --}}
    <div class="ss-legend">
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-standard"></div><span>Standard</span></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-couple"></div><span>Couple</span></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-premium"></div><span>Premium</span></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-family"></div><span>Family</span></div>
        <div class="ss-legend-sep"></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-selected"></div><span>Selected</span></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-pending"></div><span>Held</span></div>
        <div class="ss-legend-item"><div class="ss-legend-seat ss-t-sold"></div><span>Sold</span></div>
    </div>

    {{-- Screen --}}
    <div class="ss-screen-wrap">
        <div class="ss-screen-glow"></div>
        <div class="ss-screen"></div>
        <p class="ss-screen-label">SCREEN</p>
    </div>

    @if ($seatRows->isEmpty())
        <div class="ss-no-seats"><span>No seat data found for this theatre.</span></div>
    @else
        <div class="ss-seat-map">
            @foreach ($seatRows as $rowLabel => $seats)
                <div class="ss-seat-row">
                    <div class="ss-row-label">{{ $rowLabel }}</div>
                    <div class="ss-row-seats">
                        @php
                            $seatList = $seats->values();
                            $i        = 0;
                            $total    = $seatList->count();
                        @endphp

                        @while ($i < $total)
                            @php
                                $seat      = $seatList[$i];
                                $type      = strtolower($seat->seat_type);
                                $isSold    = $seat->status === 'sold';
                                $isPending = $seat->status === 'pending';
                            @endphp

                            {{-- ── COUPLE ── --}}
                            @if ($type === 'couple' && $i + 1 < $total && strtolower($seatList[$i + 1]->seat_type) === 'couple')
                                @php
                                    $seat2      = $seatList[$i + 1];
                                    $isSold2    = $seat2->status === 'sold';
                                    $isPending2 = $seat2->status === 'pending';
                                @endphp
                                <div class="ss-couple-pair">
                                    @if ($isSold)
                                        <div class="ss-seat ss-seat--couple ss-seat--sold">✕</div>
                                    @elseif ($isPending)
                                        <div class="ss-seat ss-seat--couple ss-seat--pending"
                                             data-tooltip="Held by another customer">⏳</div>
                                    @else
                                        <div class="ss-seat ss-seat--couple ss-seat--available"
                                             data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                             data-seat-id="{{ $seat->seat_id }}"
                                             data-type="couple">{{ $seat->seat_number }}</div>
                                    @endif

                                    @if ($isSold2)
                                        <div class="ss-seat ss-seat--couple ss-seat--sold">✕</div>
                                    @elseif ($isPending2)
                                        <div class="ss-seat ss-seat--couple ss-seat--pending"
                                             data-tooltip="Held by another customer">⏳</div>
                                    @else
                                        <div class="ss-seat ss-seat--couple ss-seat--available"
                                             data-seat="{{ $rowLabel }}{{ $seat2->seat_number }}"
                                             data-seat-id="{{ $seat2->seat_id }}"
                                             data-type="couple">{{ $seat2->seat_number }}</div>
                                    @endif
                                </div>
                                @php $i += 2; @endphp

                            {{-- ── PREMIUM ── --}}
                            @elseif ($type === 'premium')
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--premium ss-seat--lg ss-seat--sold">✕</div>
                                @elseif ($isPending)
                                    <div class="ss-seat ss-seat--premium ss-seat--lg ss-seat--pending"
                                         data-tooltip="Held by another customer">⏳</div>
                                @else
                                    <div class="ss-seat ss-seat--premium ss-seat--lg ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                         data-seat-id="{{ $seat->seat_id }}"
                                         data-type="premium">{{ $seat->seat_number }}</div>
                                @endif
                                @php $i++; @endphp

                            {{-- ── FAMILY ── --}}
                            @elseif ($type === 'family')
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--family ss-seat--lg ss-seat--sold">✕</div>
                                @elseif ($isPending)
                                    <div class="ss-seat ss-seat--family ss-seat--lg ss-seat--pending"
                                         data-tooltip="Held by another customer">⏳</div>
                                @else
                                    <div class="ss-seat ss-seat--family ss-seat--lg ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                         data-seat-id="{{ $seat->seat_id }}"
                                         data-type="family">{{ $seat->seat_number }}</div>
                                @endif
                                @php $i++; @endphp

                            {{-- ── STANDARD ── --}}
                            @else
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--standard ss-seat--sold">✕</div>
                                @elseif ($isPending)
                                    <div class="ss-seat ss-seat--standard ss-seat--pending"
                                         data-tooltip="Held by another customer">⏳</div>
                                @else
                                    <div class="ss-seat ss-seat--standard ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                         data-seat-id="{{ $seat->seat_id }}"
                                         data-type="standard">{{ $seat->seat_number }}</div>
                                @endif
                                @php $i++; @endphp
                            @endif
                        @endwhile
                    </div>
                    <div class="ss-row-label">{{ $rowLabel }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ══ Hidden booking form (submitted by JS) ════════════════ --}}
<form id="ss-booking-form" action="{{ route('booking.cart') }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="movie_id"          value="{{ $movie?->movie_id ?? '' }}">
    <input type="hidden" name="cinema_id"          value="{{ $cinema?->cinema_id ?? '' }}">
    <input type="hidden" name="hall_id"            value="{{ $hallId ?? '' }}">
    <input type="hidden" name="showtime_id"        value="{{ $showtimeId ?? '' }}">
    <input type="hidden" name="seat_selection_url" value="{{ url()->full() }}">
</form>

{{-- ══ Floating bottom bar ════════════════════════════════════ --}}
<div class="ss-bottom-bar">
    <div class="ss-bottom-info">
        <span class="ss-bottom-count">
            <span id="ss-selected-count">0</span> seat(s) selected
        </span>
        <span class="ss-bottom-seats" id="ss-selected-list">—</span>
    </div>
    <button class="ss-btn-next" id="ss-btn-next" disabled>
        Proceed to Add-ons <span class="ss-btn-arrow">→</span>
    </button>
</div>

{{-- ══ Auth gate modal ════════════════════════════════════════ --}}
<div id="ss-auth-modal" class="ss-auth-modal" style="display:none;">
    <div class="ss-auth-modal__box">
        <p class="ss-auth-modal__title">Sign in required</p>
        <p class="ss-auth-modal__sub">
            Your seat selection will be saved. Sign in to continue booking.
        </p>
        <button id="ss-auth-confirm" class="ss-auth-modal__confirm">SIGN IN</button>
        <button id="ss-auth-cancel"  class="ss-auth-modal__cancel">Cancel</button>
    </div>
</div>

{{-- Pending seat tooltip (moved by JS on hover) --}}
<div id="ss-tooltip" class="ss-tooltip" style="display:none;"></div>

</body>
</html>