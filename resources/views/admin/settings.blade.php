@extends('layouts.admin')
@section('title', 'Site Settings')
@section('content')
<h2 class="mb-4">Site Settings</h2>

<div class="card p-4" style="max-width: 640px;">
    <h5 class="mb-1">Homepage cover photo</h5>
    <p class="text-muted small mb-3">
        This image is shown as the large featured photo on the homepage, independent of any vehicle listing.
        Leave it unset and the homepage will fall back to a vehicle's own photo instead.
    </p>

    @if($coverPath)
        <img src="{{ asset('storage/' . $coverPath) }}" alt="Current homepage cover photo" class="mb-3" style="width:100%; max-width:420px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
        <form method="POST" action="{{ route('admin.settings.cover-photo.destroy') }}" class="mb-4" onsubmit="return confirm('Remove the homepage cover photo? The homepage will fall back to a vehicle photo.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-admin btn-admin-danger">Remove cover photo</button>
        </form>
    @else
        <p class="text-muted small mb-3"><em>No cover photo set yet. The homepage is currently using a vehicle's photo.</em></p>
    @endif

    <form method="POST" action="{{ route('admin.settings.cover-photo.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ $coverPath ? 'Replace with a new photo' : 'Upload a cover photo' }}</label>
            <input type="file" name="cover_photo" class="form-control" accept="image/*" required>
            <p class="form-text">JPG or PNG, up to 5 MB. A wide landscape photo (at least 1200&times;700px) works best.</p>
        </div>
        <button type="submit" class="btn btn-admin btn-admin-primary">Save cover photo</button>
    </form>
</div>
@endsection
