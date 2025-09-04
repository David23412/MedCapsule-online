<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - MedCapsule</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- NUCLEAR CRITICAL CSS - LIGHTNING FAST FOUC PREVENTION -->
    <style>
        /* CRITICAL CSS - Above fold only - RUTHLESS OPTIMIZATION */
        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            opacity: 1 !important;
        }
        
        /* CRITICAL: Navbar - Above fold */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(90deg, #2A6DFF, #7B3FE4, #E63946);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        /* CRITICAL: Hero section - Above fold */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2196f3;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            padding: 15px 40px;
            font-size: 1.5rem;
            border-radius: 50px;
            background: #2196f3;
            border: none;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #1976d2;
        }
    </style>
    
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

<style>
/* LIGHTNING-FAST PRELOADER - ELIMINATES ALL FOUC */
.lightning-preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    will-change: opacity, visibility;
    backface-visibility: hidden;
    transform: translateZ(0);
}

.lightning-preloader.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.preloader-container {
    text-align: center;
    animation: fadeInUp 0.3s ease-out;
}

.medcapsule-spinner {
    position: relative;
    width: 60px;
    height: 60px;
    margin: 0 auto 1.5rem;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.spinner-ring:nth-child(1) {
    animation-delay: 0s;
    border-top-color: #007bff;
}

.spinner-ring:nth-child(2) {
    animation-delay: -0.33s;
    border-top-color: #0056b3;
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
}

.spinner-ring:nth-child(3) {
    animation-delay: -0.66s;
    border-top-color: #004085;
    width: 60%;
    height: 60%;
    top: 20%;
    left: 20%;
}

.preloader-brand {
    font-size: 1.8rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    text-shadow: 0 1px 3px rgba(0, 123, 255, 0.1);
}

.preloader-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
    opacity: 0.8;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

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

/* PERFORMANCE OPTIMIZATIONS */
.lightning-preloader * {
    will-change: auto;
    backface-visibility: hidden;
    transform: translateZ(0);
}

/* RESPONSIVE DESIGN */
@media (max-width: 768px) {
    .medcapsule-spinner {
        width: 50px;
        height: 50px;
    }
    
    .preloader-brand {
        font-size: 1.5rem;
    }
    
    .preloader-subtitle {
        font-size: 0.8rem;
    }
}
</style>

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
        <a href="{{ route('home') }}" class="brand">MedCapsule</a>
        <a href="{{ route('home') }}" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
    </div>
</nav>

</body>
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
    .stat-active .stat-number { color: #3b82f6; }
    
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
        transition: all 0.3s ease;
    }
    
    /* Stacked View (Email-like) */
    .assignment-grid.stacked-view {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        max-width: 100%;
    }
    
    .assignment-grid.stacked-view .assignment-card {
        width: 100%;
        max-width: none;
        border-radius: 8px;
        border-left: 4px solid #e5e7eb;
        transition: all 0.2s ease;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .assignment-grid.stacked-view .assignment-card:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        background: #fafbfc;
    }
    
    .assignment-grid.stacked-view .assignment-card:not(:last-child) {
        border-bottom: 1px solid #f1f5f9;
    }
    
    .assignment-grid.stacked-view .assignment-card[data-status="active"] {
        border-left-color: #3b82f6;
    }
    
    .assignment-grid.stacked-view .assignment-card[data-status="overdue"] {
        border-left-color: #ef4444;
    }
    
    .assignment-grid.stacked-view .assignment-card[data-status="completed"] {
        border-left-color: #10b981;
    }
    
    .assignment-grid.stacked-view .card-content {
        padding: 1.5rem 2rem;
        flex-direction: row;
        align-items: center;
        gap: 2rem;
        flex: 1;
        min-height: 100%;
    }
    
    .assignment-grid.stacked-view .card-header {
        flex: 1;
        margin-bottom: 0;
        min-width: 0;
    }
    
    .assignment-grid.stacked-view .assignment-title {
        font-size: 1rem;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .assignment-grid.stacked-view .assignment-meta {
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }
    
    .assignment-grid.stacked-view .card-body {
        flex: 1;
        flex-direction: row;
        gap: 2rem;
        align-items: center;
    }
    
    .assignment-grid.stacked-view .deadline-section {
        flex: 0 0 auto;
        min-width: 140px;
        text-align: right;
        display: flex;
        flex-direction: row;
        gap: 1rem;
        align-items: center;
        margin-left: auto;
    }
    
    .assignment-grid.stacked-view .time-remaining.completed {
        margin-left: auto;
        flex-shrink: 0;
    }
    
    .assignment-grid.stacked-view .topic-section {
        flex: 1;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        border-left: none;
        min-width: 200px;
        max-width: 900px;
    }
    

    
    .assignment-grid.stacked-view .duration-info {
        margin-top: 0.25rem;
        font-size: 0.7rem;
    }
    

    
    /* Hide topic section in stacked view */
    .assignment-grid.stacked-view .topic-section.stacked-hidden {
        display: none;
    }
    
    /* Hide status indicator in stacked view */
    .assignment-grid.stacked-view .status-indicator {
        display: none;
    }
    
    .assignment-grid.stacked-view .card-actions {
        flex: 0 0 auto;
        margin-top: 0;
        padding-top: 0;
        border-top: none;
        gap: 0.75rem;
    }
    
    .assignment-grid.stacked-view .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Responsive stacked view */
    @media (max-width: 768px) {
        .assignment-grid.stacked-view .card-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .assignment-grid.stacked-view .card-body {
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }
        
        .assignment-grid.stacked-view .deadline-section {
            min-width: auto;
            width: 100%;
        }
        
        .assignment-grid.stacked-view .card-actions {
            width: 100%;
            justify-content: flex-end;
        }
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
    
    .course-topic {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
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
    
    /* User Progress Dropdown */
    .user-progress-section {
        width: 100%;
    }
    
    .progress-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .progress-toggle:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    
    .progress-toggle i:first-child {
        color: #3b82f6;
        margin-right: 0.5rem;
    }
    
    .toggle-icon {
        transition: transform 0.2s ease;
        color: #64748b;
    }
    
    .progress-toggle.active .toggle-icon {
        transform: rotate(180deg);
    }
    
    .user-progress-dropdown {
        margin-top: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
    }
    
    .user-progress-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .user-progress-item:last-child {
        border-bottom: none;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .user-name {
        font-weight: 500;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .user-email {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .progress-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .progress-status {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .progress-status.status-in_progress { background: #dbeafe; color: #1d4ed8; }
    .progress-status.status-completed { background: #dcfce7; color: #166534; }
    .progress-status.status-overdue { background: #fee2e2; color: #dc2626; }
    .progress-status.status-not_started { background: #f1f5f9; color: #475569; }
    
    .progress-score {
        font-weight: 600;
        color: #059669;
        font-size: 0.875rem;
    }
    
    .no-students {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2rem 1rem;
        color: #64748b;
        font-size: 0.875rem;
    }
    
    .no-students i {
        color: #94a3b8;
    }
    
    /* Clean Progress Summary */
    .progress-summary {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    
    .progress-summary:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .progress-stats {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }
    
    .stat-divider {
        font-size: 1.25rem;
        color: #cbd5e1;
        font-weight: 300;
    }
    
    .progress-bar {
        width: 100%;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    
    .view-details {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #3b82f6;
        font-weight: 500;
    }
    
    .view-details i {
        transition: transform 0.2s ease;
    }
    
    .progress-summary:hover .view-details i {
        transform: translateX(2px);
    }
    
    /* Progress Modal */
    .progress-modal {
        max-width: 800px;
        width: 90vw;
    }
    
    .progress-modal .modal-body-custom {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .student-progress-grid {
        display: grid;
        gap: 1rem;
        padding: 1rem 0;
    }
    
    .student-progress-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .student-progress-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .student-info-detailed {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .student-details {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .student-name-detailed {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.875rem;
    }
    
    .student-email-detailed {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .progress-info-detailed {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .progress-status-detailed {
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .progress-score-detailed {
        font-size: 1.125rem;
        font-weight: 700;
        color: #059669;
        min-width: 60px;
        text-align: right;
    }
    
    .no-students-modal {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 2rem;
        color: #64748b;
        text-align: center;
    }
    
    .no-students-modal i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .no-students-modal h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .no-students-modal p {
        font-size: 0.875rem;
        color: #64748b;
    }
    
    /* Progress Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal-overlay.show {
        opacity: 1;
    }
    
    .progress-modal-container {
        max-width: 1200px;
        width: 95vw;
        max-height: 85vh;
        background: white;
        border-radius: 16px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
        transform: scale(0.95);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(20px);
    }
    
    .modal-overlay.show .progress-modal-container {
        transform: scale(1);
    }
    
    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 3rem 2rem;
        color: #64748b;
        font-size: 0.875rem;
    }
    
    .loading-spinner i {
        font-size: 1.25rem;
        color: #3b82f6;
    }
    
    .progress-content {
        padding: 0;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        background: #ffffff;
    }

    .assignment-info {
        flex: 1;
    }

    .assignment-title-modal {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .assignment-title-modal::before {
        content: '📊';
        font-size: 1rem;
    }

    .assignment-meta-modal {
        font-size: 0.875rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .assignment-meta-modal::before {
        content: '🎓';
        font-size: 0.875rem;
    }

    .progress-stats-modal {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }

    .stat-item-modal {
        text-align: center;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .stat-item-modal:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .stat-number-modal {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-label-modal {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }
    
    .students-grid {
        display: grid;
        gap: 0.75rem;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    
    .student-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .student-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        background: #f9fafb;
    }

    .student-info-modal {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        flex: 1;
    }

    .student-avatar-modal {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .student-details-modal {
        flex: 1;
        min-width: 0;
    }

    .student-name-modal {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin: 0 0 0.375rem 0;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    .student-email-modal {
        font-size: 0.8rem;
        color: #64748b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }

    .student-progress-modal {
        display: flex;
        align-items: center;
        gap: 1rem;
        position: relative;
        z-index: 1;
    }

    .progress-status-modal {
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
        letter-spacing: 0.01em;
        display: inline-block;
        border: 1px solid rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .progress-status-modal.status-active {
        background: #fef3c7;
        color: #d97706;
        border-color: #f59e0b;
    }

    .progress-status-modal.status-completed {
        background: #dcfce7;
        color: #166534;
        border-color: #10b981;
    }

    .progress-status-modal.status-overdue {
        background: #fee2e2;
        color: #dc2626;
        border-color: #ef4444;
    }

    .progress-status-modal.status-not_started {
        background: #f3f4f6;
        color: #6b7280;
        border-color: #d1d5db;
    }

    .progress-score-modal {
        font-size: 1.25rem;
        font-weight: 800;
        color: #059669;
        min-width: 70px;
        text-align: right;
        letter-spacing: -0.02em;
        position: relative;
    }

    .progress-score-modal::after {
        content: '%';
        font-size: 0.8rem;
        font-weight: 600;
        color: #10b981;
        margin-left: 0.125rem;
    }
    
    .no-students-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 2rem;
        text-align: center;
        color: #64748b;
    }
    
    .no-students-message i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .no-students-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 0.5rem 0;
    }
    
    .no-students-message p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0;
    }
    
    /* Responsive modal */
    @media (max-width: 768px) {
        .progress-modal-container {
            width: 95%;
            max-height: 90vh;
        }
        
        .progress-stats-modal {
            gap: 1rem;
        }
        
        .student-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .student-progress-modal {
            width: 100%;
            justify-content: space-between;
        }
        
        .progress-score-modal {
            min-width: auto;
        }
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

    .action-btn i {
        transition: all 0.3s ease;
    }

    .primary-btn {
        background: #3b82f6;
        color: white;
        border: none;
        box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
    }

    .primary-btn:hover {
        background: #2563eb;
        color: white;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        transform: translateY(-3px) scale(1.15);
    }

    .primary-btn i {
        color: white;
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
            flex-wrap: wrap;
            gap: 0.5rem;
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
        
        /* Mobile stacked view adjustments */
        .assignment-grid.stacked-view .assignment-card {
            margin-bottom: 1rem;
        }
        
        .assignment-grid.stacked-view .card-content {
            padding: 1rem 1.25rem;
        }
        
        .assignment-grid.stacked-view .assignment-title {
            font-size: 0.9rem;
        }
        
        .assignment-grid.stacked-view .assignment-meta {
            font-size: 0.7rem;
        }
        
        .assignment-grid.stacked-view .card-body {
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }
        
        .assignment-grid.stacked-view .deadline-section {
            min-width: auto;
            width: 100%;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .assignment-grid.stacked-view .score-section {
            margin-left: 0;
            margin-top: 0.5rem;
            justify-content: center;
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
    

    
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal-overlay.show {
        opacity: 1;
    }
    
    .modal-container {
        max-width: 900px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        transform: scale(0.95) translateY(20px);
        transition: all 0.3s ease;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .modal-overlay.show .modal-container {
        transform: scale(1) translateY(0);
    }

    /* Wider container for the edit modal */
    #editAssignmentModal .modal-container {
        max-width: 900px; /* Increased width */
    }
    
    .modal-content-wrapper {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        max-width: 1100px;
        width: 95%;
        position: relative;
        animation: modalEnter 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .modal-header-custom {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title-custom {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    
    .modal-close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 50%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
    }
    
    .modal-close-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }
    
    .modal-body-custom {
        padding: 2rem 2.5rem;
    }
    
    /* Modal Form Styles */
    .modal-form-group {
        margin-bottom: 1rem;
    }
    
    .modal-form-label {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
        font-size: 0.8rem;
    }
    
    .modal-form-control, .modal-form-select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        background: white;
    }
    
    .modal-form-control:focus, .modal-form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
    
    .modal-form-text {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 0.2rem;
    }
    
    .modal-assignment-info {
        background: #f8fafc;
        border-radius: 6px;
        padding: 0.875rem;
        margin-bottom: 1.25rem;
        border-left: 3px solid #3b82f6;
    }
    
    .modal-info-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    
    .modal-info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .modal-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.25rem;
    }
    
    .modal-info-label {
        font-weight: 500;
        color: #6b7280;
        font-size: 0.75rem;
    }
    
    .modal-info-value {
        color: #374151;
        font-weight: 500;
        font-size: 0.75rem;
    }
    
    .modal-status-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .modal-status-active { background: #dcfce7; color: #166534; }
    .modal-status-completed { background: #dbeafe; color: #1e40af; }
    .modal-status-overdue { background: #fee2e2; color: #dc2626; }
    .modal-status-locked { background: #fef3c7; color: #d97706; }
    .modal-status-expired { background: #fecaca; color: #dc2626; }
    
    .modal-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }
    
    .modal-btn {
        flex: 1;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        border: none;
    }
    
    .modal-btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .modal-btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .modal-btn-secondary {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #e5e7eb;
    }
    
    .modal-btn-secondary:hover {
        background: #e5e7eb;
        color: #374151;
    }
    
    .modal-loading {
        text-align: center;
        padding: 2rem 1rem;
        color: #6b7280;
    }
    
    .modal-loading-spinner {
        width: 28px;
        height: 28px;
        border: 2px solid #e5e7eb;
        border-top: 2px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 0.75rem;
    }
    
    .modal-alert {
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.8rem;
    }
    
    .modal-alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    
    .modal-alert-error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    
    /* Responsive modal */
    @media (max-width: 768px) {
        .modal-container {
            width: 95%;
            max-height: 90vh;
        }
        
        .modal-header-custom {
            padding: 0.875rem 1rem;
        }
        
        .modal-body-custom {
            padding: 1rem;
        }
        
        .modal-info-grid {
            grid-template-columns: 1fr;
        }
        
        .modal-actions {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .modal-form-group {
            margin-bottom: 0.875rem;
        }
    }
</style>
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
        <div class="stat-card stat-active">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'active'; })->count() }}</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-card stat-overdue">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'overdue'; })->count() }}</div>
            <div class="stat-label">Overdue</div>
        </div>
        <div class="stat-card stat-completed">
            <div class="stat-number">{{ $assignments->filter(function($a) { return $a->getCurrentStatusAttribute() === 'completed'; })->count() }}</div>
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
                        @php
                            $assignmentUsers = $assignment->usersWithProgress()->get();
                            $completedCount = 0;
                            $totalStudents = $assignmentUsers->count();
                            
                            foreach($assignmentUsers as $user) {
                                $latestAttempt = $user->quizAttempts->first();
                                if ($latestAttempt && $latestAttempt->completed_at) {
                                    $completedCount++;
                                }
                            }
                        @endphp
                        
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
                setTimeout(() => {
                    closeEditModal();
                    location.reload();
                }, 1500);
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

// Minimal assignment management
document.addEventListener('DOMContentLoaded', function() {

    // LETHAL PAGE TRANSITION HANDLER
    document.addEventListener('beforeunload', function() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.remove('hidden');
            navbar.style.opacity = '0';
        }
    });
    
    // View Toggle Functionality
    const viewToggle = document.getElementById('viewToggle');
    const assignmentGrid = document.querySelector('.assignment-grid');
    const gridIcon = document.getElementById('gridIcon');
    const listIcon = document.getElementById('listIcon');
    const viewText = document.getElementById('viewText');
    
    // Load saved view preference
    const savedView = localStorage.getItem('assignmentViewMode') || 'grid';
    if (savedView === 'stacked') {
        assignmentGrid.classList.add('stacked-view');
        gridIcon.style.display = 'none';
        listIcon.style.display = 'inline';
        viewText.textContent = 'List';
    }
    
    viewToggle.addEventListener('click', function() {
        const isStacked = assignmentGrid.classList.contains('stacked-view');
        
        if (isStacked) {
            // Switch to grid view
            assignmentGrid.classList.remove('stacked-view');
            gridIcon.style.display = 'inline';
            listIcon.style.display = 'none';
            viewText.textContent = 'Grid';
            localStorage.setItem('assignmentViewMode', 'grid');
        } else {
            // Switch to stacked view
            assignmentGrid.classList.add('stacked-view');
            gridIcon.style.display = 'none';
            listIcon.style.display = 'inline';
            viewText.textContent = 'List';
            localStorage.setItem('assignmentViewMode', 'stacked');
        }
        
        // Smooth transition effect
        assignmentGrid.style.opacity = '0.8';
        setTimeout(() => {
            assignmentGrid.style.opacity = '1';
        }, 150);
    });
    
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

// Simple assignment manager - delete and refresh
class SimpleAssignmentManager {
    async deleteAssignment(assignmentId) {
        const card = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
        
        if (card) {
            card.style.opacity = "0.5";
            card.style.pointerEvents = "none";
        }
        
        try {
            const response = await fetch(`/assignments/${assignmentId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log("✅ Assignment deleted - refreshing page");
                window.location.reload();
                return true;
            } else {
                throw new Error(data.message || "Failed to delete");
            }
        } catch (error) {
            console.error("❌ Delete failed:", error);
            
            if (card) {
                card.style.opacity = "1";
                card.style.pointerEvents = "auto";
            }
            
            alert("Failed to delete assignment");
            return false;
        }
    }
    
    // Check if we need to force refresh immediately
    checkForceRefresh() {
        // Check for Laravel flash message indicating new assignments were created
        const successMessage = document.querySelector('.alert-success');
        if (successMessage && successMessage.textContent.includes('Successfully created')) {
            console.log("🚀 New assignments detected - forcing immediate sync");
            this.syncWithServer(true); // Force refresh bypasses cache
        }
        
        // Also check URL parameters for force refresh
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('refresh') === 'true') {
            console.log("🚀 URL refresh parameter detected - forcing immediate sync");
            this.syncWithServer(true);
        }
    }
    
    async syncWithServer() {
        try {
            const response = await fetch('/assignments/api/list', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) return false;
            
            const data = await response.json();
            if (data.success) {
                this.updateUI(data.assignments);
                this.lastSync = Date.now();
            }
        } catch (error) {
            console.error("Sync failed:", error);
        }
    }
    
    // Update UI with server data - SIMPLE & DIRECT
    updateUI(serverAssignments) {
        const container = document.querySelector(".assignments-container");
        if (!container) return;
        
        // Get current DOM assignments
        const currentCards = container.querySelectorAll('[data-assignment-id]');
        const currentIds = new Set(Array.from(currentCards).map(card => 
            parseInt(card.getAttribute('data-assignment-id'))
        ));
        
        // Get server assignment IDs
        const serverIds = new Set(serverAssignments.map(a => a.id));
        
        // Remove assignments that don't exist on server
        currentIds.forEach(id => {
            if (!serverIds.has(id)) {
                const card = container.querySelector(`[data-assignment-id="${id}"]`);
                if (card) {
                    console.log(`🗑️ Removing assignment ${id} - not on server`);
                    this.animateRemoval(card);
                }
            }
        });
        
        // Update count
        this.updateCount(serverAssignments.length);
    }
    
    // Animate card removal
    animateRemoval(card) {
        card.style.transition = "all 0.3s ease";
        card.style.transform = "scale(0)";
        card.style.opacity = "0";
        setTimeout(() => card.remove(), 300);
    }
    
    // Update assignment count
    updateCount(count) {
        const countElement = document.querySelector(".assignment-count");
        if (countElement) countElement.textContent = count;
        
        const headerCount = document.querySelector(".header-assignment-count");
        if (headerCount) headerCount.textContent = `${count} Assignment${count !== 1 ? "s" : ""}`;
    }
    
    startSync() {
        setInterval(() => {
            if (Date.now() - this.lastSync > 30000) {
                this.syncWithServer();
            }
        }, 10000);
        
        setTimeout(() => this.syncWithServer(), 1000);
    }
    
    forceSync() {
        this.syncWithServer();
    }
    
    // Handle assignment deletion - INSTANT & RELIABLE
    async deleteAssignment(assignmentId) {
        console.log(`🗑️ Deleting assignment ${assignmentId}`);
        
        // Immediately hide from UI
        const card = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
        if (card) {
            card.style.transition = "all 0.3s ease";
            card.style.transform = "scale(0.95)";
            card.style.opacity = "0.5";
            card.style.pointerEvents = "none";
        }
        
        try {
            const response = await fetch(`/assignments/${assignmentId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log("✅ Assignment deleted successfully");
                
                // Remove from UI immediately
                if (card) {
                    this.animateRemoval(card);
                }
                
                // Show success message
                this.showMessage("Deleted", "success");
                
                // Sync with server after 2 seconds to ensure consistency
                setTimeout(() => this.syncWithServer(), 2000);
                
                return true;
            } else {
                throw new Error(data.message || "Failed to delete");
            }
        } catch (error) {
            console.error("❌ Delete failed:", error);
            
            // Restore card on error
            if (card) {
                card.style.transform = "scale(1)";
                card.style.opacity = "1";
                card.style.pointerEvents = "auto";
            }
            
            this.showMessage("Delete failed", "error");
            return false;
        }
    }
    
    // Show simple message
    showMessage(text, type) {
        const message = document.createElement("div");
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
}

// Initialize simple manager
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
    window.location.reload();
}

// 🚀 INSTANT REFRESH: Auto-refresh if we detect assignment creation success
document.addEventListener('DOMContentLoaded', function() {
    // Check for success messages indicating new assignments
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(alert => {
        if (alert.textContent.includes('Successfully created') && alert.textContent.includes('assignment')) {
            console.log("🚀 Assignment creation detected - forcing immediate refresh");
            setTimeout(() => window.location.reload(), 500);
        }
    });
});

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

function enforceRealTimeStatus(assignmentId = null) {
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-refresh fa-spin"></i>';
    btn.disabled = true;
    
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
            // Show instant feedback
            btn.innerHTML = '<i class="fas fa-check text-success"></i> UPDATED!';
            
            // Reload page to show updated statuses
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
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
