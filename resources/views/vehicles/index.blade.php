@extends('layouts.app')
@section('title', 'Available Vehicles')
@section('content')

<div class="fx-help-chip" data-fx-help-chip>
    <a href="https://wa.me/250780097079" target="_blank" rel="noopener" class="fx-help-chip-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3.1.82.83-3-.2-.32A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.25-.13-1.47-.72-1.7-.8-.23-.08-.4-.13-.56.13-.17.25-.65.8-.8.96-.15.17-.3.19-.55.06-.25-.12-1.06-.39-2.02-1.24-.75-.66-1.25-1.48-1.4-1.73-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.15.16-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.34-.76-1.83-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.44.06-.67.31-.23.25-.87.85-.87 2.08s.9 2.41 1.02 2.58c.13.17 1.77 2.7 4.29 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.23-.16-.48-.28z"/></svg>
        <span>Need help choosing a vehicle? Chat with us</span>
    </a>
    <button type="button" class="fx-help-chip-close" data-fx-help-chip-close aria-label="Dismiss">&times;</button>
</div>

<div class="fx-intro-overlay" data-fx-intro>
    <div class="fx-intro-grid" aria-hidden="true"></div>
    <div class="fx-intro-car">
        <div class="fx-intro-car-glow" aria-hidden="true"></div>
        @if(file_exists(public_path('images/intro-ford.jpg')))
            <img src="{{ asset('images/intro-ford.jpg') }}" alt="" class="fx-intro-car-photo">
        @else
            <svg viewBox="0 0 120 48" class="fx-intro-car-svg" aria-hidden="true">
                <circle cx="34" cy="36" r="8"></circle>
                <circle cx="94" cy="36" r="8"></circle>
                <path d="M10 34 Q10 22 22 20 L34 20 Q40 10 54 10 L78 10 Q90 10 96 20 L108 20 Q116 20 116 30 L116 34 Z"></path>
            </svg>
        @endif
    </div>
    @if(file_exists(public_path('images/logo-icon.png')))
        <img src="{{ asset('images/logo-icon.png') }}" alt="FlexiRent" class="fx-intro-logo">
    @else
        <span class="fx-intro-logo fx-intro-logo-text">FlexiRent</span>
    @endif
    <button type="button" class="fx-intro-skip" data-fx-intro-skip>Skip</button>
</div>

<div class="fx-hero-breakout">
    <div class="fx-hero">
        <div class="fx-hero-blob"></div>
        <div class="fx-hero-blob b2"></div>
        <div class="fx-hero-particles" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="fx-hero-inner"
             x-data="{
                categoryId: '',
                maxPrice: '',
                carType: '',
                sheetOpen: false,
                goToFleet() {
                    const params = new URLSearchParams();
                    if (this.categoryId) params.set('category_id', this.categoryId);
                    if (this.maxPrice) params.set('max_price', this.maxPrice);
                    if (this.carType) params.set('car_type', this.carType);
                    window.location.href = '{{ route('vehicles.fleet') }}?' + params.toString();
                }
             }">
            <div class="fx-hero-copy">
                <div class="badges">
                    <span class="eyebrow"><span class="dot"></span> {{ $stats['vehicles'] }} vehicles available</span>
                    <span class="eyebrow">Kigali, Rwanda</span>
                    <span class="eyebrow" data-fx-clock>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                        <span data-fx-clock-time>--:--</span> local time
                    </span>
                </div>
                <h1 class="fx-split">Rent with a <span class="fx-accent">trusted</span> intermediary in Kigali.</h1>
                <p class="lead">Verified partners, transparent pricing, and Mobile Money payment (MTN, Airtel) on every booking. FlexiRent connects you directly to the right vehicle, built on years of hands-on experience in Kigali's car rental industry.</p>

                <div class="fx-search fx-search-desktop">
                    <div>
                        <label>Category</label>
                        <select class="form-select" x-model="categoryId">
                            <option value="">All categories</option>
                            @foreach($categoriesWithCounts as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Vehicle type</label>
                        <select class="form-select" x-model="carType">
                            <option value="">All types</option>
                            <option value="normal">Normal</option>
                            <option value="hybrid">Hybrid</option>
                            <option value="electric">Electric</option>
                        </select>
                    </div>
                    <div>
                        <label>Max price / day (RWF)</label>
                        <input type="number" class="form-control" placeholder="e.g. 50000" x-model="maxPrice" @keydown.enter="goToFleet()">
                    </div>
                    <button type="button" class="btn btn-gold" @click="goToFleet()">Search</button>
                </div>

                <button type="button" class="btn btn-gold fx-sheet-trigger mb-4" @click="sheetOpen = true">
                    Filter vehicles
                </button>

                <div class="fx-stat-row">
                    <div><span class="n" data-count="{{ $stats['vehicles'] }}">0</span><span class="l">vehicles available</span></div>
                    <div><span class="n" data-count="{{ $stats['partners'] }}">0</span><span class="l">verified partners</span></div>
                    <div><span class="n"><span data-count-decimal="{{ $stats['rating'] }}">0.0</span>&nbsp;&#9733;</span><span class="l">average rating</span></div>
                    <div><span class="n" data-count="{{ $stats['bookings'] }}">0</span><span class="l">bookings completed</span></div>
                </div>
            </div>

            @if($siteCoverPath)
                <a href="{{ route('vehicles.fleet') }}" class="fx-feature-card fx-reveal fx-tilt">
                    <img src="{{ asset('storage/'.$siteCoverPath) }}" alt="FlexiRent">
                    <span class="fx-feature-caption">FlexiRent</span>
                </a>
            @elseif($featured)
                <a href="{{ route('vehicles.show', $featured) }}" class="fx-feature-card fx-reveal fx-tilt">
                    <img src="{{ $featured->photo_path ? asset('storage/'.$featured->photo_path) : 'https://placehold.co/700x800?text=' . urlencode($featured->model) }}" alt="{{ $featured->model }}">
                    <span class="fx-feature-caption">{{ $featured->model }}</span>
                </a>
            @endif

            <div class="fx-sheet-backdrop" x-show="sheetOpen" x-cloak @click="sheetOpen = false"></div>
            <div class="fx-sheet" x-show="sheetOpen" x-cloak>
                <div class="fx-sheet-handle"></div>
                <label class="form-label fw-bold">Category</label>
                <select class="form-select mb-3" x-model="categoryId">
                    <option value="">All categories</option>
                    @foreach($categoriesWithCounts as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <label class="form-label fw-bold">Vehicle type</label>
                <select class="form-select mb-3" x-model="carType">
                    <option value="">All types</option>
                    <option value="normal">Normal</option>
                    <option value="hybrid">Hybrid</option>
                    <option value="electric">Electric</option>
                </select>
                <label class="form-label fw-bold">Max price / day (RWF)</label>
                <input type="number" class="form-control mb-4" placeholder="e.g. 50000" x-model="maxPrice">
                <button type="button" class="btn btn-gold w-100" @click="goToFleet()">Apply filters</button>
            </div>
        </div>
    </div>
</div>

<div class="fx-hero-breakout">
    <div class="fx-marquee" aria-hidden="true">
        <div class="fx-marquee-track">
            <span>Verified partners only</span><span class="fx-marquee-dot">&bull;</span>
            <span>Mobile Money on every booking</span><span class="fx-marquee-dot">&bull;</span>
            <span>Signed contract, instantly</span><span class="fx-marquee-dot">&bull;</span>
            <span>Founder-led industry expertise</span><span class="fx-marquee-dot">&bull;</span>
            <span>No call center, no bots</span><span class="fx-marquee-dot">&bull;</span>
            <span>Verified partners only</span><span class="fx-marquee-dot">&bull;</span>
            <span>Mobile Money on every booking</span><span class="fx-marquee-dot">&bull;</span>
            <span>Signed contract, instantly</span><span class="fx-marquee-dot">&bull;</span>
            <span>Founder-led industry expertise</span><span class="fx-marquee-dot">&bull;</span>
            <span>No call center, no bots</span><span class="fx-marquee-dot">&bull;</span>
        </div>
    </div>
</div>

<div class="fx-hero-breakout">
    <div class="fx-hero-inner" style="display:block; max-width:1400px; padding: 56px 20px 8px;">
        <div class="fx-eyebrow-label">Browse the fleet</div>
        <h2 class="mb-4" style="max-width: 22ch;">Find your kind of ride.</h2>
        <div class="fx-category-grid">
            <a href="{{ route('vehicles.fleet') }}" class="fx-category-card fx-reveal fx-tilt">
                <span class="fx-category-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                <span class="fx-category-name">All vehicles</span>
                <span class="fx-category-count">{{ $stats['vehicles'] }} vehicle{{ $stats['vehicles'] === 1 ? '' : 's' }}</span>
            </a>
            @foreach($categoriesWithCounts as $category)
                <a href="{{ route('vehicles.fleet', ['category_id' => $category->id]) }}" class="fx-category-card fx-reveal fx-tilt">
                    <span class="fx-category-icon">
                        @if(strtolower($category->name) === 'jeep')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h1l1.5-4.5A2 2 0 0 1 7.4 11h9.2a2 2 0 0 1 1.9 1.5L20 17h1"/><path d="M3 17v2a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h12v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-2"/><path d="M3 17h18"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h1.5l1-4a2 2 0 0 1 1.9-1.4h5.2a2 2 0 0 1 1.9 1.4l1 4H19"/><path d="M3 17v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17h12v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17"/><path d="M3 17l1.2-5.8A2 2 0 0 1 6.2 9.6h11.6a2 2 0 0 1 2 1.6L21 17"/><circle cx="7.5" cy="17" r="1.3"/><circle cx="16.5" cy="17" r="1.3"/></svg>
                        @endif
                    </span>
                    <span class="fx-category-name">{{ $category->name }}</span>
                    <span class="fx-category-count">{{ $category->vehicles_count }} vehicle{{ $category->vehicles_count === 1 ? '' : 's' }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="fx-hero-breakout fx-why">
    <div class="fx-hero-inner" style="display:block; max-width:1400px;">
        <div class="fx-eyebrow-label">Why FlexiRent</div>
        <h2 class="mb-0" style="max-width: 22ch;">Built for renting with confidence.</h2>

        <div class="fx-why-grid">
            <div class="fx-why-item fx-reveal fx-tilt">
                <div class="fx-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div>
                    <h5>Verified partners only</h5>
                    <p>Every listing comes from a company reviewed and validated by the FlexiRent team before it goes live.</p>
                </div>
            </div>
            <div class="fx-why-item fx-reveal fx-tilt">
                <div class="fx-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                </div>
                <div>
                    <h5>Mobile Money on every booking</h5>
                    <p>Pay with MTN Mobile Money, Airtel Money, or settle on site &mdash; whichever works for you.</p>
                </div>
            </div>
            <div class="fx-why-item fx-reveal fx-tilt">
                <div class="fx-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div>
                    <h5>Signed contract, instantly</h5>
                    <p>A rental contract PDF is generated automatically the moment your booking is confirmed.</p>
                </div>
            </div>
            <div class="fx-why-item fx-reveal fx-tilt">
                <div class="fx-why-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <div>
                    <h5>No call center, no bots</h5>
                    <p>Questions about a reservation go straight to the partner and to FlexiRent support.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fx-hero-breakout">
    <div class="fx-cta-band">
        <div class="fx-hero-inner fx-reveal" style="display:block; max-width:1400px;">
            <p class="fx-cta-eyebrow">6+ years of expertise &middot; Kigali, Rwanda</p>
            <h2 class="fx-cta-headline">Ready to hit<br>the road?</h2>
            <a href="{{ route('vehicles.fleet') }}" class="btn btn-navy btn-lg">Browse the fleet &rarr;</a>
        </div>
    </div>
</div>

<div class="mt-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="mb-0">Available Vehicles</h2>
        <a href="{{ route('vehicles.fleet') }}" class="btn btn-navy btn-sm">See the full fleet &rarr;</a>
    </div>
    <div class="row g-4">
        @forelse($teaserVehicles as $vehicle)
            <div class="col-12 col-md-6 col-lg-4">
                <x-vehicle-card :vehicle="$vehicle" />
            </div>
        @empty
            <div class="col-12"><p class="text-muted">No vehicles available right now.</p></div>
        @endforelse
    </div>
    <div class="text-center mt-4">
        <a href="{{ route('vehicles.fleet') }}" class="btn btn-outline-light">See the full fleet &rarr;</a>
    </div>
</div>

<div class="mt-5 pt-3">
    <h2 class="mb-2">How it works</h2>
    <p class="text-muted">Three steps, from choosing a vehicle to a signed contract.</p>
    <div class="steps-route">
        <div class="steps-route-progress" data-steps-progress></div>
        <div class="step-pin fx-reveal">
            <div class="marker">1</div>
            <h4>Choose &amp; book</h4>
            <p>Browse the catalogue, pick your dates, and confirm the booking online.</p>
        </div>
        <div class="step-pin fx-reveal">
            <div class="marker">2</div>
            <h4>Pay securely</h4>
            <p>MTN Mobile Money, Airtel Money, or pay on site, whichever you prefer.</p>
        </div>
        <div class="step-pin fx-reveal">
            <div class="marker">3</div>
            <h4>Hit the road</h4>
            <p>Pick up the vehicle with your rental contract generated automatically as a PDF.</p>
        </div>
    </div>
</div>

@endsection
