<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - MedCapsule Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* Admin layout styles */
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Sidebar */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #4e73df;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            z-index: 1;
        }
        
        .sidebar-brand span {
            color: #fff;
        }
        
        .sidebar-divider {
            margin: 0 1rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .sidebar-heading {
            color: rgba(255, 255, 255, 0.4);
            font-weight: 800;
            font-size: 0.65rem;
            text-transform: uppercase;
            padding: 0 1rem;
            margin-bottom: 0.5rem;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.15s;
        }
        
        .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            color: #fff;
            font-weight: 700;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .nav-link i {
            margin-right: 0.5rem;
            opacity: 0.8;
            font-size: 0.85rem;
            width: 1rem;
            text-align: center;
        }
        
        /* Content */
        .admin-content {
            margin-left: 250px;
            padding: 1.5rem;
        }
        
        /* Cards */
        .card {
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100px;
            }
            
            .admin-content {
                margin-left: 100px;
            }
            
            .sidebar-brand span {
                display: none;
            }
            
            .nav-link span {
                display: none;
            }
            
            .nav-link i {
                font-size: 1.2rem;
                margin-right: 0;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('home') }}">
            <div class="sidebar-brand-icon">
                <i class="fas fa-stethoscope"></i>
            </div>
            <div class="sidebar-brand-text mx-3">
                <span>MedCapsule</span>
            </div>
        </a>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Core</div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.user-course') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Content</div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('add-course.form') }}">
                    <i class="fas fa-fw fa-graduation-cap"></i>
                    <span>Courses</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('add-topic.form') }}">
                    <i class="fas fa-fw fa-folder"></i>
                    <span>Topics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('add-question.form') }}">
                    <i class="fas fa-fw fa-question-circle"></i>
                    <span>Questions</span>
                </a>
            </li>
        </ul>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">User Management</div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.user-course') && !request()->query('tab') ? 'active' : '' }}" 
                   href="{{ route('admin.user-course') }}">
                    <i class="fas fa-fw fa-users"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.user-course') && request()->query('tab') == 'payments' ? 'active' : '' }}" 
                   href="{{ route('admin.user-course', ['tab' => 'payments']) }}">
                    <i class="fas fa-fw fa-money-bill"></i>
                    <span>Payment History</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.user-course') && request()->query('tab') == 'payment-settings' ? 'active' : '' }}" 
                   href="{{ route('admin.user-course', ['tab' => 'payment-settings']) }}">
                    <i class="fas fa-cog fa-fw mr-2"></i>
                    <span>Payment Settings</span>
                </a>
            </li>
        </ul>
        
        <hr class="sidebar-divider d-none d-md-block">
        
        <ul class="nav flex-column mt-auto">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-fw fa-arrow-left"></i>
                    <span>Back to Site</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Content Wrapper -->
    <div class="admin-content">
        @yield('content')
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    @yield('scripts')
</body>
</html> 