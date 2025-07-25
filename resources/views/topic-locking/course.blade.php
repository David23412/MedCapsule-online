@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <i class="fas fa-lock me-2"></i>Topic Management
                        </h4>
                        <small class="text-muted">{{ $course->name }}</small>
                    </div>
                    <div>
                        <a href="{{ route('topic-locking.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Courses
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($topics->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No topics found for this course.
                        </div>
                    @else
                        <div class="mb-3">
                            <button type="button" class="btn btn-warning btn-sm me-2" id="bulkLockBtn">
                                <i class="fas fa-lock me-1"></i>Lock Selected
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="bulkUnlockBtn">
                                <i class="fas fa-unlock me-1"></i>Unlock Selected
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Topic Name</th>
                                        <th>Status</th>
                                        <th>Locked By</th>
                                        <th>Lock Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topics as $topic)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input topic-checkbox" 
                                                       value="{{ $topic->id }}">
                                            </td>
                                            <td>
                                                <strong>{{ $topic->name }}</strong>
                                            </td>
                                            <td>
                                                @if($topic->is_locked)
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-lock me-1"></i>Locked
                                                    </span>
                                                @else
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-unlock me-1"></i>Unlocked
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($topic->is_locked && $topic->lockedByTutor)
                                                    {{ $topic->lockedByTutor->name }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($topic->is_locked && $topic->lock_reason)
                                                    <small class="text-muted">{{ $topic->lock_reason }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($topic->is_locked)
                                                    <button type="button" class="btn btn-success btn-sm unlock-topic" 
                                                            data-topic-id="{{ $topic->id }}">
                                                        <i class="fas fa-unlock me-1"></i>Unlock
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-warning btn-sm lock-topic" 
                                                            data-topic-id="{{ $topic->id }}">
                                                        <i class="fas fa-lock me-1"></i>Lock
                                                    </button>
                                                @endif
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

<!-- Lock Reason Modal -->
<div class="modal fade" id="lockReasonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lock Topic</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="lockForm">
                    <div class="mb-3">
                        <label for="lockReason" class="form-label">Reason for locking (optional)</label>
                        <textarea class="form-control" id="lockReason" name="reason" rows="3" 
                                  placeholder="Enter reason for locking this topic..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmLock">
                    <i class="fas fa-lock me-1"></i>Lock Topic
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const topicCheckboxes = document.querySelectorAll('.topic-checkbox');
    const bulkLockBtn = document.getElementById('bulkLockBtn');
    const bulkUnlockBtn = document.getElementById('bulkUnlockBtn');
    const lockReasonModal = new bootstrap.Modal(document.getElementById('lockReasonModal'));
    let currentTopicId = null;
    let currentAction = null;

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        topicCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Individual lock/unlock buttons
    document.querySelectorAll('.lock-topic').forEach(btn => {
        btn.addEventListener('click', function() {
            currentTopicId = this.dataset.topicId;
            currentAction = 'lock';
            lockReasonModal.show();
        });
    });

    document.querySelectorAll('.unlock-topic').forEach(btn => {
        btn.addEventListener('click', function() {
            const topicId = this.dataset.topicId;
            unlockTopic(topicId);
        });
    });

    // Confirm lock button
    document.getElementById('confirmLock').addEventListener('click', function() {
        const reason = document.getElementById('lockReason').value;
        
        if (currentAction === 'lock' && currentTopicId) {
            lockTopic(currentTopicId, reason);
        } else if (currentAction === 'bulkLock') {
            const selectedTopics = getSelectedTopics();
            bulkLockTopics(selectedTopics, reason);
        }
        
        lockReasonModal.hide();
        document.getElementById('lockReason').value = '';
    });

    // Bulk actions
    bulkLockBtn.addEventListener('click', function() {
        const selectedTopics = getSelectedTopics();
        if (selectedTopics.length === 0) {
            alert('Please select topics to lock.');
            return;
        }
        currentAction = 'bulkLock';
        lockReasonModal.show();
    });

    bulkUnlockBtn.addEventListener('click', function() {
        const selectedTopics = getSelectedTopics();
        if (selectedTopics.length === 0) {
            alert('Please select topics to unlock.');
            return;
        }
        bulkUnlockTopics(selectedTopics);
    });

    function getSelectedTopics() {
        return Array.from(document.querySelectorAll('.topic-checkbox:checked'))
                   .map(checkbox => checkbox.value);
    }

    function lockTopic(topicId, reason) {
        fetch(`/topic-locking/${topicId}/lock`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while locking the topic.');
        });
    }

    function unlockTopic(topicId) {
        fetch(`/topic-locking/${topicId}/unlock`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while unlocking the topic.');
        });
    }

    function bulkLockTopics(topicIds, reason) {
        fetch('/topic-locking/bulk-lock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                topic_ids: topicIds,
                reason: reason 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while locking topics.');
        });
    }

    function bulkUnlockTopics(topicIds) {
        fetch('/topic-locking/bulk-unlock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ topic_ids: topicIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while unlocking topics.');
        });
    }
});
</script>
@endpush