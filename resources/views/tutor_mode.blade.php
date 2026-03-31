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
        :root {
            --highlight-yellow: #fff59d;
            --highlight-pink: #f8bbd0;
            --highlight-blue: #b3e5fc;
            --highlight-green: #c5e1a5;
            --highlight-purple: #e1bee7;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            zoom: 0.75;
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
            max-width: 1550px;
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

        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        .container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 1550px;
            margin: 0 auto;
        }

        .question-card {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .question-content {
            display: flex;
            gap: 2.5rem;
            align-items: flex-start;
            margin-bottom: 2rem;
            width: 100%;
        }

        .question-main {
            flex: 1;
            min-width: 0;
        }

        .question-text-container {
            flex: 1;
            min-width: 0;
        }

        /* When there's no image, text takes full width */
        .question-content:not(:has(.question-image)) .question-text-container {
            flex: 1 1 100%;
            max-width: none;
        }

        .question-text {
            font-size: 1.4rem;
            font-weight: 425;
            color: #000000;
            margin-bottom: 1rem;
            line-height: 1.5;
            user-select: text;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
        }

        /* Highlight styles */
        .question-text mark {
            border-radius: 6px;
            padding: 0.08em 0.18em;
            transition: background-color 0.18s ease, box-shadow 0.18s ease;
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
        }

        .question-text mark.highlight-yellow { background-color: var(--highlight-yellow); box-shadow: inset 0 -0.42em 0 rgba(255, 245, 157, 0.82); }
        .question-text mark.highlight-pink { background-color: var(--highlight-pink); box-shadow: inset 0 -0.42em 0 rgba(248, 187, 208, 0.86); }
        .question-text mark.highlight-blue { background-color: var(--highlight-blue); box-shadow: inset 0 -0.42em 0 rgba(179, 229, 252, 0.9); }
        .question-text mark.highlight-green { background-color: var(--highlight-green); box-shadow: inset 0 -0.42em 0 rgba(197, 225, 165, 0.9); }
        .question-text mark.highlight-purple { background-color: var(--highlight-purple); box-shadow: inset 0 -0.42em 0 rgba(225, 190, 231, 0.9); }

        /* Highlight toolbar */
        .highlight-toolbar {
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-10px) scale(0.96);
            transition: opacity 0.14s ease, transform 0.18s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.14s ease;
        }

        .highlight-toolbar.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        .highlight-toolbar[data-placement="bottom"] {
            transform: translateY(10px) scale(0.96);
        }

        .highlight-toolbar.show[data-placement="bottom"] {
            transform: translateY(0) scale(1);
        }

        .highlight-btn {
            width: 34px;
            height: 34px;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
            flex-shrink: 0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.32);
        }

        .highlight-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(59, 130, 246, 0.48);
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.14);
        }

        .highlight-btn:active {
            transform: scale(0.95);
        }

        .highlight-btn:focus-visible {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        }

        .highlight-btn.yellow { background-color: var(--highlight-yellow); }
        .highlight-btn.pink { background-color: var(--highlight-pink); }
        .highlight-btn.blue { background-color: var(--highlight-blue); }
        .highlight-btn.green { background-color: var(--highlight-green); }
        .highlight-btn.purple { background-color: var(--highlight-purple); }

        .highlight-btn.eraser {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 14px;
        }

        .highlight-btn.eraser:hover {
            color: #dc3545;
            border-color: #dc3545;
            box-shadow: 0 8px 18px rgba(220, 53, 69, 0.12);
        }

        .toolbar-divider {
            width: 1px;
            height: 26px;
            background: rgba(203, 213, 225, 0.88);
            margin: 0 3px;
        }

        /* Mobile responsiveness for highlight toolbar */
        @media (max-width: 768px) {
            .highlight-toolbar {
                gap: 5px;
                padding: 7px;
                border-radius: 14px;
                max-width: calc(100vw - 20px);
            }

            .highlight-btn {
                width: 32px;
                height: 32px;
            }

            .toolbar-divider {
                height: 22px;
            }
        }

        @media (max-width: 480px) {
            .highlight-toolbar {
                gap: 4px;
                padding: 6px;
            }

            .highlight-btn {
                width: 30px;
                height: 30px;
            }
        }

        .question-image {
            flex: 0 0 450px;
            max-width: 45%;
            display: flex;
            justify-content: center;
        }

        .question-image img {
            width: 100%;
            height: auto;
            max-height: 550px;
            border-radius: 12px;
            object-fit: contain;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .question-image img:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .options-container {
            width: 100%;
        }

        .option-card {
            padding: 1rem;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: 1.5px solid transparent;
        }

        .option-card:hover {
            background-color: #f1f3f5;
            border-color: #007bff;
            transform: translateY(-1px);
            outline: 1px solid #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }

        .option-card.selected {
            border-color: #007bff;
            background: #e3f2fd;
            color: #007bff;
        }

        .option-card.correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .option-card.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
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

        /* Disable blue highlight after checking answer */
        .option-card[style*="pointer-events: none"] {
            cursor: default !important;
        }

        .option-card[style*="pointer-events: none"]:hover {
            outline: none !important;
            border-color: inherit !important;
            background-color: inherit !important;
            transform: none !important;
            box-shadow: none !important;
            color: inherit !important;
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
            margin-bottom: 0.25rem;
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
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 5px;
            animation: waveMove 3s linear infinite;
            box-shadow:
                0 0 10px rgba(0, 123, 255, 0.5),
                0 0 20px rgba(0, 123, 255, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .progress-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            animation: shimmer 2s infinite;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(
                circle at 50% 50%,
                rgba(255, 255, 255, 0.1) 0%,
                transparent 70%
            );
            animation: pulse 2s ease-in-out infinite;
        }

        .progress-fill.warning {
            background:
                linear-gradient(90deg, #ffc107, #ff8c00),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20" viewBox="0 0 120 20"><path fill="%23ffffff" fill-opacity="0.3" d="M0 10 Q 10 0, 20 10 T 40 10 T 60 10 T 80 10 T 100 10 T 120 10 V20 H0 Z"/></svg>') repeat-x;
            box-shadow:
                0 0 10px rgba(255, 193, 7, 0.5),
                0 0 20px rgba(255, 193, 7, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .progress-fill.danger {
            background:
                linear-gradient(90deg, #dc3545, #ff6b7a),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20" viewBox="0 0 120 20"><path fill="%23ffffff" fill-opacity="0.3" d="M0 10 Q 10 0, 20 10 T 40 10 T 60 10 T 80 10 T 100 10 T 120 10 V20 H0 Z"/></svg>') repeat-x;
            box-shadow:
                0 0 10px rgba(220, 53, 69, 0.5),
                0 0 20px rgba(220, 53, 69, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        @keyframes waveMove {
            0% {
                background-position: 0 0, 0 0;
            }
            100% {
                background-position: 0 0, 60px 0;
            }
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }
            100% {
                left: 100%;
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.8;
            }
            50% {
                opacity: 1;
            }
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
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timer i {
            color: #007bff;
            transition: color 0.3s ease;
        }

        .timer.warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            color: #856404;
            animation: timerWarning 1s ease-in-out infinite alternate;
        }

        .timer.warning i {
            color: #ffc107;
        }

        .timer.danger {
            background: linear-gradient(135deg, #f8d7da, #fab1a0);
            border: 2px solid #dc3545;
            color: #721c24;
            animation: timerDanger 0.5s ease-in-out infinite alternate;
        }

        .timer.danger i {
            color: #dc3545;
        }

        @keyframes timerWarning {
            0% { transform: scale(1); }
            100% { transform: scale(1.02); }
        }

        @keyframes timerDanger {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }

        .answer-feedback .alert {
            border-radius: 8px;
            padding: 1.25rem;
            padding-left: 1.25rem;
            margin-top: 1rem;
            position: relative;
            background-color: #f0f7ff;
            border: none;
            border-left: 4px solid #007bff;
            color: #333;
        }

        .answer-feedback .alert.alert-success {
            background-color: #f0f7ff;
            border-left: 4px solid #007bff;
            color: #333;
        }

        .answer-feedback .alert.alert-danger {
            background-color: #f0f7ff;
            border-left: 4px solid #007bff;
            color: #333;
        }

        /* Explanation content wrapper */
        .explanation-content {
            padding: 0;
            margin: 0;
        }

        /* Doctor icon styles for explanation */
        .explanation-with-icon {
            display: block;
            padding: 0;
            margin: 0;
        }

        /* Inline layout for warning messages - doctor icon next to text */
        .explanation-with-icon.warning-inline {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Header with profile pic and correct answer on same line */
        .answer-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .doctor-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            flex-shrink: 0;
        }

        .answer-header strong {
            margin: 0;
            font-weight: 600;
            color: #28a745;
            font-size: 1.1rem;
        }

        .explanation-text {
            padding: 0;
            margin: 0;
            line-height: 1.7;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .explanation-text strong {
            display: block;
            margin: 0 0 1rem 0;
            font-weight: 600;
            color: #28a745;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.1rem;
        }

        .result-text {
            display: none !important;
        }

        .result-text-inline {
            display: none !important;
        }

        /* Ensure explanation text has proper spacing */
        .explanation-text > *:first-child {
            margin-top: 0;
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

        /* Written answer feedback styling – match quiz mode for clarity */
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
            /* Reset desktop zoom on tablets and phones */
            body {
                zoom: 1 !important;
            }

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
                max-width: 420px;
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



        /* ===== ENHANCED MOBILE COMPATIBILITY OPTIMIZATIONS ===== */
        
        /* Mobile Navigation */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 1rem;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1001;
            }
            
            .navbar .container {
                padding: 0;
                max-width: 100%;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .profile-circle {
                width: 35px;
                height: 35px;
            }
        }

        /* Mobile Main Content */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 70px;
                padding: 1rem;
            }
            
            .container {
                padding: 1.5rem;
                border-radius: 8px;
                max-width: 100%;
                margin: 0;
            }
            
            .topic-header h1 {
                font-size: 1.5rem;
                text-align: center;
                margin-bottom: 1rem;
            }
        }

        /* Mobile Progress Bar */
        @media (max-width: 768px) {
            .progress-bar-container {
                padding: 0.75rem;
                margin-bottom: 1rem;
                border-radius: 8px;
                position: sticky;
                top: 60px;
                z-index: 998;
                background: white;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            
            .timer {
                font-size: 1rem;
                padding: 0.4rem 0.8rem;
                margin-bottom: 0.75rem;
            }
            
            .progress-text {
                font-size: 0.85rem;
                margin-top: 0.4rem;
            }
        }

        /* Mobile Question Card */
        @media (max-width: 768px) {
            .question-card {
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 1rem;
            }
            
            .question-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .question-text {
                font-size: 1.2rem;
                line-height: 1.4;
                margin-bottom: 1rem;
            }
            
            .question-image {
                width: 100%;
                max-width: 100%;
                margin: 0 0 1rem 0;
            }
            
            .question-image img {
                width: 100%;
                height: auto;
                max-height: 250px;
                object-fit: contain;
            }
        }

        /* Mobile Options */
        @media (max-width: 768px) {
            .option-card {
                padding: 0.875rem;
                margin-bottom: 0.75rem;
                border-radius: 8px;
                gap: 0.75rem;
            }
            
            .option-letter {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
                flex-shrink: 0;
            }
            
            .option-content {
                font-size: 0.95rem;
                line-height: 1.4;
            }
        }

        /* Mobile Actions */
        @media (max-width: 768px) {
            .actions {
                margin-top: 1.25rem;
                flex-direction: column;
                gap: 0.75rem;
                padding-bottom: 60px; /* Ensure content isn't hidden behind fixed button */
            }
            
            .btn {
                padding: 0.875rem 1.25rem;
                font-size: 1rem;
                width: 100%;
                min-height: 54px; /* Touch target */
                touch-action: manipulation;
            }
            
            .actions-left {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .actions-left .btn {
                position: fixed !important;
                bottom: 0;
                left: 0;
                right: 0;
                margin: 0 !important;
                border-radius: 0;
                z-index: 1000;
                box-shadow: 0 -4px 10px rgba(0,0,0,0.1);
                padding-bottom: calc(1rem + env(safe-area-inset-bottom)) !important;
            }

            .btn-dont-know {
                width: 44px; /* Larger touch target */
                height: 44px;
                font-size: 1.1rem;
                align-self: flex-start;
            }
        }

        /* Mobile Popup */
        @media (max-width: 768px) {
            .popup {
                padding: 2rem 1.5rem;
                width: 95%;
                max-width: 400px;
                border-radius: 16px;
            }
            
            .popup-title {
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            
            .popup-score {
                font-size: 3rem;
            }
            
            .popup-message {
                font-size: 1.1rem;
                margin: 1rem 0;
            }
            
            .popup-buttons {
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 1.5rem;
            }
            
            .popup-btn {
                width: 100%;
                justify-content: center;
                min-height: 44px;
                font-size: 1rem;
                padding: 0.875rem 1.5rem;
            }
            
            .grade-icon {
                font-size: 2.5rem;
                margin-bottom: 0.75rem;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .main-content {
                padding: 0.75rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .topic-header h1 {
                font-size: 1.3rem;
            }
            
            .question-card {
                padding: 1rem;
            }
            
            .question-text {
                font-size: 1.1rem;
            }
            
            .option-card {
                padding: 0.75rem;
                gap: 0.5rem;
            }
            
            .option-letter {
                width: 26px;
                height: 26px;
                font-size: 0.85rem;
            }
            
            .option-content {
                font-size: 0.9rem;
            }
            
            .popup {
                padding: 1.5rem 1rem;
            }
            
            .popup-title {
                font-size: 1.8rem;
            }
            
            .popup-score {
                font-size: 2.5rem;
            }
            
            .popup-message {
                font-size: 1rem;
            }
        }

        /* Landscape Mobile Optimization */
        @media (max-width: 768px) and (orientation: landscape) {
            .main-content {
                margin-top: 60px;
            }
            
            .question-content {
                flex-direction: row;
                gap: 1.5rem;
            }
            
            .question-text-container {
                flex: 1;
            }
            
            .question-image {
                width: 200px;
                max-width: 200px;
                margin: 0;
            }
            
            .question-image img {
                max-height: 150px;
            }
            
            .popup {
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        /* Touch Improvements */
        @media (hover: none) and (pointer: coarse) {
            .option-card:hover {
                transform: none;
                background-color: #f8f9fa;
            }
            
            .option-card {
                min-height: 48px;
                touch-action: manipulation;
                -webkit-tap-highlight-color: rgba(0, 123, 255, 0.1);
            }
            
            .nav-link {
                min-height: 48px;
                display: flex;
                align-items: center;
            }
            
            .btn:hover {
                transform: none;
            }
            
            .popup-btn:hover {
                transform: none;
            }
            
            .btn {
                -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
            }
        }

        /* Ultra-Performance Mobile Optimizations */
        @media (max-width: 768px) {
            /* Optimize rendering performance */
            .question-card {
                will-change: auto;
                contain: layout style paint;
            }
            
            /* Simplify progress bar animations */
            .progress-fill {
                animation: none;
            }
            
            .progress-fill::before,
            .progress-fill::after {
                display: none;
            }
            
            /* Optimize text rendering */
            body {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                text-rendering: optimizeSpeed;
            }
            
            /* Reduce motion for accessibility */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
                
                .timer.warning,
                .timer.danger {
                    animation: none;
                }
            }
        }

        /* iOS Safari Specific Fixes */
        @supports (-webkit-touch-callout: none) {
            .main-content {
                min-height: calc(100vh - 80px);
            }
            
            .navbar {
                -webkit-backdrop-filter: blur(10px);
                backdrop-filter: blur(10px);
            }
            
            .popup {
                max-height: calc(100vh - 40px);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Android Chrome Specific Optimizations */
        @media screen and (-webkit-min-device-pixel-ratio: 0) and (min-resolution: .001dpcm) {
            .option-card {
                will-change: transform;
                transform: translateZ(0);
            }
            
            .btn {
                -webkit-tap-highlight-color: rgba(0, 123, 255, 0.3);
            }
        }

        /* Enhanced Accessibility */
        @media (max-width: 768px) {
            /* Improve focus indicators */
            .option-card:focus,
            .btn:focus {
                outline: 2px solid #2196f3;
                outline-offset: 2px;
            }
            
            /* Better text contrast */
            .question-text {
                color: #1a1a1a;
                font-weight: 500;
            }
            
            .option-content {
                color: #2a2a2a;
                line-height: 1.5;
            }
            
            /* Ensure timer is always readable */
            .timer {
                background: rgba(255, 255, 255, 0.95);
                border: 1px solid rgba(0, 0, 0, 0.1);
            }
            
            .timer.warning {
                background: rgba(255, 243, 205, 0.95);
                border-color: #ffc107;
            }
            
            .timer.danger {
                background: rgba(248, 215, 218, 0.95);
                border-color: #dc3545;
            }
        }

        /* Battery and Performance Optimizations */
        @media (max-width: 768px) {
            /* Reduce GPU usage */
            .progress-fill {
                will-change: width;
                transform: none;
            }
            
            /* Optimize animations */
            .popup {
                transition: opacity 0.2s ease, transform 0.2s ease;
            }
            
            .option-card {
                transition: background-color 0.15s ease, border-color 0.15s ease;
            }
            
            /* Prevent unnecessary repaints */
            .container {
                contain: layout style;
            }
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

        /* Question Navigation Sidebar */
        .question-sidebar {
            position: fixed;
            left: 0;
            top: 80px;
            bottom: 0;
            width: 80px;
            background: white;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            z-index: 999;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
        }

        .question-sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .question-sidebar::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 2px;
        }

        .question-circle {
            width: 50px;
            height: 50px;
            min-width: 50px;
            min-height: 50px;
            max-width: 50px;
            max-height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid #e9ecef;
            background: white;
            color: #495057;
            flex-shrink: 0;
        }

        .question-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .question-circle.active {
            border-color: #007bff;
            background: #e3f2fd;
            color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }

        .question-circle.correct {
            border-color: #28a745;
            background: #d4edda;
            color: #28a745;
        }

        .question-circle.incorrect {
            border-color: #dc3545;
            background: #f8d7da;
            color: #dc3545;
        }

        /* Remove blue highlight when already answered */
        .question-circle.correct.active,
        .question-circle.incorrect.active {
            border-color: inherit;
            background: inherit;
            color: inherit;
            box-shadow: none;
        }

        .question-circle.correct.active {
            border-color: #28a745;
            background: #d4edda;
            color: #28a745;
        }

        .question-circle.incorrect.active {
            border-color: #dc3545;
            background: #f8d7da;
            color: #dc3545;
        }

        .question-circle.answered {
            border-color: #6c757d;
            background: #f8f9fa;
            color: #6c757d;
        }

        /* Main content adjustment for sidebar */
        .main-content {
            margin-left: 80px;
        }

        /* Navigation arrows - minimal clean style */
        .question-nav-arrows {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            justify-content: center;
        }

.nav-arrow-btn {
    width: 36px;
    height: 36px;
    padding: 0;
    background: transparent;
    color: #007bff;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

        .nav-arrow-btn:hover:not(:disabled) {
            color: #0056b3;
            transform: scale(1.15);
        }

        .nav-arrow-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .nav-arrow-btn i {
            margin: 0;
        }

        /* I Don't Know button - minimal icon style, lower right */
.btn-dont-know {
    width: 32px;
    height: 32px;
    padding: 0;
    background: transparent;
    border: 2px solid #dc3545;
    border-radius: 50%;
    color: #dc3545;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

        .btn-dont-know:hover {
            background: #f8f9fa;
            border-color: #6c757d;
            color: #6c757d;
            transform: scale(1.08);
        }

        .btn-dont-know i {
            margin: 0;
        }

        .question-card {
            position: relative;
        }

        /* Actions layout with button on right */
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .actions-left {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        /* Mobile responsive sidebar */
        @media (max-width: 768px) {
            .question-sidebar {
                width: 60px;
                padding: 0.75rem 0.25rem;
                gap: 0.5rem;
            }

            .question-circle {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
                border-width: 2px;
            }

            .main-content {
                margin-left: 60px;
            }

            .nav-arrow-btn {
                padding: 0.625rem 1rem;
                font-size: 0.9rem;
                min-width: 44px;
                min-height: 44px;
            }
        }

        @media (max-width: 480px) {
            .question-sidebar {
                width: 50px;
                padding: 0.5rem 0.15rem;
            }

            .question-circle {
                width: 35px;
                height: 35px;
                font-size: 0.85rem;
            }

            .main-content {
                margin-left: 50px;
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Highlight Toolbar (Hidden by default, shown on text selection) -->
    <div class="highlight-toolbar" id="highlight-toolbar">
        <button class="highlight-btn yellow" onclick="applyHighlight('yellow')" title="Yellow" aria-label="Highlight Yellow"></button>
        <button class="highlight-btn pink" onclick="applyHighlight('pink')" title="Pink" aria-label="Highlight Pink"></button>
        <button class="highlight-btn blue" onclick="applyHighlight('blue')" title="Blue" aria-label="Highlight Blue"></button>
        <button class="highlight-btn green" onclick="applyHighlight('green')" title="Green" aria-label="Highlight Green"></button>
        <button class="highlight-btn purple" onclick="applyHighlight('purple')" title="Purple" aria-label="Highlight Purple"></button>
        <div class="toolbar-divider"></div>
        <button class="highlight-btn eraser" onclick="removeHighlight()" title="Remove Highlight" aria-label="Remove Highlight">
            <i class="fas fa-eraser"></i>
        </button>
    </div>



    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">← Back</a>
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

    <!-- Question Navigation Sidebar -->
    <div class="question-sidebar" id="question-sidebar">
        @foreach($questions as $index => $question)
            <div class="question-circle {{ $index === 0 ? 'active' : '' }}" 
                 data-question="{{ $index + 1 }}"
                 onclick="jumpToQuestion({{ $index + 1 }})">
                {{ $index + 1 }}
            </div>
        @endforeach
    </div>

    <div class="main-content">
        <div class="container">
            <div class="topic-header mb-4">
                @if(isset($is_random_quiz) && $is_random_quiz)
                    <h1>Random Quiz - Tutor Mode</h1>
                @else
                    <h1>{{ $topic->name }} - Tutor Mode</h1>
                @endif
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
                    @php
                        $qObj = is_array($question) ? $question['question'] : $question;
                    @endphp
                    <div class="question-content">
                        <div class="question-main">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="question-text-container">
                                    <div class="question-text">
                                        {{ is_array($question) ? $question['question']->question : $question->question }}
                                    </div>
                                </div>
                                <div class="ms-2 d-flex flex-column align-items-end gap-1">
                                    @if(!empty($qObj->enhanced_source_badges))
                                        @foreach($qObj->enhanced_source_badges as $badge)
                                            @if(isset($topic) && strtoupper($topic->name) === strtoupper($badge))
                                                @continue
                                            @endif
                                            @if(str_contains($badge, 'HIGH YIELD'))
                                                <span class="badge bg-danger text-white" style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600; white-space: nowrap;">
                                                    🔥 {{ $badge }}
                                                </span>
                                            @elseif(str_contains($badge, 'REPEATED'))
                                                <span class="badge bg-warning text-dark" style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600; white-space: nowrap;">
                                                    📈 {{ $badge }}
                                                </span>
                                            @else
                                                <span class="badge bg-primary text-white" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; white-space: nowrap;">
                                                    {{ $badge }}
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            @php
                                $rawOptions = [
                                    'A' => $qObj->option_a,
                                    'B' => $qObj->option_b,
                                    'C' => $qObj->option_c,
                                    'D' => $qObj->option_d,
                                ];
                                $cleanedOptions = [];
                                $realOptionCount = 0;
                                foreach ($rawOptions as $letter => $optText) {
                                    $clean = is_null($optText) ? '' : trim($optText);
                                    $cleanedOptions[$letter] = $clean;
                                    if ($clean !== '' && $clean !== '-') {
                                        $realOptionCount++;
                                    }
                                }
                            @endphp

                            <div class="options-container" data-question-id="{{ $qObj->id ?? '' }}">
                                @if(($qObj->question_type ?? null) === 'multiple_choice')
                                    @foreach($cleanedOptions as $letter => $cleanOption)
                                        @if($cleanOption !== '' && $cleanOption !== '-')
                                            <div class="option-card" data-option="{{ $letter }}">
                                                <span class="option-letter">{{ $letter }}</span>
                                                <div class="option-content">{{ $cleanOption }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="written-answer-container">
                                        <textarea class="form-control written-answer" rows="4" placeholder="Type your answer here..."></textarea>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($qObj->image_url ?? false)
                            <div class="question-image">
                                <img src="{{ asset($qObj->image_url) }}" alt="Question Image" class="img-fluid">
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
                        <div class="actions-left">
                            <button class="btn btn-primary check-answer" onclick="checkAnswer({{ $index + 1 }}, {{ $qObj->id ?? '' }})">
                                Check Answer
                            </button>
                            <button class="btn btn-primary next-question" style="display: none;" onclick="showNextQuestion({{ $index + 1 }})">
                                Next Question
                            </button>
                            <button class="btn btn-success view-results" style="display: none;" onclick="showFinalResults()">
                                View Results
                            </button>
                        </div>
                        <button class="btn-dont-know" onclick="handleDontKnow({{ $index + 1 }}, {{ $qObj->id ?? '' }})" title="I Don't Know">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>

                    <!-- Navigation Arrows -->
                    <div class="question-nav-arrows">
                        <button class="nav-arrow-btn" onclick="navigatePrevious({{ $index + 1 }})" {{ $index === 0 ? 'disabled' : '' }} title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="nav-arrow-btn" onclick="navigateNext({{ $index + 1 }})" {{ $index === count($questions) - 1 ? 'disabled' : '' }} title="Next">
                            <i class="fas fa-chevron-right"></i>
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
                <button class="popup-btn review-btn" id="review-answers-btn" type="button">
                    <i class="fas fa-search"></i>
                    Review Answers
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

    <!-- UNIFIED GRADING AUDIO CONTROLLER -->
    <script src="{{ asset('js/grading-audio-controller.js') }}"></script>

    <!-- 🚀 CRITICAL FIX: Move function declarations to HEAD for immediate availability 🚀 -->
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

        // Global variable declarations
        let firstAttemptAnswers = {};
        let questionStates = {};
        // Track when the quiz session started (used for duration + autosubmit)
        let quizStartTime = new Date();
        // Ensure availability across any later scripts that might reference it
        window.quizStartTime = quizStartTime;
        let currentQuestionIndex = 1;
        let totalQuestions = {{ $questions->count() }};
        let correctAnswers = 0;
        let totalAnswers = 0;
        let userAnswers = {};
        let timerInterval;
        let answerCache = {};
        let questionData = {};

        // Highlighting system
        let highlights = {};
        let currentSelection = null;
        let highlightToolbar = null;

        // LIGHTNING FAST FUNCTION DEFINITIONS 🚀
        window.selectOption = function selectOption(element) {
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
        };

        // Jump to specific question from sidebar
        window.jumpToQuestion = function(questionNumber) {
            console.log(`🎯 Jumping to question ${questionNumber}`);

            document.querySelectorAll('.question-card').forEach(card => {
                card.style.display = 'none';
            });

            const targetQuestion = document.getElementById(`question-${questionNumber}`);
            if (targetQuestion) {
                targetQuestion.style.display = 'block';
                currentQuestionIndex = questionNumber;
                updateSidebarActiveState(questionNumber);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };

        // Navigate to previous question
        window.navigatePrevious = function(currentNumber) {
            if (currentNumber > 1) {
                jumpToQuestion(currentNumber - 1);
            }
        };

        // Navigate to next question
        window.navigateNext = function(currentNumber) {
            if (currentNumber < totalQuestions) {
                jumpToQuestion(currentNumber + 1);
            }
        };

        // Handle "I Don't Know" button
        window.handleDontKnow = function(questionNumber, questionId) {
            console.log(`❓ I Don't Know clicked for Q${questionNumber}`);

            if (questionStates[questionNumber]) {
                console.log(`⚠️ Question ${questionNumber} already answered, ignoring`);
                return;
            }

            questionStates[questionNumber] = true;
            firstAttemptAnswers[questionId] = 'Dont know';

            const qData = questionData[questionId];
            if (!qData) {
                console.error(`❌ Question data not found for ID ${questionId}`);
                return;
            }

            totalAnswers++;
            userAnswers[questionId] = 'dont_know';

            updateSidebarCircleState(questionNumber, false);

            const questionCard = document.getElementById(`question-${questionNumber}`);
            if (questionCard) {
                const feedbackDiv = questionCard.querySelector('.answer-feedback');
                const alertDiv = feedbackDiv.querySelector('.alert');

                feedbackDiv.style.display = 'block';
                alertDiv.className = 'alert alert-danger';
                feedbackDiv.querySelector('.result-text').textContent = "Don't worry! Here's the answer:";
                feedbackDiv.querySelector('.explanation-content').innerHTML = `
                    <div class="explanation-with-icon">
                        <div class="answer-header">
                            <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                            <strong>Correct Answer: ${qData.correct_answer}</strong>
                        </div>
                        <div class="explanation-text">
                            ${qData.explanation || 'No explanation available.'}
                        </div>
                    </div>
                `;

                // Highlight the correct answer option in green
                questionCard.querySelectorAll('.option-card').forEach(opt => {
                    const optionLetter = opt.getAttribute('data-option');
                    if (optionLetter === qData.correct_answer) {
                        opt.classList.add('correct');
                    }
                    opt.style.pointerEvents = 'none';
                    opt.style.opacity = '0.6';
                });

                const checkBtn = questionCard.querySelector('.check-answer');
                const dontKnowBtn = questionCard.querySelector('.btn-dont-know');
                if (checkBtn) checkBtn.style.display = 'none';
                if (dontKnowBtn) dontKnowBtn.style.display = 'none';

                // Check if all questions are answered - if so, show View Results button
                if (totalAnswers === {{ $questions->count() }}) {
                    console.log('🔥 ALL QUESTIONS ANSWERED VIA I DONT KNOW: Showing View Results button');
                    const resultsBtn = questionCard.querySelector('.view-results');
                    if (resultsBtn) resultsBtn.style.display = 'inline-block';
                } else {
                    const nextBtn = questionCard.querySelector('.next-question');
                    if (nextBtn) nextBtn.style.display = 'inline-block';
                }
            }

            updateProgress(totalAnswers, {{ $questions->count() }});
            // 🎯 REMOVED INDIVIDUAL QUESTION SOUNDS - ONLY FINAL GRADING SOUND NOW
        };

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

                // Show user-friendly warning message
                const questionCard = document.getElementById(`question-${questionNumber}`);
                if (questionCard) {
                    const feedbackDiv = questionCard.querySelector('.answer-feedback');

                    // Hide any existing feedback first
                    feedbackDiv.style.display = 'none';

                    // Clear processing flag to allow retry
                    delete checkAnswer[processingKey];

                    // Show warning after a brief delay
                    setTimeout(() => {
                        feedbackDiv.style.display = 'block';
                        const alertDiv = feedbackDiv.querySelector('.alert');
                        alertDiv.className = 'alert alert-warning';
                        feedbackDiv.querySelector('.result-text').textContent = 'Please select an answer first!';
                feedbackDiv.querySelector('.explanation-content').innerHTML = `
                    <div class="explanation-with-icon warning-inline">
                        <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                        <div class="explanation-text">You need to choose an answer before checking. Select one of the options above and try again.</div>
                    </div>
                `;
                    }, 300);
                }

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

            // FALLBACK TO NETWORK REQUEST
            console.warn(`⚠️ FALLBACK TO NETWORK for Q${questionId}`);

            if (checkBtn) {
                checkBtn.disabled = true;
                checkBtn.textContent = 'Checking...';
                checkBtn.style.background = '#6c757d';
            }

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
                body: JSON.stringify({ answer })
            })
            .then(response => {
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

                if (!data.success) {
                    throw new Error(data.message || 'Answer check failed');
                }

                // CACHE THE RESULT FOR FUTURE INSTANT ACCESS
                answerCache[`${questionId}_${answer}`] = data;

                // INSTANT ANSWER STORAGE
                userAnswers[questionId] = answer;
                totalAnswers++;
                if (data.is_correct) correctAnswers++;

                // PROCESS RESULT
                processAnswerResult(questionNumber, questionId, data, checkBtn);
                delete checkAnswer[processingKey];
            })
            .catch(error => {
                console.log('ℹ️ Answer check encountered an issue, providing fallback response');

                const fallbackData = {
                    success: false,
                    is_correct: false,
                    error: 'Connection issue',
                    correct_answer: 'Unable to determine',
                    explanation: 'Please check your connection and try again'
                };

                totalAnswers++;
                updateQuestionUI(questionNumber, {
                    correct: fallbackData.is_correct,
                    explanation: fallbackData.explanation,
                    correctAnswer: fallbackData.correct_answer
                });

                questionCard.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
                delete checkAnswer[processingKey];
            });
        };

        // Function to update sidebar active state
        function updateSidebarActiveState(questionNumber) {
            document.querySelectorAll('.question-circle').forEach(circle => {
                circle.classList.remove('active');
                if (parseInt(circle.dataset.question) === questionNumber) {
                    circle.classList.add('active');
                }
            });
        }

        // Update sidebar circle state (correct/incorrect)
        function updateSidebarCircleState(questionNumber, isCorrect) {
            const circle = document.querySelector(`.question-circle[data-question="${questionNumber}"]`);
            if (circle) {
                circle.classList.remove('answered');
                if (isCorrect) {
                    circle.classList.add('correct');
                    circle.classList.remove('incorrect');
                } else {
                    circle.classList.add('incorrect');
                    circle.classList.remove('correct');
                }
            }
        }

        // Update progress bar
        function updateProgress(completed, total) {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const timerElement = document.getElementById('timer');
            const percentage = (completed / total) * 100;

            if (progressFill) {
                progressFill.style.width = `${percentage}%`;

                if (timerElement) {
                    if (timerElement.classList.contains('danger')) {
                        progressFill.classList.add('danger');
                        progressFill.classList.remove('warning');
                    } else if (timerElement.classList.contains('warning')) {
                        progressFill.classList.add('warning');
                        progressFill.classList.remove('danger');
                    } else {
                        progressFill.classList.remove('warning', 'danger');
                    }
                }
            }

            if (progressText) {
                progressText.textContent = `${completed} of ${total} questions completed`;
            }
        }

        // Process answer result
        function processAnswerResult(questionNumber, questionId, data, checkBtn) {
            const resultKey = `result_processed_${questionNumber}`;
            if (processAnswerResult[resultKey]) {
                console.log(`🔇 BULLETPROOF: Result already processed for Q${questionNumber}, skipping duplicate`);
                return;
            }
            processAnswerResult[resultKey] = true;

            updateSidebarCircleState(questionNumber, data.is_correct);

            if (totalAnswers === totalQuestions) {
                console.log('🔥 LAST QUESTION ANSWERED: Showing View Results button instead of Next Question');

                updateQuestionUI(questionNumber, {
                    correct: data.is_correct,
                    explanation: data.explanation,
                    correctAnswer: data.correct_answer,
                    showNextButton: false,
                    showViewResultsButton: true
                });

                return;
            }

            console.log('🎓 TUTOR MODE: Showing explanation for Q${questionNumber}, total answered: ${totalAnswers}/${totalQuestions}');

            updateQuestionUI(questionNumber, {
                correct: data.is_correct,
                explanation: data.explanation,
                correctAnswer: data.correct_answer,
                showNextButton: true
            });
        }

        // Update question UI after checking answer
        function updateQuestionUI(questionNumber, data) {
            const questionCard = document.getElementById(`question-${questionNumber}`);
            const feedbackDiv = questionCard.querySelector('.answer-feedback');
            const optionsContainer = questionCard.querySelector('.options-container');

            const soundKey = `sound_played_${questionNumber}`;
            const hasPlayedSound = questionCard.dataset[soundKey];

            feedbackDiv.style.display = 'block';
            const alertDiv = feedbackDiv.querySelector('.alert');
            alertDiv.className = `alert ${data.correct ? 'alert-success' : 'alert-danger'}`;

            feedbackDiv.querySelector('.result-text').textContent = data.correct ? 'Correct! 🎉' : 'Incorrect';

            if (totalAnswers < totalQuestions && !hasPlayedSound) {
                console.log(`🔥 LETHAL AUDIO POOL: Playing instant sound for Q${questionNumber} (${data.correct ? 'correct' : 'wrong'}) - FIRST TIME ONLY`);
                playInstantSound(data.correct);
                questionCard.dataset[soundKey] = 'true';
            } else if (hasPlayedSound) {
                console.log(`🔇 LETHAL AUDIO POOL: Sound already played for Q${questionNumber}, skipping duplicate`);
            } else {
                console.log(`🔇 LETHAL AUDIO POOL: Skipping sound for final question Q${questionNumber} to prevent overlap with final grading sound`);
            }

            let explanationHtml = data.explanation || '';
            let correctAnswerHeader = '';
            if (!data.correct && data.correctAnswer) {
                correctAnswerHeader = `
                    <div class="answer-header">
                        <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                        <strong>Correct Answer: ${data.correctAnswer}</strong>
                    </div>`;
            }
            // Add doctor icon wrapper
            const explanationWithIcon = `
                <div class="explanation-with-icon">
                    ${correctAnswerHeader}
                    <div class="explanation-text">
                        ${explanationHtml}
                    </div>
                </div>
            `;
            feedbackDiv.querySelector('.explanation-content').innerHTML = explanationWithIcon;

            const selectedOption = questionCard.querySelector('.option-card.selected');
            const writtenAnswer = questionCard.querySelector('.written-answer');

            if (writtenAnswer) {
                writtenAnswer.disabled = true;
                writtenAnswer.classList.add(data.correct ? 'correct' : 'incorrect');
            } else if (selectedOption) {
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

            const checkAnswerBtn = questionCard.querySelector('.check-answer');
            const nextQuestionBtn = questionCard.querySelector('.next-question');
            const viewResultsBtn = questionCard.querySelector('.view-results');

            if (checkAnswerBtn) checkAnswerBtn.style.display = 'none';

            if (data.showViewResultsButton) {
                if (viewResultsBtn) {
                    viewResultsBtn.style.display = 'block';
                    viewResultsBtn.disabled = false;
                    if (!viewResultsBtn) {
                        const actionsDiv = questionCard.querySelector('.actions');
                        const newViewResultsBtn = document.createElement('button');
                        newViewResultsBtn.className = 'btn btn-success view-results';
                        newViewResultsBtn.onclick = () => {
                            const percentage = (correctAnswers / totalAnswers) * 100;
                            showQuizResults({
                                correct: correctAnswers,
                                total: totalAnswers,
                                percentage: percentage
                            });
                        };
                        newViewResultsBtn.textContent = 'View Results';
                        actionsDiv.appendChild(newViewResultsBtn);
                    }
                }
            } else if (nextQuestionBtn) {
                nextQuestionBtn.style.display = 'block';
                nextQuestionBtn.disabled = false;
            }

            updateProgress(totalAnswers, totalQuestions);
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
                const percentage = (correctAnswers / totalAnswers) * 100;
                showQuizResults({
                    correct: correctAnswers,
                    total: totalAnswers,
                    percentage: percentage
                });
            }
        }

        // Function to show final results when "View Results" button is clicked
        function showFinalResults() {
            console.log('🎯 VIEW RESULTS: User clicked "View Results" button for final question');
            const percentage = (correctAnswers / totalAnswers) * 100;

            showQuizResults({
                correct: correctAnswers,
                total: totalAnswers,
                percentage: percentage
            });
        }

        // INSTANT RESULTS: Show popup immediately
        function showQuizResults(results) {
            console.log('🚀 INSTANT RESULTS: Starting immediate quiz completion process', results);
            const completionStart = performance.now();

            const popupOverlay = document.getElementById('popup-overlay');
            const popup = document.getElementById('popup');
            const gradeIcon = document.getElementById('grade-icon');
            const popupTitle = document.getElementById('popup-title');
            const popupScore = document.getElementById('popup-score');
            const popupMessage = document.getElementById('popup-message');

            popupScore.textContent = `${results.correct}/${results.total}`;

            const percentage = (results.correct / results.total) * 100;
            if (percentage === 100) {
                gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                gradeIcon.className = 'grade-icon perfect';
                popupTitle.textContent = 'Perfect Score!';
                popupMessage.textContent = 'Outstanding! You\'ve mastered this topic!';

                if (typeof confetti !== 'undefined') {
                    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                    const originX = isSafari ? 0.48 : 0.62;

                    setTimeout(() => {
                        confetti({
                            particleCount: 150,
                            spread: 70,
                            origin: { x: originX, y: 0.45 },
                            colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                            zIndex: 10000
                        });
                    }, 300);
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

            popupOverlay.classList.add('show');
            setTimeout(() => popup.classList.add('show'), 100);

            const uiTime = performance.now() - completionStart;
            console.log(`⚡ INSTANT UI: Popup shown in ${(uiTime).toFixed(2)}ms - COMPLETING FUNCTION NOW`);

            const isRandomQuiz = {{ isset($is_random_quiz) && $is_random_quiz ? 'true' : 'false' }};

            if (!isRandomQuiz) {
                recordTutorQuizAttempt(results);
            } else {
                console.log('🎓 TUTOR MODE: Skipping quiz attempt recording for learning mode');
            }

            playFinalGradingSound(results.correct, results.total);

            console.log(`🎉 INSTANT RESULTS: Function completed in ${(performance.now() - completionStart).toFixed(2)}ms - background operations running independently`);

            return;
        }

        // 💀 LETHAL AUDIO POOL - INSTANT SOUND PLAYBACK 💀
        function playInstantSound(isCorrect) {
            const startTime = performance.now();
            const soundType = isCorrect ? 'correct' : 'wrong';

            if (playInstantSound.isPlaying) {
                console.log(`🔇 LETHAL AUDIO POOL: Sound already playing, skipping duplicate ${soundType} sound`);
                return;
            }

            console.log(`🔥 LETHAL AUDIO POOL: Attempting 0ms playback for ${soundType} sound`);

            let audio = null;
            if (audioPool[soundType] && audioPool[soundType].length > 0) {
                audio = audioPool[soundType].shift();
                console.log(`🔥 LETHAL AUDIO POOL: Got ${soundType} audio from pool (${audioPool[soundType].length} remaining)`);
            } else {
                console.log(`🔥 LETHAL AUDIO POOL: Pool empty, creating fresh ${soundType} audio`);
                createAudioElement(soundType);
                audio = audioPool[soundType].shift();
            }

            if (!audio) {
                console.log(`ℹ️ LETHAL AUDIO POOL: No audio available for ${soundType}, continuing silently`);
                return;
            }

            try {
                audio.currentTime = 0;
                audio.volume = 0.7;

                const endedHandler = () => {
                    audio.removeEventListener('ended', endedHandler);
                    playInstantSound.isPlaying = false;
                    setTimeout(() => {
                        returnToPool(audio, soundType);
                    }, 100);
                };
                audio.addEventListener('ended', endedHandler);

                playInstantSound.isPlaying = true;

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        const playbackTime = performance.now() - startTime;
                        console.log(`✅ LETHAL AUDIO POOL: ${soundType} sound started in ${playbackTime.toFixed(2)}ms - letting it play fully`);

                    }).catch(error => {
                        console.error(`❌ LETHAL AUDIO POOL: ${soundType} playback failed`, error);
                        audio.removeEventListener('ended', endedHandler);
                        returnToPool(audio, soundType);
                        playInstantSound.isPlaying = false;
                    });
                }
            } catch (error) {
                console.error(`❌ LETHAL AUDIO POOL: ${soundType} audio error`, error);
                returnToPool(audio, soundType);
                playInstantSound.isPlaying = false;
            }
        }


        // RECORD TUTOR QUIZ ATTEMPT - FIRE AND FORGET
        function recordTutorQuizAttempt(results) {
            const funcStart = performance.now();
            console.log('📊 RECORDING TUTOR QUIZ ATTEMPT - FIRE AND FORGET');
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const quizData = {
                    results: {
                        correct: results.correct,
                        total: results.total,
                        percentage: Math.round((results.correct / results.total) * 100)
                    },
                    user_answers: userAnswers || {},
                    answered_questions: results.total,
                    duration_seconds: Math.floor((new Date() - quizStartTime) / 1000),
                    topic_id: {{ $topic->id ?? 1 }},
                    course_id: {{ $course->id ?? 1 }}
                };

                // Fire and forget - don't await this
                fetch('/quiz/tutor/complete-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(quizData)
                }).then(response => {
                    if (response.ok) {
                        console.log(`✅ QUIZ ATTEMPT RECORDED in ${(performance.now() - funcStart).toFixed(2)}ms`);
                    } else {
                        console.warn(`⚠️ QUIZ ATTEMPT RECORDING FAILED: ${response.status}`);
                    }
                }).catch(error => {
                    console.warn(`⚠️ QUIZ ATTEMPT RECORDING ERROR:`, error);
                });

                console.log(`📊 RECORDING INITIATED in ${(performance.now() - funcStart).toFixed(2)}ms - running in background`);
            } catch (error) {
                console.warn('⚠️ ERROR INITIATING QUIZ ATTEMPT RECORDING:', error);
            }
        }

        // 💀 ULTRA-MEGA INSTANT GRADING SOUND - AUDIO POOL POWERED 💀
        function playFinalGradingSound(score, total) {
            const funcStart = performance.now();
            console.log('🔥 PLAY FINAL GRADING SOUND - AUDIO POOL EDITION');

            const percentage = (score / total) * 100;

            // 💀 AUDIO POOL INSTANT SELECTION - NO DOWNLOAD DELAY 💀
            let soundType = 'partial'; // Default to partial
            if (percentage === 100) {
                soundType = 'perfect';
            } else if (percentage < 70) {
                soundType = 'wrong';
            }

            console.log(`🎵 PLAYING INSTANT ${soundType.toUpperCase()} SOUND`);

            // 💀 INSTANT AUDIO POOL ACCESS - ZERO DELAY 💀
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
                    setTimeout(() => {
                        returnToPool(audio, soundType);
                    }, 100); // Small delay to ensure full playback
                };
                audio.addEventListener('ended', endedHandler);

                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        const playbackTime = performance.now() - funcStart;
                        console.log(`✅ LETHAL AUDIO POOL: ${soundType} sound started in ${playbackTime.toFixed(2)}ms - letting it play fully`);
                    }).catch(error => {
                        console.error(`❌ LETHAL AUDIO POOL: ${soundType} playback failed`, error);
                        audio.removeEventListener('ended', endedHandler);
                        returnToPool(audio, soundType);
                    });
                }
            } catch (error) {
                console.error(`❌ LETHAL AUDIO POOL: ${soundType} audio error`, error);
                returnToPool(audio, soundType);
            }
        }

        // Timer functionality - DIFFERENT MODES FOR RANDOM VS NORMAL TUTOR
        let isRandomQuiz = {{ isset($is_random_quiz) && $is_random_quiz ? 'true' : 'false' }};
        let initialDuration = {{ isset($duration_minutes) ? $duration_minutes * 60 : 0 }}; // Only for random quiz
        let timeLeft = isRandomQuiz ? initialDuration : 0; // Start at 0 for normal tutor, duration for random
        let warningThreshold = Math.floor(initialDuration * 0.3); // Warning at 30% time remaining (only for random)
        let dangerThreshold = Math.floor(initialDuration * 0.1); // Danger at 10% time remaining (only for random)

        function updateTimer() {
            console.log('🕒 Timer tick - updating display');
            const currentTime = new Date();
            const elapsedSeconds = Math.floor((currentTime - quizStartTime) / 1000); // Ensure integer

            if (initialDuration > 0) {
                // Use countdown timer
                timeLeft = Math.max(0, initialDuration - elapsedSeconds);
            } else {
                // Use elapsed timer
                timeLeft = elapsedSeconds;
            }

            // Ensure timeLeft is always an integer
            timeLeft = Math.floor(timeLeft);

            const minutes = Math.floor(Math.abs(timeLeft) / 60);
            const seconds = Math.floor(Math.abs(timeLeft) % 60); // Ensure seconds is integer
            const timerElement = document.getElementById('timer');
            console.log('🕒 Timer element found:', !!timerElement);

            if (timerElement) {
                let timeDisplay = '';
                if (initialDuration > 0) {
                    // Countdown format - always show integers
                    timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                } else {
                    // Elapsed format - always show integers
                    timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }

                timerElement.textContent = timeDisplay;

                // Apply color grading based on time remaining
                updateTimerStyling(timerElement, timeLeft);
            }

            // Auto-submit when time runs out (only for countdown mode)
            if (initialDuration > 0 && timeLeft <= 0) {
                clearInterval(timerInterval);
                console.log('⏰ TIME RAN OUT - AUTO-GRADING RANDOM QUIZ...');
                submitQuizRandomTutor();
                return;
            }

            timerInterval = setTimeout(updateTimer, 1000);
        }

        function updateTimerStyling(timerElement, remainingTime) {
            // Remove existing classes
            timerElement.classList.remove('warning', 'danger');

            if (initialDuration <= 0) return; // No styling for elapsed timer

            const percentageRemaining = (remainingTime / initialDuration) * 100;

            if (percentageRemaining <= 10) {
                // Danger: Red styling with animation
                timerElement.classList.add('danger');
            } else if (percentageRemaining <= 30) {
                // Warning: Yellow styling
                timerElement.classList.add('warning');
            }
        }

        // LETHAL AUDIO POOL DEBUG FUNCTIONS
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
            setTimeout(() => playInstantSound(true), 100);
            setTimeout(() => playInstantSound(false), 500);
            setTimeout(() => playInstantSound(true), 1000);
        };

        // Helper functions to be fully available
        function initializeAudioPools() {
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

        function setupAudioElement(audio) {
            audio.volume = 0.7;
            audio.preload = 'auto';

            audio.addEventListener('ended', function() {
                setTimeout(() => {
                    returnToPool(audio);
                }, 200);
            });

            audio.addEventListener('error', function() {
                console.log('ℹ️ Audio element error, returning to pool');
                returnToPool(audio);
            });
        }

        function startPoolReplenishment() {
            poolReplenishmentInterval = setInterval(() => {
                replenishPools();
            }, 5000);

            setTimeout(() => {
                replenishPools();
            }, 1000);
        }

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
        }

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

            audioPool[type].push(audio);

            audio.addEventListener('ended', function() {
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

        function returnToPool(audio, type = null) {
            if (!type) {
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
                audio.currentTime = 0;
                audio.pause();

                try {
                    audio.stop && audio.stop();
                } catch (e) {
                    // Ignore errors if stop method doesn't exist
                }

                if (!audioPool[type].includes(audio)) {
                    audioPool[type].push(audio);
                    console.log(`🔥 LETHAL AUDIO POOL: Returned ${type} audio to pool (${audioPool[type].length} available)`);
                }
            }
        }

        function removeFromPool(audio, type) {
            if (audioPool[type]) {
                const index = audioPool[type].indexOf(audio);
                if (index > -1) {
                    audioPool[type].splice(index, 1);
                }
            }
        }

        // Data and initialization
        // PRELOAD ALL QUESTION DATA ON PAGE LOAD
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔥 PRELOADING QUESTION DATA FOR INSTANT CHECKING...');

            // Extract question data from DOM
            @foreach($questions as $index => $question)
                @php $qObj = is_array($question) ? $question['question'] : $question; @endphp
                questionData[{{ $qObj->id ?? 0 }}] = {
                    correct_answer: '{{ $qObj->correct_answer ?? '' }}',
                    explanation: `{{ addslashes($qObj->explanation ?? '') }}`,
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

            // Initialize GradingAudioController FIRST (for individual question sounds)
            if (typeof GradingAudioController !== 'undefined' && GradingAudioController.init) {
                GradingAudioController.init();
                console.log('✅ GradingAudioController initialized for individual question sounds');
            } else {
                console.warn('⚠️ GradingAudioController not available, falling back to old system');
            }

            // Initialize audio system (legacy system for final grading sound)
            initializeAudioPools();
            startPoolReplenishment();

            // Event delegation for option selection
            document.addEventListener('click', function(e) {
                if (e.target.closest('.option-card')) {
                    selectOption(e.target.closest('.option-card'));
                }
            });

            console.log('✅ LETHAL AUDIO POOL: System ready for 0ms instant playback');

            // Initialize highlighting system
            initializeHighlighting();
        });

        // ===== TEXT HIGHLIGHTING SYSTEM =====
        function initializeHighlighting() {
            highlightToolbar = document.getElementById('highlight-toolbar');
            console.log('🎨 Highlighting system initialized');

            // Load saved highlights from localStorage
            const savedHighlights = localStorage.getItem('tutorModeHighlights');
            if (savedHighlights) {
                try {
                    highlights = JSON.parse(savedHighlights);
                    restoreHighlights();
                    console.log('📚 Loaded saved highlights:', highlights);
                } catch (e) {
                    console.error('Failed to load highlights:', e);
                }
            }

            // Handle text selection - add to question cards specifically
            document.addEventListener('mouseup', function(e) {
                console.log('🖱️ Mouse up detected');
                handleTextSelection(e);
            });
            document.addEventListener('touchend', function(e) {
                console.log('👆 Touch end detected');
                handleTextSelection(e);
            });

            // Hide toolbar when clicking outside
            document.addEventListener('mousedown', function(e) {
                if (!highlightToolbar.contains(e.target) && !e.target.closest('.question-text')) {
                    console.log('📋 Clicking outside toolbar - hiding');
                    hideToolbar();
                }
            });

            window.addEventListener('resize', function() {
                if (highlightToolbar && highlightToolbar.classList.contains('show') && currentSelection?.questionElement && currentSelection?.range) {
                    positionToolbar(currentSelection.questionElement, currentSelection.range);
                }
            });

            window.addEventListener('scroll', function() {
                if (highlightToolbar && highlightToolbar.classList.contains('show') && currentSelection?.questionElement && currentSelection?.range) {
                    positionToolbar(currentSelection.questionElement, currentSelection.range);
                }
            }, true);
        }

        function handleTextSelection(e) {
            console.log('📝 HANDLING TEXT SELECTION EVENT');

            // Allow selection anywhere in document for now - we'll filter later
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();

            console.log('📝 Selected text length:', selectedText.length);

            if (selectedText.length === 0) {
                console.log('📝 No text selected, hiding toolbar');
                hideToolbar();
                return;
            }

            // Find the question text element containing the selection
            const startElement = selection.getRangeAt(0).startContainer;
            const questionElement = startElement.nodeType === Node.TEXT_NODE ?
                startElement.parentElement.closest('.question-text') :
                startElement.closest('.question-text');

            console.log('📝 Found question element:', !!questionElement);

            if (!questionElement) {
                console.log('📝 Selection not within question text, hiding toolbar');
                hideToolbar();
                return;
            }

            // Store the complete selection details
            currentSelection = {
                text: selectedText,
                range: selection.getRangeAt(0),
                questionElement: questionElement,
                startContainer: selection.getRangeAt(0).startContainer,
                endContainer: selection.getRangeAt(0).endContainer,
                startOffset: selection.getRangeAt(0).startOffset,
                endOffset: selection.getRangeAt(0).endOffset
            };

            console.log('📝 Stored selection details:', currentSelection);

            // Position and show toolbar
            showToolbar();
        }

        function positionToolbar(questionElement, range) {
            if (!highlightToolbar || !questionElement || !range) return;

            const bodyZoom = parseFloat(window.getComputedStyle(document.body).zoom) || 1;
            const questionRect = questionElement.getBoundingClientRect();
            const selectionRect = range.getBoundingClientRect();
            const selectionRects = Array.from(range.getClientRects()).filter(function(rect) {
                return rect.width > 0 || rect.height > 0;
            });
            const anchorRect = selectionRects.length > 0
                ? selectionRects[Math.floor(selectionRects.length / 2)]
                : selectionRect;
            const viewportPadding = 12;
            const questionPadding = 12;
            const toolbarGap = 4;

            highlightToolbar.classList.add('show');
            highlightToolbar.dataset.placement = 'top';

            const toolbarRect = highlightToolbar.getBoundingClientRect();
            const toolbarWidth = toolbarRect.width || (highlightToolbar.offsetWidth * bodyZoom) || 250;
            const toolbarHeight = toolbarRect.height || (highlightToolbar.offsetHeight * bodyZoom) || 52;

            let left = anchorRect.left + (anchorRect.width / 2) - (toolbarWidth / 2);
            let top = selectionRect.top - toolbarHeight - toolbarGap;
            let placement = 'top';

            if (top < viewportPadding) {
                top = selectionRect.bottom + toolbarGap;
                placement = 'bottom';
            }

            const minViewportLeft = viewportPadding;
            const maxViewportLeft = window.innerWidth - toolbarWidth - viewportPadding;
            const minQuestionLeft = questionRect.left + questionPadding;
            const maxQuestionLeft = questionRect.right - toolbarWidth - questionPadding;

            if (minQuestionLeft <= maxQuestionLeft) {
                left = Math.max(minQuestionLeft, Math.min(left, maxQuestionLeft));
            }

            left = Math.max(minViewportLeft, Math.min(left, maxViewportLeft));
            top = Math.max(viewportPadding, Math.min(top, window.innerHeight - toolbarHeight - viewportPadding));

            highlightToolbar.dataset.placement = placement;
            highlightToolbar.style.left = (left / bodyZoom) + 'px';
            highlightToolbar.style.top = (top / bodyZoom) + 'px';
        }

        function showToolbar() {
            const questionElement = currentSelection?.questionElement;
            const range = currentSelection?.range;
            if (!questionElement || !range) return;

            positionToolbar(questionElement, range);
            highlightToolbar.classList.add('show');
        }

        function hideToolbar() {
            if (highlightToolbar) {
                highlightToolbar.classList.remove('show');
                highlightToolbar.dataset.placement = 'top';
            }
            currentSelection = null;
        }

        window.applyHighlight = function(color) {
            if (!currentSelection || !currentSelection.range) {
                console.error('No stored selection available for highlighting');
                hideToolbar();
                return;
            }

            const questionElement = currentSelection.questionElement;
            const questionId = getQuestionId(questionElement);

            if (!questionElement) {
                console.error('No question element found');
                hideToolbar();
                return;
            }

            const range = document.createRange();
            try {
                range.setStart(currentSelection.range.startContainer, currentSelection.range.startOffset);
                range.setEnd(currentSelection.range.endContainer, currentSelection.range.endOffset);
            } catch (e) {
                console.error('Failed to recreate range:', e);
                hideToolbar();
                return;
            }

            const selectionOffsets = getRangeOffsets(questionElement, range);
            if (selectionOffsets.end <= selectionOffsets.start) {
                hideToolbar();
                return;
            }

            try {
                const existingIntervals = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
                const updatedIntervals = applyHighlightInterval(existingIntervals, selectionOffsets.start, selectionOffsets.end, color);

                renderQuestionHighlights(questionElement, updatedIntervals);
                persistQuestionHighlights(questionId, questionElement, updatedIntervals);
                console.log(`✅ SUCCESS: Applied ${color} highlight to "${currentSelection.text}"`);
                hideToolbar();
                window.getSelection().removeAllRanges();
            } catch (e) {
                console.error('Failed to apply highlight:', e);
                hideToolbar();
            }
        };

        window.removeHighlight = function() {
            if (!currentSelection || !currentSelection.range) return;

            const questionElement = currentSelection.questionElement;
            const questionId = getQuestionId(questionElement);
            const range = document.createRange();

            try {
                range.setStart(currentSelection.range.startContainer, currentSelection.range.startOffset);
                range.setEnd(currentSelection.range.endContainer, currentSelection.range.endOffset);
            } catch (e) {
                console.error('Failed to recreate range for highlight removal:', e);
                hideToolbar();
                return;
            }

            const selectionOffsets = getRangeOffsets(questionElement, range);
            if (selectionOffsets.end <= selectionOffsets.start) {
                hideToolbar();
                return;
            }

            const existingIntervals = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
            const updatedIntervals = removeHighlightInterval(existingIntervals, selectionOffsets.start, selectionOffsets.end);

            renderQuestionHighlights(questionElement, updatedIntervals);
            persistQuestionHighlights(questionId, questionElement, updatedIntervals);
            hideToolbar();
            window.getSelection().removeAllRanges();
        };

        function persistQuestionHighlights(questionId, questionElement, intervals) {
            const serializedHighlights = serializeHighlightIntervals(questionElement, intervals);

            if (serializedHighlights.length > 0) {
                highlights[questionId] = serializedHighlights;
            } else {
                delete highlights[questionId];
            }

            localStorage.setItem('tutorModeHighlights', JSON.stringify(highlights));
        }

        function restoreHighlights() {
            Object.keys(highlights).forEach(questionId => {
                const questionElement = document.querySelector(`[data-question-id="${questionId}"] .question-text`);
                if (!questionElement) return;

                const normalizedHighlights = normalizeHighlightEntries(questionElement, highlights[questionId] || []);
                renderQuestionHighlights(questionElement, normalizedHighlights);
                persistQuestionHighlights(questionId, questionElement, normalizedHighlights);
            });
        }

        function getQuestionId(questionElement) {
            const questionCard = questionElement.closest('.question-card');
            return questionCard ? questionCard.id : null;
        }

        function applyHighlightInterval(intervals, start, end, color) {
            if (end <= start) return mergeHighlightIntervals(intervals);

            const updatedIntervals = [];

            intervals.forEach(interval => {
                if (interval.end <= start || interval.start >= end) {
                    updatedIntervals.push({ ...interval });
                    return;
                }

                if (interval.start < start) {
                    updatedIntervals.push({
                        start: interval.start,
                        end: start,
                        color: interval.color
                    });
                }

                if (interval.end > end) {
                    updatedIntervals.push({
                        start: end,
                        end: interval.end,
                        color: interval.color
                    });
                }
            });

            updatedIntervals.push({ start, end, color });
            return mergeHighlightIntervals(updatedIntervals);
        }

        function removeHighlightInterval(intervals, start, end) {
            if (end <= start) return mergeHighlightIntervals(intervals);

            const updatedIntervals = [];

            intervals.forEach(interval => {
                if (interval.end <= start || interval.start >= end) {
                    updatedIntervals.push({ ...interval });
                    return;
                }

                if (interval.start < start) {
                    updatedIntervals.push({
                        start: interval.start,
                        end: start,
                        color: interval.color
                    });
                }

                if (interval.end > end) {
                    updatedIntervals.push({
                        start: end,
                        end: interval.end,
                        color: interval.color
                    });
                }
            });

            return mergeHighlightIntervals(updatedIntervals);
        }

        function normalizeHighlightEntries(questionElement, entries) {
            const textLength = questionElement.textContent.length;
            let normalizedIntervals = [];

            (entries || []).forEach(entry => {
                const color = entry?.color;
                const start = Number(entry?.offset?.start ?? 0);
                let end = Number(entry?.offset?.end ?? start);

                if ((!Number.isFinite(end) || end <= start) && typeof entry?.text === 'string') {
                    end = start + entry.text.length;
                }

                if (!color || !Number.isFinite(start) || !Number.isFinite(end)) {
                    return;
                }

                const clampedStart = Math.max(0, Math.min(start, textLength));
                const clampedEnd = Math.max(clampedStart, Math.min(end, textLength));
                normalizedIntervals = applyHighlightInterval(normalizedIntervals, clampedStart, clampedEnd, color);
            });

            return mergeHighlightIntervals(normalizedIntervals);
        }

        function mergeHighlightIntervals(intervals) {
            return intervals
                .filter(interval => interval && interval.color && interval.end > interval.start)
                .sort((a, b) => a.start - b.start || a.end - b.end)
                .reduce((mergedIntervals, interval) => {
                    const previousInterval = mergedIntervals[mergedIntervals.length - 1];

                    if (previousInterval && previousInterval.color === interval.color && interval.start <= previousInterval.end) {
                        previousInterval.end = Math.max(previousInterval.end, interval.end);
                    } else {
                        mergedIntervals.push({ ...interval });
                    }

                    return mergedIntervals;
                }, []);
        }

        function renderQuestionHighlights(questionElement, intervals) {
            const fullText = questionElement.textContent;
            const normalizedIntervals = mergeHighlightIntervals(intervals);
            const fragment = document.createDocumentFragment();
            let cursor = 0;

            normalizedIntervals.forEach(interval => {
                if (interval.start > cursor) {
                    fragment.appendChild(document.createTextNode(fullText.slice(cursor, interval.start)));
                }

                const mark = document.createElement('mark');
                mark.className = `highlight-${interval.color}`;
                mark.textContent = fullText.slice(interval.start, interval.end);
                fragment.appendChild(mark);

                cursor = interval.end;
            });

            if (cursor < fullText.length) {
                fragment.appendChild(document.createTextNode(fullText.slice(cursor)));
            }

            questionElement.textContent = '';
            questionElement.appendChild(fragment);
        }

        function serializeHighlightIntervals(questionElement, intervals) {
            const fullText = questionElement.textContent;

            return mergeHighlightIntervals(intervals).map(interval => ({
                text: fullText.slice(interval.start, interval.end),
                color: interval.color,
                offset: {
                    start: interval.start,
                    end: interval.end
                }
            }));
        }

        function getRangeOffsets(container, range) {
            return {
                start: getAbsoluteTextOffset(container, range.startContainer, range.startOffset),
                end: getAbsoluteTextOffset(container, range.endContainer, range.endOffset)
            };
        }

        function getAbsoluteTextOffset(container, node, offset) {
            let absoluteOffset = 0;
            const walker = document.createTreeWalker(
                container,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            let currentNode;
            while (currentNode = walker.nextNode()) {
                if (currentNode === node) {
                    return absoluteOffset + offset;
                }

                absoluteOffset += currentNode.textContent.length;
            }

            return absoluteOffset;
        }

    </script>

    <script>
        // NOTE: Core tutor-mode globals like questionData, userAnswers, correctAnswers,
        // totalAnswers, totalQuestions and currentQuestionIndex are declared in the
        // earlier script block to avoid duplicate variable declarations.
        // This block only defines helper functions that use those globals.
        
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

        // Track first attempt answers and question states

        // Jump to specific question from sidebar
        window.jumpToQuestion = function(questionNumber) {
            console.log(`🎯 Jumping to question ${questionNumber}`);

            document.querySelectorAll('.question-card').forEach(card => {
                card.style.display = 'none';
            });

            const targetQuestion = document.getElementById(`question-${questionNumber}`);
            if (targetQuestion) {
                targetQuestion.style.display = 'block';
                currentQuestionIndex = questionNumber;
                updateSidebarActiveState(questionNumber);

                // Check if all questions are answered and show View Results button if needed
                if (totalAnswers === {{ $questions->count() }}) {
                    console.log('🔥 ALL QUESTIONS ANSWERED: Ensuring View Results button is visible');
                    const viewResultsBtn = targetQuestion.querySelector('.view-results');
                    if (viewResultsBtn) {
                        viewResultsBtn.style.display = 'inline-block';
                        viewResultsBtn.disabled = false;
                    }
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };

        // Navigate to previous question
        window.navigatePrevious = function(currentNumber) {
            if (currentNumber > 1) {
                jumpToQuestion(currentNumber - 1);
            }
        };

        // Navigate to next question
        window.navigateNext = function(currentNumber) {
            if (currentNumber < totalQuestions) {
                jumpToQuestion(currentNumber + 1);
            }
        };

        // Update sidebar active state
        function updateSidebarActiveState(questionNumber) {
            document.querySelectorAll('.question-circle').forEach(circle => {
                circle.classList.remove('active');
                if (parseInt(circle.dataset.question) === questionNumber) {
                    circle.classList.add('active');
                }
            });
        }

        // Update sidebar circle state (correct/incorrect)
        function updateSidebarCircleState(questionNumber, isCorrect) {
            const circle = document.querySelector(`.question-circle[data-question="${questionNumber}"]`);
            if (circle) {
                circle.classList.remove('answered');
                if (isCorrect) {
                    circle.classList.add('correct');
                    circle.classList.remove('incorrect');
                } else {
                    circle.classList.add('incorrect');
                    circle.classList.remove('correct');
                }
            }
        }

        // Handle "I Don't Know" button
        window.handleDontKnow = function(questionNumber, questionId) {
            console.log(`❓ I Don't Know clicked for Q${questionNumber}`);

            if (questionStates[questionNumber]) {
                console.log(`⚠️ Question ${questionNumber} already answered, ignoring`);
                return;
            }

            questionStates[questionNumber] = true;
            firstAttemptAnswers[questionId] = 'Dont know';

            const qData = questionData[questionId];
            if (!qData) {
                console.error(`❌ Question data not found for ID ${questionId}`);
                return;
            }

            totalAnswers++;
            userAnswers[questionId] = 'dont_know';

            updateSidebarCircleState(questionNumber, false);

            const questionCard = document.getElementById(`question-${questionNumber}`);
            if (questionCard) {
                const feedbackDiv = questionCard.querySelector('.answer-feedback');
                const alertDiv = feedbackDiv.querySelector('.alert');

                feedbackDiv.style.display = 'block';
                alertDiv.className = 'alert alert-danger';
                feedbackDiv.querySelector('.result-text').textContent = "Don't worry! Here's the answer:";
                feedbackDiv.querySelector('.explanation-content').innerHTML = `
                    <div class="explanation-with-icon">
                        <div class="answer-header">
                            <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                            <strong>Correct Answer: ${qData.correct_answer}</strong>
                        </div>
                        <div class="explanation-text">
                            ${qData.explanation || 'No explanation available.'}
                        </div>
                    </div>
                `;

                // Highlight the correct answer option in green
                questionCard.querySelectorAll('.option-card').forEach(opt => {
                    const optionLetter = opt.getAttribute('data-option');
                    if (optionLetter === qData.correct_answer) {
                        opt.classList.add('correct');
                    }
                    opt.style.pointerEvents = 'none';
                    opt.style.opacity = '0.6';
                });

                const checkBtn = questionCard.querySelector('.check-answer');
                const dontKnowBtn = questionCard.querySelector('.btn-dont-know');
                if (checkBtn) checkBtn.style.display = 'none';
                if (dontKnowBtn) dontKnowBtn.style.display = 'none';

                // Check if all questions are answered - if so, show View Results button
                if (totalAnswers === {{ $questions->count() }}) {
                    console.log('🔥 ALL QUESTIONS ANSWERED VIA I DONT KNOW: Showing View Results button');
                    const resultsBtn = questionCard.querySelector('.view-results');
                    if (resultsBtn) resultsBtn.style.display = 'inline-block';
                } else {
                    const nextBtn = questionCard.querySelector('.next-question');
                    if (nextBtn) nextBtn.style.display = 'inline-block';
                }
            }

            updateProgress(totalAnswers, {{ $questions->count() }});
            playInstantSound(false);
        };

        
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
        // let currentQuestionIndex = 1; // REMOVED: Already declared above
        // let correctAnswers = 0; // REMOVED: Already declared above
        // let totalAnswers = 0; // REMOVED: Already declared above
        let questionStartTime = new Date(); // Track time for each question
        let totalStudyTime = 0; // Track total study time

        // CRITICAL FIX: Track all user answers throughout the session
        // let userAnswers = {}; // REMOVED: Already declared above

        // AUTO-GRADE RANDOM QUIZ WHEN TIME RUNS OUT (DEFINED FIRST)
        function autoGradeRandomQuiz() {
            console.log('⏰ AUTO-GRADING: Time ran out, grading random quiz with current answers...');

            // Check if we have any answers to grade
            const hasAnswers = Object.keys(userAnswers).length > 0;

            if (!hasAnswers) {
                console.log('ℹ️ No answers provided, showing empty results');
                // Show results with 0 correct answers
                showQuizResults({
                    correct: 0,
                    total: totalQuestions,
                    percentage: 0
                });
                return;
            }

            console.log(`📊 Grading ${Object.keys(userAnswers).length} answers out of ${totalQuestions} questions`);

            // Calculate current score based on answered questions
            let currentCorrect = 0;
            let answeredCount = 0;

            // Count correct answers from answered questions
            for (const [questionId, answer] of Object.entries(userAnswers)) {
                answeredCount++;
                // FIXED: Use questionData instead of undefined 'questions' variable
                const questionDataItem = questionData[questionId];
                if (questionDataItem && answer.toLowerCase().trim() === questionDataItem.correct_answer.toLowerCase().trim()) {
                    currentCorrect++;
                }
            }

            console.log(`✅ Auto-grade results: ${currentCorrect}/${answeredCount} answered questions correct`);

            // Calculate percentage based on answered questions only
            const percentage = answeredCount > 0 ? Math.round((currentCorrect / answeredCount) * 100) : 0;

            // Show results immediately
            showQuizResults({
                correct: currentCorrect,
                total: answeredCount,
                percentage: percentage
            });
        }


        // SUBMIT QUIZ WHEN TIME RUNS OUT - TZAR TECHNIQUE FROM WORKING RANDOM QUIZ
        function submitQuizRandomTutor() {
            console.log('⏰ AUTO-SUBMIT: Time ran out, submitting tutor random quiz immediately');

            // 🚀 FORCE IMMEDIATE SUBMISSION - USE CORRECT ROUTE BASED ON QUIZ TYPE
            const isRandomQuiz = {{ isset($is_random_quiz) && $is_random_quiz ? 'true' : 'false' }};
            const gradeUrl = isRandomQuiz
                ? '/quiz/grade-random'
                : '/quiz/{{ $course->id ?? 1 }}/{{ $topic->id ?? 1 }}/grade';
            console.log('🎯 GRADE URL:', gradeUrl, '(Random Quiz:', isRandomQuiz + ')');
            console.log('🏘️ QUIZ MODE DATA:', {
                course: '{{ $course->id ?? "no-course" }}',
                topic: '{{ $topic->id ?? "no-topic" }}',
                isRandomQuiz: '{{ isset($is_random_quiz) && $is_random_quiz ? "true" : "false" }}'
            });

            // 🔥 DIRECT DATABASE CHECK BEFORE SUBMISSION - TELL US THE TRUTH!
            if (typeof window.checkDatabaseBeforeSubmit === 'undefined') {
                window.checkDatabaseBeforeSubmit = true;
                console.log('🔍 PRE-SUBMIT: Checking database state...');

                // Try to check if we can connect to the endpoint
                fetch(gradeUrl, {
                    method: 'HEAD',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(response => {
                    console.log('🌐 PRE-SUBMIT: Endpoint reachable, status:', response.status);
                }).catch(error => {
                    console.error('❌ PRE-SUBMIT: Cannot reach grade endpoint!', error);
                });
            }

            const formDataObj = {};
            formDataObj.answers = {};
            formDataObj.time_taken = Math.floor((Date.now() - quizStartTime) / 1000); // Elapsed time
            formDataObj.quiz_mode = '{{ isset($is_random_quiz) && $is_random_quiz ? 'random' : 'tutor' }}';

            console.log('⏱️ TIME TAKEN:', formDataObj.time_taken);
            console.log('📊 CURRENT ANSWERS BEFORE COLLECTION:', userAnswers);
            console.log('🔥 CRITICAL: About to submit tutor quiz - ensuring data is captured!');
            console.log('🎯 CSRF TOKEN present:', !!document.querySelector('meta[name="csrf-token"]').content);

            // COLLECT ALL ANSWERS FROM FORM ELEMENTS (exactly like working random quiz)
            document.querySelectorAll('[data-question-id]').forEach(questionBox => {
                const questionId = questionBox.getAttribute('data-question-id');
                // Initialize answer as empty string (unanswered)
                formDataObj.answers[questionId] = '';

                const writtenAnswer = questionBox.querySelector('.written-answer');
                const selectedOption = questionBox.querySelector('.option-card.selected');

                if (writtenAnswer) {
                    formDataObj.answers[questionId] = writtenAnswer.value.trim();
                } else if (selectedOption) {
                    formDataObj.answers[questionId] = selectedOption.getAttribute('data-option');
                }
            });

            console.log('🚀 COLLECTED ANSWERS:', formDataObj.answers);

            fetch(gradeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formDataObj)
            })
            .then(response => {
                console.log('🚀 AUTO-GRADING RESPONSE STATUS:', response.status);

                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.error || 'Auto-grading failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('✅ AUTO-GRADING SUCCESSFUL:', data);

                    // Store quiz results globally for potential review
                    window.lastQuizResults = data;

                    // Get DOM elements
                    const popupScore = document.getElementById('popup-score');
                    const popupMessage = document.getElementById('popup-message');
                    const popupTitle = document.getElementById('popup-title');
                    const popupOverlay = document.getElementById('popup-overlay');
                    const popup = document.getElementById('popup');
                    const gradeIcon = document.getElementById('grade-icon');

                    // Update score display and UI immediately
                    const percentage = (data.score / data.total) * 100;

                    // Set popup elements
                    if (popupScore) popupScore.textContent = `${data.score}/${data.total}`;
                    if (popupMessage) popupMessage.textContent = data.message || 'Quiz completed!';

                    // Set appropriate grade icon and title
                    if (percentage === 100) {
                        if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                        if (gradeIcon) gradeIcon.className = 'grade-icon perfect';
                        if (popupTitle) popupTitle.textContent = 'Perfect Score!';
                    } else if (percentage >= 70) {
                        if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-star"></i>';
                        if (gradeIcon) gradeIcon.className = 'grade-icon good';
                        if (popupTitle) popupTitle.textContent = 'Well Done!';
                    } else {
                        if (gradeIcon) gradeIcon.innerHTML = '<i class="fas fa-book"></i>';
                        if (gradeIcon) gradeIcon.className = 'grade-icon needs-improvement';
                        if (popupTitle) popupTitle.textContent = 'Keep Learning!';
                    }

                    // Play grading sound IMMEDIATELY
                    playFinalGradingSound(data.score, data.total);

                    // Show popup with animation
                    if (popupOverlay) popupOverlay.classList.add('show');
                    if (popup) {
                        setTimeout(() => popup.classList.add('show'), 100);
                    }
                } else {
                    console.error('❌ AUTO-GRADING FAILED:', data.error);

                    // Get DOM elements for error display
                    const popupScore = document.getElementById('popup-score');
                    const popupTitle = document.getElementById('popup-title');
                    const popupMessage = document.getElementById('popup-message');
                    const popupOverlay = document.getElementById('popup-overlay');
                    const popup = document.getElementById('popup');

                    // Show error message
                    if (popupTitle) popupTitle.textContent = 'Error';
                    if (popupScore) popupScore.textContent = '';
                    if (popupMessage) popupMessage.textContent = 'An error occurred while grading. Please try again.';
                    if (popupOverlay) popupOverlay.classList.add('show');
                    if (popup) {
                        setTimeout(() => popup.classList.add('show'), 100);
                    }
                }
            })
            .catch(error => {
                console.error('🚨 AUTO-GRADING NETWORK ERROR:', error);

                // Get DOM elements for error display
                const popupScore = document.getElementById('popup-score');
                const popupTitle = document.getElementById('popup-title');
                const popupMessage = document.getElementById('popup-message');
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');

                // Show error popup
                if (popupTitle) popupTitle.textContent = 'Error';
                if (popupScore) popupScore.textContent = '';
                if (popupMessage) popupMessage.textContent = 'Failed to submit quiz. Please check your connection and try again.';
                if (popupOverlay) popupOverlay.classList.add('show');
                if (popup) {
                    setTimeout(() => popup.classList.add('show'), 100);
                }

                // Re-enable any disabled form elements
                document.querySelectorAll('input, textarea, button').forEach(el => el.disabled = false);
            });
        }

        // Update progress bar with timer color integration
        function updateProgress(completed, total) {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            const timerElement = document.getElementById('timer');
            const percentage = (completed / total) * 100;

            if (progressFill) {
                progressFill.style.width = `${percentage}%`;

                // Sync progress bar color with timer state
                if (timerElement) {
                    if (timerElement.classList.contains('danger')) {
                        progressFill.classList.add('danger');
                        progressFill.classList.remove('warning');
                    } else if (timerElement.classList.contains('warning')) {
                        progressFill.classList.add('warning');
                        progressFill.classList.remove('danger');
                    } else {
                        progressFill.classList.remove('warning', 'danger');
                    }
                }
            }

            if (progressText) {
                progressText.textContent = `${completed} of ${total} questions completed`;
            }
        }



        // 💀 LETHAL CLIENT-SIDE ANSWER CHECKING 💀
        
        // PRELOAD ALL QUESTION DATA ON PAGE LOAD
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔥 PRELOADING QUESTION DATA FOR INSTANT CHECKING...');
            
            // Extract question data from DOM
            @foreach($questions as $index => $question)
                @php $qObj = is_array($question) ? $question['question'] : $question; @endphp
                questionData[{{ $qObj->id ?? 0 }}] = {
                    correct_answer: '{{ $qObj->correct_answer ?? '' }}',
                    explanation: `{{ addslashes($qObj->explanation ?? '') }}`,
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
            
            // Initialize timer after everything is ready
            console.log('🕒 Initializing timer...');
            console.log('🕒 updateTimer function exists:', typeof updateTimer);
            console.log('🕒 quizStartTime:', quizStartTime);
            setTimeout(() => {
                if (typeof updateTimer === 'function') {
                    updateTimer();
                    console.log('✅ Timer started successfully');
                } else {
                    console.error('❌ updateTimer function not found!');
                }
            }, 100);
        });
        


        // Track first attempt answers for each question (already declared above)

        // Jump to specific question from sidebar
        window.jumpToQuestion = function(questionNumber) {
            console.log(`🎯 Jumping to question ${questionNumber}`);
            
            // Hide all questions
            document.querySelectorAll('.question-card').forEach(card => {
                card.style.display = 'none';
            });
            
            // Show target question
            const targetQuestion = document.getElementById(`question-${questionNumber}`);
            if (targetQuestion) {
                targetQuestion.style.display = 'block';
                currentQuestionIndex = questionNumber;
                
                // Update sidebar active state
                updateSidebarActiveState(questionNumber);
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };

        // Navigate to previous question
        window.navigatePrevious = function(currentNumber) {
            if (currentNumber > 1) {
                jumpToQuestion(currentNumber - 1);
            }
        };

        // Navigate to next question
        window.navigateNext = function(currentNumber) {
            if (currentNumber < totalQuestions) {
                jumpToQuestion(currentNumber + 1);
            }
        };

        // Update sidebar active state
        function updateSidebarActiveState(questionNumber) {
            document.querySelectorAll('.question-circle').forEach(circle => {
                circle.classList.remove('active');
                if (parseInt(circle.dataset.question) === questionNumber) {
                    circle.classList.add('active');
                }
            });
        }

        // Update sidebar circle state (correct/incorrect)
        function updateSidebarCircleState(questionNumber, isCorrect) {
            const circle = document.querySelector(`.question-circle[data-question="${questionNumber}"]`);
            if (circle) {
                circle.classList.remove('answered');
                if (isCorrect) {
                    circle.classList.add('correct');
                    circle.classList.remove('incorrect');
                } else {
                    circle.classList.add('incorrect');
                    circle.classList.remove('correct');
                }
            }
        }



        // Log mistake to backend
        function logMistake(questionId, userAnswer, correctAnswer) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            fetch('/mistakes/log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    question_id: questionId,
                    user_answer: userAnswer,
                    correct_answer: correctAnswer
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Mistake logged:', data);
            })
            .catch(error => {
                console.log('ℹ️ Mistake logging issue (non-critical):', error.message);
            });
        }

        // EXTRACTED ANSWER PROCESSING FUNCTION FOR SPEED
        function processAnswerResult(questionNumber, questionId, data, checkBtn) {
            // 💀 BULLETPROOF DUPLICATE PREVENTION - NO MULTIPLE PROCESSING 💀
            const resultKey = `result_processed_${questionNumber}`;
            if (processAnswerResult[resultKey]) {
                console.log(`🔇 BULLETPROOF: Result already processed for Q${questionNumber}, skipping duplicate`);
                return;
            }
            processAnswerResult[resultKey] = true;
            
            // Update sidebar circle state
            updateSidebarCircleState(questionNumber, data.is_correct);

            // 🔥 UPDATE SOURCE PROGRESS IN MODAL IF AVAILABLE
            if (data.source_progress) {
                console.log('🎯 TUTOR MODE: Updating source progress from answer check:', data.source_progress);
                updateSourceProgressDisplay(data.source_progress);
            }

            // 🎯 FINAL RESULTS CHECK - IF THIS IS THE LAST QUESTION, SHOW VIEW RESULTS BUTTON
            if (totalAnswers === totalQuestions) {
                console.log('🔥 LAST QUESTION ANSWERED: Showing View Results button instead of Next Question');

                // 🎯 SHOW EXPLANATION FOR LAST QUESTION, THEN VIEW RESULTS BUTTON
                updateQuestionUI(questionNumber, {
                    correct: data.is_correct,
                    explanation: data.explanation,
                    correctAnswer: data.correct_answer,
                    showNextButton: false, // Don't show next button
                    showViewResultsButton: true // Show "View Results" button instead
                });

                return; // DON'T show grading popup yet, let user read explanation and click View Results
            }

            // 🎯 FOR NON-LAST QUESTIONS - SHOW EXPLANATION THEN NEXT BUTTON AS NORMAL
            console.log('🎓 TUTOR MODE: Showing explanation for Q${questionNumber}, total answered: ${totalAnswers}/${totalQuestions}');

            // FOR NON-LAST QUESTIONS - SHOW NEXT BUTTON AS NORMAL
            updateQuestionUI(questionNumber, {
                correct: data.is_correct,
                explanation: data.explanation,
                correctAnswer: data.correct_answer,
                showNextButton: true // Flag to show next button for non-last questions
            });
        }

        // 🔥 UPDATE SOURCE PROGRESS DISPLAY IN MODAL
        function updateSourceProgressDisplay(sourceProgress) {
            console.log('📊 Updating source progress display:', sourceProgress);
            
            // Find the source progress element in the modal
            const sourceElement = document.querySelector(`[data-source="${sourceProgress.source}"]`);
            if (sourceElement) {
                const percentage = Math.round(sourceProgress.percentage || 0);
                const progressText = `${sourceProgress.correct}/${sourceProgress.total} (${percentage}%)`;
                
                // Update the progress text
                const progressSpan = sourceElement.querySelector('.source-progress');
                if (progressSpan) {
                    progressSpan.textContent = progressText;
                }
                
                // Update progress bar if exists
                const progressBar = sourceElement.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${percentage}%`;
                    progressBar.setAttribute('aria-valuenow', percentage);
                }
                
                console.log(`✅ Updated ${sourceProgress.source} progress to ${progressText}`);
            } else {
                console.warn(`⚠️ Could not find source element for: ${sourceProgress.source}`);
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

            // FIX: Set question state immediately to prevent "I Don't Know" button from being usable
            questionStates[questionNumber] = true;
            
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

                // Show user-friendly warning message
                const questionCard = document.getElementById(`question-${questionNumber}`);
                if (questionCard) {
                    const feedbackDiv = questionCard.querySelector('.answer-feedback');

                    // Hide any existing feedback first
                    feedbackDiv.style.display = 'none';

                    // Clear processing flag to allow retry
                    delete checkAnswer[processingKey];

                    // Show warning after a brief delay
                    setTimeout(() => {
                        feedbackDiv.style.display = 'block';
                        const alertDiv = feedbackDiv.querySelector('.alert');
                        alertDiv.className = 'alert alert-warning';
                        feedbackDiv.querySelector('.result-text').textContent = 'Please select an answer first!';
                        feedbackDiv.querySelector('.explanation-content').innerHTML = `
                            <div class="explanation-with-icon warning-inline">
                                <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                                <div class="explanation-text">You need to choose an answer before checking. Select one of the options above and try again.</div>
                            </div>
                        `;
                    }, 300);
                }

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

                    // Show results immediately (sound will be played in showQuizResults)
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

        // Function to show final results when "View Results" button is clicked
        function showFinalResults() {
            console.log('🎯 VIEW RESULTS: User clicked "View Results" button for final question');
            const percentage = (correctAnswers / totalAnswers) * 100;

            showQuizResults({
                correct: correctAnswers,
                total: totalAnswers,
                percentage: percentage
            });
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
            if (data.correct) {
                feedbackDiv.querySelector('.result-text').textContent = 'Correct! 🎉';
            } else {
                // Show what was selected vs correct answer
                const selectedOption = questionCard.querySelector('.option-card.selected');
                const userAnswer = selectedOption ? selectedOption.getAttribute('data-option') : 'Unknown';
                feedbackDiv.querySelector('.result-text').textContent = `You selected: ${userAnswer} | Correct: ${data.correctAnswer}`;
            }
            
            // 🎯 PLAY IMMEDIATE SOUND FOR ALL INDIVIDUAL ANSWERS USING UNIFIED CONTROLLER
            if (!hasPlayedSound) {
                console.log(`🔥 PLAYING IMMEDIATE SOUND: (${data.correct ? 'CORRECT' : 'WRONG'}) - FIRST TIME ONLY`);
                if (data.correct) {
                    GradingAudioController.playCorrectSound();
                } else {
                    GradingAudioController.playWrongSound();
                }
                questionCard.dataset[soundKey] = 'true';
            } else {
                console.log(`🔇 SOUND ALREADY PLAYED FOR Q${questionNumber}, skipping duplicate`);
            }
            
            // Show explanation and correct answer
            let explanationHtml = data.explanation || '';
            let correctAnswerHeader = '';
            if (data.correctAnswer) {
                // Always show correct answer in green, regardless of whether user got it right or wrong
                correctAnswerHeader = `
                    <div class="answer-header">
                        <img class="doctor-icon" alt="Doctor character" src="/images/doctor.png">
                        <strong>Correct Answer: ${data.correctAnswer}</strong>
                    </div>`;
            }

            // Create aligned result text with doctor icon
            const explanationWithIcon = `
                <div class="explanation-with-icon">
                    ${correctAnswerHeader}
                    <div class="explanation-text">
                        ${explanationHtml}
                    </div>
                </div>
            `;
            feedbackDiv.querySelector('.explanation-content').innerHTML = explanationWithIcon;

            // Hide the separate result text element since we now have it inline
            feedbackDiv.querySelector('.result-text').style.display = 'none';

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
            const viewResultsBtn = questionCard.querySelector('.view-results'); // Add View Results button
            const dontKnowBtn = questionCard.querySelector('.btn-dont-know'); // FIX: Also hide "I Don't Know" button

            if (checkAnswerBtn) checkAnswerBtn.style.display = 'none';
            if (dontKnowBtn) dontKnowBtn.style.display = 'none'; // FIX: Disable "I Don't Know" button after answer is checked

            // Handle final question differently - show "View Results" button
            if (data.showViewResultsButton) {
                if (viewResultsBtn) {
                    viewResultsBtn.style.display = 'block';
                    viewResultsBtn.disabled = false;
                    // Ensure it hasn't been created yet
                    if (!viewResultsBtn) {
                        const actionsDiv = questionCard.querySelector('.actions');
                        const newViewResultsBtn = document.createElement('button');
                        newViewResultsBtn.className = 'btn btn-success view-results';
                        newViewResultsBtn.onclick = () => {
                            const percentage = (correctAnswers / totalAnswers) * 100;
                            showQuizResults({
                                correct: correctAnswers,
                                total: totalAnswers,
                                percentage: percentage
                            });
                        };
                        newViewResultsBtn.textContent = 'View Results';
                        actionsDiv.appendChild(newViewResultsBtn);
                    }
                }
            } else if (nextQuestionBtn) {
                nextQuestionBtn.style.display = 'block';
                nextQuestionBtn.disabled = false; // FIX: Ensure Next Question is enabled
            }

            // FIX: Update progress bar immediately after checking answer
            updateProgress(totalAnswers, totalQuestions);
        }

        // 🎯 INSTANT RESULTS: Show popup immediately, background operations are completely fire-and-forget
        function showQuizResults(results) {
            console.log('🚀 INSTANT RESULTS: Starting immediate quiz completion process', results);
            const completionStart = performance.now();

            // ⚡ INSTANT UI RENDERING - SHOW POPUP IMMEDIATELY
            const popupOverlay = document.getElementById('popup-overlay');
            const popup = document.getElementById('popup');
            const gradeIcon = document.getElementById('grade-icon');
            const popupTitle = document.getElementById('popup-title');
            const popupScore = document.getElementById('popup-score');
            const popupMessage = document.getElementById('popup-message');

            // Show score as X/Y format
            popupScore.textContent = `${results.correct}/${results.total}`;

            // Set appropriate UI based on score
            const percentage = (results.correct / results.total) * 100;
            if (percentage === 100) {
                gradeIcon.innerHTML = '<i class="fas fa-crown"></i>';
                gradeIcon.className = 'grade-icon perfect';
                popupTitle.textContent = 'Perfect Score!';
                popupMessage.textContent = 'Outstanding! You\'ve mastered this topic!';

                // FIREWORKS FOR PERFECT SCORE
                if (typeof confetti !== 'undefined') {
                    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                    const originX = isSafari ? 0.48 : 0.62;

                    setTimeout(() => {
                        confetti({
                            particleCount: 150,
                            spread: 70,
                            origin: { x: originX, y: 0.45 },
                            colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98'],
                            zIndex: 10000
                        });
                    }, 300); // Small delay for effect
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

            // Show success popup with animation IMMEDIATELY
            popupOverlay.classList.add('show');
            setTimeout(() => popup.classList.add('show'), 100);

            // 🎉 FUNCTION COMPLETES INSTANTLY HERE - NO AWAITS OR BLOCKING OPERATIONS
            const uiTime = performance.now() - completionStart;
            console.log(`⚡ INSTANT UI: Popup shown in ${(uiTime).toFixed(2)}ms - COMPLETING FUNCTION NOW`);

            // 🔥 FIRE-AND-FORGET BACKGROUND OPERATIONS - NO AWAITING
            const isRandomQuiz = {{ isset($is_random_quiz) && $is_random_quiz ? 'true' : 'false' }};

            // Background database recording (completely fire-and-forget)
            if (!isRandomQuiz) {
                recordTutorQuizAttempt(results); // 🔥 NOT AWAITING THIS
            } else {
                console.log('🎓 TUTOR MODE: Skipping quiz attempt recording for learning mode');
            }

            // Background sound playback (completely fire-and-forget)
            playFinalGradingSound(results.correct, results.total); // 🔥 NOT AWAITING THIS

            console.log(`🎉 INSTANT RESULTS: Function completed in ${(performance.now() - completionStart).toFixed(2)}ms - background operations running independently`);

            // 🎯 RETURN IMMEDIATELY - NO CONTEXTWAITING
            return;
        }


        
        // 🎯 UNIFIED GRADING SOUND USING GradingAudioController
        function playFinalGradingSound(score, total) {
            const funcStart = performance.now();
            console.log('🔥 PLAY FINAL GRADING SOUND - UNIFIED CONTROLLER');

            const percentage = (score / total) * 100;
            console.log(`📊 FINAL SCORE: ${score}/${total} = ${percentage.toFixed(1)}%`);

            // 🎯 USE UNIFIED CONTROLLER FOR CONSISTENT SOUND SELECTION
            GradingAudioController.playGradingSound(percentage, total);

            console.log(`🔥 UNIFIED GRADING SOUND COMPLETED in ${(performance.now() - funcStart).toFixed(2)}ms`);
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reviewAnswersButton = document.getElementById('review-answers-btn');

            if (!reviewAnswersButton) {
                return;
            }

            window.enterTutorReviewMode = function enterTutorReviewMode() {
                if (document.body.classList.contains('tutor-review-mode')) {
                    return;
                }

                document.body.classList.add('tutor-review-mode');

                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                const timerElement = document.getElementById('timer');
                popup?.classList.remove('show');
                popupOverlay?.classList.remove('show');

                if (timerInterval) {
                    clearTimeout(timerInterval);
                    timerInterval = null;
                }

                if (timerElement) {
                    timerElement.classList.remove('warning', 'danger');
                }

                document.querySelectorAll('.check-answer, .next-question, .view-results, .btn-dont-know').forEach((element) => {
                    element.style.display = 'none';
                });

                document.querySelectorAll('.option-card').forEach((optionCard) => {
                    optionCard.style.pointerEvents = 'none';
                    optionCard.style.cursor = 'default';
                    optionCard.setAttribute('tabindex', '-1');
                });

                document.querySelectorAll('.written-answer').forEach((writtenAnswer) => {
                    writtenAnswer.readOnly = true;
                    writtenAnswer.disabled = true;
                    writtenAnswer.classList.add('review-locked');
                });

                document.querySelectorAll('.options-container').forEach((container) => {
                    const questionId = container.dataset.questionId;
                    const normalizedQuestionId = String(questionId || '');

                    if (!normalizedQuestionId) {
                        return;
                    }

                    const questionCard = container.closest('.question-card');
                    const feedbackDiv = questionCard?.querySelector('.answer-feedback');
                    const alertDiv = feedbackDiv?.querySelector('.alert');
                    const resultText = feedbackDiv?.querySelector('.result-text');
                    const explanationContent = feedbackDiv?.querySelector('.explanation-content');
                    const qData = questionData[normalizedQuestionId] || questionData[Number(normalizedQuestionId)];
                    const submittedAnswer = userAnswers[normalizedQuestionId] ?? userAnswers[Number(normalizedQuestionId)] ?? null;

                    if (!feedbackDiv || !alertDiv || !resultText || !explanationContent || !qData) {
                        return;
                    }

                    feedbackDiv.style.display = 'block';

                    const writtenAnswer = container.querySelector('.written-answer');
                    const isCorrect = submittedAnswer !== null
                        && String(submittedAnswer).trim().toLowerCase() === String(qData.correct_answer || '').trim().toLowerCase();

                    if (writtenAnswer) {
                        alertDiv.className = `alert ${isCorrect ? 'alert-success' : 'alert-danger'}`;
                        resultText.textContent = isCorrect ? 'Correct Answer' : 'Review Answer';

    

                        return;
                    }

                    const optionCards = container.querySelectorAll('.option-card');
                    optionCards.forEach((optionCard) => {
                        const optionLetter = optionCard.getAttribute('data-option');
                        optionCard.classList.remove('selected', 'correct', 'incorrect');

                        if (optionLetter === qData.correct_answer) {
                            optionCard.classList.add('correct');
                        } else if (submittedAnswer !== null && optionLetter === String(submittedAnswer) && optionLetter !== qData.correct_answer) {
                            optionCard.classList.add('incorrect');
                        }
                    });

                    alertDiv.className = `alert ${isCorrect ? 'alert-success' : 'alert-danger'}`;
                    resultText.textContent = isCorrect ? 'Correct Answer' : 'Review Answer';

    
                });

                if (typeof window.jumpToQuestion === 'function') {
                    window.jumpToQuestion(1);
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            reviewAnswersButton.addEventListener('click', function () {
                window.enterTutorReviewMode();
            });
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
        .popup-btn.review-btn {
            background: #f8f9fa !important;
            color: #495057 !important;
        }
        .popup-btn.review-btn:hover {
            background: #e9ecef !important;
            color: #495057 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1) !important;
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
        .written-answer.review-locked {
            background: #f8fafc !important;
            cursor: not-allowed !important;
        }
        .tutor-review-mode .highlight-toolbar {
            display: none !important;
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
