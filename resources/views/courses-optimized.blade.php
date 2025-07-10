<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule</title>
    
    <!-- PERFORMANCE: Critical CSS inline, rest async -->
    <style>
        /* CRITICAL PATH CSS - Inline for instant rendering */
        html { font-size: 12.8px; }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #f8f9fa; 
            color: #1a1a1a; 
            margin: 0; 
            padding: 0; 
            min-height: 100vh; 
            overflow-x: hidden; 
            line-height: 1.6;
        }
        .loading-skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .course-card { will-change: transform; transform: translateZ(0); }
        .fade-in { opacity: 0; transform: translateY(20px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .fade-in.visible { opacity: 1; transform: translateY(0); }
    </style>

    <!-- PERFORMANCE: Load fonts with display=swap for instant text -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Load Bootstrap async -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Preload critical JavaScript -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- PERFORMANCE: Defer non-critical CSS -->
    <link rel="stylesheet" href="{{ asset('css/optimized-courses.css') }}" media="print" onload="this.media='all'">
</head>
<body>
    <!-- Navbar with instant appearance -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">MedCapsule</a>
            
            <!-- PERFORMANCE: User info with lazy loading -->
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <div class="progress-container d-none d-md-block me-4" id="user-progress">
                    <!-- Progress loaded via AJAX -->
                </div>
                
                <!-- Profile with instant skeleton -->
                <div class="profile-placeholder" id="profile-container">
                    <div class="loading-skeleton" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="hero-title fade-in">Choose Your Course</h1>
            <p class="hero-subtitle fade-in">Select a course to begin your medical education journey</p>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <div class="container-fluid px-4">
            
            <!-- PERFORMANCE: Instant filter dropdown -->
            <div class="filter-section mb-4 fade-in">
                <select id="semesterFilter" class="form-select semester-filter">
                    <option value="">All Semesters</option>
                    <option value="Year 1- First semester">Year 1 - First Semester</option>
                    <option value="Year 1- Second semester">Year 1 - Second Semester</option>
                    <option value="Year 2- First semester">Year 2 - First Semester</option>
                    <option value="Year 2- Second semester">Year 2 - Second Semester</option>
                </select>
            </div>

            <!-- PERFORMANCE: Courses grid with instant loading -->
            <div class="courses-wrapper">
                <div class="courses-grid" id="courses-grid">
                    <!-- Initial skeleton loading -->
                    @for($i = 0; $i < 6; $i++)
                        <div class="course-skeleton">
                            <div class="loading-skeleton" style="height: 450px; border-radius: 12px;"></div>
                            <div class="p-3">
                                <div class="loading-skeleton mb-2" style="height: 24px; width: 80%;"></div>
                                <div class="loading-skeleton mb-2" style="height: 16px; width: 60%;"></div>
                                <div class="loading-skeleton" style="height: 40px; width: 100%;"></div>
                            </div>
                        </div>
                    @endfor
                </div>
                
                <!-- PERFORMANCE: Scroll arrows with instant response -->
                <button class="scroll-arrow scroll-left" onclick="scrollCourses('left')" aria-label="Scroll left">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="scroll-arrow scroll-right" onclick="scrollCourses('right')" aria-label="Scroll right">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE: Modals loaded on demand -->
    <div id="modal-container"></div>

    <!-- PERFORMANCE: Load JavaScript after critical rendering -->
    <script>
        // INSTANT PERFORMANCE OPTIMIZATION
        window.courseData = @json($courses ?? []);
        window.userProgress = @json($userProgress ?? []);
        window.csrfToken = '{{ csrf_token() }}';
        
        // PERFORMANCE: Client-side caching
        const CourseCache = {
            data: new Map(),
            set(key, value, ttl = 300000) { // 5 minutes
                this.data.set(key, { value, expires: Date.now() + ttl });
            },
            get(key) {
                const item = this.data.get(key);
                if (!item || Date.now() > item.expires) {
                    this.data.delete(key);
                    return null;
                }
                return item.value;
            },
            clear() { this.data.clear(); }
        };

        // PERFORMANCE: Instant course rendering
        function renderCourses(courses, semester = '') {
            const grid = document.getElementById('courses-grid');
            
            // PERFORMANCE: Use DocumentFragment for batch DOM updates
            const fragment = document.createDocumentFragment();
            
            const filteredCourses = semester ? 
                courses.filter(course => course.semester === semester) : 
                courses;

            filteredCourses.forEach((course, index) => {
                const courseCard = createCourseCard(course);
                courseCard.style.setProperty('--animation-delay', `${index * 50}ms`);
                fragment.appendChild(courseCard);
            });

            // PERFORMANCE: Single reflow
            grid.innerHTML = '';
            grid.appendChild(fragment);
            
            // PERFORMANCE: Trigger animations with RAF
            requestAnimationFrame(() => {
                grid.querySelectorAll('.course-card').forEach(card => {
                    card.classList.add('visible');
                });
            });
        }

        // PERFORMANCE: Optimized course card creation
        function createCourseCard(course) {
            const card = document.createElement('div');
            card.className = 'course-card fade-in';
            card.setAttribute('data-semester', course.semester);
            card.style.animationDelay = 'var(--animation-delay)';
            
            // PERFORMANCE: Use template strings for better performance
            card.innerHTML = `
                <div class="course-image" style="background-image: url('${course.image_url}');">
                    <div class="info-icon">
                        <i class="fas fa-info"></i>
                        <div class="info-tooltip">${course.description || 'Course information'}</div>
                    </div>
                    <div class="course-overlay">
                        <h3 class="course-title">${course.name}</h3>
                    </div>
                </div>
                <div class="course-info">
                    <div class="course-stats">
                        <div class="stat-item">
                            <i class="fas fa-book"></i>
                            <span>${course.topics_count || 0} Topics</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span>${course.enrolled_count || 0} Students</span>
                        </div>
                    </div>
                    ${course.progress > 0 ? `
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${course.progress}%;"></div>
                            </div>
                        </div>
                    ` : ''}
                    <button class="action-btn ${course.enrolled ? 'continue' : 'enroll'}" 
                            onclick="handleCourseAction(${course.id}, '${course.enrolled ? 'continue' : 'enroll'}')">
                        <i class="fas fa-${course.enrolled ? 'play' : 'plus'}"></i>
                        ${course.enrolled ? 'Continue Learning' : (course.price > 0 ? `Enroll - ${course.price} EGP` : 'Enroll Free')}
                    </button>
                </div>
            `;
            
            return card;
        }

        // PERFORMANCE: Instant filter with debouncing
        let filterTimeout;
        function handleSemesterFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                const semester = document.getElementById('semesterFilter').value;
                
                // PERFORMANCE: Check cache first
                const cacheKey = `courses_${semester}`;
                let filteredData = CourseCache.get(cacheKey);
                
                if (!filteredData) {
                    filteredData = semester ? 
                        window.courseData.filter(course => course.semester === semester) :
                        window.courseData;
                    CourseCache.set(cacheKey, filteredData);
                }
                
                renderCourses(filteredData);
                updateScrollArrows();
            }, 100); // Instant debounce
        }

        // PERFORMANCE: Smooth scrolling with requestAnimationFrame
        function scrollCourses(direction) {
            const container = document.getElementById('courses-grid');
            const cardWidth = 360;
            const gap = 24;
            const scrollAmount = cardWidth + gap;
            
            const targetScroll = direction === 'left' 
                ? Math.max(0, container.scrollLeft - scrollAmount)
                : Math.min(container.scrollWidth - container.clientWidth, container.scrollLeft + scrollAmount);
            
            // PERFORMANCE: Smooth scroll with easing
            const startScroll = container.scrollLeft;
            const distance = targetScroll - startScroll;
            const duration = 300; // ms
            let startTime;
            
            function animateScroll(currentTime) {
                if (!startTime) startTime = currentTime;
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                
                container.scrollLeft = startScroll + (distance * easeOutCubic);
                
                if (progress < 1) {
                    requestAnimationFrame(animateScroll);
                }
            }
            
            requestAnimationFrame(animateScroll);
        }

        // PERFORMANCE: Throttled scroll arrow updates
        let scrollTimeout;
        function updateScrollArrows() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const container = document.getElementById('courses-grid');
                const leftArrow = document.querySelector('.scroll-left');
                const rightArrow = document.querySelector('.scroll-right');
                
                if (leftArrow && rightArrow) {
                    leftArrow.style.opacity = container.scrollLeft > 0 ? '0.8' : '0';
                    rightArrow.style.opacity = 
                        (container.scrollLeft + container.clientWidth) < container.scrollWidth ? '0.8' : '0';
                }
            }, 16); // ~60fps
        }

        // PERFORMANCE: Course action with instant feedback
        function handleCourseAction(courseId, action) {
            const button = event.target.closest('.action-btn');
            const originalContent = button.innerHTML;
            
            // PERFORMANCE: Instant feedback
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            
            if (action === 'continue') {
                // Instant navigation
                window.location.href = `/topics/${courseId}`;
            } else {
                // PERFORMANCE: Load payment modal on demand
                loadPaymentModal(courseId).then(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }).catch(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            }
        }

        // PERFORMANCE: Lazy load payment modal
        async function loadPaymentModal(courseId) {
            const cacheKey = `payment_modal_${courseId}`;
            let modalHtml = CourseCache.get(cacheKey);
            
            if (!modalHtml) {
                const response = await fetch(`/payments/${courseId}/options`, {
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    modalHtml = data.html;
                    CourseCache.set(cacheKey, modalHtml, 60000); // 1 minute cache
                }
            }
            
            // PERFORMANCE: Insert modal into container
            const modalContainer = document.getElementById('modal-container');
            modalContainer.innerHTML = modalHtml;
            
            // Show modal
            const modal = new bootstrap.Modal(modalContainer.querySelector('.modal'));
            modal.show();
        }

        // PERFORMANCE: Async user progress loading
        async function loadUserProgress() {
            try {
                const response = await fetch('/profile/progress', {
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const progress = await response.json();
                
                // Update progress display
                const progressContainer = document.getElementById('user-progress');
                progressContainer.innerHTML = `
                    <div class="milestone-container">
                        <div class="milestone-text">
                            <span id="correctCount">${progress.correct_answers}</span> / 
                            <span id="goalCount">${progress.goal}</span>
                        </div>
                        <div class="progress-bar-liquid">
                            <div class="liquid-fill" style="width: ${progress.percentage}%;"></div>
                        </div>
                    </div>
                `;
                
                // Update profile
                const profileContainer = document.getElementById('profile-container');
                profileContainer.innerHTML = `
                    <a href="/profile" class="profile-circle ${progress.profile_picture ? '' : 'no-image'}" 
                       ${progress.profile_picture ? `style="background-image: url('${progress.profile_picture}')"` : ''}>
                        ${!progress.profile_picture ? progress.initials : ''}
                    </a>
                `;
            } catch (error) {
                console.warn('Failed to load user progress:', error);
            }
        }

        // PERFORMANCE: Initialize with Intersection Observer
        function initializeAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
        }

        // PERFORMANCE: DOM ready optimization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

        function init() {
            // PERFORMANCE: Initialize in order of priority
            renderCourses(window.courseData);
            initializeAnimations();
            
            // Setup event listeners
            document.getElementById('semesterFilter').addEventListener('change', handleSemesterFilter);
            document.getElementById('courses-grid').addEventListener('scroll', updateScrollArrows);
            
            // PERFORMANCE: Load non-critical data after main content
            requestIdleCallback(() => {
                loadUserProgress();
                updateScrollArrows();
            });
        }

        // PERFORMANCE: Preload critical routes
        window.addEventListener('load', () => {
            requestIdleCallback(() => {
                // Preload likely next pages
                const preloadUrls = ['/profile', '/topics', '/review'];
                preloadUrls.forEach(url => {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    document.head.appendChild(link);
                });
            });
        });
    </script>

    <!-- PERFORMANCE: Load heavy scripts after content -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- PERFORMANCE: Load analytics last -->
    <script>
        // Analytics and tracking code here
        window.addEventListener('load', () => {
            // Initialize analytics after everything else loads
        });
    </script>
</body>
</html>