@extends('layouts.admin')
@section('title', 'Manage Vehicles')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">Vehicles</h2>
    <a href="{{ route('admin.vehicles.create') }}" class="btn btn-admin btn-admin-primary">+ Add vehicle</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="available" @selected(request('status') == 'available')>Available</option>
            <option value="booked" @selected(request('status') == 'booked')>Booked</option>
            <option value="maintenance" @selected(request('status') == 'maintenance')>Maintenance</option>
        </select>
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover">
    <thead><tr><th>Vehicle</th><th>Partner</th><th>Price/day</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->model }} ({{ $vehicle->year }})</td>
                <td>
                    @if($vehicle->partner)
                        <a href="{{ route('admin.partners.edit', $vehicle->partner) }}">{{ $vehicle->partner->adminLabel() }}</a>
                    @else
                        FlexiRent
                    @endif
                </td>
                <td>{{ number_format($vehicle->price_per_day) }} RWF</td>
                <td>
                    <span class="badge bg-secondary">{{ ucfirst($vehicle->status) }}</span>
                    @if($vehicle->is_suspended)
                        <span class="badge bg-danger">Suspended</span>
                    @endif
                    @if($vehicle->is_featured)
                        <span class="badge" style="background:var(--gold); color:#fff;">Homepage photo</span>
                    @endif
                </td>
                <td>
                    <div class="fx-row-actions justify-content-end">
                        <a href="{{ route('admin.vehicles.history', $vehicle) }}" class="btn btn-admin btn-admin-ghost">History</a>
                        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-admin btn-admin-outline">Edit</a>
                        <div class="fx-row-menu" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" class="btn btn-admin btn-admin-ghost" @click="open = !open" aria-label="More actions">&hellip;</button>
                            <div class="fx-row-menu-panel" x-show="open" x-cloak x-transition.origin.top.right.duration.120ms>
                                @if($vehicle->is_featured)
                                    <form method="POST" action="{{ route('admin.vehicles.clear-featured', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Remove as homepage photo</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.vehicles.set-featured', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Set as homepage photo</button>
                                    </form>
                                @endif
                                <div class="fx-row-menu-divider"></div>
                                @if($vehicle->status === 'maintenance')
                                    <form method="POST" action="{{ route('admin.vehicles.toggle-status', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Mark as available</button>
                                    </form>
                                @elseif($vehicle->status === 'available')
                                    <form method="POST" action="{{ route('admin.vehicles.toggle-status', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Mark as maintenance</button>
                                    </form>
                                @endif
                                <div class="fx-row-menu-divider"></div>
                                @if($vehicle->is_suspended)
                                    <form method="POST" action="{{ route('admin.vehicles.unsuspend', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Reinstate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.vehicles.suspend', $vehicle) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Suspend listing</button>
                                    </form>
                                @endif
                                <div class="fx-row-menu-divider"></div>
                                <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Delete this vehicle? This also deletes its reservations.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fx-row-menu-item fx-row-menu-item-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No vehicles found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $vehicles->links() }}
@endsection
