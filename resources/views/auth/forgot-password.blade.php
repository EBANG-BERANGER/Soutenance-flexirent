@extends('layouts.app')
@section('title', 'Forgot Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4 auth-card">
            <h3 class="mb-2">Reset your password</h3>
            <p class="text-muted small mb-4">Enter your email address and we'll send you a link to reset your password &mdash; just like Google or any major service.</p>
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required autofocus>
                </div>
                <button class="btn btn-gold w-100">Send reset link</button>
            </form>
            <p class="mt-3 mb-0"><a href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
</div>
@endsection
