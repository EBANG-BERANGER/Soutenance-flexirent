@extends('layouts.admin')
@section('title', 'Manage Payments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">Manage Payments</h2>
    <a href="{{ route('admin.payments.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-admin btn-admin-ghost">Export CSV</a>
</div>

<form method="GET" class="row g-2 mb-2">
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Vehicle</label>
        <select name="vehicle_id" class="form-select form-select-sm">
            <option value="">All vehicles</option>
            @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>{{ $vehicle->model }} ({{ $vehicle->year }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Partner</label>
        <select name="partner_id" class="form-select form-select-sm">
            <option value="">All partners</option>
            @foreach($partners as $partner)
                <option value="{{ $partner->id }}" @selected(request('partner_id') == $partner->id)>{{ $partner->adminLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Client name or email</label>
        <input type="text" name="client" value="{{ request('client') }}" class="form-control form-control-sm" placeholder="Search client...">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') == 'pending')>Pending</option>
            <option value="paid" @selected(request('status') == 'paid')>Paid</option>
            <option value="failed" @selected(request('status') == 'failed')>Failed</option>
            <option value="refunded" @selected(request('status') == 'refunded')>Refunded</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Method</label>
        <select name="method" class="form-select form-select-sm">
            <option value="">All methods</option>
            <option value="mtn_momo" @selected(request('method') == 'mtn_momo')>MTN Mobile Money</option>
            <option value="airtel_money" @selected(request('method') == 'airtel_money')>Airtel Money</option>
            <option value="manual" @selected(request('method') == 'manual')>Manual (office)</option>
        </select>
    </div>
    <input type="hidden" name="sort" value="{{ request('sort') }}">
    <div class="col-auto d-flex align-items-end gap-2">
        <button class="btn btn-gold btn-sm">Filter</button>
        <a href="{{ route('admin.payments') }}" class="btn btn-admin btn-admin-ghost btn-sm">Clear</a>
    </div>
</form>

<div class="table-responsive">
<table class="table">
    <thead><tr>
        <th>
            <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => request('sort') === 'oldest' ? 'newest' : 'oldest'])) }}" class="text-decoration-none">
                Date {{ request('sort') === 'oldest' ? '↑' : '↓' }}
            </a>
        </th>
        <th>Reservation</th><th>Client</th><th>Owner</th><th>Method</th><th>Type</th><th>Amount</th><th>Reference</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                <td>#{{ $payment->reservation->id }} ({{ $payment->reservation->vehicle->model }})</td>
                <td>{{ $payment->reservation->client->name }}</td>
                <td>
                    @if($payment->reservation->vehicle->partner)
                        <a href="{{ route('admin.partners.edit', $payment->reservation->vehicle->partner) }}">{{ $payment->reservation->vehicle->partner->adminLabel() }}</a>
                    @else
                        FlexiRent
                    @endif
                </td>
                <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($payment->type) }}</span></td>
                <td>{{ number_format($payment->amount) }} RWF</td>
                <td>{{ $payment->transaction_ref ?? 'N/A' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span></td>
                <td>
                    @if($payment->status === 'pending')
                        <form method="POST" action="{{ route('admin.payments.mark-received', $payment) }}" class="d-inline-flex align-items-center gap-1">
                            @csrf
                            <input type="text" name="transaction_ref" class="form-control form-control-sm" placeholder="MoMo ref" style="width:110px;">
                            <button class="btn btn-sm btn-success">Mark received</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.payments.destroy', $payment) }}" class="d-inline" onsubmit="return confirm('Delete this payment record?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="text-muted">No payments match these filters.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $payments->links() }}
@endsection
