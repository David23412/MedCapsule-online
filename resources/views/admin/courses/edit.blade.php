@extends('layouts.admin')

@section('styles')
<style>
    /* Course Type Preview Styles */
    .course-type-preview {
        margin-top: 1.5rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .preview-header {
        background-color: #f8f9fa;
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .preview-header h4 {
        margin-bottom: 0.25rem;
        font-size: 1.25rem;
    }

    .preview-content {
        padding: 1.5rem;
    }

    .preview-footer {
        padding: 0 1rem 1rem;
    }

    /* Type selector styles */
    .course-type-selector {
        margin-bottom: 1.5rem;
    }

    .type-option {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .type-option:hover {
        border-color: #adb5bd;
        background-color: #f8f9fa;
    }

    .type-option.selected {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .type-option-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .type-option-header .form-check {
        margin: 0;
    }

    .type-option-title {
        font-weight: 600;
        margin-left: 0.75rem;
        font-size: 1.1rem;
    }

    .type-option-description {
        color: #6c757d;
        margin-left: 1.75rem;
        margin-bottom: 0;
    }

    /* Preview case filters */
    .case-filters-preview {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
        margin: 1.5rem 0;
    }

    .case-filter {
        padding: 0.4rem 1.2rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e9ecef;
        background: white;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .case-filter.active {
        background: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    .case-filter i {
        font-size: 0.8rem;
    }

    .case-filter .count {
        background: rgba(0,0,0,0.05);
        padding: 0.15rem 0.5rem;
        border-radius: 50px;
        font-size: 0.75rem;
        min-width: 1.2rem;
        text-align: center;
    }

    .case-filter.active .count {
        background: rgba(255,255,255,0.2);
        color: white !important;
    }

    /* Topic preview grid */
    .topic-grid-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .topic-preview-item {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.2s ease;
    }

    .topic-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        border-color: #dee2e6;
    }

    .topic-preview-icon {
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(0,0,0,0.03);
        border-radius: 50%;
    }

    .topic-preview-content h6 {
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Edit Course') }}</span>
                    <span class="badge bg-info">ID: {{ $course->id }}</span>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.courses.update', $course->id) }}" id="course-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $course->id }}">

                        <div class="row">
                            <!-- Left column - Course details -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">{{ __('Course Name') }}</label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $course->name) }}" required autocomplete="name" autofocus>

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="description" class="form-label">{{ __('Description') }}</label>
                                    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description', $course->description) }}</textarea>

                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="access_type" class="form-label">{{ __('Access Policy') }}</label>
                                            <select id="access_type" class="form-select @error('access_type') is-invalid @enderror" name="access_type" required>
                                                <option value="{{ \App\Models\Course::ACCESS_TYPE_FREE }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_FREE ? 'selected' : '' }}>
                                                    Free access
                                                </option>
                                                <option value="{{ \App\Models\Course::ACCESS_TYPE_PREVIEW }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_PREVIEW ? 'selected' : '' }}>
                                                    Preview access
                                                </option>
                                                <option value="{{ \App\Models\Course::ACCESS_TYPE_PAID }}" {{ old('access_type', $course->access_type) === \App\Models\Course::ACCESS_TYPE_PAID ? 'selected' : '' }}>
                                                    Paid access
                                                </option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Access policy controls whether the course is survey-unlocked, preview-only, or paid.
                                            </small>
                                            @error('access_type')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="price" class="form-label">{{ __('Price') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input id="price" type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" name="price" value="{{ old('price', $course->price) }}">
                                            </div>
                                            <small class="form-text text-muted">
                                                Free courses should be 0. Preview courses can optionally have an upgrade price. Paid courses must be greater than 0.
                                            </small>
                                            @error('price')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="is_published" class="form-label">{{ __('Published') }}</label>
                                            <select id="is_published" class="form-select @error('is_published') is-invalid @enderror" name="is_published" required>
                                                <option value="1" {{ old('is_published', $course->is_published) == 1 ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ old('is_published', $course->is_published) == 0 ? 'selected' : '' }}>No</option>
                                            </select>

                                            @error('is_published')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Course Type Selection -->
                                <div class="course-type-selector mt-4">
                                    <h5 class="mb-3">Course Type</h5>
                                    
                                    <div class="type-option {{ old('type', $course->type) === 'medical' || !old('type', $course->type) ? 'selected' : '' }}">
                                        <div class="type-option-header">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type" id="type-medical" value="medical" {{ old('type', $course->type) === 'medical' || !old('type', $course->type) ? 'checked' : '' }}>
                                            </div>
                                            <label class="type-option-title" for="type-medical">Medical Course</label>
                                        </div>
                                        <p class="type-option-description">Standard medical categorization with Quiz, Cases, and Practical pills</p>
                                    </div>
                                    
                                    <div class="type-option {{ old('type', $course->type) === 'custom' ? 'selected' : '' }}">
                                        <div class="type-option-header">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type" id="type-custom" value="custom" {{ old('type', $course->type) === 'custom' ? 'checked' : '' }}>
                                            </div>
                                            <label class="type-option-title" for="type-custom">Custom Course</label>
                                        </div>
                                        <p class="type-option-description">Dynamic categorization based on topic categories</p>
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Update Course') }}
                                    </button>
                                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary ms-2">
                                        {{ __('Cancel') }}
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Right column - Preview -->
                            <div class="col-md-6">
                                <div class="course-type-preview" id="course-type-preview">
                                    <!-- Preview content will be dynamically inserted here -->
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
/**
 * Course Type Switcher
 * 
 * This script handles the course type switching UI with instant preview
 * for the course edit page.
 */

class CourseTypeSwitcher {
    /**
     * Initialize the course type switcher
     * 
     * @param {string} formSelector - Selector for the course form
     * @param {string} previewSelector - Selector for the preview container
     */
    constructor(formSelector, previewSelector) {
        this.form = document.querySelector(formSelector);
        this.previewContainer = document.querySelector(previewSelector);
        this.typeRadios = document.querySelectorAll('input[name="type"]');
        this.originalType = this.getSelectedType();
        this.previewDebounceTimeout = null;
        
        this.init();
    }
    
    /**
     * Initialize the switcher
     */
    init() {
        if (!this.form || !this.previewContainer) return;
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Generate initial preview
        this.generatePreview();
    }
    
    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Listen for type changes
        this.typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.handleTypeChange();
            });
        });
        
        // Listen for form submission
        this.form.addEventListener('submit', (e) => {
            // Show confirmation if changing from medical to custom
            if (this.originalType === 'medical' && this.getSelectedType() === 'custom') {
                if (!confirm('Warning: Changing from Medical to Custom course type will affect how categorizations are displayed. This cannot be easily reversed. Are you sure?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
    
    /**
     * Handle course type change
     */
    handleTypeChange() {
        // Clear any pending preview updates
        if (this.previewDebounceTimeout) {
            clearTimeout(this.previewDebounceTimeout);
        }
        
        // Show loading state
        this.showLoading();
        
        // Debounce preview generation
        this.previewDebounceTimeout = setTimeout(() => {
            this.generatePreview();
        }, 300);
    }
    
    /**
     * Show loading state in preview
     */
    showLoading() {
        this.previewContainer.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Generating preview...</p>
            </div>
        `;
    }
    
    /**
     * Get the currently selected course type
     * 
     * @returns {string} The selected course type
     */
    getSelectedType() {
        const selectedRadio = document.querySelector('input[name="type"]:checked');
        return selectedRadio ? selectedRadio.value : 'medical';
    }
    
    /**
     * Generate preview based on selected type
     */
    generatePreview() {
        const selectedType = this.getSelectedType();
        
        // Get course data from form
        const courseData = {
            name: this.form.querySelector('input[name="name"]').value || 'Example Course',
            type: selectedType,
            id: this.form.querySelector('input[name="id"]')?.value || 1
        };
        
        // Generate preview HTML based on type
        if (selectedType === 'medical') {
            this.generateMedicalPreview(courseData);
        } else {
            this.generateCustomPreview(courseData);
        }
    }
    
    /**
     * Generate preview for medical course type
     * 
     * @param {Object} courseData - Course data from the form
     */
    generateMedicalPreview(courseData) {
        // Fetch preview data from server
        this.fetchPreviewData(courseData, (data) => {
            // Create medical course preview
            const html = `
                <div class="preview-header">
                    <h4>Medical Course Preview</h4>
                    <p class="text-muted">Standard medical categorization with Quiz, Cases, and Practical pills</p>
                </div>
                
                <div class="preview-content">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${courseData.name}</h5>
                            
                            <div class="case-filters-preview">
                                <div class="case-filter active">
                                    <i class="fas fa-th-large"></i>
                                    All
                                    <span class="count">${data.topicCount || 12}</span>
                                </div>
                                <div class="case-filter" style="color: #ff8f00;">
                                    <i class="fas fa-file-alt" style="color: #ff8f00;"></i>
                                    Cases
                                    <span class="count" style="color: #ff8f00;">${data.casesCount || 4}</span>
                                </div>
                                <div class="case-filter" style="color: #7b1fa2;">
                                    <i class="fas fa-flask" style="color: #7b1fa2;"></i>
                                    Practical
                                    <span class="count" style="color: #7b1fa2;">${data.practicalCount || 3}</span>
                                </div>
                                <div class="case-filter" style="color: #2e7d32;">
                                    <i class="fas fa-question-circle" style="color: #2e7d32;"></i>
                                    Quiz
                                    <span class="count" style="color: #2e7d32;">${data.quizCount || 5}</span>
                                </div>
                            </div>
                            
                            <div class="topic-grid-preview">
                                ${this.generateTopicPreviewItems('medical', data)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="preview-footer mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Medical courses use fixed categorization (Quiz, Cases, Practical) and are optimized for medical education.
                    </div>
                </div>
            `;
            
            this.previewContainer.innerHTML = html;
        });
    }
    
    /**
     * Generate preview for custom course type
     * 
     * @param {Object} courseData - Course data from the form
     */
    generateCustomPreview(courseData) {
        // Fetch preview data from server
        this.fetchPreviewData(courseData, (data) => {
            // Create custom course preview
            const html = `
                <div class="preview-header">
                    <h4>Custom Course Preview</h4>
                    <p class="text-muted">Dynamic categorization based on topic categories</p>
                </div>
                
                <div class="preview-content">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">${courseData.name}</h5>
                            
                            <div class="case-filters-preview">
                                <div class="case-filter active">
                                    <i class="fas fa-th-large"></i>
                                    All
                                    <span class="count">${data.topicCount || 12}</span>
                                </div>
                                ${this.generateCustomCategoryPills(data)}
                            </div>
                            
                            <div class="topic-grid-preview">
                                ${this.generateTopicPreviewItems('custom', data)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="preview-footer mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Custom courses use dynamic categorization based on your topic categories. You can create any category structure you need.
                    </div>
                </div>
            `;
            
            this.previewContainer.innerHTML = html;
        });
    }
    
    /**
     * Generate custom category pills for preview
     * 
     * @param {Object} data - Preview data
     * @returns {string} HTML for custom category pills
     */
    generateCustomCategoryPills(data) {
        const categories = data.categories || ['Fundamentals', 'Advanced', 'Projects'];
        const counts = data.categoryCounts || [4, 5, 3];
        const colors = ['#e91e63', '#2196f3', '#ff9800', '#4caf50', '#9c27b0'];
        
        let html = '';
        
        categories.forEach((category, index) => {
            const color = colors[index % colors.length];
            html += `
                <div class="case-filter" style="color: ${color};">
                    <i class="fas fa-bookmark" style="color: ${color};"></i>
                    ${category}
                    <span class="count" style="color: ${color};">${counts[index]}</span>
                </div>
            `;
        });
        
        return html;
    }
    
    /**
     * Generate topic preview items
     * 
     * @param {string} type - Course type
     * @param {Object} data - Preview data
     * @returns {string} HTML for topic preview items
     */
    generateTopicPreviewItems(type, data) {
        const count = data.topicCount || 6;
        let html = '';
        
        for (let i = 0; i < count && i < 6; i++) {
            let category, icon, color;
            
            if (type === 'medical') {
                const categories = ['quiz', 'cases', 'practical'];
                category = categories[i % 3];
                
                if (category === 'quiz') {
                    icon = 'fa-question-circle';
                    color = '#2e7d32';
                } else if (category === 'cases') {
                    icon = 'fa-file-alt';
                    color = '#ff8f00';
                } else {
                    icon = 'fa-flask';
                    color = '#7b1fa2';
                }
            } else {
                const categories = data.categories || ['Fundamentals', 'Advanced', 'Projects'];
                const colors = ['#e91e63', '#2196f3', '#ff9800', '#4caf50', '#9c27b0'];
                
                category = categories[i % categories.length];
                icon = 'fa-bookmark';
                color = colors[i % colors.length];
            }
            
            html += `
                <div class="topic-preview-item">
                    <div class="topic-preview-icon" style="color: ${color};">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="topic-preview-content">
                        <h6>Example Topic ${i + 1}</h6>
                        <span class="badge" style="background-color: ${color};">${category}</span>
                    </div>
                </div>
            `;
        }
        
        return html;
    }
    
    /**
     * Fetch preview data from server
     * 
     * @param {Object} courseData - Course data from the form
     * @param {Function} callback - Callback function to handle the data
     */
    fetchPreviewData(courseData, callback) {
        // Check if we have a real course ID to fetch data for
        if (courseData.id && courseData.id !== 'new') {
            // Fetch real data from server
            fetch(`/api/courses/${courseData.id}/preview-data?type=${courseData.type}`)
                .then(response => response.json())
                .then(data => {
                    callback(data);
                })
                .catch(error => {
                    console.error('Error fetching preview data:', error);
                    // Fall back to mock data
                    callback(this.getMockPreviewData(courseData.type));
                });
        } else {
            // Use mock data for new courses
            callback(this.getMockPreviewData(courseData.type));
        }
    }
    
    /**
     * Get mock preview data
     * 
     * @param {string} type - Course type
     * @returns {Object} Mock preview data
     */
    getMockPreviewData(type) {
        if (type === 'medical') {
            return {
                topicCount: 12,
                casesCount: 4,
                practicalCount: 3,
                quizCount: 5
            };
        } else {
            return {
                topicCount: 12,
                categories: ['Fundamentals', 'Advanced', 'Projects'],
                categoryCounts: [4, 5, 3]
            };
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const switcher = new CourseTypeSwitcher('#course-form', '#course-type-preview');
    
    // Initialize type option selection
    const typeOptions = document.querySelectorAll('.type-option');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    
    // Add click handler to the entire option div
    typeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Find the radio input inside this option
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Remove selected class from all options
                typeOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Trigger change event on the radio
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>
@endsection
