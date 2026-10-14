@extends('layouts.admin')
@section('title', 'Admin Activity History')
@section('content')
<h2 class="mb-4">Admin Activity History</h2>

<div class="table-responsive">
<table class="table">
    <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>On</th><th>Details</th></tr></thead>
    <tbody>
        @forelse($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $log->admin->name ?? 'Unknown' }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($log->action) }}</span></td>
                <td>
                    {{ $log->subject_type }} #{{ $log->subject_id }}
                    @if($log->subject_type === 'Vehicle' && ($vehicle = \App\Models\Vehicle::with('partner')->find($log->subject_id)) && $vehicle->partner)
                        &middot; <a href="{{ route('admin.partners.edit', $vehicle->partner) }}">{{ $vehicle->partner->adminLabel() }}</a>
                    @endif
                </td>
                <td>{{ $log->description }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No activity recorded yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $logs->links() }}
@endsection
