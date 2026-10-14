@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<h2 class="mb-4">My Profile</h2>

<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->username }}" class="fx-user-avatar" style="width:60px;height:60px;">
                <div>
                    <div class="fw-bold">{{ $user->name }}</div>
                    <div class="text-muted small">{{ '@'.$user->username }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
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
                    <label class="form-label">Profile photo</label>
                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    @if($user->email_verified_at)
                        <div class="form-text text-success fw-bold">&#10003; Email verified</div>
                    @else
                        <div class="form-text text-warning">Not verified yet.</div>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+250 78 000 0000">
                </div>
                <div class="mb-3">
                    <label class="form-label">Physical address</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $user->address) }}">
                </div>
                <button class="btn btn-gold">Save changes</button>
            </form>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card p-4">
            <h5 class="mb-3">Change password</h5>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Current password</label>
                    <input type="password" name="current_password" class="form-control" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                    <div class="form-text">At least 8 characters, with uppercase, lowercase, a number and a symbol.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
                <button class="btn btn-navy">Update password</button>
            </form>
        </div>
    </div>
</div>
@endsection
