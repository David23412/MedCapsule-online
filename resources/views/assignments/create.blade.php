<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - MedCapsule</title>
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
            margin-top: 60px;
            padding: 1rem;
        }
        
        .assignment-wizard {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .wizard-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .wizard-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 0.25rem 0;
        }
        
        .wizard-subtitle {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .wizard-body {
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-field {
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
            outline: none;
        }
        
        .form-control:disabled, .form-select:disabled {
            background: #f7fafc;
            color: #a0aec0;
        }
        
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        
        .quick-btn {
            background: #edf2f7;
            border: 1px solid #e2e8f0;
            color: #4a5568;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-weight: 500;
        }
        
        .quick-btn:hover {
            background: #4299e1;
            border-color: #4299e1;
            color: white;
            transform: translateY(-1px);
        }
        
        .student-selector-container {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Student Search Styles */
        .student-search-container {
            background: white;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .search-input-wrapper:focus-within {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .search-icon {
            color: #718096;
            margin-left: 0.75rem;
            font-size: 0.875rem;
        }
        
        .student-search-input {
            flex: 1;
            border: none;
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
            background: transparent;
            outline: none;
        }
        
        .student-search-input::placeholder {
            color: #a0aec0;
        }
        
        .clear-search-btn {
            background: transparent;
            border: none;
            color: #718096;
            padding: 0.5rem;
            margin-right: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .clear-search-btn:hover {
            background: #f7fafc;
            color: #4a5568;
        }
        
        .search-stats {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #718096;
            font-weight: 500;
        }
        
        .student-item.hidden {
            display: none;
        }
        
        .student-item.highlighted {
            background: #ebf8ff;
            border-color: #4299e1;
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.2);
        }
        
        .student-item.highlighted .student-name {
            color: #2b6cb0;
        }
        
        .no-search-results {
            padding: 2rem;
            text-align: center;
        }
        
        .no-search-results .empty-state {
            color: #a0aec0;
        }
        
        .no-search-results .empty-state small {
            color: #cbd5e1;
            font-size: 0.8rem;
        }
        
        .selector-header {
            background: white;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .selector-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .selector-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .student-count {
            background: #edf2f7;
            color: #4a5568;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .selector-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .control-btn {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #718096;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-weight: 500;
        }
        
        .control-btn:hover {
            border-color: #4299e1;
            color: #4299e1;
        }
        
        .student-list {
            max-height: 280px;
            overflow-y: auto;
            padding: 0.75rem;
        }
        
        .student-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .student-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .student-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .student-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 0.875rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
            position: relative;
            overflow: hidden;
        }
        
        .student-item::after {
            content: 'Click to select';
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.7rem;
            color: #a0aec0;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        
        .student-item:hover::after {
            opacity: 1;
        }
        
        .student-item.selected::after {
            content: 'Selected';
            color: #4299e1;
            font-weight: 600;
            opacity: 1;
        }
        
        .student-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(66, 153, 225, 0.05) 0%, rgba(66, 153, 225, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }
        
        .student-item:hover {
            border-color: #bee3f8;
            background: #f7fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .student-item:hover::before {
            opacity: 1;
        }
        
        .student-item.selected {
            border-color: #4299e1;
            background: #ebf8ff;
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.15);
        }
        
        .student-item.selected::before {
            opacity: 1;
        }
        
        .student-item:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .student-checkbox {
            width: 20px;
            height: 20px;
            margin-right: 0.875rem;
            accent-color: #4299e1;
            cursor: pointer;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }
        
        .student-checkbox:checked {
            transform: scale(1.1);
            transition: transform 0.15s ease;
        }
        
        .student-info {
            flex: 1;
            min-width: 0; /* Prevents flex item from overflowing */
        }
        
        .student-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin-bottom: 0.125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .student-username {
            color: #718096;
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .selection-summary {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #4a5568;
            transition: all 0.2s ease;
        }
        
        .selection-summary.has-selection {
            border-color: #4299e1;
            background: #f7fafc;
            color: #2d3748;
        }
        
        .wizard-footer {
            background: #f8fafc;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-create {
            background: #007bff;
            border: none;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-create:hover:not(:disabled) {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-create:disabled {
            background: #007bff;
            cursor: wait;
            transform: scale(0.98);
            opacity: 0.8;
        }
        
        .btn-create:active:not(:disabled) {
            transform: scale(0.98);
        }
        
        .btn-cancel {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #718096;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .btn-cancel:hover {
            border-color: #cbd5e0;
            color: #4a5568;
            text-decoration: none;
        }
        
        .loading-state {
            display: none;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
            font-size: 0.875rem;
        }
        
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #4299e1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        

        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin: 1rem 2rem;
            border: none;
        }
        
        .alert-danger {
            background: #fed7d7;
            color: #9b2c2c;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #a0aec0;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 0.5rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .wizard-body {
                padding: 1.5rem;
            }
            
            .wizard-footer {
                padding: 1.5rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-create, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
            
            .student-list {
                max-height: 240px;
            }
            
            .student-search-container {
                padding: 0.75rem 1rem;
            }
            
            .search-input-wrapper {
                flex-direction: row;
            }
            
            .student-search-input {
                font-size: 0.875rem;
            }
            
            .student-item {
                padding: 1rem;
                margin-bottom: 0.75rem;
                min-height: 60px;
            }
            
            .student-checkbox {
                width: 22px;
                height: 22px;
                margin-right: 1rem;
            }
            
            .student-name {
                font-size: 1rem;
            }
            
            .student-username {
                font-size: 0.875rem;
            }
            
            .student-item::after {
                display: none; /* Hide on mobile for cleaner look */
            }
        }
        
        .field-required {
            color: #e53e3e;
            font-weight: 600;
        }

        /* LETHAL PRELOADER STYLES - PREVENTS FOUC */
        .page-preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .page-preloader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .preloader-content {
            text-align: center;
        }

        .preloader-text {
            margin-top: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #007bff;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        .success-feedback {
            color: #38a169;
            font-weight: 500;
        }
        
        .duration-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .duration-btn {
            background: white;
            border: 2px solid #e2e8f0;
            color: #4a5568;
            padding: 0.75rem 0.5rem;
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            font-weight: 500;
        }
        
        .duration-btn:hover {
            border-color: #4299e1;
            background: #f7fafc;
            transform: translateY(-1px);
        }
        
        .duration-btn.active {
            border-color: #4299e1;
            background: #ebf8ff;
            color: #2d3748;
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.15);
        }
        
        .duration-btn i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <!-- Preloader to prevent FOUC -->
    <div class="page-preloader" id="pagePreloader">
        <div class="preloader-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="preloader-text">MedCapsule</div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="navbar" style="opacity: 0; transition: opacity 0.3s ease;">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="{{ route('home') }}" class="nav-link back-btn">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="assignment-wizard">
        <div class="wizard-header">
            <h1 class="wizard-title">Create Assignment</h1>
            <p class="wizard-subtitle">Set up a new assignment in seconds</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Fix these issues:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('assignments.store') }}" method="POST" id="assignmentForm">
            <div class="wizard-body">
                @csrf
                
                <!-- Course & Topic -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-book text-primary"></i>
                        Course & Topic
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="course_id" class="form-label">Course <span class="field-required">*</span></label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Choose course...</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->topics_count }} topics)</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="topic_id" class="form-label">Topic <span class="field-required">*</span></label>
                            <select class="form-select" id="topic_id" name="topic_id" required disabled>
                                <option value="">Select course first...</option>
                            </select>

                        </div>
                    </div>
                </div>

                <!-- Assignment Settings -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog text-primary"></i>
                        Assignment Settings
                    </h3>
                    
                    <div class="form-field">
                        <label for="title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Leave blank to use topic name">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="assignment_mode" class="form-label">Mode <span class="field-required">*</span></label>
                            <select class="form-select" id="assignment_mode" name="assignment_mode" required>
                                <option value="complete_topic">Complete Topic</option>
                                <option value="source_specific">Specific Source</option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="assigned_source" class="form-label">Source <span id="source-required" class="field-required" style="display: none;">*</span></label>
                            <select class="form-select" id="assigned_source" name="assigned_source" disabled>
                                <option value="">Select topic first...</option>
                            </select>
                            <div class="loading-state" id="sourceLoading">
                                <div class="spinner"></div>
                                Loading sources...
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="duration_minutes" class="form-label">Duration</label>
                            <select class="form-select" id="duration_minutes" name="duration_minutes">
                                <option value="">⏱️ No Time Limit</option>
                                <option value="0.17">10 seconds (testing)</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                                <option value="300">5 hours</option>
                                <option value="360">6 hours</option>
                                <option value="480">8 hours</option>
                                <option value="720">12 hours</option>
                                <option value="1440">24 hours</option>
                            </select>
                            <div class="duration-buttons">
                                <button type="button" class="duration-btn" data-duration="">
                                    <i class="fas fa-infinity"></i>
                                    No Limit
                                </button>
                                <button type="button" class="duration-btn" data-duration="0.17">
                                    <i class="fas fa-bolt"></i>
                                    10s
                                </button>
                                <button type="button" class="duration-btn" data-duration="15">
                                    <i class="fas fa-clock"></i>
                                    15m
                                </button>
                                <button type="button" class="duration-btn" data-duration="30">
                                    <i class="fas fa-clock"></i>
                                    30m
                                </button>
                                <button type="button" class="duration-btn" data-duration="45">
                                    <i class="fas fa-clock"></i>
                                    45m
                                </button>
                                <button type="button" class="duration-btn active" data-duration="60">
                                    <i class="fas fa-hourglass-half"></i>
                                    1h
                                </button>
                                <button type="button" class="duration-btn" data-duration="90">
                                    <i class="fas fa-hourglass-half"></i>
                                    1.5h
                                </button>
                                <button type="button" class="duration-btn" data-duration="120">
                                    <i class="fas fa-hourglass"></i>
                                    2h
                                </button>
                                <button type="button" class="duration-btn" data-duration="180">
                                    <i class="fas fa-hourglass"></i>
                                    3h
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label for="deadline" class="form-label">Deadline <span class="field-required">*</span></label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                   required min="{{ now()->format('Y-m-d\TH:i') }}">
                            <div class="quick-actions">
                                <button type="button" class="quick-btn" onclick="setQuickDeadlineSeconds(10)">10s</button>
                                <button type="button" class="quick-btn" onclick="setQuickDeadline(1)">Tomorrow</button>
                                <button type="button" class="quick-btn" onclick="setQuickDeadline(3)">3 days</button>
                                <button type="button" class="quick-btn" onclick="setQuickDeadline(7)">1 week</button>
                            </div>
                        </div>
                    </div>
                    

                </div>

                <!-- Student Selection -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-users text-primary"></i>
                        Select Students
                    </h3>
                    
                    <div class="student-selector-container">
                        <div class="selector-header">
                            <div class="selector-info">
                                <h4 class="selector-title">Students</h4>
                                <span class="student-count">{{ $students ? count($students) : 0 }} available</span>
                            </div>
                            <div class="selector-controls">
                                <button type="button" class="control-btn" onclick="selectAll()">
                                    <i class="fas fa-check-double me-1"></i>All
                                </button>
                                <button type="button" class="control-btn" onclick="selectNone()">
                                    <i class="fas fa-times me-1"></i>None
                                </button>
                            </div>
                        </div>
                        
                        <!-- Student Search Bar -->
                        <div class="student-search-container">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" 
                                       class="student-search-input" 
                                       id="studentSearch" 
                                       placeholder="Search students by name or username..."
                                       autocomplete="off">
                                <button type="button" class="clear-search-btn" id="clearSearch" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="search-stats">
                                <span id="searchResultsCount">{{ $students ? count($students) : 0 }}</span> students found
                            </div>
                        </div>
                        
                        <div class="student-list" id="studentList">
                            @foreach($students as $student)
                                <div class="student-item" data-student-id="{{ $student->id }}" data-student-name="{{ strtolower($student->name) }}" data-student-username="{{ strtolower($student->username ?? '') }}" onclick="toggleStudent({{ $student->id }})">
                                    <input type="checkbox" class="student-checkbox student-checkbox-input" 
                                           name="student_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}" onclick="event.stopPropagation()">
                                    <div class="student-info">
                                        <div class="student-name">{{ $student->name }}</div>
                                        <div class="student-username">{{ $student->username ?? $student->email }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- No results message -->
                        <div class="no-search-results" id="noSearchResults" style="display: none;">
                            <div class="empty-state">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>No students found matching your search</p>
                                <small>Try a different search term or clear the search</small>
                            </div>
                        </div>
                    </div>

                    @if($students->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <p>No students available for assignment</p>
                        </div>
                    @else
                        <div class="selection-summary" id="selectionSummary">
                            <i class="fas fa-info-circle me-2"></i>
                            Click students above to select them for this assignment
                        </div>
                    @endif
                </div>
            </div>

            <div class="wizard-footer">
                <a href="{{ route('assignments.index') }}" class="btn-cancel">
                    <i class="fas fa-arrow-left me-2"></i>Cancel
                </a>
                <button type="submit" class="btn-create" id="createBtn">
                    <i class="fas fa-rocket me-2" id="createBtnIcon"></i>
                    <span id="createBtnText">Create Assignments</span>
                </button>
            </div>
        </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LETHAL PRELOADER HANDLER - ELIMINATES FOUC
        document.addEventListener('DOMContentLoaded', function() {
            const preloader = document.getElementById('pagePreloader');
            const navbar = document.querySelector('.navbar');
            
            // Hide preloader and show navbar after everything is loaded
            setTimeout(() => {
                preloader.classList.add('hidden');
                navbar.style.opacity = '1';
            }, 300);
        });

        // LETHAL PAGE TRANSITION HANDLER
        document.addEventListener('beforeunload', function() {
            const preloader = document.getElementById('pagePreloader');
            if (preloader) {
                preloader.classList.remove('hidden');
            }
        });

        // LETHAL BUTTON RESET ON PAGE VISIBILITY CHANGE
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                const createBtn = document.getElementById('createBtn');
                const createBtnIcon = document.getElementById('createBtnIcon');
                const createBtnText = document.getElementById('createBtnText');
                
                if (createBtn && createBtn.disabled) {
                    // Reset button immediately when page becomes visible
                    createBtn.disabled = false;
                    createBtn.style.opacity = '1';
                    createBtn.style.transform = 'scale(1)';
                    createBtnIcon.className = 'fas fa-rocket me-2';
                    createBtnText.textContent = 'Create Assignments';
                }
            }
        });

        // AGGRESSIVE BUTTON RESET - ENSURES BUTTON IS ALWAYS CLICKABLE
        setInterval(function() {
            const createBtn = document.getElementById('createBtn');
            const createBtnIcon = document.getElementById('createBtnIcon');
            const createBtnText = document.getElementById('createBtnText');
            
            // If button has been disabled for more than 2 seconds, reset it
            if (createBtn && createBtn.disabled) {
                const disabledTime = createBtn.dataset.disabledTime;
                if (!disabledTime) {
                    createBtn.dataset.disabledTime = Date.now();
                } else if (Date.now() - parseInt(disabledTime) > 2000) {
                    createBtn.disabled = false;
                    createBtn.style.opacity = '1';
                    createBtn.style.transform = 'scale(1)';
                    createBtnIcon.className = 'fas fa-rocket me-2';
                    createBtnText.textContent = 'Create Assignments';
                    delete createBtn.dataset.disabledTime;
                }
            } else if (createBtn && !createBtn.disabled) {
                delete createBtn.dataset.disabledTime;
            }
        }, 500); // Check every 500ms
    </script>
    <script>
        // SUPER FAST STUDENT SELECTION
        function toggleStudent(studentId) {
            const checkbox = document.getElementById(`student_${studentId}`);
            const studentItem = checkbox.closest('.student-item');
            
            checkbox.checked = !checkbox.checked;
            updateStudentVisual(studentItem, checkbox.checked);
            updateSelectionSummary();
        }

        function updateStudentVisual(studentItem, isSelected) {
            if (isSelected) {
                studentItem.classList.add('selected');
            } else {
                studentItem.classList.remove('selected');
            }
        }

        // LIGHTNING FAST SELECTION SUMMARY
        function updateSelectionSummary() {
            const checked = document.querySelectorAll('.student-checkbox-input:checked');
            const count = checked.length;
            const summary = document.getElementById('selectionSummary');
            const createBtn = document.getElementById('createBtn');
            
            if (count === 0) {
                summary.innerHTML = '<i class="fas fa-info-circle me-2"></i>Click students above to select them for this assignment';
                summary.classList.remove('has-selection');
                createBtn.disabled = true;
            } else {
                summary.classList.add('has-selection');
                createBtn.disabled = false;
                
                if (count === 1) {
                    const name = checked[0].closest('.student-item').querySelector('.student-name').textContent;
                    summary.innerHTML = `<i class="fas fa-check-circle me-2 text-success"></i><span class="success-feedback">Selected: ${name}</span>`;
                } else if (count <= 3) {
                    const names = Array.from(checked).map(cb => 
                        cb.closest('.student-item').querySelector('.student-name').textContent
                    );
                    summary.innerHTML = `<i class="fas fa-check-circle me-2 text-success"></i><span class="success-feedback">Selected: ${names.join(', ')}</span>`;
                } else {
                    const firstName = checked[0].closest('.student-item').querySelector('.student-name').textContent;
                    summary.innerHTML = `<i class="fas fa-check-circle me-2 text-success"></i><span class="success-feedback">Selected: ${firstName} and ${count - 1} others</span>`;
                }
            }
        }

        // INSTANT CHECKBOX HANDLING
        document.querySelectorAll('.student-checkbox-input').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                e.stopPropagation();
                updateStudentVisual(this.closest('.student-item'), this.checked);
                updateSelectionSummary();
            });
        });

        // SUPER FAST BULK SELECTION
        function selectAll() {
            // Only select visible students (not hidden by search)
            document.querySelectorAll('.student-item:not(.hidden) .student-checkbox-input').forEach(checkbox => {
                checkbox.checked = true;
                updateStudentVisual(checkbox.closest('.student-item'), true);
            });
            updateSelectionSummary();
        }

        function selectNone() {
            // Only deselect visible students (not hidden by search)
            document.querySelectorAll('.student-item:not(.hidden) .student-checkbox-input').forEach(checkbox => {
                checkbox.checked = false;
                updateStudentVisual(checkbox.closest('.student-item'), false);
            });
            updateSelectionSummary();
        }

        // LETHAL FORM SUBMISSION HANDLER - PREVENTS BUTTON STUCK STATE
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const createBtn = document.getElementById('createBtn');
            const createBtnIcon = document.getElementById('createBtnIcon');
            const createBtnText = document.getElementById('createBtnText');
            
            // Prevent double submission
            if (createBtn.disabled) {
                e.preventDefault();
                return false;
            }

            // Validate form before submission
            const selectedStudents = document.querySelectorAll('.student-checkbox-input:checked');
            if (selectedStudents.length === 0) {
                e.preventDefault();
                alert('Please select at least one student for the assignment.');
                return false;
            }
            
            // Update button state with shorter animation
            createBtn.disabled = true;
            createBtn.dataset.disabledTime = Date.now(); // Track when disabled
            createBtn.style.opacity = '0.8';
            createBtnIcon.className = 'fas fa-spinner fa-spin me-2';
            createBtnText.textContent = 'Creating...';
            
            // Shorter visual feedback
            createBtn.style.transform = 'scale(0.98)';
            
            // Much shorter timeout to reset button - 2 seconds max
            const resetTimeout = setTimeout(() => {
                createBtn.disabled = false;
                createBtn.style.opacity = '1';
                createBtn.style.transform = 'scale(1)';
                createBtnIcon.className = 'fas fa-rocket me-2';
                createBtnText.textContent = 'Create Assignments';
                delete createBtn.dataset.disabledTime;
                console.warn('Form submission timeout - button reset');
            }, 2000); // Reset after 2 seconds instead of 15

            // Store timeout ID for potential cleanup
            createBtn.dataset.resetTimeout = resetTimeout;
        });



        // DURATION BUTTON & DROPDOWN SYNC
        const durationSelect = document.getElementById('duration_minutes');
        const durationButtons = document.querySelectorAll('.duration-btn');
        
        // Button click handler
        durationButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                durationButtons.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set the dropdown value
                durationSelect.value = this.dataset.duration;
            });
        });
        
        // Dropdown change handler
        durationSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            // Remove active class from all buttons
            durationButtons.forEach(b => b.classList.remove('active'));
            
            // Find and activate matching button
            const matchingButton = document.querySelector(`[data-duration="${selectedValue}"]`);
            if (matchingButton) {
                matchingButton.classList.add('active');
            }
        });

        // QUICK DURATION SETTING
        function setQuickDuration(minutes) {
            document.getElementById('duration_minutes').value = minutes;
        }

        // TIMEZONE-AWARE DEADLINE SETTING
        function setQuickDeadline(days) {
            const now = new Date();
            now.setDate(now.getDate() + days);
            now.setHours(23, 59, 0, 0);
            
            // Format for datetime-local input (YYYY-MM-DDTHH:MM)
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            document.getElementById('deadline').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        function setQuickDeadlineSeconds(seconds) {
            const now = new Date();
            now.setSeconds(now.getSeconds() + seconds);
            
            // Format for datetime-local input (YYYY-MM-DDTHH:MM)
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            document.getElementById('deadline').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // PRELOADED DATA FOR INSTANT LOADING
        const allTopics = @json($allTopics);
        const allSources = @json($allSources);

        // INSTANT TOPIC LOADING - NO AJAX!
        document.getElementById('course_id').addEventListener('change', function() {
            const courseId = this.value;
            const topicSelect = document.getElementById('topic_id');
            const sourceSelect = document.getElementById('assigned_source');
            
            if (courseId && allTopics[courseId]) {
                topicSelect.innerHTML = '<option value="">Choose topic...</option>';
                allTopics[courseId].forEach(topic => {
                    topicSelect.innerHTML += `<option value="${topic.id}">${topic.name} (${topic.questions_count} questions)</option>`;
                });
                topicSelect.disabled = false;
            } else {
                topicSelect.innerHTML = '<option value="">Select course first...</option>';
                topicSelect.disabled = true;
            }
            
            // Reset source selection
            sourceSelect.innerHTML = '<option value="">Select topic first...</option>';
            sourceSelect.disabled = true;
        });

        // INSTANT SOURCE LOADING - NO AJAX!
        document.getElementById('topic_id').addEventListener('change', function() {
            const topicId = this.value;
            const sourceSelect = document.getElementById('assigned_source');
            
            if (topicId && allSources[topicId]) {
                sourceSelect.innerHTML = '<option value="">Choose source...</option>';
                allSources[topicId].forEach(source => {
                    // Handle both string and object sources
                    const sourceValue = typeof source === 'object' ? source.source : source;
                    const sourceName = sourceValue || 'Unknown Source';
                    sourceSelect.innerHTML += `<option value="${sourceName}">${sourceName}</option>`;
                });
                sourceSelect.disabled = false;
            } else {
                sourceSelect.innerHTML = '<option value="">Select topic first...</option>';
                sourceSelect.disabled = true;
            }
            
            updateSourceRequirement();
        });

        // INSTANT MODE SWITCHING
        document.getElementById('assignment_mode').addEventListener('change', updateSourceRequirement);

        function updateSourceRequirement() {
            const mode = document.getElementById('assignment_mode').value;
            const sourceSelect = document.getElementById('assigned_source');
            const sourceRequired = document.getElementById('source-required');
            const topicId = document.getElementById('topic_id').value;
            
            if (mode === 'source_specific') {
                sourceSelect.required = true;
                sourceRequired.style.display = 'inline';
                
                // Load sources if topic is selected
                if (topicId && allSources[topicId]) {
                    sourceSelect.innerHTML = '<option value="">Choose source...</option>';
                    allSources[topicId].forEach(source => {
                        // Handle both string and object sources
                        const sourceValue = typeof source === 'object' ? source.source : source;
                        const sourceName = sourceValue || 'Unknown Source';
                        sourceSelect.innerHTML += `<option value="${sourceName}">${sourceName}</option>`;
                    });
                    sourceSelect.disabled = false;
                } else {
                    sourceSelect.disabled = true;
                    sourceSelect.innerHTML = '<option value="">Select topic first...</option>';
                }
            } else {
                // Complete Topic mode - disable source selection
                sourceSelect.required = false;
                sourceRequired.style.display = 'none';
                sourceSelect.value = '';
                sourceSelect.disabled = true;
                sourceSelect.innerHTML = '<option value="">Not needed for complete topic</option>';
            }
        }

        // INSTANT FORM VALIDATION
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const selectedStudents = document.querySelectorAll('.student-checkbox-input:checked').length;
            
            if (selectedStudents === 0) {
                e.preventDefault();
                alert('⚡ Select at least one student!');
                return false;
            }
            
            const deadline = new Date(document.getElementById('deadline').value);
            const now = new Date();
            
            if (deadline <= now) {
                e.preventDefault();
                alert('⚡ Deadline must be in the future!');
                return false;
            }

            const mode = document.getElementById('assignment_mode').value;
            const source = document.getElementById('assigned_source').value;
            
            if (mode === 'source_specific' && !source) {
                e.preventDefault();
                alert('⚡ Select a source for source-specific assignments!');
                return false;
            }
            
            // AUTO-GENERATE TITLE IF EMPTY
            const titleField = document.getElementById('title');
            const topicSelect = document.getElementById('topic_id');
            
            if (!titleField.value.trim() && topicSelect.value) {
                const topicName = topicSelect.options[topicSelect.selectedIndex].text;
                titleField.value = topicName;
            }
            
            // Show loading state
            const createBtn = document.getElementById('createBtn');
            createBtn.innerHTML = '<div class="spinner me-2"></div>Creating...';
            createBtn.disabled = true;
        });

        // STUDENT SEARCH FUNCTIONALITY
        const studentSearchInput = document.getElementById('studentSearch');
        const clearSearchBtn = document.getElementById('clearSearch');
        const searchResultsCount = document.getElementById('searchResultsCount');
        const studentItems = document.querySelectorAll('.student-item');
        
        // Search functionality with debouncing for better performance
        let searchTimeout;
        studentSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;
                const noSearchResults = document.getElementById('noSearchResults');
                
                studentItems.forEach(item => {
                    const studentName = item.dataset.studentName || '';
                    const studentUsername = item.dataset.studentUsername || '';
                    
                    const matchesName = studentName.includes(searchTerm);
                    const matchesUsername = studentUsername.includes(searchTerm);
                    
                    if (searchTerm === '' || matchesName || matchesUsername) {
                        item.classList.remove('hidden');
                        visibleCount++;
                        
                        // Highlight matching text if search term is not empty
                        if (searchTerm !== '') {
                            item.classList.add('highlighted');
                        } else {
                            item.classList.remove('highlighted');
                        }
                    } else {
                        item.classList.add('hidden');
                        item.classList.remove('highlighted');
                    }
                });
                
                // Update results count
                searchResultsCount.textContent = visibleCount;
                
                // Show/hide clear button
                if (searchTerm !== '') {
                    clearSearchBtn.style.display = 'flex';
                } else {
                    clearSearchBtn.style.display = 'none';
                }
                
                // Show/hide no results message
                if (searchTerm !== '' && visibleCount === 0) {
                    noSearchResults.style.display = 'block';
                } else {
                    noSearchResults.style.display = 'none';
                }
            }, 150); // 150ms debounce for better performance
        });
        
        // Clear search functionality
        clearSearchBtn.addEventListener('click', function() {
            studentSearchInput.value = '';
            studentSearchInput.focus();
            
            // Show all students
            studentItems.forEach(item => {
                item.classList.remove('hidden', 'highlighted');
            });
            
            // Update count and hide clear button
            searchResultsCount.textContent = studentItems.length;
            this.style.display = 'none';
            
            // Hide no results message
            document.getElementById('noSearchResults').style.display = 'none';
        });
        
        // Keyboard shortcuts for search
        studentSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });
        
        // BUTTON CLICK HANDLER - ENSURES ALWAYS CLICKABLE
        document.getElementById('createBtn').addEventListener('click', function(e) {
            // If button is disabled but shouldn't be, re-enable it
            if (this.disabled) {
                const disabledTime = this.dataset.disabledTime;
                if (!disabledTime || Date.now() - parseInt(disabledTime) > 1000) {
                    this.disabled = false;
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                    document.getElementById('createBtnIcon').className = 'fas fa-rocket me-2';
                    document.getElementById('createBtnText').textContent = 'Create Assignments';
                    delete this.dataset.disabledTime;
                }
            }
        });

        // INITIALIZE EVERYTHING INSTANTLY
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectionSummary();
            updateSourceRequirement();
            
            // Auto-focus first field for speed
            document.getElementById('course_id').focus();
            
            // Add keyboard shortcut to focus search (Ctrl/Cmd + K)
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    studentSearchInput.focus();
                }
            });
        });
    </script>
</body>
</html>