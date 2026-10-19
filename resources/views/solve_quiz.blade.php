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
    @include('partials.favicon')
    @include('partials.admin-dark-mode-head')

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            zoom: 0.75; /* Zoom out 25% */
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
            max-width: none;
            margin: 0 auto;
            padding: 0 2rem;
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

        .quiz-interface {
            max-width: none;
            margin: 0 auto;
            padding: 0;
        }

        .main-content {
            margin-top: 80px;
            padding: 1.5rem 2.75rem;
            min-height: 100vh;
        }

        .container {
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            margin-bottom: 100px; /* Space for fixed progress bar */
            max-width: none;
            width: 100%;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-weight: 600;
        }

        /* TOTAL TOPIC PROGRESS BAR */
        .total-topic-progress {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem auto;
            max-width: 600px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .total-topic-progress::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        .total-topic-progress h3 {
            color: white;
            margin: 0 0 1rem 0;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 600;
        }

        .total-progress-bar {
            width: 100%;
            height: 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .total-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ffed4e);
            border-radius: 6px;
            width: 0%;
            transition: width 0.8s ease;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .total-progress-text {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .total-progress-percentage {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: 800;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* TOTAL TOPIC PROGRESS BAR */
        .total-topic-progress {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem auto;
            max-width: 600px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .total-topic-progress::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        .total-topic-progress h3 {
            color: white;
            margin: 0 0 1rem 0;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 600;
        }

        .total-progress-bar {
            width: 100%;
            height: 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .total-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffd700, #ffed4e);
            border-radius: 6px;
            width: 0%;
            transition: width 0.8s ease;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .total-progress-text {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .total-progress-percentage {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: 800;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .progress-bar-container {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translate(-50%, 0);
            background: rgba(248, 249, 250, 0.98);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15), 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            max-width: 1200px;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-bottom: none;
            will-change: transform, opacity;
        }

        .progress-bar-container.at-form {
            position: static;
            transform: none;
            margin: 2rem auto;
            background: #f8f9fa;
        }

        .progress-bar-container.hidden {
            transform: translate(-50%, 200%) !important;
            opacity: 0 !important;
            pointer-events: none !important;
            visibility: hidden !important;
            display: none !important;
            z-index: -999 !important;
            bottom: -300px !important;
            transition: all 0.2s ease !important;
        }
        
        /* Smooth show animation */
        .progress-bar-container:not(.hidden) {
            animation: slideUpSmooth 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        }
        
        @keyframes slideUpSmooth {
            from {
                transform: translate(-50%, 100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
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

        /* BULLETPROOF SUBMIT BUTTON SPACING */
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
            margin-bottom: 2rem;
            position: relative;
            z-index: 1000;
            min-height: 54px; /* Touch target */
        }

        /* ============================================================================
           BULLETPROOF RESPONSIVE DESIGN - GOLDEN STANDARD MEDIA QUERIES
           ============================================================================ */
        @media (max-width: 1100px) {
            .container {
                max-width: 95% !important;
            }
            
            .progress-bar-container {
                max-width: 850px !important;
                width: calc(94% - 0.5rem) !important;
                padding: 0.2rem 0.8rem !important;
                min-height: 32px !important;
            }
        }

        @media (max-width: 850px) {
            /* Reset desktop zoom on tablets/phones so the UI is readable */
            body {
                zoom: 1 !important;
            }

            .container {
                max-width: 100%;
                border-radius: 0;
                padding: 0.75rem 1rem;
                /* Move progress bar to TOP on mobile */
                top: 70px; /* Below navbar */
                bottom: auto;
                left: 0;
                transform: translateX(0);
                width: 100vw;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }
            
            .progress-bar-container.hidden {
                transform: translateY(-120%) !important;
            }
            
            .progress-bar-container:not(.hidden) {
                transform: translateY(0) !important;
            }

            /* Fix submit button to BOTTOM on mobile */
            .submit-btn {
                position: fixed !important;
                bottom: 0;
                left: 0;
                right: 0;
                margin: 0 !important;
                border-radius: 0;
                padding-bottom: calc(1rem + env(safe-area-inset-bottom)) !important;
            }

            /* Add padding to the form so content isn't hidden behind fixed submit */
            #quiz-form {
                padding-bottom: 100px; 
            }
            
            /* Responsive question text size */
            .question-text {
                font-size: 1.1rem !important; /* Minimum 16px equivalent */
                line-height: 1.6;
            }
        }
        
        @media (max-width: 480px) {

        /* Handle iOS Safari bottom bar */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .progress-bar-container {
                padding-bottom: calc(1.2rem + env(safe-area-inset-bottom)) !important;
            }
        }

        /* Prevent any possible z-index conflicts */
        .progress-bar-container {
            isolation: isolate !important;
        }

        }

        /* Ensure smooth animations on all devices */
        @media (prefers-reduced-motion: reduce) {
            .progress-bar-container {
                transition: none !important;
            }
            
            .progress-bar-container.hidden {
                transition: none !important;
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
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .question-box {
            background: transparent;
            border-radius: 0;
            padding: 0 0 1.75rem;
            margin-bottom: 1.75rem;
            box-shadow: none;
            border: 0;
            border-bottom: 2px solid rgba(148, 163, 184, 0.28);
        }

        .question-content-wrapper {
            display: flex;
            gap: 3.25rem;
            align-items: flex-start;
            width: 100%;
        }

        .question-main {
            flex: 1;
            min-width: 0;
        }

        .question-text {
            font-size: 1.5rem;
            color: #1a202c;
            font-weight: 600;
            margin-bottom: 1.75rem;
            line-height: 1.45;
        }

        .question-image-container {
            flex: 0 0 min(34vw, 560px);
            max-width: min(34vw, 560px);
            display: flex;
            justify-content: center;
        }

        .question-image {
            width: 100%;
            height: auto;
            max-height: 640px;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .question-image:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        /* Stack on smaller screens */
        @media (max-width: 992px) {
            /* Eliminate laggy timer card on mobile/iPad per request */
            .progress-bar-container {
                display: none !important;
                visibility: hidden !important;
            }

            .question-content-wrapper {
                flex-direction: column !important;
                gap: 1.5rem;
            }

            .question-image-container {
                flex: none !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 1rem 0;
            }

            .question-image {
                max-height: 400px;
            }

            /* Inner header row: badges stack ABOVE the question text */
            .question-main .d-flex.justify-content-between.align-items-start {
                flex-direction: column-reverse !important;
                align-items: flex-start !important;
                gap: 0.6rem;
            }

            /* Badges row: wrap left-to-right, not squished on the side */
            .question-main .d-flex.justify-content-between.align-items-start > div:last-child {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: flex-start !important;
                gap: 0.35rem !important;
                margin-left: 0 !important;
                width: 100%;
            }

            /* Question text: always takes full width */
            .question-text.flex-grow-1 {
                width: 100% !important;
                flex: 1 1 100% !important;
                min-width: 0;
                font-size: 1.15rem;
                line-height: 1.55;
            }
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }

        .form-check {
            width: 100%;
            margin: 0;
            padding-left: 0;
        }

        .form-check input {
            display: none;
        }

        .form-check label {
            display: flex;
            align-items: flex-start;
            width: 100%;
            min-height: 48px; /* Touch target size */
            padding: 1.05rem 1.2rem;
            background-color: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0;
            outline: 1.5px solid transparent;
            font-size: 1.05rem; /* Ensure readable font size */
            line-height: 1.55;
            box-shadow: none;
        }

        @media (min-width: 1200px) {
            .main-content {
                padding-left: 4rem;
                padding-right: 4rem;
            }

            .question-box {
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }

            .question-text {
                font-size: 1.8rem;
                line-height: 1.42;
                margin-bottom: 2rem;
            }

            .options {
                gap: 1rem;
            }

            .form-check label {
                min-height: 72px;
                font-size: 1.12rem;
                padding: 1.2rem 1.35rem;
            }
        }

        .form-check label:hover {
            background-color: #f1f3f5;
            transform: translateY(-1px);
            outline: 1.5px solid #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }

        /* Style for selected answers before grading */
        .form-check input:checked + label {
            background-color: #e3f2fd;
            border-color: #007bff;
            color: #007bff;
        }

        /* STRONG REVIEW MODE STYLES - HIGHER SPECIFICITY TO OVERRIDE BLUE */
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

        /* EXTRA STRONG OVERRIDE FOR SELECTED WRONG ANSWERS */
        .form-check input:checked + label.incorrect {
            background-color: #fff1f0 !important;
            border-color: #f5222d !important;
            color: #f5222d !important;
            box-shadow: 0 0 0 0.2rem rgba(245, 34, 45, 0.25) !important;
        }

        /* ENSURE CORRECT SELECTED ANSWERS SHOW GREEN */
        .form-check input:checked + label.correct {
            background-color: #e3fcef !important;
            border-color: #00a854 !important;
            color: #00a854 !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 168, 84, 0.25) !important;
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

        /* Ensure correct answer card stays green even when inside incorrect feedback */
        .feedback.incorrect .correct-answer-card {
            background-color: #e3fcef !important;
            border: 1px solid #00a854 !important;
            color: #00a854 !important;
        }

        .explanation-review-card {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #f0f7ff;
            border: none;
            border-left: 4px solid #007bff;
            color: #1e3a8a;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .explanation-review-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #007bff;
        }

        .explanation-review-text {
            margin-top: 4px;
            color: #334155;
            line-height: 1.65;
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

        /* Confetti canvas – lives outside body zoom so coordinates are 1:1 with viewport */
        #confetti-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10001;
            pointer-events: none;
        }
        
        .popup-overlay.show {
            display: flex;
        }

        .popup {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            max-width: 1100px; /* Increased for desktop stretching */
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .profile-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        .profile-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .popup {
                width: 95%;
                padding: 1.5rem;
                animation: mobileSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .popup-title {
                font-size: 1.75rem;
            }

            .popup-score {
                font-size: 3rem;
            }

            .score-container {
                padding: 1.25rem;
                margin: 1rem 0;
            }

            /* Mobile quiz optimizations */
            .question-box {
                padding: 0 0 1.5rem;
                margin-bottom: 1.5rem;
                border-radius: 0;
                border-bottom-color: rgba(148, 163, 184, 0.38);
            }

            .question-text {
                font-size: 1.12rem !important;
                line-height: 1.6;
                margin-bottom: 1.35rem;
            }

            .options {
                gap: 0.75rem;
            }

            .form-check {
                padding-left: 0 !important;
            }

            .form-check label {
                font-size: 0.98rem;
                padding: 1rem 1rem;
                min-height: 56px; /* Apple HIG touch target */
            }

            .main-content {
                margin-top: 24px;
                padding: 0 0.85rem 0.75rem;
            }

            /* Prevent horizontal overflow on small screens */
            .quiz-interface {
                padding: 0;
            }

            h1 {
                font-size: 1.25rem;
                line-height: 1.35;
                margin-bottom: 0.45rem;
            }

            .grade-icon {
                width: 60px;
                height: 60px;
                font-size: 1.75rem;
            }

            .source-outline {
                padding: 0.1rem 0 0.6rem;
                margin: 0.1rem 0 0.95rem 0;
            }
        }

        @keyframes mobileSlideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0;
            }

            .question-box {
                padding: 0 0 1.2rem;
                margin-bottom: 1.2rem;
            }

            .question-text {
                font-size: 1.08rem !important;
                margin-bottom: 1rem;
            }

            .options {
                margin-left: -0.1rem;
                margin-right: -0.1rem;
                gap: 0.7rem;
            }

            .form-check label {
                font-size: 0.96rem;
                padding: 0.95rem 0.95rem;
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

        /* CRITICAL FIX: Show correct answer in green for incorrect submissions */
        .form-check-label.correct-answer {
            color: #52c41a !important;
            font-weight: 600 !important;
            position: relative;
            padding-left: 25px;
            background-color: rgba(82, 196, 26, 0.1) !important;
            border-left: 3px solid #52c41a !important;
        }

        .form-check-label.correct-answer::before {
            content: '✓ Correct Answer';
            position: absolute;
            left: 5px;
            color: #52c41a;
            font-weight: bold;
            font-size: 0.9em;
        }

        /* Style for user's incorrect selection */
        .form-check-label.user-incorrect {
            color: #ff4d4f !important;
            font-weight: 600 !important;
            position: relative;
            padding-left: 25px;
            background-color: rgba(255, 77, 79, 0.1) !important;
            border-left: 3px solid #ff4d4f !important;
        }

        .form-check-label.user-incorrect::before {
            content: '✗ Your Answer';
            position: absolute;
            left: 5px;
            color: #ff4d4f;
            font-weight: bold;
            font-size: 0.9em;
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
            background-color: #e3fcef !important;
            border-radius: 8px;
            border: 1px solid #00a854 !important;
            color: #00a854 !important;
            font-weight: 500;
        }

        .explanation-review-card {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #f0f7ff;
            border: none;
            border-left: 4px solid #007bff;
            color: #1e3a8a;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .explanation-review-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #007bff;
        }

        .explanation-review-text {
            margin-top: 4px;
            color: #334155;
            line-height: 1.65;
        }

        .xp-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #4CAF50;
            margin: 15px 0;
            text-align: center;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }

        /* PROFESSIONAL MINIMAL SOURCE DISPLAY */
        .source-outline {
            padding: 0.4rem 0 0.9rem;
            margin: 0.5rem 0 1.5rem 0;
            position: relative;
            display: block;
            width: 100%;
            text-align: center;
        }

        .source-outline small {
            color: #3b82f6;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        .source-outline small strong {
            color: #2563eb;
            font-weight: 600;
        }

        .source-outline i {
            color: #3b82f6;
            opacity: 0.85;
            font-size: 0.8rem;
            margin-right: 0.25rem;
        }


    </style>
    

</head>
<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    {{-- Include Responsive Navigation --}}
    <x-responsive-nav-integration mode="replace" />

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('topics.forCourse', $course->id) }}" class="nav-link">← Back</a>
                <a href="{{ route('profile') }}" class="profile-circle">
                    <img src="{{ $currentUserView['profile_image_url'] }}"
                         alt="Profile Picture"
                         class="profile-image"
                         onerror="this.src='{{ $currentUserView['profile_fallback_url'] }}'">
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h1>@if(isset($assignment) && $assignment && $assignment->title){{ $assignment->title }}@else{{ $topic->name }}@endif</h1>

            <!-- TOTAL TOPIC PROGRESS BAR -->
            @if(isset($totalTopicProgress) && $totalTopicProgress['total'] > 0)
                <div class="total-topic-progress">
                    <h3><i class="fas fa-trophy"></i> Topic Progress</h3>
                    <div class="total-progress-bar">
                        <div class="total-progress-fill" style="width: {{ $totalTopicProgress['percentage'] }}%"></div>
                    </div>
                    <div class="total-progress-text">
                        <span class="total-progress-percentage">{{ $totalTopicProgress['percentage'] }}%</span> Complete
                        <br>
                        <small>Correct Questions: {{ $totalTopicProgress['correct'] }}/{{ $totalTopicProgress['total'] }}</small>
                    </div>
                </div>
            @endif

            <!-- Source information display -->
            @if(isset($selected_sources) && count($selected_sources) > 0 && !in_array('all', $selected_sources))
                <div class="source-outline">
                    <small class="text-secondary">
                        <i class="fas fa-book-open me-1"></i>
                        <strong>Sources:</strong> {{ implode(', ', $selected_sources) }}
                    </small>
                </div>
            @endif

            @if ($questions->isNotEmpty())
                <div class="progress-bar-container" id="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="progress-text" id="progress-text">0 of {{ $questions->count() }} questions answered</div>
                        <div class="timer" id="timer">
                            @if(isset($assignment) && $assignment && $assignment->duration_minutes !== null && $assignment->duration_minutes > 0)
                                <span class="countdown-timer" data-duration="{{ $assignment->duration_minutes }}">
                                    @php
                                        $totalSeconds = $assignment->duration_minutes * 60;
                                        if ($totalSeconds >= 3600) {
                                            $hours = floor($totalSeconds / 3600);
                                            $minutes = floor(($totalSeconds % 3600) / 60);
                                            $seconds = $totalSeconds % 60;
                                            echo sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                                        } else {
                                            echo gmdate('i:s', $totalSeconds);
                                        }
                                    @endphp
                                </span>
                            @elseif(isset($assignment) && $assignment && $assignment->duration_minutes == 0)
                                <span class="countup-timer">00:00</span>
                            @else
                                <span class="countup-timer">00:00</span>
                            @endif
                        </div>
                    </div>
                </div>

                <form id="quiz-form" action="{{ route('quiz.grade', ['course' => $course->id, 'topic' => $topic->id], false) }}" method="POST">
                    @csrf
                    @if(isset($assignment) && $assignment)
                        <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                    @endif
                    @foreach($questions as $question)
                        @if($question->question_type === 'written')
                            @include('components.question-written', ['question' => $question])
                        @else
                            <div class="question-box {{ $question->image_url ? 'has-image' : '' }}" data-question-id="{{ $question->id }}">
                                <div class="question-content-wrapper">
                                    <div class="question-main">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="question-text flex-grow-1">
                                                {{ $question->question }}
                                            </div>
                                            
                                            <div class="ms-2 d-flex flex-column align-items-end gap-1">
                                                @if($currentUserView['is_admin'] ?? false)
                                                    <button class="btn-admin-edit" onclick="openQuizEditModal({{ $question->id }})" title="Edit Question">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>
                                                @endif
                                                @if(!empty($question->enhanced_source_badges))
                                                    @foreach($question->enhanced_source_badges as $badge)
                                                        {{-- Filter out the badge for the current topic being viewed (redundant) --}}
                                                        @if(isset($topic) && strtoupper($topic->name) === strtoupper($badge))
                                                            @continue
                                                        @endif
                                                        
                                                        @if(str_contains($badge, 'HIGH YIELD'))
                                                            <span class="badge bg-danger text-white" style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600;">
                                                                🔥 {{ $badge }}
                                                            </span>
                                                        @elseif(str_contains($badge, 'REPEATED'))
                                                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600;">
                                                                📈 {{ $badge }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-primary text-white" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                                {{ $badge }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Options will be rendered here --}}
                                        <div class="options">
                                            @php
                                                $displayOrder = ['A', 'B', 'C', 'D', 'E'];
                                                $visibleDbLetters = array_values(array_filter($displayOrder, function($l) use ($question) {
                                                    $val = trim($question->{'option_' . strtolower($l)} ?? '');
                                                    return $val !== '' && $val !== '-';
                                                }));
                                                if (empty($visibleDbLetters)) $visibleDbLetters = ['A', 'B', 'C', 'D'];
                                                $shuffledDb = $visibleDbLetters;
                                                shuffle($shuffledDb);
                                            @endphp
                                            @foreach($visibleDbLetters as $idx => $displayLetter)
                                                @php
                                                    $dbLetter = $shuffledDb[$idx];
                                                    $optVal = $question->{'option_' . strtolower($dbLetter)};
                                                @endphp
                                                @if(!empty(trim($optVal ?? '')))
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                            name="answers[{{ $question->id }}]" 
                                                            id="option_{{ $question->id }}_{{ $displayLetter }}" 
                                                            value="{{ $dbLetter }}">
                                                        <label class="form-check-label" for="option_{{ $question->id }}_{{ $displayLetter }}">
                                                            {{ $displayLetter }}. {{ $optVal }}
                                                        </label>
                                                    </div>
                                                @endif
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

                    <button type="submit" id="submit-btn" class="submit-btn">Submit Quiz</button>
                </form>
            @else
                <div class="alert alert-warning text-center" style="max-width: 600px; margin: 2rem auto; border-radius: 15px; border: 2px solid #ffc107;">
                    <h4><i class="fas fa-exclamation-triangle"></i> No Questions Available</h4>
                    <p style="margin-bottom: 0.5rem;">No questions found for the selected source(s): <strong>{{ implode(', ', $selected_sources) }}</strong></p>
                    <p style="font-size: 0.9rem; color: #6c757d; margin-bottom: 1rem;">
                        Try selecting different sources or choose "All Sources" to see all available questions for this topic.
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('topics.forCourse', $course->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Topics
                        </a>
                        <a href="{{ route('quiz.start', ['course' => $course->id, 'topic' => $topic->id]) }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> All Sources
                        </a>
                    </div>
                </div>
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
                <a href="{{ route('topics.forCourse', $course->id) }}" class="popup-btn view-progress-btn" id="view-progress-btn">
                    <i class="fas fa-chart-line"></i>
                    View Progress
                </a>
            </div>
        </div>
    </div>

    <!-- Confetti canvas – sits outside the body zoom context for accurate positioning -->
    <canvas id="confetti-canvas"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        // Create a confetti instance bound to our own canvas so body zoom doesn't shift it
        var confettiCanvas = document.getElementById('confetti-canvas');
        var myConfetti = confetti.create(confettiCanvas, { resize: true, useWorker: true });
    </script>
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

    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/quiz-session-draft.js') }}"></script>
    <script>
        window.SolveQuizPageConfig = {
            draftKey: @json('solve-quiz:' . request()->fullUrl()),
            questionIds: @json($solveQuizView['question_ids'] ?? [])
        };
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const quizForm = document.getElementById("quiz-form");
            const submitButton = document.getElementById("submit-btn");
            const popupOverlay = document.getElementById("popup-overlay");
            const popup = document.getElementById("popup");
            const popupTitle = document.getElementById("popup-title");
            const popupScore = document.getElementById("popup-score");
            const popupMessage = document.getElementById("popup-message");
            const gradeIcon = document.getElementById("grade-icon");
            const reviewButton = document.getElementById("review-btn");
            const retryButton = document.getElementById("retry-btn");
            const viewProgressButton = document.getElementById("view-progress-btn");

            let selectedAnswers = {};
            let latestReviewPayload = null;
            let isSubmitting = false;
            let quizCompleted = false;
            const totalQuestions = {{ $questions->count() }};
            const solveQuizConfig = window.SolveQuizPageConfig || {};
            const quizDraft = window.QuizSessionDraft?.create({
                key: solveQuizConfig.draftKey,
                questionIds: solveQuizConfig.questionIds || [],
                onExpire: () => window.location.reload()
            }) || null;
            let startTime = new Date();

            function applyDraftAnswers(answers) {
                if (!answers || typeof answers !== 'object') {
                    return;
                }

                Object.entries(answers).forEach(([questionId, answer]) => {
                    const normalizedAnswer = typeof answer === 'string' ? answer : '';
                    const radio = document.querySelector(`input[name="answers[${questionId}]"][value="${normalizedAnswer}"]`);
                    if (radio) {
                        radio.checked = true;
                        selectedAnswers[questionId] = normalizedAnswer;
                        return;
                    }

                    const textarea = document.querySelector(`.written-answer[data-question-id="${questionId}"]`);
                    if (textarea) {
                        textarea.value = normalizedAnswer;
                        syncWrittenAnswerCharacterCount(textarea);
                        if (normalizedAnswer.trim() !== '') {
                            selectedAnswers[questionId] = normalizedAnswer;
                        }
                    }
                });
            }

            function syncWrittenAnswerCharacterCount(textarea) {
                if (!textarea) {
                    return;
                }

                const targetId = textarea.getAttribute('data-char-count-target');
                if (!targetId) {
                    return;
                }

                const counter = document.getElementById(targetId);
                if (counter) {
                    counter.textContent = String((textarea.value || '').length);
                }
            }

            function saveQuizDraft() {
                if (!quizDraft || quizCompleted) {
                    return;
                }

                quizDraft.save({
                    answers: selectedAnswers,
                    startedAt: startTime instanceof Date ? startTime.getTime() : Date.now(),
                    scrollY: window.scrollY || 0
                });
            }

            const savedDraft = quizDraft?.load();
            if (savedDraft) {
                applyDraftAnswers(savedDraft.answers);
                if (Number.isFinite(Number(savedDraft.startedAt)) && Number(savedDraft.startedAt) > 0) {
                    startTime = new Date(Number(savedDraft.startedAt));
                }
            }

            // 💀 SIMPLIFIED PROGRESS BAR - NO OVERLAP COMPLEXITY 💀
            const progressBarContainer = document.querySelector('.progress-bar-container');
            const submitBtn = document.getElementById('submit-btn');
            
            // LETHAL PROGRESS BAR - Ultra-fast with safety checks
            function updateProgressBarVisibility() {
                if (!submitBtn || !progressBarContainer || !document.body) return;
                
                try {
                    const submitBtnRect = submitBtn.getBoundingClientRect();
                    const isSubmitVisible = submitBtnRect.top < window.innerHeight;
                    
                    if (isSubmitVisible) {
                        progressBarContainer.style.display = 'none';
                    } else {
                        progressBarContainer.style.display = 'block';
                    }
                } catch (error) {
                    // LETHAL FALLBACK - Hide progress bar if any error occurs
                    if (progressBarContainer) {
                        progressBarContainer.style.display = 'none';
                    }
                }
            }
            
            // 💀 LETHAL CLIENT-SIDE ANSWER CHECKING 💀
            let questionData = {};
            let answerCache = {};
            
            // PRELOAD ALL QUESTION DATA ON PAGE LOAD
            console.log('🔥 PRELOADING QUESTION DATA FOR INSTANT CHECKING...');
            
            // Extract question data from DOM
            questionData = @json($solveQuizView['question_data'] ?? []);
            
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

            // SIMPLE PROGRESS BAR - SAME AS QUIZ MODE

            // SIMPLE SCROLL LISTENER - SAME AS QUIZ MODE
            window.addEventListener('scroll', updateProgressBarVisibility, { passive: true });
            window.addEventListener('resize', updateProgressBarVisibility, { passive: true });
            
            // Initial check
            setTimeout(updateProgressBarVisibility, 100);
            
            console.log('✅ SIMPLIFIED Progress Bar System ACTIVATED');

            function buildExplanationCard(explanation) {
                if (!explanation) {
                    return null;
                }

                const explanationCard = document.createElement('div');
                explanationCard.className = 'explanation-review-card';

                const title = document.createElement('strong');
                title.textContent = 'Explanation:';

                const body = document.createElement('div');
                body.className = 'explanation-review-text';
                body.textContent = explanation;

                explanationCard.appendChild(title);
                explanationCard.appendChild(body);

                return explanationCard;
            }

            function resolveReviewData(payload) {
                const quizData = payload?.data || payload || {};

                return {
                    correctAnswers: payload?.correct_answers ||
                        quizData.correct_answers ||
                        quizData.correctAnswers ||
                        {},
                    incorrectAnswers: payload?.incorrect_answers ||
                        quizData.incorrect_answers ||
                        {},
                    questionExplanations: payload?.question_explanations ||
                        quizData.question_explanations ||
                        {}
                };
            }

            function showReviewMode(payload) {
                const { correctAnswers, incorrectAnswers, questionExplanations } = resolveReviewData(payload);

                if (!correctAnswers || Object.keys(correctAnswers).length === 0) {
                    console.log('❌ REVIEW MODE: No correct_answers data found in response');
                    return;
                }

                console.log('🔍 REVIEW MODE: Processing correct answers for review', correctAnswers);

                Object.keys(correctAnswers).forEach(questionId => {
                    const correctAnswer = correctAnswers[questionId];
                    const incorrectData = incorrectAnswers[questionId] || null;
                    const submittedAnswer = Object.prototype.hasOwnProperty.call(selectedAnswers, questionId)
                        ? selectedAnswers[questionId]
                        : (incorrectData?.submitted_answer ?? '');
                    const questionBox = document.querySelector(`[data-question-id="${questionId}"]`);
                    const explanation = incorrectData?.explanation || questionExplanations[questionId] || '';

                    if (!questionBox) {
                        return;
                    }

                    const existingQuestionCard = questionBox.querySelector('.explanation-review-card');
                    if (existingQuestionCard) {
                        existingQuestionCard.remove();
                    }

                    const writtenAnswer = questionBox.querySelector('.written-answer');

                    if (writtenAnswer) {
                        const feedbackElement = questionBox.querySelector('.answer-feedback');

                        if (!feedbackElement) {
                            return;
                        }

                        writtenAnswer.disabled = true;
                        writtenAnswer.classList.remove('correct', 'incorrect');
                        feedbackElement.classList.remove('correct', 'incorrect');
                        feedbackElement.textContent = '';

                        const existingCorrectAnswerCard = feedbackElement.querySelector('.correct-answer-card');
                        if (existingCorrectAnswerCard) {
                            existingCorrectAnswerCard.remove();
                        }

                        const isCorrect = !Object.prototype.hasOwnProperty.call(incorrectAnswers, questionId);

                        if (isCorrect) {
                            writtenAnswer.classList.add('correct');
                            feedbackElement.classList.add('correct');
                            feedbackElement.textContent = 'Correct!';
                        } else {
                            writtenAnswer.classList.add('incorrect');
                            feedbackElement.classList.add('incorrect');
                            feedbackElement.textContent = 'Incorrect.';

                            const correctAnswerCard = document.createElement('div');
                            correctAnswerCard.className = 'correct-answer-card';
                            correctAnswerCard.innerHTML = `Correct answer: <strong>${correctAnswer}</strong>`;
                            feedbackElement.appendChild(correctAnswerCard);
                        }

                        const explanationCard = buildExplanationCard(explanation);
                        if (explanationCard) {
                            feedbackElement.appendChild(explanationCard);
                        }

                        return;
                    }

                    const labels = questionBox.querySelectorAll('.form-check label');
                    const inputs = questionBox.querySelectorAll('.form-check input');
                    inputs.forEach(input => {
                        input.disabled = true;
                    });

                    labels.forEach(label => {
                        const input = label.previousElementSibling;
                        if (!input) {
                            return;
                        }

                        const isCorrectAnswer = input.value === correctAnswer;
                        const isSelectedAnswer = input.value === submittedAnswer;
                        const isIncorrectSelection = isSelectedAnswer && !isCorrectAnswer;

                        label.classList.remove('correct', 'incorrect');

                        if (isCorrectAnswer) {
                            label.classList.add('correct');
                        } else if (isIncorrectSelection) {
                            label.classList.add('incorrect');
                        }
                    });

                    const optionsContainer = questionBox.querySelector('.options');
                    const explanationCard = buildExplanationCard(explanation);

                    if (optionsContainer && explanationCard) {
                        optionsContainer.appendChild(explanationCard);
                    }
                });

                const firstQuestion = document.querySelector('.question-box');
                if (firstQuestion) {
                    firstQuestion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                console.log('✅ REVIEW MODE: Answer marking completed');
            }

            if (quizForm) {
                // Handle multiple choice answers
                document.querySelectorAll('.form-check input').forEach(input => {
                    input.addEventListener('change', function() {
                        const questionId = this.name.match(/\[(\d+)\]/)[1];
                        selectedAnswers[questionId] = this.value;
                        updateProgress();
                        saveQuizDraft();
                    });
                });

                // Handle written answers
                document.querySelectorAll('.written-answer').forEach(textarea => {
                    syncWrittenAnswerCharacterCount(textarea);
                    textarea.addEventListener('input', function() {
                        const questionId = this.getAttribute('data-question-id');
                        syncWrittenAnswerCharacterCount(this);
                        if (this.value.trim() !== '') {
                            selectedAnswers[questionId] = this.value;
                        } else {
                            delete selectedAnswers[questionId];
                        }
                        updateProgress();
                        saveQuizDraft();
                    });
                });

                // Function to update progress
                function updateProgress() {
                    const answeredQuestions = Object.keys(selectedAnswers).length;
                    
                    // Update progress bar
                    const progressFill = document.getElementById('progress-fill');
                    const progressText = document.getElementById('progress-text');
                    const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                    
                    progressFill.style.width = `${progressPercentage}%`;
                    progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                    
                    // submitButton.disabled = answeredQuestions !== totalQuestions;
                }

                // Add timer functionality
                window.quizStartTime = startTime.getTime(); // LETHAL FIX: Set global quiz start time
                let timerInterval;
                const timerElement = document.getElementById('timer');
                const countdownSpan = timerElement.querySelector('.countdown-timer');
                const countupSpan = timerElement.querySelector('.countup-timer');
                const isCountdown = countdownSpan !== null;
                const isCountup = countupSpan !== null;
                const durationMinutes = isCountdown ? parseFloat(countdownSpan.dataset.duration) : null;
                let totalSeconds = isCountdown ? Math.floor(durationMinutes * 60) : 0;
                
                // FALLBACK: If assignment has duration but countdown not detected, force countdown mode
                @if(isset($assignment) && $assignment && $assignment->duration_minutes !== null && $assignment->duration_minutes > 0)
                    if (!isCountdown && {{ $assignment->duration_minutes }} > 0) {
                        console.log('🔄 FALLBACK: Forcing countdown mode for assignment');
                        // Create countdown span if it doesn't exist
                        if (!countdownSpan) {
                            const newCountdownSpan = document.createElement('span');
                            newCountdownSpan.className = 'countdown-timer';
                            newCountdownSpan.setAttribute('data-duration', '{{ $assignment->duration_minutes }}');
                            @php
                                $totalSeconds = $assignment->duration_minutes * 60;
                                if ($totalSeconds >= 3600) {
                                    $hours = floor($totalSeconds / 3600);
                                    $minutes = floor(($totalSeconds % 3600) / 60);
                                    $seconds = $totalSeconds % 60;
                                    $initialTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                                } else {
                                    $initialTime = gmdate('i:s', $totalSeconds);
                                }
                            @endphp
                            newCountdownSpan.textContent = '{{ $initialTime }}';
                            timerElement.appendChild(newCountdownSpan);
                            
                            // Update references
                            const newCountdownSpanRef = timerElement.querySelector('.countdown-timer');
                            const newDurationMinutes = parseFloat(newCountdownSpanRef.dataset.duration);
                            const newTotalSeconds = Math.floor(newDurationMinutes * 60);
                            
                            // Override variables
                            Object.assign(window, {
                                isCountdown: true,
                                isCountup: false,
                                durationMinutes: newDurationMinutes,
                                totalSeconds: newTotalSeconds,
                                countdownSpan: newCountdownSpanRef
                            });
                            
                            console.log('✅ FALLBACK: Countdown mode activated');
                        }
                    }
                @endif
                
                // FALLBACK: If assignment has no time limit but countup not detected, force countup mode
                @if(isset($assignment) && $assignment && $assignment->duration_minutes == 0)
                    if (!isCountup && {{ $assignment->duration_minutes }} == 0) {
                        console.log('🔄 FALLBACK: Forcing countup mode for assignment with no time limit');
                        // Create countup span if it doesn't exist
                        if (!countupSpan) {
                            const newCountupSpan = document.createElement('span');
                            newCountupSpan.className = 'countup-timer';
                            newCountupSpan.textContent = '00:00';
                            timerElement.appendChild(newCountupSpan);
                            
                            // Update references
                            const newCountupSpanRef = timerElement.querySelector('.countup-timer');
                            
                            // Override variables
                            Object.assign(window, {
                                isCountdown: false,
                                isCountup: true,
                                durationMinutes: 0,
                                totalSeconds: 0,
                                countupSpan: newCountupSpanRef
                            });
                            
                            console.log('✅ FALLBACK: Countup mode activated');
                        }
                    }
                @endif
                
                // DEBUG: Log timer detection
                console.log('🔍 TIMER DEBUG:');
                console.log('Timer element:', timerElement);
                console.log('Countdown span:', countdownSpan);
                console.log('Countup span:', countupSpan);
                console.log('Is countdown:', isCountdown);
                console.log('Is countup:', isCountup);
                console.log('Duration minutes:', durationMinutes);
                console.log('Total seconds:', totalSeconds);
                console.log('Assignment duration:', '{{ isset($assignment) && $assignment ? ($assignment->duration_minutes ?? "null") : "no assignment" }}');
                
                function updateTimer() {
                    // LETHAL TIMER - No excessive logging for performance
                    
                    if (isCountdown) {
                        // Countdown timer for assignments
                        const currentTime = new Date();
                        const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                        const remainingSeconds = Math.max(0, totalSeconds - elapsedSeconds);
                        
                        // Professional timer format: show hours when 60+ minutes
                        if (remainingSeconds >= 3600) { // 60+ minutes
                            const hours = Math.floor(remainingSeconds / 3600);
                            const minutes = Math.floor((remainingSeconds % 3600) / 60);
                            const seconds = remainingSeconds % 60;
                            countdownSpan.textContent = 
                                `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        } else {
                            const minutes = Math.floor(remainingSeconds / 60);
                            const seconds = remainingSeconds % 60;
                            countdownSpan.textContent = 
                                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        }
                        
                        // Change color based on time remaining
                        if (remainingSeconds <= 60) { // Last minute - red
                            countdownSpan.style.color = '#dc3545';
                            countdownSpan.style.fontWeight = 'bold';
                        } else if (remainingSeconds <= 300) { // Last 5 minutes - orange
                            countdownSpan.style.color = '#fd7e14';
                            countdownSpan.style.fontWeight = 'bold';
                        } else if (remainingSeconds <= 600) { // Last 10 minutes - yellow
                            countdownSpan.style.color = '#ffc107';
                        }
                        
                        // RUTHLESS AUTO-SUBMIT when time runs out
                        if (remainingSeconds <= 0) {
                            clearTimeout(timerInterval);
                            
                            @if(isset($assignment) && $assignment && $assignment->duration_minutes)
                                // COUNTDOWN ASSIGNMENT: RUTHLESS TIME-UP SUBMISSION
                                
                                // RUTHLESSLY mark ALL unanswered questions as empty = incorrect = mistakes
                                const allQuestions = document.querySelectorAll('.question-box');
                                allQuestions.forEach(questionBox => {
                                    const questionId = questionBox.getAttribute('data-question-id');
                                    if (!selectedAnswers[questionId]) {
                                        selectedAnswers[questionId] = ''; // EMPTY = INCORRECT = MISTAKE
                                        console.log(`Question ${questionId} UNANSWERED - MARKED AS INCORRECT MISTAKE`);
                                    }
                                });
                                
                                // Also handle written questions
                                const textAreas = document.querySelectorAll('textarea[name^="answers["]');
                                textAreas.forEach(textarea => {
                                    const questionId = textarea.getAttribute('data-question-id');
                                    if (!selectedAnswers[questionId] || selectedAnswers[questionId].trim() === '') {
                                        selectedAnswers[questionId] = ''; // EMPTY = INCORRECT = MISTAKE
                                        textarea.value = '';
                                        console.log(`Written question ${questionId} UNANSWERED - MARKED AS INCORRECT MISTAKE`);
                                    }
                                });
                                
                                console.log('RUTHLESS TIME-UP: All unanswered questions will be recorded as mistakes');
                                
                            @else
                                alert('Time is up! Your quiz will be submitted automatically.');
                            @endif
                            
                            // Force submit - no mercy
                            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                            document.getElementById('quiz-form').dispatchEvent(submitEvent);
                            return;
                        }
                    } else if (isCountup) {
                        // Count up timer for no-duration assignments
                        const currentTime = new Date();
                        const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                        const minutes = Math.floor(elapsedSeconds / 60);
                        const seconds = elapsedSeconds % 60;
                        
                        countupSpan.textContent = 
                            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        
                        // Keep neutral styling for count-up
                        countupSpan.style.color = '#6c757d';
                        countupSpan.style.fontWeight = 'normal';
                    } else {
                        // Elapsed time timer for regular practice quizzes
                        const currentTime = new Date();
                        const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                        const minutes = Math.floor(elapsedSeconds / 60);
                        const seconds = elapsedSeconds % 60;
                        
                        timerElement.textContent = 
                            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    timerInterval = setTimeout(updateTimer, 1000);
                }
                
                // Submission listeners must exist before an expired draft can submit.
                updateProgress();
                if (savedDraft && Number.isFinite(Number(savedDraft.scrollY))) {
                    requestAnimationFrame(() => window.scrollTo({ top: Number(savedDraft.scrollY), behavior: 'auto' }));
                }

                // LETHAL FLAG - Prevent double sound playing
                let soundPlayed = false;
                
                // Reset flag when quiz starts
                soundPlayed = false;

                function setPopupActionsDisabled(disabled) {
                    [reviewButton, retryButton, viewProgressButton].forEach(action => {
                        if (!action) {
                            return;
                        }

                        if ('disabled' in action) {
                            action.disabled = disabled;
                        }

                        action.style.pointerEvents = disabled ? 'none' : '';
                        action.style.opacity = disabled ? '0.55' : '';
                    });
                }

                // Function to update source progress
                function updateSourceProgress(sourceProgress) {
                    if (!sourceProgress) return;
                    
                    Object.entries(sourceProgress).forEach(([source, data]) => {
                        const progressBar = document.querySelector(`[data-source="${source}"] .source-progress-value`);
                        if (progressBar) {
                            progressBar.style.width = `${data.percentage}%`;
                    
                            // FIXED: Update status class based on attempts and percentage
                            let statusClass;
                            if (data.has_attempts) {
                                if (data.percentage >= 90) {
                                    statusClass = 'completed';
                                } else if (data.percentage >= 50) {
                                    statusClass = 'in-progress';
                                } else {
                                    statusClass = 'failed';
                                }
                            } else {
                                statusClass = 'not-started';
                            }
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
                    if (isSubmitting || quizCompleted) return;
                    isSubmitting = true;
                    const gradingStartedAt = performance.now();
                    console.log('� SF16 RAPTOR QUIZ GRADING...');

                    // INSTANT UI FEEDBACK
                    submitButton.disabled = true;
                    submitButton.textContent = 'Grading...';
                    
                    // INSTANT DURATION CALC
                    const endTime = Date.now();
                    let durationSeconds = Math.floor((endTime - (window.quizStartTime || startTime.getTime())) / 1000);
                    
                    // LETHAL FIX: Ensure duration is a valid integer
                    if (isNaN(durationSeconds) || durationSeconds < 0) {
                        console.error('Invalid duration calculated:', durationSeconds);
                        durationSeconds = 1; // Fallback to 1 second if calculation fails
                    }
                    
                    clearTimeout(timerInterval);
                    
                    // 💀 NUCLEAR SPEED GRADING 💀
                    let score = 0;
                    
                    // LEAN GRADING LOOP
                    for (const [questionId, userAnswer] of Object.entries(selectedAnswers)) {
                        const questionInfo = questionData[questionId];
                        if (questionInfo && userAnswer.toLowerCase().trim() === questionInfo.correct_answer.toLowerCase().trim()) {
                            score++;
                        }
                    }
                    
                    const percentage = Math.round((score / totalQuestions) * 100);
                    const gradingTime = performance.now() - gradingStartedAt;
                    
                    console.log(`✅ GRADED in ${Math.round(gradingTime)}ms: ${score}/${totalQuestions} (${percentage}%)`);
                    
                    primeQuizCompletionSounds();
                    
                    // LETHAL DATA PREPARATION - Ultra-fast
                    const formData = new FormData(this);
                    formData.append('duration_seconds', parseInt(durationSeconds) || 1);
                    formData.append('mode', '{{ $mode ?? 'quiz' }}');
                    
                    // ENSURE ALL ANSWERS ARE INCLUDED
                    // Double-check that all radio button answers are in the FormData
                    const radioInputs = this.querySelectorAll('input[type="radio"]:checked');
                    const textInputs = this.querySelectorAll('textarea[name^="answers["]');
                    
                    console.log('Radio inputs found:', radioInputs.length);
                    console.log('Text inputs found:', textInputs.length);
                    
                    // Verify answers are in FormData
                    let answersInFormData = 0;
                    for (let [key, value] of formData.entries()) {
                        if (key.startsWith('answers[')) {
                            answersInFormData++;
                        }
                    }
                    
                    console.log('Answers in FormData:', answersInFormData);
                    console.log('Total questions:', {{ $questions->count() }});
                    
                    // DEBUG: Log form data
                    console.log('Form submission data:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key, value);
                    }
                    
                    // RUTHLESS VALIDATION: For countdown quizzes, FORCE submission with unanswered = incorrect
                    const answeredQuestions = Object.keys(selectedAnswers).length;
                    
                    @if(isset($assignment) && $assignment && $assignment->duration_minutes)
                        // COUNTDOWN ASSIGNMENT: RUTHLESS AUTO-SUBMISSION
                        // Fill in ALL unanswered questions with empty strings (marked as incorrect)
                        const allQuestions = document.querySelectorAll('.question-box');
                        allQuestions.forEach(questionBox => {
                            const questionId = questionBox.getAttribute('data-question-id');
                            if (!selectedAnswers[questionId]) {
                                selectedAnswers[questionId] = ''; // EMPTY = INCORRECT = MISTAKE
                                
                                // Also ensure FormData has this empty answer
                                const answerKey = `answers[${questionId}]`;
                                if (!formData.has(answerKey)) {
                                    formData.set(answerKey, '');
                                }
                            }
                        });
                        
                        // Also check text areas for written questions
                        const textAreas = document.querySelectorAll('textarea[name^="answers["]');
                        textAreas.forEach(textarea => {
                            const questionId = textarea.getAttribute('data-question-id');
                            if (!selectedAnswers[questionId] || selectedAnswers[questionId].trim() === '') {
                                selectedAnswers[questionId] = ''; // EMPTY = INCORRECT = MISTAKE
                                textarea.value = ''; // Ensure form field is empty
                            }
                        });
                        
                        console.log('RUTHLESS MODE: All unanswered questions marked as incorrect');
                        console.log('Final selectedAnswers:', selectedAnswers);
                        
                    @else
                        // For regular quizzes, require all questions to be answered
                        if (answeredQuestions !== totalQuestions) {
                            // Find the first unanswered question
                            const allQuestionBoxes = document.querySelectorAll('.question-box');
                            let firstUnanswered = null;
                            for (const box of allQuestionBoxes) {
                                const qId = box.getAttribute('data-question-id');
                                if (!selectedAnswers[qId]) {
                                    firstUnanswered = box;
                                    break;
                                }
                            }

                            if (firstUnanswered) {
                                firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                
                                // Temporary highlight effect
                                const originalBorder = firstUnanswered.style.borderColor;
                                firstUnanswered.style.transition = 'all 0.3s ease';
                                firstUnanswered.style.borderColor = '#dc3545';
                                firstUnanswered.style.boxShadow = '0 0 15px rgba(220, 53, 69, 0.3)';
                                
                                setTimeout(() => {
                                    firstUnanswered.style.borderColor = originalBorder;
                                    firstUnanswered.style.boxShadow = '';
                                }, 3000);
                            }

                            alert(`Please answer all questions. You have answered ${answeredQuestions} out of ${totalQuestions} questions. We've scrolled to the first unanswered question for you.`);
                            submitButton.disabled = false;
                            submitButton.innerHTML = 'Submit Quiz';
                            return;
                        }
                    @endif
                    
                    // ENSURE ALL ANSWERS ARE IN FORMDATA (including empty ones for assignments)
                    @if(isset($assignment) && $assignment)
                        // For assignments, FORCE all questions to have answers in FormData
                        // Clear existing FormData answers and rebuild from selectedAnswers
                        const keysToDelete = [];
                        for (let [key, value] of formData.entries()) {
                            if (key.startsWith('answers[')) {
                                keysToDelete.push(key);
                            }
                        }
                        keysToDelete.forEach(key => formData.delete(key));
                        
                        // Add all answers from selectedAnswers to FormData
                        for (let questionId in selectedAnswers) {
                            const answerKey = `answers[${questionId}]`;
                            formData.set(answerKey, selectedAnswers[questionId]);
                            console.log('FORCE Added to FormData:', answerKey, `"${selectedAnswers[questionId]}"`);
                        }
                        
                        // Recount FormData answers after rebuilding
                        answersInFormData = 0;
                        for (let [key, value] of formData.entries()) {
                            if (key.startsWith('answers[')) {
                                answersInFormData++;
                            }
                        }
                        console.log('FINAL FormData answers count:', answersInFormData);
                        console.log('FINAL selectedAnswers count:', Object.keys(selectedAnswers).length);
                    @else
                        // For regular quizzes, ensure FormData matches selectedAnswers
                        if (answersInFormData !== totalQuestions) {
                            console.error('Mismatch between selectedAnswers and FormData');
                            console.error('selectedAnswers:', selectedAnswers);
                            console.error('FormData answers count:', answersInFormData);
                            
                            // Try to manually add missing answers to FormData
                            for (let questionId in selectedAnswers) {
                                const answerKey = `answers[${questionId}]`;
                                if (!formData.has(answerKey)) {
                                    formData.set(answerKey, selectedAnswers[questionId]);
                                    console.log('Added missing answer:', answerKey, selectedAnswers[questionId]);
                                }
                            }
                        }
                    @endif
                    
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
                        if (!data.success || data.error) throw new Error(data.error || 'Grading failed');
                        quizCompleted = true;
                        quizDraft?.clear();
                        latestReviewPayload = data;

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

                        // Handle nested data structure from controller
                        const quizData = data.data || data;

                        // Update score display - use the correct field names from GradingService
                        const score = quizData.score || 0;
                        const total = quizData.totalQuestions || quizData.total_questions || 0;
                        popupScore.textContent = `${score}/${total}`;

                        // Use the correct message field
                        popupMessage.textContent = quizData.encouragement || quizData.message || data.message || 'Quiz completed!';
                        setPopupActionsDisabled(false);

                        // Calculate percentage
                        const percentage = (score / total) * 100;
                        playQuizCompletionSound(score, total);

                        // Update grade icon and title
                        if (percentage === 100) {
                            gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                            gradeIcon.className = 'grade-icon perfect';
                            popupTitle.textContent = 'Perfect Score!';
                            // Trigger confetti for perfect score

                            // Direct confetti call for perfect scores – uses dedicated canvas, centered on all browsers
                            if (typeof myConfetti !== 'undefined') {
                                myConfetti({
                                    particleCount: 100,
                                    spread: 70,
                                    origin: { x: 0.5, y: 0.45 },
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
                            // Store progress data in localStorage for real-time updates
                            const progressData = {
                                    topicId: {{ $topic->id }},
                                    @if(isset($assignment) && $assignment)
                                    assignmentId: {{ $assignment->id }}, // Assignment context for proper card targeting
                                    @else
                                    assignmentId: null, // Regular quiz - no assignment context
                                    @endif
                                    progress: data.source_progress,
                                    overall_topic_progress: data.overall_topic_progress, // 🔥 NEW: Include overall topic progress
                                    availableSources: Object.keys(data.source_progress),
                                allSourcesMastered: Object.values(data.source_progress).every(p => p.percentage >= 90),
                                timestamp: Math.floor(Date.now() / 1000)
                            };
                            
                            // Store in localStorage for cross-page communication
                            localStorage.setItem('quizProgressUpdate', JSON.stringify(progressData));
                            
                            // Dispatch event for current page (if any listeners)
                            const event = new CustomEvent('sourceProgressUpdated', {
                                detail: progressData
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
                                    @if(isset($assignment) && $assignment)
                                    assignmentId: {{ $assignment->id }},
                                    @else
                                    assignmentId: null,
                                    @endif
                                    mode: 'quiz',
                                    progress: data.source_progress,
                                    overall_topic_progress: data.overall_topic_progress,
                                    availableSources: Object.keys(data.source_progress),
                                    allSourcesMastered: Object.values(data.source_progress).every(p => p.percentage >= 90),
                                    userId: {{ auth()->id() }}
                                })
                            }).then(response => response.json())
                            .then(result => {
                                console.log('🔥 Server progress update stored:', result);
                            })
                            .catch(error => {
                                console.error('Failed to store progress update on server:', error);
                            });
                            
                            console.log('🔥 Quiz progress updated and stored for real-time sync (localStorage + server)');
                        }
                        
                        // LETHAL ASSIGNMENT COMPLETION EVENT - Only for assignments
                        @if(isset($assignment) && $assignment)
                        if (data.assignment_completed) {
                            console.log('🔥 DISPATCHING ASSIGNMENT COMPLETION EVENT');
                            const assignmentData = {
                                assignmentId: {{ $assignment->id }},
                                percentage: data.percentage,
                                score: data.score,
                                total: data.total,
                                timestamp: Date.now()
                            };
                            
                            // Store in localStorage for cross-page communication
                            localStorage.setItem('assignmentCompleted', JSON.stringify(assignmentData));
                            
                            // Dispatch event for current page (if any listeners)
                            const assignmentEvent = new CustomEvent('assignmentCompleted', {
                                detail: assignmentData
                            });
                            document.dispatchEvent(assignmentEvent);
                            
                            // 🔥 SERVER-SIDE STORAGE - For cross-tab communication
                            fetch('{{ route("progress.store-assignment") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    assignmentId: {{ $assignment->id }},
                                    percentage: data.percentage,
                                    score: data.score,
                                    total: data.total,
                                    userId: {{ auth()->id() }}
                                })
                            }).then(response => response.json())
                            .then(result => {
                                console.log('🔥 Server assignment completion stored:', result);
                            })
                            .catch(error => {
                                console.error('Failed to store assignment completion on server:', error);
                            });
                            
                            console.log('🔥 Assignment completion stored for real-time sync (localStorage + server)');
                        }
                        @endif
                        
                        // Show popup
                        popupOverlay.classList.add('show');
                        popup.classList.add('show');
                    })
                    .catch((error) => {
                        console.error('Quiz submission error:', error);
                        isSubmitting = false;
                        if (!isCountdown || Date.now() - startTime.getTime() < durationMinutes * 60000) {
                            clearTimeout(timerInterval);
                            timerInterval = setTimeout(updateTimer, 1000);
                        }
                        
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
                        
                        gradeIcon.innerHTML = '<i class="fas fa-circle-exclamation"></i>';
                        gradeIcon.className = 'grade-icon needs-improvement';
                        popupTitle.textContent = 'Submission Failed';
                        popupScore.textContent = '';
                        popupMessage.textContent = errorMessage;
                        popupOverlay.classList.add('show');
                        popup.classList.add('show');
                        setPopupActionsDisabled(false);
                        saveQuizDraft();
                        if (reviewButton) {
                            reviewButton.disabled = true;
                            reviewButton.style.pointerEvents = 'none';
                            reviewButton.style.opacity = '0.55';
                        }
                    });
                });

                window.addEventListener('beforeunload', saveQuizDraft);
                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'hidden') {
                        saveQuizDraft();
                    }
                });
                window.addEventListener('scroll', saveQuizDraft, { passive: true });
                saveQuizDraft();
                updateTimer();
            }

            // Add event listeners to popup buttons
            reviewButton.addEventListener("click", function () {
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);

                showReviewMode(latestReviewPayload);
                
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
                window.location.href = "{{ route('topics.forCourse', $course->id) }}";
            });
        });

        // LETHAL GLOBAL FLAG - Prevent any duplicate sound plays
        let globalSoundPlayed = false;
        let globalSoundPrimed = false;

        function primeQuizCompletionSounds() {
            if (globalSoundPrimed) {
                return;
            }

            globalSoundPrimed = true;

            ['quizPerfectSound', 'quizPartialSound', 'quizWrongSound'].forEach((soundElementId) => {
                const audioElement = document.getElementById(soundElementId);

                if (!audioElement) {
                    return;
                }

                try {
                    audioElement.volume = 0.7;
                    audioElement.muted = true;

                    const playPromise = audioElement.play();

                    if (playPromise && typeof playPromise.then === 'function') {
                        playPromise.then(() => {
                            audioElement.pause();
                            audioElement.currentTime = 0;
                            audioElement.muted = false;
                        }).catch(() => {
                            audioElement.muted = false;
                            audioElement.load();
                        });
                    } else {
                        audioElement.muted = false;
                        audioElement.load();
                    }
                } catch (_error) {
                    audioElement.muted = false;
                    audioElement.load();
                }
            });
        }
        
        // SIMPLE RELIABLE AUDIO SYSTEM
        function playQuizCompletionSound(score, total) {
            // LETHAL CHECK - Prevent duplicate plays
            if (globalSoundPlayed) {
                console.log('🚫 Sound already played - preventing duplicate');
                return;
            }
            
            globalSoundPlayed = true;
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

        // Function to create confetti – uses dedicated canvas, no browser-specific offsets needed
        function createConfetti() {
            if (typeof myConfetti !== 'undefined') {
                myConfetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { x: 0.5, y: 0.45 },
                    colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                    zIndex: 10000
                });
            }
        }
    </script>

    <script>
        // CONFETTI TEST FUNCTION – works on all browsers via dedicated canvas
        window.testConfetti = function() {
            if (typeof myConfetti !== 'undefined') {
                myConfetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { x: 0.5, y: 0.45 },
                    colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                    zIndex: 10000
                });
                alert('Testing confetti... check if confetti appears centered!');
            } else {
                alert('ERROR: No confetti library loaded! Check browser or network issues.');
            }
        };

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
            } catch (_e) {
                return text; // Return the original text if decoding fails
            }
        }
    </script>

    @if($currentUserView['is_admin'] ?? false)
    {{-- ======================================================
         ADMIN QUESTION EDIT MODAL — solve_quiz.blade.php
         Supports MCQ + Written + Delete
    ====================================================== --}}
    <script>
    window.QUIZ_QUESTION_DATA = {
        @foreach($questions as $question)
        {{ $question->id }}: {
            question_text:        @json($question->question ?? ''),
            question_type:        @json($question->question_type ?? 'multiple_choice'),
            correct_answer:       @json($question->correct_answer ?? ''),
            explanation:          @json($question->explanation ?? ''),
            option_a:             @json($question->option_a ?? ''),
            option_b:             @json($question->option_b ?? ''),
            option_c:             @json($question->option_c ?? ''),
            option_d:             @json($question->option_d ?? ''),
            option_e:             @json($question->option_e ?? ''),
            alternative_answers:  @json($question->alternative_answers ?? ''),
        },
        @endforeach
    };
    </script>

    <style>
        /* ── Admin Edit Button (solve_quiz) ── */
        .btn-admin-edit {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.6rem;
            font-size: 0.72rem;
            font-weight: 500;
            color: #6c757d;
            background: transparent;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            position: relative;
            z-index: 2;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }
        .btn-admin-edit:hover {
            color: #007bff;
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.05);
        }
        .btn-admin-edit i { font-size: 0.65rem; }
        /* Reuse edit-question-overlay/modal styles from tutor_mode */
        #sq-edit-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.55);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10500;
            padding: 1rem;
        }
        #sq-edit-overlay.show { display: flex; }
        #sq-edit-modal {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #dbe1e8;
            width: 100%;
            max-width: 1100px; /* Wider for desktop edit convenience */
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            animation: sqEditIn 0.25s cubic-bezier(0.22,1,0.36,1);
        }
        @keyframes sqEditIn {
            from { opacity:0; transform:translateY(24px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        #sq-edit-modal .eq-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:1.25rem 1.5rem;
            background:#ffffff;
            border-bottom:1px solid #e2e8f0;
            border-radius:12px 12px 0 0;
        }
        #sq-edit-modal .eq-header h5 { color:#0f172a; margin:0; font-size:1.05rem; font-weight:600; display:flex; align-items:center; gap:.5rem; }
        #sq-edit-modal .eq-close {
            background:#ffffff; border:1px solid #d1d5db; border-radius:8px;
            color:#475569; width:32px; height:32px; display:flex; align-items:center; justify-content:center;
            font-size:1.2rem; cursor:pointer; transition:background .2s,border-color .2s,color .2s;
        }
        #sq-edit-modal .eq-close:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
        #sq-edit-modal .eq-body { padding:1.5rem; }
        .sq-type-row {
            display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem;
            padding:.75rem 1rem; background:#ffffff; border-radius:10px; border:1px solid #e2e8f0;
        }
        .sq-type-label { font-size:.82rem; font-weight:600; color:#64748b; }
        .sq-type-badge {
            display:inline-flex; align-items:center; gap:.45rem; padding:.1rem 0;
            border-radius:0; font-size:.8rem; font-weight:600; cursor:pointer;
            border:none; transition:color .2s ease; position:relative;
        }
        .sq-type-badge::before {
            content:"";
            width:10px;
            height:10px;
            border-radius:50%;
            border:1.5px solid #94a3b8;
            background:#ffffff;
            flex-shrink:0;
            transition:background-color .2s ease,border-color .2s ease;
        }
        .sq-type-badge.mcq,
        .sq-type-badge.written { background:transparent; color:#475569; }
        .sq-type-badge.mcq.active,
        .sq-type-badge.written.active { background:transparent; color:#166534; }
        .sq-type-badge.active::before { background:#16a34a; border-color:#16a34a; }
        .sq-field { margin-bottom:1.1rem; }
        .sq-field label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
        .sq-input {
            width:100%; padding:.65rem .9rem; border:1px solid #d1d5db; border-radius:8px;
            font-size:.95rem; font-family:inherit; transition:border-color .2s,box-shadow .2s;
            box-sizing:border-box; resize:vertical;
        }
        .sq-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.12); }
        .sq-opts-section { margin-bottom:1.1rem; }
        .sq-opts-section > label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.6rem; }
        .sq-opt-row { display:flex; align-items:center; gap:.6rem; margin-bottom:.55rem; }
        .sq-opt-radio { display:flex; align-items:center; gap:.4rem; flex-shrink:0; }
        .sq-opt-radio input[type="radio"] { width:16px; height:16px; accent-color:#16a34a; cursor:pointer; margin:0; }
        .sq-radio-lbl {
            font-weight:600; font-size:.85rem; color:#475569;
            background:transparent; border:none; width:auto; height:auto;
            display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .sq-opt-row .sq-input { margin-bottom:0; flex:1; }
        .sq-footer {
            display:flex; align-items:center; justify-content:space-between;
            padding:1rem 1.5rem; border-top:1px solid #e2e8f0;
            background:#ffffff; border-radius:0 0 12px 12px; gap:.75rem; flex-wrap:wrap;
        }
        .sq-footer-left { display:flex; gap:.5rem; }
        .sq-footer-right { display:flex; gap:.5rem; }
        .sq-btn {
            padding:.6rem 1.25rem; border:1px solid transparent; border-radius:8px; font-size:.9rem; font-weight:600;
            cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; transition:background-color .2s ease,border-color .2s ease,color .2s ease;
        }
        .sq-btn-cancel { background:#ffffff; border-color:#d1d5db; color:#475569; }
        .sq-btn-cancel:hover { background:#f8fafc; border-color:#cbd5e1; }
        .sq-btn-save { background:#2563eb; border-color:#2563eb; color:#fff; }
        .sq-btn-save:hover { background:#1d4ed8; border-color:#1d4ed8; }
        .sq-btn-save:disabled { opacity:.6; cursor:not-allowed; }
        .sq-btn-delete { background:#dc2626; border-color:#dc2626; color:#fff; }
        .sq-btn-delete:hover { background:#b91c1c; border-color:#b91c1c; }
        .sq-status { padding:0 1.5rem 1rem; font-size:.88rem; font-weight:500; min-height:1.5rem; }
        .sq-status.success { color:#16a34a; }
        .sq-status.error   { color:#dc2626; }
        .sq-del-confirm {
            display:none; background:#fef2f2; border:1.5px solid #fca5a5;
            border-radius:10px; padding:1rem 1.25rem; margin:0 1.5rem 1rem;
        }
        .sq-del-confirm.show { display:block; }
        .sq-del-confirm p { margin:0 0 .75rem; color:#7f1d1d; font-size:.9rem; font-weight:500; }
        .sq-del-actions { display:flex; gap:.5rem; }
    </style>

    <!-- Admin Edit Modal DOM -->
    <div id="sq-edit-overlay">
        <div id="sq-edit-modal">
            <div class="eq-header">
                <h5><i class="fas fa-pen"></i> Edit Question</h5>
                <button class="eq-close" onclick="sqCloseModal()">&times;</button>
            </div>
            <div class="eq-body">
                <input type="hidden" id="sq-q-id">
                <input type="hidden" id="sq-q-type">

                <div class="sq-type-row">
                    <span class="sq-type-label">Type:</span>
                    <span class="sq-type-badge mcq" id="sq-type-mcq" onclick="sqSwitchType('multiple_choice')">
                        <i class="fas fa-list-ul"></i> Multiple Choice
                    </span>
                    <span class="sq-type-badge written" id="sq-type-written" onclick="sqSwitchType('written')">
                        <i class="fas fa-pencil-alt"></i> Written
                    </span>
                </div>

                <div class="sq-field">
                    <label><i class="fas fa-question-circle"></i> Question Text</label>
                    <textarea id="sq-q-text" class="sq-input" rows="4" placeholder="Enter question text..."></textarea>
                </div>

                <div id="sq-mcq-section">
                    <div class="sq-opts-section">
                        <label><i class="fas fa-check-circle" style="color:#16a34a"></i> Options — select radio next to the correct answer</label>
                        <div class="sq-opt-row">
                            <div class="sq-opt-radio"><input type="radio" name="sq-correct" id="sq-ca" value="A"><label for="sq-ca" class="sq-radio-lbl">A</label></div>
                            <input type="text" id="sq-oa" class="sq-input" placeholder="Option A">
                        </div>
                        <div class="sq-opt-row">
                            <div class="sq-opt-radio"><input type="radio" name="sq-correct" id="sq-cb" value="B"><label for="sq-cb" class="sq-radio-lbl">B</label></div>
                            <input type="text" id="sq-ob" class="sq-input" placeholder="Option B">
                        </div>
                        <div class="sq-opt-row">
                            <div class="sq-opt-radio"><input type="radio" name="sq-correct" id="sq-cc" value="C"><label for="sq-cc" class="sq-radio-lbl">C</label></div>
                            <input type="text" id="sq-oc" class="sq-input" placeholder="Option C">
                        </div>
                        <div class="sq-opt-row">
                            <div class="sq-opt-radio"><input type="radio" name="sq-correct" id="sq-cd" value="D"><label for="sq-cd" class="sq-radio-lbl">D</label></div>
                            <input type="text" id="sq-od" class="sq-input" placeholder="Option D">
                        </div>
                        <div class="sq-opt-row">
                            <div class="sq-opt-radio"><input type="radio" name="sq-correct" id="sq-ce" value="E"><label for="sq-ce" class="sq-radio-lbl">E</label></div>
                            <input type="text" id="sq-oe" class="sq-input" placeholder="Option E">
                        </div>
                    </div>
                </div>

                <div id="sq-written-section" style="display:none;">
                    <div class="sq-field">
                        <label><i class="fas fa-check" style="color:#16a34a"></i> Correct Answer</label>
                        <textarea id="sq-correct-text" class="sq-input" rows="3" placeholder="Enter the expected correct answer..."></textarea>
                    </div>
                    <div class="sq-field">
                        <label><i class="fas fa-random" style="color:#7c3aed"></i> Alternative Answers <span style="font-weight:400;color:#94a3b8">(one per line, optional)</span></label>
                        <textarea id="sq-alt-answers" class="sq-input" rows="3" placeholder="Enter alternative answers, one per line..."></textarea>
                    </div>
                </div>

                <div class="sq-field">
                    <label><i class="fas fa-lightbulb" style="color:#d97706"></i> Explanation <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                    <textarea id="sq-explanation" class="sq-input" rows="3" placeholder="Add an explanation..."></textarea>
                </div>
            </div>

            <div class="sq-del-confirm" id="sq-del-confirm">
                <p><i class="fas fa-exclamation-triangle"></i> Are you sure you want to permanently delete this question? This cannot be undone.</p>
                <div class="sq-del-actions">
                    <button class="sq-btn sq-btn-delete" id="sq-confirm-del" onclick="sqConfirmDelete()"><i class="fas fa-trash"></i> Yes, Delete</button>
                    <button class="sq-btn sq-btn-cancel" onclick="sqCancelDelete()">Cancel</button>
                </div>
            </div>

            <div class="sq-status" id="sq-status"></div>

            <div class="sq-footer">
                <div class="sq-footer-left">
                    <button class="sq-btn sq-btn-delete" id="sq-del-btn" onclick="sqPromptDelete()">
                        <i class="fas fa-trash-alt"></i> Delete Question
                    </button>
                </div>
                <div class="sq-footer-right">
                    <button class="sq-btn sq-btn-cancel" onclick="sqCloseModal()">Cancel</button>
                    <button class="sq-btn sq-btn-save" id="sq-save-btn" onclick="sqSaveEdit()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/solve-quiz-admin.js') }}" defer></script>
    @endif
    @include('partials.secure-study-protection', ['surfaceLabel' => 'quiz mode', 'hideOnBackground' => false])
</body>
</html>
