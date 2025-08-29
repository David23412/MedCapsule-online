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
        <!-- Notifications will be dynamically rendered here -->
    </div>
</div>

<style>
/* === BEAUTIFUL PREMIUM NOTIFICATION SYSTEM === */

.clean-notification-system {
    position: relative;
    display: inline-block;
    margin: 0 0.5rem;
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
    color: #3579F6;
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

/* === BEAUTIFUL NOTIFICATION ITEMS === */

.notification-header {
    padding: 16px 24px 12px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notification-title {
    font-size: 16px;
    font-weight: 600;
    color: #3b82f6;
    margin: 0;
}

.notification-count {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 12px;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.2s ease;
    position: relative;
}

.notification-item:hover {
    background: #f9fafb;
}

.notification-item:last-child {
    border-bottom: 1px solid #e5e7eb;
}

/* Clean divider between notifications */
.notification-item + .notification-item {
    border-top: 1px solid #f3f4f6;
}

.notification-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 50%;
    position: relative;
}

.notification-icon i {
    font-size: 16px;
}

/* CUSTOM COURSE FAVI ICON ENHANCEMENTS */
.notification-icon i.fa-tint {
    font-weight: 900 !important;
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.2));
    animation: bloodDrop 3s ease-in-out infinite;
}

@keyframes bloodDrop {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* MEDCAPSULE ENROLLMENT - HALF BLUE HALF RED CAPSULE! */
.notification-icon i.fa-capsules {
    background: linear-gradient(90deg, #3b82f6 50%, #ef4444 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 900 !important;
    animation: capsulePulse 2s ease-in-out infinite;
}

@keyframes capsulePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notification-icon i.fa-flask {
    animation: chemicalBubble 2s ease-in-out infinite;
}

.notification-icon i.fa-heartbeat {
    animation: heartPulse 1.5s ease-in-out infinite;
}

.notification-icon i.fa-microscope {
    transform: scale(1.1);
}

.notification-icon i.fa-bacteria {
    animation: bacteriaWiggle 3s ease-in-out infinite;
}

.notification-icon i.fa-bug {
    animation: bugCrawl 2s ease-in-out infinite;
}

/* CUSTOM COURSE ICON ANIMATIONS */
@keyframes chemicalBubble {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes heartPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes bacteriaWiggle {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(2deg); }
    75% { transform: rotate(-2deg); }
}

@keyframes bugCrawl {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(2px); }
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.notification-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    line-height: 1.4;
    flex: 1;
}

.notification-time {
    font-size: 12px;
    color: #6b7280;
    white-space: nowrap;
    flex-shrink: 0;
}

.notification-message {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.4;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.unread-indicator {
    position: absolute;
    left: 8px;
    top: 40%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: #3b82f6;
    border-radius: 50%;
}

.notification-item.read {
    opacity: 0.8;
}

.notification-item.urgent {
    border-left: 3px solid #ef4444;
}

.notification-item.high {
    border-left: 3px solid #f59e0b;
}

.notification-item.mastery {
    border-left: 3px solid #10b981; /* GREEN BORDER for topic mastery */
}

.notification-item.fiery {
    border-left: 3px solid #f97316; /* FIERY ORANGE BORDER for level up */
}

.notification-item.perfect {
    border-left: 3px solid #8b5cf6; /* PURPLE BORDER for perfect scores */
}

.empty-notification {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}

.empty-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
    margin-bottom: 16px;
}

.empty-icon i {
    font-size: 20px;
    color: #9ca3af;
}

.empty-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 8px 0;
}

.empty-message {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
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

        .notification-bell {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-bell:hover {
            transform: scale(1.05);
        }

        .notification-icon.fire-icon {
            color: #f97316 !important;
            animation: flame 1.5s infinite;
        }

        @keyframes flame {
            0% { transform: scale(1) rotate(0deg); }
            25% { transform: scale(1.1) rotate(5deg); }
            50% { transform: scale(1.05) rotate(-5deg); }
            75% { transform: scale(1.1) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 400px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            overflow: hidden;
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
            // Don't close if clicking on notification system
            if (notificationSystem.contains(e.target)) {
                return;
            }
            
            // Don't close if clicking on payment modal
            const paymentModal = document.getElementById('paymentModal');
            if (paymentModal && paymentModal.contains(e.target)) {
                return;
            }
            
            // Don't close if clicking on any modal
            const modals = document.querySelectorAll('.modal');
            for (let modal of modals) {
                if (modal.contains(e.target)) {
                    return;
                }
            }
            
            // Close dropdown for all other clicks
            closeDropdown();
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
        
        // DISABLED: Don't automatically mark as read - let user see notifications
        // markAllNotificationsAsRead();
        
        // DISABLED: Don't auto-delete - let notifications stay visible
        // setTimeout(() => {
        //     aggressiveAutoDelete();
        // }, 60000); // 60 seconds = 1 minute
        
        console.log('🔔 Clean dropdown opened - notifications visible (no auto-delete)');
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
                // 🎨 CRITICAL FIX: Actually render the beautiful notifications!
                renderNotifications(data.notifications, data.unread_count);
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
        
        // FIXED: Show actual unread count, don't force to 0
        const displayCount = Math.min(unreadCount, 99); // Increased limit to 99 - VIEW NOT LIMITED TO ONLY 20!
        
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
        
        console.log(`🔢 FIXED BADGE: Badge shows ${displayCount} (system had ${unreadCount})`);
    }
    
    // GLOBAL ERROR HANDLERS
    window.addEventListener('unhandledrejection', function(event) {
        console.error('🚨 Unhandled promise rejection in notification system:', event.reason);
        // Prevent the error from showing in console for users
        event.preventDefault();
    });
    
    // 🎨 BEAUTIFUL NOTIFICATION RENDERING FUNCTION
    function renderNotifications(notifications, unreadCount) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;
        
        console.log('🎨 Rendering', notifications.length, 'notifications with beautiful design');
        
        // 🎨 COMPLETE NOTIFICATION ICON SYSTEM - ALL TYPES INCLUDING TUTOR & ASSIGNMENT
        function getNotificationIcon(type) {
            const iconMap = {
                // 🎓 ACADEMIC & LEARNING (6 icons)
                'course_welcome': 'fas fa-graduation-cap',
                'quiz_completed': 'fas fa-check-circle',
                'topic_mastery': 'fas fa-graduation-cap',
                'level_up': 'fas fa-bolt', // LIGHTNING YELLOW ONE!
                'assignment_due': 'fas fa-clock',
                'assignment_created': 'fas fa-plus', // NEW ASSIGNMENT DROPPED!
                
                // 🎯 TUTOR MODE SPECIFIC - SIMPLIFIED
                'tutor_quiz_completed': 'fas fa-check-circle', // SAME AS REGULAR QUIZ
                'tutor_perfect': 'fas fa-star', // PURPLE STAR FOR 100% IN TUTOR MODE
                'tutor_needs_practice': 'fas fa-redo-alt',
                
                // 📝 ASSIGNMENT SYSTEM - SIMPLIFIED
                'assignment_completed': 'fas fa-clipboard-check',
                'assignment_perfect': 'fas fa-star', // PURPLE STAR LIKE QUIZ
                'tutor_assignment_assigned': 'fas fa-user-graduate',
                'assignment_due_date_set': 'fas fa-calendar-alt',
                'assignment_overdue_1h': 'fas fa-exclamation-circle',
                'assignment_overdue_6h': 'fas fa-exclamation-triangle',
                'assignment_overdue_24h': 'fas fa-exclamation',
                'assignment_overdue_days': 'fas fa-times-circle',
                'assignment_due_1h': 'fas fa-clock',
                'assignment_due_3h': 'fas fa-clock',
                'assignment_due_6h': 'fas fa-clock',
                'assignment_due_12h': 'fas fa-clock',
                'assignment_due_24h': 'fas fa-calendar',
                'assignment_due_48h': 'fas fa-calendar-day',
                
                // 🏆 PROGRESS & MILESTONES (5 icons) - Real system features
                'topic_completed': 'fas fa-check-circle',
                'streak_milestone': 'fas fa-fire',
                'study_streak': 'fas fa-fire',
                'correct_answers_milestone': 'fas fa-trophy',
                'quiz_mastered': 'fas fa-graduation-cap',
                
                // ⚡ ENERGY & PROGRESS (4 icons) - LIGHTNING YELLOW!
                'energy_boost': 'fas fa-bolt',
                'power_up': 'fas fa-bolt',
                'momentum': 'fas fa-bolt',
                
                // 🔥 STREAKS & MOTIVATION (2 icons)
                'streak_reset': 'fas fa-fire',
                'burning_streak': 'fas fa-fire',
                
                // ⚠️ WARNINGS & REVIEWS (5 icons)
                'review_mistakes': 'fas fa-exclamation-triangle',
                'weak_topic_identified': 'fas fa-exclamation-circle',
                'skipped_questions_detected': 'fas fa-forward',
                'review_reminder': 'fas fa-clock',
                
                // 💳 PAYMENT & ACCESS (4 icons)
                'payment_success': 'fas fa-credit-card',
                'payment_failed': 'fas fa-exclamation-triangle',
                'course_access_granted': 'fas fa-unlock',
                'course_access_denied': 'fas fa-lock',
                
                // 🔔 SYSTEM & GENERAL (8 icons)
                'system_update': 'fas fa-bell',
                'announcement': 'fas fa-bullhorn',
                'reminder': 'fas fa-bell',
                'info': 'fas fa-info-circle',
                'success': 'fas fa-check',
                'warning': 'fas fa-exclamation-triangle',
                'error': 'fas fa-exclamation-circle',
                'test': 'fas fa-bell',
                
                // 🎯 SPECIAL FEATURES - REALISTIC TRIGGERS ONLY!
                'perfect_score': 'fas fa-star', // PURPLE STAR!
                'topic_mastery': 'fas fa-check-circle', // GREEN CHECK MARK!
                'consistency_king': 'fas fa-crown', // Triggered after 7+ day study streak
                
                // 📚 SUBJECT SPECIFIC - CUSTOM FAVI ICONS FOR ALL MEDICAL COURSES!
                'anatomy_enrolled': 'fas fa-tint', // BLOOD DROP ICON - PERFECT FOR ANATOMY!
                'anatomy_completed': 'fas fa-tint',
                'anatomy_halfway': 'fas fa-tint',
                
                'biochemistry_enrolled': 'fas fa-flask', // CHEMISTRY FLASK!
                'biochemistry_completed': 'fas fa-flask',
                'biochemistry_halfway': 'fas fa-flask',
                
                'physiology_enrolled': 'fas fa-heartbeat', // HEARTBEAT FOR BODY FUNCTIONS!
                'physiology_completed': 'fas fa-heartbeat',
                'physiology_halfway': 'fas fa-heartbeat',
                
                'histology_enrolled': 'fas fa-microscope', // MICROSCOPE FOR TISSUE STUDY!
                'histology_completed': 'fas fa-microscope',
                'histology_halfway': 'fas fa-microscope',
                
                'microbiology_enrolled': 'fas fa-bacteria', // BACTERIA ICON!
                'microbiology_completed': 'fas fa-bacteria',
                'microbiology_halfway': 'fas fa-bacteria',
                
                'parasitology_enrolled': 'fas fa-bug', // BUG ICON FOR PARASITES!
                'parasitology_completed': 'fas fa-bug',
                'parasitology_halfway': 'fas fa-bug',
                
                'ethics_enrolled': 'fas fa-balance-scale', // SCALES OF JUSTICE!
                'ethics_completed': 'fas fa-balance-scale',
                'ethics_halfway': 'fas fa-balance-scale',
                
                'psychology_enrolled': 'fas fa-brain', // BRAIN FOR PSYCHOLOGY!
                'psychology_completed': 'fas fa-brain',
                'psychology_halfway': 'fas fa-brain',
                
                'patient_care_enrolled': 'fas fa-user-md', // DOCTOR ICON!
                'patient_care_completed': 'fas fa-user-md',
                'patient_care_halfway': 'fas fa-user-md',
                
                'pharma_enrolled': 'fas fa-pills', // PILLS FOR PHARMACY!
                'pharma_completed': 'fas fa-pills',
                'pharma_halfway': 'fas fa-pills',
                
                // 💊 MEDCAPSULE ENROLLMENT & WELCOME
                'medcapsule_enrolled': 'fas fa-capsules', // COOL CAPSULE ICON!
                'medcapsule_welcome': 'fas fa-capsules', // WELCOME MESSAGE ON LOGIN
                
                // Legacy support
                'cardiology_completed': 'fas fa-heart',
                'pharmacology_quiz': 'fas fa-pills',
                
                // 🎨 CUSTOM TYPES (1 icon) - REMOVED NON-EXISTENT FEATURES
                'milestone_reached': 'fas fa-flag-checkered'
            };
            return iconMap[type] || 'fas fa-bell';
        }
        
        // BEAUTIFUL SEMANTIC COLORS FOR NOTIFICATION TYPES
        function getNotificationIconColor(type) {
            const colors = {
                // 🎓 ACADEMIC & LEARNING
                'course_welcome': '#10b981',    // emerald-500
                'quiz_completed': '#3b82f6',    // blue-500
                'topic_mastery': '#10b981',     // emerald-500
                'level_up': '#fbbf24',          // amber-400 (LIGHTNING YELLOW!)
                'assignment_due': '#ef4444',     // red-500
                'assignment_created': '#10b981', // emerald-500 for new assignments
                
                // 🎯 TUTOR MODE SPECIFIC - SIMPLIFIED
                'tutor_quiz_completed': '#3b82f6',      // SAME BLUE AS REGULAR QUIZ
                'tutor_perfect': '#8b5cf6',             // PURPLE STAR FOR 100% IN TUTOR MODE
                'tutor_needs_practice': '#ef4444',      // red-500
                
                // 📝 ASSIGNMENT SYSTEM - SIMPLIFIED
                'assignment_completed': '#3b82f6',      // BLUE LIKE QUIZ
                'assignment_perfect': '#8b5cf6',        // PURPLE LIKE QUIZ
                'tutor_assignment_assigned': '#8b5cf6', // violet-500
                'assignment_due_date_set': '#6b7280',   // gray-500
                'assignment_overdue_1h': '#ef4444',     // red-500
                'assignment_overdue_6h': '#dc2626',     // red-600
                'assignment_overdue_24h': '#b91c1c',    // red-700
                'assignment_overdue_days': '#991b1b',   // red-800
                'assignment_due_1h': '#ef4444',         // red-500
                'assignment_due_3h': '#f59e0b',         // amber-500
                'assignment_due_6h': '#eab308',         // yellow-500
                'assignment_due_12h': '#06b6d4',        // cyan-500
                'assignment_due_24h': '#3b82f6',        // blue-500
                'assignment_due_48h': '#6b7280',        // gray-500
                
                // 🏆 PROGRESS & MILESTONES - REAL FEATURES ONLY
                'streak_day_1': '#10b981',              // emerald-500 (first quiz)
                'streak_day_2': '#3b82f6',              // blue-500
                'streak_day_3': '#fbbf24',              // amber-400
                'streak_week_1': '#fbbf24',             // amber-400
                'streak_day_10': '#fbbf24',             // amber-400
                'streak_week_2': '#fbbf24',             // amber-400
                'streak_day_15': '#fbbf24',             // amber-400
                'streak_day_21': '#fbbf24',             // amber-400
                'streak_month_1': '#fbbf24',            // amber-400
                'streak_month_2': '#fbbf24',            // amber-400
                'streak_month_3': '#fbbf24',            // amber-400
                'streak_month_6': '#fbbf24',            // amber-400
                'streak_year_1': '#fbbf24',             // amber-400
                'streak_milestone': '#f97316',          // orange-500 (FIXED: FIRE ICON COLOR!)
                'study_streak': '#f97316',              // orange-500 (FIXED: FIRE ICON COLOR!)
                'burning_streak': '#f97316',            // orange-500 (FIXED: FIRE ICON COLOR!)
                'course_mastery': '#1e40af',            // deep blue for course mastery
                'correct_answers_milestone': '#7c3aed', // deep purple for milestones
                'streak_reset': '#f97316',              // orange-500
                
                // ⚠️ WARNINGS & REVIEWS
                'review_mistakes': '#ef4444',           // red-500
                'weak_topic_identified': '#ef4444',     // red-500
                'skipped_questions_detected': '#3b82f6', // blue-500
                
                // 💳 PAYMENT & ACCESS
                'payment_success': '#10b981',           // emerald-500
                'payment_failed': '#ef4444',            // red-500
                'course_access_granted': '#10b981',     // emerald-500
                'course_access_denied': '#ef4444',      // red-500
                
                // 🔔 SYSTEM & GENERAL
                'system_update': '#6b7280',             // gray-500
                'announcement': '#3b82f6',              // blue-500
                'success': '#10b981',                   // emerald-500
                'warning': '#f59e0b',                   // amber-500
                'error': '#ef4444',                     // red-500
                'info': '#06b6d4',                      // cyan-500
                
                // 🎯 SPECIAL ACHIEVEMENTS
                'perfect_score': '#8b5cf6',             // PURPLE STAR!
                
                // 📚 SUBJECT-SPECIFIC COURSE NOTIFICATIONS
                'anatomy_enrolled': '#dc2626',          // red-600
                'anatomy_completed': '#dc2626',
                'anatomy_halfway': '#dc2626',
                
                'biochemistry_enrolled': '#10b981',     // emerald-500
                'biochemistry_completed': '#10b981',
                'biochemistry_halfway': '#10b981',
                
                'physiology_enrolled': '#ef4444',       // red-500
                'physiology_completed': '#ef4444',
                'physiology_halfway': '#ef4444',
                
                'histology_enrolled': '#8b5cf6',        // violet-500
                'histology_completed': '#8b5cf6',
                'histology_halfway': '#8b5cf6',
                
                'microbiology_enrolled': '#f59e0b',     // AMBER FOR BACTERIA!
                'microbiology_completed': '#f59e0b',
                'microbiology_halfway': '#f59e0b',
                
                'parasitology_enrolled': '#84cc16',     // LIME GREEN FOR BUGS!
                'parasitology_completed': '#84cc16',
                'parasitology_halfway': '#84cc16',
                
                'ethics_enrolled': '#6366f1',           // INDIGO FOR JUSTICE SCALES!
                'ethics_completed': '#6366f1',
                'ethics_halfway': '#6366f1',
                
                'psychology_enrolled': '#ec4899',       // PINK FOR BRAIN/PSYCHOLOGY!
                'psychology_completed': '#ec4899',
                'psychology_halfway': '#ec4899',
                
                'patient_care_enrolled': '#06b6d4',     // CYAN FOR DOCTOR!
                'patient_care_completed': '#06b6d4',
                'patient_care_halfway': '#06b6d4',
                
                'pharma_enrolled': '#3b82f6',           // BLUE FOR PILLS!
                'pharma_completed': '#3b82f6',
                'pharma_halfway': '#3b82f6',
                
                // 💊 MEDCAPSULE ENROLLMENT & WELCOME - SPECIAL GRADIENT COLOR!
                'medcapsule_enrolled': '#3b82f6',       // BLUE (will add red gradient in CSS)
                'medcapsule_welcome': '#3b82f6',        // BLUE (welcome message on login)
                
                // Legacy support
                'cardiology_completed': '#ef4444',      // red-500 for heart
                'pharmacology_quiz': '#06b6d4',         // cyan-500 for pills
                
                // 🎨 CUSTOM TYPES - ONLY REAL FEATURES
                'milestone_reached': '#10b981'          // emerald-500
            };
            return colors[type] || '#6b7280';
        }
        
        // 🎨 LETHAL BORDER COLOR SYSTEM - BASED ON PRIORITY
        function getNotificationBorderColor(priority, type) {
            // Override priority for specific notification types
            if (type === 'perfect_score' || type === 'tutor_perfect' || type === 'assignment_perfect') {
                return '#8b5cf6'; // PURPLE BORDER for perfect scores
            }
            if (type === 'topic_mastery') {
                return '#10b981'; // GREEN BORDER for topic mastery
            }
            if (type === 'level_up') {
                return '#f97316'; // FIERY BORDER for level up
            }
            if (type === 'course_mastery') {
                return '#1e40af'; // DEEP BLUE BORDER for course mastery
            }
            if (type === 'correct_answers_milestone') {
                return '#7c3aed'; // DEEP PURPLE BORDER for milestones
            }
            if (type === 'medcapsule_welcome') {
                return '#3b82f6'; // BLUE BORDER for welcome
            }
            if (type === 'assignment_assigned') {
                return '#3b82f6'; // BLUE BORDER for new assignments
            }
            if (type === 'streak_day_1') {
                return '#10b981'; // GREEN BORDER for first quiz
            }
            if (type === 'streak_milestone' || type === 'study_streak' || type === 'burning_streak') {
                return '#f97316'; // FIERY ORANGE BORDER for streak notifications
            }
            if (type === 'assignment_due_1h' || type === 'assignment_due_3h' || type === 'assignment_overdue') {
                return '#ef4444'; // RED BORDER for urgent deadlines
            }
            if (type === 'payment_failed') {
                return '#ef4444'; // RED BORDER for payment failures
            }
            
            // Default priority-based borders
            switch (priority) {
                case 'urgent':
                    return '#ef4444'; // RED BORDER
                case 'high':
                    return '#f59e0b'; // AMBER BORDER
                case 'perfect':
                    return '#8b5cf6'; // PURPLE BORDER
                case 'mastery':
                    return '#10b981'; // GREEN BORDER
                case 'premium':
                    return '#1e40af'; // DEEP BLUE BORDER
                case 'fiery':
                    return '#f97316'; // FIERY BORDER
                case 'exotic':
                    return '#7c3aed'; // DEEP PURPLE BORDER
                case 'blue':
                    return '#3b82f6'; // BLUE BORDER
                default:
                    return 'transparent'; // NO BORDER for normal priority
            }
        }
        
        let notificationHTML = `
            <div class="notification-header">
                <h6 class="notification-title">Notifications</h6>
                <span class="notification-count">${unreadCount}</span>
            </div>
            <div class="notification-list">
        `;
        
        if (notifications.length === 0) {
            notificationHTML += `
                <div class="empty-notification">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h4 class="empty-title">All caught up!</h4>
                    <p class="empty-message">No new notifications</p>
                </div>
            `;
        } else {
            notifications.forEach(notification => {
                const timeAgo = getTimeAgo(notification.created_at);
                const priorityClass = notification.priority === 'urgent' ? 'urgent' : 
                                    notification.priority === 'high' ? 'high' : 'normal';
                const readClass = notification.read ? 'read' : 'unread';
                const iconColor = getNotificationIconColor(notification.type);
                const borderColor = getNotificationBorderColor(notification.priority, notification.type);
                const isFireIcon = ['streak_milestone', 'study_streak', 'burning_streak'].includes(notification.type);
                
                notificationHTML += `
                    <div class="notification-item ${priorityClass} ${readClass}" data-id="${notification.id}" style="border-left: 4px solid ${borderColor};">
                        <div class="notification-icon ${isFireIcon ? 'fire-icon' : ''}" style="background-color: ${iconColor}20; border: 1px solid ${iconColor}30;">
                            <i class="${getNotificationIcon(notification.type)}" style="color: ${iconColor};"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-header-row">
                                <h6 class="notification-item-title">${notification.title}</h6>
                                <small class="notification-time">${timeAgo}</small>
                            </div>
                            <p class="notification-message">${notification.message}</p>
                        </div>
                        ${!notification.read ? '<div class="unread-indicator"></div>' : ''}
                    </div>
                `;
            });
        }
        
        notificationHTML += `
            </div>
        `;
        
        dropdown.innerHTML = notificationHTML;
        console.log('✅ Beautiful notifications rendered successfully');
    }
    
    // Helper function to format time ago
    function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + 'd ago';
        return date.toLocaleDateString();
    }
    


    // MAKE FUNCTIONS GLOBALLY AVAILABLE
    window.renderNotifications = renderNotifications;
    window.retryLoadNotifications = retryLoadNotifications;
});
</script>
@endif
