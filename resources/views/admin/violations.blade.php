@extends('layouts.admin')
@section('title', 'Traffic Violations')
@section('content')
<h2 class="mb-4">Traffic Violations</h2>

<div class="table-responsive">
<table class="table">
    <thead><tr><th>Vehicle</th><th>Reference</th><th>Date</th><th>Due</th><th>Reported by</th><th>Client</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        @forelse($violations as $violation)
            <tr>
                <td>{{ $violation->vehicle->model }}</td>
                <td>{{ $violation->reference_number }}</td>
                <td>{{ $violation->violation_date->format('d/m/Y') }}</td>
                <td>{{ $violation->payment_due_date->format('d/m/Y') }}</td>
                <td>{{ $violation->reportedBy->name }}</td>
                <td>{{ $violation->reservation?->client?->name ?? 'Not identified' }}</td>
                <td>
                    @if($violation->status === 'paid')
                        <span class="badge bg-success">Paid</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif
                </td>
                <td>
                    @if($violation->status === 'unpaid')
                        <form method="POST" action="{{ route('admin.violations.mark-paid', $violation) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-admin btn-admin-success">Mark paid</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted">No violations reported.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $violations->links() }}
@endsection
