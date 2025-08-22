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
        /* Review mistakes page zoom fix - 80% scale */
        html {
            font-size: 12.8px; /* 80% of 16px */
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6f9fc, #f1f4f8);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            zoom: 0.80; /* Zoom out 20% */
            -webkit-text-size-adjust: 100%; /* Prevent Safari text auto-sizing */
        }

        /* No top indicator styles */

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

        /* 🚀 OPTIMIZED: Reduced animation complexity for performance */
        .mistake-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06),
                      0 0 0 1px rgba(0, 0, 0, 0.03);
            transition: box-shadow 0.2s ease, transform 0.2s ease; /* Simplified transition */
            position: relative;
            overflow: hidden;
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
            transition: opacity 0.2s ease; /* Faster transition */
        }

        .mistake-card:hover {
            transform: translateY(-2px); /* Reduced movement for performance */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08),
                      0 0 0 1px rgba(0, 0, 0, 0.02);
        }

        .mistake-card:hover::before {
            opacity: 1;
        }

        /* 🚀 PERFORMANCE: Removed complex gradient overlay */
        .mistake-card::after {
            display: none; /* Removed for performance */
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

        /* 🚀 OPTIMIZED: Removed CPU-intensive confetti animations */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            opacity: 0;
            display: none; /* Disable confetti completely for performance */
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
            transition: all 0.2s ease; /* Reduced transition time */
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
            background-size: cover;
            background-position: center;
            margin-left: 1rem;
        }

        .profile-circle.no-image {
            background: linear-gradient(135deg, #007bff, #00d4ff);
        }

        .profile-circle:hover {
            transform: scale(1.05); /* Reduced scale for performance */
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }

        /* 🚀 PERFORMANCE: Removed all confetti animations */
        .confetti-1, .confetti-2, .confetti-3, .confetti-4, .confetti-5 {
            display: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); } /* Reduced movement for performance */
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

        /* 🚀 OPTIMIZED: Simplified explanation toggle animations */
        .explanation-toggle {
            display: inline-flex;
            align-items: center;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: #0ea5e9;
            transition: color 0.15s ease; /* Simplified transition */
        }

        .explanation-toggle i {
            font-size: 1.1rem;
            transition: transform 0.15s ease; /* Faster transition */
        }

        .explanation-toggle:hover {
            color: #2563eb;
        }

        .explanation-toggle.active {
            color: #2563eb;
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
            overflow: hidden;
            transition: opacity 0.15s ease; /* Simplified transition */
        }

        .explanation-content.collapse:not(.show) {
            display: none; /* Simple show/hide instead of complex animations */
        }

        .explanation-content.show {
            display: block;
            opacity: 1;
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

        /* ROBUST FILTER LOADING STATES */
        .filter-section select:disabled {
            background-color: #e2e8f0 !important;
            color: #64748b !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }

        .filter-section select.loading {
            background-image: url("data:image/svg+xml,%3csvg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M10 3V6M10 14V17M17 10H14M6 10H3M15.364 4.636L13.536 6.464M6.464 13.536L4.636 15.364M15.364 15.364L13.536 13.536M6.464 6.464L4.636 4.636' stroke='%233b82f6' stroke-width='2' stroke-linecap='round'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 16px;
            animation: spin 1s linear infinite;
            padding-right: 40px !important;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .filter-section select.loading::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid #3b82f6;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* 🚀 OPTIMIZED: Simplified dropdown styles */
        .filter-section select option {
            padding: 8px;
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

        /* 🚀 ULTRA-SMOOTH 3D Flashcard Flipping Effect */
        .mistake-card.flashcard-mode .normal-mode-content {
            display: none;
        }

        .mistake-card.flashcard-mode .flashcard-content {
            display: block;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            min-height: 450px;
            perspective: 1000px;
            position: relative;
            border-radius: 16px;
            overflow: visible;
            will-change: transform;
            backface-visibility: hidden;
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
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            will-change: transform;
            -webkit-backface-visibility: hidden;
            -moz-backface-visibility: hidden;
            -ms-backface-visibility: hidden;
            transform-style: preserve-3d;
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
            font-size: 1.7rem;
            font-weight: 500;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
        }

        .mistake-card.flashcard-mode .correct-answer {
            font-size: 1.7rem;
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
                font-size: 1.9rem;
            }

            .mistake-card.flashcard-mode .card-face {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                @if(isset($recentCourse) && $recentCourse)
                    <a href="{{ route('topics.forCourse', $recentCourse->id) }}" class="nav-link">
                        <i class="fas fa-arrow-left"></i> Back to {{ $recentCourse->name }}
                    </a>
                @else
                    <a href="{{ route('courses.index') }}" class="nav-link">
                        <i class="fas fa-arrow-left"></i> Back to Courses
                    </a>
                @endif
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user() || !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user() && Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user() || !Auth::user()->profile_picture_url)
                        {{ Auth::user() ? substr(Auth::user()->name, 0, 1) : 'U' }}
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

            <!-- ROBUST AS FUCK COURSE FILTER DROPDOWN -->
            <div class="filter-section">
                <form id="robust-filter-form" action="{{ route('review.index') }}" method="GET" class="d-flex align-items-center w-100 gap-3" data-instant-submit="true">
                    <!-- Hidden inputs for form robustness -->
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="filter_timestamp" value="{{ time() }}">
                    
                    <div class="d-flex align-items-center flex-grow-1 flex-wrap">
                        <label for="courseFilter" class="me-3">
                            <i class="fas fa-filter me-2 text-primary"></i>
                            <span>Filter by Course:</span>
                        </label>
                        <div class="position-relative flex-grow-1">
                            <select id="courseFilter" name="course" class="form-select">
                                <option value="">All Courses ({{ $totalMistakesCount ?? 0 }})</option>
                                <option value="skipped" {{ ($selectedCourse ?? '') == 'skipped' ? 'selected' : '' }}>
                                    Skipped Questions ({{ $skippedCount ?? 0 }})
                                </option>
                                @foreach($availableCourses->sortBy('name') as $course)
                                    <option value="{{ $course->id }}" {{ $selectedCourse == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $course->mistake_count ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($selectedCourse)
                        <a href="{{ route('review.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Clear Filter
                        </a>
                    @endif
                </form>
            </div>

            <div id="mistakes-content">
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
                </div>
            @else
{{-- 💀 N+1 FIX NEEDED: Eager load 'question' in controller --}}
                @foreach($mistakes as $mistake)
                    <div class="mistake-card" data-question-id="{{ $mistake->question_id }}">
                        <!-- Normal Mode Content -->
                        <div class="normal-mode-content">
                            <button class="remove-question" onclick="removeMistake({{ $mistake->question_id }})">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="question-text">
    <!-- No hardcoded indicator -->
                                <p>{{ $mistake->question->question }}</p>
                                @if($mistake->question->image_url)
                                    <img src="{{ asset($mistake->question->image_url) }}" alt="Question Image" class="question-image">
                                @endif
                                
                                <!-- No top indicator -->
                                

                            </div>
                            <div class="answers">
                                <div class="options">
                                    @php
                                        $submittedOption = $mistake->submitted_answer;
                                        $correctOption = $mistake->question->correct_answer;
                                        $isUnanswered = ($submittedOption === 'unanswered' || $submittedOption === '' || $submittedOption === null || $mistake->skipped);
                                    @endphp
                                    
                                    <!-- Submitted Answer Display -->
                                    <div class="answer-section">
                                        <h4 class="answer-label">Your answer:</h4>
                                        <div class="form-check">
                                            @if($isUnanswered)
                                                <!-- Unanswered Question - Normal Styling -->
                                                <label class="form-check-label incorrect">
                                                    UNANSWERED
                                                </label>
                                            @elseif($mistake->question->question_type === 'written')
                                                <!-- Written Question - Show Submitted Text -->
                                                <label class="form-check-label incorrect">
                                                    {{ $submittedOption }}
                                                </label>
                                            @else
                                                <!-- Multiple Choice - Show Submitted Option -->
                                                <input class="form-check-input" type="radio" disabled checked>
                                                <label class="form-check-label incorrect">
                                                    @if(in_array(strtolower($submittedOption), ['a', 'b', 'c', 'd']))
                                                        {{ $mistake->question->{'option_'.strtolower($submittedOption)} }}
                                                    @else
                                                        {{ $submittedOption }}
                                                    @endif
                                                </label>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Correct Answer -->
                                    <div class="answer-section">
                                        <h4 class="answer-label">Correct answer:</h4>
                                        <div class="form-check">
                                            @if($mistake->question->question_type === 'written')
                                                <span class="written-correct-answer d-none">{{ $mistake->question->correct_answer }}</span>
                                                <label class="form-check-label correct">
                                                    {{ $mistake->question->correct_answer }}
                                                </label>
                                            @else
                                                <label class="form-check-label correct">
                                                    {{ $mistake->question->{'option_'.strtolower($correctOption)} }}
                                                </label>
                                            @endif
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
                                    @if($mistake->skipped)
                                    <div class="info-box" style="border-color: #5dade2; background-color: rgba(93, 173, 226, 0.15);">
                                        <i class="fas fa-forward" style="color: #2980b9;"></i>
                                        <div class="info-box-value" style="color: #2980b9;">skipped</div>
                                    </div>
                                    @endif
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
                    let correctAnswer = '';
                    // Prefer written-correct-answer if present
                    const writtenCorrect = card.querySelector('.normal-mode-content .written-correct-answer');
                    if (writtenCorrect) {
                        correctAnswer = writtenCorrect.textContent;
                    } else {
                        const correctLabel = card.querySelector('.normal-mode-content .form-check-label.correct');
                        correctAnswer = correctLabel ? correctLabel.textContent : '';
                    }
                    // Get flashcard faces
                    const cardFront = card.querySelector('.flashcard-content .card-front');
                    const cardBack = card.querySelector('.flashcard-content .card-back');
                    
                    // Update flashcard content
                    cardFront.innerHTML = `
                        <div class="question-text">
                            <p>${questionText}</p>
                            ${questionImage ? `<img src="${questionImage.src}" alt="Question Image" class="question-image" style="max-width: 100%; height: auto; max-height: 200px; border-radius: 8px; margin: 1rem 0;">` : ''}
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

        // 🚀 ULTRA-SMOOTH: Enhanced card flipping with better responsiveness
        let isFlipping = false; // Prevent rapid clicking during flip

        mistakeCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (isFlashcardMode && !isFlipping) {
                    e.preventDefault();
                    isFlipping = true;

                    // Add visual feedback immediately
                    card.style.transform = 'scale(0.98)';

                    setTimeout(() => {
                        card.classList.toggle('flipped');
                        card.style.transform = '';

                        // Reset flip prevention after animation completes
                        setTimeout(() => {
                            isFlipping = false;
                        }, 600); // Match CSS transition duration
                    }, 50);
                }
            });

            // Add touch support for mobile devices
            card.addEventListener('touchstart', (e) => {
                if (isFlashcardMode) {
                    e.preventDefault();
                    // Add slight visual feedback for touch
                    card.style.transform = 'scale(0.96)';
                }
            });

            card.addEventListener('touchend', (e) => {
                if (isFlashcardMode) {
                    e.preventDefault();
                    card.style.transform = '';

                    if (!isFlipping) {
                        isFlipping = true;
                        setTimeout(() => {
                            card.classList.toggle('flipped');
                            setTimeout(() => {
                                isFlipping = false;
                            }, 600);
                        }, 50);
                    }
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
            // Get the card and show loading state
            const card = document.querySelector(`[data-question-id="${questionId}"]`);
            if (!card) {
                console.error('Card not found for question ID:', questionId);
                return;
            }

            // Show loading state
            const removeButton = card.querySelector('.remove-question');
            if (removeButton) {
                removeButton.disabled = true;
                removeButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                removeButton.style.opacity = '0.6';
            }

            // Make the API call FIRST
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/review/remove/${questionId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // SUCCESS: Remove card with animation
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-20px)';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if we need to show empty state
                        const remainingCards = document.querySelectorAll('.mistake-card');
                        if (remainingCards.length === 0) {
                            const mistakesContent = document.querySelector('#mistakes-content');
                            if (mistakesContent) {
                                mistakesContent.innerHTML = `
                                    <div class="empty-state">
                                        <i class="fas fa-check"></i>
                                        <h3>No Mistakes!</h3>
                                        <p>Fantastic work! You're crushing it with zero mistakes. Keep up the amazing progress!</p>
                                    </div>
                                `;
                            }
                        }
                        
                        showToast('Question removed successfully!', 'success');
                    }, 300);
                } else {
                    // API returned error
                    throw new Error(data.message || 'Failed to remove question');
                }
            })
            .catch(error => {
                console.error('Error removing mistake:', error);
                
                // RESTORE button state on error
                if (removeButton) {
                    removeButton.disabled = false;
                    removeButton.innerHTML = '<i class="fas fa-times"></i>';
                    removeButton.style.opacity = '1';
                }
                
                showToast('Error removing question. Please try again.', 'error');
            });
        }

        // GOLDEN STANDARD: Navigation data from backend
        const recentCourse = @json($recentCourse ?? null);
        const coursesIndexUrl = '{{ route("courses.index") }}';
        
        function getBackButtonHtml() {
            return '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Review mistakes page loaded successfully');
            
            // Check if we're in a reload loop
            if (sessionStorage.getItem('reviewPageLoaded')) {
                console.log('Page was already loaded, preventing reload loop');
                sessionStorage.removeItem('reviewPageLoaded');
                return;
            }
            sessionStorage.setItem('reviewPageLoaded', 'true');
            // 🚀 OPTIMIZED: Simplified explanation toggle - no complex animations
            const toggles = document.querySelectorAll('.explanation-toggle');
            
            toggles.forEach(toggle => {
                const content = document.querySelector(toggle.getAttribute('data-bs-target'));
                
                if (content) {
                    toggle.addEventListener('click', () => {
                        toggle.classList.toggle('active');
                        content.classList.toggle('show');
                    });
                }
            });

            // 🚀 ULTRA-OPTIMIZED FILTER SYSTEM - Lightweight & Fast
            const courseFilter = document.getElementById('courseFilter');
            if (courseFilter) {
                courseFilter.addEventListener('change', function(e) {
                    const baseUrl = '{{ route("review.index") }}';
                    const finalUrl = this.value ? `${baseUrl}?course=${encodeURIComponent(this.value)}` : baseUrl;
                    window.location.href = finalUrl;
                });
            }

            // Add error handling for flashcard mode toggle
            if (flashcardModeToggle) {
                flashcardModeToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            }
        });
    </script>
</body>
</html>
