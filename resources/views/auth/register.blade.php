<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - {{ config('app.name', 'MedCapsule') }}</title>
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
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
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
                                <label for="university" class="form-label">University</label>
                                <input id="university" type="text" class="form-control @error('university') is-invalid @enderror" name="university" value="{{ old('university') }}" required autocomplete="university">
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