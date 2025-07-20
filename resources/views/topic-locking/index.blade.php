<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .topic-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .topic-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .topic-locked {
            border-left: 4px solid #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }
        .topic-unlocked {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        }
        .stats-overview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .course-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .course-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .lock-toggle {
            width: 60px;
            height: 30px;
            border-radius: 15px;
            border: none;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .lock-toggle.locked {
            background: #dc3545;
        }
        .lock-toggle.unlocked {
            background: #28a745;
        }
        .lock-toggle::after {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: white;
            top: 2px;
            transition: all 0.3s ease;
        }
        .lock-toggle.locked::after {
            left: 2px;
        }
        .lock-toggle.unlocked::after {
            left: 32px;
        }
        .bulk-actions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Topic Locking Management</h1>
                <p class="text-muted mb-0">Control student access to course topics</p>
            </div>
            <div>
                <a href="{{ route('tutor.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $statistics['total_locked_topics'] }}</span>
                        <small>Locked Topics</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $statistics['recently_locked'] }}</span>
                        <small>Recently Locked</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ count($statistics['locks_by_course']) }}</span>
                        <small>Courses with Locks</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">{{ $courses->sum(function($course) { return $course->topics->count(); }) }}</span>
                        <small>Total Topics</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>Bulk Actions
                    </h6>
                    <small class="text-muted">Select topics below to perform bulk operations</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkLock()">
                            <i class="fas fa-lock me-1"></i>Lock Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkUnlock()">
                            <i class="fas fa-unlock me-1"></i>Unlock Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectNone()">
                            <i class="fas fa-square me-1"></i>Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses and Topics -->
        @foreach($courses as $course)
            <div class="course-section">
                <div class="course-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-0">{{ $course->name }} ({{ $course->year }})</h4>
                            <small class="text-muted">{{ $course->topics->count() }} topics</small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-danger" onclick="lockCourse({{ $course->id }})">
                                    <i class="fas fa-lock me-1"></i>Lock All
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="unlockCourse({{ $course->id }})">
                                    <i class="fas fa-unlock me-1"></i>Unlock All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @forelse($course->topics as $topic)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card topic-card h-100 {{ $topic->is_locked ? 'topic-locked' : 'topic-unlocked' }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input topic-checkbox" type="checkbox" 
                                                   value="{{ $topic->id }}" id="topic_{{ $topic->id }}">
                                            <label class="form-check-label" for="topic_{{ $topic->id }}">
                                                <h6 class="mb-0">{{ $topic->name }}</h6>
                                            </label>
                                        </div>
                                        <button type="button" 
                                                class="lock-toggle {{ $topic->is_locked ? 'locked' : 'unlocked' }}"
                                                onclick="toggleTopicLock({{ $topic->id }})"
                                                title="{{ $topic->is_locked ? 'Click to unlock' : 'Click to lock' }}">
                                        </button>
                                    </div>

                                    <div class="mb-2">
                                        <span class="badge {{ $topic->is_locked ? 'bg-danger' : 'bg-success' }}">
                                            <i class="fas fa-{{ $topic->is_locked ? 'lock' : 'unlock' }} me-1"></i>
                                            {{ $topic->is_locked ? 'Locked' : 'Unlocked' }}
                                        </span>
                                        <small class="text-muted ms-2">Order: {{ $topic->display_order }}</small>
                                    </div>

                                    @if($topic->is_locked)
                                        <div class="mt-2">
                                            @if($topic->lock_reason)
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-comment me-1"></i>{{ $topic->lock_reason }}
                                                </small>
                                            @endif
                                            @if($topic->locked_at)
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-clock me-1"></i>Locked {{ $topic->locked_at->diffForHumans() }}
                                                </small>
                                            @endif
                                            @if($topic->lockedByTutor)
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-user me-1"></i>by {{ $topic->lockedByTutor->name }}
                                                </small>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewTopicDetails({{ $topic->id }})">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </button>
                                        @if($topic->is_locked)
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="unlockTopic({{ $topic->id }})">
                                                <i class="fas fa-unlock me-1"></i>Unlock
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="lockTopic({{ $topic->id }})">
                                                <i class="fas fa-lock me-1"></i>Lock
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-4">
                                <i class="fas fa-bookmark fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No topics found in this course.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        @if($courses->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-book fa-4x text-muted mb-4"></i>
                <h4 class="text-muted">No courses available</h4>
                <p class="text-muted">You don't have any courses with students assigned yet.</p>
            </div>
        @endif

        <!-- Recently Locked Topics -->
        @if($lockedTopics->count() > 0)
            <div class="course-section">
                <div class="course-header">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Recently Locked Topics
                    </h4>
                    <small class="text-muted">Topics you've locked recently</small>
                </div>

                <div class="row">
                    @foreach($lockedTopics->take(6) as $topic)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card topic-card topic-locked h-100">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $topic->name }}</h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-book me-1"></i>{{ $topic->course->name }}
                                        </small>
                                    </p>
                                    @if($topic->lock_reason)
                                        <p class="card-text">
                                            <small class="text-muted">{{ $topic->lock_reason }}</small>
                                        </p>
                                    @endif
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Locked {{ $topic->locked_at->diffForHumans() }}
                                        </small>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" 
                                            onclick="unlockTopic({{ $topic->id }})">
                                        <i class="fas fa-unlock me-1"></i>Unlock
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle topic lock
        function toggleTopicLock(topicId) {
            const reason = prompt('Enter a reason for this action (optional):');
            
            fetch(`/topic-locking/topics/${topicId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
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
                alert('An error occurred while toggling the topic lock.');
            });
        }

        // Lock topic
        function lockTopic(topicId) {
            const reason = prompt('Enter a reason for locking this topic (optional):');
            
            fetch(`/topic-locking/topics/${topicId}/lock`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error locking topic: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while locking the topic.');
            });
        }

        // Unlock topic
        function unlockTopic(topicId) {
            if (confirm('Are you sure you want to unlock this topic?')) {
                fetch(`/topic-locking/topics/${topicId}/unlock`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error unlocking topic: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while unlocking the topic.');
                });
            }
        }

        // Lock entire course
        function lockCourse(courseId) {
            const reason = prompt('Enter a reason for locking all topics in this course (optional):');
            if (reason !== null) {
                fetch(`/topic-locking/courses/${courseId}/lock-all`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ reason: reason })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while locking the course topics.');
                });
            }
        }

        // Unlock entire course
        function unlockCourse(courseId) {
            if (confirm('Are you sure you want to unlock all topics in this course?')) {
                fetch(`/topic-locking/courses/${courseId}/unlock-all`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while unlocking the course topics.');
                });
            }
        }

        // Bulk operations
        function getSelectedTopics() {
            return Array.from(document.querySelectorAll('.topic-checkbox:checked')).map(cb => cb.value);
        }

        function bulkLock() {
            const topicIds = getSelectedTopics();
            if (topicIds.length === 0) {
                alert('Please select at least one topic.');
                return;
            }

            const reason = prompt('Enter a reason for locking these topics (optional):');
            if (reason !== null) {
                fetch('/topic-locking/bulk/lock', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ topic_ids: topicIds, reason: reason })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during bulk lock operation.');
                });
            }
        }

        function bulkUnlock() {
            const topicIds = getSelectedTopics();
            if (topicIds.length === 0) {
                alert('Please select at least one topic.');
                return;
            }

            if (confirm(`Are you sure you want to unlock ${topicIds.length} topics?`)) {
                fetch('/topic-locking/bulk/unlock', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ topic_ids: topicIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during bulk unlock operation.');
                });
            }
        }

        function selectAll() {
            document.querySelectorAll('.topic-checkbox').forEach(cb => cb.checked = true);
        }

        function selectNone() {
            document.querySelectorAll('.topic-checkbox').forEach(cb => cb.checked = false);
        }

        function viewTopicDetails(topicId) {
            // This could open a modal or navigate to a details page
            alert('Topic details functionality can be implemented here.');
        }
    </script>
</body>
</html>