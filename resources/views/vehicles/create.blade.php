@extends('layouts.app')
@section('title', 'Add a Vehicle')
@section('content')
<h2 class="mb-4">Add a Vehicle to Your Fleet</h2>
<div class="row">
<form method="POST" action="{{ route('partner.vehicles.store') }}" enctype="multipart/form-data" class="col-12 col-lg-8">
    @csrf
    <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" min="{{ \App\Models\Vehicle::minAcceptedYear() }}" max="{{ date('Y') + 1 }}" required>
            <p class="form-text">FlexiRent does not accept vehicles older than 14 years (i.e. before {{ \App\Models\Vehicle::minAcceptedYear() }}).</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Car type</label>
            <select name="car_type" id="car_type" class="form-select" required onchange="fxTogglePropulsion()">
                <option value="normal" selected>Normal</option>
                <option value="hybrid">Hybrid</option>
                <option value="electric">100% Electric</option>
            </select>
        </div>
        <div class="col-md-6 mb-3" id="propulsion-field">
            <label class="form-label">Propulsion</label>
            <select name="propulsion" id="propulsion" class="form-select">
                <option value="essence" selected>Essence (Petrol)</option>
                <option value="diesel">Diesel</option>
            </select>
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
            <input type="text" name="plate_number" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Chassis number</label>
            <input type="text" name="chassis_number" class="form-control" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / day (RWF)</label>
            <input type="number" name="price_per_day" class="form-control" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / week (RWF)</label>
            <input type="number" name="price_per_week" class="form-control" min="0">
            <p class="form-text">Optional. Leave blank to bill weekly stays at the day rate.</p>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / month (RWF)</label>
            <input type="number" name="price_per_month" class="form-control" min="0">
            <p class="form-text">Optional. Leave blank to bill monthly stays at the day rate.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Security deposit / caution (RWF)</label>
            <input type="number" name="deposit_amount" class="form-control" min="0">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Minimum rental (days)</label>
        <input type="number" name="min_rental_days" class="form-control" min="1" max="365" value="1" style="max-width:160px;">
        <p class="form-text">Shorter bookings will be blocked. Leave at 1 for no minimum. You can change this later from your dashboard.</p>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" name="driver_available" value="1" class="form-check-input" id="driver" onchange="document.getElementById('driver-fee-field').classList.toggle('d-none', !this.checked)">
        <label class="form-check-label" for="driver">Driver available</label>
    </div>
    <div class="mb-3 d-none" id="driver-fee-field">
        <label class="form-label">Driver fee per day (RWF)</label>
        <input type="number" name="driver_fee_per_day" class="form-control" min="0">
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
</form>
</div>
@endsection
