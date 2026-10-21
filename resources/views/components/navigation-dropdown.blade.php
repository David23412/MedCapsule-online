@php
    $user = Auth::user();
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
        {{ $user->name }}
        @if($user->isAdmin())
            <span class="badge bg-danger ms-1">Admin</span>
        @elseif($user->isTutor())
            <span class="badge bg-success ms-1">Tutor</span>
        @else
            <span class="badge bg-primary ms-1">Student</span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <!-- Study Streak Indicator with Enhanced Navigation -->
        <li class="d-flex justify-content-center">
            <div class="streak-indicator dropdown" data-streak="{{ $user->study_streak_days ?? 0 }}">
                <div class="streak-circle" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                    <svg viewBox="0 0 100 100">
                        <circle class="streak-bg" cx="50" cy="50" r="40"></circle>
                        <circle class="streak-progress" cx="50" cy="50" r="40"></circle>
                    </svg>
                    <div class="streak-content">
                        <div class="streak-value" data-value="{{ $user->study_streak_days ?? 0 }}">
                            {{ $user->study_streak_days ?? 0 }}
                        </div>
                        <div class="streak-label">Streak</div>
                    </div>
                    @if(($user->study_streak_days ?? 0) > 0)
                        <div class="streak-fire">
                            <i class="fas fa-fire"></i>
                        </div>
                    @endif
                </div>
                
                <!-- Enhanced Streak Navigation Dropdown -->
                <ul class="dropdown-menu streak-dropdown">
                    <li class="dropdown-header">
                        <i class="fas fa-fire me-2 text-warning"></i>Study Progress
                    </li>
                    
                    <!-- Quick Actions for All Users -->
                    <li>
                        <a class="dropdown-item" href="{{ route('courses.index') }}">
                            <i class="fas fa-book me-2"></i>Continue Learning
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('quiz.random.show') }}">
                            <i class="fas fa-random me-2"></i>Random Quiz
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('review.index') }}">
                            <i class="fas fa-exclamation-triangle me-2"></i>Review Mistakes
                        </a>
                    </li>
                    
                    <!-- Student-specific Actions -->
                    @if($user->isStudent())
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>Student Tools
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('student.assignments.index') }}">
                                <i class="fas fa-tasks me-2"></i>My Assignments
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile') }}">
                                <i class="fas fa-chart-line me-2"></i>Progress Report
                            </a>
                        </li>
                    @endif
                    
                    <!-- Tutor-specific Actions -->
                    @if($user->isTutor() || $user->isAdmin())
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">
                            <i class="fas fa-chalkboard-teacher me-2 text-success"></i>Tutor Tools
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('tutor.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Tutor Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('assignments.create') }}">
                                <i class="fas fa-plus-circle me-2"></i>Create Assignment
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('topic-locking.index') }}">
                                <i class="fas fa-lock me-2"></i>Topic Management
                            </a>
                        </li>
                    @endif
                    
                    <!-- Admin-specific Actions -->
                    @if($user->isAdmin())
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header">
                            <i class="fas fa-crown me-2 text-warning"></i>Admin Tools
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.tutors.index') }}">
                                <i class="fas fa-users-cog me-2"></i>Manage Tutors
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.payments.history') }}">
                                <i class="fas fa-credit-card me-2"></i>Payment Management
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.delete.courses') }}">
                                <i class="fas fa-book-open me-2"></i>Manage Courses
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.delete.questions') }}">
                                <i class="fas fa-question-circle me-2"></i>Manage Questions
                            </a>
                        </li>
                    @endif
                    
                    <!-- Streak Stats -->
                    <li><hr class="dropdown-divider"></li>
                    <li class="streak-stats">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2">
                            <div class="text-center">
                                <div class="fw-bold text-primary">{{ $user->study_streak_days ?? 0 }}</div>
                                <small class="text-muted">Current</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold text-success">{{ $user->max_study_streak ?? 0 }}</div>
                                <small class="text-muted">Best</small>
                            </div>
                            <div class="text-center">
                                <div class="fw-bold text-info">{{ $user->total_study_days ?? 0 }}</div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </li>
        <li><hr class="dropdown-divider"></li>

        <!-- Core Navigation Items -->
        <li>
            <a class="dropdown-item" href="{{ route('courses.index') }}">
                <i class="fas fa-book me-2"></i>Courses
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('quiz.random.show') }}">
                <i class="fas fa-random me-2"></i>Random Quiz
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('review.index') }}">
                <i class="fas fa-exclamation-triangle me-2"></i>Review Mistakes
            </a>
        </li>

        <!-- Profile and Logout -->
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ route('profile') }}">
                <i class="fas fa-user me-2"></i>Profile
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('notifications.index') }}">
                <i class="fas fa-bell me-2"></i>Notifications
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li class="px-2 py-1">
            <button type="button" class="admin-theme-toggle w-100 justify-content-center" data-theme-toggle aria-pressed="false">
                <i class="fas fa-circle-half-stroke" data-theme-icon></i>
                <span data-theme-label>Dark mode</span>
            </button>
        </li>
        <li>
            <form method="POST" action="{{ route('logout', [], false) }}" class="dropdown-item p-0">
                @csrf
                <button type="submit" class="dropdown-item w-100 text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </button>
            </form>
        </li>
    </ul>
</li>

<style>
    /* Streak Indicator Styles */
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
        width: 60px;
        height: 60px;
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
        stroke-width: 3;
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
        font-size: 1.5rem;
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
        font-size: 0.55rem;
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
        font-size: 1rem;
    }

    .streak-fire.streak-fire-celebrate {
        color: #ffb300;
        animation: streakFireCelebrate 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) 2;
        filter: drop-shadow(0 0 12px rgba(255, 179, 0, 0.95));
    }

    .streak-indicator.streak-week-complete {
        animation: streakWeekComplete 0.9s ease-out 1;
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

    .dropdown-header {
        font-weight: 600;
        font-size: 0.85rem;
        color: #6c757d;
        padding: 0.5rem 1rem;
    }

    /* Enhanced Streak Navigation Dropdown Styles */
    .streak-dropdown {
        min-width: 280px;
        border-radius: 12px;
        border: none;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        padding: 0.5rem;
        margin-top: 0.5rem;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(12px);
    }

    .streak-dropdown .dropdown-item {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        color: #374151;
    }

    .streak-dropdown .dropdown-item:hover {
        background: linear-gradient(135deg, #f0f7ff, #e3f2fd);
        color: #1976d2;
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.15);
    }

    .streak-dropdown .dropdown-item i {
        width: 18px;
        text-align: center;
        opacity: 0.8;
        transition: all 0.3s ease;
    }

    .streak-dropdown .dropdown-item:hover i {
        opacity: 1;
        transform: scale(1.1);
    }

    .streak-dropdown .dropdown-header {
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem 1rem 0.5rem;
        margin-bottom: 0.25rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .streak-dropdown .dropdown-header i {
        -webkit-text-fill-color: initial;
    }

    .streak-stats {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: 8px;
        margin: 0.5rem;
        padding: 0.5rem;
    }

    .streak-stats .fw-bold {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .streak-stats small {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    /* Enhanced streak indicator hover effects */
    .streak-indicator:hover {
        transform: scale(1.05);
        box-shadow: 0 0 35px rgba(25, 118, 210, 0.4);
    }

    .streak-indicator:hover .streak-circle svg {
        filter: drop-shadow(0 0 12px rgba(51, 154, 240, 0.6));
    }

    .streak-indicator:hover .streak-value {
        animation: glowPulse 1s infinite;
    }

    /* Dropdown positioning for streak */
    .streak-indicator.dropdown .dropdown-menu {
        left: 50%;
        transform: translateX(-50%);
        top: 100%;
        margin-top: 0.5rem;
    }

    /* Role-specific header colors */
    .streak-dropdown .dropdown-header .text-primary {
        color: #2196f3 !important;
    }

    .streak-dropdown .dropdown-header .text-success {
        color: #4caf50 !important;
    }

    .streak-dropdown .dropdown-header .text-warning {
        color: #ff9800 !important;
    }

    /* Smooth transitions for all dropdown elements */
    .streak-dropdown * {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<script>
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
