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

<!-- LETHAL PRELOADER - ELIMINATES FOUC -->
<div class="preloader" id="preloader">
    <div class="spinner"></div>
    <div class="preloader-brand">MedCapsule</div>
</div>

<!-- Minimal Navigation -->
<nav class="navbar" style="opacity: 0; transition: opacity 0.3s ease;">
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
        max-width: 300px;
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
    
    /* Stacked view specific topic styling */
    .assignment-grid.stacked-view .topic-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.25rem;
    }
    
    .assignment-grid.stacked-view .topic-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        line-height: 1.4;
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
    
    /* LETHAL PRELOADER STYLES - ELIMINATES FOUC */
    .preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }
    
    .preloader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(59, 130, 246, 0.1);
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    .preloader-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: #3b82f6;
        margin-top: 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes slideInDown {
        0% {
            transform: translateY(-20px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
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
        max-width: 420px;
        width: 90%;
        max-height: 85vh;
        overflow-y: auto;
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s ease;
    }
    
    .modal-overlay.show .modal-container {
        transform: scale(1) translateY(0);
    }
    
    .modal-content-wrapper {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        overflow: hidden;
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
        padding: 1.25rem;
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
            
            <button type="button" class="btn btn-outline btn-sm" onclick="updateOverdueAssignments()" title="Refresh Assignment Status">
                <i class="fas fa-refresh"></i>
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
                        <div class="topic-section stacked-hidden">
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
                            @endif
                            @if($assignment->duration_minutes)
                                <div class="duration-info">
                                    <small class="text-muted">{{ $assignment->duration_minutes }} min</small>
                                </div>
                            @endif
                            @if($assignment->status === 'completed' && $assignment->score_percentage)
                                <div class="time-remaining completed">
                                    {{ $assignment->score_percentage }}%
                                </div>
                            @endif
                        </div>
                        
 
                    </div>
                    
                    <!-- Actions -->
                    @if($assignment->status !== 'completed')
                        <div class="card-actions">
                            <button type="button" class="action-btn edit-btn" onclick="openEditModal({{ $assignment->id }})">
                                <i class="fas fa-edit"></i>
                            </button>
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
    // LETHAL PRELOADER HANDLER - ELIMINATES FOUC
    const preloader = document.getElementById('preloader');
    const navbar = document.querySelector('.navbar');
    setTimeout(() => {
        preloader.classList.add('hidden');
        navbar.style.opacity = '1';
        setTimeout(() => preloader.remove(), 500);
    }, 300);

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

<!-- REAL-TIME ASSIGNMENT STATUS ENFORCER -->
<script src="{{ asset('js/real-time-assignments.js') }}"></script>

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
// Edit Assignment Modal Functions
function openEditModal(assignmentId) {
    const modal = document.getElementById('editAssignmentModal');
    const modalContent = document.getElementById('editModalContent');
    
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

function closeEditModal() {
    const modal = document.getElementById('editAssignmentModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Close modal when clicking outside or pressing Escape
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

function initializeEditForm(assignmentId) {
    const form = document.getElementById('editAssignmentForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
        
        fetch(`/assignments/${assignmentId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData))
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
                showModalAlert('Error updating assignment: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModalAlert('An error occurred while updating the assignment.', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
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
</script>

</body>
</html>