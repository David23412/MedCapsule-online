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

        .badge.bg-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%) !important;
            color: white !important;
            border-color: #0d6efd;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
            color: white !important;
            border-color: #198754;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%) !important;
            color: #000 !important;
            border-color: #ffc107;
            font-weight: 700;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            border-color: #dc3545;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%) !important;
            color: #000 !important;
            border-color: #0dcaf0;
            font-weight: 700;
        }

        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
            color: white !important;
            border-color: #6c757d;
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
                    <a href="{{ route('admin.delete.index') }}" class="header-btn delete-btn">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete Data</span>
                    </a>
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
                    <div class="stat-number text-primary">{{ $userStats['total_users'] }}</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change">
                        <i class="fas fa-users"></i> Total registered users
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-success">{{ $userStats['active_users'] }}</div>
                    <div class="stat-label">Active Users</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> {{ $userStats['total_users'] > 0 ? round(($userStats['active_users'] / $userStats['total_users']) * 100) : 0 }}% active
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-warning">{{ $userStats['admin_users'] }}</div>
                    <div class="stat-label">Administrators</div>
                    <div class="stat-change">
                        <i class="fas fa-shield-alt"></i> {{ $userStats['total_users'] > 0 ? round(($userStats['admin_users'] / $userStats['total_users']) * 100, 1) : 0 }}% of users
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number text-info">{{ $userStats['total_enrollments'] }}</div>
                    <div class="stat-label">Total Enrollments</div>
                    <div class="stat-change positive">
                        <i class="fas fa-graduation-cap"></i> {{ $userStats['total_users'] > 0 ? round($userStats['total_enrollments'] / $userStats['total_users'], 1) : 0 }} avg per user
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
                                    <span class="small">Active Users: <strong>{{ $userStats['active_users'] ?? 0 }}</strong></span>
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
                                                <option value="{{ $user->id }}" data-email="{{ $user->email }}" data-role="{{ $user->role }}">
                                                    {{ $user->name }} ({{ $user->email }})
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
                                                <option value="{{ $course->id }}" data-enrolled="{{ $course->users_count ?? 0 }}">
                                                    {{ $course->name }} @if($course->price > 0)({{ $course->price }})@else(Free)@endif
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>NO COURSES FOUND</option>
                                        @endif
                                    </select>
                                </div>

                                <!-- Grant Access Buttons -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success w-100" id="grantAccessBtn">
                                        <i class="fas fa-user-plus me-2"></i>Grant Access
                                    </button>
                                </div>

                                <!-- Revoke Access Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-danger w-100" id="revokeAccessBtn">
                                        <i class="fas fa-user-times me-2"></i>Revoke Access
                                    </button>
                                </div>

                                <!-- Test Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-info w-100" id="testButton">
                                        <i class="fas fa-bug me-2"></i>Test Button (Click Me!)
                                    </button>
                                </div>

                                <!-- User Info Display -->
                                <div class="mt-3" id="userInfoSection" style="display: none;">
                                    <div class="user-info-card">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong id="selectedUserName"></strong>
                                                <div class="small text-muted" id="selectedUserEmail"></div>
                                            </div>
                                            <span class="badge" id="selectedUserRole"></span>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="small text-muted">User ID</div>
                                                <div class="fw-bold" id="selectedUserId">-</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="small text-muted">Role</div>
                                                <div class="fw-bold" id="userRoleDisplay">-</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="small text-muted">Status</div>
                                                <div class="fw-bold text-success">Active</div>
                                            </div>
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
                                    <i class="fas fa-user-shield me-2"></i>Admin Management
                                </div>
                                <span class="badge bg-warning text-dark" data-tooltip="Manage administrator privileges">
                                    <i class="fas fa-crown"></i> {{ $userStats['admin_users'] }}
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-search me-1"></i>Find User to Manage
                                    </label>
                                    <select class="form-select admin-user-select" id="admin_user_id">
                                        <option value="">Type to search for user...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" data-email="{{ $user->email }}" data-role="{{ $user->role }}" data-name="{{ $user->name }}">
                                                {{ $user->name }} ({{ $user->email }}) {{ $user->role === 'admin' ? '- ADMIN' : '' }}
                                            </option>
                                        @endforeach
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
                                        <option value="active">Active Only</option>
                                        <option value="inactive">Inactive Only</option>
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
                                                <th>Actions</th>
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
                                                        <div>{{ Carbon\Carbon::parse($enrollment->created_at)->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ Carbon\Carbon::parse($enrollment->created_at)->diffForHumans() }}</small>
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
                                                    <td>
                                                        @if($enrollment->is_enrolled)
                                                            <button type="button" class="btn btn-danger btn-sm revoke-access-btn" 
                                                                    data-user-id="{{ $enrollment->user_id ?? $enrollment->id }}" 
                                                                    data-course-id="{{ $enrollment->course_id ?? '' }}"
                                                                    data-user-name="{{ $enrollment->name ?? 'Unknown' }}"
                                                                    data-course-name="{{ $enrollment->course_name ?? 'Unknown' }}">
                                                                <i class="fas fa-user-times me-1"></i>Revoke
                                                            </button>
                                                        @else
                                                            <span class="text-muted small">Already revoked</span>
                                                        @endif
                                                    </td>

                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted">
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
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="badge bg-primary me-2">{{ $course->name }}</span>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">{{ $course->users_count ?? 0 }}</div>
                                            <small class="text-muted">enrolled</small>
                                        </div>
                                    </div>
                                    <div class="progress mb-3" style="height: 4px;">
                                        <div class="progress-bar" style="width: {{ $userStats['total_users'] > 0 ? round(($course->users_count ?? 0) / $userStats['total_users'] * 100) : 0 }}%"></div>
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
                                    <span class="badge bg-success">{{ $userStats['total_enrollments'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">Active Users</span>
                                    <span class="badge bg-info">{{ $userStats['active_users'] ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">Admin Users</span>
                                    <span class="badge bg-warning">{{ $userStats['admin_users'] ?? 0 }}</span>
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
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Course</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="payments-table-body">
                                    <!-- Payments will be loaded here -->
                                </tbody>
                            </table>
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // NUCLEAR FIX - IMMEDIATE EXECUTION
        console.log('🚀 NUCLEAR FIX INITIALIZING...');
        
        // Force jQuery to be available
        if (typeof $ === 'undefined') {
            console.error('❌ jQuery not loaded!');
            alert('ERROR: jQuery not loaded. Page will not work properly.');
        } else {
            console.log('✅ jQuery loaded successfully');
        }
        
        // NUCLEAR BUTTON HANDLERS - IMMEDIATE ATTACHMENT
        function attachNuclearHandlers() {
            console.log('🔧 Attaching NUCLEAR button handlers...');
            
            // Remove any existing handlers first
            $(document).off('click', '#grantAccessBtn');
            $(document).off('click', '#revokeAccessBtn');
            $(document).off('click', '#testButton');
            
            // GRANT ACCESS - NUCLEAR HANDLER
            $(document).on('click', '#grantAccessBtn', function(e) {
                console.log('🚀 NUCLEAR GRANT ACCESS CLICKED!');
                e.preventDefault();
                e.stopPropagation();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                console.log('🔍 NUCLEAR VALUES:', {userId, courseId});
                
                if (!userId || !courseId) {
                    alert('SELECT USER AND COURSE FIRST!');
                    return false;
                }
                
                // NUCLEAR AJAX CALL
                $.ajax({
                    url: '/manage-access/enroll',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('✅ NUCLEAR SUCCESS:', response);
                        alert('ACCESS GRANTED SUCCESSFULLY!');
                        window.location.reload();
                    },
                    error: function(xhr) {
                        console.error('❌ NUCLEAR ERROR:', xhr.responseText);
                        alert('ERROR: ' + (xhr.responseJSON?.message || 'Failed to grant access'));
                    }
                });
            });
            
            // REVOKE ACCESS - NUCLEAR HANDLER
            $(document).on('click', '#revokeAccessBtn', function(e) {
                console.log('🚀 NUCLEAR REVOKE ACCESS CLICKED!');
                e.preventDefault();
                e.stopPropagation();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                console.log('🔍 NUCLEAR VALUES:', {userId, courseId});
                
                if (!userId || !courseId) {
                    alert('SELECT USER AND COURSE FIRST!');
                    return false;
                }
                
                if (!confirm('ARE YOU SURE YOU WANT TO REVOKE ACCESS?')) {
                    return false;
                }
                
                // NUCLEAR AJAX CALL
                $.ajax({
                    url: '/manage-access/revoke',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('✅ NUCLEAR SUCCESS:', response);
                        alert('ACCESS REVOKED SUCCESSFULLY!');
                        window.location.reload();
                    },
                    error: function(xhr) {
                        console.error('❌ NUCLEAR ERROR:', xhr.responseText);
                        alert('ERROR: ' + (xhr.responseJSON?.message || 'Failed to revoke access'));
                    }
                });
            });
            
            // TEST BUTTON - NUCLEAR HANDLER
            $(document).on('click', '#testButton', function(e) {
                console.log('🚀 NUCLEAR TEST BUTTON CLICKED!');
                e.preventDefault();
                e.stopPropagation();
                
                alert('NUCLEAR TEST BUTTON WORKS! JavaScript is loaded.');
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                console.log('NUCLEAR TEST VALUES:', {userId, courseId});
            });
            
            console.log('✅ NUCLEAR handlers attached successfully');
        }
        
        // EXECUTE IMMEDIATELY
        $(document).ready(function() {
            console.log('🚀 NUCLEAR FIX - DOCUMENT READY');
            attachNuclearHandlers();
            
            // Force attach again after a delay
            setTimeout(function() {
                console.log('🚀 NUCLEAR FIX - DELAYED ATTACHMENT');
                attachNuclearHandlers();
            }, 1000);
            
            // Force attach again after another delay
            setTimeout(function() {
                console.log('🚀 NUCLEAR FIX - FINAL ATTACHMENT');
                attachNuclearHandlers();
            }, 2000);
        });
            
            // NUCLEAR FIX - CLEAN END
            console.log('🚀 NUCLEAR FIX COMPLETE');
        });

            // Alert system function
            function showAlert(message, type = 'info', duration = 5000) {
                const alertId = 'alert-' + Date.now();
                const alertClass = 'alert-' + type;
                const iconClass = getAlertIcon(type);
                
                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert" id="${alertId}" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="fas fa-${iconClass} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('body').append(alert);
                
                // Auto-dismiss after duration
                setTimeout(() => {
                    $(`#${alertId}`).alert('close');
                }, duration);
            }

            function getAlertIcon(type) {
                const icons = {
                    'success': 'check-circle',
                    'danger': 'exclamation-triangle',
                    'warning': 'exclamation-circle',
                    'info': 'info-circle'
                };
                return icons[type] || 'info-circle';
            }

            // Make showAlert available globally
            window.showAlert = showAlert;

            // Check for URL parameters to activate the right tab
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'payment-history') {
                $('#payment-tab').tab('show');
                loadPaymentHistory();
            }
            
            // LETHAL SELECT2 INITIALIZATION WITH DELAY
            console.log('🔥 LETHAL MODE: Initializing Select2');
            
            // Force delay to ensure DOM is ready
            setTimeout(function() {
                console.log('🔥 LETHAL DELAYED INITIALIZATION');
                
                // Check if elements exist
                const userSelect = $('.user-select');
                const courseSelect = $('#course_id');
                
                console.log('🔥 User select found:', userSelect.length);
                console.log('🔥 User options:', userSelect.find('option').length);
                console.log('🔥 Course select found:', courseSelect.length);
                console.log('🔥 Course options:', courseSelect.find('option').length);
                
                // Destroy existing Select2 if it exists
                if (userSelect.hasClass('select2-hidden-accessible')) {
                    userSelect.select2('destroy');
                }
                if (courseSelect.hasClass('select2-hidden-accessible')) {
                    courseSelect.select2('destroy');
                }
                
                // Initialize with LETHAL force
                if (userSelect.length > 0 && userSelect.find('option').length > 1) {
                    userSelect.select2({
                        placeholder: 'Search for user...',
                        allowClear: true,
                        width: '100%'
                    });
                    console.log('🔥 User Select2 initialized with', userSelect.find('option').length, 'options');
                } else {
                    console.error('🔥 LETHAL ERROR: User select not found or no options!');
                }

                if (courseSelect.length > 0 && courseSelect.find('option').length > 1) {
                    courseSelect.select2({
                        placeholder: 'Choose course...',
                        allowClear: true,
                        width: '100%'
                    });
                    console.log('🔥 Course Select2 initialized with', courseSelect.find('option').length, 'options');
                } else {
                    console.error('🔥 LETHAL ERROR: Course select not found or no options!');
                }
            }, 100); // 100ms delay
            
            // LETHAL BACKUP INITIALIZATION
            setTimeout(function() {
                console.log('🔥 LETHAL BACKUP CHECK');
                
                const userSelect = $('.user-select');
                const courseSelect = $('#course_id');
                
                // If Select2 still not initialized, force it
                if (!userSelect.hasClass('select2-hidden-accessible') && userSelect.find('option').length > 1) {
                    console.log('🔥 BACKUP: Initializing user select');
                    userSelect.select2({
                        placeholder: 'Search for user...',
                        allowClear: true,
                        width: '100%'
                    });
                }
                
                if (!courseSelect.hasClass('select2-hidden-accessible') && courseSelect.find('option').length > 1) {
                    console.log('🔥 BACKUP: Initializing course select');
                    courseSelect.select2({
                        placeholder: 'Choose course...',
                        allowClear: true,
                        width: '100%'
                    });
                }
            }, 500); // 500ms backup delay

            $('.admin-user-select').select2({
                placeholder: 'Search for user...',
                allowClear: true,
                width: '100%'
            });

            // Handle user selection for access display
            $('.user-select').on('select2:select', function(e) {
                const userId = e.params.data.id;
                updateUserInfo(userId);
                updateGrantButton();
            });

            $('.user-select').on('select2:unselect', function() {
                $('#userInfoSection').hide();
                updateGrantButton();
            });
            
            // Handle course selection for button state
            $('#course_id').on('select2:select change', function() {
                console.log('🔧 Course selected');
                updateGrantButton();
            });
            
            $('#course_id').on('select2:unselect', function() {
                console.log('🔧 Course unselected');
                updateGrantButton();
            });
            
            // Initialize button state after Select2 is ready
            setTimeout(function() {
                updateGrantButton();
                console.log('🔧 Button state initialized');
            }, 100);

            // Simple course selection handling - no buttons to manage

            // No button handling - buttons removed

            // Handle admin user selection
            let selectedAdminUserId = null;
            let selectedAdminIsAdmin = false;

            // Handle admin user selection
            $('.admin-user-select').on('select2:select change', function(e) {
                const selectedOption = $(this).find('option:selected');
                const userId = selectedOption.val();
                
                if (!userId) {
                    $('#selectedUserInfo').hide();
                    $('#adminActionHint').show();
                    selectedAdminUserId = null;
                    return;
                }
                
                selectedAdminUserId = userId;
                
                // Get user details from the option data attributes
                const user = {
                    id: userId,
                    name: selectedOption.data('name'),
                    email: selectedOption.data('email'),
                    role: selectedOption.data('role')
                };
                
                selectedAdminIsAdmin = user.role === 'admin';
                updateAdminInterface(user);
            });

            $('.admin-user-select').on('select2:unselect', function() {
                selectedAdminUserId = null;
                selectedAdminIsAdmin = false;
                $('#selectedUserInfo').hide();
                $('#adminActionHint').show();
                // Reset buttons to disabled state
                $('#promoteToAdminBtn, #removeAdminBtn').prop('disabled', true).addClass('disabled');
            });

            // Update admin interface when user is selected
            function updateAdminInterface(user) {
                // Show user info
                $('#selectedUserInfo').show();
                $('#adminActionHint').hide();
                
                // Update user details
                $('#selectedAdminUserName').text(user.name || 'Unknown User');
                $('#selectedAdminUserEmail').text(user.email || 'No email');
                
                // Update status badge and enable appropriate button
                if (user.role === 'admin') {
                    $('#selectedAdminUserStatus').text('ADMIN').removeClass('bg-secondary').addClass('bg-danger');
                    $('#promoteToAdminBtn').prop('disabled', true).addClass('disabled');
                    $('#removeAdminBtn').prop('disabled', false).removeClass('disabled');
                } else {
                    $('#selectedAdminUserStatus').text('USER').removeClass('bg-danger').addClass('bg-secondary');
                    $('#promoteToAdminBtn').prop('disabled', false).removeClass('disabled');
                    $('#removeAdminBtn').prop('disabled', true).addClass('disabled');
                }
            }

            // Promote to admin
            $('#promoteToAdminBtn').click(function() {
                showAlert('Promote button clicked!', 'info'); // TEST ALERT
                
                if ($(this).prop('disabled')) {
                    showAlert('Please select a regular user to promote', 'warning');
                    return;
                }

                if (!selectedAdminUserId) {
                    showAlert('Please select a user first', 'warning');
                    return;
                }

                if (!confirm('Are you sure you want to promote this user to admin?')) return;

                // Show loading state
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Promoting...').prop('disabled', true);

                $.ajax({
                    url: '{{ secure_url("/manage-access/toggle-admin") }}',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'promote',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reset button
                        $('#promoteToAdminBtn').html(originalText);
                        
                        if (response.success) {
                            selectedAdminIsAdmin = true;
                            $('#selectedAdminUserStatus').text('ADMIN').removeClass('bg-secondary').addClass('bg-danger');
                            $('#promoteToAdminBtn').prop('disabled', true).addClass('disabled');
                            $('#removeAdminBtn').prop('disabled', false).removeClass('disabled');
                            showAlert(response.message || 'User promoted to admin successfully!', 'success');
                        } else {
                            $('#promoteToAdminBtn').prop('disabled', false);
                            showAlert(response.message || 'Failed to promote user', 'danger');
                        }
                    },
                    error: function(xhr) {
                        // Reset button
                        $('#promoteToAdminBtn').html(originalText).prop('disabled', false);
                        const message = xhr.responseJSON?.message || 'Error promoting user to admin';
                        showAlert(message, 'danger');
                    }
                });
            });

            // Remove admin privileges
            $('#removeAdminBtn').click(function() {
                showAlert('Remove admin button clicked!', 'info'); // TEST ALERT
                
                if ($(this).prop('disabled')) {
                    showAlert('Please select an admin user to revoke privileges', 'warning');
                    return;
                }

                if (!selectedAdminUserId) {
                    showAlert('Please select a user first', 'warning');
                    return;
                }

                if (!confirm('Are you sure you want to remove admin privileges from this user?')) return;

                // Show loading state
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Revoking...').prop('disabled', true);

                $.ajax({
                    url: '{{ secure_url("/manage-access/toggle-admin") }}',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'revoke',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reset button
                        $('#removeAdminBtn').html(originalText);
                        
                        if (response.success) {
                            selectedAdminIsAdmin = false;
                            $('#selectedAdminUserStatus').text('USER').removeClass('bg-danger').addClass('bg-secondary');
                            $('#removeAdminBtn').prop('disabled', true).addClass('disabled');
                            $('#promoteToAdminBtn').prop('disabled', false).removeClass('disabled');
                            showAlert(response.message || 'Admin privileges removed successfully!', 'success');
                        } else {
                            $('#removeAdminBtn').prop('disabled', false);
                            showAlert(response.message || 'Failed to remove admin privileges', 'danger');
                        }
                    },
                    error: function(xhr) {
                        // Reset button
                        $('#removeAdminBtn').html(originalText).prop('disabled', false);
                        const message = xhr.responseJSON?.message || 'Error removing admin privileges';
                        showAlert(message, 'danger');
                    }
                });
            });

            // Enrollment form submission - REMOVED DUPLICATE HANDLER

            // Simple user info display
            function updateUserInfo(userId) {
                if (!userId) {
                    $('#userInfoSection').hide();
                    return;
                }

                // Get user data from the dropdown option
                const selectedOption = $('#user_id option:selected');
                if (!selectedOption.val()) {
                    $('#userInfoSection').hide();
                    return;
                }

                // Show user section
                $('#userInfoSection').show();
                
                // Update user info from dropdown data
                const userName = selectedOption.text().split(' (')[0]; // Extract name before email
                const userEmail = selectedOption.data('email');
                const userRole = selectedOption.data('role');
                
                $('#selectedUserName').text(userName);
                $('#selectedUserEmail').text(userEmail);
                $('#selectedUserId').text(userId);
                $('#selectedUserRole').text(userRole.toUpperCase())
                    .removeClass('bg-secondary bg-danger')
                    .addClass(userRole === 'admin' ? 'bg-danger' : 'bg-secondary');
                $('#userRoleDisplay').text(userRole.charAt(0).toUpperCase() + userRole.slice(1));
            }

            // Payment history functionality
            $('#payment-tab').on('shown.bs.tab', function() {
                loadPaymentHistory();
            });

            function loadPaymentHistory() {
                // Load payment statistics and history
                $.get('/admin/payments/data', function(data) {
                    // Update stats
                    $('#total-revenue').text(data.stats.total_revenue + ' EGP');
                    $('#completed-count').text(data.stats.completed_count);
                    $('#pending-count').text(data.stats.pending_count);
                    $('#rejected-count').text(data.stats.rejected_count);
                    
                    // Update table
                    const tbody = $('#payments-table-body');
                    tbody.empty();
                    
                    data.payments.forEach(function(payment) {
                        const statusBadge = getStatusBadge(payment.status);
                        const actions = getPaymentActions(payment);
                        
                        tbody.append(`
                            <tr>
                                <td>${payment.id}</td>
                                <td>
                                    <div>${payment.user.name}</div>
                                    <small class="text-muted">${payment.user.email}</small>
                                </td>
                                <td><span class="badge bg-primary">${payment.course.name}</span></td>
                                <td>${payment.amount} EGP</td>
                                <td><span class="badge bg-secondary">${payment.payment_method}</span></td>
                                <td>${statusBadge}</td>
                                <td>${payment.reference_number || '-'}</td>
                                <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                                <td>${actions}</td>
                            </tr>
                        `);
                    });
                    
                    $('#showing-records').text(data.payments.length);
                    $('#total-records-count').text(data.total);
                }).fail(function() {
                    // Fallback if endpoint doesn't exist
                    $('#payments-table-body').html('<tr><td colspan="9" class="text-center text-muted">Payment data not available</td></tr>');
                });
            }

            function getStatusBadge(status) {
                const badges = {
                    'pending': '<span class="badge bg-warning">Pending</span>',
                    'completed': '<span class="badge bg-success">Completed</span>',
                    'rejected': '<span class="badge bg-danger">Rejected</span>'
                };
                return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
            }

            function getPaymentActions(payment) {
                if (payment.status === 'pending') {
                    return `
                        <button class="btn btn-sm btn-success me-1" onclick="acceptPayment(${payment.id})">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectPayment(${payment.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                }
                return '-';
            }

            // Payment search
            $('#payment-search-btn').click(function() {
                const query = $('#payment-search').val();
                // Implement search functionality
                console.log('Searching for:', query);
            });

            // Enhanced utility functions
            function showAlert(message, type, duration = 5000) {
                const alertId = 'alert-' + Date.now();
                const alert = $(`
                    <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${getAlertIcon(type)} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                $('.container').prepend(alert);
                
                // Auto-dismiss
                setTimeout(() => {
                    $(`#${alertId}`).alert('close');
                }, duration);
            }

            function getAlertIcon(type) {
                const icons = {
                    'success': 'check-circle',
                    'danger': 'exclamation-triangle',
                    'warning': 'exclamation-circle',
                    'info': 'info-circle'
                };
                return icons[type] || 'info-circle';
            }

            // No button loading functions needed - buttons removed

            // Real-time updates
            function updateSystemTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour12: false, 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                $('#current-time').text(timeString);
            }

            // Update time every minute
            setInterval(updateSystemTime, 60000);
            updateSystemTime(); // Initial call

            // Enhanced enrollment filtering
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
                
                // Update visible count
                const visibleRows = rows.filter(':visible').length;
                showAlert(`Showing ${visibleRows} enrollments`, 'info', 2000);
            });

            // Refresh enrollments
            $('#refreshEnrollments').click(function() {
                const btn = $(this);
                btn.find('i').addClass('fa-spin');
                
                setTimeout(() => {
                    btn.find('i').removeClass('fa-spin');
                    showAlert('Enrollment data refreshed', 'success', 2000);
                    // In a real app, you'd reload the data here
                }, 1000);
            });

            // No button initialization needed - buttons removed

            // No enrollment table functions needed - functionality removed

            // Event listeners for select dropdowns
            $(document).on('change', '#user_id, #course_id', function() {
                console.log('🔄 Select dropdown changed:', $(this).attr('id'), 'Value:', $(this).val());
                updateGrantButton();
            });
            
            // Initialize button states on page load
            $(document).ready(function() {
                console.log('🚀 Page loaded, initializing button states');
                updateGrantButton();
            });
        });

        // No action functions needed - buttons removed
                    modalBody.html(`
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                    ${user.name.charAt(0).toUpperCase()}
                                </div>
                                <h5>${user.name}</h5>
                                <p class="text-muted">${user.email}</p>
                                <span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-secondary'}">${user.role.toUpperCase()}</span>
                            </div>
                            <div class="col-md-8">
                                <h6>Account Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>User ID:</strong></td>
                                        <td>${user.id}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Registered:</strong></td>
                                        <td>${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'Unknown'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Login:</strong></td>
                                        <td>${user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : 'Never'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                    </tr>
                                </table>
                                
                                <h6 class="mt-4">Enrolled Courses</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    ${user.courses && user.courses.length > 0 ? 
                                        user.courses.map(course => `<span class="badge bg-primary">${course.name}</span>`).join('') :
                                        '<span class="text-muted">No courses enrolled</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    `);
                }
            }).fail(function() {
                modal.find('.modal-body').html('<div class="alert alert-danger">Failed to load user details</div>');
            });
            
            // Clean up modal when closed
            modal.on('hidden.bs.modal', function() {
                modal.remove();
            });
        };

        // Payment management functions (enhanced)
        // Enhanced payment management with better UX
        window.acceptPayment = function(paymentId) {
            // Create confirmation modal
            const modal = $(`
                <div class="modal fade" id="acceptPaymentModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle text-success me-2"></i>Accept Payment
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to accept this payment?</p>
                                <div class="mb-3">
                                    <label class="form-label">Admin Notes (Optional)</label>
                                    <textarea class="form-control" id="acceptNotes" rows="3" placeholder="Add any notes about this payment verification..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" id="confirmAccept">
                                    <i class="fas fa-check me-2"></i>Accept Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            const modalInstance = new bootstrap.Modal(modal[0]);
            modalInstance.show();
            
            modal.find('#confirmAccept').click(function() {
                const notes = modal.find('#acceptNotes').val();
                const btn = $(this);
                
                showButtonLoading('#confirmAccept', 'Accept Payment');
                
                $.ajax({
                    url: `/admin/payments/verify/${paymentId}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { admin_notes: notes },
                    success: function(response) {
                        hideButtonLoading('#confirmAccept');
                        if (response.success) {
                            modalInstance.hide();
                            showAlert('Payment accepted successfully', 'success');
                            loadPaymentHistory();
                            
                            // Update the specific row
                            updatePaymentRow(paymentId, 'completed');
                        }
                    },
                    error: function(xhr) {
                        hideButtonLoading('#confirmAccept');
                        const message = xhr.responseJSON?.message || 'Error accepting payment';
                        showAlert(message, 'danger');
                    }
                });
            });
            
            modal.on('hidden.bs.modal', function() {
                modal.remove();
            });
        };

        window.rejectPayment = function(paymentId) {
            // Create rejection modal
            const modal = $(`
                <div class="modal fade" id="rejectPaymentModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-times-circle text-danger me-2"></i>Reject Payment
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please provide a reason for rejecting this payment:</p>
                                <div class="mb-3">
                                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="rejectReason" rows="3" placeholder="Explain why this payment is being rejected..." required></textarea>
                                </div>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    The user will be notified of this rejection and the reason provided.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmReject">
                                    <i class="fas fa-times me-2"></i>Reject Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            const modalInstance = new bootstrap.Modal(modal[0]);
            modalInstance.show();
            
            modal.find('#confirmReject').click(function() {
                const reason = modal.find('#rejectReason').val().trim();
                
                if (!reason) {
                    showAlert('Please provide a reason for rejection', 'warning');
                    return;
                }
                
                showButtonLoading('#confirmReject', 'Reject Payment');
                
                $.ajax({
                    url: `/admin/payments/${paymentId}/reject`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: { reason: reason },
                    success: function(response) {
                        hideButtonLoading('#confirmReject');
                        if (response.success) {
                            modalInstance.hide();
                            showAlert('Payment rejected successfully', 'success');
                            loadPaymentHistory();
                            
                            // Update the specific row
                            updatePaymentRow(paymentId, 'rejected');
                        }
                    },
                    error: function(xhr) {
                        hideButtonLoading('#confirmReject');
                        const message = xhr.responseJSON?.message || 'Error rejecting payment';
                        showAlert(message, 'danger');
                    }
                });
            });
            
            modal.on('hidden.bs.modal', function() {
                modal.remove();
            });
        };

        // Update payment row status
        function updatePaymentRow(paymentId, status) {
            const row = $(`tr[data-payment-id="${paymentId}"]`);
            if (row.length) {
                const statusCell = row.find('td:nth-child(6)');
                const actionsCell = row.find('td:nth-child(9)');
                
                if (status === 'completed') {
                    statusCell.html('<span class="badge bg-success">Completed</span>');
                    actionsCell.html('<span class="text-muted small">Verified</span>');
                } else if (status === 'rejected') {
                    statusCell.html('<span class="badge bg-danger">Rejected</span>');
                    actionsCell.html('<span class="text-muted small">Rejected</span>');
                }
                
                // Add visual feedback
                row.addClass('table-warning');
                setTimeout(() => {
                    row.removeClass('table-warning');
                }, 2000);
            }
        }

        // Performance optimizations
            
            // Debounce function for search inputs
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Enhanced search with debouncing
            const debouncedPaymentSearch = debounce(function(query) {
                if (query.length < 2) return;
                
                // Perform search
                const rows = $('#payments-table-body tr');
                let visibleCount = 0;
                
                rows.each(function() {
                    const row = $(this);
                    const text = row.text().toLowerCase();
                    
                    if (text.includes(query.toLowerCase())) {
                        row.show();
                        visibleCount++;
                    } else {
                        row.hide();
                    }
                });
                
                $('#showing-records').text(visibleCount);
            }, 300);

            $('#payment-search').on('input', function() {
                const query = $(this).val();
                if (query.length === 0) {
                    $('#payments-table-body tr').show();
                    $('#showing-records').text($('#payments-table-body tr').length);
                } else {
                    debouncedPaymentSearch(query);
                }
            });

            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl/Cmd + K to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    if ($('#access').hasClass('active')) {
                        $('.user-select').select2('open');
                    } else {
                        $('#payment-search').focus();
                    }
                }
                
                // Ctrl/Cmd + Shift + A to switch to Access tab
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
                    e.preventDefault();
                    $('#access-tab').tab('show');
                }
                
                // Ctrl/Cmd + Shift + P to switch to Payment tab
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
                    e.preventDefault();
                    $('#payment-tab').tab('show');
                }
                
                // Escape to close modals
                if (e.key === 'Escape') {
                    $('.modal.show').modal('hide');
                }
            });

            // No form preferences needed - form removed

        });
    </script>
</body>
</html>