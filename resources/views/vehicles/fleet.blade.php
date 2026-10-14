@extends('layouts.app')
@section('title', 'Full Fleet')
@section('content')

<div class="mb-4"
     x-data="{
        categoryId: '{{ request('category_id') }}',
        maxPrice: '{{ request('max_price') }}',
        search: '{{ request('search') }}',
        carTypes: @js((array) request('car_type', [])),
        loading: false,
        loadVehicles() {
            this.loading = true;
            const grid = document.getElementById('fleet-grid');
            grid.innerHTML = Array.from({ length: 6 }).map(() =>
                '<div class=\'col-12 col-md-6 col-lg-4\'><div class=\'fx-skeleton-card\'>' +
                '<div class=\'fx-skeleton-img\'></div>' +
                '<div class=\'fx-skeleton-line\'></div>' +
                '<div class=\'fx-skeleton-line short\'></div>' +
                '</div></div>'
            ).join('');

            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.categoryId) params.set('category_id', this.categoryId);
            if (this.maxPrice) params.set('max_price', this.maxPrice);
            this.carTypes.forEach((t) => params.append('car_type[]', t));

            history.replaceState(null, '', '{{ route('vehicles.fleet') }}?' + params.toString());

            fetch('{{ route('vehicles.filter') }}?' + params.toString())
                .then(r => r.text())
                .then(html => {
                    grid.innerHTML = html;
                    this.loading = false;
                    if (window.fxReinit) window.fxReinit(grid);
                });
        }
     }">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="fx-eyebrow-label">Full fleet</div>
            <h1 class="mb-1" style="font-size: 2rem;">Browse every available vehicle</h1>
            <p class="text-muted mb-0">Filter by category, vehicle type and budget to find the right fit.</p>
        </div>
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-light btn-sm align-self-center">&larr; Back to home</a>
    </div>

    <div class="fx-fleet-filters mb-4">
        <div class="fx-field">
            <label class="form-label">Search by model</label>
            <input type="text" class="form-control" placeholder="e.g. Corolla, RAV4..." x-model="search" @input.debounce.400ms="loadVehicles()">
        </div>
        <div>
            <label class="form-label d-block">Category</label>
            <select class="form-select" x-model="categoryId" @change="loadVehicles()">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fx-field">
            <label class="form-label">Max price / day (RWF)</label>
            <input type="number" class="form-control" x-model="maxPrice" @change="loadVehicles()">
        </div>
        <div>
            <label class="form-label d-block">Vehicle type</label>
            <div class="fx-type-pills">
                <button type="button" class="fx-type-pill" :class="{ 'is-active': carTypes.length === 0 }" @click="carTypes = []; loadVehicles()">
                    <span>All</span>
                </button>
                <label class="fx-type-pill">
                    <input type="checkbox" value="normal" x-model="carTypes" @change="loadVehicles()">
                    <span>Normal</span>
                </label>
                <label class="fx-type-pill">
                    <input type="checkbox" value="hybrid" x-model="carTypes" @change="loadVehicles()">
                    <span>Hybrid</span>
                </label>
                <label class="fx-type-pill">
                    <input type="checkbox" value="electric" x-model="carTypes" @change="loadVehicles()">
                    <span>Electric</span>
                </label>
            </div>
        </div>
    </div>

    <div id="fleet-grid">
        @include('vehicles._grid', ['vehicles' => $vehicles, 'activeCategory' => $activeCategory])
    </div>
</div>

@endsection
