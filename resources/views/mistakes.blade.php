<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Review Your Mistakes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6f9fc, #f1f4f8);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3.5rem;
            padding: 1.5rem 0;
            position: relative;
        }

        .header-section::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                rgba(0,0,0,0) 0%, 
                rgba(0,0,0,0.1) 50%, 
                rgba(0,0,0,0) 100%);
        }

        h1 {
            color: #2c3e50;
            font-weight: 800;
            font-size: 2.75rem;
            margin: 0;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
            position: relative;
        }

        .learn-mistakes-btn {
            background: #fef2f2;
            color: #b91c1c;
            padding: 0.9rem 2.2rem;
            border-radius: 9px;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.25s ease;
            border: 1px solid #fecaca;
        }

        .learn-mistakes-btn:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(185, 28, 28, 0.08);
        }

        .learn-mistakes-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(185, 28, 28, 0.05);
        }

        .learn-mistakes-btn i {
            font-size: 1.05rem;
            transition: all 0.25s ease;
            opacity: 0.9;
        }

        .learn-mistakes-btn:hover i {
            transform: translateX(3px);
            opacity: 1;
        }

        .mistake-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06),
                      0 0 0 1px rgba(0, 0, 0, 0.03);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .mistake-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            opacity: 0;
            transition: all 0.4s ease;
        }

        .mistake-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08),
                      0 0 0 1px rgba(0, 0, 0, 0.02);
        }

        .mistake-card:hover::before {
            opacity: 1;
        }

        .mistake-card::after {
            content: '';
            position: absolute;
            top: 5px;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(180deg, 
                rgba(255,255,255,0.8) 0%, 
                rgba(255,255,255,0) 100%);
            opacity: 0.5;
            pointer-events: none;
        }

        .remove-question {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: white;
            border: 2px solid #ff4d4d;
            color: #ff4d4d;
            font-size: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 12px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(255, 77, 77, 0.1);
        }

        .remove-question:hover {
            background-color: #ff4d4d;
            color: white;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 12px rgba(255, 77, 77, 0.2);
        }

        .question-section {
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 2rem;
            margin-right: 3rem;
        }

        .question-text {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .answer-section {
            margin-bottom: 0.75rem;
        }

        .answer-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 0.35rem;
            font-family: 'Poppins', sans-serif;
        }

        .answer-text {
            flex: 1;
            font-size: 1.1rem;
            line-height: 1.5;
        }

        .info-boxes {
            position: absolute;
            bottom: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
            z-index: 1;
        }

        .info-box {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .info-box:hover {
            border-color: #3b82f6;
            background-color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        .info-box-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
        }

        .info-box-value {
            font-size: 0.75rem;
            font-weight: 600;
            color: #000000;
        }

        .info-box i {
            font-size: 0.75rem;
            color: #64748b;
        }

        .topic-badge, .time-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            color: #4a5568;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .topic-badge:hover, .time-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .topic-badge i, .time-badge i {
            margin-right: 0.8rem;
            color: #3498db;
            font-size: 1.1rem;
        }

        .question-image {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            border-radius: 8px;
            margin: 1rem 0;
            display: block;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
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
            padding: 0.75rem 1rem;
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
            font-weight: 500;
            color: #000000;
            font-size: 0.95rem;
        }

        .form-check label:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            transform: translateY(-1px);
        }

        .form-check input:checked + label {
            background-color: #e3f2fd;
            border-color: #007bff;
            color: #000000;
        }

        .form-check label.correct {
            background-color: #e3fcef !important;
            border-color: #00a854 !important;
            color: #000000 !important;
        }

        .form-check label.incorrect {
            background-color: #fff1f0 !important;
            border-color: #f5222d !important;
            color: #000000 !important;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 20px rgba(148, 163, 184, 0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .empty-state::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        .empty-state i {
            font-size: 2.5rem;
            color: #3b82f6;
            margin-bottom: 1.5rem;
            display: inline-flex;
            padding: 1.2rem;
            border-radius: 50%;
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            animation: float 3s ease-in-out infinite;
        }

        .empty-state h3 {
            color: #1e293b;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            opacity: 0;
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
            margin-left: 1rem;
        }

        .profile-circle.no-image {
            background: linear-gradient(135deg, #007bff, #00d4ff);
        }

        .profile-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }

        .confetti-1 { background: #3b82f6; top: 20%; left: 20%; animation: confetti 3s ease-out infinite; }
        .confetti-2 { background: #60a5fa; top: 25%; right: 25%; animation: confetti 2.5s ease-out infinite 0.2s; }
        .confetti-3 { background: #93c5fd; top: 35%; left: 35%; animation: confetti 3.5s ease-out infinite 0.4s; }
        .confetti-4 { background: #2563eb; top: 40%; right: 40%; animation: confetti 3s ease-out infinite 0.6s; }
        .confetti-5 { background: #1d4ed8; top: 45%; left: 45%; animation: confetti 2.8s ease-out infinite 0.8s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes confetti {
            0% { transform: translateY(0) rotate(0); opacity: 1; }
            100% { transform: translateY(100px) rotate(360deg); opacity: 0; }
        }

        .empty-state .btn-primary {
            background: #f0f9ff;
            color: #0284c7;
            padding: 0.6rem 1.4rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid #bae6fd;
            transition: all 0.2s ease;
            display: inline-block;
            animation: buttonPulse 2s infinite;
        }

        .empty-state .btn-primary:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
            transform: translateY(-1px);
            animation: none;
        }

        .empty-state .btn-primary:active {
            transform: translateY(0);
        }

        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .toast {
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .toast.show {
            opacity: 1;
        }

        .toast.success {
            border-left: 4px solid #dc2626;
        }

        .toast.error {
            border-left: 4px solid #ff4d4d;
        }

        .explanation-toggle {
            display: inline-flex;
            align-items: center;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #0ea5e9;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .explanation-toggle i {
            font-size: 1.1rem;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .explanation-toggle:hover {
            transform: scale(1.1);
            color: #2563eb;
        }

        .explanation-toggle.active {
            color: #2563eb;
            transform: scale(1.1);
        }

        .explanation-toggle.active i {
            transform: rotate(180deg);
        }

        .explanation-content {
            background: #f8fafc;
            border-radius: 8px;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #1e293b;
            border-left: 3px solid #0ea5e9;
            padding: 1rem;
            margin-top: 0.75rem;
            transform-origin: top;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform, opacity, height;
            overflow: hidden;
        }

        .explanation-content.collapse:not(.show) {
            display: block;
            opacity: 0;
            transform: translateY(-12px);
            height: 0 !important;
            padding-top: 0;
            padding-bottom: 0;
            margin: 0;
        }

        .explanation-content.collapsing {
            height: 0;
        }

        .explanation-content.collapsing.show {
            height: var(--explanation-height);
        }

        .explanation-content.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .learn-mistakes-btn {
                width: 100%;
                justify-content: center;
            }
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

        .nav-link:hover {
            color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        /* Filter dropdown styles */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 16px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            position: relative;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-section:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .filter-section label {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .filter-section label i {
            font-size: 1.1rem;
        }

        .filter-section .position-relative {
            position: relative;
        }

        .filter-section select {
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 10px 18px;
            font-size: 1rem;
            min-width: 280px;
            background-color: #f8fafc;
            color: #334155;
            font-weight: 500;
            box-shadow: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-section select:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            border-color: rgba(59, 130, 246, 0.5);
            outline: none;
        }

        /* Style for dropdown options */
        .filter-section select option {
            padding: 10px;
            font-size: 1rem;
            background-color: white;
            color: #334155;
            font-weight: normal;
        }

        .filter-section select option:checked {
            background-color: #3b82f6;
            color: white;
        }

        .filter-section .btn-outline-secondary {
            border-radius: 12px;
            font-size: 0.9rem;
            padding: 10px 16px;
            background-color: white;
            border-color: #e2e8f0;
            color: #64748b;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-weight: 500;
        }

        .filter-section .btn-outline-secondary:hover {
            background-color: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .filter-section form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-section label {
                margin-bottom: 8px;
            }
            
            .filter-section .btn-outline-secondary {
                margin-top: 12px;
                width: 100%;
            }
        }

        /* Mistake counter styles */
        .mistake-counter {
            margin-top: 0.5rem;
        }

        .mistake-counter .badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.85rem;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .mistake-counter .badge::before {
            content: "•";
            display: inline-block;
            animation: pulse 1.5s infinite;
            font-size: 1.2rem;
            line-height: 0;
            position: relative;
            top: 1px;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Flashcard Mode Styles */
        .flashcard-mode-toggle {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            color: #475569;
            padding: 0.7rem 1.4rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .flashcard-mode-toggle:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .flashcard-mode-toggle.active {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        .flashcard-mode-toggle i {
            font-size: 1.1rem;
            transition: transform 0.4s ease;
        }

        .flashcard-mode-toggle.active i {
            transform: rotate(180deg);
        }

        /* Flashcard Styles */
        .mistake-card.flashcard-mode .normal-mode-content {
            display: none;
        }

        .mistake-card.flashcard-mode .flashcard-content {
            display: block;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: all 0.7s cubic-bezier(0.4, 0.2, 0.2, 1);
            min-height: 450px;
            perspective: 1200px;
            position: relative;
            border-radius: 16px;
            overflow: visible;
        }

        .mistake-card.flashcard-mode::before {
            display: none; /* Remove the blue hover effect at the top */
        }

        .mistake-card.flashcard-mode:hover::before {
            display: none; /* Ensure hover effect stays removed */
        }

        .mistake-card.flashcard-mode .card-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #ffffff;
            padding: 3rem;
            border: 2px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            will-change: transform;
        }

        .mistake-card.flashcard-mode:hover .card-face {
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .mistake-card.flashcard-mode:hover {
            transform: none; /* Remove any hover transform */
        }

        .mistake-card.flashcard-mode .card-front {
            transform: rotateY(0deg);
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1) !important;
        }

        .mistake-card.flashcard-mode .card-back {
            transform: rotateY(180deg);
        }

        .mistake-card.flashcard-mode.flipped .card-back {
            border: 2px solid #16a34a;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);
        }

        .mistake-card.flashcard-mode.flipped .flashcard-content {
            transform: rotateY(180deg);
        }

        .mistake-card.flashcard-mode .question-text {
            text-align: center;
            max-width: 90%;
            margin: 0;
            padding: 0;
        }

        .mistake-card.flashcard-mode .question-text p {
            font-size: 1.5rem;
            font-weight: 500;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
        }

        .mistake-card.flashcard-mode .correct-answer {
            font-size: 1.5rem;
            font-weight: 500;
            color: #16a34a;
            text-align: center;
            max-width: 90%;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .mistake-card.flashcard-mode .flashcard-content {
                min-height: 250px;
            }

            .mistake-card.flashcard-mode .question-text p,
            .mistake-card.flashcard-mode .correct-answer {
                font-size: 1.15rem;
            }

            .mistake-card.flashcard-mode .card-face {
                padding: 1.5rem;
            }
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
            <div class="toast-container"></div>
            <div class="header-section">
                <div>
                    <h1>Review Your Mistakes</h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button id="flashcardModeToggle" class="flashcard-mode-toggle">
                        <i class="fas fa-sync-alt"></i>
                        Flashcard Mode
                    </button>
                    <a href="{{ route('review.learn') }}" class="learn-mistakes-btn">
                        Learn Mistakes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Course Filter Dropdown -->
            <div class="filter-section">
                <form action="{{ route('review.index') }}" method="GET" class="d-flex align-items-center w-100 gap-3">
                    <div class="d-flex align-items-center flex-grow-1 flex-wrap">
                        <label for="courseFilter" class="me-3">
                            <i class="fas fa-filter me-2 text-primary"></i>
                            <span>Filter by Course:</span>
                        </label>
                        <div class="position-relative flex-grow-1">
                            @php
                                // Get total mistakes count (unfiltered)
                                $totalMistakesCount = App\Models\Mistake::where('user_id', auth()->id())->count();
                                
                                // Get course counts regardless of current filter
                                $allCourseCounts = DB::table('mistakes')
                                    ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                                    ->join('topics', 'questions.topic_id', '=', 'topics.id')
                                    ->join('courses', 'topics.course_id', '=', 'courses.id')
                                    ->where('mistakes.user_id', auth()->id())
                                    ->select('courses.id', 'courses.name', DB::raw('count(*) as count'))
                                    ->groupBy('courses.id', 'courses.name')
                                    ->get()
                                    ->keyBy('id');
                            @endphp
                            <select id="courseFilter" name="course" class="form-select" onchange="this.form.submit()">
                                <option value="">All Courses ({{ $totalMistakesCount }})</option>
                                @foreach($courses->sortBy('name') as $course)
                                    <option value="{{ $course->id }}" {{ $courseFilter == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $allCourseCounts[$course->id]->count ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($courseFilter)
                        <a href="{{ route('review.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear Filter
                        </a>
                    @endif
                </form>
            </div>

            @if($mistakes->isEmpty())
                <div class="empty-state">
                    <div class="confetti confetti-1"></div>
                    <div class="confetti confetti-2"></div>
                    <div class="confetti confetti-3"></div>
                    <div class="confetti confetti-4"></div>
                    <div class="confetti confetti-5"></div>
                    <i class="fas fa-check"></i>
                    <h3>No Mistakes!</h3>
                    <p>Fantastic work! You're crushing it with zero mistakes. Keep up the amazing progress!</p>
                    <a href="javascript:history.back()" class="btn btn-primary">Take More Quizzes</a>
                </div>
            @else
                @foreach($mistakes as $mistake)
                    <div class="mistake-card" data-question-id="{{ $mistake->question_id }}">
                        <!-- Normal Mode Content -->
                        <div class="normal-mode-content">
                            <button class="remove-question" onclick="removeMistake({{ $mistake->question_id }})">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="question-text">
                                <p>{{ $mistake->question->question }}</p>
                                @if($mistake->question->image_url)
                                    <img src="{{ $mistake->question->image_url }}" alt="Question Image" class="question-image">
                                @endif
                            </div>
                            <div class="answers">
                                <div class="options">
                                    @php
                                        $submittedOption = $mistake->submitted_answer;
                                        $correctOption = $mistake->question->correct_answer;
                                    @endphp
                                    
                                    <!-- Submitted Answer (if incorrect) -->
                                    @if($submittedOption !== $correctOption)
                                        <div class="answer-section">
                                            <h4 class="answer-label">Your answer:</h4>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" disabled checked>
                                                <label class="form-check-label incorrect">
                                                    {{ $mistake->question->{'option_'.strtolower($submittedOption)} }}
                                                </label>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Correct Answer -->
                                    <div class="answer-section">
                                        <h4 class="answer-label">Correct answer:</h4>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label correct">
                                                {{ $mistake->question->{'option_'.strtolower($correctOption)} }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @if($mistake->question->explanation)
                                    <div class="mt-3">
                                        <button class="explanation-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#explanation-{{ $mistake->question_id }}">
                                            <i class="fas fa-lightbulb"></i>
                                        </button>
                                        <div class="collapse explanation-content" id="explanation-{{ $mistake->question_id }}">
                                            {{ $mistake->question->explanation }}
                                        </div>
                                    </div>
                                @endif
                                <div class="info-boxes">
                                    <div class="info-box">
                                        <div class="info-box-label">Date</div>
                                        <div class="info-box-value">{{ $mistake->last_attempt_date->format('M j, Y') }}</div>
                                    </div>
                                    @if($mistake->question->topic && $mistake->question->topic->course)
                                    <div class="info-box">
                                        <div class="info-box-label">Topic</div>
                                        <div class="info-box-value">{{ $mistake->question->topic->name }}</div>
                                    </div>
                                    <div class="info-box">
                                        <div class="info-box-label">Course</div>
                                        <div class="info-box-value">{{ $mistake->question->topic->course->name }}</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Flashcard Mode Content -->
                        <div class="flashcard-content">
                            <div class="card-face card-front"></div>
                            <div class="card-face card-back"></div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Flashcard Mode functionality
        const flashcardModeToggle = document.getElementById('flashcardModeToggle');
        const mistakeCards = document.querySelectorAll('.mistake-card');
        let isFlashcardMode = false;

        flashcardModeToggle.addEventListener('click', () => {
            isFlashcardMode = !isFlashcardMode;
            flashcardModeToggle.classList.toggle('active');
            
            mistakeCards.forEach(card => {
                if (isFlashcardMode) {
                    // Get content from original card
                    const questionText = card.querySelector('.normal-mode-content .question-text p').textContent;
                    const questionImage = card.querySelector('.normal-mode-content .question-image');
                    const correctAnswer = card.querySelector('.normal-mode-content .form-check-label.correct').textContent;
                    
                    // Get flashcard faces
                    const cardFront = card.querySelector('.flashcard-content .card-front');
                    const cardBack = card.querySelector('.flashcard-content .card-back');
                    
                    // Update flashcard content
                    cardFront.innerHTML = `
                        <div class="question-text">
                            <p>${questionText}</p>
                            ${questionImage ? questionImage.outerHTML : ''}
                        </div>
                    `;
                    cardBack.innerHTML = `
                        <div class="correct-answer">
                            ${correctAnswer}
                        </div>
                    `;
                } else {
                    // Clean up flashcard content when turning mode off
                    const cardFront = card.querySelector('.flashcard-content .card-front');
                    const cardBack = card.querySelector('.flashcard-content .card-back');
                    if (cardFront) cardFront.innerHTML = '';
                    if (cardBack) cardBack.innerHTML = '';
                    card.classList.remove('flipped');
                }
                
                // Toggle flashcard mode
                card.classList.toggle('flashcard-mode');
            });

            // Update button text
            flashcardModeToggle.innerHTML = `
                <i class="fas fa-sync-alt"></i>
                Flashcard Mode ${isFlashcardMode ? 'ON' : 'OFF'}
            `;
        });

        // Handle card flipping in flashcard mode
        mistakeCards.forEach(card => {
            card.addEventListener('click', () => {
                if (isFlashcardMode) {
                    card.classList.toggle('flipped');
                }
            });
        });

        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                ${message}
            `;
            toastContainer.appendChild(toast);
            
            // Trigger reflow to enable animation
            toast.offsetHeight;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function removeMistake(questionId) {
            // Get the card and remove it immediately for better UX
            const card = document.querySelector(`[data-question-id="${questionId}"]`);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    card.remove();
                    // Check if we need to show empty state
                    if (document.querySelectorAll('.mistake-card').length === 0) {
                        location.reload();
                        return;
                    }
                }, 300);
            }

            // Make the API call
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/review/remove/${questionId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Error:', error);
                // Only show error toast if the card is still visible
                const cardStillExists = document.querySelector(`[data-question-id="${questionId}"]`);
                if (cardStillExists) {
                    showToast('Error removing question. Please try again.', 'error');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.explanation-toggle');
            
            toggles.forEach(toggle => {
                const content = document.querySelector(toggle.getAttribute('data-bs-target'));
                
                if (content) {
                    // Store the content height as a CSS variable
                    const updateHeight = () => {
                        const height = content.scrollHeight;
                        content.style.setProperty('--explanation-height', height + 'px');
                    };

                    // Update height on window resize
                    window.addEventListener('resize', updateHeight);
                    
                    // Initial height calculation
                    updateHeight();

                    content.addEventListener('transitionend', (e) => {
                        if (e.propertyName === 'height') {
                            if (!content.classList.contains('show')) {
                                content.style.height = '';
                            }
                        }
                    });

                    toggle.addEventListener('click', () => {
                        requestAnimationFrame(() => {
                            toggle.classList.toggle('active');
                            
                            if (content.classList.contains('show')) {
                                // Closing
                                const height = content.scrollHeight;
                                content.style.height = height + 'px';
                                content.offsetHeight; // Force reflow
                                content.style.height = '0px';
                            } else {
                                // Opening
                                content.style.height = content.scrollHeight + 'px';
                            }
                        });
                    });
                }
            });
        });
    </script>
</body>
</html>