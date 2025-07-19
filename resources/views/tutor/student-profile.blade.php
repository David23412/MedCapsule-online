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

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            zoom: 0.80;
        }

        /* Navbar */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 2rem;
            color: #007bff;
            text-decoration: none;
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #007bff;
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding: 2rem;
            min-height: calc(100vh - 100px);
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
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
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.3);
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .student-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .student-university {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .quick-stat {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .quick-stat-value {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }

        .quick-stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 0.5rem;
            color: #007bff;
        }

        /* Performance Metrics */
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .metric-item {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            display: block;
        }

        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
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
            transition: background-color 0.3s ease;
        }

        .quiz-item:hover {
            background-color: #f8f9fa;
        }

        .quiz-item:last-child {
            border-bottom: none;
        }

        .quiz-topic {
            font-weight: 600;
            color: #2c3e50;
        }

        .quiz-score {
            font-weight: 700;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            color: white;
        }

        .score-excellent { background-color: #28a745; }
        .score-good { background-color: #17a2b8; }
        .score-average { background-color: #ffc107; color: #333; }
        .score-poor { background-color: #dc3545; }

        .quiz-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Topic Performance */
        .topic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .topic-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .topic-stats {
            text-align: right;
        }

        .topic-average {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .topic-attempts {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Back Button */
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }

        .back-btn i {
            margin-right: 0.5rem;
        }

        /* Status Indicators */
        .status-active {
            color: #28a745;
            font-weight: 600;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .student-name {
                font-size: 2rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
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
        <div class="profile-header">
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
                    <p class="student-university">
                        <i class="fas fa-university"></i> {{ $student->university ?? 'University not specified' }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-envelope"></i> {{ $student->email }}
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-circle {{ $data['overview']['last_active'] && $data['overview']['last_active']->gte(now()->subDays(3)) ? 'status-active' : 'status-inactive' }}"></i>
                        @if($data['overview']['last_active'])
                            Last active {{ $data['overview']['last_active']->diffForHumans() }}
                        @else
                            Never active
                        @endif
                    </p>

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
                <div class="content-card">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Performance Overview
                    </h3>
                    <div class="performance-grid">
                        <div class="metric-item">
                            <span class="metric-value">{{ $data['overview']['average_score'] }}%</span>
                            <span class="metric-label">Average Score</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $data['overview']['best_score'] }}%</span>
                            <span class="metric-label">Best Score</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $data['overview']['accuracy_rate'] }}%</span>
                            <span class="metric-label">Accuracy Rate</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-value">{{ $data['study_patterns']['recent_activity'] }}</span>
                            <span class="metric-label">Recent Activity (30 days)</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Quiz History -->
                <div class="content-card">
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
                        <p class="text-muted text-center py-4">No quiz attempts yet</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Study Patterns -->
                <div class="content-card">
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
                <div class="content-card">
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
                        <p class="text-muted text-center py-4">No topic data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>