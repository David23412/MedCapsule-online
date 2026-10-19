<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    <!-- RUTHLESS: Resource hints for faster loading -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- RUTHLESS: Async loading for better performance -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.admin-dark-mode-head')
    <link rel="stylesheet" href="{{ asset('css/profile-page.css') }}">
    <style>
        body {
            zoom: 0.75;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }
        @media (max-width: 850px) {
            body { zoom: 1 !important; }
        }

        /* Leaderboard privacy toggle — inline fallback */
        .privacy-control {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: #f1f5f9;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .privacy-control:hover {
            background: #e2e8f0;
        }
        .privacy-control-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            user-select: none;
            white-space: nowrap;
        }
        .privacy-switch {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 20px;
            overflow: hidden;
            vertical-align: middle;
            cursor: pointer;
            flex-shrink: 0;
        }
        .privacy-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
            margin: 0;
            padding: 0;
            border: 0;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            pointer-events: none;
        }
        .privacy-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 20px;
        }
        .privacy-slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .privacy-switch input:checked + .privacy-slider {
            background-color: #10b981;
        }
        .privacy-switch input:checked + .privacy-slider:before {
            transform: translateX(16px);
        }
        .privacy-control.updating {
            opacity: 0.6;
            pointer-events: none;
        }
        .profile-toggle-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <!-- Sidebar -->
    <div class="sidebar">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="true">
            <i class="fas fa-chevron-left"></i>
        </button>

        <!-- Logo -->
        <a href="{{ route('courses.index') }}" class="sidebar-logo">
            <!-- MedCapsule Capsule Pill Logo — glossy 3D effect -->
            <div class="sidebar-logo-icon">
                <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
                    <defs>
                        <linearGradient id="pillBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb"/>
                            <stop offset="45%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#1d4ed8"/>
                        </linearGradient>
                        <linearGradient id="pillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0)"/>
                            <stop offset="30%" stop-color="rgba(255,255,255,0.6)"/>
                            <stop offset="70%" stop-color="rgba(255,255,255,0.5)"/>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                        </linearGradient>
                        <linearGradient id="pillShadow" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgba(0,0,0,0)"/>
                            <stop offset="100%" stop-color="rgba(10,20,80,0.28)"/>
                        </linearGradient>
                        <filter id="pillDrop" x="-8%" y="-15%" width="116%" height="150%">
                            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"/>
                        </filter>
                    </defs>
                    <!-- Pill body -->
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#pillBody)" filter="url(#pillDrop)"/>
                    <!-- Underside depth -->
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#pillShadow)"/>
                    <!-- Gloss highlight -->
                    <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#pillGloss)" opacity="0.88"/>
                    <!-- Specular dot -->
                    <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"/>
                </svg>
            </div>
            <div class="sidebar-logo-copy">
                <div class="sidebar-logo-text">MedCapsule</div>
                <div class="sidebar-logo-sub">Study Platform</div>
            </div>
        </a>


        <!-- Main Navigation -->
        <div class="sidebar-nav">
            <span class="sidebar-section-label">Navigation</span>

            <a class="nav-link" href="{{ route('courses.index') }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-link-label">Home</span>
            </a>
            <a class="nav-link" href="{{ route('courses.index') }}">
                <i class="fas fa-book-open nav-icon"></i>
                <span class="nav-link-label">My Courses</span>
            </a>
            <a class="nav-link active" href="{{ route('profile') }}">
                <i class="fas fa-gear nav-icon"></i>
                <span class="nav-link-label">Settings</span>
            </a>
            <a class="nav-link" href="{{ route('review.index') }}">
                <i class="fas fa-rotate-right nav-icon"></i>
                <span class="nav-link-label">Review Mistakes</span>
            </a>

            @if(auth()->user()->isAdmin())
            <span class="sidebar-section-label">Admin</span>

            <a class="nav-link" href="{{ route('admin.user-course') }}">
                <i class="fas fa-users-cog nav-icon"></i>
                <span class="nav-link-label">Manage Access</span>
                <span class="sidebar-admin-badge">Admin</span>
            </a>
            <a class="nav-link" href="{{ route('add-topic.form') }}">
                <i class="fas fa-plus-circle nav-icon"></i>
                <span class="nav-link-label">Add Topic</span>
            </a>
            <a class="nav-link" href="{{ route('add-question.form') }}">
                <i class="fas fa-circle-question nav-icon"></i>
                <span class="nav-link-label">Add Question</span>
            </a>
            <a class="nav-link" href="{{ route('add-course.form') }}">
                <i class="fas fa-graduation-cap nav-icon"></i>
                <span class="nav-link-label">Add Course</span>
            </a>
            @endif
        </div>

        <!-- Footer / Logout -->
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout', [], false) }}">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start" style="cursor: pointer;">
                    <i class="fas fa-arrow-right-from-bracket nav-icon"></i>
                    <span class="nav-link-label">Sign Out</span>
                </button>
            </form>
        </div>

    </div>
    <div class="profile-sidebar-backdrop" id="profileSidebarBackdrop" aria-hidden="true"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header animate-fadeInUp">
            <span class="profile-hero-orb orb-one"></span>
            <span class="profile-hero-orb orb-two"></span>
            <form action="{{ secure_url(route('profile.update-picture', [], false)) }}" method="POST" enctype="multipart/form-data" id="profileForm" style="display: none;">
                @csrf
                <input type="file" 
                       name="profile_picture" 
                       id="profilePictureInput" 
                       accept="image/*"
                       style="display: none;">
            </form>
            <div class="profile-hero-layout">
                <div class="profile-picture-shell">
                    <div class="profile-picture" id="profilePictureContainer" style="cursor: pointer;" title="Click to upload new profile picture">
                        @php
                            $profileImageUrl = $currentUserView['profile_image_url'] ?? auth()->user()->getProfileImageUrl();
                            $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&size=200&background=007bff&color=ffffff');
                        @endphp
                        <img src="{{ $profileImageUrl }}"
                             alt="Profile Picture"
                             id="profileImage"
                             onerror="this.src='{{ $profileFallbackUrl }}'">
                        <div class="upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>

                <div class="welcome-text profile-hero-copy">
                    <h1>Welcome back, {{ $currentUserView['name'] ?? auth()->user()->name }}</h1>
                    <div class="profile-meta-row">
                        <p><i class="fas fa-calendar-alt"></i> Member since {{ $currentUserView['member_since'] ?? auth()->user()->created_at->format('F Y') }}</p>
                        
                        @if($userRank)
                            <p>
                                <i class="fas fa-trophy" style="color: #FFD700;"></i> Rank #{{ $userRank }} on leaderboard
                                <span style="margin-left: 5px; font-size: 0.9rem; color: #546e7a;">
                                    <i class="fas fa-bolt" style="color: #7e57c2;"></i> {{ number_format($currentUserView['xp'] ?? 0) }} XP
                                </span>
                            </p>
                        @else
                            @if(($currentUserView['xp'] ?? 0) > 0)
                            <p>
                                <i class="fas fa-bolt" style="color: #7e57c2;"></i> {{ number_format($currentUserView['xp'] ?? 0) }} XP
                            </p>
                            @else
                            <p>
                                <i class="fas fa-award" style="color: #64b5f6;"></i>
                                <span style="margin-left: 5px; font-size: 0.9rem;">Not yet ranked</span>
                            </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card streak-card animate-fadeInUp" style="animation-delay: 0.1s">
                    <h5><i class="fas fa-fire me-2 pulsing-icon"></i>Study Streak</h5>
                    <div class="stats-value">{{ $studyStreak }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card accuracy-card animate-fadeInUp" style="animation-delay: 0.2s">
                    <h5><i class="fas fa-bullseye me-2 pulsing-icon"></i>Accuracy Rate</h5>
                    <div class="stats-value">{{ number_format($accuracyRate, 1) }}%</div>
                    <small class="text-muted">Based on {{ $totalQuestions }} attempts</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card study-time-card animate-fadeInUp" style="animation-delay: 0.3s">
                    <h5><i class="fas fa-clock me-2 clock-icon"></i>Study Time</h5>
                    <div class="stats-value">{{ $formattedStudyTimes['total'] }}</div>
                    <div class="study-time-breakdown">
                        <div class="breakdown-item">
                            <small>Quiz Mode</small>
                            <span>{{ $formattedStudyTimes['quiz'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Tutor Mode</small>
                            <span>{{ $formattedStudyTimes['tutor'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Random Quizzes</small>
                            <span>{{ $formattedStudyTimes['random_quiz'] }}</span>
                        </div>
                        <div class="breakdown-item">
                            <small>Mistake Review</small>
                            <span>{{ $formattedStudyTimes['mistake_review'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.4s">
                    <h5><i class="fas fa-question-circle me-2"></i>Total Questions</h5>
                    <div class="stats-value">{{ $totalQuestions }}</div>
                    <small class="text-muted">Questions attempted</small>
                </div>
            </div>
        </div>

        @if(!empty($courseCompletionWidget['courses']))
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card course-completion-card animate-fadeInUp" style="animation-delay: 0.45s">
                    <div class="course-completion-header">
                        <div>
                            <h5 class="mb-1"><i class="fas fa-circle-notch me-2"></i>Course Coverage</h5>
                            <p class="course-completion-subtitle mb-0">Questions solved across your enrolled course bank.</p>
                        </div>
                        <div class="course-completion-controls">
                            <select class="form-select" id="courseCompletionCourseSelect" aria-label="Select course completion course">
                                @foreach($courseCompletionWidget['courses'] as $courseCompletionCourse)
                                    <option value="{{ $courseCompletionCourse['id'] }}">{{ $courseCompletionCourse['name'] }}</option>
                                @endforeach
                            </select>
                            <select class="form-select" id="courseCompletionSourceSelect" aria-label="Filter course completion by source">
                                <option value="all">All sources</option>
                            </select>
                        </div>
                    </div>

                    <div class="course-completion-body">
                        <div class="completion-ring" id="courseCompletionRing" role="img" aria-label="Course coverage 0%">
                            <svg class="completion-ring-svg" viewBox="0 0 180 180" aria-hidden="true" focusable="false">
                                <defs>
                                    <linearGradient id="courseCompletionGradient" x1="30" y1="150" x2="150" y2="30" gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#1d4ed8" />
                                        <stop offset="48%" stop-color="#0ea5e9" />
                                        <stop offset="100%" stop-color="#38bdf8" />
                                    </linearGradient>
                                    <filter id="courseCompletionGlow" x="-35%" y="-35%" width="170%" height="170%">
                                        <feGaussianBlur stdDeviation="4" result="blur" />
                                        <feColorMatrix in="blur" type="matrix" values="0 0 0 0 0.05 0 0 0 0 0.45 0 0 0 0 1 0 0 0 0.55 0" />
                                        <feMerge>
                                            <feMergeNode />
                                            <feMergeNode in="SourceGraphic" />
                                        </feMerge>
                                    </filter>
                                </defs>
                                <circle class="completion-ring-halo" cx="90" cy="90" r="72" />
                                <circle class="completion-ring-track" cx="90" cy="90" r="66" />
                                <circle class="completion-ring-progress" id="courseCompletionRingProgress" cx="90" cy="90" r="66" pathLength="100" />
                                <circle class="completion-ring-spark" id="courseCompletionRingSpark" cx="90" cy="24" r="4.5" />
                            </svg>
                            <div class="completion-ring-inner">
                                <span class="completion-ring-value" id="courseCompletionPercent">0%</span>
                                <span class="completion-ring-label" id="courseCompletionScope">All sources</span>
                            </div>
                        </div>

                        <div class="course-completion-copy">
                            <div class="course-completion-kicker" id="courseCompletionCourseName">Course</div>
                            <div class="course-completion-count">
                                <span id="courseCompletionSolved">0</span>
                                <span class="course-completion-total">/ <span id="courseCompletionTotal">0</span> questions</span>
                            </div>
                            <div class="course-completion-meter" aria-hidden="true">
                                <span id="courseCompletionMeter"></span>
                            </div>
                            <div class="course-completion-meta">
                                <span id="courseCompletionRemaining">0 remaining</span>
                                <span id="courseCompletionSourceCount">0 sources</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Study Goals Widget -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card study-goals-card animate-fadeInUp" style="animation-delay: 0.5s; position: relative; overflow: hidden;">
                    <!-- Purple slash decoration -->
                    <div class="goal-decoration"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0" style="font-weight: 600; font-size: 1.3rem; color: #0f172a; letter-spacing: -0.01em;">
                            This Week's Goal
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary goal-settings-btn" onclick="openGoalModal()" style="font-size: 0.85rem; padding: 6px 16px;">
                                Planner Settings
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="flex: 1;">
                            <div class="d-flex align-items-baseline gap-3 mb-3">
                                <span style="font-size: 3rem; font-weight: 800; color: {{ $currentUserView['weekly_progress_color'] ?? '#ef4444' }}; line-height: 1;">{{ $currentUserView['weekly_questions'] ?? 0 }}</span>
                                <span style="font-size: 1.5rem; font-weight: 600; color: #64748b;">/ {{ $currentUserView['weekly_goal'] ?? 50 }}</span>
                            </div>
                            
                            <div class="progress" style="height: 10px; border-radius: 10px; background: #e2e8f0; margin-bottom: 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.08);">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ $currentUserView['weekly_progress_percent'] ?? 0 }}%; 
                                            background: linear-gradient(90deg, {{ $currentUserView['weekly_progress_color'] ?? '#ef4444' }}, {{ ($currentUserView['weekly_progress_color'] ?? '#ef4444') }}dd);
                                            border-radius: 10px;
                                            transition: width 0.6s ease;
                                            box-shadow: 0 2px 4px {{ ($currentUserView['weekly_progress_color'] ?? '#ef4444') }}40;">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="color: #64748b; font-size: 0.9rem;">{{ $currentUserView['week_start_label'] ?? '' }} - {{ $currentUserView['week_end_label'] ?? '' }}</span>
                                <span style="font-weight: 700; font-size: 1.1rem; color: {{ $currentUserView['weekly_progress_color'] ?? '#ef4444' }};">{{ number_format($currentUserView['weekly_progress_percent'] ?? 0, 0) }}%</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <span class="badge rounded-pill text-bg-light" style="padding: 0.55rem 0.85rem; font-weight: 600;">
                                    {{ $currentUserView['daily_study_minutes'] ?? 0 }}m / day
                                </span>

                                <span class="badge rounded-pill text-bg-light text-capitalize" style="padding: 0.55rem 0.85rem; font-weight: 600;">
                                    {{ str_replace('_', ' ', $currentUserView['planner_focus'] ?? 'balanced') }}
                                </span>
                                @if(!empty($currentUserView['exam_date']))
                                    <span class="badge rounded-pill text-bg-light" style="padding: 0.55rem 0.85rem; font-weight: 600;">
                                        Exam {{ \Carbon\Carbon::parse($currentUserView['exam_date'])->format('M j, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        @if(($currentUserView['weekly_progress_percent'] ?? 0) >= 100)
                            <div class="ms-4" style="font-size: 3.5rem; animation: celebrationPulse 1.5s ease-in-out infinite;">
                                🏆
                            </div>
                        @endif
                    </div>
                    
                    @if(($currentUserView['weekly_progress_percent'] ?? 0) >= 100)
                        <div class="confetti-container"></div>
                    @endif
                </div>
            </div>
        </div>


        <!-- Charts Row -->
        <div class="row mt-4">
            <!-- Performance Over Time -->
            <div class="col-md-8">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.6s">
                    <h5>Performance Over Time</h5>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Study Distribution -->
            <div class="col-md-4">
                <div class="stats-card animate-fadeInUp" style="animation-delay: 0.7s">
                    <h5>Performance Ratio</h5>
                    <div class="chart-container">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Areas to Review -->
        <div class="row mt-4">
            <!-- Areas to Review -->
            <div class="col-md-6">
                <div class="stats-card review-section animate-fadeInUp" style="animation-delay: 0.7s">
                    @php
                        $coachTopics = $weakTopicCoach['topics'] ?? collect();
                    @endphp
                    <h5><i class="fas fa-list-check me-2"></i>Areas to Review</h5>
                    @if($coachTopics->isEmpty())
                        <p class="text-muted mb-0">Start practicing to unlock topic coaching and targeted reminders.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($coachTopics as $topic)
                            @php
                                $grade = $topic['grade'];
                                $urgencyClass = match($topic['severity']) {
                                    'critical' => 'topic-critical',
                                    'struggling' => 'topic-struggling',
                                    'improving' => 'topic-improving',
                                    default => 'topic-good'
                                };
                                $progressClass = match($topic['severity']) {
                                    'critical' => 'progress-bar-critical',
                                    'struggling' => 'progress-bar-struggling',
                                    'improving' => 'progress-bar-improving',
                                    default => 'progress-bar-good'
                                };
                                $buttonClass = match($topic['severity']) {
                                    'critical' => 'btn-outline-danger',
                                    'struggling' => 'btn-outline-warning',
                                    'improving' => 'btn-outline-info',
                                    default => 'btn-outline-success'
                                };
                            @endphp
                            <li class="list-group-item topic-review-item {{ $urgencyClass }}">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="{{ $topic['severity_icon'] }} me-1 {{ $topic['severity'] === 'critical' ? 'text-danger' : ($topic['severity'] === 'struggling' ? 'text-warning' : 'text-info') }}"></i>
                                            {{ $topic['topic_name'] }}
                                        </h6>
                                        <small class="text-muted">{{ $topic['course_name'] }}</small>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div class="progress-bar {{ $progressClass }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $grade }}%">
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ number_format($grade, 0) }}% accuracy
                                            @if(($topic['open_mistakes_count'] ?? 0) > 0)
                                                · {{ $topic['open_mistakes_count'] }} open mistake{{ ($topic['open_mistakes_count'] ?? 0) === 1 ? '' : 's' }}
                                                · review mistakes for this topic
                                            @elseif(($topic['trend_direction'] ?? 'flat') === 'down')
                                                · recent scores are dropping
                                            @elseif(($topic['days_since_last_activity'] ?? null) !== null)
                                                @php
                                                    $daysSinceLastActivity = max(0, (int) floor((float) $topic['days_since_last_activity']));
                                                @endphp
                                                · last active {{ $daysSinceLastActivity === 0 ? 'today' : $daysSinceLastActivity . 'd ago' }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        @if(($topic['open_mistakes_count'] ?? 0) > 0)
                                            <a href="{{ $topic['mistakes_url'] }}" class="btn btn-sm {{ $buttonClass }}">
                                                Review Mistakes
                                            </a>
                                        @endif
                                        <a href="{{ $topic['practice_url'] }}" class="btn btn-sm {{ $buttonClass }}">
                                            Practice <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="col-md-6">
                <div class="stats-card review-section animate-fadeInUp" style="animation-delay: 0.7s">
                    <div class="leaderboard-header">
                        <h5 class="mb-0"><i class="fas fa-medal me-2" style="color: #FFD700;"></i>Leaderboard (Top 10)</h5>
                        <div class="profile-toggle-group">
                            <div class="privacy-control" title="Toggle dark mode for the entire platform">
                                <span class="privacy-control-label">Dark mode</span>
                                <label class="privacy-switch">
                                    <input type="checkbox" id="darkModeToggle">
                                    <span class="privacy-slider"></span>
                                </label>
                            </div>
                            <div class="privacy-control" title="Toggle your visibility on the leaderboard">
                                <span class="privacy-control-label">Appear on leaderboard</span>
                                <label class="privacy-switch">
                                    <input type="checkbox" id="leaderboardToggle" {{ ($currentUserView['show_on_leaderboard'] ?? true) ? 'checked' : '' }}>
                                    <span class="privacy-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    @if($leaderboard->isEmpty())
                        <p class="text-muted">Start practicing to join the leaderboard!</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($leaderboard as $index => $user)
                            <li class="list-group-item topic-review-item" style="border-bottom: 1px solid rgba(0,0,0,0.05); {{ $user->id === auth()->id() ? 'background-color: rgba(126, 87, 194, 0.08); border-left-color: #7e57c2;' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rank-emoji text-muted" style="font-size: 0.9rem; min-width: 24px;">
                                            @if($index === 0)
                                                🏆
                                            @elseif($index === 1)
                                                🥈
                                            @elseif($index === 2)
                                                🥉
                                            @else
                                                #{{ $index + 1 }}
                                            @endif
                                        </span>
                                        <span>
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                            <small class="text-primary" style="font-size: 0.8rem; font-weight: 500; margin-left: 3px;">(You)</small>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="xp-counter" style="font-size: 0.9rem; color: #7e57c2; font-weight: 600;" data-value="{{ $user->xp }}">
                                            {{ number_format($user->xp) }} XP
                                        </span>
                                        <span style="color: #7e57c2; font-weight: 600; font-size: 0.85rem;">Level {{ $user->level }}</span>
                                    </div>
                                </div>
                                <div class="progress mt-1" style="height: 2px;">
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: {{ ($user->xp % 1000) / 10 }}%; background: linear-gradient(90deg, #7e57c2, #b388ff); box-shadow: 0 0 10px rgba(126, 87, 194, 0.3);">
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        
                        @if($userRank > 10)
                        <div class="text-center mt-3">
                            <p class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-arrow-up me-1"></i>
                                @php
                                    $xpNeeded = $currentUserView['xp_needed_for_top_ten'] ?? 0;
                                @endphp
                                You need {{ $xpNeeded }} more XP to reach the top 10!
                            </p>
                        </div>
                        @elseif(!$userRank)
                        <div class="text-center mt-3">
                            <p class="text-muted" style="font-size: 0.9rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                Start practicing to earn XP and join the leaderboard!
                            </p>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        window.ProfilePageConfig = {
            chartData: @json($chartData),
            correctCount: {{ $currentUserView['correct_answers_count'] ?? auth()->user()->correct_answers_count }},
            incorrectCount: {{ $totalQuestions - ($currentUserView['correct_answers_count'] ?? auth()->user()->correct_answers_count) }},
            totalCount: {{ $totalQuestions }},
            courseCompletion: @json($courseCompletionWidget),
            weeklyGoal: {{ $currentUserView['weekly_goal'] ?? (auth()->user()->weekly_goal ?? 50) }},
            examDate: @json($currentUserView['exam_date'] ?? null),

            dailyStudyMinutes: {{ $currentUserView['daily_study_minutes'] ?? 0 }},
            plannerFocus: @json($currentUserView['planner_focus'] ?? 'balanced'),
            updateGoalRoute: @json(route('profile.update-goal'))
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/profile-page.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const darkModeToggle = document.getElementById('darkModeToggle');

            if (!darkModeToggle || !window.MedCapsuleTheme) {
                return;
            }

            const syncDarkModeToggle = function () {
                darkModeToggle.checked = window.MedCapsuleTheme.isDarkModeEnabled();
            };

            syncDarkModeToggle();

            darkModeToggle.addEventListener('change', function () {
                window.MedCapsuleTheme.setTheme(darkModeToggle.checked ? 'dark' : 'light');
            });

            window.addEventListener('medcapsule-theme-change', syncDarkModeToggle);
        });
    </script>

</body>
</html>
