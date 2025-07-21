@php
    use App\Services\UniversalNotificationService;
    
    // PERFORMANCE OPTIMIZED - AUTO-CLEANUP ON EVERY ACCESS
    $unreadCount = auth()->check() ? min(UniversalNotificationService::getUnreadCount(auth()->user()), 5) : 0;
@endphp

@if(auth()->check())
<!-- CLEAN BLUE NOTIFICATION SYSTEM -->
<div class="clean-notification-system" id="notificationSystem">
    <button 
        class="clean-notification-bell" 
        id="notificationBell" 
        aria-label="Notifications"
        aria-expanded="false"
        aria-haspopup="true"
    >
        <i class="fas fa-bell"></i>
        @if($unreadCount > 0)
            <span 
                class="clean-notification-badge" 
                id="notificationBadge"
                aria-label="{{ $unreadCount }} unread notifications"
            >
                {{ $unreadCount }}
            </span>
        @endif
    </button>
    
    <div 
        class="clean-notification-dropdown" 
        id="notificationDropdown"
        role="dialog"
        aria-label="Notifications"
        aria-hidden="true"
    >
        @include('components.notification-dropdown')
    </div>
</div>

<style>
/* === CLEAN BLUE NOTIFICATION SYSTEM === */

.clean-notification-system {
    position: relative;
    display: inline-block;
}

/* === CLEAN BLUE NOTIFICATION BELL === */
.clean-notification-bell {
    /* Size */
    width: 40px;
    height: 40px;
    
    /* Layout */
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    
    /* Appearance - Original blue design */
    background: transparent;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    
    /* Remove default styles */
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    
    /* Smooth micro-interactions */
    transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.clean-notification-bell i {
    font-size: 20px;
    color: #3b82f6;
    transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover State with micro-interaction */
.clean-notification-bell:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: scale(1.05);
}

.clean-notification-bell:hover i {
    color: #2563eb;
}

/* Active State with button press feedback */
.clean-notification-bell:active {
    animation: buttonPress 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Bell ring animation class */
.clean-notification-bell.bell-ring i {
    animation: bellRing 600ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* New notification indicator */
.clean-notification-bell.has-new-notification {
    animation: successPulse 800ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus State */
.clean-notification-bell:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* === CLEAN NOTIFICATION BADGE === */
.clean-notification-badge {
    /* Position */
    position: absolute;
    top: -6px;
    right: -6px;
    z-index: 10;
    
    /* Size */
    min-width: 18px;
    height: 18px;
    
    /* Layout */
    display: flex;
    align-items: center;
    justify-content: center;
    
    /* Appearance */
    background: #ef4444;
    color: white;
    border-radius: 50%;
    border: 2px solid white;
    
    /* Typography */
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    
    /* Shadow */
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
    
    /* Padding for text */
    padding: 0 2px;
    
    /* Smooth animations */
    transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Badge animation classes */
.clean-notification-badge.badge-appear {
    animation: badgeAppear 400ms cubic-bezier(0.4, 0, 0.2, 1);
}

.clean-notification-badge.badge-disappear {
    animation: badgeDisappear 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.clean-notification-badge.badge-count-change {
    animation: badgeCountChange 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* === CLEAN NOTIFICATION DROPDOWN === */
.clean-notification-dropdown {
    /* Position */
    position: absolute;
    top: calc(100% + 8px);
    right: -190px;
    z-index: 1050;
    
    /* Size - Wider and more spacious */
    width: 420px;
    max-height: 480px;
    
    /* Appearance */
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    
    /* Shadow */
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    
    /* Layout */
    display: none;
    flex-direction: column;
    overflow: hidden;
    
    /* Animation origin */
    transform-origin: top right;
}

.clean-notification-dropdown.show {
    display: flex;
    animation: cleanDropdownEnter 200ms ease;
}

/* === SMOOTH MICRO-INTERACTIONS AND ANIMATIONS === */

/* Dropdown entrance animation */
@keyframes cleanDropdownEnter {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* New notification entrance animation */
@keyframes notificationSlideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
        max-height: 0;
        padding: 0;
        margin: 0;
    }
    to {
        opacity: 1;
        transform: translateX(0);
        max-height: 100px;
        padding: 12px 20px;
        margin: 0;
    }
}

/* Badge count change animation */
@keyframes badgeCountChange {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Badge appearance animation */
@keyframes badgeAppear {
    from {
        opacity: 0;
        transform: scale(0);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Badge disappear animation */
@keyframes badgeDisappear {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0);
    }
}

/* Bell ring micro-interaction */
@keyframes bellRing {
    0%, 100% { transform: rotate(0deg); }
    10% { transform: rotate(10deg); }
    20% { transform: rotate(-8deg); }
    30% { transform: rotate(6deg); }
    40% { transform: rotate(-4deg); }
    50% { transform: rotate(2deg); }
    60% { transform: rotate(0deg); }
}

/* Button press feedback */
@keyframes buttonPress {
    0% { transform: scale(1); }
    50% { transform: scale(0.95); }
    100% { transform: scale(1); }
}

/* Notification item hover animation */
@keyframes notificationHover {
    from { transform: translateX(0); }
    to { transform: translateX(4px); }
}

/* Loading shimmer effect */
@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

/* Success feedback animation */
@keyframes successPulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 767px) {
    .clean-notification-dropdown {
        right: 8px;
        left: 8px;
        width: auto;
    }
}

/* === LOADING, ERROR, AND OFFLINE STATES === */

/* Loading State */
.notification-loading-state {
    padding: 16px 20px;
}

.loading-skeleton {
    animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-header,
.skeleton-icon,
.skeleton-title,
.skeleton-message {
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-header {
    height: 20px;
    border-radius: 4px;
    margin-bottom: 16px;
    width: 60%;
}

.skeleton-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.skeleton-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    animation: notificationSlideIn 400ms cubic-bezier(0.4, 0, 0.2, 1);
}

.skeleton-item:nth-child(2) {
    animation-delay: 100ms;
}

.skeleton-item:nth-child(3) {
    animation-delay: 200ms;
}

.skeleton-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
}

.skeleton-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-title {
    height: 16px;
    border-radius: 4px;
    width: 70%;
}

.skeleton-message {
    height: 14px;
    border-radius: 4px;
    width: 90%;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Error State */
.notification-error-state {
    padding: 32px 24px;
    text-align: center;
}

.error-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.error-icon {
    font-size: 32px;
    color: #ef4444;
    margin-bottom: 8px;
}

.error-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.error-message {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.error-retry-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 200ms ease;
    margin-top: 8px;
}

.error-retry-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.error-retry-btn:active {
    transform: translateY(0);
}

/* Offline State */
.notification-offline-state {
    padding: 32px 24px;
    text-align: center;
}

.offline-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.offline-icon {
    font-size: 32px;
    color: #f59e0b;
    margin-bottom: 8px;
}

.offline-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.offline-message {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

.offline-retry-btn {
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 200ms ease;
    margin-top: 8px;
}

.offline-retry-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
}

.offline-retry-btn:active {
    transform: translateY(0);
}

/* === ACCESSIBILITY === */
@media (prefers-reduced-motion: reduce) {
    .clean-notification-bell,
    .clean-notification-dropdown,
    .clean-notification-badge,
    .skeleton-item,
    .error-retry-btn,
    .offline-retry-btn {
        transition: none !important;
        animation: none !important;
    }
    
    .clean-notification-bell:hover,
    .error-retry-btn:hover,
    .offline-retry-btn:hover {
        transform: none !important;
    }
    
    .clean-notification-bell:active {
        animation: none !important;
    }
    
    /* Simplified animations for reduced motion */
    @keyframes cleanDropdownEnter {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 1; }
    }
    
    @keyframes shimmer {
        0%, 100% { background-position: 0 0; }
    }
    
    @keyframes notificationSlideIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes badgeAppear,
    @keyframes badgeDisappear,
    @keyframes badgeCountChange,
    @keyframes bellRing,
    @keyframes buttonPress,
    @keyframes notificationHover,
    @keyframes successPulse {
        from { opacity: 1; }
        to { opacity: 1; }
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationSystem = document.getElementById('notificationSystem');
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (!notificationSystem || !notificationBell || !notificationDropdown) {
        console.warn('Notification system elements not found');
        return;
    }
    
    // Initialize system
    initializeNotificationSystem();
    
    // Initialize network monitoring
    initializeNetworkMonitoring();
    
    function initializeNotificationSystem() {
        console.log('🔔 Clean Notification System Initialized');
        
        // Bell click handler
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationSystem.contains(e.target)) {
                closeDropdown();
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Keyboard navigation
        notificationBell.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleDropdown();
            }
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isDropdownOpen()) {
                closeDropdown();
                notificationBell.focus();
            }
        });
        
        // Load initial notifications
        loadNotifications();
    }
    
    function isDropdownOpen() {
        return notificationDropdown.classList.contains('show');
    }
    
    function toggleDropdown() {
        if (isDropdownOpen()) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }
    
    function openDropdown() {
        // Add smooth entrance animation
        notificationDropdown.style.display = 'flex';
        notificationDropdown.classList.add('show');
        notificationDropdown.setAttribute('aria-hidden', 'false');
        notificationBell.setAttribute('aria-expanded', 'true');
        
        // Add success feedback to bell
        notificationBell.classList.add('has-new-notification');
        setTimeout(() => {
            notificationBell.classList.remove('has-new-notification');
        }, 800);
        
        // Load fresh notifications
        loadNotifications();
        
        // AUTOMATICALLY MARK ALL AS READ WHEN OPENED - NO CLICKING NEEDED
        markAllNotificationsAsRead();
        
        // AGGRESSIVE AUTO-DELETE: Schedule deletion in 1 minute after viewing
        setTimeout(() => {
            aggressiveAutoDelete();
        }, 60000); // 60 seconds = 1 minute
        
        console.log('🔔 Clean dropdown opened - ALL NOTIFICATIONS AUTO-READ - AUTO-DELETE scheduled in 60 seconds');
    }
    
    function closeDropdown() {
        // Add smooth exit animation
        notificationDropdown.style.animation = 'cleanDropdownEnter 200ms cubic-bezier(0.4, 0, 0.2, 1) reverse';
        
        setTimeout(() => {
            notificationDropdown.classList.remove('show');
            notificationDropdown.setAttribute('aria-hidden', 'true');
            notificationBell.setAttribute('aria-expanded', 'false');
            notificationDropdown.style.display = 'none';
            notificationDropdown.style.animation = '';
        }, 200);
        
        console.log('🔕 Clean dropdown closed');
    }
    
    function loadNotifications() {
        console.log('📡 Loading notifications...');
        
        // Show loading state
        showLoadingState();
        
        fetch('/notifications', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('✅ Notifications loaded:', data.notifications.length);
                updateBadge(data.unread_count);
                hideLoadingState();
                // The dropdown component will handle rendering
            } else {
                throw new Error(data.message || 'Failed to load notifications');
            }
        })
        .catch(error => {
            console.error('❌ Failed to load notifications:', error);
            showErrorState(error.message);
        });
    }
    
    // LOADING STATE MANAGEMENT
    function showLoadingState() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.innerHTML = `
                <div class="notification-loading-state">
                    <div class="loading-skeleton">
                        <div class="skeleton-header"></div>
                        <div class="skeleton-items">
                            <div class="skeleton-item">
                                <div class="skeleton-icon"></div>
                                <div class="skeleton-content">
                                    <div class="skeleton-title"></div>
                                    <div class="skeleton-message"></div>
                                </div>
                            </div>
                            <div class="skeleton-item">
                                <div class="skeleton-icon"></div>
                                <div class="skeleton-content">
                                    <div class="skeleton-title"></div>
                                    <div class="skeleton-message"></div>
                                </div>
                            </div>
                            <div class="skeleton-item">
                                <div class="skeleton-icon"></div>
                                <div class="skeleton-content">
                                    <div class="skeleton-title"></div>
                                    <div class="skeleton-message"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        console.log('⏳ Loading state displayed');
    }
    
    function hideLoadingState() {
        // Loading state will be replaced by actual content
        console.log('✅ Loading state hidden');
    }
    
    // ERROR STATE MANAGEMENT
    function showErrorState(errorMessage) {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            // Fade out current content
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'translateY(-4px)';
            
            setTimeout(() => {
                dropdown.innerHTML = `
                    <div class="notification-error-state" style="opacity: 0; transform: translateY(8px);">
                        <div class="error-content">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                            <h4 class="error-title">Unable to Load Notifications</h4>
                            <p class="error-message">${errorMessage}</p>
                            <button class="error-retry-btn" onclick="retryLoadNotifications()">
                                <i class="fas fa-redo-alt"></i>
                                Try Again
                            </button>
                        </div>
                    </div>
                `;
                
                // Fade in error state
                dropdown.style.opacity = '1';
                dropdown.style.transform = 'translateY(0)';
                
                const errorState = dropdown.querySelector('.notification-error-state');
                setTimeout(() => {
                    errorState.style.opacity = '1';
                    errorState.style.transform = 'translateY(0)';
                    errorState.style.transition = 'all 300ms cubic-bezier(0.4, 0, 0.2, 1)';
                }, 50);
            }, 150);
        }
        console.log('❌ Error state displayed:', errorMessage);
    }
    
    // OFFLINE STATE MANAGEMENT
    function showOfflineState() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.innerHTML = `
                <div class="notification-offline-state">
                    <div class="offline-content">
                        <i class="fas fa-wifi-slash offline-icon"></i>
                        <h4 class="offline-title">You're Offline</h4>
                        <p class="offline-message">Check your internet connection and try again</p>
                        <button class="offline-retry-btn" onclick="retryLoadNotifications()">
                            <i class="fas fa-sync-alt"></i>
                            Retry
                        </button>
                    </div>
                </div>
            `;
        }
        console.log('📡 Offline state displayed');
    }
    
    // RETRY FUNCTIONALITY
    function retryLoadNotifications() {
        console.log('🔄 Retrying notification load...');
        loadNotifications();
    }
    
    // NETWORK STATUS MONITORING
    function initializeNetworkMonitoring() {
        window.addEventListener('online', function() {
            console.log('🌐 Back online - reloading notifications');
            if (isDropdownOpen()) {
                loadNotifications();
            }
        });
        
        window.addEventListener('offline', function() {
            console.log('📡 Gone offline');
            if (isDropdownOpen()) {
                showOfflineState();
            }
        });
    }
    
    function updateBadge(unreadCount) {
        const badge = document.getElementById('notificationBadge');
        
        // FORCE LIMIT TO 5 NOTIFICATIONS MAX - NEVER SHOW MORE
        const displayCount = Math.min(unreadCount, 5);
        
        if (displayCount > 0) {
            if (badge) {
                // Animate count change
                const oldCount = parseInt(badge.textContent) || 0;
                if (oldCount !== displayCount) {
                    badge.classList.add('badge-count-change');
                    setTimeout(() => {
                        badge.textContent = displayCount;
                        badge.setAttribute('aria-label', `${displayCount} unread notifications`);
                    }, 150);
                    setTimeout(() => {
                        badge.classList.remove('badge-count-change');
                    }, 300);
                }
            } else {
                // Create badge with entrance animation
                const newBadge = document.createElement('span');
                newBadge.id = 'notificationBadge';
                newBadge.className = 'clean-notification-badge badge-appear';
                newBadge.textContent = displayCount;
                newBadge.setAttribute('aria-label', `${displayCount} unread notifications`);
                notificationBell.appendChild(newBadge);
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    newBadge.classList.remove('badge-appear');
                }, 400);
                
                // Add bell ring animation for new notifications
                notificationBell.classList.add('bell-ring');
                setTimeout(() => {
                    notificationBell.classList.remove('bell-ring');
                }, 600);
            }
        } else {
            if (badge) {
                // Animate badge disappearance
                badge.classList.add('badge-disappear');
                setTimeout(() => {
                    if (badge.parentNode) {
                        badge.remove();
                    }
                }, 300);
            }
        }
        
        console.log(`🔢 FORCED LIMIT: Badge shows ${displayCount} (system had ${unreadCount})`);
    }
    
    // AGGRESSIVE AUTO-DELETE FUNCTION - DATABASE OPTIMIZATION
    function aggressiveAutoDelete() {
        fetch('/notifications/aggressive-cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateBadge(0);
                console.log('🗑️ AGGRESSIVE AUTO-DELETE: All viewed notifications deleted for database optimization');
                console.log(`📊 PERFORMANCE: Deleted ${data.deleted_count} notifications`);
            } else {
                throw new Error(data.message || 'Aggressive cleanup failed');
            }
        })
        .catch(error => {
            console.error('❌ Failed to perform aggressive auto-delete:', error);
            // Graceful degradation - continue without showing error to user
        });
    }
    
    // CLEAN AUTO-READ FUNCTION - NO CLICKING NEEDED
    window.markAllNotificationsAsRead = function() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update UI immediately - all notifications are now read
                updateBadge(0);
                console.log('✅ ALL NOTIFICATIONS AUTO-READ - NO CLICKING REQUIRED');
            } else {
                throw new Error(data.message || 'Failed to mark notifications as read');
            }
        })
        .catch(error => {
            console.error('❌ Failed to auto-read notifications:', error);
            // Show error state if dropdown is open
            if (isDropdownOpen()) {
                showErrorState('Failed to mark notifications as read. Please try again.');
            }
        });
    };
    
    // Delete specific notification for database optimization
    function deleteSpecificNotification(notificationId) {
        fetch('/notifications/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log(`🗑️ AGGRESSIVE DELETE: Notification ${notificationId} deleted for database optimization`);
            } else {
                throw new Error(data.message || 'Failed to delete notification');
            }
        })
        .catch(error => {
            console.error('❌ Failed to delete notification:', error);
            // Graceful degradation - continue without showing error
        });
    }
    
    // GLOBAL ERROR HANDLERS
    window.addEventListener('unhandledrejection', function(event) {
        console.error('🚨 Unhandled promise rejection in notification system:', event.reason);
        // Prevent the error from showing in console for users
        event.preventDefault();
    });
    
    // MAKE RETRY FUNCTION GLOBALLY AVAILABLE
    window.retryLoadNotifications = retryLoadNotifications;
});
</script>
@endif