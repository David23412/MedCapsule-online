@php
    use App\Services\UniversalNotificationService;
    
    // DISABLED: PERFORMANCE OPTIMIZED - AUTO-CLEANUP ON EVERY ACCESS
    if (auth()->check()) {
        // DISABLED: This triggers aggressive cleanup automatically
        // $notifications = UniversalNotificationService::getNotifications(auth()->user(), 5)->take(5);
        // $unreadCount = min(UniversalNotificationService::getUnreadCount(auth()->user()), 5);
        
        // ENABLED: Get notifications without auto-cleanup
        $notifications = UniversalNotificationService::getNotifications(auth()->user(), 20);
        $unreadCount = UniversalNotificationService::getUnreadCount(auth()->user());
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
/* === EXACT COPY OF OLD CLEAN LAYOUT === */

/* Header - JUST WIDER NOT BIGGER */
.notification-header {
    padding: 16px 24px 12px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    text-align: center;
}

.notification-title {
    font-size: 16px;
    font-weight: 600;
    color: #3b82f6;
    margin: 0;
}

/* List - JUST WIDER NOT BIGGER */
.notification-list {
    max-height: 320px;
    overflow-y: auto;
    padding: 8px 0;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.notification-list::-webkit-scrollbar {
    display: none;
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
        // 🎓 ACADEMIC & LEARNING
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
        'info' => 'fas fa-info-circle',
        'level_up' => 'fas fa-level-up-alt',
        'topic_mastery' => 'fas fa-graduation-cap',
        'study_streak' => 'fas fa-fire',
        'correct_answers_milestone' => 'fas fa-trophy',
        'review_mistakes' => 'fas fa-exclamation-triangle',
        'weak_topic_identified' => 'fas fa-exclamation-circle',
        'skipped_questions_detected' => 'fas fa-forward',
        'test' => 'fas fa-bell',
        
        // 🎯 TUTOR MODE SPECIFIC - SIMPLIFIED
        'tutor_quiz_completed' => 'fas fa-check-circle', // SAME AS REGULAR QUIZ
        'tutor_needs_practice' => 'fas fa-redo-alt',
        
        // 📝 ASSIGNMENT SYSTEM - SIMPLIFIED
        'assignment_completed' => 'fas fa-clipboard-check',
        'assignment_perfect' => 'fas fa-star', // PURPLE STAR LIKE QUIZ
        'tutor_assignment_assigned' => 'fas fa-user-graduate',
        'assignment_due_date_set' => 'fas fa-calendar-alt',
        'assignment_overdue_1h' => 'fas fa-exclamation-circle',
        'assignment_overdue_6h' => 'fas fa-exclamation-triangle',
        'assignment_overdue_24h' => 'fas fa-exclamation',
        'assignment_overdue_days' => 'fas fa-times-circle',
        'assignment_due_1h' => 'fas fa-clock',
        'assignment_due_3h' => 'fas fa-clock',
        'assignment_due_6h' => 'fas fa-clock',
        'assignment_due_12h' => 'fas fa-clock',
        'assignment_due_24h' => 'fas fa-calendar',
        'assignment_due_48h' => 'fas fa-calendar-day',
        
        // 🎯 SPECIAL FEATURES - REALISTIC TRIGGERS ONLY!
        'perfect_score' => 'fas fa-star', // PURPLE STAR!
        'topic_mastery' => 'fas fa-check-circle', // GREEN CHECK MARK!
        'consistency_king' => 'fas fa-crown', // Triggered after 7+ day study streak
        
        // 💊 MEDCAPSULE WELCOME & ENROLLMENT - HALF BLUE HALF RED CAPSULE!
        'medcapsule_welcome' => 'fas fa-capsules', // WELCOME MESSAGE ON LOGIN
        'medcapsule_enrolled' => 'fas fa-capsules' // COOL CAPSULE ICON!
    ];
    
    return $icons[$type] ?? 'fas fa-bell';
}

function getNotificationIconColor($type) {
    $colors = [
        // 🎓 ACADEMIC & LEARNING
        'course_welcome' => '#10b981',    // emerald-500
        'quiz_completed' => '#3b82f6',    // blue-500
        'topic_mastery' => '#10b981',     // emerald-500
        'level_up' => '#fbbf24',          // amber-400 (LIGHTNING YELLOW!)
        'assignment_due' => '#ef4444',     // red-500
        'grade_released' => '#8b5cf6',    // violet-500
        
        // 🏆 ACHIEVEMENTS & MILESTONES
        'achievement' => '#f59e0b',       // amber-500
        'streak_milestone' => '#f97316',  // orange-500
        'study_streak' => '#f97316',      // orange-500
        'correct_answers_milestone' => '#f59e0b', // amber-500
        'quiz_mastered' => '#10b981',     // emerald-500
        
        // ⚡ ENERGY & PROGRESS (LIGHTNING YELLOW ONE!)
        'energy_boost' => '#fbbf24',      // amber-400
        'power_up' => '#fbbf24',          // amber-400
        'momentum' => '#fbbf24',          // amber-400
        
        // 🔥 STREAKS & MOTIVATION
        'streak_reset' => '#f97316',      // orange-500
        'burning_streak' => '#f97316',    // orange-500
        
        // ⚠️ WARNINGS & REVIEWS
        'review_mistakes' => '#ef4444',   // red-500
        'weak_topic_identified' => '#ef4444', // red-500
        'skipped_questions_detected' => '#f59e0b', // amber-500
        'review_reminder' => '#06b6d4',   // cyan-500
        
        // 💳 PAYMENT & ACCESS
        'payment_success' => '#10b981',   // emerald-500
        'payment_failed' => '#ef4444',    // red-500
        'course_access_granted' => '#10b981', // emerald-500
        'course_access_denied' => '#ef4444',  // red-500
        
        // 🔔 SYSTEM & GENERAL
        'system_update' => '#6b7280',     // gray-500
        'announcement' => '#3b82f6',      // blue-500
        'reminder' => '#06b6d4',          // cyan-500
        'info' => '#06b6d4',              // cyan-500
        'success' => '#10b981',           // emerald-500
        'warning' => '#f59e0b',           // amber-500
        'error' => '#ef4444',             // red-500
        'test' => '#3b82f6',              // blue-500
        
        // 🎯 TUTOR MODE SPECIFIC - SIMPLIFIED
        'tutor_quiz_completed' => '#3b82f6',      // SAME BLUE AS REGULAR QUIZ
        'tutor_needs_practice' => '#ef4444',      // red-500
        
        // 📝 ASSIGNMENT SYSTEM - SIMPLIFIED
        'assignment_completed' => '#3b82f6',      // BLUE LIKE QUIZ
        'assignment_perfect' => '#8b5cf6',        // PURPLE LIKE QUIZ
        'tutor_assignment_assigned' => '#8b5cf6', // violet-500
        'assignment_due_date_set' => '#6b7280',   // gray-500
        'assignment_overdue_1h' => '#ef4444',     // red-500
        'assignment_overdue_6h' => '#dc2626',     // red-600
        'assignment_overdue_24h' => '#b91c1c',    // red-700
        'assignment_overdue_days' => '#991b1b',   // red-800
        'assignment_due_1h' => '#ef4444',         // red-500
        'assignment_due_3h' => '#f59e0b',         // amber-500
        'assignment_due_6h' => '#eab308',         // yellow-500
        'assignment_due_12h' => '#06b6d4',        // cyan-500
        'assignment_due_24h' => '#3b82f6',        // blue-500
        'assignment_due_48h' => '#6b7280',        // gray-500
        
        // 🎯 SPECIAL FEATURES - REALISTIC TRIGGERS!
        'perfect_score' => '#8b5cf6',     // PURPLE STAR!
        'topic_mastery' => '#10b981',     // GREEN CHECK MARK!
        'consistency_king' => '#f59e0b',  // amber-500 (7+ day streak)
        
        // 💊 MEDCAPSULE WELCOME & ENROLLMENT - SPECIAL GRADIENT COLOR!
        'medcapsule_welcome' => '#3b82f6',   // BLUE (welcome message on login)
        'medcapsule_enrolled' => '#3b82f6',  // BLUE (will add red gradient in CSS)
        
        // 📚 SUBJECT SPECIFIC
        'anatomy_mastered' => '#ef4444',  // red-500
        'cardiology_completed' => '#ef4444', // red-500
        'pharmacology_quiz' => '#06b6d4', // cyan-500
        'physiology_quiz' => '#8b5cf6',   // violet-500
        
        // 🎨 CUSTOM TYPES
        'milestone_reached' => '#10b981', // emerald-500
        'goal_achieved' => '#f59e0b',     // amber-500
        'breakthrough' => '#fbbf24',      // amber-400
        'inspiration' => '#fbbf24'        // amber-400
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