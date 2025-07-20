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
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-create {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-create:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .student-checkbox {
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .student-checkbox:hover {
            background-color: #f8f9fa;
        }
        .student-checkbox input:checked + label {
            color: #28a745;
            font-weight: 600;
        }
        .selected-count {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="form-container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create Assignment</h1>
                    <p class="text-muted mb-0">Assign topics to students with deadlines</p>
                </div>
                <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('assignments.store') }}" method="POST" id="assignmentForm">
                @csrf
                
                <!-- Course and Topic Selection -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-book me-2"></i>Course & Topic
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course *</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select a course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">
                                            {{ $course->name }} ({{ $course->year }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="topic_id" class="form-label">Topic *</label>
                                <select class="form-select" id="topic_id" name="topic_id" required disabled>
                                    <option value="">Select a course first</option>
                                </select>
                                <div class="form-text">Topics will load after selecting a course</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment Details -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Assignment Details
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Assignment Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="{{ old('title') }}" placeholder="Leave blank to use topic name">
                                <div class="form-text">Optional - will use topic name if empty</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                                <select class="form-select" id="duration_minutes" name="duration_minutes">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>1 hour</option>
                                    <option value="90">1.5 hours</option>
                                    <option value="120">2 hours</option>
                                    <option value="180">3 hours</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deadline" class="form-label">Deadline *</label>
                                <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                       required min="{{ now()->format('Y-m-d\TH:i') }}">
                                <div class="quick-deadline-buttons mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setQuickDeadline(1)">Tomorrow</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setQuickDeadline(3)">3 Days</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setQuickDeadline(7)">1 Week</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Instructions (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Any special instructions...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Selection -->
                <div class="form-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="section-title mb-0">
                            <i class="fas fa-users me-2"></i>Select Students
                        </h4>
                        <div class="d-flex align-items-center gap-3">
                            <span class="selected-count" id="selectedCount">0 selected</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectNone()">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        @foreach($students as $student)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="student-checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input student-checkbox-input" type="checkbox" 
                                               name="student_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}">
                                        <label class="form-check-label" for="student_{{ $student->id }}">
                                            {{ $student->name }}
                                            <small class="text-muted d-block">{{ $student->username }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($students->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No students available for assignment.</p>
                        </div>
                    @endif
                </div>

                <!-- Submit -->
                <div class="form-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-create">
                            <i class="fas fa-plus me-2"></i>Create Assignments
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update selected count
        function updateSelectedCount() {
            const checked = document.querySelectorAll('.student-checkbox-input:checked').length;
            document.getElementById('selectedCount').textContent = `${checked} selected`;
        }

        // Add event listeners to checkboxes
        document.querySelectorAll('.student-checkbox-input').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Select all students
        function selectAll() {
            document.querySelectorAll('.student-checkbox-input').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }

        // Select no students
        function selectNone() {
            document.querySelectorAll('.student-checkbox-input').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }

        // Quick deadline functions
        function setQuickDeadline(days) {
            const now = new Date();
            now.setDate(now.getDate() + days);
            now.setHours(23, 59);
            
            const formatted = now.toISOString().slice(0, 16);
            document.getElementById('deadline').value = formatted;
        }

        // Load topics when course is selected
        document.getElementById('course_id').addEventListener('change', function() {
            const courseId = this.value;
            const topicSelect = document.getElementById('topic_id');
            
            if (courseId) {
                topicSelect.disabled = true;
                topicSelect.innerHTML = '<option value="">Loading topics...</option>';
                
                fetch(`/assignments/courses/${courseId}/topics`)
                    .then(response => response.json())
                    .then(topics => {
                        topicSelect.innerHTML = '<option value="">Select a topic</option>';
                        topics.forEach(topic => {
                            topicSelect.innerHTML += `<option value="${topic.id}">${topic.name}</option>`;
                        });
                        topicSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading topics:', error);
                        topicSelect.innerHTML = '<option value="">Error loading topics</option>';
                    });
            } else {
                topicSelect.innerHTML = '<option value="">Select a course first</option>';
                topicSelect.disabled = true;
            }
        });

        // Form validation
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const selectedStudents = document.querySelectorAll('.student-checkbox-input:checked').length;
            
            if (selectedStudents === 0) {
                e.preventDefault();
                alert('Please select at least one student.');
                return false;
            }
            
            const deadline = new Date(document.getElementById('deadline').value);
            const now = new Date();
            
            if (deadline <= now) {
                e.preventDefault();
                alert('Deadline must be in the future.');
                return false;
            }
        });

        // Initialize
        updateSelectedCount();
    </script>
</body>
</html>