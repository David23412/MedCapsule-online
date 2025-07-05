@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Delete Questions
                    </h3>
                    <a href="{{ route('admin.delete.index') }}" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Warning</h5>
                        <p class="mb-0">Deleting questions will permanently remove:</p>
                        <ul class="mb-0">
                            <li>All user mistakes for these questions</li>
                            <li>All quiz attempts for these questions</li>
                            <li>All progress tracking for these questions</li>
                        </ul>
                    </div>

                    <!-- Filters -->
                    <form method="GET" action="{{ secure_url(route('admin.delete.questions', [], false)) }}" class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Course</label>
                            <select class="form-select" name="course_id">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Topic</label>
                            <select class="form-select" name="topic_id">
                                <option value="">All Topics</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}" {{ request('topic_id') == $topic->id ? 'selected' : '' }}>
                                        {{ $topic->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search Questions</label>
                            <input type="text" class="form-control" name="search" placeholder="Search question text..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                    </form>

                    <!-- Bulk Actions -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="selectAll()">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">
                                        <i class="fas fa-square me-1"></i>Deselect All
                                    </button>
                                </div>
                                <div>
                                    <span class="text-muted me-3">
                                        <span id="selectedCount">0</span> selected
                                    </span>
                                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled onclick="bulkDelete()">
                                        <i class="fas fa-trash me-1"></i>Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($questions->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No questions found</h4>
                            <p class="text-muted">No questions match your current filters.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Question</th>
                                        <th>Topic</th>
                                        <th>Course</th>
                                        <th>Mistakes</th>
                                        <th>Attempts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questions as $question)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="question-checkbox" value="{{ $question->id }}" onchange="updateSelectedCount()">
                                            </td>
                                            <td>
                                                <div class="question-text">
                                                    {{ Str::limit($question->question, 100) }}
                                                    @if($question->image_url)
                                                        <i class="fas fa-image text-muted ms-1" title="Has image"></i>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    Type: {{ ucfirst($question->question_type) }} | 
                                                    Source: {{ $question->source }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $question->topic->name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $question->topic->course->name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $question->mistakes_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $question->quiz_attempts_count }}</span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm"
                                                        onclick="showDeleteConfirmation({{ $question->id }}, '{{ addslashes(Str::limit($question->question, 50)) }}', '{{ $question->topic->name }}', '{{ $question->topic->course->name }}', {{ $question->mistakes_count }}, {{ $question->quiz_attempts_count }})">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $questions->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Question Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</h6>
                    <p class="mb-0">You are about to permanently delete this question and all its associated data.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <strong>Question:</strong>
                        <p id="questionText" class="text-muted"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Location:</strong>
                        <p class="text-muted">
                            <span id="topicName"></span><br>
                            <small id="courseName"></small>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>What will be deleted:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Mistakes
                                <span class="badge bg-warning" id="mistakesCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Quiz Attempts
                                <span class="badge bg-success" id="attemptsCount">0</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Additional data:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Progress tracking</li>
                            <li class="list-group-item">Study session records</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmCheck">
                        <label class="form-check-label" for="confirmCheck">
                            I understand that this action is permanent and cannot be undone
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                        <i class="fas fa-trash me-1"></i>Delete Question
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Bulk Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</h6>
                    <p class="mb-0">You are about to permanently delete <strong id="bulkDeleteCount">0</strong> questions and all their associated data.</p>
                </div>

                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulkConfirmCheck">
                        <label class="form-check-label" for="bulkConfirmCheck">
                            I understand that this action is permanent and cannot be undone
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <form id="bulkDeleteForm" method="POST" action="{{ secure_url(route('admin.delete.questions.bulk', [], false)) }}">
                    @csrf
                    <input type="hidden" name="question_ids" id="bulkQuestionIds">
                    <button type="submit" class="btn btn-danger" id="bulkDeleteBtn" disabled>
                        <i class="fas fa-trash me-1"></i>Delete Questions
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.question-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
    updateSelectedCount();
}

function deselectAll() {
    document.querySelectorAll('.question-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
    updateSelectedCount();
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    document.querySelectorAll('.question-checkbox').forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
    const count = selectedCheckboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkDeleteBtn').disabled = count === 0;
}

function showDeleteConfirmation(questionId, questionText, topicName, courseName, mistakesCount, attemptsCount) {
    // Update modal content
    document.getElementById('questionText').textContent = questionText;
    document.getElementById('topicName').textContent = topicName;
    document.getElementById('courseName').textContent = courseName;
    document.getElementById('mistakesCount').textContent = mistakesCount;
    document.getElementById('attemptsCount').textContent = attemptsCount;
    
    // Update form action
    document.getElementById('deleteForm').action = `{{ secure_url('admin/delete/question') }}/${questionId}`;
    
    // Reset checkbox
    document.getElementById('confirmCheck').checked = false;
    document.getElementById('deleteBtn').disabled = true;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function bulkDelete() {
    const selectedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
    const questionIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    document.getElementById('bulkDeleteCount').textContent = questionIds.length;
    document.getElementById('bulkQuestionIds').value = JSON.stringify(questionIds);
    
    // Reset checkbox
    document.getElementById('bulkConfirmCheck').checked = false;
    document.getElementById('bulkDeleteBtn').disabled = true;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('bulkDeleteModal')).show();
}

// Enable delete buttons when checkboxes are checked
document.getElementById('confirmCheck').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});

document.getElementById('bulkConfirmCheck').addEventListener('change', function() {
    document.getElementById('bulkDeleteBtn').disabled = !this.checked;
});

// Initialize selected count
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>
@endsection 