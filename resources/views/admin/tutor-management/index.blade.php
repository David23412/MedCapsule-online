<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Management - MedCapsule Admin</title>
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
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .quick-action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.3);
            color: white;
        }
        .tutor-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .student-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            margin: 0.25rem;
            display: inline-block;
        }
        .alert-unassigned {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            border: none;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-users-cog me-3"></i>Tutor Management Dashboard
                    </h1>
                    <p class="mb-0 opacity-75">Streamlined tutor and student assignment system</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('admin.tutors.create') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Add New Tutor
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number">{{ $stats['total_tutors'] }}</div>
                    <div class="text-muted">Active Tutors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number">{{ $stats['total_students'] }}</div>
                    <div class="text-muted">Total Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-warning">{{ $stats['unassigned_students'] }}</div>
                    <div class="text-muted">Unassigned Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-number text-success">{{ $stats['active_assignments'] }}</div>
                    <div class="text-muted">Active Assignments</div>
                </div>
            </div>
        </div>

        <!-- Unassigned Students Alert -->
        @if($stats['unassigned_students'] > 0)
        <div class="alert alert-unassigned mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ $stats['unassigned_students'] }} students need tutor assignment
                    </h5>
                    <p class="mb-0">These students are enrolled but not assigned to any tutor yet.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('admin.tutors.bulk-operations') }}" class="btn btn-dark">
                        <i class="fas fa-magic me-2"></i>Bulk Assign
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <a href="{{ route('admin.tutors.create') }}" class="quick-action-btn">
                            <i class="fas fa-user-plus me-2"></i>Create New Tutor
                        </a>
                        <a href="{{ route('admin.tutors.bulk-operations') }}" class="quick-action-btn">
                            <i class="fas fa-users me-2"></i>Bulk Operations
                        </a>
                        <a href="{{ route('assignments.create') }}" class="quick-action-btn">
                            <i class="fas fa-tasks me-2"></i>Create Assignment
                        </a>
                        <a href="{{ route('add-course.form') }}" class="quick-action-btn">
                            <i class="fas fa-book me-2"></i>Add Course
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Tutors -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Recent Tutors
                        </h5>
                        <a href="{{ route('admin.tutors.bulk-operations') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        @forelse($recentTutors as $tutor)
                        <div class="tutor-card">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1">{{ $tutor->name }}</h6>
                                    <small class="text-muted">{{ $tutor->email }}</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="fw-bold text-primary">{{ $tutor->assigned_students_count }}</div>
                                        <small class="text-muted">Assigned Students</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="{{ route('admin.tutors.assign-students', $tutor) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-users me-1"></i>Manage Students
                                    </a>
                                </div>
                            </div>
                            @if($tutor->assigned_students_count > 0)
                            <div class="mt-2">
                                @foreach($tutor->assignedStudents->take(5) as $student)
                                    <span class="student-badge">{{ $student->name }}</span>
                                @endforeach
                                @if($tutor->assigned_students_count > 5)
                                    <span class="student-badge">+{{ $tutor->assigned_students_count - 5 }} more</span>
                                @endif
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-plus fa-3x mb-3"></i>
                            <h5>No tutors yet</h5>
                            <p>Create your first tutor to get started</p>
                            <a href="{{ route('admin.tutors.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Tutor
                            </a>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Unassigned Students -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user-clock me-2"></i>Unassigned Students
                        </h5>
                    </div>
                    <div class="card-body">
                        @forelse($unassignedStudents as $student)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                            <div>
                                <div class="fw-bold">{{ $student->name }}</div>
                                <small class="text-muted">{{ $student->email }}</small>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="quickAssignStudent({{ $student->id }}, '{{ $student->name }}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        @empty
                        <div class="text-center text-success py-4">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">All students are assigned!</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Assign Modal -->
    <div class="modal fade" id="quickAssignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Assign Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Assign <strong id="studentName"></strong> to:</p>
                    <select class="form-select" id="tutorSelect">
                        <option value="">Select a tutor...</option>
{{-- assignedStudents eager-loaded in controller with withCount --}}
                        @foreach($recentTutors as $tutor)
                            <option value="{{ $tutor->id }}">{{ $tutor->name }} ({{ $tutor->assigned_students_count }} students)</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processQuickAssign()">Assign Student</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedStudentId = null;

        function quickAssignStudent(studentId, studentName) {
            selectedStudentId = studentId;
            document.getElementById('studentName').textContent = studentName;
            new bootstrap.Modal(document.getElementById('quickAssignModal')).show();
        }

        function processQuickAssign() {
            const tutorId = document.getElementById('tutorSelect').value;
            if (!tutorId) {
                alert('Please select a tutor');
                return;
            }

            fetch('{{ route("admin.tutors.process-assignment", ":tutor") }}'.replace(':tutor', tutorId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    student_ids: [selectedStudentId]
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error assigning student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error assigning student');
            });
        }
    </script>
</body>
</html>