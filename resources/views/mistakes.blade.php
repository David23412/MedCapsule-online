<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <link rel="stylesheet" href="{{ asset('css/mistakes-page.css') }}?v={{ filemtime(public_path('css/mistakes-page.css')) }}">
    
    {{-- Desktop styles only --}}
</head>
<body>
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    @php
        $profileImageUrl = $currentUserView['profile_image_url'] ?? Auth::user()->getProfileImageUrl();
        $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff');
        $mistakeCollection = collect($mistakes ?? []);
        $coursesCollection = collect($availableCourses ?? []);
        $activeReviewLabel = 'All Courses';
        $staleCount = $mistakeCollection->filter(fn ($mistake) => $mistake->last_attempt_date && $mistake->last_attempt_date->lt(now()->subDays(7)))->count();
        $coldReviewCount = $mistakeCollection->filter(fn ($mistake) => (int) $mistake->correct_streak === 0)->count();
        $closeReviewCount = $mistakeCollection->filter(fn ($mistake) => (int) $mistake->correct_streak >= 3)->count();
        $writtenCount = $mistakeCollection->filter(fn ($mistake) => ($mistake->question->question_type ?? null) === 'written')->count();
        $topWeakCourse = $coursesCollection->sortByDesc('mistake_count')->first();
        $oldestMistake = $mistakeCollection->sortBy('last_attempt_date')->first();

        if (!empty($selectedCourse)) {
            $activeReviewLabel = optional($coursesCollection->firstWhere('id', (int) $selectedCourse))->name ?? 'Filtered Course';
        }
    @endphp

    <div class="sidebar">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="true">
            <i class="fas fa-chevron-left"></i>
        </button>

        <a href="{{ route('home') }}" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
                    <defs>
                        <linearGradient id="reviewSidebarPillBody" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb"></stop>
                            <stop offset="45%" stop-color="#3b82f6"></stop>
                            <stop offset="100%" stop-color="#1d4ed8"></stop>
                        </linearGradient>
                        <linearGradient id="reviewSidebarPillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0)"></stop>
                            <stop offset="30%" stop-color="rgba(255,255,255,0.6)"></stop>
                            <stop offset="70%" stop-color="rgba(255,255,255,0.5)"></stop>
                            <stop offset="100%" stop-color="rgba(255,255,255,0)"></stop>
                        </linearGradient>
                        <linearGradient id="reviewSidebarPillShadow" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgba(0,0,0,0)"></stop>
                            <stop offset="100%" stop-color="rgba(10,20,80,0.28)"></stop>
                        </linearGradient>
                        <filter id="reviewSidebarPillDrop" x="-8%" y="-15%" width="116%" height="150%">
                            <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"></feDropShadow>
                        </filter>
                    </defs>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#reviewSidebarPillBody)" filter="url(#reviewSidebarPillDrop)"></rect>
                    <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#reviewSidebarPillShadow)"></rect>
                    <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#reviewSidebarPillGloss)" opacity="0.88"></rect>
                    <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"></ellipse>
                </svg>
            </div>
            <div class="sidebar-logo-copy">
                <div class="sidebar-logo-text">MedCapsule</div>
                <div class="sidebar-logo-sub">Review Mistakes</div>
            </div>
        </a>

        <div class="sidebar-nav">
            <span class="sidebar-section-label">Navigation</span>

            @if(isset($recentCourse) && $recentCourse)
                <a class="nav-link" href="{{ route('topics.forCourse', $recentCourse->id) }}">
                    <i class="fas fa-arrow-left nav-icon"></i>
                    <span class="nav-link-label">Back to {{ $recentCourse->name }}</span>
                </a>
            @else
                <a class="nav-link" href="{{ route('courses.index') }}">
                    <i class="fas fa-arrow-left nav-icon"></i>
                    <span class="nav-link-label">Back to Courses</span>
                </a>
            @endif

            <a class="nav-link" href="{{ route('home') }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-link-label">Home</span>
            </a>
            <a class="nav-link" href="{{ route('courses.index') }}">
                <i class="fas fa-book-open nav-icon"></i>
                <span class="nav-link-label">My Courses</span>
            </a>
            <a class="nav-link active" href="{{ route('review.index') }}">
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

            <div class="sidebar-coming-soon">
                <div class="coming-soon-overlay">
                    <span class="coming-soon-badge">Coming Soon</span>
                </div>
                <div class="sidebar-blur-container">
                    <span class="sidebar-section-label">Review Bank</span>

                    <div class="sidebar-insight-card sidebar-insight-card-primary">
                        <div class="sidebar-insight-head">
                            <i class="fas fa-heart-pulse nav-icon"></i>
                            <span class="nav-link-label">Bank Health</span>
                        </div>
                        <p class="sidebar-insight-copy">
                            {{ $mistakeCollection->count() }} cards are active in {{ strtolower($activeReviewLabel) }}.
                        </p>
                        <div class="sidebar-chip-list">
                            <span class="sidebar-chip">{{ $activeReviewLabel }}</span>
                            <span class="sidebar-chip">{{ $totalMistakesCount ?? $mistakeCollection->count() }} total</span>
                        </div>
                    </div>

                    <div class="sidebar-stat-grid">
                        <div class="sidebar-stat-card sidebar-stat-card-loaded">
                            <span class="sidebar-stat-label">Queue</span>
                            <strong>{{ $mistakeCollection->count() }}</strong>
                            <small>Visible now</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-skipped">
                            <span class="sidebar-stat-label">Skipped</span>
                            <strong>{{ $skippedCount ?? 0 }}</strong>
                            <small>Need another pass</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-cold">
                            <span class="sidebar-stat-label">Stale</span>
                            <strong>{{ $staleCount }}</strong>
                            <small>7 days+</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-ready">
                            <span class="sidebar-stat-label">Close</span>
                            <strong>{{ $closeReviewCount }}</strong>
                            <small>Near clear</small>
                        </div>
                    </div>

                    <span class="sidebar-section-label">Priority</span>

                    <div class="sidebar-insight-card sidebar-insight-card-soft">
                        <div class="sidebar-insight-head">
                            <i class="fas fa-bullseye nav-icon"></i>
                            <span class="nav-link-label">Next Focus</span>
                        </div>
                        <p class="sidebar-insight-copy">
                            @if($topWeakCourse)
                                {{ $topWeakCourse->name }} is carrying the biggest review load.
                            @else
                                Your review queue is balanced across courses.
                            @endif
                        </p>
                        <div class="sidebar-chip-list">
                            @if($topWeakCourse)
                                <span class="sidebar-chip">{{ $topWeakCourse->name }}</span>
                                <span class="sidebar-chip">{{ $topWeakCourse->mistake_count }} flagged</span>
                            @endif
                            <span class="sidebar-chip">{{ $coldReviewCount }} cold</span>
                            <span class="sidebar-chip">{{ $writtenCount }} written</span>
                            @if($oldestMistake && $oldestMistake->last_attempt_date)
                                <span class="sidebar-chip">Oldest {{ $oldestMistake->last_attempt_date->diffForHumans() }}</span>
                            @endif
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
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="toast-container"></div>
            <div class="header-section">
                <div>
                    <h1>Review Your Mistakes</h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button id="flashcardModeToggle" class="flashcard-mode-toggle">
                        <i class="fas fa-sync-alt"></i>
                        Flashcard Mode
                    </button>
                    @if($mistakes->isEmpty())
                        <span class="learn-mistakes-btn disabled">
                            No Mistakes to Learn <i class="fas fa-check-circle"></i>
                        </span>
                    @else
                        <a href="{{ route('review.learn') }}" class="learn-mistakes-btn">
                            Learn Mistakes <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>

            <!-- ROBUST AS FUCK COURSE FILTER DROPDOWN -->
            <div class="filter-section">
                <form id="robust-filter-form" action="{{ route('review.index') }}" method="GET" class="d-flex align-items-center w-100 gap-3" data-instant-submit="true">
                    <!-- Hidden inputs for form robustness -->
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="filter_timestamp" value="{{ time() }}">
                    
                    <div class="d-flex align-items-center flex-grow-1 flex-wrap">
                        <label for="courseFilter" class="me-3">
                            <i class="fas fa-filter me-2 text-primary"></i>
                            <span>Filter by Course:</span>
                        </label>
                        <div class="position-relative flex-grow-1">
                            <select id="courseFilter" name="course" class="form-select">
                                <option value="">All Courses ({{ $totalMistakesCount ?? 0 }})</option>
                                @foreach($availableCourses->sortBy('name') as $course)
                                    <option value="{{ $course->id }}" {{ $selectedCourse == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $course->mistake_count ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($selectedCourse)
                        <a href="{{ route('review.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear Filter
                        </a>
                    @endif
                </form>
            </div>

            <div id="mistakes-content">
            @if($mistakes->isEmpty())
                <div class="empty-state">
                    <div class="confetti confetti-1"></div>
                    <div class="confetti confetti-2"></div>
                    <div class="confetti confetti-3"></div>
                    <div class="confetti confetti-4"></div>
                    <div class="confetti confetti-5"></div>
                    <i class="fas fa-check"></i>
                    <h3>No Mistakes!</h3>
                    <p>Fantastic work! You're crushing it with zero mistakes. Keep up the amazing progress!</p>
                </div>
            @else
                @foreach($mistakes as $mistake)
                        <div class="mistake-card" data-question-id="{{ $mistake->question_id }}">
                        <!-- Normal Mode Content -->
                        <div class="normal-mode-content">
                            <button class="remove-question" onclick="removeMistake({{ $mistake->question_id }})">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="question-text">
    <!-- No hardcoded indicator -->
                                <p>{{ $mistake->question->question }}</p>
                                @if($mistake->question->image_url)
                                    <img src="{{ asset($mistake->question->image_url) }}" alt="Question Image" class="question-image">
                                @endif
                                
                                <!-- No top indicator -->
                                

                            </div>
                            <div class="answers">
                                <div class="options">
                                    @php
                                        $submittedOption = $mistake->submitted_answer;
                                        $correctOption = $mistake->question->correct_answer;
                                        $isUnanswered = ($submittedOption === 'unanswered' || $submittedOption === '' || $submittedOption === null || $mistake->skipped);
                                    @endphp
                                    
                                    <!-- Submitted Answer Display -->
                                    <div class="answer-section">
                                        <h4 class="answer-label">Your answer:</h4>
                                        <div class="form-check">
                                            @if($isUnanswered)
                                                <!-- Unanswered Question - Normal Styling -->
                                                <label class="form-check-label incorrect">
                                                    UNANSWERED
                                                </label>
                                            @elseif($mistake->question->question_type === 'written')
                                                <!-- Written Question - Show Submitted Text -->
                                                <label class="form-check-label incorrect">
                                                    {{ $submittedOption }}
                                                </label>
                                            @else
                                                <!-- Multiple Choice - Show Submitted Option -->
                                                <input class="form-check-input" type="radio" disabled checked>
                                                <label class="form-check-label incorrect">
                                                    @if(in_array(strtolower($submittedOption), ['a', 'b', 'c', 'd']))
                                                        {{ $mistake->question->{'option_'.strtolower($submittedOption)} }}
                                                    @else
                                                        {{ $submittedOption }}
                                                    @endif
                                                </label>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Correct Answer -->
                                    <div class="answer-section">
                                        <h4 class="answer-label">Correct answer:</h4>
                                        <div class="form-check">
                                            @if($mistake->question->question_type === 'written')
                                                <span class="written-correct-answer d-none">{{ $mistake->question->correct_answer }}</span>
                                                <label class="form-check-label correct">
                                                    {{ $mistake->question->correct_answer }}
                                                </label>
                                            @else
                                                <label class="form-check-label correct">
                                                    {{ $mistake->question->{'option_'.strtolower($correctOption)} }}
                                                </label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($mistake->question->explanation)
                                    <div class="mt-3 explanation-wrapper">
                                        <button class="explanation-toggle" type="button" data-explanation-target="explanation-{{ $mistake->question_id }}" aria-expanded="false" aria-controls="explanation-{{ $mistake->question_id }}">
                                            <i class="fas fa-lightbulb"></i>
                                        </button>
                                        <div class="explanation-panel" id="explanation-{{ $mistake->question_id }}">
                                            <div class="explanation-content">
                                                {{ $mistake->question->explanation }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="info-boxes">
                                    @if($mistake->skipped)
                                    <div class="info-box" style="border-color: #5dade2; background-color: rgba(93, 173, 226, 0.15);">
                                        <i class="fas fa-forward" style="color: #2980b9;"></i>
                                        <div class="info-box-value" style="color: #2980b9;">skipped</div>
                                    </div>
                                    @endif
                                    <div class="info-box">
                                        <div class="info-box-label">Date</div>
                                        <div class="info-box-value">{{ $mistake->last_attempt_date->format('M j, Y') }}</div>
                                    </div>
                                    @if($mistake->question->topic && $mistake->question->topic->course)
                                    <div class="info-box">
                                        <div class="info-box-label">Topic</div>
                                        <div class="info-box-value">{{ $mistake->question->topic->name }}</div>
                                    </div>
                                    <div class="info-box">
                                        <div class="info-box-label">Course</div>
                                        <div class="info-box-value">{{ $mistake->question->topic->course->name }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Flashcard Mode Content -->
                        <div class="flashcard-content">
                            <div class="card-face card-front"></div>
                            <div class="card-face card-back"></div>
                        </div>
                    </div>
                @endforeach
            @endif
            </div>
        </div>
    </div>

    <script>
        window.MistakesPageConfig = {
            recentCourse: @json($recentCourse ?? null),
            coursesIndexUrl: @json(route('courses.index')),
            reviewIndexUrl: @json(route('review.index'))
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/mistakes-page.js') }}?v={{ filemtime(public_path('js/mistakes-page.js')) }}" defer></script>

    {{-- No mobile JS --}}
</body>
</html>
