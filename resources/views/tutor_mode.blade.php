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

    

    <!-- 💀 LETHAL AUDIO POOL SYSTEM - 5 READY ELEMENTS PER SOUND 💀 -->
    <div id="audio-pool" style="display: none;">
        <!-- CORRECT SOUND POOL -->
        <audio class="audio-pool-correct" preload="auto" volume="0.7">
            <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-correct" preload="auto" volume="0.7">
            <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-correct" preload="auto" volume="0.7">
            <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-correct" preload="auto" volume="0.7">
            <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-correct" preload="auto" volume="0.7">
            <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
        </audio>
        
        <!-- WRONG SOUND POOL -->
        <audio class="audio-pool-wrong" preload="auto" volume="0.7">
            <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-wrong" preload="auto" volume="0.7">
            <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-wrong" preload="auto" volume="0.7">
            <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-wrong" preload="auto" volume="0.7">
            <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-wrong" preload="auto" volume="0.7">
            <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
        </audio>
        
        <!-- PERFECT SOUND POOL -->
        <audio class="audio-pool-perfect" preload="auto" volume="0.7">
            <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-perfect" preload="auto" volume="0.7">
            <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-perfect" preload="auto" volume="0.7">
            <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-perfect" preload="auto" volume="0.7">
            <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-perfect" preload="auto" volume="0.7">
            <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
        </audio>
        
        <!-- PARTIAL SOUND POOL -->
        <audio class="audio-pool-partial" preload="auto" volume="0.7">
            <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-partial" preload="auto" volume="0.7">
            <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-partial" preload="auto" volume="0.7">
            <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-partial" preload="auto" volume="0.7">
            <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
        </audio>
        <audio class="audio-pool-partial" preload="auto" volume="0.7">
            <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
        </audio>
        

    </div>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <!-- ERROR SUPPRESSION DISABLED FOR DEBUGGING -->
    <!-- <script src="{{ asset('js/lethal-error-suppression.js') }}"></script> -->
    
    <!-- 💀 NUCLEAR SPEED OPTIMIZATIONS 💀 -->
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="dns-prefetch" href="{{ url('/') }}">

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
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">← Back</a>
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
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
                                    <div class="option-card" data-option="{{ $letter }}">
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
                <button class="popup-btn continue-btn" id="continue-btn" onclick="window.location.href='/topics/{{ $course->id }}'">
                    <i class="fas fa-arrow-right"></i>
                    Continue Learning
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v={{ time() }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js?v={{ time() }}"></script>
    
    <script>
        // 💀 LETHAL AUDIO POOL SYSTEM - 0MS INSTANT PLAYBACK 💀
        let audioPool = {
            correct: [],
            wrong: [],
            perfect: [],
            partial: []
        };
        let poolReplenishmentTimer = null;
        let poolReplenishmentInterval = null;
        
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

        // 💀 LETHAL AUDIO POOL INITIALIZATION 💀
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔥 LETHAL AUDIO POOL: Initializing bulletproof sound system...');
            
            // Initialize audio pools
            initializeAudioPools();
            
            // Start pool replenishment
            startPoolReplenishment();
            
            // Event delegation for option selection
            document.addEventListener('click', function(e) {
                if (e.target.closest('.option-card')) {
                    selectOption(e.target.closest('.option-card'));
                }
            });
            
            console.log('✅ LETHAL AUDIO POOL: System ready for 0ms instant playback');
        });
        
        // 💀 AUDIO POOL INITIALIZATION 💀
        function initializeAudioPools() {
            // Load existing audio elements into pools
            const correctElements = document.querySelectorAll('.audio-pool-correct');
            const wrongElements = document.querySelectorAll('.audio-pool-wrong');
            const perfectElements = document.querySelectorAll('.audio-pool-perfect');
            const partialElements = document.querySelectorAll('.audio-pool-partial');
            
            correctElements.forEach(audio => {
                audioPool.correct.push(audio);
                setupAudioElement(audio);
            });
            
            wrongElements.forEach(audio => {
                audioPool.wrong.push(audio);
                setupAudioElement(audio);
            });
            
            perfectElements.forEach(audio => {
                audioPool.perfect.push(audio);
                setupAudioElement(audio);
            });
            
            partialElements.forEach(audio => {
                audioPool.partial.push(audio);
                setupAudioElement(audio);
            });
            
            console.log(`🔥 LETHAL AUDIO POOL: Initialized - Correct: ${audioPool.correct.length}, Wrong: ${audioPool.wrong.length}, Perfect: ${audioPool.perfect.length}, Partial: ${audioPool.partial.length}`);
        }
        
        // 💀 AUDIO ELEMENT SETUP 💀
        function setupAudioElement(audio) {
            audio.volume = 0.7;
            audio.preload = 'auto';
            
            // Add event listeners for pool management
            audio.addEventListener('ended', function() {
                // Wait a bit longer before returning to pool to ensure full playback
                setTimeout(() => {
                    returnToPool(audio);
                }, 200);
            });
            
            audio.addEventListener('error', function() {
                console.log('ℹ️ Audio element error, returning to pool');
                returnToPool(audio);
            });
        }
        
        // 💀 POOL REPLENISHMENT SYSTEM 💀
        function startPoolReplenishment() {
            // Check pool levels every 5 seconds
            poolReplenishmentInterval = setInterval(() => {
                replenishPools();
            }, 5000);
            
            // Initial replenishment check
            setTimeout(() => {
                replenishPools();
            }, 1000);
        }
        
        // 💀 POOL REPLENISHMENT 💀
        function replenishPools() {
            const minPoolSize = 3;
            
            // Replenish correct pool
            if (audioPool.correct.length < minPoolSize) {
                const needed = minPoolSize - audioPool.correct.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('correct');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished correct pool with ${needed} elements`);
            }
            
            // Replenish wrong pool
            if (audioPool.wrong.length < minPoolSize) {
                const needed = minPoolSize - audioPool.wrong.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('wrong');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished wrong pool with ${needed} elements`);
            }
            
            // Replenish perfect pool
            if (audioPool.perfect.length < minPoolSize) {
                const needed = minPoolSize - audioPool.perfect.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('perfect');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished perfect pool with ${needed} elements`);
            }
            
            // Replenish partial pool
            if (audioPool.partial.length < minPoolSize) {
                const needed = minPoolSize - audioPool.partial.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('partial');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished partial pool with ${needed} elements`);
            }
            
            // Replenish perfect pool
            if (audioPool.perfect.length < minPoolSize) {
                const needed = minPoolSize - audioPool.perfect.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('perfect');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished perfect pool with ${needed} elements`);
            }
            
            // Replenish partial pool
            if (audioPool.partial.length < minPoolSize) {
                const needed = minPoolSize - audioPool.partial.length;
                for (let i = 0; i < needed; i++) {
                    createAudioElement('partial');
                }
                console.log(`🔥 LETHAL AUDIO POOL: Replenished partial pool with ${needed} elements`);
            }
            

        }
        
        // 💀 CREATE FRESH AUDIO ELEMENT 💀
        function createAudioElement(type) {
            const audio = new Audio();
            let sourceUrl = '';
            
            switch(type) {
                case 'correct':
                    sourceUrl = '/sounds/Single-correct.mp3';
                    break;
                case 'wrong':
                    sourceUrl = '/sounds/quiz-wrong.mp3';
                    break;
                case 'perfect':
                    sourceUrl = '/sounds/quiz-perfect.mp3';
                    break;
                case 'partial':
                    sourceUrl = '/sounds/quiz-partial.mp3';
                    break;
            }
            
            audio.src = sourceUrl;
            audio.volume = 0.7;
            audio.preload = 'auto';
            
            // Add to pool
            audioPool[type].push(audio);
            
            // Setup event listeners
            audio.addEventListener('ended', function() {
                // Wait a bit longer before returning to pool to ensure full playback
                setTimeout(() => {
                    returnToPool(audio, type);
                }, 200);
            });
            
            audio.addEventListener('error', function() {
                console.log(`ℹ️ Fresh audio element error for ${type}, removing from pool`);
                removeFromPool(audio, type);
            });
            
            console.log(`🔥 LETHAL AUDIO POOL: Created fresh ${type} audio element`);
        }
        
        // 💀 RETURN AUDIO TO POOL 💀
        function returnToPool(audio, type = null) {
            if (!type) {
                // Determine type from class or source
                if (audio.classList.contains('audio-pool-correct') || audio.src.includes('Single-correct')) {
                    type = 'correct';
                } else if (audio.classList.contains('audio-pool-wrong') || audio.src.includes('quiz-wrong')) {
                    type = 'wrong';
                } else if (audio.classList.contains('audio-pool-perfect') || audio.src.includes('quiz-perfect')) {
                    type = 'perfect';
                } else if (audio.classList.contains('audio-pool-partial') || audio.src.includes('quiz-partial')) {
                    type = 'partial';
                }
            }
            
            if (type && audioPool[type]) {
                // Reset audio completely
                audio.currentTime = 0;
                audio.pause();
                
                // Ensure audio is fully stopped
                try {
                    audio.stop && audio.stop();
                } catch (e) {
                    // Ignore errors if stop method doesn't exist
                }
                
                // Add back to pool if not already there
                if (!audioPool[type].includes(audio)) {
                    audioPool[type].push(audio);
                    console.log(`🔥 LETHAL AUDIO POOL: Returned ${type} audio to pool (${audioPool[type].length} available)`);
                }
            }
        }
        
        // 💀 REMOVE FROM POOL 💀
        function removeFromPool(audio, type) {
            if (audioPool[type]) {
                const index = audioPool[type].indexOf(audio);
                if (index > -1) {
                    audioPool[type].splice(index, 1);
                }
            }
        }

        // [FIX] REMOVE INFINITE RELOAD LOOP
        // if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
        //     window.location.reload(true);
        // }

        let quizStartTime = new Date();
        let currentQuestionIndex = 1;
        let totalQuestions = {{ $questions->count() }};
        let correctAnswers = 0;
        let totalAnswers = 0;
        let questionStartTime = new Date(); // Track time for each question
        let totalStudyTime = 0; // Track total study time
        
        // CRITICAL FIX: Track all user answers throughout the session
        let userAnswers = {};
        
        // Timer functionality
        let timerInterval;
        
        function updateTimer() {
            const currentTime = new Date();
            const elapsedSeconds = Math.floor((currentTime - quizStartTime) / 1000);
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



        // 💀 LETHAL CLIENT-SIDE ANSWER CHECKING 💀
        let answerCache = {};
        let questionData = {};
        
        // PRELOAD ALL QUESTION DATA ON PAGE LOAD
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔥 PRELOADING QUESTION DATA FOR INSTANT CHECKING...');
            
            // Extract question data from DOM
            @foreach($questions as $index => $question)
                @php $qObj = is_array($question) ? $question['question'] : $question; @endphp
                questionData[{{ $qObj->id ?? 0 }}] = {
                    correct_answer: '{{ $qObj->correct_answer ?? '' }}',
                    explanation: `{!! addslashes($qObj->explanation ?? '') !!}`,
                    question_type: '{{ $qObj->question_type ?? 'multiple_choice' }}'
                };
            @endforeach
            
            console.log('✅ Question data preloaded for', Object.keys(questionData).length, 'questions');
            
            // PERFORMANCE MONITORING
            window.performanceStats = {
                instantChecks: 0,
                cacheHits: 0,
                networkRequests: 0,
                totalTime: 0
            };
            
            window.getPerformanceStats = function() {
                console.log('📊 PERFORMANCE STATS:', window.performanceStats);
                const avgTime = window.performanceStats.totalTime / (window.performanceStats.instantChecks + window.performanceStats.cacheHits + window.performanceStats.networkRequests);
                console.log(`📊 AVERAGE RESPONSE TIME: ${Math.round(avgTime)}ms`);
                return window.performanceStats;
            };
        });
        
        // EXTRACTED ANSWER PROCESSING FUNCTION FOR SPEED
        function processAnswerResult(questionNumber, questionId, data, checkBtn) {
            // 💀 BULLETPROOF DUPLICATE PREVENTION - NO MULTIPLE PROCESSING 💀
            const resultKey = `result_processed_${questionNumber}`;
            if (processAnswerResult[resultKey]) {
                console.log(`🔇 BULLETPROOF: Result already processed for Q${questionNumber}, skipping duplicate`);
                return;
            }
            processAnswerResult[resultKey] = true;
            
            updateQuestionUI(questionNumber, {
                correct: data.is_correct,
                explanation: data.explanation,
                correctAnswer: data.correct_answer
            });

            // FINAL RESULTS CHECK
            if (totalAnswers === totalQuestions) {
                const percentage = (correctAnswers / totalAnswers) * 100;
                
                // Save quiz attempt to database
                saveTutorQuizAttempt(correctAnswers, totalAnswers, percentage);
                
                // Show results (sound will be played in showQuizResults)
                showQuizResults({
                    correct: correctAnswers,
                    total: totalAnswers,
                    percentage: percentage
                });
            }
        }
        
        window.checkAnswer = function checkAnswer(questionNumber, questionId) {
            const startTime = performance.now();
            console.log(`🚀 CHECKING ANSWER: Question ${questionNumber}, ID ${questionId}`);
            
            // 💀 BULLETPROOF DUPLICATE PREVENTION - NO MULTIPLE PROCESSING 💀
            const processingKey = `processing_${questionNumber}`;
            if (checkAnswer[processingKey]) {
                console.log(`🔇 BULLETPROOF: Question ${questionNumber} already being processed, skipping duplicate`);
                return;
            }
            checkAnswer[processingKey] = true;
            
            // INSTANT DOM ACCESS
            const questionCard = document.getElementById(`question-${questionNumber}`);
            if (!questionCard) {
                console.error(`❌ Question card not found: question-${questionNumber}`);
                return;
            }
            
            const writtenAnswer = questionCard.querySelector('.written-answer');
            const selectedOption = questionCard.querySelector('.option-card.selected');
            const checkBtn = questionCard.querySelector('.check-answer');
            
            // INSTANT ANSWER EXTRACTION
            let answer = '';
            if (writtenAnswer) {
                answer = writtenAnswer.value.trim();
            } else if (selectedOption) {
                answer = selectedOption.getAttribute('data-option');
            }

            if (!answer) {
                console.warn('⚠️ No answer selected');
                return;
            }

            console.log(`📝 Answer selected: "${answer}"`);
            
            // 💀 INSTANT CLIENT-SIDE CHECKING - NO NETWORK DELAY 💀
            if (questionData[questionId]) {
                console.log(`⚡ INSTANT CLIENT-SIDE CHECK for Q${questionId}`);
                
                const qData = questionData[questionId];
                const isCorrect = answer.toLowerCase().trim() === qData.correct_answer.toLowerCase().trim();
                
                const instantData = {
                    success: true,
                    is_correct: isCorrect,
                    correct_answer: qData.correct_answer,
                    explanation: qData.explanation
                };
                
                console.log(`✅ INSTANT RESULT: ${isCorrect ? 'CORRECT' : 'WRONG'} (0ms)`);
                
                // TRACK PERFORMANCE
                window.performanceStats.instantChecks++;
                window.performanceStats.totalTime += (performance.now() - startTime);
                
                // INSTANT PROCESSING
                userAnswers[questionId] = answer;
                totalAnswers++;
                if (isCorrect) correctAnswers++;
                
                // INSTANT UI UPDATE
                processAnswerResult(questionNumber, questionId, instantData, checkBtn);
                // Clear processing flag
                delete checkAnswer[processingKey];
                return;
            }
            
            // FALLBACK: CHECK CACHE
            const cacheKey = `${questionId}_${answer}`;
            if (answerCache[cacheKey]) {
                console.log(`⚡ CACHE HIT! Instant response for Q${questionId}`);
                const cachedData = answerCache[cacheKey];
                
                // TRACK PERFORMANCE
                window.performanceStats.cacheHits++;
                window.performanceStats.totalTime += (performance.now() - startTime);
                
                // INSTANT PROCESSING FROM CACHE
                userAnswers[questionId] = answer;
                totalAnswers++;
                if (cachedData.is_correct) correctAnswers++;
                
                // INSTANT UI UPDATE FROM CACHE
                processAnswerResult(questionNumber, questionId, cachedData, checkBtn);
                // Clear processing flag
                delete checkAnswer[processingKey];
                return;
            }

            // LAST RESORT: NETWORK REQUEST
            console.warn(`⚠️ FALLBACK TO NETWORK for Q${questionId}`);
            
            // INSTANT UI FEEDBACK
            if (checkBtn) {
                checkBtn.disabled = true;
                checkBtn.textContent = 'Checking...';
                checkBtn.style.background = '#6c757d';
            }

            // 💀 F16 RAPTOR SPEED FETCH 💀
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000); // 2 second timeout
            
            // CACHE CSRF TOKEN FOR SPEED
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/quiz/tutor/check/${questionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache',
                    'Connection': 'keep-alive'
                },
                body: JSON.stringify({ answer }),
                signal: controller.signal,
                keepalive: true
            })
            .then(response => {
                clearTimeout(timeoutId);
                const responseTime = performance.now() - startTime;
                console.log(`⚡ Server response in ${Math.round(responseTime)}ms (Status: ${response.status})`);
                
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }
                
                return response.json();
            })
            .then(data => {
                const totalTime = performance.now() - startTime;
                console.log(`✅ Answer processed in ${Math.round(totalTime)}ms`);
                console.log('📊 Server performance:', data.performance_metrics);
                
                if (!data.success) {
                    throw new Error(data.message || 'Answer check failed');
                }

                // CACHE THE RESULT FOR FUTURE INSTANT ACCESS
                answerCache[cacheKey] = data;
                console.log(`💾 Cached result for Q${questionId}_${answer}`);

                // INSTANT ANSWER STORAGE
                userAnswers[questionId] = answer;
                console.log(`💾 Stored answer: Q${questionId} = "${answer}"`);

                // INSTANT COUNTERS
                totalAnswers++;
                if (data.is_correct) correctAnswers++;

                // PROCESS RESULT
                processAnswerResult(questionNumber, questionId, data, checkBtn);
                // Clear processing flag
                delete checkAnswer[processingKey];
            })
            .catch(error => {
                // LETHAL FIX: Silent error handling - don't spam console or alert user
                console.log('ℹ️ Answer check encountered an issue, providing fallback response');
                
                // Provide fallback response instead of failing
                const fallbackData = {
                    success: false,
                    is_correct: false,
                    error: 'Connection issue',
                    correct_answer: 'Unable to determine',
                    explanation: 'Please check your connection and try again'
                };
                
                // Process the fallback data as if it was a normal response
                totalAnswers++;
                // Don't increment correctAnswers since we don't know if it's correct
                
                // Update UI with fallback data
                updateQuestionUI(questionNumber, {
                    correct: fallbackData.is_correct,
                    explanation: fallbackData.explanation,
                    correctAnswer: fallbackData.correct_answer
                });
                
                // Re-enable inputs
                questionCard.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
                
                // Continue with quiz if all questions are answered
                if (totalAnswers === totalQuestions) {
                    const percentage = (correctAnswers / totalAnswers) * 100;
                    
                    // Save attempt and show results (sound will be played in showQuizResults)
                    saveTutorQuizAttempt(correctAnswers, totalAnswers, percentage);
                    showQuizResults({
                        correct: correctAnswers,
                        total: totalAnswers,
                        percentage: percentage
                    });
                }
                
                // Clear processing flag
                delete checkAnswer[processingKey];
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
                
                // Show results without playing sound here (sound will be played in showQuizResults)
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
            
            // 💀 BULLETPROOF SOUND PREVENTION - NO DOUBLE PLAYBACK 💀
            const soundKey = `sound_played_${questionNumber}`;
            const hasPlayedSound = questionCard.dataset[soundKey];
            
            // Show feedback
            feedbackDiv.style.display = 'block';
            const alertDiv = feedbackDiv.querySelector('.alert');
            alertDiv.className = `alert ${data.correct ? 'alert-success' : 'alert-danger'}`;
            
            // Update feedback text
            feedbackDiv.querySelector('.result-text').textContent = data.correct ? 'Correct! 🎉' : 'Incorrect';
            
            // 💀 LETHAL AUDIO POOL - INSTANT SOUND FEEDBACK (ONCE ONLY) 💀
            // Skip individual question sounds for the last question to prevent overlap with final grading sound
            if (totalAnswers < totalQuestions && !hasPlayedSound) {
                console.log(`🔥 LETHAL AUDIO POOL: Playing instant sound for Q${questionNumber} (${data.correct ? 'correct' : 'wrong'}) - FIRST TIME ONLY`);
                playInstantSound(data.correct);
                // Mark this question as having played sound
                questionCard.dataset[soundKey] = 'true';
            } else if (hasPlayedSound) {
                console.log(`🔇 LETHAL AUDIO POOL: Sound already played for Q${questionNumber}, skipping duplicate`);
            } else {
                console.log(`🔇 LETHAL AUDIO POOL: Skipping sound for final question Q${questionNumber} to prevent overlap with final grading sound`);
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
            console.log('🚀 SHOW QUIZ RESULTS CALLED - STARTING INSTANT SOUND');
            const soundStart = performance.now();
            
            // 💀 NUCLEAR INSTANT GRADING SOUND - PLAYS IMMEDIATELY 💀
            playFinalGradingSound(results.correct, results.total);
            
            console.log(`✅ SOUND TRIGGERED in ${(performance.now() - soundStart).toFixed(2)}ms`);
            
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
            } else if (percentage >= 70) {
                gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                gradeIcon.className = 'grade-icon good';
                popupTitle.textContent = 'Well Done!';
                popupMessage.textContent = 'Good progress! Keep up the great work!';
            } else {
                gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                gradeIcon.className = 'grade-icon needs-improvement';
                popupTitle.textContent = 'Keep Learning!';
                popupMessage.textContent = 'Don\'t give up! Practice makes perfect!';
            }

            // Show popup with animation
            popupOverlay.classList.add('show');
            setTimeout(() => popup.classList.add('show'), 100);
        }



        // 💀 LETHAL AUDIO POOL - INSTANT SOUND PLAYBACK 💀
        function playInstantSound(isCorrect) {
            const startTime = performance.now();
            const soundType = isCorrect ? 'correct' : 'wrong';
            
            // 💀 BULLETPROOF DUPLICATE PREVENTION 💀
            if (playInstantSound.isPlaying) {
                console.log(`🔇 LETHAL AUDIO POOL: Sound already playing, skipping duplicate ${soundType} sound`);
                return;
            }
            
            console.log(`🔥 LETHAL AUDIO POOL: Attempting 0ms playback for ${soundType} sound`);
            
            // 💀 SHIFT FROM POOL - GET READY AUDIO ELEMENT 💀
            let audio = null;
            if (audioPool[soundType] && audioPool[soundType].length > 0) {
                audio = audioPool[soundType].shift(); // Remove from pool
                console.log(`🔥 LETHAL AUDIO POOL: Got ${soundType} audio from pool (${audioPool[soundType].length} remaining)`);
            } else {
                // 💀 FALLBACK: CREATE FRESH AUDIO 💀
                console.log(`🔥 LETHAL AUDIO POOL: Pool empty, creating fresh ${soundType} audio`);
                createAudioElement(soundType);
                audio = audioPool[soundType].shift();
            }
            
            if (!audio) {
                console.log(`ℹ️ LETHAL AUDIO POOL: No audio available for ${soundType}, continuing silently`);
                return;
            }
            
            // 💀 INSTANT PLAYBACK WITH ERROR HANDLING 💀
            try {
                audio.currentTime = 0;
                audio.volume = 0.7;
                
                // Add a one-time ended listener to ensure proper cleanup
                const endedHandler = () => {
                    audio.removeEventListener('ended', endedHandler);
                    // Reset playing flag when sound ends
                    playInstantSound.isPlaying = false;
                    setTimeout(() => {
                        returnToPool(audio, soundType);
                    }, 100); // Small delay to ensure full playback
                };
                audio.addEventListener('ended', endedHandler);
                
                // 💀 SET PLAYING FLAG TO PREVENT DUPLICATES 💀
                playInstantSound.isPlaying = true;
                
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        const playbackTime = performance.now() - startTime;
                        console.log(`✅ LETHAL AUDIO POOL: ${soundType} sound started in ${playbackTime.toFixed(2)}ms - letting it play fully`);
                        
                    }).catch(error => {
                        console.error(`❌ LETHAL AUDIO POOL: ${soundType} playback failed`, error);
                        audio.removeEventListener('ended', endedHandler);
                        // Return to pool even on error
                        returnToPool(audio, soundType);
                        // Reset playing flag on error
                        playInstantSound.isPlaying = false;
                    });
                }
            } catch (error) {
                console.error(`❌ LETHAL AUDIO POOL: ${soundType} audio error`, error);
                // Return to pool even on error
                returnToPool(audio, soundType);
                // Reset playing flag on error
                playInstantSound.isPlaying = false;
            }
        }
        
        // 💀 NUCLEAR INSTANT GRADING SOUND - ZERO DELAY 💀
        function playFinalGradingSound(score, total) {
            const funcStart = performance.now();
            console.log('🔥 PLAY FINAL GRADING SOUND CALLED');
            
            const percentage = (score / total) * 100;
            
            // 💀 NUCLEAR SPEED - DIRECT AUDIO CREATION 💀
            const audio = new Audio();
            
            if (percentage === 100) {
                audio.src = '/sounds/quiz-perfect.mp3';
            } else if (percentage >= 70) {
                audio.src = '/sounds/quiz-partial.mp3';
            } else {
                audio.src = '/sounds/quiz-wrong.mp3';
            }
            
            audio.volume = 0.7;
            
            const playStart = performance.now();
            audio.play().then(() => {
                console.log(`✅ AUDIO PLAYED in ${(performance.now() - playStart).toFixed(2)}ms`);
            }).catch((error) => {
                console.log(`❌ AUDIO FAILED in ${(performance.now() - playStart).toFixed(2)}ms:`, error);
            });
            
            console.log(`🔥 FUNCTION COMPLETED in ${(performance.now() - funcStart).toFixed(2)}ms`);
        }
        
        // 💀 LETHAL AUDIO POOL DEBUG FUNCTIONS 💀
        window.debugAudioPool = function() {
            console.log('🔥 LETHAL AUDIO POOL DEBUG:');
            console.log('Correct pool size:', audioPool.correct.length);
            console.log('Wrong pool size:', audioPool.wrong.length);
            console.log('Perfect pool size:', audioPool.perfect.length);
            console.log('Partial pool size:', audioPool.partial.length);
            console.log('Total pool elements:', audioPool.correct.length + audioPool.wrong.length + audioPool.perfect.length + audioPool.partial.length);
        };
        
        window.testAudioPool = function() {
            console.log('🔥 LETHAL AUDIO POOL: Testing instant playback...');
            setTimeout(() => playInstantSound(true), 100);   // Test correct
            setTimeout(() => playInstantSound(false), 500);  // Test wrong
            setTimeout(() => playInstantSound(true), 1000);  // Test correct again
        };

    </script>

    <script>
        // 💀 NUCLEAR AUDIO PRELOADING SYSTEM 💀


        // SAVE TUTOR QUIZ ATTEMPT TO DATABASE
        function saveTutorQuizAttempt(correctAnswers, totalQuestions, percentage) {
            console.log('🔥 LETHAL DEBUG: saveTutorQuizAttempt called', {
                correctAnswers, totalQuestions, percentage
            });
            
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('score', correctAnswers);
            formData.append('total_questions', totalQuestions);
            formData.append('percentage_grade', percentage);
            formData.append('mode', 'tutor');
            formData.append('time_taken', Math.floor((Date.now() - quizStartTime) / 1000));
            
            // CRITICAL FIX: We now use stored userAnswers instead of DOM collection
            console.log('🔥 TUTOR DEBUG: Preparing to submit tutor quiz with stored answers');
            
            // CRITICAL FIX: Use stored userAnswers instead of trying to collect from DOM
            console.log('🔥 TUTOR DEBUG: Using stored userAnswers:', userAnswers);
            console.log('🔥 TUTOR DEBUG: Stored answers count:', Object.keys(userAnswers).length);
            console.log('🔥 TUTOR DEBUG: Expected questions:', totalQuestions);
            
            // Add stored answers to form data
            Object.keys(userAnswers).forEach(questionId => {
                formData.append(`answers[${questionId}]`, userAnswers[questionId]);
                console.log(`🔥 TUTOR DEBUG: Added to FormData - answers[${questionId}] = ${userAnswers[questionId]}`);
            });
            
            // LETHAL FIX: Remove frontend calculation - backend will handle scoring
            
            // Add source information
            const selectedSources = @json($selected_sources ?? []);
            if (selectedSources && selectedSources.length > 0) {
                selectedSources.forEach((source, index) => {
                    formData.append(`sources[${index}]`, source);
                });
            }
            
            // Debug FormData contents
            console.log('🔥 LETHAL DEBUG: FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            console.log('🔥 LETHAL DEBUG: Sending request to:', '{{ route("quiz.grade", ["course" => $course->id, "topic" => $topic->id]) }}');
            
            fetch('{{ route("quiz.grade", ["course" => $course->id, "topic" => $topic->id]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('🔥 LETHAL DEBUG: Response status:', response.status);
                
                // LETHAL FIX: Handle all response types gracefully
                if (!response.ok) {
                    console.warn(`⚠️ Quiz grading request returned ${response.status}: ${response.statusText}`);
                    return {
                        success: false,
                        error: `Server returned ${response.status}`,
                        message: 'Unable to grade quiz due to server error'
                    };
                }
                
                return response.json().catch(jsonError => {
                    console.warn('⚠️ Failed to parse quiz grading JSON response');
                    return {
                        success: false,
                        error: 'Invalid response format',
                        message: 'Unable to parse server response'
                    };
                });
            })
            .then(data => {
                console.log('🔥 LETHAL DEBUG: Response data:', data);
                if (data.success) {
                    console.log('✅ Tutor quiz attempt saved successfully');
                    
                    // Fetch updated source progress for tutor mode with LETHAL ERROR HANDLING
                    fetch(`/topics/{{ $topic->id }}/source-progress?mode=tutor`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        console.log('🔥 TUTOR DEBUG: Source progress response status:', response.status);
                        
                        // LETHAL FIX: Handle all response types gracefully
                        if (!response.ok) {
                            // Don't throw error, just log and return default data
                            console.warn(`⚠️ Source progress request returned ${response.status}: ${response.statusText}`);
                            return {
                                success: false,
                                progress: {},
                                availableSources: [],
                                hasMultipleSources: false,
                                mode: 'tutor'
                            };
                        }
                        
                        return response.json().catch(jsonError => {
                            console.warn('⚠️ Failed to parse JSON response, using default data');
                            return {
                                success: false,
                                progress: {},
                                availableSources: [],
                                hasMultipleSources: false,
                                mode: 'tutor'
                            };
                        });
                    })
                    .then(progressData => {
                        console.log('🔥 TUTOR DEBUG: Fetched tutor source progress:', progressData);
                        
                        // LETHAL FIX: Always handle the response, even if not successful
                        if (progressData && progressData.success && progressData.progress) {
                            // Store progress data in localStorage for cross-page communication
                            const progressDataForStorage = {
                                    topicId: {{ $topic->id }},
                                    assignmentId: null, // Tutor mode - no assignment context
                                    progress: progressData.progress,
                                    availableSources: progressData.availableSources || [],
                                allSourcesMastered: progressData.progress ? Object.values(progressData.progress).every(p => p.percentage >= 90) : false,
                                timestamp: Date.now()
                            };
                            
                            // Store in localStorage for cross-page communication
                            localStorage.setItem('quizProgressUpdate', JSON.stringify(progressDataForStorage));
                            
                            // Dispatch a custom event with the updated source progress data
                            const event = new CustomEvent('sourceProgressUpdated', {
                                detail: progressDataForStorage
                            });
                            document.dispatchEvent(event);
                            
                            // 🔥 SERVER-SIDE STORAGE - For cross-tab communication
                            fetch('{{ route("progress.store-update") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    topicId: {{ $topic->id }},
                                    assignmentId: null,
                                    progress: progressData.progress,
                                    availableSources: progressData.availableSources || [],
                                    allSourcesMastered: progressData.progress ? Object.values(progressData.progress).every(p => p.percentage >= 90) : false,
                                    userId: {{ auth()->id() }}
                                })
                            }).then(response => response.json())
                            .then(result => {
                                console.log('🔥 Server progress update stored (tutor mode):', result);
                            })
                            .catch(error => {
                                console.error('Failed to store progress update on server (tutor mode):', error);
                            });
                            
                            console.log('✅ Source progress update event dispatched for tutor mode with real-time sync (localStorage + server)');
                        } else {
                            // LETHAL FIX: Don't error, just log and continue
                            console.log('ℹ️ Source progress not available or unsuccessful, continuing without update');
                            
                                                    // Store empty progress data in localStorage for cross-page communication
                        const emptyProgressData = {
                                    topicId: {{ $topic->id }},
                                    assignmentId: null,
                                    progress: {},
                                    availableSources: [],
                            allSourcesMastered: false,
                            timestamp: Date.now()
                        };
                        
                        // Store in localStorage for cross-page communication
                        localStorage.setItem('quizProgressUpdate', JSON.stringify(emptyProgressData));
                        
                        // Dispatch event with empty data to prevent UI issues
                        const event = new CustomEvent('sourceProgressUpdated', {
                            detail: emptyProgressData
                            });
                            document.dispatchEvent(event);
                        }
                    })
                    .catch(error => {
                        // LETHAL FIX: Silent error handling - log but don't spam console
                        console.log('ℹ️ Source progress fetch encountered an issue, continuing normally');
                        
                        // Store empty progress data in localStorage for cross-page communication
                        const emptyProgressData = {
                                topicId: {{ $topic->id }},
                                assignmentId: null,
                                progress: {},
                                availableSources: [],
                            allSourcesMastered: false,
                            timestamp: Date.now()
                        };
                        
                        // Store in localStorage for cross-page communication
                        localStorage.setItem('quizProgressUpdate', JSON.stringify(emptyProgressData));
                        
                        // Dispatch event with empty data to prevent UI issues
                        const event = new CustomEvent('sourceProgressUpdated', {
                            detail: emptyProgressData
                        });
                        document.dispatchEvent(event);
                    });
                } else {
                    console.log('❌ Failed to save tutor quiz attempt:', data.error || data.message);
                }
            })
            .catch(error => {
                // LETHAL FIX: Silent error handling - don't spam console
                console.log('ℹ️ Quiz attempt save encountered an issue, but quiz results are still available');
            });
        }



        // Handle source progress updates from tutor quiz completion
        document.addEventListener('sourceProgressUpdated', function(e) {
            console.log('🔥 TUTOR MODE: Source progress update received:', e.detail);
            const { topicId, progress, availableSources, allSourcesMastered } = e.detail;
            
            // Update the source information display if it exists
            const sourcesInfo = document.querySelector('.sources-info');
            if (sourcesInfo && progress) {
                console.log('✅ TUTOR MODE: Updating source progress display');
                
                // Update the source display text with progress information
                const selectedSources = @json($selected_sources ?? []);
                if (selectedSources && selectedSources.length > 0 && !selectedSources.includes('all')) {
                    let progressText = '';
                    selectedSources.forEach(source => {
                        const sourceProgress = progress[source];
                        if (sourceProgress) {
                            const percentage = Math.round(sourceProgress.percentage);
                            const status = percentage >= 90 ? '✅' : percentage >= 50 ? '🔄' : percentage > 0 ? '❌' : '⭕';
                            progressText += `${source}: ${status} ${percentage}% (${sourceProgress.completed}/${sourceProgress.total}) `;
                        }
                    });
                    
                    if (progressText) {
                        sourcesInfo.innerHTML = `
                            <span class="text-muted">
                                <strong>Progress:</strong> ${progressText}
                            </span>
                        `;
                    }
                }
            }
            
            console.log('✅ TUTOR MODE: Source progress update completed');
        });
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