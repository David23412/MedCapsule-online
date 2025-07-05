{{-- Notification Bell Component --}}
<li class="nav-item me-3">
    <div class="notification-bell position-relative" 
         x-data="notificationBell()" 
         :class="{'has-unread': unreadCount > 0}">
        <button 
            @click="toggleNotifications" 
            class="position-relative focus:outline-none"
            aria-label="Notifications"
        >
            <i class="fas fa-bell"></i>
            <span 
                x-cloak
                x-show="unreadCount > 0" 
                x-text="unreadCount > 99 ? '99+' : unreadCount"
                class="badge"
            ></span>
        </button>
        
        <div 
            x-show="isOpen" 
            @click.away="isOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="notification-dropdown"
            style="display: none;"
        >
            <div class="notification-header">
                <h6>Notifications</h6>
                <button 
                    x-show="unreadCount > 0"
                    @click="markAllAsRead()"
                    class="mark-all-read"
                    x-cloak
                >
                    <i class="fas fa-check-double me-1"></i>
                    Mark all as read
                </button>
            </div>

            <div class="notification-list" x-show="loading">
                <div class="notification-loading">
                    <div class="loading-spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>Loading notifications...</span>
                </div>
            </div>

            <div class="notification-list" x-show="!loading">
                <template x-if="notifications.length === 0">
                    <div class="empty-notification">
                        <div class="empty-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>
                        <p class="empty-text">No notifications</p>
                    </div>
                </template>
                
                <template x-if="notifications.length > 0">
                    <div>
                        <template x-for="(notification, index) in notifications.slice(0, 5)" :key="notification.id">
                            <div 
                                :class="{
                                    'notification-unread': !notification.is_read,
                                    'notification-urgent': ['weak_topics', 'weak_topic_added', 'review_mistakes'].includes(notification.type),
                                    'notification-item-appear': true
                                }"
                                class="notification-item"
                                style="--appear-delay: calc(0.05s * var(--index))"
                                :style="{
                                    '--index': index
                                }"
                            >
                                <div class="d-flex">
                                    <div 
                                        class="notification-icon me-2"
                                        :class="{'icon-pulse': ['weak_topics', 'weak_topic_added', 'review_mistakes'].includes(notification.type) && !notification.is_read}"
                                    >
                                        <i :class="getTypeIcon(notification.type)"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">
                                            <span x-text="notification.title"></span>
                                            <small class="notification-time" x-text="notification.time_ago"></small>
                                        </div>
                                        <p class="notification-message" x-text="notification.message"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</li> 