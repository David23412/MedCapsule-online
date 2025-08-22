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
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
                
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
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
                                @php
                                    $user = auth()->user();
                                    if ($user->role === 'admin') {
                                        $availableCourses = \App\Models\Course::orderBy('name')->get();
                                    } else {
                                        // Get courses the user is enrolled in through the pivot table
                                        $availableCourses = \App\Models\Course::whereHas('users', function($query) use ($user) {
                                            $query->where('user_id', $user->id);
                                        })->orderBy('name')->get();
                                        
                                        // If no enrolled courses found, get all courses (fallback)
                                        if ($availableCourses->isEmpty()) {
                                            $availableCourses = \App\Models\Course::orderBy('name')->get();
                                        }
                                    }
                                @endphp
                                @if($availableCourses->count() > 0)
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
                        <a href="{{ route('video.show', ['topic' => $topic->id]) }}" class="video-play-btn">
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
                    
                    // Filter topic cards to show only assignments
                    topicCards.forEach(card => {
                        const hasAssignment = card.dataset.hasAssignment === 'true';
                        
                        if (hasAssignment) {
                            card.style.display = 'block';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        } else {
                            card.style.display = 'none';
                        }
                    });
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
                    
                    // Filter topic cards
                    topicCards.forEach(card => {
                        const caseType = card.dataset.caseType;
                        const hasAssignment = card.dataset.hasAssignment === 'true';
                        
                        let shouldShow = false;
                        
                        if (filterType === 'assignments') {
                            // Only show assignment cards
                            shouldShow = hasAssignment;
                        } else if (filterType === 'all') {
                            // Show only regular topics, NOT assignment cards
                            shouldShow = !hasAssignment;
                        } else {
                            // Dynamic filtering based on case type (exclude assignment cards)
                            shouldShow = !hasAssignment && caseType && caseType.toLowerCase() === filterType.toLowerCase();
                        }
                        
                        if (shouldShow) {
                            card.style.display = 'block';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        } else {
                            card.style.display = 'none';
                        }
                    });
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
            


            // ROBUST: Handle quiz button clicks with multiple prevention methods

            
            // 🚀 ULTRA-FAST: Optimized quiz button handler - single click, instant response
            document.addEventListener('click', function(e) {
                // Use closest() to handle clicks on child elements (icons, spans)
                const button = e.target.closest('.start-quiz-btn');
                if (!button) return;
                
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Prevent double-clicks
                if (button.dataset.processing === 'true') return;
                button.dataset.processing = 'true';
                
                const topicId = button.dataset.topicId;
                const courseId = button.dataset.courseId;
                const originalHref = button.dataset.quizUrl;
                
                // Instant visual feedback
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                button.style.pointerEvents = 'none';
                
                // Check if assignments filter is active - direct redirect
                const isAssignmentsFilterActive = topicsContainer?.classList.contains('assignments-filter-active');
                
                if (isAssignmentsFilterActive) {
                    window.location.href = originalHref;
                    return;
                }
                
                // For regular topics, check if modal is needed
                if (!topicId || !courseId) {
                    window.location.href = originalHref;
                    return;
                }
                
                // Quick source check - if only one source, skip modal
                fetch(`/topics/${topicId}/source-progress?mode=quiz`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const availableSources = data.availableSources || [];
                    
                    // If only one source or no sources, redirect directly
                    if (availableSources.length <= 1) {
                        window.location.href = originalHref;
                        return;
                    }
                    
                    // Multiple sources - show modal
                    const modal = getModal();
                    if (!modal) {
                        window.location.href = originalHref;
                        return;
                    }
                    
                    // Reset button
                    button.innerHTML = originalContent;
                    button.style.pointerEvents = '';
                    button.dataset.processing = 'false';
                    
                    // Set form values
                    sourceTopicId.value = topicId;
                    sourceCourseId.value = courseId;
                    sourceSelectionForm.action = originalHref;
                    
                    // Update sources immediately
                    if (typeof updateSourcesContainer === 'function') {
                        updateSourcesContainer(data);
                    }
                    
                    // Show modal
                    sourceSelectionModal.show();
                })
                .catch(error => {
                    // On error, redirect directly
                    window.location.href = originalHref;
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

            // Random Quiz Modal Validation
            if (randomQuizForm) {
                randomQuizForm.addEventListener('submit', function(e) {
                    // Check if at least one course is selected
                    const selectedCourses = this.querySelectorAll('input[name="selected_courses[]"]:checked');
                    
                    if (selectedCourses.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one course for the random quiz.');
                        return false;
                    }
                    
                    // Validate question limit
                    const questionLimitValue = parseInt(questionLimit?.value || 10);
                    if (questionLimitValue < 1 || questionLimitValue > 100) {
                        e.preventDefault();
                        alert('Please enter a valid question limit (1-100).');
                        return false;
                    }
                    
                    // Validate time limit
                    const timeLimitValue = parseFloat(timeLimit?.value || 30);
                    if (timeLimitValue < 0.1 || timeLimitValue > 180) {
                        e.preventDefault();
                        alert('Please enter a valid time limit (1-180 minutes).');
                        return false;
                    }
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
    </script>
</body>
</html>