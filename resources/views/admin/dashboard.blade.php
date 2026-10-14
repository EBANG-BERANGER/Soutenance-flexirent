@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Overview</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2"><div class="fx-admin-stat">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
        <div><small>Users</small><h4>{{ $stats['users'] }}</h4></div>
    </div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="fx-admin-stat">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l2.5 2.5"/></svg></span>
        <div><small>Pending partners</small><h4>{{ $stats['partners_pending'] }}</h4></div>
    </div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="fx-admin-stat">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h1.5l1-4a2 2 0 0 1 1.9-1.4h5.2a2 2 0 0 1 1.9 1.4l1 4H19"/><circle cx="7.5" cy="17" r="1.3"/><circle cx="16.5" cy="17" r="1.3"/></svg></span>
        <div><small>Vehicles</small><h4>{{ $stats['vehicles'] }}</h4></div>
    </div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="fx-admin-stat">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
        <div><small>Reservations</small><h4>{{ $stats['reservations'] }}</h4></div>
    </div></div>
    <div class="col-12 col-md-4 col-lg-3"><div class="fx-admin-stat is-financial">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
        <div><small>Revenue</small><h4>{{ number_format($stats['revenue']) }} RWF</h4></div>
    </div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="fx-admin-stat is-financial">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
        <div><small>FlexiRent commission</small><h4>{{ number_format($stats['platform_commission']) }} RWF</h4></div>
    </div></div>
    <div class="col-6 col-md-4 col-lg-3"><div class="fx-admin-stat is-financial">
        <span class="fx-admin-stat-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><circle cx="17" cy="14" r="1"/></svg></span>
        <div><small>Owed to owners</small><h4>{{ number_format($stats['owner_payout']) }} RWF</h4></div>
    </div></div>
</div>

<h4>Pending Partner Validations</h4>
<div class="table-responsive">
<table class="table table-hover mb-5">
    <thead><tr><th>Company</th><th>User</th><th>TIN / National ID</th><th>Document</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($pendingPartners as $p)
            <tr>
                <td><a href="{{ route('admin.partners.edit', $p) }}">{{ $p->adminLabel() }}</a></td>
                <td>{{ $p->user->name }} ({{ $p->user->email }})</td>
                <td>{{ $p->isIndividual() ? $p->national_id_number : $p->tin }}</td>
                <td>
                    @if($p->documents_path)
                        <a href="{{ asset('storage/' . $p->documents_path) }}" target="_blank" rel="noopener">View document</a>
                    @else
                        <span class="text-muted">None</span>
                    @endif
                </td>
                <td>
                    <div class="fx-row-actions justify-content-end">
                        <form method="POST" action="{{ route('admin.partners.validate', $p) }}">
                            @csrf
                            <button type="submit" class="btn btn-admin btn-admin-success">Validate</button>
                        </form>
                        <form method="POST" action="{{ route('admin.partners.reject', $p) }}">
                            @csrf
                            <button type="submit" class="btn btn-admin btn-admin-warn">Reject</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No pending partners.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<h4>Active Partners</h4>
<div class="table-responsive">
<table class="table table-hover mb-5">
    <thead><tr><th>Company</th><th>User</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($activePartners as $p)
            <tr>
                <td><a href="{{ route('admin.partners.edit', $p) }}">{{ $p->adminLabel() }}</a></td>
                <td>{{ $p->user->name }} ({{ $p->user->email }})</td>
                <td>
                    @if($p->is_suspended)
                        <span class="badge bg-danger">Suspended</span>
                    @else
                        <span class="badge bg-success">Active</span>
                    @endif
                </td>
                <td>
                    <div class="fx-row-actions justify-content-end">
                        <a href="{{ route('admin.partners.history', $p) }}" class="btn btn-admin btn-admin-ghost">History</a>
                        <a href="{{ route('admin.partners.edit', $p) }}" class="btn btn-admin btn-admin-outline">Edit</a>
                        <div class="fx-row-menu" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" class="btn btn-admin btn-admin-ghost" @click="open = !open" aria-label="More actions">&hellip;</button>
                            <div class="fx-row-menu-panel" x-show="open" x-cloak x-transition.duration.120ms>
                                @if($p->is_suspended)
                                    <form method="POST" action="{{ route('admin.partners.unsuspend', $p) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Reinstate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.partners.suspend', $p) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Suspend</button>
                                    </form>
                                @endif
                                <div class="fx-row-menu-divider"></div>
                                <form method="POST" action="{{ route('admin.partners.destroy', $p) }}" onsubmit="return confirm('Delete this partner and all their vehicles?')">
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
            <tr><td colspan="4" class="text-muted">No validated partners yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<h4>Recent Reservations</h4>
<div class="table-responsive">
<table class="table">
    <thead><tr><th>Client</th><th>Vehicle</th><th>Status</th></tr></thead>
    <tbody>
        @foreach($recentReservations as $r)
            <tr>
                <td>{{ $r->client->name }}</td>
                <td>{{ $r->vehicle->model }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($r->status) }}</span></td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>

<h4>Notifications</h4>
<div class="table-responsive mb-4">
    <table class="table">
        <thead><tr><th>Date</th><th>Title</th><th>Content</th></tr></thead>
        <tbody>
            @forelse($notifications as $n)
                <tr>
                    <td class="text-nowrap">{{ $n->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $n->title }}</td>
                    <td>{{ $n->content }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">No notifications yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
