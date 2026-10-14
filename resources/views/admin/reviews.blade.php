@extends('layouts.admin')
@section('title', 'Manage Reviews')
@section('content')
<h2 class="mb-4">Manage Reviews</h2>

<div class="table-responsive">
<table class="table">
    <thead><tr><th>Client</th><th>Vehicle</th><th>Rating</th><th>Comment</th><th>Actions</th></tr></thead>
    <tbody>
        @forelse($reviews as $review)
            <tr>
                <td>{{ $review->reservation->client->name }}</td>
                <td>{{ $review->reservation->vehicle->model }}</td>
                <td>{{ $review->rating }} / 5</td>
                <td>{{ $review->comment }}</td>
                <td>
                    <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-sm btn-navy">Edit</a>
                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}" class="d-inline fx-moderate-review-form">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="moderation_reason">
                        <button type="button" class="btn btn-sm btn-danger fx-moderate-review-btn">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No reviews yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $reviews->links() }}
<script>
document.querySelectorAll('.fx-moderate-review-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var reason = prompt('Reason for removing this review (required):');
        if (!reason || !reason.trim()) return;
        var form = btn.closest('form');
        form.querySelector('input[name="moderation_reason"]').value = reason.trim();
        form.submit();
    });
});
</script>
@endsection
