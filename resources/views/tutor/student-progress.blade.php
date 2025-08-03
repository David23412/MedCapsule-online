@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Student Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            @if($student->profile_picture_url)
                                <img src="{{ asset($student->profile_picture_url) }}" 
                                     class="rounded-circle" 
                                     width="64" 
                                     height="64"
                                     alt="{{ $student->name }}">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                     style="width: 64px; height: 64px; font-size: 24px;">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <h2 class="mb-1">{{ $student->name }}</h2>
                            <p class="text-muted mb-0">
                                Member since {{ $student->created_at->format('M Y') }} · 
                                Last active {{ $student->last_active_at ? $student->last_active_at->diffForHumans() : 'Never' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Course Progress</h6>
                    <h3 class="mb-0">{{ $analytics['overview']['completed_courses'] }}/{{ $analytics['overview']['total_courses'] }}</h3>
                    <small class="text-muted">Courses Completed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Average Quiz Score</h6>
                    <h3 class="mb-0">{{ number_format($analytics['overview']['average_quiz_score'], 1) }}%</h3>
                    <small class="text-muted">Across All Attempts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Study Time</h6>
                    <h3 class="mb-0">{{ number_format($analytics['overview']['total_study_time'] / 60, 1) }}h</h3>
                    <small class="text-muted">Total Time Spent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Improvement</h6>
                    @php
                        $trend = $analytics['quizPerformance']['improvement_trend']['percentage'];
                        $trendColor = $trend >= 0 ? 'success' : 'danger';
                        $trendIcon = $trend >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                    @endphp
                    <h3 class="mb-0 text-{{ $trendColor }}">
                        <i class="fas {{ $trendIcon }} me-2"></i>
                        {{ abs(number_format($trend, 1)) }}%
                    </h3>
                    <small class="text-muted">Monthly Progress</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Course Progress -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Course Progress</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Progress</th>
                                    <th>Time Spent</th>
                                    <th>Status</th>
                                    <th>Last Accessed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analytics['courseProgress']['courses'] as $course)
                                <tr>
                                    <td>{{ $course['name'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar" 
                                                     role="progressbar" 
                                                     style="width: {{ $course['completion'] }}%"
                                                     aria-valuenow="{{ $course['completion'] }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="text-muted">{{ number_format($course['completion'], 1) }}%</span>
                                        </div>
                                    </td>
                                    <td>{{ number_format($course['time_spent'] / 60, 1) }}h</td>
                                    <td>
                                        <span class="badge bg-{{ $course['status'] === 'completed' ? 'success' : 'primary' }}">
                                            {{ ucfirst($course['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $course['last_accessed'] ? Carbon\Carbon::parse($course['last_accessed'])->diffForHumans() : 'Never' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strengths & Weaknesses -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Performance Analysis</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-success mb-3">Strengths</h6>
                    @forelse($analytics['strengthsWeaknesses']['strengths'] as $topic => $data)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $topic }}</span>
                            <span class="text-success">{{ number_format($data['average_score'], 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: {{ $data['average_score'] }}%">
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No strengths identified yet</p>
                    @endforelse

                    <h6 class="text-danger mb-3 mt-4">Areas for Improvement</h6>
                    @forelse($analytics['strengthsWeaknesses']['weaknesses'] as $topic => $data)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ $topic }}</span>
                            <span class="text-danger">{{ number_format($data['average_score'], 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" 
                                 role="progressbar" 
                                 style="width: {{ $data['average_score'] }}%">
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No significant weaknesses found</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Weekly Activity Chart -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Weekly Activity</h5>
                </div>
                <div class="card-body">
                    <canvas id="weeklyActivityChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($analytics['recentActivity'] as $activity)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <p class="mb-1">{{ $activity['description'] }}</p>
                                <small class="text-muted">{{ Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="list-group-item text-center text-muted">
                            No recent activity
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts and data
    let weeklyActivityChart;
    let lastUpdateTimestamp = new Date().toISOString();
    
    function initializeCharts(weeklyActivity) {
        const dates = Object.keys(weeklyActivity);
        const quizAttempts = dates.map(date => weeklyActivity[date].quiz_attempts);
        const studyTime = dates.map(date => weeklyActivity[date].study_time / 60);

        weeklyActivityChart = new Chart(document.getElementById('weeklyActivityChart'), {
            type: 'bar',
            data: {
                labels: dates.map(date => new Date(date).toLocaleDateString()),
                datasets: [{
                    label: 'Quiz Attempts',
                    data: quizAttempts,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Study Time (hours)',
                    data: studyTime,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    'y-axis-1': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Quiz Attempts'
                        }
                    },
                    'y-axis-2': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Study Time (hours)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                animation: {
                    duration: 750
                }
            }
        });
    }

    // Initialize Echo for real-time updates
    const studentId = {{ $student->id }};
    const tutorId = {{ auth()->id() }};
    
    // Listen for updates on private channel
    window.Echo.private(`tutor.${tutorId}`)
        .listen('.student.progress.updated', (data) => {
            if (data.student_id === studentId) {
                updateDashboard(data.analytics);
                showUpdateNotification();
                lastUpdateTimestamp = data.timestamp;
            }
        });

    // Function to update dashboard with new data
    function updateDashboard(analytics) {
        // Update overview cards
        document.getElementById('completed-courses').textContent = 
            `${analytics.overview.completed_courses}/${analytics.overview.total_courses}`;
        document.getElementById('average-quiz-score').textContent = 
            `${analytics.overview.average_quiz_score.toFixed(1)}%`;
        document.getElementById('total-study-time').textContent = 
            `${(analytics.overview.total_study_time / 60).toFixed(1)}h`;

        // Update improvement trend
        const trend = analytics.quizPerformance.improvement_trend.percentage;
        const trendElement = document.getElementById('improvement-trend');
        trendElement.innerHTML = `
            <i class="fas fa-arrow-${trend >= 0 ? 'up' : 'down'} me-2"></i>
            ${Math.abs(trend).toFixed(1)}%
        `;
        trendElement.className = `mb-0 text-${trend >= 0 ? 'success' : 'danger'}`;

        // Update course progress table
        updateCourseProgressTable(analytics.courseProgress.courses);

        // Update strengths and weaknesses
        updatePerformanceAnalysis(analytics.strengthsWeaknesses);

        // Update weekly activity chart
        updateWeeklyActivityChart(analytics.weeklyActivity);

        // Update recent activity feed
        updateRecentActivity(analytics.recentActivity);

        // Update last refresh timestamp
        document.getElementById('last-update').textContent = 
            `Last updated: ${new Date().toLocaleTimeString()}`;
    }

    // Function to update course progress table
    function updateCourseProgressTable(courses) {
        const tableBody = document.querySelector('#course-progress-table tbody');
        tableBody.innerHTML = courses.map(course => `
            <tr>
                <td>${course.name}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                            <div class="progress-bar" 
                                 role="progressbar" 
                                 style="width: ${course.completion}%">
                            </div>
                        </div>
                        <span class="text-muted">${course.completion.toFixed(1)}%</span>
                    </div>
                </td>
                <td>${(course.time_spent / 60).toFixed(1)}h</td>
                <td>
                    <span class="badge bg-${course.status === 'completed' ? 'success' : 'primary'}">
                        ${course.status.charAt(0).toUpperCase() + course.status.slice(1)}
                    </span>
                </td>
                <td>${formatTimeAgo(course.last_accessed)}</td>
            </tr>
        `).join('');
    }

    // Function to update performance analysis
    function updatePerformanceAnalysis(data) {
        updateTopicList('strengths-list', data.strengths);
        updateTopicList('weaknesses-list', data.weaknesses);
    }

    function updateTopicList(elementId, topics) {
        const container = document.getElementById(elementId);
        if (Object.keys(topics).length === 0) {
            container.innerHTML = '<p class="text-muted">No data available</p>';
            return;
        }

        container.innerHTML = Object.entries(topics).map(([topic, data]) => `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>${topic}</span>
                    <span class="text-${elementId.includes('strengths') ? 'success' : 'danger'}">
                        ${data.average_score.toFixed(1)}%
                    </span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-${elementId.includes('strengths') ? 'success' : 'danger'}" 
                         role="progressbar" 
                         style="width: ${data.average_score}%">
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Function to update weekly activity chart
    function updateWeeklyActivityChart(weeklyActivity) {
        const dates = Object.keys(weeklyActivity);
        const quizAttempts = dates.map(date => weeklyActivity[date].quiz_attempts);
        const studyTime = dates.map(date => weeklyActivity[date].study_time / 60);

        weeklyActivityChart.data.labels = dates.map(date => new Date(date).toLocaleDateString());
        weeklyActivityChart.data.datasets[0].data = quizAttempts;
        weeklyActivityChart.data.datasets[1].data = studyTime;
        weeklyActivityChart.update('active');
    }

    // Function to update recent activity feed
    function updateRecentActivity(activities) {
        const container = document.getElementById('recent-activity-feed');
        if (activities.length === 0) {
            container.innerHTML = `
                <div class="list-group-item text-center text-muted">
                    No recent activity
                </div>
            `;
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <p class="mb-1">${activity.description}</p>
                    <small class="text-muted">${formatTimeAgo(activity.timestamp)}</small>
                </div>
            </div>
        `).join('');
    }

    // Function to show update notification
    function showUpdateNotification() {
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
        notification.innerHTML = `
            <i class="fas fa-sync-alt me-2"></i> Dashboard updated with latest data
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Helper function to format time ago
    function formatTimeAgo(timestamp) {
        if (!timestamp) return 'Never';
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    }

    // Initialize charts with current data
    initializeCharts(@json($analytics['weeklyActivity']));

    // Poll for updates every minute as backup
    setInterval(async () => {
        try {
            const response = await fetch(`/tutor/student/${studentId}/updates?last_update=${lastUpdateTimestamp}`);
            const data = await response.json();
            
            if (data.success && data.timestamp !== lastUpdateTimestamp) {
                updateDashboard(data.data);
                lastUpdateTimestamp = data.timestamp;
            }
        } catch (error) {
            console.error('Failed to fetch updates:', error);
        }
    }, 60000);
});
</script>
@endpush

@push('styles')
<style>
    .update-pulse {
        animation: pulse 1s;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .chart-container {
        position: relative;
        min-height: 300px;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endpush 