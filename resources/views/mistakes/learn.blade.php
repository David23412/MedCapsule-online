<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Learn From Mistakes - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- 💀 NUCLEAR AUDIO PRELOADING SYSTEM - F16 RAPTOR SPEED 💀 -->
    <audio id="correctSound" preload="auto" volume="0.7">
        <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
    </audio>
    <audio id="wrongSound" preload="auto" volume="0.6">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>
    <audio id="encouragementSound" preload="auto" volume="0.7">
        <source src="/sounds/encouragement.mp3" type="audio/mpeg">
    </audio>
    
    <!-- 💀 GRADING SOUNDS PRELOADING - INSTANT FINAL FEEDBACK 💀 -->
    <audio id="quizPerfectSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>
    
    <!-- 💀 INSTANT AUDIO PRELOADING SCRIPT 💀 -->
    <script>
        // NUCLEAR PRELOADING - RUNS IMMEDIATELY
        (function() {
            const audioUrls = [
                '/sounds/Single-correct.mp3',
                '/sounds/quiz-wrong.mp3',
                '/sounds/encouragement.mp3'
            ];
            
            // 💀 LETHAL AUDIO CONTEXT CREATION 💀
            let audioContext;
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                console.log('AudioContext not supported');
            }
            
            // Preload all audio files instantly with error handling
            audioUrls.forEach(url => {
                const audio = new Audio();
                audio.preload = 'auto';
                audio.src = url;
                
                // Handle 404 errors silently
                audio.addEventListener('error', function(e) {
                    console.log('Audio preload failed (404):', url);
                });
                
                // Log successful loads
                audio.addEventListener('canplaythrough', function() {
                    console.log('Audio preloaded successfully:', url);
                });
                
                audio.load();
            });
            
            // 💀 LETHAL AUDIO CONTEXT PRIMING 💀
            if (audioContext && audioContext.state === 'suspended') {
                document.addEventListener('click', function() {
                    audioContext.resume();
                }, { once: true });
            }
        })();
    </script>
    
    <!-- 💀 NUCLEAR SPEED OPTIMIZATIONS 💀 -->
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="dns-prefetch" href="{{ url('/') }}">
    <link rel="stylesheet" href="{{ asset('css/mistakes-learn-page.css') }}">
    
    {{-- Responsive Navigation CSS --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-navigation.css') }}">
</head>

<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    @php
        $profileImageUrl = $currentUserView['profile_image_url'] ?? Auth::user()->getProfileImageUrl();
        $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff');
        $selectedTopicNames = collect($selectedTopicNames ?? []);
        $isTopicFocusedSession = $selectedTopicNames->isNotEmpty();
        $topicSessionTitle = $selectedTopicNames->count() === 1
            ? $selectedTopicNames->first() . ' Mistake Session'
            : 'Focused Topic Mistake Session';
        $sessionLoadedCount = $mistakes->count();
        $coveragePercent = $totalMistakesCount > 0 ? (int) round(($sessionLoadedCount / $totalMistakesCount) * 100) : 0;
        $coursesCollection = collect($courses ?? []);
        $activeCourseName = 'All Courses';
        $sessionNow = now();
        $dueNowCount = $mistakes->filter(function ($mistake) use ($sessionNow) {
            return !(bool) $mistake->skipped
                && (!$mistake->next_review_at || $mistake->next_review_at->lte($sessionNow));
        })->count();
        $highRiskCount = $mistakes->filter(function ($mistake) {
            return !(bool) $mistake->skipped && (float) ($mistake->retrievability ?? 0.9) <= 0.55;
        })->count();
        $stabilizingCount = $mistakes->filter(function ($mistake) {
            return !(bool) $mistake->skipped
                && (float) ($mistake->stability ?? 0) >= 1.5
                && (float) ($mistake->retrievability ?? 0.9) > 0.55;
        })->count();
        $averageIntervalDays = round($mistakes->avg(fn ($mistake) => (float) ($mistake->interval_days ?? 0)) ?? 0, 1);
        $averageRetrievability = (int) round(($mistakes->avg(fn ($mistake) => (float) ($mistake->retrievability ?? 0.9)) ?? 0.9) * 100);
        $upNextMistake = $mistakes
            ->filter(fn ($mistake) => !(bool) $mistake->skipped && $mistake->next_review_at)
            ->sortBy('next_review_at')
            ->first();
        $queueLeadText = $dueNowCount > 0
            ? "{$dueNowCount} due now"
            : ($upNextMistake ? 'Next review ' . $upNextMistake->next_review_at->diffForHumans() : 'Queue is clear');

        if (!empty($courseFilter)) {
            $activeCourseName = optional($coursesCollection->firstWhere('id', (int) $courseFilter))->name ?? 'Custom Filter';
        }
    @endphp

    <div class="sidebar">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="true">
            <i class="fas fa-chevron-left"></i>
        </button>

        <a href="{{ route('courses.index') }}" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
                    <defs>
                        <linearGradient id="learnSidebarPillBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb"></stop>
                            <stop offset="45%" stop-color="#3b82f6"></stop>
                            <stop offset="100%" stop-color="#1d4ed8"></stop>
                        </linearGradient>
                        <linearGradient id="learnSidebarPillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0)"></stop>
                            <stop offset="30%" stop-color="rgba(255,255,255,0.6)"></stop>
                            <stop offset="70%" stop-color="rgba(255,255,255,0.5)"></stop>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"></stop>
                        </linearGradient>
                        <linearGradient id="learnSidebarPillShadow" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgba(0,0,0,0)"></stop>
                            <stop offset="100%" stop-color="rgba(10,20,80,0.28)"></stop>
                        </linearGradient>
                        <filter id="learnSidebarPillDrop" x="-8%" y="-15%" width="116%" height="150%">
                            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"></feDropShadow>
                        </filter>
                    </defs>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#learnSidebarPillBody)" filter="url(#learnSidebarPillDrop)"></rect>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#learnSidebarPillShadow)"></rect>
                    <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#learnSidebarPillGloss)" opacity="0.88"></rect>
                    <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"></ellipse>
                </svg>
            </div>
            <div class="sidebar-logo-copy">
                <div class="sidebar-logo-text">MedCapsule</div>
                <div class="sidebar-logo-sub">Learn Mistakes</div>
            </div>
        </a>

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
            <a class="nav-link" href="{{ route('review.index') }}">
                <i class="fas fa-rotate-right nav-icon"></i>
                <span class="nav-link-label">Review Mistakes</span>
            </a>
            <a class="nav-link active" href="{{ route('review.learn') }}">
                <i class="fas fa-bolt nav-icon"></i>
                <span class="nav-link-label">Learn Mistakes</span>
            </a>
            <a class="nav-link" href="{{ route('profile') }}">
                <i class="fas fa-gear nav-icon"></i>
                <span class="nav-link-label">Profile Settings</span>
            </a>

            <div class="sidebar-coming-soon">
                <div class="coming-soon-overlay">
                    <span class="coming-soon-badge">Coming Soon</span>
                </div>
                <div class="sidebar-blur-container">
                    <span class="sidebar-section-label">Session</span>

                    <div class="sidebar-insight-card sidebar-insight-card-primary">
                        <div class="sidebar-insight-head">
                            <i class="fas fa-crosshairs nav-icon"></i>
                            <span class="nav-link-label">Session Focus</span>
                            <span class="sidebar-mode-chip" id="sidebarModeValue" data-mode="pending">Pick mode</span>
                        </div>
                        <p class="sidebar-insight-copy">
                            @if($isTopicFocusedSession)
                                {{ $topicSessionTitle }}
                            @else
                                Adaptive review across your active mistake bank.
                            @endif
                        </p>
                        <div class="sidebar-chip-list">
                            <span class="sidebar-chip">{{ $activeCourseName }}</span>
                            <span class="sidebar-chip">{{ $sessionLoadedCount }} loaded</span>
                            <span class="sidebar-chip">{{ $coveragePercent }}% coverage</span>
                        </div>
                    </div>

                    <div class="sidebar-insight-card sidebar-insight-card-soft">
                        <div class="sidebar-insight-head">
                            <i class="fas fa-wave-square nav-icon"></i>
                            <span class="nav-link-label">Session Pulse</span>
                        </div>
                        <div class="sidebar-progress-meta">
                            <span id="sidebarProgressSummary">0 reviewed this run</span>
                            <span id="sidebarProgressPercent">0%</span>
                        </div>
                        <div class="sidebar-progress-track">
                            <div class="sidebar-progress-fill" id="sidebarProgressFill"></div>
                        </div>
                        <div class="sidebar-stat-grid">
                            <div class="sidebar-stat-card sidebar-stat-card-coverage">
                                <span class="sidebar-stat-label">Cleared</span>
                                <strong id="sidebarCompletedCount">0</strong>
                                <small>Finished this run</small>
                            </div>
                            <div class="sidebar-stat-card sidebar-stat-card-cold">
                                <span class="sidebar-stat-label">Active</span>
                                <strong id="sidebarRemainingCount">{{ $sessionLoadedCount }}</strong>
                                <small>Still in rotation</small>
                            </div>
                        </div>
                    </div>

                    <span class="sidebar-section-label">Context</span>

                    <div class="sidebar-insight-card sidebar-insight-card-soft">
                        <div class="sidebar-insight-head">
                            <i class="fas fa-layer-group nav-icon"></i>
                            <span class="nav-link-label">Current Target</span>
                        </div>
                        <p class="sidebar-insight-copy">
                            @if($isTopicFocusedSession)
                                Focused repair on the topic{{ $selectedTopicNames->count() === 1 ? '' : 's' }} you picked.
                            @else
                                Balanced review across the mistakes most likely to slow your next quiz.
                            @endif
                        </p>
                        <div class="sidebar-chip-list">
                            <span class="sidebar-chip">{{ $activeCourseName }}</span>
                            @forelse($selectedTopicNames->take(2) as $topicName)
                                <span class="sidebar-chip">{{ $topicName }}</span>
                            @empty
                                <span class="sidebar-chip">All weak topics</span>
                            @endforelse
                        </div>
                    </div>
                </div>
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

    <div class="main-content">
    <div class="container">

        @if($mistakes->isEmpty())
            <div class="done-message">
                <div class="confetti confetti-1"></div>
                <div class="confetti confetti-2"></div>
                <div class="confetti confetti-3"></div>
                <div class="confetti confetti-4"></div>
                <div class="confetti confetti-5"></div>
                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 1.5rem; display: inline-flex; padding: 1.2rem; border-radius: 50%; background: #eff6ff; border: 2px solid #bfdbfe; animation: float 3s ease-in-out infinite;"></i>
                <h3 style="color: #1e293b; font-size: 1.75rem; font-weight: 600; margin-bottom: 1rem; letter-spacing: -0.02em; background: linear-gradient(135deg, #1e3a8a, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    {{ ($totalMistakesCount ?? 0) > 0 ? 'No Mistakes Match This Filter' : "You've Mastered All Mistakes!" }}
                </h3>
                <p style="color: #64748b; font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
                    {{ ($totalMistakesCount ?? 0) > 0
                        ? 'Your deck still has mistakes, but none matched the current filter. Change the course filter or mode and keep reviewing.'
                        : 'Your dedication to learning is impressive! Come back later for more practice.' }}
                </p>
                <a href="{{ route('courses.index') }}" class="btn btn-primary" style="background: #f0f9ff; color: #0284c7; padding: 0.6rem 1.4rem; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 0.95rem; border: 1px solid #bae6fd; transition: all 0.2s ease; display: inline-block; animation: buttonPulse 2s infinite;">Back to Home</a>
            </div>
        @else
            @if($isTopicFocusedSession)
                <div class="focused-session-bar" style="background: rgba(248, 250, 252, 0.6); border: 1px solid #e2e8f0; border-radius: 999px; padding: 0.5rem 1.5rem; margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center; gap: 12px; width: fit-content; max-width: 900px; backdrop-filter: blur(8px);">
                    <i class="fas fa-crosshairs" style="color: #3b82f6; font-size: 0.8rem;"></i>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600; color: #334155;">{{ $topicSessionTitle }}</span>
                        <span style="color: #e2e8f0;">•</span>
                        <span>{{ $selectedTopicNames->count() === 1 ? $selectedTopicNames->first() : $selectedTopicNames->count() . ' topics' }}</span>
                        <span style="color: #e2e8f0;">•</span>
                        <span style="color: #3b82f6; font-weight: 500;">
                            {{ $selectedTopicMistakeCount ?? $mistakes->count() }} Mistake{{ ($selectedTopicMistakeCount ?? $mistakes->count()) === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
            @endif

            <!-- Add progress bar container -->
            <div class="progress-container">
                <div class="progress-header">
                    <div class="progress-title">Learning Progress</div>
                    <div class="progress-text">0 reviewed this run</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <div class="difficulty-overlay"></div>
            <div class="difficulty-selector">
                <h2 class="difficulty-title">{{ $isTopicFocusedSession ? $topicSessionTitle : 'Choose Your Difficulty Level' }}</h2>
                @if($isTopicFocusedSession)
                    <p style="text-align: center; color: #64748b; margin: -8px 0 24px;">
                        Start a focused mistake-review run for {{ $selectedTopicNames->count() === 1 ? $selectedTopicNames->first() : 'these selected topics' }}.
                    </p>
                @endif
                
                <!-- Course filter dropdown -->
                <div class="course-filter mb-4">
                    <form action="{{ route('review.learn') }}" method="GET" id="filter-form">
                        <input type="hidden" name="mode" value="{{ $currentLearnMode ?? 'quiz' }}">
                        <div class="mb-3">
                            <select id="course-filter" name="course" class="form-select">
                                <option value="">All Courses ({{ $totalMistakesCount }})</option>
                                @foreach($courses ?? [] as $course)
                                    <option value="{{ $course->id }}" {{ ($courseFilter ?? '') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $courseCounts[$course->id] ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <select id="topic-filter" name="topic_ids[]" class="form-select">
                                <option value="">All Topics</option>
                                @foreach($topics ?? [] as $topicOption)
                                    <option value="{{ $topicOption->id }}" {{ in_array($topicOption->id, $selectedTopicIds ?? [], true) ? 'selected' : '' }}>
                                        {{ $topicOption->name }} ({{ $topicCounts[$topicOption->id] ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select id="question-type-filter" name="question_type" class="form-select">
                                <option value="all" {{ ($selectedQuestionType ?? 'all') === 'all' ? 'selected' : '' }}>All Questions</option>
                                <option value="multiple_choice" {{ ($selectedQuestionType ?? 'all') === 'multiple_choice' ? 'selected' : '' }}>MCQ Only</option>
                                <option value="written" {{ ($selectedQuestionType ?? 'all') === 'written' ? 'selected' : '' }}>Written Only</option>
                            </select>
                        </div>
                    </form>
                </div>
                
                <div class="difficulty-options">
                    <div class="difficulty-option" data-mode="practice">
                        <h3>Easy</h3>
                        <p>Get each mistake right twice to finish the session.</p>
                    </div>
                    <div class="difficulty-option" data-mode="quiz">
                        <h3>Hard</h3>
                        <p>Get each mistake right three times for a deeper review.</p>
                    </div>
                    <div class="difficulty-option difficulty-option-spaced @if(!($currentUserView['is_admin'] ?? Auth::check() && Auth::user()->isAdmin())) sidebar-coming-soon @endif" data-mode="spaced">
                        <div class="@if(!($currentUserView['is_admin'] ?? Auth::check() && Auth::user()->isAdmin())) sidebar-blur-container @endif">
                            <h3>Spaced Repetition</h3>
                            <p>Focus on the mistakes you're most likely to forget first.</p>
                        </div>
                        @if(!($currentUserView['is_admin'] ?? Auth::check() && Auth::user()->isAdmin()))
                        <div class="coming-soon-overlay">
                            <i class="fas fa-lock blue-lock-icon"></i>
                        </div>
                        @endif
                    </div>
                </div>
                <p class="difficulty-note">
                    Easy and Hard focus on repeated practice. Spaced Repetition prioritizes mistakes based on your personal review schedule.
                </p>
                <button class="start-button" disabled>Start Learning</button>
            </div>

            @foreach($mistakes as $index => $mistake)
                @php
                    $nextReviewAt = $mistake->next_review_at;
                    $retrievability = (float) ($mistake->retrievability ?? 0.9);
                    $stability = (float) ($mistake->stability ?? 0);
                    $difficulty = (float) ($mistake->difficulty ?? 0);
                    $memoryStatusLabel = !$nextReviewAt || $nextReviewAt->lte($sessionNow)
                        ? 'Due now'
                        : ($nextReviewAt->isToday() ? 'Later today' : ($nextReviewAt->isTomorrow() ? 'Tomorrow' : 'Scheduled'));
                    $memoryStatusClass = !$nextReviewAt || $nextReviewAt->lte($sessionNow)
                        ? 'is-due'
                        : ($retrievability <= 0.55 ? 'is-risk' : 'is-scheduled');
                    $stabilityLabel = $stability < 0.5 ? 'Fragile' : ($stability < 2 ? 'Building' : ($stability < 7 ? 'Settling' : 'Stable'));
                    $difficultyLabel = $difficulty >= 0.75 ? 'Stubborn' : ($difficulty >= 0.55 ? 'Sticky' : ($difficulty >= 0.35 ? 'Improving' : 'Fluent'));
                @endphp
                <div class="card {{ $index === 0 ? 'active' : '' }}" 
                    data-question-id="{{ $mistake->question_id }}"
                    data-stage="{{ $mistake->stage }}"
                    data-course-id="{{ $mistake->question->topic->course_id ?? 'all' }}"
                    data-next-review-at="{{ optional($mistake->next_review_at)->toIso8601String() }}"
                    data-interval-days="{{ (float) ($mistake->interval_days ?? 0) }}"
                    data-stability="{{ round($stability, 2) }}"
                    data-difficulty="{{ round($difficulty, 2) }}"
                    data-retrievability="{{ round($retrievability, 4) }}"
                    data-review-count="{{ (int) ($mistake->review_count ?? 0) }}"
                    data-review-streak="{{ (int) ($mistake->correct_streak ?? 0) }}"
                    data-last-review-rating="{{ (int) ($mistake->last_review_rating ?? 0) }}"
                    data-concept-risk-score="{{ round((float) ($mistake->concept_risk_score ?? 0), 4) }}"
                    data-focus-concept-name="{{ $mistake->focus_concept_name ?? '' }}"
                    data-learning-state="{{ $mistake->learning_state ?? '' }}"
                    data-same-day-relearn-step="{{ (int) ($mistake->same_day_relearn_step ?? 0) }}"
                    data-last-attempt-date="{{ optional($mistake->last_attempt_date)->toIso8601String() }}"
                    data-skipped="{{ $mistake->skipped ? 'true' : 'false' }}">
                    <!-- Course: {{ $mistake->question->topic->course->name ?? 'Unknown' }} -->

                    <div class="question">
                        <div class="question-text-content">
                            @php
                                $questionText = is_string($mistake->question) ? $mistake->question : json_decode($mistake->question)->question;
                            @endphp
                            {{ $questionText }}
                        </div>
                        @if($mistake->question && $mistake->question->image_url)
                            <img src="{{ asset($mistake->question->image_url) }}" alt="Question Image" class="question-image">
                        @endif
                    </div>
                    <div class="review-sync-feedback" data-role="review-sync-feedback"></div>
                    <div class="spaced-confidence-panel" data-role="spaced-confidence-panel">
                        <div class="spaced-confidence-options" role="group" aria-label="Spaced review confidence">
                            <button type="button" class="spaced-confidence-option" data-confidence="guessed">Guessed</button>
                            <button type="button" class="spaced-confidence-option" data-confidence="unsure">Unsure</button>
                            <button type="button" class="spaced-confidence-option" data-confidence="knew">Knew it</button>
                        </div>
                    </div>
                    
                    @if(isset($mistake->question->question_type) && $mistake->question->question_type === 'written')
                    <div class="written-answer-container">
                        <div class="form-group">
                            <textarea class="form-control written-answer" rows="4" placeholder="Type your answer here..."></textarea>

                        </div>
                        <button class="btn btn-primary submit-written-answer" data-question-id="{{ $mistake->question_id }}">
                            Submit Answer
                        </button>
                        <div class="written-answer-feedback"></div>
                    </div>
                    @else
                    <div class="options">
                        @foreach(collect(['A', 'B', 'C', 'D'])->shuffle() as $option)
                            @php
                                $optionText = $mistake->question->{'option_' . strtolower($option)};
                                if (!is_string($optionText)) {
                                    $optionText = json_decode($optionText);
                                }
                            @endphp
                            <div class="option" 
                                data-option="{{ $option }}"
                                data-is-correct="{{ $option === $mistake->question->correct_answer ? 'true' : 'false' }}">
                                {{ $optionText }}
                            </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="stage-indicator" data-mode="practice">
                        <!-- Stage dots will be dynamically added by JavaScript -->
                    </div>
                    
                    <!-- Skip Button -->
                    <div class="skip-button-container">
                        <button class="skip-question-btn" data-question-id="{{ $mistake->question_id }}">
                            >> skip
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    </div>

    <div class="encouragement-message">
        <h2>Great job! Keep going! 🎉</h2>
        <p>You're making great progress!</p>
    </div>

    <!-- Quiz-style completion popup -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon">🎉</div>
            <h2 class="popup-title" id="popup-title">Session Complete!</h2>
            <div class="time-container">
                <div class="popup-time" id="popup-time">0:00</div>
                <div class="time-details">Study Time</div>
            </div>
            <p class="popup-message" id="popup-message">Great work! You're making excellent progress!</p>
            <div class="popup-buttons">
                <a href="{{ route('review.index') }}" class="popup-btn review-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Review
                </a>
                <button class="popup-btn retry-btn" id="continue-learning">
                    <i class="fas fa-redo"></i>
                    Continue Learning
                </button>
            </div>
        </div>
    </div>

    <!-- 💀 AUDIO ELEMENTS MOVED TO HEAD FOR NUCLEAR PRELOADING 💀 -->

    <script>
        window.MistakesLearnPageConfig = {
            reviewLearnUrl: @json(route('review.learn')),
            saveTimeUrl: @json(secure_url('/learn/save-time')),
            autoSelectEasy: @json(!empty($selectedTopicIds ?? [])),
            selectedTopicIds: @json(array_values($selectedTopicIds ?? [])),
            selectedQuestionType: @json($selectedQuestionType ?? 'all'),
            currentMode: @json($currentLearnMode ?? 'quiz'),
            currentDeckMode: @json(($currentLearnMode ?? 'quiz') === 'spaced' ? 'spaced' : 'classic'),
            autoStartMode: @json($autoStartLearnMode ?? null)
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/mistakes-learn-page.js') }}" defer></script>

</body>

</html>
