<link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}" data-medcapsule-theme-stylesheet="true">
<script>
    (function () {
        try {
            if (window.localStorage.getItem('medcapsule-theme') === 'dark') {
                document.documentElement.setAttribute('data-admin-theme', 'dark');
            }
        } catch (error) {
            console.warn('Unable to restore theme preference.', error);
        }
    })();
</script>
