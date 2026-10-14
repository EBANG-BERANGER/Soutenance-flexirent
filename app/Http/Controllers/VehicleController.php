<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Vehicle;
use App\Support\ImageOptimizer;
use App\Support\SiteCover;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Homepage: marketing landing + a short teaser of the fleet. The full,
     * filterable catalogue lives on its own page (see fleet()) so the
     * listing keeps scaling as the number of vehicles grows, instead of
     * bloating the homepage itself.
     */
    public function index(Request $request)
    {
        $categoriesWithCounts = Category::withCount(['vehicles' => function ($q) {
            $q->where('status', 'available')->where('is_suspended', false);
        }])->orderBy('name')->get();

        $stats = [
            'vehicles' => Vehicle::where('status', 'available')->where('is_suspended', false)->count(),
            'partners' => Partner::where('status', 'validated')->count(),
            'rating' => round(Review::avg('rating') ?? 5, 1),
            // Confirmed + completed reservations: a real trust signal (successful
            // bookings), used in place of "cities covered" since the schema has
            // no clean city/district field to count against.
            'bookings' => Reservation::whereIn('status', ['confirmed', 'completed'])->count(),
        ];

        // A dedicated site cover photo (uploaded in admin, not tied to any
        // vehicle) takes priority over the vehicle-based feature below.
        $siteCoverPath = SiteCover::path();

        // Admin can pin a specific vehicle's photo as the homepage feature;
        // otherwise fall back to the latest available one.
        $featured = Vehicle::with(['category', 'partner', 'images'])
            ->where('is_suspended', false)
            ->where('is_featured', true)
            ->first()
            ?? Vehicle::with(['category', 'partner', 'images'])
                ->where('status', 'available')
                ->where('is_suspended', false)
                ->latest()
                ->first();

        // Booked vehicles stay in the catalogue (marked "Booked until") rather
        // than disappearing: only maintenance/suspended ones are pulled.
        $teaserVehicles = Vehicle::with(['category', 'partner', 'reservations' => function ($q) {
                $q->where('status', 'confirmed');
            }])
            ->whereIn('status', ['available', 'booked'])
            ->where('is_suspended', false)
            ->when($featured, fn ($q) => $q->where('id', '!=', $featured->id))
            ->latest()
            ->take(3)
            ->get();

        return view('vehicles.index', compact('categoriesWithCounts', 'stats', 'featured', 'teaserVehicles', 'siteCoverPath'));
    }

    /**
     * Full fleet catalogue: every available vehicle, filterable by
     * category, vehicle type (normal/hybrid/electric) and budget, paginated
     * so it keeps working as the fleet grows well beyond a homepage teaser.
     */
    public function fleet(Request $request)
    {
        $vehicles = $this->filteredQuery($request)->paginate(9)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $activeCategory = $request->filled('category_id') ? $categories->firstWhere('id', (int) $request->category_id) : null;

        return view('vehicles.fleet', compact('vehicles', 'categories', 'activeCategory'));
    }

    /**
     * Reactive filtering (Alpine.js + fetch): returns just the grid
     * partial so the fleet page re-renders without a full page reload.
     */
    public function filter(Request $request)
    {
        $vehicles = $this->filteredQuery($request)->paginate(9)->withQueryString();
        $activeCategory = $request->filled('category_id') ? Category::find($request->category_id) : null;

        return view('vehicles._grid', compact('vehicles', 'activeCategory'));
    }

    private function filteredQuery(Request $request)
    {
        // Booked vehicles stay in the catalogue (marked "Booked until") rather
        // than disappearing: only maintenance/suspended ones are pulled.
        $query = Vehicle::with(['category', 'partner', 'reservations' => function ($q) {
                $q->where('status', 'confirmed');
            }])
            ->whereIn('status', ['available', 'booked'])
            ->where('is_suspended', false);

        if ($request->filled('search')) {
            $query->where('model', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('car_type')) {
            $query->whereIn('car_type', (array) $request->car_type);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        // When the client is shopping to a budget, lead with the cheapest
        // options that fit it rather than the most recently listed vehicles.
        return $request->filled('max_price')
            ? $query->orderBy('price_per_day', 'asc')
            : $query->latest();
    }

    /**
     * Vehicle detail page (Ch.5.5 Figure 5.2/5.3).
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load('category', 'partner', 'images');
        $similarVehicles = $vehicle->similarVehicles();

        return view('vehicles.show', compact('vehicle', 'similarVehicles'));
    }

    public function create()
    {
        $this->authorizePartner();
        $categories = Category::orderBy('name')->get();

        return view('vehicles.create', compact('categories'));
    }

    /**
     * Partner adds a vehicle to their fleet (Ch.5.4.2 Vehicle Management Module).
     */
    public function store(Request $request)
    {
        $partner = $this->authorizePartner();
        $isElectric = $request->input('car_type') === 'electric';

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:' . Vehicle::minAcceptedYear(), 'max:' . (date('Y') + 1)],
            // A 100%-electric vehicle has no combustion engine, so propulsion
            // (essence/diesel) only applies to normal/hybrid vehicles.
            'propulsion' => [$isElectric ? 'nullable' : 'required', 'nullable', 'in:diesel,essence'],
            'car_type' => ['required', 'in:normal,hybrid,electric'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles'],
            'chassis_number' => ['required', 'string', 'max:50'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'price_per_week' => ['nullable', 'numeric', 'min:0'],
            'price_per_month' => ['nullable', 'numeric', 'min:0'],
            'min_rental_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'driver_available' => ['boolean'],
            'driver_fee_per_day' => ['required_if:driver_available,1', 'nullable', 'numeric', 'min:0'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:12288'],
        ], [
            'year.min' => 'Vehicles older than 14 years are not accepted on FlexiRent.',
        ]);

        $validated['partner_id'] = $partner->id;
        $validated['driver_available'] = $request->boolean('driver_available');
        $validated['driver_fee_per_day'] = $validated['driver_fee_per_day'] ?? 0;
        $validated['deposit_amount'] = $validated['deposit_amount'] ?? 0;
        $validated['min_rental_days'] = $validated['min_rental_days'] ?? 1;
        $validated['propulsion'] = $isElectric ? null : $validated['propulsion'];
        unset($validated['photos']);

        $vehicle = Vehicle::create($validated);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = ImageOptimizer::store($photo, 'vehicles');
                $vehicle->images()->create(['path' => $path]);
            }

            $vehicle->update(['photo_path' => $vehicle->images()->first()->path]);
        }

        return redirect()->route('partner.dashboard')->with('status', 'Vehicle added successfully.');
    }

    private function authorizePartner(): Partner
    {
        $partner = auth()->user()->partner;

        abort_if(! $partner, 403, 'Only registered partners can manage vehicles.');
        abort_unless($partner->isValidated(), 403, 'Your partner account is not yet validated or has been suspended.');

        return $partner;
    }
}
