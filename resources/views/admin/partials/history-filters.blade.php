{{-- GET form with no action: resubmits to the current URL (path segments
     like /vehicles/{vehicle}/history are untouched, only the query string
     changes), so this works unmodified wherever it's included. --}}
<form method="GET" class="row g-2 align-items-end mb-2">
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">From</label>
        <input type="date" name="from" value="{{ $filters['from'] ?? request('from') }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">To</label>
        <input type="date" name="to" value="{{ $filters['to'] ?? request('to') }}" class="form-control form-control-sm">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1 d-block">Client name or email</label>
        <input type="text" name="client" value="{{ $filters['client'] ?? request('client') }}" class="form-control form-control-sm" placeholder="Search client...">
    </div>
    <div class="col-auto">
        <button class="btn btn-admin btn-admin-primary btn-sm">Filter</button>
        <a href="{{ request()->url() }}" class="btn btn-admin btn-admin-ghost btn-sm">Clear</a>
    </div>
</form>
<div class="d-flex gap-2 flex-wrap mb-4">
    @php
        $keepClient = ($filters['client'] ?? request('client')) ? '&client=' . urlencode($filters['client'] ?? request('client')) : '';
    @endphp
    <a href="?from={{ now()->subDays(7)->toDateString() }}&to={{ now()->toDateString() }}{{ $keepClient }}" class="fx-chip">Last 7 days</a>
    <a href="?from={{ now()->subDays(30)->toDateString() }}&to={{ now()->toDateString() }}{{ $keepClient }}" class="fx-chip">Last 30 days</a>
    <a href="?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}{{ $keepClient }}" class="fx-chip">This month</a>
    <a href="{{ request()->url() }}{{ $keepClient ? '?' . ltrim($keepClient, '&') : '' }}" class="fx-chip">All time</a>
</div>
