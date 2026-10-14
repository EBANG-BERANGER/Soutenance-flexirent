@extends('layouts.admin')
@section('title', 'Edit Review')
@section('content')
<h2 class="mb-4">Edit Review #{{ $review->id }}</h2>

<div class="row">
<form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="col-12 col-md-6">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Rating (1-5)</label>
        <select name="rating" class="form-select">
            @for($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" @selected($review->rating == $i)>{{ $i }}</option>
            @endfor
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Comment</label>
        <textarea name="comment" class="form-control" rows="3">{{ $review->comment }}</textarea>
    </div>
    <button class="btn btn-gold">Save changes</button>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>
@endsection
