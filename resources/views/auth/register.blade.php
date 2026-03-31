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
    <!-- Optimized CSS Loading -->
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
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            zoom: 0.75;
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
        .register-container {
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .invalid-feedback {
            font-size: 0.875rem;
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
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                    Login
                </a>
            </div>
        </div>
    </nav>

    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Create Account</h4>
                        
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="number" class="form-label">Number</label>
                                <input id="number" type="tel" class="form-control @error('number') is-invalid @enderror" name="number" value="{{ old('number') }}" required minlength="4" maxlength="20" pattern="[0-9+\-\s()]+" autocomplete="tel">
                                @error('number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="university" class="form-label">University</label>
                                <select id="university" class="form-select @error('university') is-invalid @enderror" name="university" required autocomplete="university">
                                    <option value="" disabled {{ old('university') ? '' : 'selected' }}>Select your university</option>
                                    @foreach ($universities as $university)
                                        <option value="{{ $university['value'] }}" {{ old('university') === $university['value'] ? 'selected' : '' }}>
                                            {{ $university['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('university')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text small">
                                    Must be at least 8 characters long
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password-confirm" class="form-label">Confirm Password</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                                <label class="form-check-label small" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                            
                            <div class="text-center small">
                                <p class="mb-0">Already have an account? 
                                    <a href="{{ route('login') }}" class="text-decoration-none">Login</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimized JS Loading -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
