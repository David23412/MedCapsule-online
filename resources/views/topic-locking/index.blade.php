<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Management - MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
            text-decoration: none;
        }
        
        .back-btn {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            background: #e9ecef;
            border-color: #dee2e6;
            transform: translateX(-2px);
        }
        
        .main-content {
            margin-top: 80px;
            padding: 0;
            min-height: calc(100vh - 80px);
        }
        
        .topic-management {
            width: 100%;
            background: white;
            min-height: calc(100vh - 80px);
        }
        
        .management-header {
            background: white;
            padding: 1.5rem 3rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .management-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 0.25rem 0;
        }
        
        .management-subtitle {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .management-controls {
            background: #f8fafc;
            padding: 1rem 3rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
        }
        
        .search-input:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.9rem;
        }
        
        .control-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .control-btn {
            background: white;
            border: 2px solid #e2e8f0;
            color: #4a5568;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .control-btn:hover {
            border-color: #4299e1;
            color: #4299e1;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem 3rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-icon {
            font-size: 1.25rem;
            color: #718096;
            margin-bottom: 0.25rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
        }
        
        .stat-card.primary .stat-number { color: #4299e1; }
        .stat-card.primary .stat-icon { color: #4299e1; }
        
        .stat-card.success .stat-number { color: #10b981; }
        .stat-card.success .stat-icon { color: #10b981; }
        
        .stat-card.danger .stat-number { color: #ef4444; }
        .stat-card.danger .stat-icon { color: #ef4444; }
        
        .management-body {
            padding: 0;
        }
        
        .course-section {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .course-section:last-child {
            border-bottom: none;
        }
        
        .course-header {
            background: white;
            padding: 2rem 3rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .course-header:hover {
            background: #f8fafc;
            border-left-color: #4299e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .course-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .course-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2d3748;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .course-details h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.025em;
        }
        
        .course-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: #718096;
            margin-top: 0.25rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .meta-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .meta-item i {
            font-size: 0.875rem;
        }
        
        .meta-item:first-child {
            color: #4299e1;
            background: #ebf8ff;
            border-color: #bee3f8;
        }
        
        .meta-item:first-child i {
            color: #4299e1;
        }
        
        .meta-item:nth-child(2) {
            color: #ef4444;
            background: #fff5f5;
            border-color: #fed7d7;
        }
        
        .meta-item:nth-child(2) i {
            color: #ef4444;
        }
        
        .meta-item:nth-child(3) {
            color: #10b981;
            background: #f0fff4;
            border-color: #c6f6d5;
        }
        
        .meta-item:nth-child(3) i {
            color: #10b981;
        }
        
        .course-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .select-all-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #4299e1;
        }
        
        .expand-btn {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #718096;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .expand-btn:hover {
            border-color: #4299e1;
            color: #4299e1;
        }
        
        .expand-btn.expanded {
            transform: rotate(180deg);
        }
        
        .topics-container {
            background: #f8fafc;
            padding: 1rem 3rem;
            display: none;
        }
        
        .topics-container.expanded {
            display: block;
        }
        
        .topic-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: border-color 0.35s ease, background 0.35s ease, box-shadow 0.35s ease, transform 0.2s ease;
            position: relative;
        }
        
        .topic-item:hover {
            border-color: #bee3f8;
            background: #f7fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .topic-item:last-child {
            margin-bottom: 0;
        }
        
        .topic-item.locked {
            border-left: 4px solid #ef4444;
            transition: border-color 0.35s ease, background 0.35s ease, border-left-color 0.35s ease;
        }
        
        .topic-item.unlocked {
            border-left: 4px solid #10b981;
            transition: border-color 0.35s ease, background 0.35s ease, border-left-color 0.35s ease;
        }
        
        .topic-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #4299e1;
        }
        
        .topic-info {
            flex: 1;
        }
        
        .topic-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin: 0 0 0.25rem 0;
        }
        
        .topic-meta {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .topic-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: background 0.35s ease, color 0.35s ease;
        }
        
        .topic-status.locked {
            background: #fed7d7;
            color: #9b2c2c;
        }
        
        .topic-status.unlocked {
            background: #c6f6d5;
            color: #276749;
        }
        
        .topic-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            background: white;
            border: 1px solid #e2e8f0;
            color: #718096;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .action-btn.lock {
            color: #ef4444;
            border-color: #fecaca;
        }
        
        .action-btn.lock:hover {
            background: #fef2f2;
            border-color: #f87171;
            color: #dc2626;
        }
        
        .action-btn.unlock {
            color: #10b981;
            border-color: #d1fae5;
        }
        
        .action-btn.unlock:hover {
            background: #f0fdf4;
            border-color: #34d399;
            color: #059669;
        }
        
        .selection-summary {
            background: #edf2f7;
            padding: 1rem 3rem;
            border-top: 1px solid #e2e8f0;
            display: none;
            justify-content: space-between;
            align-items: center;
        }
        
        .selection-summary.active {
            display: flex;
        }
        
        .selection-info {
            font-size: 0.875rem;
            color: #4a5568;
            font-weight: 500;
        }
        
        .bulk-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .bulk-btn {
            background: white;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .bulk-btn.danger {
            border-color: #fed7d7;
            color: #9b2c2c;
        }
        
        .bulk-btn.danger:hover {
            background: #fed7d7;
        }
        
        .bulk-btn.success {
            border-color: #c6f6d5;
            color: #276749;
        }
        
        .bulk-btn.success:hover {
            background: #c6f6d5;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #a0aec0;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #4299e1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 10;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Success/Error States */
        .success-flash {
            animation: successFlash 0.6s ease-out;
        }
        
        .error-flash {
            animation: errorFlash 0.6s ease-out;
        }
        
        @keyframes successFlash {
            0% { background-color: #c6f6d5; }
            100% { background-color: transparent; }
        }
        
        @keyframes errorFlash {
            0% { background-color: #fed7d7; }
            100% { background-color: transparent; }
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1100;
        }
        
        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            min-width: 300px;
            margin-bottom: 10px;
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .toast-body {
            padding: 12px 16px;
            font-size: 0.9rem;
        }
        
        .toast.success .toast-header {
            color: #276749;
            background: #f0fff4;
        }
        
        .toast.error .toast-header {
            color: #9b2c2c;
            background: #fffaf0;
        }
        
        /* Improved Mobile Responsiveness */
        @media (max-width: 1024px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .management-header {
                padding: 1rem 1.5rem;
            }
            
            .management-controls {
                padding: 1rem 1.5rem;
            }
            
            .stats-row {
                padding: 1rem 1.5rem;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .main-content {
                margin-top: 70px;
                padding: 0.5rem;
            }
            
            .management-header {
                padding: 1rem;
                text-align: center;
            }
            
            .management-title {
                font-size: 1.25rem;
            }
            
            .management-controls {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 0.75rem;
            }
            
            .search-container {
                max-width: none;
            }
            
            .control-buttons {
                justify-content: center;
            }
            
            .stats-row {
                grid-template-columns: 1fr 1fr;
                padding: 1rem;
                gap: 0.5rem;
            }
            
            .stat-card {
                padding: 0.75rem;
            }
            
            .stat-number {
                font-size: 1.25rem;
            }
            
            .course-header {
                padding: 1rem;
                flex-direction: column;
                gap: 0.75rem;
                align-items: stretch;
            }
            
            .course-info {
                justify-content: center;
                text-align: center;
            }
            
            .course-meta {
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .course-actions {
                justify-content: space-between;
                align-items: center;
            }
            
            .topics-container {
                padding: 0.75rem 1rem;
            }
            
            .topic-item {
                padding: 0.75rem;
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            
            .topic-info {
                text-align: center;
            }
            
            .topic-status {
                align-self: center;
            }
            
            .topic-actions {
                justify-content: center;
            }
            
            .selection-summary {
                padding: 1rem;
                flex-direction: column;
                gap: 0.75rem;
                text-align: center;
            }
            
            .bulk-actions {
                justify-content: center;
            }
            
            .toast-container {
                top: 80px;
                right: 10px;
                left: 10px;
            }
            
            .toast {
                min-width: auto;
            }
        }
        
        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .course-meta {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .topic-item {
                padding: 0.5rem;
            }
            
            .bulk-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .bulk-btn {
                justify-content: center;
            }
        }
        
        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .action-btn, .expand-btn, .control-btn {
                min-width: 44px;
                min-height: 44px;
            }
            
            .topic-checkbox, .select-all-checkbox {
                width: 20px;
                height: 20px;
            }
            
            .course-header {
                padding: 1.25rem 1rem;
            }
            
            .topic-item {
                padding: 1rem;
            }
        }
        
        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus states */
        .action-btn:focus,
        .expand-btn:focus,
        .control-btn:focus,
        .bulk-btn:focus {
            outline: 2px solid #4299e1;
            outline-offset: 2px;
        }
        
        .topic-checkbox:focus,
        .select-all-checkbox:focus {
            outline: 2px solid #4299e1;
            outline-offset: 2px;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        .course-header,
        .topic-item {
            transition: all 0.15s ease;
        }
        
        /* Improved hover states for better feedback */
        .course-header:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .topic-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover,
        .expand-btn:hover,
        .control-btn:hover {
            transform: translateY(-1px);
        }
        
        /* Loading skeleton for better perceived performance */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
                <a href="{{ route('courses.index') }}" class="nav-link back-btn">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="topic-management">
            <div class="management-header">
                <h1 class="management-title">Topic Management</h1>
                <p class="management-subtitle">Manage topic access and permissions across all courses</p>
            </div>

            <div class="stats-row">
                <div class="stat-card primary">
                    <i class="fas fa-graduation-cap stat-icon"></i>
                    <div class="stat-number">{{ $courses->count() }}</div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-list stat-icon"></i>
                    <div class="stat-number">
                        @php
                            $totalTopics = 0;
                            foreach($courses as $course) {
                                $totalTopics += $course->topics->count();
                            }
                        @endphp
                        {{ $totalTopics }}
                    </div>
                    <div class="stat-label">Topics</div>
                </div>
                <div class="stat-card danger">
                    <i class="fas fa-lock stat-icon"></i>
                    <div class="stat-number">
                        @php
                            $lockedTopics = 0;
                            foreach($courses as $course) {
                                $lockedTopics += $course->topics->where('is_locked', true)->count();
                            }
                        @endphp
                        {{ $lockedTopics }}
                    </div>
                    <div class="stat-label">Locked</div>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-unlock stat-icon"></i>
                    <div class="stat-number">
                        @php
                            $unlockedTopics = 0;
                            foreach($courses as $course) {
                                $unlockedTopics += $course->topics->where('is_locked', false)->count();
                            }
                        @endphp
                        {{ $unlockedTopics }}
                    </div>
                    <div class="stat-label">Unlocked</div>
                </div>
            </div>

            <div class="management-controls">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="globalSearch" placeholder="Search courses or topics...">
                </div>
                <div class="control-buttons">
                    <button class="control-btn" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="management-body">
                @if($courses->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No courses available</h3>
                        <p>Contact an administrator to add courses for topic management.</p>
                    </div>
                @else
{{-- 💀 N+1 FIX NEEDED: Eager load 'id' in controller --}}
                    @foreach($courses as $course)
                        <div class="course-section" data-course-id="{{ $course->id }}">
                            <div class="course-header" onclick="toggleCourse({{ $course->id }})">
                                <div class="course-info">
                                    <div class="course-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="course-details">
                                        <h3>{{ $course->name }}</h3>
                                        <div class="course-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-list"></i>
                                                {{ $course->topics->count() }} topics
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-lock"></i>
{{-- TODO: Move this query to controller --}}
{{-- {{ $course->topics->where('is_locked', true)->count() }} locked --}}
{{-- Use: Pass data from controller instead --}}
                                            </span>
{{-- TODO: Move this query to controller --}}
{{-- <span class="meta-item"> --}}
{{-- Use: Pass data from controller instead --}}
                                                <i class="fas fa-unlock"></i>
                                                {{ $course->topics->where('is_locked', false)->count() }} unlocked
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="course-actions" onclick="event.stopPropagation()">
                                    <div class="select-all-container">
                                        <input type="checkbox" class="select-all-checkbox" id="selectAll{{ $course->id }}" data-course-id="{{ $course->id }}" onchange="toggleSelectAll({{ $course->id }})">
                                        <label for="selectAll{{ $course->id }}">Select All</label>
                                    </div>
                                    <button class="expand-btn" id="expandBtn{{ $course->id }}" onclick="toggleCourse({{ $course->id }})">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="topics-container" id="topics{{ $course->id }}">
                                @foreach($course->topics as $topic)
                                    <div class="topic-item {{ $topic->is_locked ? 'locked' : 'unlocked' }}" data-topic-id="{{ $topic->id }}" data-course-id="{{ $course->id }}">
                                        <input type="checkbox" class="topic-checkbox" id="topic{{ $topic->id }}" 
                                               value="{{ $topic->id }}" data-course-id="{{ $course->id }}" onchange="updateSelection()">
                                        
                                        <div class="topic-info">
                                            <div class="topic-name">{{ $topic->name }}</div>
                                        </div>
                                        
                                        <div class="topic-status {{ $topic->is_locked ? 'locked' : 'unlocked' }}">
                                            <i class="fas fa-{{ $topic->is_locked ? 'lock' : 'unlock' }}"></i>
                                            {{ $topic->is_locked ? 'Locked' : 'Available' }}
                                        </div>
                                        
                                        <div class="topic-actions">
                                            @if($topic->is_locked)
                                                <button class="action-btn unlock" onclick="unlockTopic({{ $topic->id }})" title="Click to unlock this topic" data-topic-id="{{ $topic->id }}">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            @else
                                                <button class="action-btn lock" onclick="lockTopic({{ $topic->id }})" title="Click to lock this topic" data-topic-id="{{ $topic->id }}">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="selection-summary" id="selectionSummary">
                <div class="selection-info">
                    <span id="selectedCount">0</span> topics selected
                </div>
                <div class="bulk-actions">
                    <button class="bulk-btn danger" id="bulkLockBtn" onclick="bulkLock()">
                        <i class="fas fa-lock"></i>
                        Lock Selected
                    </button>
                    <button class="bulk-btn success" id="bulkUnlockBtn" onclick="bulkUnlock()">
                        <i class="fas fa-unlock"></i>
                        Unlock Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentTopicId = null;
        let selectedTopics = new Set();

        // Toggle course expansion
        function toggleCourse(courseId) {
            const container = document.getElementById(`topics${courseId}`);
            const btn = document.getElementById(`expandBtn${courseId}`);
            
            if (container.classList.contains('expanded')) {
                container.classList.remove('expanded');
                btn.classList.remove('expanded');
            } else {
                container.classList.add('expanded');
                btn.classList.add('expanded');
            }
        }

        // Toggle select all for course
        function toggleSelectAll(courseId) {
            const selectAllCheckbox = document.getElementById(`selectAll${courseId}`);
            const topicCheckboxes = document.querySelectorAll(`input[data-course-id="${courseId}"].topic-checkbox`);
            
            topicCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
                if (selectAllCheckbox.checked) {
                    selectedTopics.add(parseInt(checkbox.value));
                } else {
                    selectedTopics.delete(parseInt(checkbox.value));
                }
            });
            
            updateSelection();
        }

        // Update selection display
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.topic-checkbox');
            selectedTopics.clear();
            
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedTopics.add(parseInt(checkbox.value));
                }
            });
            
            const count = selectedTopics.size;
            document.getElementById('selectedCount').textContent = count;
            
            const summary = document.getElementById('selectionSummary');
            if (count > 0) {
                summary.classList.add('active');
            } else {
                summary.classList.remove('active');
            }
        }

        // Instantly update a single topic's DOM to reflect locked/unlocked state
        function updateTopicDOM(topicId, isLocked) {
            const topicItem = document.querySelector(`.topic-item[data-topic-id="${topicId}"]`);
            if (!topicItem) return;

            // Toggle item classes with smooth transition
            topicItem.classList.toggle('locked', isLocked);
            topicItem.classList.toggle('unlocked', !isLocked);

            // Update status badge
            const statusEl = topicItem.querySelector('.topic-status');
            if (statusEl) {
                statusEl.classList.toggle('locked', isLocked);
                statusEl.classList.toggle('unlocked', !isLocked);
                statusEl.innerHTML = isLocked
                    ? '<i class="fas fa-lock"></i> Locked'
                    : '<i class="fas fa-unlock"></i> Available';
            }

            // Swap action button
            const actionsEl = topicItem.querySelector('.topic-actions');
            if (actionsEl) {
                if (isLocked) {
                    actionsEl.innerHTML = `<button class="action-btn unlock" onclick="unlockTopic(${topicId})" title="Click to unlock this topic" data-topic-id="${topicId}"><i class="fas fa-unlock"></i></button>`;
                } else {
                    actionsEl.innerHTML = `<button class="action-btn lock" onclick="lockTopic(${topicId})" title="Click to lock this topic" data-topic-id="${topicId}"><i class="fas fa-lock"></i></button>`;
                }
            }

            // Uncheck selection
            const checkbox = topicItem.querySelector('.topic-checkbox');
            if (checkbox) checkbox.checked = false;

            // Brief highlight flash
            topicItem.style.boxShadow = isLocked ? '0 0 0 2px #ef4444' : '0 0 0 2px #10b981';
            setTimeout(() => { topicItem.style.boxShadow = ''; }, 600);
        }

        // Update the header stats counters without reload
        function updateHeaderStats() {
            const allItems = document.querySelectorAll('.topic-item');
            const lockedCount = document.querySelectorAll('.topic-item.locked').length;
            const unlockedCount = document.querySelectorAll('.topic-item.unlocked').length;

            // Update stat cards if they exist
            const statValues = document.querySelectorAll('.stat-value');
            if (statValues.length >= 3) {
                statValues[0].textContent = allItems.length;   // Total
                statValues[1].textContent = lockedCount;       // Locked
                statValues[2].textContent = unlockedCount;     // Unlocked
            }

            // Update per-course counters
            document.querySelectorAll('.course-section').forEach(section => {
                const courseItems = section.querySelectorAll('.topic-item');
                const courseLocked = section.querySelectorAll('.topic-item.locked').length;
                const courseUnlocked = section.querySelectorAll('.topic-item.unlocked').length;

                const courseStats = section.querySelectorAll('.course-stat-value');
                if (courseStats.length >= 2) {
                    courseStats[0].textContent = courseLocked;
                    courseStats[1].textContent = courseUnlocked;
                }
            });
        }

        // Lock single topic - instant
        function lockTopic(topicId) {
            console.log('Locking topic:', topicId);
            const button = document.querySelector(`button[data-topic-id="${topicId}"].lock`);
            if (button) {
                button.classList.add('loading');
            }
            
            // Optimistic update — reflect change instantly
            updateTopicDOM(topicId, true);
            updateHeaderStats();
            showToast('Topic locked successfully', 'success');
            
            fetch(`/topic-locking/${topicId}/lock`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: '' // No reason needed for instant lock
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Try to get response text first
                return response.text().then(text => {
                    console.log('Response text:', text);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                    }
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error(`Invalid JSON response: ${text}`);
                    }
                });
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Already updated optimistically — no reload needed
                } else {
                    // Revert optimistic update on failure
                    updateTopicDOM(topicId, false);
                    updateHeaderStats();
                    showToast('Error: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert optimistic update on network error
                updateTopicDOM(topicId, false);
                updateHeaderStats();
                showToast('Error: ' + error.message, 'error');
            });
        }

        // Unlock single topic - instant
        function unlockTopic(topicId) {
            console.log('Unlocking topic:', topicId);
            const button = document.querySelector(`button[data-topic-id="${topicId}"].unlock`);
            if (button) {
                button.classList.add('loading');
            }
            
            // Optimistic update — reflect change instantly
            updateTopicDOM(topicId, false);
            updateHeaderStats();
            showToast('Topic unlocked successfully', 'success');
            
            fetch(`/topic-locking/${topicId}/unlock`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Already updated optimistically — no reload needed
                } else {
                    // Revert optimistic update on failure
                    updateTopicDOM(topicId, true);
                    updateHeaderStats();
                    showToast('Error: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert optimistic update on network error
                updateTopicDOM(topicId, true);
                updateHeaderStats();
                showToast('Error: ' + error.message, 'error');
            });
        }

        // Bulk lock - instant
        function bulkLock() {
            if (selectedTopics.size === 0) return;
            
            const button = document.getElementById('bulkLockBtn');
            button.classList.add('loading');

            const topicIds = Array.from(selectedTopics);
            const count = topicIds.length;

            // Optimistic update — reflect changes instantly
            topicIds.forEach(id => updateTopicDOM(id, true));
            updateHeaderStats();
            selectedTopics.clear();
            updateSelection();
            showToast(`${count} topics locked successfully`, 'success');
            button.classList.remove('loading');
            
            fetch('/topic-locking/bulk-lock', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    topic_ids: topicIds,
                    reason: '' // No reason needed for instant lock
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    topicIds.forEach(id => updateTopicDOM(id, false));
                    updateHeaderStats();
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                topicIds.forEach(id => updateTopicDOM(id, false));
                updateHeaderStats();
                showToast('An error occurred while locking topics.', 'error');
            });
        }

        // Bulk unlock - instant
        function bulkUnlock() {
            if (selectedTopics.size === 0) return;
            
            const button = document.getElementById('bulkUnlockBtn');
            button.classList.add('loading');

            const topicIds = Array.from(selectedTopics);
            const count = topicIds.length;

            // Optimistic update — reflect changes instantly
            topicIds.forEach(id => updateTopicDOM(id, false));
            updateHeaderStats();
            selectedTopics.clear();
            updateSelection();
            showToast(`${count} topics unlocked successfully`, 'success');
            button.classList.remove('loading');
            
            fetch('/topic-locking/bulk-unlock', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    topic_ids: topicIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    topicIds.forEach(id => updateTopicDOM(id, true));
                    updateHeaderStats();
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                topicIds.forEach(id => updateTopicDOM(id, true));
                updateHeaderStats();
                showToast('An error occurred while unlocking topics.', 'error');
            });
        }

        // Toast notification function
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                    <strong>${type === 'success' ? 'Success' : 'Error'}</strong>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Hide and remove toast
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        }

        // Search functionality
        document.getElementById('globalSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const courseHeaders = document.querySelectorAll('.course-header');
            const topicItems = document.querySelectorAll('.topic-item');
            
            courseHeaders.forEach(header => {
                const courseName = header.querySelector('h3').textContent.toLowerCase();
                const courseSection = header.closest('.course-section');
                let hasVisibleTopics = false;
                
                // Check topics in this course
                const courseTopics = courseSection.querySelectorAll('.topic-item');
                courseTopics.forEach(topic => {
                    const topicName = topic.querySelector('.topic-name').textContent.toLowerCase();
                    if (topicName.includes(searchTerm) || courseName.includes(searchTerm)) {
                        topic.style.display = 'flex';
                        hasVisibleTopics = true;
                    } else {
                        topic.style.display = 'none';
                    }
                });
                
                // Show/hide course based on search results
                if (hasVisibleTopics || courseName.includes(searchTerm)) {
                    courseSection.style.display = 'block';
                    if (searchTerm) {
                        // Auto-expand when searching
                        const container = courseSection.querySelector('.topics-container');
                        const btn = courseSection.querySelector('.expand-btn');
                        container.classList.add('expanded');
                        btn.classList.add('expanded');
                    }
                } else {
                    courseSection.style.display = 'none';
                }
            });
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            location.reload();
        });
    </script>
</body>
</html>
