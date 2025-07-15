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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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

        .mistakes-link {
            background: linear-gradient(135deg, #fff5f5, #ffe3e3);
            color: #e03131;
            border: 1px solid #ffa8a8;
        }

        .mistakes-link:hover {
            background: linear-gradient(135deg, #ffe3e3, #ffa8a8);
            color: #c92a2a;
            border-color: #ff6b6b;
        }

        .mistakes-link i {
            color: #e03131;
        }

        .mistakes-link:hover i {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .progress-bar-container {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
            padding: 1rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            max-width: 800px;
            width: 100%;
        }

        .progress-bar-container.at-form {
            position: relative;
            bottom: auto;
            transform: none;
            left: auto;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
            background:
                linear-gradient(90deg, #007bff, #00d4ff),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20" viewBox="0 0 120 20"><path fill="%23ffffff" fill-opacity="0.3" d="M0 10 Q 10 0, 20 10 T 40 10 T 60 10 T 80 10 T 100 10 T 120 10 V20 H0 Z"/></svg>') repeat-x;
            background-size: 100% 100%, 60px 20px;
            background-repeat: no-repeat, repeat-x;
            background-position: 0 0, 0 0;
            width: 0;
            transition: width 0.3s ease;
            border-radius: 5px;
            animation: waveMove 2s linear infinite, glowPulse 2s ease-in-out infinite alternate;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.5), 0 0 20px rgba(0, 212, 255, 0.3);
        }
        
        @keyframes waveMove {
            0% {
                background-position: 0 0, 0 0;
            }
            100% {
                background-position: 0 0, 60px 0;
            }
        }
        
        @keyframes glowPulse {
            0% {
                box-shadow: 0 0 10px rgba(0, 212, 255, 0.5), 0 0 20px rgba(0, 212, 255, 0.3);
            }
            100% {
                box-shadow: 0 0 20px rgba(0, 212, 255, 0.8), 0 0 30px rgba(0, 212, 255, 0.5);
            }
        }

        .progress-text {
            text-align: center;
            color: #6c757d;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .timer {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .question-box {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .question-text {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .question-box.has-image {
            padding-bottom: 1rem;
        }

        .question-image {
            max-width: 100%;
            max-height: 300px;
            width: auto;
            height: auto;
            border-radius: 8px;
            margin: 1rem auto;
            display: block;
            object-fit: contain;
            transform: perspective(1000px) rotateY(-2deg);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .question-image:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .form-check {
            margin: 0;
            padding: 0;
        }

        .form-check input {
            display: none;
        }

        .form-check label {
            display: block;
            padding: 1rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
        }

        .form-check label:hover {
            background-color: #e9ecef;
            transform: translateY(-1px);
        }

        .form-check input:checked + label {
            background-color: #e3f2fd;
            border-color: #007bff;
            color: #007bff;
        }

        .form-check label.correct {
            background-color: #e3fcef !important;
            border-color: #00a854 !important;
            color: #00a854 !important;
        }

        .form-check label.incorrect {
            background-color: #fff1f0 !important;
            border-color: #f5222d !important;
            color: #f5222d !important;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .submit-btn:hover:not(:disabled) {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
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
            overflow: hidden;
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

        .score-details {
            font-size: 1.2rem;
            color: #6c757d;
            margin-top: 0.5rem;
            animation: fadeIn 0.5s ease-out 0.2s both;
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
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .review-btn {
            background: #f8f9fa;
            color: #495057;
        }

        .review-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .view-progress-btn {
            background: #28a745;
            color: white;
        }

        .view-progress-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.2);
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

        @keyframes shimmer {
            100% { transform: translateX(100%); }
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

        @keyframes confetti {
            0% { transform: translateY(0) rotateZ(0); opacity: 1; }
            100% { transform: translateY(1000px) rotateZ(720deg); opacity: 0; }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #ffd700;
            position: absolute;
            top: -10px;
            z-index: 999;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .popup-buttons {
                flex-direction: column;
            }

            .popup-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 850px) {
            .progress-bar-container {
                max-width: 100%;
                border-radius: 0;
            }
        }

        .profile-circle {
            width: 32px;
            height: 32px;
            background: #2196f3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.2s ease;
            background-size: cover;
            background-position: center;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
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
            <h1>Random Quiz</h1>

            @if ($questions->isNotEmpty())
                <div class="progress-bar-container" id="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions answered</div>
                        <div class="timer" id="timer">{{ gmdate('i:s', $time_limit) }}</div>
                    </div>
                </div>

                <form id="quiz-form" action="{{ secure_url(route('quiz.grade-random', [], false)) }}" method="POST">
                    @csrf
                    <input type="hidden" name="time_taken" id="time-taken" value="0">
                    @foreach($questions as $question)
                        @if($question->question_type === 'written')
                            @include('components.question-written', ['question' => $question])
                        @else
                            <div class="question-box {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
                                <div class="question-text">{{ $question->question }}</div>

                                @if($question->image_url)
                                    <img src="{{ asset($question->image_url) }}"
                                        alt="Question image"
                                        class="question-image"
                                        onerror="this.style.display='none'">
                                @endif

                                <div class="options">
                                    @foreach(['A', 'B', 'C', 'D'] as $option)
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
                        @endif
                    @endforeach

                    <button type="submit" id="submit-btn" class="submit-btn" disabled>Submit Quiz</button>
                </form>
            @else
                <p class="text-center text-danger">No questions available for this quiz.</p>
            @endif
        </div>
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
                <a href="{{ route('topics.forCourse', ['course' => $selectedCourses->first()->id]) }}" class="popup-btn view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    Back to Topics
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressBarContainer = document.getElementById('progress-bar-container');
            const submitBtn = document.getElementById('submit-btn');
            const quizForm = document.getElementById('quiz-form');
            const timeTakenField = document.getElementById('time-taken');
            const timerDisplay = document.getElementById('timer');
            
            let selectedAnswers = {};
            
            // Update progress function
            function updateProgress() {
                const totalQuestions = {{ $questions->count() }};
                const answeredQuestions = Object.keys(selectedAnswers).length;
                
                // Update progress bar
                const progressFill = document.getElementById('progress-fill');
                const progressText = document.getElementById('progress-text');
                const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                
                progressFill.style.width = `${progressPercentage}%`;
                progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                
                // Enable submit button when all questions are answered
                submitBtn.disabled = answeredQuestions !== totalQuestions;
            }
            
            // Handle multiple choice question answers
            document.querySelectorAll('.form-check input').forEach(input => {
                input.addEventListener('change', function() {
                    const questionId = this.name.match(/\[(\d+)\]/)[1];
                    selectedAnswers[questionId] = this.value;
                    updateProgress();
                });
            });
            
            // Handle written question answers
            document.querySelectorAll('.written-answer').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    const questionId = this.getAttribute('data-question-id');
                    if (this.value.trim() !== '') {
                        selectedAnswers[questionId] = this.value;
                    } else {
                        delete selectedAnswers[questionId];
                    }
                    updateProgress();
                });
            });

            // Timer functionality
            let timeLimit = {{ $time_limit }};
            let timeRemaining = timeLimit;
            let timeTaken = 0;
            let timerInterval;
            
            function updateTimer() {
                timeRemaining--;
                timeTaken++;
                
                // Update timer display
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Update hidden field with time taken
                timeTakenField.value = timeTaken;
                
                // Timer expiration logic
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    // Submit quiz automatically without alert
                    submitQuiz();
                }
                
                // Visual indication when time is running low
                if (timeRemaining <= 30) {
                    timerDisplay.style.color = '#ff4d4f';
                    timerDisplay.style.fontWeight = '700';
                    
                    if (timeRemaining <= 10) {
                        timerDisplay.classList.add('pulsing');
                    }
                }
            }
            
            // Start the timer
            timerInterval = setInterval(updateTimer, 1000);

            const popupOverlay = document.getElementById("popup-overlay");
            const popup = document.getElementById("popup");
            const popupTitle = document.getElementById("popup-title");
            const popupScore = document.getElementById("popup-score");
            const popupMessage = document.getElementById("popup-message");
            const reviewButton = document.getElementById("review-btn");
            const viewProgressButton = document.getElementById("view-progress-btn");

            function submitQuiz() {
                // Disable all inputs and submit button
                document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => input.disabled = true);
                
                // Submit quiz
                const gradeUrl = '{{ secure_url(route('quiz.grade-random', [], false)) }}';

                const formDataObj = {};
                formDataObj.answers = {};
                formDataObj.time_taken = parseInt(document.getElementById('time-taken').value, 10) || 0;

                // Add all questions (answered and unanswered) to formData
                document.querySelectorAll('.question-box').forEach(questionBox => {
                    const questionId = questionBox.dataset.questionId;
                    const selectedAnswer = questionBox.querySelector('input:checked');
                    if (selectedAnswer) {
                        formDataObj.answers[questionId] = selectedAnswer.value;
                    } else if (!formDataObj.answers.hasOwnProperty(questionId)) {
                        // Mark unanswered multiple choice as empty string
                        formDataObj.answers[questionId] = '';
                    }
                });
                // Add written answers to formData
                document.querySelectorAll('.written-answer').forEach(textarea => {
                    const questionId = textarea.getAttribute('data-question-id');
                    if (textarea.value.trim() !== '') {
                        formDataObj.answers[questionId] = textarea.value;
                    } else {
                        formDataObj.answers[questionId] = '';
                    }
                });

                // Show loading state
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay';
                loadingOverlay.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Grading your quiz...</div>
                `;
                document.body.appendChild(loadingOverlay);

                // Configure fetch options with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

                const retryFetch = async (url, options, maxRetries = 3, delay = 1000) => {
                    for (let i = 0; i < maxRetries; i++) {
                        try {
                            const response = await fetch(url, { ...options, signal: controller.signal });
                            
                            if (!response.ok) {
                                const data = await response.json();
                                throw new Error(data.error || `HTTP error! status: ${response.status}`);
                            }
                            
                            clearTimeout(timeoutId);
                            return await response.json();
                        } catch (error) {
                            if (error.name === 'AbortError') {
                                throw new Error('Request timed out. Please try again.');
                            }
                            
                            if (i === maxRetries - 1) {
                                throw error;
                            }
                            
                            // Wait before retrying
                            await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
                        }
                    }
                };

                retryFetch(gradeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formDataObj)
                })
                .then(data => {
                    // Remove loading overlay
                    document.body.removeChild(loadingOverlay);
                    
                    if (data.success) {
                        // Handle success
                        displayQuizResults(data);
                    } else {
                        throw new Error(data.error || 'An error occurred while grading the quiz');
                    }
                })
                .catch(error => {
                    // Remove loading overlay
                    if (document.body.contains(loadingOverlay)) {
                        document.body.removeChild(loadingOverlay);
                    }
                    
                    // Show error message
                    const errorMessage = error.message || 'An error occurred while grading the quiz. Please try again.';
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }

            // Handle form submission
            quizForm.addEventListener("submit", function (event) {
                event.preventDefault();
                clearInterval(timerInterval);
                submitQuiz();
            });

            // Add event listeners to popup buttons
            reviewButton.addEventListener("click", function () {
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);
            });
        });

        // Function to play quiz completion sound based on score
        function playQuizCompletionSound(score, total) {
            console.log(`Playing sound for score: ${score}/${total}`);
            const percentage = (score / total) * 100;
            
            // Get all sound elements
            const perfectSound = document.getElementById('quizPerfectSound');
            const partialSound = document.getElementById('quizPartialSound');
            const wrongSound = document.getElementById('quizWrongSound');
            
            // Reset all sounds to start
            [perfectSound, partialSound, wrongSound].forEach(sound => {
                if (sound) {
                    sound.pause();
                    sound.currentTime = 0;
                }
            });

            // Function to safely play audio with user interaction check
            function safePlayAudio(audioElement, soundName) {
                if (!audioElement) {
                    console.warn(`${soundName} audio element not found`);
                    return;
                }
                
                // Check if user has interacted with the page
                if (document.visibilityState === 'visible') {
                    // Try to play with user interaction context
                    const playPromise = audioElement.play();
                    
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => {
                                console.log(`${soundName} played successfully`);
                            })
                            .catch(error => {
                                // Handle autoplay policy restrictions
                                if (error.name === 'NotAllowedError') {
                                    console.log(`${soundName} blocked by autoplay policy - user interaction required`);
                                    // Optionally show a message to user about enabling audio
                                } else {
                                    console.error(`${soundName} play failed:`, error);
                                }
                            });
                    }
                } else {
                    console.log(`${soundName} skipped - page not visible`);
                }
            }

                // Play appropriate sound based on score
                if (percentage === 100) {
                safePlayAudio(perfectSound, 'Perfect');
                } else if (score === 0) {
                    // This will trigger for both wrong answers and unanswered questions
                safePlayAudio(wrongSound, 'Wrong');
                } else {
                safePlayAudio(partialSound, 'Partial');
            }
        }

        // Function to create confetti
        function createConfetti() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                zIndex: 9999
            });
        }
    </script>
</body>
</html> 