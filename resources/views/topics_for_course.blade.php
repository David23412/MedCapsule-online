<!DOCTYPE html>
<html lang="en">
<head>
    <!-- 🚀 OPTIMIZED: Minimal critical functions only -->
    <script>
        window.startPerformanceTracking=window.startPerformanceTracking||function(){return{startTime:Date.now()}};
        window.endPerformanceTracking=window.endPerformanceTracking||function(){return 0};
        window.loadNotifications=window.loadNotifications||function(){};
        window.initializeNotificationSystem=window.initializeNotificationSystem||function(){};
        window.performanceMetrics=window.performanceMetrics||{loadStartTime:Date.now(),retryAttempts:0,networkErrors:0};
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Disable source maps -->
    <meta name="devtools-disable-sourcemaps" content="true">
    <title>MedCapsule</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <!-- 🚀 OPTIMIZED: Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/topics.css') }}?v=1.8">

    <link rel="stylesheet" href="{{ asset('css/topics-for-course-page.css') }}?v={{ time() }}">
    
    {{-- Responsive Navigation CSS --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-navigation.css') }}">
</head>
<body>
    {{-- Mobile-only sticky top bar --}}
    <header class="topics-mobile-header" id="topicsMobileHeader">
        <button class="topics-mobile-hamburger" id="topicsMobileHamburger" aria-label="Open navigation menu" aria-expanded="false" aria-controls="topicsSidebar">
            <span></span><span></span><span></span>
        </button>
        <span class="topics-mobile-course-name">{{ $course->name }}</span>
        <a href="{{ route('courses.index') }}" class="topics-mobile-home-link" aria-label="Home">
            <i class="fas fa-home"></i>
        </a>
    </header>
    {{-- Offcanvas backdrop --}}
    <div class="topics-sidebar-backdrop" id="topicsSidebarBackdrop" aria-hidden="true"></div>
    @php
        $profileImageUrl = $currentUserView['profile_image_url'] ?? Auth::user()->getProfileImageUrl();
        $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff');
        $masteredCount = $topics->filter(function ($topic) {
            $userProgress = $topic->userProgress->first();
            return $userProgress && ($userProgress->percentage_grade ?? 0) >= 80;
        })->count();
        $masteredPercentage = $topics->count() > 0 ? round(($masteredCount / $topics->count()) * 100) : 0;
        $collapsedTaskDots = collect($gamePlan['tasks'] ?? [])
            ->take(3)
            ->map(fn ($task) => !empty($task['completed']))
            ->pad(3, false)
            ->values();
        $resolvedPlannerSettings = $gamePlan['settings']['resolved_settings'] ?? [];
        $plannerFocusLabel = ucwords(str_replace('_', ' ', (string) ($resolvedPlannerSettings['planner_focus'] ?? ($gamePlan['settings']['planner_focus'] ?? 'balanced'))));
        $dailyProgress = $gamePlan['daily_progress'] ?? [];
        $trackedMinutesToday = (int) ($dailyProgress['tracked_minutes'] ?? 0);
        $targetMinutesToday = (int) ($dailyProgress['target_minutes'] ?? ($resolvedPlannerSettings['allocated_minutes'] ?? 0));
        $studyProgressPercent = (int) ($dailyProgress['progress_percent'] ?? 0);
        $plannerDebug = $gamePlan['debug'] ?? null;
        $canViewPlannerDebug = Auth::user()->isAdmin() || Auth::user()->isTutor();
    @endphp

    <div class="sidebar" id="topicsSidebar" role="navigation" aria-label="Course navigation">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="true">
            <span class="sidebar-toggle-chevron" aria-hidden="true">
                <i class="fas fa-chevron-left"></i>
            </span>
        </button>
        {{-- Mobile-only close button inside the drawer --}}
        <button type="button" class="topics-sidebar-close-btn" id="topicsSidebarCloseBtn" aria-label="Close navigation">
            <i class="fas fa-times"></i>
        </button>

        <a href="{{ route('courses.index') }}" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
                    <defs>
                        <linearGradient id="topicsSidebarPillBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb"></stop>
                            <stop offset="45%" stop-color="#3b82f6"></stop>
                            <stop offset="100%" stop-color="#1d4ed8"></stop>
                        </linearGradient>
                        <linearGradient id="topicsSidebarPillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0)"></stop>
                            <stop offset="30%" stop-color="rgba(255,255,255,0.6)"></stop>
                            <stop offset="70%" stop-color="rgba(255,255,255,0.5)"></stop>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"></stop>
                        </linearGradient>
                        <linearGradient id="topicsSidebarPillShadow" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgba(0,0,0,0)"></stop>
                            <stop offset="100%" stop-color="rgba(10,20,80,0.28)"></stop>
                        </linearGradient>
                        <filter id="topicsSidebarPillDrop" x="-8%" y="-15%" width="116%" height="150%">
                            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"></feDropShadow>
                        </filter>
                    </defs>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#topicsSidebarPillBody)" filter="url(#topicsSidebarPillDrop)"></rect>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#topicsSidebarPillShadow)"></rect>
                    <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#topicsSidebarPillGloss)" opacity="0.88"></rect>
                    <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"></ellipse>
                </svg>
            </div>
            <div class="sidebar-logo-copy">
                <div class="sidebar-logo-text">MedCapsule</div>
                <div class="sidebar-logo-sub">{{ $course->name }}</div>
            </div>
        </a>

        <div class="sidebar-nav">
            <span class="sidebar-section-label">Navigation</span>

            <a class="nav-link" href="{{ route('courses.index') }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-link-label">Home</span>
            </a>
            <a class="nav-link active" href="{{ route('courses.index') }}">
                <i class="fas fa-book-open nav-icon"></i>
                <span class="nav-link-label">My Courses</span>
            </a>
            <a class="nav-link" href="{{ route('review.index') }}">
                <i class="fas fa-rotate-right nav-icon"></i>
                <span class="nav-link-label">Review Mistakes</span>
            </a>
            <a class="nav-link" href="{{ route('review.learn') }}">
                <i class="fas fa-bolt nav-icon"></i>
                <span class="nav-link-label">Learn Mistakes</span>
            </a>
            <a class="nav-link" href="{{ route('profile') }}">
                <i class="fas fa-gear nav-icon"></i>
                <span class="nav-link-label">Profile Settings</span>
            </a>

            <span class="sidebar-section-label">Game Plan</span>

            @if(!$hasFullCourseAccess && $previewTopicLimit)
                <div class="sidebar-preview-indicator" title="Preview mode" aria-label="Preview mode">
                    <i class="fas fa-exclamation-triangle sidebar-preview-icon" aria-hidden="true"></i>
                    <strong class="sidebar-preview-label">Preview mode</strong>
                </div>
            @endif

                    <div class="sidebar-collapsed-task-indicator" aria-hidden="true">
                        <div class="sidebar-collapsed-task-indicator-shell">
                            @foreach($collapsedTaskDots as $isCompleted)
                                <span class="sidebar-collapsed-task-dot{{ $isCompleted ? ' is-complete' : '' }}"></span>
                            @endforeach
                        </div>
                    </div>

                    <div class="sidebar-game-plan">
                <div class="sidebar-game-plan-head">
                    <div>
                        <div class="sidebar-game-plan-title">Today&apos;s Plan</div>
                        <div class="sidebar-game-plan-sub">{{ $course->name }}</div>
                    </div>
                    <div class="sidebar-game-plan-level">Lvl {{ $currentUserView['level'] ?? Auth::user()->getCurrentLevel() }}</div>
                </div>

                @if(session('status'))
                    <div style="margin-top: 0.7rem; padding: 0.65rem 0.8rem; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-size: 0.82rem;">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="sidebar-game-plan-meta">
                    <span>{{ $gamePlan['completed_count'] ?? 0 }}/{{ $gamePlan['total_count'] ?? 0 }} cleared</span>
                    <span>{{ $gamePlan['earned_xp'] ?? 0 }} XP earned</span>
                </div>

                <div class="sidebar-game-plan-meta" style="margin-top: 0.5rem; flex-direction: column; align-items: flex-start; gap: 0.2rem;">
                    <span>{{ $gamePlan['planned_minutes'] ?? 0 }} min planned · {{ $gamePlan['planned_questions'] ?? 0 }} questions</span>
                    <span>Focus: {{ $plannerFocusLabel }}</span>
                    @if(!empty($resolvedPlannerSettings['preferred_source'] ?? null))
                        <span>Source: {{ $resolvedPlannerSettings['preferred_source'] }}</span>
                    @endif
                </div>

                <div class="sidebar-game-plan-meta" style="margin-top: 0.75rem; flex-direction: column; align-items: flex-start; gap: 0.35rem;">
                    <span>Today: {{ $trackedMinutesToday }}/{{ max($targetMinutesToday, 1) }} min</span>
                    <div style="width: 100%; height: 6px; border-radius: 999px; background: #dbeafe; overflow: hidden;">
                        <div style="height: 100%; width: {{ min(100, max(0, $studyProgressPercent)) }}%; border-radius: 999px; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                    </div>
                    <span>{{ $dailyProgress['tracked_questions'] ?? 0 }} questions tracked today</span>
                </div>

                <div class="sidebar-task-list">
                    @forelse(($gamePlan['tasks'] ?? []) as $task)
                        <div class="sidebar-task-item{{ !empty($task['completed']) ? ' is-complete' : '' }}{{ !empty($task['is_preview']) ? ' is-preview' : '' }}">
                            <div class="sidebar-task-check" aria-hidden="true">
                                <i class="fas {{ !empty($task['is_preview']) ? 'fa-lock' : (!empty($task['completed']) ? 'fa-check' : 'fa-circle') }}"></i>
                            </div>
                            <div class="sidebar-task-copy">
                                <strong>{{ $task['title'] }}</strong>
                                <span>
                                    {{ $task['meta'] }}
                                    @if(!empty($task['is_preview']))
                                        · Locked preview
                                    @endif
                                </span>
                            </div>
                            @if(empty($task['is_preview']))
                                <span class="sidebar-task-xp">+{{ $task['xp'] }} XP</span>
                            @else
                                <span class="sidebar-task-xp" style="color: #64748b;">Preview</span>
                            @endif
                        </div>
                    @empty
                        <div class="sidebar-task-empty">
                            No tasks to push right now. You&apos;re clear to explore.
                        </div>
                    @endforelse
                </div>

                @if($canViewPlannerDebug && is_array($plannerDebug))
                    <details style="margin-top: 0.9rem; border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 12px; background: rgba(239, 246, 255, 0.78);">
                        <summary style="cursor: pointer; padding: 0.75rem 0.9rem; font-size: 0.82rem; font-weight: 600; color: #1e3a8a;">
                            Planner Debug
                        </summary>
                        <div style="padding: 0 0.9rem 0.9rem; display: grid; gap: 0.8rem;">
                            <div style="font-size: 0.78rem; color: #1e40af;">
                                Frontier topic: {{ $plannerDebug['frontier_topic_id'] ?? 'none' }}<br>
                                Frontier stage: {{ $plannerDebug['frontier_stage'] ?? 'none' }}
                            </div>

                            <div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #1f2937; margin-bottom: 0.35rem;">Visible Tasks</div>
                                @forelse(($plannerDebug['tasks'] ?? []) as $debugTask)
                                    <div style="padding: 0.45rem 0.55rem; border-radius: 10px; background: #fff; border: 1px solid rgba(148, 163, 184, 0.2); font-size: 0.74rem; color: #334155; margin-bottom: 0.35rem;">
                                        <strong>{{ $debugTask['title'] ?? ($debugTask['id'] ?? 'task') }}</strong><br>
                                        <span>slot {{ $debugTask['slot_index'] ?? '-' }} · level {{ $debugTask['backfill_level'] ?? '-' }} · {{ $debugTask['type'] ?? 'unknown' }}</span><br>
                                        <span>reason: {{ $debugTask['trigger_reason'] ?? 'n/a' }}</span>
                                        @if(!empty($debugTask['is_preview']))
                                            <br><span>preview unlocks after: {{ $debugTask['unlocks_after_task_id'] ?? 'n/a' }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <div style="font-size: 0.74rem; color: #64748b;">No debug tasks available.</div>
                                @endforelse
                            </div>

                            <div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #1f2937; margin-bottom: 0.35rem;">Topic States</div>
                                @foreach(($plannerDebug['topics'] ?? []) as $debugTopic)
                                    <div style="padding: 0.45rem 0.55rem; border-radius: 10px; background: #fff; border: 1px solid rgba(148, 163, 184, 0.2); font-size: 0.74rem; color: #334155; margin-bottom: 0.35rem;">
                                        <strong>{{ $debugTopic['display_order'] ?? '?' }}. {{ $debugTopic['topic_name'] ?? 'Topic' }}</strong><br>
                                        <span>{{ $debugTopic['progression_stage'] ?? 'unknown' }} · unlocked {{ !empty($debugTopic['curriculum_unlocked']) ? 'yes' : 'no' }} · priority {{ $debugTopic['priority_score'] ?? 0 }}</span>
                                        @if(!empty($debugTopic['locked_reason']))
                                            <br><span>lock reason: {{ $debugTopic['locked_reason'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endif
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="{{ route('profile') }}" class="sidebar-profile-link">
                <span class="sidebar-profile-avatar">
                    <img src="{{ $profileImageUrl }}" alt="Profile Picture" onerror="this.src='{{ $profileFallbackUrl }}'">
                </span>
                <span class="sidebar-profile-copy">
                    <span class="sidebar-profile-name">{{ Auth::user()->name }}</span>
                    <span class="sidebar-profile-meta">Study profile</span>
                </span>
            </a>
            <form method="POST" action="{{ secure_url(route('logout', [], false)) }}">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start" style="cursor: pointer;">
                    <i class="fas fa-arrow-right-from-bracket nav-icon"></i>
                    <span class="nav-link-label">Sign Out</span>
                </button>
            </form>
        </div>
    </div>

    <main class="main-content">
    <!-- Random Quiz Modal -->
    <div class="modal fade" id="randomQuizModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-random"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Random Quiz Mode</h5>
                            <p class="modal-subtitle mb-0">Customize your quiz settings</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('quiz.random.show') }}" method="GET" id="randomQuizForm">
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-book-open text-warning"></i>
                                <label class="form-label mb-2">Select Courses</label>
                            </div>
                            <div class="courses-container">
                                @if(isset($availableCourses) && $availableCourses->count() > 0)
{{-- 💀 N+1 FIX NEEDED: Eager load 'name' in controller --}}
                                    @foreach($availableCourses as $availableCourse)
                                        <div class="form-check custom-checkbox">
                                            <input class="form-check-input" type="checkbox" 
                                                name="selected_courses[]" 
                                                value="{{ $availableCourse->id }}" 
                                                id="course{{ $availableCourse->id }}"
                                                checked>
                                            <label class="form-check-label" for="course{{ $availableCourse->id }}">
                                                {{ $availableCourse->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-exclamation-circle mb-2"></i>
                                        <p class="mb-0">Random Quiz is only available after full enrollment in a course.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-list-ol text-warning"></i>
                                <label for="questionLimit" class="form-label mb-2">Number of Questions</label>
                            </div>
                            <select class="form-select custom-select" id="questionLimit" name="question_limit" required>
                                <option value="">Select number of questions</option>
                                <option value="5">5 Questions</option>
                                <option value="10">10 Questions</option>
                                <option value="15">15 Questions</option>
                                <option value="20">20 Questions</option>
                                <option value="30">30 Questions</option>
                                <option value="50">50 Questions</option>
                                <option value="100">100 Questions</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-book text-warning"></i>
                                <label class="form-label mb-2">Select Sources</label>
                            </div>
                            <div class="courses-container" id="sourceCheckboxContainer">
                                <div class="text-center text-muted py-3" id="sourcePlaceholder">
                                    <i class="fas fa-info-circle mb-2"></i>
                                    <p class="mb-0">Select courses above to load available sources.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-clock text-warning"></i>
                                <label for="timeLimit" class="form-label mb-2">Time Limit (minutes)</label>
                            </div>
                            <select class="form-select custom-select" id="timeLimit" name="duration_minutes" required>
                                <option value="">Select time limit</option>
                                <option value="0.167">10 Seconds (Testing)</option>
                                <option value="5">5 Minutes</option>
                                <option value="10">10 Minutes</option>
                                <option value="15">15 Minutes</option>
                                <option value="20">20 Minutes</option>
                                <option value="30">30 Minutes</option>
                                <option value="45">45 Minutes</option>
                                <option value="60">60 Minutes</option>
                                <option value="90">90 Minutes</option>
                                <option value="120">2 Hours</option>
                            </select>
                        </div>

                        <!-- Mode Selection -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="quiz_mode" id="modalQuizMode" value="quiz" checked>
                                    <label class="form-check-label" for="modalQuizMode">Quiz Mode</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="quiz_mode" id="modalTutorMode" value="tutor">
                                    <label class="form-check-label" for="modalTutorMode">Tutor Mode</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="randomQuizForm" class="btn btn-warning">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="searchQuestionsModal" tabindex="-1" aria-labelledby="searchQuestionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content search-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="modal-title-wrapper w-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <div class="modal-icon-wrapper me-3">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" id="searchQuestionsModalLabel">Search Course Questions</h5>
                                    <p class="modal-subtitle mb-0">Find and bookmark any question instantly</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="search-input-group mt-3">
                            <i class="fas fa-search search-input-icon"></i>
                            <input type="text" id="courseQuestionSearchInput" class="form-control" placeholder="Type keywords, concepts, or question text..." autocomplete="off">
                            <div class="search-spinner" id="searchSpinner" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-body pt-3">
                    <div id="searchQuestionsResults" class="search-results-container">
                        <div class="search-results-placeholder">
                            <i class="fas fa-keyboard mb-2"></i>
                            <p>Start typing to search keywords, concepts, or question text</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Source Selection Modal -->
    <div class="modal fade" id="sourceSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper d-none d-md-flex">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Select Question Sources</h5>
                            <p class="mb-0 text-muted small d-none d-md-block">Choose which sources to include in your quiz</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <form action="" method="GET" id="sourceSelectionForm">
                        <input type="hidden" name="course_id" id="source_course_id">
                        <input type="hidden" name="topic_id" id="source_topic_id">
                        
                        <div class="sources-container mb-3">
                            <!-- Source cards will be dynamically inserted here -->
                        </div>
                        
                        <!-- Simplified Mode Selection -->
                        <div class="d-flex align-items-center gap-3 mb-3 px-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="quizMode" value="quiz" checked>
                                <label class="form-check-label" for="quizMode">Quiz Mode</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="tutorMode" value="tutor">
                                <label class="form-check-label" for="tutorMode">Tutor Mode</label>
                            </div>
                        </div>

                        <div class="px-2 d-none" id="tutorScopeSection">
                            <div class="small text-muted mb-2">Choose how to start</div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="tutor_scope" id="tutorScopeRemaining" value="remaining" checked>
                                <label class="form-check-label" for="tutorScopeRemaining">
                                    Continue solving
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tutor_scope" id="tutorScopeFull" value="full">
                                <label class="form-check-label" for="tutorScopeFull">
                                    Retry
                                </label>
                            </div>
                            <div class="small text-muted mt-2" id="tutorScopeHint">
                                Continue where you stopped, or start again.
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="sourceSelectionForm" class="btn btn-primary" id="startSourceQuizBtn">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>





    <!-- Course Header -->
    <header class="course-header">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="course-progress">
            <div class="progress-heading-row">
                <div class="progress-text-title">Topics Mastered</div>
                <div class="progress-meta">
                    <span class="progress-count">{{ $masteredCount }} of {{ $topics->count() }} complete</span>
                    <span class="progress-percent">{{ $masteredPercentage }}%</span>
                </div>
            </div>
            <div class="topics-progress-container" aria-label="Topics mastered progress">
                <div class="topics-progress-navigation">
                    <button class="progress-nav-btn progress-nav-prev" id="progressPrevBtn" aria-label="Previous topics">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="topics-progress-track" id="topicsProgressTrack">
                        @php
                            $allTopics = $topics->values();
                            $totalTopics = $allTopics->count();
                            $segmentsToShow = 10;
                            $currentIndex = 0;
                        @endphp
                        @for($i = 0; $i < min($segmentsToShow, $totalTopics); $i++)
                            @php
                                $topicIndex = $i;
                                $topic = $allTopics->get($topicIndex);
                                if (!$topic) continue;
                                $topicProgress = $topic->userProgress->first();
                                $isMastered = $topicProgress && ($topicProgress->percentage_grade ?? 0) >= 80;
                            @endphp
                            <span
                                class="topics-progress-segment{{ $isMastered ? ' is-mastered' : '' }}"
                                data-topic-id="{{ $topic->id }}"
                                data-index="{{ $topicIndex }}"
                                aria-hidden="true"
                            ></span>
                        @endfor
                    </div>
                    <button class="progress-nav-btn progress-nav-next" id="progressNextBtn" aria-label="Next topics">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="case-filters">
        @if($course->type === 'custom')
            @php
                $categories = $topics->pluck('case_type')->unique()->filter()->values();
            @endphp
            
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @foreach($categories as $category)
                @php
                    $categoryCount = $topics->where('case_type', $category)->count();
                    // Define icons for custom categories
                    $categoryIcons = [
                        'homework' => 'fas fa-pencil-alt',
                        'lab' => 'fas fa-microscope',
                        'reading' => 'fas fa-book-open',
                        'project' => 'fas fa-project-diagram',
                        'assignment' => 'fas fa-tasks',
                        'exercise' => 'fas fa-dumbbell',
                        'tutorial' => 'fas fa-play-circle',
                        'lecture' => 'fas fa-chalkboard-teacher',
                        'exam' => 'fas fa-clipboard-check',
                        'test' => 'fas fa-file-alt',
                        'review' => 'fas fa-eye',
                        'practice' => 'fas fa-repeat',
                        'study' => 'fas fa-graduation-cap',
                        'notes' => 'fas fa-sticky-note',
                        'video' => 'fas fa-video',
                        'audio' => 'fas fa-volume-up',
                        'discussion' => 'fas fa-comments',
                        'forum' => 'fas fa-comment-dots',
                        'workshop' => 'fas fa-tools',
                        'seminar' => 'fas fa-users'
                    ];
                    $icon = $categoryIcons[strtolower($category)] ?? 'fas fa-tag';
                @endphp
                
                <div class="case-filter{{ $categories->count() == 0 ? ' active' : '' }}" data-filter="{{ strtolower($category) }}">
                    <i class="{{ $icon }}"></i>
                    {{ ucfirst($category) }}
                    <span class="count">{{ $categoryCount }}</span>
                </div>
            @endforeach
        @else
            <!-- ========== MEDICAL COURSE CATEGORIZATION ========== -->
            @php
                $casesCount = $topics->where('case_type', 'cases')->count();
                $practicalCount = $topics->where('case_type', 'practical')->count();
                $quizCount = $topics->where('case_type', 'quiz')->count();
            @endphp
            
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @if($casesCount > 0)
                <div class="case-filter" data-filter="cases">
                    <i class="fas fa-file-alt"></i>
                    Cases
                    <span class="count">{{ $casesCount }}</span>
                </div>
            @endif
            
            @if($practicalCount > 0)
                <div class="case-filter" data-filter="practical">
                    <i class="fas fa-flask"></i>
                    Practical
                    <span class="count">{{ $practicalCount }}</span>
                </div>
            @endif
            
            @if($quizCount > 0)
                <div class="case-filter" data-filter="quiz">
                    <i class="fas fa-question-circle"></i>
                    Quiz
                    <span class="count">{{ $quizCount }}</span>
                </div>
            @endif
        @endif
        

        
        <!-- Fixed position pills that are always present -->
        <a href="#" class="random-link" data-bs-toggle="modal" data-bs-target="#randomQuizModal" style="order: 98;">
            <i class="fas fa-random"></i>
            Random
        </a>
        <a href="{{ route('review.index') }}" class="mistakes-link" style="order: 99;">
            <i class="fas fa-times-circle"></i>
            Review Mistakes
        </a>
        <a href="#" class="search-icon-bubble" data-bs-toggle="modal" data-bs-target="#searchQuestionsModal" title="Search Questions" style="order: 101;">
            <i class="fas fa-search"></i>
        </a>
        
        {{-- Assignment Icon in Red Circle - Only show if there are assignments --}}
        @if(isset($assignments) && (is_array($assignments) ? count($assignments) : $assignments->count()) > 0)
            <div class="assignment-icon-circle" data-filter="assignments" style="order: 100;">
                <div class="assignment-circle">
                    <i class="fas fa-tasks"></i>
                    <span class="assignment-glow-count">{{ is_array($assignments) ? count($assignments) : $assignments->count() }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="topicSearch" placeholder="Search topics..." class="search-input">
            <button type="button" id="clearSearch" class="clear-search-btn" style="display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Topics List -->
    <div class="topics-container">
        {{-- 🚀 LETHAL ASSIGNMENT OPTIMIZATION: Pre-calculated data --}}
        @if(isset($assignments) && count($assignments) > 0)
            @php
                // 🚀 SURGICAL OPTIMIZATION: Pre-calculate ALL data ONCE
                $assignmentData = $assignments->mapWithKeys(function($assignment) use ($topics) {
                    $topic = $topics->firstWhere('id', $assignment->topic_id);
                    if (!$topic) return [];
                    
                    return [$assignment->id => [
                        'topic' => $topic,
                        'progress' => $assignment->user_progress['percentage'] ?? 0,
                        'status' => $assignment->status_string,
                        'quizUrl' => secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id, 'assignment_id' => $assignment->id], false)),
                        'isLocked' => in_array($assignment->status, ['locked', 'expired', 'completed']) || ($assignment->status === 'overdue'),
                        'lockReason' => $assignment->getLockReason() ?? 'Assignment is locked'
                    ]];
                });
            @endphp
            
            @foreach($assignments as $assignment)
                @php 
                    $data = $assignmentData[$assignment->id] ?? null;
                    if (!$data) continue;
                    
                    // SIMPLE: Just check if user has attempted this assignment
                    $isLocked = $assignment->isLockedForUser(auth()->id());
                @endphp
                
                <div class="topic-card assignment-{{ $assignment->isCompletedByUser(auth()->id()) ? 'completed' : ($data['status'] === 'overdue' ? 'overdue' : 'active') }}" 
                     data-case-type="assignment" 
                     data-topic-id="{{ $data['topic']->id }}"
                     data-assignment-id="{{ $assignment->id }}"
                     data-has-assignment="true" 
                     data-assignment-deadline="{{ $assignment->deadline }}" 
                     data-assignment-status="{{ $data['status'] }}">
                    
                    {{-- Assignment Status Bar --}}
                    <div class="assignment-status-bar assignment-{{ $assignment->isCompletedByUser(auth()->id()) ? 'completed' : ($data['status'] === 'overdue' ? 'overdue' : 'active') }}">
                        <div class="assignment-info-minimal">
                            @if($data['status'] === 'active' && !$assignment->isLockedForUser(auth()->id()))
                                <span>Assignment Due:</span>
                                <div class="assignment-countdown-minimal" 
                                     data-deadline="{{ $assignment->deadline }}"
                                     data-assignment-status="{{ $data['status'] }}"
                                     data-is-locked="{{ $assignment->isLockedForUser(auth()->id()) ? 'true' : 'false' }}">
                                    <span class="countdown-display"></span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="topic-info">
                        <div class="topic-main">
                            <h3 class="topic-name">
                                <i class="fas fa-tasks"></i>
                                <span class="topic-title-text">{{ $assignment->title }}</span>
                            </h3>
                        </div>
                    </div>

                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $data['progress'] }}%"></div>
                        </div>
                        <div class="progress-label">
                            <span>Assignment Progress</span>
                            <span>{{ number_format($data['progress'], 1) }}%</span>
                        </div>
                    </div>

                    <div class="topic-actions">
                        <div class="stat-item">
                            @if($assignment->isCompletedByUser(auth()->id()))
                                <i class="fas fa-check-circle text-success"></i>Assignment Completed
                            @elseif($assignment->isLockedForUser(auth()->id()) || $data['status'] === 'overdue')
                                <i class="fas fa-times text-danger"></i>Assignment Overdue
                            @else
                                <i class="fas fa-play text-primary"></i>Start Assignment
                            @endif
                        </div>
                        
                        @if($data['status'] === 'overdue')
                            {{-- RED LOCK for overdue assignments --}}
                            <div class="assignment-locked-icon" title="Assignment overdue">
                                <i class="fas fa-lock"></i>
                            </div>
                        @elseif($isLocked)
                            {{-- RED LOCK for locked assignments --}}
                            <div class="assignment-locked-icon" title="Assignment not available">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            <a href="javascript:void(0)" class="start-quiz-btn assignment-start-btn" data-topic-id="{{ $data['topic']->id }}" data-course-id="{{ $data['topic']->course_id }}" data-quiz-url="{{ $data['quizUrl'] }}">
                                <span>Start Assignment</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                    {{-- Assignment cards don't need case type badges --}}
                </div>
            @endforeach
        @endif

        {{-- Regular Topic Cards Section --}}
        @if($topics->count() > 0)
            @foreach($topics as $topic)
                @php
                    $isLocked = isset($topic->is_locked) ? $topic->is_locked : false;
                    $isPreviewLocked = (bool) ($topic->is_preview_locked ?? false);
                @endphp
                
                {{-- Topic Card Container with Video Button --}}
                <div class="topic-with-video-container">
                    <div class="topic-card" 
                         data-case-type="{{ $topic->case_type }}" 
                         data-topic-id="{{ $topic->id }}"
                         data-has-assignment="false">
                        
                        {{-- Regular topics don't have assignment status bars --}}
                    
                    <div class="topic-info">
                        <div class="topic-main">
                            <div class="topic-title-row">
                                <h3 class="topic-name">
                                    <i class="fas fa-book-open"></i>
                                    <span class="topic-title-text">{{ $topic->name }}</span>
                                </h3>
                                @if(($topic->mistakes_count ?? 0) > 0)
                                    <span class="topic-mistakes-badge" title="{{ $topic->mistakes_count }} mistakes in this topic">
                                        {{ $topic->mistakes_count }}
                                    </span>
                                @endif
                            </div>
                            
                            @if($topic->last_attempt_date)
                                <div class="last-attempt">
                                    <i class="far fa-clock"></i>
                                    Last attempt: {{ \Carbon\Carbon::parse($topic->last_attempt_date)->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                        
                        @php
                            $userProgress = $topic->userProgress->first();
                            $percentageGrade = $userProgress ? $userProgress->percentage_grade : null;
                            $hasAttemptedTopic = $topic->last_attempt_date !== null || $userProgress?->last_attempt_date !== null;
                        @endphp
                        @if($hasAttemptedTopic)
                            <div class="grade-badge 
                                {{ $percentageGrade >= 100 ? 'grade-mastered' : 
                                   ($percentageGrade >= 66 ? 'grade-good' : 
                                   ($percentageGrade >= 33 ? 'grade-in-progress' : 'grade-bad')) }}">
                                <i class="fas {{ $percentageGrade >= 100 ? 'fa-star' : 
                                               ($percentageGrade >= 66 ? 'fa-check' : 
                                               ($percentageGrade >= 33 ? 'fa-sync-alt' : 'fa-times')) }}"></i>
                                {{ $percentageGrade >= 100 ? 'Mastered' : 
                                   ($percentageGrade >= 66 ? 'Good' : 
                                   ($percentageGrade >= 33 ? 'In Progress' : 'Just Started')) }}
                            </div>
                        @else
                            <div class="grade-badge grade-none">
                                <i class="fas fa-hourglass-start"></i>
                                Not Started
                            </div>
                        @endif
                    </div>

                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $percentageGrade ?? 0) }}%"></div>
                        </div>
                        <div class="progress-label">
                            <span>Progress</span>
                            <span>{{ $percentageGrade ? number_format(min(100, $percentageGrade), 1) : 0 }}%</span>
                        </div>
                    </div>

                    <div class="topic-actions">
                        <div class="stat-item">
                            @if($percentageGrade >= 100)
                                <i class="fas fa-check-circle text-success"></i>
                                Mastered
                            @elseif($percentageGrade >= 66)
                                <i class="fas fa-check text-success"></i>
                                Good
                            @elseif($percentageGrade >= 33)
                                <i class="fas fa-sync-alt text-info"></i>
                                Making progress
                            @elseif($hasAttemptedTopic)
                                <i class="fas fa-rocket text-primary"></i>
                                Just getting started
                            @else
                                <i class="fas fa-flag text-primary"></i>
                                Ready to start
                            @endif
                        </div>
                        
                        @if($isLocked)
                            {{-- Show white button with red lock for locked topics --}}
                            <div class="topic-locked-btn" title="{{ $isPreviewLocked ? 'Unlock the full course to access this topic' : 'Topic is locked' }}">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            @php
                                $quizUrl = secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id], false));
                            @endphp
                            <a href="javascript:void(0)" class="start-quiz-btn" data-topic-id="{{ $topic->id }}" data-course-id="{{ $topic->course_id }}" data-quiz-url="{{ route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id]) }}">
                                @if($hasAttemptedTopic)
                                    <span>Retake Quiz</span>
                                @else
                                    <span>Start Quiz</span>
                                @endif
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                        <div class="case-type-badge {{ $topic->case_type }}">{{ $topic->case_type }}</div>
                    </div>
                    
                    {{-- Video Play Button OUTSIDE the card --}}
                    @if($topic->video && !$isLocked)
                        <a href="{{ route('video.show', ['topic' => $topic->id]) }}" class="video-play-btn" id="video-btn-{{ $topic->id }}">
                            <i class="fas fa-play"></i>
                        </a>
                    @endif
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No Topics Available</h3>
                <p>Topics for this course will be added soon.</p>
            </div>
        @endif
    </div>

    <div class="task-toast-stack" id="taskToastStack" aria-live="polite" aria-atomic="true"></div>
    </main>


    <script src="{{ asset('js/topics-optimized.js') }}?v=2.1"></script>

    {{-- REMOVED: quiz-modal.js to prevent conflicts - unified in main script --}}

    <script>
        window.TopicsPageConfig = {
            randomQuizRoute: @json(route('quiz.random.show')),
            randomTutorRoute: @json(route('quiz.random.tutor')),
            recentProgressRoute: @json(route('progress.recent-updates')),
            taskCelebration: @json([
                'awarded_now' => $gamePlan['awarded_now'] ?? 0,
                'just_completed_tasks' => $gamePlan['just_completed_tasks'] ?? [],
            ])
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer crossorigin="anonymous"></script>
    <script src="{{ asset('js/topics-for-course-page.js') }}" defer></script>

    <script>
    /* Mobile Sidebar (Offcanvas) Toggle */
    (function() {
        var hamburger = document.getElementById('topicsMobileHamburger');
        var sidebar   = document.getElementById('topicsSidebar');
        var backdrop  = document.getElementById('topicsSidebarBackdrop');
        var closeBtn  = document.getElementById('topicsSidebarCloseBtn');

        function openSidebar() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.classList.add('topics-sidebar-open');
            hamburger && hamburger.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.classList.remove('topics-sidebar-open');
            hamburger && hamburger.setAttribute('aria-expanded', 'false');
        }

        hamburger  && hamburger.addEventListener('click', function() {
            sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
        });
        backdrop   && backdrop.addEventListener('click', closeSidebar);
        closeBtn   && closeBtn.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    })();
    
    /* Topics Progress Navigation */
    (function() {
        const prevBtn = document.getElementById('progressPrevBtn');
        const nextBtn = document.getElementById('progressNextBtn');
        const track = document.getElementById('topicsProgressTrack');
        
        if (!prevBtn || !nextBtn || !track) return;
        
        const allTopics = @json($topics->values()->toArray());
        const totalTopics = allTopics.length;
        const segmentsToShow = 10;
        let currentIndex = 0;
        
        function updateSegments() {
            const endIndex = Math.min(currentIndex + segmentsToShow, totalTopics);
            
            // Clear current segments
            track.innerHTML = '';
            
            // Add new segments
            for (let i = currentIndex; i < endIndex; i++) {
                const topic = allTopics[i];
                if (!topic) continue;
                
                const isMastered = topic.user_progress && topic.user_progress.length > 0 && 
                                 topic.user_progress[0].percentage_grade >= 80;
                
                const segment = document.createElement('span');
                segment.className = 'topics-progress-segment' + (isMastered ? ' is-mastered' : '');
                segment.setAttribute('data-topic-id', topic.id);
                segment.setAttribute('data-index', i);
                segment.setAttribute('aria-hidden', 'true');
                
                track.appendChild(segment);
            }
            
            // Update button states
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = endIndex >= totalTopics;
            
            // Update button styles
            prevBtn.style.opacity = currentIndex === 0 ? '0.3' : '1';
            nextBtn.style.opacity = endIndex >= totalTopics ? '0.3' : '1';
        }
        
        prevBtn.addEventListener('click', function() {
            if (currentIndex > 0) {
                currentIndex = Math.max(0, currentIndex - segmentsToShow);
                updateSegments();
            }
        });
        
        nextBtn.addEventListener('click', function() {
            if (currentIndex + segmentsToShow < totalTopics) {
                currentIndex = Math.min(currentIndex + segmentsToShow, totalTopics - segmentsToShow);
                updateSegments();
            }
        });
        
        // Initialize button states
        updateSegments();
    })();

    /* Search Questions Feature */
    (function() {
        const searchInput = document.getElementById('courseQuestionSearchInput');
        const resultsContainer = document.getElementById('searchQuestionsResults');
        const spinner = document.getElementById('searchSpinner');
        const courseId = {{ $course->id }};
        let searchTimeout;

        if (!searchInput || !resultsContainer) return;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                resultsContainer.innerHTML = `
                    <div class="search-results-placeholder">
                        <i class="fas fa-keyboard mb-2"></i>
                        <p>Start typing to search keywords, concepts, or question text</p>
                    </div>
                `;
                return;
            }

            spinner.style.display = 'block';
            searchTimeout = setTimeout(() => performSearch(query), 500);
        });

        async function performSearch(query) {
            try {
                const response = await fetch(`/courses/${courseId}/search-questions?query=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                spinner.style.display = 'none';

                if (!data.success || !data.questions || data.questions.length === 0) {
                    resultsContainer.innerHTML = `
                        <div class="search-results-placeholder">
                            <i class="fas fa-search-minus mb-2"></i>
                            <p>No questions found matching "${query}"</p>
                        </div>
                    `;
                    return;
                }

                resultsContainer.innerHTML = data.questions.map(q => `
                    <div class="search-result-card" data-question-id="${q.id}">
                        <div class="search-result-content">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="search-result-question flex-grow-1 me-3">${q.question}</div>
                                <div class="search-result-status-action">
                                    ${q.is_already_mistake 
                                        ? '<div class="mistake-status-pill"><i class="fas fa-check-circle"></i></div>' 
                                        : `<button class="btn add-mistake-minimal-btn" data-id="${q.id}" title="Add to Mistakes"><i class="fas fa-plus"></i></button>`
                                    }
                                </div>
                            </div>
                            <div class="search-result-answer mb-3">
                                <span class="badge bg-success-subtle text-success me-3">Correct Answer</span>
                                <span class="fw-semibold text-dark">${q.correct_answer}</span>
                            </div>
                            <div class="search-result-explanation">
                                ${q.explanation}
                            </div>
                            ${q.topic_titles && q.topic_titles.length > 0 ? `
                                <div class="search-result-footer d-flex justify-content-end mt-3">
                                    <div class="search-result-meta d-flex gap-2 flex-wrap justify-content-end">
                                        ${q.topic_titles.map(title => `<span class="source-tag">${title}</span>`).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `).join('');

                // Attach events to Add to Mistakes buttons
                resultsContainer.querySelectorAll('.add-mistake-minimal-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const qId = this.dataset.id;
                        addQuestionToMistakes(qId, this);
                    });
                });

            } catch (error) {
                console.error('Search error:', error);
                spinner.style.display = 'none';
                resultsContainer.innerHTML = '<div class="alert alert-danger">Search failed. Please try again.</div>';
            }
        }

        async function addQuestionToMistakes(qId, button) {
            const originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            try {
                const response = await fetch(`/questions/${qId}/add-mistake`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Replace minimal button with check icon instantly
                    const parent = button.parentElement;
                    parent.innerHTML = '<div class="mistake-status-pill"><i class="fas fa-check-circle"></i></div>';
                    
                    showToast('Mistake Added', data.message, 'success');
                } else {
                    button.disabled = false;
                    button.innerHTML = originalContent;
                    showToast('Error', data.message || 'Failed to add mistake.', 'error');
                }
            } catch (error) {
                console.error('Add mistake error:', error);
                button.disabled = false;
                button.innerHTML = originalContent;
                showToast('Error', 'An error occurred. Please try again.', 'error');
            }
        }

        function showToast(title, message, type) {
            const toastStack = document.getElementById('taskToastStack');
            if (!toastStack) {
                alert(message);
                return;
            }

            const toast = document.createElement('div');
            toast.className = `task-toast ${type === 'error' ? 'toast-error' : ''}`;
            toast.innerHTML = `
                <div class="task-toast-icon">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check'}"></i>
                </div>
                <div class="task-toast-copy">
                    <span class="task-toast-kicker">${title}</span>
                    <strong class="task-toast-title">${message}</strong>
                </div>
            `;

            toastStack.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('is-visible'));
            setTimeout(() => {
                toast.classList.remove('is-visible');
                setTimeout(() => toast.remove(), 260);
            }, 3000);
        }
    })();
    </script>
</body>
</html>
