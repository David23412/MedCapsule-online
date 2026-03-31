<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            font-size: 14px;
        }

        @media (min-width: 1400px) {
            :root {
                font-size: 16px;
            }
        }

        @media (max-width: 768px) {
            :root {
                font-size: 13px;
            }
            body {
                zoom: 1 !important;
            }
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 95%;
        }

        @media (max-width: 1500px) {
            .container {
                max-width: 95%;
            }
        }

        .navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            display: inline-flex;
            align-items: center;
            padding: 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0069d9;
        }
        .form-control {
            border-radius: 5px;
            font-size: 1rem; /* Minimum 16px to prevent iOS auto-zoom */
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .login-container {
            margin-top: 80px;
        }
    </style>
</head>
<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/" aria-label="MedCapsule">
                <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="loginPillBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb"/>
                            <stop offset="45%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#1d4ed8"/>
                        </linearGradient>
                        <linearGradient id="loginPillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0)"/>
                            <stop offset="30%" stop-color="rgba(255,255,255,0.6)"/>
                            <stop offset="70%" stop-color="rgba(255,255,255,0.5)"/>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                        </linearGradient>
                        <linearGradient id="loginPillShadow" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgba(0,0,0,0)"/>
                            <stop offset="100%" stop-color="rgba(10,20,80,0.28)"/>
                        </linearGradient>
                        <filter id="loginPillDrop" x="-8%" y="-15%" width="116%" height="150%">
                            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"/>
                        </filter>
                    </defs>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#loginPillBody)" filter="url(#loginPillDrop)"/>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#loginPillShadow)"/>
                    <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#loginPillGloss)" opacity="0.88"/>
                    <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"/>
                </svg>
            </a>
            <div class="d-flex">
                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">
                    Register
                </a>
            </div>
        </div>
    </nav>

    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Sign In</h4>
                        
                        @if(session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label">Password</label>
                                    @if (Route::has('password.request'))
                                        <a class="text-decoration-none small" href="{{ route('password.request') }}">
                                            Forgot?
                                        </a>
                                    @endif
                                </div>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary">
                                    Login
                                </button>
                            </div>
                            
                            <div class="text-center small">
                                <p class="mb-0">Don't have an account? 
                                    <a href="{{ route('register') }}" class="text-decoration-none">Register</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
