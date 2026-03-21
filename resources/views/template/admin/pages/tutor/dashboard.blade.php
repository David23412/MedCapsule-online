@extends('template.admin.layouts.tutor')

@section('title', 'Dashboard')

@section('styles')
<style>
    /* Main Content */
    .main-content {
        margin-top: 0;
        padding: 0;
        min-height: auto;
    }

    /* Minimal Dashboard Header */
    .dashboard-header {
        background: white;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        border-left: 3px solid #3b82f6;
    }

    .dashboard-title {
        color: #1f2937;
        font-weight: 600;
        margin: 0;
        font-size: 1.5rem;
    }

    .dashboard-subtitle {
        color: #6b7280;
        margin: 0.25rem 0 0 0;
        font-size: 0.875rem;
    }

    /* Minimal Filters */
    .filters-section {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }

    .filter-group {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-input {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        min-width: 200px;
    }

    .filter-select {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        min-width: 150px;
    }

    /* Minimal White Student Cards */
    .student-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        cursor: pointer;
        border: 1px solid #e5e7eb;
        height: 100%;
    }

    .student-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #d1d5db;
        border-left: 3px solid #3b82f6;
    }

    .student-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .student-avatar {
        margin-right: 0.75rem;
    }

    .avatar-img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }

    .avatar-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 500;
        font-size: 14px;
    }

    .student-name {
        margin: 0;
        color: #1e293b;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .student-status {
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-right: 4px;
    }

    .status-dot.active {
        background-color: #10b981;
    }

    .status-dot.inactive {
        background-color: #ef4444;
    }

    /* Student Stats */
    .student-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.75rem;
        margin: 0.75rem 0;
        padding: 0.75rem 0;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }

    .stat-item {
        text-align: center;
    }

    .stat-label {
        font-size: 0.65rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 500;
        margin-bottom: 0.25rem;
        letter-spacing: 0.025em;
    }

    .stat-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
    }

    .stat-value.excellent { color: #10b981; }
    .stat-value.good { color: #06b6d4; }
    .stat-value.average { color: #f59e0b; }
    .stat-value.poor { color: #ef4444; }

    /* Progress Indicator */
    .student-progress {
        margin-bottom: 0.75rem;
    }

    .progress-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .progress-label {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 500;
    }

    .trend {
        font-size: 0.65rem;
        font-weight: 500;
        padding: 0.15rem 0.4rem;
        border-radius: 6px;
    }

    .trend.improving {
        background-color: #dcfce7;
        color: #166534;
    }

    .trend.declining {
        background-color: #fecaca;
        color: #991b1b;
    }

    .trend.stable {
        background-color: #f1f5f9;
        color: #475569;
    }

    /* Student Actions */
    .student-actions {
        display: flex;
        gap: 0.5rem;
    }

    .student-actions .btn {
        flex: 1;
        font-size: 0.7rem;
        padding: 0.35rem 0.6rem;
        border-radius: 4px;
        font-weight: 500;
    }

    .btn-sm-custom {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }

    /* Minimal Summary Card */
    .summary-card {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        border-top: 3px solid #6b7280;
    }

    .summary-item {
        text-align: center;
        padding: 0.5rem 0;
        border-right: 1px solid #f3f4f6;
    }

    .summary-item:last-child {
        border-right: none;
    }

    .summary-label {
        font-weight: 500;
        color: #6b7280;
        font-size: 0.8rem;
        display: block;
        margin-bottom: 0.25rem;
    }

    .summary-value {
        font-weight: 600;
        color: #1f2937;
        font-size: 1.25rem;
    }

    /* Premium Minimal White Stat Cards */
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        color: #374151;
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
        position: relative;
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    /* Minimal color accents - only subtle left border */
    .stat-card.stat-primary { border-left: 3px solid #3b82f6; }
    .stat-card.stat-success { border-left: 3px solid #10b981; }
    .stat-card.stat-info { border-left: 3px solid #06b6d4; }
    .stat-card.stat-warning { border-left: 3px solid #f59e0b; }

    .stat-icon {
        font-size: 1.5rem;
        margin-right: 1rem;
        color: #9ca3af;
        opacity: 0.8;
    }

    /* Subtle icon color hints */
    .stat-card.stat-primary .stat-icon { color: #3b82f6; opacity: 0.7; }
    .stat-card.stat-success .stat-icon { color: #10b981; opacity: 0.7; }
    .stat-card.stat-info .stat-icon { color: #06b6d4; opacity: 0.7; }
    .stat-card.stat-warning .stat-icon { color: #f59e0b; opacity: 0.7; }

    .stat-content h3 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.2;
    }

    .stat-content p {
        margin: 0;
        font-size: 0.875rem;
        color: #6b7280;
        font-weight: 500;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .student-stats {
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .student-actions {
            flex-direction: column;
        }

        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-input, .filter-select {
            min-width: auto;
        }
    }
</style>
@endsection

@section('tutor-content')
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="dashboard-title">
                        <i class="fas fa-chalkboard-teacher me-2"></i>
                        Welcome, {{ $tutor->name }}
                    </h2>
                    <p class="dashboard-subtitle">
                        <strong>{{ $students->count() }}</strong> students • 
                        <strong>{{ $analytics['active_students'] }}</strong> active • 
                        <strong>{{ number_format($analytics['average_score'], 1) }}%</strong> avg score
                    </p>
                    @if(auth()->user()->isAdmin())
                        <span class="badge bg-warning">
                            <i class="fas fa-crown me-1"></i>Admin View
                        </span>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group me-2" role="group">
                        <a href="{{ route('assignments.create') }}" class="btn btn-success btn-sm-custom">
                            <i class="fas fa-plus"></i> Create
                        </a>
                        <a href="{{ route('assignments.index') }}" class="btn btn-outline-success btn-sm-custom">
                            <i class="fas fa-clipboard-list"></i> Manage
                        </a>
                    </div>
                    <button class="btn btn-outline-primary btn-sm-custom me-2" onclick="exportGradeReports()">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                    <button class="btn btn-primary btn-sm-custom" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filter-group">
                <div>
                    <input type="text" id="studentSearch" class="filter-input" placeholder="Search students..." onkeyup="filterStudents()">
                </div>
                <div>
                    <select id="statusFilter" class="filter-select" onchange="filterStudents()">
                        <option value="">All Students</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div>
                    <select id="performanceFilter" class="filter-select" onchange="filterStudents()">
                        <option value="">All Performance</option>
                        <option value="excellent">Excellent (80%+)</option>
                        <option value="good">Good (70-79%)</option>
                        <option value="average">Average (60-69%)</option>
                        <option value="poor">Needs Help (<60%)</option>
                    </select>
                </div>
                <div>
                    <button class="btn btn-outline-secondary btn-sm-custom" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['total_students'] }}</h3>
                        <p>Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['active_students'] }}</h3>
                        <p>Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ number_format($analytics['average_score'], 1) }}%</h3>
                        <p>Average</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $analytics['struggling_students'] }}</h3>
                        <p>Need Help</p>
                    </div>
                </div>
            </div>
        </div>



        <!-- Students Grid -->
        <div class="row" id="studentsGrid">
            @forelse($students as $student)
            <div class="col-md-6 col-lg-4 col-xl-3 mb-3 student-item" 
                 data-name="{{ strtolower($student->name) }}"
                 data-status="{{ $student->last_active_at && $student->last_active_at->gte(now()->subDays(3)) ? 'active' : 'inactive' }}"
                 data-performance="{{ $student->enhanced_metrics['score_class'] }}">
                <div class="student-card" onclick="viewStudentProfile({{ $student->id }})">
                    <div class="student-header">
                        <div class="student-avatar">
                            @if($student->profile_picture_url)
                                <img src="{{ \App\Models\User::getProfileImageUrlFor($student) }}"
                                     alt="{{ htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') }}"
                                     class="avatar-img">
                            @else
                                <div class="avatar-placeholder">
                                    {{ htmlspecialchars(substr($student->name, 0, 1), ENT_QUOTES, 'UTF-8') }}
                                </div>
                            @endif
                        </div>
                        <div class="student-info">
                            <h5 class="student-name">{{ $student->name }}</h5>
                            <div class="student-status">
                                @php
                                    $lastActive = $student->last_active_at;
                                    $isActive = $lastActive && $lastActive->gte(now()->subDays(3));
                                @endphp
                                <span class="status-dot {{ $isActive ? 'active' : 'inactive' }}"></span>
                                {{ $isActive ? 'Active' : 'Inactive' }}
                                @if($lastActive)
                                    • {{ $lastActive->diffForHumans() }}
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="student-stats">
                        <div class="stat-item">
                            <div class="stat-label">Quiz Average</div>
                            @php
                                $avgScore = $student->enhanced_metrics['average_score'] ?? 0;
                                $scoreClass = $student->enhanced_metrics['score_class'] ?? 'poor';
                            @endphp
                            <div class="stat-value {{ $scoreClass }}">{{ number_format($avgScore, 1) }}%</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Total Quizzes</div>
                            <div class="stat-value">{{ $student->enhanced_metrics['total_quizzes'] ?? 0 }}</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Study Streak</div>
                            <div class="stat-value">{{ $student->study_streak_days ?? 0 }} days</div>
                        </div>
                    </div>

                    <div class="student-progress">
                        <div class="progress-indicator">
                            <span class="progress-label">Recent Performance</span>
                            @switch($student->enhanced_metrics['performance_trend'])
                                @case('improving')
                                    <span class="trend improving"><i class="fas fa-arrow-up"></i> Improving</span>
                                    @break
                                @case('declining')
                                    <span class="trend declining"><i class="fas fa-arrow-down"></i> Declining</span>
                                    @break
                                @default
                                    <span class="trend stable"><i class="fas fa-minus"></i> Stable</span>
                            @endswitch
                        </div>
                    </div>

                    <div class="student-actions">
                        <button class="btn btn-primary" onclick="event.stopPropagation(); viewStudentProfile({{ $student->id }})">
                            <i class="fas fa-user"></i> View Profile
                        </button>
                        <button class="btn btn-outline-primary" onclick="event.stopPropagation(); generateStudentReport({{ $student->id }})">
                            <i class="fas fa-file-alt"></i> Report
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4>No Students Assigned</h4>
                    <p class="text-muted">You don't have any students assigned yet. Contact your administrator to get students assigned to you.</p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Class Summary -->
        @if($students->count() > 0)
        <div class="row mt-3">
            <div class="col-12">
                <div class="summary-card">
                    <h6 class="mb-3">Class Summary</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="summary-item">
                                <span class="summary-label">Class Average</span>
                                <span class="summary-value">{{ number_format($students->avg(function($s) { return $s->enhanced_metrics['average_score'] ?? 0; }), 1) }}%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-item">
                                <span class="summary-label">Quiz Attempts</span>
                                <span class="summary-value">{{ $students->sum(function($s) { return $s->enhanced_metrics['total_quizzes'] ?? 0; }) }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-item">
                                <span class="summary-label">Active Students</span>
                                <span class="summary-value">{{ $students->filter(function($s) { return $s->last_active_at && $s->last_active_at->gte(now()->subDays(7)); })->count() }}/{{ $students->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
    // Student Profile Functions
    function viewStudentProfile(studentId) {
        window.location.href = `/tutor/student/${studentId}/profile`;
    }

    function generateStudentReport(studentId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/tutor/student/' + studentId + '/report';
        form.target = '_blank';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function exportGradeReports() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/tutor/reports/export';
        form.target = '_blank';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function refreshDashboard() {
        const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
        const originalText = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;

        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // Filtering Functions
    function filterStudents() {
        const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const performanceFilter = document.getElementById('performanceFilter').value;

        const studentItems = document.querySelectorAll('.student-item');
        let visibleCount = 0;

        studentItems.forEach(item => {
            const name = item.getAttribute('data-name');
            const status = item.getAttribute('data-status');
            const performance = item.getAttribute('data-performance');

            let showItem = true;

            // Search filter
            if (searchTerm && !name.includes(searchTerm)) {
                showItem = false;
            }

            // Status filter
            if (statusFilter && status !== statusFilter) {
                showItem = false;
            }

            // Performance filter
            if (performanceFilter && performance !== performanceFilter) {
                showItem = false;
            }

            if (showItem) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide empty state
        const emptyState = document.getElementById('emptyState');
        if (visibleCount === 0 && !emptyState) {
            const grid = document.getElementById('studentsGrid');
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'emptyState';
            emptyDiv.className = 'col-12';
            emptyDiv.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                    <h5>No students found</h5>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                </div>
            `;
            grid.appendChild(emptyDiv);
        } else if (visibleCount > 0 && emptyState) {
            emptyState.remove();
        }
    }

    function clearFilters() {
        document.getElementById('studentSearch').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('performanceFilter').value = '';
        filterStudents();
    }
</script>
@endsection
