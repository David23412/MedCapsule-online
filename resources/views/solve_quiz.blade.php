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
            background: rgba(248, 249, 250, 0.98);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15), 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            max-width: 1000px;
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
            margin-bottom: 45px !important; /* BULLETPROOF: Always ensure space for progress bar */
            position: relative;
            z-index: 1;
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
            .progress-bar-container {
                max-width: 100%;
                border-radius: 0;
                padding: 0.75rem 1rem;
                left: 0;
                transform: translateX(0);
                width: 100vw;
            }
            
            .progress-bar-container.hidden {
                transform: translateY(120%) !important;
            }
            
            .progress-bar-container:not(.hidden) {
                transform: translateY(0) !important;
            }
        }
        
        @media (max-width: 480px) {
            .progress-bar-container {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .progress-text {
                font-size: 0.8rem;
            }
            
            .timer {
                font-size: 0.8rem;
            }
        }

        /* BULLETPROOF: Handle very small screens */
        @media (max-width: 320px) {
            .progress-bar-container {
                padding: 0.6rem !important;
                min-height: 60px !important;
            }
            
            .submit-btn {
                margin-bottom: 65px !important;
            }
        }

        /* BULLETPROOF: Handle landscape orientation on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .progress-bar-container {
                padding: 0.5rem 1rem !important;
                min-height: 50px !important;
            }
            
            .submit-btn {
                margin-bottom: 55px !important;
            }
        }

        /* BULLETPROOF: Handle iOS Safari bottom bar */
        @supports (padding-bottom: env(safe-area-inset-bottom)) {
            .progress-bar-container {
                padding-bottom: calc(1.2rem + env(safe-area-inset-bottom)) !important;
            }
        }

        /* BULLETPROOF: Prevent any possible z-index conflicts */
        .progress-bar-container {
            isolation: isolate !important;
        }

        /* BULLETPROOF: Ensure smooth animations on all devices */
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

        /* Ensure correct answer card stays green even when inside incorrect feedback */
        .feedback.incorrect .correct-answer-card {
            background-color: #e3fcef !important;
            border: 1px solid #00a854 !important;
            color: #00a854 !important;
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
            background-color: #e3fcef !important;
            border-radius: 8px;
            border: 1px solid #00a854 !important;
            color: #00a854 !important;
            font-weight: 500;
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
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

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
            <h1>@if(isset($assignment) && $assignment && $assignment->title){{ $assignment->title }}@else{{ $topic->name }}@endif</h1>

            <!-- Source information display -->
            @if(isset($selected_sources) && count($selected_sources) > 0 && !in_array('all', $selected_sources))
                <div class="sources-info text-center mb-4">
                    <span class="text-muted">
                        Source: {{ implode(', ', $selected_sources) }}
                    </span>
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

                <form id="quiz-form" action="{{ secure_url(route('quiz.grade', ['course' => $course->id, 'topic' => $topic->id], false)) }}" method="POST">
                    @csrf
                    @if(isset($assignment) && $assignment)
                        <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
                    @endif
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
                <a href="{{ route('topics.forCourse', $course->id) }}" class="popup-btn view-progress-btn" id="view-progress-btn">
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
            const totalQuestions = {{ $questions->count() }};

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

            // SIMPLE PROGRESS BAR - SAME AS QUIZ MODE

            // SIMPLE SCROLL LISTENER - SAME AS QUIZ MODE
            window.addEventListener('scroll', updateProgressBarVisibility, { passive: true });
            window.addEventListener('resize', updateProgressBarVisibility, { passive: true });
            
            // Initial check
            setTimeout(updateProgressBarVisibility, 100);
            
            console.log('✅ SIMPLIFIED Progress Bar System ACTIVATED');

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
                
                // Start the timer
                updateTimer();

                // LETHAL FLAG - Prevent double sound playing
                let soundPlayed = false;
                
                // Reset flag when quiz starts
                soundPlayed = false;
                
                // Function to show quiz results popup
                function showQuizResults(data) {
                    const popupOverlay = document.getElementById('popup-overlay');
                    const popupTitle = document.getElementById('popup-title');
                    const popupScore = document.getElementById('popup-score');
                    const popupMessage = document.getElementById('popup-message');
                    const gradeIcon = document.getElementById('grade-icon');
                    
                    // Update popup content
                    popupScore.textContent = `${data.score}/${data.total}`;
                    
                    // Set title and message based on performance
                    if (data.percentage === 100) {
                        popupTitle.textContent = 'Perfect Score!';
                        popupMessage.textContent = 'Outstanding! You got everything right!';
                        gradeIcon.innerHTML = '<i class="fas fa-star" style="color: #ffd700;"></i>';
                    } else if (data.percentage >= 80) {
                        popupTitle.textContent = 'Well Done!';
                        popupMessage.textContent = 'Great job! You\'re making excellent progress!';
                        gradeIcon.innerHTML = '<i class="fas fa-thumbs-up" style="color: #28a745;"></i>';
                    } else if (data.percentage >= 60) {
                        popupTitle.textContent = 'Good Effort!';
                        popupMessage.textContent = 'You\'re on the right track! Keep practicing!';
                        gradeIcon.innerHTML = '<i class="fas fa-check-circle" style="color: #17a2b8;"></i>';
                    } else {
                        popupTitle.textContent = 'Keep Learning!';
                        popupMessage.textContent = 'Don\'t worry! Review the material and try again!';
                        gradeIcon.innerHTML = '<i class="fas fa-lightbulb" style="color: #ffc107;"></i>';
                    }
                    
                    // Show popup
                    popupOverlay.classList.add('show');
                    
                    // LETHAL FIX: Play sound only once
                    if (!soundPlayed) {
                        playQuizCompletionSound(data.score, data.total);
                        soundPlayed = true;
                    }
                }

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
                    
                    const startTime = performance.now();
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
                    const gradingTime = performance.now() - startTime;
                    
                    console.log(`✅ GRADED in ${Math.round(gradingTime)}ms: ${score}/${totalQuestions} (${percentage}%)`);
                    
                    // INSTANT RESULTS
                    showQuizResults({
                        score: score,
                        total: totalQuestions,
                        percentage: percentage,
                        processing_time: gradingTime
                    });
                    
                    // LETHAL DATA PREPARATION - Ultra-fast
                    const formData = new FormData(this);
                    formData.append('time_taken', parseInt(durationSeconds) || 1); // LETHAL FIX: Ensure integer with fallback
                    formData.append('mode', '{{ $mode ?? 'quiz' }}');
                    
                    // LETHAL DEBUG: Log what we're sending
                    console.log('🔥 SENDING time_taken:', parseInt(durationSeconds) || 1, 'type:', typeof (parseInt(durationSeconds) || 1));
                    
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
                            alert(`Please answer all questions. You have answered ${answeredQuestions} out of ${totalQuestions} questions.`);
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
                            // Store progress data in localStorage for real-time updates
                            const progressData = {
                                    topicId: {{ $topic->id }},
                                    @if(isset($assignment) && $assignment)
                                    assignmentId: {{ $assignment->id }}, // Assignment context for proper card targeting
                                    @else
                                    assignmentId: null, // Regular quiz - no assignment context
                                    @endif
                                    progress: data.source_progress,
                                    availableSources: Object.keys(data.source_progress),
                                allSourcesMastered: Object.values(data.source_progress).every(p => p.percentage >= 90),
                                timestamp: Date.now()
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
                                    progress: data.source_progress,
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
                        
                        // LETHAL FIX: Sound already played in showQuizResults - no duplicate
                        
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
                window.location.href = "{{ route('topics.forCourse', $course->id) }}";
            });
        });

        // LETHAL GLOBAL FLAG - Prevent any duplicate sound plays
        let globalSoundPlayed = false;
        
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
