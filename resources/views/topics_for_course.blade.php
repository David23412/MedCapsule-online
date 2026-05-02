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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Disable source maps -->
    <meta name="devtools-disable-sourcemaps" content="true">
    <title>MedCapsule</title>
    @include('partials.favicon')
    @include('partials.admin-dark-mode-head')
    <!-- 🚀 OPTIMIZED: Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ $bladeAsset->url('css/topics.css') }}">

    <link rel="stylesheet" href="{{ $bladeAsset->url('css/topics-for-course-page.css') }}">
    
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
                            @foreach($topicsPageView['collapsed_task_dots'] as $isCompleted)
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
                    <div class="sidebar-game-plan-level">Lvl {{ $currentUserView['level'] }}</div>
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
                    <span>Focus: {{ $topicsPageView['planner_focus_label'] }}</span>
                    @if(!empty(($gamePlan['settings']['resolved_settings']['preferred_source'] ?? null)))
                        <span>Source: {{ $gamePlan['settings']['resolved_settings']['preferred_source'] }}</span>
                    @endif
                </div>

                <div class="sidebar-game-plan-meta" style="margin-top: 0.75rem; flex-direction: column; align-items: flex-start; gap: 0.35rem;">
                    <span>Today: {{ $topicsPageView['tracked_minutes_today'] }}/{{ max($topicsPageView['target_minutes_today'], 1) }} min</span>
                    <div style="width: 100%; height: 6px; border-radius: 999px; background: #dbeafe; overflow: hidden;">
                        <div style="height: 100%; width: {{ min(100, max(0, $topicsPageView['study_progress_percent'])) }}%; border-radius: 999px; background: linear-gradient(90deg, #2563eb, #60a5fa);"></div>
                    </div>
                    <span>{{ $gamePlan['daily_progress']['tracked_questions'] ?? 0 }} questions tracked today</span>
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

                @if($topicsPageView['can_view_planner_debug'] && is_array($topicsPageView['planner_debug']))
                    <details style="margin-top: 0.9rem; border: 1px solid rgba(37, 99, 235, 0.12); border-radius: 12px; background: rgba(239, 246, 255, 0.78);">
                        <summary style="cursor: pointer; padding: 0.75rem 0.9rem; font-size: 0.82rem; font-weight: 600; color: #1e3a8a;">
                            Planner Debug
                        </summary>
                        <div style="padding: 0 0.9rem 0.9rem; display: grid; gap: 0.8rem;">
                            <div style="font-size: 0.78rem; color: #1e40af;">
                                Frontier topic: {{ $topicsPageView['planner_debug']['frontier_topic_id'] ?? 'none' }}<br>
                                Frontier stage: {{ $topicsPageView['planner_debug']['frontier_stage'] ?? 'none' }}
                            </div>

                            <div>
                                <div style="font-size: 0.76rem; font-weight: 700; color: #1f2937; margin-bottom: 0.35rem;">Visible Tasks</div>
                                @forelse(($topicsPageView['planner_debug']['tasks'] ?? []) as $debugTask)
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
                                @foreach(($topicsPageView['planner_debug']['topics'] ?? []) as $debugTopic)
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

        @include('partials.student-sidebar-profile', [
            'profileUrl' => route('profile'),
            'profileName' => $currentUserView['name'],
            'profileMeta' => 'Study profile',
            'profileImageUrl' => $currentUserView['profile_image_url'],
            'profileFallbackUrl' => $currentUserView['profile_fallback_url'],
            'logoutAction' => secure_url(route('logout', [], false)),
        ])
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

    @if($hasFullCourseAccess)
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
    @endif

    @if($currentUserView['is_admin'])
        <div class="sq-edit-overlay" id="sq-edit-overlay">
            <div class="sq-edit-modal">
                <div class="sq-header">
                    <h5><i class="fas fa-pen"></i> Edit Question</h5>
                    <button class="sq-close-btn" onclick="sqCloseModal()">&times;</button>
                </div>

                <div class="sq-body">
                    <input type="hidden" id="sq-q-id">
                    <input type="hidden" id="sq-q-type" value="multiple_choice">

                    <div class="sq-field">
                        <label><i class="fas fa-question-circle" style="color:#2563eb"></i> Question Text</label>
                        <textarea id="sq-q-text" class="sq-input" rows="4" placeholder="Edit question text..."></textarea>
                    </div>

                    <div class="sq-type-switch">
                        <button type="button" class="sq-type-btn active" id="sq-type-mcq" onclick="sqSwitchType('multiple_choice')">
                            <i class="fas fa-list-ul"></i> Multiple Choice
                        </button>
                        <button type="button" class="sq-type-btn" id="sq-type-written" onclick="sqSwitchType('written')">
                            <i class="fas fa-pen"></i> Written
                        </button>
                    </div>

                    <div id="sq-mcq-section">
                        <div class="sq-field">
                            <label><i class="fas fa-a" style="color:#10b981"></i> Option A</label>
                            <input id="sq-oa" class="sq-input" type="text" placeholder="Option A">
                        </div>
                        <div class="sq-field">
                            <label><i class="fas fa-b" style="color:#f59e0b"></i> Option B</label>
                            <input id="sq-ob" class="sq-input" type="text" placeholder="Option B">
                        </div>
                        <div class="sq-field">
                            <label><i class="fas fa-c" style="color:#6366f1"></i> Option C</label>
                            <input id="sq-oc" class="sq-input" type="text" placeholder="Option C">
                        </div>
                        <div class="sq-field">
                            <label><i class="fas fa-d" style="color:#ef4444"></i> Option D</label>
                            <input id="sq-od" class="sq-input" type="text" placeholder="Option D">
                        </div>

                        <div class="sq-field">
                            <label><i class="fas fa-check" style="color:#16a34a"></i> Correct Answer</label>
                            <div class="sq-correct-grid">
                                <label class="sq-correct-option"><input type="radio" name="sq-correct" id="sq-ca" value="A"> A</label>
                                <label class="sq-correct-option"><input type="radio" name="sq-correct" id="sq-cb" value="B"> B</label>
                                <label class="sq-correct-option"><input type="radio" name="sq-correct" id="sq-cc" value="C"> C</label>
                                <label class="sq-correct-option"><input type="radio" name="sq-correct" id="sq-cd" value="D"> D</label>
                            </div>
                        </div>
                    </div>

                    <div id="sq-written-section" style="display:none;">
                        <div class="sq-field">
                            <label><i class="fas fa-check" style="color:#16a34a"></i> Correct Answer</label>
                            <textarea id="sq-correct-text" class="sq-input" rows="3" placeholder="Enter the expected correct answer..."></textarea>
                        </div>
                        <div class="sq-field">
                            <label><i class="fas fa-random" style="color:#7c3aed"></i> Alternative Answers <span style="font-weight:400;color:#94a3b8">(one per line, optional)</span></label>
                            <textarea id="sq-alt-answers" class="sq-input" rows="3" placeholder="Enter alternative answers, one per line..."></textarea>
                        </div>
                    </div>

                    <div class="sq-field">
                        <label><i class="fas fa-lightbulb" style="color:#d97706"></i> Explanation <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                        <textarea id="sq-explanation" class="sq-input" rows="3" placeholder="Add an explanation..."></textarea>
                    </div>
                </div>

                <div class="sq-del-confirm" id="sq-del-confirm">
                    <p><i class="fas fa-exclamation-triangle"></i> Are you sure you want to permanently delete this question? This cannot be undone.</p>
                    <div class="sq-del-actions">
                        <button class="sq-btn sq-btn-delete" id="sq-confirm-del" onclick="sqConfirmDelete()"><i class="fas fa-trash"></i> Yes, Delete</button>
                        <button class="sq-btn sq-btn-cancel" onclick="sqCancelDelete()">Cancel</button>
                    </div>
                </div>

                <div class="sq-status" id="sq-status"></div>

                <div class="sq-footer">
                    <div class="sq-footer-left">
                        <button class="sq-btn sq-btn-delete" id="sq-del-btn" onclick="sqPromptDelete()">
                            <i class="fas fa-trash-alt"></i> Delete Question
                        </button>
                    </div>
                    <div class="sq-footer-right">
                        <button class="sq-btn sq-btn-cancel" onclick="sqCloseModal()">Cancel</button>
                        <button class="sq-btn sq-btn-save" id="sq-save-btn" onclick="sqSaveEdit()">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                                <input class="form-check-input" type="radio" name="quiz_mode" id="readMode" value="read">
                                <label class="form-check-label" for="readMode">Read Mode</label>
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
                                    Continue mistakes
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tutor_scope" id="tutorScopeFull" value="full">
                                <label class="form-check-label" for="tutorScopeFull">
                                    Retry
                                </label>
                            </div>
                            <div class="small text-muted mt-2" id="tutorScopeHint">
                                Continue with the questions you still have wrong, or start again.
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
                    <span class="progress-count">{{ $topicsPageView['mastered_count'] }} of {{ $topics->count() }} complete</span>
                    <span class="progress-percent">{{ $topicsPageView['mastered_percentage'] }}%</span>
                </div>
            </div>
            <div class="topics-progress-container" aria-label="Topics mastered progress">
                <div class="topics-progress-navigation">
                    <button class="progress-nav-btn progress-nav-prev" id="progressPrevBtn" aria-label="Previous topics">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="topics-progress-track" id="topicsProgressTrack">
                        @foreach($topicsPageView['topic_progress_segments'] as $segment)
                            <span
                                class="topics-progress-segment{{ $segment['is_mastered'] ? ' is-mastered' : '' }}"
                                data-topic-id="{{ $segment['id'] }}"
                                data-index="{{ $segment['index'] }}"
                                aria-hidden="true"
                            ></span>
                        @endforeach
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
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @foreach($topicsPageView['category_options'] as $category)
                <div class="case-filter" data-filter="{{ $category['filter'] }}">
                    <i class="{{ $category['icon'] }}"></i>
                    {{ $category['label'] }}
                    <span class="count">{{ $category['count'] }}</span>
                </div>
            @endforeach
        @else
            {{-- Always show "All" pill for navigation --}}
            <div class="case-filter active" data-filter="all">
                <i class="fas fa-th-large"></i>
                All
                <span class="count">{{ $topics->count() }}</span>
            </div>
            
            @foreach($topicsPageView['category_options'] as $category)
                <div class="case-filter" data-filter="{{ $category['filter'] }}">
                    <i class="{{ $category['icon'] }}"></i>
                    {{ $category['label'] }}
                    <span class="count">{{ $category['count'] }}</span>
                </div>
            @endforeach
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
        @if($hasFullCourseAccess)
            <a href="#" class="search-icon-bubble" data-bs-toggle="modal" data-bs-target="#searchQuestionsModal" title="Search Questions" style="order: 101;">
                <i class="fas fa-search"></i>
            </a>
        @endif
        
        {{-- Assignment Icon in Red Circle - Only show if there are assignments --}}
        @if($topicsPageView['assignments_count'] > 0)
            <div class="assignment-icon-circle" data-filter="assignments" style="order: 100;">
                <div class="assignment-circle">
                    <i class="fas fa-tasks"></i>
                    <span class="assignment-glow-count">{{ $topicsPageView['assignments_count'] }}</span>
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
        @if($assignments->count() > 0)
            @foreach($assignments as $assignment)
                @php($data = $assignmentViewData[$assignment->id] ?? null)
                @continue(!$data)
                
                <div class="topic-card assignment-{{ $data['display_state'] }}" 
                     data-case-type="assignment" 
                     data-topic-id="{{ $data['topic']->id }}"
                     data-assignment-id="{{ $assignment->id }}"
                     data-has-assignment="true" 
                     data-assignment-deadline="{{ $assignment->deadline }}" 
                     data-assignment-status="{{ $data['status'] }}">
                    
                    {{-- Assignment Status Bar --}}
                    <div class="assignment-status-bar assignment-{{ $assignment->isCompletedByUser(auth()->id()) ? 'completed' : ($data['status'] === 'overdue' ? 'overdue' : 'active') }}">
                        <div class="assignment-info-minimal">
                            @if($data['status'] === 'active' && !$data['is_locked'])
                                <span>Assignment Due:</span>
                                <div class="assignment-countdown-minimal" 
                                     data-deadline="{{ $assignment->deadline }}"
                                     data-assignment-status="{{ $data['status'] }}"
                                     data-is-locked="{{ $data['is_locked'] ? 'true' : 'false' }}">
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
                            @if($data['is_completed'])
                                <i class="fas fa-check-circle text-success"></i>Assignment Completed
                            @elseif($data['is_locked'] || $data['status'] === 'overdue')
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
                        @elseif($data['is_locked'])
                            {{-- RED LOCK for locked assignments --}}
                            <div class="assignment-locked-icon" title="Assignment not available">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            <a href="javascript:void(0)" class="start-quiz-btn assignment-start-btn" data-topic-id="{{ $data['topic']->id }}" data-course-id="{{ $data['topic']->course_id }}" data-quiz-url="{{ $data['quiz_url'] }}">
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
                @php($topicView = $topicCardData[$topic->id] ?? null)
                
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
                        
                        @php($percentageGrade = $topicView['percentage_grade'] ?? null)
                        @php($hasAttemptedTopic = $topicView['has_attempted'] ?? false)
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
                        
                        @if($topic->is_locked)
                            {{-- Show white button with red lock for locked topics --}}
                            <div class="topic-locked-btn" title="{{ ($topicView['is_preview_locked'] ?? false) ? 'Unlock the full course to access this topic' : 'Topic is locked' }}">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
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
                    @if($topic->video && !$topic->is_locked)
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


    <script src="{{ $bladeAsset->url('js/topics-optimized.js') }}"></script>

    {{-- REMOVED: quiz-modal.js to prevent conflicts - unified in main script --}}

    <script>
        window.TopicsPageConfig = {
            randomQuizRoute: @json(route('quiz.random.show')),
            randomTutorRoute: @json(route('quiz.random.tutor')),
            recentProgressRoute: @json(route('progress.recent-updates')),
            progressTopics: @json($topics->values()->toArray()),
            taskCelebration: @json([
                'awarded_now' => $gamePlan['awarded_now'] ?? 0,
                'just_completed_tasks' => $gamePlan['just_completed_tasks'] ?? [],
            ])
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer crossorigin="anonymous"></script>
    <script src="{{ asset('js/topics-for-course-page.js') }}" defer></script>
    <script>
    @if($hasFullCourseAccess)
        /* Search Questions Feature */
        (function() {
            const searchInput = document.getElementById('courseQuestionSearchInput');
            const resultsContainer = document.getElementById('searchQuestionsResults');
            const spinner = document.getElementById('searchSpinner');
            const courseId = {{ $course->id }};
            const canEditQuestions = {{ $currentUserView['is_admin'] ? 'true' : 'false' }};
            const searchQuestionData = new Map();
            let searchTimeout;

            if (!searchInput || !resultsContainer) return;

            resultsContainer.classList.add('search-results-protected');

            const clearSearchSelection = () => {
                const selection = window.getSelection ? window.getSelection() : null;
                if (!selection || selection.isCollapsed) {
                    return;
                }

                selection.removeAllRanges();
            };

            const isProtectedSearchTarget = (target) => {
                return target instanceof HTMLElement
                    && Boolean(target.closest('#searchQuestionsResults'))
                    && !target.closest('button, input, textarea, select, option, [contenteditable=""], [contenteditable="true"]');
            };

            ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach((eventName) => {
                resultsContainer.addEventListener(eventName, (event) => {
                    if (!isProtectedSearchTarget(event.target)) {
                        return;
                    }

                    if (eventName === 'copy' || eventName === 'cut') {
                        try {
                            event.clipboardData?.setData('text/plain', 'Copying course search content is disabled.');
                        } catch (error) {
                            // Ignore clipboard access failures.
                        }
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation?.();
                    clearSearchSelection();
                }, true);
            });

            resultsContainer.addEventListener('keydown', (event) => {
                const key = String(event.key || '').toLowerCase();
                const withModifier = event.ctrlKey || event.metaKey;

                if (withModifier && ['a', 'c', 'x'].includes(key)) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation?.();
                    clearSearchSelection();
                }
            }, true);

            resultsContainer.addEventListener('mouseup', clearSearchSelection, true);
            resultsContainer.addEventListener('touchend', clearSearchSelection, true);

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

                    data.questions.forEach((question) => {
                        searchQuestionData.set(String(question.id), question);
                    });

                    resultsContainer.innerHTML = data.questions.map(q => `
                        <div class="search-result-card" data-question-id="${q.id}">
                            <div class="search-result-content">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="search-result-question flex-grow-1 me-3">${q.question}</div>
                                    <div class="search-result-status-action d-flex align-items-start gap-2">
                                        ${canEditQuestions
                                            ? `<button class="btn btn-outline-primary btn-sm search-edit-question-btn" data-id="${q.id}" title="Edit Question"><i class="fas fa-pen"></i></button>`
                                            : ''
                                        }
                                        ${q.is_already_mistake
                                            ? '<div class="mistake-status-pill"><i class="fas fa-check-circle"></i></div>'
                                            : `<button class="btn add-mistake-minimal-btn" data-id="${q.id}" title="Add to Mistakes"><i class="fas fa-plus"></i></button>`
                                        }
                                    </div>
                                </div>
                                <div class="search-result-answer mb-3">
                                    <span class="badge bg-success-subtle text-success me-3" style="align-self: flex-start; margin-top: 5px;">Correct Answer</span>
                                    <div class="fw-semibold text-dark search-result-answer-text">${q.correct_answer}</div>
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

                    resultsContainer.querySelectorAll('.add-mistake-minimal-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const qId = this.dataset.id;
                            addQuestionToMistakes(qId, this);
                        });
                    });

                    if (canEditQuestions) {
                        resultsContainer.querySelectorAll('.search-edit-question-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                openSearchQuestionEditModal(this.dataset.id);
                            });
                        });
                    }

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

            if (!canEditQuestions) {
                return;
            }

            const editBaseUrl = '/quiz/tutor/question/';
            const editCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            window.openSearchQuestionEditModal = function(qId) {
                const data = searchQuestionData.get(String(qId));
                if (!data) {
                    showToast('Error', 'Question data not available.', 'error');
                    return;
                }

                document.getElementById('sq-q-id').value = qId;
                document.getElementById('sq-q-text').value = data.question || '';
                document.getElementById('sq-explanation').value = data.explanation === 'No explanation available.' ? '' : (data.explanation || '');
                document.getElementById('sq-status').textContent = '';
                document.getElementById('sq-status').className = 'sq-status';
                document.getElementById('sq-del-confirm').classList.remove('show');
                document.getElementById('sq-save-btn').disabled = false;
                document.getElementById('sq-del-btn').style.display = '';

                sqSwitchType(data.question_type === 'written' ? 'written' : 'multiple_choice', true, data);

                document.getElementById('sq-edit-overlay').classList.add('show');
                document.body.style.overflow = 'hidden';
            };

            window.sqSwitchType = function(type, populate, data) {
                document.getElementById('sq-q-type').value = type;
                const mcqButton = document.getElementById('sq-type-mcq');
                const writtenButton = document.getElementById('sq-type-written');
                const mcqSection = document.getElementById('sq-mcq-section');
                const writtenSection = document.getElementById('sq-written-section');

                if (type === 'written') {
                    mcqButton.classList.remove('active');
                    writtenButton.classList.add('active');
                    mcqSection.style.display = 'none';
                    writtenSection.style.display = 'block';

                    if (populate && data) {
                        document.getElementById('sq-correct-text').value = data.raw_correct_answer || '';
                        document.getElementById('sq-alt-answers').value = data.alternative_answers || '';
                    }
                    return;
                }

                writtenButton.classList.remove('active');
                mcqButton.classList.add('active');
                writtenSection.style.display = 'none';
                mcqSection.style.display = 'block';

                if (populate && data) {
                    document.getElementById('sq-oa').value = data.option_a || '';
                    document.getElementById('sq-ob').value = data.option_b || '';
                    document.getElementById('sq-oc').value = data.option_c || '';
                    document.getElementById('sq-od').value = data.option_d || '';
                    const radio = document.getElementById('sq-c' + String(data.raw_correct_answer || '').toLowerCase());
                    if (radio) {
                        radio.checked = true;
                    }
                }
            };

            window.sqCloseModal = function() {
                document.getElementById('sq-edit-overlay').classList.remove('show');
                document.body.style.overflow = '';
            };

            window.sqPromptDelete = function() {
                document.getElementById('sq-del-confirm').classList.add('show');
                document.getElementById('sq-save-btn').disabled = true;
                document.getElementById('sq-del-btn').style.display = 'none';
            };

            window.sqCancelDelete = function() {
                document.getElementById('sq-del-confirm').classList.remove('show');
                document.getElementById('sq-save-btn').disabled = false;
                document.getElementById('sq-del-btn').style.display = '';
            };

            window.sqSaveEdit = async function() {
                const qId = document.getElementById('sq-q-id').value;
                const qType = document.getElementById('sq-q-type').value;
                const saveBtn = document.getElementById('sq-save-btn');
                const questionText = document.getElementById('sq-q-text').value.trim();

                if (!questionText) {
                    sqSetStatus('Question text is required.', 'error');
                    return;
                }

                let payload = {
                    _method: 'PUT',
                    question: questionText,
                    question_type: qType,
                    explanation: document.getElementById('sq-explanation').value.trim()
                };

                if (qType === 'written') {
                    const correctAnswer = document.getElementById('sq-correct-text').value.trim();
                    if (!correctAnswer) {
                        sqSetStatus('Correct answer is required.', 'error');
                        return;
                    }

                    payload.correct_answer = correctAnswer;
                    payload.alternative_answers = document.getElementById('sq-alt-answers').value.trim();
                } else {
                    const checked = document.querySelector('input[name="sq-correct"]:checked');
                    if (!checked) {
                        sqSetStatus('Please select the correct answer.', 'error');
                        return;
                    }

                    payload.correct_answer = checked.value;
                    payload.option_a = document.getElementById('sq-oa').value.trim();
                    payload.option_b = document.getElementById('sq-ob').value.trim();
                    payload.option_c = document.getElementById('sq-oc').value.trim();
                    payload.option_d = document.getElementById('sq-od').value.trim();
                }

                saveBtn.disabled = true;
                sqSetStatus('Saving…', '');

                try {
                    const response = await fetch(editBaseUrl + qId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': editCsrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (!data.success) {
                        sqSetStatus(data.message || 'Error saving.', 'error');
                        saveBtn.disabled = false;
                        return;
                    }

                    const cachedQuestion = searchQuestionData.get(String(qId)) || {};
                    const updatedQuestion = {
                        ...cachedQuestion,
                        question: payload.question,
                        question_type: payload.question_type,
                        explanation: payload.explanation || 'No explanation available.',
                        raw_correct_answer: payload.correct_answer,
                        alternative_answers: payload.alternative_answers || '',
                        option_a: payload.option_a || '',
                        option_b: payload.option_b || '',
                        option_c: payload.option_c || '',
                        option_d: payload.option_d || ''
                    };

                    if (payload.question_type === 'written') {
                        updatedQuestion.correct_answer = payload.correct_answer;
                    } else {
                        const optionMap = {
                            A: payload.option_a || '',
                            B: payload.option_b || '',
                            C: payload.option_c || '',
                            D: payload.option_d || ''
                        };
                        updatedQuestion.correct_answer = `${payload.correct_answer}: ${optionMap[payload.correct_answer] || ''}`.trim();
                    }

                    searchQuestionData.set(String(qId), updatedQuestion);
                    updateSearchQuestionCard(qId, updatedQuestion);
                    sqSetStatus('✓ Saved!', 'success');
                    setTimeout(sqCloseModal, 900);
                } catch (error) {
                    sqSetStatus('Network error: ' + error.message, 'error');
                    saveBtn.disabled = false;
                }
            };

            window.sqConfirmDelete = async function() {
                const qId = document.getElementById('sq-q-id').value;
                const button = document.getElementById('sq-confirm-del');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
                sqSetStatus('Deleting…', '');

                try {
                    const response = await fetch(editBaseUrl + qId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': editCsrf,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!data.success) {
                        sqSetStatus(data.message || 'Error deleting.', 'error');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                        return;
                    }

                    searchQuestionData.delete(String(qId));
                    document.querySelector(`.search-result-card[data-question-id="${qId}"]`)?.remove();
                    sqSetStatus('✓ Deleted.', 'success');
                    setTimeout(sqCloseModal, 800);
                } catch (error) {
                    sqSetStatus('Network error: ' + error.message, 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                }
            };

            function updateSearchQuestionCard(qId, question) {
                const card = document.querySelector(`.search-result-card[data-question-id="${qId}"]`);
                if (!card) {
                    return;
                }

                const questionText = card.querySelector('.search-result-question');
                const answerText = card.querySelector('.search-result-answer-text');
                const explanationText = card.querySelector('.search-result-explanation');

                if (questionText) {
                    questionText.textContent = question.question || '';
                }

                if (answerText) {
                    answerText.textContent = question.correct_answer || '';
                }

                if (explanationText) {
                    explanationText.textContent = question.explanation || 'No explanation available.';
                }
            }

            function sqSetStatus(message, className) {
                const status = document.getElementById('sq-status');
                status.textContent = message;
                status.className = 'sq-status' + (className ? ' ' + className : '');
            }

            document.getElementById('sq-edit-overlay')?.addEventListener('click', (event) => {
                if (event.target.id === 'sq-edit-overlay') {
                    sqCloseModal();
                }
            });
        })();
    @endif
    </script>
</body>
</html>
