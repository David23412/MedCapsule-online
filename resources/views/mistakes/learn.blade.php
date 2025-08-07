<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Learn From Mistakes - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- 💀 NUCLEAR AUDIO PRELOADING SYSTEM - F16 RAPTOR SPEED 💀 -->
    <audio id="correctSound" preload="auto" volume="0.7">
        <source src="/sounds/Single-correct.mp3" type="audio/mpeg">
    </audio>
    <audio id="wrongSound" preload="auto" volume="0.6">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>
    <audio id="encouragementSound" preload="auto" volume="0.7">
        <source src="/sounds/encouragement.mp3" type="audio/mpeg">
    </audio>
    
    <!-- 💀 GRADING SOUNDS PRELOADING - INSTANT FINAL FEEDBACK 💀 -->
    <audio id="quizPerfectSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-perfect.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizPartialSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-partial.mp3" type="audio/mpeg">
    </audio>
    <audio id="quizWrongSound" preload="auto" volume="0.7">
        <source src="/sounds/quiz-wrong.mp3" type="audio/mpeg">
    </audio>
    
    <!-- 💀 INSTANT AUDIO PRELOADING SCRIPT 💀 -->
    <script>
        // NUCLEAR PRELOADING - RUNS IMMEDIATELY
        (function() {
            const audioUrls = [
                '/sounds/Single-correct.mp3',
                '/sounds/quiz-wrong.mp3',
                '/sounds/encouragement.mp3'
            ];
            
            // 💀 LETHAL AUDIO CONTEXT CREATION 💀
            let audioContext;
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                console.log('AudioContext not supported');
            }
            
            // Preload all audio files instantly with error handling
            audioUrls.forEach(url => {
                const audio = new Audio();
                audio.preload = 'auto';
                audio.src = url;
                
                // Handle 404 errors silently
                audio.addEventListener('error', function(e) {
                    console.log('Audio preload failed (404):', url);
                });
                
                // Log successful loads
                audio.addEventListener('canplaythrough', function() {
                    console.log('Audio preloaded successfully:', url);
                });
                
                audio.load();
            });
            
            // 💀 LETHAL AUDIO CONTEXT PRIMING 💀
            if (audioContext && audioContext.state === 'suspended') {
                document.addEventListener('click', function() {
                    audioContext.resume();
                }, { once: true });
            }
        })();
    </script>
    
    <!-- 💀 NUCLEAR SPEED OPTIMIZATIONS 💀 -->
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="dns-prefetch" href="{{ url('/') }}">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
            zoom: 0.80;
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        .navbar {
            background-color: white;
            padding: 0.75rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
            margin: 0 auto;
            height: 100%;
        }

        .navbar-brand {
            color: #007bff;
            font-weight: 600;
            font-size: 1.25rem;
            text-decoration: none;
        }

        .back-button {
            background: none;
            border: none;
            font-size: 1rem;
            color: #495057;
            cursor: pointer;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            border-radius: 6px;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transform: translateX(-5px);
            color: #007bff;
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

        .nav-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 80px auto 20px;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .difficulty-selector {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95%;
            max-width: 800px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #007bff;
            z-index: 1000;
        }

        .difficulty-title {
            font-size: 2.5em;
            color: #007bff;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .difficulty-options {
            display: flex;
            gap: 30px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .difficulty-option {
            padding: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 400px;
            background: white;
            text-align: center;
        }

        .difficulty-option:hover {
            border-color: #007bff;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.15);
        }

        .difficulty-option.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.2);
        }

        .difficulty-option h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 1.8em;
        }

        .difficulty-option p {
            margin: 0;
            color: #666;
            font-size: 1.1em;
            line-height: 1.5;
        }

        .start-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 1.2em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            font-weight: 500;
        }

        .start-button:hover:not(:disabled) {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        .start-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e0e0e0;
            padding: 30px;
            margin: 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: none;
            position: relative;
        }

        .card.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .question {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 25px;
            line-height: 1.5;
            font-weight: 500;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option {
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1em;
            background: white;
            color: #495057;
        }

        .option:hover {
            border-color: #007bff;
            transform: translateX(4px);
            background-color: #f8f9fa;
        }

        .option.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }

        .option.correct {
            border-color: #28a745;
            background-color: #e8f5e9;
            color: #28a745;
        }

        .option.incorrect {
            border-color: #dc3545;
            background-color: #fff5f5;
            color: #dc3545;
        }

        .stage-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .stage-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .stage-dot.active {
            background-color: #28a745;
            transform: scale(1.2);
        }

        .stage-dot.completed {
            background-color: #28a745;
        }

        .encouragement-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 1000;
            text-align: center;
            border: 2px solid #28a745;
        }

        .encouragement-message h2 {
            color: #28a745;
            margin-bottom: 10px;
            font-size: 1.8em;
            font-weight: 600;
        }

        .encouragement-message p {
            color: #495057;
            margin: 0;
            font-size: 1.2em;
        }

        /* Quiz-style popup styles */
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

        /* Ensure confetti appears above popup */
        canvas {
            z-index: 10001 !important;
            pointer-events: none;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
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
            z-index: 1001;
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
        }

        .time-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 2rem;
            border-radius: 16px;
            margin: 1.5rem 0;
            position: relative;
            overflow: hidden;
        }

        .popup-time {
            font-size: 4rem;
            font-weight: 800;
            color: #007bff;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 123, 255, 0.2);
        }

        .time-details {
            font-size: 1.2rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .popup-message {
            color: #495057;
            font-size: 1.2rem;
            margin: 1.5rem 0;
            line-height: 1.6;
        }

        .popup-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: nowrap;
            margin-top: 2rem;
        }

        .popup-btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .review-btn {
            background: #f8f9fa;
            color: #495057;
        }

        .review-btn:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            color: #495057;
            text-decoration: none;
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

        .grade-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .completion-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 20px 24px;
            border-radius: 12px;
            text-align: center;
            min-width: 120px;
            flex: 1;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-item h3 {
            font-size: 2.2em;
            margin: 0;
            font-weight: 700;
        }

        .stat-item p {
            color: #6c757d;
            margin: 5px 0 0;
            font-size: 0.9em;
            font-weight: 500;
        }

        .completion-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
        }

        .completion-button:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }

        .difficulty-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(5px);
            display: block;
        }

        /* Remove progress dots styles and update progress container */
        .progress-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            display: none;
            animation: slideDown 0.5s ease-out;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .progress-title {
            font-size: 1.1rem;
            color: #495057;
            font-weight: 500;
        }

        .progress-text {
            font-size: 1rem;
            color: #6c757d;
        }

        .progress-bar-container {
            height: 10px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            width: 0;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border-radius: 20px;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: shimmer 2s infinite;
        }

        /* Update animations */
        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Add responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                margin-top: 70px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .progress-container {
                margin: 15px auto;
                padding: 12px 15px;
            }
        }

        .question-image {
            max-width: 300px;
            max-height: 200px;
            margin: 15px auto;
            display: block;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Animation styles for the celebratory card */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes confetti {
            0% { transform: translateY(0) rotate(0); opacity: 1; }
            100% { transform: translateY(100px) rotate(360deg); opacity: 0; }
        }

        @keyframes buttonPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .done-message {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(148, 163, 184, 0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            margin-top: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .done-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            opacity: 0;
            z-index: 3000;
        }

        .confetti-1 { background: #3b82f6; top: 20%; left: 20%; animation: confetti 3s ease-out infinite; }
        .confetti-2 { background: #60a5fa; top: 25%; right: 25%; animation: confetti 2.5s ease-out infinite 0.2s; }
        .confetti-3 { background: #93c5fd; top: 35%; left: 35%; animation: confetti 3.5s ease-out infinite 0.4s; }
        .confetti-4 { background: #2563eb; top: 40%; right: 40%; animation: confetti 3s ease-out infinite 0.6s; }
        .confetti-5 { background: #1d4ed8; top: 45%; left: 45%; animation: confetti 2.8s ease-out infinite 0.8s; }

        .written-answer-container {
            margin-top: 1.5rem;
        }
        
        .written-answer {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 1rem;
        }
        
        .written-answer:focus {
            border-color: #4dabf7;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: 0;
        }
        

        
        .answer-hint {
            margin: 8px 0;
            padding: 8px;
            background-color: rgba(0, 123, 255, 0.05);
            border-left: 2px solid rgba(0, 123, 255, 0.3);
            border-radius: 4px;
        }
        
        .answer-hint i {
            color: #007bff;
            margin-right: 5px;
        }
        
        .submit-written-answer {
            margin-bottom: 1rem;
            display: block;
            width: 100%;
        }
        
        .written-answer-feedback {
            min-height: 20px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .written-answer-feedback.correct {
            color: #28a745;
            font-weight: 500;
            padding: 8px 12px;
            border-left: 3px solid #28a745;
            background-color: rgba(40, 167, 69, 0.05);
            border-radius: 4px;
        }
        
        .written-answer-feedback.incorrect {
            color: #dc3545;
            font-weight: 500;
            padding: 8px 12px;
            border-left: 3px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
            border-radius: 4px;
        }
        
        .written-answer-correct {
            background-color: rgba(227, 252, 239, 0.3);
            border: 1px solid rgba(0, 168, 84, 0.5);
            border-radius: 6px;
            padding: 10px;
            margin-top: 15px;
            color: #28a745;
            font-weight: 500;
        }

        /* Course filter styles */
        .course-filter {
            margin-bottom: 20px;
        }

        .form-select {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-select:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Skip button styles */
        .skip-question-btn:hover {
            color: #0056b3 !important;
            border-bottom-color: #0056b3 !important;
            transform: translateY(-1px);
        }

    </style>
</head>

<body>
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <nav class="navbar">
        <div class="container">
            <div class="nav-group">
                <a href="{{ route('review.index') }}" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Review
                </a>
                <a href="{{ route('home') }}" class="nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </div>
            @auth
            <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                @if(!Auth::user()->profile_picture_url)
                    {{ substr(Auth::user()->name, 0, 1) }}
                @endif
            </a>
            @endauth
        </div>
    </nav>

    <div class="container">

        @if($mistakes->isEmpty())
            <div class="done-message">
                <div class="confetti confetti-1"></div>
                <div class="confetti confetti-2"></div>
                <div class="confetti confetti-3"></div>
                <div class="confetti confetti-4"></div>
                <div class="confetti confetti-5"></div>
                <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 1.5rem; display: inline-flex; padding: 1.2rem; border-radius: 50%; background: #eff6ff; border: 2px solid #bfdbfe; animation: float 3s ease-in-out infinite;"></i>
                <h3 style="color: #1e293b; font-size: 1.75rem; font-weight: 600; margin-bottom: 1rem; letter-spacing: -0.02em; background: linear-gradient(135deg, #1e3a8a, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">You've Mastered All Mistakes!</h3>
                <p style="color: #64748b; font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">Your dedication to learning is impressive! Come back later for more practice.</p>
                <a href="{{ route('home') }}" class="btn btn-primary" style="background: #f0f9ff; color: #0284c7; padding: 0.6rem 1.4rem; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 0.95rem; border: 1px solid #bae6fd; transition: all 0.2s ease; display: inline-block; animation: buttonPulse 2s infinite;">Back to Home</a>
            </div>
        @else
            <!-- Add progress bar container -->
            <div class="progress-container">
                <div class="progress-header">
                    <div class="progress-title">Learning Progress</div>
                    <div class="progress-text">0 of 0 completed this session</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <div class="difficulty-overlay"></div>
            <div class="difficulty-selector">
                <h2 class="difficulty-title">Choose Your Difficulty Level</h2>
                
                <!-- Course filter dropdown -->
                <div class="course-filter mb-4">
                    <form action="{{ route('review.learn') }}" method="GET" id="filter-form">
                        <select id="course-filter" name="course" class="form-select">
                            @php
                                // Get total count from all mistakes (unfiltered)
                                $totalMistakesCount = App\Models\Mistake::where('user_id', auth()->id())->count();
                                $skippedCount = App\Models\Mistake::where('user_id', auth()->id())->where('skipped', true)->count();
                            @endphp
                            <option value="">All Courses ({{ $totalMistakesCount }})</option>
                            <option value="skipped" {{ ($courseFilter ?? '') == 'skipped' ? 'selected' : '' }}>
                                Skipped Questions ({{ $skippedCount }})
                            </option>
                            @foreach($courses ?? [] as $course)
                                @php
                                    $courseCount = App\Models\Mistake::where('user_id', auth()->id())
                                        ->whereHas('question.topic.course', function($q) use ($course) {
                                            $q->where('id', $course->id);
                                        })->count();
                                @endphp
                                <option value="{{ $course->id }}" {{ ($courseFilter ?? '') == $course->id ? 'selected' : '' }}>
                                    {{ $course->name }} ({{ $courseCount }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                
                <div class="difficulty-options">
                    <div class="difficulty-option" data-mode="practice">
                        <h3>Easy</h3>
                        <p>Master with 2 correct answers</p>
                    </div>
                    <div class="difficulty-option" data-mode="quiz">
                        <h3>Hard</h3>
                        <p>Master with 3 correct answers</p>
                    </div>
                </div>
                <button class="start-button" disabled>Start Learning</button>
            </div>

            @foreach($mistakes as $index => $mistake)
                <div class="card {{ $index === 0 ? 'active' : '' }}" 
                    data-question-id="{{ $mistake->question_id }}"
                    data-stage="{{ $mistake->stage }}"
                    data-course-id="{{ $mistake->question->topic->course_id ?? 'all' }}">
                    <!-- Course: {{ $mistake->question->topic->course->name ?? 'Unknown' }} -->

                    <div class="question">
                        @php
                            $questionText = is_string($mistake->question) ? $mistake->question : json_decode($mistake->question)->question;
                        @endphp
                        {{ $questionText }}
                        @if($mistake->question && $mistake->question->image_url)
                            <img src="{{ asset($mistake->question->image_url) }}" alt="Question Image" class="question-image">
                        @endif
                    </div>
                    
                    @if(isset($mistake->question->question_type) && $mistake->question->question_type === 'written')
                    <div class="written-answer-container">
                        <div class="form-group">
                            <textarea class="form-control written-answer" rows="4" placeholder="Type your answer here..."></textarea>

                        </div>
                        <button class="btn btn-primary submit-written-answer" data-question-id="{{ $mistake->question_id }}">
                            Submit Answer
                        </button>
                        <div class="written-answer-feedback"></div>
                    </div>
                    @else
                    <div class="options">
                        @foreach(['A', 'B', 'C', 'D'] as $option)
                            @php
                                $optionText = $mistake->question->{'option_' . strtolower($option)};
                                if (!is_string($optionText)) {
                                    $optionText = json_decode($optionText);
                                }
                            @endphp
                            <div class="option" 
                                data-option="{{ $option }}"
                                data-is-correct="{{ $option === $mistake->question->correct_answer ? 'true' : 'false' }}">
                                {{ $optionText }}
                            </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="stage-indicator" data-mode="practice">
                        <!-- Stage dots will be dynamically added by JavaScript -->
                    </div>
                    
                    <!-- Skip Button -->
                    <div class="skip-button-container" style="position: absolute; bottom: 15px; right: 15px;">
                        <button class="skip-question-btn" data-question-id="{{ $mistake->question_id }}" 
                                style="background: transparent; color: #007bff; border: none; border-bottom: 2px solid #007bff; padding: 4px 8px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">
                            >> skip
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="encouragement-message">
        <h2>Great job! Keep going! 🎉</h2>
        <p>You're making great progress!</p>
    </div>

    <!-- Quiz-style completion popup -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup" id="popup">
            <div class="grade-icon" id="grade-icon">🎉</div>
            <h2 class="popup-title" id="popup-title">Session Complete!</h2>
            <div class="time-container">
                <div class="popup-time" id="popup-time">0:00</div>
                <div class="time-details">Study Time</div>
            </div>
            <p class="popup-message" id="popup-message">Great work! You're making excellent progress!</p>
            <div class="popup-buttons">
                <a href="{{ route('review.index') }}" class="popup-btn review-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Review
                </a>
                <button class="popup-btn retry-btn" id="continue-learning">
                    <i class="fas fa-redo"></i>
                    Continue Learning
                </button>
            </div>
        </div>
    </div>

    <!-- 💀 AUDIO ELEMENTS MOVED TO HEAD FOR NUCLEAR PRELOADING 💀 -->

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const difficultyOptions = document.querySelectorAll('.difficulty-option');
            const startButton = document.querySelector('.start-button');
            const difficultySelector = document.querySelector('.difficulty-selector');
            const difficultyOverlay = document.querySelector('.difficulty-overlay');
            const progressContainer = document.querySelector('.progress-container');
            const correctSound = document.getElementById('correctSound');
            const wrongSound = document.getElementById('wrongSound');
            const encouragementSound = document.getElementById('encouragementSound');
            const courseFilter = document.getElementById('course-filter');
            
            // 💀 LETHAL AUDIO POOL SYSTEM - MULTIPLE READY AUDIO ELEMENTS 💀
            const audioPool = {
                correct: [],
                wrong: [],
                encouragement: []
            };
            
            // Create multiple audio elements for instant playback
            function createAudioPool() {
                // Create 10 ready-to-play audio elements for each sound (DOUBLED FOR LETHAL SPEED)
                for (let i = 0; i < 10; i++) {
                    // Correct sound pool
                    const correctAudio = new Audio('/sounds/Single-correct.mp3');
                    correctAudio.volume = 0.7;
                    correctAudio.preload = 'auto';
                    audioPool.correct.push(correctAudio);
                    
                    // Wrong sound pool
                    const wrongAudio = new Audio('/sounds/quiz-wrong.mp3');
                    wrongAudio.volume = 0.6;
                    wrongAudio.preload = 'auto';
                    audioPool.wrong.push(wrongAudio);
                    
                    // Encouragement sound pool
                    const encouragementAudio = new Audio('/sounds/encouragement.mp3');
                    encouragementAudio.volume = 0.7;
                    encouragementAudio.preload = 'auto';
                    audioPool.encouragement.push(encouragementAudio);
                }
            }
            
            // Initialize the audio pool
            createAudioPool();
            
            // 💀 LETHAL AUDIO POOL REPLENISHMENT SYSTEM 💀
            function replenishAudioPool() {
                // Replenish correct audio pool if running low
                if (audioPool.correct.length < 5) {
                    for (let i = 0; i < 3; i++) {
                        const correctAudio = new Audio('/sounds/Single-correct.mp3');
                        correctAudio.volume = 0.7;
                        correctAudio.preload = 'auto';
                        audioPool.correct.push(correctAudio);
                    }
                }
                
                // Replenish wrong audio pool if running low
                if (audioPool.wrong.length < 5) {
                    for (let i = 0; i < 3; i++) {
                        const wrongAudio = new Audio('/sounds/quiz-wrong.mp3');
                        wrongAudio.volume = 0.6;
                        wrongAudio.preload = 'auto';
                        audioPool.wrong.push(wrongAudio);
                    }
                }
                
                // Replenish encouragement audio pool if running low
                if (audioPool.encouragement.length < 5) {
                    for (let i = 0; i < 3; i++) {
                        const encouragementAudio = new Audio('/sounds/encouragement.mp3');
                        encouragementAudio.volume = 0.7;
                        encouragementAudio.preload = 'auto';
                        audioPool.encouragement.push(encouragementAudio);
                    }
                }
            }
            
            // Replenish pool every 5 seconds to ensure we never run out
            setInterval(replenishAudioPool, 5000);
            
            // 💀 LETHAL AUDIO POOL MONITORING - PREVENTS RUNNING OUT 💀
            setInterval(() => {
                console.log('🔍 AUDIO POOL STATUS:', {
                    correct: audioPool.correct.length,
                    wrong: audioPool.wrong.length,
                    encouragement: audioPool.encouragement.length
                });
                
                // Force replenish if pools are getting low
                if (audioPool.correct.length < 3) {
                    console.log('⚠️ CORRECT POOL LOW - FORCE REPLENISHING');
                    for (let i = 0; i < 5; i++) {
                        const correctAudio = new Audio('/sounds/Single-correct.mp3');
                        correctAudio.volume = 0.7;
                        correctAudio.preload = 'auto';
                        audioPool.correct.push(correctAudio);
                    }
                }
                
                if (audioPool.wrong.length < 3) {
                    console.log('⚠️ WRONG POOL LOW - FORCE REPLENISHING');
                    for (let i = 0; i < 5; i++) {
                        const wrongAudio = new Audio('/sounds/quiz-wrong.mp3');
                        wrongAudio.volume = 0.6;
                        wrongAudio.preload = 'auto';
                        audioPool.wrong.push(wrongAudio);
                    }
                }
            }, 2000); // Check every 2 seconds
            
            // Simple skip tracking
            let skippedQuestions = new Set();

            // 💀 LETHAL F16 RAPTOR SOUND SYSTEM - INSTANT FEEDBACK 💀
            const soundSystem = {
                playSound: function(audioElement) {
                    if (!audioElement) return;
                    
                    // Check if audio is ready or has errors
                    if (audioElement.readyState < 2 || audioElement.error) {
                        console.log('Audio not ready, skipping playback');
                        return;
                    }
                    
                    try {
                        // NUCLEAR SPEED: Clone and play for instant response
                        const clone = audioElement.cloneNode();
                        clone.volume = audioElement.volume;
                        
                        // Play the clone immediately
                        const playPromise = clone.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(() => {
                                // Fallback: try original element
                                audioElement.currentTime = 0;
                                audioElement.play().catch(() => {
                                    // Final fallback: try again after micro delay
                                    setTimeout(() => {
                                        audioElement.play().catch(() => {});
                                    }, 5);
                                });
                            });
                        }
                        
                        // Clean up clone after playback
                        clone.addEventListener('ended', () => {
                            clone.remove();
                        });
                    } catch (e) {
                        // Silent fail for maximum speed
                    }
                },
                
                playCorrect: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        console.log('🔇 AUDIO BLOCKED - Quiz not started yet');
                        return;
                    }
                    this.playSound(correctSound);
                },
                
                playWrong: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        console.log('🔇 AUDIO BLOCKED - Quiz not started yet');
                        return;
                    }
                    this.playSound(wrongSound);
                },
                
                playEncouragement: function() {
                    // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                    if (!quizStarted) {
                        console.log('🔇 AUDIO BLOCKED - Quiz not started yet');
                        return;
                    }
                    this.playSound(encouragementSound);
                }
            };

            // 💀 NUCLEAR AUDIO PRELOADING SYSTEM - F16 RAPTOR SPEED 💀
            const audioElements = [correctSound, wrongSound, encouragementSound];
            
            // Force preload all audio files immediately
            audioElements.forEach(sound => {
                if (sound) {
                    // Set volume and preload
                    sound.volume = 0.7;
                    sound.preload = 'auto';
                    
                    // Add error handling for 404s
                    sound.addEventListener('error', function(e) {
                        console.log('Audio file not found (404):', sound.src);
                    });
                    
                    // Force load the audio
                    sound.load();
                    
                    // 💀 NO AUDIO PRIMING - COMPLETELY SILENT 💀
                    // Just load the audio without playing it
                    sound.load();
                }
            });
            
            // 💀 NO AUDIO PRIMING - COMPLETELY SILENT 💀
            // Audio will be primed only when first needed during actual gameplay
            
            // 💀 SILENT FALLBACK FOR MISSING AUDIO FILES 💀
            // Create silent audio elements if the original ones fail to load
            setTimeout(() => {
                audioElements.forEach((sound, index) => {
                    if (sound && sound.error) {
                        console.log('Creating silent fallback for audio:', index);
                        // Create a silent audio element as fallback
                        const silentAudio = new Audio();
                        silentAudio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT';
                        silentAudio.volume = 0;
                        silentAudio.id = sound.id;
                        sound.parentNode.replaceChild(silentAudio, sound);
                    }
                });
            }, 2000);

            // Initialize questions data and mode-specific settings
            const MODE_SETTINGS = {
                practice: {
                    requiredCorrect: 2,
                    totalStages: 2
                },
                quiz: {
                    requiredCorrect: 3,
                    totalStages: 3
                }
            };

            let selectedMode = null;
            let questionsData = [];
            let currentQuestionIndex = 0;
            let activeQuestions = new Set();
            let totalAttempts = 0;
            let correctFirstAttempts = 0;
            let startTime = 0;
            let quizStarted = false; // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀

            // Initialize questions data from the cards
            document.querySelectorAll('.card').forEach(card => {
                const questionData = {
                    element: card,
                    id: card.dataset.questionId,
                    correctStreak: 0,
                    courseId: card.dataset.courseId || 'all',
                    isActive: true
                };
                questionsData.push(questionData);
                activeQuestions.add(questionData.id);
                // Hide all cards initially
                card.classList.remove('active');
            });



            // Setup continue learning button
            document.getElementById('continue-learning').addEventListener('click', function() {
                // Hide popup
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                
                popup.classList.remove('show');
                setTimeout(() => {
                    popupOverlay.classList.remove('show');
                }, 300);
                
                // Reset tracking variables
                totalAttempts = 0;
                correctFirstAttempts = 0;
                startTime = Date.now();
                
                // Reset all questions data for a new session
                questionsData.forEach(q => {
                    q.correctStreak = 0;
                    q.isActive = true;
                });
                
                currentQuestionIndex = 0;
                activeQuestions.clear();
                questionsData.forEach(q => activeQuestions.add(q.id));
                
                // Make sure all cards are hidden
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('active');
                });
                
                // Show the first question
                showNextQuestion();
                updateStageIndicator();
                
                // Reset progress
                updateProgress();
            });

            // Setup submit button for written answers
            document.querySelectorAll('.submit-written-answer').forEach(button => {
                button.addEventListener('click', function() {
                    const container = this.closest('.written-answer-container');
                    const textarea = container.querySelector('.written-answer');
                    const feedbackElement = container.querySelector('.written-answer-feedback');
                    const questionId = this.dataset.questionId;
                    const submittedAnswer = textarea.value.trim();
                    
                    if (submittedAnswer === '') {
                        feedbackElement.textContent = 'Please enter an answer.';
                        feedbackElement.className = 'written-answer-feedback incorrect';
                        return;
                    }
                    
                    // Disable input while processing
                    textarea.disabled = true;
                    this.disabled = true;
                    
                    // Check the answer on the server
                    fetch(`/learn/review/${questionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            selected_answer: submittedAnswer,
                            mode: selectedMode
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Use the is_correct flag from the server response instead of parsing the message
                            const isCorrect = data.is_correct === true;
                            
                            // Debug logging to help track any future issues
                            console.log('Answer evaluation result:', {
                                isCorrect: isCorrect,
                                answer: submittedAnswer.substring(0, 50) + (submittedAnswer.length > 50 ? '...' : ''),
                                answerLength: submittedAnswer.length,
                                message: data.message,
                                questionId: questionId
                            });
                            
                            // Track total attempts
                            totalAttempts++;
                            
                            if (isCorrect) {
                                // Play correct sound
                                soundSystem.playCorrect();
                                
                                // Show correct feedback
                                feedbackElement.textContent = 'Correct! Well done.';
                                feedbackElement.className = 'written-answer-feedback correct';
                                
                                // For accuracy calculation - only count as correct first attempt
                                // if this is the first attempt for the current question
                                if (questionsData[currentQuestionIndex].correctStreak === 0) {
                                    correctFirstAttempts++;
                                }
                                
                                // Increment streak for this question
                                questionsData[currentQuestionIndex].correctStreak++;
                                
                                // Check if question is mastered based on mode requirements
                                if (questionsData[currentQuestionIndex].correctStreak >= MODE_SETTINGS[selectedMode].requiredCorrect) {
                                    questionsData[currentQuestionIndex].isActive = false;
                                    activeQuestions.delete(questionsData[currentQuestionIndex].id);
                                    
                                    // Only show encouragement message when mastered
                                    const encouragementMessage = document.querySelector('.encouragement-message');
                                    encouragementMessage.style.display = 'block';
                                    setTimeout(() => {
                                        encouragementMessage.style.display = 'none';
                                    }, 1000);
                                }
                            } else {
                                // Play wrong sound
                                soundSystem.playWrong();
                                
                                // Show incorrect feedback with the specific message from backend
                                feedbackElement.textContent = data.message || 'Incorrect. Try again!';
                                feedbackElement.className = 'written-answer-feedback incorrect';
                                
                                // Show the correct answer if provided in the response
                                if (data.correct_answer) {
                                    // Create an element to show the correct answer
                                    const correctAnswerDiv = document.createElement('div');
                                    correctAnswerDiv.className = 'written-answer-correct';
                                    correctAnswerDiv.innerHTML = `<strong>Correct Answer:</strong> ${data.correct_answer}`;
                                    correctAnswerDiv.style.marginTop = '10px';
                                    correctAnswerDiv.style.padding = '8px';
                                    correctAnswerDiv.style.backgroundColor = 'rgba(227, 252, 239, 0.3)';
                                    correctAnswerDiv.style.border = '1px solid rgba(0, 168, 84, 0.5)';
                                    correctAnswerDiv.style.borderRadius = '6px';
                                    correctAnswerDiv.style.color = '#28a745';
                                    
                                    // Add the correct answer below the feedback
                                    feedbackElement.appendChild(correctAnswerDiv);
                                }
                                
                                // Reset streak on wrong answer
                                questionsData[currentQuestionIndex].correctStreak = 0;
                            }
                            
                            // Update stage indicator
                            updateStageIndicator();
                            
                            // Update progress
                            updateProgress();
                            
                            // Move to next question after a short delay
                            setTimeout(() => {
                                // Reset the form
                                textarea.value = '';
                                textarea.disabled = false;
                                this.disabled = false;
                                
                                // Clear all feedback elements
                                feedbackElement.textContent = '';
                                feedbackElement.className = 'written-answer-feedback';
                                
                                // Remove any correct answer divs
                                const correctAnswerDiv = feedbackElement.querySelector('.written-answer-correct');
                                if (correctAnswerDiv) {
                                    feedbackElement.removeChild(correctAnswerDiv);
                                }
                                
                                // Move to next question
                                currentQuestionIndex = (currentQuestionIndex + 1) % questionsData.length;
                                showNextQuestion();
                                updateStageIndicator();
                            }, 1500);
                        } else {
                            console.error('Error checking answer:', data.message);
                            feedbackElement.textContent = 'Error checking answer. Please try again.';
                            feedbackElement.className = 'written-answer-feedback incorrect';
                            
                            // Re-enable the form
                            textarea.disabled = false;
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        feedbackElement.textContent = 'Error checking answer. Please try again.';
                        feedbackElement.className = 'written-answer-feedback incorrect';
                        
                        // Re-enable the form
                        textarea.disabled = false;
                        this.disabled = false;
                    });
                });
            });

            function updateStageIndicator() {
                const stageIndicator = document.querySelector('.card.active .stage-indicator');
                if (!stageIndicator || !selectedMode) return;

                const currentQuestion = questionsData[currentQuestionIndex];
                const modeConfig = MODE_SETTINGS[selectedMode];
                
                // Clear existing dots
                stageIndicator.innerHTML = '';
                
                // Add dots based on the mode's total stages
                for (let i = 0; i < modeConfig.totalStages; i++) {
                    const dot = document.createElement('div');
                    dot.className = 'stage-dot';
                    if (i < currentQuestion.correctStreak) {
                        dot.classList.add('completed');
                    }
                    stageIndicator.appendChild(dot);
                }
            }

            // Handle difficulty selection
            difficultyOptions.forEach(option => {
                option.addEventListener('click', function() {
                    difficultyOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedMode = this.dataset.mode;
                    startButton.disabled = false;
                });
            });

            // Handle start button click
            startButton.addEventListener('click', function() {
                if (!selectedMode) return;
                
                // 💀 BULLETPROOF AUDIO CONTROL - ENABLE SOUNDS ONLY AFTER QUIZ STARTS 💀
                quizStarted = true;
                console.log('🔥 QUIZ STARTED - AUDIO SYSTEM NOW ACTIVE');
                
                // Initialize start time when learning begins
                    startTime = Date.now();
                    
                // Hide difficulty selector and overlay
                    difficultySelector.style.display = 'none';
                    difficultyOverlay.style.display = 'none';
                    
                // Show progress container
                    progressContainer.style.display = 'block';
                    
                    // Initialize the first question
                    currentQuestionIndex = -1; // Set to -1 so showNextQuestion will start at 0
                    showNextQuestion();
            });

            // 💀 LETHAL INSTANT SOUND SYSTEM - F16 RAPTOR SPEED 💀
            document.querySelectorAll('.option').forEach(option => {
                option.addEventListener('click', function(e) {
                    if (!this.parentElement.classList.contains('answered')) {
                        // 💀 LETHAL INSTANT SOUND PLAYBACK - NUCLEAR SPEED 💀
                        const isCorrect = this.dataset.isCorrect === "true";
                        
                        // 💀 BULLETPROOF AUDIO CONTROL - NO SOUNDS UNTIL QUIZ STARTS 💀
                        if (!quizStarted) {
                            console.log('🔇 AUDIO BLOCKED - Quiz not started yet');
                            return;
                        }
                        
                        // 💀 NUCLEAR INSTANT SOUND - PLAYS IMMEDIATELY 💀
                        const soundStart = performance.now();
                        
                        // 💀 LETHAL DIAGNOSTIC - CHECK AUDIO STATE 💀
                        console.log('🔍 AUDIO DIAGNOSTIC:', {
                            correctPoolSize: audioPool.correct.length,
                            wrongPoolSize: audioPool.wrong.length,
                            correctAudioReady: document.getElementById('correctSound')?.readyState,
                            wrongAudioReady: document.getElementById('wrongSound')?.readyState,
                            quizStarted: quizStarted
                        });
                        
                        if (isCorrect) {
                            // 💀 INSTANT CORRECT SOUND - LETHAL MULTI-LAYER SYSTEM 💀
                            let soundPlayed = false;
                            
                            // Layer 1: Fresh audio (ALWAYS WORKS)
                            try {
                                const freshAudio = new Audio('/sounds/Single-correct.mp3');
                                freshAudio.volume = 0.7;
                                freshAudio.preload = 'auto';
                                
                                // Force play with multiple attempts
                                const playPromise = freshAudio.play();
                                if (playPromise !== undefined) {
                                    playPromise.then(() => {
                                        soundPlayed = true;
                                        console.log('✅ FRESH CORRECT SOUND PLAYED SUCCESSFULLY');
                                    }).catch(() => {
                                        console.log('❌ FRESH CORRECT SOUND FAILED, TRYING FALLBACK');
                                        // Layer 2: Audio pool fallback
                                        if (audioPool.correct.length > 0) {
                                            const readyAudio = audioPool.correct.shift();
                                            readyAudio.currentTime = 0;
                                            readyAudio.play().catch(() => {
                                                console.log('❌ AUDIO POOL FAILED, TRYING PRELOADED');
                                                // Layer 3: Preloaded element fallback
                                                const correctAudio = document.getElementById('correctSound');
                                                if (correctAudio) {
                                                    correctAudio.currentTime = 0;
                                                    correctAudio.play().catch(() => {
                                                        console.log('❌ ALL CORRECT SOUND METHODS FAILED');
                                                    });
                                                }
                                            });
                                            readyAudio.addEventListener('ended', () => {
                                                audioPool.correct.push(readyAudio);
                                            }, { once: true });
                                        }
                                    });
                                }
                            } catch (error) {
                                console.log('❌ FRESH AUDIO CREATION FAILED:', error);
                            }
                        } else {
                            // 💀 INSTANT WRONG SOUND - LETHAL MULTI-LAYER SYSTEM 💀
                            let soundPlayed = false;
                            
                            // Layer 1: Fresh audio (ALWAYS WORKS)
                            try {
                                const freshAudio = new Audio('/sounds/quiz-wrong.mp3');
                                freshAudio.volume = 0.6;
                                freshAudio.preload = 'auto';
                                
                                // Force play with multiple attempts
                                const playPromise = freshAudio.play();
                                if (playPromise !== undefined) {
                                    playPromise.then(() => {
                                        soundPlayed = true;
                                        console.log('✅ FRESH WRONG SOUND PLAYED SUCCESSFULLY');
                                    }).catch(() => {
                                        console.log('❌ FRESH WRONG SOUND FAILED, TRYING FALLBACK');
                                        // Layer 2: Audio pool fallback
                                        if (audioPool.wrong.length > 0) {
                                            const readyAudio = audioPool.wrong.shift();
                                            readyAudio.currentTime = 0;
                                            readyAudio.play().catch(() => {
                                                console.log('❌ AUDIO POOL FAILED, TRYING PRELOADED');
                                                // Layer 3: Preloaded element fallback
                                                const wrongAudio = document.getElementById('wrongSound');
                                                if (wrongAudio) {
                                                    wrongAudio.currentTime = 0;
                                                    wrongAudio.play().catch(() => {
                                                        console.log('❌ ALL WRONG SOUND METHODS FAILED');
                                                    });
                                                }
                                            });
                                            readyAudio.addEventListener('ended', () => {
                                                audioPool.wrong.push(readyAudio);
                                            }, { once: true });
                                        }
                                    });
                                }
                            } catch (error) {
                                console.log('❌ FRESH AUDIO CREATION FAILED:', error);
                            }
                        }
                        
                        console.log(`🎵 INSTANT SOUND TRIGGERED in ${(performance.now() - soundStart).toFixed(2)}ms`);
                        
                        // Mark options container as answered
                        this.parentElement.classList.add('answered');
                        
                        const currentQuestion = questionsData[currentQuestionIndex];
                        
                        // Mark selected option
                        this.classList.add(isCorrect ? 'correct' : 'incorrect');
                        
                        // Track total attempts
                        totalAttempts++;
                        
                        if (isCorrect) {
                            // Increment streak for this question
                            currentQuestion.correctStreak++;
                            
                            // Check if question has reached mastery based on mode requirements
                            if (currentQuestion.correctStreak >= MODE_SETTINGS[selectedMode].requiredCorrect) {
                                // Remove from active questions
                                activeQuestions.delete(currentQuestion.id);
                                
                                // Show encouragement message
                                const encouragementMessage = document.querySelector('.encouragement-message');
                                encouragementMessage.style.display = 'block';
                                setTimeout(() => {
                                    encouragementMessage.style.display = 'none';
                                }, 1000);
                            }

                            // Update stage indicator
                            updateStageIndicator();
                        } else {
                            // Reset streak on wrong answer
                            currentQuestion.correctStreak = 0;
                            // Update stage indicator to show reset
                            updateStageIndicator();
                        }

                        // Update progress
                        updateProgress();

                        // Move to next question after a short delay
                        setTimeout(() => {
                            // Reset the answered state
                            this.parentElement.classList.remove('answered');
                            this.classList.remove('correct', 'incorrect');

                            // Show next question
                            showNextQuestion();
                        }, 800);
                    }
                });
            });

            // Simple skip button handler
            document.querySelectorAll('.skip-question-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const questionId = this.dataset.questionId;
                    const currentQuestion = questionsData[currentQuestionIndex];
                    
                    // Simple API call to mark as skipped
                    fetch(`/review/skip/${questionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ Question skipped successfully');
                        } else {
                            console.warn('⚠️ Skip failed:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error skipping question:', error);
                    });
                    
                    // Always skip locally for this session
                    skippedQuestions.add(questionId);
                    activeQuestions.delete(currentQuestion.id);
                    updateProgress();
                    showNextQuestion();
                });
            });

            function showNextQuestion() {
                if (questionsData.length === 0) return;

                // Check if all questions have been mastered (streak of 2)
                if (activeQuestions.size === 0) {
                    showCompletion();
                    return;
                }

                // Find the next active question
                let nextQuestionFound = false;
                let originalIndex = currentQuestionIndex;
                let nextIndex = currentQuestionIndex;

                do {
                    nextIndex = (nextIndex + 1) % questionsData.length;
                    if (activeQuestions.has(questionsData[nextIndex].id)) {
                        nextQuestionFound = true;
                        break;
                    }
                    // If we've checked all questions and found none active
                    if (nextIndex === originalIndex) {
                        // If there are no active questions left, show completion
                        showCompletion();
                        return;
                    }
                } while (!nextQuestionFound);

                // Update current question index to the next active question
                currentQuestionIndex = nextIndex;

                // Hide ALL cards in the DOM
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('active');
                });

                // Show only the current question
                questionsData[currentQuestionIndex].element.classList.add('active');
                
                // Update progress
                updateProgress();
                updateStageIndicator();
            }

            function updateProgress() {
                const total = questionsData.length;
                const completed = questionsData.length - activeQuestions.size;
                const progressText = document.querySelector('.progress-text');
                const progressFill = document.querySelector('.progress-fill');
                
                progressText.textContent = `${completed} of ${total} mastered this session`;
                progressFill.style.width = `${(completed / total) * 100}%`;
            }

            function showCompletion() {
                // 💀 NUCLEAR INSTANT GRADING SOUND - PLAYS IMMEDIATELY BEFORE ANYTHING ELSE 💀
                const soundStart = performance.now();
                
                // Calculate completion percentage for sound selection
                const totalQuestions = questionsData.length;
                const masteredQuestions = questionsData.length - activeQuestions.size;
                const completionPercentage = totalQuestions > 0 ? (masteredQuestions / totalQuestions) * 100 : 0;
                
                // 💀 LETHAL FINAL GRADING SOUND - ENCOURAGEMENT FOR LEARN MISTAKES 💀
                if (quizStarted) {
                    console.log('🎯 FINAL GRADING SOUND TRIGGERED - LEARN MISTAKES COMPLETION!');
                    
                    // 💀 LEARN MISTAKES = ENCOURAGEMENT SOUND 💀
                    // This is specifically designed for learn mistakes system
                    console.log('🎉 LEARN MISTAKES MASTERY - PLAYING ENCOURAGEMENT SOUND');
                    
                    // 💀 LETHAL DIRECT ENCOURAGEMENT SOUND SYSTEM 💀
                    let soundPlayed = false;
                    
                    // Layer 1: Fresh encouragement audio (ALWAYS WORKS)
                    try {
                        const freshAudio = new Audio('/sounds/encouragement.mp3');
                        freshAudio.volume = 0.7;
                        freshAudio.preload = 'auto';
                        
                        // Force play with multiple attempts
                        const playPromise = freshAudio.play();
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                soundPlayed = true;
                                console.log('✅ FRESH ENCOURAGEMENT SOUND PLAYED SUCCESSFULLY');
                            }).catch(() => {
                                console.log('❌ FRESH ENCOURAGEMENT SOUND FAILED, TRYING FALLBACK');
                                // Layer 2: Preloaded encouragement element fallback
                                const encouragementAudio = document.getElementById('encouragementSound');
                                if (encouragementAudio) {
                                    encouragementAudio.currentTime = 0;
                                    encouragementAudio.volume = 0.7;
                                    encouragementAudio.play().catch(() => {
                                        console.log('❌ ALL ENCOURAGEMENT SOUND METHODS FAILED');
                                    });
                                }
                            });
                        }
                    } catch (error) {
                        console.log('❌ FRESH ENCOURAGEMENT AUDIO CREATION FAILED:', error);
                    }
                    
                    console.log(`🎵 ENCOURAGEMENT SOUND TRIGGERED in ${(performance.now() - soundStart).toFixed(2)}ms`);
                }
                
                const popupOverlay = document.getElementById('popup-overlay');
                const popup = document.getElementById('popup');
                const popupTitle = document.getElementById('popup-title');
                const popupScore = document.getElementById('popup-score');
                const popupMessage = document.getElementById('popup-message');
                const gradeIcon = document.getElementById('grade-icon');
                
                // Calculate stats
                const timeSpentValue = Math.round((Date.now() - startTime) / 1000);
                
                // Calculate and display study time
                const studyTimeSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(studyTimeSeconds / 60);
                const seconds = studyTimeSeconds % 60;
                const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                // Update popup content
                popupTitle.textContent = 'Session Complete!';
                document.getElementById('popup-time').textContent = timeString;
                
                // Always show celebration message
                gradeIcon.innerHTML = '🎉';
                popupMessage.textContent = 'Great work! You\'re making excellent progress!';
                
                // Save the study time to the database
                fetch('{{ secure_url("/learn/save-time") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        study_time: timeSpentValue
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to save study time:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving study time:', error);
                });
                
                // Show popup with animation
                popupOverlay.classList.add('show');
                setTimeout(() => {
                    popup.classList.add('show');
                    
                    // Dead center confetti - no calculations, just center
                    confetti({
                        particleCount: 150,
                        spread: 60,
                        origin: { x: 0.5, y: 0.5 },
                        colors: ['#007bff', '#0056b3', '#3b82f6', '#60a5fa'],
                        ticks: 400,
                        disableForReducedMotion: true,
                        scalar: 1.0,
                        gravity: 0.6,
                        drift: 0,
                        zIndex: 10000
                    });
                }, 100);
            }

            // Update completion message styling
            const completionMessage = document.querySelector('.completion-message');
            if (completionMessage) {
                completionMessage.style.background = 'white';
                completionMessage.style.padding = '2rem';
                completionMessage.style.borderRadius = '12px';
                completionMessage.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                completionMessage.style.maxWidth = '500px';
                completionMessage.style.width = '90%';
                completionMessage.style.textAlign = 'center';
                completionMessage.style.position = 'fixed';
                completionMessage.style.top = '50%';
                completionMessage.style.left = '50%';
                completionMessage.style.transform = 'translate(-50%, -50%)';
                
                // Update the title styling
                const title = completionMessage.querySelector('h1');
                if (title) {
                    title.style.fontSize = '1.8rem';
                    title.style.fontWeight = '600';
                    title.style.color = '#1a1a1a';
                    title.style.marginBottom = '1.5rem';
                    title.innerHTML = 'Session Complete! 🎉';
                }
                
                // Update the stats styling
                const statsContainer = completionMessage.querySelector('.completion-stats');
                if (statsContainer) {
                    statsContainer.style.display = 'grid';
                    statsContainer.style.gridTemplateColumns = 'repeat(2, 1fr)'; // Changed to 2 columns
                    statsContainer.style.gap = '1rem';
                    statsContainer.style.margin = '1.5rem 0';
                    
                    // Update individual stat items
                    statsContainer.querySelectorAll('.stat-item').forEach(item => {
                        // Remove accuracy rate item
                        if (item.querySelector('p').textContent.trim() === 'Accuracy Rate') {
                            item.remove();
                            return;
                        }
                        
                        item.style.background = '#f8f9fa';
                        item.style.padding = '1rem';
                        item.style.borderRadius = '8px';
                        item.style.border = '1px solid #e9ecef';
                        
                        const statTitle = item.querySelector('h3');
                        if (statTitle) {
                            statTitle.style.fontSize = '1.5rem';
                            statTitle.style.fontWeight = '600';
                            statTitle.style.color = '#007bff';
                            statTitle.style.margin = '0 0 0.5rem 0';
                        }
                        
                        const statLabel = item.querySelector('p');
                        if (statLabel) {
                            statLabel.style.fontSize = '0.875rem';
                            statLabel.style.color = '#6c757d';
                            statLabel.style.margin = '0';
                        }
                    });
                }
                
                // Update buttons styling
                const buttons = completionMessage.querySelectorAll('.completion-button');
                buttons.forEach(button => {
                    button.style.background = '#007bff';
                    button.style.color = 'white';
                    button.style.border = 'none';
                    button.style.padding = '0.75rem 1.5rem';
                    button.style.borderRadius = '6px';
                    button.style.fontSize = '0.95rem';
                    button.style.fontWeight = '500';
                    button.style.textDecoration = 'none';
                    button.style.transition = 'all 0.2s ease';
                    button.style.display = 'inline-block';
                    button.style.margin = '0 0.5rem';
                    
                    button.addEventListener('mouseover', () => {
                        button.style.background = '#0056b3';
                        button.style.transform = 'translateY(-1px)';
                    });
                    
                    button.addEventListener('mouseout', () => {
                        button.style.background = '#007bff';
                        button.style.transform = 'translateY(0)';
                    });
                });
            }

            // BULLETPROOF SMOOTH FILTER - WORKS EVERY FUCKING TIME
            if (courseFilter) {
                console.log('🔥 BULLETPROOF FILTER INITIALIZED');
                
                // Store original state for restoration
                const originalOpacity = courseFilter.style.opacity || '1';
                const originalCursor = courseFilter.style.cursor || 'pointer';
                
                const handleFilterChange = function(e) {
                    const selectedValue = this.value;
                    console.log('🎯 FILTER CHANGE:', selectedValue);
                    
                    // DON'T disable the element - just provide visual feedback
                    this.style.opacity = '0.8';
                    this.style.cursor = 'wait';
                    
                    // Create a temporary loading option
                    const loadingOption = document.createElement('option');
                    loadingOption.value = '';
                    loadingOption.textContent = 'Loading...';
                    loadingOption.selected = true;
                    this.appendChild(loadingOption);
                    
                    // Use setTimeout to ensure smooth UI update before navigation
                    setTimeout(() => {
                        // Build the URL manually for instant navigation
                        const baseUrl = '{{ route("review.learn") }}';
                        const newUrl = selectedValue ? `${baseUrl}?course=${encodeURIComponent(selectedValue)}` : baseUrl;
                        
                        console.log('🚀 NAVIGATING TO:', newUrl);
                        window.location.href = newUrl;
                    }, 50); // Tiny delay for smooth UI feedback
                };
                
                // Single, clean event listener
                courseFilter.addEventListener('change', handleFilterChange);
                
                // Keyboard support
                courseFilter.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleFilterChange.call(this, e);
                    }
                });
                
                // Restore state on focus (in case something went wrong)
                courseFilter.addEventListener('focus', function() {
                    this.style.opacity = originalOpacity;
                    this.style.cursor = originalCursor;
                    this.disabled = false;
                    
                    // Remove any loading options
                    const loadingOptions = this.querySelectorAll('option[value=""]:not([data-original])');
                    loadingOptions.forEach(opt => {
                        if (opt.textContent === 'Loading...') {
                            opt.remove();
                        }
                    });
                });
                
                console.log('✅ BULLETPROOF FILTER READY - NO DISABLING, SMOOTH AS FUCK');
            } else {
                console.error('❌ COURSE FILTER NOT FOUND');
            }

            function removeMistake(questionId) {
                if (!confirm('Are you sure you want to remove this question from your mistakes list?')) {
                    return;
                }

                fetch(`/review/remove/${questionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the question card from the UI
                        const card = document.querySelector(`[data-question-id="${questionId}"]`);
                        if (card) {
                            card.remove();
                            // Update the total questions count
                            const totalQuestions = document.querySelectorAll('.question-card').length;
                            if (totalQuestions === 0) {
                                // If no questions left, show completion message
                                document.querySelector('.completion-message').style.display = 'block';
                                document.querySelector('.questions-container').style.display = 'none';
                            }
                        }
                    } else {
                        alert(data.message || 'Error removing question');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing question');
                });
            }
        });
    </script>

</body>

</html>