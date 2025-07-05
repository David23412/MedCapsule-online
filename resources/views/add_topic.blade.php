<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Topic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .type-option {
            flex: 1;
            position: relative;
        }

        .type-option input[type="radio"] {
            display: none;
        }

        .type-option label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .type-option input[type="radio"]:checked + label {
            border-color: #0d6efd;
            background: #e3f2fd;
        }

        .type-option.quiz input[type="radio"]:checked + label {
            border-color: #495057;
            background: #e9ecef;
        }

        .type-option.practical input[type="radio"]:checked + label {
            border-color: #856404;
            background: #fff3cd;
        }

        .type-option i {
            font-size: 1.1rem;
        }

        .type-option.quiz i { color: #495057; }
        .type-option.cases i { color: #0d6efd; }
        .type-option.practical i { color: #856404; }

        .type-option input[type="radio"]:checked + label i {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h3>Add a New Topic</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- ✅ FIXED: Route updated to 'add-topic' -->
                <form method="POST" action="{{ route('add-topic') }}">
                    @csrf

                    <!-- Course Selection -->
                    <div class="mb-3">
                        <label class="form-label">Select Course:</label>
                        <select name="course_id" class="form-select" required>
                            <option value="" disabled selected>Select a course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Topic Type Selection -->
                    <div class="mb-4">
                        <label class="form-label d-block mb-2">Topic Type:</label>
                        <div class="type-selector">
                            <div class="type-option quiz">
                                <input type="radio" name="case_type" id="type-quiz" value="quiz" checked>
                                <label for="type-quiz">
                                    <i class="fas fa-question-circle"></i>
                                    Quiz
                                </label>
                            </div>
                            <div class="type-option cases">
                                <input type="radio" name="case_type" id="type-cases" value="cases">
                                <label for="type-cases">
                                    <i class="fas fa-file-alt"></i>
                                    Case
                                </label>
                            </div>
                            <div class="type-option practical">
                                <input type="radio" name="case_type" id="type-practical" value="practical">
                                <label for="type-practical">
                                    <i class="fas fa-flask"></i>
                                    Practical
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Topic Name Input -->
                    <div class="mb-3">
                        <label class="form-label">Topic Name:</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <!-- Topic Description Input -->
                    <div class="mb-3">
                        <label class="form-label">Topic Description:</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Add Topic</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
