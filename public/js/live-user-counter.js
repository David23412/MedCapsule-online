(function () {
    function initializeLiveUserCounter() {
        const counterRoot = document.querySelector('.live-users');
        const userCountEl = counterRoot?.querySelector('.user-count');

        if (!counterRoot || !userCountEl) {
            return;
        }

        const initialCount = Number.parseInt(userCountEl.textContent || '0', 10);
        let currentCount = Number.isFinite(initialCount) ? initialCount : 0;

        const config = {
            reverbKey: counterRoot.getAttribute('data-reverb-key') || '',
            reverbHost: counterRoot.getAttribute('data-reverb-host') || '',
            reverbPort: counterRoot.getAttribute('data-reverb-port') || '',
            reverbScheme: counterRoot.getAttribute('data-reverb-scheme') || 'https',
            countEndpoint: counterRoot.getAttribute('data-count-endpoint') || '/api/active-users',
        };

        function animateValue(start, end, duration) {
            if (start === end) {
                return;
            }

            const range = end - start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.max(1, Math.abs(Math.floor(duration / range)));
            let current = start;

            const timer = window.setInterval(() => {
                current += increment;
                userCountEl.textContent = String(current);
                if (current === end) {
                    window.clearInterval(timer);
                    userCountEl.classList.remove('updating');
                }
            }, stepTime);
        }

        function updateCounter(nextCount) {
            const numericCount = Number.parseInt(String(nextCount || 0), 10);
            if (!Number.isFinite(numericCount) || numericCount === currentCount) {
                return;
            }

            userCountEl.classList.add('updating');
            animateValue(currentCount, numericCount, 500);
            currentCount = numericCount;
        }

        async function fetchInitialCount() {
            try {
                const response = await fetch(config.countEndpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                updateCounter(data.count);
            } catch (error) {
                console.error('Failed to fetch initial user count:', error);
            }
        }

        if (typeof window.Echo !== 'undefined' && config.reverbKey && config.reverbHost && config.reverbPort) {
            const echo = new window.Echo({
                broadcaster: 'reverb',
                key: config.reverbKey,
                wsHost: config.reverbHost,
                wsPort: config.reverbPort,
                wssPort: config.reverbPort,
                forceTLS: config.reverbScheme === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            echo.channel('live-users')
                .listen('UserCountUpdated', (event) => {
                    updateCounter(event.count);
                })
                .error((error) => {
                    console.error('Echo channel error:', error);
                });
        } else {
            console.error('Laravel Echo is not loaded for live user counter.');
        }

        fetchInitialCount();
    }

    document.addEventListener('DOMContentLoaded', initializeLiveUserCounter, { once: true });
})();
