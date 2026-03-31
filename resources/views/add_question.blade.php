<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            zoom: 0.8; /* 80% zoom for better overview */
        }
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #007bff, #00bcd4);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #00bcd4);
            border: none;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #008ba3);
            transform: translateY(-1px);
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            margin-top: 10px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .image-upload-section {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 10px;
        }
        
        .image-upload-section:hover {
            border-color: #007bff;
            background: #f0f8ff;
        }
        
        .upload-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- LIGHTNING-FAST FOUC ELIMINATOR -->
    @include('components.lightning-preloader')

    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header text-center">
                        <h3 class="mb-0">Add New Question</h3>
                    </div>
                    <div class="card-body p-4">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ secure_url('add-question') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-4">
                                <label class="form-label">Topic</label>
                                <select name="topic_id" id="topic_id" class="form-select" required>
                                    <option value="" disabled selected>Select a topic</option>
{{-- 💀 N+1 FIX NEEDED: Eager load 'name' in controller --}}
                                    @foreach($topics as $topic)
                                        <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>{{ $topic->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Question Text</label>
                                <textarea name="question" class="form-control" rows="3" required>{{ old('question') }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Question Type</label>
                                <select name="question_type" id="question_type" class="form-select" required>
                                    <option value="multiple_choice" selected>Multiple Choice</option>
                                    <option value="written">Written Answer</option>
                                </select>
                                <div class="form-text text-muted">
                                    Select "Multiple Choice" for traditional MCQs or "Written Answer" for questions where students provide a text response
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Explanation (Optional)</label>
                                <textarea name="explanation" class="form-control" rows="3" placeholder="Add an explanation that will help students understand this question better">{{ old('explanation') }}</textarea>
                                <div class="form-text text-muted">
                                    This explanation will be shown to students when reviewing their mistakes
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Concept (Optional)</label>
                                <textarea
                                    name="concept_names"
                                    id="concept_names"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Enter the main concept. Example: Partial pressure gradient"
                                >{{ old('concept_names') }}</textarea>
                                <div class="form-text text-muted">
                                    Use one clear concept name. The system derives any internal slug automatically.
                                </div>
                                <div class="mt-3">
                                    <div class="small text-muted mb-2">Existing concepts in this topic</div>
                                    <div id="concept_suggestions" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Question Image (Optional)</label>
                                <div class="image-upload-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">
                                                <i class="fas fa-upload me-1"></i>Upload Image
                                            </label>
                                            <input type="file" 
                                                   class="form-control" 
                                                   name="question_image" 
                                                   id="question_image" 
                                                   accept="image/*">
                                            <div class="upload-info">
                                                Supported: JPG, PNG, GIF (max 2MB)
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">
                                                <i class="fas fa-link me-1"></i>Or Enter Image URL
                                            </label>
                                            <input type="url" 
                                                   class="form-control" 
                                                   name="image_url" 
                                                   id="image_url" 
                                                   placeholder="https://example.com/image.jpg">
                                            <div class="upload-info">
                                                Or provide an external image URL
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <img id="preview" class="preview-image">
                                </div>
                            </div>

                            <!-- Multiple Choice Options -->
                            <div id="multiple_choice_section">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option A</label>
                                        <input type="text" name="option_a" class="form-control mc-required" value="{{ old('option_a') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option B</label>
                                        <input type="text" name="option_b" class="form-control mc-required" value="{{ old('option_b') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option C</label>
                                        <input type="text" name="option_c" class="form-control mc-required" value="{{ old('option_c') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option D</label>
                                        <input type="text" name="option_d" class="form-control mc-required" value="{{ old('option_d') }}">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Correct Answer</label>
                                    <select name="correct_answer" id="mc_correct_answer" class="form-select mc-required">
                                        <option value="A" {{ old('correct_answer') === 'A' ? 'selected' : '' }}>A</option>
                                        <option value="B" {{ old('correct_answer') === 'B' ? 'selected' : '' }}>B</option>
                                        <option value="C" {{ old('correct_answer') === 'C' ? 'selected' : '' }}>C</option>
                                        <option value="D" {{ old('correct_answer') === 'D' ? 'selected' : '' }}>D</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Written Answer Section -->
                            <div id="written_section" style="display:none;">
                                <div class="mb-4">
                                    <label class="form-label">Correct Answer</label>
                                    <textarea name="written_correct_answer" id="written_correct_answer" class="form-control" rows="3" placeholder="Enter the expected correct answer">{{ old('written_correct_answer') }}</textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Alternative Acceptable Answers (Optional)</label>
                                    <textarea name="alternative_answers" class="form-control" rows="3" placeholder="Enter alternative acceptable answers, one per line">{{ old('alternative_answers') }}</textarea>
                                    <div class="form-text text-muted">
                                        Enter variations of acceptable answers, one per line. These will be considered correct along with the main answer.
                                    </div>
                                </div>
                            </div>

                            <!-- Source Field -->
                            <div class="mb-4">
                                <label class="form-label">Source</label>
                                <select name="source" class="form-select" required>
                                    <option value="" disabled selected>Select a source</option>
                                    <option value="Assuit" {{ old('source') === 'Assuit' ? 'selected' : '' }}>Assuit</option>
                                    <option value="Cairo" {{ old('source') === 'Cairo' ? 'selected' : '' }}>Cairo</option>
                                    <option value="Alexandria" {{ old('source') === 'Alexandria' ? 'selected' : '' }}>Alexandria</option>
                                    <option value="Other" {{ old('source') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <div class="form-text text-muted">
                                    Select the source of this question
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('admin.delete.index') }}" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt me-1"></i>Delete Management
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Question
                                </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const conceptSuggestionsByTopic = @json($conceptSuggestionsByTopic ?? []);
        // Image preview functionality for both file upload and URL input
        const imageUrlInput = document.getElementById('image_url');
        const imageFileInput = document.getElementById('question_image');
        const previewImage = document.getElementById('preview');
        const topicSelect = document.getElementById('topic_id');
        const conceptsInput = document.getElementById('concept_names');
        const conceptSuggestions = document.getElementById('concept_suggestions');

        // Handle URL input
        imageUrlInput.addEventListener('input', function() {
            if (this.value) {
                previewImage.src = this.value;
                previewImage.style.display = 'block';
                
                // Handle image load error
                previewImage.onerror = function() {
                    previewImage.style.display = 'none';
                };
            } else {
                previewImage.style.display = 'none';
            }
        });

        // Handle file upload
        imageFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Clear URL input when file is selected
                imageUrlInput.value = '';
                
                // Create a preview URL for the uploaded file
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewImage.style.display = 'none';
            }
        });

        // Clear file input when URL is entered
        imageUrlInput.addEventListener('input', function() {
            if (this.value) {
                imageFileInput.value = '';
            }
        });

        // Toggle question type sections
        const questionTypeSelect = document.getElementById('question_type');
        const multipleChoiceSection = document.getElementById('multiple_choice_section');
        const writtenSection = document.getElementById('written_section');
        const mcRequiredInputs = document.querySelectorAll('.mc-required');
        const writtenCorrectAnswer = document.getElementById('written_correct_answer');

        questionTypeSelect.addEventListener('change', function() {
            if (this.value === 'multiple_choice') {
                multipleChoiceSection.style.display = 'block';
                writtenSection.style.display = 'none';
                
                // Make multiple choice fields required
                mcRequiredInputs.forEach(input => {
                    input.setAttribute('required', 'required');
                });
                
                // Remove required from written fields
                writtenCorrectAnswer.removeAttribute('required');
            } else {
                multipleChoiceSection.style.display = 'none';
                writtenSection.style.display = 'block';
                
                // Make written fields required
                writtenCorrectAnswer.setAttribute('required', 'required');
                
                // Remove required from multiple choice fields
                mcRequiredInputs.forEach(input => {
                    input.removeAttribute('required');
                });
            }
        });

        const appendConceptName = (name) => {
            const currentValue = conceptsInput.value.trim();
            const existingNames = currentValue
                ? currentValue.split(/[\r\n,;]+/).map(value => value.trim().toLowerCase()).filter(Boolean)
                : [];

            if (existingNames.includes(name.toLowerCase())) {
                return;
            }

            conceptsInput.value = currentValue ? `${currentValue}\n${name}` : name;
        };

        const renderConceptSuggestions = () => {
            const topicId = topicSelect.value;
            const names = conceptSuggestionsByTopic[String(topicId)] || [];

            conceptSuggestions.innerHTML = '';

            if (!topicId || names.length === 0) {
                conceptSuggestions.innerHTML = '<span class="text-muted small">No saved concepts yet for this topic.</span>';
                return;
            }

            names.forEach((name) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-primary';
                button.textContent = name;
                button.addEventListener('click', () => appendConceptName(name));
                conceptSuggestions.appendChild(button);
            });
        };

        topicSelect.addEventListener('change', renderConceptSuggestions);
        renderConceptSuggestions();
    </script>
</body>
</html>
