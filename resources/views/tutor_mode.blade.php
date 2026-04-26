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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">




    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.admin-dark-mode-head')

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
            zoom: 0.80;
            -webkit-text-size-adjust: 100%;
            /* Prevent Safari text auto-sizing */
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
            padding: 1.5rem 2.75rem;
        }

        .container {
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
            max-width: none;
            margin: 0 auto;
        }

        .question-card {
            background: transparent;
            border-radius: 0;
            padding: 0 0 1.75rem;
            box-shadow: none;
            margin-bottom: 1.75rem;
            border: 0;
            border-bottom: 2px solid rgba(148, 163, 184, 0.28);
            user-select: text;
            -webkit-user-select: text;
        }

        .question-content {
            display: flex;
            gap: 3.25rem;
            align-items: flex-start;
            margin-bottom: 2rem;
            width: 100%;
            user-select: text;
            -webkit-user-select: text;
        }

        .question-main {
            flex: 1;
            min-width: 0;
            user-select: text;
            -webkit-user-select: text;
        }

        .question-text-container {
            flex: 1;
            min-width: 0;
            user-select: text;
            -webkit-user-select: text;
        }

        /* When there's no image, text takes full width */
        .question-content:not(:has(.question-image)) .question-text-container {
            flex: 1 1 100%;
            max-width: none;
        }

        .question-text {
            font-size: 1.6rem;
            font-weight: 600;
            color: #000000;
            margin-bottom: 1.75rem;
            line-height: 1.45;
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

        .question-text mark.highlight-yellow {
            background-color: var(--highlight-yellow);
            box-shadow: inset 0 -0.42em 0 rgba(255, 245, 157, 0.82);
        }

        .question-text mark.highlight-pink {
            background-color: var(--highlight-pink);
            box-shadow: inset 0 -0.42em 0 rgba(248, 187, 208, 0.86);
        }

        .question-text mark.highlight-blue {
            background-color: var(--highlight-blue);
            box-shadow: inset 0 -0.42em 0 rgba(179, 229, 252, 0.9);
        }

        .question-text mark.highlight-green {
            background-color: var(--highlight-green);
            box-shadow: inset 0 -0.42em 0 rgba(197, 225, 165, 0.9);
        }

        .question-text mark.highlight-purple {
            background-color: var(--highlight-purple);
            box-shadow: inset 0 -0.42em 0 rgba(225, 190, 231, 0.9);
        }

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

        .highlight-btn.yellow {
            background-color: var(--highlight-yellow);
        }

        .highlight-btn.pink {
            background-color: var(--highlight-pink);
        }

        .highlight-btn.blue {
            background-color: var(--highlight-blue);
        }

        .highlight-btn.green {
            background-color: var(--highlight-green);
        }

        .highlight-btn.purple {
            background-color: var(--highlight-purple);
        }

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
            flex: 0 0 min(34vw, 560px);
            max-width: min(34vw, 560px);
            display: flex;
            justify-content: center;
        }

        .question-image img {
            width: 100%;
            height: auto;
            max-height: 640px;
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
            padding: 1.05rem 1.2rem;
            background-color: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            margin-bottom: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: 1.5px solid transparent;
            box-shadow: none;
        }

        .option-card:hover {
            background-color: #f1f3f5;
            border-color: #007bff;
            transform: translateY(-1px);
            outline: 1px solid #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }

        .option-card.selected {
            border-color: #007bff !important;
            background: #e3f2fd !important;
            color: #007bff !important;
            box-shadow: 0 0 0 1px rgba(0, 123, 255, 0.08);
        }

        .option-card.selected:hover,
        .option-card.selected:focus,
        .option-card.selected:active {
            border-color: #007bff !important;
            background: #e3f2fd !important;
            color: #007bff !important;
            outline: 1px solid #007bff;
            transform: none;
        }

        .option-card.selected .option-letter {
            background: rgba(0, 123, 255, 0.14);
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
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent);
            animation: shimmer 2s infinite;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%,
                    rgba(255, 255, 255, 0.1) 0%,
                    transparent 70%);
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

            0%,
            100% {
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
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.02);
            }
        }

        @keyframes timerDanger {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.05);
            }
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
            user-select: text;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
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
        .explanation-text>*:first-child {
            margin-top: 0;
        }

        .question-content {
            display: flow-root;
            /* Clear float without overflow hidden */
        }

        .options-container {
            clear: both;
            /* Clear float */
        }

        .option-content {
            font-size: 1rem;
            line-height: 1.5;
            user-select: text;
            -webkit-user-select: text;
        }

        .sources-info {
            margin: 0.25rem 0 1rem !important;
        }

        @media (min-width: 1200px) {
            .main-content {
                padding-left: 4rem;
                padding-right: 4rem;
            }

            .question-card {
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }

            .question-text {
                font-size: 1.8rem;
                line-height: 1.42;
                margin-bottom: 2rem;
            }

            .option-card {
                min-height: 72px;
                padding: 1.2rem 1.35rem;
            }

            .option-content {
                font-size: 1.08rem;
            }
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

            .container,
            .progress-bar-container {
                max-width: 90%;
            }
        }

        @media (max-width: 850px) {

            /* Reset desktop zoom on tablets and phones */
            body {
                zoom: 1 !important;
            }

            .container {
                padding: 0;
                width: 100%;
                border-radius: 0;
            }

            /* ── iPad / Tablet (768px–850px) layout fixes ── */
            .question-sidebar {
                width: 62px;
                top: 64px;
                /* below navbar */
                padding: 0.75rem 0.25rem;
                gap: 0.5rem;
            }

            .question-circle {
                width: 42px;
                height: 42px;
                min-width: 42px;
                min-height: 42px;
                max-width: 42px;
                max-height: 42px;
                font-size: 0.9rem;
            }

            .main-content {
                margin-left: 62px;
                margin-top: 64px;
                padding: 0.5rem 1rem 0.75rem;
            }

            .progress-bar-container {
                /* No broken transform — just sit naturally */
                max-width: 100%;
                margin-bottom: 1rem;
            }

            /* ── Question layout: stack image below text on tablet ── */
            .question-content {
                flex-direction: column !important;
                gap: 1.25rem;
            }

            /* Image goes full width below the question */
            .question-image {
                flex: none !important;
                width: 100% !important;
                max-width: 400px;
                margin: 0 auto;
            }

            /* Inner header row: badges stack ABOVE the question text */
            .question-main .d-flex.justify-content-between {
                flex-direction: column-reverse !important;
                align-items: flex-start !important;
                gap: 0.6rem;
            }

            /* Badges row: wrap left-to-right, not squished on the side */
            .question-main .d-flex.justify-content-between>div:last-child {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: flex-start !important;
                gap: 0.35rem;
                margin-left: 0 !important;
                width: 100%;
            }

            /* Question text: always takes full width */
            .question-text-container {
                width: 100% !important;
                flex: 1 1 100% !important;
                min-width: 0;
            }

            .question-text {
                font-size: 1.15rem;
                line-height: 1.55;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0;
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
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.8), transparent);
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
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
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
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .grade-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
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

            /* main-content margin-left and margin-top are set
               by the sidebar block below — no overrides needed here */
            .container {
                padding: 0;
                border-radius: 0;
                max-width: 100%;
                margin: 0;
            }

            .topic-header h1 {
                font-size: 1.4rem;
                margin-bottom: 0.45rem;
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
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            .topic-header.mb-4 {
                margin-bottom: 0.5rem !important;
            }

            .sources-info.mb-4 {
                margin-bottom: 0.85rem !important;
            }

            .question-card {
                padding: 0 0 1.5rem;
                border-radius: 0;
                margin-bottom: 1.5rem;
                border-bottom-color: rgba(148, 163, 184, 0.38);
            }

            .question-content {
                flex-direction: column;
                gap: 1rem;
            }

            .question-text {
                font-size: 1.12rem;
                line-height: 1.6;
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
                padding: 1rem 1rem;
                margin-bottom: 0.75rem;
                border-radius: 12px;
                gap: 0.75rem;
            }

            .option-letter {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
                flex-shrink: 0;
            }

            .option-content {
                font-size: 0.98rem;
                line-height: 1.5;
            }
        }

        /* Mobile Actions */
        @media (max-width: 768px) {
            .btn {
                padding: 0.875rem 1.25rem;
                font-size: 1rem;
                touch-action: manipulation;
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
                padding: 0 0.85rem 0.75rem;
            }

            .container {
                padding: 0;
            }

            .topic-header h1 {
                font-size: 1.3rem;
            }

            .question-card {
                padding: 0 0 1.2rem;
                margin-bottom: 1.2rem;
            }

            .topic-header.mb-4 {
                margin-bottom: 0.35rem !important;
            }

            .sources-info.mb-4 {
                margin-bottom: 0.7rem !important;
            }

            .question-text {
                font-size: 1.08rem;
            }

            .option-card {
                padding: 0.95rem 0.95rem;
                gap: 0.6rem;
                margin-left: -0.1rem;
                margin-right: -0.1rem;
            }

            .option-letter {
                width: 26px;
                height: 26px;
                font-size: 0.85rem;
            }

            .option-content {
                font-size: 0.96rem;
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
                contain: layout style;
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

        /* Admin Edit Button */
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

        .btn-admin-edit i {
            font-size: 0.65rem;
        }

        /* Edit Question Modal */
        .edit-question-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10050;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }

        .edit-question-overlay.active {
            display: flex;
        }

        .edit-question-modal {
            background: #fff;
            border-radius: 16px;
            width: 94%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            animation: editSlideIn 0.25s ease;
        }

        @keyframes editSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .edit-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .edit-modal-header h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6c757d;
            cursor: pointer;
            line-height: 1;
            padding: 0 0.25rem;
        }

        .edit-modal-close:hover {
            color: #212529;
        }

        .edit-modal-body {
            padding: 1.25rem 1.5rem;
        }

        .edit-field {
            margin-bottom: 1rem;
        }

        .edit-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.35rem;
        }

        .edit-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.88rem;
            border: 1.5px solid #dee2e6;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .edit-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.12);
        }

        .edit-options-grid {
            margin-bottom: 1rem;
        }

        .edit-option-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }

        .edit-option-radio {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            flex-shrink: 0;
        }

        .edit-option-radio input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: #28a745;
            cursor: pointer;
        }

        .edit-radio-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
            min-width: 14px;
            cursor: pointer;
        }

        .edit-option-row .edit-input {
            flex: 1;
        }

        .edit-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .edit-btn {
            padding: 0.5rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .edit-btn-cancel {
            background: #f1f3f5;
            color: #495057;
        }

        .edit-btn-cancel:hover {
            background: #e9ecef;
        }

        .edit-btn-save {
            background: #007bff;
            color: #fff;
        }

        .edit-btn-save:hover {
            background: #0056b3;
        }

        .edit-btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .edit-status {
            text-align: center;
            padding: 0 1.5rem 0.75rem;
            font-size: 0.8rem;
            min-height: 1.2rem;
        }

        .edit-status.success {
            color: #28a745;
        }

        .edit-status.error {
            color: #dc3545;
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

        /* ══ Mobile/Tablet sidebar: hide side bar, show horizontal strip ══ */
        @media (max-width: 768px) {

            /* ── Sidebar: rotate into a horizontal scrollable strip ─── */
            .question-sidebar {
                /* Switch from left-fixed column to top horizontal strip */
                position: fixed;
                left: 0;
                right: 0;
                top: auto;
                bottom: 0;
                width: 100% !important;
                height: 58px;
                flex-direction: row;
                overflow-x: auto;
                overflow-y: hidden;
                padding: 0.4rem 0.5rem;
                gap: 0.4rem;
                align-items: center;
                justify-content: flex-start;
                border-top: 1px solid #e9ecef;
                border-right: none;
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
                z-index: 999;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .question-sidebar::-webkit-scrollbar {
                display: none;
            }

            .question-circle {
                width: 40px;
                height: 40px;
                min-width: 40px;
                min-height: 40px;
                max-width: 40px;
                max-height: 40px;
                font-size: 0.85rem;
                border-width: 2px;
                flex-shrink: 0;
            }

            /* ── Main content: full width, add bottom padding for strip ─ */
            .main-content {
                margin-left: 0 !important;
                margin-top: 60px;
                padding: 1rem;
                padding-bottom: 72px;
                /* room for bottom strip */
            }

            /* ── Progress bar: sticky under navbar ────────────────────── */
            .progress-bar-container {
                position: sticky;
                top: 60px;
                z-index: 998;
                background: white;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                padding: 0.75rem;
                margin-bottom: 1rem;
                max-width: 100%;
            }

            /* ── Actions: stacked, Check Answer pinned ─────────────────── */
            .actions {
                margin-top: 1.25rem;
                flex-direction: column;
                gap: 0.75rem;
                /* Extra bottom padding so content not hidden behind strip */
                padding-bottom: 0;
            }

            .actions-left {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            /* Override the old fixed bottom button — let it flow naturally */
            .actions-left .btn {
                position: static !important;
                width: 100% !important;
                border-radius: 8px !important;
                margin: 0 !important;
                min-height: 52px;
                touch-action: manipulation;
            }

            .btn-dont-know {
                align-self: flex-end;
                width: 44px;
                height: 44px;
                font-size: 1rem;
            }

            .nav-arrow-btn {
                font-size: 1rem;
                min-width: 44px;
                min-height: 44px;
            }
        }

        @media (max-width: 480px) {
            .question-sidebar {
                height: 54px;
                padding: 0.35rem 0.4rem;
            }

            .question-circle {
                width: 36px;
                height: 36px;
                min-width: 36px;
                min-height: 36px;
                max-width: 36px;
                max-height: 36px;
                font-size: 0.8rem;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0.75rem;
                padding-bottom: 68px;
            }
        }

        /* ═══════════════════════════════════════════════════
           Annotation Canvas – Overlay + Toolbar + Toggle
           ═══════════════════════════════════════════════════ */

        /* ── Toggle button (always visible) ─────────────── */
        .annot-toggle-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .annot-toggle-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 24px rgba(0, 123, 255, 0.45);
        }

        .annot-toggle-btn:active {
            transform: scale(0.95);
        }

        .annot-toggle-active {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
            box-shadow: 0 4px 16px rgba(231, 76, 60, 0.4) !important;
        }

        /* ── Overlay (hidden by default) ────────────────── */
        .annot-overlay {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 99998;
            display: none;
            pointer-events: none;
        }

        .annot-overlay.annot-visible {
            display: block;
            pointer-events: auto;
        }

        /* ── Canvas ─────────────────────────────────────── */
        .annot-canvas {
            position: absolute;
            inset: 0;
            cursor: crosshair;
            touch-action: none;
            /* CRITICAL: let JS handle ALL touches on tablet */
        }

        /* ── Toolbar (bottom-center, pill shape) ────────── */
        .annot-toolbar {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            user-select: none;
            -webkit-user-select: none;
        }

        .annot-section {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .annot-divider {
            width: 1px;
            height: 28px;
            background: rgba(203, 213, 225, 0.85);
            margin: 0 4px;
            flex-shrink: 0;
        }

        /* ── Colour buttons ─────────────────────────────── */
        .annot-color-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            flex-shrink: 0;
            padding: 0;
            outline: none;
        }

        .annot-color-btn:hover {
            transform: scale(1.15);
        }

        .annot-color-btn.annot-active {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            transform: scale(1.1);
        }

        /* ── Tool buttons ───────────────────────────────── */
        .annot-tool-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            color: #475569;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.12s ease, background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
            padding: 0;
            outline: none;
            flex-shrink: 0;
        }

        .annot-tool-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(59, 130, 246, 0.5);
            color: #007bff;
        }

        .annot-tool-btn:active {
            transform: scale(0.93);
        }

        .annot-tool-btn.annot-active {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .annot-tool-btn.annot-disabled {
            opacity: 0.35;
            pointer-events: none;
        }

        /* Eraser active = red tint */
        .annot-eraser-btn.annot-active {
            background: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #fff !important;
        }

        /* Highlighter active = amber/orange tint */
        .annot-highlighter-btn.annot-active {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border-color: #d97706 !important;
            color: #fff !important;
        }

        /* Pen active = blue (already via annot-active) */
        .annot-pen-btn.annot-active {
            background: #007bff !important;
            border-color: #007bff !important;
            color: #fff !important;
        }

        /* Clear = red on hover */
        .annot-clear-btn:hover {
            color: #dc3545;
            border-color: #dc3545;
        }

        /* Close = red on hover */
        .annot-close-btn:hover {
            color: #dc3545;
            border-color: #dc3545;
        }

        /* ── Size indicator ─────────────────────────────── */
        .annot-size-indicator {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            min-width: 18px;
            text-align: center;
            user-select: none;
        }

        .annot-size-section {
            gap: 3px !important;
        }

        /* ── Cursor circle (eraser / highlighter size preview) ─ */
        .annot-cursor-circle {
            position: fixed;
            pointer-events: none;
            z-index: 100000;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.35);
            transform: translate(-50%, -50%);
            will-change: left, top;
            transition: width 0.10s ease, height 0.10s ease, background 0.10s ease;
        }

        /* ── Body class: annotation mode active (scrolling allowed) ─ */
        body.annot-mode-active {
            /* Scrolling is intentionally NOT blocked — users scroll normally */
        }

        /* ── Mobile / small-tablet responsive ───────────── */
        @media (max-width: 768px) {
            .annot-toggle-btn {
                bottom: 80px;
                right: 16px;
                width: 46px;
                height: 46px;
                font-size: 1.1rem;
            }

            .annot-toolbar {
                bottom: 140px;
                padding: 6px 10px;
                gap: 4px;
                border-radius: 18px;
                max-width: calc(100vw - 24px);
                flex-wrap: wrap;
                justify-content: center;
            }

            .annot-color-btn {
                width: 24px;
                height: 24px;
            }

            .annot-tool-btn {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .annot-toolbar {
                bottom: 130px;
                padding: 5px 8px;
            }
        }
    </style>
</head>

<body>
    <!-- Highlight Toolbar (Hidden by default, shown on text selection) -->
    <div class="highlight-toolbar" id="highlight-toolbar">
        <button class="highlight-btn yellow" onclick="applyHighlight('yellow')" title="Yellow"
            aria-label="Highlight Yellow"></button>
        <button class="highlight-btn pink" onclick="applyHighlight('pink')" title="Pink"
            aria-label="Highlight Pink"></button>
        <button class="highlight-btn blue" onclick="applyHighlight('blue')" title="Blue"
            aria-label="Highlight Blue"></button>
        <button class="highlight-btn green" onclick="applyHighlight('green')" title="Green"
            aria-label="Highlight Green"></button>
        <button class="highlight-btn purple" onclick="applyHighlight('purple')" title="Purple"
            aria-label="Highlight Purple"></button>
        <div class="toolbar-divider"></div>
        <button class="highlight-btn eraser" onclick="removeHighlight()" title="Remove Highlight"
            aria-label="Remove Highlight">
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
                    <img src="{{ $profileImageUrl }}" alt="Profile Picture" class="profile-image"
                        onerror="this.src='{{ $profileFallbackUrl }}'">
                </a>
            </div>
        </div>
    </nav>

    <!-- Question Navigation Sidebar -->
    <div class="question-sidebar" id="question-sidebar">
        @foreach($questions as $index => $question)
            <div class="question-circle {{ $index === 0 ? 'active' : '' }}" data-question="{{ $index + 1 }}"
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
                <div class="question-card" id="question-{{ $index + 1 }}"
                    style="{{ $index === 0 ? '' : 'display: none;' }}">
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
                                    @if(auth()->user()->isAdmin())
                                        <button class="btn-admin-edit" onclick="openEditModal({{ $qObj->id ?? 0 }})"
                                            title="Edit Question">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                    @endif
                                    @if(!empty($qObj->enhanced_source_badges))
                                        @foreach($qObj->enhanced_source_badges as $badge)
                                            @if(isset($topic) && strtoupper($topic->name) === strtoupper($badge))
                                                @continue
                                            @endif
                                            @if(str_contains($badge, 'HIGH YIELD'))
                                                <span class="badge bg-danger text-white"
                                                    style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600; white-space: nowrap;">
                                                    🔥 {{ $badge }}
                                                </span>
                                            @elseif(str_contains($badge, 'REPEATED'))
                                                <span class="badge bg-warning text-dark"
                                                    style="font-size: 0.7rem; padding: 0.3rem 0.6rem; font-weight: 600; white-space: nowrap;">
                                                    📈 {{ $badge }}
                                                </span>
                                            @else
                                                <span class="badge bg-primary text-white"
                                                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem; white-space: nowrap;">
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
                                    @foreach(collect(array_keys($cleanedOptions))->shuffle() as $letter)
                                        @php $cleanOption = $cleanedOptions[$letter]; @endphp
                                        @if($cleanOption !== '' && $cleanOption !== '-')
                                            <div class="option-card" data-option="{{ $letter }}">
                                                <span class="option-letter">{{ $letter }}</span>
                                                <div class="option-content">{{ $cleanOption }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="written-answer-container">
                                        <textarea class="form-control written-answer" rows="4"
                                            placeholder="Type your answer here..."></textarea>
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
                            <button class="btn btn-primary check-answer"
                                onclick="checkAnswer({{ $index + 1 }}, {{ $qObj->id ?? '' }})">
                                Check Answer
                            </button>
                            <button class="btn btn-primary next-question" style="display: none;"
                                onclick="showNextQuestion({{ $index + 1 }})">
                                Next Question
                            </button>
                            <button class="btn btn-success view-results" style="display: none;"
                                onclick="showFinalResults()">
                                View Results
                            </button>
                        </div>
                        <button class="btn-dont-know" onclick="handleDontKnow({{ $index + 1 }}, {{ $qObj->id ?? '' }})"
                            title="I Don't Know">
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

    @if(auth()->user()->isAdmin())
        <!-- ============================================================
             ADMIN INLINE EDIT MODAL — Full support for MCQ + Written
             ============================================================ -->
        <style>
            /* ── Modal Overlay ── */
            .edit-question-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(4px);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 10500;
                padding: 1rem;
            }

            .edit-question-overlay.show {
                display: flex;
            }

            /* ── Modal Box ── */
            .edit-question-modal {
                background: #fff;
                border-radius: 12px;
                border: 1px solid #dbe1e8;
                width: 100%;
                max-width: 660px;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
                animation: editModalIn 0.25s cubic-bezier(0.22, 1, 0.36, 1);
            }

            @keyframes editModalIn {
                from {
                    opacity: 0;
                    transform: translateY(24px) scale(0.97);
                }

                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            /* ── Header ── */
            .edit-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.25rem 1.5rem;
                border-bottom: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 12px 12px 0 0;
            }

            .edit-modal-header h5 {
                color: #0f172a;
                margin: 0;
                font-size: 1.05rem;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .edit-modal-close {
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                color: #475569;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                cursor: pointer;
                transition: background 0.2s, border-color 0.2s, color 0.2s;
            }

            .edit-modal-close:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            /* ── Body ── */
            .edit-modal-body {
                padding: 1.5rem;
            }

            /* Question type badge row */
            .edit-type-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
                padding: 0.75rem 1rem;
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }

            .edit-type-label {
                font-size: 0.82rem;
                font-weight: 600;
                color: #64748b;
            }

            .edit-type-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.1rem 0;
                font-size: 0.8rem;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: color 0.2s ease;
                position: relative;
            }

            .edit-type-badge::before {
                content: "";
                width: 10px;
                height: 10px;
                border-radius: 50%;
                border: 1.5px solid #94a3b8;
                background: #ffffff;
                flex-shrink: 0;
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }

            .edit-type-badge.mcq,
            .edit-type-badge.written {
                background: transparent;
                color: #475569;
            }

            .edit-type-badge.mcq.active,
            .edit-type-badge.written.active {
                background: transparent;
                color: #166534;
            }

            .edit-type-badge.active::before {
                background: #16a34a;
                border-color: #16a34a;
            }

            /* Fields */
            .edit-field {
                margin-bottom: 1.1rem;
            }

            .edit-field label {
                display: block;
                font-size: 0.82rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 0.4rem;
            }

            .edit-input {
                width: 100%;
                padding: 0.65rem 0.9rem;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 0.95rem;
                font-family: inherit;
                transition: border-color 0.2s, box-shadow 0.2s;
                box-sizing: border-box;
                resize: vertical;
            }

            .edit-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12);
            }

            /* Options grid */
            .edit-options-section {
                margin-bottom: 1.1rem;
            }

            .edit-options-section label {
                display: block;
                font-size: 0.82rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 0.6rem;
            }

            .edit-option-row {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                margin-bottom: 0.55rem;
            }

            .edit-option-radio {
                display: flex;
                align-items: center;
                gap: 0.4rem;
                flex-shrink: 0;
            }

            .edit-option-radio input[type="radio"] {
                width: 16px;
                height: 16px;
                accent-color: #16a34a;
                cursor: pointer;
                margin: 0;
            }

            .edit-radio-label {
                font-weight: 600;
                font-size: 0.85rem;
                color: #475569;
                background: transparent;
                border: none;
                width: auto;
                height: auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .edit-option-row .edit-input {
                margin-bottom: 0;
                flex: 1;
            }

            /* ── Footer ── */
            .edit-modal-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 0 0 12px 12px;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .edit-footer-left {
                display: flex;
                gap: 0.5rem;
            }

            .edit-footer-right {
                display: flex;
                gap: 0.5rem;
            }

            .edit-btn {
                padding: 0.6rem 1.25rem;
                border: 1px solid transparent;
                border-radius: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            }

            .edit-btn-cancel {
                background: #ffffff;
                border-color: #d1d5db;
                color: #475569;
            }

            .edit-btn-cancel:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
            }

            .edit-btn-save {
                background: #2563eb;
                border-color: #2563eb;
                color: #fff;
            }

            .edit-btn-save:hover {
                background: #1d4ed8;
                border-color: #1d4ed8;
            }

            .edit-btn-save:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .edit-btn-delete {
                background: #dc2626;
                border-color: #dc2626;
                color: #fff;
            }

            .edit-btn-delete:hover {
                background: #b91c1c;
                border-color: #b91c1c;
            }

            /* Status */
            .edit-status {
                padding: 0 1.5rem 1rem;
                font-size: 0.88rem;
                font-weight: 500;
                min-height: 1.5rem;
            }

            .edit-status.success {
                color: #16a34a;
            }

            .edit-status.error {
                color: #dc2626;
            }

            /* Delete confirmation */
            .delete-confirm-box {
                display: none;
                background: #fef2f2;
                border: 1.5px solid #fca5a5;
                border-radius: 10px;
                padding: 1rem 1.25rem;
                margin: 0 1.5rem 1rem;
            }

            .delete-confirm-box.show {
                display: block;
            }

            .delete-confirm-box p {
                margin: 0 0 0.75rem;
                color: #7f1d1d;
                font-size: 0.9rem;
                font-weight: 500;
            }

            .delete-confirm-actions {
                display: flex;
                gap: 0.5rem;
            }
        </style>

        <div class="edit-question-overlay" id="edit-question-overlay">
            <div class="edit-question-modal" id="edit-question-modal">

                <div class="edit-modal-header">
                    <h5><i class="fas fa-pen"></i> Edit Question</h5>
                    <button class="edit-modal-close" onclick="closeEditModal()" title="Close">&times;</button>
                </div>

                <div class="edit-modal-body">
                    <input type="hidden" id="edit-question-id">
                    <input type="hidden" id="edit-question-type">

                    <!-- Question type indicator + toggle -->
                    <div class="edit-type-row">
                        <span class="edit-type-label">Type:</span>
                        <span class="edit-type-badge mcq" id="edit-type-mcq" onclick="switchEditType('multiple_choice')">
                            <i class="fas fa-list-ul"></i> Multiple Choice
                        </span>
                        <span class="edit-type-badge written" id="edit-type-written" onclick="switchEditType('written')">
                            <i class="fas fa-pencil-alt"></i> Written
                        </span>
                    </div>

                    <!-- Question text field -->
                    <div class="edit-field">
                        <label for="edit-question-text"><i class="fas fa-question-circle"></i> Question Text</label>
                        <textarea id="edit-question-text" class="edit-input" rows="4"
                            placeholder="Enter question text..."></textarea>
                    </div>

                    <!-- MCQ fields -->
                    <div id="edit-mcq-section">
                        <div class="edit-options-section">
                            <label><i class="fas fa-check-circle" style="color:#16a34a"></i> Options — select the radio
                                button next to the correct answer</label>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-A" value="A">
                                    <label for="edit-correct-A" class="edit-radio-label">A</label>
                                </div>
                                <input type="text" id="edit-option-a" class="edit-input" placeholder="Option A">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-B" value="B">
                                    <label for="edit-correct-B" class="edit-radio-label">B</label>
                                </div>
                                <input type="text" id="edit-option-b" class="edit-input" placeholder="Option B">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-C" value="C">
                                    <label for="edit-correct-C" class="edit-radio-label">C</label>
                                </div>
                                <input type="text" id="edit-option-c" class="edit-input" placeholder="Option C">
                            </div>
                            <div class="edit-option-row">
                                <div class="edit-option-radio">
                                    <input type="radio" name="edit-correct" id="edit-correct-D" value="D">
                                    <label for="edit-correct-D" class="edit-radio-label">D</label>
                                </div>
                                <input type="text" id="edit-option-d" class="edit-input" placeholder="Option D">
                            </div>
                        </div>
                    </div>

                    <!-- Written fields -->
                    <div id="edit-written-section" style="display:none;">
                        <div class="edit-field">
                            <label for="edit-correct-answer-text"><i class="fas fa-check" style="color:#16a34a"></i> Correct
                                Answer</label>
                            <textarea id="edit-correct-answer-text" class="edit-input" rows="3"
                                placeholder="Enter the expected correct answer..."></textarea>
                        </div>
                        <div class="edit-field">
                            <label for="edit-alternative-answers"><i class="fas fa-random" style="color:#7c3aed"></i>
                                Alternative Acceptable Answers <span style="font-weight:400;color:#94a3b8">(one per line,
                                    optional)</span></label>
                            <textarea id="edit-alternative-answers" class="edit-input" rows="3"
                                placeholder="Enter alternative answers, one per line..."></textarea>
                        </div>
                    </div>

                    <!-- Explanation (both types) -->
                    <div class="edit-field">
                        <label for="edit-explanation"><i class="fas fa-lightbulb" style="color:#d97706"></i> Explanation
                            <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                        <textarea id="edit-explanation" class="edit-input" rows="3"
                            placeholder="Add an explanation for this question..."></textarea>
                    </div>
                </div>

                <!-- Delete confirmation box -->
                <div class="delete-confirm-box" id="delete-confirm-box">
                    <p><i class="fas fa-exclamation-triangle"></i> Are you sure you want to permanently delete this
                        question? This action cannot be undone.</p>
                    <div class="delete-confirm-actions">
                        <button class="edit-btn edit-btn-delete" id="confirm-delete-btn" onclick="confirmDeleteQuestion()">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>
                        <button class="edit-btn edit-btn-cancel" onclick="cancelDeleteQuestion()">Cancel</button>
                    </div>
                </div>

                <div class="edit-status" id="edit-status"></div>

                <div class="edit-modal-footer">
                    <div class="edit-footer-left">
                        <button class="edit-btn edit-btn-delete" id="edit-delete-btn" onclick="promptDeleteQuestion()">
                            <i class="fas fa-trash-alt"></i> Delete Question
                        </button>
                    </div>
                    <div class="edit-footer-right">
                        <button class="edit-btn edit-btn-cancel" onclick="closeEditModal()">Cancel</button>
                        <button class="edit-btn edit-btn-save" id="edit-save-btn" onclick="saveQuestionEdit()">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // ─────────────────────────────────────────────────────────────
            //  ADMIN INLINE EDIT MODAL — tutor_mode.blade.php
            // ─────────────────────────────────────────────────────────────
            const EDIT_UPDATE_URL = '/quiz/tutor/question/';
            const EDIT_DELETE_URL = '/quiz/tutor/question/';
            const EDIT_CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function openEditModal(questionId) {
                const data = window.TUTOR_CONFIG?.questionData?.[questionId];
                if (!data) { alert('Question data not found.'); return; }

                // Populate hidden fields
                document.getElementById('edit-question-id').value = questionId;
                document.getElementById('edit-question-type').value = data.question_type || 'multiple_choice';

                // Question text
                document.getElementById('edit-question-text').value = data.question_text || '';

                // Explanation
                document.getElementById('edit-explanation').value = data.explanation || '';

                // Switch UI to the right type
                const qType = data.question_type === 'written' ? 'written' : 'multiple_choice';
                switchEditType(qType, /* populate= */ true, data);

                // Reset status & delete confirm
                document.getElementById('edit-status').textContent = '';
                document.getElementById('edit-status').className = 'edit-status';
                document.getElementById('delete-confirm-box').classList.remove('show');
                document.getElementById('edit-save-btn').disabled = false;

                // Show overlay
                document.getElementById('edit-question-overlay').classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function switchEditType(type, populate, data) {
                document.getElementById('edit-question-type').value = type;

                const mcqBadge = document.getElementById('edit-type-mcq');
                const writBadge = document.getElementById('edit-type-written');
                const mcqSection = document.getElementById('edit-mcq-section');
                const writSection = document.getElementById('edit-written-section');

                if (type === 'written') {
                    mcqBadge.classList.remove('active');
                    writBadge.classList.add('active');
                    mcqSection.style.display = 'none';
                    writSection.style.display = 'block';
                    if (populate && data) {
                        document.getElementById('edit-correct-answer-text').value = data.correct_answer || '';
                        document.getElementById('edit-alternative-answers').value = data.alternative_answers || '';
                    }
                } else {
                    writBadge.classList.remove('active');
                    mcqBadge.classList.add('active');
                    writSection.style.display = 'none';
                    mcqSection.style.display = 'block';
                    if (populate && data) {
                        document.getElementById('edit-option-a').value = data.option_a || '';
                        document.getElementById('edit-option-b').value = data.option_b || '';
                        document.getElementById('edit-option-c').value = data.option_c || '';
                        document.getElementById('edit-option-d').value = data.option_d || '';
                        const correct = (data.correct_answer || '').toUpperCase();
                        const radio = document.getElementById('edit-correct-' + correct);
                        if (radio) radio.checked = true;
                    }
                }
            }

            function closeEditModal() {
                document.getElementById('edit-question-overlay').classList.remove('show');
                document.body.style.overflow = '';
            }

            async function saveQuestionEdit() {
                const questionId = document.getElementById('edit-question-id').value;
                const qType = document.getElementById('edit-question-type').value;
                const saveBtn = document.getElementById('edit-save-btn');
                const statusEl = document.getElementById('edit-status');

                const questionText = document.getElementById('edit-question-text').value.trim();
                if (!questionText) { setEditStatus('Question text is required.', 'error'); return; }

                let payload = {
                    _method: 'PUT',
                    question: questionText,
                    question_type: qType,
                    explanation: document.getElementById('edit-explanation').value.trim(),
                };

                if (qType === 'written') {
                    const correctText = document.getElementById('edit-correct-answer-text').value.trim();
                    if (!correctText) { setEditStatus('Correct answer is required for written questions.', 'error'); return; }
                    payload.correct_answer = correctText;
                    payload.alternative_answers = document.getElementById('edit-alternative-answers').value.trim();
                } else {
                    const checked = document.querySelector('input[name="edit-correct"]:checked');
                    if (!checked) { setEditStatus('Please select the correct answer.', 'error'); return; }
                    payload.correct_answer = checked.value;
                    payload.option_a = document.getElementById('edit-option-a').value.trim();
                    payload.option_b = document.getElementById('edit-option-b').value.trim();
                    payload.option_c = document.getElementById('edit-option-c').value.trim();
                    payload.option_d = document.getElementById('edit-option-d').value.trim();
                }

                saveBtn.disabled = true;
                setEditStatus('Saving…', '');

                try {
                    const res = await fetch(EDIT_UPDATE_URL + questionId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': EDIT_CSRF,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();

                    if (json.success) {
                        setEditStatus('✓ Saved successfully!', 'success');
                        // Update live question text on the page
                        updateLiveQuestionCard(questionId, payload, json.data);
                        // Update TUTOR_CONFIG cache
                        if (window.TUTOR_CONFIG?.questionData?.[questionId]) {
                            Object.assign(window.TUTOR_CONFIG.questionData[questionId], {
                                question_text: payload.question,
                                question_type: payload.question_type,
                                correct_answer: payload.correct_answer,
                                explanation: payload.explanation,
                                alternative_answers: payload.alternative_answers || '',
                                option_a: payload.option_a || '',
                                option_b: payload.option_b || '',
                                option_c: payload.option_c || '',
                                option_d: payload.option_d || '',
                            });
                        }
                        setTimeout(closeEditModal, 900);
                    } else {
                        setEditStatus(json.message || 'Error saving.', 'error');
                        saveBtn.disabled = false;
                    }
                } catch (err) {
                    setEditStatus('Network error: ' + err.message, 'error');
                    saveBtn.disabled = false;
                }
            }

            function updateLiveQuestionCard(questionId, payload, serverData) {
                // Update the visible question text in all cards on the page
                document.querySelectorAll('.question-card, .question-box, .written-question').forEach(card => {
                    const cid = card.dataset.questionId || card.id?.replace('question-', '');
                    if (String(cid) !== String(questionId) && card.id !== 'question-' + questionId) return;
                    const textEl = card.querySelector('.question-text');
                    if (textEl) textEl.textContent = payload.question;
                });
                // Also match by id="question-X" pattern used in tutor mode
                document.querySelectorAll('[id^="question-"]').forEach(card => {
                    const idx = card.id.replace('question-', '');
                    const optionsContainer = card.querySelector('.options-container');
                    if (!optionsContainer) return;
                    if (String(optionsContainer.dataset.questionId) !== String(questionId)) return;
                    const textEl = card.querySelector('.question-text');
                    if (textEl) textEl.textContent = payload.question;
                });
            }

            function promptDeleteQuestion() {
                document.getElementById('delete-confirm-box').classList.add('show');
                document.getElementById('edit-save-btn').disabled = true;
                document.getElementById('edit-delete-btn').style.display = 'none';
            }

            function cancelDeleteQuestion() {
                document.getElementById('delete-confirm-box').classList.remove('show');
                document.getElementById('edit-save-btn').disabled = false;
                document.getElementById('edit-delete-btn').style.display = '';
            }

            async function confirmDeleteQuestion() {
                const questionId = document.getElementById('edit-question-id').value;
                const confirmBtn = document.getElementById('confirm-delete-btn');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
                setEditStatus('Deleting question…', '');

                try {
                    const res = await fetch(EDIT_DELETE_URL + questionId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': EDIT_CSRF,
                            'Accept': 'application/json',
                        },
                    });
                    const json = await res.json();

                    if (json.success) {
                        setEditStatus('✓ Question deleted.', 'success');
                        // Remove the card from the page
                        document.querySelectorAll('.question-card, .question-box, .written-question').forEach(card => {
                            const cid = card.dataset.questionId;
                            if (String(cid) === String(questionId)) card.remove();
                        });
                        // Also remove by id pattern
                        document.querySelectorAll('[id^="question-"]').forEach(card => {
                            const optCont = card.querySelector(`.options-container[data-question-id="${questionId}"]`);
                            if (optCont) card.remove();
                        });
                        setTimeout(closeEditModal, 800);
                    } else {
                        setEditStatus(json.message || 'Error deleting.', 'error');
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                    }
                } catch (err) {
                    setEditStatus('Network error: ' + err.message, 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Yes, Delete';
                }
            }

            function setEditStatus(msg, cls) {
                const el = document.getElementById('edit-status');
                el.textContent = msg;
                el.className = 'edit-status' + (cls ? ' ' + cls : '');
            }

            // Close overlay on backdrop click
            document.getElementById('edit-question-overlay')?.addEventListener('click', function (e) {
                if (e.target === this) closeEditModal();
            });
        </script>
    @endif

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
                <a href="{{ route('topics.forCourse', $course->id) }}" class="popup-btn view-progress-btn"
                    id="view-progress-btn">
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
    <script src="{{ asset('js/quiz-session-draft.js') }}"></script>

    <script>
        window.TUTOR_CONFIG = {
            totalQuestions: {{ $questions->count() }},
            isRandomQuiz: {{ isset($is_random_quiz) && $is_random_quiz ? 'true' : 'false' }},
            isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }},
            topicId: {{ $topic->id ?? 1 }},
            courseId: {{ $course->id ?? 1 }},
            initialDuration: {{ isset($duration_minutes) ? $duration_minutes * 60 : 0 }},
            quizMode: '{{ isset($is_random_quiz) && $is_random_quiz ? "random" : "tutor" }}',
            tutorScope: @json($tutor_scope ?? 'full'),
            selectedSources: @json($selected_sources ?? []),
            randomSessionKey: @json($random_session_key ?? null),
            tutorSessionKey: @json($tutor_session_key ?? null),
            draftKey: @json(
                (isset($is_random_quiz) && $is_random_quiz)
                ? 'tutor-random:' . ($random_session_key ?? 'default')
                : 'tutor:' . (($tutor_session_key ?? null) ?: request()->fullUrl())
            ),
            questionData: {
                @foreach($questions as $index => $question)
                    @php $qObj = is_array($question) ? $question['question'] : $question; @endphp
                    {{ $qObj->id ?? 0 }}: {
                    correct_answer: @json($qObj->correct_answer ?? ''),
                    explanation: @json($qObj->explanation ?? ''),
                    question_type: @json($qObj->question_type ?? 'multiple_choice'),
                    @if(auth()->user()->isAdmin())
                            question_text: @json($qObj->question ?? ''),
                        option_a: @json($qObj->option_a ?? ''),
                        option_b: @json($qObj->option_b ?? ''),
                        option_c: @json($qObj->option_c ?? ''),
                        option_d: @json($qObj->option_d ?? ''),
                        alternative_answers: @json($qObj->alternative_answers ?? ''),
                    @endif
                    },
                @endforeach
            },
        gradeUrl: '{{ isset($is_random_quiz) && $is_random_quiz ? "/quiz/grade-random" : "/quiz/" . ($course->id ?? 1) . "/" . ($topic->id ?? 1) . "/grade" }}',
            gradeMeta: {
            course: '{{ $course->id ?? "no-course" }}',
                topic: '{{ $topic->id ?? "no-topic" }}',
                    isRandomQuiz: '{{ isset($is_random_quiz) && $is_random_quiz ? "true" : "false" }}'
        }
        };
    </script>
    <script src="{{ asset('js/tutor-mode.js') }}"></script>

    <!-- Annotation Canvas (iPad Pencil / Pen drawing overlay) -->
    <script src="{{ asset('js/annotation-canvas.js') }}"></script>

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
        canvas:not(#annot-canvas) {
            z-index: 10001 !important;
            pointer-events: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }

        /* Hide annotation toggle + toolbar during review mode */
        .tutor-review-mode .annot-toggle-btn,
        .tutor-review-mode .annot-overlay {
            display: none !important;
        }
    </style>
</body>

</html>
