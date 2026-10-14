<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FlexiRent') - FlexiRent</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Runs synchronously, before any CSS paints, so the site never
        // flashes light-then-dark. app.js takes over from here (re-checks
        // every 5 min), see initDayNightTheme().
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
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body>
    <nav class="fx-navbar" x-data="{ open: false }">
        <div class="fx-navbar-inner">
            <a class="fx-brand" href="{{ route('vehicles.index') }}">
                @if(file_exists(public_path('images/logo-icon.png')))
                    <img src="{{ asset('images/logo-icon.png') }}" alt="FlexiRent" class="fx-brand-logo">
                @endif
                <span>FlexiRent</span>
            </a>
            <button class="fx-nav-toggle" type="button" @click="open = !open" :aria-expanded="open.toString()" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <div class="fx-nav-actions" :class="{ 'is-open': open }">
                @auth
                    <a href="{{ route('profile.edit') }}" class="fx-user-chip">
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->username }}" class="fx-user-avatar">
                        {{ auth()->user()->username }}
                    </a>
                    @if(auth()->user()->isPartner())
                        <a href="{{ route('partner.dashboard') }}" class="btn btn-outline-light btn-sm">Partner Dashboard</a>
                    @endif
                    <a href="{{ route('reservations.index') }}" class="btn btn-outline-light btn-sm">My Reservations</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-gold btn-sm">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-gold btn-sm">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    @auth
        @unless(auth()->user()->hasVerifiedEmail())
            <div class="fx-verify-banner">
                Please verify your email address ({{ auth()->user()->email }}).
                <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn-link-inline">Resend verification email</button>
                </form>
            </div>
        @endunless
    @endauth

    @if (session('status') || $errors->any())
        <div id="fx-flash-data" class="d-none"
             data-status="{{ session('status') }}"
             data-errors="{{ $errors->any() ? json_encode($errors->all()) : '' }}"></div>
    @endif

    <div class="fx-page mt-4">
        @unless(request()->routeIs('vehicles.index'))
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('vehicles.index') }}" class="fx-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back
            </a>
        @endunless
        @yield('content')
    </div>

    @if(request()->routeIs('vehicles.index'))
        <footer class="fx-footer fx-reveal">
            <div class="fx-footer-inner">
                <div class="fx-footer-about">
                    <a href="{{ route('vehicles.index') }}" class="fx-footer-brand-link">
                        @if(file_exists(public_path('images/logo-icon.png')))
                            <img src="{{ asset('images/logo-icon.png') }}" alt="FlexiRent" class="fx-brand-logo">
                        @endif
                        <span class="fx-footer-brand">FlexiRent</span>
                    </a>
                    <p class="fx-footer-tagline">Your trusted car rental intermediary in Kigali</p>
                    <p>FlexiRent connects vehicle owners with renters across Kigali through secure booking, Mobile Money payment, and verified partners.</p>
                    <div class="fx-footer-social">
                        <a href="#" aria-label="Facebook" target="_blank" rel="noopener">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H7.9V12h2.6V9.8c0-2.6 1.5-4 3.9-4 1.1 0 2.3.2 2.3.2v2.5h-1.3c-1.3 0-1.7.8-1.7 1.6V12h2.9l-.5 2.9h-2.4v7A10 10 0 0 0 22 12z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram" target="_blank" rel="noopener">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                        </a>
                        <a href="#" aria-label="TikTok" target="_blank" rel="noopener">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 2c.3 2.4 1.8 4 4.5 4.2v3c-1.6 0-3-.5-4.5-1.5v6.8a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.1v3.1a2.7 2.7 0 1 0 1.9 2.6V2h3z"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h6>Navigation</h6>
                    <ul>
                        <li><a href="{{ route('vehicles.index') }}">Home</a></li>
                        <li><a href="{{ route('about') }}">About</a></li>
                        <li><a href="{{ route('services') }}">Services</a></li>
                        <li><a href="{{ route('vehicles.fleet') }}">Our Vehicles</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h6>Services</h6>
                    <ul>
                        <li><a href="{{ route('vehicles.fleet') }}">Car Rental</a></li>
                        <li><a href="{{ route('services') }}">Airport Transfer</a></li>
                        <li><a href="{{ route('services') }}">Chauffeur Service</a></li>
                        <li><a href="{{ route('services') }}">Other Services</a></li>
                    </ul>
                </div>

                <div>
                    <h6>Contact</h6>
                    <ul>
                        <li><a href="tel:+250780097079">+250 780 097 079</a></li>
                        <li><a href="https://wa.me/250780097079" target="_blank" rel="noopener">WhatsApp</a></li>
                        <li><a href="mailto:contact@flexirent.rw">contact@flexirent.rw</a></li>
                        <li>Kigali, Rwanda</li>
                    </ul>
                </div>

                <div class="fx-footer-newsletter">
                    <h6>Newsletter</h6>
                    <p>Get updates on new vehicles and offers.</p>
                    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="fx-newsletter-form">
                        @csrf
                        <input type="email" name="email" placeholder="Your email" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>

            <div class="fx-footer-legal">
                <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('legal-notice') }}">Legal Notice</a>
            </div>
            <div class="fx-footer-bottom">&copy; {{ date('Y') }} FlexiRent. All rights reserved.</div>
        </footer>
    @endif

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
