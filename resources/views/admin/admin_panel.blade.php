{{--
    resources/views/admin/admin_panel.blade.php
    Lean Admin Launchpad – centered brand, compact cards.
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

    {{-- Header – centered brand, greeting only --}}
    <header class="ap-header">
        <h2 class="ap-header__title" id="greeting-title">
            {{-- JS fills dynamic greeting --}}
        </h2>
    </header>
    {{-- Compact card grid --}}
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
    </main>

    <footer class="ap-footer">
        <i class="fas fa-film"></i> Admin Panel &copy; {{ date('Y') }}
    </footer>
</div>

<script>
    (function() {
        const hour = new Date().getHours();
        let greeting = '';
        if (hour < 12) greeting = 'Good morning,';
        else if (hour < 18) greeting = 'Good afternoon,';
        else greeting = 'Good evening,';

        const titleEl = document.getElementById('greeting-title');
        if (titleEl) {
            titleEl.innerHTML = `${greeting} <span style="background: linear-gradient(135deg, #fff, #7b87f5); background-clip: text; -webkit-background-clip: text; color: transparent;">Admin.</span>`;
        }
    })();
</script>

</body>
</html>