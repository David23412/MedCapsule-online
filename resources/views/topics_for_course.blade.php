<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $course->name }} Topics - MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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

        @keyframes float-particle {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(10px, -10px) rotate(5deg); }
            50% { transform: translate(0, -15px) rotate(0deg); }
            75% { transform: translate(-10px, -10px) rotate(-5deg); }
        }

        @keyframes glow {
            0%, 100% { text-shadow: 0 0 20px rgba(255,255,255,0.3); }
            50% { text-shadow: 0 0 30px rgba(255,255,255,0.5); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .course-header {
            background: linear-gradient(135deg, #0072ff, #00c6ff);
            color: white;
            padding: 4rem 3rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            min-height: 280px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .course-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 1000px 100%;
            animation: shimmer 8s linear infinite;
        }

        .case-filters {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .case-filter {
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
            background: white;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .case-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            border-color: #dee2e6;
        }

        .case-filter.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        /* Add subtle color indications for each category */
        .case-filter:nth-child(2) i, /* Cases */
        .case-filter:nth-child(2) span:not(.count),
        .case-filter:nth-child(2) .count {
            color: #ff8f00;
        }

        .case-filter:nth-child(2).active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .case-filter:nth-child(3) i, /* Practical */
        .case-filter:nth-child(3) span:not(.count),
        .case-filter:nth-child(3) .count {
            color: #7b1fa2;
        }

        .case-filter:nth-child(3).active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .case-filter:nth-child(4) i, /* Quiz */
        .case-filter:nth-child(4) span:not(.count),
        .case-filter:nth-child(4) .count {
            color: #2e7d32;
        }

        .case-filter:nth-child(4).active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .case-filter i {
            font-size: 0.8rem;
        }

        .case-filter.active i,
        .case-filter.active span {
            color: white !important;
        }

        .case-filter .count {
            background: rgba(0,0,0,0.05);
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            min-width: 1.2rem;
            text-align: center;
        }

        .case-filter.active .count {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            pointer-events: none;
            filter: blur(0.5px);
        }

        .particle:nth-child(1) { top: 15%; left: 10%; animation: float-particle 6s infinite; }
        .particle:nth-child(2) { top: 25%; right: 15%; animation: float-particle 7s infinite 0.5s; }
        .particle:nth-child(3) { top: 35%; left: 25%; animation: float-particle 8s infinite 1s; }
        .particle:nth-child(4) { top: 20%; right: 25%; animation: float-particle 7.5s infinite 1.5s; }
        .particle:nth-child(5) { top: 30%; left: 35%; animation: float-particle 6.5s infinite 2s; }
        .particle:nth-child(6) { top: 40%; right: 35%; animation: float-particle 7.2s infinite 2.5s; }
        .particle:nth-child(7) { top: 25%; left: 45%; animation: float-particle 6.8s infinite 3s; }
        .particle:nth-child(8) { top: 35%; right: 45%; animation: float-particle 7.8s infinite 3.5s; }

        .topics-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .topic-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
            position: relative;
            overflow: hidden;
        }

        .topic-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .topic-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .topic-main {
            flex: 1;
            padding-right: 2rem;
        }

        .topic-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .topic-name i {
            color: #007bff;
            font-size: 1rem;
        }

        .topic-description {
            display: none;
        }

        .topic-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .progress-container {
            flex: 1;
            margin-top: 1.5rem;
        }

        .progress {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 0.8rem;
        }

        .progress-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(90deg, #007bff, #00bcd4);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0) 0%, 
                rgba(255,255,255,0.3) 50%, 
                rgba(255,255,255,0) 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .start-quiz-btn {
            background: #007bff;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-top: 1rem;
        }

        .start-quiz-btn:hover {
            background: #0056b3;
            transform: translateX(2px);
            color: white;
        }

        .start-quiz-btn i {
            transition: transform 0.3s ease;
        }

        .start-quiz-btn:hover i {
            transform: translateX(3px);
        }

        .grade-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .grade-mastered {
            background-color: #e3fcef;
            color: #00a854;
        }

        .grade-good {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .grade-in-progress {
            background-color: #e6f7ff;
            color: #1890ff;
        }

        .grade-bad {
            background-color: #fff1f0;
            color: #f5222d;
        }

        .grade-none {
            background-color: #f5f5f5;
            color: #8c8c8c;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .topic-card:nth-child(1) { animation-delay: 0.1s; }
        .topic-card:nth-child(2) { animation-delay: 0.2s; }
        .topic-card:nth-child(3) { animation-delay: 0.3s; }
        .topic-card:nth-child(4) { animation-delay: 0.4s; }
        .topic-card:nth-child(5) { animation-delay: 0.5s; }

        .nav-link {
            color: #495057;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-circle {
            width: 36px;
            height: 36px;
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
            text-decoration: none;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
            color: white;
            text-decoration: none;
        }

        .mistakes-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            background: #ffebee;
            border: 1px solid #ef9a9a;
            transition: all 0.3s ease;
        }

        .mistakes-link:hover {
            background: #ffcdd2;
            color: #c62828;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
        }

        .course-progress {
            background: rgba(255, 255, 255, 0.15);
            padding: 1.25rem 1.75rem;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            max-width: 400px;
            margin: 0;
        }

        .course-progress:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .progress-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .progress-circle::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        .progress-circle::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.2);
            animation: pulseRing 2s infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes pulseRing {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.3;
            }
            100% {
                transform: scale(1);
                opacity: 0.6;
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .course-progress:hover .progress-circle {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.15);
        }

        .progress-text {
            text-align: left;
            transition: all 0.3s ease;
        }

        .progress-text-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: white;
        }

        .progress-text-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .course-progress:hover .progress-text-subtitle {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .last-attempt {
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .last-attempt i {
            color: #007bff;
        }

        .topic-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .topic-card[data-case-type] {
            transition: all 0.3s ease;
        }

        .topic-card.hidden {
            display: none;
        }

        .case-type-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .case-type-badge.quiz {
            background: #e8f5e9;  
            color: #2e7d32;      
        }

        .case-type-badge.cases {
            background: #fff8e1;  
            color: #ff8f00;      
        }

        .case-type-badge.practical {
            background: #f3e5f5;  
            color: #7b1fa2;      
        }

        .case-type-badge i {
            font-size: 0.8rem;
        }

        .random-link {
            color: #fd7e14;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            background: #fff8e1;
            border: 1px solid #ffd54f;
            transition: all 0.3s ease;
        }

        .random-link:hover {
            background: #ffe082;
            color: #f57c00;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(255, 152, 0, 0.1);
        }

        /* Random Quiz Modal Styles */
        #randomQuizModal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            background: white;
        }

        #randomQuizModal .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        #randomQuizModal .modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        #randomQuizModal .modal-icon-wrapper {
            width: 40px;
            height: 40px;
            background: #fff8e1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #randomQuizModal .modal-icon-wrapper i {
            color: #ffc107;
            font-size: 1.2rem;
        }

        #randomQuizModal .modal-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.25rem;
        }

        #randomQuizModal .modal-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 400;
        }

        #randomQuizModal .modal-body {
            padding: 1.5rem;
        }

        #randomQuizModal .setting-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        #randomQuizModal .setting-header i {
            font-size: 1.1rem;
            color: #ffc107;
        }

        #randomQuizModal .setting-header .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        #randomQuizModal .courses-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        #randomQuizModal .custom-checkbox {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .custom-checkbox:hover {
            background: rgba(255, 193, 7, 0.05);
        }

        #randomQuizModal .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.2em;
            border: 2px solid #dee2e6;
            transition: all 0.2s ease;
        }

        #randomQuizModal .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        #randomQuizModal .form-check-label {
            color: #495057;
            font-size: 0.95rem;
            padding-left: 0.5rem;
        }

        #randomQuizModal .custom-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: #495057;
            transition: all 0.2s ease;
        }

        #randomQuizModal .custom-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.1);
        }

        #randomQuizModal .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        #randomQuizModal .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #2c3e50;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-warning:hover {
            background-color: #ffca2c;
            border-color: #ffca2c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
        }

        #randomQuizModal .btn-outline-secondary {
            border: 2px solid #dee2e6;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
            transform: translateY(-1px);
        }

        #randomQuizModal .btn-close {
            opacity: 0.5;
            transition: all 0.2s ease;
        }

        #randomQuizModal .btn-close:hover {
            opacity: 1;
        }

        /* Custom scrollbar for courses container */
        #randomQuizModal .courses-container::-webkit-scrollbar {
            width: 6px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }

        #randomQuizModal .courses-container::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        /* Sources container styles */
        .sources-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
        }

        /* Source selection styles - minimalistic design */
        #sourceSelectionModal .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            background: white;
        }

        #sourceSelectionModal .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
        }

        #sourceSelectionModal .modal-icon-wrapper {
            width: 40px;
            height: 40px;
            background: #f0f7ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        #sourceSelectionModal .modal-icon-wrapper i {
            color: #007bff;
            font-size: 1.2rem;
        }

        #sourceSelectionModal .modal-body {
            padding: 1.5rem;
        }

        #sourceSelectionModal .sources-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 0;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar {
            width: 6px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-thumb {
            background: #dee2e6;
            border-radius: 3px;
        }

        #sourceSelectionModal .sources-container::-webkit-scrollbar-thumb:hover {
            background: #adb5bd;
        }

        #sourceSelectionModal .custom-checkbox {
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 0.75rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-start;
        }

        #sourceSelectionModal .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 4px;
        }

        /* Fix checkbox alignment */
        #sourceSelectionModal .form-check-input {
            margin-top: 0.2rem;
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 0.5rem;
            flex-shrink: 0;
        }

        #sourceSelectionModal .custom-checkbox:last-child {
            margin-bottom: 0;
        }

        #sourceSelectionModal .custom-checkbox:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        #sourceSelectionModal .form-check-input {
            margin-top: 0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            position: relative;
            flex-shrink: 0;
        }

        #sourceSelectionModal .form-check-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding-left: 0;
        }

        #sourceSelectionModal .text-muted {
            font-size: 0.95rem;
            font-weight: 500;
            min-width: 30px;
            text-align: right;
        }

        #sourceSelectionModal .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #sourceSelectionModal .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        #sourceSelectionModal .source-info {
            font-size: 0.9rem;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        
        /* Source completion status indicators */
        .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 5px; /* Increased from 4px */
        }
        
        .source-status.completed {
            background-color: #28a745;
        }
        
        .source-status.in-progress {
            background-color: #ffc107;
        }
        
        .source-status.not-started {
            background-color: #dee2e6;
        }
        
        .source-completion-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            font-weight: 600;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .source-completion-badge.completed {
            background-color: #d4edda;
            color: #28a745;
        }
        
        .source-completion-badge.in-progress {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .source-progress-bar {
            height: 5px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
            position: relative;
        }
        
        .source-progress-value {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00bcd4);
            border-radius: 2px;
            transition: width 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .source-info-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .source-info-title i {
            color: #007bff;
        }
        
        .source-info-text {
            margin-bottom: 0;
            line-height: 1.4;
        }

        #sourceSelectionModal .d-flex.align-items-center {
            min-height: 28px; /* Ensure consistent height for the source name and badge */
        }

        #sourceSelectionModal .form-check-label > div {
            flex: 1;
            padding-right: 10px;
        }

        #sourceSelectionModal .form-check-label span:first-child {
            font-weight: 500;
            font-size: 1rem;
        }

        /* Source card styles */
        .source-card {
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            background: transparent;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .source-card:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .source-card.selected {
            background-color: rgba(0, 123, 255, 0.03);
        }

        .source-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: #1a202c;
        }

        .source-status-text {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .source-status-text.completed {
            color: #28a745;
        }

        .source-status-text.in-progress {
            color: #ffc107;
        }

        .source-status-text.failed {
            color: #dc3545;
        }

        .source-status-text.not-started {
            color: #6c757d;
        }

        .source-progress-bar {
            height: 4px;
            width: 100%;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 0.5rem 0;
            position: relative;
        }

        .source-progress-value {
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }

        .source-progress-value.completed {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }

        .source-progress-value.in-progress {
            background: linear-gradient(90deg, #007bff, #00bcd4);
        }

        .source-progress-value.failed {
            background: linear-gradient(90deg, #dc3545, #ff6b6b);
        }

        .source-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .questions-count {
            font-size: 0.8rem;
            color: #64748b;
        }

        .source-info-wrapper {
            width: 100%;
        }

        .form-check-input {
            margin-top: 0;
            cursor: pointer;
        }

        .source-status {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 3px;
        }

        .source-status.completed {
            background-color: #28a745;
        }

        .source-status.in-progress {
            background-color: #ffc107;
        }

        .source-status.failed {
            background-color: #dc3545;
        }

        .source-status.not-started {
            background-color: #e9ecef;
        }

        /* Mode Selection Styles */
        .mode-selection {
            display: none;
        }
        
        /* Simple mode toggle styles */
        .form-check-inline {
            margin-right: 1rem;
        }
        
        .form-check-inline .form-check-input {
            margin-right: 0.5rem;
        }
        
        .form-check-inline .form-check-label {
            color: #495057;
            font-size: 0.9rem;
        }
        
        .form-check-input:checked ~ .form-check-label {
            color: #007bff;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Courses
                </a>
                
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <!-- Random Quiz Modal -->
    <div class="modal fade" id="randomQuizModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-random"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Random Quiz Mode</h5>
                            <p class="modal-subtitle mb-0">Customize your quiz settings</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('quiz.random') }}" method="POST" id="randomQuizForm">
                        @csrf
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-book-open text-warning"></i>
                                <label class="form-label mb-2">Select Courses</label>
                            </div>
                            <div class="courses-container">
                                @php
                                    $user = auth()->user();
                                    $courses = $user->is_admin ? \App\Models\Course::all() : $user->courses;
                                @endphp
                                @foreach($courses as $course)
                                    <div class="form-check custom-checkbox">
                                        <input class="form-check-input" type="checkbox" 
                                            name="selected_courses[]" 
                                            value="{{ $course->id }}" 
                                            id="course{{ $course->id }}"
                                            checked>
                                        <label class="form-check-label" for="course{{ $course->id }}">
                                            {{ $course->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-list-ol text-warning"></i>
                                <label for="questionLimit" class="form-label mb-2">Number of Questions</label>
                            </div>
                            <select class="form-select custom-select" id="questionLimit" name="question_limit">
                                <option value="10">10 Questions</option>
                                <option value="20">20 Questions</option>
                                <option value="30">30 Questions</option>
                                <option value="50">50 Questions</option>
                                <option value="100">100 Questions</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <div class="setting-header">
                                <i class="fas fa-clock text-warning"></i>
                                <label for="timeLimit" class="form-label mb-2">Time Limit (minutes)</label>
                            </div>
                            <select class="form-select custom-select" id="timeLimit" name="time_limit">
                                <option value="0.167">10 Seconds (Testing)</option>
                                <option value="5">5 Minutes</option>
                                <option value="10">10 Minutes</option>
                                <option value="15">15 Minutes</option>
                                <option value="20">20 Minutes</option>
                                <option value="30">30 Minutes</option>
                                <option value="45">45 Minutes</option>
                                <option value="60">60 Minutes</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="randomQuizForm" class="btn btn-warning">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Source Selection Modal -->
    <div class="modal fade" id="sourceSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-wrapper">
                        <div class="modal-icon-wrapper">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="modal-title-content">
                            <h5 class="modal-title mb-0">Select Question Sources</h5>
                            <p class="mb-0 text-muted small">Choose which sources to include in your quiz</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <form action="" method="GET" id="sourceSelectionForm">
                        <input type="hidden" name="course_id" id="source_course_id">
                        <input type="hidden" name="topic_id" id="source_topic_id">
                        
                        <div class="sources-container mb-3">
                            <!-- Source cards will be dynamically inserted here -->
                        </div>
                        
                        <!-- Simplified Mode Selection -->
                        <div class="d-flex align-items-center gap-3 mb-3 px-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="quizMode" value="quiz" checked>
                                <label class="form-check-label" for="quizMode">Quiz Mode</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="quiz_mode" id="tutorMode" value="tutor">
                                <label class="form-check-label" for="tutorMode">Tutor Mode</label>
                            </div>
                        </div>
                        
                        <div class="source-info">
                            <div class="source-info-title">
                                <i class="fas fa-info-circle"></i>
                                <span>Tip</span>
                            </div>
                            <p class="source-info-text">
                                Complete all sources to master this topic. Progress is tracked for each source.
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" form="sourceSelectionForm" class="btn btn-primary" id="startSourceQuizBtn">
                        <i class="fas fa-play me-2"></i>Start Quiz
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Header -->
    <header class="course-header">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="course-progress">
            <div class="progress-circle">
                {{ $topics->where('percentage_grade', 100)->count() }}/{{ $topics->count() }}
            </div>
            <div class="progress-text">
                <div class="progress-text-title">Course Progress</div>
                <div class="progress-text-subtitle">Topics Mastered</div>
            </div>
        </div>
    </header>

    <!-- Case Filters -->
    <div class="case-filters">
        <div class="case-filter active">
            <i class="fas fa-all"></i>
            All
            <span class="count">{{ $topics->count() }}</span>
        </div>
        <div class="case-filter">
            <i class="fas fa-file-alt"></i>
            Cases
            <span class="count">{{ $topics->where('case_type', 'cases')->count() }}</span>
        </div>
        <div class="case-filter">
            <i class="fas fa-flask"></i>
            Practical
            <span class="count">{{ $topics->where('case_type', 'practical')->count() }}</span>
        </div>
        <div class="case-filter">
            <i class="fas fa-question-circle"></i>
            Quiz
            <span class="count">{{ $topics->where('case_type', 'quiz')->count() }}</span>
        </div>
        <a href="#" class="nav-link random-link" data-bs-toggle="modal" data-bs-target="#randomQuizModal">
            <i class="fas fa-random"></i>
            Random
        </a>
        <a href="{{ route('review.index') }}" class="mistakes-link">
            <i class="fas fa-times-circle"></i>
            Review Mistakes
        </a>
    </div>

    <!-- Topics List -->
    <div class="topics-container">
        @if($topics->count() > 0)
            @foreach($topics as $topic)
                <div class="topic-card" data-case-type="{{ $topic->case_type }}" data-topic-id="{{ $topic->id }}">
                    <div class="topic-info">
                        <div class="topic-main">
                            <h3 class="topic-name">
                                <i class="fas fa-book-open"></i>
                                {{ $topic->name }}
                            </h3>
                            
                            @if($topic->last_attempt_date)
                                <div class="last-attempt">
                                    <i class="far fa-clock"></i>
                                    Last attempt: {{ \Carbon\Carbon::parse($topic->last_attempt_date)->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                        
                        @if($topic->percentage_grade !== null)
                            <div class="grade-badge 
                                {{ $topic->percentage_grade >= 100 ? 'grade-mastered' : 
                                   ($topic->percentage_grade >= 66 ? 'grade-good' : 
                                   ($topic->percentage_grade >= 33 ? 'grade-in-progress' : 'grade-bad')) }}">
                                <i class="fas {{ $topic->percentage_grade >= 100 ? 'fa-star' : 
                                               ($topic->percentage_grade >= 66 ? 'fa-check' : 
                                               ($topic->percentage_grade >= 33 ? 'fa-sync-alt' : 'fa-times')) }}"></i>
                                {{ $topic->percentage_grade >= 100 ? 'Mastered' : 
                                   ($topic->percentage_grade >= 66 ? 'Good' : 
                                   ($topic->percentage_grade >= 33 ? 'In Progress' : 'Just Started')) }}
                            </div>
                        @else
                            <div class="grade-badge grade-none">
                                <i class="fas fa-hourglass-start"></i>
                                Not Started
                            </div>
                        @endif
                    </div>

                    <div class="progress-container">
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ min(100, $topic->percentage_grade ?? 0) }}%"></div>
                        </div>
                        <div class="progress-label">
                            <span>Progress</span>
                            <span>{{ $topic->percentage_grade ? number_format(min(100, $topic->percentage_grade), 1) : 0 }}%</span>
                        </div>
                    </div>

                    <div class="topic-actions">
                        <div class="stat-item">
                            @if($topic->percentage_grade >= 100)
                                <i class="fas fa-check-circle text-success"></i>
                                Mastered
                            @elseif($topic->percentage_grade >= 66)
                                <i class="fas fa-check text-success"></i>
                                Good
                            @elseif($topic->percentage_grade >= 33)
                                <i class="fas fa-sync-alt text-info"></i>
                                Making progress
                            @elseif($topic->percentage_grade !== null)
                                <i class="fas fa-rocket text-primary"></i>
                                Just getting started
                            @else
                                <i class="fas fa-flag text-primary"></i>
                                Ready to start
                            @endif
                        </div>
                        
                        @php
                            $quizUrl = route('quiz.start', ['course' => $topic->course_id, 'topic' => $topic->id]);
                        @endphp
                        <a href="{{ $quizUrl }}" class="start-quiz-btn" data-topic-id="{{ $topic->id }}" data-course-id="{{ $topic->course_id }}">
                            @if($topic->percentage_grade !== null)
                                <span>Retake Quiz</span>
                            @else
                                <span>Start Quiz</span>
                            @endif
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="case-type-badge {{ $topic->case_type }}">{{ $topic->case_type }}</div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No Topics Available</h3>
                <p>Topics for this course will be added soon.</p>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.min.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Case filter functionality
            const caseFilters = document.querySelectorAll('.case-filter');
            const topicCards = document.querySelectorAll('.topic-card');

            caseFilters.forEach(filter => {
                filter.addEventListener('click', function() {
                    // Remove active class from all filters
                    caseFilters.forEach(f => f.classList.remove('active'));
                    
                    // Add active class to clicked filter
                    this.classList.add('active');
                    
                    // Get the case type from the filter text
                    const filterText = this.textContent.trim().toLowerCase();
                    let filterType = '';
                    
                    if (filterText.includes('all')) {
                        filterType = 'all';
                    } else if (filterText.includes('cases')) {
                        filterType = 'cases';
                    } else if (filterText.includes('practical')) {
                        filterType = 'practical';
                    } else if (filterText.includes('quiz')) {
                        filterType = 'quiz';
                    }
                    
                    // Filter topic cards
                    topicCards.forEach(card => {
                        if (filterType === 'all' || card.dataset.caseType === filterType) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Handle source progress updates from quiz completion
            document.addEventListener('sourceProgressUpdated', function(e) {
                const { topicId, progress, availableSources, allSourcesMastered } = e.detail;
                
                // Update source progress bars in the modal
                if (progress) {
                    updateSourcesContainer({
                        progress: progress,
                        availableSources: availableSources
                    });
                }
                
                // Update topic mastery status
                const topicCard = document.querySelector(`[data-topic-id="${topicId}"]`);
                if (topicCard) {
                    const gradeBadge = topicCard.querySelector('.grade-badge');
                    if (gradeBadge) {
                        gradeBadge.className = `grade-badge ${allSourcesMastered ? 'grade-mastered' : 'grade-good'}`;
                        gradeBadge.innerHTML = allSourcesMastered ? 
                            '<i class="fas fa-star"></i> Mastered' : 
                            '<i class="fas fa-check"></i> Good';
                    }
                }
            });

            // Source selection modal functionality
            const sourceSelectionModal = new bootstrap.Modal(document.getElementById('sourceSelectionModal'));
            
            // Handle mode change
            document.querySelectorAll('input[name="quiz_mode"]').forEach(radio => {
                radio.addEventListener('change', async function() {
                    const topicId = document.getElementById('source_topic_id').value;
                    if (!topicId) return;
                    
                    const sourcesContainer = document.querySelector('.sources-container');
                    sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                    try {
                        const response = await fetch(`/topics/${topicId}/source-progress?mode=${this.value}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        updateSourcesContainer(data);
                    } catch (error) {
                        console.error('Error fetching source progress:', error);
                        sourcesContainer.innerHTML = '<div class="alert alert-danger">Error loading sources. Please try again.</div>';
                    }
                });
            });

            // Helper function to update sources container
            function updateSourcesContainer(data) {
                const sourcesContainer = document.querySelector('.sources-container');
                        sourcesContainer.innerHTML = '';
                        
                        if (!data.availableSources || data.availableSources.length === 0) {
                            sourcesContainer.innerHTML = '<div class="alert alert-info">No questions available for this topic.</div>';
                            return;
                        }
                        
                        data.availableSources.forEach(source => {
                            const progress = data.progress[source] || { completed: 0, total: 0, percentage: 0 };
                            const card = document.createElement('div');
                            card.className = 'source-card';
                            card.setAttribute('data-source', source);

                            // Add selected class if there are questions
                    if (progress.total_questions_available > 0) {
                                card.classList.add('selected');
                            }

                    // Determine status class based on percentage
                            const statusClass = progress.percentage >= 90 ? 'completed' : 
                                      progress.percentage >= 50 ? 'in-progress' :
                                      progress.percentage > 0 ? 'failed' :
                                              'not-started';

                            card.innerHTML = `
                                <div class="source-status ${statusClass}"></div>
                                <div class="source-content">
                                    <div class="source-info-wrapper">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" name="sources[]" value="${source}" 
                                               class="form-check-input" ${progress.total_questions_available > 0 ? 'checked' : ''}>
                                                <span class="ms-2 source-name">${source}</span>
                                            </div>
                                    <div class="source-status-text ${statusClass}">
                                                ${progress.percentage >= 90 ? 
                                                    '<i class="fas fa-check-circle me-1"></i>Completed' : 
                                                    progress.percentage >= 50 ? 
                                                    '<i class="fas fa-clock me-1"></i>In Progress' :
                                                    progress.percentage > 0 ?
                                                    '<i class="fas fa-times-circle me-1"></i>Failed' :
                                                    '<i class="fas fa-circle me-1"></i>Not Started'}
                                            </div>
                                        </div>
                                        <div class="source-progress-bar">
                                    <div class="source-progress-value ${statusClass}" 
                                                 style="width: ${progress.percentage}%;">
                                            </div>
                                        </div>
                                        <div class="source-stats">
                                            <span class="questions-count">
                                        <i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;

                            sourcesContainer.appendChild(card);

                            // Add click handler for the entire card
                            card.addEventListener('click', function(e) {
                                if (e.target.type === 'checkbox') return;
                                const checkbox = this.querySelector('input[type="checkbox"]');
                                checkbox.checked = !checkbox.checked;
                                this.classList.toggle('selected', checkbox.checked);
                                
                                // Ensure at least one source is selected
                                const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                                if (!anyChecked) {
                                    checkbox.checked = true;
                                    this.classList.add('selected');
                                }
                            });
                        });
            }
            
            // Handle quiz button clicks
            document.querySelectorAll('.start-quiz-btn').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const topicId = this.dataset.topicId;
                    const courseId = this.dataset.courseId;
                    
                    document.getElementById('source_topic_id').value = topicId;
                    document.getElementById('source_course_id').value = courseId;
                    document.getElementById('sourceSelectionForm').action = this.href;
                    
                    // Fetch and update source progress
                    const sourcesContainer = document.querySelector('.sources-container');
                    sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                    try {
                        const selectedMode = document.querySelector('input[name="quiz_mode"]:checked').value;
                        const response = await fetch(`/topics/${topicId}/source-progress?mode=${selectedMode}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        updateSourcesContainer(data);
                    } catch (error) {
                        console.error('Error fetching source progress:', error);
                        console.log('Topic ID:', topicId);
                        console.log('Attempted URL:', `/topics/${topicId}/source-progress`);
                        sourcesContainer.innerHTML = '<div class="alert alert-danger">Error loading sources. Please try again. Details: ' + error.message + '</div>';
                    }
                    
                    sourceSelectionModal.show();
                });
            });

            // Form submission validation
            document.getElementById('sourceSelectionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const checkedSources = this.querySelectorAll('input[name="sources[]"]:checked');
                if (checkedSources.length === 0) {
                    alert('Please select at least one source to start the quiz.');
                    return;
                }

                const selectedMode = this.querySelector('input[name="quiz_mode"]:checked').value;
                const courseId = this.querySelector('#source_course_id').value;
                const topicId = this.querySelector('#source_topic_id').value;
                
                // Build the URL with selected sources
                const selectedSources = Array.from(checkedSources).map(cb => cb.value);
                const sourceParams = selectedSources.map(s => `sources[]=${s}`).join('&');
                
                // Redirect to appropriate route based on mode
                const baseUrl = selectedMode === 'tutor' 
                    ? `/quiz/${courseId}/${topicId}/tutor`
                    : `/quiz/${courseId}/${topicId}`;
                    
                window.location.href = `${baseUrl}?${sourceParams}`;
            });
        });
    </script>
</body>
</html>