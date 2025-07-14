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

        .submit-btn.btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .submit-btn.btn-warning:hover:not(:disabled) {
            background: #e0a800;
        }

        .submit-btn.btn-primary {
            background: #007bff;
            color: white;
        }

        .submit-btn.btn-primary:hover:not(:disabled) {
            background: #0056b3;
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
            animation: glow-gold 2s ease-in-out infinite alternate;
        }

        .grade-icon.excellent i {
            color: #ff6b35 !important;
            text-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
            animation: glow-orange 2s ease-in-out infinite alternate;
        }

        .grade-icon.good i {
            color: #40a9ff !important;
            text-shadow: 0 0 10px rgba(64, 169, 255, 0.5);
            animation: pulse-blue 1.5s ease-in-out infinite;
        }

        .grade-icon.improving i {
            color: #ff9800 !important;
            text-shadow: 0 0 10px rgba(255, 152, 0, 0.5);
            animation: pulse-orange 1.5s ease-in-out infinite;
        }

        .grade-icon.needs-improvement i {
            color: #ff4d4f !important;
            text-shadow: 0 0 10px rgba(255, 77, 79, 0.5);
            animation: pulse-red 1.5s ease-in-out infinite;
        }

        /* GOLDEN STANDARD: Enhanced animations for better user feedback */
        @keyframes glow-gold {
            from { text-shadow: 0 0 10px rgba(255, 215, 0, 0.5), 0 0 20px rgba(255, 215, 0, 0.3); }
            to { text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 30px rgba(255, 215, 0, 0.5); }
        }

        @keyframes glow-orange {
            from { text-shadow: 0 0 10px rgba(255, 107, 53, 0.5), 0 0 20px rgba(255, 107, 53, 0.3); }
            to { text-shadow: 0 0 20px rgba(255, 107, 53, 0.8), 0 0 30px rgba(255, 107, 53, 0.5); }
        }

        @keyframes pulse-blue {
            0%, 100% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.1); opacity: 1; }
        }

        @keyframes pulse-orange {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
        }

        @keyframes pulse-red {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
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

        /* GOLDEN STANDARD: Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            color: white;
            font-size: 1.2rem;
            backdrop-filter: blur(5px);
        }

        .loading-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 1rem;
        }

        /* Timer pulsing animation */
        .timer.pulsing {
            animation: timerPulse 1s ease-in-out infinite;
        }

        @keyframes timerPulse {
            0%, 100% { 
                transform: scale(1); 
                opacity: 1; 
            }
            50% { 
                transform: scale(1.05); 
                opacity: 0.8; 
            }
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

    <!-- GOLDEN STANDARD: Add audio elements with enhanced error handling and fallbacks -->
    <audio id="quizPerfectSound" preload="auto" onerror="console.warn('Failed to load quiz-perfect.mp3 - audio feedback disabled')">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mp3">
        <!-- Fallback for browsers that don't support HTML5 audio -->
        Your browser does not support the audio element.
    </audio>
    <audio id="quizPartialSound" preload="auto" onerror="console.warn('Failed to load quiz-partial.mp3 - audio feedback disabled')">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mp3">
        Your browser does not support the audio element.
    </audio>
    <audio id="quizWrongSound" preload="auto" onerror="console.warn('Failed to load quiz-wrong.mp3 - audio feedback disabled')">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mp3">
        Your browser does not support the audio element.
    </audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressBarContainer = document.getElementById('progress-bar-container');
            const submitBtn = document.getElementById('submit-btn');
            const quizForm = document.getElementById('quiz-form');
            const timeTakenField = document.getElementById('time-taken');
            const timerDisplay = document.getElementById('timer');
            
            let selectedAnswers = {};
            
            // GOLDEN STANDARD: Update progress function with robust tracking
            function updateProgress() {
                const totalQuestions = {{ $questions->count() }};
                const answeredQuestions = Object.keys(selectedAnswers).length;
                
                // Update progress bar
                const progressFill = document.getElementById('progress-fill');
                const progressText = document.getElementById('progress-text');
                const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                
                if (progressFill && progressText) {
                    progressFill.style.width = `${progressPercentage}%`;
                    progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                }
                
                // Enable submit button when all questions are answered OR allow partial submission
                if (submitBtn) {
                    submitBtn.disabled = false; // Always allow submission
                    if (answeredQuestions === totalQuestions) {
                        submitBtn.textContent = 'Submit Quiz';
                        submitBtn.classList.remove('btn-warning');
                        submitBtn.classList.add('btn-primary');
                    } else {
                        submitBtn.textContent = `Submit Quiz (${answeredQuestions}/${totalQuestions} answered)`;
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.classList.add('btn-warning');
                    }
                }
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
            let quizSubmitted = false; // Prevent double submission
            
            function updateTimer() {
                if (quizSubmitted) {
                    clearInterval(timerInterval);
                    return;
                }
                
                timeRemaining--;
                timeTaken++;
                
                // Update timer display
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Update hidden field with time taken
                timeTakenField.value = timeTaken;
                
                // Timer expiration logic - GOLDEN STANDARD AUTO-SUBMIT
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    if (!quizSubmitted) {
                        console.log('Timer expired - auto-submitting quiz');
                        quizSubmitted = true;
                        submitQuiz();
                    }
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
            
            // Initialize progress on page load
            updateProgress();

            const popupOverlay = document.getElementById("popup-overlay");
            const popup = document.getElementById("popup");
            const popupTitle = document.getElementById("popup-title");
            const popupScore = document.getElementById("popup-score");
            const popupMessage = document.getElementById("popup-message");
            const reviewButton = document.getElementById("review-btn");
            const viewProgressButton = document.getElementById("view-progress-btn");

            function submitQuiz() {
                // GOLDEN STANDARD SUBMISSION PROTECTION
                if (quizSubmitted) {
                    console.log('Quiz already submitted, preventing duplicate submission');
                    return;
                }
                quizSubmitted = true;
                
                // Clear timer immediately
                clearInterval(timerInterval);
                
                // Disable all inputs and submit button
                document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => input.disabled = true);
                
                // Submit quiz
                const gradeUrl = '{{ secure_url(route('quiz.grade-random', [], false)) }}';

                const formDataObj = {};
                formDataObj.answers = {};
                formDataObj.time_taken = parseInt(document.getElementById('time-taken').value, 10) || 0;

                // GOLDEN STANDARD: Ensure ALL questions are included (answered or unanswered)
                const allQuestionIds = [];
                
                // Collect all multiple choice questions
                document.querySelectorAll('.question-box[data-question-id]').forEach(questionBox => {
                    const questionId = questionBox.dataset.questionId;
                    allQuestionIds.push(questionId);
                    
                    const selectedAnswer = questionBox.querySelector('input:checked');
                    if (selectedAnswer) {
                        formDataObj.answers[questionId] = selectedAnswer.value;
                    } else {
                        // Mark unanswered multiple choice as null for backend to handle as mistake
                        formDataObj.answers[questionId] = null;
                    }
                });
                
                // Collect all written questions
                document.querySelectorAll('.written-answer').forEach(textarea => {
                    const questionId = textarea.getAttribute('data-question-id');
                    if (!allQuestionIds.includes(questionId)) {
                        allQuestionIds.push(questionId);
                    }
                    
                    if (textarea.value.trim() !== '') {
                        formDataObj.answers[questionId] = textarea.value.trim();
                    } else {
                        // Mark unanswered written as null for backend to handle as mistake
                        formDataObj.answers[questionId] = null;
                    }
                });
                
                console.log('Submitting quiz with answers:', formDataObj.answers);
                console.log('Total questions found:', allQuestionIds.length);

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
                    if (document.body.contains(loadingOverlay)) {
                        document.body.removeChild(loadingOverlay);
                    }
                    
                    // GOLDEN STANDARD: Comprehensive response validation and handling
                    console.log('🎯 Quiz grading response received:', data);
                    
                    // Validate response structure
                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid response format received from server');
                    }
                    
                    if (data.success === true || (data.score !== undefined && data.total !== undefined)) {
                        // GOLDEN STANDARD: Handle successful grading with validation
                        console.log('✅ Quiz graded successfully:', {
                            score: data.score,
                            total: data.total || data.total_questions,
                            percentage: data.percentage_grade,
                            message: data.message
                        });
                        
                        // Ensure required fields are present
                        if (data.score === undefined || (data.total === undefined && data.total_questions === undefined)) {
                            console.warn('⚠️ Missing required score data, attempting fallback');
                            
                            // Create fallback data structure
                            const fallbackData = {
                                ...data,
                                score: data.score || 0,
                                total: data.total || data.total_questions || 0,
                                percentage_grade: data.percentage_grade || 0,
                                message: data.message || 'Quiz completed successfully!'
                            };
                            
                            displayQuizResults(fallbackData);
                        } else {
                            displayQuizResults(data);
                        }
                    } else {
                        // Handle error responses
                        const errorMessage = data.error || data.message || 'An error occurred while grading the quiz';
                        console.error('❌ Quiz grading failed:', errorMessage);
                        throw new Error(errorMessage);
                    }
                })
                .catch(error => {
                    // Remove loading overlay
                    if (document.body.contains(loadingOverlay)) {
                        document.body.removeChild(loadingOverlay);
                    }
                    
                    // GOLDEN STANDARD: Comprehensive error handling with categorization
                    console.error('❌ Quiz grading error:', error);
                    
                    // Reset submission state to allow retry
                    quizSubmitted = false;
                    document.querySelectorAll('input, textarea, button[type="submit"]').forEach(input => input.disabled = false);
                    
                    // GOLDEN STANDARD: Categorize errors for better user experience
                    const errorMessage = error.message || 'An error occurred while grading the quiz. Please try again.';
                    const isNetworkError = error.name === 'TypeError' || errorMessage.includes('fetch');
                    const isSessionError = errorMessage.includes('session expired') || errorMessage.includes('no questions found') || errorMessage.includes('422');
                    const isServerError = errorMessage.includes('500') || errorMessage.includes('Internal Server Error');
                    
                    console.log('🔍 Error categorization:', {
                        isNetworkError,
                        isSessionError,
                        isServerError,
                        originalError: errorMessage
                    });
                    
                    if (isSessionError) {
                        // Handle session-related errors
                        Swal.fire({
                            title: '⏰ Session Expired',
                            html: `
                                <div style="text-align: center; margin: 20px 0;">
                                    <p>Your quiz session has expired or is no longer valid.</p>
                                    <p style="color: #666; font-size: 0.9em;">This can happen if you've been inactive for too long or if there was a browser issue.</p>
                                </div>
                            `,
                            icon: 'warning',
                            confirmButtonText: 'Start New Quiz',
                            confirmButtonColor: '#3085d6',
                            allowOutsideClick: false,
                            showCloseButton: true
                        }).then(() => {
                            window.location.href = '{{ route("topics.forCourse", ["course" => $selectedCourses->first()->id]) }}';
                        });
                    } else if (isNetworkError) {
                        // Handle network-related errors
                        Swal.fire({
                            title: '🌐 Connection Issue',
                            html: `
                                <div style="text-align: center; margin: 20px 0;">
                                    <p>There was a problem connecting to the server.</p>
                                    <p style="color: #666; font-size: 0.9em;">Please check your internet connection and try again.</p>
                                </div>
                            `,
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonText: 'Retry Submission',
                            cancelButtonText: 'Go Back',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                console.log('🔄 User requested retry after network error');
                                submitQuiz();
                            } else {
                                console.log('🔙 User chose to go back after network error');
                            }
                        });
                    } else if (isServerError) {
                        // Handle server-related errors
                        Swal.fire({
                            title: '🛠️ Server Error',
                            html: `
                                <div style="text-align: center; margin: 20px 0;">
                                    <p>The server encountered an issue while processing your quiz.</p>
                                    <p style="color: #666; font-size: 0.9em;">This is usually temporary. Your answers are saved locally.</p>
                                </div>
                            `,
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonText: 'Try Again',
                            cancelButtonText: 'Save & Exit',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#ffc107',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                console.log('🔄 User requested retry after server error');
                                submitQuiz();
                            } else {
                                console.log('💾 User chose to save and exit after server error');
                                // Could implement local storage save here
                                window.location.href = '{{ route("topics.forCourse", ["course" => $selectedCourses->first()->id]) }}';
                            }
                        });
                    } else {
                        // Handle generic errors
                        Swal.fire({
                            title: '⚠️ Grading Error',
                            html: `
                                <div style="text-align: center; margin: 20px 0;">
                                    <p>${errorMessage}</p>
                                    <p style="color: #666; font-size: 0.9em;">You can try submitting again or contact support if the problem persists.</p>
                                </div>
                            `,
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonText: 'Retry Submission',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                console.log('🔄 User requested retry after generic error');
                                submitQuiz();
                            } else {
                                console.log('❌ User cancelled after error');
                            }
                        });
                    }
                });
            }

            // Handle form submission - GOLDEN STANDARD
            quizForm.addEventListener("submit", function (event) {
                event.preventDefault();
                
                if (!quizSubmitted) {
                    console.log('Manual quiz submission triggered');
                    submitQuiz();
                }
            });

            // Add event listeners to popup buttons
            reviewButton.addEventListener("click", function () {
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);
            });
        });

        // GOLDEN STANDARD: Enhanced audio feedback system with comprehensive error handling
        function playQuizCompletionSound(score, total) {
            try {
                console.log(`🔊 Playing quiz completion sound for score: ${score}/${total}`);
                
                // GOLDEN STANDARD: Validate inputs
                if (typeof score !== 'number' || typeof total !== 'number' || total === 0) {
                    console.warn('⚠️ Invalid score parameters for audio playback:', { score, total });
                    return;
                }
                
                const percentage = (score / total) * 100;
                console.log(`📊 Audio selection based on ${percentage.toFixed(1)}% performance`);
                
                // GOLDEN STANDARD: Get all sound elements with validation
                const perfectSound = document.getElementById('quizPerfectSound');
                const partialSound = document.getElementById('quizPartialSound');
                const wrongSound = document.getElementById('quizWrongSound');
                
                const audioElements = { perfectSound, partialSound, wrongSound };
                const missingElements = Object.entries(audioElements)
                    .filter(([name, element]) => !element)
                    .map(([name]) => name);
                
                if (missingElements.length > 0) {
                    console.warn('⚠️ Missing audio elements:', missingElements);
                }
                
                // GOLDEN STANDARD: Reset all available sounds to start position
                Object.values(audioElements).forEach(sound => {
                    if (sound) {
                        try {
                            sound.pause();
                            sound.currentTime = 0;
                        } catch (resetError) {
                            console.warn('⚠️ Failed to reset audio element:', resetError.message);
                        }
                    }
                });

                // GOLDEN STANDARD: Enhanced safe audio playback with comprehensive error handling
                function safePlayAudio(audioElement, soundName, scorePercentage) {
                    return new Promise((resolve, reject) => {
                        if (!audioElement) {
                            const warning = `${soundName} audio element not found`;
                            console.warn(`⚠️ ${warning}`);
                            resolve({ played: false, reason: 'element_not_found' });
                            return;
                        }
                        
                        // Check if the audio file is actually loaded
                        if (audioElement.readyState < 2) {
                            console.warn(`⚠️ ${soundName} audio not ready (readyState: ${audioElement.readyState})`);
                            resolve({ played: false, reason: 'not_ready' });
                            return;
                        }
                        
                        // GOLDEN STANDARD: Check browser audio context and user interaction
                        if (document.visibilityState !== 'visible') {
                            console.log(`🔇 ${soundName} skipped - page not visible`);
                            resolve({ played: false, reason: 'page_not_visible' });
                            return;
                        }
                        
                        // GOLDEN STANDARD: Try to play with comprehensive error handling
                        try {
                            const playPromise = audioElement.play();
                            
                            if (playPromise !== undefined) {
                                playPromise
                                    .then(() => {
                                        console.log(`✅ ${soundName} audio played successfully for ${scorePercentage.toFixed(1)}% score`);
                                        resolve({ played: true, reason: 'success' });
                                    })
                                    .catch(error => {
                                        // GOLDEN STANDARD: Handle specific audio errors
                                        if (error.name === 'NotAllowedError') {
                                            console.log(`🔇 ${soundName} blocked by autoplay policy - user interaction required`);
                                            resolve({ played: false, reason: 'autoplay_blocked' });
                                        } else if (error.name === 'NotSupportedError') {
                                            console.warn(`⚠️ ${soundName} format not supported by browser`);
                                            resolve({ played: false, reason: 'format_not_supported' });
                                        } else if (error.name === 'AbortError') {
                                            console.log(`🔇 ${soundName} playback aborted`);
                                            resolve({ played: false, reason: 'aborted' });
                                        } else {
                                            console.error(`❌ ${soundName} playback failed:`, error);
                                            resolve({ played: false, reason: 'playback_error', error: error.message });
                                        }
                                    });
                            } else {
                                console.log(`✅ ${soundName} play() returned undefined (legacy browser)`);
                                resolve({ played: true, reason: 'legacy_success' });
                            }
                        } catch (syncError) {
                            console.error(`❌ ${soundName} synchronous play error:`, syncError);
                            resolve({ played: false, reason: 'sync_error', error: syncError.message });
                        }
                    });
                }

                // GOLDEN STANDARD: Determine and play appropriate sound based on performance
                let selectedAudio = null;
                let soundName = '';
                
                if (percentage >= 100) {
                    selectedAudio = perfectSound;
                    soundName = 'Perfect Score';
                    console.log('🏆 Playing perfect score sound');
                } else if (percentage >= 70) {
                    selectedAudio = partialSound;
                    soundName = 'Good Score';
                    console.log('🎯 Playing good score sound');
                } else if (score === 0 || percentage === 0) {
                    selectedAudio = wrongSound;
                    soundName = 'No Correct Answers';
                    console.log('💪 Playing encouragement sound');
                } else {
                    selectedAudio = partialSound;
                    soundName = 'Partial Score';
                    console.log('📈 Playing partial score sound');
                }
                
                // GOLDEN STANDARD: Play the selected audio with result logging
                if (selectedAudio) {
                    safePlayAudio(selectedAudio, soundName, percentage)
                        .then(result => {
                            if (result.played) {
                                console.log(`✅ Audio feedback completed successfully`);
                            } else {
                                console.log(`🔇 Audio feedback skipped: ${result.reason}`);
                                
                                // GOLDEN STANDARD: Optional visual feedback when audio fails
                                if (result.reason === 'autoplay_blocked') {
                                    // Could add a small visual indicator that audio was blocked
                                    console.log('💡 Consider adding visual feedback for audio-blocked users');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('❌ Unexpected error in audio playback:', error);
                        });
                } else {
                    console.warn('⚠️ No appropriate audio element selected for playback');
                }
                
            } catch (error) {
                console.error('❌ Critical error in playQuizCompletionSound:', error);
                // Don't throw - audio failure should never break the quiz experience
            }
        }

        // GOLDEN STANDARD: Display quiz results with comprehensive error handling and validation
        function displayQuizResults(data) {
            try {
                console.log('🎯 Displaying quiz results:', data);
                
                // GOLDEN STANDARD: Validate data structure
                if (!data || typeof data !== 'object') {
                    throw new Error('Invalid quiz results data received');
                }
                
                // GOLDEN STANDARD: Validate all required DOM elements
                const popup = document.getElementById('popup');
                const popupOverlay = document.getElementById('popup-overlay');
                const popupTitle = document.getElementById('popup-title');
                const popupScore = document.getElementById('popup-score');
                const popupMessage = document.getElementById('popup-message');
                const gradeIcon = document.getElementById('grade-icon');
                
                const missingElements = [];
                if (!popup) missingElements.push('popup');
                if (!popupOverlay) missingElements.push('popup-overlay');
                if (!popupTitle) missingElements.push('popup-title');
                if (!popupScore) missingElements.push('popup-score');
                if (!popupMessage) missingElements.push('popup-message');
                if (!gradeIcon) missingElements.push('grade-icon');
                
                if (missingElements.length > 0) {
                    console.error('❌ Missing quiz result popup elements:', missingElements);
                    throw new Error(`Missing popup elements: ${missingElements.join(', ')}`);
                }
                
                // GOLDEN STANDARD: Extract and validate score data with fallbacks
                const score = parseInt(data.score) || 0;
                const total = parseInt(data.total || data.total_questions) || 0;
                const percentage = parseFloat(data.percentage_grade) || (total > 0 ? (score / total) * 100 : 0);
                
                if (total === 0) {
                    console.warn('⚠️ Total questions is 0, quiz may be invalid');
                }
                
                console.log('📊 Quiz Score Breakdown:', {
                    score: score,
                    total: total,
                    percentage: percentage.toFixed(1) + '%',
                    message: data.message
                });
                
                // GOLDEN STANDARD: Update popup content with sanitized data
                popupScore.textContent = `${score}/${total}`;
                popupTitle.textContent = 'Quiz Complete!';
                
                // GOLDEN STANDARD: Set grade icon and message based on performance with enhanced feedback
                if (percentage >= 100) {
                    gradeIcon.innerHTML = '<i class="fas fa-trophy" style="color: #FFD700;"></i>';
                    gradeIcon.className = 'grade-icon perfect';
                    popupTitle.textContent = 'Perfect Score! 🏆';
                    popupMessage.textContent = data.message || 'Outstanding! You achieved a perfect score! 🌟';
                    
                    // GOLDEN STANDARD: Enhanced confetti for perfect scores
                    setTimeout(() => createConfetti(), 300);
                    setTimeout(() => createConfetti(), 600);
                    
                } else if (percentage >= 90) {
                    gradeIcon.innerHTML = '<i class="fas fa-star" style="color: #FF6B35;"></i>';
                    gradeIcon.className = 'grade-icon excellent';
                    popupTitle.textContent = 'Excellent Work! ⭐';
                    popupMessage.textContent = data.message || 'Amazing performance! You\'re mastering this material! 🎯';
                    setTimeout(() => createConfetti(), 400);
                    
                } else if (percentage >= 80) {
                    gradeIcon.innerHTML = '<i class="fas fa-medal" style="color: #4CAF50;"></i>';
                    gradeIcon.className = 'grade-icon good';
                    popupTitle.textContent = 'Great Job! 🥇';
                    popupMessage.textContent = data.message || 'Well done! You\'re doing great! 💪';
                    
                } else if (percentage >= 70) {
                    gradeIcon.innerHTML = '<i class="fas fa-thumbs-up" style="color: #2196F3;"></i>';
                    gradeIcon.className = 'grade-icon good';
                    popupTitle.textContent = 'Good Work! 👍';
                    popupMessage.textContent = data.message || 'Good progress! Keep up the momentum! 📈';
                    
                } else if (percentage >= 50) {
                    gradeIcon.innerHTML = '<i class="fas fa-chart-line" style="color: #FF9800;"></i>';
                    gradeIcon.className = 'grade-icon improving';
                    popupTitle.textContent = 'Keep Improving! 📊';
                    popupMessage.textContent = data.message || 'You\'re making progress! Review and try again! 📚';
                    
                } else {
                    gradeIcon.innerHTML = '<i class="fas fa-redo" style="color: #F44336;"></i>';
                    gradeIcon.className = 'grade-icon needs-improvement';
                    popupTitle.textContent = 'Keep Practicing! 🔄';
                    popupMessage.textContent = data.message || 'Don\'t give up! Every attempt helps you learn! 💪';
                }
                
                // GOLDEN STANDARD: Play appropriate audio feedback with error handling
                try {
                    playQuizCompletionSound(score, total);
                } catch (audioError) {
                    console.warn('⚠️ Audio playback failed:', audioError.message);
                    // Continue without audio - don't break the experience
                }
                
                // GOLDEN STANDARD: Animate popup display with enhanced timing
                popupOverlay.style.display = 'flex';
                popupOverlay.classList.add('show');
                
                // Staggered animation for better visual effect
                setTimeout(() => {
                    popup.classList.add('show');
                    
                    // Add a subtle bounce effect for perfect scores
                    if (percentage >= 100) {
                        setTimeout(() => {
                            popup.style.transform = 'scale(1.05)';
                            setTimeout(() => {
                                popup.style.transform = 'scale(1)';
                            }, 200);
                        }, 200);
                    }
                }, 150);
                
                // GOLDEN STANDARD: Store comprehensive results for review functionality
                window.quizResults = {
                    ...data,
                    displayedAt: new Date().toISOString(),
                    finalScore: score,
                    finalTotal: total,
                    finalPercentage: percentage
                };
                
                console.log('✅ Quiz results displayed successfully');
                
            } catch (error) {
                console.error('❌ Critical error displaying quiz results:', error);
                
                // GOLDEN STANDARD: Comprehensive fallback with user-friendly messaging
                try {
                    // Try to show a basic popup if possible
                    const basicScore = (data && data.score !== undefined) ? data.score : 'Unknown';
                    const basicTotal = (data && (data.total || data.total_questions)) ? (data.total || data.total_questions) : 'Unknown';
                    
                    Swal.fire({
                        title: '🎯 Quiz Complete!',
                        html: `
                            <div style="font-size: 1.2em; margin: 20px 0;">
                                <strong>Score: ${basicScore}/${basicTotal}</strong>
                            </div>
                            <p style="color: #666;">Your quiz has been submitted and scored successfully!</p>
                            ${data && data.message ? `<p style="font-style: italic;">"${data.message}"</p>` : ''}
                        `,
                        icon: 'success',
                        confirmButtonText: 'Continue',
                        confirmButtonColor: '#4CAF50',
                        showCloseButton: true,
                        timer: null,
                        timerProgressBar: false
                    }).then((result) => {
                        if (result.isConfirmed || result.isDismissed) {
                            // Optionally redirect or refresh
                            console.log('User acknowledged quiz completion');
                        }
                    });
                    
                } catch (fallbackError) {
                    console.error('❌ Even fallback display failed:', fallbackError);
                    
                    // Ultimate fallback - browser alert
                    const score = (data && data.score !== undefined) ? data.score : '?';
                    const total = (data && (data.total || data.total_questions)) ? (data.total || data.total_questions) : '?';
                    alert(`Quiz Complete!\nScore: ${score}/${total}\n\nYour quiz has been submitted successfully.`);
                }
            }
        }

        // Function to create confetti
        function createConfetti() {
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                    zIndex: 9999
                });
            }
        }
    </script>
</body>
</html> 