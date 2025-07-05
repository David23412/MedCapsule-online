// Notification Bell JavaScript
function notificationBell() {
    return {
        notifications: [],
        isOpen: false,
        unreadCount: 0,
        loading: false,
        lastFetched: 0,
        notificationSound: new Audio('/sounds/notification-sound.mp3'),
        prevUnreadCount: 0,
        
        init() {
            // Preload the notification sound
            this.notificationSound.load();
            
            // Set user interaction flag
            this.setUserInteractionFlag();
            
            // Fetch initial notifications
            this.fetchNotifications().then(() => {
                this.prevUnreadCount = this.unreadCount;
            });
            
            // Set up polling
            this.setupPolling();

            // Watch for unread count changes
            this.watchUnreadCount();
        },
        
        setUserInteractionFlag() {
            if (!document.documentElement.hasAttribute('data-user-interacted')) {
                const interactionEvents = [
                    'click', 'touchstart', 'keydown', 'scroll', 'mousedown'
                ];
                
                const handleInteraction = () => {
                    document.documentElement.setAttribute('data-user-interacted', 'true');
                    interactionEvents.forEach(event => {
                        document.removeEventListener(event, handleInteraction);
                    });
                };
                
                interactionEvents.forEach(event => {
                    document.addEventListener(event, handleInteraction);
                });
            }
        },
        
        setupPolling() {
            let pollInterval = 30000; // 30 seconds
            
            const checkForNewNotifications = () => {
                if (Date.now() - this.lastFetched >= 30000) {
                    const oldUnreadCount = this.unreadCount;
                    
                    this.fetchNotifications().then(() => {
                        if (this.unreadCount > oldUnreadCount) {
                            this.playNotificationSound();
                        }
                    });
                }
                setTimeout(checkForNewNotifications, pollInterval);
            };
            
            // Adjust poll interval based on tab visibility
            document.addEventListener('visibilitychange', () => {
                pollInterval = document.visibilityState === 'visible' ? 30000 : 180000;
                
                if (document.visibilityState === 'visible' && Date.now() - this.lastFetched >= 10000) {
                    const oldUnreadCount = this.unreadCount;
                    this.fetchNotifications().then(() => {
                        if (this.unreadCount > oldUnreadCount) {
                            this.playNotificationSound();
                        }
                    });
                }
            });
            
            // Start polling
            setTimeout(checkForNewNotifications, pollInterval);
        },
        
        watchUnreadCount() {
            this.$watch('unreadCount', (newValue, oldValue) => {
                if (newValue > oldValue) {
                    // Animate bell
                    const bellIcon = this.$el.querySelector('i.fa-bell');
                    if (bellIcon) {
                        bellIcon.style.animation = 'none';
                        void bellIcon.offsetWidth;
                        bellIcon.style.animation = 'bellRing 1s';
                    }
                    
                    // Play sound when count increases
                    if (newValue > this.prevUnreadCount) {
                        this.playNotificationSound();
                        this.prevUnreadCount = newValue;
                    }
                }
            });
        },
        
        // Fetch notifications from server
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
            } catch (error) {
                console.error('Error fetching notifications:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Mark all notifications as read
        async markAllAsRead() {
            if (this.unreadCount === 0) return;
            
            try {
                const response = await fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update local state
                    this.notifications = this.notifications.map(notification => ({
                        ...notification,
                        is_read: true
                    }));
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },
        
        // Play notification sound if user has interacted with page
        playNotificationSound() {
            if (document.documentElement.hasAttribute('data-user-interacted')) {
                this.notificationSound.currentTime = 0;
                this.notificationSound.play().catch(err => {
                    console.log('Could not play notification sound:', err);
                });
            }
        },
        
        // Toggle notification dropdown
        toggleNotifications() {
            this.isOpen = !this.isOpen;
            
            if (this.isOpen) {
                this.fetchNotifications();
            }
        },
        
        // Get appropriate icon class based on notification type
        getIconClass(type) {
            switch(type) {
                case 'achievement':
                    return 'fas fa-award text-warning';
                case 'streak':
                    return 'fas fa-fire text-danger';
                case 'milestone':
                    return 'fas fa-trophy text-warning';
                case 'weak_topics':
                case 'weak_topic_added':
                    return 'fas fa-exclamation-triangle text-danger';
                case 'review_mistakes':
                    return 'fas fa-redo text-primary';
                default:
                    return 'fas fa-bell text-primary';
            }
        }
    };
} 