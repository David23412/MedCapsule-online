<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tutor Dashboard - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* 80% Zoom */
        html {
            font-size: 12.8px; /* 80% of 16px */
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            scroll-behavior: smooth;
            zoom: 0.80;
            -webkit-text-size-adjust: 100%;
            margin: 0;
            padding: 0;
        }

        /* Navbar - Consistent with MedCapsule design */
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
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 2rem;
            color: #007bff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .nav-link {
            color: #333;
            font-weight: 500;
            padding: 0.5rem 1rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: background-color 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .profile-circle.no-image {
            background-color: #007bff;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            min-height: calc(100vh - 80px);
        }

        /* Tutor Dashboard Specific Styles */
        .dashboard-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .dashboard-title {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
            font-size: 1.8rem;
        }

        .dashboard-subtitle {
            color: #6c757d;
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
        }

        /* Student Cards */
        .student-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            height: 100%;
        }

        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #007bff;
        }

        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .student-avatar {
            margin-right: 1rem;
        }

        .avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }

        .avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .student-name {
            margin: 0 0 0.3rem 0;
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .student-email {
            margin: 0 0 0.5rem 0;
            color: #6c757d;
            font-size: 0.8rem;
        }

        .student-status {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            color: #6c757d;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-dot.active {
            background-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.3);
        }

        .status-dot.inactive {
            background-color: #dc3545;
        }

        /* Student Stats */
        .student-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid #f1f3f4;
            border-bottom: 1px solid #f1f3f4;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 0.7rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-value.excellent { color: #28a745; }
        .stat-value.good { color: #17a2b8; }
        .stat-value.average { color: #ffc107; }
        .stat-value.poor { color: #dc3545; }

        /* Progress Indicator */
        .student-progress {
            margin-bottom: 1rem;
        }

        .progress-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
        }

        .trend {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
        }

        .trend.improving {
            background-color: #d4edda;
            color: #155724;
        }

        .trend.declining {
            background-color: #f8d7da;
            color: #721c24;
        }

        .trend.stable {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Student Actions */
        .student-actions {
            display: flex;
            gap: 0.5rem;
        }

        .student-actions .btn {
            flex: 1;
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
        }

        /* Summary Card */
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 500;
            color: #6c757d;
        }

        .summary-value {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-card.bg-primary { background: linear-gradient(135deg, #007bff, #0056b3) !important; }
        .stat-card.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34) !important; }
        .stat-card.bg-info { background: linear-gradient(135deg, #17a2b8, #117a8b) !important; }
        .stat-card.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800) !important; }

        .stat-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            opacity: 0.8;
        }

        .stat-content h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Course Assignments */
        .course-assignments-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-assignment-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #007bff;
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .course-header h6 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                zoom: 0.9;
            }

            .student-stats {
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
            }

            .student-actions {
                flex-direction: column;
            }

            .summary-stats {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown me-4">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile">Profile</a></li>
                            <li><a class="dropdown-item" href="/tutor/dashboard">Tutor Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="/logout" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="/profile" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                           style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                            @if(!Auth::user()->profile_picture_url)
                                {{ substr(Auth::user()->name, 0, 1) }}
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="dashboard-title">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        Welcome, {{ $tutor->name }}
                    </h2>
                    <p class="dashboard-subtitle">
                        <strong>{{ $students->count() }}</strong> students assigned • 
                        <strong>{{ $analytics['active_students'] }}</strong> active this week • 
                        <strong>{{ number_format($analytics['average_score'], 1) }}%</strong> class average
                    </p>
                    @if(auth()->user()->isAdmin())
                        <span class="badge bg-warning">
                            <i class="fas fa-crown me-1"></i>Admin View - You can see all students
                        </span>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2" role="group">
                        <a href="{{ route('assignments.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Create Assignment
                        </a>
                        <a href="{{ route('assignments.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-clipboard-list"></i> Manage
                        </a>
                    </div>
                    <button class="btn btn-outline-primary me-2" onclick="exportGradeReports()">
                        <i class="fas fa-file-export"></i> Export Reports
                    </button>
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['total_students'] }}</h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['active_students'] }}</h3>
                        <p>Active This Week</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ number_format($analytics['average_score'], 1) }}%</h3>
                        <p>Class Average</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['struggling_students'] }}</h3>
                        <p>Need Attention</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Assignments Overview -->
        @if(isset($courseAssignments) && count($courseAssignments) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="course-assignments-card">
                    <h5><i class="fas fa-book me-2"></i>Course Assignments Overview</h5>
                    <div class="course-grid">
                        @foreach($courseAssignments as $courseId => $assignment)
                        <div class="course-assignment-item">
                            <div class="course-header">
                                <h6>{{ $assignment['course_name'] }}</h6>
                                <span class="badge bg-secondary">{{ $assignment['total_students'] }} students</span>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar" style="width: {{ $assignment['average_progress'] }}%"></div>
                            </div>
                            <small class="text-muted">{{ number_format($assignment['average_progress'], 1) }}% average progress</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Students Grid -->
        <div class="row">
            @forelse($students as $student)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="student-card" onclick="viewStudentProfile({{ $student->id }})">
                    <div class="student-header">
                        <div class="student-avatar">
                            @if($student->profile_picture_url)
                                <img src="{{ secure_url($student->profile_picture_url) }}" 
                                     alt="{{ $student->name }}" 
                                     class="avatar-img">
                            @else
                                <div class="avatar-placeholder">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div class="student-info">
                            <h5 class="student-name">{{ $student->name }}</h5>
                            <p class="student-email">{{ $student->email }}</p>
                            <div class="student-status">
                                @php
                                    $lastActive = $student->last_active_at;
                                    $isActive = $lastActive && $lastActive->gte(now()->subDays(3));
                                @endphp
                                <span class="status-dot {{ $isActive ? 'active' : 'inactive' }}"></span>
                                {{ $isActive ? 'Active' : 'Inactive' }}
                                @if($lastActive)
                                    • {{ $lastActive->diffForHumans() }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="student-stats">
                        <div class="stat-item">
                            <div class="stat-label">Quiz Average</div>
                            @php
                                $avgScore = $student->quizAttempts->avg('score') ?? 0;
                                $scoreClass = $avgScore >= 80 ? 'excellent' : ($avgScore >= 70 ? 'good' : ($avgScore >= 60 ? 'average' : 'poor'));
                            @endphp
                            <div class="stat-value {{ $scoreClass }}">{{ number_format($avgScore, 1) }}%</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Quizzes</div>
                            <div class="stat-value">{{ $student->quizAttempts->count() }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Study Streak</div>
                            <div class="stat-value">{{ $student->study_streak_days ?? 0 }} days</div>
                        </div>
                    </div>

                    <div class="student-progress">
                        @php
                            $recentAttempts = $student->quizAttempts->take(5);
                            $trend = 0;
                            if ($recentAttempts->count() >= 2) {
                                $recent = $recentAttempts->take(2)->avg('score');
                                $older = $recentAttempts->skip(2)->avg('score');
                                $trend = $recent - $older;
                            }
                        @endphp
                        <div class="progress-indicator">
                            <span class="progress-label">Recent Performance</span>
                            @if($trend > 5)
                                <span class="trend improving"><i class="fas fa-arrow-up"></i> Improving</span>
                            @elseif($trend < -5)
                                <span class="trend declining"><i class="fas fa-arrow-down"></i> Declining</span>
                            @else
                                <span class="trend stable"><i class="fas fa-minus"></i> Stable</span>
                            @endif
                        </div>
                    </div>

                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="event.stopPropagation(); viewStudentProfile({{ $student->id }})">
                            <i class="fas fa-user"></i> View Profile
                        </button>
                        <button class="btn btn-outline-primary" onclick="event.stopPropagation(); generateStudentReport({{ $student->id }})">
                            <i class="fas fa-file-alt"></i> Report
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4>No Students Assigned</h4>
                    <p class="text-muted">You don't have any students assigned yet. Contact your administrator to get students assigned to you.</p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Quick Stats Summary -->
        @if($students->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="summary-card">
                    <h5>Class Summary</h5>
                    <div class="summary-stats">
                        <div class="summary-item">
                            <span class="summary-label">Class Average:</span>
                            <span class="summary-value">{{ number_format($students->avg(function($s) { return $s->quizAttempts->avg('score') ?? 0; }), 1) }}%</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Quiz Attempts:</span>
                            <span class="summary-value">{{ $students->sum(function($s) { return $s->quizAttempts->count(); }) }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Active Students:</span>
                            <span class="summary-value">{{ $students->filter(function($s) { return $s->last_active_at && $s->last_active_at->gte(now()->subDays(7)); })->count() }}/{{ $students->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Student Profile Functions
        function viewStudentProfile(studentId) {
            // Redirect to the student's profile page via tutor route
            window.location.href = `/tutor/student/${studentId}/profile`;
        }

        function generateStudentReport(studentId) {
            // Generate a grade report for the specific student
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/tutor/student/' + studentId + '/report';
            form.target = '_blank';
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function exportGradeReports() {
            // Export grade reports for all students
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/tutor/reports/export';
            form.target = '_blank';
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function refreshDashboard() {
            // Show loading state
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            // Reload the page
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    </script>
</body>
</html>