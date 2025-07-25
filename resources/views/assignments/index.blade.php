<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - MedCapsule</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
</head>
<body>

<!-- Preloader -->
<div class="preloader" id="preloader">
    <div class="spinner"></div>
</div>

<!-- Minimal Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <a href="{{ route('home') }}" class="brand">MedCapsule</a>
        <a href="{{ route('home') }}" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
    </div>
</nav>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: #1e293b;
        line-height: 1.6;
        min-height: 100vh;
        scroll-behavior: smooth;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow-x: hidden;
    }
    
    /* Smooth scrolling optimization */
    html {
        scroll-behavior: smooth;
    }
    
    /* Performance optimizations */
    * {
        will-change: auto;
    }
    
    .assignment-card, .stat-card, .filters {
        will-change: transform;
        backface-visibility: hidden;
        transform: translateZ(0);
    }
    
    /* Navigation */
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 0.75rem 0;
    }
    
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: #3b82f6;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .brand:hover {
        color: #2563eb;
        transform: translateY(-1px);
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .nav-link:hover {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }
    
    /* Main Content */
    .main-content {
        margin-top: 80px;
        padding: 2rem 1.5rem;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    
    .header-actions {
        display: flex;
        gap: 0.75rem;
    }
    
    /* Premium Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        line-height: 1.4;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .btn-primary:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
        text-decoration: none;
    }
    
    .btn-success {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .btn-success:hover {
        background: #059669;
        border-color: #059669;
        color: white;
        text-decoration: none;
    }
    
    .btn-outline {
        background: white;
        color: #6b7280;
        border-color: #d1d5db;
    }
    
    .btn-outline:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #374151;
        text-decoration: none;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.25rem;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .stat-overdue .stat-number { color: #ef4444; }
    .stat-completed .stat-number { color: #10b981; }
    
    /* Ultra-slim filters */
    .filters {
        background: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        margin-bottom: 1.5rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .filters:hover {
        background: white;
        border-color: #3b82f6;
        transform: translateY(-1px);
    }
    
    .filter-grid {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        white-space: nowrap;
    }
    
    .form-select {
        padding: 0.375rem 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        background: white;
        font-size: 0.8rem;
        min-width: 120px;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        transform: scale(1.02);
    }
    
    .filter-actions {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }
    
    /* Assignment Grid */
    .assignment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }
    
    .assignment-card {
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
    }
    
    .assignment-card:nth-child(1) { animation-delay: 0.1s; }
    .assignment-card:nth-child(2) { animation-delay: 0.2s; }
    .assignment-card:nth-child(3) { animation-delay: 0.3s; }
    .assignment-card:nth-child(4) { animation-delay: 0.4s; }
    .assignment-card:nth-child(5) { animation-delay: 0.5s; }
    .assignment-card:nth-child(6) { animation-delay: 0.6s; }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .assignment-card:hover {
        transform: translateY(-4px) scale(1.02);
        background: white;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    /* Smooth hover-based colored borders */
    .assignment-card[data-status="active"]:hover {
        border-color: #3b82f6;
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
    }
    
    .assignment-card[data-status="overdue"]:hover {
        border-color: #ef4444;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.15);
    }
    
    .assignment-card[data-status="completed"]:hover {
        border-color: #10b981;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
    }
    
    .assignment-card[data-status="locked"]:hover {
        border-color: #6b7280;
        box-shadow: 0 8px 25px rgba(107, 114, 128, 0.15);
    }
    
    .card-content {
        padding: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    /* Premium Card Header */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .title-section {
        flex: 1;
    }
    
    .assignment-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 0.5rem 0;
        line-height: 1.3;
    }
    
    .assignment-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: #64748b;
    }
    
    .student-name {
        font-weight: 500;
        color: #374151;
    }
    
    .meta-separator {
        color: #cbd5e1;
    }
    
    .course-name {
        color: #64748b;
    }
    
    /* Status Indicator */
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 0.25rem;
    }
    
    .status-indicator.status-active { background: #3b82f6; }
    .status-indicator.status-overdue { background: #ef4444; }
    .status-indicator.status-completed { background: #10b981; }
    .status-indicator.status-locked { background: #6b7280; }
    
    /* Card Body */
    .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .topic-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: white;
        border-radius: 6px;
        border-left: 3px solid #e2e8f0;
    }
    
    .topic-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .topic-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .deadline-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .deadline-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .deadline-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .deadline-date {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .time-remaining {
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        background: white;
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    
    .time-remaining.overdue {
        background: white;
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .time-remaining.completed {
        background: white;
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    

    
    .card-actions {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(226, 232, 240, 0.6);
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .action-btn:hover {
        text-decoration: none;
        transform: translateY(-2px) scale(1.1);
    }
    
    .edit-btn {
        background: white;
        color: #6b7280;
        border: 1px solid #e5e7eb;
    }
    
    .edit-btn:hover {
        background: white;
        color: #6b7280;
        border-color: #6b7280;
        box-shadow: 0 2px 8px rgba(107, 114, 128, 0.2);
    }
    
    .delete-btn {
        background: white;
        color: #ef4444;
        border: 1px solid #e5e7eb;
    }
    
    .delete-btn:hover {
        background: white;
        color: #ef4444;
        border-color: #ef4444;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
        grid-column: 1 / -1;
    }
    
    .empty-icon {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }
    
    .empty-text {
        margin-bottom: 2rem;
    }
    
    /* Smooth scrolling for all elements */
    * {
        scroll-behavior: smooth;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: rgba(226, 232, 240, 0.3);
    }
    
    ::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.3);
        border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.5);
    }
    
    /* Responsive Design with smooth transitions */
    @media (max-width: 768px) {
        .main-content {
            padding: 1rem;
        }
        
        .page-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .header-actions {
            justify-content: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .filter-grid {
            flex-direction: column;
            gap: 0.75rem;
            align-items: stretch;
        }
        
        .filter-item {
            justify-content: space-between;
        }
        
        .filter-actions {
            margin-left: 0;
            justify-content: center;
        }
        
        .assignment-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .card-actions {
            justify-content: center;
        }
        
        .nav-link span {
            display: none;
        }
        
        .assignment-card {
            animation-delay: 0.1s !important;
        }
    }
    
    @media (max-width: 480px) {
        .nav-container {
            padding: 0 1rem;
        }
        
        .brand {
            font-size: 1.25rem;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .filters {
            padding: 0.5rem 0.75rem;
        }
        
        .filter-label {
            font-size: 0.75rem;
        }
        
        .form-select {
            min-width: 100px;
            font-size: 0.75rem;
        }
    }
    
    /* Preloader styles */
    .preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
    }
    
    .preloader.hidden {
        opacity: 0;
        pointer-events: none;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(59, 130, 246, 0.1);
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Assignments</h1>
        <div class="header-actions">
            <button type="button" class="btn btn-outline btn-sm" onclick="updateOverdueAssignments()" title="Sync overdue status">
                <i class="fas fa-sync"></i>
            </button>
            <a href="{{ route('assignments.analytics') }}" class="btn btn-outline btn-sm" title="View analytics">
                <i class="fas fa-chart-bar"></i>
            </a>
            <a href="{{ route('assignments.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i>
                <span>New Assignment</span>
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $assignments->where('status', 'active')->count() }}</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card stat-overdue">
            <div class="stat-number">{{ $assignments->where('status', 'overdue')->count() }}</div>
            <div class="stat-label">Overdue</div>
        </div>
        <div class="stat-card stat-completed">
            <div class="stat-number">{{ $assignments->where('status', 'completed')->count() }}</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $assignments->count() }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <!-- Ultra-slim Filters -->
    <div class="filters">
        <form method="GET" action="{{ route('assignments.index') }}">
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
                    <a href="{{ route('assignments.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Assignments -->
    <div class="assignment-grid">
        @forelse($assignments as $assignment)
            <div class="assignment-card" data-status="{{ $assignment->status }}">
                <div class="card-content">
                    <!-- Header with title and status -->
                    <div class="card-header">
                        <div class="title-section">
                            <h3 class="assignment-title">{{ $assignment->title }}</h3>
                            <div class="assignment-meta">
                                <span class="student-name">{{ $assignment->student->name }}</span>
                                <span class="meta-separator">•</span>
                                <span class="course-name">{{ $assignment->course->name }}</span>
                            </div>
                        </div>
                        <span class="status-indicator status-{{ $assignment->status }}"></span>
                    </div>
                    
                    <!-- Topic and deadline info -->
                    <div class="card-body">
                        <div class="topic-section">
                            <span class="topic-label">Topic</span>
                            <span class="topic-name">{{ $assignment->topic->name }}</span>
                        </div>
                        
                        <div class="deadline-section">
                            <div class="deadline-info">
                                <span class="deadline-label">Due</span>
                                <span class="deadline-date">{{ $assignment->deadline->format('M j, g:i A') }}</span>
                            </div>
                            @if($assignment->status !== 'completed')
                                <div class="time-remaining {{ $assignment->isOverdue() ? 'overdue' : '' }}">
                                    {{ $assignment->getTimeRemainingHuman() }}
                                </div>
                            @elseif($assignment->score)
                                <div class="time-remaining completed">
                                    {{ $assignment->score }}%
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    @if($assignment->status !== 'completed')
                        <div class="card-actions">
                            <a href="{{ route('assignments.edit', $assignment) }}" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="action-btn delete-btn" onclick="deleteAssignment({{ $assignment->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    @endif
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



<script>
// Performance optimizations and smooth effects
document.addEventListener('DOMContentLoaded', function() {
    // Hide preloader
    const preloader = document.getElementById('preloader');
    setTimeout(() => {
        preloader.classList.add('hidden');
        setTimeout(() => preloader.remove(), 500);
    }, 300);
    
    // Auto-submit on select change for instant filtering
    const selects = document.querySelectorAll('.form-select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            form.submit();
        });
    });
    
    // Intersection Observer for smooth card animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all cards for smooth scroll animations
    document.querySelectorAll('.assignment-card, .stat-card').forEach(card => {
        observer.observe(card);
    });
    
    // Smooth scroll to top on page load
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Smooth delete with animation
function deleteAssignment(assignmentId) {
    if (confirm('Delete this assignment?')) {
        const card = document.querySelector(`[onclick="deleteAssignment(${assignmentId})"]`).closest('.assignment-card');
        card.style.transform = 'scale(0.95)';
        card.style.opacity = '0.5';
        
        fetch(`/assignments/${assignmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                card.style.transform = 'scale(0)';
                setTimeout(() => location.reload(), 300);
            } else {
                card.style.transform = 'scale(1)';
                card.style.opacity = '1';
                alert('Error: ' + data.message);
            }
        })
        .catch(() => {
            card.style.transform = 'scale(1)';
            card.style.opacity = '1';
            alert('An error occurred');
        });
    }
}

function updateOverdueAssignments() {
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('/assignments/update-overdue', {
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
            btn.innerHTML = originalHTML;
            alert('Error: ' + data.message);
        }
    })
    .catch(() => {
        btn.innerHTML = originalHTML;
        alert('An error occurred');
    });
}
</script>

</body>
</html>