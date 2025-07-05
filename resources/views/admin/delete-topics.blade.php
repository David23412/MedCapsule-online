@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-book me-2"></i>
                        Delete Topics
                    </h3>
                    <a href="{{ route('admin.delete.index') }}" class="btn btn-outline-light btn-sm">
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
                        <p class="mb-0">Deleting a topic will permanently remove:</p>
                        <ul class="mb-0">
                            <li>All questions in the topic</li>
                            <li>All user quiz attempts for the topic</li>
                            <li>All mistakes recorded for those questions</li>
                            <li>All user progress and statistics for the topic</li>
                            <li>All spaced repetition data for those questions</li>
                        </ul>
                    </div>

                    @if($topics->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No topics found</h4>
                            <p class="text-muted">There are no topics available for deletion.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Topic Name</th>
                                        <th>Course</th>
                                        <th>Questions</th>
                                        <th>Quiz Attempts</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topics as $topic)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $topic->name }}</strong>
                                                    @if($topic->description)
                                                        <br>
                                                        <small class="text-muted">{{ Str::limit($topic->description, 100) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $topic->course->name }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $topic->questions_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $topic->quiz_attempts_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($topic->case_type) }}</span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm"
                                                        onclick="showDeleteConfirmation({{ $topic->id }}, '{{ addslashes($topic->name) }}', '{{ addslashes($topic->course->name) }}', {{ $topic->questions_count }}, {{ $topic->quiz_attempts_count }})">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Topic Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</h6>
                    <p class="mb-0">You are about to permanently delete the topic "<strong id="topicName"></strong>" from course "<strong id="courseName"></strong>" and all its associated data.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>What will be deleted:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Questions
                                <span class="badge bg-success" id="questionsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Quiz Attempts
                                <span class="badge bg-info" id="attemptsCount">0</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Additional data that will be removed:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">All user mistakes</li>
                            <li class="list-group-item">All spaced repetition data</li>
                            <li class="list-group-item">All progress tracking</li>
                            <li class="list-group-item">All study sessions</li>
                            <li class="list-group-item">All source progress</li>
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
                        <i class="fas fa-trash me-1"></i>Delete Topic
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteConfirmation(topicId, topicName, courseName, questionsCount, attemptsCount) {
    // Update modal content
    document.getElementById('topicName').textContent = topicName;
    document.getElementById('courseName').textContent = courseName;
    document.getElementById('questionsCount').textContent = questionsCount;
    document.getElementById('attemptsCount').textContent = attemptsCount;
    
    // Update form action
    document.getElementById('deleteForm').action = `/admin/delete/topic/${topicId}`;
    
    // Reset checkbox
    document.getElementById('confirmCheck').checked = false;
    document.getElementById('deleteBtn').disabled = true;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Enable delete button when checkbox is checked
document.getElementById('confirmCheck').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});
</script>
@endsection 