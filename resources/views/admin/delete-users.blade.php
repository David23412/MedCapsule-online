@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Delete Users
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
                        <p class="mb-0">Deleting a user will permanently remove:</p>
                        <ul class="mb-0">
                            <li>All their quiz attempts and progress</li>
                            <li>All their mistakes and learning data</li>
                            <li>All their payment records</li>
                            <li>All their study sessions and statistics</li>
                            <li>All their course enrollments</li>
                        </ul>
                    </div>

                    <!-- Search and Filters -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Search Users</label>
                            <form method="GET" class="d-flex">
                                <input type="text" 
                                       class="form-control me-2" 
                                       name="search" 
                                       placeholder="Search by name or email..." 
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Role</label>
                            <select class="form-select" onchange="window.location.href=this.value">
                                <option value="{{ route('admin.delete.users') }}" {{ !request('is_admin') ? 'selected' : '' }}>
                                    All Users
                                </option>
                                <option value="{{ route('admin.delete.users', ['is_admin' => 1]) }}" {{ request('is_admin') == 1 ? 'selected' : '' }}>
                                    Admins Only
                                </option>
                                <option value="{{ route('admin.delete.users', ['is_admin' => 0]) }}" {{ request('is_admin') == 0 ? 'selected' : '' }}>
                                    Regular Users Only
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <a href="{{ route('admin.delete.users') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-refresh me-1"></i>Clear Filters
                            </a>
                        </div>
                    </div>

                    @if($users->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No users found</h4>
                            <p class="text-muted">No users match your current filters.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Quiz Attempts</th>
                                        <th>Mistakes</th>
                                        <th>Payments</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($user->profile_picture_url)
                                                        <img src="{{ $user->profile_picture_url }}" 
                                                             alt="{{ $user->name }}" 
                                                             class="rounded-circle me-2" 
                                                             style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $user->name }}</strong>
                                                        @if($user->university)
                                                            <br>
                                                            <small class="text-muted">{{ $user->university }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $user->email }}</span>
                                            </td>
                                            <td>
                                                @if($user->is_admin)
                                                    <span class="badge bg-danger">Admin</span>
                                                @else
                                                    <span class="badge bg-secondary">User</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $user->quiz_attempts_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $user->mistakes_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $user->payments_count }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                            </td>
                                            <td>
                                                @if($user->id === auth()->id())
                                                    <span class="text-muted">Current User</span>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm"
                                                            onclick="showDeleteConfirmation({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', {{ $user->quiz_attempts_count }}, {{ $user->mistakes_count }}, {{ $user->payments_count }})">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $users->appends(request()->query())->links() }}
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
                    Confirm User Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone!</h6>
                    <p class="mb-0">You are about to permanently delete the user "<strong id="userName"></strong>" (<strong id="userEmail"></strong>) and all their associated data.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>What will be deleted:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Quiz Attempts
                                <span class="badge bg-primary" id="attemptsCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Mistakes
                                <span class="badge bg-warning" id="mistakesCount">0</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Payments
                                <span class="badge bg-success" id="paymentsCount">0</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Additional data that will be removed:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">All progress tracking</li>
                            <li class="list-group-item">All study sessions</li>
                            <li class="list-group-item">All spaced repetition data</li>
                            <li class="list-group-item">All course enrollments</li>
                            <li class="list-group-item">All user statistics</li>
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
                        <i class="fas fa-trash me-1"></i>Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteConfirmation(userId, userName, userEmail, attemptsCount, mistakesCount, paymentsCount) {
    // Update modal content
    document.getElementById('userName').textContent = userName;
    document.getElementById('userEmail').textContent = userEmail;
    document.getElementById('attemptsCount').textContent = attemptsCount;
    document.getElementById('mistakesCount').textContent = mistakesCount;
    document.getElementById('paymentsCount').textContent = paymentsCount;
    
    // Update form action
    document.getElementById('deleteForm').action = `/admin/delete/user/${userId}`;
    
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