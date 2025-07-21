/**
 * Universal Notification System JavaScript Module
 * Handles notification bell, dropdown, and API interactions
 */
class NotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.notifications = [];
        this.unreadCount = 0;
        this.isLoading = false;
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        document.addEventListener('DOMContentLoaded', () => {
            this.setupEventListeners();
            this.loadNotifications();
        });
        
        this.isInitialized = true;
        console.log('NotificationSystem initialized');
    }

    setupEventListeners() {
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (!notificationBell) return;
        
        // Toggle notification dropdown
        notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notificationBell.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }

    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const bell = document.getElementById('notificationBell');
        
        if (!dropdown) return;
        
        const isVisible = dropdown.style.display === 'block';
        
        if (isVisible) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const bell = document.getElementById('notificationBell');
        
        if (!dropdown) return;
        
        dropdown.style.display = 'block';
        
        if (this.unreadCount > 0) {
            bell.classList.add('has-unread');
        }
        
        // Load fresh notifications when opening
        this.loadNotifications();
    }

    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const bell = document.getElementById('notificationBell');
        
        if (!dropdown) return;
        
        dropdown.style.display = 'none';
        bell.classList.remove('has-unread');
    }

    async loadNotifications() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch('/notifications', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load notifications');
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.warn('Failed to load notifications:', error);
            this.showError('Failed to load notifications');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    renderNotifications() {
        const notificationList = document.getElementById('notificationList');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        
        if (!notificationList) return;
        
        if (this.notifications.length === 0) {
            notificationList.innerHTML = this.getEmptyNotificationHTML();
            return;
        }
        
        let html = '';
        this.notifications.forEach((notification, index) => {
            html += this.getNotificationItemHTML(notification, index);
        });
        
        notificationList.innerHTML = html;
        
        // Show/hide mark all read button
        if (markAllReadBtn) {
            markAllReadBtn.style.display = this.unreadCount > 0 ? 'flex' : 'none';
        }
    }

    getNotificationItemHTML(notification, index) {
        const iconColor = this.getNotificationIconColor(notification.type);
        const icon = this.getNotificationIcon(notification.type);
        const isUnread = !notification.read;
        const isUrgent = notification.priority === 'urgent';
        
        return `
            <div class="notification-item ${isUnread ? 'notification-unread' : ''} ${isUrgent ? 'notification-urgent' : ''}"
                 data-notification-id="${notification.id}"
                 style="--appear-delay: ${index * 0.1}s"
                 onclick="notificationSystem.markAsRead('${notification.id}')">
                <div class="d-flex align-items-start">
                    <div class="notification-icon ${isUrgent && isUnread ? 'icon-pulse' : ''}">
                        <i class="${icon}" style="color: ${iconColor};"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="notification-title">
                            ${notification.title}
                            <span class="notification-time">${this.formatTimeAgo(notification.created_at)}</span>
                        </div>
                        <div class="notification-message">${notification.message}</div>
                        ${isUnread ? '<div class="notification-action-hint"><i class="fas fa-mouse-pointer me-1"></i>Click to mark as read</div>' : ''}
                    </div>
                </div>
            </div>
        `;
    }

    getEmptyNotificationHTML() {
        return `
            <div class="empty-notification">
                <i class="fas fa-bell-slash mb-2" style="font-size: 2rem; color: #dee2e6;"></i>
                <p class="mb-0">No notifications yet</p>
                <small class="text-muted">We'll notify you when something important happens</small>
            </div>
        `;
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            if (!response.ok) {
                throw new Error('Failed to mark notification as read');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('notification-unread');
                    const actionHint = notificationItem.querySelector('.notification-action-hint');
                    if (actionHint) {
                        actionHint.remove();
                    }
                }
                
                // Update local state
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read = true;
                }
                
                this.unreadCount = data.unread_count;
                this.updateBadge();
                this.updateMarkAllReadButton();
            }
        } catch (error) {
            console.warn('Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to mark all notifications as read');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-unread').forEach(item => {
                    item.classList.remove('notification-unread');
                });
                document.querySelectorAll('.notification-action-hint').forEach(hint => {
                    hint.remove();
                });
                
                // Update local state
                this.notifications.forEach(notification => {
                    notification.read = true;
                });
                
                this.unreadCount = 0;
                this.updateBadge();
                this.updateMarkAllReadButton();
            }
        } catch (error) {
            console.warn('Failed to mark all notifications as read:', error);
        }
    }

    updateBadge() {
        const badge = document.getElementById('notificationBadge');
        const bell = document.getElementById('notificationBell');
        
        if (!bell) return;
        
        if (this.unreadCount > 0) {
            if (badge) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            } else {
                // Create badge if it doesn't exist
                const newBadge = document.createElement('span');
                newBadge.id = 'notificationBadge';
                newBadge.className = 'notification-badge';
                newBadge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                bell.querySelector('.notification-bell-btn').appendChild(newBadge);
            }
            bell.classList.add('has-unread');
        } else {
            if (badge) {
                badge.remove();
            }
            bell.classList.remove('has-unread');
        }
    }

    updateMarkAllReadButton() {
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.style.display = this.unreadCount > 0 ? 'flex' : 'none';
        }
    }

    showLoading() {
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.innerHTML = `
                <div class="notification-loading">
                    <div class="loading-spinner"></div>
                    <span>Loading notifications...</span>
                </div>
            `;
        }
    }

    hideLoading() {
        // Loading will be hidden when renderNotifications is called
    }

    showError(message) {
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.innerHTML = `
                <div class="empty-notification">
                    <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem; color: #dc3545;"></i>
                    <p class="mb-0">${message}</p>
                    <small class="text-muted">Please try again later</small>
                </div>
            `;
        }
    }

    getNotificationIcon(type) {
        const icons = {
            'course_welcome': 'fas fa-graduation-cap',
            'quiz_completed': 'fas fa-check-circle',
            'achievement': 'fas fa-trophy',
            'streak_milestone': 'fas fa-fire',
            'assignment_due': 'fas fa-clock',
            'grade_released': 'fas fa-star',
            'system_update': 'fas fa-bell',
            'payment_success': 'fas fa-credit-card',
            'payment_failed': 'fas fa-exclamation-triangle',
            'course_access_granted': 'fas fa-unlock',
            'course_access_denied': 'fas fa-lock',
            'reminder': 'fas fa-reminder',
            'announcement': 'fas fa-bullhorn',
            'error': 'fas fa-exclamation-circle',
            'success': 'fas fa-check',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        return icons[type] || 'fas fa-bell';
    }

    getNotificationIconColor(type) {
        const colors = {
            'course_welcome': '#28a745',
            'quiz_completed': '#007bff',
            'achievement': '#ffc107',
            'streak_milestone': '#fd7e14',
            'assignment_due': '#dc3545',
            'grade_released': '#6f42c1',
            'system_update': '#6c757d',
            'payment_success': '#28a745',
            'payment_failed': '#dc3545',
            'course_access_granted': '#28a745',
            'course_access_denied': '#dc3545',
            'reminder': '#17a2b8',
            'announcement': '#007bff',
            'error': '#dc3545',
            'success': '#28a745',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        return colors[type] || '#6c757d';
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return time.toLocaleDateString();
    }
}

// Initialize notification system
const notificationSystem = new NotificationSystem();

// Global functions for backward compatibility
function markNotificationAsRead(notificationId) {
    notificationSystem.markAsRead(notificationId);
}

function markAllNotificationsAsRead() {
    notificationSystem.markAllAsRead();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}

// Make available globally
window.NotificationSystem = NotificationSystem;
window.notificationSystem = notificationSystem;