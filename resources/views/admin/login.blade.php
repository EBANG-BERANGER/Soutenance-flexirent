<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - FlexiRent</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="preconnect" href="https://api.fontshare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=general-sans@400,500,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body style="background: var(--navy-900);">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh;">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    @if(file_exists(public_path('images/logo-icon.png')))
                        <img src="{{ asset('images/logo-icon.png') }}" alt="FlexiRent" style="width:70px;height:70px;border-radius:50%;border:2px solid var(--gold);">
                    @else
                        <span style="color:#fff;font-size:1.4rem;font-weight:bold;">FlexiRent</span>
                    @endif
                </div>
                <div class="card p-4 auth-card">
                    <h3 class="mb-1">Administrator Access</h3>
                    <p class="text-muted small mb-4">This portal is for FlexiRent administrators only. Clients and partners should use the regular login page.</p>
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.login.attempt') }}" autocomplete="off">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Admin email</label>
                            <input type="email" name="email" class="form-control" autocomplete="off" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="text-end mb-3">
                            <a href="{{ route('admin.password.request') }}" class="small">Forgot your password?</a>
                        </div>
                        <button class="btn btn-navy w-100">Sign in to Admin</button>
                    </form>
                </div>
                <p class="text-center mt-3"><a href="{{ route('login') }}" class="text-white small">Not an administrator? Client / partner login</a></p>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
