<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Solve Quiz - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- LETHAL AUDIO SYSTEM - NO EXTERNAL DEPENDENCIES -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <link rel="stylesheet" href="{{ asset('css/quiz-solve-page.css') }}">
    
    {{-- Responsive Navigation CSS --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-utils.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-navigation.css') }}">
</head>
<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    {{-- Include Responsive Navigation --}}
    <x-responsive-nav-integration mode="replace" />

    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('topics.forCourse', $selectedCourses->first()->id) }}" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
                @php
                    $profileImageUrl = $currentUserView['profile_image_url'] ?? Auth::user()->getProfileImageUrl();
                    $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff');
                @endphp
                <a href="{{ route('profile') }}" class="profile-circle">
                    <img src="{{ $profileImageUrl }}"
                         alt="Profile Picture"
                         class="profile-image"
                         onerror="this.src='{{ $profileFallbackUrl }}'">
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h1>Solve Quiz</h1>

            @if ($questions->isNotEmpty())
                <div class="progress-bar-container" id="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions answered</div>
                        <div class="timer" id="timer">{{ gmdate('i:s', floor($duration_minutes * 60)) }} remaining</div>
                    </div>
                </div>

                <form id="quiz-form" action="{{ secure_url(route('quiz.grade-solve', [], false)) }}" method="POST">
                    @csrf
                    <input type="hidden" name="time_taken" id="time-taken" value="0">
                    @foreach($questions as $question)
                        @if($question->question_type === 'written')
                            @include('components.question-written', ['question' => $question])
                        @else
                            <div class="question-box {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
                                <div class="question-content-wrapper">
                                    <div class="question-main">
                                        <div class="question-text">{{ $question->question }}</div>

                                        <div class="options">
                                            @php
                                                $allOptions = ['A', 'B', 'C', 'D'];
                                                $visibleOptions = array_values(array_filter($allOptions, fn($opt) => {
                                                    $field = 'option_' . strtolower($opt);
                                                    $val = trim($question->$field ?? '');
                                                    return $val !== '' && $val !== '-';
                                                }));
                                                if (empty($visibleOptions)) $visibleOptions = ['A', 'B', 'C', 'D'];
                                            @endphp
                                            @foreach(collect($visibleOptions)->shuffle() as $option)
                                                @php
                                                    $optionField = 'option_' . strtolower($option);
                                                @endphp
                                                    <div class="form-check">
                                                        <input type="radio" 
                                                               name="answers[{{ $question->id }}]" 
                                                               value="{{ $option }}" 
                                                            id="q{{ $question->id }}_{{ strtolower($option) }}" 
                                                            class="form-check-input">
                                                    <label for="q{{ $question->id }}_{{ strtolower($option) }}" class="form-check-label">
                                                        {{ $option }}. {{ $question->$optionField }}
                                                        </label>
                                                    </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($question->image_url)
                                        <div class="question-image-container">
                                            <img src="{{ asset($question->image_url) }}" 
                                                alt="Question image"
                                                class="question-image"
                                                onerror="this.style.display='none'">
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                    @endforeach

                    <button type="submit" id="submit-btn" class="submit-btn" disabled>Submit Quiz</button>
                </form>
            @else
                <p class="text-center text-danger">No questions available for this quiz.</p>
            @endif
        </div>

    <!-- Quiz Results Popup -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon"></div>
            <h2 class="popup-title" id="popup-title">Quiz Complete!</h2>
            <div class="score-container">
                <div class="popup-score" id="popup-score">0/0</div>
            </div>
            <p class="popup-message" id="popup-message">Keep practicing! You're getting there!</p>
            <div class="popup-buttons">
                <button class="popup-btn review-btn" id="review-btn">
                    <i class="fas fa-search"></i>
                    Review Answers
                </button>
                <a href="{{ route('topics.forCourse', $selectedCourses->first()->id) }}" class="popup-btn view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    Back to Topics
                </a>
            </div>
        </div>
    </div>

    <script>
        window.QuizSolvePageConfig = {
            totalQuestions: {{ $questions->count() }},
            timeLimitSeconds: {{ (int) floor($duration_minutes * 60) }},
            gradeUrl: @json(route('quiz.grade-random')),
            draftKey: @json('quiz-solve:' . request()->fullUrl()),
            questionIds: @json(($questions ?? collect())->pluck('id')->values()->all())
        };
    </script>
    <script src="{{ asset('js/quiz-session-draft.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/quiz-solve-page.js') }}" defer></script>
</body>
</html>
