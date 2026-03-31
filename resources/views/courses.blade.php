<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>

    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MedCapsule">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#667eea">
    <meta name="msapplication-TileColor" content="#667eea">
    <meta name="msapplication-TileImage" content="/images/icon-144x144.png">
    <meta name="application-name" content="MedCapsule">
    <meta name="description" content="The ultimate medical education platform for students and professionals. Learn, practice, and excel in medical knowledge.">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/images/icon-512x512.png">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%233579F6' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">

    <!-- RUTHLESS: Resource hints for faster loading -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- SUPER MINIMAL CRITICAL CSS -->
    <style>
        /* ESSENTIAL FOUC PREVENTION + PERFORMANCE */
        .clean-notification-dropdown{display:none!important}
        .clean-notification-dropdown.show{display:flex!important}
        .navbar{min-height:76px!important}
        .course-card{opacity:1!important;transform:none!important;contain:layout style paint}
        .course-image{background-color:#ffffff!important;opacity:1!important;will-change:auto}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif!important}
        /* INSTANT PERFORMANCE BOOST */
        *{box-sizing:border-box}
        .courses-grid{contain:layout style;transform:translateZ(0)}
        .course-card{backface-visibility:hidden}
    </style>
    
    <!-- SUPER FAST IMAGE PRELOADING - ONLY FIRST 2 VISIBLE IMAGES -->
    @foreach ($courses->take(2) as $course)
        @php
            $imageUrl = $course->image_url;
            // AGGRESSIVE image optimization - updated dimensions
            if (str_contains($imageUrl, 'unsplash.com')) {
                $imageUrl = str_replace('?', '?w=465&h=670&fit=crop&auto=format,compress&q=60&fm=webp&', $imageUrl);
            } elseif (str_contains($imageUrl, 'picsum.photos')) {
                $imageUrl = str_replace('picsum.photos/', 'picsum.photos/465/670/', $imageUrl) . '?blur=1';
            }
        @endphp
        <link rel="preload" as="image" href="{{ $imageUrl }}" fetchpriority="high">
    @endforeach
    
    <!-- SUPER AGGRESSIVE RESOURCE HINTS -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    
    <!-- ONLY PRECONNECT TO ACTIVE IMAGE DOMAINS -->
    @if($courses->isNotEmpty())
        @php
            $imageDomains = $courses->pluck('image_url')->map(function($url) {
                return parse_url($url, PHP_URL_HOST);
            })->unique()->take(2); // Only first 2 domains
        @endphp
        @foreach($imageDomains as $domain)
            <link rel="preconnect" href="https://{{ $domain }}" crossorigin>
        @endforeach
    @endif

    <!-- SUPER LIGHTNING SPEED CSS LOADING - FIXED ONLOAD HANDLER -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="preload" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
    
    <!-- INSTANT FONT LOADING - SYSTEM FONTS FIRST -->
    <style>
        /* INSTANT SYSTEM FONTS - ZERO DELAY */
        body, .navbar-brand, .course-title, .preloader-brand {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }
    </style>
    
    <!-- LOAD CUSTOM FONTS IN BACKGROUND - FIXED ONLOAD HANDLER -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="preload" as="style" onload="if(this&&document.body){this.onload=null;this.rel='stylesheet';document.body.style.fontFamily='Poppins, -apple-system, BlinkMacSystemFont, sans-serif';}">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" rel="preload" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">

    <!-- BOLD IMPACTFUL FONT FOR MEDICAL COURSES HEADER -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&display=swap" rel="preload" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">

    <!-- FONT AWESOME - ASYNC LOAD - FIXED ONLOAD HANDLER -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="if(this){this.onload=null;this.rel='stylesheet';}">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></noscript>

    <link rel="stylesheet" href="{{ asset('css/courses-page.css') }}">
    <link rel="stylesheet" href="{{ asset('css/free-course-survey.css') }}">
</head>
<body>
    <!-- 🔥 SCROLL PROGRESS BAR -->
    <div class="scroll-progress" id="scrollProgress"></div>
    
    <!-- SAFE PRELOADER - WON'T BREAK ANYTHING -->
    @include('components.lightning-preloader')

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <span class="brand-pill-logo" aria-hidden="true">
                    <svg width="52" height="26" viewBox="0 0 52 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="MedCapsule">
                        <defs>
                            <linearGradient id="coursesPillBody" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#225edc"></stop>
                                <stop offset="45%" stop-color="#367bea"></stop>
                                <stop offset="100%" stop-color="#1a47cb"></stop>
                            </linearGradient>
                            <linearGradient id="coursesPillGloss" x1="0.1" y1="0" x2="0.9" y2="0">
                                <stop offset="0%" stop-color="rgba(255,255,255,0)"></stop>
                                <stop offset="30%" stop-color="rgba(255,255,255,0.6)"></stop>
                                <stop offset="70%" stop-color="rgba(255,255,255,0.5)"></stop>
                                <stop offset="100%" stop-color="rgba(255,255,255,0)"></stop>
                            </linearGradient>
                            <linearGradient id="coursesPillShadow" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgba(0,0,0,0)"></stop>
                                <stop offset="100%" stop-color="rgba(10,20,80,0.28)"></stop>
                            </linearGradient>
                            <filter id="coursesPillDrop" x="-8%" y="-15%" width="116%" height="150%">
                                <feDropShadow dx="0" dy="2.5" stdDeviation="2" flood-color="#1d4ed8" flood-opacity="0.38"></feDropShadow>
                            </filter>
                        </defs>
                        <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#coursesPillBody)" filter="url(#coursesPillDrop)"></rect>
                        <rect x="1" y="3" width="50" height="18" rx="9" fill="url(#coursesPillShadow)"></rect>
                        <rect x="4" y="4" width="44" height="7" rx="5" fill="url(#coursesPillGloss)" opacity="0.88"></rect>
                        <ellipse cx="10" cy="8" rx="3.5" ry="2" fill="rgba(255,255,255,0.6)"></ellipse>
                    </svg>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Sign Up</a>
                        </li>
                    @else
                        <li class="nav-item dropdown me-1">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                {{ $currentUserView['name'] ?? Auth::user()->name }}
                                @if($currentUserView['is_admin'] ?? Auth::user()->isAdmin())
                                    <span class="badge bg-warning ms-1">Admin</span>
                                @elseif($currentUserView['is_tutor'] ?? Auth::user()->isTutor())
                                    <span class="badge bg-info ms-1">Tutor</span>
                                @else
                                    <span class="badge bg-primary ms-1">Student</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="d-flex justify-content-center">
                                    <div class="streak-indicator" data-streak="{{ $currentUserView['study_streak_days'] ?? 0 }}">
                                        <div class="streak-circle">
                                            <svg viewBox="0 0 100 100">
                                                <circle class="streak-progress" cx="50" cy="50" r="45"></circle>
                                            </svg>
                                            <div class="streak-content">
                                                <div class="streak-value">{{ $currentUserView['study_streak_days'] ?? 0 }}</div>
                                                <div class="streak-label">Streak</div>
                                            </div>
                                            @if(($currentUserView['study_streak_days'] ?? 0) > 0)
                                                <div class="streak-fire">
                                                    <i class="fas fa-fire"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile') }}">
                                        <i class="fas fa-user-circle me-2"></i>My Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="https://t.me/Medcapsule1" target="_blank">
                                        <i class="fab fa-telegram-plane me-2"></i>Join Support
                                    </a>
                                </li>
                                @if($currentUserView['is_admin'] ?? Auth::user()->isAdmin())
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">
                                        <i class="fas fa-crown me-2 text-warning"></i>Admin Controls
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.index') }}">
                                            <i class="fas fa-clipboard-list me-2"></i>Assignment Management
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.create') }}">
                                            <i class="fas fa-plus me-2"></i>Create Assignment
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('topic-locking.index') }}">
                                            <i class="fas fa-lock me-2"></i>Topic Management
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('tutor.dashboard') }}">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Tutor Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.user-course') }}">
                                            <i class="fas fa-users-cog me-2"></i>Manage Access
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-topic.form') }}">
                                            <i class="fas fa-plus-circle me-2"></i>Add Topic
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-question.form') }}">
                                            <i class="fas fa-question-circle me-2"></i>Add Question
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('add-course.form') }}">
                                            <i class="fas fa-graduation-cap me-2"></i>Add Course
                                        </a>
                                    </li>
                                @endif
                                @if($currentUserView['is_tutor'] ?? Auth::user()->isTutor())
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('tutor.dashboard') }}">
                                            <i class="fas fa-chalkboard-teacher me-2"></i>Tutor Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assignments.index') }}">
                                            <i class="fas fa-clipboard-list me-2"></i>My Assignments
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('topic-locking.index') }}">
                                            <i class="fas fa-lock me-2"></i>Topic Management
                                        </a>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ secure_url(route('logout', [], false)) }}" class="dropdown-item p-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item w-100 text-danger logout-btn">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        <!-- Notification Bell -->
                        <li class="nav-item me-1">
                            @include('components.notification-bell')
                        </li>
                        
                        <li class="nav-item me-5">
                            @php
                                $profileImageUrl = $currentUserView['profile_image_url'] ?? Auth::user()->getProfileImageUrl();
                                $profileFallbackUrl = $currentUserView['profile_fallback_url'] ?? ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff');
                            @endphp
                            <a href="{{ route('profile') }}" class="profile-circle">
                                <img src="{{ $profileImageUrl }}"
                                     alt="Profile Picture"
                                     class="profile-image"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.src='{{ $profileFallbackUrl }}'">
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <!-- 🌟 ENHANCED MEDICAL ANIMATION ELEMENTS 🌟 -->

        <!-- Enhanced DNA Double Helix -->
        <div class="enhanced-dna-helix">
            <div class="dna-strand-left"></div>
            <div class="dna-strand-right"></div>
            <!-- DNA connecting bonds -->
            @for($i = 0; $i < 8; $i++)
                <div class="dna-bond" style="top: {{ $i * 15 + 10 }}px; transform: rotate({{ $i * 45 }}deg);"></div>
            @endfor
        </div>

        <!-- Floating Molecules (positioned away from title) -->
        <div class="molecule glucose">
            <div class="atom carbon" style="top: 10px; left: 15px;"></div>
            <div class="atom carbon" style="top: 5px; left: 35px;"></div>
            <div class="atom oxygen" style="top: 25px; left: 10px;"></div>
            <div class="atom oxygen" style="top: 20px; left: 40px;"></div>
            <div class="atom hydrogen" style="top: 15px; left: 5px;"></div>
            <div class="bond" style="top: 15px; left: 20px; width: 15px; transform: rotate(45deg);"></div>
        </div>

        <div class="molecule water">
            <div class="atom oxygen" style="top: 12px; left: 17px;"></div>
            <div class="atom hydrogen" style="top: 5px; left: 10px;"></div>
            <div class="atom hydrogen" style="top: 5px; left: 25px;"></div>
            <div class="bond" style="top: 8px; left: 12px; width: 8px; transform: rotate(-45deg);"></div>
            <div class="bond" style="top: 8px; left: 22px; width: 8px; transform: rotate(45deg);"></div>
        </div>

        <!-- Ultra-Compact Capsule-Shaped Pills -->
        <div class="ultra-capsule capsule-large">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-medium">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-small">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-variant1">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>
        <div class="ultra-capsule capsule-variant2">
            <div class="capsule-head"></div>
            <div class="capsule-body"></div>
        </div>

        <!-- 🧬 BIOLOGICAL CELL - Clean & Simple -->
        <div class="biological-cell">
            <!-- Cell Membrane -->
            <div class="cell-membrane"></div>

            <!-- Cell Interior -->
            <div class="cell-interior">
                <!-- Nucleus -->
                <div class="cell-nucleus"></div>

                <!-- Scattered Chromosomes -->
                <div class="chromosome chromosome-1"></div>
                <div class="chromosome chromosome-2"></div>
                <div class="chromosome chromosome-3"></div>
                <div class="chromosome chromosome-4"></div>
            </div>
        </div>

        <!-- 🔥 SINGLE RED HEARTBEAT WAVE - LIVE ECG MONITOR 🔥 -->
        <div class="heartbeat-wave heartbeat-wave-primary">
            <svg viewBox="0 0 200 60" class="heartbeat-svg">
                <defs>
                    <linearGradient id="heartbeatGradient1" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#ff0000;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#ff4444;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#ff0000;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <path d="M0,30 L30,30 L32,10 L34,50 L36,30 L50,30 L52,20 L54,40 L56,30 L200,30"
                      stroke="url(#heartbeatGradient1)" stroke-width="3" fill="none" stroke-linecap="round"
                      class="heartbeat-path"/>
            </svg>
        </div>

        <!-- 🦠 TINY CELL UNDER HEARTBEAT - MICRO ORGANISM 🌟 -->
        <div class="micro-cell">
            <!-- Tiny Cell Membrane -->
            <div class="micro-membrane"></div>

            <!-- Tiny Cell Interior -->
            <div class="micro-interior">
                <!-- Tiny Nucleus -->
                <div class="micro-nucleus"></div>

                <!-- Tiny Chromosomes -->
                <div class="micro-chromosome micro-chromosome-1"></div>
                <div class="micro-chromosome micro-chromosome-2"></div>
            </div>
        </div>

        <h1 class="hero-title">Pick Your Course</h1>
    </section>

    <div class="position-relative">
        <div class="semester-pills">
            <select class="semester-dropdown" id="semesterDropdown" onchange="filterCoursesByAcademicYear()" aria-label="Filter courses by academic year">
                <option value="">All Courses</option>
                @php
                    // Get unique academic years directly from courses table
                    $academicYears = $courses->pluck('academic_year')->filter()->unique()->sort();

                    // Debug: Show academic years in console
                    $debugYears = $courses->pluck('academic_year')->toArray();
                @endphp

                @if($academicYears->isNotEmpty())
                    <optgroup label="Academic Years">
                        @foreach($academicYears as $year)
                            @php
                                // Format the year to be more readable
                                $readableYear = ucwords(strtolower(str_replace('_', ' ', $year)));
                                $readableYear = str_replace(['Year', 'Semester'], ['Year', 'Semester'], $readableYear);
                            @endphp
                            <option value="{{ $year }}" {{ ($selectedYear ?? '') == $year ? 'selected' : '' }}>
                                {{ $readableYear }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            </select>

            {{-- Debug removed for production performance --}}
        </div>
            
            @guest
            @else
            @endguest
        </div>

        <!-- Scroll Arrows -->
        <button class="scroll-arrow scroll-left" onclick="scrollCourses('left')" aria-label="Scroll courses left" title="Scroll left">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        
        <div class="courses-grid">
{{-- ✅ N+1 FIXED: topics_count eager loaded in CourseService --}}
            @foreach ($courses as $course)
                <div class="course-card" 
                     style="--course-color: {{ $course->color }}; --course-title-color: {{ $course->title_color }}"
                     data-academic-year="{{ $course->academic_year }}"
                     data-course-id="{{ $course->id }}"
                     data-semester="{{ $course->semester }}">
                    @php
                        $optimizedImageUrl = $course->image_url;
                        // Apply same optimization as preload - updated dimensions
                        if (str_contains($optimizedImageUrl, 'unsplash.com')) {
                            $optimizedImageUrl = str_replace('?', '?w=465&h=670&fit=crop&auto=format,compress&q=75&fm=webp&', $optimizedImageUrl);
                        } elseif (str_contains($optimizedImageUrl, 'picsum.photos')) {
                            $optimizedImageUrl = str_replace('picsum.photos/', 'picsum.photos/465/670/', $optimizedImageUrl);
                        }
                    @endphp
                    <div class="course-image"
                         role="img"
                         aria-label="{{ $course->name }} course image"
                         style="background-image: url('{{ $optimizedImageUrl }}');"
                         data-bg-image="{{ $optimizedImageUrl }}"
                         data-course-name="{{ $course->name }}">
                        <div class="info-icon" role="button" tabindex="0" aria-label="Course information">
                            <i class="fas fa-info" aria-hidden="true"></i>
                            <div class="info-tooltip">
                                {{ $course->description }}
                            </div>
                        </div>
                        <div class="course-overlay">
                            <h3 class="course-title">{{ $course->name }}</h3>
                        </div>
                    </div>
                    <div class="course-info">
                        <div class="course-stats-row">
                            <span class="topics-badge">
                                <i class="fas fa-book-open me-1"></i>
                                {{ $course->topics_count }} Topics
                            </span>
                            <div class="enrolled-users-preview">
                                @foreach($course->enrolled_preview as $user)
                                    <div class="profile-circle-small" 
                                         @if($user['profile_picture_url'])
                                             style="background-image: url('{{ \App\Models\User::getProfileImageUrlFor((object)$user) }}'); background-size: cover; background-position: center; background-repeat: no-repeat;"
                                         @else
                                             style="background: linear-gradient(135deg, #007bff, #00d4ff);"
                                         @endif
                                         title="{{ $user['name'] }}">
                                        @if(!$user['profile_picture_url'])
                                            {{ $user['initial'] }}
                                        @endif
                                    </div>
                                @endforeach
                                @if($course->has_more)
                                    <div class="profile-circle-small more" title="More enrolled users">
                                        +{{ $course->enrolled_users_count - 3 }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="progress-container" role="progressbar" aria-valuenow="{{ $course->completion_percentage }}" aria-valuemin="0" aria-valuemax="100" aria-label="Course completion progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $course->completion_percentage }}%">
                                    <span class="progress-percentage">{{ number_format($course->completion_percentage, 0) }}%</span>
                                </div>
                            </div>
                        </div>
                        @if (auth()->check())
                            @if ($course->requires_survey_unlock)
                                <button
                                    type="button"
                                    class="action-btn enroll free-course-survey-trigger"
                                    data-course-id="{{ $course->id }}"
                                    data-course-name="{{ $course->name }}"
                                    data-course-url="{{ route('topics.forCourse', $course->id, false) }}"
                                    aria-label="Unlock {{ $course->name }}"
                                    style="background: #ffffff; border-color: #e5e7eb; color: #6b7280;"
                                >
                                    <i class="fas fa-lock"></i>
                                    <span>Unlock Course</span>
                                </button>
                            @elseif ($course->is_preview_access)
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn continue" aria-label="Continue preview of {{ $course->name }}">
                                    <i class="fas fa-eye"></i>
                                    <span>Preview {{ $course->preview_topics_count }} Topics</span>
                                </a>
                            @elseif ($course->is_enrolled)
                                <!-- User is enrolled - show continue button -->
                                <a href="{{ route('topics.forCourse', $course->id) }}" class="action-btn continue" aria-label="Continue learning {{ $course->name }}">
                                    <i class="fas fa-play"></i>
                                    Continue Learning
                                </a>
                            @elseif ($course->isPreviewAccess() && !$course->is_enrolled)
                                <button type="button"
                                        class="action-btn enroll free ajax-enroll-btn"
                                        data-course-id="{{ $course->id }}"
                                        data-enroll-url="{{ route('courses.enroll', $course->id) }}"
                                        data-topics-url="{{ route('topics.forCourse', $course->id) }}"
                                        aria-label="Start preview of {{ $course->name }}">
                                    <i class="fas fa-eye"></i>
                                    <span>Start Preview (Free)</span>
                                </button>
                            @elseif ($course->isFreeAccess())
                                <!-- FIXED: Free course - show "Start Learning" button (will auto-enroll) -->
                                <button type="button"
                                        class="action-btn enroll free ajax-enroll-btn"
                                        data-course-id="{{ $course->id }}"
                                        data-enroll-url="{{ route('courses.enroll', $course->id) }}"
                                        data-topics-url="{{ route('topics.forCourse', $course->id) }}"
                                        aria-label="Start learning {{ $course->name }} (Free course)">
                                    <i class="fas fa-play"></i>
                                    <span>Start Learning (Free)</span>
                                </button>
                            @elseif ($course->has_pending_payment)
                                <!-- User has pending payment - show pending button -->
                                <button type="button" class="action-btn enroll" id="paymentBtn_{{ $course->id }}" disabled style="background: #fff7ed; border-color: #fed7aa; color: #ea580c; cursor: not-allowed;" aria-label="Payment pending for {{ $course->name }}">
                                    <i class="fas fa-clock" style="color: #ea580c;"></i>
                                    <span style="color: #ea580c; font-weight: bold;">Payment Pending</span>
                                </button>
                            @else
                                <!-- User is not enrolled and no pending payment - show payment button -->
                                <button type="button" class="action-btn enroll show-payment-modal" id="paymentBtn_{{ $course->id }}" data-course-id="{{ $course->id }}" aria-label="Unlock {{ $course->name }} course">
                                    <i class="fas fa-unlock"></i>
                                    <span>Unlock</span>
                                </button>
                            @endif
                        @else
                            @if ($course->isPreviewAccess())
                                <a href="{{ route('login') }}" class="action-btn enroll free" aria-label="Sign in to start preview of {{ $course->name }}">
                                    <i class="fas fa-eye"></i>
                                    Sign in to Start Preview
                                </a>
                            @elseif ($course->isFreeAccess())
                                <a href="{{ route('login') }}" class="action-btn enroll free" aria-label="Sign in to start {{ $course->name }} (Free course)">
                                    <i class="fas fa-play"></i>
                                    Sign in to Start (Free)
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="action-btn enroll" aria-label="Sign in to access {{ $course->name }}">
                                    <i class="fas fa-lock"></i>
                                    Sign in to Access
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <button class="scroll-arrow scroll-right" onclick="scrollCourses('right')" aria-label="Scroll courses right" title="Scroll right">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>

    <div class="backdrop" id="backdrop" onclick="hideUnlockInstructions()"></div>
    <div class="unlock-alert" id="unlockAlert">
        <h3>Course Access Required</h3>
        <p>This course requires administrator approval. Please contact your institution's administrator to request access to this course.</p>
        <div class="contact-info">
            <div class="email mt-2">
                <i class="fas fa-envelope"></i>
                <a href="mailto:admin@medcapsule.com">admin@medcapsule.com</a>
            </div>
        </div>
        <p class="mt-3 small text-muted">Once approved, you will receive an email notification and can start learning immediately.</p>
        <button class="btn btn-primary mt-3" onclick="hideUnlockInstructions()">Close</button>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal content will be loaded dynamically -->
                <div class="modal-body text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading payment options...</p>
                </div>
            </div>
        </div>
    </div>

    @auth
        @include('components.free-course-survey-lock')
    @endauth

    <!-- Milestone Progress - BACKEND OPTIMIZED -->
    @php
        // RUTHLESS: Cache calculations to avoid repeated database queries
        $totalCorrectAnswers = $currentUserView['total_correct_answers'] ?? (Auth::check() ? Auth::user()->getTotalCorrectAnswers() : 0);
        $progressPercentage = min(($totalCorrectAnswers % 100), 100);
    @endphp
    
    <div class="milestone-container">
        <div class="milestone-stats">
            <span class="correct-count" id="correctCount">{{ $totalCorrectAnswers }}</span>
            <span class="separator">/</span>
            <span class="goal-count" id="goalCount">100</span>
            <span class="questions-text">questions solved</span>
        </div>
        <div class="milestone-progress">
            <div class="liquid-progress">
                <div class="liquid-fill" id="progressFill" style="width: {{ $progressPercentage }}%">
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                    <div class="liquid-wave"></div>
                </div>
            </div>
            <div class="milestone-marks">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="milestone-mark {{ $totalCorrectAnswers >= ($i * 20) ? 'active' : '' }}" 
                         style="left: {{ $i * 20 }}%" 
                         id="mark-{{ $i * 20 }}">
                        <i class="fas fa-check"></i>
                    </div>
                @endfor
            </div>
        </div>
    </div>



    <script>
        window.CoursesPageConfig = {
            correctAnswersInitial: {{ $currentUserView['total_correct_answers'] ?? (auth()->check() ? auth()->user()->getTotalCorrectAnswers() : 0) }},
            profileRoute: @json(route('profile')),
            user: @json(auth()->user() ? ['id' => auth()->id(), 'name' => auth()->user()->name] : null),
            authUserId: @json(auth()->id()),
            freeCourseSurvey: {
                enabled: @json(auth()->check() && !($currentUserView['free_courses_unlocked'] ?? false)),
                draftUrl: @json(route('free-course-survey.draft')),
                submitUrl: @json(route('free-course-survey.submit')),
                redirectTo: @json(request()->query('redirect_to')),
                openImmediately: @json(request()->boolean('survey')),
                draft: @json($freeCourseSurveyDraftPayload ?? null),
                successMessage: @json(session('free_course_unlock_success')),
                description: @json(config('free_course_survey.description')),
            }
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/courses-page.js') }}?v={{ filemtime(public_path('js/courses-page.js')) }}" defer></script>
    <script src="{{ asset('js/free-course-survey.js') }}?v={{ filemtime(public_path('js/free-course-survey.js')) }}" defer></script>

    <!-- Floating Contact Button -->
    <div class="floating-contact">
        <div class="contact-options" id="contactOptions">
            <a href="https://t.me/Medcapsule1" target="_blank" class="contact-option telegram">
                <i class="fab fa-telegram"></i>
                <span>Join Telegram</span>
            </a>
            <a href="https://wa.me/YOUR_WHATSAPP_NUMBER" target="_blank" class="contact-option whatsapp">
                <i class="fab fa-whatsapp"></i>
                <span>Join WhatsApp</span>
            </a>
        </div>
            </div>

</body>
</html>
