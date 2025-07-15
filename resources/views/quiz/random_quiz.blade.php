<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Random Quiz - Test your medical knowledge with MedCapsule">
    <meta name="theme-color" content="#007bff">
    
    <title>Random Quiz - MedCapsule</title>
    
    <!-- GOLDEN STANDARD: Optimized resource loading with preconnect and preload -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Critical CSS loaded first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Optimized font loading with display=swap for better performance -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"></noscript>
    
    <!-- Font Awesome with optimized loading -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></noscript>
    
    <!-- Optimized favicon with proper formats -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    
    <!-- GOLDEN STANDARD: Optimized and enhanced CSS with performance focus -->
    <style>
        /* CSS Custom Properties for better maintainability and performance */
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --box-shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --box-shadow-lg: 0 8px 15px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.15s ease;
            --transition-smooth: 0.3s ease;
            --font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', roboto, sans-serif;
            --navbar-height: 70px;
        }

        /* Remove default styles for performance */
        * {
            box-sizing: border-box;
        }

        /* Enhanced base styles with better performance */
        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Remove no-js class when JavaScript loads */
        .js body {
            /* Enhanced animations when JS is available */
        }

        /* GOLDEN STANDARD: Enhanced navbar with better performance */
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            box-shadow: var(--box-shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: var(--navbar-height);
            display: flex;
            align-items: center;
            will-change: transform;
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
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: transform var(--transition-smooth);
            will-change: transform;
        }

        .navbar-brand:hover {
            color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            will-change: transform, background-color;
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: transform var(--transition-smooth);
        }

        .nav-link:hover {
            color: var(--primary-color);
            background-color: var(--light-bg);
            transform: translateY(-1px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        /* Enhanced mistakes link with better visual feedback */
        .mistakes-link {
            background: linear-gradient(135deg, #fff5f5, #ffe3e3);
            color: #e03131;
            border: 1px solid #ffa8a8;
            position: relative;
            overflow: hidden;
        }

        .mistakes-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }

        .mistakes-link:hover::before {
            left: 100%;
        }

        .mistakes-link:hover {
            background: linear-gradient(135deg, #ffe3e3, #ffa8a8);
            color: #c92a2a;
            border-color: #ff6b6b;
            transform: translateY(-2px);
        }

        .mistakes-link i {
            color: #e03131;
        }

        .mistakes-link:hover i {
            animation: pulse 1s infinite;
        }

        /* GOLDEN STANDARD: Enhanced main content with better spacing */
        .main-content {
            margin-top: calc(var(--navbar-height) + 2rem);
            padding: 0 2rem 2rem;
            min-height: calc(100vh - var(--navbar-height));
        }

        .container {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow);
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            will-change: transform;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            text-align: center;
            margin-bottom: 2.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 2.2rem;
            position: relative;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), #00d4ff);
            border-radius: 2px;
        }

        /* GOLDEN STANDARD: Enhanced progress bar with better performance */
        .progress-bar-container {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(248, 249, 250, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 1020;
            transition: all var(--transition-smooth);
            max-width: 900px;
            width: calc(100% - 2rem);
            will-change: transform;
        }

        .progress-bar-container.at-form {
            position: relative;
            bottom: auto;
            transform: none;
            left: auto;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow-sm);
            background: var(--white);
        }

        .timer-progress-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .timer {
            font-size: 1.1rem;
            color: var(--secondary-color);
            font-weight: 600;
            padding: 0.5rem 1rem;
            background: rgba(0, 123, 255, 0.1);
            border-radius: var(--border-radius);
            transition: all var(--transition-smooth);
        }

        .timer.warning {
            background: rgba(255, 193, 7, 0.2);
            color: #e65100;
        }

        .timer.critical {
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #00d4ff);
            width: 0;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 6px;
            position: relative;
            will-change: width;
        }

        .progress-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-text {
            text-align: center;
            color: var(--secondary-color);
            margin-top: 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* GOLDEN STANDARD: Enhanced question styling with better visual hierarchy */
        .question-box {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all var(--transition-smooth);
            will-change: transform, box-shadow;
            position: relative;
        }

        .question-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .question-number {
            position: absolute;
            top: -12px;
            left: 2rem;
            background: var(--primary-color);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: var(--box-shadow-sm);
        }

        .question-text {
            font-size: 1.15rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-weight: 500;
        }

        .question-image {
            max-width: 100%;
            max-height: 400px;
            width: auto;
            height: auto;
            border-radius: var(--border-radius);
            margin: 1.5rem auto;
            display: block;
            object-fit: contain;
            transition: transform var(--transition-smooth);
            box-shadow: var(--box-shadow-sm);
            cursor: pointer;
        }

        .question-image:hover {
            transform: scale(1.02);
            box-shadow: var(--box-shadow);
        }

        /* GOLDEN STANDARD: Enhanced options with better interaction feedback */
        .options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
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
            padding: 1.25rem 1.5rem;
            background-color: #fafbfc;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all var(--transition-smooth);
            margin: 0;
            position: relative;
            font-weight: 500;
            will-change: transform, background-color, border-color;
        }

        .form-check label::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 1.5rem;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            transition: all var(--transition-smooth);
        }

        .form-check label:hover {
            background-color: #f1f3f4;
            border-color: var(--primary-color);
            transform: translateX(3px);
        }

        .form-check input:checked + label {
            background-color: rgba(0, 123, 255, 0.08);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateX(3px);
        }

        .form-check input:checked + label::before {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check input:checked + label::after {
            content: '✓';
            position: absolute;
            top: 50%;
            right: 1.5rem;
            transform: translateY(-50%);
            color: var(--white);
            font-weight: bold;
            font-size: 12px;
        }

        /* Enhanced correct/incorrect states */
        .form-check label.correct {
            background-color: rgba(40, 167, 69, 0.1) !important;
            border-color: var(--success-color) !important;
            color: var(--success-color) !important;
        }

        .form-check label.incorrect {
            background-color: rgba(220, 53, 69, 0.1) !important;
            border-color: var(--danger-color) !important;
            color: var(--danger-color) !important;
        }

        /* GOLDEN STANDARD: Enhanced written answer styling */
        .written-answer {
            width: 100%;
            min-height: 120px;
            padding: 1.25rem;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: var(--font-family);
            resize: vertical;
            transition: all var(--transition-smooth);
            background-color: #fafbfc;
        }

        .written-answer:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .written-answer.correct {
            border-color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.05);
        }

        .written-answer.incorrect {
            border-color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.05);
        }

        /* GOLDEN STANDARD: Enhanced submit button with better states */
        .submit-btn {
            width: 100%;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--border-radius-lg);
            cursor: pointer;
            transition: all var(--transition-smooth);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            will-change: transform, background-color;
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
<<<<<<< HEAD
        }

=======
            transform: none !important;
        }

        .submit-btn.btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: var(--white);
        }

        .submit-btn.btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #e0a800);
            color: #856404;
        }

        .submit-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        /* GOLDEN STANDARD: Enhanced popup with better animations */
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .popup {
            background: var(--white);
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            transform: scale(0.7) translateY(50px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .popup-overlay.show .popup {
            transform: scale(1) translateY(0);
        }

        .grade-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease infinite;
        }

        .grade-icon.perfect i {
            color: #FFD700 !important;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
            animation: glow-gold 2s ease-in-out infinite alternate;
        }

        .grade-icon.excellent i {
            color: #ff6b35 !important;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.8);
            animation: glow-orange 2s ease-in-out infinite alternate;
        }

        .grade-icon.good i {
            color: #40a9ff !important;
            text-shadow: 0 0 15px rgba(64, 169, 255, 0.6);
            animation: pulse-blue 1.5s ease-in-out infinite;
        }

        .grade-icon.improving i {
            color: #ff9800 !important;
            text-shadow: 0 0 15px rgba(255, 152, 0, 0.6);
            animation: pulse-orange 1.5s ease-in-out infinite;
        }

        .grade-icon.needs-improvement i {
            color: #ff4d4f !important;
            text-shadow: 0 0 15px rgba(255, 77, 79, 0.6);
            animation: pulse-red 1.5s ease-in-out infinite;
        }

        .popup-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .score-container {
            margin: 2rem 0;
        }

        .popup-score {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .popup-message {
            font-size: 1.1rem;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .popup-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .popup-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .review-btn {
            background: linear-gradient(135deg, var(--secondary-color), #495057);
            color: var(--white);
        }

        .view-progress-btn {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: var(--white);
        }

        .popup-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        /* GOLDEN STANDARD: Enhanced loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3000;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-smooth);
        }

        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            margin-top: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* GOLDEN STANDARD: Enhanced animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

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

        .pulsing {
            animation: pulse 0.5s infinite;
        }

        /* GOLDEN STANDARD: Enhanced responsive design */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }

            .nav-links {
                gap: 0.5rem;
            }

            .nav-link {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            .main-content {
                padding: 0 1rem 1rem;
            }

            .container {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.8rem;
                margin-bottom: 2rem;
            }

            .question-box {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .question-text {
                font-size: 1rem;
            }

            .form-check label {
                padding: 1rem;
                font-size: 0.95rem;
            }

            .progress-bar-container {
                padding: 1rem;
                width: calc(100% - 1rem);
            }

            .timer-progress-row {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .popup {
                padding: 2rem;
                width: 95%;
            }

            .popup-buttons {
                flex-direction: column;
            }

            .popup-btn {
                text-align: center;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.3rem;
            }

            .container {
                padding: 1rem;
            }

            .question-box {
                padding: 1rem;
            }

            .form-check label {
                padding: 0.75rem;
            }

            .submit-btn {
                font-size: 1rem;
                padding: 0.75rem 1.5rem;
            }
        }

<<<<<<< HEAD
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
=======
        /* GOLDEN STANDARD: Print styles for accessibility */
        @media print {
            .navbar,
            .progress-bar-container,
            .popup-overlay,
            .loading-overlay {
                display: none !important;
            }

            .main-content {
                margin-top: 0;
            }

            .container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* GOLDEN STANDARD: High contrast mode support */
        @media (prefers-contrast: high) {
            :root {
                --primary-color: #0000ff;
                --success-color: #008000;
                --danger-color: #ff0000;
            }

            .form-check label {
                border-width: 3px;
            }
        }

        /* GOLDEN STANDARD: Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
        }
    </style>
</head>

<body>
    <!-- GOLDEN STANDARD: Enhanced navbar with better accessibility -->
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="container">
            <a href="{{ route('dashboard') }}" class="navbar-brand" aria-label="MedCapsule Home">
                <i class="fas fa-pills me-2" aria-hidden="true"></i>
                MedCapsule
            </a>
            <div class="nav-links">
                <a href="{{ route('topics.forCourse', ['course' => $selectedCourses->first()->id]) }}" 
                   class="nav-link" aria-label="Back to topics">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Back to Topics</span>
                </a>
                <a href="{{ route('mistakes.index') }}" 
                   class="nav-link mistakes-link" aria-label="Review mistakes">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span class="d-none d-md-inline">Mistakes</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- GOLDEN STANDARD: Enhanced main content with better structure -->
    <main class="main-content" role="main">
        <div class="container">
            <header>
                <h1>
                    <i class="fas fa-random me-3" aria-hidden="true"></i>
                    Random Quiz Challenge
                </h1>
            </header>

            @if($questions && $questions->count() > 0)
                <!-- GOLDEN STANDARD: Enhanced progress bar with timer -->
                <div class="progress-bar-container at-form" role="region" aria-label="Quiz progress">
                    <div class="timer-progress-row">
                        <div class="timer" id="timer" aria-live="polite" aria-label="Time remaining">
                            {{ sprintf('%02d:%02d', floor($time_limit / 60), $time_limit % 60) }}
                        </div>
                        <div class="progress-text" id="progress-text" aria-live="polite">
                            0 of {{ $questions->count() }} questions answered
                        </div>
                    </div>
                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                </div>

                <!-- GOLDEN STANDARD: Enhanced form with better validation -->
                <form id="quiz-form" 
                      action="{{ route('quiz.grade-random') }}" 
                      method="POST" 
                      novalidate
                      aria-label="Random quiz form">
                    @csrf
                    <input type="hidden" name="time_taken" id="time-taken" value="0">

                    @foreach($questions as $index => $question)
                        <article class="question-box" 
                                 data-question-id="{{ $question->id }}" 
                                 data-question-type="{{ $question->question_type ?? 'multiple_choice' }}"
                                 role="group" 
                                 aria-labelledby="question-{{ $question->id }}-title">
                            
                            <div class="question-number" aria-hidden="true">
                                {{ $index + 1 }} / {{ $questions->count() }}
                            </div>

                            <h2 class="question-text" id="question-{{ $question->id }}-title">
                                {{ $question->question }}
                            </h2>

                            @if($question->image_url)
                                <div class="question-image-container">
                                    <img src="{{ asset($question->image_url) }}" 
                                         alt="Question {{ $index + 1 }} illustration" 
                                         class="question-image"
                                         loading="lazy"
                                         onerror="this.style.display='none'"
                                         onclick="this.requestFullscreen?.() || this.webkitRequestFullscreen?.()"
                                         tabindex="0"
                                         role="button"
                                         aria-label="Click to view image in fullscreen">
                                </div>
                            @endif

                            @if(($question->question_type ?? 'multiple_choice') === 'written')
                                <!-- GOLDEN STANDARD: Enhanced written answer input -->
                                <div class="written-answer-container">
                                    <label for="answer-{{ $question->id }}" class="visually-hidden">
                                        Your answer for question {{ $index + 1 }}
                                    </label>
                                    <textarea name="answers[{{ $question->id }}]" 
                                              id="answer-{{ $question->id }}"
                                              class="written-answer" 
                                              data-question-id="{{ $question->id }}"
                                              placeholder="Type your detailed answer here..."
                                              aria-describedby="answer-{{ $question->id }}-help"
                                              spellcheck="true"
                                              autocomplete="off"></textarea>
                                    <div id="answer-{{ $question->id }}-help" class="form-text">
                                        Provide a comprehensive answer based on your medical knowledge.
                                    </div>
                                    <div class="answer-feedback" 
                                         id="feedback-{{ $question->id }}" 
                                         aria-live="polite"></div>
                                </div>
                            @else
                                <!-- GOLDEN STANDARD: Enhanced multiple choice options -->
                                <fieldset class="options" role="radiogroup" aria-labelledby="question-{{ $question->id }}-title">
                                    <legend class="visually-hidden">Select your answer for question {{ $index + 1 }}</legend>
                                    
                                    @foreach(['A', 'B', 'C', 'D'] as $option)
                                        @php
                                            $optionProperty = strtolower($option) . '_option';
                                            $optionText = $question->$optionProperty;
                                        @endphp
                                        
                                        @if($optionText)
                                            <div class="form-check">
                                                <input type="radio" 
                                                       id="q{{ $question->id }}_{{ $option }}" 
                                                       name="answers[{{ $question->id }}]" 
                                                       value="{{ $option }}" 
                                                       class="form-check-input"
                                                       aria-describedby="q{{ $question->id }}_{{ $option }}_desc">
                                                <label for="q{{ $question->id }}_{{ $option }}" 
                                                       class="form-check-label">
                                                    <strong>{{ $option }}.</strong> {{ $optionText }}
                                                </label>
                                            </div>
                                        @endif
                                    @endforeach
                                </fieldset>
                            @endif
                        </article>
                    @endforeach

                    <!-- GOLDEN STANDARD: Enhanced submit button -->
                    <div class="text-center mt-4">
                        <button type="submit" 
                                id="submit-btn" 
                                class="submit-btn btn-primary" 
                                disabled
                                aria-describedby="submit-help">
                            <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>
                            Submit Quiz
                        </button>
                        <div id="submit-help" class="form-text mt-2">
                            Answer questions to enable submission
                        </div>
                    </div>
                </form>
            @else
                <!-- GOLDEN STANDARD: Enhanced empty state -->
                <div class="text-center py-5" role="alert">
                    <i class="fas fa-question-circle fa-3x text-muted mb-3" aria-hidden="true"></i>
                    <h2 class="text-muted">No Questions Available</h2>
                    <p class="text-muted mb-4">
                        No questions are available for this quiz. Please try selecting different courses.
                    </p>
                    <a href="{{ route('topics.forCourse', ['course' => $selectedCourses->first()->id]) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>
                        Back to Topics
                    </a>
                </div>
            @endif
        </div>

        <!-- GOLDEN STANDARD: Fixed progress bar for mobile -->
        <div class="progress-bar-container" id="progress-bar-container" role="region" aria-label="Quiz progress">
            <div class="timer-progress-row">
                <div class="timer" id="timer-mobile" aria-live="polite">
                    {{ sprintf('%02d:%02d', floor($time_limit / 60), $time_limit % 60) }}
                </div>
                <div class="progress-text" id="progress-text-mobile" aria-live="polite">
                    0 of {{ $questions->count() }} questions answered
                </div>
            </div>
            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-fill" id="progress-fill-mobile"></div>
            </div>
        </div>
    </main>

    <!-- GOLDEN STANDARD: Enhanced results popup with better accessibility -->
    <div class="popup-overlay" id="popup-overlay" role="dialog" aria-labelledby="popup-title" aria-modal="true">
        <div class="popup" role="document">
            <div class="grade-icon" id="grade-icon" aria-hidden="true"></div>
            <h2 class="popup-title" id="popup-title">Quiz Complete!</h2>
            <div class="score-container">
                <div class="popup-score" id="popup-score" aria-label="Quiz score">0/0</div>
            </div>
            <p class="popup-message" id="popup-message">
                Keep practicing! You're getting there!
            </p>
            <div class="popup-buttons">
                <button class="popup-btn review-btn" id="review-btn" type="button">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    Review Answers
                </button>
                <a href="{{ route('topics.forCourse', ['course' => $selectedCourses->first()->id]) }}" 
                   class="popup-btn view-progress-btn">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    Back to Topics
                </a>
            </div>
        </div>
    </div>

    <!-- GOLDEN STANDARD: Enhanced loading overlay -->
    <div class="loading-overlay" id="loading-overlay" aria-hidden="true">
        <div class="text-center">
            <div class="loading-spinner" aria-hidden="true"></div>
            <div class="loading-text">Grading your quiz...</div>
        </div>
    </div>

<<<<<<< HEAD
    <!-- Add audio elements -->
    <audio id="quizPerfectSound" preload="auto">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mpeg">
=======
    <!-- GOLDEN STANDARD: Optimized script loading -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" crossorigin="anonymous"></script>

    <!-- GOLDEN STANDARD: Enhanced audio elements with error handling -->
    <audio id="quizPerfectSound" preload="metadata" aria-hidden="true">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-perfect.mp3') }}" type="audio/mp3">
    </audio>
    <audio id="quizPartialSound" preload="metadata" aria-hidden="true">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-partial.mp3') }}" type="audio/mp3">
    </audio>
    <audio id="quizWrongSound" preload="metadata" aria-hidden="true">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/quiz-wrong.mp3') }}" type="audio/mp3">
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
    </audio>

    <!-- GOLDEN STANDARD: Optimized JavaScript with performance focus -->
    <script>
        // Remove no-js class and add js class for enhanced styling
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');

        // GOLDEN STANDARD: Performance optimized DOMContentLoaded handler
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Random Quiz initialized');

            // GOLDEN STANDARD: Cached DOM elements for better performance
            const elements = {
                form: document.getElementById('quiz-form'),
                submitBtn: document.getElementById('submit-btn'),
                progressFill: document.getElementById('progress-fill'),
                progressFillMobile: document.getElementById('progress-fill-mobile'),
                progressText: document.getElementById('progress-text'),
                progressTextMobile: document.getElementById('progress-text-mobile'),
                timer: document.getElementById('timer'),
                timerMobile: document.getElementById('timer-mobile'),
                timeTakenField: document.getElementById('time-taken'),
                popup: document.getElementById('popup'),
                popupOverlay: document.getElementById('popup-overlay'),
                popupTitle: document.getElementById('popup-title'),
                popupScore: document.getElementById('popup-score'),
                popupMessage: document.getElementById('popup-message'),
                gradeIcon: document.getElementById('grade-icon'),
                reviewBtn: document.getElementById('review-btn'),
                loadingOverlay: document.getElementById('loading-overlay')
            };

            // GOLDEN STANDARD: Validate required elements
            const requiredElements = ['form', 'submitBtn', 'progressFill', 'timer', 'timeTakenField'];
            const missingElements = requiredElements.filter(key => !elements[key]);
            
<<<<<<< HEAD
            let selectedAnswers = {};
            
            // Update progress function
=======
            if (missingElements.length > 0) {
                console.error('❌ Missing required elements:', missingElements);
                return;
            }

            // GOLDEN STANDARD: State management
            const state = {
                selectedAnswers: new Map(),
                totalQuestions: {{ $questions->count() }},
                timeLimit: {{ $time_limit }},
                timeRemaining: {{ $time_limit }},
                timeTaken: 0,
                timerInterval: null,
                quizSubmitted: false,
                audioContext: null
            };

            // GOLDEN STANDARD: Optimized progress update function
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
            function updateProgress() {
                const answeredCount = state.selectedAnswers.size;
                const percentage = (answeredCount / state.totalQuestions) * 100;
                
<<<<<<< HEAD
                // Update progress bar
                const progressFill = document.getElementById('progress-fill');
                const progressText = document.getElementById('progress-text');
                const progressPercentage = (answeredQuestions / totalQuestions) * 100;
                
                progressFill.style.width = `${progressPercentage}%`;
                progressText.textContent = `${answeredQuestions} of ${totalQuestions} questions answered`;
                
                // Enable submit button when all questions are answered
                submitBtn.disabled = answeredQuestions !== totalQuestions;
=======
                // Use requestAnimationFrame for smooth animations
                requestAnimationFrame(() => {
                    // Update progress bars
                    if (elements.progressFill) {
                        elements.progressFill.style.width = `${percentage}%`;
                    }
                    if (elements.progressFillMobile) {
                        elements.progressFillMobile.style.width = `${percentage}%`;
                    }
                    
                    // Update progress text
                    const progressMessage = `${answeredCount} of ${state.totalQuestions} questions answered`;
                    if (elements.progressText) {
                        elements.progressText.textContent = progressMessage;
                    }
                    if (elements.progressTextMobile) {
                        elements.progressTextMobile.textContent = progressMessage;
                    }
                    
                    // Update submit button
                    updateSubmitButton(answeredCount);
                    
                    // Update progress bar aria attributes
                    document.querySelectorAll('[role="progressbar"]').forEach(bar => {
                        bar.setAttribute('aria-valuenow', Math.round(percentage));
                    });
                });
            }

            // GOLDEN STANDARD: Optimized submit button update
            function updateSubmitButton(answeredCount) {
                if (!elements.submitBtn) return;
                
                elements.submitBtn.disabled = false; // Always allow submission
                
                if (answeredCount === state.totalQuestions) {
                    elements.submitBtn.textContent = '✓ Submit Quiz';
                    elements.submitBtn.className = 'submit-btn btn-primary';
                } else {
                    elements.submitBtn.innerHTML = `
                        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>
                        Submit Quiz (${answeredCount}/${state.totalQuestions} answered)
                    `;
                    elements.submitBtn.className = 'submit-btn btn-warning';
                }
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
            }

<<<<<<< HEAD
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
=======
            // GOLDEN STANDARD: Optimized event handlers with delegation
            function initializeEventHandlers() {
                // Multiple choice answers with event delegation
                elements.form.addEventListener('change', function(e) {
                    if (e.target.matches('.form-check-input')) {
                        const questionId = e.target.name.match(/\[(\d+)\]/)?.[1];
                        if (questionId) {
                            state.selectedAnswers.set(questionId, e.target.value);
                            updateProgress();
                        }
                    }
                });

                // Written answers with debounced input
                let inputTimeout;
                elements.form.addEventListener('input', function(e) {
                    if (e.target.matches('.written-answer')) {
                        clearTimeout(inputTimeout);
                        inputTimeout = setTimeout(() => {
                            const questionId = e.target.getAttribute('data-question-id');
                            if (questionId) {
                                if (e.target.value.trim() !== '') {
                                    state.selectedAnswers.set(questionId, e.target.value);
                                } else {
                                    state.selectedAnswers.delete(questionId);
                                }
                                updateProgress();
                            }
                        }, 300); // Debounce for 300ms
                    }
                });

                // Form submission
                elements.form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!state.quizSubmitted) {
                        console.log('📝 Quiz submission initiated');
                        submitQuiz();
                    }
                });

                // Popup buttons
                if (elements.reviewBtn) {
                    elements.reviewBtn.addEventListener('click', function() {
                        hidePopup();
                    });
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
                }

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === 'Enter' && !state.quizSubmitted) {
                        e.preventDefault();
                        submitQuiz();
                    } else if (e.key === 'Escape' && elements.popupOverlay.classList.contains('show')) {
                        hidePopup();
                    }
                });
            }

            // GOLDEN STANDARD: Enhanced timer with better performance
            function initializeTimer() {
                function updateTimer() {
                    if (state.quizSubmitted) {
                        clearInterval(state.timerInterval);
                        return;
                    }
                    
                    state.timeRemaining--;
                    state.timeTaken++;
                    
                    const minutes = Math.floor(state.timeRemaining / 60);
                    const seconds = state.timeRemaining % 60;
                    const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    // Update timer displays
                    if (elements.timer) {
                        elements.timer.textContent = timeDisplay;
                    }
                    if (elements.timerMobile) {
                        elements.timerMobile.textContent = timeDisplay;
                    }
                    
                    // Update hidden field
                    elements.timeTakenField.value = state.timeTaken;
                    
                    // Visual warnings
                    const timerElements = [elements.timer, elements.timerMobile].filter(Boolean);
                    
                    if (state.timeRemaining <= 30) {
                        timerElements.forEach(timer => {
                            timer.className = 'timer warning';
                            if (state.timeRemaining <= 10) {
                                timer.className = 'timer critical pulsing';
                            }
                        });
                    }
                    
                    // Auto-submit when time expires
                    if (state.timeRemaining <= 0) {
                        clearInterval(state.timerInterval);
                        if (!state.quizSubmitted) {
                            console.log('⏰ Timer expired - auto-submitting quiz');
                            state.quizSubmitted = true;
                            submitQuiz();
                        }
                    }
                }
                
                // Start timer
                state.timerInterval = setInterval(updateTimer, 1000);
            }
<<<<<<< HEAD
            
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
=======

            // GOLDEN STANDARD: Enhanced quiz submission with comprehensive error handling
            async function submitQuiz() {
                if (state.quizSubmitted) {
                    console.log('Quiz already submitted, preventing duplicate submission');
                    return;
                }
                
                state.quizSubmitted = true;
                clearInterval(state.timerInterval);
                
                // Show loading overlay
                showLoadingOverlay();
                
                // Disable form inputs
                const formInputs = elements.form.querySelectorAll('input, textarea, button');
                formInputs.forEach(input => input.disabled = true);
                
                try {
                    // Prepare form data
                    const formData = {
                        answers: Object.fromEntries(state.selectedAnswers),
                        time_taken: state.timeTaken
                    };
                    
                    console.log('📤 Submitting quiz:', {
                        answersCount: state.selectedAnswers.size,
                        timeTaken: state.timeTaken
                    });
                    
                    // Submit with retry logic
                    const response = await fetchWithRetry(
                        '{{ route('quiz.grade-random') }}',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(formData)
                        }
                    );
                    
                    hideLoadingOverlay();
                    
                    if (response.success === true || (response.score !== undefined && response.total !== undefined)) {
                        console.log('✅ Quiz graded successfully:', response);
                        displayQuizResults(response);
                    } else {
                        throw new Error(response.error || 'Invalid response format');
                    }
                    
                } catch (error) {
                    hideLoadingOverlay();
                    handleSubmissionError(error);
                }
            }

            // GOLDEN STANDARD: Fetch with retry logic
            async function fetchWithRetry(url, options, maxRetries = 3) {
                for (let i = 0; i < maxRetries; i++) {
                    try {
                        const response = await fetch(url, options);
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        return await response.json();
                    } catch (error) {
                        if (i === maxRetries - 1) {
                            throw error;
                        }
                        
                        // Wait before retrying
                        await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
                    }
                }
            }
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd

            // GOLDEN STANDARD: Enhanced results display
            function displayQuizResults(data) {
                try {
                    console.log('🎯 Displaying quiz results:', data);
                    
                    // Validate data
                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid quiz results data received');
                    }
                    
                    // Validate required elements
                    const requiredPopupElements = [elements.popup, elements.popupOverlay, elements.popupTitle, 
                                                 elements.popupScore, elements.popupMessage, elements.gradeIcon];
                    const missingPopupElements = requiredPopupElements.filter(el => !el);
                    
                    if (missingPopupElements.length > 0) {
                        throw new Error('Missing popup elements');
                    }
                    
                    // Extract score data
                    const score = parseInt(data.score) || 0;
                    const total = parseInt(data.total || data.total_questions) || 0;
                    const percentage = parseFloat(data.percentage_grade) || (total > 0 ? (score / total) * 100 : 0);
                    
                    console.log('📊 Score breakdown:', { score, total, percentage: percentage.toFixed(1) + '%' });
                    
                    // Update popup content
                    elements.popupScore.textContent = `${score}/${total}`;
                    elements.popupTitle.textContent = 'Quiz Complete!';
                    
                    // Set grade icon and message based on performance
                    updateGradeDisplay(percentage, data.message);
                    
                    // Play audio feedback
                    playQuizCompletionSound(score, total);
                    
                    // Show popup with animation
                    showPopup();
                    
                    // Store results for review
                    window.quizResults = {
                        ...data,
                        displayedAt: new Date().toISOString(),
                        finalScore: score,
                        finalTotal: total,
                        finalPercentage: percentage
                    };
                    
                    console.log('✅ Quiz results displayed successfully');
                    
                } catch (error) {
                    console.error('❌ Error displaying quiz results:', error);
                    showFallbackSuccessMessage(data);
                }
            }

            // GOLDEN STANDARD: Enhanced grade display
            function updateGradeDisplay(percentage, message) {
                if (percentage >= 100) {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-trophy" style="color: #FFD700;"></i>';
                    elements.gradeIcon.className = 'grade-icon perfect';
                    elements.popupTitle.textContent = 'Perfect Score! 🏆';
                    elements.popupMessage.textContent = message || 'Outstanding! You achieved a perfect score! 🌟';
                    createEnhancedConfetti();
                } else if (percentage >= 90) {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-star" style="color: #ff6b35;"></i>';
                    elements.gradeIcon.className = 'grade-icon excellent';
                    elements.popupTitle.textContent = 'Excellent Work! ⭐';
                    elements.popupMessage.textContent = message || 'Amazing performance! You\'re mastering this material! 🎯';
                    createConfetti();
                } else if (percentage >= 80) {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-medal" style="color: #4CAF50;"></i>';
                    elements.gradeIcon.className = 'grade-icon good';
                    elements.popupTitle.textContent = 'Great Job! 🥇';
                    elements.popupMessage.textContent = message || 'Well done! You\'re doing great! 💪';
                } else if (percentage >= 70) {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-thumbs-up" style="color: #2196F3;"></i>';
                    elements.gradeIcon.className = 'grade-icon good';
                    elements.popupTitle.textContent = 'Good Work! 👍';
                    elements.popupMessage.textContent = message || 'Good progress! Keep up the momentum! 📈';
                } else if (percentage >= 50) {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-chart-line" style="color: #FF9800;"></i>';
                    elements.gradeIcon.className = 'grade-icon improving';
                    elements.popupTitle.textContent = 'Keep Improving! 📊';
                    elements.popupMessage.textContent = message || 'You\'re making progress! Review and try again! 📚';
                } else {
                    elements.gradeIcon.innerHTML = '<i class="fas fa-redo" style="color: #F44336;"></i>';
                    elements.gradeIcon.className = 'grade-icon needs-improvement';
                    elements.popupTitle.textContent = 'Keep Practicing! 🔄';
                    elements.popupMessage.textContent = message || 'Don\'t give up! Every attempt helps you learn! 💪';
                }
            }

            // GOLDEN STANDARD: Enhanced audio system
            function playQuizCompletionSound(score, total) {
                try {
                    if (typeof score !== 'number' || typeof total !== 'number' || total === 0) {
                        console.warn('⚠️ Invalid score parameters for audio playback');
                        return;
                    }
                    
                    const percentage = (score / total) * 100;
                    let audioId;
                    
                    if (percentage >= 100) {
                        audioId = 'quizPerfectSound';
                    } else if (percentage >= 70) {
                        audioId = 'quizPartialSound';
                    } else {
                        audioId = 'quizWrongSound';
                    }
                    
                    const audio = document.getElementById(audioId);
                    if (audio && audio.readyState >= 2) {
                        audio.currentTime = 0;
                        const playPromise = audio.play();
                        
                        if (playPromise !== undefined) {
                            playPromise
                                .then(() => console.log(`✅ Audio played: ${audioId}`))
                                .catch(error => console.log(`🔇 Audio blocked: ${error.name}`));
                        }
                    }
                } catch (error) {
                    console.warn('⚠️ Audio playback error:', error.message);
                }
            }

            // GOLDEN STANDARD: Enhanced confetti effects
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

            function createEnhancedConfetti() {
                if (typeof confetti !== 'undefined') {
                    // Multiple confetti bursts for perfect score
                    setTimeout(() => createConfetti(), 300);
                    setTimeout(() => createConfetti(), 600);
                    setTimeout(() => {
                        confetti({
                            particleCount: 200,
                            spread: 100,
                            origin: { y: 0.4 },
                            colors: ['#FFD700', '#FFA500'],
                            zIndex: 9999
                        });
                    }, 900);
                }
            }

            // GOLDEN STANDARD: Popup management
            function showPopup() {
                elements.popupOverlay.style.display = 'flex';
                requestAnimationFrame(() => {
                    elements.popupOverlay.classList.add('show');
                    elements.popupOverlay.setAttribute('aria-hidden', 'false');
                    elements.popup.focus();
                });
            }

            function hidePopup() {
                elements.popupOverlay.classList.remove('show');
                elements.popupOverlay.setAttribute('aria-hidden', 'true');
                setTimeout(() => {
                    elements.popupOverlay.style.display = 'none';
                }, 400);
            }

            // GOLDEN STANDARD: Loading overlay management
            function showLoadingOverlay() {
                if (elements.loadingOverlay) {
                    elements.loadingOverlay.style.display = 'flex';
                    requestAnimationFrame(() => {
                        elements.loadingOverlay.classList.add('show');
                        elements.loadingOverlay.setAttribute('aria-hidden', 'false');
                    });
                }
            }

            function hideLoadingOverlay() {
                if (elements.loadingOverlay) {
                    elements.loadingOverlay.classList.remove('show');
                    elements.loadingOverlay.setAttribute('aria-hidden', 'true');
                    setTimeout(() => {
                        elements.loadingOverlay.style.display = 'none';
                    }, 300);
                }
            }

            // GOLDEN STANDARD: Error handling
            function handleSubmissionError(error) {
                console.error('❌ Quiz submission error:', error);
                
                // Reset submission state
                state.quizSubmitted = false;
                elements.form.querySelectorAll('input, textarea, button').forEach(input => input.disabled = false);
                
                const errorMessage = error.message || 'An error occurred while grading the quiz. Please try again.';
                const isNetworkError = error.name === 'TypeError' || errorMessage.includes('fetch');
                const isSessionError = errorMessage.includes('session expired') || errorMessage.includes('422');
                const isServerError = errorMessage.includes('500');
                
                if (isSessionError) {
                    showErrorDialog('⏰ Session Expired', 'Your quiz session has expired. Please start a new random quiz.', 'warning');
                } else if (isNetworkError) {
                    showErrorDialog('🌐 Connection Issue', 'There was a problem connecting to the server. Please check your connection and try again.', 'error', true);
                } else if (isServerError) {
                    showErrorDialog('🛠️ Server Error', 'The server encountered an issue. This is usually temporary.', 'error', true);
                } else {
                    showErrorDialog('⚠️ Grading Error', errorMessage, 'error', true);
                }
            }

            function showErrorDialog(title, message, icon, allowRetry = false) {
                const buttons = allowRetry ? {
                    showCancelButton: true,
                    confirmButtonText: 'Retry Submission',
                    cancelButtonText: 'Cancel'
                } : {
                    confirmButtonText: 'OK'
                };

<<<<<<< HEAD
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
=======
                Swal.fire({
                    title,
                    text: message,
                    icon,
                    ...buttons,
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed && allowRetry) {
                        submitQuiz();
                    }
                });
            }

            function showFallbackSuccessMessage(data) {
                const score = (data && data.score !== undefined) ? data.score : 'Unknown';
                const total = (data && (data.total || data.total_questions)) ? (data.total || data.total_questions) : 'Unknown';
                
                Swal.fire({
                    title: '🎯 Quiz Complete!',
                    html: `
                        <div style="font-size: 1.2em; margin: 20px 0;">
                            <strong>Score: ${score}/${total}</strong>
                        </div>
                        <p>Your quiz has been submitted and scored successfully!</p>
                    `,
                    icon: 'success',
                    confirmButtonText: 'Continue'
                });
            }
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd

            // GOLDEN STANDARD: Initialize everything
            initializeEventHandlers();
            initializeTimer();
            updateProgress();

            console.log('✅ Random Quiz fully initialized and ready');
        });

        // GOLDEN STANDARD: Service Worker registration for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            });
<<<<<<< HEAD

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
=======
>>>>>>> cursor/fix-random-quiz-mode-errors-fecd
        }
    </script>
</body>
</html> 