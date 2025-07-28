<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $student->name }} - Student Profile - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js for better data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --border-radius: 12px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-text);
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color);
            text-decoration: none;
        }

        .back-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .back-btn i {
            margin-right: 0.5rem;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .student-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .student-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .student-info {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .quick-stat {
            text-align: center;
            background: rgba(255,255,255,0.15);
            padding: 1rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .quick-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.3rem;
        }

        .quick-stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .content-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 0.8rem;
        }

        .card-title i {
            margin-right: 0.8rem;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        /* Performance Metrics */
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .metric-item {
            text-align: center;
            padding: 1.2rem;
            background: var(--light-bg);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .metric-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-text);
            display: block;
            margin-bottom: 0.3rem;
        }

        .metric-label {
            font-size: 0.8rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Quiz History */
        .quiz-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f3f4;
            transition: var(--transition);
        }

        .quiz-item:hover {
            background-color: var(--light-bg);
            border-radius: 8px;
        }

        .quiz-item:last-child {
            border-bottom: none;
        }

        .quiz-topic {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.3rem;
        }

        .quiz-score {
            font-weight: 700;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            color: white;
            font-size: 0.9rem;
            min-width: 60px;
            text-align: center;
        }

        .score-excellent { background-color: var(--success-color); }
        .score-good { background-color: var(--info-color); }
        .score-average { background-color: var(--warning-color); color: #333; }
        .score-poor { background-color: var(--danger-color); }

        .quiz-date {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        /* Topic Performance */
        .topic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            transition: var(--transition);
        }

        .topic-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,123,255,0.1);
        }

        .topic-name {
            font-weight: 600;
            color: var(--dark-text);
        }

        .topic-stats {
            text-align: right;
        }

        .topic-average {
            font-weight: 700;
            font-size: 1rem;
        }

        .topic-attempts {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        /* Progress Bars */
        .progress-container {
            margin: 1rem 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #0056b3);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* Status Indicators */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.active {
            background-color: var(--success-color);
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.3);
        }

        .status-dot.inactive {
            background-color: var(--secondary-color);
        }

        /* Enhanced Metric Items */
        .metric-item small {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .metric-item .text-success {
            color: var(--success-color) !important;
        }

        .metric-item .text-danger {
            color: var(--danger-color) !important;
        }

        .metric-item .text-info {
            color: var(--info-color) !important;
        }

        /* Assignments - Professional & Minimal */
        .assignments-list {
            margin-top: 1.5rem;
        }

        .assignment-item {
            padding: 1.25rem;
            border: 1px solid #f1f3f4;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .assignment-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #e9ecef;
            transition: background 0.3s ease;
        }

        .assignment-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .assignment-item.completed::before {
            background: var(--success-color);
        }

        .assignment-item.active::before {
            background: var(--primary-color);
        }

        .assignment-item.overdue::before {
            background: var(--danger-color);
        }

        .assignment-item.locked::before {
            background: var(--secondary-color);
        }

        /* Smooth animations for assignment items */
        .assignment-item {
            animation: fadeInUp 0.4s ease-out;
        }

        .assignment-item:nth-child(1) { animation-delay: 0.1s; }
        .assignment-item:nth-child(2) { animation-delay: 0.2s; }
        .assignment-item:nth-child(3) { animation-delay: 0.3s; }
        .assignment-item:nth-child(4) { animation-delay: 0.4s; }
        .assignment-item:nth-child(5) { animation-delay: 0.5s; }

        .assignment-title {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            line-height: 1.4;
        }

        .assignment-details {
            margin-bottom: 0.75rem;
            color: #6b7280;
        }

        .assignment-details small {
            font-size: 0.85rem;
        }

        .assignment-status {
            text-align: center;
            padding: 0.5rem 0;
        }

        .assignment-deadline {
            text-align: right;
            padding: 0.5rem 0;
        }

        /* Professional Badge Design */
        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge.bg-success {
            background-color: #dcfce7 !important;
            color: #166534 !important;
        }

        .badge.bg-danger {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }

        .badge.bg-primary {
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        .badge.bg-secondary {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
        }

        /* Assignment Stats */
        .assignment-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-item.completed .stat-value {
            color: var(--success-color);
        }

        .stat-item.active .stat-value {
            color: var(--primary-color);
        }

        .stat-item.overdue .stat-value {
            color: var(--danger-color);
        }

        .stat-item.average .stat-value {
            color: var(--info-color);
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0.5rem;
            }
            
            .profile-header {
                padding: 1.5rem;
                text-align: center;
            }
            
            .student-name {
                font-size: 1.5rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            
            .performance-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quiz-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .topic-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .assignment-item .row {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .assignment-status,
            .assignment-deadline {
                text-align: left !important;
            }
            
            .assignment-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
                padding: 0.8rem;
            }
            
            .stat-value {
                font-size: 1.3rem;
            }
            
            .content-card {
                padding: 1rem;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .performance-grid {
                grid-template-columns: 1fr;
            }
            
            .assignment-stats {
                grid-template-columns: 1fr;
            }
            
            .student-name {
                font-size: 1.3rem;
            }
            
            .assignment-item {
                padding: 1rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Print Styles */
        @media print {
            .navbar, .back-btn {
                display: none;
            }
            
            .main-content {
                padding: 0;
            }
            
            .content-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            
            <div class="navbar-nav ms-auto">
                <a href="{{ route('tutor.dashboard') }}" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header fade-in-up">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    @if($student->profile_picture_url)
                        <img src="{{ secure_url($student->profile_picture_url) }}" 
                             alt="{{ $student->name }}" 
                             class="student-avatar">
                    @else
                        <div class="avatar-placeholder">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-9">
                    <h1 class="student-name">{{ $student->name }}</h1>
                    <p class="student-info">
                        <i class="fas fa-university"></i> {{ $student->university ?? 'University not specified' }}
                    </p>
                    <p class="student-info">
                        <i class="fas fa-envelope"></i> {{ $student->email }}
                    </p>
                    <p class="student-info">
                        <i class="fas fa-calendar"></i> Member since {{ $data['overview']['member_since'] ? $data['overview']['member_since']->format('M Y') : 'Unknown' }}
                    </p>
                    
                    <div class="status-indicator {{ $data['overview']['last_active'] && $data['overview']['last_active']->gte(now()->subDays(3)) ? 'status-active' : 'status-inactive' }}">
                        <i class="fas fa-circle"></i>
                        @if($data['overview']['last_active'])
                            Last active {{ $data['overview']['last_active']->diffForHumans() }}
                        @else
                            Never active
                        @endif
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <span class="quick-stat-value">{{ $data['overview']['total_quizzes'] }}</span>
                            <span class="quick-stat-label">Total Quizzes</span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-value">{{ $data['overview']['average_score'] }}%</span>
                            <span class="quick-stat-label">Average Score</span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-value">{{ $data['overview']['study_streak'] }}</span>
                            <span class="quick-stat-label">Study Streak</span>
                        </div>
                        <div class="quick-stat">
                            <span class="quick-stat-value">{{ number_format($data['overview']['total_xp']) }}</span>
                            <span class="quick-stat-label">Total XP</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Performance Overview -->
            <div class="col-lg-8">

                <!-- Assignments Overview -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i>
                        Assignments Overview
                    </h3>
                    
                    <!-- Assignment Stats -->
                    <div class="assignment-stats">
                        <div class="stat-item completed">
                            <span class="stat-value">{{ $assignments['completed_count'] }}</span>
                            <span class="stat-label">Completed</span>
                        </div>
                        <div class="stat-item active">
                            <span class="stat-value">{{ $assignments['active_count'] }}</span>
                            <span class="stat-label">Active</span>
                        </div>
                        <div class="stat-item overdue">
                            <span class="stat-value">{{ $assignments['overdue_count'] }}</span>
                            <span class="stat-label">Overdue</span>
                        </div>
                        <div class="stat-item average">
                            <span class="stat-value">{{ round($assignments['average_score'], 1) }}%</span>
                            <span class="stat-label">Avg Score</span>
                        </div>
                    </div>

                    <!-- All Assignments List -->
                    <div class="assignments-list">
                        @if(count($assignments['all_assignments']) > 0)
                            @foreach($assignments['all_assignments'] as $assignment)
                                <div class="assignment-item 
                                    @if($assignment['is_completed']) completed
                                    @elseif($assignment['is_overdue']) overdue
                                    @elseif($assignment['is_active']) active
                                    @else locked
                                    @endif">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="assignment-title">{{ $assignment['title'] }}</div>
                                            <div class="assignment-details">
                                                <small>
                                                    <i class="fas fa-book"></i> {{ $assignment['topic'] }} • 
                                                    <i class="fas fa-user"></i> {{ $assignment['tutor'] }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="assignment-status">
                                                @if($assignment['is_completed'])
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Completed
                                                    </span>
                                                    @if($assignment['score_percentage'])
                                                        <div class="mt-1">
                                                            <small class="text-muted">Score: {{ $assignment['score_percentage'] }}%</small>
                                                        </div>
                                                    @endif
                                                @elseif($assignment['is_overdue'])
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Overdue
                                                    </span>
                                                @elseif($assignment['is_active'])
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-clock"></i> Active
                                                    </span>
                                                    <div class="mt-1">
                                                        <small class="text-muted">{{ $assignment['time_remaining'] }}</small>
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-lock"></i> Locked
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="assignment-deadline">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    {{ \Carbon\Carbon::parse($assignment['deadline'])->format('M j, Y') }}
                                                </small>
                                                @if($assignment['completed_at'])
                                                    <div class="mt-1">
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle"></i> 
                                                            {{ \Carbon\Carbon::parse($assignment['completed_at'])->format('M j, Y') }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <i class="fas fa-tasks"></i>
                                <p>No assignments yet</p>
                                <small>This student hasn't been assigned any tasks yet.</small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Quiz History -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Recent Quiz History
                    </h3>
                    @if(count($data['recent_quizzes']) > 0)
                        @foreach($data['recent_quizzes'] as $quiz)
                            <div class="quiz-item">
                                <div>
                                    <div class="quiz-topic">{{ $quiz['topic'] }}</div>
                                    <div class="quiz-date">{{ \Carbon\Carbon::parse($quiz['date'])->format('M j, Y g:i A') }}</div>
                                </div>
                                <div class="text-end">
                                    <span class="quiz-score 
                                        @if($quiz['score'] >= 80) score-excellent
                                        @elseif($quiz['score'] >= 70) score-good
                                        @elseif($quiz['score'] >= 60) score-average
                                        @else score-poor
                                        @endif
                                    ">
                                        {{ $quiz['score'] }}%
                                    </span>
                                    @if($quiz['duration'] > 0)
                                        <div class="quiz-date">{{ gmdate('i:s', $quiz['duration']) }} min</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p>No quiz attempts yet</p>
                            <small>This student hasn't taken any quizzes yet.</small>
                        </div>
                    @endif
                </div>

                <!-- Performance Chart -->
                @if(count($data['recent_quizzes']) > 0)
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-chart-area"></i>
                        Performance Trend
                    </h3>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Study Patterns -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-brain"></i>
                        Study Patterns
                    </h3>
                    <div class="metric-item mb-3">
                        <span class="metric-value">{{ $data['study_patterns']['average_session_time'] }} min</span>
                        <span class="metric-label">Avg Session Time</span>
                    </div>
                    <div class="metric-item mb-3">
                        <span class="metric-value">{{ $data['study_patterns']['most_active_day'] }}</span>
                        <span class="metric-label">Most Active Day</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value">
                            @if($data['study_patterns']['improvement_trend'] == 'improving')
                                <i class="fas fa-arrow-up text-success"></i> Improving
                            @elseif($data['study_patterns']['improvement_trend'] == 'declining')
                                <i class="fas fa-arrow-down text-danger"></i> Declining
                            @else
                                <i class="fas fa-minus text-warning"></i> Stable
                            @endif
                        </span>
                        <span class="metric-label">Performance Trend</span>
                    </div>
                </div>

                <!-- Performance by Topic -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i>
                        Performance by Topic
                    </h3>
                    @if(count($data['performance_by_topic']) > 0)
                        @foreach(array_slice($data['performance_by_topic'], 0, 5) as $topic)
                            <div class="topic-item">
                                <div class="topic-name">{{ $topic['topic'] }}</div>
                                <div class="topic-stats">
                                    <div class="topic-average 
                                        @if($topic['average_score'] >= 80) text-success
                                        @elseif($topic['average_score'] >= 70) text-info
                                        @elseif($topic['average_score'] >= 60) text-warning
                                        @else text-danger
                                        @endif
                                    ">
                                        {{ $topic['average_score'] }}%
                                    </div>
                                    <div class="topic-attempts">{{ $topic['attempts'] }} attempts</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <p>No topic data available</p>
                            <small>This student hasn't attempted any topics yet.</small>
                        </div>
                    @endif
                </div>

                <!-- Student Insights -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb"></i>
                        Real-Time Insights
                    </h3>
                    <div class="insights">
                        @if($data['overview']['average_score'] >= 80)
                            <div class="alert alert-success">
                                <i class="fas fa-star"></i>
                                <strong>High Performer:</strong> This student consistently achieves excellent scores.
                            </div>
                        @elseif($data['overview']['average_score'] >= 60)
                            <div class="alert alert-info">
                                <i class="fas fa-chart-line"></i>
                                <strong>Steady Progress:</strong> This student shows consistent improvement.
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Needs Support:</strong> This student may benefit from additional guidance.
                            </div>
                        @endif

                        @if($data['real_time_stats']['improvement_rate'] > 5)
                            <div class="alert alert-success">
                                <i class="fas fa-rocket"></i>
                                <strong>Improving:</strong> {{ $data['real_time_stats']['improvement_rate'] }}% improvement in recent performance!
                            </div>
                        @elseif($data['real_time_stats']['improvement_rate'] < -5)
                            <div class="alert alert-warning">
                                <i class="fas fa-arrow-down"></i>
                                <strong>Declining:</strong> {{ abs($data['real_time_stats']['improvement_rate']) }}% decline in recent performance.
                            </div>
                        @endif

                        @if(count($data['real_time_stats']['strength_areas']) > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-trophy"></i>
                                <strong>Strengths:</strong> 
                                @foreach($data['real_time_stats']['strength_areas'] as $strength)
                                    {{ $strength['topic'] }} ({{ $strength['average_score'] }}%)
                                    @if(!$loop->last), @endif
                                @endforeach
                            </div>
                        @endif

                        @if(count($data['real_time_stats']['weakness_areas']) > 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Focus Areas:</strong> 
                                @foreach($data['real_time_stats']['weakness_areas'] as $weakness)
                                    {{ $weakness['topic'] }} ({{ $weakness['average_score'] }}%)
                                    @if(!$loop->last), @endif
                                @endforeach
                            </div>
                        @endif

                        @if($data['study_patterns']['recent_activity'] == 0)
                            <div class="alert alert-secondary">
                                <i class="fas fa-clock"></i>
                                <strong>Inactive:</strong> No recent activity detected.
                            </div>
                        @endif

                        @if($data['overview']['study_streak'] > 7)
                            <div class="alert alert-success">
                                <i class="fas fa-fire"></i>
                                <strong>Study Streak:</strong> {{ $data['overview']['study_streak'] }} days of consistent study!
                            </div>
                        @endif

                        <div class="alert alert-light">
                            <i class="fas fa-info-circle"></i>
                            <strong>Data Accuracy:</strong> All metrics are calculated in real-time from the latest student activity.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Performance Chart Script -->
    @if(count($data['recent_quizzes']) > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            const quizData = @json(array_reverse(array_slice($data['recent_quizzes'], 0, 10)));
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: quizData.map(quiz => new Date(quiz.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Quiz Scores (%)',
                        data: quizData.map(quiz => quiz.score),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        });
    </script>
    @endif

    <!-- Smooth scrolling and animations -->
    <script>
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading states
        document.addEventListener('DOMContentLoaded', function() {
            // Remove loading class after page loads
            document.body.classList.remove('loading');
        });

        // Add print functionality
        function printProfile() {
            window.print();
        }
    </script>
</body>
</html>