@extends('layouts.app')
@section('title', 'Verify Email')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4 auth-card text-center">
            <h3 class="mb-3">Verify your email address</h3>
            <p class="text-muted">
                We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                Click the link in that email to confirm it's really you.
            </p>
            <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
                @csrf
                <button class="btn btn-gold">Resend verification email</button>
            </form>
        </div>
    </div>
</div>
@endsection
