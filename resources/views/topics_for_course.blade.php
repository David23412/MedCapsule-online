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
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <!-- 🚀 OPTIMIZED: Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/topics.css?v=1.8">

    <!-- 🚀 CRITICAL CSS: Only essential styles for initial render -->
    <style>
        /* Only keep critical styles that aren't in the external CSS file */
        body{font-family:'Poppins',sans-serif;background-color:#f8f9fa;zoom:.72;margin:0;padding:0;line-height:1.6;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}

        .case-filter[data-filter="agamlo"],.case-filter[data-filter="lab"]{color:#7b1fa2}
        .case-filter[data-filter="easy"],.case-filter[data-filter="reading"]{color:#2e7d32}
        
        /* Modal backdrop - kept inline for performance */
        .modal-backdrop{background:rgba(0,0,0,.5)!important;position:fixed!important;top:0!important;left:0!important;width:100%!important;height:100%!important;z-index:1040!important;opacity:1!important}
        
        /* FORCE OVERRIDE: Assignment start button styling */
        .assignment-start-btn {
            background: #007bff !important;
            border: none !important;
            color: white !important;
            font-weight: 600 !important;
            user-select: none !important;
            -webkit-tap-highlight-color: transparent !important;
        }

        .assignment-start-btn:hover {
            background: #0056b3 !important;
            color: white !important;
        }

        .assignment-start-btn:active {
            background: #004085 !important;
        }

        /* FORCE OVERRIDE: Assignment Due label styling */
        .assignment-status-bar.assignment-active .assignment-info-minimal span:first-child {
            color: #e65100 !important;
        }
        
        /* ADDITIONAL OVERRIDE: Target the specific span element */
        .assignment-info-minimal span:first-child {
            color: #e65100 !important;
        }
        
        /* ULTIMATE OVERRIDE: Target by text content */
        .assignment-status-bar.assignment-active span:contains("Assignment Due:") {
            color: #e65100 !important;
        }
        
        /* FINAL OVERRIDE: Force assignment active status bar color */
        .assignment-status-bar.assignment-active {
            color: #e65100 !important;
        }
        
        .assignment-status-bar.assignment-active * {
            color: #e65100 !important;
        }

        /* Profile image styles */
        .profile-circle .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* Search bar styles */
        .search-container {
            padding: 0.5rem 1rem 0 1rem;
        }

        .search-wrapper {
            position: relative;
            max-width: 910px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 3rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 0.9rem;
            z-index: 1;
        }

        .clear-search-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }

        .clear-search-btn:hover {
            background-color: #f8f9fa;
            color: #495057;
        }

        /* Search highlighting */
        .search-highlight {
            background-color: #fff3cd; /* Light yellow */
            padding: 0 1px; /* Minimal padding */
            border-radius: 1px; /* Smoother integration */
            font-weight: inherit; /* Natural font weight */
            box-shadow: none; /* Remove any box-shadow if present */
        }

        /* Source selection modal styles from old.topics */
        #sourceSelectionModal .source-card {
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            background: transparent;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        #sourceSelectionModal .source-card:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        #sourceSelectionModal .source-card.selected {
            background-color: rgba(0, 123, 255, 0.03);
        }
        #sourceSelectionModal .source-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1a202c;
        }
        #sourceSelectionModal .source-status-text {
            font-size: 0.8rem;
            font-weight: 500;
        }
        #sourceSelectionModal .source-status-text.completed {
            color: #28a745;
        }
        #sourceSelectionModal .source-status-text.in-progress {
            color: #ffc107;
        }
        #sourceSelectionModal .source-status-text.failed {
            color: #dc3545;
        }
        #sourceSelectionModal .source-status-text.not-started {
            color: #6c757d;
        }
        #sourceSelectionModal .source-progress-bar {
            height: 4px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 0.5rem 0;
            position: relative;
        }
        #sourceSelectionModal .source-progress-value {
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }
        #sourceSelectionModal .source-progress-value.completed {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }
        #sourceSelectionModal .source-progress-value.in-progress {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }
        #sourceSelectionModal .source-progress-value.failed {
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
        }
        #sourceSelectionModal .source-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #sourceSelectionModal .questions-count {
            font-size: 0.8rem;
            color: #64748b;
        }
        #sourceSelectionModal .source-info-wrapper {
            width: 100%;
        }
        #sourceSelectionModal .form-check-input {
            margin-top: 0;
            cursor: pointer;
        }
        #sourceSelectionModal .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 3px;
        }
        #sourceSelectionModal .source-status.completed {
            background-color: #28a745;
        }
        #sourceSelectionModal .source-status.in-progress {
            background-color: #ffc107;
        }
        #sourceSelectionModal .source-status.failed {
            background-color: #dc3545;
        }
        #sourceSelectionModal .source-status.not-started {
            background-color: #e9ecef;
        }
    </style>
    
    {{-- Responsive Navigation CSS --}}
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/mobile-utils.css">
    <link rel="stylesheet" href="/css/responsive-navigation.css">
</head>
<body>
    {{-- Include Responsive Navigation --}}
    <x-responsive-nav-integration mode="replace" />

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
                
                @php
                    $profileImageUrl = Auth::user()->getProfileImageUrl();
                @endphp
                <a href="{{ route('profile') }}" class="profile-circle">
                    <img src="{{ $profileImageUrl }}"
                         alt="Profile Picture"
                         class="profile-image"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&size=200&background=007bff&color=ffffff'">
                </a>
            </div>
        </div>
    </nav>

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
                                        <p class="mb-0">No courses available. Please contact your administrator.</p>
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
                                <label for="sourceSelect" class="form-label mb-2">Select Sources</label>
                            </div>
                            <select class="form-select custom-select" id="sourceSelect" name="selected_sources[]" required>
                                <option value="">Select sources</option>
                                <!-- Sources will be loaded dynamically -->
                                <option disabled>Loading sources...</option>
                            </select>
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

    <!-- Source Selection Modal -->
    <div class="modal fade" id="sourceSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Select Question Sources</h5>
                            <p class="mb-0 text-muted small">Choose which sources to include in your quiz</p>
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
                        
                        <div class="source-info">
                            <div class="source-info-title">
                                <i class="fas fa-info-circle"></i>
                                <span>Tip</span>
                            </div>
                            <p class="source-info-text">
                                Complete all sources to master this topic. Progress is tracked for each source.
                            </p>
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
            <div class="progress-circle">
                @php
                    $masteredCount = 0;
                    foreach($topics as $topic) {
                        $userProgress = $topic->userProgress->first();
                        if ($userProgress && $userProgress->percentage_grade >= 80) {
                            $masteredCount++;
                        }
                    }
                @endphp
                {{ $masteredCount }}/{{ $topics->count() }}
            </div>
            <div class="progress-text">
                <div class="progress-text-title">Course Progress</div>
                <div class="progress-text-subtitle">Topics Mastered</div>
            </div>
        </div>
    </header>

    <div class="case-filters" data-course-type="{{ $course->type }}" data-topics="{{ $topics->toJson() }}">
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
                                {{ $assignment->title }}
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
                    // Check if topic is locked (you can customize this logic based on your requirements)
                    $isLocked = isset($topic->is_locked) ? $topic->is_locked : false;
                    // Alternative: Check if topic has a locked status
                    // $isLocked = $topic->status === 'locked';
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
                            <h3 class="topic-name">
                                <i class="fas fa-book-open"></i>
                                {{ $topic->name }}
                            </h3>
                            
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
                        @endphp
                        @if($percentageGrade !== null)
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
                            @elseif($percentageGrade !== null)
                                <i class="fas fa-rocket text-primary"></i>
                                Just getting started
                            @else
                                <i class="fas fa-flag text-primary"></i>
                                Ready to start
                            @endif
                        </div>
                        
                        @if($isLocked)
                            {{-- Show white button with red lock for locked topics --}}
                            <div class="topic-locked-btn" title="Topic is locked">
                                <i class="fas fa-lock"></i>
                            </div>
                        @else
                            @php
                                $quizUrl = secure_url(route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id], false));
                            @endphp
                            <a href="javascript:void(0)" class="start-quiz-btn" data-topic-id="{{ $topic->id }}" data-course-id="{{ $topic->course_id }}" data-quiz-url="{{ route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id]) }}">
                                @if($percentageGrade !== null)
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


    <script src="/js/topics-optimized.js?v=2.1"></script>

    {{-- REMOVED: quiz-modal.js to prevent conflicts - unified in main script --}}

    {{-- 🚀 OPTIMIZED: Efficient Topic Search & Filtering --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Element Caching --- //
            const searchInput = document.getElementById('topicSearch');
            const clearSearchBtn = document.getElementById('clearSearch');
            const topicsContainer = document.querySelector('.topics-container');
            const topicCards = topicsContainer.querySelectorAll('.topic-card');
            const caseFiltersContainer = document.querySelector('.case-filters');

            if (!searchInput || !topicsContainer || !caseFiltersContainer) {
                console.error('Search components not found. Aborting search script.');
                return;
            }

            // --- State Management --- //
            let searchTerm = '';

            // --- Utility Functions --- //
            const debounce = (func, delay) => {
                let timeoutId;
                return (...args) => {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => func.apply(this, args), delay);
                };
            };

            // --- Core Logic --- //
            const performSearch = () => {
                const normalizedSearchTerm = searchTerm.toLowerCase().trim();
                const activeFilter = caseFiltersContainer.querySelector('.case-filter.active');
                const activeCategory = activeFilter ? activeFilter.dataset.filter : 'all';

                let visibleTopics = 0;

                topicCards.forEach(card => {
                    const topicNameElement = card.querySelector('.topic-name');
                    const originalText = topicNameElement?.textContent || '';
                    const topicName = originalText.toLowerCase();
                    const cardCategory = card.dataset.caseType;

                    const categoryMatch = activeCategory === 'all' || cardCategory === activeCategory;
                    const searchMatch = normalizedSearchTerm === '' || topicName.includes(normalizedSearchTerm);

                    if (categoryMatch && searchMatch) {
                        card.style.display = '';

                        // Highlight matching text if there's a search term
                        if (normalizedSearchTerm && topicNameElement) {
                            const regex = new RegExp(`(${normalizedSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                            const highlightedText = originalText.replace(regex, '<mark class="search-highlight">$1</mark>');
                            topicNameElement.innerHTML = highlightedText;
                        } else if (topicNameElement) {
                            // Remove highlighting if no search term
                            topicNameElement.innerHTML = originalText;
                        }

                        visibleTopics++;
                    } else {
                        card.style.display = 'none';
                        // Also remove highlighting when card is hidden
                        if (topicNameElement) {
                            topicNameElement.innerHTML = originalText;
                        }
                    }
                });

                // Optional: Handle no results found state
                // You can add a message inside topicsContainer if visibleTopics === 0
            };

            const debouncedSearch = debounce(performSearch, 250);

            // --- Event Listeners --- //
            searchInput.addEventListener('input', () => {
                searchTerm = searchInput.value;
                clearSearchBtn.style.display = searchTerm ? 'flex' : 'none';
                debouncedSearch();
            });

            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchTerm = '';
                clearSearchBtn.style.display = 'none';
                performSearch(); // Perform search immediately on clear
                searchInput.focus();
            });

            caseFiltersContainer.addEventListener('click', (e) => {
                const target = e.target.closest('.case-filter');
                if (target) {
                    // When a filter is clicked, re-apply the current search term
                    setTimeout(performSearch, 0); // Use setTimeout to allow the UI to update first
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer crossorigin="anonymous"></script>
    
    <!-- Topic-specific JavaScript files -->
    <script>
        // Ensure countdown seconds are always visible and functional.
        document.addEventListener('DOMContentLoaded', function() {
            // 🚀 CACHED DOM ELEMENTS - DECLARE ONCE AT TOP
            const topicsContainer = document.querySelector('.topics-container');
            const caseFilters = document.querySelectorAll('.case-filter');
            const topicCards = document.querySelectorAll('.topic-card');
            const sourceModal = document.getElementById('sourceSelectionModal');
            const sourcesContainer = document.querySelector('.sources-container');
            const quizButtons = document.querySelectorAll('.start-quiz-btn');
            const sourceTopicId = document.getElementById('source_topic_id');
            const sourceCourseId = document.getElementById('source_course_id');
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const sourceSelectionForm = document.getElementById('sourceSelectionForm');
            const randomQuizForm = document.getElementById('randomQuizForm');
            const randomQuizModal = document.getElementById('randomQuizModal');
            const assignmentIconCircle = document.querySelector('.assignment-icon-circle');
            const assignmentCircle = document.querySelector('.assignment-circle');
            const questionLimit = document.getElementById('questionLimit');
            const timeLimit = document.getElementById('timeLimit');
            const courseCheckboxes = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]');
            const modals = document.querySelectorAll('.modal');

            // Search functionality elements
            const topicSearch = document.getElementById('topicSearch');
            const clearSearch = document.getElementById('clearSearch');

            // Search functionality
            function applyFiltersAndSearch() {
                const searchTerm = topicSearch.value.toLowerCase().trim();
                const activeFilter = document.querySelector('.case-filter.active')?.dataset.filter || 'all';
                const isAssignmentsFilterActive = topicsContainer?.classList.contains('assignments-filter-active');

                topicCards.forEach(card => {
                    const caseType = card.dataset.caseType;
                    const hasAssignment = card.dataset.hasAssignment === 'true';
                    const topicName = card.querySelector('.topic-name')?.textContent.toLowerCase() || '';

                    // First check filter criteria
                    let passesFilter = false;
                    if (isAssignmentsFilterActive) {
                        passesFilter = hasAssignment;
                    } else if (activeFilter === 'all') {
                        passesFilter = !hasAssignment;
                    } else {
                        passesFilter = !hasAssignment && caseType && caseType.toLowerCase() === activeFilter.toLowerCase();
                    }

                    // Then check search criteria (only if passes filter)
                    let passesSearch = true;
                    if (passesFilter && searchTerm) {
                        passesSearch = topicName.includes(searchTerm);
                    }

                    // Show/hide based on both filter and search
                    if (passesFilter && passesSearch) {
                        card.style.display = 'block';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Search input handler
            if (topicSearch) {
                topicSearch.addEventListener('input', function() {
                    // Show/hide clear button
                    clearSearch.style.display = this.value.trim() ? 'flex' : 'none';
                    // Apply filters and search
                    applyFiltersAndSearch();
                });
            }

            // Clear search button handler
            if (clearSearch) {
                clearSearch.addEventListener('click', function() {
                    topicSearch.value = '';
                    this.style.display = 'none';
                    applyFiltersAndSearch();
                    topicSearch.focus();
                });
            }
            
            // 🚨 CRITICAL FIX: Assignment icon click handler with null safety
            if (assignmentIconCircle && assignmentCircle) {
                assignmentIconCircle.addEventListener('click', function() {
                    // Remove active class from all filters
                    caseFilters.forEach(f => f.classList.remove('active'));

                    // Add active class to assignment circle
                    const circleElement = this.querySelector('.assignment-circle');
                    if (circleElement) {
                        circleElement.classList.add('active');
                    }

                    // Get the topics container
                    if (topicsContainer) {
                        topicsContainer.classList.add('assignments-filter-active');
                    }

                    // Apply filters and search
                    applyFiltersAndSearch();
                });
            }

            // Regular case filter functionality
            caseFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    // Remove active class from all filters
                    caseFilters.forEach(f => f.classList.remove('active'));

                    // Remove active class from assignment icon
                    if (assignmentCircle) {
                        assignmentCircle.classList.remove('active');
                    }

                    // Add active class to clicked filter
                    this.classList.add('active');

                    // Get the filter type
                    const filterType = this.dataset.filter || this.textContent.trim().toLowerCase();

                    // Add/remove assignments filter class
                    if (filterType === 'assignments') {
                        topicsContainer.classList.add('assignments-filter-active');
                    } else {
                        topicsContainer.classList.remove('assignments-filter-active');
                    }

                    // Apply filters and search
                    applyFiltersAndSearch();
                });
            });

            // 🚀 LETHAL COUNTDOWN OPTIMIZATION: Intersection Observer + Smart Intervals
            const countdownObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const countdown = entry.target;
                    if (entry.isIntersecting) {
                        startCountdown(countdown);
                    } else {
                        pauseCountdown(countdown);
                    }
                });
            }, { threshold: 0.1 });

            const activeCountdowns = new Map();
            
            function startCountdown(countdown) {
                if (activeCountdowns.has(countdown)) return;
                
                const updateInterval = setInterval(() => {
                    updateSingleCountdown(countdown);
                }, getUpdateFrequency(countdown));
                
                activeCountdowns.set(countdown, updateInterval);
                updateSingleCountdown(countdown); // Initial update
            }
            
            function pauseCountdown(countdown) {
                const interval = activeCountdowns.get(countdown);
                if (interval) {
                    clearInterval(interval);
                    activeCountdowns.delete(countdown);
                }
            }
            
            function getUpdateFrequency(countdown) {
                return 1000; // Always update every second for live countdown
            }
            
            function updateSingleCountdown(countdown) {
                const deadline = new Date(countdown.dataset.deadline);
                const now = new Date();
                const timeLeft = deadline - now;
                
                if (timeLeft <= 0) {
                    handleOverdueAssignment(countdown);
                    pauseCountdown(countdown);
                    return;
                }
                
                let countdownText = '';
                
                // Calculate total seconds remaining
                const totalSeconds = Math.floor(timeLeft / 1000);

                // Calculate days, hours, minutes, seconds
                const days = Math.floor(totalSeconds / (3600 * 24));
                const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                // Format hours, minutes, seconds with leading zeros
                let displayHours = hours;
                let ampm = '';

                // Convert hours to 12-hour format and determine AM/PM
                ampm = displayHours >= 12 ? 'PM' : 'AM';
                displayHours = displayHours % 12;
                displayHours = displayHours ? displayHours : 12; // The hour '0' should be '12'

                const formattedHours12 = String(displayHours).padStart(2, '0');
                const formattedMinutes = String(minutes).padStart(2, '0');
                const formattedSeconds = String(seconds).padStart(2, '0');

                if (days > 0) {
                    countdownText = `${days}d ${formattedHours12}:${formattedMinutes}:${formattedSeconds} ${ampm}`;
                } else {
                    countdownText = `${formattedHours12}:${formattedMinutes}:${formattedSeconds} ${ampm}`;
                }

                if (timeLeft < 3600000) { // Less than 1 hour
                    countdown.classList.add('countdown-urgent');
                } else {
                    countdown.classList.remove('countdown-urgent');
                }
                
                const display = countdown.querySelector('.countdown-display');
                if (display) display.textContent = countdownText;
                
                if (timeLeft < 3600000) {
                    countdown.classList.add('countdown-urgent');
                    const banner = countdown.closest('.assignment-status-banner');
                    if (banner) banner.style.animation = 'urgentBanner 1s infinite';
                }
            }
            
            function handleOverdueAssignment(countdown) {
                const topicCard = countdown.closest('.topic-card');
                if (!topicCard) return;
                
                topicCard.classList.add('topic-locked');
                
                const banner = topicCard.querySelector('.assignment-status-banner');
                if (banner) {
                    banner.className = 'assignment-status-banner assignment-overdue';
                    banner.innerHTML = `<div class="assignment-info overdue"><i class="fas fa-exclamation-triangle"></i><span class="assignment-label">ASSIGNMENT OVERDUE</span><span class="overdue-text">Topic Locked</span></div>`;
                }
                
                const startBtn = topicCard.querySelector('.start-quiz-btn');
                if (startBtn) {
                    Object.assign(startBtn.style, {
                        pointerEvents: 'none',
                        background: '#f1f3f4',
                        color: '#9ca3af',
                        border: '2px solid #e5e7eb',
                        opacity: '0.6',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    });
                    startBtn.innerHTML = '<i class="fas fa-lock lock-icon" style="color: #9ca3af; font-size: 1.2em; margin: 0;"></i>';
                }
            }
            
            // Initialize countdown observers
            document.querySelectorAll('.assignment-countdown-minimal').forEach(countdown => {
                countdownObserver.observe(countdown);
            });

            // Handle source progress updates from quiz completion
            document.addEventListener('sourceProgressUpdated', function(e) {
                const { topicId, progress, availableSources, allSourcesMastered, assignmentId, mode } = e.detail;
                
                // ALWAYS update source progress bars in the modal (for both quiz and tutor mode)
                if (progress) {
                    updateSourcesContainer({
                        progress: progress,
                        availableSources: availableSources
                    });
                }
                
                // CRITICAL: Only update topic card for quiz mode, NOT tutor mode
                if (mode !== 'tutor') {
                    // Update topic mastery status - FIXED: Only update the correct card type
                    let targetCard = null;
                    
                    if (assignmentId) {
                        // This is an assignment completion - find the specific assignment card
                        targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);

                    } else {
                        // This is regular quiz completion - find the regular topic card (not assignment cards)
                        targetCard = document.querySelector(`[data-topic-id="${topicId}"][data-has-assignment="false"]`);

                    }
                    
                    if (targetCard) {
                        const gradeBadge = targetCard.querySelector('.grade-badge');
                        if (gradeBadge) {
                            gradeBadge.className = `grade-badge ${allSourcesMastered ? 'grade-mastered' : 'grade-good'}`;
                            gradeBadge.innerHTML = allSourcesMastered ? 
                                '<i class="fas fa-star"></i> Mastered' : 
                                '<i class="fas fa-check"></i> Good';
                        }
                        
                        // Update assignment progress bar if this is an assignment completion
                        if (assignmentId) {
                            console.log('🎯 Assignment completion detected, updating progress for assignment:', assignmentId);
                            updateAssignmentProgress(assignmentId, targetCard);
                        }
                    }
                } else {
                    console.log('🎓 TUTOR MODE: Source modal updated, topic card unchanged');
                }
            });

            // 🚀 OPTIMIZED: Lazy modal initialization
            let sourceSelectionModal;
            function getModal() {
                if (!sourceSelectionModal && sourceModal) {
                    sourceSelectionModal = new bootstrap.Modal(sourceModal);
                    console.log('🚨 Modal initialized successfully:', sourceSelectionModal);
                }
                return sourceSelectionModal;
            }
            
            // Handle mode change
            document.querySelectorAll('input[name="quiz_mode"]').forEach(radio => {
                radio.addEventListener('change', async function() {
                    const topicId = sourceTopicId.value;
                    if (!topicId) return;
                    
                    sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                    try {
                        const response = await fetch(`/topics/${topicId}/source-progress?mode=${this.value}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken.content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        updateSourcesContainer(data);
                    } catch (error) {
                        sourcesContainer.innerHTML = '<div class="alert alert-danger">Error loading sources. Please try again.</div>';
                    }
                });
            });

            // Helper function to update assignment progress bar
            async function updateAssignmentProgress(assignmentId, targetCard) {
                console.log('🔄 Updating assignment progress for:', assignmentId);
                
                try {
                    // Fetch the latest assignment progress from the server
                    const response = await fetch(`/api/assignments/${assignmentId}/progress`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    console.log('📡 API Response status:', response.status);
                    
                    if (response.ok) {
                        const data = await response.json();
                        console.log('📊 Progress data received:', data);
                        
                        // Update the progress bar
                        const progressBar = targetCard.querySelector('.progress .progress-bar');
                        const progressLabel = targetCard.querySelector('.progress-label span:last-child');
                        
                        console.log('🎯 Found elements:', {
                            progressBar: !!progressBar,
                            progressLabel: !!progressLabel,
                            targetCard: !!targetCard
                        });
                        
                        if (progressBar && data.progress !== undefined) {
                            progressBar.style.width = `${data.progress}%`;
                            console.log('📊 Updated progress bar width to:', `${data.progress}%`);
                        }
                        
                        if (progressLabel && data.progress !== undefined) {
                            progressLabel.textContent = `${data.progress.toFixed(1)}%`;
                            console.log('📊 Updated progress label to:', `${data.progress.toFixed(1)}%`);
                        }
                        
                        console.log('✅ Assignment progress updated:', {
                            assignmentId: assignmentId,
                            progress: data.progress
                        });
                    } else {
                        console.error('❌ API response not ok:', response.status, response.statusText);
                    }
                } catch (error) {
                    console.error('❌ Failed to update assignment progress:', error);
                }
            }

            // Helper function to update sources container
            function updateSourcesContainer(data) {
                        sourcesContainer.innerHTML = '';
                        
                        if (!data.availableSources || data.availableSources.length === 0) {
                            sourcesContainer.innerHTML = '<div class="alert alert-info">No questions available for this topic.</div>';
                            return;
                        }
                        
                        data.availableSources.forEach(source => {
                            const progress = data.progress[source] || { completed: 0, total: 0, percentage: 0 };
                            const card = document.createElement('div');
                            card.className = 'source-card';
                            card.setAttribute('data-source', source);

                            // Add selected class if there are questions
                    if (progress.total_questions_available > 0) {
                                card.classList.add('selected');
                            }

                    // FIXED: Determine status class based on attempts and percentage
                            let statusClass, statusText, statusIcon;
                            
                            if (progress.has_attempts) {
                                // Source has been attempted
                                if (progress.percentage >= 90) {
                                    statusClass = 'completed';
                                    statusText = 'Completed';
                                    statusIcon = 'fas fa-check-circle';
                                } else if (progress.percentage >= 50) {
                                    statusClass = 'in-progress';
                                    statusText = 'In Progress';
                                    statusIcon = 'fas fa-clock';
                                } else {
                                    statusClass = 'failed';
                                    statusText = 'Failed';
                                    statusIcon = 'fas fa-times-circle';
                                }
                            } else {
                                // Source has not been attempted yet
                                statusClass = 'not-started';
                                statusText = 'Not started yet';
                                statusIcon = 'fas fa-circle';
                            }

                            card.innerHTML = `
                                <div class="source-status ${statusClass}"></div>
                                <div class="source-content">
                                    <div class="source-info-wrapper">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" name="sources[]" value="${source}" 
                                               class="form-check-input" ${progress.total_questions_available > 0 ? 'checked' : ''}>
                                                <span class="ms-2 source-name">${source}</span>
                                            </div>
                                    <div class="source-status-text ${statusClass}">
                                                <i class="${statusIcon} me-1"></i>${statusText}
                                            </div>
                                        </div>
                                        <div class="source-progress-bar">
                                    <div class="source-progress-value ${statusClass}" 
                                                 style="width: ${progress.percentage}%;">
                                            </div>
                                        </div>
                                        <div class="source-stats">
                                            <span class="questions-count">
                                        <i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;

                            sourcesContainer.appendChild(card);

                            // Add click handler for the entire card
                            card.addEventListener('click', function(e) {
                                if (e.target.type === 'checkbox') return;
                                const checkbox = this.querySelector('input[type="checkbox"]');
                                checkbox.checked = !checkbox.checked;
                                this.classList.toggle('selected', checkbox.checked);
                                
                                // Ensure at least one source is selected
                                const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                                if (!anyChecked) {
                                    checkbox.checked = true;
                                    this.classList.add('selected');
                                }
                            });
                        });
            }
            


            // =====================================================================
            // � UNIFIED QUIZ BUTTON HANDLER - THE ONLY HANDLER FOR QUIZ BUTTONS
            // =====================================================================
            // 
            // PURPOSE: Prevent conflicts by having ONE single handler for all quiz buttons
            // 
            // WHY THIS EXISTS: Previously had 3 competing handlers:
            // 1. topics-optimized.js (direct redirect - caused the bug)
            // 2. quiz-modal.js (proper modal handling)  
            // 3. Inline script in this file (also proper modal handling)
            // 
            // SOLUTION: Removed all conflicts and created this single, clean handler
            // 
            // LOGIC FLOW:
            // - Assignment cards (data-has-assignment="true") -> Direct redirect
            // - Regular topics -> Show source selection modal with Quiz/Tutor modes
            // 
            // ⚠️ IMPORTANT: If you need to modify quiz button behavior, 
            // modify ONLY THIS SECTION. Do not add handlers elsewhere!
            // =====================================================================
            
            document.addEventListener('click', function(e) {
                const button = e.target.closest('.start-quiz-btn');
                if (!button) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                // Prevent double-clicks
                if (button.dataset.processing === 'true') return;
                button.dataset.processing = 'true';
                
                const topicId = button.dataset.topicId;
                const courseId = button.dataset.courseId;
                const originalHref = button.dataset.quizUrl;
                
                // Show loading state
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                button.style.pointerEvents = 'none';
                
                // SIMPLIFIED LOGIC:
                // 1. Assignment cards -> Direct redirect (no modal needed)
                // 2. Regular topics -> Show modal for source/mode selection
                
                const isAssignmentCard = button.closest('[data-has-assignment="true"]');
                
                if (isAssignmentCard) {
                    // Assignment: Direct navigation
                    window.location.href = originalHref;
                    return;
                }
                
                // Regular topic: Show source selection modal
                if (!topicId || !courseId) {
                    // Fallback if data missing
                    window.location.href = originalHref;
                    return;
                }
                
                // Show modal for regular topics
                const modal = getModal();
                if (!modal) {
                    // Fallback if modal fails
                    window.location.href = originalHref;
                    return;
                }
                
                // Reset button state
                button.innerHTML = originalContent;
                button.style.pointerEvents = '';
                button.dataset.processing = 'false';
                
                // Configure and show modal
                sourceTopicId.value = topicId;
                sourceCourseId.value = courseId;
                sourceSelectionForm.action = originalHref;
                
                // Load sources and show modal
                fetch(`/topics/${topicId}/source-progress?mode=quiz`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (typeof updateSourcesContainer === 'function') {
                        updateSourcesContainer(data);
                    }
                    modal.show();
                })
                .catch(() => {
                    // On error, still show modal with fallback
                    modal.show();
                });
            });

            // Form submission
            sourceSelectionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const checkedSources = this.querySelectorAll('input[name="sources[]"]:checked');
                if (checkedSources.length === 0) {
                    alert('Please select at least one source to start the quiz.');
                    return;
                }

                const selectedMode = this.querySelector('input[name="quiz_mode"]:checked').value;
                const courseId = this.querySelector('#source_course_id').value;
                const topicId = this.querySelector('#source_topic_id').value;
                
                const selectedSources = Array.from(checkedSources).map(cb => cb.value);
                const sourceParams = selectedSources.map(s => `sources[]=${s}`).join('&');
                
                const baseUrl = selectedMode === 'tutor' 
                    ? `/quiz/${courseId}/${topicId}/tutor`
                    : `/quiz/${courseId}/${topicId}`;
                    
                window.location.href = `${baseUrl}?${sourceParams}`;
            });

            // Random Quiz Modal Event Handlers
            if (randomQuizModal) {
                // Load sources when modal is shown
                randomQuizModal.addEventListener('show.bs.modal', function() {
                    loadRandomQuizSources();
                });

                // Handle course selection changes
                courseCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        loadRandomQuizSources();
                    });
                });
            }

            // Function to load sources for selected courses
            async function loadRandomQuizSources() {
                const selectedCourses = Array.from(courseCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                const sourceSelect = document.getElementById('sourceSelect');

                if (selectedCourses.length === 0) {
                    // Clear options and show placeholder
                    sourceSelect.innerHTML = '<option value="">Select courses first</option>';
                    return;
                }

                // Clear current options and show loading
                sourceSelect.innerHTML = '<option disabled selected>Loading sources...</option>';

                try {
                    const queryParams = new URLSearchParams();
                    selectedCourses.forEach(courseId => {
                        queryParams.append('course_ids[]', courseId);
                    });

                    const response = await fetch('/api/courses-sources?' + queryParams.toString(), {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.content,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success && data.sources && data.sources.length > 0) {
                        renderRandomQuizSources(data.sources);
                    } else {
                        sourceSelect.innerHTML = '<option value="">No sources available</option>';
                    }
                } catch (error) {
                    console.error('Error loading sources:', error);
                    sourceSelect.innerHTML = '<option value="">Error loading sources</option>';
                }
            }

            // Function to render sources in the random quiz modal
            function renderRandomQuizSources(sources) {
                const sourceSelect = document.getElementById('sourceSelect');
                sourceSelect.innerHTML = '';

                if (sources.length === 0) {
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.textContent = 'No sources available';
                    sourceSelect.appendChild(option);
                    return;
                }

                // Add "All Sources" option first
                const allOption = document.createElement('option');
                allOption.value = 'all';
                allOption.textContent = 'All Sources';
                allOption.selected = true; // Default to all sources
                sourceSelect.appendChild(allOption);

                // Add individual sources
                sources.forEach(source => {
                    const option = document.createElement('option');
                    option.value = source.name;
                    option.textContent = `${source.name} (${source.question_count} questions)`;
                    sourceSelect.appendChild(option);
                });
            }

            // Random Quiz Modal Validation and Mode Handling
            if (randomQuizForm) {
                randomQuizForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Check if at least one course is selected
                    const selectedCourses = this.querySelectorAll('input[name="selected_courses[]"]:checked');

                    if (selectedCourses.length === 0) {
                        alert('Please select at least one course for the random quiz.');
                        return false;
                    }

                    // Check if a source is selected
                    const sourceSelect = document.getElementById('sourceSelect');
                    const selectedSource = sourceSelect.value;

                    if (!selectedSource || selectedSource === '') {
                        alert('Please select a source for the random quiz.');
                        return false;
                    }

                    // Validate question limit
                    const questionLimitValue = parseInt(questionLimit?.value || 10);
                    if (questionLimitValue < 1 || questionLimitValue > 100) {
                        alert('Please enter a valid question limit (1-100).');
                        return false;
                    }

                    // Validate time limit
                    const timeLimitValue = parseFloat(timeLimit?.value || 30);
                    if (timeLimitValue < 0.1 || timeLimitValue > 180) {
                        alert('Please enter a valid time limit (1-180 minutes).');
                        return false;
                    }

                    // Handle mode selection - CRITICAL FIX: Search entire modal, not just form
                    const modal = document.getElementById('randomQuizModal');
                    const selectedModeElement = modal.querySelector('input[name="quiz_mode"]:checked');
                    const selectedMode = selectedModeElement ? selectedModeElement.value : 'quiz';

                    console.log('🎯 RANDOM QUIZ MODE SELECTED:', selectedMode); // DEBUG LOG
                    console.log('🎯 SELECTED ELEMENT:', selectedModeElement); // DEBUG LOG

                    const formData = new FormData(this);
                    const params = new URLSearchParams();

                    // Add all form data to params
                    for (let [key, value] of formData.entries()) {
                        if (key === 'selected_courses[]') {
                            params.append('selected_courses[]', value);
                        } else if (key === 'selected_sources[]') {
                            params.append('selected_sources[]', value);
                        } else {
                            params.append(key, value);
                        }
                    }

                    // CRITICAL FIX: Ensure mode is properly included in params
                    params.set('quiz_mode', selectedMode);

                    // Determine the URL based on selected mode - FIXED: More explicit routing
                    let url;
                    if (selectedMode === 'tutor') {
                        url = '{{ route("quiz.random.tutor") }}?' + params.toString();
                        console.log('🎯 REDIRECTING TO TUTOR MODE:', url); // DEBUG LOG
                    } else {
                        url = '{{ route("quiz.random.show") }}?' + params.toString();
                        console.log('🎯 REDIRECTING TO QUIZ MODE:', url); // DEBUG LOG
                    }

                    // Redirect to the appropriate URL
                    window.location.href = url;
                });
            }
        });
    </script>
    
    <script>
        // Critical path JavaScript only
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize performance tracking
            if (window.performance && window.performance.mark) {
                window.performance.mark('topics-page-start');
            }

            // Apply saved filter state from URL or sessionStorage
            const urlParams = new URLSearchParams(window.location.search);
            const savedFilter = urlParams.get('filter') || sessionStorage.getItem('topicsFilter') || 'all';

            if (savedFilter !== 'all') {
                document.body.classList.add(`${savedFilter}-filter-active`);
            }
        });

        // Handle video play button clicks specifically
        function handleVideoPlay(event, topicId) {
            event.preventDefault();
            event.stopImmediatePropagation();

            console.log('🎬 Video button clicked for topic:', topicId);

            // Get the href from the clicked link
            const videoLink = event.target.closest('a');
            if (videoLink && videoLink.href) {
                console.log('🎯 Redirecting to:', videoLink.href);
                window.location.href = videoLink.href;
            } else {
                console.error('❌ Could not find video link');
            }

            return false;
        }

        // Add event listeners for video buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Find all video play buttons and add click handlers
            const videoButtons = document.querySelectorAll('.video-play-btn');
            videoButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    const href = this.getAttribute('href');
                    console.log('🎬 Video button clicked, redirecting to:', href);

                    if (href) {
                        window.location.href = href;
                    } else {
                        console.error('❌ No href found on video button');
                    }

                    return false;
                });
            });
        });
    </script>
</body>
</html>
