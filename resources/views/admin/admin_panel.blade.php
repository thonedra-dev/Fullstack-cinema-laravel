<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS CONTROL | Cinema Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    @vite(['resources/css/admin_panel.css', 'resources/js/admin_panel.js'])
</head>
<body>

<div class="ap-wrapper">

    {{-- Header with Global Auth State --}}
    <header class="ap-header">
        <div class="ap-header__brand">
            <i class="fas fa-network-wired ap-header__logo"></i>
            <span class="ap-header__name">NEXUS // ADMIN</span>
        </div>
        
        <div class="ap-header__auth">
            {{-- Pulling the Supervisor Name from the Auth Session --}}
            <span class="auth-name">
                <i class="fas fa-user-astronaut"></i> 
                {{ auth('supervisor')->user()->supervisor_name ?? 'SYS.ADMIN' }}
            </span>
            
            <form method="POST" action="{{ route('admin.logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn"><i class="fas fa-power-off"></i></button>
            </form>
        </div>
    </header>

    {{-- Holographic Boot Pop-up --}}
    <div id="greeting-overlay" class="ap-greeting-overlay" style="display: none;">
        <div id="greeting-popup" class="ap-greeting-popup cyber-boot">
            <div class="scanline"></div>
            <button id="greeting-close" class="ap-greeting-close" aria-label="Close greeting">×</button>

            <div class="greeting-content">
                <div class="hologram-container">
                    <i class="fas fa-fingerprint hologram-icon"></i>
                </div>

                <div class="terminal-bubble">
                    <p class="sys-text">> SYSTEM_BOOT_SEQUENCE_INITIATED...</p>
                    <p class="sys-text">> AUTH_CONFIRMED: <span class="highlight">{{ auth('supervisor')->user()->supervisor_name ?? 'SYS.ADMIN' }}</span></p>
                    <div id="greeting-message" class="message-text"></div>
                    <p class="sys-text blink">> AWAITING_COMMAND_</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Cyberpunk Card Grid --}}
    <main class="ap-grid">
        <a href="{{ route('admin.cinema.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-building ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">ADD CINEMA</h2>
                <p class="ap-card__desc">Register new sector</p>
            </div>
        </a>

        <a href="{{ route('admin.cinema.index') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-satellite-dish ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">VIEW CINEMAS</h2>
                <p class="ap-card__desc">Scan all sectors</p>
            </div>
        </a>

        <a href="{{ route('admin.city.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-city ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">ADD CITY</h2>
                <p class="ap-card__desc">Expand grid territory</p>
            </div>
        </a>

        <a href="{{ route('admin.theatre.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-vr-cardboard ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">CREATE THEATRE</h2>
                <p class="ap-card__desc">Configure sim rooms</p>
            </div>
        </a>

        <a href="{{ route('admin.service.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-microchip ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">ADD SERVICE</h2>
                <p class="ap-card__desc">Hardware & amenities</p>
            </div>
        </a>

        <a href="{{ route('admin.movie.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-film ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">CREATE MOVIE</h2>
                <p class="ap-card__desc">Upload data streams</p>
            </div>
        </a>

        <a href="{{ route('admin.managers.index') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-users-cog ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">MANAGERS</h2>
                <p class="ap-card__desc">Node operators</p>
            </div>
        </a>

        <a href="{{ route('admin.proposals.index') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-file-code ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">PROPOSALS</h2>
                <p class="ap-card__desc">Review incoming logs</p>
            </div>
        </a>

        <a href="{{ route('admin.food_drink.create') }}" class="ap-card cyber-edge">
            <div class="ap-card__icon-wrap"><i class="fas fa-flask ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">FOOD & DRINKS</h2>
                <p class="ap-card__desc">Synthesized rations</p>
           </div>
        </a>
    </main>

    <footer class="ap-footer">
        <i class="fas fa-terminal"></i> NEXUS CONTROL &copy; {{ date('Y') }} // END OF LINE
    </footer>
</div>

</body>
</html>