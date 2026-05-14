@extends('layouts.admin')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-4">
            <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Course: {{ $course->name }}</h3>
            <span class="badge bg-light text-primary">ID: {{ $course->id }}</span>
        </div>
        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
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

            <div class="row">
                <!-- Left Column - Course Preview -->
                <div class="col-lg-5">
                    <div class="sticky-top" style="top: 20px;">
                        <h4 class="mb-3 text-muted">Card Preview</h4>
                        <div class="preview-card" id="previewCard" style="--course-color: {{ $course->color ?? '#007bff' }}; --course-title-color: {{ $course->title_color ?? '#FFFFFF' }}; margin: 0;">
                            <div class="preview-image" id="previewImage" style="background-image: url('{{ $course->image_url }}');">
                                <div class="info-icon">
                                    <i class="fas fa-info"></i>
                                    <div class="info-tooltip" id="previewTooltip">
                                        {{ $course->description }}
                                    </div>
                                </div>
                                <div class="preview-overlay">
                                    <h3 class="preview-title" id="previewTitle">{{ $course->name }}</h3>
                                </div>
                            </div>
                            <div class="preview-info">
                                <div class="preview-stats-row">
                                    <span class="preview-topics-badge">
                                        <i class="fas fa-book-open me-1"></i>
                                        <span id="previewTopicsCount">{{ $course->topics_count ?? 0 }}</span> Topics
                                    </span>
                                    <div class="preview-enrolled-users">
                                        @foreach($course->enrolled_preview ?? [] as $previewUser)
                                            <div class="preview-profile-circle" 
                                                 @if($previewUser['profile_picture_url']) style="background-image: url('{{ $previewUser['profile_picture_url'] }}')" @endif>
                                                @if(!$previewUser['profile_picture_url']) {{ $previewUser['initial'] }} @endif
                                            </div>
                                        @endforeach
                                        @if(($course->enrolled_users_count ?? 0) > 3)
                                            <div class="preview-profile-circle more">+{{ $course->enrolled_users_count - 3 }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="preview-progress-container">
                                    <div class="preview-progress-bar">
                                        <div class="preview-progress-fill" id="previewProgressFill" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div class="preview-action-btn" id="previewActionBtn">
                                    <i class="fas fa-play"></i>
                                    <span id="previewActionText">
                                        @if($course->access_type === 'paid')
                                            Unlock for {{ $course->price }} EGP
                                        @elseif($course->access_type === 'preview')
                                            Start Preview
                                        @else
                                            Unlock via Survey
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Course Type Preview (Medical/Custom) -->
                        <div class="mt-4 p-3 bg-light rounded border">
                            <h5 class="text-muted mb-3"><i class="fas fa-layer-group me-2"></i>Type Preview</h5>
                            <div id="course-type-preview">
                                <!-- Dynamically populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Form Fields -->
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('admin.courses.update', $course->id) }}" enctype="multipart/form-data" id="course-form">
                        @csrf
                        @method('PUT')

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="name" class="form-label">Course Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $course->name) }}"
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
                                              placeholder="Enter course description">{{ old('description', $course->description) }}</textarea>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="academic_year" class="form-label">Academic Year / Grade</label>
                                        <select class="form-select" id="academic_year" name="academic_year" required>
                                            <option value="">Select Academic Year or Grade</option>
                                            @php
                                                $standardAcademicYears = ['YEAR 1 - SEMESTER 1', 'YEAR 1 - SEMESTER 2', 'YEAR 2 - SEMESTER 1', 'YEAR 2 - SEMESTER 2', 'YEAR 3 - SEMESTER 1', 'YEAR 3 - SEMESTER 2', 'YEAR 4 - SEMESTER 1', 'YEAR 4 - SEMESTER 2', 'YEAR 5 - SEMESTER 1', 'YEAR 5 - SEMESTER 2', 'YEAR 6 - SEMESTER 1', 'YEAR 6 - SEMESTER 2'];
                                                $standardTests = ['SAT', 'EST', 'ACT'];
                                                $selectedAcademicYear = old('academic_year', $course->academic_year);
                                            @endphp
                                            @if($selectedAcademicYear && !in_array($selectedAcademicYear, array_merge($standardAcademicYears, $standardTests), true))
                                                <option value="{{ $selectedAcademicYear }}" selected>{{ $selectedAcademicYear }}</option>
                                            @endif
                                            <optgroup label="Medical School Years">
                                                @foreach($standardAcademicYears as $year)
                                                    <option value="{{ $year }}" {{ $selectedAcademicYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Standardized Tests">
                                                @foreach($standardTests as $test)
                                                    <option value="{{ $test }}" {{ $selectedAcademicYear == $test ? 'selected' : '' }}>{{ $test }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="is_published" class="form-label">Status</label>
                                        <select class="form-select" id="is_published" name="is_published" required>
                                            <option value="1" {{ old('is_published', $course->is_published) == 1 ? 'selected' : '' }}>Published</option>
                                            <option value="0" {{ old('is_published', $course->is_published) == 0 ? 'selected' : '' }}>Draft (Hidden)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Visual Design</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Course Image</label>

                                    <ul class="nav nav-tabs mb-3" id="imageUploadTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-panel" type="button" role="tab">
                                                <i class="fas fa-upload me-1"></i>Upload New Image
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-panel" type="button" role="tab">
                                                <i class="fas fa-link me-1"></i>Image URL
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content" id="imageUploadContent">
                                        <div class="tab-pane fade show active" id="upload-panel" role="tabpanel">
                                            <div class="upload-area border-2 border-dashed border-primary rounded p-4 text-center position-relative"
                                                 id="uploadArea"
                                                 style="min-height: 120px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="file"
                                                       class="form-control d-none"
                                                       id="image_file"
                                                       name="image_file"
                                                       accept="image/*">
                                                <div id="uploadContent" class="{{ $course->image ? 'd-none' : '' }}">
                                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                                    <p class="mb-1">Click to upload or drag and drop</p>
                                                    <small class="text-muted">PNG, JPG, JPEG</small>
                                                </div>
                                                <div id="uploadPreview" class="{{ $course->image ? '' : 'd-none' }}">
                                                    <img id="previewImg" src="{{ $course->image_url }}" alt="Preview" class="img-fluid rounded" style="max-height: 100px;">
                                                    <p class="mt-2 mb-0" id="fileName">{{ $course->image ? 'Current Image' : '' }}</p>
                                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeImage">
                                                        <i class="fas fa-times"></i> Clear Selection
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="tab-pane fade" id="url-panel" role="tabpanel">
                                            <input type="url"
                                                   class="form-control"
                                                   id="image_url"
                                                   name="image_url"
                                                   value="{{ old('image_url', $course->image && filter_var($course->image, FILTER_VALIDATE_URL) ? $course->image : '') }}"
                                                   placeholder="https://example.com/image.jpg">
                                            <div class="form-text text-muted">
                                                Current image will be kept if both file and URL outputs are empty.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label d-flex align-items-center">
                                            Accent Color
                                            <div class="color-preview" id="colorPreview" style="background-color: {{ $course->color ?? '#007bff' }}"></div>
                                        </label>
                                        <div class="color-field-controls">
                                            <input type="color"
                                                   class="form-control form-control-color"
                                                   id="color"
                                                   name="color"
                                                   value="{{ old('color', $course->color ?? '#007bff') }}"
                                                   title="Choose course accent color">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm color-picker-trigger"
                                                    data-color-target="color"
                                                    data-default-label="Pick from screen">
                                                Pick
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label d-flex align-items-center">
                                            Title Color
                                            <div class="color-preview" id="titleColorPreview" style="background-color: {{ $course->title_color ?? '#FFFFFF' }}"></div>
                                        </label>
                                        <div class="color-field-controls">
                                            <input type="color"
                                                   class="form-control form-control-color"
                                                   id="title_color"
                                                   name="title_color"
                                                   value="{{ old('title_color', $course->title_color ?? '#FFFFFF') }}"
                                                   title="Choose course title color">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm color-picker-trigger"
                                                    data-color-target="title_color"
                                                    data-default-label="Pick from screen">
                                                Pick
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Access & Pricing</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="access_type" class="form-label">Access Policy</label>
                                    <select class="form-select" id="access_type" name="access_type" required>
                                        <option value="{{ \App\Models\Course::ACCESS_TYPE_FREE }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_FREE ? 'selected' : '' }}>
                                            Free access
                                        </option>
                                        <option value="{{ \App\Models\Course::ACCESS_TYPE_PREVIEW }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_PREVIEW ? 'selected' : '' }}>
                                            Preview access
                                        </option>
                                        <option value="{{ \App\Models\Course::ACCESS_TYPE_PAID }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_PAID ? 'selected' : '' }}>
                                            Paid access (Locked until purchase)
                                        </option>
                                    </select>
                                    <div class="form-text text-muted" id="accessPolicyHelp"></div>
                                </div>

                                <div class="mb-4">
                                    <label for="price" class="form-label" id="priceLabel">Price (EGP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">EGP</span>
                                        <input type="number"
                                               class="form-control"
                                               id="price"
                                               name="price"
                                               value="{{ old('price', $course->price ?? 0) }}"
                                               min="0"
                                               step="0.01">
                                    </div>
                                    <div class="form-text text-muted" id="priceHelp"></div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Course Logic Type</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <div class="type-option p-3 border rounded {{ old('type', $course->type) === 'medical' ? 'selected border-primary bg-light' : '' }}" onclick="selectType('medical')">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="type-medical" value="medical" {{ old('type', $course->type) === 'medical' ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="type-medical">Medical</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Quiz, Cases, Practical pills.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="type-option p-3 border rounded {{ old('type', $course->type) === 'custom' ? 'selected border-primary bg-light' : '' }}" onclick="selectType('custom')">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="type-custom" value="custom" {{ old('type', $course->type) === 'custom' ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="type-custom">Custom</label>
                                                </div>
                                                <small class="text-muted d-block mt-1">Dynamic categories.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Courses
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.preview-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    width: 100%;
    position: relative;
    border: 2px solid var(--course-color, #3b82f6);
    border-left: 8px solid var(--course-color, #3b82f6);
}

.preview-image {
    position: relative;
    height: 480px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-image: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
}

.info-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: help;
    z-index: 3;
}

.info-tooltip {
    position: absolute;
    top: 40px;
    right: 0;
    width: 240px;
    background: white;
    border-radius: 8px;
    padding: 12px;
    font-size: 0.85rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 10;
}

.info-icon:hover .info-tooltip {
    opacity: 1;
    visibility: visible;
}

.preview-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem;
    background: linear-gradient(to top, rgba(0,0,0,0.05), transparent);
}

.preview-title {
    font-size: 2.4rem;
    font-weight: 800;
    color: var(--course-title-color, #FFFFFF);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    margin: 0;
    line-height: 1.2;
}

.preview-info {
    padding: 1.5rem;
    background: white;
}

.preview-stats-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.preview-topics-badge {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

.preview-enrolled-users {
    display: flex;
    align-items: center;
}

.preview-profile-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #e1e7ff;
    border: 2px solid white;
    margin-left: -10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    background-size: cover;
    background-position: center;
}

.preview-profile-circle:first-child { margin-left: 0; }
.preview-profile-circle.more { background: #f0f0f0; color: #666; }

.preview-progress-bar {
    height: 6px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.preview-progress-fill {
    height: 100%;
    background: #00d28d;
}

.preview-action-btn {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
}

.color-preview {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    margin-left: 10px;
    border: 1px solid #ddd;
}

.color-field-controls {
    display: flex;
    gap: 10px;
}

.upload-area {
    transition: all 0.2s ease;
}

.upload-area:hover {
    background: rgba(13, 110, 253, 0.05);
}

.type-option {
    cursor: pointer;
    transition: all 0.2s ease;
}

.type-option:hover {
    border-color: #0d6efd;
}

.type-option.selected {
    background: #f8fbff;
    border-color: #0d6efd !important;
}

/* Course Type Preview specific */
.case-filter {
    padding: 5px 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
</style>

@endsection

@section('scripts')
<script>
// Live preview logic
const nameInput = document.getElementById('name');
const descriptionInput = document.getElementById('description');
const colorInput = document.getElementById('color');
const titleColorInput = document.getElementById('title_color');
const accessTypeInput = document.getElementById('access_type');
const priceInput = document.getElementById('price');
const imageFileInput = document.getElementById('image_file');
const imageUrlInput = document.getElementById('image_url');
const uploadArea = document.getElementById('uploadArea');
const uploadContent = document.getElementById('uploadContent');
const uploadPreview = document.getElementById('uploadPreview');
const previewImg = document.getElementById('previewImg');
const fileName = document.getElementById('fileName');
const removeImageBtn = document.getElementById('removeImage');

const previewTitle = document.getElementById('previewTitle');
const previewTooltip = document.getElementById('previewTooltip');
const previewCard = document.getElementById('previewCard');
const previewImage = document.getElementById('previewImage');
const previewActionText = document.getElementById('previewActionText');

// Colors
const colorPreview = document.getElementById('colorPreview');
const titleColorPreview = document.getElementById('titleColorPreview');

function setImageInputFile(file) {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    imageFileInput.files = transfer.files;
}

function showUploadPreview(src, label) {
    previewImg.src = src;
    fileName.textContent = label;
    uploadContent.classList.add('d-none');
    uploadPreview.classList.remove('d-none');
}

function clearUploadSelection() {
    imageFileInput.value = '';
    uploadContent.classList.remove('d-none');
    uploadPreview.classList.add('d-none');
}

function handleSelectedImageFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        imageFileInput.value = '';
        return;
    }

    imageUrlInput.value = '';

    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.style.backgroundImage = `url('${e.target.result}')`;
        showUploadPreview(e.target.result, file.name);
    };
    reader.readAsDataURL(file);
}

function updatePreview() {
    // Basic text
    previewTitle.textContent = nameInput.value || 'Course Title';
    previewTooltip.textContent = descriptionInput.value || 'Course description...';
    
    // Colors
    const courseColor = colorInput.value;
    const titleColor = titleColorInput.value;
    previewCard.style.setProperty('--course-color', courseColor);
    previewCard.style.setProperty('--course-title-color', titleColor);
    colorPreview.style.backgroundColor = courseColor;
    titleColorPreview.style.backgroundColor = titleColor;
    
    // Access & Price UI
    const accessType = accessTypeInput.value;
    const price = priceInput.value;
    const accessPolicyHelp = document.getElementById('accessPolicyHelp');
    const priceHelp = document.getElementById('priceHelp');
    const priceLabel = document.getElementById('priceLabel');
    
    if (accessType === 'free') {
        priceInput.value = 0;
        priceInput.readOnly = true;
        priceLabel.textContent = 'Price (EGP)';
        accessPolicyHelp.textContent = 'Full access after survey.';
        priceHelp.textContent = 'Free courses must be 0.';
        previewActionText.textContent = 'Unlock via Survey';
    } else if (accessType === 'preview') {
        priceInput.readOnly = false;
        priceLabel.textContent = 'Upgrade Price (EGP)';
        accessPolicyHelp.textContent = 'Limited topics initially.';
        priceHelp.textContent = 'Set a price for full upgrade.';
        previewActionText.textContent = price > 0 ? `Start Preview, Upgrade ${price} EGP` : 'Start Preview';
    } else {
        priceInput.readOnly = false;
        priceLabel.textContent = 'Price (EGP)';
        accessPolicyHelp.textContent = 'Locked until purchase.';
        priceHelp.textContent = 'Paid courses must be > 0.';
        previewActionText.textContent = price > 0 ? `Unlock for ${price} EGP` : 'Set Price';
    }
    
    // Image Preview
    if (imageFileInput.files && imageFileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.style.backgroundImage = `url('${e.target.result}')`;
            showUploadPreview(e.target.result, imageFileInput.files[0].name);
        };
        reader.readAsDataURL(imageFileInput.files[0]);
    } else if (imageUrlInput.value) {
        previewImage.style.backgroundImage = `url('${imageUrlInput.value}')`;
        clearUploadSelection();
    }
}

// Initial update
updatePreview();

// Event listeners
[nameInput, descriptionInput, colorInput, titleColorInput, accessTypeInput, priceInput, imageUrlInput].forEach(input => {
    input.addEventListener('input', updatePreview);
});

imageFileInput.addEventListener('change', () => {
    if (imageFileInput.files && imageFileInput.files[0]) {
        handleSelectedImageFile(imageFileInput.files[0]);
    } else {
        updatePreview();
    }
});

// Image management
uploadArea.addEventListener('click', () => imageFileInput.click());
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#0056b3';
    uploadArea.style.backgroundColor = '#f8f9ff';
});
uploadArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#007bff';
    uploadArea.style.backgroundColor = 'transparent';
});
uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = '#007bff';
    uploadArea.style.backgroundColor = 'transparent';

    const file = e.dataTransfer.files[0];
    if (file) {
        setImageInputFile(file);
        handleSelectedImageFile(file);
    }
});
removeImageBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    clearUploadSelection();
    updatePreview();
});

imageUrlInput.addEventListener('input', () => {
    if (imageUrlInput.value.trim()) {
        clearUploadSelection();
        previewImage.style.backgroundImage = `url('${imageUrlInput.value.trim()}')`;
    }
});

document.getElementById('upload-tab').addEventListener('click', () => {
    imageUrlInput.value = '';
});

document.getElementById('url-tab').addEventListener('click', () => {
    clearUploadSelection();
});

// Type Selection logic
function selectType(type) {
    document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected', 'border-primary', 'bg-light'));
    const selected = document.getElementById(`type-${type}`).closest('.type-option');
    selected.classList.add('selected', 'border-primary', 'bg-light');
    document.getElementById(`type-${type}`).checked = true;
    generateTypePreview(type);
}

function generateTypePreview(type) {
    const container = document.getElementById('course-type-preview');
    if (type === 'medical') {
        container.innerHTML = `
            <div class="case-filter"><i class="fas fa-th-large"></i> All</div>
            <div class="case-filter text-warning"><i class="fas fa-file-alt"></i> Cases</div>
            <div class="case-filter text-info"><i class="fas fa-flask"></i> Practical</div>
            <div class="case-filter text-success"><i class="fas fa-question-circle"></i> Quiz</div>
        `;
    } else {
        container.innerHTML = `
            <div class="case-filter"><i class="fas fa-th-large"></i> All</div>
            <div class="case-filter text-danger"><i class="fas fa-bookmark"></i> Category A</div>
            <div class="case-filter text-primary"><i class="fas fa-bookmark"></i> Category B</div>
        `;
    }
}

// Eye dropper support
document.querySelectorAll('.color-picker-trigger').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!window.EyeDropper) return alert('EyeDropper not supported in this browser.');
        const dropper = new EyeDropper();
        try {
            const result = await dropper.open();
            const input = document.getElementById(btn.dataset.colorTarget);
            input.value = result.sRGBHex;
            updatePreview();
        } catch (e) {}
    });
});

// Initial type preview
generateTypePreview('{{ $course->type ?? "medical" }}');
</script>
@endsection
