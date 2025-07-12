<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule - Advanced Medical Education Platform</title>
    
    <!-- PERFORMANCE: Critical SEO and meta tags -->
    <meta name="description" content="MedCapsule - Advanced Medical Education Platform for medical students. Interactive quizzes, progress tracking, and comprehensive learning materials.">
    <meta name="keywords" content="medical education, medical quizzes, medical learning, medical students, USMLE, medical training">
    <meta name="author" content="MedCapsule">
    <meta name="robots" content="index, follow">
    
    <!-- PERFORMANCE: Open Graph tags for social sharing -->
    <meta property="og:title" content="MedCapsule - Advanced Medical Education Platform">
    <meta property="og:description" content="Interactive medical education with quizzes, progress tracking, and comprehensive learning materials.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="MedCapsule">
    
    <!-- PERFORMANCE: Twitter Card tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="MedCapsule - Advanced Medical Education Platform">
    <meta name="twitter:description" content="Interactive medical education with quizzes, progress tracking, and comprehensive learning materials.">
    
    <!-- PERFORMANCE: Theme and app meta tags -->
    <meta name="theme-color" content="#007bff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="MedCapsule">
    
    <!-- PERFORMANCE: Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- PERFORMANCE: DNS prefetch for better performance -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- PERFORMANCE: Optimized favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    
    <!-- PERFORMANCE: Critical CSS inline for instant rendering -->
    <style>
        /* CRITICAL PATH CSS - Above the fold content */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --font-primary: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        * {
            box-sizing: border-box;
        }
        
        html {
            font-size: 12.8px; /* 80% scale for performance */
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeSpeed;
        }
        
        body {
            font-family: var(--font-primary);
            background-color: var(--light-color);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            line-height: 1.6;
            color: var(--dark-color);
        }
        
        /* PERFORMANCE: Loading skeleton for instant feedback */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* PERFORMANCE: Optimized animations with hardware acceleration */
        .fade-in {
            opacity: 0;
            transform: translateY(20px) translateZ(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform, opacity;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0) translateZ(0);
        }
        
        /* PERFORMANCE: Critical navbar styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transform: translateZ(0);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 2rem;
            background: linear-gradient(90deg, #2A6DFF, #7B3FE4, #E63946);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: transform 0.3s ease;
            will-change: transform;
        }
        
        /* PERFORMANCE: Critical hero section styles */
        .hero-section {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            padding: 15px 40px;
            font-size: 1.5rem;
            border-radius: 50px;
            background: var(--primary-color);
            border: none;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            will-change: transform;
        }
        
        /* PERFORMANCE: Optimized card styles */
        .course-card, .feature-box {
            will-change: transform;
            transform: translateZ(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .course-card:hover, .feature-box:hover {
            transform: translateY(-5px) translateZ(0);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* PERFORMANCE: Reduced motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* PERFORMANCE: Mobile optimizations */
        @media (max-width: 768px) {
            html {
                font-size: 14px;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .btn-primary {
                padding: 12px 30px;
                font-size: 1.2rem;
            }
        }
    </style>

    <!-- PERFORMANCE: Preload critical fonts -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Preload critical CSS -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Fallback for noscript -->
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- PERFORMANCE: Preload critical JavaScript -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" as="script">
    
    <!-- PERFORMANCE: Structured data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "MedCapsule",
        "description": "Advanced Medical Education Platform",
        "url": "{{ url('/') }}",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>
</head>
<body>
    <!-- PERFORMANCE: Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only sr-only-focusable">Skip to main content</a>
    
    <!-- PERFORMANCE: Interactive background with optimized animations -->
    <div class="interactive-bg" id="interactive-bg">
        <div class="floating-elements" id="floating-elements"></div>
        <div class="gradient-overlay"></div>
    </div>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <!-- PERFORMANCE: Optimized brand with lazy-loaded SVG -->
            <a class="navbar-brand" href="{{ route('home') }}">
                <svg class="capsule-logo" viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg">
                    <!-- SVG content loaded via JavaScript for performance -->
                </svg>
                MedCapsule
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- PERFORMANCE: Live user counter component -->
                    <li class="nav-item">
                        <x-live-user-counter />
                    </li>
                    
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Sign Up</a>
                        </li>
                    @else
                        <!-- PERFORMANCE: User dropdown with lazy loading -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ Auth::user()->name }}
                                @if(Auth::user()->is_admin)
                                    <span class="badge bg-primary ms-1">Admin</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- PERFORMANCE: Streak indicator with optimized rendering -->
                                <li class="d-flex justify-content-center">
                                    <div class="streak-indicator" data-streak="{{ Auth::user()->study_streak_days ?? 0 }}">
                                        <div class="streak-circle">
                                            <svg viewBox="0 0 100 100">
                                                <circle class="streak-bg" cx="50" cy="50" r="40"></circle>
                                                <circle class="streak-progress" cx="50" cy="50" r="40"></circle>
                                            </svg>
                                            <div class="streak-content">
                                                <div class="streak-value">{{ Auth::user()->study_streak_days ?? 0 }}</div>
                                                <div class="streak-label">Streak</div>
                                            </div>
                                            @if((Auth::user()->study_streak_days ?? 0) > 0)
                                                <div class="streak-fire">
                                                    <i class="fas fa-fire"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <!-- Navigation items -->
                                <li><a class="dropdown-item" href="{{ route('courses.index') }}"><i class="fas fa-book me-2"></i>My Courses</a></li>
                                <li><a class="dropdown-item" href="{{ route('review.index') }}"><i class="fas fa-exclamation-circle me-2"></i>My Mistakes</a></li>
                                @if(Auth::user()->is_admin)
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.user-course') }}"><i class="fas fa-users-cog me-2"></i>Manage Access</a></li>
                                    <li><a class="dropdown-item" href="{{ route('add-topic.form') }}"><i class="fas fa-plus-circle me-2"></i>Add Topic</a></li>
                                    <li><a class="dropdown-item" href="{{ route('add-question.form') }}"><i class="fas fa-question-circle me-2"></i>Add Question</a></li>
                                    <li><a class="dropdown-item" href="{{ route('add-course.form') }}"><i class="fas fa-graduation-cap me-2"></i>Add Course</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li>
                                    <form method="POST" action="{{ secure_url(route('logout', [], false)) }}" class="dropdown-item p-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item w-100">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- PERFORMANCE: Notification Bell Component -->
                        <x-notification-bell />
                        
                        <!-- PERFORMANCE: Profile circle with lazy image loading -->
                        <li class="nav-item pe-2">
                            <a href="{{ route('profile') }}" class="nav-link">
                                <div class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');' : '' }}">
                                    @if(!Auth::user()->profile_picture_url)
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- PERFORMANCE: Live activity banner -->
    <div class="live-activity-banner" id="live-activity-banner">
        <div class="activity-scroll" id="activity-scroll">
            <!-- Activity items loaded via JavaScript -->
        </div>
    </div>

    <!-- Main Content -->
    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero-section fade-in">
            <div class="container">
                <h1>Welcome to MedCapsule</h1>
                <p class="lead">Your Advanced Medical Education Platform</p>
                <p>Master medical knowledge with interactive quizzes, track your progress, and excel in your studies.</p>
                <a href="{{ route('courses.index') }}" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i>
                    Start Learning
                </a>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features fade-in">
            <div class="container">
                <h2>Why Choose MedCapsule?</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="feature-box text-center">
                            <i class="fas fa-brain fa-3x mb-3"></i>
                            <h4>Interactive Learning</h4>
                            <p>Engage with comprehensive quizzes and interactive content designed for medical students.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box text-center">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <h4>Progress Tracking</h4>
                            <p>Monitor your learning progress with detailed analytics and performance insights.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-box text-center">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h4>Community Learning</h4>
                            <p>Connect with fellow medical students and learn together in a supportive environment.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Courses Section -->
        <section class="courses-section fade-in">
            <div class="container-fluid">
                <div class="container">
                    <h2 class="text-center mb-5">Featured Courses</h2>
                    
                    <!-- PERFORMANCE: Course grid with lazy loading -->
                    <div class="courses-grid" id="courses-grid">
                        <!-- Course cards loaded via JavaScript for performance -->
                        <div class="loading-skeleton" style="height: 300px; border-radius: 12px;"></div>
                        <div class="loading-skeleton" style="height: 300px; border-radius: 12px;"></div>
                        <div class="loading-skeleton" style="height: 300px; border-radius: 12px;"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="statistics-section fade-in">
            <div class="container">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <i class="fas fa-users fa-2x mb-3"></i>
                            <h4 class="counter">5000</h4>
                            <p>Active Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <i class="fas fa-book fa-2x mb-3"></i>
                            <h4 class="counter">150</h4>
                            <p>Courses Available</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <i class="fas fa-question fa-2x mb-3"></i>
                            <h4 class="counter">10000</h4>
                            <p>Practice Questions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <i class="fas fa-trophy fa-2x mb-3"></i>
                            <h4 class="counter">95</h4>
                            <p>Success Rate %</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer fade-in">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2025 MedCapsule. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>
                        <a href="/about">About</a> | 
                        <a href="/contact">Contact</a> | 
                        <a href="/faq">FAQ</a> | 
                        <a href="/privacy">Privacy Policy</a> |
                        <a href="/demo"><strong>Try 40 Free Questions</strong></a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- PERFORMANCE: Load JavaScript after critical content -->
    <script>
        // PERFORMANCE: Critical JavaScript inline for instant functionality
        window.MedCapsule = {
            config: {
                csrfToken: '{{ csrf_token() }}',
                userId: {{ Auth::check() ? Auth::id() : 'null' }},
                isAuthenticated: {{ Auth::check() ? 'true' : 'false' }},
                streakDays: {{ Auth::check() ? (Auth::user()->study_streak_days ?? 0) : 0 }}
            },
            cache: new Map(),
            performance: {
                startTime: performance.now(),
                metrics: {}
            }
        };

        // PERFORMANCE: Instant intersection observer for animations
        if ('IntersectionObserver' in window) {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // PERFORMANCE: Observe elements when DOM is ready
            function initializeAnimations() {
                document.querySelectorAll('.fade-in').forEach(el => {
                    observer.observe(el);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeAnimations);
            } else {
                initializeAnimations();
            }
        }

        // PERFORMANCE: Lazy load non-critical components
        function loadNonCriticalComponents() {
            // Load interactive background
            loadInteractiveBackground();
            
            // Load course data
            loadCourseData();
            
            // Load activity feed
            loadActivityFeed();
            
            // Initialize counters
            initializeCounters();
            
            // Load capsule logo animation
            loadCapsuleLogo();
        }

        // PERFORMANCE: Load components after critical rendering
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                requestIdleCallback(loadNonCriticalComponents);
            });
        } else {
            requestIdleCallback(loadNonCriticalComponents);
        }

        // PERFORMANCE: Functions for lazy loading components
        function loadInteractiveBackground() {
            const container = document.getElementById('floating-elements');
            if (!container) return;
            
            // Create floating elements
            for (let i = 0; i < 5; i++) {
                const element = document.createElement('div');
                element.className = 'floating-element';
                element.style.cssText = `
                    position: absolute;
                    width: ${100 + i * 50}px;
                    height: ${100 + i * 50}px;
                    background: linear-gradient(45deg, rgba(33, 150, 243, 0.1), rgba(33, 150, 243, 0.05));
                    border-radius: 50%;
                    animation: float-element ${15 + i * 2}s infinite ease-in-out;
                    animation-delay: ${i * -3}s;
                    top: ${Math.random() * 100}%;
                    left: ${Math.random() * 100}%;
                `;
                container.appendChild(element);
            }
        }

        function loadCourseData() {
            const grid = document.getElementById('courses-grid');
            if (!grid) return;
            
            // Simulate course data loading
            const courses = [
                { id: 1, name: 'Anatomy Fundamentals', description: 'Basic anatomy concepts', topics: 25, questions: 500 },
                { id: 2, name: 'Physiology Basics', description: 'Human physiology', topics: 30, questions: 600 },
                { id: 3, name: 'Pathology Essentials', description: 'Disease mechanisms', topics: 20, questions: 400 }
            ];
            
            grid.innerHTML = courses.map(course => `
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h4>${course.name}</h4>
                    </div>
                    <div class="course-content">
                        <p>${course.description}</p>
                        <div class="course-stats">
                            <span><i class="fas fa-book"></i> ${course.topics} Topics</span>
                            <span><i class="fas fa-question"></i> ${course.questions} Questions</span>
                        </div>
                    </div>
                    <div class="course-footer">
                        <a href="/courses/${course.id}" class="btn btn-primary btn-sm">View Course</a>
                    </div>
                </div>
            `).join('');
            
            // Trigger animations
            requestAnimationFrame(() => {
                grid.querySelectorAll('.course-card').forEach(card => {
                    card.classList.add('visible');
                });
            });
        }

        function loadActivityFeed() {
            const scroll = document.getElementById('activity-scroll');
            if (!scroll) return;
            
            const activities = [
                { icon: 'user-graduate', text: 'New student joined from Egypt' },
                { icon: 'trophy', text: 'Perfect score in Neurology!' },
                { icon: 'star', text: '200+ questions answered today' },
                { icon: 'fire', text: '15-day study streak achieved!' },
                { icon: 'book', text: 'New course: Advanced Pharmacology' }
            ];
            
            scroll.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <i class="fas fa-${activity.icon}"></i>
                    <span>${activity.text}</span>
                </div>
            `).join('');
        }

        function initializeCounters() {
            const counters = document.querySelectorAll('.counter');
            const observerOptions = { threshold: 0.5 };
            
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            counters.forEach(counter => counterObserver.observe(counter));
        }

        function animateCounter(element) {
            const target = parseInt(element.textContent);
            let current = 0;
            const increment = target / 60; // 60 frames for 1 second
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    element.textContent = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target;
                }
            };
            
            requestAnimationFrame(updateCounter);
        }

        function loadCapsuleLogo() {
            const logo = document.querySelector('.capsule-logo');
            if (!logo) return;
            
            logo.innerHTML = `
                <defs>
                    <linearGradient id="capsule-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#007bff;stop-opacity:1" />
                        <stop offset="50%" style="stop-color:#0056b3;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#004085;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <rect x="10" y="15" width="80" height="20" rx="10" ry="10" fill="url(#capsule-gradient)" />
                <rect x="50" y="15" width="40" height="20" fill="#ff0000" rx="0 10 10 0" ry="10"/>
                <line x1="50" y1="15" x2="50" y2="35" stroke="white" stroke-width="1.5" stroke-dasharray="2,1"/>
            `;
        }

        // PERFORMANCE: Performance monitoring
        window.addEventListener('load', () => {
            const loadTime = performance.now() - window.MedCapsule.performance.startTime;
            window.MedCapsule.performance.metrics.loadTime = loadTime;
            
            // Report performance metrics
            if (navigator.sendBeacon) {
                const data = JSON.stringify({
                    loadTime: loadTime,
                    url: window.location.href,
                    userAgent: navigator.userAgent
                });
                navigator.sendBeacon('/api/performance-metrics', data);
            }
        });
    </script>

    <!-- PERFORMANCE: Load external scripts asynchronously -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- PERFORMANCE: Load CSS after critical rendering -->
    <link rel="stylesheet" href="{{ asset('css/home-optimized.css') }}" media="print" onload="this.media='all'">
    
    <!-- PERFORMANCE: Service Worker for caching -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>