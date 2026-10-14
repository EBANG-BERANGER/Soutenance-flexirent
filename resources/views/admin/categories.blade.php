@extends('layouts.admin')
@section('title', 'Manage Categories')
@section('content')
<h2 class="mb-4">Manage Categories</h2>

<form method="POST" action="{{ route('admin.categories.store') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-auto">
        <input type="text" name="name" class="form-control" placeholder="New category name" required>
    </div>
    <div class="col-auto">
        <button class="btn btn-gold">Add category</button>
    </div>
</form>

<div class="table-responsive">
<table class="table">
    <thead><tr><th>Name</th><th>Vehicles</th><th>Actions</th></tr></thead>
    <tbody>
        @forelse($categories as $category)
            <tr>
                <td>
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="d-flex gap-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $category->name }}" style="max-width:220px">
                        <button class="btn btn-sm btn-navy">Save</button>
                    </form>
                </td>
                <td>{{ $category->vehicles_count }}</td>
                <td>
                    @if($category->vehicles_count === 0)
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    @else
                        <span class="text-muted small">In use</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-muted">No categories yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection
