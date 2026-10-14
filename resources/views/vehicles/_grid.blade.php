@php
    $activeCategory = $activeCategory ?? null;
    $activeMaxPrice = request('max_price');
    $activeCarTypes = array_filter((array) request('car_type', []));
    $hasActiveFilters = $activeCategory || $activeMaxPrice || $activeCarTypes;
@endphp
@if($hasActiveFilters)
    <p class="fx-filter-summary mb-3">
        <strong>{{ $vehicles->total() }}</strong> vehicle{{ $vehicles->total() === 1 ? '' : 's' }}
        @if($activeCarTypes) <strong>{{ collect($activeCarTypes)->map(fn ($t) => ucfirst($t))->join(' / ') }}</strong> @endif
        @if($activeCategory) in <strong>{{ $activeCategory->name }}</strong> @endif
        @if($activeMaxPrice) for a budget up to <strong>{{ number_format($activeMaxPrice) }} RWF/day</strong> @endif
    </p>
@endif

<div class="row g-4">
    @forelse($vehicles as $vehicle)
        <div class="col-12 col-md-6 col-lg-4">
            <x-vehicle-card :vehicle="$vehicle" />
        </div>
    @empty
        <div class="col-12">
            <div class="fx-empty-state">
                <p class="text-muted mb-2">
                    @if($hasActiveFilters)
                        No{{ $activeCarTypes ? ' ' . collect($activeCarTypes)->map(fn ($t) => ucfirst($t))->join('/') : '' }} vehicles{{ $activeCategory ? " in {$activeCategory->name}" : '' }}{{ $activeMaxPrice ? ' fit a budget of ' . number_format($activeMaxPrice) . ' RWF/day' : '' }} right now. Try widening your filters.
                    @else
                        No vehicles match your filters right now.
                    @endif
                </p>
                @if($hasActiveFilters)
                    <a href="{{ route('vehicles.fleet') }}" class="btn btn-outline-light btn-sm">Clear filters</a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $vehicles->links() }}</div>
