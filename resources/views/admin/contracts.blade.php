@extends('layouts.admin')
@section('title', 'Manage Contracts')
@section('content')
<h2 class="mb-4">Manage Contracts</h2>

<div class="table-responsive">
<table class="table">
    <thead><tr><th>Reservation</th><th>Client</th><th>Vehicle</th><th>Generated</th><th>Actions</th></tr></thead>
    <tbody>
        @forelse($contracts as $contract)
            <tr>
                <td>#{{ $contract->reservation_id }}</td>
                <td>{{ $contract->reservation->client->name }}</td>
                <td>{{ $contract->reservation->vehicle->model }}</td>
                <td>{{ $contract->generated_at?->format('d/m/Y H:i') }}</td>
                <td>
                    <a class="btn btn-sm btn-navy" href="{{ route('contracts.download', $contract->reservation) }}">Download</a>
                    <form method="POST" action="{{ route('admin.contracts.destroy', $contract) }}" class="d-inline" onsubmit="return confirm('Delete this contract file?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No contracts generated yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $contracts->links() }}
@endsection
