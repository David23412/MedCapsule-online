// Notification bell — WebSocket with fallback fetch on error
function notificationBell() {
    return {
        notifications: [],
        isOpen: false,
        unreadCount: 0,
        loading: false,
        lastFetched: 0,
        notificationSound: null,
        prevUnreadCount: 0,
        websocket: null,

        init() {
            this.setUserInteractionFlag();

            // Fetch initial notifications immediately
            this.fetchNotifications().then(() => {
                this.prevUnreadCount = this.unreadCount;
            });

            this.initializeWebSocket();

            // Watch for unread count changes with instant response
            this.watchUnreadCount();
        },

        initializeWebSocket() {
            // Use wss:// on HTTPS pages to avoid Mixed Content errors
            const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            this.websocket = new WebSocket(`${proto}//${window.location.host}/notifications/stream`);

            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);

                if (data.type === 'notification') {
                    this.unreadCount = data.unread_count;
                    this.notifications = data.notifications || [];
                    this.updateBadge();

                    if (data.unread_count > this.prevUnreadCount) {
                        this.playNotificationSound();
                    }
                    this.prevUnreadCount = data.unread_count;
                }
            };

            this.websocket.onerror = () => {
                // Fallback to a single fetch when WebSocket is unavailable
                this.fetchNotifications();
            };
        },

        setUserInteractionFlag() {
            window.addEventListener('click', () => {
                if (!window.hasUserInteraction) {
                    window.hasUserInteraction = true;
                }
            });
        },

        watchUnreadCount() {
            this.$watch('unreadCount', (newCount, oldCount) => {
                if (newCount > oldCount && oldCount !== 0) {
                    this.playNotificationSound();

                    const bellIcon = this.$el.querySelector('i.fa-bell');
                    if (bellIcon) {
                        bellIcon.style.animation = 'none';
                        void bellIcon.offsetWidth; // Force reflow
                        bellIcon.style.animation = 'bellRing 1s';
                    }
                }
            });
        },

        playNotificationSound() {
            if (!window.hasUserInteraction) {
                return;
            }
            if (!this.notificationSound) {
                this.notificationSound = new Audio('/sounds/Soft-ui-notification-sound-effect.mp3');
            }
            this.notificationSound.currentTime = 0;
            this.notificationSound.play().catch(() => {});
        },

        async fetchNotifications() {
            try {
                this.loading = true;
                const response = await fetch('/notifications', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (data.success) {
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                    this.lastFetched = Date.now();
                }
            } catch (_error) {
                // Silently ignore network errors for notification fetches
            } finally {
                this.loading = false;
            }
        },

        updateBadge() {
            const badge = document.getElementById('notificationBadge');
            const displayCount = Math.min(this.unreadCount, 99);

            if (displayCount > 0) {
                if (badge) {
                    const oldCount = parseInt(badge.textContent, 10) || 0;
                    if (oldCount !== displayCount) {
                        badge.classList.add('badge-count-change');
                        badge.textContent = displayCount;
                        badge.setAttribute('aria-label', `${displayCount} unread notifications`);

                        requestAnimationFrame(() => {
                            badge.classList.remove('badge-count-change');
                        });
                    }
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notificationBadge';
                    newBadge.className = 'clean-notification-badge badge-appear';
                    newBadge.textContent = displayCount;
                    newBadge.setAttribute('aria-label', `${displayCount} unread notifications`);

                    const bell = document.querySelector('.notification-bell');
                    if (bell) {
                        bell.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.remove();
            }
        },

        cleanup() {
            if (this.websocket) {
                this.websocket.close();
                this.websocket = null;
            }
        }
    };
}

// Auto-cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.notificationBellInstance) {
        window.notificationBellInstance.cleanup();
    }
});
