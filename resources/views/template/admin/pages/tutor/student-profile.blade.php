@extends('template.admin.layouts.tutor')

@section('title', $student->name . ' - Student Profile')

@section('styles')
<style>
        :root {
            --primary-color: #4f46e5; /* Indigo */
            --primary-hover: #4338ca;
            --secondary-color: #6b7280; /* Gray */
            --success-color: #16a34a; /* Green */
            --warning-color: #f59e0b; /* Amber */
            --danger-color: #ef4444; /* Red */
            --info-color: #3b82f6; /* Blue */
            --light-bg: #f9fafb; /* Very light gray */
            --dark-text: #111827; /* Darker gray */
            --border-radius: 12px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;

            /* New soft widget colors */
            --widget-1-bg: #eef2ff;
            --widget-1-text: #4338ca;
            --widget-2-bg: #f0fdf4;
            --widget-2-text: #16a34a;
            --widget-3-bg: #fffbeb;
            --widget-3-text: #d97706;
            --widget-4-bg: #fdf2f8;
            --widget-4-text: #db2777;
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
            background: white;
            color: var(--dark-text);
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .profile-header-top {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid var(--primary-hover);
            box-shadow: 0 0 0 4px white, 0 4px 12px rgba(0,0,0,0.15); /* Enhanced outline */
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            border: 4px solid var(--primary-hover);
            box-shadow: 0 0 0 4px white, 0 4px 12px rgba(0,0,0,0.15); /* Enhanced outline */
        }

        .student-name {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .student-info {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }

        .student-info svg {
            margin-right: 0.5rem;
            opacity: 0.7;
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
            background-color: var(--light-bg);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            text-align: left;
            transition: var(--transition);
            flex: 1;
            min-width: 180px;
            border: 1px solid #e5e7eb; /* Sleek outline for widgets */
        }

        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .quick-stat:nth-child(1) { background-color: var(--widget-1-bg); color: var(--widget-1-text); }
        .quick-stat:nth-child(2) { background-color: var(--widget-2-bg); color: var(--widget-2-text); }
        .quick-stat:nth-child(3) { background-color: var(--widget-3-bg); color: var(--widget-3-text); }
        .quick-stat:nth-child(4) { background-color: var(--widget-4-bg); color: var(--widget-4-text); }


        .quick-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.25rem;
        }

        .quick-stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            opacity: 0.8;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid #e5e7eb; /* Sleek outline for widgets */
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

        .card-title svg {
            margin-right: 0.8rem;
            color: var(--primary-color);
            width: 20px;
            height: 20px;
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
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.8rem;
            text-align: center;
            border: 1px solid transparent;
        }

        .score-excellent {
            background-color: #dcfce7;
            color: #166534;
            border-color: #a7f3d0;
        }
        .score-good {
            background-color: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        .score-average {
            background-color: #fefce8;
            color: #a16207;
            border-color: #fef08a;
        }
        .score-poor {
            background-color: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

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

            .profile-header-top {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1rem;
            }
            
            .profile-header {
                padding: 1.5rem;
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
@endsection

@section('tutor-content')
        <!-- Profile Header -->
        <div class="profile-header fade-in-up">
            <div class="profile-header-top">
                <div class="avatar-container">
                    @if($student->profile_picture_url)
                        <img src="{{ \App\Models\User::getProfileImageUrlFor($student) }}"
                             alt="{{ htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') }}"
                             class="student-avatar">
                    @else
                        <div class="avatar-placeholder">
                            {{ htmlspecialchars(substr($student->name, 0, 1), ENT_QUOTES, 'UTF-8') }}
                        </div>
                    @endif
                </div>
                <div class="info-container">
                    <h1 class="student-name">{{ $student->name }}</h1>
                    <p class="student-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4 15.5v-4.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4.5a.5.5 0 0 1-1 0V12h-.5v3.5a.5.5 0 0 1-1 0zM8 15.5v-4.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4.5a.5.5 0 0 1-1 0V12h-.5v3.5a.5.5 0 0 1-1 0zm4-11A.5.5 0 0 1 12.5 4h1a.5.5 0 0 1 .5.5v1.5a.5.5 0 0 1-1 0V5h-.5v1.5a.5.5 0 0 1-1 0V4.5z"/><path d="M2 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1.5a.5.5 0 0 1-1 0V4a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10.5a.5.5 0 0 1-1 0V4z"/></svg> {{ $student->university ?? 'University not specified' }}
                    </p>
                    <p class="student-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/></svg> {{ $student->email }}
                    </p>
                    <p class="student-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg> Member since {{ $data['overview']['member_since'] ? $data['overview']['member_since']->format('M Y') : 'Unknown' }}
                    </p>
                    <div class="status-indicator {{ $data['overview']['last_active'] && $data['overview']['last_active']->gte(now()->subDays(3)) ? 'status-active' : 'status-inactive' }}">
                        <svg viewBox="0 0 16 16" fill="currentColor" width="10" height="10" style="margin-right: 0.5rem;"><circle cx="8" cy="8" r="8"/></svg>
                        @if($student->last_active_at)
                            Last active {{ $student->last_active_at->diffForHumans() }}
                        @else
                            Never active
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="quick-stat">
                    <span class="quick-stat-value">{{ $data['overview']['total_quizzes'] ?? 0 }}</span>
                    <span class="quick-stat-label">Total Quizzes</span>
                </div>
                @php
                    $averageScore = $data['overview']['average_score'] ?? 0;
                    $scoreCategory = '';
                    if ($averageScore >= 90) {
                        $scoreCategory = 'score-excellent';
                    } elseif ($averageScore >= 75) {
                        $scoreCategory = 'score-good';
                    } elseif ($averageScore >= 50) {
                        $scoreCategory = 'score-average';
                    } else {
                        $scoreCategory = 'score-poor';
                    }
                @endphp
                <div class="quick-stat {{ $scoreCategory }}">
                    <span class="quick-stat-value">{{ number_format($averageScore, 1) }}%</span>
                    <span class="quick-stat-label">Average Score</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-value">{{ $data['overview']['best_score'] ?? 0 }}%</span>
                    <span class="quick-stat-label">Best Score</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-value">{{ $student->xp ?? 0 }}</span>
                    <span class="quick-stat-label">Total XP</span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Performance Overview -->
            <div class="col-lg-8">

                <!-- Assignments Overview -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.5 2H8.6c-.4 0-.8.2-1.1.5-.3.3-.5.7-.5 1.1V4c0 .4.2.8.5 1.1.3.3.7.5 1.1.5h1.8c.4 0 .8-.2 1.1-.5.3-.3.5-.7.5-1.1V3.6c0-.4-.2-.8-.5-1.1-.3-.3-.7-.5-1.1-.5h-1M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01M10 22h4c4.5 0 6.5-2 6.5-6.5V8.5C20.5 4 18.5 2 14 2h-4C5.5 2 3.5 4 3.5 8.5v7C3.5 20 5.5 22 10 22z"></path></svg>
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
                                                <small class="d-flex align-items-center text-muted">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                                    <span class="me-2">{{ $assignment['topic'] }}</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                                    <span>{{ $assignment['tutor'] }}</span>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="assignment-status">
                                                @if($assignment['is_completed'])
                                                    <span class="badge bg-success">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Completed
                                                    </span>
                                                    @if($assignment['score_percentage'])
                                                        <div class="mt-1">
                                                            <small class="text-muted">Score: {{ $assignment['score_percentage'] }}%</small>
                                                        </div>
                                                    @endif
                                                @elseif($assignment['is_overdue'])
                                                    <span class="badge bg-danger">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg> Overdue
                                                    </span>
                                                @elseif($assignment['is_active'])
                                                    <span class="badge bg-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Active
                                                    </span>
                                                    <div class="mt-1">
                                                        <small class="text-muted">{{ $assignment['time_remaining'] }}</small>
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Locked
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <div class="assignment-deadline">
                                                <small class="text-muted d-flex align-items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                                    <span>{{ \Carbon\Carbon::parse($assignment['deadline'])->format('M j, Y') }}</span>
                                                </small>
                                                @if($assignment['completed_at'])
                                                    <div class="mt-1">
                                                        <small class="text-success">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> 
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

                <!-- Most Recent Quiz -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#303f9f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Most Recent Quiz
                    </h3>
                    @if(count($data['recent_quizzes']) > 0)
                        @php
                            $latestQuiz = $data['recent_quizzes'][0];
                            $score = $latestQuiz['score'];
                            
                            // Define soft colors based on score
                            if ($score >= 90) {
                                $bgColor = 'rgba(16, 185, 129, 0.1)';
                                $textColor = '#166534'; // Darker green
                                $borderColor = 'rgba(16, 185, 129, 0.3)';
                            } elseif ($score >= 75) {
                                $bgColor = 'rgba(59, 130, 246, 0.1)';
                                $textColor = '#1e40af'; // Darker blue
                                $borderColor = 'rgba(59, 130, 246, 0.3)';
                            } elseif ($score >= 50) {
                                $bgColor = 'rgba(245, 158, 11, 0.1)';
                                $textColor = '#92400e'; // Darker amber
                                $borderColor = 'rgba(245, 158, 11, 0.3)';
                            } else {
                                $bgColor = 'rgba(239, 68, 68, 0.1)';
                                $textColor = '#991b1b'; // Darker red
                                $borderColor = 'rgba(239, 68, 68, 0.3)';
                            }
                            
                            $duration = $latestQuiz['duration'];
                            $minutes = floor($duration / 60);
                            $seconds = $duration % 60;
                        @endphp
                        <div class="quiz-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-radius: 8px; background: {{ $bgColor }}; border: 1px solid {{ $borderColor }};">
                            <div>
                                <div class="quiz-topic" style="font-weight: 600; color: {{ $textColor }}; font-size: 1.1rem;">
                                    {{ $latestQuiz['topic'] }}
                                </div>
                                <div class="quiz-date" style="color: {{ $textColor }}; opacity: 0.8; font-size: 0.9rem;">
                                    {{ \Carbon\Carbon::parse($latestQuiz['date'])->format('M j, Y') }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div style="font-size: 1.5rem; font-weight: 700; color: {{ $textColor }}; margin-bottom: 0.25rem;">
                                    {{ number_format($score, 1) }}%
                                </div>
                                <div style="color: {{ $textColor }}; opacity: 0.8; font-size: 0.8rem; display: flex; align-items: center; justify-content: flex-end;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    {{ sprintf('%dm %ds', $minutes, $seconds) }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ced4da" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15.5 2H8.6c-.4 0-.8.2-1.1.5-.3.3-.5.7-.5 1.1V4c0 .4.2.8.5 1.1.3.3.7.5 1.1.5h1.8c.4 0 .8-.2 1.1-.5.3-.3.5-.7.5-1.1V3.6c0-.4-.2-.8-.5-1.1-.3-.3-.7-.5-1.1-.5h-1M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01M10 22h4c4.5 0 6.5-2 6.5-6.5V8.5C20.5 4 18.5 2 14 2h-4C5.5 2 3.5 4 3.5 8.5v7C3.5 20 5.5 22 10 22z"></path>
                            </svg>
                            <p>No quiz attempts yet</p>
                            <small>This student hasn't taken any quizzes yet.</small>
                        </div>
                    @endif
                </div>

                <!-- Performance Chart -->
                @if(count($data['recent_quizzes']) > 0)
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#303f9f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        Study Patterns
                    </h3>
                    @php
                        $score = $data['performance_metrics']['average_score_in_completed_quizzes'] ?? 0;
                        $scoreClass = '';
                        if ($score >= 90) {
                            $scoreClass = 'score-excellent';
                        } elseif ($score >= 75) {
                            $scoreClass = 'score-good';
                        } elseif ($score >= 50) {
                            $scoreClass = 'score-average';
                        } else {
                            $scoreClass = 'score-poor';
                        }
                    @endphp
                    <div class="metric-item {{ $scoreClass }}">
                        <span class="metric-value">{{ number_format($score, 2) }}%</span>
                        <span class="metric-label">Avg. Score (Completed)</span>
                    </div>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align: middle;"><path d="M18 15l-6-6-6 6"/></svg> Improving
                            @elseif($data['study_patterns']['improvement_trend'] == 'declining')
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M6 15l6-6 6 6"/></svg> Declining
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00796b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="5" y1="12" x2="19" y2="12"/></svg> Stable
                            @endif
                        </span>
                        <span class="metric-label">Performance Trend</span>
                    </div>
                </div>

                <!-- Performance by Topic -->
                <div class="content-card fade-in-up">
                    <h3 class="card-title">
                        <i class="fas fa-book me-2" style="vertical-align: middle;"></i>
                        Performance by Topic
                    </h3>
                    @if(count($data['performance_by_topic']) > 0)
                        @foreach(array_slice($data['performance_by_topic'], 0, 5) as $topic)
                            <div class="topic-item">
                                @php
                                    $score = $topic['average_score'];
                                    $scoreClass = '';
                                    if ($score >= 90) {
                                        $scoreClass = 'score-excellent';
                                    } elseif ($score >= 75) {
                                        $scoreClass = 'score-good';
                                    } elseif ($score >= 50) {
                                        $scoreClass = 'score-average';
                                    } else {
                                        $scoreClass = 'score-poor';
                                    }
                                @endphp
                                <div class="topic-name">{{ $topic['topic'] }}</div>
                                <div class="topic-stats">
                                    <div class="topic-average {{ $scoreClass }}">{{ number_format($score, 1) }}%</div>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.09 16.05A6.47 6.47 0 0 1 12 18a6.5 6.5 0 0 1-6.5-6.5A6.5 6.5 0 0 1 12 5a6.5 6.5 0 0 1 6.44 7.94l-1.35 2.69a.5.5 0 0 0 .44.72h1.35Z"></path><path d="M18.57 12.91A9 9 0 1 1 3.43 12.91"></path><path d="M8 12a1 1 0 1 0 2 0 1 1 0 1 0-2 0Z"></path><path d="m12.33 16-.33 2"></path><path d="m12.66 4-.33-2"></path><path d="m4.66 8.34-.33-2"></path><path d="m20.33 8.34.33-2"></path><path d="m4.66 15.66-.33 2"></path><path d="m20.33 15.66.33 2"></path></svg>
                        Real-Time Insights
                    </h3>
                    <div class="insights">
                        @if($data['overview']['average_score'] >= 80)
                            <div class="alert alert-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <strong>High Performer:</strong> This student consistently achieves excellent scores.
                            </div>
                        @elseif($data['overview']['average_score'] >= 60)
                            <div class="alert alert-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                                <strong>Steady Progress:</strong> This student shows consistent improvement.
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                <strong>Needs Attention:</strong> This student may need additional support in some areas.
                            </div>
                        @endif

                        @if($data['real_time_stats']['improvement_rate'] > 5)
                            <div class="alert alert-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                <strong>Improving:</strong> {{ $data['real_time_stats']['improvement_rate'] }}% improvement in recent performance!
                            </div>
                        @elseif($data['real_time_stats']['improvement_rate'] < -5)
                            <div class="alert alert-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path></svg>
                                <strong>Declining:</strong> Performance has declined by {{ abs($data['real_time_stats']['improvement_rate']) }}%.
                            </div>
                        @endif

                        @if(count($data['real_time_stats']['strength_areas']) > 0)
                            <div class="alert alert-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <strong>Strengths:</strong>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($data['real_time_stats']['strength_areas'] as $strength)
                                        @php
                                            $score = $strength['average_score'];
                                            $scoreClass = '';
                                            if ($score >= 90) {
                                                $scoreClass = 'score-excellent';
                                            } elseif ($score >= 75) {
                                                $scoreClass = 'score-good';
                                            } elseif ($score >= 50) {
                                                $scoreClass = 'score-average';
                                            } else {
                                                $scoreClass = 'score-poor';
                                            }
                                        @endphp
                                        <span class="badge {{ $scoreClass }}" style="font-size: 0.8rem; padding: 0.5em 0.9em;">
                                            {{ $strength['topic'] }}: {{ number_format($score, 1) }}%
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(count($data['real_time_stats']['weakness_areas']) > 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Focus Areas:</strong>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($data['real_time_stats']['weakness_areas'] as $weakness)
                                        @php
                                            $score = $weakness['average_score'];
                                            $scoreClass = '';
                                            if ($score >= 90) {
                                                $scoreClass = 'score-excellent';
                                            } elseif ($score >= 75) {
                                                $scoreClass = 'score-good';
                                            } elseif ($score >= 50) {
                                                $scoreClass = 'score-average';
                                            } else {
                                                $scoreClass = 'score-poor';
                                            }
                                        @endphp
                                        <span class="badge {{ $scoreClass }}" style="font-size: 0.8rem; padding: 0.5em 0.9em;">
                                            {{ $weakness['topic'] }}: {{ number_format($score, 1) }}%
                                        </span>
                                    @endforeach
                                </div>
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
@endsection

@section('scripts')
<script>
    // Performance Chart Script
    @if(count($data['recent_quizzes']) > 0)
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');

        const quizData = @json(array_reverse(array_slice($data['recent_quizzes'], 0, 10)));

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: quizData.map(quiz => new Date(quiz.date).toLocaleDateString()),
                datasets: [{
                    label: 'Quiz Score',
                    data: quizData.map(quiz => quiz.score),
                    topicNames: quizData.map(quiz => quiz.topic),
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
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].dataset.topicNames[context[0].dataIndex];
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += `${context.parsed.y}%`;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: value => value + '%' }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
    });
    @endif

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
@endsection
