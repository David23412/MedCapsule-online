<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - MedCapsule</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/assignments-page.css') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
</head>
<body>

<!-- LIGHTNING-FAST FOUC ELIMINATOR PRELOADER -->
<div id="lightningPreloader" class="lightning-preloader">
    <div class="preloader-container">
        <div class="medcapsule-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <div class="preloader-brand">MedCapsule</div>
        <div class="preloader-subtitle">Loading...</div>
    </div>
</div>


<script>
// LIGHTNING-FAST PRELOADER CONTROLLER
(function() {
    'use strict';
    
    let preloaderHidden = false;
    
    function hidePreloader() {
        if (preloaderHidden) return;
        
        const preloader = document.getElementById('lightningPreloader');
        if (preloader) {
            preloaderHidden = true;
            preloader.classList.add('hidden');
            
            // Remove from DOM after animation for memory optimization
            setTimeout(() => {
                if (preloader.parentNode) {
                    preloader.parentNode.removeChild(preloader);
                }
            }, 200);
        }
    }
    
    // Hide preloader when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hidePreloader);
    } else {
        // DOM already loaded
        setTimeout(hidePreloader, 50);
    }
    
    // Fallback timeout - never show preloader for more than 1 second
    setTimeout(hidePreloader, 1000);
    
    // Hide on window load as backup
    window.addEventListener('load', hidePreloader);
})();
</script>

<!-- 👑 ADMIN SUPERIOR PRIVILEGES: User role tracking -->
<script>
    window.userRole = '{{ auth()->user()->role ?? "guest" }}';
    window.isAdmin = {{ auth()->check() && auth()->user()->role === 'admin' ? 'true' : 'false' }};
    console.log('👑 User role loaded:', window.userRole);
    if (window.isAdmin) {
        console.log('👑 Admin superior privileges active');
    }
</script>

<!-- Minimal Navigation -->
<nav class="navbar" style="opacity: 1; transition: opacity 0.3s ease;">
    <div class="nav-container">
        <a href="{{ route('courses.index') }}" class="brand">MedCapsule</a>
        <a href="{{ route('courses.index') }}" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
    </div>
</nav>

</body>
<div class="main-content">
    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="animation: slideInDown 0.5s ease;">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            @if(session('created_count'))
                <div class="mt-1">
                    <small class="text-success-emphasis">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ session('created_count') }} assignment{{ session('created_count') > 1 ? 's' : '' }} created successfully
                    </small>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Assignments</h1>
        <div class="header-actions">
            <!-- View Toggle Button -->
            <button type="button" class="btn btn-outline btn-sm" id="viewToggle" title="Toggle View">
                <i class="fas fa-th-large" id="gridIcon"></i>
                <i class="fas fa-list" id="listIcon" style="display: none;"></i>
                <span id="viewText">Grid</span>
            </button>
            

            <a href="{{ route('assignments.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i>
                <span>New Assignment</span>
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card stat-active" data-stat="active">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'active'; })->count() }}</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card stat-overdue" data-stat="overdue">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'overdue'; })->count() }}</div>
            <div class="stat-label">Overdue</div>
        </div>
        <div class="stat-card stat-completed" data-stat="completed">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'completed'; })->count() }}</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card" data-stat="total">
            <div class="stat-number">{{ $assignments->count() }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <!-- Ultra-slim Filters -->
    <div class="filters">
        <form method="GET" action="{{ route('assignments.index') }}" id="assignmentFiltersForm">
            <div class="filter-grid">
                <div class="filter-item">
                    <span class="filter-label">Status:</span>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Course:</span>
                    <select name="course_id" class="form-select">
                        <option value="">All</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Student:</span>
                    <select name="student_id" class="form-select">
                        <option value="">All</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline btn-sm assignment-filters-reset">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Assignments -->
    <div class="assignment-grid assignments-container">
        @forelse($assignments as $assignment)
            <div class="assignment-card" data-status="{{ $assignment->status_string }}" data-assignment-id="{{ $assignment->id }}">
                <div class="card-content">
                    <!-- Clean Minimal Header -->
                    <div class="card-header">
                        <div class="title-section">
                            <h3 class="assignment-title">{{ $assignment->title }}</h3>
                            <div class="assignment-meta">
                                <span class="course-topic">{{ $assignment->course->name }} • {{ $assignment->topic->name }}</span>
                            </div>
                        </div>
                        <span class="status-indicator status-{{ $assignment->status_string }}"></span>
                    </div>

                    <!-- Clean Card Body -->
                    <div class="card-body">
                        <div class="deadline-section">
                            <div class="deadline-info">
                                <span class="deadline-label">Due</span>
                                <span class="deadline-date">{{ $assignment->deadline->format('M j, g:i A') }}</span>
                            </div>
                            @if($assignment->status_string !== 'completed')
                                <div class="time-remaining {{ $assignment->status_string === 'overdue' ? 'overdue' : '' }}">
                                    {{ $assignment->getTimeRemainingHuman() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    

                    
                    <!-- Clean Actions -->
                    <div class="card-actions">
                        <button type="button" class="action-btn primary-btn" onclick="openProgressModal({{ $assignment->id }})" title="View Student Progress">
                            <i class="fas fa-chart-line"></i>
                        </button>
                        @if($assignment->getCurrentStatusAttribute() !== 'completed')
                            <button type="button" class="action-btn edit-btn" onclick="console.log('Edit button clicked for assignment:', {{ $assignment->id }}); openEditModal({{ $assignment->id }})" title="Edit Assignment">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="action-btn delete-btn" onclick="deleteAssignment({{ $assignment->id }})" title="Delete Assignment">
                                <i class="fas fa-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="empty-title">No assignments found</h3>
                <p class="empty-text">Create your first assignment to get started with managing student work.</p>
                <a href="{{ route('assignments.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Create Assignment
                </a>
            </div>
        @endforelse
    </div>
</div>

<!-- Student Progress Modal -->
<div id="progressModal" class="modal-overlay" style="display: none;">
    <div class="modal-container progress-modal-container">
        <div class="modal-header-custom">
            <h3 class="modal-title">
                <i class="fas fa-chart-line"></i>
                Student Progress
            </h3>
            <button type="button" class="modal-close-btn" onclick="closeProgressModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body-custom">
            <div id="progressModalContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading student progress...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Edit Assignment Modal Functions - Define immediately to ensure availability
window.openEditModal = function(assignmentId) {
    console.log('openEditModal called with ID:', assignmentId);
    const modal = document.getElementById('editAssignmentModal');
    const modalContent = document.getElementById('editModalContent');
    
    if (!modal) {
        console.error('Modal element not found: editAssignmentModal');
        return;
    }
    if (!modalContent) {
        console.error('Modal content element not found: editModalContent');
        return;
    }
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="modal-loading">
            <div class="modal-loading-spinner"></div>
            <p>Loading assignment details...</p>
        </div>
    `;
    
    // Show modal with animation
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Load assignment data
    fetch(`/assignments/${assignmentId}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;

            // Convert hour dropdown to a number input
            const hourSelect = document.getElementById('deadline_hour');
            if (hourSelect) {
                const hourInput = document.createElement('input');
                hourInput.type = 'number';
                hourInput.id = 'deadline_hour';
                hourInput.name = 'deadline_hour';
                hourInput.className = 'form-control modal-form-control';
                hourInput.value = hourSelect.value;
                hourInput.min = '1';
                hourInput.max = '12';
                hourInput.required = true;
                hourSelect.parentNode.replaceChild(hourInput, hourSelect);
            }

            initializeEditForm(assignmentId);
        })
        .catch(error => {
            console.error('Error loading assignment:', error);
            modalContent.innerHTML = `
                <div class="modal-alert modal-alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading assignment details. Please try again.
                </div>
            `;
        });
}

window.closeEditModal = function() {
    const modal = document.getElementById('editAssignmentModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function initializeEditForm(assignmentId) {
    const form = document.getElementById('editAssignmentForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        submitBtn.disabled = true;
        
        // Submit form
        fetch(`/assignments/${assignmentId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showModalAlert('Assignment updated successfully!', 'success');
                closeEditModal();
                refreshAssignmentsIndex(window.location.href, { pushState: false, preserveScroll: true })
                    .catch(error => {
                        console.error('Failed to refresh assignments after update:', error);
                        showModalAlert('Assignment updated, but the page did not refresh cleanly.', 'error');
                    });
            } else {
                showModalAlert(data.message || 'Error updating assignment', 'error');
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModalAlert('An error occurred while updating the assignment', 'error');
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        });
    });
    
    // Auto-focus first input for better UX
    const firstInput = document.querySelector('#editAssignmentForm input, #editAssignmentForm select');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
}

function showModalAlert(message, type) {
    const modalBody = document.querySelector('#editAssignmentModal .modal-body-custom');
    const alertDiv = document.createElement('div');
    alertDiv.className = `modal-alert modal-alert-${type === 'success' ? 'success' : 'error'}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    // Insert at the top of modal body
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

const assignmentsIndexUrl = @json(route('assignments.index'));
let assignmentCardObserver = null;

function setAssignmentViewMode(mode) {
    const assignmentGrid = document.querySelector('.assignment-grid');
    const gridIcon = document.getElementById('gridIcon');
    const listIcon = document.getElementById('listIcon');
    const viewText = document.getElementById('viewText');

    if (!assignmentGrid || !gridIcon || !listIcon || !viewText) {
        return;
    }

    const isStacked = mode === 'stacked';
    assignmentGrid.classList.toggle('stacked-view', isStacked);
    gridIcon.style.display = isStacked ? 'none' : 'inline';
    listIcon.style.display = isStacked ? 'inline' : 'none';
    viewText.textContent = isStacked ? 'List' : 'Grid';
    localStorage.setItem('assignmentViewMode', isStacked ? 'stacked' : 'grid');
}

function applySavedAssignmentView() {
    setAssignmentViewMode(localStorage.getItem('assignmentViewMode') || 'grid');
}

function initializeViewToggle() {
    const viewToggle = document.getElementById('viewToggle');
    if (!viewToggle || viewToggle.dataset.bound === 'true') {
        return;
    }

    viewToggle.dataset.bound = 'true';
    viewToggle.addEventListener('click', function() {
        const assignmentGrid = document.querySelector('.assignment-grid');
        const nextMode = assignmentGrid && assignmentGrid.classList.contains('stacked-view')
            ? 'grid'
            : 'stacked';

        setAssignmentViewMode(nextMode);

        if (assignmentGrid) {
            assignmentGrid.style.opacity = '0.8';
            setTimeout(() => {
                assignmentGrid.style.opacity = '1';
            }, 150);
        }
    });
}

function observeAssignmentCards() {
    if (assignmentCardObserver) {
        assignmentCardObserver.disconnect();
    }

    assignmentCardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    document.querySelectorAll('.assignment-card, .stat-card').forEach(card => {
        assignmentCardObserver.observe(card);
    });
}

function getTopLevelAlert(container) {
    if (!container) {
        return null;
    }

    return Array.from(container.children).find(child =>
        child.classList && child.classList.contains('alert')
    ) || null;
}

function setFiltersLoading(form, isLoading) {
    if (!form) {
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) {
        return;
    }

    if (isLoading) {
        if (!submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
        }
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        submitBtn.disabled = true;
        return;
    }

    submitBtn.innerHTML = submitBtn.dataset.originalHtml || submitBtn.innerHTML;
    submitBtn.disabled = false;
}

function buildAssignmentsFilterUrl(form) {
    const url = new URL(form?.action || assignmentsIndexUrl, window.location.origin);
    url.search = '';

    if (!form) {
        return url;
    }

    const formData = new FormData(form);
    for (const [key, value] of formData.entries()) {
        const normalizedValue = String(value).trim();
        if (normalizedValue !== '') {
            url.searchParams.append(key, normalizedValue);
        }
    }

    return url;
}

function initializeAssignmentFilters() {
    const filtersForm = document.getElementById('assignmentFiltersForm');
    if (!filtersForm || filtersForm.dataset.bound === 'true') {
        return;
    }

    filtersForm.dataset.bound = 'true';

    filtersForm.querySelectorAll('.form-select').forEach(select => {
        select.addEventListener('change', function() {
            const nextUrl = buildAssignmentsFilterUrl(filtersForm);
            refreshAssignmentsIndex(nextUrl, { pushState: true, preserveScroll: true });
        });
    });

    const resetLink = filtersForm.querySelector('.assignment-filters-reset');
    if (resetLink) {
        resetLink.addEventListener('click', function(event) {
            event.preventDefault();
            refreshAssignmentsIndex(assignmentsIndexUrl, { pushState: true, preserveScroll: false });
        });
    }
}

function initializeAssignmentsPageUI(options = {}) {
    initializeViewToggle();
    applySavedAssignmentView();
    initializeAssignmentFilters();
    observeAssignmentCards();

    if (options.scrollToTop) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

async function refreshAssignmentsIndex(nextUrl = window.location.href, options = {}) {
    const { pushState = false, preserveScroll = true } = options;
    const currentScrollY = window.scrollY;
    const form = document.getElementById('assignmentFiltersForm');
    const requestUrl = nextUrl instanceof URL ? nextUrl : new URL(nextUrl, window.location.origin);

    setFiltersLoading(form, true);

    try {
        const response = await fetch(requestUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Failed to refresh assignments: ${response.status}`);
        }

        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const parsedMainContent = parsed.querySelector('.main-content');
        const currentMainContent = document.querySelector('.main-content');

        if (!parsedMainContent || !currentMainContent) {
            throw new Error('Unable to parse refreshed assignments content');
        }

        const sections = ['.stats-grid', '.filters', '.assignment-grid.assignments-container'];
        sections.forEach(selector => {
            const currentSection = document.querySelector(selector);
            const newSection = parsed.querySelector(selector);

            if (currentSection && newSection) {
                currentSection.replaceWith(newSection.cloneNode(true));
            }
        });

        const currentAlert = getTopLevelAlert(currentMainContent);
        if (currentAlert) {
            currentAlert.remove();
        }

        const newAlert = getTopLevelAlert(parsedMainContent);
        const pageHeader = document.querySelector('.page-header');
        if (newAlert && pageHeader) {
            pageHeader.before(newAlert.cloneNode(true));
        }

        if (pushState) {
            window.history.pushState({}, '', requestUrl.toString());
        }

        initializeAssignmentsPageUI();

        if (preserveScroll) {
            window.scrollTo({ top: currentScrollY });
        }
    } finally {
        setFiltersLoading(document.getElementById('assignmentFiltersForm'), false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');

    document.addEventListener('beforeunload', function() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.remove('hidden');
        }

        if (navbar) {
            navbar.style.opacity = '0';
        }
    });

    initializeAssignmentsPageUI({ scrollToTop: true });
});

window.addEventListener('popstate', function() {
    refreshAssignmentsIndex(window.location.href, { pushState: false, preserveScroll: false }).catch(error => {
        console.error('Failed to restore assignments state from history:', error);
    });
});

class SimpleAssignmentManager {
    animateRemoval(card) {
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'scale(0.95)';
        card.style.opacity = '0';
        setTimeout(() => card.remove(), 300);
    }

    showMessage(text, type) {
        const message = document.createElement('div');
        message.textContent = text;
        message.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 6px;
            font-weight: 500;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(message);
        setTimeout(() => message.remove(), 3000);
    }

    async deleteAssignment(assignmentId) {
        const card = document.querySelector(`[data-assignment-id="${assignmentId}"]`);

        if (card) {
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
        }

        try {
            const response = await fetch(`/assignments/${assignmentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to delete assignment');
            }

            if (card) {
                this.animateRemoval(card);
            }

            this.showMessage('Deleted', 'success');
            await refreshAssignmentsIndex(window.location.href, { pushState: false, preserveScroll: true });
            return true;
        } catch (error) {
            console.error('Delete failed:', error);

            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }

            this.showMessage('Delete failed', 'error');
            return false;
        }
    }
}

const assignmentManager = new SimpleAssignmentManager();

// Open progress modal
window.openProgressModal = function(assignmentId) {
    const modal = document.getElementById('progressModal');
    const content = document.getElementById('progressModalContent');
    
    if (!modal) {
        console.error('Progress modal not found');
        return;
    }
    
    // Show modal
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
    
    // Load progress data - for now, let's create a static version
    // Later you can replace this with an actual API call
    loadStaticProgressData(assignmentId, content);
}

// Load real progress data from server
function loadStaticProgressData(assignmentId, content) {
    // Make an AJAX call to get the real assignment data
    fetch(`/api/assignments/${assignmentId}/progress`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displayProgressData(data.assignment, data.students, content);
        } else {
            content.innerHTML = '<div class="no-students-message"><i class="fas fa-exclamation-triangle"></i><h3>Error Loading Data</h3><p>' + (data.message || 'Failed to load progress data') + '</p></div>';
        }
    })
    .catch(error => {
        console.error('Error loading progress:', error);
        content.innerHTML = '<div class="no-students-message"><i class="fas fa-exclamation-triangle"></i><h3>Failed to Load Data</h3><p>Error: ' + error.message + '</p></div>';
    });
}



// Display the progress data in the modal
function displayProgressData(assignment, students, content) {
    const totalStudents = students.length;
    const completedStudents = students.filter(s => s.status === 'completed').length;
    const progressPercentage = totalStudents > 0 ? Math.round((completedStudents / totalStudents) * 100) : 0;

    let studentsHTML = '';

    if (students.length === 0) {
        studentsHTML = `
            <div class="no-students-message">
                <i class="fas fa-users"></i>
                <h3>No Students Assigned</h3>
                <p>This assignment has no students assigned to it.</p>
            </div>
        `;
    } else {
        studentsHTML = students.map(student => {
            const initials = student.name.split(' ').map(n => n[0]).join('').toUpperCase();
            const status = student.status_string || student.status || 'not_started';
            const hasCompleted = student.completed_at || status === 'completed';
            let statusText = '';
            let statusClass = '';
            let scoreDisplay = '';

            // Determine status and display logic - STUDENT PROGRESS TAKES PRECEDENCE
            if (hasCompleted) {
                // Student completed the assignment - show their actual score regardless of assignment deadline
                statusText = 'Completed';
                statusClass = 'status-completed';
                scoreDisplay = student.score ? `<span class="progress-score-modal">${student.score}%</span>` : '<span class="progress-score-modal">0%</span>';
            } else if (status === 'overdue' || status === 'not_started') {
                // Assignment is inactive (overdue or not started) - student hasn't completed it
                statusText = 'Inactive';
                statusClass = 'status-overdue';
                scoreDisplay = '<span class="progress-score-modal">0%</span>';
            } else if (status === 'active' || status === 'in_progress') {
                // Assignment is still active and student is working on it
                statusText = 'Active';
                statusClass = 'status-active';
                scoreDisplay = ''; // No percentage for active assignments
            } else {
                // Fallback for any other status - treat as inactive
                statusText = 'Inactive';
                statusClass = 'status-overdue';
                scoreDisplay = '<span class="progress-score-modal">0%</span>';
            }

            return `
                <div class="student-card">
                    <div class="student-info-modal">
                        <div class="student-avatar-modal">${initials}</div>
                        <div class="student-details-modal">
                            <h5 class="student-name-modal">${student.name}</h5>
                            <p class="student-email-modal">${student.email}</p>
                        </div>
                    </div>
                    <div class="student-progress-modal">
                        <span class="progress-status-modal ${statusClass}">${statusText}</span>
                        ${scoreDisplay}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    const progressHTML = `
        <div class="progress-content">
            <div class="progress-header">
                <div class="assignment-info">
                    <h4 class="assignment-title-modal">${assignment.title || 'Assignment Progress'}</h4>
                    <p class="assignment-meta-modal">${assignment.course || 'Course'}</p>
                </div>
                <div class="progress-stats-modal">
                    <div class="stat-item-modal">
                        <span class="stat-number-modal">${totalStudents}</span>
                        <span class="stat-label-modal">Total</span>
                    </div>
                    <div class="stat-item-modal">
                        <span class="stat-number-modal">${completedStudents}</span>
                        <span class="stat-label-modal">Completed</span>
                    </div>
                    <div class="stat-item-modal">
                        <span class="stat-number-modal">${progressPercentage}%</span>
                        <span class="stat-label-modal">Progress</span>
                    </div>
                </div>
            </div>
            <div class="students-grid">
                ${studentsHTML}
            </div>
        </div>
    `;
    
    content.innerHTML = progressHTML;
}



// Close progress modal
window.closeProgressModal = function() {
    const modal = document.getElementById('progressModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

// Simple delete function - NO CONFIRMATION POPUPS
window.deleteAssignment = function(assignmentId) {
    assignmentManager.deleteAssignment(assignmentId);
}

function forceRefreshAssignments() {
    refreshAssignmentsIndex(window.location.href, { pushState: false, preserveScroll: true }).catch(error => {
        console.error('Manual assignments refresh failed:', error);
    });
}

// Add CSS animations
const style = document.createElement("style");
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .assignment-card {
        transition: all 0.3s ease;
    }
    
    .assignment-card.deleting {
        transform: scale(0.95);
        opacity: 0.5;
        pointer-events: none;
    }
`;
document.head.appendChild(style);

console.log("🚀 Smart Assignment Caching System loaded");
console.log("🔧 Testing function availability:");
console.log("openEditModal available:", typeof window.openEditModal);
console.log("openProgressModal available:", typeof window.openProgressModal);
console.log("deleteAssignment available:", typeof window.deleteAssignment);

window.updateOverdueAssignments = function() {
    enforceRealTimeStatus();
}

function enforceRealTimeStatus(assignmentId = null, triggerElement = null) {
    const btn = triggerElement || (typeof event !== 'undefined' ? event.target : null);
    const originalHTML = btn ? btn.innerHTML : null;

    if (btn) {
        btn.innerHTML = '<i class="fas fa-refresh fa-spin"></i>';
        btn.disabled = true;
    }
    
    const payload = assignmentId ? { assignment_id: assignmentId } : {};
    
    fetch('/assignments/enforce-real-time-status', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check text-success"></i> UPDATED!';
            }

            return refreshAssignmentsIndex(window.location.href, { pushState: false, preserveScroll: true });
        } else {
            if (btn) {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        if (btn) {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
        console.error('Real-time enforcement error:', error);
        alert('An error occurred during real-time status enforcement');
    });
}
</script>




<!-- Edit Assignment Modal -->
<div id="editAssignmentModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-content-wrapper">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">
                    <i class="fas fa-edit"></i>
                    Edit Assignment
                </h5>
                <button type="button" class="modal-close-btn" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-custom">
                <div id="editModalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal event handlers - moved to earlier script section to avoid duplication
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('editAssignmentModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeEditModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeEditModal();
            }
        });
    }
});
</script>

</body>
</html>
