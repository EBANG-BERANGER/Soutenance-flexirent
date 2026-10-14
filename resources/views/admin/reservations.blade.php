@extends('layouts.admin')
@section('title', 'Manage Reservations')
@section('content')
<h2 class="mb-4">Reservations</h2>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') == 'pending')>Pending</option>
            <option value="confirmed" @selected(request('status') == 'confirmed')>Confirmed</option>
            <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
            <option value="completed" @selected(request('status') == 'completed')>Completed</option>
            <option value="suspended" @selected(request('status') == 'suspended')>Suspended</option>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control" placeholder="From">
    </div>
    <div class="col-auto">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control" placeholder="To">
    </div>
    <div class="col-auto">
        <button class="btn btn-admin btn-admin-primary">Filter</button>
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover">
    <thead><tr><th>Client</th><th>Vehicle</th><th>Dates</th><th>Total</th><th>Commission / Owner</th><th>Status</th><th>Payment</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($reservations as $r)
            <tr>
                <td>{{ $r->client->name }}</td>
                <td>{{ $r->vehicle->model }}</td>
                <td>{{ $r->start_date->format('d/m/Y') }} to {{ $r->end_date->format('d/m/Y') }}</td>
                <td>{{ number_format($r->total_price) }} RWF</td>
                <td>
                    @if($r->platform_commission !== null)
                        <small class="d-block text-muted">FlexiRent: {{ number_format($r->platform_commission) }} RWF</small>
                        <small class="d-block text-muted">Owner: {{ number_format($r->owner_payout) }} RWF</small>
                    @else
                        <small class="text-muted">N/A</small>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $r->status === 'suspended' ? 'bg-warning text-dark' : 'bg-secondary' }}">{{ ucfirst($r->status) }}</span>
                </td>
                <td>
                    @if($r->payment_status === 'paid')
                        <span class="badge bg-success">Paid</span>
                    @elseif($r->payment_status === 'deposit_paid')
                        <span class="badge bg-warning text-dark">Deposit ({{ number_format($r->balanceDue()) }} due)</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif
                </td>
                <td>
                    <div class="fx-row-actions justify-content-end">
                        <a href="{{ route('admin.reservations.edit', $r) }}" class="btn btn-admin btn-admin-outline">Edit</a>
                        <div class="fx-row-menu" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" class="btn btn-admin btn-admin-ghost" @click="open = !open" aria-label="More actions">&hellip;</button>
                            <div class="fx-row-menu-panel" x-show="open" x-cloak x-transition.duration.120ms>
                                @if($r->status === 'confirmed')
                                    <form method="POST" action="{{ route('admin.reservations.suspend', $r) }}" onsubmit="return confirm('Suspend this active rental? The vehicle will show as available again until you resume it.')">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Suspend</button>
                                    </form>
                                @elseif($r->status === 'suspended')
                                    <form method="POST" action="{{ route('admin.reservations.resume', $r) }}">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Resume</button>
                                    </form>
                                @endif
                                @if(! in_array($r->status, ['completed', 'cancelled']))
                                    <form method="POST" action="{{ route('admin.reservations.cancel', $r) }}" onsubmit="return confirm('Cancel this reservation? This works at any point, even mid-rental.')">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item">Cancel</button>
                                    </form>
                                    <div class="fx-row-menu-divider"></div>
                                @elseif($r->status === 'cancelled' && $r->status_before_cancellation)
                                    <form method="POST" action="{{ route('admin.reservations.reactivate', $r) }}" class="fx-reactivate-form">
                                        @csrf
                                        <input type="hidden" name="reason">
                                        <button type="button" class="fx-row-menu-item fx-reactivate-btn">Reactivate</button>
                                    </form>
                                    <div class="fx-row-menu-divider"></div>
                                @endif
                                <form method="POST" action="{{ route('admin.reservations.destroy', $r) }}" onsubmit="return confirm('Delete this reservation permanently?')">
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
            <tr><td colspan="8" class="text-muted">No reservations found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $reservations->links() }}
<script>
document.querySelectorAll('.fx-reactivate-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var reason = prompt('Reason for reactivating this reservation (required):');
        if (!reason || !reason.trim()) return;
        var form = btn.closest('form');
        form.querySelector('input[name="reason"]').value = reason.trim();
        form.submit();
    });
});
</script>
@endsection
