@extends('layouts.admin')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center py-4">
            <h3 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Course</h3>
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

            <div class="row">
                <!-- Left Column - Course Preview -->
                <div class="col-lg-5">
                    <div class="preview-card" id="previewCard" style="--course-color: #007bff; --course-title-color: #FFFFFF; margin: 0;">
                        <div class="preview-image" id="previewImage">
                            <div class="info-icon">
                                <i class="fas fa-info"></i>
                                <div class="info-tooltip" id="previewTooltip">
                                    Course description will appear here
                                </div>
                            </div>
                            <div class="preview-overlay">
                                <h3 class="preview-title" id="previewTitle">Course Preview</h3>
                            </div>
                        </div>
                        <div class="preview-info">
                            <div class="preview-stats-row">
                                <span class="preview-topics-badge">
                                    <i class="fas fa-book-open me-1"></i>
                                    <span id="previewTopicsCount">0</span> Topics
                                </span>
                                <div class="preview-enrolled-users">
                                    <div class="preview-profile-circle">A</div>
                                    <div class="preview-profile-circle">B</div>
                                    <div class="preview-profile-circle">C</div>
                                    <div class="preview-profile-circle more">+5</div>
                                </div>
                            </div>
                            <div class="preview-progress-container">
                                <div class="preview-progress-bar">
                                    <div class="preview-progress-fill" id="previewProgressFill"></div>
                                </div>
                            </div>
                            <div class="preview-action-btn" id="previewActionBtn">
                                <i class="fas fa-play"></i>
                                <span id="previewActionText">Start Learning (Free)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Form Fields -->
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('add-course') }}" enctype="multipart/form-data">
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
                            <label for="academic_year" class="form-label">Academic Year / Grade</label>
                            <select class="form-select" id="academic_year" name="academic_year" required>
                                <option value="">Select Academic Year or Grade</option>
                                <optgroup label="Medical School Years">
                                    <option value="YEAR 1 - SEMESTER 1" {{ old('academic_year') == 'YEAR 1 - SEMESTER 1' ? 'selected' : '' }}>YEAR 1 - SEMESTER 1</option>
                                    <option value="YEAR 1 - SEMESTER 2" {{ old('academic_year') == 'YEAR 1 - SEMESTER 2' ? 'selected' : '' }}>YEAR 1 - SEMESTER 2</option>
                                    <option value="YEAR 2 - SEMESTER 1" {{ old('academic_year') == 'YEAR 2 - SEMESTER 1' ? 'selected' : '' }}>YEAR 2 - SEMESTER 1</option>
                                    <option value="YEAR 2 - SEMESTER 2" {{ old('academic_year') == 'YEAR 2 - SEMESTER 2' ? 'selected' : '' }}>YEAR 2 - SEMESTER 2</option>
                                    <option value="YEAR 3 - SEMESTER 1" {{ old('academic_year') == 'YEAR 3 - SEMESTER 1' ? 'selected' : '' }}>YEAR 3 - SEMESTER 1</option>
                                    <option value="YEAR 3 - SEMESTER 2" {{ old('academic_year') == 'YEAR 3 - SEMESTER 2' ? 'selected' : '' }}>YEAR 3 - SEMESTER 2</option>
                                    <option value="YEAR 4 - SEMESTER 1" {{ old('academic_year') == 'YEAR 4 - SEMESTER 1' ? 'selected' : '' }}>YEAR 4 - SEMESTER 1</option>
                                    <option value="YEAR 4 - SEMESTER 2" {{ old('academic_year') == 'YEAR 4 - SEMESTER 2' ? 'selected' : '' }}>YEAR 4 - SEMESTER 2</option>
                                    <option value="YEAR 5 - SEMESTER 1" {{ old('academic_year') == 'YEAR 5 - SEMESTER 1' ? 'selected' : '' }}>YEAR 5 - SEMESTER 1</option>
                                    <option value="YEAR 5 - SEMESTER 2" {{ old('academic_year') == 'YEAR 5 - SEMESTER 2' ? 'selected' : '' }}>YEAR 5 - SEMESTER 2</option>
                                    <option value="YEAR 6 - SEMESTER 1" {{ old('academic_year') == 'YEAR 6 - SEMESTER 1' ? 'selected' : '' }}>YEAR 6 - SEMESTER 1</option>
                                    <option value="YEAR 6 - SEMESTER 2" {{ old('academic_year') == 'YEAR 6 - SEMESTER 2' ? 'selected' : '' }}>YEAR 6 - SEMESTER 2</option>
                                </optgroup>
                                <optgroup label="Standardized Tests">
                                    <option value="SAT" {{ old('academic_year') == 'SAT' ? 'selected' : '' }}>SAT</option>
                                    <option value="EST" {{ old('academic_year') == 'EST' ? 'selected' : '' }}>EST</option>
                                    <option value="ACT" {{ old('academic_year') == 'ACT' ? 'selected' : '' }}>ACT</option>
                                </optgroup>
                            </select>
                            <div class="form-text text-muted">
                                Select the academic year/semester or standardized test for this course
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Course Image</label>

                            <!-- Image Upload Tabs -->
                            <ul class="nav nav-tabs mb-3" id="imageUploadTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-panel" type="button" role="tab">
                                        <i class="fas fa-upload me-1"></i>Upload Image
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-panel" type="button" role="tab">
                                        <i class="fas fa-link me-1"></i>Image URL
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="imageUploadContent">
                                <!-- Upload Panel -->
                                <div class="tab-pane fade show active" id="upload-panel" role="tabpanel">
                                    <div class="upload-area border-2 border-dashed border-primary rounded p-4 text-center position-relative"
                                         id="uploadArea"
                                         style="min-height: 120px; cursor: pointer; transition: all 0.3s ease;">
                                        <input type="file"
                                               class="form-control d-none"
                                               id="image_file"
                                               name="image_file"
                                               accept="image/*">
                                        <div id="uploadContent">
                                            <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                            <p class="mb-1">Click to upload or drag and drop</p>
                                            <small class="text-muted">PNG, JPG, JPEG</small>
                                        </div>
                                        <div id="uploadPreview" class="d-none">
                                            <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 100px;">
                                            <p class="mt-2 mb-0" id="fileName"></p>
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeImage">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- URL Panel -->
                                <div class="tab-pane fade" id="url-panel" role="tabpanel">
                                    <input type="url"
                                           class="form-control"
                                           id="image_url"
                                           name="image_url"
                                           value="{{ old('image_url') }}"
                                           placeholder="https://example.com/image.jpg">
                                    <div class="form-text text-muted">
                                        Provide a URL for the course cover image
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-flex align-items-center">
                                Course Color
                                <div class="color-preview" id="colorPreview"></div>
                            </label>
                            <div class="color-field-controls">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="color"
                                       name="color"
                                       value="{{ old('color', '#007bff') }}"
                                       title="Choose course accent color">
                                <button type="button"
                                        class="btn btn-outline-secondary color-picker-trigger"
                                        data-color-target="color"
                                        data-default-label="Pick from screen">
                                    Pick from screen
                                </button>
                            </div>
                            <div class="form-text text-muted">
                                This color will be used as the accent color for the course card. The screen picker works in browsers that support it.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-flex align-items-center">
                                Title Color
                                <div class="color-preview" id="titleColorPreview"></div>
                            </label>
                            <div class="color-field-controls">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="title_color"
                                       name="title_color"
                                       value="{{ old('title_color', '#FFFFFF') }}"
                                       title="Choose course title color">
                                <button type="button"
                                        class="btn btn-outline-secondary color-picker-trigger"
                                        data-color-target="title_color"
                                        data-default-label="Pick from screen">
                                    Pick from screen
                                </button>
                            </div>
                            <div class="form-text text-muted">
                                This color will be used for the course title text. The screen picker works in browsers that support it.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="access_type" class="form-label">Course Access Policy</label>
                            <select class="form-select" id="access_type" name="access_type" required>
                                <option value="{{ \App\Models\Course::ACCESS_TYPE_FREE }}" {{ old('access_type', \App\Models\Course::ACCESS_TYPE_FREE) === \App\Models\Course::ACCESS_TYPE_FREE ? 'selected' : '' }}>
                                    Free access
                                </option>
                                <option value="{{ \App\Models\Course::ACCESS_TYPE_PREVIEW }}" {{ old('access_type') === \App\Models\Course::ACCESS_TYPE_PREVIEW ? 'selected' : '' }}>
                                    Preview access
                                </option>
                                <option value="{{ \App\Models\Course::ACCESS_TYPE_PAID }}" {{ old('access_type') === \App\Models\Course::ACCESS_TYPE_PAID ? 'selected' : '' }}>
                                    Paid access
                                </option>
                            </select>
                            <div class="form-text text-muted" id="accessPolicyHelp">
                                Free courses unlock full access after the survey. Preview courses unlock limited topics. Paid courses require payment for access.
                            </div>
                        </div>

                        <!-- Course Pricing -->
                        <div class="mb-4">
                            <label class="form-label">Course Pricing</label>
                            <div class="form-text text-muted mb-3">
                                Price is commerce metadata. Access is controlled by the policy above.
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label for="price" class="form-label" id="priceLabel">Price (EGP)</label>
                                    <input type="number"
                                           class="form-control"
                                           id="price"
                                           name="price"
                                           value="{{ old('price', '0') }}"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text text-muted" id="priceHelp">
                                        Free courses are forced to 0. Preview courses can optionally include an upgrade price. Paid courses must have a price greater than 0.
                                    </div>
                                </div>
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
                                    <i class="fas fa-plus"></i> Create Course
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Courses Management Section -->
    <div class="card shadow mt-5">
        <div class="card-header bg-dark text-white py-4 d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-list me-2"></i>Existing Courses</h3>
            <div class="input-group w-50">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="courseSearch" class="form-control" placeholder="Search courses by name...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Preview</th>
                            <th>Course Name</th>
                            <th>Academic Year</th>
                            <th>Policy</th>
                            <th>Status</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coursesTableBody">
                        @foreach($courses as $course)
                            <tr class="course-row" data-name="{{ strtolower($course->name) }}">
                                <td class="ps-3">
                                    <div style="width: 50px; height: 50px; border-radius: 8px; background-color: {{ $course->color }}; background-image: url('{{ $course->image_url }}'); background-size: cover; background-position: center; border: 1px solid #ddd;"></div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $course->name }}</div>
                                    <small class="text-muted">{{ Str::limit($course->description, 50) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-outline-secondary text-dark border">{{ $course->academic_year }}</span>
                                </td>
                                <td>
                                    @if($course->access_type === 'free')
                                        <span class="badge bg-success">Free</span>
                                    @elseif($course->access_type === 'preview')
                                        <span class="badge bg-info">Preview</span>
                                    @else
                                        <span class="badge bg-primary">Paid ({{ $course->price }} EGP)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($course->is_published)
                                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>Published</span>
                                    @else
                                        <span class="text-warning"><i class="fas fa-clock me-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td class="text-end px-4">
                                    <a href="{{ route('admin.courses.edit', $course->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($courses->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No courses found. Start by creating one above!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Course search functionality
document.getElementById('courseSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.course-row').forEach(row => {
        const name = row.dataset.name;
        row.style.display = name.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<style>
.preview-card {
    flex: 0 0 420px;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    transform: translateY(0);
    position: relative;
    border: 2px solid var(--course-color, #3b82f6);
    border-left: 8px solid var(--course-color, #3b82f6);
}

.preview-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.preview-image {
    position: relative;
    height: 520px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-image: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
}

.info-icon {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 24px;
    height: 24px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: help;
    z-index: 3;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.info-icon i {
    font-size: 12px;
    color: #007bff;
}

.info-tooltip {
    position: absolute;
    top: 40px;
    right: 0;
    width: 220px;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 0.85rem;
    color: #444;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-5px);
    transition: all 0.2s ease;
    line-height: 1.4;
    z-index: 4;
    pointer-events: none;
}

.info-icon:hover {
    background: white;
    transform: scale(1.05);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
}

.info-icon:hover .info-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.preview-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.5rem;
    background: none;
    z-index: 2;
}

.preview-title {
    font-size: 2.6rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.3;
    font-family: 'Inter', 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    z-index: 2;
    mix-blend-mode: normal;
    margin-bottom: 1rem;
    color: var(--course-title-color, #FFFFFF);
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.preview-info {
    padding: 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    background: white;
}

.preview-stats-row {
    display: flex;
    align-items: center;
    margin-top: 12px;
}

.preview-topics-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #777;
    font-weight: 400;
    margin-right: 16px;
}

.preview-topics-badge i {
    font-size: 1rem;
    color: #2196f3;
}

.preview-enrolled-users {
    display: flex;
    align-items: center;
    margin-left: 4px;
}

.preview-profile-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff, #00d4ff);
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: -8px;
    border: 2px solid white;
    background-size: cover;
    background-position: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.preview-profile-circle:first-child {
    margin-left: 0;
}

.preview-profile-circle.more {
    background: #f0f0f0;
    color: #666;
}

.preview-progress-container {
    margin-top: 3rem;
    padding-top: 1rem;
    width: 100%;
    padding: 1px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 2px;
}

.preview-progress-bar {
    width: 100%;
    height: 4px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 2px;
    overflow: hidden;
}

.preview-progress-fill {
    height: 100%;
    background: linear-gradient(to right, #34D399, #059669);
    border-radius: 2px;
    transition: width 0.3s ease;
    width: 0%;
}

.preview-action-btn {
    width: 100%;
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: default;
    background: #16a34a;
    color: white;
}

.preview-action-btn i {
    color: white;
}

.color-preview {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    margin-left: 10px;
    border: 2px solid #fff;
    transition: all 0.3s ease;
}

.color-field-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.color-field-controls .form-control-color {
    flex: 0 0 auto;
}

.color-picker-trigger {
    white-space: nowrap;
}

.upload-area {
    border: 2px dashed #007bff;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area:hover {
    border-color: #0056b3;
    background-color: rgba(0, 123, 255, 0.05);
}
</style>

<script>
// Live preview functionality
const nameInput = document.getElementById('name');
const descriptionInput = document.getElementById('description');
const imageUrlInput = document.getElementById('image_url');
const imageFileInput = document.getElementById('image_file');
const colorInput = document.getElementById('color');
const titleColorInput = document.getElementById('title_color');
const accessTypeInput = document.getElementById('access_type');
const priceInput = document.getElementById('price');
const academicYearInput = document.getElementById('academic_year');

const previewCard = document.getElementById('previewCard');
const previewImage = document.getElementById('previewImage');
const previewTitle = document.getElementById('previewTitle');
const previewTooltip = document.getElementById('previewTooltip');
const previewTopicsCount = document.getElementById('previewTopicsCount');
const previewProgressFill = document.getElementById('previewProgressFill');
const previewActionBtn = document.getElementById('previewActionBtn');
const previewActionText = document.getElementById('previewActionText');
const colorPreview = document.getElementById('colorPreview');
const titleColorPreview = document.getElementById('titleColorPreview');
const accessPolicyHelp = document.getElementById('accessPolicyHelp');
const priceLabel = document.getElementById('priceLabel');
const priceHelp = document.getElementById('priceHelp');
const colorPickerButtons = document.querySelectorAll('.color-picker-trigger');

// Image upload elements
const uploadArea = document.getElementById('uploadArea');
const uploadContent = document.getElementById('uploadContent');
const uploadPreview = document.getElementById('uploadPreview');
const previewImg = document.getElementById('previewImg');
const fileName = document.getElementById('fileName');
const removeImageBtn = document.getElementById('removeImage');
const maxCourseImageBytes = 50 * 1024 * 1024;

let currentImageSource = null; // 'file' or 'url'
let lastMonetizedPrice = (() => {
    const initialPrice = parseFloat(priceInput.value);
    return Number.isFinite(initialPrice) && initialPrice > 0 ? priceInput.value : '';
})();

function setImageInputFile(file) {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    imageFileInput.files = transfer.files;
}

function formatPriceLabel(value) {
    const price = parseFloat(value);

    if (!Number.isFinite(price) || price <= 0) {
        return null;
    }

    return price % 1 === 0 ? String(price) : price.toFixed(2);
}

function syncAccessPolicyForm() {
    const accessType = accessTypeInput.value;
    const currentPrice = parseFloat(priceInput.value);

    if (Number.isFinite(currentPrice) && currentPrice > 0) {
        lastMonetizedPrice = priceInput.value;
    }

    if (accessType === 'free') {
        priceInput.value = '0';
        priceInput.readOnly = true;
        priceInput.placeholder = '0.00';
        priceLabel.textContent = 'Price (EGP)';
        accessPolicyHelp.textContent = 'Free courses unlock full access after the survey.';
        priceHelp.textContent = 'Free courses are always stored with a price of 0.';
        return;
    }

    priceInput.readOnly = false;

    if (accessType === 'preview') {
        priceLabel.textContent = 'Upgrade Price (EGP)';
        priceInput.placeholder = 'Optional';
        accessPolicyHelp.textContent = 'Preview courses unlock limited topics after the survey. Add a price only if users can upgrade to the full course.';
        priceHelp.textContent = 'Leave this blank or 0 for preview-only access, or set a price for an upgrade path.';
        return;
    }

    priceLabel.textContent = 'Price (EGP)';
    priceInput.placeholder = 'Required';
    accessPolicyHelp.textContent = 'Paid courses stay locked until payment is completed.';
    priceHelp.textContent = 'Paid courses must have a price greater than 0.';

    if (!Number.isFinite(currentPrice) || currentPrice <= 0) {
        priceInput.value = lastMonetizedPrice;
    }
}

function openNativeColorPicker(input) {
    if (!input) {
        return;
    }

    if (typeof input.showPicker === 'function') {
        input.showPicker();
        return;
    }

    input.click();
}

function applyPickedColor(input, value) {
    if (!input || !/^#[0-9A-F]{6}$/i.test(value)) {
        return;
    }

    input.value = value.toUpperCase();
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

async function handleScreenColorPick(button) {
    const input = document.getElementById(button.dataset.colorTarget);
    if (!input) {
        return;
    }

    if (typeof window.EyeDropper !== 'function') {
        openNativeColorPicker(input);
        return;
    }

    const defaultLabel = button.dataset.defaultLabel || button.textContent.trim();
    button.disabled = true;
    button.textContent = 'Pick a color...';

    try {
        const eyeDropper = new window.EyeDropper();
        const { sRGBHex } = await eyeDropper.open();
        applyPickedColor(input, sRGBHex);
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.error('Screen color picker failed:', error);
            openNativeColorPicker(input);
        }
    } finally {
        button.disabled = false;
        button.textContent = defaultLabel;
    }
}

function updatePreview() {
    syncAccessPolicyForm();

    // Update title text
    previewTitle.textContent = nameInput.value || 'Course Preview';

    // Update description tooltip
    previewTooltip.textContent = descriptionInput.value || 'Course description will appear here';

    // Update image based on current source
    let imageUrl = null;
    if (currentImageSource === 'file' && imageFileInput.files[0]) {
        imageUrl = URL.createObjectURL(imageFileInput.files[0]);
    } else if (currentImageSource === 'url' && imageUrlInput.value.trim()) {
        imageUrl = imageUrlInput.value.trim();
    }

    console.log('Updating preview, imageUrl:', imageUrl, 'source:', currentImageSource);

    if (imageUrl) {
        previewImage.style.backgroundImage = `url('${imageUrl}')`;
        previewImage.style.backgroundSize = 'cover';
        previewImage.style.backgroundPosition = 'center';
        previewImage.style.backgroundRepeat = 'no-repeat';

        // Create a temporary img element to test if the image loads
        const testImg = new Image();
        testImg.onload = function() {
            console.log('✅ Image loaded successfully:', imageUrl);
        };
        testImg.onerror = function() {
            console.log('❌ Image failed to load:', imageUrl);
            previewImage.style.backgroundImage = 'linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%)';
            alert('Failed to load image from URL. Please check the URL and try again.');
        };
        testImg.src = imageUrl;

        console.log('Set background image to:', `url('${imageUrl}')`);
    } else {
        previewImage.style.backgroundImage = 'linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%)';
        console.log('Set default background');
    }

    // Get selected colors
    const courseColor = colorInput.value;
    const titleColor = titleColorInput.value;

    // Update CSS custom properties for dynamic styling
    previewCard.style.setProperty('--course-color', courseColor);
    previewCard.style.setProperty('--course-title-color', titleColor);

    // Update color previews
    if (colorPreview) colorPreview.style.backgroundColor = courseColor;
    if (titleColorPreview) titleColorPreview.style.backgroundColor = titleColor;

    // Update action button based on access policy
    const accessType = accessTypeInput.value;
    const formattedPrice = formatPriceLabel(priceInput.value);

    if (accessType === 'paid') {
        previewActionBtn.style.background = '#f3f4f6';
        previewActionBtn.style.color = '#6b7280';
        previewActionBtn.querySelector('i').className = 'fas fa-lock';
        previewActionText.textContent = formattedPrice
            ? `Unlock for ${formattedPrice} EGP`
            : 'Set paid price';
    } else if (accessType === 'preview') {
        previewActionBtn.style.background = '#2563eb';
        previewActionBtn.style.color = 'white';
        previewActionBtn.querySelector('i').className = 'fas fa-eye';
        previewActionText.textContent = formattedPrice
            ? `Start Preview, Upgrade ${formattedPrice} EGP`
            : 'Start Preview';
    } else {
        previewActionBtn.style.background = '#16a34a';
        previewActionBtn.style.color = 'white';
        previewActionBtn.querySelector('i').className = 'fas fa-play';
        previewActionText.textContent = 'Unlock via Survey';
    }

    // Simulate random topics count for preview
    const topicsCount = Math.floor(Math.random() * 15) + 5;
    previewTopicsCount.textContent = topicsCount;

    // Simulate random progress for preview
    const progress = Math.floor(Math.random() * 100);
    previewProgressFill.style.width = progress + '%';
}

// Image upload functionality
uploadArea.addEventListener('click', () => {
    imageFileInput.click();
});

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

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        setImageInputFile(files[0]);
        handleFileUpload(files[0]);
    }
});

imageFileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileUpload(e.target.files[0]);
    }
});

function handleFileUpload(file) {
    console.log('handleFileUpload called with file:', file.name, 'size:', file.size, 'type:', file.type);

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        return;
    }

    if (file.size > maxCourseImageBytes) {
        alert('Please select an image under 50MB.');
        imageFileInput.value = '';
        return;
    }

    // File type already validated above

    // Clear URL input when file is uploaded
    imageUrlInput.value = '';
    currentImageSource = 'file';

    console.log('Set currentImageSource to file, cleared URL input');
    console.log('Files in input:', imageFileInput.files.length);

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        console.log('FileReader onload fired');
        previewImg.src = e.target.result;
        fileName.textContent = file.name;
        uploadContent.classList.add('d-none');
        uploadPreview.classList.remove('d-none');

        console.log('About to call updatePreview from handleFileUpload');
        updatePreview();
    };
    reader.readAsDataURL(file);
}

removeImageBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    imageFileInput.value = '';
    currentImageSource = null;
    uploadContent.classList.remove('d-none');
    uploadPreview.classList.add('d-none');
    updatePreview();
});

// Handle URL input
imageUrlInput.addEventListener('input', () => {
    if (imageUrlInput.value) {
        // Clear file input when URL is provided
        imageFileInput.value = '';
        currentImageSource = 'url';
        uploadContent.classList.remove('d-none');
        uploadPreview.classList.add('d-none');
    } else {
        currentImageSource = null;
    }
    updatePreview();
});

// Tab switching logic
document.getElementById('upload-tab').addEventListener('click', () => {
    imageUrlInput.value = '';
    if (currentImageSource === 'url') {
        currentImageSource = null;
        updatePreview();
    }
});

document.getElementById('url-tab').addEventListener('click', () => {
    imageFileInput.value = '';
    if (currentImageSource === 'file') {
        currentImageSource = null;
        uploadContent.classList.remove('d-none');
        uploadPreview.classList.add('d-none');
        updatePreview();
    }
});

// Add event listeners for real-time updates
[nameInput, descriptionInput, imageUrlInput, colorInput, titleColorInput, accessTypeInput, priceInput].forEach(input => {
    input.addEventListener('input', updatePreview);
});

accessTypeInput.addEventListener('change', updatePreview);

colorPickerButtons.forEach(button => {
    button.addEventListener('click', () => {
        handleScreenColorPick(button);
    });
});

// Add specific listeners for image inputs
imageUrlInput.addEventListener('input', debounceUpdatePreview);
imageFileInput.addEventListener('change', debounceUpdatePreview);

// Debounce function to prevent excessive calls
function debounceUpdatePreview() {
    clearTimeout(window.previewTimeout);
    window.previewTimeout = setTimeout(updatePreview, 300);
}

// Initial setup
syncAccessPolicyForm();
updatePreview();
</script>
@endsection
