@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Delete Courses
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
                        <p class="mb-0">Deleting a course will permanently remove:</p>
                        <ul class="mb-0">
                            <li>All topics in the course</li>
                            <li>All questions in those topics</li>
                            <li>All user quiz attempts for those topics</li>
                            <li>All mistakes recorded for those questions</li>
                            <li>All user progress and statistics</li>
                            <li>All payments associated with the course</li>
                        </ul>
                    </div>

                    @if($courses->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No courses found</h4>
                            <p class="text-muted">There are no courses available for deletion.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Topics</th>
                                        <th>Questions</th>
                                        <th>Enrolled Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($courses as $course)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($course->image_url)
                                                        <img src="{{ asset($course->image_url) }}" 
                                                             alt="{{ $course->name }}" 
                                                             class="rounded me-2" 
                                                             style="width: 40px; height: 40px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <strong>{{ $course->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $course->description }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $course->topics_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $course->questions_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $course->users_count }}</span>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm"
                                                        data-delete-course="{{ $course->id }}" data-course-name="{{ $course->name }}"
                                                        data-topics-count="{{ $course->topics_count }}" data-questions-count="{{ $course->questions_count }}" data-users-count="{{ $course->users_count }}">
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
                    Confirm Course Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</h6>
                    <p class="mb-0">You are about to permanently delete the course "<strong id="courseName"></strong>" and all its associated data.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>What will be deleted:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Topics
                                <span class="badge bg-primary" id="topicsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Questions
                                <span class="badge bg-success" id="questionsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Enrolled Users
                                <span class="badge bg-info" id="usersCount">0</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Additional data that will be removed:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">All quiz attempts</li>
                            <li class="list-group-item">All user mistakes</li>
                            <li class="list-group-item">All progress tracking</li>
                            <li class="list-group-item">All payment records</li>
                            <li class="list-group-item">All study sessions</li>
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
                        <i class="fas fa-trash me-1"></i>Delete Course
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteConfirmation(courseId, courseName, topicsCount, questionsCount, usersCount) {
    // Update modal content
    document.getElementById('courseName').textContent = courseName;
    document.getElementById('topicsCount').textContent = topicsCount;
    document.getElementById('questionsCount').textContent = questionsCount;
    document.getElementById('usersCount').textContent = usersCount;
    
    // Update form action
    document.getElementById('deleteForm').action = `/admin/delete/course/${courseId}`;
    
    // Reset checkbox
    document.getElementById('confirmCheck').checked = false;
    document.getElementById('deleteBtn').disabled = true;
    
    // Show modal
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.querySelectorAll('[data-delete-course]').forEach(button => {
    button.addEventListener('click', () => showDeleteConfirmation(
        button.dataset.deleteCourse, button.dataset.courseName,
        button.dataset.topicsCount, button.dataset.questionsCount, button.dataset.usersCount
    ));
});

// Enable delete button when checkbox is checked
document.getElementById('confirmCheck').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});
</script>
@endsection 