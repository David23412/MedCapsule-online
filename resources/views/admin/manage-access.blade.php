@php
    $activeTab = match (request()->query('tab')) {
        'question-quality' => 'question-quality',
        'mentorship' => 'mentorship',
        'payments' => 'payment-history',
        'survey-results' => 'survey-results',
        'scheduler-quality' => 'scheduler-quality',
        'security-watchlist', 'security' => 'security-watchlist',
        'videos' => 'video-management',
        'preview-topics' => 'preview-topics',
        'payment-settings', 'settings' => 'settings',
        default => 'access',
    };
    $tabDescriptions = [
        'question-quality' => 'Find invalid answers and explanations that only repeat the correct answer.',
        'access' => 'Choose a user and course to review access. Account permissions are managed separately.',
        'preview-topics' => 'Choose which topics students can open with preview access.',
        'payment-history' => 'Find payments and review their status.',
        'survey-results' => 'Review student feedback and individual survey responses.',
        'mentorship' => 'Review mentored students and their learning progress.',
        'scheduler-quality' => 'Review learning scheduler performance and coverage.',
        'security-watchlist' => 'Review flagged accounts and resolve security locks.',
        'video-management' => 'Add course videos and manage existing recordings.',
        'settings' => 'Manage topic visibility, payment options, and course display settings.',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Access - MedCapsule</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @include('partials.admin-dark-mode-head')

    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #333333;
            --header-color: #2c3e50;
            --icon-color: #0d6efd;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --border-radius: 8px;
            --border-radius-sm: 6px;
        }
        
        /* ── Notification System ────────────────────────────────── */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 420px;
        }
        
        .notification {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.14), 0 2px 10px rgba(0,0,0,0.08);
            padding: 16px 18px 10px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 340px;
            max-width: 420px;
            transform: translateX(110%);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
            pointer-events: auto;
            position: relative;
            overflow: hidden;
            flex-wrap: wrap;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.success {
            border-left: 4px solid #198754;
            background-color: #f0fdf4;
        }
        
        .notification.error {
            border-left: 4px solid #dc3545;
            background-color: #fef2f2;
        }

        .notification.warning {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
        }

        .notification.info {
            border-left: 4px solid #0ea5e9;
            background-color: #f0f9ff;
        }
        
        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
            animation: notif-icon-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s both;
        }
        @keyframes notif-icon-pop {
            0%   { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .notification.success .notification-icon {
            color: #fff;
            background-color: #198754;
            box-shadow: 0 2px 8px rgba(25,135,84,0.15);
        }
        .notification.error .notification-icon {
            color: #fff;
            background-color: #dc3545;
            box-shadow: 0 2px 8px rgba(220,53,69,0.15);
        }
        .notification.warning .notification-icon {
            color: #fff;
            background-color: #f59e0b;
            box-shadow: 0 2px 8px rgba(245,158,11,0.15);
        }
        .notification.info .notification-icon {
            color: #fff;
            background-color: #0ea5e9;
            box-shadow: 0 2px 8px rgba(14,165,233,0.15);
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 2px;
            color: #1e293b;
        }
        
        .notification-message {
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 4px;
            transition: color 0.15s, background 0.15s;
            font-size: 12px;
            line-height: 1;
            flex-shrink: 0;
        }
        
        .notification-close:hover {
            color: #475569;
            background: rgba(0,0,0,0.06);
        }

        /* Progress bar countdown */
        .notification-progress {
            width: 100%;
            height: 3px;
            background: rgba(0,0,0,0.06);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            flex-basis: 100%;
        }
        .notification-progress-bar {
            height: 100%;
            width: 100%;
            border-radius: 2px;
        }
        .notification.success .notification-progress-bar { background: #198754; }
        .notification.error   .notification-progress-bar { background: #dc3545; }
        .notification.warning .notification-progress-bar { background: #f59e0b; }
        .notification.info    .notification-progress-bar { background: #0ea5e9; }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            line-height: 1.6;
            font-size: 14px;
        }

        .main-header {
            background: transparent;
            border-bottom: none;
            padding: 1.25rem 0 0;
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: none;
            backdrop-filter: blur(10px);
        }

        .header-shell {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--header-color);
        }

        .page-subtitle {
            color: #666666;
            font-size: 1rem;
            margin-bottom: 0;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            background: white;
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: var(--shadow);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--header-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header i {
            color: var(--icon-color);
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }

        .card-header .badge {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }

        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: var(--border-radius-sm);
            padding: 0.6rem 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .form-label {
            font-weight: 600;
            color: var(--header-color);
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .form-label i {
            color: var(--icon-color);
            margin-right: 0.5rem;
        }

        .btn {
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.15s ease-in-out;
            border: none;
            cursor: pointer;
            text-transform: none;
            letter-spacing: 0.3px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background-color: #0d6efd;
            color: white;
            border: 2px solid #0d6efd;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: white;
        }

        .btn-success {
            background-color: #198754;
            color: white;
            border: 2px solid #198754;
        }

        .btn-success:hover {
            background-color: #146c43;
            border-color: #146c43;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
            border: 2px solid #ffc107;
            font-weight: 700;
        }

        .btn-warning:hover {
            background-color: #ffb300;
            border-color: #ffb300;
            color: #000;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: 2px solid #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
            color: white;
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #495057;
            background: white;
            font-weight: 600;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .btn i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }

        .table {
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        .table th {
            font-weight: 700;
            color: var(--header-color);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 0.75rem;
            background-color: #f8f9fa;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* FIXED: Keep yellow background on completed payments even on hover */
        .table tbody tr[data-payment-status="completed"] {
            background-color: #fffbeb !important;
        }
        
        .table tbody tr[data-payment-status="completed"]:hover {
            background-color: #fef3c7 !important;
        }

        .table .fw-medium {
            color: #2c3e50;
            font-weight: 600;
        }

        .badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5em 1em;
            border-radius: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid transparent;
        }

        /* Enhanced Data Pills - More Vibrant & Professional */
        .data-pill {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0.025em;
            border: 1px solid;
            backdrop-filter: blur(4px);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Course Pill - Vibrant Blue */
        .data-pill.course {
            background: rgba(13, 110, 253, 0.12) !important;
            color: #1d4ed8 !important;
            border-color: rgba(13, 110, 253, 0.25);
        }

        /* Method Pill - Vibrant Teal */
        .data-pill.method {
            background: rgba(13, 202, 240, 0.12) !important;
            color: #0f766e !important;
            border-color: rgba(13, 202, 240, 0.25);
        }

        /* Status Pills - More Vibrant Colors */
        .data-pill.status-completed {
            background: rgba(34, 197, 94, 0.12) !important;
            color: #166534 !important;
            border-color: rgba(34, 197, 94, 0.25);
        }

        .data-pill.status-pending {
            background: rgba(251, 191, 36, 0.15) !important;
            color: #92400e !important;
            border-color: rgba(251, 191, 36, 0.30);
        }

        .data-pill.status-rejected {
            background: rgba(239, 68, 68, 0.12) !important;
            color: #991b1b !important;
            border-color: rgba(239, 68, 68, 0.25);
        }

        .data-pill.status-default {
            background: rgba(107, 114, 128, 0.12) !important;
            color: #374151 !important;
            border-color: rgba(107, 114, 128, 0.25);
        }

        /* Hover Effects - Subtle Enhancement */
        .data-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        /* Legacy Badge Support - Updated for Consistency */
        .badge.bg-primary {
            background: rgba(13, 110, 253, 0.08) !important;
            color: #1e40af !important;
            border: 1px solid rgba(13, 110, 253, 0.15);
        }

        .badge.bg-success {
            background: rgba(25, 135, 84, 0.08) !important;
            color: #14532d !important;
            border: 1px solid rgba(25, 135, 84, 0.15);
        }

        .badge.bg-warning {
            background: rgba(251, 191, 36, 0.08) !important;
            color: #78350f !important;
            border: 1px solid rgba(251, 191, 36, 0.15);
            font-weight: 600;
        }

        .badge.bg-danger {
            background: rgba(220, 53, 69, 0.08) !important;
            color: #7f1d1d !important;
            border: 1px solid rgba(220, 53, 69, 0.15);
        }

        .badge.bg-info {
            background: rgba(13, 202, 240, 0.08) !important;
            color: #0c4a6e !important;
            border: 1px solid rgba(13, 202, 240, 0.15);
            font-weight: 600;
        }

        .badge.bg-secondary {
            background: rgba(108, 117, 125, 0.08) !important;
            color: #374151 !important;
            border: 1px solid rgba(108, 117, 125, 0.15);
        }

        .stats-row {
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 8px 8px 0 0;
        }

        .stat-card {
            border: 2px solid #e9ecef;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-number.text-primary { color: #0d6efd !important; }
        .stat-number.text-success { color: #198754 !important; }
        .stat-number.text-warning { color: #f57c00 !important; }
        .stat-number.text-info { color: #0dcaf0 !important; }

        .stat-label {
            color: var(--header-color);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .stat-change.positive {
            color: var(--success-color);
        }

        .stat-change.negative {
            color: var(--danger-color);
        }


        .select2-container .select2-selection--single {
            height: 42px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius-sm);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
            font-size: 0.9rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        .select2-dropdown {
            border-radius: var(--border-radius-sm);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border: 1px solid #e0e0e0;
        }
        
        /* FIXED: Improved dropdown styling for better readability */
        .select2-results__option {
            padding: 12px 16px !important;
            font-size: 0.95rem !important;
            line-height: 1.5 !important;
            border-bottom: 1px solid #f0f0f0 !important;
            transition: all 0.2s ease !important;
            background-color: white !important;
        }
        
        .select2-results__option:last-child {
            border-bottom: none !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #f8f9fa !important;
            color: #2c3e50 !important;
        }
        
        .select2-results__option--highlighted .fw-medium {
            color: #1976d2 !important;
        }
        
        .select2-results__option--selected {
            background-color: #e8f5e9 !important;
            color: #2c3e50 !important;
        }
        
        .select2-results__option--selected .fw-medium {
            color: #2e7d32 !important;
        }
        
        /* FIXED: Better styling for selected item in input box */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #2c3e50 !important;
            font-weight: 500 !important;
            line-height: 40px !important;
        }
        
        .select2-container--default .select2-selection--single {
            background-color: white !important;
            border: 2px solid #ced4da !important;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #2196f3 !important;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1) !important;
        }
        
        /* User name styling */
        .select2-results__option .fw-medium {
            font-size: 1rem !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
            display: block !important;
            margin-bottom: 4px !important;
        }
        
        /* Email styling */
        .select2-results__option .text-muted {
            font-size: 0.85rem !important;
            color: #6c757d !important;
            display: block !important;
        }
        
        /* Role badge styling */
        .select2-results__option .badge {
            font-size: 0.75rem !important;
            padding: 4px 8px !important;
            border-radius: 4px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .select2-results__option .badge.bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
        }
        
        /* Search box styling */
        .select2-search--dropdown .select2-search__field {
            padding: 10px 12px !important;
            font-size: 0.95rem !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 6px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }
        
        .select2-search--dropdown .select2-search__field:focus {
            border-color: #2196f3 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1) !important;
        }

        .nav-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 0.85rem;
            background: rgba(248, 250, 252, 0.92);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            border-bottom: none;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-item {
            margin: 0;
        }

        .nav-tabs .nav-link {
            border: 1px solid rgba(15, 23, 42, 0.08);
            color: #475569;
            font-weight: 700;
            font-size: 0.98rem;
            padding: 0.95rem 1.25rem;
            transition: all 0.15s ease;
            position: relative;
            background: #ffffff;
            margin-right: 0;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            min-height: 52px;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background: #eef5ff;
            border-color: rgba(13, 110, 253, 0.28);
            transform: translateY(-1px);
        }

        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%);
            border-color: rgba(13, 110, 253, 0.5);
            box-shadow: 0 10px 24px rgba(13, 110, 253, 0.28);
        }

        .nav-tabs .nav-link i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .alert {
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            border: none;
            box-shadow: var(--shadow-sm);
        }

        .user-info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        }

        .user-info-card strong {
            color: #1565c0;
            font-weight: 700;
        }

        .user-info-card .text-muted {
            color: #424242 !important;
            font-weight: 500;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .info-tooltip {
            position: relative;
            cursor: help;
        }

        .info-tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark-color);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
        }

        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #495057;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--border-radius);
            border: 2px dashed #ced4da;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #6c757d;
        }

        .empty-state div {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
        }

        .empty-state .small {
            color: #6c757d;
            font-weight: 500;
        }

        .survey-hero-card {
            background:
                radial-gradient(circle at top left, rgba(191, 219, 254, 0.42), transparent 34%),
                radial-gradient(circle at bottom right, rgba(196, 181, 253, 0.22), transparent 30%),
                linear-gradient(135deg, #f9fbff 0%, #f1f6ff 42%, #fcfaff 100%);
            color: #20304a;
            border: 1px solid rgba(148, 163, 184, 0.1);
            overflow: hidden;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
            position: relative;
        }

        .survey-hero-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.28) 50%, transparent 100%);
            transform: translateX(-100%);
            animation: surveyHeroSheen 7s ease-in-out infinite;
            pointer-events: none;
        }

        .survey-hero-card h3,
        .survey-hero-card p,
        .survey-hero-card span,
        .survey-hero-card div,
        .survey-hero-card small {
            color: inherit !important;
        }

        .survey-highlight-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            color: #3d4f75;
            border: 1px solid rgba(148, 163, 184, 0.12);
            backdrop-filter: blur(14px);
            font-weight: 600;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.035);
        }

        .survey-pane-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .survey-hero-layout {
            position: relative;
            z-index: 1;
        }

        .survey-hero-copy {
            max-width: 720px;
        }

        .survey-hero-title {
            font-size: clamp(1.75rem, 2.3vw, 2.5rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            margin-bottom: 0.85rem;
        }

        .survey-hero-body {
            max-width: 620px;
            color: #60708a !important;
            font-size: 1rem;
            line-height: 1.7;
        }

        .survey-hero-orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(2px);
            opacity: 0.75;
            animation: surveyOrbFloat 10s ease-in-out infinite;
        }

        .survey-hero-orb.orb-one {
            width: 170px;
            height: 170px;
            top: -48px;
            right: -20px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.14) 0%, rgba(59, 130, 246, 0) 72%);
        }

        .survey-hero-orb.orb-two {
            width: 120px;
            height: 120px;
            bottom: -34px;
            left: 18%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, rgba(124, 58, 237, 0) 70%);
            animation-delay: -3s;
        }

        .survey-hero-mini-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .survey-hero-mini-stat {
            min-width: 140px;
            padding: 0.85rem 1rem;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(239, 246, 255, 0.84) 100%);
            border: 1px solid rgba(96, 165, 250, 0.18);
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.08);
            backdrop-filter: blur(12px);
        }

        .survey-hero-mini-stat strong {
            display: block;
            font-size: 1.1rem;
            color: #1e3a8a;
        }

        .survey-hero-mini-stat span {
            display: block;
            font-size: 0.8rem;
            color: #6b7a90 !important;
            margin-top: 0.25rem;
        }

        .survey-metric-card {
            height: 100%;
            border: 1px solid rgba(148, 163, 184, 0.08);
            background:
                radial-gradient(circle at top right, rgba(219, 234, 254, 0.24), transparent 34%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.985) 0%, rgba(249, 250, 255, 1) 100%);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.04);
            border-radius: 20px;
            transition: transform 0.35s ease, box-shadow 0.35s ease;
            opacity: 0;
            transform: translateY(16px);
            transition: opacity 0.65s ease, transform 0.65s ease, box-shadow 0.35s ease;
        }

        .survey-insight-card {
            height: 100%;
            border: 1px solid rgba(148, 163, 184, 0.08);
            background:
                radial-gradient(circle at top left, rgba(219, 234, 254, 0.22), transparent 30%),
                radial-gradient(circle at bottom right, rgba(233, 213, 255, 0.14), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 250, 255, 1) 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
            border-radius: 22px;
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.7s ease, transform 0.7s ease, box-shadow 0.35s ease;
        }

        .survey-insight-value {
            font-family: "Inter", sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.02em;
            color: #2a4dbf;
            text-rendering: geometricPrecision;
            text-shadow: 0 0 0 rgba(0, 0, 0, 0.01);
        }

        .survey-metric-value {
            font-family: "Inter", sans-serif;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.02em;
            color: var(--header-color);
            text-rendering: geometricPrecision;
            text-shadow: 0 0 0 rgba(0, 0, 0, 0.01);
        }

        .survey-question-card {
            border: 1px solid rgba(148, 163, 184, 0.08);
            background:
                radial-gradient(circle at top right, rgba(219, 234, 254, 0.16), transparent 26%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 250, 255, 1) 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
            border-radius: 24px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.75s ease, transform 0.75s ease, box-shadow 0.35s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .survey-question-card::before {
            content: '';
            position: absolute;
            inset: auto 18% 18% 18%;
            height: 130px;
            background:
                radial-gradient(circle at 18% 50%, rgba(37, 99, 235, 0.04), transparent 35%),
                radial-gradient(circle at 52% 40%, rgba(124, 58, 237, 0.035), transparent 30%),
                radial-gradient(circle at 82% 54%, rgba(56, 189, 248, 0.03), transparent 32%);
            filter: blur(32px);
            opacity: 0.28;
            transform: translate3d(0, 0, 0);
            animation: surveyCardFlow 18s ease-in-out infinite;
            pointer-events: none;
        }

        .survey-question-card .card-body,
        .survey-insight-card .card-body,
        .survey-metric-card .card-body {
            padding: 1.35rem 1.4rem;
            position: relative;
            z-index: 1;
        }

        .survey-question-topline {
            margin-bottom: 1.25rem;
        }

        .survey-question-main {
            gap: 1.75rem !important;
            align-items: center !important;
        }

        .survey-question-meta {
            min-width: 92px;
            padding: 0.7rem 0.85rem;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(240, 247, 255, 0.96) 0%, rgba(247, 242, 255, 0.88) 100%);
            border: 1px solid rgba(96, 165, 250, 0.14);
        }

        .survey-chart-shell {
            width: 190px;
            min-width: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .survey-chart-shell::before {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background:
                radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, rgba(37, 99, 235, 0.01) 45%, transparent 70%);
            animation: surveyPulseHalo 6.2s ease-in-out infinite;
            pointer-events: none;
        }

        .survey-donut {
            width: 182px;
            height: 182px;
            position: relative;
            transform: scale(0.9) rotate(-12deg);
            opacity: 0;
            transition: transform 0.95s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.75s ease;
        }

        .survey-donut-svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .survey-donut-ring {
            fill: none;
            stroke: rgba(148, 163, 184, 0.08);
            stroke-width: 18;
        }

        .survey-donut-segment {
            fill: none;
            stroke-linecap: butt;
            cursor: pointer;
            transition: stroke-width 0.22s ease, filter 0.22s ease, opacity 0.22s ease;
        }

        .survey-donut-segment:hover,
        .survey-donut-segment.is-active {
            stroke-width: 24;
            filter:
                drop-shadow(0 0 1px rgba(15, 23, 42, 0.22))
                drop-shadow(0 0 6px rgba(96, 165, 250, 0.28))
                drop-shadow(0 0 12px rgba(59, 130, 246, 0.16))
                drop-shadow(0 6px 14px rgba(37, 99, 235, 0.14));
        }

        .survey-donut-core {
            position: absolute;
            inset: 20px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 50%;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
        }

        .survey-donut-center {
            position: absolute;
            inset: 0;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.25rem;
            pointer-events: none;
            gap: 0.4rem;
            transform: translateY(-3px);
        }

        .survey-donut-center::before {
            content: '';
            position: absolute;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.95) 0%, rgba(244, 247, 255, 0.75) 100%);
            z-index: -1;
        }

        .survey-donut-center strong {
            font-family: "Inter", sans-serif;
            font-size: 1.85rem;
            line-height: 0.9;
            letter-spacing: -0.02em;
            color: #1e3a8a;
            font-weight: 800;
            text-rendering: geometricPrecision;
            text-shadow: 0 0 0 rgba(0, 0, 0, 0.01);
            margin: 0;
        }

        .survey-donut-center span {
            display: block;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1;
            margin: 0;
        }

        .survey-donut-tooltip {
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translate(-50%, -8px);
            padding: 0.45rem 0.65rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid rgba(148, 163, 184, 0.12);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            font-size: 0.78rem;
            line-height: 1.35;
            color: #334155;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
            z-index: 3;
        }

        .survey-donut-tooltip.is-visible {
            opacity: 1;
            transform: translate(-50%, -14px);
        }

        .survey-donut-tooltip strong {
            display: block;
            font-family: "Inter", sans-serif;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #1e3a8a;
        }

        .survey-question-kicker {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--primary-color);
            margin-bottom: 0.35rem;
        }

        .survey-choice-row + .survey-choice-row {
            margin-top: 0.9rem;
        }

        .survey-choice-track {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #e9eefb;
            overflow: hidden;
            margin-top: 0.35rem;
        }

        .survey-choice-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #8ec5fc 0%, #3b82f6 100%);
        }

        .survey-choice-bar.is-top {
            background: linear-gradient(90deg, #34d399 0%, #059669 100%);
        }

        .survey-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(226, 232, 240, 0.75);
            opacity: 0;
            transform: translateX(10px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .survey-legend-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .survey-legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-top: 0.3rem;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.7);
        }

        .survey-legend-content {
            flex: 1 1 auto;
            min-width: 0;
        }

        .survey-legend-progress {
            width: 100%;
            height: 2px;
            margin-top: 0.5rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.12);
            overflow: hidden;
        }

        .survey-legend-progress-fill {
            height: 100%;
            border-radius: inherit;
            transform-origin: left center;
            transform: scaleX(0);
            opacity: 1;
            filter: saturate(1.22) contrast(1.02);
            transition: transform 0.8s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .survey-question-card.is-visible,
        .survey-insight-card.is-visible,
        .survey-metric-card.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .survey-question-card.is-visible:hover,
        .survey-insight-card.is-visible:hover,
        .survey-metric-card:hover {
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.05);
            transform: translateY(-1px);
        }

        .survey-question-card.is-visible .survey-donut {
            opacity: 1;
            transform: scale(1) rotate(0deg);
            animation: surveyDonutFloat 5.4s ease-in-out infinite;
        }

        .survey-question-card.is-visible .survey-legend-item {
            opacity: 1;
            transform: translateX(0);
        }

        .survey-question-card.is-visible .survey-legend-progress-fill {
            transform: scaleX(1);
        }

        .survey-question-card.is-visible .survey-legend-item:nth-child(1) { transition-delay: 0.08s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(2) { transition-delay: 0.14s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(3) { transition-delay: 0.2s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(4) { transition-delay: 0.26s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(5) { transition-delay: 0.32s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(6) { transition-delay: 0.38s; }
        .survey-question-card.is-visible .survey-legend-item:nth-child(7) { transition-delay: 0.44s; }

        .survey-premium-muted {
            color: #6b7a90 !important;
        }

        .survey-sticky-column {
            position: sticky;
            top: 1.5rem;
        }

        .survey-sidebar-card {
            border-radius: 22px;
            border: 1px solid rgba(96, 165, 250, 0.14);
            box-shadow: 0 18px 36px rgba(37, 99, 235, 0.06);
            background:
                radial-gradient(circle at top right, rgba(219, 234, 254, 0.45), transparent 28%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(246, 249, 255, 1) 100%);
        }

        @keyframes surveyHeroSheen {
            0%, 68%, 100% { transform: translateX(-100%); }
            84% { transform: translateX(100%); }
        }

        @keyframes surveyOrbFloat {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(0, 6px, 0) scale(1.01); }
        }

        @keyframes surveyDonutFloat {
            0%, 100% { transform: scale(1) rotate(0deg) translateY(0); }
            50% { transform: scale(1.006) rotate(0.6deg) translateY(-2px); }
        }

        @keyframes surveyCardFlow {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); opacity: 0.22; }
            33% { transform: translate3d(6px, -4px, 0) scale(1.015); opacity: 0.28; }
            66% { transform: translate3d(-5px, 4px, 0) scale(0.995); opacity: 0.2; }
        }

        @keyframes surveyPulseHalo {
            0%, 100% { transform: scale(0.94); opacity: 0.32; }
            50% { transform: scale(1.02); opacity: 0.42; }
        }

        .survey-response-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #f3f6ff;
            color: #3154c9;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .survey-response-pill + .survey-response-pill {
            margin-top: 0.45rem;
        }

        .survey-feedback-card {
            border-left: 4px solid var(--primary-color);
            background: linear-gradient(180deg, rgba(13, 110, 253, 0.04) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .survey-feedback-text {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.55;
        }

        /* Written answer items */
        .survey-written-item {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            transition: background 0.15s ease;
        }
        .survey-written-item:last-child { border-bottom: none; }
        .survey-written-item:hover { background: rgba(96, 165, 250, 0.04); }
        .survey-written-item .written-meta {
            font-size: 0.78rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }
        .survey-written-item .written-text {
            color: #334155;
            font-size: 0.92rem;
            line-height: 1.5;
            word-break: break-word;
        }

        /* Student responses table */
        .survey-student-table {
            width: 100%;
            font-size: 0.88rem;
        }
        .survey-student-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            padding: 0.65rem 0.75rem;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .survey-student-table td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            vertical-align: middle;
        }
        .survey-student-table tr:hover td {
            background: rgba(96, 165, 250, 0.04);
        }
        .survey-student-table .student-name-cell {
            font-weight: 600;
            color: #1e3a8a;
            cursor: pointer;
        }
        .survey-student-table .student-name-cell:hover {
            text-decoration: underline;
        }

        /* Student detail modal answers */
        .student-detail-answer {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }
        .student-detail-answer:last-child { border-bottom: none; }
        .student-detail-answer .detail-question-id {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary-color);
            letter-spacing: 0.06em;
            margin-bottom: 0.15rem;
        }
        .student-detail-answer .detail-prompt {
            font-size: 0.88rem;
            color: #475569;
            margin-bottom: 0.35rem;
        }
        .student-detail-answer .detail-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.92rem;
        }
        .student-detail-answer .detail-value.not-answered {
            color: #94a3b8;
            font-style: italic;
            font-weight: 400;
        }
        .student-detail-answer .detail-value.is-textarea {
            background: rgba(96, 165, 250, 0.06);
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            border-left: 3px solid var(--primary-color);
            line-height: 1.5;
            word-break: break-word;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-header {
                padding: 0.75rem 0;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
            
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .card-header {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .quick-actions {
                flex-direction: column;
            }

            .survey-chart-shell {
                width: 100%;
                min-width: 0;
                margin-bottom: 1rem;
            }

            .survey-donut {
                width: 156px;
                height: 156px;
            }

            .survey-hero-mini-stats {
                gap: 0.6rem;
            }

            .survey-hero-mini-stat {
                min-width: calc(50% - 0.3rem);
            }

            .survey-sticky-column {
                position: static;
            }

            .quick-actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .header-shell {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
            }
            
            .stats-row .col-md-3 {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .nav-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 1rem;
            }

            .nav-tabs .nav-link {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
                white-space: nowrap;
            }
        }

        /* Action buttons styling */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 4px;
        }

        .btn-group .btn {
            margin-right: 0.25rem;
        }

        .btn-group .btn:last-child {
            margin-right: 0;
        }

        /* GOLDEN STANDARD GRANT ACCESS BUTTON */
        #grantAccessBtn {
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        #grantAccessBtn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #grantAccessBtn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
        }

        /* Print styles */
        @media print {
            .main-header .btn,
            .nav-tabs,
            .quick-actions {
                display: none !important;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .table {
                font-size: 0.8rem;
            }
        }

        .badge.bg-warning {
            color: #000 !important;
        }
        
        .price-info {
            line-height: 1.2;
        }
        
        .price-info .text-muted {
            font-size: 0.75rem;
        }
        
        /* Notification container */
        #notificationContainer {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 9999;
            pointer-events: none;
        }
        
        #notificationContainer .alert {
            pointer-events: all;
        }

        /* Last Active Users - activity status dots */
        .activity-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .activity-status .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }
        .activity-status.online  { color: #0f5132; background: #d1e7dd; }
        .activity-status.online  .status-dot { background: #198754; animation: pulse-dot 1.5s infinite; }
        .activity-status.recent  { color: #055160; background: #cff4fc; }
        .activity-status.recent  .status-dot { background: #0dcaf0; }
        .activity-status.today   { color: #664d03; background: #fff3cd; }
        .activity-status.today   .status-dot { background: #ffc107; }
        .activity-status.this_week { color: #41464b; background: #e2e3e5; }
        .activity-status.this_week .status-dot { background: #6c757d; }
        .activity-status.inactive { color: #842029; background: #f8d7da; }
        .activity-status.inactive .status-dot { background: #dc3545; }
        .activity-status.never   { color: #41464b; background: #e9ecef; }
        .activity-status.never   .status-dot { background: #adb5bd; }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.4); }
        }

        .level-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="header-shell">
                <div>
                    <h2 class="page-title mb-1">Manage Access</h2>
                    <p class="page-subtitle mb-0">Manage course access, account permissions, and platform settings</p>
                </div>
                <div class="header-actions">
                    <a href="{{ route('courses.index') }}" class="header-btn back-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Courses</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Enhanced Stats Dashboard -->
        <details class="card mb-3" id="platformOverview">
            <summary class="p-3 fw-semibold" style="cursor: pointer;">Platform overview <span class="fw-normal text-muted ms-2">User totals and system health</span></summary>
            <div class="p-3 pt-0">
        <div class="row stats-row g-3">
            <div class="col-xl col-md-6 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number text-primary">{{ isset($userStats) ? ($userStats['total_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change">
                        <i class="fas fa-user-graduate"></i> {{ isset($userStats) ? ($userStats['student_users'] ?? 0) : 0 }} students
                        &nbsp;·&nbsp;
                        <i class="fas fa-chalkboard-teacher"></i> {{ isset($userStats) ? ($userStats['tutor_users'] ?? 0) : 0 }} tutors
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number text-success" id="stat-active-users">{{ isset($userStats) ? ($userStats['active_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Active Now</div>
                    <div class="stat-change positive">
                        <i class="fas fa-circle" style="font-size:0.5rem;vertical-align:middle;color:#198754;animation:pulse-dot 1.5s infinite;"></i> Live sessions
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number text-warning">{{ isset($userStats) ? ($userStats['admin_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Administrators</div>
                    <div class="stat-change">
                        <i class="fas fa-shield-alt"></i> {{ isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0 ? round(($userStats['admin_users'] / $userStats['total_users']) * 100, 1) : 0 }}% of users
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number text-info">{{ isset($userStats) ? ($userStats['total_enrollments'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Enrollments</div>
                    <div class="stat-change positive">
                        <i class="fas fa-graduation-cap"></i> {{ isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0 ? round($userStats['total_enrollments'] / $userStats['total_users'], 1) : 0 }} avg per user
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number" style="color:#8b5cf6;" id="stat-new-today">{{ isset($userStats) ? ($userStats['new_users_today'] ?? 0) : 0 }}</div>
                    <div class="stat-label">New Today</div>
                    <div class="stat-change positive">
                        <i class="fas fa-user-plus"></i> Registered today
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health Indicators — live-refreshed via /manage-access/system-stats -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-heartbeat me-2" style="color: #28a745; font-size: 1.3rem;"></i>
                            <span style="color: #2c3e50; font-weight: 700; font-size: 1.1rem;">System Health</span>
                            <span class="system-health-badge" id="sysHealthBadge">
                                <i class="fas fa-check-circle"></i>All Systems Operational
                            </span>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" id="refreshSystemStats" title="Refresh system stats">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body py-2">
                        <div class="row text-center g-2">
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-database mb-1" id="sysDbIcon" style="font-size:1.1rem;color:#198754;"></i>
                                    <span class="small fw-semibold" id="sysDbStatus">Online</span>
                                    <span class="text-muted" style="font-size:0.7rem;">Database</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-server mb-1" style="font-size:1.1rem;color:#198754;"></i>
                                    <span class="small fw-semibold" id="sysLoad">—</span>
                                    <span class="text-muted" style="font-size:0.7rem;">Server Load</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-clock mb-1" style="font-size:1.1rem;color:#0dcaf0;"></i>
                                    <span class="small fw-semibold" id="sysUptime">—</span>
                                    <span class="text-muted" style="font-size:0.7rem;">Uptime</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-memory mb-1" style="font-size:1.1rem;color:#0dcaf0;"></i>
                                    <span class="small fw-semibold" id="sysMemory">{{ round(memory_get_usage(true) / 1024 / 1024, 1) }}MB</span>
                                    <span class="text-muted" style="font-size:0.7rem;">Memory</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-tachometer-alt mb-1" style="font-size:1.1rem;color:#198754;"></i>
                                    <span class="small fw-semibold" id="sysResponse">—</span>
                                    <span class="text-muted" style="font-size:0.7rem;">Response</span>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fab fa-php mb-1" style="font-size:1.1rem;color:#777BB3;"></i>
                                    <span class="small fw-semibold" id="sysPhp">{{ PHP_VERSION }}</span>
                                    <span class="text-muted" style="font-size:0.7rem;">PHP</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            </div>
        </details>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="manageTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'question-quality' ? 'active' : '' }}" id="question-quality-tab" data-bs-toggle="tab" data-bs-target="#question-quality" type="button" role="tab" aria-controls="question-quality" aria-selected="{{ $activeTab === 'question-quality' ? 'true' : 'false' }}">
                    <i class="fas fa-check-double" aria-hidden="true"></i> Question Quality
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'access' ? 'active' : '' }}" id="access-tab" data-bs-toggle="tab" data-bs-target="#access" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>User Access
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'preview-topics' ? 'active' : '' }}" id="preview-topics-tab" data-bs-toggle="tab" data-bs-target="#preview-topics" type="button" role="tab">
                    <i class="fas fa-eye me-2"></i>Course Previews
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'payment-history' ? 'active' : '' }}" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-history" type="button" role="tab">
                    <i class="fas fa-credit-card me-2"></i>Payment History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'survey-results' ? 'active' : '' }}" id="survey-results-tab" data-bs-toggle="tab" data-bs-target="#survey-results" type="button" role="tab">
                    <i class="fas fa-chart-pie me-2"></i>Survey Results
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'mentorship' ? 'active' : '' }}" id="mentorship-tab" data-bs-toggle="tab" data-bs-target="#mentorship" type="button" role="tab">
                    <i class="fas fa-user-graduate me-2"></i>Mentorship
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'scheduler-quality' ? 'active' : '' }}" id="scheduler-quality-tab" data-bs-toggle="tab" data-bs-target="#scheduler-quality" type="button" role="tab">
                    <i class="fas fa-brain me-2"></i>Scheduler Quality
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'security-watchlist' ? 'active' : '' }}" id="security-watchlist-tab" data-bs-toggle="tab" data-bs-target="#security-watchlist" type="button" role="tab">
                    <i class="fas fa-user-lock me-2"></i>Security Watchlist
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'video-management' ? 'active' : '' }}" id="video-tab" data-bs-toggle="tab" data-bs-target="#video-management" type="button" role="tab">
                    <i class="fas fa-video me-2"></i>Video Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="{{ route('admin.question-pdf.index') }}">
                    <i class="fas fa-file-pdf me-2"></i>Question PDF
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                    <i class="fas fa-cog me-2"></i>Settings
                </button>
            </li>
        </ul>

        <p class="text-muted mt-3 mb-3" id="manageSectionDescription" role="status" aria-live="polite">{{ $tabDescriptions[$activeTab] }}</p>
        <!-- Tab Content -->
        <div class="tab-content" id="manageTabContent">
            <!-- User Access Tab -->
            <div class="tab-pane fade {{ $activeTab === 'access' ? 'show active' : '' }}" id="access" role="tabpanel">
                <div class="row g-4">
                    <!-- Enhanced Grant Access Form -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-search me-2"></i>Course Access
                                </div>
                                <span class="badge bg-info" data-tooltip="Search and view user and course information">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label" for="user_id">
                                        <i class="fas fa-user me-1"></i>1. Choose a user
                                    </label>
                                    <select class="form-control user-select" id="user_id" name="user_id">
                                        <option value="">Type to search for user...</option>
                                        @if(isset($users) && $users->count() > 0)
                                            @foreach($users as $user)
                                                @php $securityStatus = $user->security_status ?? []; @endphp
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }} data-email="{{ $user->email ?? '' }}" data-number="{{ $user->number ?? '' }}" data-role="{{ $user->role ?? 0 }}" data-name="{{ $user->name ?? '' }}" data-mentored="{{ $user->is_mentored ? '1' : '0' }}" data-security-label="{{ $securityStatus['label'] ?? 'Clear' }}" data-security-blocked="{{ !empty($securityStatus['is_blocked']) ? '1' : '0' }}" data-security-suspicious="{{ !empty($securityStatus['is_suspicious']) ? '1' : '0' }}" data-security-permanent="{{ !empty($securityStatus['is_permanently_locked']) ? '1' : '0' }}" data-security-score="{{ $securityStatus['hard_lock_score'] ?? 0 }}" data-security-last="{{ $securityStatus['last_suspicious_at'] ?? '' }}">
                                                    {{ $user->name ?? 'Unknown' }} ({{ $user->email ?? 'No Email' }})
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>NO USERS FOUND</option>
                                        @endif
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Start typing name or email to find users quickly
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="course_id">
                                        <i class="fas fa-graduation-cap me-1"></i>2. Choose a course
                                    </label>
                                    <select class="form-select" id="course_id" name="course_id" disabled aria-describedby="accessSelectionSummary">
                                        <option value="">Choose course...</option>
                                        @if(isset($courses) && $courses->count() > 0)
                                            @foreach($courses as $course)
                                                <option value="{{ isset($course->id) ? $course->id : '' }}" {{ old('course_id') == $course->id ? 'selected' : '' }} data-price="{{ isset($course->price) ? $course->price : 0 }}">
                                                    {{ isset($course->name) ? $course->name : 'Unknown Course' }} ({{ $course->formatted_price ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>NO COURSES FOUND</option>
                                        @endif
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Select a course to grant or revoke access
                                    </div>
                                </div>

                                <div class="border rounded p-3 mb-3" role="status" aria-live="polite" id="accessSelectionSummary">1. Choose a user. 2. Choose a course. 3. Review their access below.</div>
                                <button type="button" id="retryAccessStatus" class="btn btn-outline-secondary btn-sm mb-3" hidden>Retry loading access</button>
                                <div class="quick-actions mb-3">
                                    <button type="button" class="btn btn-primary w-100 mb-2" id="grantAccessBtn" disabled>
                                        <i class="fas fa-user-plus me-2"></i>Grant Course Access
                                    </button>
                                    <button type="button" class="btn btn-outline-danger w-100" id="revokeAccessBtn" disabled>
                                        <i class="fas fa-user-times me-2"></i>Revoke Course Access
                                    </button>
                                </div>

                                <div class="mt-2 align-items-center justify-content-between p-2 border rounded bg-light mb-3" id="mentorshipToggleWrapper" style="display: none;">
                                    <div>
                                        <i class="fas fa-user-graduate me-2 text-primary"></i>
                                        <strong>Mentorship Program</strong>
                                        <div class="small text-muted">Enable or remove mentorship for this user</div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="mentorshipStatusToggle" style="transform: scale(1.4); cursor: pointer;">
                                    </div>
                                </div>

                                <!-- User Info Display -->
                                <div class="mt-3" id="userInfoSection" style="display: none;">
                                    <div class="d-flex align-items-center p-2 bg-light rounded">
                                        <div class="profile-picture-container me-3" style="width: 40px; height: 40px;">
                                            <img id="userProfileImage" src="" alt="Profile" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                            <div id="userInitialFallback" class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100%; height: 100%; font-size: 16px; font-weight: 600;">
                                                <span id="userInitial">U</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" id="selectedUserName"></div>
                                            <div class="small text-muted" id="selectedUserEmail"></div>
                                            <div class="small text-muted" id="selectedUserNumber"></div>
                                            <div class="small mt-1" id="selectedUserSecurity"></div>
                                        </div>
                                        <div class="ms-auto" id="userMentorshipBadge" style="display: none;">
                                            <span class="badge bg-success">MENTORED</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Admin Management -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-user-shield me-2"></i>Account Permissions
                                </div>
                                <span class="badge bg-warning text-dark" data-tooltip="Manage administrator privileges">
                                    <i class="fas fa-crown"></i> {{ isset($userStats) ? ($userStats['admin_users'] ?? 0) : 0 }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-search me-1"></i>Find User to Manage
                                    </label>
                                    <select class="form-select admin-user-select" id="admin_user_id">
                                        <option value="">Type to search for user...</option>
                                        @if(isset($users) && $users->count() > 0)
                                            @foreach($users as $user)
                                                @php $securityStatus = $user->security_status ?? []; @endphp
                                                <option value="{{ isset($user->id) ? $user->id : '' }}" data-email="{{ isset($user->email) ? $user->email : '' }}" data-number="{{ isset($user->number) ? $user->number : '' }}" data-role="{{ isset($user->role) ? $user->role : 0 }}" data-name="{{ isset($user->name) ? $user->name : '' }}" data-security-label="{{ $securityStatus['label'] ?? 'Clear' }}" data-security-blocked="{{ !empty($securityStatus['is_blocked']) ? '1' : '0' }}" data-security-suspicious="{{ !empty($securityStatus['is_suspicious']) ? '1' : '0' }}" data-security-permanent="{{ !empty($securityStatus['is_permanently_locked']) ? '1' : '0' }}" data-security-score="{{ $securityStatus['hard_lock_score'] ?? 0 }}" data-security-last="{{ $securityStatus['last_suspicious_at'] ?? '' }}">
                                                    {{ isset($user->name) ? $user->name : 'Unknown' }} ({{ isset($user->email) ? $user->email : 'No Email' }}) {{ (isset($user->role) && $user->role === \App\Models\User::ROLE_ADMIN) ? '- ADMIN' : '' }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>NO USERS FOUND</option>
                                        @endif
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Search by name or email to manage admin privileges
                                    </div>
                                </div>
                                
                                <!-- Always Visible Admin Actions -->
                                <div id="adminActions">
                                    <div class="mb-3" id="selectedUserInfo" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong id="selectedAdminUserName">User Name</strong>
                                            <span id="selectedAdminUserStatus" class="badge bg-secondary">STATUS</span>
                                        </div>
                                        <div class="small text-muted" id="selectedAdminUserEmail">user@example.com</div>
                                        <div class="small text-muted" id="selectedAdminUserNumber"></div>
                                        <div class="small mt-2" id="selectedAdminSecurityStatus"></div>
                                    </div>
                                    
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-success w-100" id="promoteToAdminBtn" disabled>
                                                <i class="fas fa-user-shield me-2"></i>Promote to Admin
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-danger w-100" id="removeAdminBtn" disabled>
                                                <i class="fas fa-user-times me-2"></i>Revoke Admin
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 small text-muted text-center" id="adminActionHint">
                                        Select a user above to manage their admin privileges
                                    </div>
                                    <div class="mt-3 border rounded p-3 bg-light" id="securityActionPanel" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <div class="fw-semibold">
                                                    <i class="fas fa-shield-alt me-1"></i>Security Status
                                                </div>
                                                <div class="small text-muted" id="securityActionText">No suspicious lock state detected.</div>
                                            </div>
                                            <button type="button" class="btn btn-outline-danger btn-sm" id="clearSecurityLockBtn" disabled>
                                                <i class="fas fa-unlock me-1"></i>Revoke Lock
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-layer-group me-2"></i>Subject Locks
                                </div>
                                <span class="badge bg-secondary" id="subjectLockSummary">Select a course</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5">
                                        <label class="form-label" for="subject_lock_course_id">
                                            <i class="fas fa-graduation-cap me-1"></i>Course
                                        </label>
                                        <select class="form-select" id="subject_lock_course_id">
                                            <option value="">Choose course...</option>
                                            @if(isset($courses) && $courses->count() > 0)
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label" for="subject_lock_subject">
                                            <i class="fas fa-bookmark me-1"></i>Subject
                                        </label>
                                        <select class="form-select" id="subject_lock_subject" disabled>
                                            <option value="">Select a course first...</option>
                                        </select>
                                        <div class="form-text" id="subjectLockStats">Subjects are loaded from existing topics.</div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-danger flex-fill" id="lockSubjectBtn" disabled>
                                                <i class="fas fa-lock me-1"></i>Lock
                                            </button>
                                            <button type="button" class="btn btn-success flex-fill" id="unlockSubjectBtn" disabled>
                                                <i class="fas fa-unlock me-1"></i>Unlock
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Total Topics</th>
                                                <th>Locked Topics</th>
                                            </tr>
                                        </thead>
                                        <tbody id="subjectLockTableBody">
                                            <tr>
                                                <td colspan="3" class="text-muted text-center py-3">Choose a course to view subject lock status.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Recent Activity -->
                <div class="row mt-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-history me-2"></i>Recent User Enrollments
                                </div>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="enrollmentFilter" style="width: auto;">
                                        <option value="all">All Users</option>
                                        <option value="mentored">Mentorship Only</option>
                                        <option value="has_active">With Active Courses</option>
                                        <option value="has_revoked">With Revoked Courses</option>
                                    </select>
                                    <button class="btn btn-outline-secondary btn-sm" id="refreshEnrollments" data-tooltip="Refresh enrollment data">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="enrollmentsTable">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Mentorship</th>
                                                <th>Courses</th>
                                                <th>Status</th>
                                                <th>Last Activity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($enrolledUsers ?? [] as $userRow)
                                                <tr data-active-count="{{ $userRow->active_count }}" data-revoked-count="{{ $userRow->revoked_count }}" data-is-mentored="{{ $userRow->is_mentored ? '1' : '0' }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                                    {{ strtoupper(substr($userRow->email, 0, 1)) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium">
                                                                    {{ $userRow->name ?? 'Unknown' }}
                                                                </div>
                                                                <small class="text-muted">{{ $userRow->email }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($userRow->is_mentored)
                                                            <span class="badge bg-success" style="font-size: 0.9rem; padding: 0.4em 0.8em;">1</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border" style="font-size: 0.9rem; padding: 0.4em 0.8em;">0</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1" style="max-width: 250px;">
                                                            @foreach($userRow->courses as $course)
                                                                @php $c = (object) $course; @endphp
                                                                <span class="badge {{ $c->is_enrolled ? 'bg-primary' : 'bg-secondary text-decoration-line-through' }}" style="font-size: 0.72rem;" title="{{ $c->is_enrolled ? 'Active' : 'Revoked' }}">
                                                                    {{ $c->course_name }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($userRow->active_count > 0 && $userRow->revoked_count > 0)
                                                            <span class="badge bg-success">{{ $userRow->active_count }} active</span>
                                                            <span class="badge bg-secondary">{{ $userRow->revoked_count }} revoked</span>
                                                        @elseif($userRow->active_count > 0)
                                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>{{ $userRow->active_count }} active</span>
                                                        @else
                                                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i>All revoked</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($userRow->last_activity)
                                                            <div>{{ \Carbon\Carbon::parse($userRow->last_activity)->format('M d, Y') }}</div>
                                                            <small class="text-muted">{{ \Carbon\Carbon::parse($userRow->last_activity)->diffForHumans() }}</small>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">
                                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                                        <div>No enrollments found</div>
                                                        <small>Start by granting course access to users</small>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats Sidebar -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-pie me-2"></i>Course Distribution
                            </div>
                            <div class="card-body">
                                @foreach($courses ?? [] as $course)
                                    @php
                                        $enrolledCount = isset($courseStats) ? ($courseStats[$course->id] ?? 0) : 0;
                                        $percentage = (isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0) ? round(($enrolledCount / $userStats['total_users']) * 100) : 0;
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center mb-2" style="overflow: hidden;">
                                        <div style="flex: 1; min-width: 0;">
                                            <span class="data-pill course" style="max-width: 100%; display: inline-block; margin-bottom: 2px;">{{ $course->name }}</span>
                                        </div>
                                        <div class="text-end flex-shrink-0" style="margin-left: 8px;">
                                            <div class="fw-bold">{{ $enrolledCount }}</div>
                                            <small class="text-muted">{{ $percentage }}% of users</small>
                                        </div>
                                    </div>
                                    <div class="progress mb-3" style="height: 4px;">
                                        <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Quick Overview -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-tachometer-alt me-2"></i>Quick Overview
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small"><i class="fas fa-users me-1 text-primary"></i> Total Users</span>
                                    <span class="badge bg-primary">{{ isset($userStats) ? ($userStats['total_users'] ?? 0) : 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small"><i class="fas fa-book-open me-1 text-success"></i> Active Enrollments</span>
                                    <span class="badge bg-success">{{ isset($userStats) ? ($userStats['total_enrollments'] ?? 0) : 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small"><i class="fas fa-graduation-cap me-1 text-info"></i> Courses Available</span>
                                    <span class="badge bg-info">{{ count($courses ?? []) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small"><i class="fas fa-chart-line me-1 text-warning"></i> Avg Courses/User</span>
                                    @php
                                        $avgCoursesPerUser = (isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0)
                                            ? round($userStats['total_enrollments'] / $userStats['total_users'], 1)
                                            : 0;
                                    @endphp
                                    <span class="badge bg-warning text-dark">{{ $avgCoursesPerUser }}</span>
                                </div>
                                @php
                                    $enrolledUsersCount = isset($userStats) ? ($userStats['enrolled_users_count'] ?? 0) : 0;
                                    $unenrolledUsers = max(0, (isset($userStats) ? $userStats['total_users'] : 0) - $enrolledUsersCount);
                                @endphp
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small"><i class="fas fa-user-slash me-1 text-secondary"></i> No Enrollments</span>
                                    <span class="badge bg-secondary">{{ $unenrolledUsers }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last Active Users -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-user-clock me-2"></i>Last Active Users
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="input-group input-group-sm" style="width: 240px;">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="lastActiveSearch" placeholder="Search by name or email...">
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm" id="refreshLastActive" title="Refresh">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="lastActiveTable">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Level</th>
                                                <th>Last Active</th>
                                                <th>Last Quiz</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lastActiveBody">
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                                    <div>Loading user activity...</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" id="lastActivePagination" style="display: none;">
                                    <small class="text-muted" id="lastActiveInfo">Showing 0 users</small>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" id="lastActivePrev" disabled>
                                            <i class="fas fa-chevron-left"></i> Prev
                                        </button>
                                        <button class="btn btn-outline-secondary" id="lastActivePageDisplay" disabled style="min-width: 80px;"></button>
                                        <button class="btn btn-outline-secondary" id="lastActiveNext" disabled>
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'security-watchlist' ? 'show active' : '' }}" id="security-watchlist" role="tabpanel">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-user-lock me-2"></i>Security Watchlist
                                </div>
                                <button class="btn btn-outline-secondary btn-sm" id="refreshSecurityUsers" title="Refresh security watchlist">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="securityUsersTable">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Status</th>
                                                <th>Last Event</th>
                                                <th>Risk Count</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>Loading security watchlist...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'preview-topics' ? 'show active' : '' }}" id="preview-topics" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-eye me-2"></i>Choose Preview Course
                                </div>
                                <span class="badge bg-info">Preview Users</span>
                            </div>
                            <div class="card-body">
                                <label class="form-label" for="preview_course_id">
                                    <i class="fas fa-graduation-cap me-1"></i>Course
                                </label>
                                <select class="form-select" id="preview_course_id">
                                    <option value="">Choose course...</option>
                                    @if(isset($courses) && $courses->count() > 0)
                                        @foreach($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-text">
                                    Select the topics that preview users can open before paying.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-list-check me-2"></i>Open Topics for Preview
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="previewSelectNone" disabled>
                                        <i class="fas fa-xmark me-1"></i>None
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" id="savePreviewTopics" disabled>
                                        <i class="fas fa-save me-1"></i>Save
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="previewTopicsEmpty" class="text-muted text-center py-4">
                                    Pick a course to manage preview topics.
                                </div>
                                <div id="previewTopicsSummary" class="alert alert-info py-2 px-3 mb-3" style="display: none;"></div>
                                <div id="previewTopicsList" class="preview-topic-list" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mentorship Tab -->
            <div class="tab-pane fade {{ $activeTab === 'mentorship' ? 'show active' : '' }}" id="mentorship" role="tabpanel">
                <!-- Mentorship Stats -->
                <div class="row g-3 mb-4" id="mentorship-stats-container">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-primary" id="mentorship-total-students">0</div>
                            <div class="stat-label">Total Mentored</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-danger" id="mentorship-struggling-students">0</div>
                            <div class="stat-label">Struggling</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-success" id="mentorship-avg-task-progress">0%</div>
                            <div class="stat-label">Avg Task Progress</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-info" id="mentorship-total-quizzes">0</div>
                            <div class="stat-label">Cumulative Quizzes</div>
                        </div>
                    </div>
                </div>

                <!-- Mentorship Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user-graduate me-2"></i>Mentorship Program Performance
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" id="refreshMentorshipData">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="mentorship-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Avg Score</th>
                                        <th>Task Progress</th>
                                        <th>Coverage</th>
                                        <th>Discipline</th>
                                        <th>Trend</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="mentorship-table-body">
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div class="mt-2 text-muted">Loading mentorship data...</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Tab -->
            <div class="tab-pane fade {{ $activeTab === 'payment-history' ? 'show active' : '' }}" id="payment-history" role="tabpanel">       
         <!-- Payment Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-primary" id="total-revenue">0.00 EGP</div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-success" id="completed-count">0</div>
                            <div class="stat-label">Completed Payments</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-warning" id="pending-count">0</div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-danger" id="rejected-count">0</div>
                            <div class="stat-label">Rejected</div>
                        </div>
                    </div>
                </div>

                <div class="small text-muted mb-3">
                    <div class="d-flex flex-wrap gap-3 mb-1">
                        <span>Confirmed payments: <strong id="confirmed-payment-revenue">0.00 EGP</strong></span>
                        <span>Manual enrollments (<span id="manual-enrollment-count">0</span>): <strong id="manual-enrollment-revenue">0.00 EGP</strong></span>
                    </div>
                    Manual paid enrollments use current course prices; confirmed payments use recorded amounts. No double-counting.
                </div>

                <!-- Payment History Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-credit-card me-2"></i>Payment History</span>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" id="payment-search" placeholder="Search payments...">
                            <button class="btn btn-outline-secondary" type="button" id="payment-search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Enhanced table with horizontal scroll -->
                        <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                            <table class="table table-hover mb-0" style="min-width: 1200px;">
                                <thead>
                                    <tr>
                                        <th style="width: 80px; min-width: 80px;">ID</th>
                                        <th style="width: 200px; min-width: 200px;">User</th>
                                        <th style="width: 150px; min-width: 150px;">Course</th>
                                        <th style="width: 100px; min-width: 100px;">Amount</th>
                                        <th style="width: 120px; min-width: 120px;">Method</th>
                                        <th style="width: 100px; min-width: 100px;">Status</th>
                                        <th style="width: 220px; min-width: 220px;">
                                            <strong style="color: #2196f3; font-size: 0.9rem;">
                                                <i class="fas fa-receipt me-1"></i>
                                                Transaction Code
                                            </strong>
                                        </th>
                                        <th style="width: 120px; min-width: 120px;">Date</th>
                                        <th style="width: 120px; min-width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="payments-table-body">
                                    <!-- Payments will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Scroll indicator -->
                        <div class="px-3 py-2 bg-light border-top" id="scroll-indicator" style="display: none;">
                            <small class="text-muted">
                                <i class="fas fa-arrows-alt-h me-1"></i>
                                Scroll horizontally to view all columns
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Showing <span id="showing-records">0</span> of <span id="total-records-count">0</span> payments
                            </div>
                            <nav id="payment-pagination" aria-label="Payment pagination">
                                <!-- Pagination will be added here -->
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Tab -->
            <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}" id="settings" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-eye-slash me-2"></i>Topic Visibility</div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="hideEmptyTopicsToggle"
                                @checked(\App\Models\AdminSetting::get('hide_empty_topics', false))>
                            <label class="form-check-label" for="hideEmptyTopicsToggle">Hide topics with no questions</label>
                        </div>
                        <p class="text-muted small mt-2 mb-0">Hide empty topics from the topics page for everyone. Directly assigned and linked questions count; video topics stay visible. Topics reappear when questions are added. Nothing is deleted.</p>
                        <div id="emptyTopicVisibilityStatus" class="small mt-2" role="status" aria-live="polite"></div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-users-cog me-2"></i>Live User Counter Settings
                                </div>
                                <span class="badge bg-info">
                                    <i class="fas fa-chart-line"></i> Social Proof
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Social Proof Feature:</strong> Enable fake live user counts to show believable activity on your site. 
                                    The counter will fluctuate naturally between your min/max range, updating every 30 seconds.
                                </div>
                                
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <label class="form-label mb-0">
                                                <i class="fas fa-toggle-on me-1"></i><strong>Enable Fake Live Users</strong>
                                            </label>
                                            <div class="small text-muted">Show fake user counts instead of real session data</div>
                                        </div>
                                        <div class="form-check form-switch" style="font-size: 1.5rem;">
                                            <input class="form-check-input" type="checkbox" id="fakeLiveUsersToggle" style="cursor: pointer;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="fakeUserSettings" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-arrow-down me-1"></i>Minimum Count
                                            </label>
                                            <div class="input-group">
                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustRange('min', -10)"><i class="fas fa-minus"></i></button>
                                                <input type="number" class="form-control text-center" id="fakeUsersMin" min="1" max="999" value="87">
                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustRange('min', 10)"><i class="fas fa-plus"></i></button>
                                            </div>
                                            <div class="form-text">
                                                Lowest number of "active users" to display
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <i class="fas fa-arrow-up me-1"></i>Maximum Count
                                            </label>
                                            <div class="input-group">
                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustRange('max', -10)"><i class="fas fa-minus"></i></button>
                                                <input type="number" class="form-control text-center" id="fakeUsersMax" min="1" max="999" value="143">
                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustRange('max', 10)"><i class="fas fa-plus"></i></button>
                                            </div>
                                            <div class="form-text">
                                                Highest number of "active users" to display
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <div class="small text-muted mb-2">
                                            <i class="fas fa-lightbulb me-1"></i><strong>How it works:</strong>
                                        </div>
                                        <ul class="small mb-0">
                                            <li>Numbers change every 30 seconds for natural variation</li>
                                            <li>Counts are rounded to nearest 10 with small offsets (e.g., 97, 108, 123)</li>
                                            <li>Believable ranges: 50-100 for small sites, 80-150 for medium, 100-300 for large</li>
                                            <li>Only visible to regular users - admins can see real counts in System Health</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-success" id="saveFakeUserSettings">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="testFakeUsers">
                                            <i class="fas fa-eye me-2"></i>Preview Current Count
                                        </button>
                                    </div>
                                    
                                    <div id="previewCount" class="mt-3" style="display: none;">
                                        <div class="alert alert-success">
                                            <strong>Current Preview:</strong> <span id="previewNumber" class="fs-4"></span> users active now
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-bar me-2"></i>Quick Stats
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Current Mode:</span>
                                        <span class="badge bg-secondary" id="currentMode">Loading...</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Real Active Users:</span>
                                        <strong id="realUserCount">{{ isset($userStats) ? ($userStats['active_users'] ?? 0) : 0 }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Displayed Count:</span>
                                        <strong id="displayedCount">-</strong>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="small text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <strong>Admin Note:</strong> These settings only affect the public-facing live user counter. 
                                    Real analytics and admin dashboards always show accurate data.
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-history me-2"></i>Recommended Ranges
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Small Site:</strong>
                                    <div class="small text-muted">Min: 47, Max: 89</div>
                                </div>
                                <div class="mb-2">
                                    <strong>Medium Site:</strong>
                                    <div class="small text-muted">Min: 87, Max: 143</div>
                                </div>
                                <div class="mb-2">
                                    <strong>Large Site:</strong>
                                    <div class="small text-muted">Min: 127, Max: 287</div>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-outline-primary w-100" onclick="applyPreset(87, 143)">
                                        Apply Medium Preset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fake Students Counter Section -->
                <div class="row g-4 mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-user-graduate me-2"></i>Active Medical Students Counter
                                </div>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-graduation-cap"></i> Homepage Stat
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Homepage Counter:</strong> Control the "Active Medical Students" number shown at the bottom of your homepage.
                                </div>
                                
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <label class="form-label mb-0">
                                                    <i class="fas fa-toggle-on me-1"></i><strong>Enable Fake Students Count</strong>
                                                </label>
                                                <div class="small text-muted">Override real student count with custom number</div>
                                            </div>
                                            <div class="form-check form-switch" style="font-size: 1.5rem;">
                                                <input class="form-check-input" type="checkbox" id="fakeStudentsToggle" style="cursor: pointer;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6" id="fakeStudentsSettings" style="display: none;">
                                        <label class="form-label">
                                            <i class="fas fa-users me-1"></i>Student Count
                                        </label>
                                        <div class="input-group input-group-lg">
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStudentCount(-100)">
                                                <i class="fas fa-minus"></i> 100
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStudentCount(-10)">
                                                <i class="fas fa-minus"></i> 10
                                            </button>
                                            <input type="number" class="form-control text-center fw-bold" id="fakeStudentsCount" min="0" max="99999" value="2847" style="font-size: 1.5rem;">
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStudentCount(10)">
                                                <i class="fas fa-plus"></i> 10
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" onclick="adjustStudentCount(100)">
                                                <i class="fas fa-plus"></i> 100
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Displayed on homepage "Why Medical Students Choose Us" section
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success" id="saveFakeStudentsSettings">
                                        <i class="fas fa-save me-2"></i>Save Student Count
                                    </button>
                                    <span class="ms-3 text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>Real count: <strong>{{ $userStats['student_users'] ?? 0 }}</strong> students
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fake Enrolled Users Per Course Section -->
                <div class="row g-4 mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-users-cog me-2"></i>Fake Enrolled Users Per Course
                                </div>
                                <span class="badge bg-primary">
                                    <i class="fas fa-id-card"></i> Profile Circles
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Course Profile Circles:</strong> Control the fake enrolled user count displayed as profile circles on each course card. This shows social proof to encourage enrollment.
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%;">Course Name</th>
                                                <th style="width: 15%;" class="text-center">Real Enrolled</th>
                                                <th style="width: 15%;" class="text-center">Fake Mode</th>
                                                <th style="width: 20%;">Fake Count</th>
                                                <th style="width: 10%;" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="courseFakeEnrolledTable">
                                            @if(isset($courses) && $courses->count() > 0)
                                                @foreach($courses as $course)
                                                    <tr data-course-id="{{ $course->id }}">
                                                        <td>
                                                            <strong>{{ $course->name }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-secondary">{{ $course->enrolledUsers->count() ?? 0 }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-flex justify-content-center">
                                                                <input class="form-check-input course-fake-toggle" 
                                                                       type="checkbox" 
                                                                       data-course-id="{{ $course->id }}"
                                                                       {{ $course->fake_enrolled_enabled ? 'checked' : '' }}>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustCourseCount({{ $course->id }}, -10)">
                                                                    <i class="fas fa-minus"></i>
                                                                </button>
                                                                <input type="number" 
                                                                       class="form-control text-center course-fake-count" 
                                                                       data-course-id="{{ $course->id }}"
                                                                       min="0" 
                                                                       max="9999" 
                                                                       value="{{ $course->fake_enrolled_count ?? 0 }}">
                                                                <button class="btn btn-outline-secondary" type="button" onclick="adjustCourseCount({{ $course->id }}, 10)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-success save-course-fake" data-course-id="{{ $course->id }}">
                                                                <i class="fas fa-save"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No courses found</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="saveAllCourseFakeSettings">
                                        <i class="fas fa-save me-2"></i>Save All Courses
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="applyBulkCourseCount()">
                                        <i class="fas fa-magic me-2"></i>Apply Bulk Count
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Video Management Tab -->
            <div class="tab-pane fade {{ $activeTab === 'video-management' ? 'show active' : '' }}" id="video-management" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-video me-2"></i>Add Video to Topic
                                </div>
                                <span class="badge bg-info">
                                    <i class="fas fa-youtube"></i> YouTube Integration
                                </span>
                            </div>
                            <div class="card-body">
                                <form id="addVideoForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-graduation-cap me-1"></i>Select Course
                                        </label>
                                        <select class="form-select" id="video_course_id" name="course_id">
                                            <option value="">Choose course...</option>
                                            @if(isset($courses) && $courses->count() > 0)
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-book me-1"></i>Select Topic
                                        </label>
                                        <select class="form-select" id="video_topic_id" name="topic_id" disabled>
                                            <option value="">First select a course...</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-youtube me-1"></i>YouTube URL
                                        </label>
                                        <input type="text" class="form-control" id="youtube_url" name="youtube_url" placeholder="https://youtu.be/VIDEO_ID, YouTube URL, or video ID">
                                        <div class="form-text">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Paste any YouTube URL - we'll extract the video ID automatically
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-tag me-1"></i>Video Title
                                        </label>
                                        <input type="text" class="form-control" id="video_title" name="title" placeholder="Enter video title">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-align-left me-1"></i>Description (Optional)
                                        </label>
                                        <textarea class="form-control" id="video_description" name="description" rows="3" placeholder="Brief description of the video content"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Video
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-list me-2"></i>Existing Videos
                                </div>
                                <span class="badge bg-success" id="video-count">
                                    <i class="fas fa-video"></i> Loading...
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Topic</th>
                                                <th>Video</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="videos-table-body">
                                            <tr>
                                                <td colspan="4" class="text-center py-3">
                                                    <i class="fas fa-spinner fa-spin"></i> Loading videos...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.partials.question-quality')
            @include('admin.partials.survey-results', ['surveyInsights' => $surveyInsights ?? [], 'activeTab' => $activeTab])
            @include('admin.partials.scheduler-calibration', ['schedulerCalibration' => $schedulerCalibration ?? [], 'activeTab' => $activeTab])
        </div>
    </div>

    <!-- Notification Container -->
    <div class="notification-container" id="notificationContainer"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        window.MANAGE_ACCESS_CONFIG = {
            csrfToken: '{{ csrf_token() }}',
            activeTab: '{{ $activeTab ?? "" }}',
            tabDescriptions: @json($tabDescriptions),
            studentProfileBaseUrl: '{{ url('/tutor/student') }}',
            deleteUserBaseUrl: '{{ url('/admin/delete/user') }}',
            routes: {
                questionQuality: '{{ route('admin.question-quality') }}',
                adminVideosIndex: '{{ route('admin.videos.index') }}',
                adminVideosStore: '{{ route('admin.videos.store') }}',
                adminVideosDestroyBase: '{{ url('/admin/videos') }}',
                previewTopicsBase: '{{ url('/admin/courses') }}',
                courseTopicsBase: '{{ url('/api/courses') }}',
                courseSubjects: '{{ route('admin.user-course.course-subjects') }}',
                lockSubject: '{{ route('admin.user-course.subjects.lock') }}',
                unlockSubject: '{{ route('admin.user-course.subjects.unlock') }}'
            },
            courseOptions: {!! json_encode(
                isset($courses) && $courses->count() > 0
                    ? $courses->map(fn ($c) => [
                        'id' => $c->id,
                        'price' => $c->price,
                        'name' => $c->name,
                        'formattedPrice' => $c->formatted_price ?? 'N/A',
                    ])->values()->toArray()
                    : [],
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ) !!}
        };
    </script>
    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-payment-list.js') }}"></script>
    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-manage-access.js') }}"></script>
    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-payment-actions.js') }}"></script>
    <script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-question-quality.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Mentorship Tab Logic
            $('#mentorship-tab').on('shown.bs.tab', function (e) {
                loadMentorshipData();
            });

            $('#refreshMentorshipData').on('click', function() {
                const $btn = $(this);
                $btn.find('i').addClass('fa-spin');
                loadMentorshipData(function() {
                    $btn.find('i').removeClass('fa-spin');
                });
            });

            function loadMentorshipData(callback) {
                const $tbody = $('#mentorship-table-body');
                
                $.ajax({
                    url: '/manage-access/mentorship-data',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Update summary stats
                            $('#mentorship-total-students').text(response.summary.total_students);
                            $('#mentorship-avg-task-progress').text(response.summary.average_task_progress + '%');
                            $('#mentorship-struggling-students').text(response.summary.struggling_students);
                            
                            const cumulativeQuizzes = response.students.reduce((acc, s) => acc + (s.enhanced_metrics.total_quizzes || 0), 0);
                            $('#mentorship-total-quizzes').text(cumulativeQuizzes);

                            // Render table
                            let html = '';
                            if (!response.students || response.students.length === 0) {
                                html = '<tr><td colspan="8" class="text-center py-4 text-muted">No mentored students found.</td></tr>';
                            } else {
                                response.students.forEach(function(student) {
                                    const metrics = student.enhanced_metrics;
                                    const discipline = metrics.discipline;
                                    const taskProgress = metrics.task_progress || 0;
                                    const coverage = metrics.topic_coverage;
                                    
                                    // Color coding for score
                                    const scoreClass = metrics.score_class === 'excellent' ? 'bg-success' : 
                                                      (metrics.score_class === 'good' ? 'bg-info' : 'bg-danger');
                                    
                                    // Trend display
                                    let trendIcon = '<i class="fas fa-minus text-muted"></i> Minimal';
                                    if (metrics.performance_trend === 'improving') trendIcon = '<i class="fas fa-arrow-up text-success"></i> Improving';
                                    else if (metrics.performance_trend === 'declining') trendIcon = '<i class="fas fa-arrow-down text-danger"></i> Declining';

                                    // Task Progress Badge
                                    const taskColor = taskProgress >= 80 ? 'success' : (taskProgress >= 50 ? 'warning' : 'danger');
                                    const taskHtml = `
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px; min-width: 60px;">
                                                <div class="progress-bar bg-${taskColor}" role="progressbar" style="width: ${taskProgress}%"></div>
                                            </div>
                                            <span class="small fw-bold">${taskProgress}%</span>
                                        </div>
                                    `;

                                    // Topic Coverage Badge
                                    const coverageHtml = `
                                        <div class="text-nowrap">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-book-open me-1 text-primary"></i> ${coverage.attempted} / ${coverage.total}
                                            </span>
                                            <div class="small text-muted mt-1" style="font-size: 0.7rem;">${coverage.percentage}% Coverage</div>
                                        </div>
                                    `;

                                    // Discipline display
                                    const disciplineBadge = `<span class="badge bg-${discipline.color}">${discipline.status.replace('_', ' ').toUpperCase()}</span>`;
                                    
                                    // Formatting date
                                    const lastActive = student.last_active_at ? new Date(student.last_active_at).toLocaleDateString() : 'Never';

                                    html += `
                                        <tr class="${metrics.score_class === 'poor' || discipline.status === 'inactive' ? 'table-warning' : ''}">
                                            <td>
                                                <div class="fw-bold">${student.name}</div>
                                                <div class="small text-muted">${student.email}</div>
                                            </td>
                                            <td>
                                                <span class="badge ${scoreClass}" style="font-size: 0.85rem;">${metrics.average_score}%</span>
                                                <div class="small text-muted mt-1">${metrics.total_quizzes} quizzes</div>
                                            </td>
                                            <td>${taskHtml}</td>
                                            <td>${coverageHtml}</td>
                                            <td>
                                                ${disciplineBadge}
                                                <div class="small text-muted mt-1" style="max-width: 150px; font-size: 0.75rem;">${discipline.reason}</div>
                                            </td>
                                            <td>${trendIcon}</td>
                                            <td style="font-size: 0.8rem;">${lastActive}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/tutor/student/${student.id}" class="btn btn-outline-primary" title="Details">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                    <a href="${discipline.whatsapp_link}" target="_blank" class="btn btn-outline-success" title="WhatsApp Member">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                });
                            }
                            $tbody.html(html);
                        } else {
                            $tbody.html('<tr><td colspan="6" class="text-center py-4 text-danger">Error: ' + response.message + '</td></tr>');
                        }
                    },
                    error: function(xhr) {
                        $tbody.html('<tr><td colspan="6" class="text-center py-4 text-danger">Failed to connect to the server.</td></tr>');
                    },
                    complete: function() {
                        if (typeof callback === 'function') callback();
                    }
                });
            }

            // Auto-load if tab is already active (via URL)
            if (MANAGE_ACCESS_CONFIG.activeTab === 'mentorship' || window.location.hash === '#mentorship') {
                $('#mentorship-tab').tab('show');
                loadMentorshipData();
            }
        });
    </script>

    @include('partials.admin-dark-mode-script')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
