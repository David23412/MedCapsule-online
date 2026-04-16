@extends('layouts.app')

@section('content')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Tutor Dashboard') - MedCapsule</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <!-- Lightning-FAST FOUC PREVENTION -->
    <style>
        /* CRITICAL CSS - PREVENTS FOUC */
        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }

        /* EMERGENCY FIX - ALWAYS VISIBLE */
        #app {
            opacity: 1 !important;
        }

        /* Tutor-specific styles */
        .tutor-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #6366f1 10%, #4f46e5 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(99, 102, 241, 0.15);
            z-index: 1;
            transition: all 0.3s ease;
            overflow-y: auto;
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

        .tutor-content {
            margin-left: 280px;
            padding: 1.5rem;
            min-height: calc(100vh - 0px);
        }

        .profile-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 12px;
            color: white;
            text-decoration: none;
            background-color: #6366f1;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.8rem;
        }

        .card-title svg {
            margin-right: 0.8rem;
            color: #6366f1;
            width: 20px;
            height: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tutor-sidebar {
                width: 100px;
            }

            .tutor-content {
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
    <div class="tutor-sidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('courses.index') }}">
            <div class="sidebar-brand-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="sidebar-brand-text mx-3">
                <span>Tutor Panel</span>
            </div>
        </a>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Core</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tutor.dashboard') ? 'active' : '' }}" href="{{ route('tutor.dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Student Management</div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tutor.students') }}">
                    <i class="fas fa-fw fa-users"></i>
                    <span>My Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('assignments.index') }}">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>Assignments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tutor.reports') }}">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>

        <hr class="sidebar-divider d-none d-md-block">

        <ul class="nav flex-column mt-auto">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('courses.index') }}">
                    <i class="fas fa-fw fa-arrow-left"></i>
                    <span>Back to Site</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Content Wrapper -->
    <div class="tutor-content">
        @yield('tutor-content')
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    @yield('scripts')
</body>
</html>
@endsection
