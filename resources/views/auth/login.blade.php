<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'MedCapsule') }}</title>
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
        .login-container {
            margin-top: 80px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
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
