<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Course Access - MedCapsule Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 2rem;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .proof-thumbnail {
            position: relative;
            display: inline-block;
        }
        
        .proof-thumbnail img {
            transition: all 0.2s ease;
        }
        
        .proof-thumbnail img:hover {
            transform: scale(3);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 10;
        }
        
        .proof-thumbnail::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.1);
            border-radius: 3px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .proof-thumbnail:hover::after {
            opacity: 1;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background: #2196f3;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
            border-color: #2196f3;
        }

        .btn-primary {
            background: #2196f3;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #1976d2;
            transform: translateY(-1px);
        }

        .table {
            margin: 0;
        }

        .table th {
            font-weight: 600;
            color: #2196f3;
            border-bottom-width: 1px;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
            margin: 0.2rem;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .badge.renewal {
            background-color: #17a2b8 !important;
            margin-left: 0.5rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .enrollment-history {
            max-height: 400px;
            overflow-y: auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #2196f3;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2196f3;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .warning-stat {
            color: #ff9800;
        }

        .success-stat {
            color: #4caf50;
        }

        .info-stat {
            color: #2196f3;
        }

        .latest-users-table td {
            padding: 1rem;
        }

        .course-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .course-name {
            font-weight: 600;
            color: #2196f3;
            margin-bottom: 0.5rem;
        }

        .course-enrolled {
            font-size: 0.9rem;
            color: #666;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-new {
            background: #e3f2fd;
            color: #2196f3;
        }

        .status-active {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-inactive {
            background: #ffebee;
            color: #f44336;
        }

        .select2-container .select2-selection--single {
            height: 45px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 45px;
            padding-left: 15px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 45px;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #2196f3;
        }

        .select2-dropdown {
            border-color: #e0e0e0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .select2-search__field {
            border-radius: 4px !important;
            padding: 5px 10px !important;
        }

        .course-stat-card {
            border-left: 4px solid transparent;
            transition: transform 0.2s ease;
        }

        .course-stat-card:hover {
            transform: translateX(5px);
        }

        .select2-container .select2-selection--single {
            height: 50px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 50px;
            padding-left: 20px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
        }

        .select2-results__option {
            padding: 10px 20px;
            border-bottom: 1px solid #eee;
        }

        .select2-results__option:last-child {
            border-bottom: none;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f8f9fa;
            color: #333;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e3f2fd;
        }

        .badge.anatomy {
            background-color: #ff4444 !important;
            color: white;
        }

        .badge.histology {
            background-color: #ff66b2 !important;
            color: white;
        }

        .badge.physiology {
            background-color: #ffeb3b !important;
            color: black;
        }

        .badge.biochemistry {
            background-color: #9c27b0 !important;
            color: white;
        }

        /* Add these styles for the user dropdown */
        .user-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .user-email {
            font-weight: 500;
        }

        .courses-count {
            font-size: 0.85rem;
            color: #666;
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .select2-container--default .select2-results__option {
            padding: 8px 15px;
        }

        .text-decoration-line-through {
            text-decoration: line-through !important;
            opacity: 0.7;
        }

        /* Highlight pending verification rows */
        .table tr.pending-verification {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .table tr.pending-verification:hover {
            background-color: rgba(255, 193, 7, 0.2) !important;
        }

        /* Add these styles to your existing styles section */
        .fw-500 {
            font-weight: 500 !important;
        }

        .rounded-4 {
            border-radius: 12px !important;
        }

        .table > :not(caption) > * > * {
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }

        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .btn {
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2196f3;
            box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.15);
        }

        #payment-search {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        #payment-search-btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .pagination .page-link {
            padding: 0.5rem 0.75rem;
            color: #2196f3;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: #2196f3;
            border-color: #2196f3;
            color: white;
        }

        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #0d6efd;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }

        /* Payment history table styling */
        .payment-history-section {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-history-section .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
        }

        .payment-stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
            height: 100%;
        }

        .payment-stats-card .icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .payment-stats-card .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .payment-stats-card .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Search and filter styling */
        .search-input {
            border-radius: 8px 0 0 8px !important;
            border: 1px solid #e0e0e0;
        }

        .search-btn {
            border-radius: 0 8px 8px 0 !important;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">MedCapsule</a>
                <div class="d-flex">
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Courses
                    </a>
                </div>
            </div>
        </nav>

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

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="user-management-tab" data-bs-toggle="tab" data-bs-target="#user-management" type="button" role="tab" aria-controls="user-management" aria-selected="true">
                    <i class="fas fa-users me-2"></i> User Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-history-tab" data-bs-toggle="tab" data-bs-target="#payment-history" type="button" role="tab" aria-controls="payment-history" aria-selected="false">
                    <i class="fas fa-money-bill-wave me-2"></i> Payment History
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- User Management Tab -->
            <div class="tab-pane fade show active" id="user-management" role="tabpanel" aria-labelledby="user-management-tab">
                <!-- Key Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-users stat-icon info-stat"></i>
                        <div class="stat-value">{{ $userStats['total_users'] }}</div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-plus stat-icon success-stat"></i>
                        <div class="stat-value">{{ $userStats['active_users'] }}</div>
                        <div class="stat-label">Active Users (7d)</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-week stat-icon info-stat"></i>
                        <div class="stat-value">{{ $userStats['admin_users'] }}</div>
                        <div class="stat-label">Admin Users</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-clock stat-icon warning-stat"></i>
                        <div class="stat-value">{{ $userStats['total_enrollments'] }}</div>
                        <div class="stat-label">Total Enrollments</div>
                    </div>
                </div>

                <div class="row">
                    <!-- Enrollment Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-plus me-2"></i> Enroll User in Course
                            </div>
                            <div class="card-body">
                                <form id="enrollmentForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">Select User</label>
                                        <select class="form-control user-select" id="user_id" name="user_id" required>
                                            <option value="">Select or search for a user...</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" data-courses="{{ $user->courses()->count() }}">
                                                    {{ $user->name }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="course_id" class="form-label">Select Course</label>
                                        <select class="form-select course-select" id="course_id" name="course_id" required>
                                            <option value="">Choose a course...</option>
                                            @foreach($courses as $course)
                                                <option value="{{ $course->id }}" data-color="{{ $course->color }}">{{ $course->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="enrollButton" data-action="enroll">
                                            <i class="fas fa-plus-circle me-2"></i>Grant Access
                                        </button>
                                        <button type="button" class="btn btn-danger" id="revokeButton" data-action="unenroll">
                                            <i class="fas fa-minus-circle me-2"></i>Revoke Access
                                        </button>
                                        <button type="button" class="btn btn-warning ms-auto" id="toggleAdminBtn" disabled>
                                            <i class="fas fa-user-shield me-2"></i>Promote to Admin
                                        </button>
                                    </div>

                                    <!-- User's Current Access -->
                                    <div class="mt-4" id="userAccessSection" style="display: none;">
                                        <h6 class="mb-3">Current Access</h6>
                                        <div id="userAccessList" class="d-flex flex-wrap gap-2">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Latest Users -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <i class="fas fa-user-clock me-2"></i> Latest Registered Users
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table latest-users-table">
                                        <thead>
                                            <tr>
                                                <th>Email</th>
                                                <th>Registered</th>
                                                <th>Courses</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($latestUsers ?? [] as $user)
                                                <tr>
                                                    <td>{{ $user['email'] }}</td>
                                                    <td>{{ Carbon\Carbon::parse($user['registered_at'])->diffForHumans() }}</td>
                                                    <td>{{ $user['courses_count'] }}</td>
                                                    <td>
                                                        @if($user['courses_count'] == 0)
                                                            <span class="status-badge status-new">New</span>
                                                        @else
                                                            <span class="status-badge status-active">Active</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Course Statistics -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-bar me-2"></i> Course Enrollment Statistics
                            </div>
                            <div class="card-body">
                                <div class="course-stats-grid">
                                    @foreach($courseStats ?? [] as $stat)
                                        <div class="course-stat-card" style="border-left-color: {{ $courses->where('name', $stat->name)->first()->color }}">
                                            <div class="course-name" style="color: {{ $courses->where('name', $stat->name)->first()->color }}">
                                                {{ $stat->name }}
                                            </div>
                                            <div class="course-enrolled">
                                                <i class="fas fa-users me-2"></i>
                                                {{ $stat->enrolled_count }} enrolled
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Recent Enrollments with compact design -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-history me-2"></i>Course Access History</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Course</th>
                                                <th>Access Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="accessHistoryTable">
                                            @foreach($enrolledUsers ?? [] as $enrollment)
                                                <tr>
                                                    <td class="small">{{ $enrollment->email }}</td>
                                                    <td>
                                                        @if($enrollment->enrollment_status === 'revoked')
                                                            <span class="badge bg-secondary text-decoration-line-through">
                                                                {{ $enrollment->course_name }}
                                                            </span>
                                                        @else
                                                            <span class="badge" style="background-color: {{ $courses->where('name', $enrollment->course_name)->first()->color }}">
                                                                {{ $enrollment->course_name }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="small">{{ Carbon\Carbon::parse($enrollment->created_at)->format('M d, Y') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Tab -->
            <div class="tab-pane fade" id="payment-history" role="tabpanel" aria-labelledby="payment-history-tab">
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-money-bill-wave text-primary me-3"></i>
                                    <div>
                                        <div class="h5 mb-0 text-primary" id="total-revenue">0.00 EGP</div>
                                        <div class="text-muted small">Total Revenue</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-3"></i>
                                    <div>
                                        <div class="h5 mb-0 text-success" id="completed-count">0</div>
                                        <div class="text-muted small">Completed</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-warning me-3"></i>
                                    <div>
                                        <div class="h5 mb-0 text-warning" id="pending-count">0</div>
                                        <div class="text-muted small">Pending</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-times-circle text-danger me-3"></i>
                                    <div>
                                        <div class="h5 mb-0 text-danger" id="rejected-count">0</div>
                                        <div class="text-muted small">Rejected</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Table Card -->
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-dark">Payment History</h5>
                            <div class="d-flex align-items-center">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="payment-search" placeholder="Search payments...">
                                    <button class="btn btn-primary" type="button" id="payment-search-btn">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-muted small fw-500">ID</th>
                                    <th class="text-muted small fw-500">User</th>
                                    <th class="text-muted small fw-500">Course</th>
                                    <th class="text-muted small fw-500">Amount</th>
                                    <th class="text-muted small fw-500">Method</th>
                                    <th class="text-muted small fw-500">Status</th>
                                    <th class="text-muted small fw-500">Reference</th>
                                    <th class="text-muted small fw-500">Date</th>
                                    <th class="text-muted small fw-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payments-table-body">
                                <!-- Payments will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small" id="total-records">
                                Showing <span id="showing-records" class="fw-500">0</span> of <span id="total-records-count" class="fw-500">0</span> payments
                            </div>
                            <nav id="payment-pagination" class="mb-0" aria-label="Payment history pagination">
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
        $(document).ready(function() {
            // Check for URL parameters to activate the right tab
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'payment-history') {
                // Activate payment history tab
                $('#payment-history-tab').tab('show');
            }
            
            // Enhanced Select2 initialization for user selection
            $('.user-select').select2({
                placeholder: 'Select or search for a user...',
                allowClear: true,
                width: '100%',
                templateResult: formatUser,
                templateSelection: formatUserSelection
            });

            // Enhanced Select2 initialization for course selection
            $('.course-select').select2({
                placeholder: 'Choose a course...',
                allowClear: true,
                width: '100%'
            });

            // Format user options in dropdown
            function formatUser(user) {
                if (!user.id) return user.text;
                
                var $container = $(
                    '<div class="user-option">' +
                        '<div class="user-email">' + user.text + '</div>' +
                        '<span class="courses-count">' + 
                            $(user.element).data('courses') + ' courses' +
                        '</span>' +
                    '</div>'
                );
                
                return $container;
            }

            // Format selected user
            function formatUserSelection(user) {
                if (!user.id) return user.text;
                return user.text;
            }

            // Handle admin toggle button
            let selectedUserId = null;
            let isAdmin = false;

            $('.user-select').on('select2:select', function(e) {
                selectedUserId = e.params.data.id;
                $('#toggleAdminBtn').prop('disabled', false);
                
                // Update button text based on admin status
                $.get(`/manage-access/search-users?query=${e.params.data.text}`, function(users) {
                    if (users.length > 0) {
                        isAdmin = users[0].is_admin;
                        updateToggleButton();
                    }
                });
            });

            $('.user-select').on('select2:unselect', function() {
                selectedUserId = null;
                $('#toggleAdminBtn').prop('disabled', true);
            });

            function updateToggleButton() {
                const btn = $('#toggleAdminBtn');
                if (isAdmin) {
                    btn.html('<i class="fas fa-user-times me-2"></i>Remove Admin');
                    btn.removeClass('btn-danger').addClass('btn-warning');
                } else {
                    btn.html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                    btn.removeClass('btn-warning').addClass('btn-danger');
                }
            }

            $('#toggleAdminBtn').click(function() {
                if (!selectedUserId) return;

                $.ajax({
                    url: '{{ route("admin.user-course.toggle-admin") }}',
                    method: 'POST',
                    data: {
                        user_id: selectedUserId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            isAdmin = !isAdmin;
                            updateToggleButton();
                            
                            // Show success message
                            const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                response.message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>');
                            $('.container').prepend(alert);
                        }
                    },
                    error: function(xhr) {
                        // Show error message
                        const message = xhr.responseJSON?.message || 'An error occurred while updating admin status';
                        const alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');
                        $('.container').prepend(alert);
                    }
                });
            });

            function updateUserAccess(userId) {
                if (!userId) {
                    $('#userAccessSection').hide();
                    return;
                }

                $.get(`/manage-access/search-users?query=${userId}`, function(users) {
                    if (users.length > 0) {
                        const user = users[0];
                        const accessList = $('#userAccessList');
                        accessList.empty();

                        if (user.enrolled_courses && user.enrolled_courses.length > 0) {
                            user.enrolled_courses.forEach(courseId => {
                                const course = $(`#course_id option[value="${courseId}"]`);
                                const courseName = course.text();
                                const courseColor = course.data('color');
                                
                                accessList.append(
                                    `<span class="badge" style="background-color: ${courseColor}">
                                        ${courseName}
                                        <button type="button" class="btn-close btn-close-white ms-2" 
                                            style="font-size: 0.5rem;" 
                                            onclick="revokeAccess('${userId}', '${courseId}')">
                                        </button>
                                    </span>`
                                );
                            });
                            $('#userAccessSection').show();
                        } else {
                            accessList.html('<span class="text-muted">No course access</span>');
                            $('#userAccessSection').show();
                        }
                    }
                });
            }

            $('.user-select').on('select2:select', function(e) {
                updateUserAccess(e.params.data.id);
            });

            $('#revokeButton').click(function() {
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                const courseName = $('#course_id option:selected').text();
                
                if (!userId || !courseId) return;

                $.ajax({
                    url: '{{ route("admin.user-course.enroll") }}',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        action: 'unenroll',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                response.message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>');
                            $('.container').prepend(alert);

                            // Find and update existing row if it exists
                            const existingRow = $(`#accessHistoryTable tr:contains('${response.user.email}'):contains('${courseName}')`);
                            if (existingRow.length) {
                                existingRow.find('.badge').removeClass('bg-success')
                                    .addClass('bg-secondary text-decoration-line-through');
                            } else {
                                // Add new row for revoked access
                                const newRow = $('<tr></tr>');
                                newRow.append($('<td class="small"></td>').text(response.user.email));
                                newRow.append($('<td></td>').html(
                                    `<span class="badge bg-secondary text-decoration-line-through">${courseName}</span>`
                                ));
                                newRow.append($('<td class="small"></td>').text(response.user.enrolled_at));
                                $('#accessHistoryTable').prepend(newRow);
                            }

                            updateUserAccess(userId);
                            updateCourseStats();
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred while revoking access';
                        const alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');
                        $('.container').prepend(alert);
                    }
                });
            });

            window.revokeAccess = function(userId, courseId) {
                $.ajax({
                    url: '{{ route("admin.user-course.enroll") }}',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        action: 'unenroll',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            updateUserAccess(userId);
                            updateCourseStats();
                        }
                    }
                });
            };

            // Handle enrollment form submission
            $('#enrollmentForm').on('submit', function(e) {
                e.preventDefault();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                if (!userId || !courseId) {
                    return;
                }

                $.ajax({
                    url: '{{ route("admin.user-course.enroll") }}',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        action: 'enroll',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                                response.message +
                                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                '</div>');
                            $('.container').prepend(alert);

                            // Add new access to history
                            const newRow = $('<tr></tr>');
                            newRow.append($('<td class="small"></td>').text(response.user.email));
                            newRow.append($('<td></td>').html(
                                `<span class="badge" style="background-color: ${$('#course_id option:selected').data('color')}">${response.user.course_name}</span>`
                            ));
                            newRow.append($('<td class="small"></td>').text(response.user.enrolled_at));

                            $('#accessHistoryTable').prepend(newRow);
                            updateUserAccess(userId);
                            updateCourseStats();
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred while updating enrollment';
                        const alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');
                        $('.container').prepend(alert);
                    }
                });
            });

            function updateCourseStats() {
                // Refresh the course statistics section
                $.get(window.location.href, function(data) {
                    const newStats = $(data).find('.course-stats-grid').html();
                    $('.course-stats-grid').html(newStats);
                });
            }

            // Load payment history when the tab is activated
            let paymentHistoryLoaded = false;
            document.getElementById('payment-history-tab').addEventListener('shown.bs.tab', function (e) {
                if (!paymentHistoryLoaded) {
                    loadPaymentHistory();
                    paymentHistoryLoaded = true;
                }
            });
            
            // Also trigger load if tab is active on page load due to URL parameter
            if (urlParams.get('tab') === 'payment-history') {
                loadPaymentHistory();
                paymentHistoryLoaded = true;
            }

            // Search payments
            document.getElementById('payment-search-btn').addEventListener('click', function() {
                loadPaymentHistory(1); // Search from page 1 with current search term
            });
            
            // Search on Enter key
            document.getElementById('payment-search').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    loadPaymentHistory(1);
                }
            });
            
            // Load payment history when tab is activated
            document.getElementById('payment-history-tab').addEventListener('shown.bs.tab', function() {
                if (!document.querySelector('#payments-table-body tr')) {
                    loadPaymentHistory();
                }
            });

            // Add event listeners to view buttons
            document.querySelectorAll('.view-payment').forEach(button => {
                button.addEventListener('click', function() {
                    const paymentId = this.getAttribute('data-payment-id');
                    viewPaymentDetails(paymentId);
                });
            });
            
            // Add event listeners to accept buttons
            document.querySelectorAll('.accept-payment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const paymentId = this.getAttribute('data-payment-id');
                    acceptPayment(paymentId);
                });
            });
        });
    </script>

    <!-- Add JavaScript for loading payment history -->
    <script>
        function loadPaymentHistory(page = 1, filters = {}) {
            const searchQuery = document.getElementById('payment-search').value.trim();
            let url = `/admin/payment-history?page=${page}`;
            
            if (searchQuery) {
                url += `&search=${encodeURIComponent(searchQuery)}`;
            }
            
            // Add filters to URL
            Object.entries(filters).forEach(([key, value]) => {
                if (value) {
                    url += `&${key}=${encodeURIComponent(value)}`;
                }
            });
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        console.error('Error loading payment data:', data.message);
                        return;
                    }
                    
                    const tableBody = document.getElementById('payments-table-body');
                    tableBody.innerHTML = '';
                    
                    const payments = data.payments;
                    
                    if (!payments || !payments.data || payments.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="text-muted"><i class="fas fa-search me-2"></i>No payments found</div></td></tr>';
                        return;
                    }
                    
                    updatePaymentStats(payments);
                    
                    payments.data.forEach(payment => {
                        const row = document.createElement('tr');
                        row.className = payment.status === 'pending' ? 'table-warning' : '';
                        row.innerHTML = `
                            <td>${payment.id}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-bold">${payment.user_name}</div>
                                        <div class="small text-muted">${payment.user_email}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">${payment.course_name}</span>
                                ${payment.payment_data?.is_renewal ? 
                                    `<span class="badge bg-info ms-1" title="This is a renewal payment">
                                        <i class="fas fa-sync-alt"></i> Renewal
                                    </span>` : ''}
                            </td>
                            <td class="fw-bold">
                                ${parseFloat(payment.amount).toFixed(2)} ${payment.currency || 'EGP'}
                            </td>
                            <td>
                                <span class="badge ${getBadgeClass(payment.payment_method)}">
                                    ${formatPaymentMethod(payment.payment_method)}
                                </span>
                            </td>
                            <td>
                                <span class="badge ${getStatusBadgeClass(payment.status)}">
                                    ${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                                </span>
                                ${payment.expired_at ? 
                                    `<span class="badge bg-secondary ms-1" title="Subscription expired on ${formatDate(payment.expired_at)}">
                                        <i class="fas fa-clock"></i> Expired
                                    </span>` : ''}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <code class="me-2">${payment.reference_number}</code>
                                    ${payment.payment_data?.transaction_id ? 
                                        `<span class="badge bg-info" title="Transaction ID: ${payment.payment_data.transaction_id}">
                                            <i class="fas fa-receipt"></i>
                                        </span>` : ''}
                                </div>
                            </td>
                            <td>
                                <div>
                                    ${formatDate(payment.created_at)}
                                </div>
                                ${payment.paid_at ? 
                                    `<small class="text-success">
                                        <i class="fas fa-check-circle"></i> Paid: ${formatDate(payment.paid_at)}
                                    </small>` : ''}
                            </td>
                            <td>
                                ${payment.status === 'completed' ? `
                                    <span class="badge bg-success d-flex align-items-center gap-2 p-2">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                ` : payment.status === 'pending' ? `
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-success grant-access-btn" 
                                                data-payment-id="${payment.id}"
                                                ${hasActivePayment(payments.data, payment) ? 'title="Warning: User has an active subscription" data-bs-toggle="tooltip"' : ''}>
                                            <i class="fas fa-check me-1"></i> Accept
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectPayment(${payment.id})">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>
                                ` : payment.status === 'rejected' ? `
                                    <span class="badge bg-danger d-flex align-items-center gap-2 p-2">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </span>
                                ` : `
                                    <span class="badge bg-secondary d-flex align-items-center gap-2 p-2">
                                        <i class="fas fa-ban"></i> ${payment.status}
                                    </span>
                                `}
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                    
                    updatePagination(payments);
                })
                .catch(error => {
                    console.error('Error loading payment history:', error);
                    const tableBody = document.getElementById('payments-table-body');
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Error loading payments</div></td></tr>';
                });
        }
        
        function updatePagination(payments) {
            const paginationContainer = document.getElementById('payment-pagination');
            if (!paginationContainer) return;
            
            paginationContainer.innerHTML = '';
            
            if (!payments || !payments.last_page || payments.last_page <= 1) return;
            
            const ul = document.createElement('ul');
            ul.className = 'pagination';
            
            // Previous button
            const prevLi = document.createElement('li');
            const currentPage = payments.current_page || 1;
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            const prevLink = document.createElement('a');
            prevLink.className = 'page-link';
            prevLink.href = '#';
            prevLink.innerHTML = '&laquo;';
            prevLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    loadPaymentHistory(currentPage - 1);
                }
            });
            prevLi.appendChild(prevLink);
            ul.appendChild(prevLi);
            
            // Page links
            const lastPage = payments.last_page || 1;
            for (let i = 1; i <= lastPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${currentPage === i ? 'active' : ''}`;
                const link = document.createElement('a');
                link.className = 'page-link';
                link.href = '#';
                link.textContent = i;
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadPaymentHistory(i);
                });
                li.appendChild(link);
                ul.appendChild(li);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === lastPage ? 'disabled' : ''}`;
            const nextLink = document.createElement('a');
            nextLink.className = 'page-link';
            nextLink.href = '#';
            nextLink.innerHTML = '&raquo;';
            nextLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < lastPage) {
                    loadPaymentHistory(currentPage + 1);
                }
            });
            nextLi.appendChild(nextLink);
            ul.appendChild(nextLi);
            
            paginationContainer.appendChild(ul);
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }
        
        function getBadgeClass(method) {
            switch (method) {
                case 'manual':
                case 'manual_payment':
                    return 'bg-secondary';
                case 'fawry':
                    return 'bg-primary';
                case 'vodafone_cash':
                    return 'bg-danger';
                case 'instapay':
                    return 'bg-info';
                default:
                    return 'bg-secondary';
            }
        }
        
        function formatPaymentMethod(method) {
            switch (method) {
                case 'manual':
                case 'manual_payment':
                    return 'Manual Payment';
                case 'fawry':
                    return 'Fawry';
                case 'vodafone_cash':
                    return 'Vodafone Cash';
                case 'instapay':
                    return 'InstaPay';
                default:
                    return method;
            }
        }
        
        function getStatusBadgeClass(status) {
            switch (status) {
                case 'completed':
                    return 'bg-success';
                case 'pending':
                    return 'bg-warning text-dark';
                case 'pending_verification':
                    return 'bg-info';
                case 'rejected':
                    return 'bg-danger';
                case 'expired':
                    return 'bg-secondary';
                default:
                    return 'bg-secondary';
            }
        }
        
        function viewPaymentDetails(paymentId) {
            fetch(`/admin/payment-details/${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message);
                        return;
                    }
                    
                    // Create modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'paymentDetailsModal';
                    modal.setAttribute('tabindex', '-1');
                    modal.setAttribute('aria-labelledby', 'paymentDetailsModalLabel');
                    modal.setAttribute('aria-hidden', 'true');
                    
                    const details = data.payment;
                    const paymentData = details.payment_data ? JSON.stringify(details.payment_data, null, 2) : 'No details available';
                    
                    // Check if this is a manual payment with transaction ID
                    const hasTransactionData = details.payment_data && 
                                              (details.payment_data.transaction_id || 
                                               details.payment_method === 'manual_payment');
                    
                    // Check if there's a payment proof screenshot
                    const hasProofImage = details.payment_data && details.payment_data.proof_path;
                    
                    // Determine if admin verification buttons should be shown
                    const showVerifyButtons = details.status === 'pending' && 
                                             (hasTransactionData || details.payment_method === 'manual_payment');
                    
                    modal.innerHTML = `
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details #${details.id}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Basic Information</h6>
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th>User:</th>
                                                    <td>${details.user_name}</td>
                                                </tr>
                                                <tr>
                                                    <th>Course:</th>
                                                    <td>${details.course_name}</td>
                                                </tr>
                                                <tr>
                                                    <th>Amount:</th>
                                                    <td>${details.amount} ${details.currency}</td>
                                                </tr>
                                                <tr>
                                                    <th>Method:</th>
                                                    <td>${formatPaymentMethod(details.payment_method)}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td><span class="badge ${getStatusBadgeClass(details.status)}">${details.status}</span></td>
                                                </tr>
                                                <tr>
                                                    <th>Reference:</th>
                                                    <td>${details.reference_number}</td>
                                                </tr>
                                                <tr>
                                                    <th>Created:</th>
                                                    <td>${formatDate(details.created_at)}</td>
                                                </tr>
                                                <tr>
                                                    <th>Paid At:</th>
                                                    <td>${details.paid_at ? formatDate(details.paid_at) : 'Not paid yet'}</td>
                                                </tr>
                                                ${hasTransactionData ? `
                                                <tr>
                                                    <th>Transaction ID:</th>
                                                    <td class="text-primary fw-bold">${details.payment_data.transaction_id}</td>
                                                </tr>
                                                <tr>
                                                    <th>Payment Date:</th>
                                                    <td>${details.payment_data.payment_date ? details.payment_data.payment_date : 'N/A'}</td>
                                                </tr>` : ''}
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            ${hasProofImage ? `
                                            <h6>Payment Proof</h6>
                                            <div class="payment-proof-image mb-3">
                                                <img src="/storage/${details.payment_data.proof_path}" class="img-fluid rounded border" 
                                                     alt="Payment Proof" style="max-height: 200px;" 
                                                     onclick="window.open('/storage/${details.payment_data.proof_path}', '_blank')">
                                            </div>` : ''}
                                            <h6>Payment Data</h6>
                                            <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code>${paymentData}</code></pre>
                                        </div>
                                    </div>
                                    
                                    ${showVerifyButtons ? `
                                    <div class="mt-3">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning bg-opacity-25">
                                                <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Verification Required</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="admin-notes" class="form-label">Admin Notes</label>
                                                    <textarea class="form-control" id="admin-notes" rows="2"></textarea>
                                                </div>
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="rejectPayment(${details.id})">
                                                        <i class="fas fa-times me-2"></i>Reject Payment
                                                    </button>
                                                    <button type="button" class="btn btn-success" 
                                                            onclick="verifyPayment(${details.id})">
                                                        <i class="fas fa-check me-2"></i>Accept Payment
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>` : ''}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Initialize and show the modal
                    const modalInstance = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
                    modalInstance.show();
                    
                    // Clean up when the modal is closed
                    document.getElementById('paymentDetailsModal').addEventListener('hidden.bs.modal', function () {
                        document.body.removeChild(modal);
                    });
                })
                .catch(error => {
                    console.error('Error fetching payment details:', error);
                    alert('An error occurred while loading payment details.');
                });
        }
        
        // Function to approve a payment
        function approvePayment(paymentId) {
            const adminNotes = document.getElementById('admin-notes').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            if (!confirm('Are you sure you want to approve this payment? This will grant the user access to the course.')) {
                return;
            }
            
            fetch(`/admin/payments/${paymentId}/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    admin_notes: adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    bootstrap.Modal.getInstance(document.getElementById('paymentDetailsModal')).hide();
                    
                    // Show success message
                    alert('Payment approved successfully!');
                    
                    // Reload payment history
                    loadPaymentHistory();
                } else {
                    alert(data.message || 'Failed to approve payment.');
                }
            })
            .catch(error => {
                console.error('Error approving payment:', error);
                alert('An error occurred while approving the payment.');
            });
        }
        
        // Function to reject a payment
        function rejectPayment(paymentId) {
            const reason = prompt('Please provide a reason for rejecting this payment:');
            if (!reason) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Show loading state
            const buttons = document.querySelectorAll(`button[data-payment-id="${paymentId}"]`);
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            });

            fetch(`/admin/payments/${paymentId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason,
                    admin_notes: 'Rejected via payment history'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message with better styling
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    successAlert.style.zIndex = '9999';
                    successAlert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i> Payment rejected successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(successAlert);
                    
                    // Remove alert after 3 seconds
                    setTimeout(() => {
                        successAlert.remove();
                    }, 3000);
                    
                    // Refresh the payment history
                    loadPaymentHistory();
                } else {
                    alert(data.message || 'Failed to reject payment.');
                }
            })
            .catch(error => {
                console.error('Error rejecting payment:', error);
                alert('An error occurred while rejecting the payment.');
                
                // Reset buttons on error
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-times me-1"></i> Reject';
                });
            });
        }
        
        // Function to quickly verify a payment from the table
        function verifyPayment(paymentId) {
            if (!confirm('Are you sure you want to accept this payment? This will grant the user access to the course.')) {
                return;
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/admin/payments/${paymentId}/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    admin_notes: 'Verified via quick action'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Payment verified successfully. The user now has access to the course.');
                    
                    // Reload payment history
                    loadPaymentHistory();
                } else {
                    alert(data.message || 'Failed to verify payment.');
                }
            })
            .catch(error => {
                console.error('Error verifying payment:', error);
                alert('An error occurred while verifying the payment.');
            });
        }

        // Update the grant access functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.grant-access-btn')) {
                const btn = e.target.closest('.grant-access-btn');
                const paymentId = btn.getAttribute('data-payment-id');
                
                if (!paymentId) {
                    alert('Missing payment information');
                    return;
                }

                if (!confirm('Are you sure you want to accept this payment?')) {
                    return;
                }

                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch(`/admin/payments/${paymentId}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        admin_notes: 'Accepted via payment history'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message with better styling
                        const successAlert = document.createElement('div');
                        successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                        successAlert.style.zIndex = '9999';
                        successAlert.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i> Payment accepted successfully
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.body.appendChild(successAlert);
                        
                        // Remove alert after 3 seconds
                        setTimeout(() => {
                            successAlert.remove();
                        }, 3000);
                        
                        // Refresh the payment history
                        loadPaymentHistory();
                    } else {
                        alert(data.message || 'Failed to accept payment.');
                    }
                })
                .catch(error => {
                    console.error('Error accepting payment:', error);
                    alert('An error occurred while accepting the payment.');
                    
                    // Reset button on error
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Accept';
                });
            }
        });

        function updatePaymentStats(payments) {
            let totalRevenue = 0;
            let completed = 0;
            let pending = 0;
            let rejected = 0;

            payments.data.forEach(payment => {
                const amount = parseFloat(payment.amount) || 0;
                totalRevenue += payment.status === 'completed' ? amount : 0;
                
                switch(payment.status) {
                    case 'completed':
                        completed++;
                        break;
                    case 'pending':
                        pending++;
                        break;
                    case 'rejected':
                        rejected++;
                        break;
                }
            });

            document.getElementById('total-revenue').textContent = totalRevenue.toFixed(2) + ' EGP';
            document.getElementById('completed-count').textContent = completed;
            document.getElementById('pending-count').textContent = pending;
            document.getElementById('rejected-count').textContent = rejected;
            
            // Update records count
            document.getElementById('showing-records').textContent = payments.data.length;
            document.getElementById('total-records-count').textContent = payments.total;
        }

        function applyFilters() {
            const filters = {
                status: document.getElementById('status-filter').value,
                method: document.getElementById('method-filter').value,
                dateFrom: document.getElementById('date-from').value,
                dateTo: document.getElementById('date-to').value
            };
            loadPaymentHistory(1, filters);
        }

        function resetFilters() {
            document.getElementById('status-filter').value = '';
            document.getElementById('method-filter').value = '';
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
            loadPaymentHistory(1);
        }

        // Add this helper function to check for active payments
        function hasActivePayment(payments, currentPayment) {
            return payments.some(p => 
                p.user_id === currentPayment.user_id && 
                p.course_id === currentPayment.course_id && 
                p.status === 'completed' && 
                !p.expired_at &&
                p.id !== currentPayment.id
            );
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 