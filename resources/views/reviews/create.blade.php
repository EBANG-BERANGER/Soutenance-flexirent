@extends('layouts.app')
@section('title', 'Leave a Review')
@section('content')
<h2 class="mb-4">Rate your rental</h2>
<div class="row">
<form method="POST" action="{{ route('reviews.store', $reservation) }}" class="col-12 col-md-6">
    @csrf
    <div class="mb-3">
        <label class="form-label">Rating (1-5)</label>
        <select name="rating" class="form-select" required>
            @for($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Comment (optional)</label>
        <textarea name="comment" class="form-control" rows="3"></textarea>
    </div>
    <button class="btn btn-gold">Submit review</button>
</form>
</div>
@endsection
