@php
    $adminDarkModeEnabled = auth()->check() && auth()->user()->isAdmin();
@endphp
<meta name="medcapsule-admin-dark-mode-access" content="{{ $adminDarkModeEnabled ? '1' : '0' }}">
<link rel="stylesheet" href="{{ asset('css/admin-dark-mode.css') }}">
<script>
    (function () {
        var canUseAdminDarkMode = document.querySelector('meta[name="medcapsule-admin-dark-mode-access"]')?.getAttribute('content') === '1';
        if (!canUseAdminDarkMode) {
            document.documentElement.removeAttribute('data-admin-theme');
            return;
        }

        try {
            if (window.localStorage.getItem('medcapsule-admin-theme') === 'dark') {
                document.documentElement.setAttribute('data-admin-theme', 'dark');
            }
        } catch (error) {
            console.warn('Unable to restore admin theme preference.', error);
        }
    })();
</script>
