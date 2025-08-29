<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Access - MedCapsule</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
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
        
        /* Notification System */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        }
        
        .notification {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: auto;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification.success {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
        }
        
        .notification.error {
            border-left: 4px solid #dc3545;
            background: linear-gradient(135deg, #fff8f8 0%, #ffe8e8 100%);
        }
        
        .notification-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .notification.success .notification-icon {
            color: #28a745;
        }
        
        .notification.error .notification-icon {
            color: #dc3545;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .notification-message {
            font-size: 13px;
            color: #666;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s;
        }
        
        .notification-close:hover {
            color: #666;
        }

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
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: var(--shadow-sm);
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
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            color: white;
            border: 2px solid #0d6efd;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            border: 2px solid #198754;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #146c43 0%, #0f5132 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #000;
            border: 2px solid #ffc107;
            font-weight: 700;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #ffb300 0%, #ff8f00 100%);
            color: #000;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: 2px solid #dc3545;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
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
            border-bottom: 3px solid var(--primary-color);
            padding: 1rem 0.75rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            white-space: nowrap;
            font-size: 0.95rem;
        }

        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-weight: 500;
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
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
            box-shadow: var(--shadow);
        }

        .nav-tabs {
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 1.25rem 2rem;
            transition: all 0.15s ease;
            position: relative;
            background: #f8f9fa;
            margin-right: 0.5rem;
            border-radius: 8px 8px 0 0;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background: #e3f2fd;
            transform: translateY(-2px);
        }

        .nav-tabs .nav-link.active {
            color: white;
            background: var(--primary-color);
            border-bottom: none;
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
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
            
            .quick-actions .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            
            .stats-row .col-md-3 {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --light-color: #1a1a1a;
                --dark-color: #ffffff;
                --border-color: #333333;
                --text-muted: #aaaaaa;
            }
        }

        .form-text {
            color: #495057 !important;
            font-weight: 500;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .form-text i {
            color: var(--icon-color);
            margin-right: 0.5rem;
        }

        .form-check-label {
            color: #495057;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .text-danger {
            color: #dc3545 !important;
            font-weight: 700;
        }

        .text-muted {
            color: #6c757d !important;
            font-weight: 500;
        }

        .small {
            font-size: 0.9rem !important;
            font-weight: 500;
        }

        /* Enhanced visibility for all text elements */
        h1, h2, h3, h4, h5, h6 {
            color: var(--header-color) !important;
            font-weight: 700;
        }

        p, div, span {
            color: #495057;
        }

        /* Header Button Styles - Minimal and Clean */
        .header-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            background: transparent;
        }

        .header-btn:hover {
            color: #495057;
            background: #f8f9fa;
            border-color: #e9ecef;
            text-decoration: none;
        }

        .header-btn i {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .header-btn span {
            font-weight: 500;
        }

        /* Minimal System Health Badge */
        .system-health-badge {
            background: transparent !important;
            color: #28a745 !important;
            border: 1px solid #28a745;
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
            font-weight: 500 !important;
            border-radius: 4px;
        }

        .system-health-badge i {
            font-size: 0.75rem;
            margin-right: 0.25rem;
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
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="page-title mb-1">User Management</h2>
                    <p class="page-subtitle mb-0">Grant access • Manage admins • Track payments</p>
                </div>
                <div class="d-flex gap-3">
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
        <div class="row stats-row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-primary">{{ isset($userStats) ? ($userStats['total_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change">
                        <i class="fas fa-users"></i> Total registered users
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-success">{{ isset($userStats) ? ($userStats['active_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Active Users</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> {{ isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0 ? round(($userStats['active_users'] / $userStats['total_users']) * 100) : 0 }}% active
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-warning">{{ isset($userStats) ? ($userStats['admin_users'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Administrators</div>
                    <div class="stat-change">
                        <i class="fas fa-shield-alt"></i> {{ isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0 ? round(($userStats['admin_users'] / $userStats['total_users']) * 100, 1) : 0 }}% of users
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-info">{{ isset($userStats) ? ($userStats['total_enrollments'] ?? 0) : 0 }}</div>
                    <div class="stat-label">Total Enrollments</div>
                    <div class="stat-change positive">
                        <i class="fas fa-graduation-cap"></i> {{ isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0 ? round($userStats['total_enrollments'] / $userStats['total_users'], 1) : 0 }} avg per user
                    </div>
                </div>
            </div>
        </div>

        <!-- System Health Indicators -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-heartbeat me-2" style="color: #28a745; font-size: 1.3rem;"></i>
                        <span style="color: #2c3e50; font-weight: 700; font-size: 1.1rem;">System Health</span>
                        <span class="system-health-badge">
                            <i class="fas fa-check-circle"></i>All Systems Operational
                        </span>
                    </div>
                    <div class="card-body py-2">
                        <div class="row text-center">
                            <div class="col-md-3 col-6">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-database text-success me-2"></i>
                                    <span class="small">Database: <strong>{{ DB::connection()->getPdo() ? 'Online' : 'Offline' }}</strong></span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-server text-success me-2"></i>
                                    <span class="small">Server: <strong>{{ function_exists('sys_getloadavg') ? 'Healthy' : 'Unknown' }}</strong></span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-users text-info me-2"></i>
                                    <span class="small">Active Users: <strong>{{ isset($userStats) ? ($userStats['active_users'] ?? 0) : 0 }}</strong></span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-memory text-info me-2"></i>
                                    <span class="small">Memory: <strong>{{ round(memory_get_usage(true) / 1024 / 1024, 1) }}MB</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="manageTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="access-tab" data-bs-toggle="tab" data-bs-target="#access" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>User Access
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-history" type="button" role="tab">
                    <i class="fas fa-credit-card me-2"></i>Payment History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="video-tab" data-bs-toggle="tab" data-bs-target="#video-management" type="button" role="tab">
                    <i class="fas fa-video me-2"></i>Video Management
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="manageTabContent">
            <!-- User Access Tab -->
            <div class="tab-pane fade show active" id="access" role="tabpanel">
                <div class="row g-4">
                    <!-- Enhanced Grant Access Form -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-search me-2"></i>Search Users & Courses
                                </div>
                                <span class="badge bg-info" data-tooltip="Search and view user and course information">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-1"></i>Search Users
                                    </label>
                                    <select class="form-control user-select" id="user_id" name="user_id">
                                        <option value="">Type to search for user...</option>
                                        @if(isset($users) && $users->count() > 0)
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }} data-email="{{ $user->email ?? '' }}" data-role="{{ $user->role ?? 0 }}" data-name="{{ $user->name ?? '' }}">
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
                                    <label class="form-label">
                                        <i class="fas fa-graduation-cap me-1"></i>Search Courses
                                    </label>
                                    <select class="form-select" id="course_id" name="course_id">
                                        <option value="">Choose course...</option>
                                        @if(isset($courses) && $courses->count() > 0)
                                            @foreach($courses as $course)
                                                <option value="{{ isset($course->id) ? $course->id : '' }}" {{ old('course_id') == $course->id ? 'selected' : '' }} data-price="{{ isset($course->price) ? $course->price : 0 }}">
                                                    {{ isset($course->name) ? $course->name : 'Unknown Course' }} @if(isset($course->price) && $course->price > 0)({{ $course->price }} EGP)@else(Free)@endif
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

                                <div class="quick-actions">
                                    <button type="button" class="btn btn-success" id="grantAccessBtn">
                                        <i class="fas fa-user-plus me-2"></i>Grant Access
                                    </button>
                                    <button type="button" class="btn btn-danger" id="revokeAccessBtn">
                                        <i class="fas fa-user-times me-2"></i>Revoke Access
                                    </button>
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
                                        </div>
                                        <span class="badge" id="selectedUserRole"></span>
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
                                    <i class="fas fa-user-shield me-2"></i>Admin Management
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
                                                <option value="{{ isset($user->id) ? $user->id : '' }}" data-email="{{ isset($user->email) ? $user->email : '' }}" data-role="{{ isset($user->role) ? $user->role : 0 }}" data-name="{{ isset($user->name) ? $user->name : '' }}">
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
                                    </div>
                                    
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-success w-100" id="promoteToAdminBtn">
                                                <i class="fas fa-user-shield me-2"></i>Promote to Admin
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-danger w-100" id="removeAdminBtn">
                                                <i class="fas fa-user-times me-2"></i>Revoke Admin
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 small text-muted text-center" id="adminActionHint">
                                        Select a user above to manage their admin privileges
                                    </div>
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
                                    <i class="fas fa-history me-2"></i>Recent Enrollments
                                </div>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="enrollmentFilter" style="width: auto;">
                                        <option value="all">All Status</option>
                                        <option value="enrolled">Active Only</option>
                                        <option value="revoked">Revoked Only</option>
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
                                                <th>Course</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($enrolledUsers ?? [] as $enrollment)
                                                <tr data-status="{{ $enrollment->is_enrolled ? 'enrolled' : 'revoked' }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                                    {{ strtoupper(substr($enrollment->email, 0, 1)) }}
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium">{{ $enrollment->name ?? 'Unknown' }}</div>
                                                                <small class="text-muted">{{ $enrollment->email }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $enrollment->course_name }}</span>
                                                    </td>
                                                    <td>
                                                        <div>{{ \Carbon\Carbon::parse($enrollment->created_at)->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ \Carbon\Carbon::parse($enrollment->created_at)->diffForHumans() }}</small>
                                                    </td>
                                                    <td>
                                                        @if(!$enrollment->is_enrolled)
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Revoked
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Enrolled
                                                            </span>
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
                                        $enrolledCount = isset($enrolledUsers) ? $enrolledUsers->where('course_id', $course->id)->where('is_enrolled', true)->count() : 0;
                                        $percentage = (isset($userStats) && isset($userStats['total_users']) && $userStats['total_users'] > 0) ? round(($enrolledCount / $userStats['total_users']) * 100) : 0;
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center mb-2" style="overflow: hidden;">
                                        <div style="flex: 1; min-width: 0;">
                                            <span class="data-pill course" style="max-width: 100%; display: inline-block; margin-bottom: 2px;">{{ $course->name }}</span>
                                        </div>
                                        <div class="text-end flex-shrink-0" style="margin-left: 8px;">
                                            <div class="fw-bold">{{ $enrolledCount }}</div>
                                            <small class="text-muted">enrolled</small>
                                        </div>
                                    </div>
                                    <div class="progress mb-3" style="height: 4px;">
                                        <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Recent Activity Summary -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-activity me-2"></i>Activity Summary
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">Total Enrollments</span>
                                    <span class="badge bg-success">{{ isset($userStats) ? ($userStats['total_enrollments'] ?? 0) : 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">Active Users</span>
                                    <span class="badge bg-info">{{ isset($userStats) ? ($userStats['active_users'] ?? 0) : 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">Admin Users</span>
                                    <span class="badge bg-warning">{{ isset($userStats) ? ($userStats['admin_users'] ?? 0) : 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small">Total Courses</span>
                                    <span class="badge bg-secondary">{{ count($courses ?? []) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Tab -->
            <div class="tab-pane fade" id="payment-history" role="tabpanel">       
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
                            <div class="stat-label">Completed</div>
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
                                        <th style="width: 180px; min-width: 180px;">Reference</th>
                                        <th style="width: 220px; min-width: 220px;">
                                            <strong style="color: #dc3545; font-size: 0.9rem;">
                                                <i class="fas fa-exchange-alt me-1"></i>
                                                Provider Transaction
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
        </div>
    </div>

    <!-- Notification Container -->
    <div class="notification-container" id="notificationContainer"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            console.log('🚀 MANAGE ACCESS PAGE LOADED');
            
            // Notification system
            function showNotification(type, title, message, duration = 3000) {
                const container = $('#notificationContainer');
                const notification = $(`
                    <div class="notification ${type}">
                        <div class="notification-icon">
                            ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'}
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${title}</div>
                            <div class="notification-message">${message}</div>
                        </div>
                        <button class="notification-close" onclick="$(this).parent().remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
                
                container.append(notification);
                
                // Trigger animation
                setTimeout(() => {
                    notification.addClass('show');
                }, 100);
                
                // Auto remove
                setTimeout(() => {
                    notification.removeClass('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, duration);
            }
            
            // Initialize Select2 for user search with AJAX and debugging
            $('.user-select').select2({
                placeholder: 'Type to search for user...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 2,
                debug: true, // Enable debugging
                ajax: {
                    url: '/manage-access/search-users',
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        console.log('🔍 Select2 AJAX Data:', params);
                        return {
                            query: params.term
                        };
                    },
                    processResults: function (data, params) {
                        console.log('📊 Select2 Process Results:', data);
                        if (data.success && data.users) {
                            return {
                                results: data.users.map(function (user) {
                                    return {
                                        id: user.id,
                                        text: user.name + ' (' + user.email + ')',
                                        name: user.name,
                                        email: user.email,
                                        role: user.role
                                    };
                                })
                            };
                        } else {
                            console.warn('❌ No users found or API error:', data);
                            return {
                                results: []
                            };
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Select2 AJAX Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (user) {
                    console.log('🎨 Template Result:', user);
                    if (user.loading) {
                        return user.text;
                    }
                    if (!user.id) {
                        return user.text;
                    }

                    // Get role name
                    let roleName = 'Student';
                    if (user.role === 1) roleName = 'Admin';
                    else if (user.role === 2) roleName = 'Tutor';

                    // Create display with role badge
                    let display = '<div class="d-flex align-items-center justify-content-between w-100">';
                    display += '<div class="flex-grow-1">';
                    display += '<div class="fw-medium">' + user.name + '</div>';
                    display += '<small class="text-muted">' + user.email + '</small>';
                    display += '</div>';
                    display += '<span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">' + roleName + '</span>';
                    display += '</div>';

                    return display;
                },
                templateSelection: function (user) {
                    console.log('✅ Template Selection:', user);
                    if (!user.id) {
                        return user.text;
                    }
                    return user.name + ' (' + user.email + ')';
                }
            });

            // Add event listeners for debugging
            $('.user-select').on('select2:open', function() {
                console.log('🔓 Select2 opened');
            });

            $('.user-select').on('select2:close', function() {
                console.log('🔒 Select2 closed');
            });

            $('.user-select').on('select2:select', function(e) {
                console.log('✅ User selected:', e.params.data);
            });

            $('.user-select').on('select2:unselect', function(e) {
                console.log('❌ User unselected:', e.params.data);
            });
            
            // GRANT ACCESS BUTTON HANDLER
            $('#grantAccessBtn').on('click', function(e) {
                console.log('🚀 GRANT ACCESS BUTTON CLICKED!');
                e.preventDefault();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                console.log('Values:', {userId, courseId});
                
                if (!userId || !courseId) {
                    console.error('Please select both a user and a course first!');
                    return;
                }
                
                // Disable button during request
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                $.ajax({
                    url: '/manage-access/enroll',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('✅ SUCCESS:', response);
                        
                        // Show success notification
                        showNotification('success', 'Access Granted!', 'User has been successfully enrolled in the course.');
                        
                        // Reset button
                        const btn = $('#grantAccessBtn');
                        btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i>Grant Access');
                        
                        // Refresh enrollment data smoothly
                        refreshEnrollmentData();
                    },
                    error: function(xhr) {
                        console.error('❌ ERROR:', xhr.responseText);
                        
                        // Show error notification
                        showNotification('error', 'Access Failed!', 'Failed to grant access. Please try again.');
                        
                        // Reset button
                        const btn = $('#grantAccessBtn');
                        btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i>Grant Access');
                    }
                });
            });
            
            // REVOKE ACCESS BUTTON HANDLER
            $('#revokeAccessBtn').on('click', function(e) {
                console.log('🚀 REVOKE ACCESS BUTTON CLICKED!');
                e.preventDefault();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                console.log('Values:', {userId, courseId});
                
                if (!userId || !courseId) {
                    console.error('Please select both a user and a course first!');
                    return;
                }
                
                // Instant revocation - no confirmation needed
                
                // Disable button during request
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                $.ajax({
                    url: '/manage-access/revoke',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('✅ SUCCESS:', response);
                        
                        // Show success notification
                        showNotification('error', 'Access Revoked!', 'User access has been successfully revoked.');
                        
                        // Reset button
                        const btn = $('#revokeAccessBtn');
                        btn.prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Access');
                        
                        // Refresh enrollment data smoothly
                        refreshEnrollmentData();
                    },
                    error: function(xhr) {
                        console.error('❌ ERROR:', xhr.responseText);
                        
                        // Show error notification
                        showNotification('error', 'Revoke Failed!', 'Failed to revoke access. Please try again.');
                        
                        // Reset button
                        const btn = $('#revokeAccessBtn');
                        btn.prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Access');
                    }
                });
            });
            

            
            // User selection handler
            $('#user_id').on('change', function() {
                try {
                    console.log('=== USER SELECTION DEBUG ===');
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');

                    console.log('User selection:', {userId, userOption});

                    if (userId) {
                        const userName = userOption.text();
                        const userEmail = userOption.data('email');
                        const userRole = userOption.data('role');

                        console.log('User data:', {userName, userEmail, userRole, userRoleType: typeof userRole});

                        // Safe integer handling for role
                        const safeUserRole = (userRole && typeof userRole === 'number') ? userRole : 0;
                        const displayRole = safeUserRole === 1 ? 'ADMIN' : safeUserRole === 2 ? 'TUTOR' : 'STUDENT';

                        // Set user initial (first letter of name) - safe handling
                        const userInitial = (userName && userName.length > 0) ? userName.charAt(0).toUpperCase() : 'U';
                        $('#userInitial').text(userInitial);

                        // Update UI with safe fallbacks
                        $('#selectedUserName').text(userName || 'Unknown');
                        $('#selectedUserEmail').text(userEmail || 'No Email');
                        $('#selectedUserRole').removeClass().addClass('badge').addClass(safeUserRole === 1 ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#userInfoSection').show();

                        // Update course dropdown with enrollment status
                        updateCourseDropdown(userId);
                    } else {
                        $('#userInfoSection').hide();
                        // Reset course dropdown
                        resetCourseDropdown();
                    }
                } catch (error) {
                    console.error('Error in user selection handler:', error);
                    // Fallback behavior
                    $('#userInfoSection').hide();
                }
            });
            
            // Function to update course dropdown with enrollment status
            function updateCourseDropdown(userId) {
                $.ajax({
                    url: '/manage-access/user-enrollments',
                    method: 'GET',
                    data: { user_id: userId },
                    success: function(response) {
                        if (response.success) {
                            const courseSelect = $('#course_id');
                            courseSelect.empty();
                            courseSelect.append('<option value="">Choose course...</option>');
                            
                            response.courses.forEach(function(course) {
                                const isEnrolled = course.is_enrolled;
                                const statusBadge = isEnrolled ? 
                                    '<span class="badge bg-success">✓ Enrolled</span>' : 
                                    '<span class="badge bg-secondary">✗ Not Enrolled</span>';
                                
                                const priceText = course.price > 0 ? `(${course.price} EGP)` : '(Free)';
                                
                                courseSelect.append(`
                                    <option value="${course.id}" data-enrolled="${isEnrolled}" data-price="${course.price}">
                                        ${course.name} ${priceText} - ${statusBadge}
                                    </option>
                                `);
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load user enrollments:', xhr.responseText);
                        resetCourseDropdown();
                    }
                });
            }
            
            // Function to reset course dropdown
            function resetCourseDropdown() {
                const courseSelect = $('#course_id');
                courseSelect.empty();
                courseSelect.append('<option value="">Choose course...</option>');
{{-- 💀 N+1 FIX NEEDED: Eager load 'name' in controller --}}
                
                // Add original courses
                @if(isset($courses) && $courses->count() > 0)
                    @foreach($courses as $course)
                        courseSelect.append(`
                            <option value="{{ $course->id }}" data-price="{{ $course->price }}">
                                {{ $course->name }} @if($course->price > 0)({{ $course->price }} EGP)@else(Free)@endif
                            </option>
                        `);
                    @endforeach
                @endif
            }
            
            console.log('✅ All event handlers attached successfully');
            
            // Initialize Admin User Select2
            $('.admin-user-select').select2({
                placeholder: 'Type to search for user...',
                allowClear: true,
                width: '100%'
            });
            
            // Admin Management Variables
            let selectedAdminUserId = null;
            let selectedAdminIsAdmin = false;
            
            // Admin user selection handler
            $('#admin_user_id').on('change', function() {
                try {
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');

                    console.log('Admin user selection:', {userId, userOption});

                    if (userId) {
                        const userName = userOption.data('name');
                        const userEmail = userOption.data('email');
                        const userRole = userOption.data('role');

                        console.log('User data:', {userName, userEmail, userRole});

                        selectedAdminUserId = userId;
                        selectedAdminIsAdmin = userRole === 1;

                        // Safe string handling
                        const safeUserRole = (userRole && typeof userRole === 'string') ? userRole : 'user';
                        const displayRole = safeUserRole.toUpperCase();

                        // Update UI
                        $('#selectedAdminUserName').text(userName || 'Unknown');
                        $('#selectedAdminUserEmail').text(userEmail || 'No Email');
                        $('#selectedAdminUserStatus').removeClass().addClass('badge').addClass(safeUserRole === 'admin' ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#selectedUserInfo').show();

                        // Update buttons
                        if (selectedAdminIsAdmin === 1) {
                            $('#promoteToAdminBtn').prop('disabled', true).html('<i class="fas fa-check me-2"></i>Already Admin');
                            $('#removeAdminBtn').prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Admin');
                            $('#adminActionHint').text('This user is currently an administrator');
                        } else {
                            $('#promoteToAdminBtn').prop('disabled', false).html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                            $('#removeAdminBtn').prop('disabled', true).html('<i class="fas fa-user-times me-2"></i>Not Admin');
                            $('#adminActionHint').text('This user is not an administrator');
                        }
                    } else {
                        $('#selectedUserInfo').hide();
                        $('#promoteToAdminBtn').prop('disabled', true).html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                        $('#removeAdminBtn').prop('disabled', true).html('<i class="fas fa-user-times me-2"></i>Revoke Admin');
                        $('#adminActionHint').text('Select a user above to manage their admin privileges');
                        selectedAdminUserId = null;
                        selectedAdminIsAdmin = false;
                    }
                } catch (error) {
                    console.error('Error in admin user selection handler:', error);
                    // Fallback behavior
                    $('#selectedUserInfo').hide();
                    $('#promoteToAdminBtn').prop('disabled', true);
                    $('#removeAdminBtn').prop('disabled', true);
                }
            });
            
            // Promote to Admin button handler
            $('#promoteToAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (!selectedAdminUserId || selectedAdminIsAdmin) {
                    alert('Please select a non-admin user to promote');
                    return;
                }
                
                if (!confirm('Are you sure you want to promote this user to administrator?')) {
                    return;
                }
                
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Promoting...').prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-admin',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'promote',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('User promoted to administrator successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.message || 'Failed to promote user'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Error promoting user:', xhr.responseText);
                        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to promote user'));
                    },
                    complete: function() {
                        $('#promoteToAdminBtn').html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Remove Admin button handler
            $('#removeAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (!selectedAdminUserId || !selectedAdminIsAdmin) {
                    alert('Please select an admin user to revoke privileges');
                    return;
                }
                
                if (!confirm('Are you sure you want to revoke administrator privileges from this user?')) {
                    return;
                }
                
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Revoking...').prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-admin',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'revoke',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Administrator privileges revoked successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.message || 'Failed to revoke admin privileges'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Error revoking admin:', xhr.responseText);
                        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to revoke admin privileges'));
                    },
                    complete: function() {
                        $('#removeAdminBtn').html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Enrollment filter handler
            $('#enrollmentFilter').change(function() {
                const filter = $(this).val();
                const rows = $('#enrollmentsTable tbody tr');
                
                rows.each(function() {
                    const row = $(this);
                    const status = row.data('status');
                    
                    if (filter === 'all' || status === filter) {
                        row.show();
                    } else {
                        row.hide();
                    }
                });
            });
            
            // Refresh enrollments handler
            $('#refreshEnrollments').click(function() {
                const btn = $(this);
                btn.find('i').addClass('fa-spin');
                
                refreshEnrollmentData();
                
                setTimeout(() => {
                    btn.find('i').removeClass('fa-spin');
                }, 1000);
            });
            
            // Function to refresh enrollment data smoothly
            function refreshEnrollmentData() {
                $.ajax({
                    url: '/manage-access/enrollments',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.enrollments) {
                            updateEnrollmentTable(response.enrollments);
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to refresh enrollment data:', xhr.responseText);
                    }
                });
            }
            
            // Function to update enrollment table
            function updateEnrollmentTable(enrollments) {
                const tbody = $('#enrollmentsTable tbody');
                tbody.empty();
                
                if (enrollments.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <div>No enrollments found</div>
                                <small>Start by granting course access to users</small>
                            </td>
                        </tr>
                    `);
                } else {
                    enrollments.forEach(function(enrollment) {
                        const statusBadge = enrollment.is_enrolled ? 
                            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Enrolled</span>' :
                            '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Revoked</span>';
                        
                        const date = new Date(enrollment.created_at).toLocaleDateString();
                        const timeAgo = new Date(enrollment.created_at).toLocaleDateString();
                        
                        tbody.append(`
                            <tr data-status="${enrollment.is_enrolled ? 'enrolled' : 'revoked'}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                ${enrollment.name ? enrollment.name.charAt(0).toUpperCase() : 'U'}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium">${enrollment.name || 'Unknown'}</div>
                                            <small class="text-muted">${enrollment.email}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">${enrollment.course_name}</span>
                                </td>
                                <td>
                                    <div>${date}</div>
                                    <small class="text-muted">${timeAgo}</small>
                                </td>
                                <td>${statusBadge}</td>
                            </tr>
                        `);
                    });
                }
            }
            
    // Payment History Tab Handler
    $('#payment-tab').on('click', function() {
        console.log('Payment tab clicked, loading payments...');
        loadPayments();
    });
            
            // Auto-load payments when page loads (for admin users)
            @if(Auth::user()->role === 'admin')
            console.log('Admin user detected, auto-loading payments...');
            loadPayments();
            @endif
            
            // Payment search handler
            $('#payment-search-btn').on('click', function() {
                const search = $('#payment-search').val();
                loadPayments(1, search);
            });
            
            // Enter key search
            $('#payment-search').on('keypress', function(e) {
                if (e.which === 13) {
                    const search = $(this).val();
                    loadPayments(1, search);
                }
            });

            // Check if table needs horizontal scrolling and show indicator
            function checkTableScroll() {
                const tableWrapper = $('.table-responsive')[1]; // Payment table is the second table-responsive
                const scrollIndicator = $('#scroll-indicator');

                if (tableWrapper) {
                    const hasHorizontalScroll = tableWrapper.scrollWidth > tableWrapper.clientWidth;

                    if (hasHorizontalScroll) {
                        scrollIndicator.show();
                        console.log('Table needs horizontal scrolling - indicator shown');
                    } else {
                        scrollIndicator.hide();
                        console.log('Table fits in container - indicator hidden');
                    }
                }
            }

            // Check scroll on page load and window resize
            $(window).on('load resize', function() {
                // Small delay to ensure table is fully rendered
                setTimeout(checkTableScroll, 100);
            });

            // Check scroll when payment data is loaded
            // We'll use a different approach since loadPayments doesn't return a promise
            let scrollCheckTimeout;
            const originalLoadPayments = loadPayments;
            loadPayments = function(page = 1, search = '') {
                if (scrollCheckTimeout) {
                    clearTimeout(scrollCheckTimeout);
                }
                originalLoadPayments.call(this, page, search);
                // Check scroll after a delay to allow table to render
                scrollCheckTimeout = setTimeout(checkTableScroll, 1000);
            };

            // Monitor table content changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.target.id === 'payments-table-body') {
                        setTimeout(checkTableScroll, 100);
                    }
                });
            });

            // Start observing
            const targetNode = document.getElementById('payments-table-body');
            if (targetNode) {
                observer.observe(targetNode, { childList: true });
            }

            // Initial check
            setTimeout(checkTableScroll, 500);

        });
        
        // Load payments function
        function loadPayments(page = 1, search = '') {
            console.log('Loading payments...', {page, search});

            // Show loading state
            const tbody = $('#payments-table-body');
            tbody.html(`
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <div>Loading payments...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: '/manage-access/payments',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    page: page,
                    search: search
                },
                success: function(response) {
                                            console.log('Payments loaded:', response);
                        console.log('Response type:', typeof response);
                        console.log('Success:', response.success);
                        console.log('Stats:', response.stats);
                        console.log('Payments count:', response.payments ? response.payments.length : 'undefined');
                        
                        if (response.success) {
                        console.log('Response stats:', response.stats);
                        
                        // Update statistics with proper type checking
                        const totalRevenue = parseFloat(response.stats.total_revenue || 0);
                        const completedCount = parseInt(response.stats.completed_count || 0);
                        const pendingCount = parseInt(response.stats.pending_count || 0);
                        const rejectedCount = parseInt(response.stats.rejected_count || 0);
                        
                        $('#total-revenue').text(totalRevenue.toFixed(2) + ' EGP');
                        $('#completed-count').text(completedCount);
                        $('#pending-count').text(pendingCount);
                        $('#rejected-count').text(rejectedCount);
                        
                        // Update payments table
                        const tbody = $('#payments-table-body');
                        tbody.empty();
                        
                        if (response.payments.length === 0) {
                            tbody.append(`
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-credit-card fa-3x mb-3 d-block"></i>
                                        <div>No payments found</div>
                                        <small>Payments will appear here once users make purchases</small>
                                    </td>
                                </tr>
                            `);
                        } else {
                            response.payments.forEach(function(payment) {
                                const statusBadge = getStatusBadge(payment.status);
                                const actions = getPaymentActions(payment);
                                const date = new Date(payment.created_at).toLocaleDateString();
                                
                                // Create price display
                                const priceDisplay = `<div class="fw-bold">${payment.amount} EGP</div>`;
                                
                                const transactionInfo = payment.transaction_id ?
                                    `<div><code class="small">${payment.reference_number || 'N/A'}</code></div>
                                     <small class="text-muted">TX: ${payment.transaction_id}</small>` :
                                    `<code class="small">${payment.reference_number || 'N/A'}</code>`;

                                const providerTransaction = payment.transaction_id ?
                                    `<div class="d-flex align-items-center">
                                        <span class="badge bg-danger me-2" style="font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-exchange-alt me-1"></i>PROVIDER
                                        </span>
                                        <code class="small text-danger fw-bold" style="font-size: 0.8rem;">
                                            ${payment.transaction_id}
                                        </code>
                                     </div>` :
                                    `<span class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>Pending
                                     </span>`;

                                tbody.append(`
                                    <tr data-payment-id="${payment.id}">
                                        <td>#${payment.id}</td>
                                        <td>
                                            <div class="fw-medium">${payment.user_name}</div>
                                            <small class="text-muted">${payment.user_email}</small>
                                        </td>
                                        <td>
                                            <span class="data-pill course">${payment.course_name}</span>
                                        </td>
                                        <td>${priceDisplay}</td>
                                        <td><span class="data-pill method">${payment.payment_method}</span></td>
                                        <td>${statusBadge}</td>
                                        <td>${transactionInfo}</td>
                                        <td>${providerTransaction}</td>
                                        <td>${date}</td>
                                        <td>${actions}</td>
                                    </tr>
                                `);
                            });
                        }
                        
                        // Update pagination info
                        $('#showing-records').text(response.payments.length);
                        $('#total-records-count').text(response.pagination.total);
                        
                        // Generate pagination (simple version)
                        const pagination = $('#payment-pagination');
                        pagination.empty();
                        
                        if (response.pagination.last_page > 1) {
                            let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                            
                            // Previous button
                            if (response.pagination.current_page > 1) {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPayments(${response.pagination.current_page - 1}, '${search}')">Previous</a></li>`;
                            }
                            
                            // Page numbers (simplified)
                            for (let i = 1; i <= Math.min(response.pagination.last_page, 5); i++) {
                                const active = i === response.pagination.current_page ? 'active' : '';
                                paginationHtml += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadPayments(${i}, '${search}')">${i}</a></li>`;
                            }
                            
                            // Next button
                            if (response.pagination.current_page < response.pagination.last_page) {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPayments(${response.pagination.current_page + 1}, '${search}')">Next</a></li>`;
                            }
                            
                            paginationHtml += '</ul>';
                            pagination.html(paginationHtml);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error loading payments:', xhr.responseText);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseJSON);
                    
                    let errorMessage = 'Please try again later';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Payment endpoint not found. Please check the route.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred while loading payments.';
                    }
                    
                    $('#payments-table-body').html(`
                        <tr>
                            <td colspan="9" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                <div>Error loading payments</div>
                                <small>${errorMessage}</small>
                                <br><small class="text-muted">Status: ${xhr.status}</small>
                            </td>
                        </tr>
                    `);
                }
            });
        }
        
        function getStatusBadge(status) {
            switch(status) {
                case 'completed':
                    return '<span class="data-pill status-completed">Completed</span>';
                case 'pending':
                    return '<span class="data-pill status-pending">Pending</span>';
                case 'rejected':
                    return '<span class="data-pill status-rejected">Rejected</span>';
                default:
                    return '<span class="data-pill status-default">' + status + '</span>';
            }
        }
        
        function getPaymentActions(payment) {
            if (payment.status === 'pending') {
                return `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success btn-sm" onclick="acceptPayment(${payment.id})" title="Accept Payment">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rejectPayment(${payment.id})" title="Reject Payment">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            } else {
                return '<span class="text-muted small">No actions</span>';
            }
        }

        // Payment Management Functions
        function acceptPayment(paymentId) {
            console.log('Accept payment called for ID:', paymentId);
            
            // Instant acceptance - no confirmation needed
            
            // Send verification request
            fetch(`/admin/payments/verify/${paymentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ admin_notes: 'Payment accepted' }),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 404) {
                        throw new Error('Route not found. Please check if you are logged in as admin.');
                    } else if (response.status === 419) {
                        throw new Error('CSRF token expired. Please refresh the page and try again.');
                    } else if (response.status === 403) {
                        throw new Error('Access denied. You must be an admin to verify payments.');
                    } else if (response.status === 401) {
                        throw new Error('Not authenticated. Please log in and try again.');
                    } else {
                        throw new Error(`Server error: ${response.status} ${response.statusText}`);
                    }
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Payment accepted successfully!', 'success');
                    
                    // Update the UI
                    const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('.payment-status');
                        if (statusCell) {
                            statusCell.textContent = 'Completed';
                            statusCell.className = 'badge payment-status bg-success';
                        }
                        
                        // Update actions cell
                        const actionsCell = row.querySelector('td:last-child');
                        if (actionsCell) {
                            actionsCell.innerHTML = '<span class="text-muted small">Completed</span>';
                        }
                    }
                    
                    // Refresh payment data
                    loadPayments();
                } else {
                    throw new Error(data.message || 'Payment verification failed');
                }
            })
            .catch(error => {
                console.error('Error accepting payment:', error);
                showNotification('Failed to accept payment: ' + error.message, 'error');
            });
        }

        function rejectPayment(paymentId) {
            console.log('Reject payment called for ID:', paymentId);
            
            // Instant rejection - no reason needed
            
            fetch(`/admin/payments/${paymentId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: 'Payment rejected'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Payment rejected successfully!', 'success');
                    
                    // Update the UI
                    const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('.payment-status');
                        if (statusCell) {
                            statusCell.textContent = 'Rejected';
                            statusCell.className = 'badge payment-status bg-danger';
                        }
                        
                        // Update actions cell
                        const actionsCell = row.querySelector('td:last-child');
                        if (actionsCell) {
                            actionsCell.innerHTML = '<span class="text-muted small">Rejected</span>';
                        }
                    }
                    
                    // Refresh payment data
                    loadPayments();
                } else {
                    showNotification('Failed to reject payment: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error rejecting payment:', error);
                showNotification('Failed to reject payment: ' + error.message, 'error');
            });
        }

        // Notification system
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            notification.className = `alert ${bgColor} text-white alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${icon} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 4000);
        }
    </script>


</body>
</html>   
         <!-- Video Management Tab -->
            <div class="tab-pane fade" id="video-management" role="tabpanel">
                <div class="row g-4">
                    <!-- Add Video Form -->
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
                                        <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                               placeholder="https://youtu.be/VIDEO_ID or https://www.youtube.com/watch?v=VIDEO_ID">
                                        <div class="form-text">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            Paste any YouTube URL - we'll extract the video ID automatically
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-tag me-1"></i>Video Title
                                        </label>
                                        <input type="text" class="form-control" id="video_title" name="title" 
                                               placeholder="Enter video title">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-align-left me-1"></i>Description (Optional)
                                        </label>
                                        <textarea class="form-control" id="video_description" name="description" 
                                                  rows="3" placeholder="Brief description of the video content"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Video
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Video List -->
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
        </div>
    </div>

    <!-- Video Management JavaScript -->
    <script>
        $(document).ready(function() {
            // Load videos when video tab is clicked
            $('#video-tab').on('click', function() {
                loadVideos();
            });
            
            // Course selection handler for video form
            $('#video_course_id').on('change', function() {
                const courseId = $(this).val();
                const topicSelect = $('#video_topic_id');
                
                if (courseId) {
                    // Load topics for selected course
                    $.ajax({
                        url: `/api/courses/${courseId}/topics`,
                        method: 'GET',
                        success: function(topics) {
                            topicSelect.empty().append('<option value="">Select topic...</option>');
                            topics.forEach(topic => {
                                topicSelect.append(`<option value="${topic.id}">${topic.name}</option>`);
                            });
                            topicSelect.prop('disabled', false);
                        },
                        error: function() {
                            showNotification('Failed to load topics', 'error');
                        }
                    });
                } else {
                    topicSelect.empty().append('<option value="">First select a course...</option>').prop('disabled', true);
                }
            });
            
            // Add video form submission
            $('#addVideoForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    topic_id: $('#video_topic_id').val(),
                    youtube_url: $('#youtube_url').val(),
                    title: $('#video_title').val(),
                    description: $('#video_description').val(),
                    _token: '{{ csrf_token() }}'
                };
                
                $.ajax({
                    url: '/admin/videos',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showNotification('Video added successfully!', 'success');
                            $('#addVideoForm')[0].reset();
                            $('#video_topic_id').prop('disabled', true).empty().append('<option value="">First select a course...</option>');
                            loadVideos();
                        } else {
                            showNotification(response.message || 'Failed to add video', 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        showNotification(response?.message || 'Failed to add video', 'error');
                    }
                });
            });
        });
        
        // Load videos function
        function loadVideos() {
            console.log('Loading videos...');

            // Show loading state
            const tbody = $('#videos-table-body');
            tbody.html(`
                <tr>
                    <td colspan="4" class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <div>Loading videos...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: '/admin/videos',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    console.log('Videos response:', response);
                    tbody.empty();

                    if (response.success && response.videos && response.videos.length > 0) {
                        $('#video-count').html(`<i class="fas fa-video"></i> ${response.videos.length}`);

                        response.videos.forEach(video => {
                            const statusBadge = '<span class="badge bg-success">Active</span>';

                            tbody.append(`
                                <tr>
                                    <td>
                                        <div class="fw-medium">${video.topic_name || 'N/A'}</div>
                                        <small class="text-muted">${video.course_name || 'N/A'}</small>
                                    </td>
                                    <td>
                                        <div class="fw-medium">${video.title || 'Untitled'}</div>
                                        <small class="text-muted">
                                            <i class="fab fa-youtube text-danger"></i>
                                            ${video.youtube_video_id ? video.youtube_video_id.substring(0, 8) + '...' : 'N/A'}
                                        </small>
                                    </td>
                                    <td>${statusBadge}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteVideo(${video.id})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });

                        showNotification('Videos loaded successfully!', 'success');
                    } else {
                        $('#video-count').html('<i class="fas fa-video"></i> 0');
                        tbody.append(`
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    <i class="fas fa-video fa-2x mb-2"></i>
                                    <div>No videos found</div>
                                    <small>Add your first video using the form</small>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Videos error:', xhr, status, error);
                    tbody.html(`
                        <tr>
                            <td colspan="4" class="text-center py-3 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <div>Failed to load videos</div>
                                <small>${xhr.responseJSON?.message || error || 'Please try again'}</small>
                            </td>
                        </tr>
                    `);
                    showNotification('Failed to load videos: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }
        

        
        // Delete video
        function deleteVideo(videoId) {
            if (confirm('Are you sure you want to delete this video?')) {
                $.ajax({
                    url: `/admin/videos/${videoId}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Video deleted successfully!', 'success');
                            loadVideos();
                        } else {
                            showNotification('Failed to delete video', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Failed to delete video', 'error');
                    }
                });
            }
        }
    </script>

    <!-- Notification Container -->
    <div id="notificationContainer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
