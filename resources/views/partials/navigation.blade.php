<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">MedCapsule</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <x-live-user-counter />
                </li>
                
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Sign Up</a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                            @if(Auth::user()->is_admin)
                                <span class="badge bg-primary ms-1">Admin</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="d-flex justify-content-center">
                                <div class="streak-indicator" data-streak="{{ Auth::user()->study_streak_days ?? 0 }}">
                                    <div class="streak-circle">
                                        <svg viewBox="0 0 100 100">
                                            <circle class="streak-bg" cx="50" cy="50" r="40"></circle>
                                            <circle class="streak-progress" cx="50" cy="50" r="40"></circle>
                                        </svg>
                                        <div class="streak-content">
                                            <div class="streak-value" data-value="{{ Auth::user()->study_streak_days ?? 0 }}">
                                                {{ Auth::user()->study_streak_days ?? 0 }}
                                            </div>
                                            <div class="streak-label">Streak</div>
                                        </div>
                                        @if((Auth::user()->study_streak_days ?? 0) > 0)
                                            <div class="streak-fire">
                                                <i class="fas fa-fire"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('courses.index') }}">
                                    <i class="fas fa-book me-2"></i>My Courses
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('review.index') }}">
                                    <i class="fas fa-exclamation-circle me-2"></i>My Mistakes
                                </a>
                            </li>
                            @if(Auth::user()->is_admin)
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.user-course') }}">
                                        <i class="fas fa-users-cog me-2"></i>Manage Access
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('add-topic.form') }}">
                                        <i class="fas fa-plus-circle me-2"></i>Add Topic
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('add-question.form') }}">
                                        <i class="fas fa-question-circle me-2"></i>Add Question
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('add-course.form') }}">
                                        <i class="fas fa-graduation-cap me-2"></i>Add Course
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile') }}">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="dropdown-item p-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item w-100">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Notification Bell Component -->
                    <x-notification-bell />
                    
                    <li class="nav-item pe-2">
                        <a href="{{ route('profile') }}" class="nav-link">
                            <div class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                                 style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                                @if(!Auth::user()->profile_picture_url)
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                @endif
                            </div>
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Navbar */
    .navbar {
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1rem 0;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.95);
    }

    .navbar-brand {
        font-weight: 600;
        color: #007bff;
        font-size: 2rem;
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover {
        color: #0056b3;
        transform: translateY(-1px);
    }

    .nav-link {
        color: #333;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: #007bff;
        background-color: #f8f9fa;
        transform: translateY(-1px);
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border-radius: 12px;
        padding: 0.5rem;
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        color: #333;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background-color: #f0f7ff;
        color: #2196f3;
        transform: translateX(5px);
    }

    .dropdown-divider {
        margin: 0.5rem 0;
        border-color: #eee;
    }

    .badge {
        font-size: 0.7em;
        padding: 0.35em 0.65em;
        font-weight: 500;
    }

    .badge-pill {
        border-radius: 50px;
    }

    .dropdown-item.active, 
    .dropdown-item:active {
        background-color: #2196f3;
        color: white;
    }

    /* Style for the logout button to look like a link */
    .dropdown-item[type="submit"] {
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
    }

    .dropdown-item[type="submit"]:hover {
        background: #ffebee;
        color: #f44336;
    }

    /* Profile Circle */
    .profile-circle {
        width: 36px;
        height: 36px;
        background: #2196f3;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        transition: transform 0.2s ease;
        background-size: cover;
        background-position: center;
    }

    .profile-circle.no-image {
        background: #2196f3;
    }

    .profile-circle:hover {
        transform: scale(1.1);
    }

    /* Streak Indicator */
    .streak-indicator {
        background: #f9f9f9;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        margin: 10px auto;
        padding: 5px;
        transition: all 0.3s ease;
        width: 60px; /* Set fixed width for circle */
        height: 60px; /* Equal height for perfect circle */
        position: relative;
        margin: 1rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        backdrop-filter: blur(12px);
        box-shadow: 0 0 25px rgba(25, 118, 210, 0.25);
    }

    .streak-circle {
        width: 100%;
        height: 100%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .streak-circle svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
        filter: drop-shadow(0 0 8px rgba(51, 154, 240, 0.4));
    }

    .streak-circle circle {
        fill: none;
        stroke-width: 3; /* Slightly thicker stroke for better visibility */
        stroke-linecap: round;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .streak-bg {
        stroke: rgba(33, 150, 243, 0.15);
    }

    .streak-progress {
        stroke: #2196f3;
        stroke-dasharray: 251.2;
        stroke-dashoffset: 251.2;
        transition: stroke-dashoffset 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        filter: drop-shadow(0 0 8px rgba(25, 118, 210, 0.8));
    }

    .streak-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        width: 100%;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2;
    }

    .streak-value {
        font-size: 1.5rem; /* Smaller font size to fit circle better */
        font-weight: 700;
        background: linear-gradient(135deg, #1565c0, #2196f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        position: relative;
        line-height: 1;
        margin-bottom: 0.2rem;
        text-shadow: 0 0 25px rgba(33, 150, 243, 0.6);
        animation: glowPulse 2s infinite;
    }

    .streak-label {
        font-size: 0.55rem; /* Smaller font size for better fit */
        background: linear-gradient(135deg, #1565c0, #2196f3);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.95;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        animation: glowPulse 2s infinite 0.5s;
    }

    @keyframes glowPulse {
        0% { 
            filter: brightness(1) drop-shadow(0 0 10px rgba(25, 118, 210, 0.5));
            transform: scale(1);
        }
        50% { 
            filter: brightness(1.4) drop-shadow(0 0 15px rgba(25, 118, 210, 0.7));
            transform: scale(1.05);
        }
        100% { 
            filter: brightness(1) drop-shadow(0 0 10px rgba(25, 118, 210, 0.5));
            transform: scale(1);
        }
    }

    .streak-fire {
        position: absolute;
        bottom: -2px;
        right: -2px;
        color: #ff6b6b;
        animation: flame 1.5s infinite;
        font-size: 1rem; /* Adjusted size */
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    @keyframes flame {
        0% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.2) rotate(5deg); }
        100% { transform: scale(1) rotate(0deg); }
    }

    /* Enhanced notification bell styles */
    .notification-bell button {
        background: transparent;
        border: none;
        outline: none;
        position: relative;
        transition: all 0.3s ease;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .notification-bell i {
        color: #2196f3;
        font-size: 1.25rem;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .notification-bell button:hover i {
        transform: scale(1.15);
    }

    .notification-bell button:hover {
        background: rgba(33, 150, 243, 0.08);
    }

    .notification-bell button:active i {
        transform: scale(0.9);
        transition: transform 0.15s ease;
    }

    .notification-bell .badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 0.7rem;
        padding: 0.15em 0.35em;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f44336;
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
        animation: notificationPulse 2s infinite;
        z-index: 10;
    }

    @keyframes notificationPulse {
        0% {
            transform: scale(1);
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
        }
        50% {
            transform: scale(1.1); 
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.7);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 2px 5px rgba(244, 67, 54, 0.5);
        }
    }

    @keyframes bellRing {
        0% { transform: rotate(0); }
        10% { transform: rotate(20deg); }
        20% { transform: rotate(-20deg); }
        30% { transform: rotate(15deg); }
        40% { transform: rotate(-15deg); }
        50% { transform: rotate(10deg); }
        60% { transform: rotate(-10deg); }
        70% { transform: rotate(5deg); }
        80% { transform: rotate(-5deg); }
        90% { transform: rotate(2deg); }
        100% { transform: rotate(0); }
    }
    
    .notification-bell p {
        margin-bottom: 0;
    }

    /* Bell ring animation on unread notifications */
    .notification-bell.has-unread button i {
        animation: bellRing 2.5s infinite;
        animation-delay: 1s;
    }
    
    /* Fix text-purple class for notification icon */
    .text-purple {
        color: #9C27B0;
    }

    /* Enhanced notification dropdown styles */
    .notification-bell .position-absolute,
    .notification-dropdown {
        width: 320px;
        border-radius: 8px;
        box-shadow: 0 3px 12px rgba(33, 150, 243, 0.1), 0 0 0 1px rgba(0, 123, 255, 0.25);
        border: none;
        z-index: 1050;
        overflow: hidden;
        max-height: 380px;
        display: flex;
        flex-direction: column;
        background-color: white;
        margin-top: 8px;
        left: 50%;
        transform: translateX(-50%);
    }
    
    .notification-dropdown {
        position: absolute !important;
        top: 100% !important;
        right: auto !important;
    }

    /* Fix x-cloak to prevent content flash before Alpine.js loads */
    [x-cloak] { 
        display: none !important; 
    }
</style>

<script>
    function notificationBell() {
        return {
            notifications: [],
            isOpen: false,
            unreadCount: 0,
            loading: false,
            lastFetched: 0,
            
            init() {
                this.fetchNotifications();
                
                // Set up a polling mechanism to check for new notifications periodically
                // Use a more efficient approach by checking less frequently when the tab is not active
                let pollInterval = 60000; // Default: 1 minute
                
                const checkForNewNotifications = () => {
                    // Only fetch if the tab is visible and it's been at least 60 seconds since the last fetch
                    if (document.visibilityState === 'visible' && Date.now() - this.lastFetched >= 60000) {
                        this.fetchNotifications();
                    }
                    setTimeout(checkForNewNotifications, pollInterval);
                };
                
                // Adjust poll interval based on tab visibility
                document.addEventListener('visibilitychange', () => {
                    pollInterval = document.visibilityState === 'visible' ? 60000 : 180000; // 1 minute when visible, 3 minutes when hidden
                });
                
                setTimeout(checkForNewNotifications, pollInterval);

                // Add bell ring animation when new notifications arrive
                this.$watch('unreadCount', (newValue, oldValue) => {
                    if (newValue > oldValue && oldValue !== 0) {
                        const bellIcon = this.$el.querySelector('i.fa-bell');
                        bellIcon.style.animation = 'none';
                        // Trigger reflow
                        void bellIcon.offsetWidth;
                        bellIcon.style.animation = 'bellRing 1s';
                    }
                });
            },
            
            // Methods to filter notifications by type for better organization
            getNotificationsByType(type) {
                return this.notifications.filter(notification => notification.type === type);
            },
            
            // Get notifications that don't fit into predefined categories
            getOtherNotifications() {
                const predefinedTypes = ['level_up', 'quiz_completed', 'study_streak', 'review_reminder', 'course_progress'];
                return this.notifications.filter(notification => !predefinedTypes.includes(notification.type));
            },
            
            fetchNotifications() {
                if (this.loading) return;
                
                this.loading = true;
                this.lastFetched = Date.now();
                
                fetch('/notifications?limit=20', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin' // Include cookies for authentication
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Check if there are new notifications by comparing counts
                        const oldUnreadCount = this.unreadCount;
                        
                        // Store all notifications but we'll only display up to 5 in the UI
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;
                        
                        // If there are new notifications, trigger animation
                        if (this.unreadCount > oldUnreadCount && oldUnreadCount > 0) {
                            // Play a subtle notification sound if enabled by the user
                            // This could be implemented with user preferences later
                        }
                    })
                    .catch(error => console.error('Error fetching notifications:', error))
                    .finally(() => {
                        this.loading = false;
                    });
            },
            
            toggleNotifications() {
                this.isOpen = !this.isOpen;
                
                // If opening notifications panel
                if (this.isOpen) {
                    // Make sure we have the latest notifications when opening
                    if (Date.now() - this.lastFetched >= 30000) { // 30 seconds threshold
                        this.fetchNotifications();
                    }
                    
                    // Play bell sound effect when opening notifications
                    if (this.unreadCount > 0) {
                        const bellIcon = this.$el.querySelector('i.fa-bell');
                        bellIcon.style.animation = 'bellRing 1s';
                    }
                }
            },
            
            markAllAsRead() {
                if (this.unreadCount === 0) return;
                
                fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin' // Include cookies for authentication
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.notifications.forEach(notification => {
                            notification.is_read = true;
                        });
                        this.unreadCount = 0;
                    }
                })
                .catch(error => console.error('Error marking notifications as read:', error));
            },
            
            formatDate(dateString) {
                if (!dateString) return '';
                
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - date);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
                const diffMinutes = Math.floor(diffTime / (1000 * 60));
                
                if (diffMinutes < 1) {
                    return 'Just now';
                } else if (diffMinutes < 60) {
                    return `${diffMinutes}m ago`;
                } else if (diffHours < 24) {
                    return `${diffHours}h ago`;
                } else if (diffDays < 7) {
                    return `${diffDays}d ago`;
                } else {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }
            }
        };
    }
    
    // Streak animation initialization
    document.addEventListener('DOMContentLoaded', function() {
        const streakIndicator = document.querySelector('.streak-indicator');
        if (streakIndicator) {
            const streakProgress = document.querySelector('.streak-progress');
            const streak = parseInt(streakIndicator.dataset.streak);
            
            // Calculate progress (assuming max streak of 30 days)
            const maxStreak = 30;
            const progress = Math.min(streak / maxStreak, 1);
            const circumference = 2 * Math.PI * 40; // circle radius is 40
            
            // Animate the progress
            setTimeout(() => {
                streakProgress.style.strokeDasharray = circumference;
                streakProgress.style.strokeDashoffset = circumference * (1 - progress);
            }, 100);
            
            // Add hover effect for the flame
            if (streak > 0) {
                const streakFire = document.querySelector('.streak-fire');
                if (streakFire) {
                    streakIndicator.addEventListener('mouseover', () => {
                        streakFire.style.transform = 'scale(1.2)';
                    });
                    streakIndicator.addEventListener('mouseout', () => {
                        streakFire.style.transform = 'scale(1)';
                    });
                }
            }
        }
    });
</script> 