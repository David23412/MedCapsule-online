<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    <meta
        name="description"
        content="MedCapsule helps medical students learn faster with structured courses, recall systems, and clinical practice."
    >

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300..700&family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Sora:wght@100..800&display=swap"
        rel="stylesheet"
    >

    <script>
        window.__MEDCAPSULE_LANDING__ = {{ Illuminate\Support\Js::from([
            'isAuthenticated' => auth()->check(),
            'appUrl' => url(''),
            'user' => auth()->check() ? [
                'name' => auth()->user()->name,
                'profile_image_url' => auth()->user()->getProfileImageUrl(),
                'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&size=100&background=F0F4FF&color=3579F6',
            ] : null,
        ]) }};
    </script>

    @viteReactRefresh
    @vite('resources/js/landing-entry.tsx')
</head>
<body>
    <div id="root"></div>
</body>
</html>
