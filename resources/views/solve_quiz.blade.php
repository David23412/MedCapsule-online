<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            zoom: 0.80; /* Zoom out 20% */
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
            max-width: 1000px;
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
            transform: translate(-50%, 0);
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            z-index: 999;
            max-width: 1000px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .progress-bar-container.at-form {
            position: static;
            transform: none;
            margin: 2rem auto;
            background: #f8f9fa;
        }

        .progress-bar-container.hidden {
            transform: translate(-50%, 100%);
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            display: none !important;
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
            .progress-bar-container.hidden {
                transform: translate(-50%, 100%);
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 1rem;
            }
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
            transition: width 0.3s ease;
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

        /* Style for selected answers before grading */
        .form-check input:checked + label {
            background-color: #e3f2fd;
            border-color: #007bff;
            color: #007bff;
        }

        /* Styles for answers after grading - must override selected styles */
        .form-check input[disabled] + label.correct {
            background-color: #e3fcef;
            border-color: #00a854;
            color: #00a854;
        }

        .form-check input[disabled] + label.incorrect {
            background-color: #fff1f0;
            border-color: #f5222d;
            color: #f5222d;
        }

        .written-answer.correct {
            border-color: #00a854 !important;
            background-color: #e3fcef !important;
            color: #00a854 !important;
        }

        .written-answer.incorrect {
            border-color: #f5222d !important;
            background-color: #fff1f0 !important;
            color: #f5222d !important;
        }

        .feedback.correct {
            color: #00a854 !important;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .feedback.incorrect {
            color: #f5222d !important;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .correct-answer-card {
            background-color: #e3fcef !important;
            border: 1px solid #00a854 !important;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            color: #00a854 !important;
            font-weight: 500;
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
            background: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
            pointer-events: none !important;
            transform: none !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2) !important;
            border: 2px solid #495057 !important;
        }

        .submit-btn:disabled:hover {
            background: #6c757d !important;
            transform: none !important;
            cursor: not-allowed !important;
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
            transition: width 0.3s ease;
            border-radius: 5px;
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

        /* Ensure confetti appears above modal and is properly positioned */
        canvas {
            z-index: 10001 !important;
            pointer-events: none;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
        
        /* Make sure popup has proper positioning context */
        .popup {
            position: relative;
            z-index: 1001;
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

        .retry-btn {
            background: #007bff;
            color: white;
        }

        .retry-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.2);
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

        /* BRUTAL VISUAL FEEDBACK ANIMATIONS */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes wobble {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-2deg); }
            75% { transform: rotate(2deg); }
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
            z-index: 1000;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #007bff, #00d4ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
            background-size: cover;
            background-position: center;
        }

        .profile-circle.no-image {
            background: linear-gradient(135deg, #007bff, #00d4ff);
        }

        .profile-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .popup {
                width: 95%;
                padding: 1.5rem;
            }

            .popup-score {
                font-size: 3rem;
            }
        }

        /* Answer Review Styles */
        .form-check-label.correct {
            color: #52c41a;
            font-weight: 500;
            position: relative;
            padding-left: 25px;
        }

        .form-check-label.correct::before {
            content: '✓';
            position: absolute;
            left: 5px;
            color: #52c41a;
        }

        .form-check-label.incorrect {
            color: #ff4d4f;
            font-weight: 500;
            position: relative;
            padding-left: 25px;
        }

        .form-check-label.incorrect::before {
            content: '✗';
            position: absolute;
            left: 5px;
            color: #ff4d4f;
        }

        .written-answer.correct {
            border-color: #52c41a !important;
            background-color: rgba(82, 196, 26, 0.1);
        }

        .written-answer.incorrect {
            border-color: #ff4d4f !important;
            background-color: rgba(255, 77, 79, 0.1);
        }

        .answer-feedback {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .answer-feedback.correct {
            color: #52c41a;
            background-color: rgba(82, 196, 26, 0.1);
        }

        .answer-feedback.incorrect {
            color: #ff4d4f;
            background-color: rgba(255, 77, 79, 0.1);
        }

        .correct-answer-card {
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(82, 196, 26, 0.1);
            border-radius: 4px;
            border-left: 3px solid #52c41a;
        }

        .xp-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #4CAF50;
            margin: 15px 0;
            text-align: center;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
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
            <h1>{{ $topic->name }}</h1>

            <!-- Source information display -->
            @if(isset($selected_sources) && count($selected_sources) > 0 && !in_array('all', $selected_sources))
                <div class="sources-info text-center mb-4">
                    <span class="text-muted">
                        Source: {{ implode(', ', $selected_sources) }}
                    </span>
                </div>
            @endif

            @if ($questions->isNotEmpty())
                <div class="progress-bar-container">
                    <div class="timer">
                        <i class="fas fa-clock"></i>
                        <span id="timer">00:00</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions answered</div>
                </div>

                <form id="quiz-form" action="{{ secure_url(route('quiz.grade', ['course' => $course->id, 'topic' => $topic->id], false)) }}" method="POST">
                    @csrf
                    @foreach($questions as $question)
                        @if($question->question_type === 'written')
                            @include('components.question-written', ['question' => $question])
                        @else
                            <div class="question-box {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
                                <div class="question-text">
                                    {{ $question->question }}
                                </div>

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
                <p class="text-center text-danger">No questions available for this topic.</p>
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
                <div class="score-details" id="score-details"></div>
            </div>
            <p class="popup-message" id="popup-message">Keep practicing! You're getting there!</p>
            <div class="popup-buttons">
                <button class="popup-btn review-btn" id="review-btn">
                    <i class="fas fa-search"></i>
                    Review Answers
                </button>
                <button class="popup-btn retry-btn" id="retry-btn">
                    <i class="fas fa-redo"></i>
                    Retry Quiz
                </button>
                <a href="{{ route('topics.forCourse', ['course' => $course->id]) }}" class="popup-btn view-progress-btn" id="view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    View Progress
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <!-- WORKING AUDIO SYSTEM WITH REAL FILES -->
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
        document.addEventListener("DOMContentLoaded", function () {
            const quizForm = document.getElementById("quiz-form");
            const submitButton = document.getElementById("submit-btn");
            const popupOverlay = document.getElementById("popup-overlay");
            const popup = document.getElementById("popup");
            const popupTitle = document.getElementById("popup-title");
            const popupScore = document.getElementById("popup-score");
            const popupMessage = document.getElementById("popup-message");
            const reviewButton = document.getElementById("review-btn");
            const retryButton = document.getElementById("retry-btn");
            const viewProgressButton = document.getElementById("view-progress-btn");

            let selectedAnswers = {};

            // WORKING: Smart progress bar positioning system
            const progressBarContainer = document.querySelector('.progress-bar-container');
            const submitBtn = document.getElementById('submit-btn');
            let rafId = null;
            
            function updateProgressBarPosition() {
                if (!submitBtn || !progressBarContainer) return;
                
                const submitBtnRect = submitBtn.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                
                // INTELLIGENT BUT STRICT AS FUCK:
                // Hide when submit button is anywhere near the viewport (300px buffer)
                if (submitBtnRect.top <= windowHeight + 300) {
                    // NUCLEAR HIDE - multiple methods to ensure it's GONE
                    progressBarContainer.classList.add('hidden');
                    progressBarContainer.style.display = 'none';
                    progressBarContainer.style.visibility = 'hidden';
                    progressBarContainer.style.opacity = '0';
                } else {
                    // Show when submit button is FAR AWAY (user scrolled back up to work on questions)
                    progressBarContainer.classList.remove('hidden');
                    progressBarContainer.style.display = '';
                    progressBarContainer.style.visibility = '';
                    progressBarContainer.style.opacity = '';
                }
            }

            function onScroll() {
                if (rafId) return;
                
                rafId = requestAnimationFrame(() => {
                    updateProgressBarPosition();
                    rafId = null;
                });
            }

            // Event listeners with passive scrolling for better performance
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll, { passive: true });
            
            // Initial position check
            updateProgressBarPosition();

            if (quizForm) {
                // Handle multiple choice answers
                document.querySelectorAll('.form-check input').forEach(input => {
                    input.addEventListener('change', function() {
                        const questionId = this.name.match(/\[(\d+)\]/)[1];
                        selectedAnswers[questionId] = this.value;
                        updateProgress();
                    });
                });

                // Handle written answers
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

                // Function to update progress
                function updateProgress() {
                    const totalQuestions = {{ $questions->count() }};
                    const answeredQuestions = Object.keys(selectedAnswers).length;
                    
                    // Update progress bar
                    const progressFill = document.getElementById('progress-fill');
                    const progressText = document.getElementById('progress-text');
                    const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                    
                    progressFill.style.width = `${progressPercentage}%`;
                    progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                    
                    submitButton.disabled = answeredQuestions !== totalQuestions;
                }

                // Add timer functionality
                let startTime = new Date();
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

                // Function to update source progress
                function updateSourceProgress(sourceProgress) {
                    if (!sourceProgress) return;
                    
                    Object.entries(sourceProgress).forEach(([source, data]) => {
                        const progressBar = document.querySelector(`[data-source="${source}"] .source-progress-value`);
                        if (progressBar) {
                            progressBar.style.width = `${data.percentage}%`;
                    
                            // Update status class
                            const statusClass = data.percentage >= 90 ? 'completed' :
                                              data.percentage >= 50 ? 'in-progress' : 'failed';
                            progressBar.className = `source-progress-value ${statusClass}`;
                            
                            // Update questions count
                            const questionsCount = document.querySelector(`[data-source="${source}"] .questions-count`);
                            if (questionsCount) {
                                questionsCount.textContent = `${data.score}/${data.total} Questions`;
                            }
                        }
                    });
                }

                quizForm.addEventListener("submit", function (event) {
                    event.preventDefault();

                    // Disable submit button
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
                    
                    // Calculate duration
                    const endTime = new Date();
                    const durationSeconds = Math.floor((endTime - startTime) / 1000);
                    
                    // Clear timer interval
                    clearTimeout(timerInterval);
                    
                    // BRUTAL DATA VALIDATION AND PREPARATION
                    const formData = new FormData(this);
                    formData.append('time_taken', durationSeconds);
                    formData.append('mode', '{{ $mode ?? 'quiz' }}');
                    
                    // DEBUG: Log form data
                    console.log('Form submission data:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key, value);
                    }
                    
                    // VALIDATE: Ensure we have answers
                    const totalQuestions = {{ $questions->count() }};
                    const answeredQuestions = Object.keys(selectedAnswers).length;
                    
                    if (answeredQuestions !== totalQuestions) {
                        alert(`Please answer all questions. You have answered ${answeredQuestions} out of ${totalQuestions} questions.`);
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Submit Quiz';
                        return;
                    }
                    
                    // ENSURE CSRF TOKEN EXISTS
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('CSRF token not found');
                        alert('Security token missing. Please refresh the page and try again.');
                        return;
                    }
                    
                    // Send request
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            if (response.status === 422) {
                                return response.json().then(errorData => {
                                    console.error('Validation errors:', errorData);
                                    throw new Error(`Validation failed: ${JSON.stringify(errorData.errors || errorData.message || 'Unknown validation error')}`);
                                });
                            }
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // LOCK SUBMIT BUTTON PERMANENTLY - NO RE-ENABLING
                        submitButton.disabled = true;
                        submitButton.innerHTML = 'Quiz Submitted';
                        submitButton.style.background = '#6c757d';
                        submitButton.style.cursor = 'not-allowed';
                        submitButton.style.opacity = '0.6';

                        // Check for error response
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        // Update popup content
                        const gradeIcon = document.getElementById('grade-icon');
                        const popupTitle = document.getElementById('popup-title');
                        const popupScore = document.getElementById('popup-score');
                        const popupMessage = document.getElementById('popup-message');
                        const scoreDetails = document.getElementById('score-details');

                        // Update score display
                        popupScore.textContent = `${data.score}/${data.total}`;
                        popupMessage.textContent = data.message;

                        // Calculate percentage
                        const percentage = (data.score / data.total) * 100;

                        // Update grade icon and title
                        if (percentage === 100) {
                            gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                            gradeIcon.className = 'grade-icon perfect';
                            popupTitle.textContent = 'Perfect Score!';
                            // Use unified confetti system for perfect scores
                            if (typeof ConfettiManager !== 'undefined') {
                                ConfettiManager.celebratePerfectScore('#popup');
                            } else if (typeof confetti !== 'undefined') {
                                // SINGLE PERFECT CONFETTI - Dead center of screen where modal is
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
                        } else {
                            gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                            gradeIcon.className = 'grade-icon needs-improvement';
                            popupTitle.textContent = 'Keep Learning!';
                        }

                        // Show XP earned if available
                        if (data.xp_earned !== undefined) {
                            const xpText = document.createElement('div');
                            xpText.className = 'xp-total';
                            xpText.textContent = `+${data.xp_earned} XP`;
                            scoreDetails.appendChild(xpText);
                        }
                        
                        // Update source progress if available
                        if (data.source_progress) {
                            // Dispatch a custom event with the source progress data
                            const event = new CustomEvent('sourceProgressUpdated', {
                                detail: {
                                    topicId: {{ $topic->id }},
                                    progress: data.source_progress,
                                    availableSources: Object.keys(data.source_progress),
                                    allSourcesMastered: Object.values(data.source_progress).every(p => p.percentage >= 90)
                                }
                            });
                            window.parent.document.dispatchEvent(event);
                        }
                        
                        // Mark correct and incorrect answers only if we have the data
                        if (data.correct_answers) {
                            Object.keys(data.correct_answers).forEach(questionId => {
                                const correctAnswer = data.correct_answers[questionId];
                                const submittedAnswer = formData.get(`answers[${questionId}]`);
                                const questionBox = document.querySelector(`[data-question-id="${questionId}"]`);
                                
                                if (questionBox) {
                                    // Check if this is a written question
                                    const writtenAnswer = questionBox.querySelector('.written-answer');
                                    if (writtenAnswer) {
                                        // Handle written question
                                        const feedbackElement = questionBox.querySelector('.answer-feedback');
                                        
                                        // Disable the textarea
                                        writtenAnswer.disabled = true;
                                        
                                        // Store the correct answer for review
                                        questionBox.setAttribute('data-correct-answer', correctAnswer);
                                        
                                        // Check if answer was correct
                                        const isCorrect = correctAnswer === submittedAnswer;
                                        
                                        if (isCorrect) {
                                            writtenAnswer.classList.add('correct');
                                            feedbackElement.classList.add('correct');
                                            feedbackElement.textContent = 'Correct!';
                                        } else {
                                            writtenAnswer.classList.add('incorrect');
                                            feedbackElement.classList.add('incorrect');
                                            
                                            // Create a card to show the correct answer
                                            const correctAnswerCard = document.createElement('div');
                                            correctAnswerCard.className = 'correct-answer-card';
                                            correctAnswerCard.innerHTML = `Correct answer: <strong>${correctAnswer}</strong>`;
                                            feedbackElement.appendChild(correctAnswerCard);
                                        }
                                    } else {
                                        // Handle multiple choice question
                                        const labels = questionBox.querySelectorAll('.form-check label');
                                        const inputs = questionBox.querySelectorAll('.form-check input');
                                        
                                        // First disable all inputs
                                        inputs.forEach(input => input.disabled = true);
                                        
                                        // Then mark answers
                                        labels.forEach(label => {
                                            const input = label.previousElementSibling;
                                            if (input) {
                                                const isCorrectAnswer = input.value === correctAnswer;
                                                const isSelectedAnswer = input.value === submittedAnswer;
                                                
                                                // Remove any existing classes
                                                label.classList.remove('correct', 'incorrect');
                                                
                                                if (isCorrectAnswer) {
                                                    // Always mark correct answer in green
                                                    label.classList.add('correct');
                                                } else if (isSelectedAnswer) {
                                                    // Mark selected wrong answer in red
                                                    label.classList.add('incorrect');
                                                }
                                            }
                                        });
                                    }
                                }
                            });
                        }
                        
                        // Play completion sound
                        playQuizCompletionSound(data.score, data.total);
                        
                        // Show popup
                        popupOverlay.classList.add('show');
                        popup.classList.add('show');
                    })
                    .catch((error) => {
                        console.error('Quiz submission error:', error);
                        
                        // Re-enable submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Submit Quiz';
                        submitButton.style.background = '';
                        submitButton.style.cursor = '';
                        submitButton.style.opacity = '';
                        
                        // Show detailed error message
                        let errorMessage = 'An error occurred while submitting the quiz.';
                        
                        if (error.message.includes('Validation failed')) {
                            errorMessage = 'Quiz validation failed. Please check your answers and try again.';
                        } else if (error.message.includes('422')) {
                            errorMessage = 'Invalid quiz data. Please refresh the page and try again.';
                        } else if (error.message.includes('CSRF')) {
                            errorMessage = 'Security token expired. Please refresh the page and try again.';
                        }
                        
                        alert(errorMessage + '\n\nTechnical details: ' + error.message);
                    });
                });
            }

            // Add event listeners to popup buttons
            reviewButton.addEventListener("click", function () {
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);
                
                // BRUTAL SUBMIT BUTTON LOCKDOWN IN REVIEW MODE
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '✓ Quiz Completed - Review Mode';
                    submitButton.style.background = '#28a745';
                    submitButton.style.cursor = 'not-allowed';
                    submitButton.style.opacity = '0.7';
                    submitButton.style.pointerEvents = 'none';
                    
                    // Add visual indicator that it's locked
                    submitButton.style.border = '2px solid #155724';
                    submitButton.style.boxShadow = 'inset 0 2px 4px rgba(0,0,0,0.2)';
                }
            });

            retryButton.addEventListener("click", function () {
                window.location.reload();
            });

            viewProgressButton.addEventListener("click", function () {
                window.location.href = "{{ route('topics.forCourse', ['course' => $course->id]) }}";
            });
        });

        // SIMPLE RELIABLE AUDIO SYSTEM
        function playQuizCompletionSound(score, total) {
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
            
            // SIMPLE AND RELIABLE
            if (audioElement) {
                try {
                    audioElement.volume = 0.7;
                    audioElement.currentTime = 0;
                    
                    audioElement.play().then(() => {
                        console.log(`✅ Audio ${soundId} played successfully`);
                    }).catch(error => {
                        console.log(`❌ Audio ${soundId} failed:`, error);
                        // Fallback to Web Audio API
                        playWebAudioSound(soundId, percentage);
                    });
                } catch (error) {
                    console.log(`❌ Audio element error for ${soundId}:`, error);
                    playWebAudioSound(soundId, percentage);
                }
            } else {
                console.log(`❌ Audio element not found for ${soundId}`);
                playWebAudioSound(soundId, percentage);
            }
        }
        
        // STRATEGY 2: Web Audio API fallback
        function playWebAudioSound(soundId, percentage) {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                // Different frequencies for different scores
                if (soundId === 'perfect') {
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
                } else if (soundId === 'partial') {
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
                } else {
                    oscillator.frequency.setValueAtTime(300, audioContext.currentTime);
                }
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
                
                console.log(`Web Audio API sound played for ${soundId}`);
            } catch (error) {
                console.log('Web Audio API failed:', error);
                // STRATEGY 4: Visual feedback only
                showVisualFeedback(soundId);
            }
        }
        
        // STRATEGY 3: Dynamic audio creation
        function createAndPlayAudio(soundId, percentage) {
            const audio = new Audio();
            const soundPaths = [
                `/sounds/quiz-${soundId}.mp3`,
                `{{ url('sounds') }}/quiz-${soundId}.mp3`,
                `{{ asset('sounds') }}/quiz-${soundId}.mp3`
            ];
            
            let pathIndex = 0;
            
            function tryNextPath() {
                if (pathIndex < soundPaths.length) {
                    audio.src = soundPaths[pathIndex];
                    audio.volume = 0.7;
                    
                    audio.addEventListener('canplaythrough', () => {
                        audio.play().then(() => {
                            console.log(`Dynamic audio played for ${soundId}`);
                        }).catch(() => {
                            pathIndex++;
                            tryNextPath();
                        });
                    }, { once: true });
                    
                    audio.addEventListener('error', () => {
                        pathIndex++;
                        tryNextPath();
                    }, { once: true });
                    
                    audio.load();
                } else {
                    // All paths failed, use Web Audio API
                    playWebAudioSound(soundId, percentage);
                }
            }
            
            tryNextPath();
        }
        
        // STRATEGY 4: Visual feedback when all audio fails
        function showVisualFeedback(soundId) {
            const popup = document.getElementById('popup');
            if (popup) {
                popup.style.animation = 'none';
                popup.offsetHeight; // Trigger reflow
                
                if (soundId === 'perfect') {
                    popup.style.animation = 'pulse 0.6s ease-in-out';
                } else if (soundId === 'partial') {
                    popup.style.animation = 'shake 0.5s ease-in-out';
                } else {
                    popup.style.animation = 'wobble 0.5s ease-in-out';
                }
            }
            console.log(`Visual feedback shown for ${soundId}`);
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

    <script>
        // Utility function to safely decode HTML entities
        function decodeHtmlEntities(text) {
            if (!text) return '';
            
            try {
                // Create a DOM element to leverage browser's built-in decoder
                const textArea = document.createElement('textarea');
                textArea.innerHTML = text;
                const decodedText = textArea.value;
                
                // Return safely decoded text
                return decodedText;
            } catch (e) {
                console.error('Error decoding HTML entities:', e);
                return text; // Return the original text if decoding fails
            }
        }
    </script>
</body>
</html>
