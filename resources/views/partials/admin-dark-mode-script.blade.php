<script>
    (function () {
        if (window.MedCapsuleTheme) {
            window.MedCapsuleTheme.syncToggles();
            return;
        }

        var storageKey = 'medcapsule-theme';
        var root = document.documentElement;

        function isDarkModeEnabled() {
            return root.getAttribute('data-admin-theme') === 'dark';
        }

        function syncToggles() {
            document.querySelectorAll('[data-admin-theme-toggle]').forEach(function (toggle) {
                var enabled = isDarkModeEnabled();
                toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
                toggle.classList.toggle('is-active', enabled);

                var label = toggle.querySelector('[data-admin-theme-label]');
                if (label) {
                    label.textContent = enabled ? 'Light mode' : 'Dark mode';
                }

                var icon = toggle.querySelector('[data-admin-theme-icon]');
                if (icon) {
                    icon.className = enabled ? 'fas fa-moon' : 'fas fa-circle-half-stroke';
                }
            });
        }

        function setTheme(nextTheme) {
            if (nextTheme === 'dark') {
                root.setAttribute('data-admin-theme', 'dark');
            } else {
                root.removeAttribute('data-admin-theme');
            }

            try {
                window.localStorage.setItem(storageKey, nextTheme);
            } catch (error) {
                console.warn('Unable to persist theme preference.', error);
            }

            syncToggles();
            window.dispatchEvent(new CustomEvent('medcapsule-theme-change', {
                detail: { theme: nextTheme }
            }));
        }

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-admin-theme-toggle]');
            if (!toggle) {
                return;
            }

            event.preventDefault();
            setTheme(isDarkModeEnabled() ? 'light' : 'dark');
        });

        syncToggles();
        window.MedCapsuleTheme = {
            setTheme: setTheme,
            isDarkModeEnabled: isDarkModeEnabled,
            syncToggles: syncToggles
        };
    })();
</script>
