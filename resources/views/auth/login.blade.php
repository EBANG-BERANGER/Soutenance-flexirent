@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4 auth-card">
            <h3 class="mb-4">Login to FlexiRent</h3>
            <form method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf
                <div class="fx-field mb-3">
                    <label class="form-label">Email or username</label>
                    <input type="text" name="login" class="form-control" autocomplete="off" required autofocus>
                </div>
                <div class="fx-field mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="off" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
                </div>
                <button class="btn btn-gold w-100">Login</button>
            </form>
            <p class="mt-3 mb-0">No account? <a href="{{ route('register') }}">Register here</a></p>
            <p class="mt-2 mb-0 text-muted small">Administrator? <a href="{{ route('admin.login') }}">Sign in through the admin portal</a></p>
        </div>
    </div>
</div>
@endsection
