<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/fleet', [VehicleController::class, 'fleet'])->name('vehicles.fleet');
Route::get('/vehicles/filter', [VehicleController::class, 'filter'])->name('vehicles.filter');
Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

// Static marketing / legal pages (footer)
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/legal-notice', [PageController::class, 'legalNotice'])->name('legal-notice');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,1');

// Guest auth (client / partner)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Guest auth (admin) - entirely separate, unlinked portal, never shares a form or flow with client/partner login
Route::middleware('guest')->prefix('control-panel')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt')->middleware('throttle:5,1');

    Route::get('/forgot-password', [AdminAuthController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [AdminAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AdminAuthController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
});

// Authenticated (any role)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password')->middleware('throttle:5,1');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/vehicles/{vehicle}/reserve', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/vehicles/{vehicle}/reserve', [ReservationController::class, 'store'])->name('reservations.store')->middleware('throttle:10,1');

    Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('/reservations/{reservation}/payment', [ReservationController::class, 'paymentForm'])->name('reservations.payment');
    Route::post('/reservations/{reservation}/payment', [PaymentController::class, 'store'])->name('payments.store')->middleware('throttle:10,1');
    Route::get('/reservations/{reservation}/payment/{payment}/confirming', [PaymentController::class, 'confirming'])->name('payments.confirming');
    Route::post('/reservations/{reservation}/payment/{payment}/simulate-confirm', [PaymentController::class, 'simulateConfirm'])->name('payments.simulate-confirm')->middleware('throttle:10,1');

    Route::get('/reservations/{reservation}/contract', [ContractController::class, 'download'])->name('contracts.download');

    Route::get('/reservations/{reservation}/review', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reservations/{reservation}/review', [ReviewController::class, 'store'])->name('reviews.store')->middleware('throttle:10,1');
});

// Partner only
Route::middleware(['auth', 'role:partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/dashboard', [PartnerController::class, 'dashboard'])->name('dashboard');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::post('/vehicles/{vehicle}/toggle-availability', [PartnerController::class, 'toggleVehicleAvailability'])->name('vehicles.toggle-availability');
    Route::post('/vehicles/{vehicle}/min-rental-days', [PartnerController::class, 'updateMinRentalDays'])->name('vehicles.min-rental-days.update');
    Route::post('/vehicles/{vehicle}/violations', [PartnerController::class, 'storeViolation'])->name('vehicles.violations.store');
    Route::post('/notifications/reply', [PartnerController::class, 'replyToAdmin'])->name('notifications.reply');
});

// Admin only - full CRUD across every entity in the system, plus history
Route::middleware(['auth', 'role:admin'])->prefix('control-panel')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/history', [AdminController::class, 'history'])->name('history');

    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings');
    Route::post('/settings/cover-photo', [AdminSettingsController::class, 'updateCoverPhoto'])->name('settings.cover-photo.update');
    Route::delete('/settings/cover-photo', [AdminSettingsController::class, 'destroyCoverPhoto'])->name('settings.cover-photo.destroy');

    Route::post('/partners/{partner}/validate', [AdminController::class, 'validatePartner'])->name('partners.validate');
    Route::post('/partners/{partner}/reject', [AdminController::class, 'rejectPartner'])->name('partners.reject');
    Route::post('/partners/{partner}/suspend', [AdminController::class, 'suspendPartner'])->name('partners.suspend');
    Route::post('/partners/{partner}/unsuspend', [AdminController::class, 'unsuspendPartner'])->name('partners.unsuspend');
    Route::get('/partners/{partner}/document', [AdminController::class, 'partnerDocument'])->name('partners.document');
    Route::get('/partners/{partner}/edit', [AdminController::class, 'editPartner'])->name('partners.edit');
    Route::get('/partners/{partner}/history', [AdminController::class, 'partnerHistory'])->name('partners.history');
    Route::get('/partners/{partner}/history/export', [AdminController::class, 'partnerHistoryExport'])->name('partners.history.export');
    Route::put('/partners/{partner}', [AdminController::class, 'updatePartner'])->name('partners.update');
    Route::delete('/partners/{partner}', [AdminController::class, 'destroyPartner'])->name('partners.destroy');

    Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
    Route::delete('/reviews/{review}', [AdminController::class, 'moderateReview'])->name('reviews.moderate');
    Route::get('/reviews/{review}/edit', [AdminController::class, 'editReview'])->name('reviews.edit');
    Route::put('/reviews/{review}', [AdminController::class, 'updateReview'])->name('reviews.update');

    Route::get('/violations', [AdminController::class, 'violations'])->name('violations');
    Route::post('/violations/{violation}/mark-paid', [AdminController::class, 'markViolationPaid'])->name('violations.mark-paid');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/unsuspend', [AdminController::class, 'unsuspendUser'])->name('users.unsuspend');
    Route::post('/users/{user}/ban', [AdminController::class, 'banUser'])->name('users.ban');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    Route::get('/vehicles', [AdminController::class, 'vehicles'])->name('vehicles');
    Route::get('/vehicles/create', [AdminController::class, 'createVehicle'])->name('vehicles.create');
    Route::post('/vehicles', [AdminController::class, 'storeVehicle'])->name('vehicles.store');
    Route::post('/vehicles/{vehicle}/suspend', [AdminController::class, 'suspendVehicle'])->name('vehicles.suspend');
    Route::post('/vehicles/{vehicle}/unsuspend', [AdminController::class, 'unsuspendVehicle'])->name('vehicles.unsuspend');
    Route::post('/vehicles/{vehicle}/toggle-status', [AdminController::class, 'toggleVehicleStatus'])->name('vehicles.toggle-status');
    Route::post('/vehicles/{vehicle}/set-featured', [AdminController::class, 'setFeaturedVehicle'])->name('vehicles.set-featured');
    Route::post('/vehicles/{vehicle}/clear-featured', [AdminController::class, 'clearFeaturedVehicle'])->name('vehicles.clear-featured');
    Route::get('/vehicles/{vehicle}/edit', [AdminController::class, 'editVehicle'])->name('vehicles.edit');
    Route::get('/vehicles/{vehicle}/history', [AdminController::class, 'vehicleHistory'])->name('vehicles.history');
    Route::get('/vehicles/{vehicle}/history/export', [AdminController::class, 'vehicleHistoryExport'])->name('vehicles.history.export');
    Route::put('/vehicles/{vehicle}', [AdminController::class, 'updateVehicle'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [AdminController::class, 'destroyVehicle'])->name('vehicles.destroy');
    Route::delete('/vehicles/images/{image}', [AdminController::class, 'destroyVehicleImage'])->name('vehicles.images.destroy');

    Route::get('/reservations', [AdminController::class, 'reservations'])->name('reservations');
    Route::post('/reservations/{reservation}/cancel', [AdminController::class, 'cancelReservation'])->name('reservations.cancel');
    Route::post('/reservations/{reservation}/reactivate', [AdminController::class, 'reactivateReservation'])->name('reservations.reactivate');
    Route::post('/reservations/{reservation}/suspend', [AdminController::class, 'suspendReservation'])->name('reservations.suspend');
    Route::post('/reservations/{reservation}/resume', [AdminController::class, 'resumeReservation'])->name('reservations.resume');
    Route::get('/reservations/{reservation}/edit', [AdminController::class, 'editReservation'])->name('reservations.edit');
    Route::put('/reservations/{reservation}', [AdminController::class, 'updateReservation'])->name('reservations.update');
    Route::delete('/reservations/{reservation}', [AdminController::class, 'destroyReservation'])->name('reservations.destroy');

    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    Route::get('/payments/export', [AdminController::class, 'paymentsExport'])->name('payments.export');
    Route::post('/payments/{payment}/mark-received', [AdminController::class, 'markPaymentReceived'])->name('payments.mark-received');
    Route::delete('/payments/{payment}', [AdminController::class, 'destroyPayment'])->name('payments.destroy');

    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');

    Route::get('/contracts', [AdminController::class, 'contracts'])->name('contracts');
    Route::delete('/contracts/{contract}', [AdminController::class, 'destroyContract'])->name('contracts.destroy');
});
