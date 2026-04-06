<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .assignment-card {
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .assignment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .urgent-card {
            border-left: 5px solid #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        .active-card {
            border-left: 5px solid #007bff;
            background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%);
        }
        .overdue-card {
            border-left: 5px solid #fd7e14;
            background: linear-gradient(135deg, #fff8f0 0%, #ffffff 100%);
        }
        .completed-card {
            border-left: 5px solid #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        }
        .countdown-timer {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .urgent-timer {
            color: #dc3545;
            animation: pulse 2s infinite;
        }
        .warning-timer {
            color: #fd7e14;
        }
        .normal-timer {
            color: #007bff;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .stats-overview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .btn-start {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-start:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .section-header {
            border-bottom: 3px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">My Assignments</h1>
                <p class="text-muted mb-0">Track your homework and deadlines</p>
            </div>
            <div>
                <a href="{{ route('student.assignments.history') }}" class="btn btn-outline-primary me-2">
                    <i class="fas fa-history me-2"></i>History
                </a>
                <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Courses
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $groupedAssignments['urgent']->count() }}</span>
                        <small>Urgent (< 24h)</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $groupedAssignments['active']->count() }}</span>
                        <small>Active</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $groupedAssignments['overdue']->count() }}</span>
                        <small>Overdue</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ number_format($analytics['completion_rate'], 1) }}%</span>
                        <small>Completion Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Urgent Assignments -->
        @if($groupedAssignments['urgent']->count() > 0)
            <div class="mb-5">
                <h4 class="section-header text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Urgent Assignments (Due within 24 hours)
                </h4>
                <div class="row">
                    @foreach($groupedAssignments['urgent'] as $assignment)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card assignment-card urgent-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">{{ $assignment->title }}</h5>
                                        <span class="badge bg-danger">URGENT</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user me-1"></i>{{ $assignment->tutor ? $assignment->tutor->name : 'Unknown Tutor' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-book me-1"></i>{{ $assignment->course ? $assignment->course->name : 'Unknown Course' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-bookmark me-1"></i>{{ $assignment->topic ? $assignment->topic->name : 'Unknown Topic' }}
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="countdown-timer urgent-timer" data-deadline="{{ $assignment->deadline->toISOString() }}">
                                            Loading...
                                        </div>
                                        <small class="text-muted">
                                            Due: {{ $assignment->deadline->format('M j, Y g:i A') }}
                                        </small>
                                    </div>

                                    @if($assignment->description)
                                        <p class="card-text small">{{ substr($assignment->description, 0, 80) }}{{ strlen($assignment->description) > 80 ? '...' : '' }}</p>
                                    @endif
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        @if($assignment->canBeCompleted())
                                            <button type="button" class="btn btn-start btn-sm" onclick="startAssignment({{ $assignment->id }})">
                                                <i class="fas fa-play me-1"></i>Start Now
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Active Assignments -->
        @if($groupedAssignments['active']->count() > 0)
            <div class="mb-5">
                <h4 class="section-header text-primary">
                    <i class="fas fa-clock me-2"></i>Active Assignments
                </h4>
                <div class="row">
                    @foreach($groupedAssignments['active'] as $assignment)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card assignment-card active-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">{{ $assignment->title }}</h5>
                                        <span class="badge bg-primary">Active</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user me-1"></i>{{ $assignment->tutor ? $assignment->tutor->name : 'Unknown Tutor' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-book me-1"></i>{{ $assignment->course ? $assignment->course->name : 'Unknown Course' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-bookmark me-1"></i>{{ $assignment->topic ? $assignment->topic->name : 'Unknown Topic' }}
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="countdown-timer normal-timer" data-deadline="{{ $assignment->deadline->toISOString() }}">
                                            Loading...
                                        </div>
                                        <small class="text-muted">
                                            Due: {{ $assignment->deadline->format('M j, Y g:i A') }}
                                        </small>
                                    </div>

                                    @if($assignment->description)
                                        <p class="card-text small">{{ substr($assignment->description, 0, 80) }}{{ strlen($assignment->description) > 80 ? '...' : '' }}</p>
                                    @endif
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        @if($assignment->canBeCompleted())
                                            <button type="button" class="btn btn-start btn-sm" onclick="startAssignment({{ $assignment->id }})">
                                                <i class="fas fa-play me-1"></i>Start
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Overdue Assignments -->
        @if($groupedAssignments['overdue']->count() > 0)
            <div class="mb-5">
                <h4 class="section-header text-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>Overdue Assignments
                </h4>
                <div class="row">
                    @foreach($groupedAssignments['overdue'] as $assignment)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card assignment-card overdue-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">{{ $assignment->title }}</h5>
                                        <span class="badge bg-warning">Overdue</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user me-1"></i>{{ $assignment->tutor ? $assignment->tutor->name : 'Unknown Tutor' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-book me-1"></i>{{ $assignment->course ? $assignment->course->name : 'Unknown Course' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-bookmark me-1"></i>{{ $assignment->topic ? $assignment->topic->name : 'Unknown Topic' }}
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="text-warning">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $assignment->getTimeRemainingHuman() }}
                                        </div>
                                        <small class="text-muted">
                                            Was due: {{ $assignment->deadline->format('M j, Y g:i A') }}
                                        </small>
                                    </div>

                                    @if($assignment->description)
                                        <p class="card-text small">{{ substr($assignment->description, 0, 80) }}{{ strlen($assignment->description) > 80 ? '...' : '' }}</p>
                                    @endif
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="requestExtension({{ $assignment->id }})">
                                            <i class="fas fa-clock me-1"></i>Request Extension
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Completed Assignments -->
        @if($groupedAssignments['completed']->count() > 0)
            <div class="mb-5">
                <h4 class="section-header text-success">
                    <i class="fas fa-check-circle me-2"></i>Recently Completed
                </h4>
                <div class="row">
                    @foreach($groupedAssignments['completed']->take(6) as $assignment)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card assignment-card completed-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">{{ $assignment->title }}</h5>
                                        <span class="badge bg-success">Completed</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-user me-1"></i>{{ $assignment->tutor ? $assignment->tutor->name : 'Unknown Tutor' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-book me-1"></i>{{ $assignment->course ? $assignment->course->name : 'Unknown Course' }}
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-bookmark me-1"></i>{{ $assignment->topic ? $assignment->topic->name : 'Unknown Topic' }}
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="text-success">
                                            <i class="fas fa-check me-1"></i>
                                            Completed {{ $assignment->completed_at->diffForHumans() }}
                                        </div>
                                        @if($assignment->score_percentage)
                                            <div class="text-success">
                                                <i class="fas fa-star me-1"></i>Score: {{ $assignment->score_percentage }}%
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-sm btn-outline-success w-100">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($groupedAssignments['completed']->count() > 6)
                    <div class="text-center">
                        <a href="{{ route('student.assignments.history') }}" class="btn btn-outline-success">
                            <i class="fas fa-history me-2"></i>View All Completed Assignments
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Empty State -->
        @if($groupedAssignments['urgent']->count() == 0 && $groupedAssignments['active']->count() == 0 && $groupedAssignments['overdue']->count() == 0 && $groupedAssignments['completed']->count() == 0)
            <div class="text-center py-5">
                <i class="fas fa-tasks fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">No assignments yet</h4>
                <p class="text-muted">Your tutor hasn't assigned any homework yet. Check back later!</p>
                <a href="{{ route('courses.index') }}" class="btn btn-primary">
                    <i class="fas fa-book me-2"></i>Browse Courses
                </a>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer functionality
        function updateCountdowns() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const deadline = new Date(timer.dataset.deadline);
                const now = new Date();
                const diff = deadline - now;

                if (diff <= 0) {
                    timer.textContent = 'Overdue';
                    timer.className = 'countdown-timer urgent-timer';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                let timeString = '';
                if (days > 0) {
                    timeString = `${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    timeString = `${hours}h ${minutes}m ${seconds}s`;
                } else {
                    timeString = `${minutes}m ${seconds}s`;
                }

                timer.textContent = timeString;

                // Update timer color based on urgency
                if (diff < 2 * 60 * 60 * 1000) { // Less than 2 hours
                    timer.className = 'countdown-timer urgent-timer';
                } else if (diff < 24 * 60 * 60 * 1000) { // Less than 24 hours
                    timer.className = 'countdown-timer warning-timer';
                } else {
                    timer.className = 'countdown-timer normal-timer';
                }
            });
        }

        // Start assignment
        function startAssignment(assignmentId) {
            if (confirm('Are you ready to start this assignment?')) {
                fetch(`/student/assignments/${assignmentId}/start`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.json();
                    }
                })
                .then(data => {
                    if (data && !data.success) {
                        alert('Error starting assignment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while starting the assignment.');
                });
            }
        }

        // Request extension
        function requestExtension(assignmentId) {
            const reason = prompt('Please provide a reason for the extension request:');
            if (reason && reason.trim()) {
                const requestedDeadline = prompt('When would you like the new deadline to be? (YYYY-MM-DD HH:MM)');
                if (requestedDeadline) {
                    fetch(`/student/assignments/${assignmentId}/request-extension`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reason: reason.trim(),
                            requested_deadline: requestedDeadline
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Extension request sent to your tutor!');
                        } else {
                            alert('Error sending extension request: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while sending the extension request.');
                    });
                }
            }
        }

        // Initialize countdowns and update every second
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    </script>
</body>
</html>