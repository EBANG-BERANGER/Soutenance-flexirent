<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Control Panel') - FlexiRent Admin</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <script>
        (function () {
            try {
                var hour = Number(new Intl.DateTimeFormat('en-US', { timeZone: 'Africa/Kigali', hour: 'numeric', hourCycle: 'h23' }).format(new Date()));
                if (hour < 6 || hour >= 18) document.documentElement.setAttribute('data-theme', 'night');
            } catch (e) {}
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="preconnect" href="https://api.fontshare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" rel="stylesheet">
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body class="fx-admin">
    <div class="fx-admin-shell" x-data="{ sidebarOpen: false }">
        <aside class="fx-admin-sidebar" :class="{ 'is-open': sidebarOpen }">
            <a href="{{ route('admin.dashboard') }}" class="fx-admin-brand">
                @if(file_exists(public_path('images/logo-icon.png')))
                    <img src="{{ asset('images/logo-icon.png') }}" alt="FlexiRent" class="fx-brand-logo">
                @endif
                <span>FlexiRent</span>
            </a>
            <div class="fx-admin-sub">Control panel</div>

            <div class="fx-admin-nav-group">
                <div class="fx-admin-nav-label">Overview</div>
                <ul class="fx-admin-nav">
                    <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                        Overview
                    </a></li>
                </ul>
            </div>

            <div class="fx-admin-nav-group">
                <div class="fx-admin-nav-label">Operations</div>
                <ul class="fx-admin-nav">
                    <li><a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Users
                    </a></li>
                    <li><a href="{{ route('admin.vehicles') }}" class="{{ request()->routeIs('admin.vehicles*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h1.5l1-4a2 2 0 0 1 1.9-1.4h5.2a2 2 0 0 1 1.9 1.4l1 4H19"/><path d="M3 17v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17h12v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17"/><path d="M3 17l1.2-5.8A2 2 0 0 1 6.2 9.6h11.6a2 2 0 0 1 2 1.6L21 17"/><circle cx="7.5" cy="17" r="1.3"/><circle cx="16.5" cy="17" r="1.3"/></svg>
                        Vehicles
                    </a></li>
                    <li><a href="{{ route('admin.reservations') }}" class="{{ request()->routeIs('admin.reservations*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        Reservations
                    </a></li>
                    <li><a href="{{ route('admin.payments') }}" class="{{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                        Payments
                    </a></li>
                    <li><a href="{{ route('admin.violations') }}" class="{{ request()->routeIs('admin.violations*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        Violations
                    </a></li>
                </ul>
            </div>

            <div class="fx-admin-nav-group">
                <div class="fx-admin-nav-label">Catalog</div>
                <ul class="fx-admin-nav">
                    <li><a href="{{ route('admin.categories') }}" class="{{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Categories
                    </a></li>
                    <li><a href="{{ route('admin.reviews') }}" class="{{ request()->routeIs('admin.reviews*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Reviews
                    </a></li>
                </ul>
            </div>

            <div class="fx-admin-nav-group">
                <div class="fx-admin-nav-label">Records</div>
                <ul class="fx-admin-nav">
                    <li><a href="{{ route('admin.contracts') }}" class="{{ request()->routeIs('admin.contracts*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                        Contracts
                    </a></li>
                    <li><a href="{{ route('admin.history') }}" class="{{ request()->routeIs('admin.history') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        History
                    </a></li>
                    <li><a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Settings
                    </a></li>
                </ul>
            </div>
        </aside>

        <div class="fx-admin-main">
            <div class="fx-admin-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="fx-admin-toggle" type="button" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">&#9776;</button>
                    <div class="fx-admin-identity">
                        <div class="fx-admin-identity-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                        <div>
                            <div class="fx-admin-identity-name">{{ auth()->user()->name }}</div>
                            <span class="role-tag">Administrator</span>
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-admin btn-admin-outline">Logout</button>
                </form>
            </div>

            <div class="fx-admin-content">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
