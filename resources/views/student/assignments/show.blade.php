<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .assignment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .info-card.urgent {
            border-left-color: #dc3545;
        }
        .info-card.overdue {
            border-left-color: #fd7e14;
        }
        .info-card.completed {
            border-left-color: #28a745;
        }
        .countdown-display {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .countdown-timer {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
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
        .btn-start {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .progress-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Header -->
        <div class="assignment-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">{{ $assignment->title }}</h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user me-2"></i>Assigned by {{ $assignment->tutor ? $assignment->tutor->name : 'Unknown Tutor' }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="status-badge 
                        {{ $assignment->status === 'active' ? 'bg-primary' : '' }}
                        {{ $assignment->status === 'overdue' ? 'bg-warning' : '' }}
                        {{ $assignment->status === 'completed' ? 'bg-success' : '' }}
                        {{ $assignment->status === 'locked' ? 'bg-secondary' : '' }}">
                        {{ ucfirst($assignment->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Assignment Details -->
                <div class="info-card 
                    {{ $assignment->status === 'active' && $assignment->deadline->diffInHours(now()) <= 24 ? 'urgent' : '' }}
                    {{ $assignment->status === 'overdue' ? 'overdue' : '' }}
                    {{ $assignment->status === 'completed' ? 'completed' : '' }}">
                    <h4 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>Assignment Details
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong><i class="fas fa-book me-2"></i>Course:</strong>
                                    {{ $assignment->course ? $assignment->course->name : 'Unknown Course' }}
                                </li>
                                <li class="mb-2">
                                    <strong><i class="fas fa-bookmark me-2"></i>Topic:</strong>
                                    {{ $assignment->topic ? $assignment->topic->name : 'Unknown Topic' }}
                                </li>
                                <li class="mb-2">
                                    <strong><i class="fas fa-clock me-2"></i>Duration:</strong>
                                    {{ $assignment->duration_minutes }} minutes
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong><i class="fas fa-calendar me-2"></i>Assigned:</strong>
                                    {{ $assignment->created_at->format('M j, Y g:i A') }}
                                </li>
                                <li class="mb-2">
                                    <strong><i class="fas fa-calendar-check me-2"></i>Due:</strong>
                                    {{ $assignment->deadline->format('M j, Y g:i A') }}
                                </li>
                                @if($assignment->completed_at)
                                    <li class="mb-2">
                                        <strong><i class="fas fa-check me-2"></i>Completed:</strong>
                                        {{ $assignment->completed_at->format('M j, Y g:i A') }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    @if($assignment->description)
                        <div class="mt-3">
                            <strong><i class="fas fa-file-text me-2"></i>Instructions:</strong>
                            <p class="mt-2 mb-0">{{ $assignment->description }}</p>
                        </div>
                    @endif

                    @if($assignment->score)
                        <div class="mt-3">
                            <div class="alert alert-success">
                                <i class="fas fa-star me-2"></i>
                                <strong>Score: {{ $assignment->score }}%</strong>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                @if($assignment->canBeCompleted())
                    <div class="text-center mb-4">
                        <button type="button" class="btn btn-start btn-lg" onclick="startAssignment()">
                            <i class="fas fa-play me-2"></i>Start Assignment
                        </button>
                        <p class="text-muted mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            This will take you to the topic quiz
                        </p>
                    </div>
                @elseif($assignment->status === 'completed')
                    <div class="text-center mb-4">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Assignment Completed!</strong>
                            @if($assignment->score_percentage)
                                <br>Your score: {{ $assignment->score_percentage }}%
                            @endif
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Assignment Locked</strong>
                            <br>This assignment has been completed and cannot be retaken.
                        </div>
                    </div>
                @elseif($assignment->status === 'overdue')
                    <div class="text-center mb-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>This assignment is overdue</strong>
                        </div>
                        <button type="button" class="btn btn-outline-warning" onclick="requestExtension()">
                            <i class="fas fa-clock me-2"></i>Request Extension
                        </button>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Countdown Timer -->
                @if($assignment->status !== 'completed')
                    <div class="countdown-display">
                        <h5 class="mb-3">
                            @if($assignment->isOverdue())
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>Overdue
                            @else
                                <i class="fas fa-clock me-2"></i>Time Remaining
                            @endif
                        </h5>
                        
                        @if(!$assignment->isOverdue())
                            <div class="countdown-timer normal-timer" data-deadline="{{ $assignment->deadline->toISOString() }}">
                                Loading...
                            </div>
                        @else
                            <div class="countdown-timer urgent-timer">
                                {{ $assignment->getTimeRemainingHuman() }}
                            </div>
                        @endif
                        
                        <small class="text-muted">
                            {{ $assignment->isOverdue() ? 'Was due' : 'Due' }}: 
                            {{ $assignment->deadline->format('M j, Y g:i A') }}
                        </small>
                    </div>
                @endif

                <!-- Progress Information -->
                <div class="progress-section">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2"></i>Progress
                    </h5>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Completion</small>
                            <small>{{ $assignment->status === 'completed' ? '100' : '0' }}%</small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar {{ $assignment->status === 'completed' ? 'bg-success' : 'bg-primary' }}" 
                                 style="width: {{ $assignment->status === 'completed' ? '100' : '0' }}%"></div>
                        </div>
                    </div>

                    @if($assignment->status !== 'completed')
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Time Progress</small>
                                <small>{{ number_format($assignment->getProgressPercentage(), 1) }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" 
                                     style="width: {{ $assignment->getProgressPercentage() }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="text-center">
                        <small class="text-muted">
                            Difficulty: 
                            <span class="badge 
                                {{ $assignment->getDifficultyLevel() === 'urgent' ? 'bg-danger' : '' }}
                                {{ $assignment->getDifficultyLevel() === 'high' ? 'bg-warning' : '' }}
                                {{ $assignment->getDifficultyLevel() === 'medium' ? 'bg-info' : '' }}
                                {{ $assignment->getDifficultyLevel() === 'low' ? 'bg-success' : '' }}">
                                {{ ucfirst($assignment->getDifficultyLevel()) }}
                            </span>
                        </small>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="info-card">
                    <h6 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('student.assignments.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-2"></i>All Assignments
                        </a>
                        <a href="{{ route('student.assignments.history') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-history me-2"></i>Assignment History
                        </a>
                        <a href="{{ $assignment->course ? route('courses.show', $assignment->course) : '#' }}" class="btn btn-outline-info btn-sm {{ $assignment->course ? '' : 'disabled' }}">
                            <i class="fas fa-book me-2"></i>View Course
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer functionality
        function updateCountdown() {
            const timer = document.querySelector('.countdown-timer[data-deadline]');
            if (!timer) return;

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
        }

        // Start assignment
        function startAssignment() {
            if (confirm('Are you ready to start this assignment? This will take you to the topic quiz.')) {
                fetch(`/student/assignments/{{ $assignment->id }}/start`, {
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
        function requestExtension() {
            const reason = prompt('Please provide a reason for the extension request:');
            if (reason && reason.trim()) {
                const requestedDeadline = prompt('When would you like the new deadline to be? (YYYY-MM-DD HH:MM)');
                if (requestedDeadline) {
                    fetch(`/student/assignments/{{ $assignment->id }}/request-extension`, {
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

        // Initialize countdown and update every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>