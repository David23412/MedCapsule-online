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
/* === ULTRA-MODERN NOTIFICATION SYSTEM === */

.clean-notification-system {
    position: relative;
    display: inline-block;
}

/* === CLEAN NOTIFICATION BELL (NO BOX) === */
.clean-notification-bell {
    /* Size */
    width: 44px;
    height: 44px;
    
    /* Layout */
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    
    /* Clean design - no box */
    background: transparent;
    border: none;
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
    font-size: 18px;
    color: #667eea;
    transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover State with clean micro-interaction */
.clean-notification-bell:hover {
    background: transparent;
    transform: translateY(-1px);
}

.clean-notification-bell:hover i {
    color: #5a67d8;
    transform: scale(1.1);
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

/* === MODERN NOTIFICATION BADGE === */
.clean-notification-badge {
    /* Position */
    position: absolute;
    top: -8px;
    right: -8px;
    z-index: 10;
    
    /* Size */
    min-width: 20px;
    height: 20px;
    
    /* Layout */
    display: flex;
    align-items: center;
    justify-content: center;
    
    /* Modern gradient appearance */
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border-radius: 10px;
    border: 2px solid rgba(255, 255, 255, 0.9);
    
    /* Typography */
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    
    /* Enhanced shadow */
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    
    /* Padding for text */
    padding: 0 3px;
    
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

/* === ULTRA-MODERN NOTIFICATION DROPDOWN === */
.clean-notification-dropdown {
    /* Position */
    position: absolute;
    top: calc(100% + 12px);
    right: -200px; /* Proper positioning to prevent cutoff */
    z-index: 1050;
    
    /* Size - Wider and more spacious */
    width: 450px;
    max-height: 520px;
    
    /* Modern glass morphism appearance */
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    
    /* Enhanced shadow */
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1);
    
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

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Screen reader only class */
.sr-only {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
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

/* Advanced Loading State */
.notification-loading-state {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.loading-header-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px 24px 12px;
    border-bottom: 1px solid #f3f4f6;
}

.skeleton-header {
    height: 16px;
    border-radius: 4px;
    width: 120px;
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #f3f4f6;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-skeleton {
    padding: 8px 0;
}

.loading-status {
    padding: 16px 20px;
    text-align: center;
    border-top: 1px solid #f8fafc;
    background: #fafbfc;
}

.loading-text {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
    display: block;
    margin-bottom: 8px;
}

.cache-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 12px;
    color: #94a3b8;
}

.cache-info i {
    font-size: 11px;
}

.skeleton-items {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.skeleton-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f3f4f6;
    animation: notificationSlideIn 400ms cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    animation-fill-mode: forwards;
}

.skeleton-item:nth-child(1) {
    animation-delay: 0ms;
}

.skeleton-item:nth-child(2) {
    animation-delay: 100ms;
}

.skeleton-item:nth-child(3) {
    animation-delay: 200ms;
}

.skeleton-item:last-child {
    border-bottom: none;
}

.skeleton-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
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
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-message {
    height: 14px;
    border-radius: 4px;
    width: 90%;
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200px 100%;
    animation: shimmer 1.5s infinite;
}

/* Error State */
.notification-error-state {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.error-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 32px 24px;
    text-align: center;
}

.error-icon-container {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}

.error-icon {
    font-size: 28px;
    color: #ef4444;
}

.error-title {
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
    line-height: 1.4;
}

.error-message {
    font-size: 15px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
    max-width: 280px;
}

.error-retry-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 8px;
    min-height: 44px;
}

.error-retry-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.error-retry-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.error-retry-btn:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Offline State */
.notification-offline-state {
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.offline-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 32px 24px;
    text-align: center;
}

.offline-icon-container {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}

.offline-icon {
    font-size: 28px;
    color: #f59e0b;
}

.offline-title {
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
    line-height: 1.4;
}

.offline-message {
    font-size: 15px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
    max-width: 280px;
}

.offline-retry-btn {
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 8px;
    min-height: 44px;
}

.offline-retry-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.offline-retry-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
}

.offline-retry-btn:focus {
    outline: 2px solid #f59e0b;
    outline-offset: 2px;
}

.offline-cached-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #94a3b8;
    background: #f8fafc;
    padding: 8px 12px;
    border-radius: 6px;
    margin-top: 8px;
}

.offline-cached-notice i {
    font-size: 12px;
}

/* Cached Notifications State */
.cached-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: rgba(245, 158, 11, 0.1);
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
    font-size: 13px;
    color: #92400e;
}

.cached-warning i {
    font-size: 12px;
    color: #f59e0b;
    flex-shrink: 0;
}

.cached-warning span {
    flex: 1;
    line-height: 1.4;
}

.cached-retry-btn {
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
}

.cached-retry-btn:hover {
    background: #d97706;
    transform: translateY(-1px);
}

.cached-retry-btn:active {
    transform: translateY(0);
}

.cached-retry-btn:focus {
    outline: 2px solid #f59e0b;
    outline-offset: 1px;
}

.cached-notification {
    position: relative;
    opacity: 0.9;
}

.cached-notification::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(245, 158, 11, 0.03) 10px,
        rgba(245, 158, 11, 0.03) 20px
    );
    pointer-events: none;
}

.cached-notification .notification-time::after {
    content: ' (cached)';
    font-size: 10px;
    color: #f59e0b;
    font-weight: 500;
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
        
        // Start performance tracking
        startPerformanceTracking();
        
        // Check network status first
        if (!navigator.onLine) {
            console.log('📡 Device is offline - showing offline state');
            endPerformanceTracking(false);
            showOfflineState();
            announceToScreenReader('You are offline. Showing cached notifications if available.');
            return;
        }
        
        // Show loading state with accessibility announcement
        showLoadingState();
        announceToScreenReader('Loading notifications...');
        
        // Create AbortController for timeout handling
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
        }, 8000); // 8 second timeout for better UX
        
        // Add request headers for better caching and performance
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Cache-Control': 'no-cache',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        // Add performance timing header if available
        if (performanceMetrics.loadStartTime) {
            headers['X-Client-Timestamp'] = performanceMetrics.loadStartTime.toString();
        }
        
        fetch('/notifications', {
            method: 'GET',
            headers: headers,
            signal: controller.signal,
            credentials: 'same-origin'
        })
        .then(response => {
            clearTimeout(timeoutId);
            
            // Log response timing
            const responseTime = performance.now() - performanceMetrics.loadStartTime;
            console.log(`📊 Server response time: ${responseTime.toFixed(2)}ms`);
            
            if (!response.ok) {
                // Enhanced error information
                const errorInfo = {
                    status: response.status,
                    statusText: response.statusText,
                    url: response.url,
                    headers: Object.fromEntries(response.headers.entries())
                };
                throw new Error(`HTTP ${response.status}: ${response.statusText}`, { cause: errorInfo });
            }
            
            return response.json();
        })
        .then(data => {
            const loadTime = endPerformanceTracking(true);
            
            if (data.success) {
                console.log('✅ Notifications loaded:', data.notifications.length, `in ${loadTime.toFixed(2)}ms`);
                
                // Cache notifications for offline use
                cacheNotifications(data.notifications);
                
                // Update badge with smooth animation
                updateBadge(data.unread_count);
                
                // Hide loading state
                hideLoadingState();
                
                // Reload the dropdown content with fresh data
                reloadDropdownContent();
                
                // Reset retry count on success
                retryCount = 0;
                
                // Announce success to screen readers
                const notificationCount = data.notifications.length;
                const unreadCount = data.unread_count;
                announceToScreenReader(
                    `Notifications loaded. ${notificationCount} total notifications, ${unreadCount} unread.`
                );
                
            } else {
                throw new Error(data.message || 'Server returned unsuccessful response');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            endPerformanceTracking(false);
            
            console.error('❌ Failed to load notifications:', error);
            
            // Enhanced error handling with specific recovery strategies
            if (error.name === 'AbortError') {
                console.log('⏱️ Request timed out');
                performanceMetrics.retryAttempts++;
                
                if (retryCount < maxRetries) {
                    console.log('🔄 Auto-retrying due to timeout');
                    announceToScreenReader('Request timed out. Retrying...');
                    autoRetryLoadNotifications();
                } else {
                    announceToScreenReader('Request timed out. Showing cached notifications.', 'assertive');
                    showCachedNotificationsOrError('Request timed out. Please try again.');
                }
                
            } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                console.log('🌐 Network error detected');
                performanceMetrics.networkErrors++;
                
                if (navigator.onLine && retryCount < maxRetries) {
                    console.log('🔄 Auto-retrying due to network error');
                    announceToScreenReader('Network error. Retrying...');
                    autoRetryLoadNotifications();
                } else {
                    announceToScreenReader('Network connection issue. Showing cached notifications.', 'assertive');
                    showCachedNotificationsOrError('Network connection issue. Showing cached notifications.');
                }
                
            } else if (error.message.includes('HTTP 500') || error.message.includes('HTTP 502') || error.message.includes('HTTP 503')) {
                console.log('🔧 Server error detected');
                performanceMetrics.retryAttempts++;
                
                if (retryCount < maxRetries) {
                    console.log('🔄 Auto-retrying due to server error');
                    announceToScreenReader('Server temporarily unavailable. Retrying...');
                    autoRetryLoadNotifications();
                } else {
                    announceToScreenReader('Server error. Please try again later.', 'assertive');
                    showErrorState(error.message);
                }
                
            } else if (error.message.includes('HTTP 403')) {
                console.log('🔒 Authentication error detected');
                announceToScreenReader('Authentication required. Please refresh the page.', 'assertive');
                showErrorState('Session expired. Please refresh the page to continue.');
                
            } else if (error.message.includes('HTTP 404')) {
                console.log('🔍 Endpoint not found');
                announceToScreenReader('Notification service not available.', 'assertive');
                showErrorState('Notification service temporarily unavailable. Please contact support if this persists.');
                
            } else {
                // For other errors, show error state immediately
                console.log('❓ Unknown error type:', error.message);
                announceToScreenReader('An error occurred loading notifications.', 'assertive');
                showErrorState(error.message);
            }
        });
    }
    
    // ADVANCED LOADING STATE MANAGEMENT
    function showLoadingState() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        // Get cache info for better loading experience
        const cacheInfo = getCacheInfo();
        const loadingMessage = cacheInfo.exists ? 
            `Refreshing notifications... (${cacheInfo.count} cached)` : 
            'Loading notifications...';
        
        dropdown.innerHTML = `
            <div class="notification-loading-state" role="status" aria-label="${loadingMessage}">
                <div class="notification-header">
                    <div class="loading-header-content">
                        <div class="skeleton-header" aria-hidden="true"></div>
                        <div class="loading-spinner" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="notification-list">
                    <div class="loading-skeleton" aria-hidden="true">
                        <div class="skeleton-items">
                            ${generateSkeletonItems(3)}
                        </div>
                    </div>
                    <div class="loading-status">
                        <span class="loading-text">${loadingMessage}</span>
                        ${cacheInfo.exists ? `
                            <div class="cache-info">
                                <i class="fas fa-clock"></i>
                                <span>Last updated ${cacheInfo.ageMinutes}m ago</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        // Add loading timeout indicator
        setTimeout(() => {
            const loadingText = dropdown.querySelector('.loading-text');
            if (loadingText && loadingText.textContent.includes('Loading')) {
                loadingText.textContent = 'Still loading... This is taking longer than usual.';
                announceToScreenReader('Loading is taking longer than expected. Please wait.');
            }
        }, 5000);
        
        console.log('⏳ Advanced loading state displayed with cache info:', cacheInfo);
    }
    
    function generateSkeletonItems(count) {
        let items = '';
        for (let i = 0; i < count; i++) {
            items += `
                <div class="skeleton-item" style="animation-delay: ${i * 100}ms;">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title" style="width: ${60 + Math.random() * 30}%;"></div>
                        <div class="skeleton-message" style="width: ${80 + Math.random() * 20}%;"></div>
                    </div>
                </div>
            `;
        }
        return items;
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
            dropdown.style.transition = 'all 200ms cubic-bezier(0.4, 0, 0.2, 1)';
            
            setTimeout(() => {
                dropdown.innerHTML = `
                    <div class="notification-error-state" style="opacity: 0; transform: translateY(8px);">
                        <div class="notification-header">
                            <h6 class="notification-title" style="color: #ef4444;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                                Error
                            </h6>
                        </div>
                        <div class="error-content">
                            <div class="error-icon-container">
                                <i class="fas fa-exclamation-triangle error-icon"></i>
                            </div>
                            <h4 class="error-title">Unable to Load Notifications</h4>
                            <p class="error-message">${getErrorMessage(errorMessage)}</p>
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
    
    // Helper function to provide user-friendly error messages
    function getErrorMessage(errorMessage) {
        if (errorMessage.includes('HTTP 500')) {
            return 'Server is temporarily unavailable. Please try again in a moment.';
        } else if (errorMessage.includes('HTTP 404')) {
            return 'Notification service not found. Please contact support if this persists.';
        } else if (errorMessage.includes('HTTP 403')) {
            return 'You don\'t have permission to view notifications. Please refresh the page.';
        } else if (errorMessage.includes('NetworkError') || errorMessage.includes('Failed to fetch')) {
            return 'Network connection issue. Please check your internet connection.';
        } else if (errorMessage.includes('timeout')) {
            return 'Request timed out. Please try again.';
        } else {
            return 'Something went wrong loading your notifications. Please try again.';
        }
    }
    
    // OFFLINE STATE MANAGEMENT
    function showOfflineState() {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.innerHTML = `
                <div class="notification-offline-state">
                    <div class="notification-header">
                        <h6 class="notification-title" style="color: #f59e0b;">
                            <i class="fas fa-wifi-slash" style="margin-right: 8px;"></i>
                            Offline
                        </h6>
                    </div>
                    <div class="offline-content">
                        <div class="offline-icon-container">
                            <i class="fas fa-wifi-slash offline-icon"></i>
                        </div>
                        <h4 class="offline-title">You're Offline</h4>
                        <p class="offline-message">Check your internet connection and try again</p>
                        <button class="offline-retry-btn" onclick="retryLoadNotifications()">
                            <i class="fas fa-sync-alt"></i>
                            Retry Connection
                        </button>
                        <div class="offline-cached-notice">
                            <i class="fas fa-info-circle"></i>
                            <span>Showing cached notifications when available</span>
                        </div>
                    </div>
                </div>
            `;
        }
        console.log('📡 Offline state displayed');
    }
    
    // ADVANCED CACHING FUNCTIONALITY
    function cacheNotifications(notifications) {
        try {
            // Validate notifications data before caching
            if (!Array.isArray(notifications)) {
                console.warn('⚠️ Invalid notifications data for caching');
                return;
            }
            
            // Sanitize and optimize notifications for caching
            const sanitizedNotifications = notifications.map(notification => ({
                id: notification.id,
                type: notification.type || 'info',
                title: notification.title || 'Notification',
                message: notification.message || '',
                created_at: notification.created_at,
                read: notification.read || false,
                priority: notification.priority || 'normal'
            }));
            
            const cacheData = {
                notifications: sanitizedNotifications,
                timestamp: Date.now(),
                version: '2.0',
                userAgent: navigator.userAgent.substring(0, 100), // For debugging
                url: window.location.href,
                count: sanitizedNotifications.length
            };
            
            // Check storage quota before caching
            const dataSize = JSON.stringify(cacheData).length;
            if (dataSize > 1024 * 1024) { // 1MB limit
                console.warn('⚠️ Cache data too large, truncating notifications');
                cacheData.notifications = sanitizedNotifications.slice(0, 10);
            }
            
            localStorage.setItem('cached_notifications', JSON.stringify(cacheData));
            console.log(`💾 Notifications cached successfully (${sanitizedNotifications.length} items, ${(dataSize/1024).toFixed(1)}KB)`);
            
        } catch (error) {
            console.warn('⚠️ Failed to cache notifications:', error);
            
            // Try to clear old cache if storage is full
            if (error.name === 'QuotaExceededError') {
                try {
                    localStorage.removeItem('cached_notifications');
                    console.log('🧹 Cleared old cache due to storage quota');
                } catch (clearError) {
                    console.error('❌ Failed to clear cache:', clearError);
                }
            }
        }
    }
    
    function getCachedNotifications() {
        try {
            const cached = localStorage.getItem('cached_notifications');
            if (!cached) {
                return null;
            }
            
            const cacheData = JSON.parse(cached);
            
            // Validate cache structure
            if (!cacheData.notifications || !cacheData.timestamp || !cacheData.version) {
                console.warn('⚠️ Invalid cache structure, clearing cache');
                localStorage.removeItem('cached_notifications');
                return null;
            }
            
            // Check cache age
            const age = Date.now() - cacheData.timestamp;
            const maxAge = 24 * 60 * 60 * 1000; // 24 hours
            
            if (age > maxAge) {
                console.log('🕒 Cache expired, clearing old data');
                localStorage.removeItem('cached_notifications');
                return null;
            }
            
            // Validate notifications array
            if (!Array.isArray(cacheData.notifications)) {
                console.warn('⚠️ Invalid cached notifications format');
                return null;
            }
            
            // Log cache hit
            performanceMetrics.cacheHits++;
            const ageMinutes = Math.round(age / 60000);
            console.log(`💾 Using cached notifications (${cacheData.notifications.length} items, age: ${ageMinutes} minutes)`);
            
            return cacheData.notifications;
            
        } catch (error) {
            console.warn('⚠️ Failed to retrieve cached notifications:', error);
            
            // Clear corrupted cache
            try {
                localStorage.removeItem('cached_notifications');
                console.log('🧹 Cleared corrupted cache');
            } catch (clearError) {
                console.error('❌ Failed to clear corrupted cache:', clearError);
            }
            
            return null;
        }
    }
    
    function getCacheInfo() {
        try {
            const cached = localStorage.getItem('cached_notifications');
            if (cached) {
                const cacheData = JSON.parse(cached);
                const age = Date.now() - cacheData.timestamp;
                const sizeKB = (cached.length / 1024).toFixed(1);
                
                return {
                    exists: true,
                    count: cacheData.notifications?.length || 0,
                    ageMinutes: Math.round(age / 60000),
                    sizeKB: sizeKB,
                    version: cacheData.version
                };
            }
        } catch (error) {
            console.warn('⚠️ Failed to get cache info:', error);
        }
        
        return { exists: false };
    }
    
    function showCachedNotificationsOrError(errorMessage) {
        const cachedNotifications = getCachedNotifications();
        
        if (cachedNotifications && cachedNotifications.length > 0) {
            console.log('💾 Showing cached notifications as fallback');
            showCachedNotificationsState(cachedNotifications, errorMessage);
        } else {
            console.log('❌ No cached notifications available - showing error state');
            showErrorState(errorMessage);
        }
    }
    
    function showCachedNotificationsState(notifications, warningMessage) {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            let notificationItems = '';
            
            notifications.slice(0, 5).forEach(notification => {
                const icon = getNotificationIcon(notification.type);
                const iconColor = getNotificationIconColor(notification.type);
                const timeAgo = getTimeAgo(notification.created_at);
                
                notificationItems += `
                    <div class="notification-item cached-notification">
                        <div class="notification-icon">
                            <i class="${icon}" style="color: ${iconColor};"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title-row">
                                <span class="notification-item-title">${notification.title}</span>
                                <span class="notification-time">${timeAgo}</span>
                            </div>
                            <p class="notification-message">${notification.message}</p>
                        </div>
                    </div>
                `;
            });
            
            if (notificationItems === '') {
                notificationItems = `
                    <div class="empty-notification">
                        <i class="fas fa-bell-slash"></i>
                        <p>All caught up</p>
                        <small>No cached notifications</small>
                    </div>
                `;
            }
            
            dropdown.innerHTML = `
                <div class="notification-header">
                    <h6 class="notification-title" style="color: #f59e0b;">
                        <i class="fas fa-archive" style="margin-right: 8px;"></i>
                        Cached Notifications
                    </h6>
                </div>
                <div class="cached-warning">
                    <i class="fas fa-info-circle"></i>
                    <span>${warningMessage}</span>
                    <button class="cached-retry-btn" onclick="retryLoadNotifications()">
                        <i class="fas fa-sync-alt"></i>
                        Retry
                    </button>
                </div>
                <div class="notification-list">
                    ${notificationItems}
                </div>
            `;
        }
    }
    
    function reloadDropdownContent() {
        // Trigger a reload of the dropdown component content
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && isDropdownOpen()) {
            // Use a small delay to ensure smooth transition
            setTimeout(() => {
                fetch('/notifications/dropdown-content', {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                })
                .then(response => response.text())
                .then(html => {
                    dropdown.innerHTML = html;
                })
                .catch(error => {
                    console.warn('⚠️ Failed to reload dropdown content:', error);
                    // Keep existing content on failure
                });
            }, 100);
        }
    }
    
    // RETRY FUNCTIONALITY
    function retryLoadNotifications() {
        console.log('🔄 Manual retry triggered - resetting retry count');
        retryCount = 0; // Reset retry count for manual retries
        loadNotifications();
    }
    
    function autoRetryLoadNotifications() {
        console.log('🔄 Auto retry triggered');
        retryWithBackoff();
    }
    
    // NETWORK STATUS MONITORING
    let retryCount = 0;
    const maxRetries = 3;
    
    function initializeNetworkMonitoring() {
        window.addEventListener('online', function() {
            console.log('🌐 Back online - reloading notifications');
            retryCount = 0; // Reset retry count when back online
            
            if (isDropdownOpen()) {
                // Add a small delay to ensure connection is stable
                setTimeout(() => {
                    loadNotifications();
                }, 500);
            }
        });
        
        window.addEventListener('offline', function() {
            console.log('📡 Gone offline');
            if (isDropdownOpen()) {
                showOfflineState();
            }
        });
        
        // Monitor connection quality
        if ('connection' in navigator) {
            navigator.connection.addEventListener('change', function() {
                const connection = navigator.connection;
                console.log('🌐 Connection changed:', {
                    effectiveType: connection.effectiveType,
                    downlink: connection.downlink,
                    rtt: connection.rtt
                });
                
                // If connection is very slow, show appropriate messaging
                if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
                    console.log('🐌 Slow connection detected');
                }
            });
        }
    }
    
    function retryWithBackoff() {
        if (retryCount >= maxRetries) {
            console.log('❌ Max retries reached - showing error state');
            showErrorState('Unable to connect after multiple attempts. Please check your connection and try again.');
            return;
        }
        
        const delay = Math.pow(2, retryCount) * 1000; // Exponential backoff: 1s, 2s, 4s
        retryCount++;
        
        console.log(`🔄 Retry attempt ${retryCount}/${maxRetries} in ${delay}ms`);
        
        setTimeout(() => {
            loadNotifications();
        }, delay);
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
    
    // PERFORMANCE MONITORING
    const performanceMetrics = {
        loadStartTime: null,
        loadEndTime: null,
        retryAttempts: 0,
        cacheHits: 0,
        networkErrors: 0
    };
    
    function startPerformanceTracking() {
        performanceMetrics.loadStartTime = performance.now();
    }
    
    function endPerformanceTracking(success = true) {
        performanceMetrics.loadEndTime = performance.now();
        const loadTime = performanceMetrics.loadEndTime - performanceMetrics.loadStartTime;
        
        console.log(`📊 Notification load performance: ${loadTime.toFixed(2)}ms (${success ? 'success' : 'failed'})`);
        
        // Log performance metrics for monitoring
        if (loadTime > 3000) {
            console.warn('⚠️ Slow notification load detected:', loadTime + 'ms');
        }
        
        return loadTime;
    }
    
    // ACCESSIBILITY ENHANCEMENTS
    function announceToScreenReader(message, priority = 'polite') {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', priority);
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
        
        document.body.appendChild(announcement);
        announcement.textContent = message;
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
    
    // ADVANCED ERROR RECOVERY
    function handleCriticalError(error, context) {
        console.error('🚨 Critical notification system error:', error, 'Context:', context);
        
        // Log error for monitoring
        performanceMetrics.networkErrors++;
        
        // Attempt graceful recovery
        try {
            // Clear any corrupted state
            localStorage.removeItem('cached_notifications');
            
            // Reset retry counter
            retryCount = 0;
            
            // Show user-friendly error
            showErrorState('System temporarily unavailable. We\'re working to fix this.');
            
            // Announce to screen readers
            announceToScreenReader('Notification system encountered an error. Please try again.', 'assertive');
            
        } catch (recoveryError) {
            console.error('🚨 Failed to recover from critical error:', recoveryError);
            
            // Last resort: reload page after delay
            setTimeout(() => {
                if (confirm('The notification system encountered a critical error. Would you like to reload the page?')) {
                    window.location.reload();
                }
            }, 2000);
        }
    }
    
    // GLOBAL ERROR HANDLERS
    window.addEventListener('unhandledrejection', function(event) {
        handleCriticalError(event.reason, 'unhandled_promise_rejection');
        event.preventDefault();
    });
    
    window.addEventListener('error', function(event) {
        if (event.filename && event.filename.includes('notification')) {
            handleCriticalError(event.error, 'javascript_error');
        }
    });
    
    // HELPER FUNCTIONS
    function getNotificationIcon(type) {
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
            'reminder': 'fas fa-bell',
            'announcement': 'fas fa-bullhorn',
            'error': 'fas fa-exclamation-circle',
            'success': 'fas fa-check',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        return icons[type] || 'fas fa-bell';
    }
    
    function getNotificationIconColor(type) {
        const colors = {
            'course_welcome': '#10b981',
            'quiz_completed': '#3b82f6',
            'achievement': '#f59e0b',
            'streak_milestone': '#f97316',
            'assignment_due': '#ef4444',
            'grade_released': '#8b5cf6',
            'system_update': '#6b7280',
            'payment_success': '#10b981',
            'payment_failed': '#ef4444',
            'course_access_granted': '#10b981',
            'course_access_denied': '#ef4444',
            'reminder': '#06b6d4',
            'announcement': '#3b82f6',
            'error': '#ef4444',
            'success': '#10b981',
            'warning': '#f59e0b',
            'info': '#06b6d4'
        };
        return colors[type] || '#6b7280';
    }
    
    function getTimeAgo(dateString) {
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + 'd ago';
            return Math.floor(diffInSeconds / 2592000) + 'mo ago';
        } catch (error) {
            return 'recently';
        }
    }
    
    // PERFORMANCE OPTIMIZATION - DEBOUNCED LOADING
    let loadingDebounceTimer = null;
    
    function debouncedLoadNotifications() {
        if (loadingDebounceTimer) {
            clearTimeout(loadingDebounceTimer);
        }
        
        loadingDebounceTimer = setTimeout(() => {
            loadNotifications();
        }, 100);
    }
    
    // HEALTH CHECK SYSTEM
    function performHealthCheck() {
        const healthMetrics = {
            cacheHealth: getCacheInfo(),
            performanceMetrics: performanceMetrics,
            networkStatus: navigator.onLine,
            lastLoadTime: performanceMetrics.loadEndTime - performanceMetrics.loadStartTime,
            memoryUsage: performance.memory ? {
                used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
                total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
                limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
            } : null
        };
        
        console.log('🏥 Notification System Health Check:', healthMetrics);
        
        // Auto-cleanup if memory usage is high
        if (healthMetrics.memoryUsage && healthMetrics.memoryUsage.used > 50) {
            console.log('🧹 High memory usage detected, performing cleanup');
            
            // Clear old cache entries
            try {
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.startsWith('cached_') && key !== 'cached_notifications') {
                        localStorage.removeItem(key);
                    }
                });
            } catch (error) {
                console.warn('⚠️ Failed to perform memory cleanup:', error);
            }
        }
        
        return healthMetrics;
    }
    
    // PERIODIC HEALTH CHECKS
    setInterval(performHealthCheck, 5 * 60 * 1000); // Every 5 minutes
    
    // MAKE FUNCTIONS GLOBALLY AVAILABLE
    window.retryLoadNotifications = retryLoadNotifications;
    window.performHealthCheck = performHealthCheck;
    window.getCacheInfo = getCacheInfo;
    
    // INITIALIZATION COMPLETE
    console.log('🚀 Golden Standard Notification System Initialized');
    console.log('📊 Features: Advanced caching, performance monitoring, accessibility, error recovery');
    console.log('🎯 Performance target: <100ms render, <3s load, 99.9% uptime');
});
</script>
@endif