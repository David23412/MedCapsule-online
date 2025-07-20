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
        .preview-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
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
                    <i class="fas fa-arrow-left me-2"></i>Back to Assignments
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

            <form action="{{ route('assignments.store') }}" method="POST" id="bulkAssignmentForm">
                @csrf
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Assignment Details
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Assignment Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="{{ old('title') }}" required>
                                <div class="form-text">This title will be used for all assignments</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" 
                                       value="{{ old('duration_minutes', 60) }}" min="1" max="1440">
                                <div class="form-text">Estimated time to complete</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deadline" class="form-label">Deadline *</label>
                                <input type="datetime-local" class="form-control" id="deadline" name="deadline" 
                                       value="{{ old('deadline') }}" required min="{{ now()->format('Y-m-d\TH:i') }}">
                                <div class="form-text">Same deadline for all students</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skip_existing" name="skip_existing" value="1">
                                    <label class="form-check-label" for="skip_existing">
                                        Skip students who already have this assignment
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Optional description or instructions for students">{{ old('description') }}</textarea>
                    </div>
                </div>

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
                                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
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
                                    Select None
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="studentsContainer">
                        @foreach($students as $student)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="student-checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input student-checkbox-input" type="checkbox" 
                                               name="student_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}"
                                               {{ in_array($student->id, old('student_ids', [])) ? 'checked' : '' }}>
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

                <!-- Preview Section -->
                <div class="form-section" id="previewSection" style="display: none;">
                    <h4 class="section-title">
                        <i class="fas fa-eye me-2"></i>Bulk Assignment Preview
                    </h4>
                    <div class="preview-section">
                        <div id="previewContent">
                            <!-- Preview will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-outline-info" onclick="previewBulkAssignment()">
                                <i class="fas fa-eye me-2"></i>Preview Assignments
                            </button>
                        </div>
                        <div>
                            <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-create">
                                <i class="fas fa-layer-group me-2"></i>Create Assignments
                            </button>
                        </div>
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

        // Preview bulk assignment
        function previewBulkAssignment() {
            const form = document.getElementById('bulkAssignmentForm');
            const formData = new FormData(form);
            
            const title = formData.get('title');
            const description = formData.get('description');
            const courseSelect = document.getElementById('course_id');
            const topicSelect = document.getElementById('topic_id');
            const deadline = formData.get('deadline');
            const duration = formData.get('duration_minutes');
            const skipExisting = formData.get('skip_existing');
            
            const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox-input:checked'))
                .map(checkbox => {
                    const label = document.querySelector(`label[for="${checkbox.id}"]`);
                    return label.textContent.trim();
                });
            
            if (!title || !courseSelect.value || !topicSelect.value || selectedStudents.length === 0 || !deadline) {
                alert('Please fill in all required fields and select at least one student before previewing.');
                return;
            }
            
            const courseName = courseSelect.options[courseSelect.selectedIndex].text;
            const topicName = topicSelect.options[topicSelect.selectedIndex].text;
            const deadlineFormatted = new Date(deadline).toLocaleString();
            
            const previewHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">${title}</h5>
                        ${description ? `<p class="text-muted">${description}</p>` : ''}
                        <ul class="list-unstyled">
                            <li><strong>Course:</strong> ${courseName}</li>
                            <li><strong>Topic:</strong> ${topicName}</li>
                            <li><strong>Deadline:</strong> ${deadlineFormatted}</li>
                            <li><strong>Duration:</strong> ${duration} minutes</li>
                            ${skipExisting ? '<li><strong>Skip existing:</strong> Yes</li>' : ''}
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Selected Students (${selectedStudents.length}):</h6>
                        <div class="row">
                            ${selectedStudents.map(student => `
                                <div class="col-6">
                                    <small class="text-muted">• ${student}</small>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = previewHtml;
            document.getElementById('previewSection').style.display = 'block';
            document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
        }

        // Form validation
        document.getElementById('bulkAssignmentForm').addEventListener('submit', function(e) {
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
            
            if (!confirm(`Are you sure you want to create ${selectedStudents} assignments?`)) {
                e.preventDefault();
                return false;
            }
        });

        // Initialize selected count
        updateSelectedCount();
    </script>
</body>
</html>