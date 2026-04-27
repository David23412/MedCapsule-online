<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-start" content="{{ auth()->check() ? session()->get('auth.logged_in_at', time()) : time() }}">

    <title>MedCapsule</title>

    <!-- LIGHTNING-FAST FOUC PREVENTION -->
    <style>
        /* CRITICAL CSS - PREVENTS FOUC */
        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }
        
        /* EMERGENCY FIX - ALWAYS VISIBLE */
        #app {
            opacity: 1 !important;
        }
    </style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles - CRITICAL FIRST -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notification-design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast-design.css') }}">

    <!-- Scripts - OPTIMIZED LOADING -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js" defer></script>
    <script src="{{ asset('js/session-handler.js') }}" defer></script>
    <script src="{{ asset('js/toast-manager.js') }}" defer></script>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.admin-dark-mode-head')
</head>
<body>
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <div id="app">
        @include('partials.navigation')

        <!-- Page Content -->
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @include('partials.admin-dark-mode-script')
    @stack('scripts')
</body>
</html> 
