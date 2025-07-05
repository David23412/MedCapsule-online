<!-- Notification Dropdown Content -->
<div 
    x-data="{ 
        showNotifications: false, 
        notifications: [], 
        unreadCount: 0,
        loading: true,
        async fetchNotifications() {
            try {
                this.loading = true;
                const response = await fetch('/notifications?t=' + new Date().getTime()); // Add timestamp to bust cache
                const data = await response.json();
                
                if (data.notifications) {
                    // Add a property to identify urgent notifications
                    this.notifications = data.notifications.map(notification => ({
                        ...notification,
                        isUrgent: ['weak_topics', 'weak_topic_added', 'review_mistakes'].includes(notification.type)
                    }));
                    this.unreadCount = data.unread_count;
                } else {
                    this.notifications = [];
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
                this.notifications = [];
                this.unreadCount = 0;
            } finally {
                this.loading = false;
            }
        },
        async markAsRead(id) {
            try {
                const response = await fetch('/notifications/mark-as-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content
                    },
                    body: JSON.stringify({
                        notification_ids: [id]
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Find and mark notification as read in local state
                    this.notifications = this.notifications.map(n => 
                        n.id === id ? {...n, is_read: true, read_at: new Date().toISOString()} : n
                    );
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },
        async markAllAsRead() {
            try {
                const response = await fetch('/notifications/mark-as-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content
                    },
                    body: JSON.stringify({
                        all: true
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mark all notifications as read in local state
                    this.notifications = this.notifications.map(n => ({...n, is_read: true, read_at: new Date().toISOString()}));
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                // Today, show time
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } else if (diffDays === 1) {
                return 'Yesterday';
            } else if (diffDays < 7) {
                return `${diffDays} days ago`;
            } else {
                return date.toLocaleDateString();
            }
        },
        getTypeIcon(type) {
            switch(type) {
                case 'achievement':
                case 'correct_answers_milestone':
                    return 'fas fa-award text-warning';
                case 'streak':
                case 'study_streak':
                    return 'fas fa-fire text-danger';
                case 'milestone':
                    return 'fas fa-trophy text-success';
                case 'topic_mastery':
                    return 'fas fa-graduation-cap text-success';
                case 'review_mistakes':
                    return 'fas fa-redo text-primary';
                case 'weak_topics':
                case 'weak_topic_added':
                    return 'fas fa-exclamation-triangle text-danger';
                case 'leaderboard_rank':
                    return 'fas fa-crown text-warning';
                case 'performance':
                    return 'fas fa-chart-line text-info';
                case 'quiz_completed':
                    return 'fas fa-check-circle text-success';
                case 'course_welcome':
                    return 'fas fa-book text-primary';
                case 'course_progress':
                    return 'fas fa-running text-info';
                case 'review_reminder':
                    return 'fas fa-history text-secondary';
                case 'info':
                default:
                    return 'fas fa-bell text-primary';
            }
        }
    }" 
    @click.away="showNotifications = false"
    x-init="fetchNotifications(); setInterval(() => { if (!showNotifications) fetchNotifications() }, 60000)"
    class="notification-dropdown"
>
    <button 
        @click="showNotifications = !showNotifications" 
        class="notification-btn"
        aria-label="Notifications"
        :class="{'pulse': unreadCount > 0}"
    >
        <i class="fas fa-bell"></i>
        <span 
            x-show="unreadCount > 0" 
            x-text="unreadCount" 
            class="notification-badge"
            x-transition:enter="scale-in"
            x-transition:leave="scale-out"
        ></span>
    </button>
    
    <div 
        x-show="showNotifications" 
        class="notification-dropdown-content"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-[-10px]"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-[-10px]"
    >
        <div class="notification-header">
            <h6>Notifications</h6>
            <template x-if="unreadCount > 0">
                <button 
                    @click="markAllAsRead"
                    class="mark-all-read"
                >
                    <i class="fas fa-check-double me-1"></i>
                    Mark all as read
                </button>
            </template>
            <template x-if="unreadCount === 0 && notifications.length > 0">
                <small class="text-muted">Auto-deleted after 1 minute</small>
            </template>
        </div>

        <div class="notification-list" x-show="!loading">
            <template x-if="notifications.length === 0">
                <div class="empty-notification">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <p class="empty-text">No notifications</p>
                    <small class="text-muted">Notifications auto-delete after 1 minute</small>
                </div>
            </template>
            
            <template x-if="notifications.length > 0">
                <div>
                    <template x-for="(notification, index) in notifications.slice(0, 5)" :key="notification.id">
                        <div 
                            :class="{
                                'notification-unread': !notification.is_read,
                                'notification-urgent': notification.isUrgent,
                                'notification-item-appear': true
                            }"
                            class="notification-item"
                            style="--appear-delay: calc(0.05s * var(--index))"
                            :style="{
                                '--index': index
                            }"
                            @click="notification.is_read ? null : markAsRead(notification.id)"
                        >
                            <div class="d-flex">
                                <div 
                                    class="notification-icon me-2"
                                    :class="{'icon-pulse': notification.isUrgent && !notification.is_read}"
                                >
                                    <i :class="getTypeIcon(notification.type)"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <span x-text="notification.title"></span>
                                        <small class="notification-time" x-text="notification.time_ago"></small>
                                    </div>
                                    <p class="notification-message" x-text="notification.message"></p>
                                    <template x-if="notification.isUrgent && !notification.is_read">
                                        <div class="notification-action-hint">
                                            <i class="fas fa-hand-point-up fa-xs me-1"></i>
                                            <small>Click to acknowledge</small>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="!notification.is_read">
                                <button 
                                    @click.stop="markAsRead(notification.id)" 
                                    class="mark-read-btn"
                                    title="Mark as read"
                                >
                                    <i class="fas fa-check"></i>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
        
        <div class="notification-list" x-show="loading">
            <div class="notification-loading">
                <div class="loading-spinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Loading notifications...</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Notifications */
.notification-dropdown {
    position: relative;
    display: inline-block;
}

.notification-btn {
    background: none;
    border: none;
    color: #495057;
    font-size: 1.2rem;
    position: relative;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.notification-btn:hover {
    background-color: #f8f9fa;
    color: #007bff;
    transform: scale(1.05);
}

/* Pulsing animation for button */
@keyframes pulse-ring {
    0% { transform: scale(0.95); }
    50% { transform: scale(1.05); }
    100% { transform: scale(0.95); }
}

.pulse {
    animation: pulse-ring 2s infinite;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #dc3545;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    border-radius: 50%;
    width: 1.2rem;
    height: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translate(30%, -30%);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Badge animations */
@keyframes scale-in {
    0% { transform: translate(30%, -30%) scale(0); }
    80% { transform: translate(30%, -30%) scale(1.2); }
    100% { transform: translate(30%, -30%) scale(1); }
}

@keyframes scale-out {
    0% { transform: translate(30%, -30%) scale(1); }
    100% { transform: translate(30%, -30%) scale(0); }
}

.scale-in {
    animation: scale-in 0.3s forwards;
}

.scale-out {
    animation: scale-out 0.3s forwards;
}

.notification-dropdown-content {
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    z-index: 1000;
    margin-top: 0.8rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
    color: #343a40;
}

.mark-all-read {
    background: none;
    border: none;
    color: #007bff;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.mark-all-read:hover {
    background-color: #e6f2ff;
    transform: translateY(-1px);
}

.notification-list {
    padding: 0.5rem 0;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    opacity: 0;
    animation: appear-in 0.5s forwards;
    animation-delay: var(--appear-delay, 0s);
}

@keyframes appear-in {
    0% { 
        opacity: 0; 
        transform: translateY(10px);
    }
    100% { 
        opacity: 1; 
        transform: translateY(0);
    }
}

.notification-item-appear {
    animation: appear-in 0.5s forwards;
    animation-delay: var(--appear-delay, 0s);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f8f9fa;
    transform: translateX(3px);
}

.notification-unread {
    background-color: #e3f2fd;
}

.notification-unread:hover {
    background-color: #d0e8fa;
}

/* Urgent notification styling */
.notification-urgent {
    position: relative;
}

.notification-urgent:not(.notification-unread)::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #ffcc00;
}

.notification-urgent.notification-unread {
    background-color: #fff3cd;
    border-left: 3px solid #dc3545;
}

.notification-urgent.notification-unread:hover {
    background-color: #ffecb5;
}

@keyframes icon-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.icon-pulse {
    animation: icon-pulse 1.5s infinite;
}

.notification-icon {
    font-size: 1.2rem;
    width: 2.2rem;
    height: 2.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.04);
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-item:hover .notification-icon {
    transform: scale(1.1) rotate(5deg);
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #343a40;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    font-size: 0.95rem;
}

.notification-time {
    color: #6c757d;
    font-size: 0.75rem;
    white-space: nowrap;
    margin-left: 0.5rem;
    font-weight: normal;
}

.notification-message {
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
    color: #495057;
    line-height: 1.4;
}

.notification-action-hint {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.3rem;
    animation: fade-in-up 1s ease infinite alternate;
}

@keyframes fade-in-up {
    0% { opacity: 0.7; transform: translateY(3px); }
    100% { opacity: 1; transform: translateY(0); }
}

.empty-notification {
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: #6c757d;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-text {
    font-size: 1.05rem;
    font-weight: 500;
    margin: 0;
    color: #495057;
}

.text-muted {
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 5px;
}

.empty-icon {
    font-size: 2.8rem;
    margin-bottom: 1.5rem;
    color: #dee2e6;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

.mark-read-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(0, 123, 255, 0.1);
    border: none;
    color: #007bff;
    cursor: pointer;
    font-size: 0.8rem;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    opacity: 0;
    transition: all 0.2s ease;
}

.notification-item:hover .mark-read-btn {
    opacity: 1;
}

.mark-read-btn:hover {
    background-color: rgba(0, 123, 255, 0.2);
    transform: scale(1.05);
}

.notification-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    padding: 2rem 1rem;
    color: #6c757d;
}

/* Custom loading spinner */
.loading-spinner {
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 576px) {
    .notification-dropdown-content {
        width: 300px;
        right: -100px;
    }
}
</style> 