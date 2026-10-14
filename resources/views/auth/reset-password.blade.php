@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4 auth-card">
            <h3 class="mb-4">Choose a new password</h3>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $email }}" required autofocus>
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
                <button class="btn btn-gold w-100">Reset password</button>
            </form>
        </div>
    </div>
</div>
@endsection
