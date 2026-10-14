@extends('layouts.admin')
@section('title', 'Edit Vehicle')
@section('content')
<h2 class="mb-4">Edit Vehicle</h2>

@if($vehicle->images->isNotEmpty())
<div class="mb-4">
    <label class="form-label d-block">Current photos</label>
    <div class="d-flex flex-wrap gap-3">
        @foreach($vehicle->images as $image)
            <div class="text-center">
                <img src="{{ asset('storage/'.$image->path) }}" style="width:110px;height:90px;object-fit:cover;border-radius:8px;" class="mb-1 {{ $vehicle->photo_path === $image->path ? 'border border-3' : 'border' }}">
                @if($vehicle->photo_path === $image->path)
                    <div class="small text-muted">Cover</div>
                @endif
                <form method="POST" action="{{ route('admin.vehicles.images.destroy', $image) }}" onsubmit="return confirm('Remove this photo?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger mt-1">Remove</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="row">
<form method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" enctype="multipart/form-data" class="col-12 col-md-8">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Owner</label>
        <select name="partner_id" class="form-select">
            <option value="" @selected(!$vehicle->partner_id)>FlexiRent (owned directly, no partner)</option>
            @foreach($partners as $partner)
                <option value="{{ $partner->id }}" @selected($vehicle->partner_id == $partner->id)>
                    {{ $partner->company_name ?: $partner->user->name . ' (individual)' }} ({{ ucfirst($partner->status) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected($vehicle->category_id == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" value="{{ $vehicle->model }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" value="{{ $vehicle->year }}" min="{{ \App\Models\Vehicle::minAcceptedYear() }}" max="{{ date('Y') + 1 }}" required>
            <p class="form-text">Vehicles older than 14 years (before {{ \App\Models\Vehicle::minAcceptedYear() }}) are not accepted.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Car type</label>
            <select name="car_type" id="car_type" class="form-select" required onchange="fxTogglePropulsion()">
                <option value="normal" @selected($vehicle->car_type == 'normal')>Normal</option>
                <option value="hybrid" @selected($vehicle->car_type == 'hybrid')>Hybrid</option>
                <option value="electric" @selected($vehicle->car_type == 'electric')>100% Electric</option>
            </select>
        </div>
        <div class="col-md-6 mb-3" id="propulsion-field">
            <label class="form-label">Propulsion</label>
            <select name="propulsion" id="propulsion" class="form-select">
                <option value="essence" @selected($vehicle->propulsion == 'essence')>Essence (Petrol)</option>
                <option value="diesel" @selected($vehicle->propulsion == 'diesel')>Diesel</option>
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
            <input type="text" name="plate_number" class="form-control" value="{{ $vehicle->plate_number }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Chassis number</label>
            <input type="text" name="chassis_number" class="form-control" value="{{ $vehicle->chassis_number }}" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / day (RWF)</label>
            <input type="number" name="price_per_day" class="form-control" value="{{ $vehicle->price_per_day }}" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / week (RWF)</label>
            <input type="number" name="price_per_week" class="form-control" value="{{ $vehicle->price_per_week }}" min="0">
            <p class="form-text">Optional. Leave blank to bill weekly stays at the day rate.</p>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Price / month (RWF)</label>
            <input type="number" name="price_per_month" class="form-control" value="{{ $vehicle->price_per_month }}" min="0">
            <p class="form-text">Optional. Leave blank to bill monthly stays at the day rate.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Driver fee / day (RWF)</label>
            <input type="number" name="driver_fee_per_day" class="form-control" value="{{ $vehicle->driver_fee_per_day }}">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Deposit / caution (RWF)</label>
            <input type="number" name="deposit_amount" class="form-control" value="{{ $vehicle->deposit_amount }}">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Minimum rental (days)</label>
        <input type="number" name="min_rental_days" class="form-control" value="{{ $vehicle->min_rental_days }}" min="1" max="365" style="max-width:160px;" required>
        <p class="form-text">Shorter bookings will be blocked. The partner can also change this from their dashboard.</p>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="available" @selected($vehicle->status == 'available')>Available</option>
            <option value="booked" @selected($vehicle->status == 'booked')>Booked</option>
            <option value="maintenance" @selected($vehicle->status == 'maintenance')>Maintenance</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Add more photos</label>
        <div class="fx-photo-picker" data-photo-picker>
            <div class="fx-photo-grid" data-photo-grid></div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-add>+ Add photos</button>
            <input type="file" name="photos[]" accept="image/*" multiple class="d-none" data-photo-input>
        </div>
        <p class="form-text">Click "Add photos" as many times as you like: each selection is added to the set.</p>
    </div>
    <button class="btn btn-gold">Save changes</button>
    <a href="{{ route('admin.vehicles') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>
@endsection
