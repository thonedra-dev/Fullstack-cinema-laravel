{{--
    resources/views/staff/view_seats.blade.php
    ─────────────────────────────────────────
    Shared Read-Only Real-Time Seat Monitoring Interface.
    Controller: StaffSeatMonitoringController@index
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Monitor — {{ $movie?->movie_name ?? 'Showtime Grid' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/view_seats.css', 'resources/js/view_seats.js'])

    {{-- ══ ROLE-DRIVEN ACCENT VARIABLE INJECTION ══════════════════
         Kept inline in blade because it depends on $role at render time.
         This does NOT affect middleware — it is purely presentational.
    ════════════════════════════════════════════════════════════════ --}}
    <style>
        @if($role === 'manager')
            :root {
                --staff-accent:           #22c55e;
                --staff-accent-rgb:       34, 197, 94;
                --staff-glow:             0 0 25px rgba(34, 197, 94, 0.45);
                --staff-border:           rgba(34, 197, 94, 0.24);
                --staff-gradient:         linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                --staff-btn-padding-border: #15803d;
            }
        @else
            :root {
                --staff-accent:           #a855f7;
                --staff-accent-rgb:       168, 85, 247;
                --staff-glow:             0 0 25px rgba(168, 85, 247, 0.45);
                --staff-border:           rgba(168, 85, 247, 0.24);
                --staff-gradient:         linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
                --staff-btn-padding-border: #ec4899;
            }
        @endif
    </style>
</head>
<body>

    <nav class="sm-navbar">
        <div class="sm-navbar__brand">
            <span>🎬</span>
            <span>CinemaX Secure Staff Portal</span>
        </div>
        <div class="sm-navbar__indicator">
            Live Monitoring Active
        </div>
    </nav>

    <main class="sm-container">

        {{-- ── Meta Card ──────────────────────────────────────────── --}}
        <section class="sm-meta-card">
            <div>
                <p class="sm-meta-item__label">Feature Presentation</p>
                <p class="sm-meta-item__value sm-meta-item__value--highlight">{{ $movie?->movie_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="sm-meta-item__label">Cinema Complex</p>
                <p class="sm-meta-item__value">{{ $cinemaName }}</p>
            </div>
            <div>
                <p class="sm-meta-item__label">Theatre / Hall</p>
                <p class="sm-meta-item__value">{{ $theatreName }} (Hall {{ $hallId }})</p>
            </div>
            <div>
                <p class="sm-meta-item__label">Date Block</p>
                <p class="sm-meta-item__value">{{ $date }}</p>
            </div>
            <div>
                <p class="sm-meta-item__label">Session Time</p>
                <p class="sm-meta-item__value">{{ $time }}</p>
            </div>
        </section>

        {{-- ── Stats Grid ─────────────────────────────────────────── --}}
        <section class="sm-stats-grid">
            <div class="sm-stat-box sm-stat-box--total">
                <p class="sm-stat-box__num">{{ $totalCount }}</p>
                <p class="sm-stat-box__label">Total Capacity</p>
            </div>
            <div class="sm-stat-box sm-stat-box--available">
                <p class="sm-stat-box__num">{{ $availableCount }}</p>
                <p class="sm-stat-box__label">Available Inventory</p>
            </div>
            <div class="sm-stat-box sm-stat-box--pending">
                <p class="sm-stat-box__num">{{ $pendingCount }}</p>
                <p class="sm-stat-box__label">Held / Pending</p>
            </div>
            <div class="sm-stat-box sm-stat-box--sold">
                <p class="sm-stat-box__num">{{ $soldCount }}</p>
                <p class="sm-stat-box__label">Sold / Confirmed</p>
            </div>
        </section>

        {{-- ── Arena ──────────────────────────────────────────────── --}}
        <section class="sm-arena">

            {{-- Screen --}}
            <div class="sm-screen-container">
                <div class="sm-screen-curve"></div>
                <div class="sm-screen-glow"></div>
                <div class="sm-screen-label">Front Screen Axis</div>
            </div>

            {{-- Legend --}}
            <div class="sm-legend">
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--standard"></div>
                    <span>Standard</span>
                </div>
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--couple"></div>
                    <span>Couple</span>
                </div>
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--premium"></div>
                    <span>Premium</span>
                </div>
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--family"></div>
                    <span>Family</span>
                </div>
                <div class="sm-legend-sep"></div>
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--pending"></div>
                    <span>Held</span>
                </div>
                <div class="sm-legend-item">
                    <div class="sm-legend-swatch sm-legend-swatch--sold"></div>
                    <span>Sold</span>
                </div>
            </div>

            {{-- Seat Map --}}
            <div class="sm-grid">
                @forelse($seatRows as $rowLabel => $seats)
                    <div class="sm-row">
                        <div class="sm-row-label">{{ $rowLabel }}</div>

                        <div class="sm-seats-row-wrap">
                            @php
                                $seatList = $seats->values();
                                $i        = 0;
                                $total    = $seatList->count();
                            @endphp

                            @while ($i < $total)
                                @php
                                    $seat   = $seatList[$i];
                                    $type   = strtolower($seat->seat_type);
                                    $status = $seat->status; // available | pending | sold
                                @endphp

                                {{-- ── COUPLE — pair wrapper ── --}}
                                @if ($type === 'couple' && $i + 1 < $total && strtolower($seatList[$i + 1]->seat_type) === 'couple')
                                    @php
                                        $seat2   = $seatList[$i + 1];
                                        $status2 = $seat2->status;
                                    @endphp
                                    <div class="sm-couple-pair">
                                        <div class="sm-seat sm-seat--couple sm-seat--{{ $status }}"
                                             title="Seat {{ $rowLabel }}{{ $seat->seat_number }} · {{ ucfirst($type) }} · {{ ucfirst($status) }}">
                                            {{ $seat->seat_number }}
                                        </div>
                                        <div class="sm-seat sm-seat--couple sm-seat--{{ $status2 }}"
                                             title="Seat {{ $rowLabel }}{{ $seat2->seat_number }} · {{ ucfirst($type) }} · {{ ucfirst($status2) }}">
                                            {{ $seat2->seat_number }}
                                        </div>
                                    </div>
                                    @php $i += 2; @endphp

                                {{-- ── PREMIUM ── --}}
                                @elseif ($type === 'premium')
                                    <div class="sm-seat sm-seat--premium sm-seat--{{ $status }}"
                                         title="Seat {{ $rowLabel }}{{ $seat->seat_number }} · Premium · {{ ucfirst($status) }}">
                                        {{ $seat->seat_number }}
                                    </div>
                                    @php $i++; @endphp

                                {{-- ── FAMILY ── --}}
                                @elseif ($type === 'family')
                                    <div class="sm-seat sm-seat--family sm-seat--{{ $status }}"
                                         title="Seat {{ $rowLabel }}{{ $seat->seat_number }} · Family · {{ ucfirst($status) }}">
                                        {{ $seat->seat_number }}
                                    </div>
                                    @php $i++; @endphp

                                {{-- ── STANDARD (default) ── --}}
                                @else
                                    <div class="sm-seat sm-seat--standard sm-seat--{{ $status }}"
                                         title="Seat {{ $rowLabel }}{{ $seat->seat_number }} · Standard · {{ ucfirst($status) }}">
                                        {{ $seat->seat_number }}
                                    </div>
                                    @php $i++; @endphp
                                @endif

                            @endwhile
                        </div>

                        <div class="sm-row-label">{{ $rowLabel }}</div>
                    </div>
                @empty
                    <div style="text-align:center; color:rgba(240,238,255,0.4); padding:40px 0;">
                        No physical seat configuration metrics compiled for this structural class.
                    </div>
                @endforelse
            </div>

            <div class="sm-border-decoration">
                <div class="sm-decoration-badge">Real-Time Telemetry Feed</div>
            </div>

        </section>

    </main>

    <footer class="sm-footer">
        @if($role === 'manager')
            [ Manager Layout ] — CinemaX Theatre Operations Monitoring Matrix
        @elseif($role === 'supervisor')
            [ Supervisor Layout ] — CinemaX Global System Seating Control Panel
        @else
            [ Guest ] — Access Restricted
        @endif
    </footer>

    {{-- Tooltip element moved by JS on pending seat hover --}}
    <div id="sm-tooltip" style="
        display: none;
        position: fixed;
        background: #1e1e30;
        border: 1px solid rgba(234,179,8,0.4);
        color: #fef08a;
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 6px;
        pointer-events: none;
        z-index: 9999;
        white-space: nowrap;
    "></div>

</body>
</html>