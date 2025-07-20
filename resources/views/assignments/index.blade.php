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
        .assignment-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .assignment-card.overdue {
            border-left-color: #dc3545;
        }
        .assignment-card.urgent {
            border-left-color: #fd7e14;
        }
        .assignment-card.completed {
            border-left-color: #28a745;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-create {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-create:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Assignment Management</h1>
                <p class="text-muted mb-0">Manage and track student assignments</p>
            </div>
            <div>
                <a href="{{ route('assignments.create') }}" class="btn btn-create me-2">
                    <i class="fas fa-plus me-2"></i>New Assignment
                </a>
                <a href="{{ route('assignments.create') }}" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>Create Assignment
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $assignments->where('status', 'active')->count() }}</h4>
                            <small>Active Assignments</small>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $assignments->where('status', 'overdue')->count() }}</h4>
                            <small>Overdue</small>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $assignments->where('status', 'completed')->count() }}</h4>
                            <small>Completed</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $assignments->count() }}</h4>
                            <small>Total Assignments</small>
                        </div>
                        <i class="fas fa-tasks fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="{{ route('assignments.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Assignments List -->
        <div class="row">
            @forelse($assignments as $assignment)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card assignment-card h-100 
                        {{ $assignment->status === 'overdue' ? 'overdue' : '' }}
                        {{ $assignment->status === 'active' && $assignment->deadline->diffInHours(now()) <= 24 ? 'urgent' : '' }}
                        {{ $assignment->status === 'completed' ? 'completed' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">{{ $assignment->title }}</h5>
                                <span class="badge status-badge 
                                    {{ $assignment->status === 'active' ? 'bg-primary' : '' }}
                                    {{ $assignment->status === 'overdue' ? 'bg-danger' : '' }}
                                    {{ $assignment->status === 'completed' ? 'bg-success' : '' }}
                                    {{ $assignment->status === 'locked' ? 'bg-secondary' : '' }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="fas fa-user me-1"></i>{{ $assignment->student->name }}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-book me-1"></i>{{ $assignment->course->name }}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-bookmark me-1"></i>{{ $assignment->topic->name }}
                                </small>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar me-1"></i>
                                    Due: {{ $assignment->deadline->format('M j, Y g:i A') }}
                                </small>
                                <small class="d-block 
                                    {{ $assignment->isOverdue() ? 'text-danger' : '' }}
                                    {{ $assignment->status === 'active' && $assignment->deadline->diffInHours(now()) <= 24 ? 'text-warning' : '' }}">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $assignment->getTimeRemainingHuman() }}
                                </small>
                            </div>

                            @if($assignment->description)
                                <p class="card-text small text-muted">
                                    {{ Str::limit($assignment->description, 100) }}
                                </p>
                            @endif

                            @if($assignment->score)
                                <div class="mb-2">
                                    <small class="text-success">
                                        <i class="fas fa-star me-1"></i>Score: {{ $assignment->score }}%
                                    </small>
                                </div>
                            @endif
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($assignment->status !== 'completed')
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteAssignment({{ $assignment->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                                
                                @if($assignment->canBeCompleted())
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="markCompleted({{ $assignment->id }})">
                                        <i class="fas fa-check me-1"></i>Complete
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No assignments found</h4>
                        <p class="text-muted">Create your first assignment to get started.</p>
                        <a href="{{ route('assignments.create') }}" class="btn btn-create">
                            <i class="fas fa-plus me-2"></i>Create Assignment
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-warning w-100" onclick="updateOverdueAssignments()">
                                    <i class="fas fa-sync me-2"></i>Update Overdue Status
                                </button>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('assignments.analytics') }}" class="btn btn-outline-info w-100">
                                    <i class="fas fa-chart-bar me-2"></i>View Analytics
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="{{ route('tutor.dashboard') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function deleteAssignment(assignmentId) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch(`/assignments/${assignmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting assignment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the assignment.');
                });
            }
        }

        function markCompleted(assignmentId) {
            const score = prompt('Enter score (0-100, optional):');
            if (score !== null) {
                const scoreValue = score === '' ? null : parseInt(score);
                
                fetch(`/assignments/${assignmentId}/mark-completed`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ score: scoreValue })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error completing assignment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while completing the assignment.');
                });
            }
        }

        function updateOverdueAssignments() {
            fetch('/assignments/update-overdue', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Updated ${data.updated_count} overdue assignments.`);
                    location.reload();
                } else {
                    alert('Error updating assignments: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating assignments.');
            });
        }
    </script>
</body>
</html>