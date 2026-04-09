{{--
    resources/views/users/select_seats.blade.php
    ─────────────────────────────────────────────
    Seat selection page — cyberpunk cinema aesthetic.
    Controller: UserSeatSelectionController@index
    Data:
      $movie        – Movie model (movie_name, landscape_poster)
      $cinema       – cinema row (cinema_name)
      $theatreName  – string
      $theatreId    – int|null
      $date         – string Y-m-d
      $time         – string "07:00 PM"
      $seatRows     – Collection keyed by row_label (each row = Collection of seat objects)
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
</head>
<body class="ss-body">

{{-- ══════════════════════════════════════════════════════════
     TOP DIVISION: Landscape Poster Hero + Movie Info
══════════════════════════════════════════════════════════ --}}
<div class="ss-hero"
     style="background-image: url('{{ $movie && $movie->landscape_poster ? asset('images/movies/' . $movie->landscape_poster) : '' }}');">
    <div class="ss-hero-overlay"></div>

    <div class="ss-hero-content">
        {{-- 3-step progress --}}
        <div class="ss-steps">
            <div class="ss-step ss-step--active">
                <span class="ss-step-num">1</span>
                <span class="ss-step-text">Select Seats</span>
            </div>
            <div class="ss-step-divider"></div>
            <div class="ss-step">
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

        {{-- Session info banner --}}
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

{{-- ══════════════════════════════════════════════════════════
     SECOND DIVISION: Seat Selection
══════════════════════════════════════════════════════════ --}}
<div class="ss-main">

    {{-- Legend ──────────────────────────────────────────── --}}
    <div class="ss-legend">
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-standard"></div>
            <span>Standard</span>
        </div>
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-couple"></div>
            <span>Couple</span>
        </div>
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-premium"></div>
            <span>Premium</span>
        </div>
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-family"></div>
            <span>Family</span>
        </div>
        <div class="ss-legend-sep"></div>
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-selected"></div>
            <span>Selected</span>
        </div>
        <div class="ss-legend-item">
            <div class="ss-legend-seat ss-t-sold"></div>
            <span>Sold</span>
        </div>
    </div>

    {{-- Screen ───────────────────────────────────────────── --}}
    <div class="ss-screen-wrap">
        <div class="ss-screen-glow"></div>
        <div class="ss-screen"></div>
        <p class="ss-screen-label">SCREEN</p>
    </div>

    {{-- Seat map ─────────────────────────────────────────── --}}
    @if ($seatRows->isEmpty())
        <div class="ss-no-seats">
            <span>No seat data found for this theatre.</span>
        </div>
    @else
        <div class="ss-seat-map">
            @foreach ($seatRows as $rowLabel => $seats)
                <div class="ss-seat-row">
                    <div class="ss-row-label">{{ $rowLabel }}</div>

                    <div class="ss-row-seats">
                        @php
                            $seatList = $seats->values();
                            $i = 0;
                            $total = $seatList->count();
                        @endphp

                        @while ($i < $total)
                            @php
                                $seat = $seatList[$i];
                                $type = strtolower($seat->seat_type); // standard|couple|premium|family
                                $isSold = $seat->status === 'sold';
                            @endphp

                            @if ($type === 'couple' && $i + 1 < $total && strtolower($seatList[$i + 1]->seat_type) === 'couple')
                                {{-- Couple seats: render as a pair --}}
                                @php $seat2 = $seatList[$i + 1]; $isSold2 = $seat2->status === 'sold'; @endphp
                                <div class="ss-couple-pair">
                                    @if ($isSold)
                                        <div class="ss-seat ss-seat--couple ss-seat--sold">✕</div>
                                    @else
                                        <div class="ss-seat ss-seat--couple ss-seat--available"
                                             data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                             data-type="couple">{{ $seat->seat_number }}</div>
                                    @endif
                                    @if ($isSold2)
                                        <div class="ss-seat ss-seat--couple ss-seat--sold">✕</div>
                                    @else
                                        <div class="ss-seat ss-seat--couple ss-seat--available"
                                             data-seat="{{ $rowLabel }}{{ $seat2->seat_number }}"
                                             data-type="couple">{{ $seat2->seat_number }}</div>
                                    @endif
                                </div>
                                @php $i += 2; @endphp
                            @elseif ($type === 'premium')
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--premium ss-seat--lg ss-seat--sold">✕</div>
                                @else
                                    <div class="ss-seat ss-seat--premium ss-seat--lg ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                         data-type="premium">{{ $seat->seat_number }}</div>
                                @endif
                                @php $i++; @endphp
                            @elseif ($type === 'family')
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--family ss-seat--lg ss-seat--sold">✕</div>
                                @else
                                    <div class="ss-seat ss-seat--family ss-seat--lg ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
                                         data-type="family">{{ $seat->seat_number }}</div>
                                @endif
                                @php $i++; @endphp
                            @else
                                {{-- Standard --}}
                                @if ($isSold)
                                    <div class="ss-seat ss-seat--standard ss-seat--sold">✕</div>
                                @else
                                    <div class="ss-seat ss-seat--standard ss-seat--available"
                                         data-seat="{{ $rowLabel }}{{ $seat->seat_number }}"
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

</div>{{-- /.ss-main --}}

{{-- ══════════════════════════════════════════════════════════
     FLOATING BOTTOM BAR
══════════════════════════════════════════════════════════ --}}
<div class="ss-bottom-bar">
    <div class="ss-bottom-info">
        <span class="ss-bottom-count"><span id="ss-selected-count">0</span> seat(s) selected</span>
        <span class="ss-bottom-seats" id="ss-selected-list">—</span>
    </div>
    <button class="ss-btn-next" id="ss-btn-next" disabled>
        Proceed to Add-ons <span class="ss-btn-arrow">→</span>
    </button>
</div>

</body>
</html>