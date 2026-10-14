@extends('layouts.admin')
@section('title', 'Add Vehicle')
@section('content')
<h2 class="mb-4">Add a Vehicle</h2>

<div class="row">
<form method="POST" action="{{ route('admin.vehicles.store') }}" enctype="multipart/form-data" class="col-12 col-lg-8">
    @csrf
    <div class="mb-3">
        <label class="form-label">Owner</label>
        <select name="partner_id" class="form-select @error('partner_id') is-invalid @enderror">
            <option value="" @selected(!old('partner_id'))>FlexiRent (owned directly, no partner)</option>
            @foreach($partners as $partner)
                <option value="{{ $partner->id }}" @selected(old('partner_id') == $partner->id)>
                    {{ $partner->company_name ?: $partner->user->name . ' (individual)' }} ({{ ucfirst($partner->status) }})
                </option>
            @endforeach
        </select>
        @error('partner_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <p class="form-text">Pick a partner if this vehicle belongs to one of their fleets, or leave it as FlexiRent if the platform owns the car directly.</p>
    </div>
    <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}" required>
            @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year') }}" min="{{ \App\Models\Vehicle::minAcceptedYear() }}" max="{{ date('Y') + 1 }}" required>
            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <p class="form-text">Vehicles older than 14 years (before {{ \App\Models\Vehicle::minAcceptedYear() }}) are not accepted.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Car type</label>
            <select name="car_type" id="car_type" class="form-select @error('car_type') is-invalid @enderror" required onchange="fxTogglePropulsion()">
                <option value="normal" @selected(old('car_type', 'normal') == 'normal')>Normal</option>
                <option value="hybrid" @selected(old('car_type') == 'hybrid')>Hybrid</option>
                <option value="electric" @selected(old('car_type') == 'electric')>100% Electric</option>
            </select>
            @error('car_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 mb-3" id="propulsion-field">
            <label class="form-label">Propulsion</label>
            <select name="propulsion" id="propulsion" class="form-select @error('propulsion') is-invalid @enderror">
                <option value="essence" @selected(old('propulsion', 'essence') == 'essence')>Essence (Petrol)</option>
                <option value="diesel" @selected(old('propulsion') == 'diesel')>Diesel</option>
            </select>
            @error('propulsion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <p class="form-text">For hybrid vehicles, pick the engine that pairs with the electric motor.</p>
        </div>
    </div>
    <script>
    function fxTogglePropulsion() {
        var isElectric = document.getElementById('car_type').value === 'electric';
        document.getElementById('propulsion-field').classList.toggle('d-none', isElectric);
        document.getElementById('propulsion').required = !isElectric;
    }
    document.addEventListener('DOMContentLoaded', fxTogglePropulsion);
    </script>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Plate number</label>
            <input type="text" name="plate_number" class="form-control @error('plate_number') is-invalid @enderror" value="{{ old('plate_number') }}" required>
            @error('plate_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <p class="form-text">Must be unique: check this isn't already used by another vehicle.</p>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Chassis number</label>
            <input type="text" name="chassis_number" class="form-control @error('chassis_number') is-invalid @enderror" value="{{ old('chassis_number') }}" required>
            @error('chassis_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / day (RWF)</label>
            <input type="number" name="price_per_day" class="form-control @error('price_per_day') is-invalid @enderror" value="{{ old('price_per_day') }}" required>
            @error('price_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / week (RWF)</label>
            <input type="number" name="price_per_week" class="form-control @error('price_per_week') is-invalid @enderror" value="{{ old('price_per_week') }}" min="0">
            @error('price_per_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <p class="form-text">Optional. Leave blank to bill weekly stays at the day rate.</p>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / month (RWF)</label>
            <input type="number" name="price_per_month" class="form-control @error('price_per_month') is-invalid @enderror" value="{{ old('price_per_month') }}" min="0">
            @error('price_per_month') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <p class="form-text">Optional. Leave blank to bill monthly stays at the day rate.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Security deposit / caution (RWF)</label>
            <input type="number" name="deposit_amount" class="form-control @error('deposit_amount') is-invalid @enderror" value="{{ old('deposit_amount') }}" min="0">
            @error('deposit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Minimum rental (days)</label>
        <input type="number" name="min_rental_days" class="form-control @error('min_rental_days') is-invalid @enderror" value="{{ old('min_rental_days', 1) }}" min="1" max="365" style="max-width:160px;">
        @error('min_rental_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <p class="form-text">Shorter bookings will be blocked. Leave at 1 for no minimum. The partner can also change this from their dashboard.</p>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="available" @selected(old('status', 'available') == 'available')>Available</option>
            <option value="booked" @selected(old('status') == 'booked')>Booked</option>
            <option value="maintenance" @selected(old('status') == 'maintenance')>Maintenance</option>
        </select>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" name="driver_available" value="1" class="form-check-input" id="driver" @checked(old('driver_available')) onchange="document.getElementById('driver-fee-field').classList.toggle('d-none', !this.checked)">
        <label class="form-check-label" for="driver">Driver available</label>
    </div>
    <div class="mb-3 {{ old('driver_available') ? '' : 'd-none' }}" id="driver-fee-field">
        <label class="form-label">Driver fee per day (RWF)</label>
        <input type="number" name="driver_fee_per_day" class="form-control @error('driver_fee_per_day') is-invalid @enderror" value="{{ old('driver_fee_per_day') }}" min="0">
        @error('driver_fee_per_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Photos</label>
        <div class="fx-photo-picker" data-photo-picker>
            <div class="fx-photo-grid" data-photo-grid></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-add>+ Add photos</button>
            <input type="file" name="photos[]" accept="image/*" multiple class="d-none" data-photo-input>
        </div>
        <p class="form-text">Click "Add photos" as many times as you like: each selection is added to the set. The first one becomes the cover image shown in the catalog.</p>
    </div>
    <button class="btn btn-gold">Add vehicle</button>
    <a href="{{ route('admin.vehicles') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>
@endsection
