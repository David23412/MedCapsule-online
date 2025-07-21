<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Add cache control -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    
    <!-- TUTOR MODE AUDIO SYSTEM -->
    <audio id="correctChimeSound" preload="auto" volume="0.7">
        <source src="/sounds/correct-chime.mp3" type="audio/mpeg">
    </audio>
    <audio id="wrongAnswerSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizPerfectSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
    </audio>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            zoom: 0.80;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        .navbar-brand {
            color: #007bff;
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            color: #0056b3;
            transform: translateY(-1px);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .profile-circle.no-image {
            background-color: #007bff;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }

        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 1000px;
            margin: 0 auto;
        }

        .question-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .question-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .question-text-container {
            flex: 1;
            min-width: 0; /* Prevents flex item from overflowing */
        }

        .question-text {
            font-size: 1.25rem;
            line-height: 1.6;
            color: #333;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .question-image {
            width: 280px;
            flex-shrink: 0;
        }

        .question-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .options-container {
            width: 100%;
        }

        .option-card {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .option-card:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .option-card.selected {
            border-color: #007bff;
            background: #f0f7ff;
        }

        .option-card.correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .option-card.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .option-letter {
            background: #e9ecef;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
            flex-shrink: 0;
        }

        .progress-bar-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            width: 0;
            transition: width 0.5s ease;
            border-radius: 5px;
        }

        .progress-text {
            text-align: center;
            color: #6c757d;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .timer {
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .timer i {
            color: #007bff;
        }

        .answer-feedback .alert {
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .answer-feedback .alert.alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .answer-feedback .alert.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .question-content {
            overflow: hidden; /* Clear float */
        }

        .options-container {
            clear: both; /* Clear float */
        }

        .option-content {
            font-size: 1rem;
            line-height: 1.5;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }

        .btn-success {
            background: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        .btn-success:hover {
            background: #0056b3 !important;
            border-color: #0056b3 !important;
        }

        @media (max-width: 1100px) {
            .container, .progress-bar-container {
                max-width: 90%;
            }
        }

        @media (max-width: 850px) {
            .container {
                padding: 1.5rem;
                width: 100%;
                border-radius: 8px;
            }
            
            .progress-bar-container {
                transform: translate(-50%, 0);
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .question-content {
                flex-direction: column;
            }

            .question-image {
                width: 100%;
                max-width: 280px;
                margin: 0 auto 1.5rem;
            }

            .question-text {
                font-size: 1.1rem;
            }
        }

        .topic-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .popup-overlay.show {
            display: flex;
        }

        .popup {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            width: 90%;
            position: relative;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .popup.show {
            transform: translateY(0);
            opacity: 1;
        }

        .popup-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-weight: 700;
            animation: slideDown 0.5s ease-out;
        }

        .score-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 2rem;
            border-radius: 16px;
            margin: 1.5rem 0;
            position: relative;
            overflow: hidden;
        }

        .score-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.8), transparent);
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }

        .popup-score {
            font-size: 4rem;
            font-weight: 800;
            color: #007bff;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 123, 255, 0.2);
            animation: scaleIn 0.5s ease-out;
        }

        .popup-message {
            color: #495057;
            font-size: 1.2rem;
            margin: 1.5rem 0;
            line-height: 1.6;
            animation: fadeIn 0.5s ease-out 0.4s both;
        }

        .popup-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }

        .grade-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .grade-icon.perfect {
            color: #ffd700;
        }

        .grade-icon.good {
            color: #52c41a;
        }

        .grade-icon.needs-improvement {
            color: #1890ff;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .grade-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .grade-icon.perfect i {
            color: #FFD700 !important;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .grade-icon.good i {
            color: #40a9ff !important;
            text-shadow: 0 0 10px rgba(64, 169, 255, 0.5);
        }

        .grade-icon.needs-improvement i {
            color: #ff4d4f !important;
            text-shadow: 0 0 10px rgba(255, 77, 79, 0.5);
        }



        @media (max-width: 768px) {
            .popup-buttons {
                flex-direction: column;
        }

        .popup-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }

        .popup-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .retry-btn {
            background: #007bff !important;
            color: white !important;
        }

        .retry-btn:hover {
            background: #0056b3 !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
            text-decoration: none !important;
        }

        .view-progress-btn {
            background: #28a745 !important;
            color: white !important;
        }

        .view-progress-btn:hover {
            background: #218838 !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.2);
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">← Back</a>
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="topic-header mb-4">
                <h1>{{ $topic->name }} - Tutor Mode</h1>
            </div>

            <!-- Source information display -->
            @if(isset($selected_sources) && count($selected_sources) > 0 && !in_array('all', $selected_sources))
                <div class="sources-info text-center mb-4">
                    <span class="text-muted">
                        Source: {{ implode(', ', $selected_sources) }}
                    </span>
                </div>
            @endif

            <!-- Progress bar -->
            <div class="progress-bar-container">
                <div class="timer">
                    <i class="fas fa-clock"></i>
                    <span id="timer">00:00</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions completed</div>
            </div>

            <!-- Questions Container -->
            @foreach($questions as $index => $question)
                <div class="question-card" id="question-{{ $index + 1 }}" style="{{ $index === 0 ? '' : 'display: none;' }}">
                    <div class="question-content">
                        <div class="question-text-container">
                            <div class="question-text">
                                {!! is_array($question) ? $question['question']->question : $question->question !!}
                            </div>
                        </div>

                        @php
                            $qObj = is_array($question) ? $question['question'] : $question;
                        @endphp
                        @if($qObj->image_url ?? false)
                            <div class="question-image">
                                <img src="{{ asset($qObj->image_url) }}" alt="Question Image" class="img-fluid">
                            </div>
                        @endif
                    </div>

                    <div class="options-container" data-question-id="{{ $qObj->id ?? '' }}">
                        @if(($qObj->question_type ?? null) === 'multiple_choice')
                            @foreach(['A' => $qObj->option_a, 'B' => $qObj->option_b, 'C' => $qObj->option_c, 'D' => $qObj->option_d] as $letter => $option)
                                @if($option)
                                    <div class="option-card" data-option="{{ $letter }}" onclick="selectOption(this)">
                                        <span class="option-letter">{{ $letter }}</span>
                                        <div class="option-content">{!! $option !!}</div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="written-answer-container">
                                <textarea class="form-control written-answer" rows="4" placeholder="Type your answer here..."></textarea>
                            </div>
                        @endif
                    </div>

                    <!-- Answer Explanation (Hidden by default) -->
                    <div class="answer-feedback mt-4" style="display: none;">
                        <div class="alert" role="alert">
                            <h5 class="result-text mb-3"></h5>
                            <div class="explanation-content"></div>
                        </div>
                    </div>

                    <div class="actions mt-4">
                        <button class="btn btn-primary check-answer" onclick="checkAnswer({{ $index + 1 }}, {{ $qObj->id ?? '' }})">
                            Check Answer
                        </button>
                        <button class="btn btn-primary next-question" style="display: none;" onclick="showNextQuestion({{ $index + 1 }})">
                            Next Question
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Add audio elements -->
    <audio id="quizPerfectSound" preload="auto">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mpeg">
    </audio>

    <!-- Quiz Results Popup - EXACT COPY FROM SOLVE_QUIZ -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon"></div>
            <h2 class="popup-title" id="popup-title">Quiz Complete!</h2>
            <div class="score-container">
                <div class="popup-score" id="popup-score">0/0</div>
                <div class="score-details" id="score-details"></div>
            </div>
            <p class="popup-message" id="popup-message">Keep practicing! You're getting there!</p>
            <div class="popup-buttons">
                <button class="popup-btn retry-btn" id="retry-btn" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i>
                    Retry Quiz
                </button>
                <a href="{{ route('topics.forCourse', ['course' => $course->id]) }}" class="popup-btn view-progress-btn" id="view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    Back to Topics
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v={{ time() }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js?v={{ time() }}"></script>
    
    <script>
        // LIGHTNING FAST FUNCTION DEFINITIONS 🚀
        function selectOption(element) {
            try {
                const container = element.closest('.options-container');
                if (!container) return;
                
                // OPTIMIZED: Remove selected from siblings only
                const siblings = container.querySelectorAll('.option-card');
                siblings.forEach(opt => opt.classList.remove('selected'));
                
                // CLEAN SELECTION FEEDBACK
                element.classList.add('selected');
                
            } catch (error) {
                console.error('selectOption error:', error);
            }
        }

        // OPTIMIZED EVENT DELEGATION FOR BETTER PERFORMANCE
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.option-card')) {
                    selectOption(e.target.closest('.option-card'));
                }
            });
        });

        // [FIX] REMOVE INFINITE RELOAD LOOP
        // if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
        //     window.location.reload(true);
        // }

        let startTime = new Date();
        let currentQuestionIndex = 1;
        let totalQuestions = {{ $questions->count() }};
        let correctAnswers = 0;
        let totalAnswers = 0;
        let questionStartTime = new Date(); // Track time for each question
        let totalStudyTime = 0; // Track total study time
        
        // Timer functionality
        let timerInterval;
        
        function updateTimer() {
            const currentTime = new Date();
            const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
            const minutes = Math.floor(elapsedSeconds / 60);
            const seconds = elapsedSeconds % 60;
            
            document.getElementById('timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timerInterval = setTimeout(updateTimer, 1000);
        }
        
        // Start the timer
        updateTimer();

        // Update progress bar
        function updateProgress(completed, total) {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const percentage = (completed / total) * 100;
            
            progressFill.style.width = `${percentage}%`;
            progressText.textContent = `${completed} of ${total} questions completed`;
        }



        // Function to check answer
        // LIGHTNING FAST OPTIMIZED ANSWER CHECKING 🚀
        function checkAnswer(questionNumber, questionId) {
            const startTime = performance.now();
            const questionCard = document.getElementById(`question-${questionNumber}`);
            const writtenAnswer = questionCard.querySelector('.written-answer');
            const selectedOption = questionCard.querySelector('.option-card.selected');
            const checkBtn = questionCard.querySelector('.check-answer');
            
            // INSTANT VALIDATION
            let answer = '';
            if (writtenAnswer) {
                answer = writtenAnswer.value.trim();
            } else if (selectedOption) {
                answer = selectedOption.getAttribute('data-option');
            }

            if (!answer) {
                // PROFESSIONAL FEEDBACK - NO ANIMATION
                return;
            }

            // INSTANT UI FEEDBACK - NO LOADING ANIMATION
            if (checkBtn) {
                checkBtn.disabled = true;
                checkBtn.innerHTML = 'Checking...';
                checkBtn.style.background = '#6c757d';
            }

            // OPTIMIZED FETCH - MINIMAL PAYLOAD
            fetch(`/quiz/tutor/check/${questionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ answer, question_number: questionNumber })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                const endTime = performance.now();
                console.log(`⚡ Answer checked in ${Math.round(endTime - startTime)}ms`);
                
                if (!data.success) throw new Error(data.error || 'Check failed');

                // INSTANT COUNTERS
                totalAnswers++;
                if (data.is_correct) correctAnswers++;

                // INSTANT UI UPDATE
                updateQuestionUI(questionNumber, {
                    correct: data.is_correct,
                    explanation: data.explanation,
                    correctAnswer: data.correct_answer
                });

                // FINAL RESULTS
                if (totalAnswers === totalQuestions) {
                    const percentage = (correctAnswers / totalAnswers) * 100;
                    showQuizResults({
                        correct: correctAnswers,
                        total: totalAnswers,
                        percentage: percentage
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'An error occurred while checking your answer.');
                
                // Re-enable inputs on error
                questionCard.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
            });
        }

        // Function to show next question
        function showNextQuestion(currentNumber) {
            const currentQuestion = document.getElementById(`question-${currentNumber}`);
            const nextQuestion = document.getElementById(`question-${currentNumber + 1}`);
            
            if (currentQuestion) {
                currentQuestion.style.display = 'none';
            }
            
            if (nextQuestion) {
                nextQuestion.style.display = 'block';
                currentQuestionIndex = currentNumber + 1;
                updateProgress(currentNumber + 1, totalQuestions);
            } else {
                // No more questions, show final results
                const percentage = (correctAnswers / totalAnswers) * 100;
                showQuizResults({
                    correct: correctAnswers,
                    total: totalAnswers,
                    percentage: percentage
                });
            }
        }

        // Function to update question UI after checking answer
        function updateQuestionUI(questionNumber, data) {
            const questionCard = document.getElementById(`question-${questionNumber}`);
            const feedbackDiv = questionCard.querySelector('.answer-feedback');
            const optionsContainer = questionCard.querySelector('.options-container');
            
            // Show feedback
            feedbackDiv.style.display = 'block';
            const alertDiv = feedbackDiv.querySelector('.alert');
            alertDiv.className = `alert ${data.correct ? 'alert-success' : 'alert-danger'}`;
            
            // Update feedback text
            feedbackDiv.querySelector('.result-text').textContent = data.correct ? 'Correct! 🎉' : 'Incorrect';
            
            // PLAY INDIVIDUAL QUESTION SOUND (but not on last question to avoid double sound)
            if (totalAnswers < totalQuestions) {
                playIndividualQuestionSound(data.correct);
            }
            
            // Show explanation and correct answer if incorrect
            let explanationHtml = data.explanation || '';
            if (!data.correct && data.correctAnswer) {
                explanationHtml = `<strong>Correct Answer: ${data.correctAnswer}</strong><br><br>${explanationHtml}`;
            }
            feedbackDiv.querySelector('.explanation-content').innerHTML = explanationHtml;

            // Update option styling
            const selectedOption = questionCard.querySelector('.option-card.selected');
            const writtenAnswer = questionCard.querySelector('.written-answer');
            
            if (writtenAnswer) {
                writtenAnswer.disabled = true;
                writtenAnswer.classList.add(data.correct ? 'correct' : 'incorrect');
            } else if (selectedOption) {
                // Update multiple choice styling
                const options = questionCard.querySelectorAll('.option-card');
                options.forEach(option => {
                    option.classList.remove('selected', 'correct', 'incorrect');
                    if (option === selectedOption) {
                        option.classList.add(data.correct ? 'correct' : 'incorrect');
                    } else if (!data.correct && option.getAttribute('data-option') === data.correctAnswer) {
                        option.classList.add('correct');
                    }
                });
            }

            // Show next question button and hide check answer button
            const checkAnswerBtn = questionCard.querySelector('.check-answer');
            const nextQuestionBtn = questionCard.querySelector('.next-question');
            
            if (checkAnswerBtn) checkAnswerBtn.style.display = 'none';
            if (nextQuestionBtn) {
                nextQuestionBtn.style.display = 'block';
                nextQuestionBtn.disabled = false; // FIX: Ensure Next Question is enabled
            }

            // FIX: Update progress bar immediately after checking answer
            updateProgress(totalAnswers, totalQuestions);
        }

        // Function to show quiz results
        function showQuizResults(results) {
            const popupOverlay = document.getElementById('popup-overlay');
            const popup = document.getElementById('popup');
            const gradeIcon = document.getElementById('grade-icon');
            const popupTitle = document.getElementById('popup-title');
            const popupScore = document.getElementById('popup-score');
            const popupMessage = document.getElementById('popup-message');

            // Show score as X/Y, not percentage
            popupScore.textContent = `${results.correct}/${results.total}`;

            // Set appropriate icon and message based on score
            const percentage = (results.correct / results.total) * 100;
            if (percentage === 100) {
                gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                gradeIcon.className = 'grade-icon perfect';
                popupTitle.textContent = 'Perfect Score!';
                popupMessage.textContent = 'Outstanding! You\'ve mastered this topic!';
                // PERFECT CENTER CONFETTI
                if (typeof confetti !== 'undefined') {
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { x: 0.5, y: 0.5 },
                        colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                        zIndex: 10000
                    });
                }
                playFinalGradingSound(results.correct, results.total);
            } else if (percentage >= 70) {
                gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                gradeIcon.className = 'grade-icon good';
                popupTitle.textContent = 'Well Done!';
                popupMessage.textContent = 'Good progress! Keep up the great work!';
                playFinalGradingSound(results.correct, results.total);
            } else {
                gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                gradeIcon.className = 'grade-icon needs-improvement';
                popupTitle.textContent = 'Keep Learning!';
                popupMessage.textContent = 'Don\'t give up! Practice makes perfect!';
                playFinalGradingSound(results.correct, results.total);
            }

            // Show popup with animation
            popupOverlay.classList.add('show');
            setTimeout(() => popup.classList.add('show'), 100);
        }



        // Function to play quiz completion sound

    </script>

    <!-- TUTOR MODE AUDIO SYSTEM -->
    <audio id="singleCorrectSound" preload="auto" volume="0.7">
        <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
    </audio>
    <audio id="singleWrongSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
    </audio>
    <!-- Final grading sounds (same as quiz mode) -->
    <audio id="quizPerfectSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>

    <script>
        // TUTOR MODE AUDIO FUNCTIONS
        function playIndividualQuestionSound(isCorrect) {
            const audioElement = isCorrect ? 
                document.getElementById('singleCorrectSound') : 
                document.getElementById('singleWrongSound');
            
            if (audioElement) {
                try {
                    audioElement.volume = 0.7;
                    audioElement.currentTime = 0;
                    audioElement.play().then(() => {
                        console.log(`✅ Individual question sound played: ${isCorrect ? 'correct' : 'wrong'}`);
                    }).catch(error => {
                        console.log(`❌ Individual question sound failed:`, error);
                    });
                } catch (error) {
                    console.log(`❌ Audio element error:`, error);
                }
            }
        }

        // FINAL GRADING SOUND (same as quiz mode)
        function playFinalGradingSound(score, total) {
            const percentage = (score / total) * 100;
            
            let audioElement;
            let soundId;
            
            if (percentage === 100) {
                audioElement = document.getElementById('quizPerfectSound');
                soundId = 'perfect';
            } else if (percentage >= 70) {
                audioElement = document.getElementById('quizPartialSound');
                soundId = 'partial';
            } else {
                audioElement = document.getElementById('quizWrongSound');
                soundId = 'wrong';
            }
            
            if (audioElement) {
                try {
                    audioElement.volume = 0.7;
                    audioElement.currentTime = 0;
                    audioElement.play().then(() => {
                        console.log(`✅ Final grading sound played: ${soundId}`);
                    }).catch(error => {
                        console.log(`❌ Final grading sound failed:`, error);
                    });
                } catch (error) {
                    console.log(`❌ Audio element error:`, error);
                }
            }
        }
    </script>

    <!-- FORCE BIG BEAUTIFUL BUTTON STYLING -->
    <style>
        .popup-btn {
            padding: 1rem 2rem !important;
            border: none !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            font-size: 1.1rem !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.8rem !important;
            text-decoration: none !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            min-width: 150px !important;
            justify-content: center !important;
        }
        .popup-btn.retry-btn {
            background: #007bff !important;
            color: white !important;
        }
        .popup-btn.retry-btn:hover {
            background: #0056b3 !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2) !important;
        }
        .popup-btn.view-progress-btn {
            background: #28a745 !important;
            color: white !important;
        }
        .popup-btn.view-progress-btn:hover {
            background: #218838 !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.2) !important;
        }
        .popup-buttons {
            display: flex !important;
            gap: 1rem !important;
            justify-content: center !important;
            flex-wrap: wrap !important;
            margin-top: 2rem !important;
        }
        /* ENSURE CONFETTI APPEARS ABOVE MODAL */
        canvas {
            z-index: 10001 !important;
            pointer-events: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
    </style>
</body>
</html> 