@extends('layouts.admin')
@section('title', 'Edit Partner')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
    <h2 class="mb-0">{{ $partner->adminLabel() }}</h2>
    <a href="{{ route('admin.partners.history', $partner) }}" class="btn btn-admin btn-admin-outline">Financial history</a>
</div>
<p class="text-muted mb-4">
    {{ $partner->user->name }} ({{ $partner->user->email }}, {{ $partner->user->phone ?? 'no phone on file' }})
    &middot;
    @if($partner->is_suspended)
        <span class="badge bg-danger">Suspended</span>
    @elseif($partner->status === 'validated')
        <span class="badge bg-success">Validated</span>
    @elseif($partner->status === 'pending')
        <span class="badge bg-warning text-dark">Pending validation</span>
    @else
        <span class="badge bg-secondary">{{ ucfirst($partner->status) }}</span>
    @endif
</p>

<div class="row">
<form method="POST" action="{{ route('admin.partners.update', $partner) }}" class="col-12 col-md-6">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label d-block">Partner type</label>
        <select name="partner_type" id="partner_type" class="form-select" onchange="fxToggleAgencyFields()">
            <option value="agency" @selected(old('partner_type', $partner->partner_type) == 'agency')>Agency</option>
            <option value="individual" @selected(old('partner_type', $partner->partner_type) == 'individual')>Individual owner</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Company name</label>
        <input type="text" name="company_name" id="company_name" class="form-control" value="{{ old('company_name', $partner->company_name) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">TIN</label>
        <input type="text" name="tin" id="tin" class="form-control" value="{{ old('tin', $partner->tin) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">National ID number</label>
        <input type="text" name="national_id_number" id="national_id_number" class="form-control" value="{{ old('national_id_number', $partner->national_id_number) }}">
    </div>
    <div class="mb-3">
        <label class="form-label d-block">Supporting document</label>
        @if($partner->documents_path)
            <a href="{{ asset('storage/' . $partner->documents_path) }}" target="_blank" rel="noopener">View current document</a>
        @else
            <span class="text-muted">None on file</span>
        @endif
    </div>
    <script>
    function fxToggleAgencyFields() {
        var isAgency = document.getElementById('partner_type').value === 'agency';
        document.getElementById('company_name').required = isAgency;
        document.getElementById('tin').required = isAgency;
        document.getElementById('national_id_number').required = !isAgency;
    }
    document.addEventListener('DOMContentLoaded', fxToggleAgencyFields);
    </script>
    <button class="btn btn-gold">Save changes</button>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>

<h4 class="mt-4">Vehicles</h4>
<div class="table-responsive">
<table class="table table-hover">
    <thead><tr><th>Vehicle</th><th>Price/day</th><th>Status</th><th>Reservations</th><th></th></tr></thead>
    <tbody>
        @forelse($vehicles as $v)
            <tr>
                <td>{{ $v->model }} ({{ $v->year }})</td>
                <td>{{ number_format($v->price_per_day) }} RWF</td>
                <td><span class="badge bg-secondary">{{ ucfirst($v->status) }}</span></td>
                <td>{{ $v->reservations_count }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.vehicles.history', $v) }}" class="btn btn-admin btn-admin-ghost">History</a>
                    <a href="{{ route('admin.vehicles.edit', $v) }}" class="btn btn-admin btn-admin-outline">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No vehicles listed yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection
