{{--
    resources/views/admin/admin_panel.blade.php
    Cinema Admin Launchpad – Time‑based greeting pop‑up,
    comic side‑by‑side layout, playful female character.
    Original card grid untouched.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Launchpad | Cinema Manager</title>

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    @vite(['resources/css/admin_panel.css', 'resources/js/admin_panel.js'])
</head>
<body>

<div class="ap-wrapper">

    {{-- Header – brand only (greeting moved to pop‑up) --}}
    <header class="ap-header">
        <div class="ap-header__brand">
            <i class="fas fa-film ap-header__logo"></i>
            <span class="ap-header__name">CINEMA ADMIN</span>
        </div>
    </header>

    {{-- Greeting pop‑up overlay (hidden by default, JS will reveal) --}}
    <div id="greeting-overlay" class="ap-greeting-overlay" style="display: none;">
        <div id="greeting-popup" class="ap-greeting-popup">
            <button id="greeting-close" class="ap-greeting-close" aria-label="Close greeting">×</button>

            {{-- Side‑by‑side comic container --}}
            <div class="greeting-content">

                {{-- Female character (modern playful girl) --}}
                <div id="character-container" class="character-container">
                    <div class="character-head">
                        <div class="character-hair"></div>
                        <div class="eyes">
                            <div class="eye left"></div>
                            <div class="eye right"></div>
                        </div>
                        <div class="mouth"></div>
                    </div>
                    <div class="character-body">
                        <div class="arm left"></div>
                        <div class="arm right"></div>
                    </div>
                    <div class="character-skirt"></div>
                    <div class="props">
                        <div class="prop-item prop-coffee">☕</div>
                        <div class="prop-item prop-sun">🌅</div>
                        <div class="prop-item prop-clapper">🎬</div>
                        <div class="prop-item prop-popcorn">🍿</div>
                        <div class="prop-item prop-ticket">🎟️</div>
                    </div>
                </div>

                {{-- Speech bubble with greeting message --}}
                <div id="greeting-message" class="speech-bubble"></div>
            </div>
        </div>
    </div>

    {{-- Compact card grid – exactly as before --}}
    <main class="ap-grid">
        <a href="{{ route('admin.cinema.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-building ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Add Cinema</h2>
                <p class="ap-card__desc">Register a new branch</p>
            </div>
        </a>

        <a href="{{ route('admin.cinema.index') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-eye ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">View Cinemas</h2>
                <p class="ap-card__desc">Manage all branches</p>
            </div>
        </a>

        <a href="{{ route('admin.city.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-city ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Add City</h2>
                <p class="ap-card__desc">Expand to new locations</p>
            </div>
        </a>

        <a href="{{ route('admin.theatre.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-film ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Create Theatre</h2>
                <p class="ap-card__desc">Set up screening rooms</p>
            </div>
        </a>

        <a href="{{ route('admin.service.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-concierge-bell ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Add Service</h2>
                <p class="ap-card__desc">Amenities & extras</p>
            </div>
        </a>

        <a href="{{ route('admin.movie.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-clapperboard ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Create Movie</h2>
                <p class="ap-card__desc">Add new films</p>
            </div>
        </a>

        <a href="{{ route('admin.managers.index') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-users ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Managers</h2>
                <p class="ap-card__desc">Staff & roles</p>
            </div>
        </a>

        <a href="{{ route('admin.proposals.index') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-envelope-open-text ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Proposals</h2>
                <p class="ap-card__desc">Review & approve</p>
            </div>
        </a>

        <a href="{{ route('admin.food_drink.create') }}" class="ap-card">
            <div class="ap-card__icon-wrap"><i class="fas fa-hamburger ap-card__icon"></i></div>
            <div class="ap-card__content">
                <h2 class="ap-card__title">Food & Drinks</h2>
                <p class="ap-card__desc">Global menu catalog</p>
           </div>
        </a>
    </main>

    <footer class="ap-footer">
        <i class="fas fa-film"></i> Admin Panel &copy; {{ date('Y') }}
    </footer>
</div>

</body>
</html>