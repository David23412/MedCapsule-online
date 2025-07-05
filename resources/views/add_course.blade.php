<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - MedCapsule</title>
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
        .preview-card {
            width: 100%;
            height: 200px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .preview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
        }
        .preview-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }
        .preview-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-left: 10px;
            border: 2px solid #fff;
            transition: all 0.3s ease;
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
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="mb-0">Add New Course</h3>
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

                        <div class="preview-card mb-4" id="previewCard">
                            <div class="preview-image" id="previewImage"></div>
                            <div class="preview-overlay">
                                <h3 class="preview-title" id="previewTitle" style="color: #FFFFFF;">Course Preview</h3>
                            </div>
                        </div>

                        <form action="{{ route('add-course') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label for="name" class="form-label">Course Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required 
                                       placeholder="Enter course name">
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Course Description</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          required 
                                          placeholder="Enter course description">{{ old('description') }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label">Image URL</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="image" 
                                       name="image" 
                                       value="{{ old('image') }}" 
                                       placeholder="https://example.com/image.jpg">
                                <div class="form-text text-muted">
                                    Provide a URL for the course cover image
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label d-flex align-items-center">
                                    Course Color
                                    <div class="color-preview" id="colorPreview"></div>
                                </label>
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="color" 
                                       name="color" 
                                       value="{{ old('color', '#007bff') }}"
                                       title="Choose course accent color">
                                <div class="form-text text-muted">
                                    This color will be used as the accent color for the course card
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label d-flex align-items-center">
                                    Title Color
                                    <div class="color-preview" id="titleColorPreview"></div>
                                </label>
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="title_color" 
                                       name="title_color" 
                                       value="{{ old('title_color', '#FFFFFF') }}"
                                       title="Choose course title color">
                                <div class="form-text text-muted">
                                    This color will be used for the course title text
                                </div>
                            </div>

                            <!-- Course Pricing -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid" value="1" {{ old('is_paid') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_paid">Paid Course</label>
                                </div>
                                <div class="form-text text-muted mb-3">
                                    Enable if this is a paid course requiring payment before enrollment
                                </div>
                                
                                <div id="pricing_options" class="border p-3 rounded mt-2" style="{{ old('is_paid') ? '' : 'display: none;' }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="price" class="form-label">Price</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="price" 
                                                   name="price" 
                                                   value="{{ old('price', '0.00') }}" 
                                                   min="0" 
                                                   step="0.01">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="currency" class="form-label">Currency</label>
                                            <select class="form-select" id="currency" name="currency">
                                                <option value="EGP" {{ old('currency') == 'EGP' ? 'selected' : '' }}>Egyptian Pound (EGP)</option>
                                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                                <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>British Pound (GBP)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Course
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
        // Live preview functionality
        const nameInput = document.getElementById('name');
        const imageInput = document.getElementById('image');
        const colorInput = document.getElementById('color');
        const titleColorInput = document.getElementById('title_color');
        const previewCard = document.getElementById('previewCard');
        const previewImage = document.getElementById('previewImage');
        const previewTitle = document.getElementById('previewTitle');
        const colorPreview = document.getElementById('colorPreview');
        const titleColorPreview = document.getElementById('titleColorPreview');
        const isPaidCheckbox = document.getElementById('is_paid');
        const pricingOptions = document.getElementById('pricing_options');

        function updatePreview() {
            // Update title text
            previewTitle.textContent = nameInput.value || 'Course Preview';
            
            // Update image if provided
            if (imageInput.value) {
                previewImage.style.backgroundImage = `url('${imageInput.value}')`;
                previewImage.style.backgroundSize = 'cover';
                previewImage.style.backgroundPosition = 'center';
            }
            
            // Get selected colors
            const courseColor = colorInput.value;
            const titleColor = titleColorInput.value;
            
            // Update course accent color
            previewCard.style.borderLeft = `8px solid ${courseColor}`;
            colorPreview.style.backgroundColor = courseColor;
            
            // Update title color independently
            previewTitle.style.color = titleColor;
            titleColorPreview.style.backgroundColor = titleColor;
        }

        function togglePricing() {
            if (isPaidCheckbox.checked) {
                pricingOptions.style.display = 'block';
            } else {
                pricingOptions.style.display = 'none';
            }
        }

        // Add event listeners
        [nameInput, imageInput, colorInput, titleColorInput].forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        
        isPaidCheckbox.addEventListener('change', togglePricing);

        // Initial setup
        updatePreview();
        togglePricing();
    </script>
</body>
</html> 