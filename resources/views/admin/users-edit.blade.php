@extends('layouts.admin')
@section('title', 'Edit User')
@section('content')
<h2 class="mb-4">Edit User</h2>

<div class="row">
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="col-12 col-md-6">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Full name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            <option value="client" @selected($user->role == 'client')>Client</option>
            <option value="partner" @selected($user->role == 'partner')>Partner</option>
            <option value="admin" @selected($user->role == 'admin')>Admin</option>
        </select>
    </div>
    <button class="btn btn-gold">Save changes</button>
    <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>
@endsection
