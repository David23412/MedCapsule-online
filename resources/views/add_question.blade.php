<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
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
        }
    </style>
</head>
<body class="bg-light">
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

                        <form method="POST" action="{{ route('add-question') }}">
                            @csrf

                            <div class="mb-4">
                                <label class="form-label">Topic</label>
                                <select name="topic_id" class="form-select" required>
                                    <option value="" disabled selected>Select a topic</option>
                                    @foreach($topics as $topic)
                                        <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Question Text</label>
                                <textarea name="question" class="form-control" rows="3" required></textarea>
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
                                <textarea name="explanation" class="form-control" rows="3" placeholder="Add an explanation that will help students understand this question better"></textarea>
                                <div class="form-text text-muted">
                                    This explanation will be shown to students when reviewing their mistakes
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Question Image (Optional)</label>
                                <input type="url" 
                                       class="form-control" 
                                       name="image_url" 
                                       id="image_url" 
                                       placeholder="https://example.com/image.jpg">
                                <div class="form-text text-muted">
                                    Add an image URL if the question includes a visual element
                                </div>
                                <img id="preview" class="preview-image">
                            </div>

                            <!-- Multiple Choice Options -->
                            <div id="multiple_choice_section">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option A</label>
                                        <input type="text" name="option_a" class="form-control mc-required">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option B</label>
                                        <input type="text" name="option_b" class="form-control mc-required">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option C</label>
                                        <input type="text" name="option_c" class="form-control mc-required">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Option D</label>
                                        <input type="text" name="option_d" class="form-control mc-required">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Correct Answer</label>
                                    <select name="correct_answer" id="mc_correct_answer" class="form-select mc-required">
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Written Answer Section -->
                            <div id="written_section" style="display:none;">
                                <div class="mb-4">
                                    <label class="form-label">Correct Answer</label>
                                    <textarea name="written_correct_answer" id="written_correct_answer" class="form-control" rows="3" placeholder="Enter the expected correct answer"></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Alternative Acceptable Answers (Optional)</label>
                                    <textarea name="alternative_answers" class="form-control" rows="3" placeholder="Enter alternative acceptable answers, one per line"></textarea>
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
                                    <option value="Assuit">Assuit</option>
                                    <option value="Cairo">Cairo</option>
                                    <option value="Alexandria">Alexandria</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="form-text text-muted">
                                    Select the source of this question
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Question
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        const imageUrlInput = document.getElementById('image_url');
        const previewImage = document.getElementById('preview');

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
    </script>
</body>
</html>