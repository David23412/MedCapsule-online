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
        .assignment-wizard {
            max-width: 900px;
            margin: 0 auto;
        }
        .wizard-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        .step-number {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        .topic-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .topic-card:hover {
            border-color: #007bff;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
        .topic-card.selected {
            border-color: #28a745;
            background: #f8fff9;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        .student-selection {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .bulk-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .student-checkbox {
            margin-bottom: 0.5rem;
        }
        .btn-primary-gradient {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-success-gradient {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn-primary-gradient:hover, .btn-success-gradient:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            color: white;
        }
        .assignment-summary {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .quick-deadline-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .quick-deadline-btn {
            padding: 0.25rem 0.75rem;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 20px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-deadline-btn:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="assignment-wizard">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-tasks me-2 text-primary"></i>Create Assignment
                    </h1>
                    <p class="text-muted mb-0">Quick and easy assignment creation</p>
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

            <!-- Step 1: Select Course & Topic -->
            <div class="wizard-card">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <div>
                        <h4 class="mb-1">Choose Course & Topic</h4>
                        <p class="text-muted mb-0">Select the course and topic you want to assign</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="course_id" class="form-label fw-bold">Course</label>
                        <select class="form-select form-select-lg" id="course_id" required>
                            <option value="">Choose a course...</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->name }} ({{ $course->year }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Available Topics</label>
                        <div id="topicsContainer" class="border rounded p-3" style="min-height: 120px; background: #f8f9fa;">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-arrow-left me-2"></i>Select a course to see topics
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Student Selection -->
            <div class="wizard-card" id="step2" style="display: none;">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <div>
                        <h4 class="mb-1">Select Students</h4>
                        <p class="text-muted mb-0">Choose who should complete this assignment</p>
                    </div>
                </div>

                <div class="student-selection">
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllStudents()">
                            <i class="fas fa-check-double me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllStudents()">
                            <i class="fas fa-times me-1"></i>Clear All
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="selectCourseStudents()">
                            <i class="fas fa-graduation-cap me-1"></i>Students in Course Only
                        </button>
                    </div>
                    
                    <div class="row" id="studentsContainer">
                        @foreach($students as $student)
                            <div class="col-md-6 col-lg-4 student-checkbox" data-student-id="{{ $student->id }}">
                                <div class="form-check p-3 border rounded mb-2 student-card">
                                    <input class="form-check-input" type="checkbox" value="{{ $student->id }}" 
                                           id="student_{{ $student->id }}" name="student_ids[]">
                                    <label class="form-check-label w-100" for="student_{{ $student->id }}">
                                        <div class="fw-bold">{{ $student->name }}</div>
                                        <small class="text-muted">{{ $student->username }}</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Step 3: Assignment Details -->
            <div class="wizard-card" id="step3" style="display: none;">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <div>
                        <h4 class="mb-1">Assignment Details</h4>
                        <p class="text-muted mb-0">Set deadline and additional information</p>
                    </div>
                </div>

                <form action="{{ route('assignments.bulk-store') }}" method="POST" id="assignmentForm">
                    @csrf
                    <input type="hidden" id="selected_course_id" name="course_id">
                    <input type="hidden" id="selected_topic_id" name="topic_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Assignment Title</label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                       placeholder="Will auto-generate from topic name" value="{{ old('title') }}">
                                <div class="form-text">Leave blank to use topic name automatically</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deadline" class="form-label fw-bold">Deadline</label>
                                <div class="quick-deadline-buttons">
                                    <button type="button" class="quick-deadline-btn" onclick="setQuickDeadline(1)">Tomorrow</button>
                                    <button type="button" class="quick-deadline-btn" onclick="setQuickDeadline(3)">3 Days</button>
                                    <button type="button" class="quick-deadline-btn" onclick="setQuickDeadline(7)">1 Week</button>
                                    <button type="button" class="quick-deadline-btn" onclick="setQuickDeadline(14)">2 Weeks</button>
                                </div>
                                <input type="datetime-local" class="form-control form-control-lg" id="deadline" name="deadline" 
                                       required min="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duration_minutes" class="form-label fw-bold">Duration (minutes)</label>
                                <select class="form-select form-select-lg" id="duration_minutes" name="duration_minutes">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>1 hour</option>
                                    <option value="90">1.5 hours</option>
                                    <option value="120">2 hours</option>
                                    <option value="180">3 hours</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Instructions (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Any special instructions for students...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 4: Review & Create -->
            <div class="wizard-card" id="step4" style="display: none;">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <div>
                        <h4 class="mb-1">Review & Create</h4>
                        <p class="text-muted mb-0">Confirm your assignment details</p>
                    </div>
                </div>

                <div class="assignment-summary">
                    <div id="assignmentSummary">
                        <!-- Summary will be populated by JavaScript -->
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" onclick="goToStep(3)">
                        <i class="fas fa-arrow-left me-2"></i>Back to Edit
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-info me-2" onclick="previewAssignments()">
                            <i class="fas fa-eye me-2"></i>Preview
                        </button>
                        <button type="button" class="btn btn-success-gradient" onclick="createAssignments()">
                            <i class="fas fa-check me-2"></i>Create Assignments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Preview assignment
        function previewAssignment() {
            const form = document.getElementById('assignmentForm');
            const formData = new FormData(form);
            
            const title = formData.get('title');
            const description = formData.get('description');
            const courseSelect = document.getElementById('course_id');
            const topicSelect = document.getElementById('topic_id');
            const studentSelect = document.getElementById('student_id');
            const deadline = formData.get('deadline');
            const duration = formData.get('duration_minutes');
            
            if (!title || !courseSelect.value || !topicSelect.value || !studentSelect.value || !deadline) {
                alert('Please fill in all required fields before previewing.');
                return;
            }
            
            const courseName = courseSelect.options[courseSelect.selectedIndex].text;
            const topicName = topicSelect.options[topicSelect.selectedIndex].text;
            const studentName = studentSelect.options[studentSelect.selectedIndex].text;
            const deadlineFormatted = new Date(deadline).toLocaleString();
            
            const previewHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">${title}</h5>
                        ${description ? `<p class="text-muted">${description}</p>` : ''}
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><strong>Student:</strong> ${studentName}</li>
                            <li><strong>Course:</strong> ${courseName}</li>
                            <li><strong>Topic:</strong> ${topicName}</li>
                            <li><strong>Deadline:</strong> ${deadlineFormatted}</li>
                            <li><strong>Duration:</strong> ${duration} minutes</li>
                        </ul>
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = previewHtml;
            document.getElementById('previewSection').style.display = 'block';
            document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
        }

        // Form validation
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const deadline = new Date(document.getElementById('deadline').value);
            const now = new Date();
            
            if (deadline <= now) {
                e.preventDefault();
                alert('Deadline must be in the future.');
                return false;
            }
        });
    </script>
</body>
</html>