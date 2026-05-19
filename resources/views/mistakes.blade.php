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

    @include('partials.favicon')
    @include('partials.admin-dark-mode-head')

    <link rel="stylesheet" href="{{ $bladeAsset->url('css/mistakes-page.css') }}">
    
    {{-- Desktop styles only --}}
</head>
<body>
    {{-- Mobile-only sticky top bar --}}
    <header class="mistakes-mobile-header" id="mistakesMobileHeader">
        <button class="mistakes-mobile-hamburger" id="mistakesMobileHamburger" aria-label="Open navigation menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <span class="mistakes-mobile-title">Review Mistakes</span>
        <a href="{{ route('courses.index') }}" class="mistakes-mobile-home" aria-label="Home">
            <i class="fas fa-home"></i>
        </a>
    </header>
    <div class="mistakes-sidebar-backdrop" id="mistakesSidebarBackdrop" aria-hidden="true"></div>
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <div class="sidebar" id="mistakesSidebar" role="navigation" aria-label="Review navigation">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="true">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button type="button" class="mistakes-sidebar-close-btn" id="mistakesSidebarCloseBtn" aria-label="Close navigation">
            <i class="fas fa-times"></i>
        </button>

        <a href="{{ route('courses.index') }}" class="sidebar-logo">
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

            <a class="nav-link" href="{{ route('courses.index') }}">
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
                            {{ $mistakes->count() }} cards are active in {{ strtolower($mistakeStats['active_review_label']) }}.
                        </p>
                        <div class="sidebar-chip-list">
                            <span class="sidebar-chip">{{ $mistakeStats['active_review_label'] }}</span>
                            <span class="sidebar-chip">{{ $totalMistakesCount ?? $mistakes->count() }} total</span>
                        </div>
                    </div>

                    <div class="sidebar-stat-grid">
                        <div class="sidebar-stat-card sidebar-stat-card-loaded">
                            <span class="sidebar-stat-label">Queue</span>
                            <strong>{{ $mistakes->count() }}</strong>
                            <small>Visible now</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-skipped">
                            <span class="sidebar-stat-label">Skipped</span>
                            <strong>{{ $skippedCount ?? 0 }}</strong>
                            <small>Need another pass</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-cold">
                            <span class="sidebar-stat-label">Stale</span>
                            <strong>{{ $mistakeStats['stale_count'] }}</strong>
                            <small>7 days+</small>
                        </div>
                        <div class="sidebar-stat-card sidebar-stat-card-ready">
                            <span class="sidebar-stat-label">Close</span>
                            <strong>{{ $mistakeStats['close_review_count'] }}</strong>
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
                            @if($mistakeStats['top_weak_course'])
                                {{ $mistakeStats['top_weak_course']->name }} is carrying the biggest review load.
                            @else
                                Your review queue is balanced across courses.
                            @endif
                        </p>
                        <div class="sidebar-chip-list">
                            @if($mistakeStats['top_weak_course'])
                                <span class="sidebar-chip">{{ $mistakeStats['top_weak_course']->name }}</span>
                                <span class="sidebar-chip">{{ $mistakeStats['top_weak_course']->mistake_count }} flagged</span>
                            @endif
                            <span class="sidebar-chip">{{ $mistakeStats['cold_review_count'] }} cold</span>
                            <span class="sidebar-chip">{{ $mistakeStats['written_count'] }} written</span>
                            @if($mistakeStats['oldest_mistake'] && $mistakeStats['oldest_mistake']->last_attempt_date)
                                <span class="sidebar-chip">Oldest {{ $mistakeStats['oldest_mistake']->last_attempt_date->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
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
                <div class="header-actions d-flex align-items-center justify-content-end gap-3 flex-wrap">
                    <button id="flashcardModeToggle" class="flashcard-mode-toggle">
                        <i class="fas fa-sync-alt"></i>
                        Flashcard Mode
                    </button>
                    @if(!$mistakes->isEmpty())
                        <button id="deleteAllMistakesButton" class="delete-all-mistakes-btn" type="button" data-bs-toggle="modal" data-bs-target="#deleteAllMistakesModal">
                            <i class="fas fa-trash"></i>
                            Delete Mistakes
                        </button>
                    @endif
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

                    <div class="d-flex align-items-center flex-grow-1 flex-wrap">
                        <label for="courseFilter" class="me-3">
                            <i class="fas fa-filter me-2 text-primary"></i>
                            <span>Filter by Course:</span>
                        </label>
                        <div class="position-relative flex-grow-1">
                            <select id="courseFilter" name="course" class="form-select">
                                <option value="">All Courses ({{ $totalMistakesCount ?? 0 }})</option>
                                @foreach($mistakeStats['available_courses_sorted'] as $course)
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
                            <button class="remove-question" onclick="removeMistake({{ $mistake->question_id }})">
                                <i class="fas fa-times"></i>
                            </button>

                            <div class="normal-mode-content{{ $mistake->question->image_url ? ' has-question-image' : ' no-question-image' }}">
                                <div class="question-text">
                                    <p>{{ $mistake->question->question }}</p>
                                    @if($mistake->question->image_url)
                                        <img src="{{ asset($mistake->question->image_url) }}" alt="Question Image" class="question-image">
                                    @endif
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
                                                    <label class="form-check-label incorrect">
                                                        UNANSWERED
                                                    </label>
                                                @elseif($mistake->question->question_type === 'written')
                                                    <label class="form-check-label incorrect">
                                                        {{ $submittedOption }}
                                                    </label>
                                                @else
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
                                            <label class="info-box-label">Date</label>
                                            <span class="info-box-value">{{ $mistake->last_attempt_date->format('M j, Y') }}</span>
                                        </div>
                                        @if($mistake->question->topic && $mistake->question->topic->course)
                                        <div class="info-box">
                                            <label class="info-box-label">Topic</label>
                                            <span class="info-box-value">{{ $mistake->question->topic->name }}</span>
                                        </div>
                                        <div class="info-box">
                                            <label class="info-box-label">Course</label>
                                            <span class="info-box-value">{{ $mistake->question->topic->course->name }}</span>
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

    <div class="modal fade" id="deleteAllMistakesModal" tabindex="-1" aria-labelledby="deleteAllMistakesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content delete-mistakes-modal">
                <div class="modal-header">
                    <h2 class="modal-title" id="deleteAllMistakesModalLabel">Delete saved mistakes</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="delete-mistakes-warning">are you sure you want to delete all ur mistakes?</p>
                    <label for="deleteMistakesCourseSelect" class="delete-mistakes-label">Mistakes to delete</label>
                    <select id="deleteMistakesCourseSelect" class="form-select">
                        <option value="">All Courses ({{ $totalMistakesCount ?? 0 }})</option>
                        @foreach($mistakeStats['available_courses_sorted'] as $course)
                            <option value="{{ $course->id }}" {{ $selectedCourse == $course->id ? 'selected' : '' }}>
                                {{ $course->name }} ({{ $course->mistake_count ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="delete-mistakes-no-btn" data-bs-dismiss="modal">No</button>
                    <button type="button" class="delete-mistakes-yes-btn" id="confirmDeleteAllMistakesButton">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.MistakesPageConfig = {
            recentCourse: @json($recentCourse ?? null),
            coursesIndexUrl: @json(route('courses.index')),
            reviewIndexUrl: @json(route('review.index')),
            removeAllMistakesUrl: @json(route('review.remove-all'))
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ $bladeAsset->url('js/mistakes-page.js') }}" defer></script>

    <script>
    /* Mobile Sidebar (Offcanvas) Toggle — Mistakes Page */
    (function() {
        var hamburger = document.getElementById('mistakesMobileHamburger');
        var sidebar   = document.getElementById('mistakesSidebar');
        var backdrop  = document.getElementById('mistakesSidebarBackdrop');
        var closeBtn  = document.getElementById('mistakesSidebarCloseBtn');
        function openSidebar() {
            sidebar.classList.add('is-open');
            backdrop.classList.add('is-visible');
            document.body.classList.add('mistakes-sidebar-open');
            hamburger && hamburger.setAttribute('aria-expanded', 'true');
        }
        function closeSidebar() {
            sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-visible');
            document.body.classList.remove('mistakes-sidebar-open');
            hamburger && hamburger.setAttribute('aria-expanded', 'false');
        }
        hamburger && hamburger.addEventListener('click', function() {
            sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
        });
        backdrop  && backdrop.addEventListener('click', closeSidebar);
        closeBtn  && closeBtn.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    })();
    </script>
    @include('partials.secure-study-protection', ['surfaceLabel' => 'mistake cards', 'watermarkEnabled' => false])
</body>
</html>
