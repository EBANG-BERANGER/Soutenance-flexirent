<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\BannedContact;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\Violation;
use App\Services\ContractService;
use App\Services\NotificationService;
use App\Services\PaymentConfirmationService;
use App\Services\ReliabilityService;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Administrator Dashboard: full CRUD oversight of every entity in the
 * system, independent of which user or partner owns it, plus a complete
 * activity history (Ch.5.4.6).
 */
class AdminController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ContractService $contractService,
        private PaymentConfirmationService $paymentConfirmationService,
        private ReliabilityService $reliabilityService,
    ) {
    }

    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'partners_pending' => Partner::where('status', 'pending')->count(),
            'vehicles' => Vehicle::count(),
            'reservations' => Reservation::count(),
            'revenue' => Payment::where('status', 'paid')->sum('amount'),
            'platform_commission' => Reservation::whereNotNull('platform_commission')->sum('platform_commission'),
            'owner_payout' => Reservation::whereNotNull('owner_payout')->sum('owner_payout'),
        ];

        $pendingPartners = Partner::where('status', 'pending')->with('user')->get();
        $activePartners = Partner::where('status', 'validated')->with('user')->get();
        $recentReservations = Reservation::with('client', 'vehicle')->latest()->take(10)->get();
        $notifications = \App\Models\Notification::where('user_id', auth()->id())->latest()->take(15)->get();

        return view('admin.dashboard', compact('stats', 'pendingPartners', 'activePartners', 'recentReservations', 'notifications'));
    }

    public function history(Request $request)
    {
        $logs = AdminActivityLog::with('admin')->latest('created_at')->paginate(30);

        return view('admin.history', compact('logs'));
    }

    // ---------------------------------------------------------------
    // Partners
    // ---------------------------------------------------------------

    /**
     * The submitted data (TIN / national ID number) and the uploaded
     * document are complementary, not redundant: the data guarantees the
     * record is internally consistent, but only a human admin reading the
     * document can judge whether it's actually authentic. Neither check
     * substitutes for the other, which is why both are required before a
     * partner can be validated.
     */
    public function validatePartner(Partner $partner)
    {
        $partner->update(['status' => 'validated']);
        AdminActivityLog::record('validated', $partner, "Validated partner {$partner->company_name}");

        return back()->with('status', "{$partner->company_name} has been validated.");
    }

    public function rejectPartner(Partner $partner)
    {
        $partner->update(['status' => 'rejected']);
        AdminActivityLog::record('rejected', $partner, "Rejected partner {$partner->company_name}");

        return back()->with('status', "{$partner->company_name} has been rejected.");
    }

    public function suspendPartner(Partner $partner)
    {
        $partner->update(['is_suspended' => true]);
        AdminActivityLog::record('suspended', $partner, "Suspended partner {$partner->company_name}");

        return back()->with('status', "{$partner->company_name} has been suspended.");
    }

    public function unsuspendPartner(Partner $partner)
    {
        $partner->update(['is_suspended' => false]);
        AdminActivityLog::record('reinstated', $partner, "Reinstated partner {$partner->company_name}");

        return back()->with('status', "{$partner->company_name} has been reinstated.");
    }

    public function editPartner(Partner $partner)
    {
        $partner->load('user');
        $vehicles = $partner->vehicles()->withCount('reservations')->get();

        return view('admin.partners-edit', compact('partner', 'vehicles'));
    }

    /**
     * Financial history across every vehicle this partner has ever listed:
     * see Reservation::financialHistoryQuery() for what counts and how the
     * from/to/client filters work. The summary block always reflects
     * whichever filters are active, defaulting to the all-time total
     * (same number as Partner::totalReceived()) when none are set.
     */
    public function partnerHistory(Request $request, Partner $partner)
    {
        $filters = $this->historyFilters($request);
        $vehicleIds = $partner->vehicles()->pluck('id');

        $query = Reservation::financialHistoryQuery($vehicleIds, $filters['from'], $filters['to'], $filters['client']);
        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(total_price), 0) as total_price,
            COALESCE(SUM(platform_commission), 0) as platform_commission,
            COALESCE(SUM(owner_payout), 0) as owner_payout,
            COUNT(*) as count
        ')->first();

        $reservations = $query->with('vehicle', 'client')
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.partners-history', compact('partner', 'reservations', 'totals', 'filters'));
    }

    /** Same filters as partnerHistory(), streamed as CSV. */
    public function partnerHistoryExport(Request $request, Partner $partner)
    {
        $filters = $this->historyFilters($request);
        $vehicleIds = $partner->vehicles()->pluck('id');

        $query = Reservation::financialHistoryQuery($vehicleIds, $filters['from'], $filters['to'], $filters['client'])
            ->with('vehicle', 'client')
            ->orderBy('start_date');

        return $this->streamFinancialHistoryCsv(
            $query,
            'flexirent-' . \Illuminate\Support\Str::slug($partner->adminLabel()) . '-history.csv',
            fn ($r) => [$r->vehicle->model, $r->start_date->format('Y-m-d'), $r->end_date->format('Y-m-d'), $r->client->name, $r->client->email, $r->total_price, $r->platform_commission, $r->owner_payout, $r->payment_status],
            ['Vehicle', 'Start date', 'End date', 'Client', 'Client email', 'Total price (RWF)', 'FlexiRent commission (RWF)', 'Owner payout (RWF)', 'Payment status']
        );
    }

    public function updatePartner(Request $request, Partner $partner)
    {
        $isAgency = $request->input('partner_type', $partner->partner_type) === 'agency';

        $validated = $request->validate([
            'partner_type' => ['required', 'in:agency,individual'],
            'company_name' => [$isAgency ? 'required' : 'nullable', 'string', 'max:255'],
            'tin' => [$isAgency ? 'required' : 'nullable', 'string', 'max:50'],
            'national_id_number' => [$isAgency ? 'nullable' : 'required', 'string', 'max:50'],
        ]);

        $partner->update($validated);
        AdminActivityLog::record('updated', $partner, "Updated partner {$partner->company_name}");

        return redirect()->route('admin.dashboard')->with('status', "{$partner->company_name} has been updated.");
    }

    public function destroyPartner(Partner $partner)
    {
        $name = $partner->company_name;
        $partner->delete();
        AdminActivityLog::record('deleted', $partner, "Deleted partner {$name} and its vehicles");

        return redirect()->route('admin.dashboard')->with('status', "{$name} has been deleted.");
    }

    // ---------------------------------------------------------------
    // Reviews
    // ---------------------------------------------------------------

    public function reviews()
    {
        $reviews = Review::with('reservation.client', 'reservation.vehicle')
            ->latest()
            ->paginate(20);

        return view('admin.reviews', compact('reviews'));
    }

    public function moderateReview(Request $request, Review $review)
    {
        $validated = $request->validate([
            'moderation_reason' => ['required', 'string', 'max:1000'],
        ]);

        // The review row is deleted immediately below, so moderation_reason
        // never actually persists on it. The reason is kept durably via
        // AdminActivityLog instead (visible on the admin History page),
        // which is the only place it's queryable after this point.
        AdminActivityLog::record('deleted', $review, "Deleted review #{$review->id}. Reason: {$validated['moderation_reason']}");
        $review->delete();

        return back()->with('status', 'Review removed.');
    }

    // ---------------------------------------------------------------
    // Violations
    // ---------------------------------------------------------------

    public function violations()
    {
        $violations = Violation::with('vehicle', 'reservation.client', 'reportedBy')
            ->latest('violation_date')
            ->paginate(20);

        return view('admin.violations', compact('violations'));
    }

    public function markViolationPaid(Violation $violation)
    {
        $violation->update(['status' => 'paid']);
        AdminActivityLog::record('updated', $violation, "Marked violation {$violation->reference_number} as paid");

        return back()->with('status', "Violation {$violation->reference_number} marked as paid.");
    }

    public function editReview(Review $review)
    {
        return view('admin.reviews-edit', compact('review'));
    }

    public function updateReview(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update($validated);
        AdminActivityLog::record('updated', $review, "Updated review #{$review->id}");

        return redirect()->route('admin.dashboard')->with('status', "Review #{$review->id} updated.");
    }

    // ---------------------------------------------------------------
    // Users
    // ---------------------------------------------------------------

    public function users(Request $request)
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('username', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users-create');
    }

    public function storeUser(Request $request)
    {
        $isPartner = $request->input('role') === 'partner';
        $isAgency = $isPartner && $request->input('partner_type', 'agency') === 'agency';
        $isIndividualPartner = $isPartner && $request->input('partner_type') === 'individual';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:client,partner,admin'],
            'partner_type' => ['required_if:role,partner', 'nullable', 'in:agency,individual'],
            'company_name' => [$isAgency ? 'required' : 'nullable', 'string', 'max:255'],
            'tin' => [$isAgency ? 'required' : 'nullable', 'string', 'max:50'],
            'national_id_number' => [$isIndividualPartner ? 'required' : 'nullable', 'string', 'max:50'],
            'documents_path' => [$isPartner ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            ...collect($validated)->only(['name', 'username', 'email', 'phone', 'address', 'role'])->all(),
            'password' => Hash::make($validated['password']),
        ]);

        if ($validated['role'] === 'partner') {
            Partner::create([
                'user_id' => $user->id,
                'partner_type' => $validated['partner_type'],
                'company_name' => $validated['company_name'] ?? null,
                'tin' => $validated['tin'] ?? null,
                'national_id_number' => $validated['national_id_number'] ?? null,
                'documents_path' => $request->file('documents_path')->store('partner-documents', 'public'),
                'status' => 'pending',
            ]);
        }

        dispatch(function () use ($user) {
            \App\Support\MailRetry::attempt(fn () => $user->sendEmailVerificationNotification());
        });

        AdminActivityLog::record('created', $user, "Created user {$user->name}");

        return redirect()->route('admin.users')->with('status', "{$user->name} has been created.");
    }

    public function editUser(User $user)
    {
        return view('admin.users-edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:client,partner,admin'],
        ]);

        $user->update($validated);
        AdminActivityLog::record('updated', $user, "Updated user {$user->name}");

        return redirect()->route('admin.users')->with('status', "{$user->name} has been updated.");
    }

    public function suspendUser(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be suspended from here.');

        $user->update(['is_suspended' => true]);
        AdminActivityLog::record('suspended', $user, "Suspended user {$user->name}");

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function unsuspendUser(User $user)
    {
        $user->update(['is_suspended' => false]);
        AdminActivityLog::record('reinstated', $user, "Reinstated user {$user->name}");

        return back()->with('status', "{$user->name} has been reinstated.");
    }

    /**
     * Permanent ban: distinct from suspension, irreversible, and blocks
     * re-registration under a different identity via banned_contacts.
     * The user's historical data (reservations, payments, contracts,
     * reviews) is deliberately left intact for audit/accounting.
     */
    public function banUser(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be banned from here.');

        $user->update(['banned_at' => now()]);

        BannedContact::create([
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        AdminActivityLog::record('banned', $user, "Permanently banned user {$user->name}");

        return back()->with('status', "{$user->name} has been permanently banned.");
    }

    public function destroyUser(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be deleted from here.');

        $name = $user->name;
        $user->delete();
        AdminActivityLog::record('deleted', $user, "Deleted user {$name}");

        return redirect()->route('admin.users')->with('status', "{$name} has been deleted.");
    }

    // ---------------------------------------------------------------
    // Vehicles
    // ---------------------------------------------------------------

    public function vehicles(Request $request)
    {
        $vehicles = Vehicle::with('partner.user', 'category')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.vehicles', compact('vehicles'));
    }

    public function createVehicle()
    {
        $categories = Category::orderBy('name')->get();
        $partners = Partner::orderBy('company_name')->get();

        return view('admin.vehicles-create', compact('categories', 'partners'));
    }

    public function storeVehicle(Request $request)
    {
        $isElectric = $request->input('car_type') === 'electric';

        $validated = $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:' . Vehicle::minAcceptedYear(), 'max:' . (date('Y') + 1)],
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
            'status' => ['required', 'in:available,booked,maintenance'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:12288'],
        ], [
            'year.min' => 'Vehicles older than 14 years are not accepted on FlexiRent.',
        ]);

        $validated['partner_id'] = $validated['partner_id'] ?: null;
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

        AdminActivityLog::record('created', $vehicle, "Created vehicle {$vehicle->model}");

        return redirect()->route('admin.vehicles')->with('status', "{$vehicle->model} has been added.");
    }

    public function editVehicle(Vehicle $vehicle)
    {
        $categories = Category::orderBy('name')->get();
        $partners = Partner::orderBy('company_name')->get();
        $vehicle->load('images');

        return view('admin.vehicles-edit', compact('vehicle', 'categories', 'partners'));
    }

    /**
     * Financial history for a single vehicle: see
     * Reservation::financialHistoryQuery() for what counts and how the
     * from/to/client filters work. The summary block always reflects
     * whichever filters are active (all-time totals when none are set).
     */
    public function vehicleHistory(Request $request, Vehicle $vehicle)
    {
        $filters = $this->historyFilters($request);

        $query = Reservation::financialHistoryQuery([$vehicle->id], $filters['from'], $filters['to'], $filters['client']);
        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(total_price), 0) as total_price,
            COALESCE(SUM(platform_commission), 0) as platform_commission,
            COALESCE(SUM(owner_payout), 0) as owner_payout,
            COUNT(*) as count
        ')->first();

        $reservations = $query->with('client')
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.vehicles-history', compact('vehicle', 'reservations', 'totals', 'filters'));
    }

    /**
     * Same filters as vehicleHistory(), streamed as CSV instead of
     * rendered as a page, so an admin can pull one vehicle's rental
     * history into a spreadsheet without pulling the whole reservations
     * table.
     */
    public function vehicleHistoryExport(Request $request, Vehicle $vehicle)
    {
        $filters = $this->historyFilters($request);
        $query = Reservation::financialHistoryQuery([$vehicle->id], $filters['from'], $filters['to'], $filters['client'])
            ->with('client')
            ->orderBy('start_date');

        return $this->streamFinancialHistoryCsv(
            $query,
            'flexirent-' . \Illuminate\Support\Str::slug($vehicle->model) . '-history.csv',
            fn ($r) => [$r->start_date->format('Y-m-d'), $r->end_date->format('Y-m-d'), $r->client->name, $r->client->email, $r->total_price, $r->platform_commission, $r->owner_payout, $r->payment_status]
        );
    }

    /**
     * Shared by vehicleHistory() and partnerHistory() so the two pages'
     * filter query params (and defaults) never drift apart.
     */
    private function historyFilters(Request $request): array
    {
        return [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'client' => $request->input('client'),
        ];
    }

    /**
     * Streams a reservation query as CSV using a caller-supplied row
     * mapper, chunked so exporting a long history doesn't load every
     * matching reservation into memory at once.
     */
    private function streamFinancialHistoryCsv($query, string $filename, callable $toRow, array $header = ['Start date', 'End date', 'Client', 'Client email', 'Total price (RWF)', 'FlexiRent commission (RWF)', 'Owner payout (RWF)', 'Payment status'])
    {
        return response()->streamDownload(function () use ($query, $toRow, $header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);

            $query->chunk(200, function ($rows) use ($out, $toRow) {
                foreach ($rows as $row) {
                    fputcsv($out, $toRow($row));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updateVehicle(Request $request, Vehicle $vehicle)
    {
        $isElectric = $request->input('car_type') === 'electric';

        $validated = $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:' . Vehicle::minAcceptedYear(), 'max:' . (date('Y') + 1)],
            'propulsion' => [$isElectric ? 'nullable' : 'required', 'nullable', 'in:diesel,essence'],
            'car_type' => ['required', 'in:normal,hybrid,electric'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number,' . $vehicle->id],
            'chassis_number' => ['required', 'string', 'max:50'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'price_per_week' => ['nullable', 'numeric', 'min:0'],
            'price_per_month' => ['nullable', 'numeric', 'min:0'],
            'min_rental_days' => ['required', 'integer', 'min:1', 'max:365'],
            'driver_fee_per_day' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,booked,maintenance'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:12288'],
        ], [
            'year.min' => 'Vehicles older than 14 years are not accepted on FlexiRent.',
        ]);
        unset($validated['photos']);
        $validated['partner_id'] = $validated['partner_id'] ?: null;
        $validated['propulsion'] = $isElectric ? null : $validated['propulsion'];

        // A vehicle committed to a pending/confirmed reservation can't be
        // pulled from availability out from under the client. Price and
        // photos remain editable, only the maintenance switch is blocked.
        if ($validated['status'] === 'maintenance' && $vehicle->status !== 'maintenance' && $vehicle->isLockedByActiveReservation()) {
            return back()->withErrors(['status' => 'This vehicle has a pending or confirmed reservation and cannot be marked as maintenance.'])->withInput();
        }

        $watchedFields = [
            'price_per_day' => 'Price per day',
            'price_per_week' => 'Price per week',
            'price_per_month' => 'Price per month',
            'min_rental_days' => 'Minimum rental days',
            'driver_fee_per_day' => 'Driver fee per day',
            'deposit_amount' => 'Deposit',
            'status' => 'Status',
        ];
        $before = $vehicle->only(array_keys($watchedFields));

        $vehicle->update($validated);

        $changes = [];
        foreach ($watchedFields as $field => $label) {
            if ((string) $before[$field] !== (string) $vehicle->{$field}) {
                $changes[] = "{$label}: {$before[$field]} → {$vehicle->{$field}}";
            }
        }
        if ($changes && $vehicle->partner) {
            $this->notificationService->notifyPartnerVehicleUpdated($vehicle, $changes);
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = ImageOptimizer::store($photo, 'vehicles');
                $vehicle->images()->create(['path' => $path]);
            }

            if (! $vehicle->photo_path) {
                $vehicle->update(['photo_path' => $vehicle->images()->first()->path]);
            }
        }

        AdminActivityLog::record('updated', $vehicle, "Updated vehicle {$vehicle->model}");

        return redirect()->route('admin.vehicles')->with('status', "{$vehicle->model} has been updated.");
    }

    public function destroyVehicleImage(VehicleImage $image)
    {
        $vehicle = $image->vehicle;
        Storage::disk('public')->delete($image->path);
        $wasCover = $vehicle->photo_path === $image->path;
        $image->delete();

        if ($wasCover) {
            $vehicle->update(['photo_path' => $vehicle->images()->first()?->path]);
        }

        AdminActivityLog::record('updated', $vehicle, "Removed a photo from {$vehicle->model}");

        return back()->with('status', 'Photo removed.');
    }

    public function suspendVehicle(Vehicle $vehicle)
    {
        $vehicle->update(['is_suspended' => true]);
        AdminActivityLog::record('suspended', $vehicle, "Suspended vehicle {$vehicle->model}");

        return back()->with('status', "{$vehicle->model} has been suspended and removed from the public catalog.");
    }

    public function unsuspendVehicle(Vehicle $vehicle)
    {
        $vehicle->update(['is_suspended' => false]);
        AdminActivityLog::record('reinstated', $vehicle, "Reinstated vehicle {$vehicle->model}");

        return back()->with('status', "{$vehicle->model} is visible in the catalog again.");
    }

    /**
     * One-click status change from the vehicle list, without opening the
     * full edit form. A vehicle currently booked by a confirmed reservation
     * can't be forced available by hand: syncVehicleAvailability() resolves
     * it to whatever the active reservations actually justify.
     */
    public function toggleVehicleStatus(Vehicle $vehicle)
    {
        if ($vehicle->status === 'maintenance') {
            $this->paymentConfirmationService->syncVehicleAvailability($vehicle, force: true);
        } else {
            abort_if($vehicle->isLockedByActiveReservation(), 422, 'This vehicle has a pending or confirmed reservation and cannot be marked unavailable.');
            $vehicle->update(['status' => 'maintenance']);
        }

        $vehicle->refresh();
        AdminActivityLog::record('updated', $vehicle, "Set {$vehicle->model} status to {$vehicle->status}");

        return back()->with('status', "{$vehicle->model} is now marked as {$vehicle->status}.");
    }

    /**
     * Pick which vehicle's photo is shown as the homepage's featured image.
     * Only one vehicle can hold the spot at a time, so setting one clears
     * the flag on every other vehicle first.
     */
    public function setFeaturedVehicle(Vehicle $vehicle)
    {
        abort_if(! $vehicle->images()->exists() && ! $vehicle->photo_path, 422, 'This vehicle has no photo to feature yet. Add one first.');

        Vehicle::where('is_featured', true)->update(['is_featured' => false]);
        $vehicle->update(['is_featured' => true]);
        AdminActivityLog::record('updated', $vehicle, "Set {$vehicle->model} as the homepage featured photo");

        return back()->with('status', "{$vehicle->model} is now the homepage's featured photo.");
    }

    public function clearFeaturedVehicle(Vehicle $vehicle)
    {
        $vehicle->update(['is_featured' => false]);
        AdminActivityLog::record('updated', $vehicle, "Removed {$vehicle->model} as the homepage featured photo");

        return back()->with('status', "{$vehicle->model} is no longer the homepage's featured photo.");
    }

    public function destroyVehicle(Vehicle $vehicle)
    {
        abort_if($vehicle->isLockedByActiveReservation(), 422, 'This vehicle has a pending or confirmed reservation and cannot be deleted.');

        $label = "{$vehicle->model}";
        $vehicle->delete();
        AdminActivityLog::record('deleted', $vehicle, "Deleted vehicle {$label}");

        return redirect()->route('admin.vehicles')->with('status', "{$label} has been deleted.");
    }

    // ---------------------------------------------------------------
    // Reservations
    // ---------------------------------------------------------------

    public function reservations(Request $request)
    {
        $reservations = Reservation::with('client', 'vehicle')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('start_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('end_date', '<=', $request->to))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations', compact('reservations'));
    }

    public function editReservation(Reservation $reservation)
    {
        return view('admin.reservations-edit', compact('reservation'));
    }

    public function updateReservation(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'with_driver' => ['boolean'],
            'status' => ['required', 'in:pending,confirmed,cancelled,completed,suspended'],
        ]);

        $withDriver = $request->boolean('with_driver');
        $days = \Illuminate\Support\Carbon::parse($validated['start_date'])
            ->diffInDays(\Illuminate\Support\Carbon::parse($validated['end_date'])) ?: 1;
        $wasCompleted = $reservation->status === 'completed';

        // Locked + re-checked here for the same reason store() locks the
        // vehicle: without it, this and another concurrent booking/edit on
        // the same vehicle could both read "available" before either commits.
        $updated = DB::transaction(function () use ($reservation, $validated, $withDriver, $days) {
            $lockedVehicle = Vehicle::whereKey($reservation->vehicle_id)->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->isAvailableBetween($validated['start_date'], $validated['end_date'], $reservation->id)) {
                return false;
            }

            // Recompute at the rate actually agreed at booking, not the
            // vehicle's current live price (see Reservation::chargedRateBreakdown()).
            $pricePerDay = (float) ($reservation->price_per_day_snapshot ?? $lockedVehicle->price_per_day);
            $driverFeePerDay = (float) ($reservation->driver_fee_per_day_snapshot ?? $lockedVehicle->driver_fee_per_day);
            $deposit = (float) ($reservation->vehicle_deposit_snapshot ?? $lockedVehicle->deposit_amount);
            $subtotal = $pricePerDay * $days;
            $driverFee = $withDriver ? $driverFeePerDay * $days : 0;

            $reservation->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'with_driver' => $withDriver,
                'status' => $validated['status'],
                'total_price' => $subtotal + $driverFee + $deposit + (float) $reservation->delivery_fee,
            ]);

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['start_date' => 'This vehicle is not available for the selected dates.']);
        }

        // Only on the actual pending/confirmed → completed transition, so
        // re-saving an already-completed reservation doesn't double-award.
        if ($validated['status'] === 'completed' && ! $wasCompleted) {
            $this->reliabilityService->awardCompleted($reservation->fresh('client'));
        }

        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        AdminActivityLog::record('updated', $reservation, "Updated reservation #{$reservation->id}");

        return redirect()->route('admin.reservations')->with('status', "Reservation #{$reservation->id} has been updated.");
    }

    public function cancelReservation(Reservation $reservation)
    {
        abort_if($reservation->status === 'completed', 422, 'A completed reservation cannot be cancelled.');

        $reservation->update(['status' => 'cancelled']);
        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        $this->notificationService->notifyReservationCancelledByAdmin($reservation);
        AdminActivityLog::record('cancelled', $reservation, "Cancelled reservation #{$reservation->id}");

        return back()->with('status', "Reservation #{$reservation->id} has been cancelled.");
    }

    /**
     * Temporarily pause an active rental (e.g. the vehicle needs unplanned
     * maintenance mid-rental) without cancelling the booking outright. The
     * vehicle frees up immediately since syncVehicleAvailability() only
     * counts 'confirmed' reservations as booking it out.
     */
    /**
     * Dispute-resolution tool: restores a client-cancelled reservation to
     * whatever it was before cancellation. Deliberately not exposed to
     * clients or partners: reactivating after a cancellation fee was
     * already charged is a judgment call only FlexiRent staff should make.
     * Any fee retained at cancellation is left untouched; refund it manually
     * via the payment record if that's the intended resolution.
     */
    public function reactivateReservation(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->status === 'cancelled', 422, 'Only a cancelled reservation can be reactivated.');
        abort_unless($reservation->status_before_cancellation, 422, 'This reservation has no prior status to restore.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $restoredStatus = $reservation->status_before_cancellation;

        $reactivated = DB::transaction(function () use ($reservation, $restoredStatus) {
            $lockedVehicle = Vehicle::whereKey($reservation->vehicle_id)->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->isAvailableBetween($reservation->start_date, $reservation->end_date, $reservation->id)) {
                return false;
            }

            $reservation->update([
                'status' => $restoredStatus,
                'status_before_cancellation' => null,
            ]);

            return true;
        });

        if (! $reactivated) {
            return back()->withErrors(['reason' => 'This vehicle is no longer available for these dates: another reservation now conflicts.']);
        }

        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        AdminActivityLog::record('reactivated', $reservation, "Reactivated reservation #{$reservation->id} (restored to '{$restoredStatus}'). Reason: {$validated['reason']}");

        return back()->with('status', "Reservation #{$reservation->id} has been reactivated.");
    }

    public function suspendReservation(Reservation $reservation)
    {
        abort_unless($reservation->status === 'confirmed', 422, 'Only an active (confirmed) reservation can be suspended.');

        $reservation->update(['status' => 'suspended']);
        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        AdminActivityLog::record('updated', $reservation, "Suspended reservation #{$reservation->id}");

        return back()->with('status', "Reservation #{$reservation->id} has been suspended.");
    }

    public function resumeReservation(Reservation $reservation)
    {
        abort_unless($reservation->status === 'suspended', 422, 'Only a suspended reservation can be resumed.');

        // The vehicle frees up the moment a reservation is suspended
        // (syncVehicleAvailability only counts 'confirmed' ones), so it may
        // have been booked by someone else in the meantime. Re-check before
        // restoring, same as reactivateReservation() does after a cancellation.
        $resumed = DB::transaction(function () use ($reservation) {
            $lockedVehicle = Vehicle::whereKey($reservation->vehicle_id)->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->isAvailableBetween($reservation->start_date, $reservation->end_date, $reservation->id)) {
                return false;
            }

            $reservation->update(['status' => 'confirmed']);

            return true;
        });

        if (! $resumed) {
            return back()->withErrors(['reservation' => 'This vehicle is no longer available for these dates: another reservation now conflicts.']);
        }

        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        AdminActivityLog::record('updated', $reservation, "Resumed reservation #{$reservation->id}");

        return back()->with('status', "Reservation #{$reservation->id} has been resumed.");
    }

    public function destroyReservation(Reservation $reservation)
    {
        $id = $reservation->id;
        $vehicle = $reservation->vehicle;
        $reservation->delete();
        $this->paymentConfirmationService->syncVehicleAvailability($vehicle);
        AdminActivityLog::record('deleted', $reservation, "Deleted reservation #{$id}");

        return redirect()->route('admin.reservations')->with('status', "Reservation #{$id} has been deleted.");
    }


    // ---------------------------------------------------------------
    // Payments
    // ---------------------------------------------------------------

    public function payments(Request $request)
    {
        $payments = $this->filteredPaymentsQuery($request)
            ->orderBy('created_at', $request->get('sort') === 'oldest' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments', [
            'payments' => $payments,
            'vehicles' => Vehicle::orderBy('model')->get(['id', 'model', 'year']),
            'partners' => Partner::with('user')->get(),
        ]);
    }

    public function paymentsExport(Request $request)
    {
        $query = $this->filteredPaymentsQuery($request)->orderBy('created_at', $request->get('sort') === 'oldest' ? 'asc' : 'desc');

        return $this->streamFinancialHistoryCsv(
            $query,
            'flexirent-transactions.csv',
            fn (Payment $p) => [
                $p->created_at->format('Y-m-d H:i'),
                $p->reservation_id,
                $p->reservation->vehicle->model,
                $p->reservation->client->name,
                $p->reservation->client->email,
                $p->reservation->vehicle->partner?->adminLabel() ?? 'FlexiRent',
                $p->method,
                $p->type,
                $p->amount,
                $p->transaction_ref,
                $p->status,
            ],
            ['Date', 'Reservation', 'Vehicle', 'Client', 'Client email', 'Partner', 'Method', 'Type', 'Amount (RWF)', 'Reference', 'Status']
        );
    }

    /**
     * Cross-filterable base query behind both the payments list and its CSV
     * export, so the two can never show different transactions for the same
     * filters. Vehicle/partner/client can all be combined with a date range
     * at once, unlike the single-criterion filters on the per-vehicle and
     * per-partner history pages.
     */
    private function filteredPaymentsQuery(Request $request)
    {
        return Payment::with('reservation.client', 'reservation.vehicle.partner.user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('vehicle_id'), fn ($q) => $q->whereHas(
                'reservation', fn ($r) => $r->where('vehicle_id', $request->vehicle_id)
            ))
            ->when($request->filled('partner_id'), fn ($q) => $q->whereHas(
                'reservation.vehicle', fn ($v) => $v->where('partner_id', $request->partner_id)
            ))
            ->when($request->filled('client'), fn ($q) => $q->whereHas('reservation.client', function ($c) use ($request) {
                $c->where('name', 'like', "%{$request->client}%")
                    ->orWhere('email', 'like', "%{$request->client}%");
            }))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
    }

    public function markPaymentReceived(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'transaction_ref' => ['nullable', 'string', 'max:100'],
        ]);

        $reservation = $this->paymentConfirmationService->confirm($payment, $validated['transaction_ref'] ?? null);
        AdminActivityLog::record('confirmed', $payment, "Marked payment #{$payment->id} as received");

        return back()->with('status', "Payment for reservation #{$reservation->id} marked as received.");
    }

    public function destroyPayment(Payment $payment)
    {
        $id = $payment->id;
        $payment->delete();
        AdminActivityLog::record('deleted', $payment, "Deleted payment #{$id}");

        return redirect()->route('admin.payments')->with('status', "Payment #{$id} has been deleted.");
    }

    // ---------------------------------------------------------------
    // Categories
    // ---------------------------------------------------------------

    public function categories()
    {
        $categories = Category::withCount('vehicles')->orderBy('name')->get();

        return view('admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        $category = Category::create($validated);
        AdminActivityLog::record('created', $category, "Created category {$category->name}");

        return back()->with('status', "Category {$category->name} has been created.");
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,' . $category->id],
        ]);

        $category->update($validated);
        AdminActivityLog::record('updated', $category, "Renamed category to {$category->name}");

        return back()->with('status', "Category has been updated.");
    }

    public function destroyCategory(Category $category)
    {
        abort_if($category->vehicles()->exists(), 422, 'This category still has vehicles assigned to it.');

        $name = $category->name;
        $category->delete();
        AdminActivityLog::record('deleted', $category, "Deleted category {$name}");

        return back()->with('status', "Category {$name} has been deleted.");
    }

    // ---------------------------------------------------------------
    // Contracts
    // ---------------------------------------------------------------

    public function contracts(Request $request)
    {
        $contracts = Contract::with('reservation.client', 'reservation.vehicle')
            ->latest()
            ->paginate(20);

        return view('admin.contracts', compact('contracts'));
    }

    public function destroyContract(Contract $contract)
    {
        Storage::disk('public')->delete($contract->pdf_path);
        $reservationId = $contract->reservation_id;
        $contract->delete();
        AdminActivityLog::record('deleted', $contract, "Deleted contract for reservation #{$reservationId}");

        return back()->with('status', "Contract for reservation #{$reservationId} has been deleted.");
    }
}
