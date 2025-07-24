@php
    use App\Services\UniversalNotificationService;
    
    // PERFORMANCE OPTIMIZED - AUTO-CLEANUP ON EVERY ACCESS
    if (auth()->check()) {
        // This triggers aggressive cleanup automatically
        $notifications = UniversalNotificationService::getNotifications(auth()->user(), 5)->take(5);
        $unreadCount = min(UniversalNotificationService::getUnreadCount(auth()->user()), 5);
    } else {
        $notifications = collect();
        $unreadCount = 0;
    }
@endphp

<!-- EXACT COPY OF OLD CLEAN LAYOUT -->
<div class="notification-header">
    <h6 class="notification-title">Notifications</h6>
</div>

<div class="notification-list" id="notificationList">
    @if($notifications->isEmpty())
        <div class="empty-notification">
            <i class="fas fa-bell-slash"></i>
            <p>All caught up</p>
            <small>No new notifications</small>
        </div>
    @else
        @foreach($notifications as $index => $notification)
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="{{ getNotificationIcon($notification['type']) }}" 
                       style="color: {{ getNotificationIconColor($notification['type']) }};"></i>
                </div>
                
                <div class="notification-content">
                    <div class="notification-title-row">
                        <span class="notification-item-title">{{ $notification['title'] }}</span>
                        <span class="notification-time">{{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}</span>
                    </div>
                    <p class="notification-message">{{ $notification['message'] }}</p>
                </div>
            </div>
        @endforeach
    @endif
</div>

<style>
/* === ULTRA-MODERN NOTIFICATION LAYOUT === */

/* Modern Header */
.notification-header {
    padding: 20px 28px 16px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    border-bottom: 1px solid rgba(102, 126, 234, 0.1);
    text-align: center;
    border-radius: 20px 20px 0 0;
}

.notification-title {
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
    letter-spacing: -0.025em;
}

/* Modern List */
.notification-list {
    max-height: 360px;
    overflow-y: auto;
    padding: 12px 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
}

.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: transparent;
}

.notification-list::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.3);
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: rgba(102, 126, 234, 0.5);
}

/* Empty State - JUST WIDER NOT BIGGER */
.empty-notification {
    padding: 40px 24px;
    text-align: center;
    color: #9ca3af;
}

.empty-notification i {
    font-size: 32px;
    margin-bottom: 12px;
    opacity: 0.6;
}

.empty-notification p {
    font-size: 14px;
    font-weight: 500;
    margin: 0 0 4px 0;
    color: #6b7280;
}

.empty-notification small {
    font-size: 12px;
    color: #9ca3af;
}

/* Notification Items - JUST WIDER NOT BIGGER */
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    background: white;
    border-bottom: 1px solid #f3f4f6;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.notification-item:hover {
    background: #f9fafb;
}

.notification-item:last-child {
    border-bottom: none;
}

/* Clean styling - All notifications look the same since they're auto-read */

/* Icon - Clean circular background like the old design */
.notification-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #f5f5f5;
    border-radius: 50%;
}

.notification-icon i {
    font-size: 16px;
}

/* Content */
.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 4px;
}

.notification-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    flex: 1;
    line-height: 1.4;
}

.notification-time {
    font-size: 12px;
    color: #888;
    white-space: nowrap;
    flex-shrink: 0;
}

.notification-message {
    font-size: 13px;
    color: #666;
    line-height: 1.4;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Responsive */
@media (max-width: 767px) {
    .notification-header {
        padding: 14px 16px 10px;
    }
    
    .notification-item {
        padding: 10px 16px;
        gap: 10px;
    }
    
    .notification-icon {
        width: 32px;
        height: 32px;
    }
    
    .notification-icon i {
        font-size: 14px;
    }
    
    .empty-notification {
        padding: 32px 16px;
    }
}
</style>

@php
function getNotificationIcon($type) {
    $icons = [
        'course_welcome' => 'fas fa-graduation-cap',
        'quiz_completed' => 'fas fa-check-circle',
        'achievement' => 'fas fa-trophy',
        'streak_milestone' => 'fas fa-fire',
        'assignment_due' => 'fas fa-clock',
        'grade_released' => 'fas fa-star',
        'system_update' => 'fas fa-bell',
        'payment_success' => 'fas fa-credit-card',
        'payment_failed' => 'fas fa-exclamation-triangle',
        'course_access_granted' => 'fas fa-unlock',
        'course_access_denied' => 'fas fa-lock',
        'reminder' => 'fas fa-bell',
        'announcement' => 'fas fa-bullhorn',
        'error' => 'fas fa-exclamation-circle',
        'success' => 'fas fa-check',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];
    
    return $icons[$type] ?? 'fas fa-bell';
}

function getNotificationIconColor($type) {
    $colors = [
        'course_welcome' => '#10b981',    // emerald-500
        'quiz_completed' => '#3b82f6',    // blue-500
        'achievement' => '#f59e0b',       // amber-500
        'streak_milestone' => '#f97316',  // orange-500
        'assignment_due' => '#ef4444',    // red-500
        'grade_released' => '#8b5cf6',    // violet-500
        'system_update' => '#6b7280',     // gray-500
        'payment_success' => '#10b981',   // emerald-500
        'payment_failed' => '#ef4444',    // red-500
        'course_access_granted' => '#10b981', // emerald-500
        'course_access_denied' => '#ef4444',  // red-500
        'reminder' => '#06b6d4',          // cyan-500
        'announcement' => '#3b82f6',      // blue-500
        'error' => '#ef4444',             // red-500
        'success' => '#10b981',           // emerald-500
        'warning' => '#f59e0b',           // amber-500
        'info' => '#06b6d4'               // cyan-500
    ];
    
    return $colors[$type] ?? '#6b7280';
}
@endphp
<script>
// CLEAN AUTO-READ LAYOUT - NO CLICKING NEEDED
document.addEventListener('DOMContentLoaded', function() {
    const notificationList = document.getElementById('notificationList');
    if (notificationList) {
        notificationList.style.webkitOverflowScrolling = 'touch';
    }
    
    console.log('✨ CLEAN AUTO-READ: All notifications automatically read when viewed - NO CLICKING REQUIRED');
});
</script>