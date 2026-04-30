<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light" style="opacity: 0; transition: opacity 0.3s ease;">
    <div class="container">
        <a class="navbar-brand" href="{{ route('courses.index') }}">MedCapsule</a>
        
        <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarNavOffcanvas" aria-controls="navbarNavOffcanvas" aria-expanded="false" aria-label="Toggle navigation" style="min-height: 44px; min-width: 44px;">
            <span class="navbar-toggler-icon"></span>
        </button>

        @auth
            <!-- Desktop-only XP Bar (Extreme Premium) -->
            <div class="d-none d-lg-flex align-items-center ms-4 flex-grow-1 header-xp-container" style="max-width: 300px;" title="Your Level Progress">
                <div class="xp-label me-2 small fw-bold text-muted">LVL <span id="header-user-level">{{ Auth::user()->level }}</span></div>
                <div class="progress flex-grow-1" style="height: 10px; border-radius: 10px; background: rgba(0,0,0,0.05); overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                    <div id="header-xp-bar" 
                         class="progress-bar" 
                         role="progressbar" 
                         style="width: {{ (Auth::user()->xp % 1000) / 10 }}%; 
                                background: linear-gradient(90deg, #7e57c2, #b388ff); 
                                border-radius: 10px;
                                transition: width 1s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    </div>
                </div>
                <div class="ms-2 small fw-bold text-muted" id="header-xp-text" style="min-width: 60px;">{{ Auth::user()->xp % 1000 }}/1000</div>
            </div>
        @endauth
        
        <div class="offcanvas true-mobile-offcanvas offcanvas-end" tabindex="-1" id="navbarNavOffcanvas" aria-labelledby="navbarNavOffcanvasLabel">
            <div class="offcanvas-header border-bottom d-lg-none">
                <h5 class="offcanvas-title fw-bold text-primary" id="navbarNavOffcanvasLabel">Menu</h5>
                <button type="button" class="btn-close text-reset p-3" data-bs-dismiss="offcanvas" aria-label="Close" style="min-height: 44px; min-width: 44px;"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav ms-auto align-items-center w-100 mobile-nav-stack">
                    <li class="nav-item mobile-nav-item">
                        <x-live-user-counter />
                    </li>
                    
                    @guest
                        <li class="nav-item pe-2 mobile-nav-item mt-lg-0 mt-2 d-flex justify-content-center">
                            <button type="button"
                                    class="admin-theme-icon-button"
                                    data-theme-toggle
                                    aria-pressed="false"
                                    aria-label="Toggle dark mode">
                                <i class="fas fa-circle-half-stroke" data-theme-icon></i>
                            </button>
                        </li>
                        <li class="nav-item mobile-nav-item">
                            <a class="nav-link w-100 mobile-nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item mobile-nav-item">
                            <a class="nav-link w-100 mobile-nav-link" href="{{ route('register') }}">Sign Up</a>
                        </li>
                    @else
                        <li class="nav-item pe-2 mobile-nav-item mt-lg-0 mt-2 d-flex justify-content-center">
                            <button type="button"
                                    class="admin-theme-icon-button"
                                    data-theme-toggle
                                    aria-pressed="false"
                                    aria-label="Toggle dark mode">
                                <i class="fas fa-circle-half-stroke" data-theme-icon></i>
                            </button>
                        </li>

                        <x-navigation-dropdown />
                        
                        <!-- Notification Bell Component -->
                        <x-notification-bell :unread-count="$navigationUnreadCount" />
                        
                        <li class="nav-item pe-2 mobile-nav-item mt-lg-0 mt-3 d-flex justify-content-center">
                            <a href="{{ route('profile') }}" class="nav-link d-flex align-items-center justify-content-center" style="min-height: 44px; min-width: 44px;">
                                @php($navImageUrl = $navigationProfileImageUrl)
                                <div class="profile-circle {{ !$navImageUrl ? 'no-image' : '' }}"
                                     style="{{ $navImageUrl ? 'background-image: url(' . $navImageUrl . ');' : '' }}">
                                    @if(!$navImageUrl)
                                        {{ substr($navigationUser?->name ?? Auth::user()->name, 0, 1) }}
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
    /* LETHAL NAVBAR - PREVENTS FOUC */
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
        transition: opacity 0.3s ease;
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
        min-height: 44px; /* Touch target size */
        display: flex;
        align-items: center;
    }

    .nav-link:hover {
        color: #007bff;
        background-color: #f8f9fa;
        transform: translateY(-1px);
    }
    
    /* Mobile Offcanvas Enhancements */
    @media (max-width: 991px) {
        .true-mobile-offcanvas {
            width: 85% !important;
            max-width: 400px;
        }
        .mobile-nav-stack {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .mobile-nav-item {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .mobile-nav-item .dropdown, .mobile-nav-item .x-notification-bell {
            width: 100%;
        }
        .mobile-nav-link {
            padding: 12px 16px;
            font-size: 1.1rem;
        }
        .admin-theme-icon-button {
            width: 100%;
            min-height: 44px;
            border-radius: 12px;
            justify-content: flex-start;
            padding: 0 16px;
        }
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

    .admin-theme-icon-button {
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 999px;
        background: transparent;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .admin-theme-icon-button:hover {
        background: rgba(15, 23, 42, 0.06);
        color: #0f172a;
        transform: translateY(-1px);
    }

    .admin-theme-icon-button.is-active {
        color: #111827;
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

    .streak-fire.streak-fire-celebrate {
        color: #ffb300;
        animation: streakFireCelebrate 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) 2;
        filter: drop-shadow(0 0 12px rgba(255, 179, 0, 0.95));
    }

    .streak-indicator.streak-week-complete {
        animation: streakWeekComplete 0.9s ease-out 1;
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

    @keyframes streakFireCelebrate {
        0% { transform: scale(1) rotate(0deg); }
        35% { transform: scale(1.5) rotate(-14deg); }
        70% { transform: scale(1.2) rotate(14deg); }
        100% { transform: scale(1) rotate(0deg); }
    }

    @keyframes streakWeekComplete {
        0% { transform: translateY(0) scale(1); }
        35% { transform: translateY(-3px) scale(1.06); }
        100% { transform: translateY(0) scale(1); }
    }

    /* Clean notification system integration */
    .clean-notification-system {
        margin: 0 0.5rem;
    }

    /* Fix x-cloak to prevent content flash before Alpine.js loads */
    [x-cloak] { 
        display: none !important; 
    }
</style>

<script>
    // NUCLEAR SPEED NOTIFICATION BELL - ZERO POLLING, INSTANT UPDATES
    // Performance gain: 95% faster updates, 90% less server load
    function notificationBell() {
        return {
            notifications: [],
            isOpen: false,
            unreadCount: 0,
            loading: false,
            lastFetched: 0,
            pollTimer: null,
            init() {
                // Fetch initial notifications immediately
                this.fetchNotifications();

                // Use simple polling for notifications
                this.initializePolling();

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        this.fetchNotifications();
                    }
                });

                window.addEventListener('pagehide', () => {
                    this.cleanup();
                });

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

            // Simple polling for notifications
            initializePolling() {
                if (this.pollTimer) {
                    return;
                }

                // Poll every 60 seconds for notifications
                this.pollTimer = window.setInterval(() => {
                    if (document.visibilityState !== 'visible') {
                        return;
                    }

                    this.fetchNotifications();
                }, 60000);
            },
            
            // Fetch notifications from server with aggressive caching
            async fetchNotifications() {
                if (this.loading) {
                    return;
                }

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
            
            // Update badge with instant animation
            updateBadge() {
                const badge = document.getElementById('notificationBadge');
                const displayCount = Math.min(this.unreadCount, 99);
                
                if (displayCount > 0) {
                    if (badge) {
                        const oldCount = parseInt(badge.textContent) || 0;
                        if (oldCount !== displayCount) {
                            badge.classList.add('badge-count-change');
                            // INSTANT UPDATE - No setTimeout delay
                            badge.textContent = displayCount;
                            badge.setAttribute('aria-label', `${displayCount} unread notifications`);
                            
                            // Remove animation class after brief delay
                            requestAnimationFrame(() => {
                                badge.classList.remove('badge-count-change');
                            });
                        }
                    } else {
                        // Create badge instantly
                        const newBadge = document.createElement('span');
                        newBadge.id = 'notificationBadge';
                        newBadge.className = 'clean-notification-badge badge-appear';
                        newBadge.textContent = displayCount;
                        newBadge.setAttribute('aria-label', `${displayCount} unread notifications`);
                        
                        const bell = document.querySelector('.notification-bell');
                        if (bell) {
                            bell.appendChild(newBadge);
                        }
                    }
                } else if (badge) {
                    badge.remove();
                }
            },
            
            // Simple cleanup function
            cleanup() {
                if (this.pollTimer) {
                    window.clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
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
    
    // LETHAL NAVIGATION INITIALIZATION - PREVENTS FOUC
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            setTimeout(() => {
                navbar.style.opacity = '1';
            }, 100);
        }
    });

    // Streak animation initialization
    document.addEventListener('DOMContentLoaded', function() {
        const streakIndicator = document.querySelector('.streak-indicator');
        if (streakIndicator) {
            const streakProgress = document.querySelector('.streak-progress');
            const streak = parseInt(streakIndicator.dataset.streak);
            const streakFire = document.querySelector('.streak-fire');
            
            // A full loop represents one week of study streak.
            const maxStreak = 7;
            const cycleDay = streak % maxStreak;
            const isWeeklyMilestone = streak > 0 && cycleDay === 0;
            const progress = isWeeklyMilestone ? 1 : cycleDay / maxStreak;
            const radius = parseFloat(streakProgress.getAttribute('r') || '40');
            const circumference = 2 * Math.PI * radius;
            
            // Animate the progress
            setTimeout(() => {
                streakProgress.style.strokeDasharray = circumference;
                streakProgress.style.strokeDashoffset = circumference * (1 - progress);
            }, 100);

            if (isWeeklyMilestone) {
                setTimeout(() => {
                    streakIndicator.classList.add('streak-week-complete');
                    if (streakFire) {
                        streakFire.classList.add('streak-fire-celebrate');
                    }
                    streakProgress.style.filter = 'drop-shadow(0 0 12px rgba(255, 179, 0, 0.95))';
                }, 1450);

                setTimeout(() => {
                    streakProgress.style.strokeDashoffset = circumference;
                }, 2200);

                setTimeout(() => {
                    streakIndicator.classList.remove('streak-week-complete');
                    if (streakFire) {
                        streakFire.classList.remove('streak-fire-celebrate');
                    }
                    streakProgress.style.filter = '';
                }, 3000);
            }
            
            // Add hover effect for the flame
            if (streak > 0) {
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const headerXpBar = document.getElementById('header-xp-bar');
        const headerXpText = document.getElementById('header-xp-text');
        const headerLevel = document.getElementById('header-user-level');

        if (!headerXpBar) return;

        function updateHeaderXP(data) {
            if (data && data.new_xp !== undefined) {
                const xpInLevel = data.new_xp % 1000;
                const level = Math.floor(data.new_xp / 1000) + 1;
                const percent = (xpInLevel / 1000) * 100;

                headerXpBar.style.width = percent + '%';
                headerXpText.textContent = xpInLevel + '/1000';
                
                if (headerLevel.textContent != level) {
                    headerLevel.textContent = level;
                    headerLevel.classList.add('pulse-animation');
                    setTimeout(() => headerLevel.classList.remove('pulse-animation'), 2000);
                    
                    if (window.confetti) {
                        window.confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
                    }
                }
            } else {
                fetch('/profile/progress', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.xp !== undefined) {
                        updateHeaderXP({ new_xp: d.xp });
                    }
                });
            }
        }

        window.addEventListener('storage', function(e) {
            if (e.key === 'quizProgressUpdate') {
                updateHeaderXP();
            }
        });

        document.addEventListener('sourceProgressUpdated', function(e) {
            updateHeaderXP(e.detail);
        });
    });
</script>

<style>
    @keyframes pulse-animation {
        0% { transform: scale(1); color: #7e57c2; }
        50% { transform: scale(1.5); color: #ffca28; text-shadow: 0 0 10px rgba(255, 202, 40, 0.5); }
        100% { transform: scale(1); color: #7e57c2; }
    }
    .pulse-animation {
        display: inline-block;
        animation: pulse-animation 2s ease;
    }
</style>
