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
    
    <style>
        /* ══ ROLE-DRIVEN ACCENT PROFILE VARIABLE INJECTION ════════ */
        @if($role === 'manager')
            :root {
                --staff-accent: #22c55e;
                --staff-accent-rgb: 34, 197, 94;
                --staff-glow: 0 0 25px rgba(34, 197, 94, 0.45);
                --staff-border: rgba(34, 197, 94, 0.24);
                --staff-gradient: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                --staff-btn-padding-border: #15803d;
            }
        @else
            :root {
                --staff-accent: #a855f7;
                --staff-accent-rgb: 168, 85, 247;
                --staff-glow: 0 0 25px rgba(168, 85, 247, 0.45);
                --staff-border: rgba(168, 85, 247, 0.24);
                --staff-gradient: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
                --staff-btn-padding-border: #ec4899;
            }
        @endif

        /* ══ SEAT MAP LAYOUT BASE STYLES ══════════════════════════ */
        *, *::before, *::after { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }

        body {
            background-color: #07070f;
            color: #f0eeff;
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Staff Navigation Band */
        .sm-navbar {
            background: #0e0e1a;
            border-bottom: 1px solid var(--staff-border);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        .sm-navbar__brand {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sm-navbar__indicator {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--staff-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--staff-accent);
            box-shadow: var(--staff-glow);
        }

        /* Core Framework Wrapper */
        .sm-container {
            max-width: 1200px;
            width: 100%;
            margin: 32px auto;
            padding: 0 24px;
            flex: 1;
        }

        /* Metadata Block Layout */
        .sm-meta-card {
            background: #0e0e1a;
            border: 1px solid rgba(255,255,255,0.05);
            border-left: 4px solid var(--staff-accent);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: 2fr repeat(4, 1fr);
            gap: 20px;
            align-items: center;
        }

        .sm-meta-item__label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(240,238,255,0.4);
            margin-bottom: 4px;
        }

        .sm-meta-item__value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        .sm-meta-item__value--highlight {
            color: var(--staff-accent);
            font-weight: 700;
        }

        /* Live Monitoring Stats Dashboard Grid */
        .sm-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 40px;
        }

        .sm-stat-box {
            background: #111122;
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .sm-stat-box__num {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .sm-stat-box__label {
            font-size: 0.75rem;
            color: rgba(240,238,255,0.5);
            font-weight: 500;
        }

        .sm-stat-box--total { border-top: 3px solid #3b82f6; .sm-stat-box__num { color: #3b82f6; } }
        .sm-stat-box--available { border-top: 3px solid #22c55e; .sm-stat-box__num { color: #22c55e; } }
        .sm-stat-box--pending { border-top: 3px solid #eab308; .sm-stat-box__num { color: #eab308; } }
        .sm-stat-box--sold { border-top: 3px solid #ef4444; .sm-stat-box__num { color: #ef4444; } }

        /* The Modular Seating Arena */
        .sm-arena {
            background: #0e0e1a;
            border: 1px solid rgba(168,85,247,0.05);
            border-radius: 16px;
            padding: 60px 40px;
            box-shadow: inset 0 0 40px rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Screen Glow & Projection Elements */
        .sm-screen-container {
            width: 80%;
            max-width: 650px;
            perspective: 300px;
            margin-bottom: 60px;
            text-align: center;
        }

        .sm-screen-curve {
            background: #1a1a2e;
            height: 8px;
            border-radius: 50%;
            box-shadow: 0 2px 20px 4px var(--staff-accent);
        }

        .sm-screen-glow {
            margin-top: 12px;
            height: 45px;
            background: linear-gradient(to bottom, rgba(var(--staff-accent-rgb), 0.18), transparent);
            filter: blur(8px);
            border-radius: 4px;
        }

        .sm-screen-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--staff-accent);
            font-weight: 700;
            margin-top: -30px;
            opacity: 0.8;
        }

        /* Pure Seating Layout Grid Framework */
        .sm-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 900px;
        }

        .sm-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .sm-row-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--staff-accent);
            width: 24px;
            text-align: center;
            opacity: 0.6;
        }

        .sm-seats-row-wrap {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        /* Read-Only Non-Interactive Seat Styles */
        .sm-seat {
            width: 28px;
            height: 26px;
            font-size: 0.65rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            user-select: none;
            cursor: default; /* Enforces read-only profile models */
            transition: none;
            color: #fff;
        }

        .sm-seat--available {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.6);
        }

        .sm-seat--pending {
            background: #eab308;
            border: 1px solid #ca8a04;
            color: #000;
            box-shadow: 0 0 8px rgba(234,179,8,0.3);
        }

        .sm-seat--sold {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: rgba(239, 68, 68, 0.8);
            position: relative;
        }

        /* Diagonal lines over sold seats */
        .sm-seat--sold::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 1px;
            background: rgba(239, 68, 68, 0.4);
            transform: rotate(45deg);
            inset: 0;
            margin: auto;
        }

        /* Interactive Map Decorative Layout Border */
        .sm-border-decoration {
            border: 1px solid var(--staff-btn-padding-border);
            padding: 3px;
            border-radius: 8px;
            margin-top: 30px;
            display: inline-block;
        }

        .sm-decoration-badge {
            background: var(--staff-gradient);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 12px;
            border-radius: 4px;
            color: #000;
        }

        /* Branded Structural Footer Accent */
        .sm-footer {
            background: #07070f;
            border-top: 1px solid rgba(255,255,255,0.03);
            padding: 24px;
            text-align: center;
            font-size: 0.75rem;
            color: rgba(240,238,255,0.25);
            font-family: monospace;
            margin-top: auto;
        }
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

        <section class="sm-arena">
            
            <div class="sm-screen-container">
                <div class="sm-screen-curve"></div>
                <div class="sm-screen-glow"></div>
                <div class="sm-screen-label">Front Screen Axis</div>
            </div>

            <div class="sm-grid">
                @forelse($seatRows as $rowLabel => $seats)
                    <div class="sm-row">
                        <div class="sm-row-label">{{ $rowLabel }}</div>
                        
                        <div class="sm-seats-row-wrap">
                            @foreach($seats as $seat)
                                <div class="sm-seat sm-seat--{{ $seat->status }}"
                                     title="Seat {{ $rowLabel }}{{ $seat->seat_number }} • Status: {{ ucfirst($seat->status) }}">
                                    {{ $seat->seat_number }}
                                </div>
                            @endforeach
                        </div>

                        <div class="sm-row-label">{{ $rowLabel }}</div>
                    </div>
                @empty
                    <div style="text-align: center; color: rgba(240,238,255,0.4); padding: 40px 0;">
                        No physical seat configuration metrics compiled for this structural class.
                    </div>
                @endforelse
            </div>

            <div class="sm-border-decoration">
                <div class="sm-decoration-badge">
                    Real-Time Telemetry Feed
                </div>
            </div>

        </section>

    </main>

    <footer class="sm-footer">
       {{-- Replace the footer @if block --}}
@if($role === 'manager')
    [ Manager Layout ] — CinemaX Theatre Operations Monitoring Matrix
@elseif($role === 'supervisor')
    [ Supervisor Layout ] — CinemaX Global System Seating Control Panel
@else
    [ Guest ] — Access Restricted
@endif
    </footer>

</body>
</html>