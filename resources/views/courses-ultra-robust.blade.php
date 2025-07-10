<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Explore MedCapsule courses for medical education">
    <title>Medical Courses - MedCapsule</title>
    
    <!-- SECURITY: Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self';">
    
    <!-- PERFORMANCE: Critical CSS - Inline for instant rendering -->
    <style>
        /* CRITICAL PATH CSS ONLY */
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
        .loading-skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); 
            background-size: 200% 100%; 
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        @keyframes loading { 
            0% { background-position: 200% 0; } 
            100% { background-position: -200% 0; } 
        }
        .course-card { 
            will-change: transform; 
            transform: translateZ(0); 
            backface-visibility: hidden;
        }
        .fade-in { 
            opacity: 0; 
            transform: translateY(20px) translateZ(0); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .fade-in.visible { 
            opacity: 1; 
            transform: translateY(0) translateZ(0); 
        }
        .error-boundary {
            padding: 2rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            text-align: center;
            margin: 2rem;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>

    <!-- PERFORMANCE: Preload critical resources with error handling -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    
    <!-- PERFORMANCE: Preload critical JavaScript -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <!-- FALLBACK: Critical resources without JavaScript -->
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- PERFORMANCE: Load optimized CSS async with fallback -->
    <link rel="stylesheet" href="{{ asset('css/optimized-courses.css') }}" media="print" onload="this.media='all'" onerror="this.media='all'">
</head>
<body>
    <!-- ACCESSIBILITY: Skip navigation -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- PERFORMANCE: Instant navbar with error boundary -->
    <nav class="navbar navbar-expand-lg fixed-top" role="navigation" aria-label="Main navigation">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('courses') }}" aria-label="MedCapsule Home">MedCapsule</a>
            
            <!-- PERFORMANCE: User info with lazy loading and error handling -->
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <div class="progress-container d-none d-md-block me-4" id="user-progress" aria-live="polite">
                    <!-- Progress loaded via AJAX with error handling -->
                    <div class="loading-skeleton" style="width: 200px; height: 20px;"></div>
                </div>
                
                <!-- Profile with skeleton and error handling -->
                <div class="profile-placeholder" id="profile-container">
                    <div class="loading-skeleton" style="width: 40px; height: 40px; border-radius: 50%;" aria-label="Loading profile"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ACCESSIBILITY: Main content landmark -->
    <main id="main-content" role="main">
        <!-- Hero section with error boundary -->
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="container">
                <h1 id="hero-title" class="hero-title fade-in">Choose Your Course</h1>
                <p class="hero-subtitle fade-in">Select a course to begin your medical education journey</p>
            </div>
        </section>

        <!-- Course selection section -->
        <section class="course-selection" aria-labelledby="course-section-title">
            <div class="container-fluid px-4">
                <h2 id="course-section-title" class="sr-only">Available Medical Courses</h2>
                
                <!-- PERFORMANCE: Instant filter dropdown with error handling -->
                <div class="filter-section mb-4 fade-in">
                    <label for="semesterFilter" class="form-label sr-only">Filter by semester</label>
                    <select id="semesterFilter" class="form-select semester-filter" aria-label="Filter courses by semester">
                        <option value="">All Semesters</option>
                        <option value="Year 1- First semester">Year 1 - First Semester</option>
                        <option value="Year 1- Second semester">Year 1 - Second Semester</option>
                        <option value="Year 2- First semester">Year 2 - First Semester</option>
                        <option value="Year 2- Second semester">Year 2 - Second Semester</option>
                    </select>
                </div>

                <!-- PERFORMANCE: Courses grid with instant loading and error handling -->
                <div class="courses-wrapper" role="region" aria-label="Course cards">
                    <div class="courses-grid" id="courses-grid" 
                         role="list" 
                         aria-live="polite" 
                         aria-label="Available courses"
                         tabindex="0">
                        <!-- Initial loading skeleton with accessibility -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="course-skeleton" role="listitem" aria-label="Loading course {{ $i + 1 }}">
                                <div class="loading-skeleton" style="height: 450px; border-radius: 12px;"></div>
                                <div class="p-3">
                                    <div class="loading-skeleton mb-2" style="height: 24px; width: 80%;"></div>
                                    <div class="loading-skeleton mb-2" style="height: 16px; width: 60%;"></div>
                                    <div class="loading-skeleton" style="height: 40px; width: 100%;"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    
                    <!-- PERFORMANCE: Scroll arrows with accessibility -->
                    <button class="scroll-arrow scroll-left" 
                            onclick="CourseManager.scrollCourses('left')" 
                            aria-label="Scroll courses left"
                            type="button">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="scroll-arrow scroll-right" 
                            onclick="CourseManager.scrollCourses('right')" 
                            aria-label="Scroll courses right"
                            type="button">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- PERFORMANCE: Modals loaded on demand with error boundaries -->
    <div id="modal-container" role="dialog" aria-hidden="true"></div>

    <!-- ACCESSIBILITY: Error notification area -->
    <div id="error-notifications" 
         role="alert" 
         aria-live="assertive" 
         class="sr-only"></div>

    <!-- PERFORMANCE: Load JavaScript after critical content with comprehensive error handling -->
    <script>
        'use strict';
        
        // SECURITY: Sanitize and validate data
        window.courseData = @json($courses ?? []);
        window.userProgress = @json($userProgress ?? []);
        window.csrfToken = '{{ csrf_token() }}';
        
        // ROBUSTNESS: Global error handler
        window.addEventListener('error', function(event) {
            console.error('Global error:', event.error);
            CourseManager.showErrorNotification('An unexpected error occurred. Please refresh the page.');
        });

        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            CourseManager.showErrorNotification('A network error occurred. Please check your connection.');
        });

        // PERFORMANCE & SECURITY: Robust Course Manager with comprehensive error handling
        const CourseManager = {
            // SECURITY: Input sanitization
            sanitizeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },

            // ROBUSTNESS: Safe DOM query with error handling
            safeQuerySelector(selector, context = document) {
                try {
                    return context.querySelector(selector);
                } catch (error) {
                    console.error('Invalid selector:', selector, error);
                    return null;
                }
            },

            // ROBUSTNESS: Safe DOM query all with error handling
            safeQuerySelectorAll(selector, context = document) {
                try {
                    return context.querySelectorAll(selector);
                } catch (error) {
                    console.error('Invalid selector:', selector, error);
                    return [];
                }
            },

            // ACCESSIBILITY: Error notifications
            showErrorNotification(message) {
                const errorContainer = this.safeQuerySelector('#error-notifications');
                if (errorContainer) {
                    errorContainer.textContent = message;
                    errorContainer.classList.remove('sr-only');
                    
                    setTimeout(() => {
                        errorContainer.classList.add('sr-only');
                        errorContainer.textContent = '';
                    }, 5000);
                }
            },

            // PERFORMANCE: Client-side caching with TTL and size limits
            cache: {
                data: new Map(),
                maxSize: 100,
                defaultTTL: 300000, // 5 minutes
                
                set(key, value, ttl = this.defaultTTL) {
                    try {
                        // Prevent cache from growing too large
                        if (this.data.size >= this.maxSize) {
                            const firstKey = this.data.keys().next().value;
                            this.data.delete(firstKey);
                        }
                        
                        this.data.set(key, { 
                            value, 
                            expires: Date.now() + ttl,
                            created: Date.now()
                        });
                    } catch (error) {
                        console.error('Cache set error:', error);
                    }
                },
                
                get(key) {
                    try {
                        const item = this.data.get(key);
                        if (!item || Date.now() > item.expires) {
                            this.data.delete(key);
                            return null;
                        }
                        return item.value;
                    } catch (error) {
                        console.error('Cache get error:', error);
                        return null;
                    }
                },
                
                clear() {
                    try {
                        this.data.clear();
                    } catch (error) {
                        console.error('Cache clear error:', error);
                    }
                },
                
                // Clean expired entries
                cleanup() {
                    try {
                        const now = Date.now();
                        for (const [key, item] of this.data.entries()) {
                            if (now > item.expires) {
                                this.data.delete(key);
                            }
                        }
                    } catch (error) {
                        console.error('Cache cleanup error:', error);
                    }
                }
            },

            // PERFORMANCE: Instant course rendering with error handling
            renderCourses(courses, semester = '') {
                try {
                    const grid = this.safeQuerySelector('#courses-grid');
                    if (!grid) {
                        throw new Error('Courses grid not found');
                    }
                    
                    // Validate courses data
                    if (!Array.isArray(courses)) {
                        throw new Error('Invalid courses data');
                    }
                    
                    // PERFORMANCE: Use DocumentFragment for batch DOM updates
                    const fragment = document.createDocumentFragment();
                    
                    const filteredCourses = semester ? 
                        courses.filter(course => course?.semester === semester) : 
                        courses;

                    if (filteredCourses.length === 0) {
                        this.renderEmptyState(grid);
                        return;
                    }

                    filteredCourses.forEach((course, index) => {
                        try {
                            const courseCard = this.createCourseCard(course, index);
                            if (courseCard) {
                                fragment.appendChild(courseCard);
                            }
                        } catch (error) {
                            console.error('Error creating course card:', error);
                        }
                    });

                    // PERFORMANCE: Single reflow
                    grid.innerHTML = '';
                    grid.appendChild(fragment);
                    
                    // PERFORMANCE: Trigger animations with RAF
                    requestAnimationFrame(() => {
                        this.safeQuerySelectorAll('.course-card', grid).forEach(card => {
                            card.classList.add('visible');
                        });
                    });
                    
                    // ACCESSIBILITY: Update screen reader
                    grid.setAttribute('aria-label', `${filteredCourses.length} courses available`);
                    
                } catch (error) {
                    console.error('Error rendering courses:', error);
                    this.renderErrorState();
                }
            },

            // ROBUSTNESS: Empty state rendering
            renderEmptyState(container) {
                container.innerHTML = `
                    <div class="empty-state" role="status" aria-live="polite">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <h3>No courses found</h3>
                        <p>Try adjusting your filter or check back later.</p>
                        <button type="button" class="btn btn-primary" onclick="CourseManager.resetFilters()">
                            <i class="fas fa-refresh" aria-hidden="true"></i>
                            Reset Filters
                        </button>
                    </div>
                `;
            },

            // ROBUSTNESS: Error state rendering
            renderErrorState() {
                const grid = this.safeQuerySelector('#courses-grid');
                if (grid) {
                    grid.innerHTML = `
                        <div class="error-boundary" role="alert">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <h3>Unable to load courses</h3>
                            <p>Please check your connection and try again.</p>
                            <button type="button" class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh" aria-hidden="true"></i>
                                Retry
                            </button>
                        </div>
                    `;
                }
            },

            // SECURITY: Secure course card creation with XSS prevention
            createCourseCard(course, index) {
                try {
                    // Validate course data
                    if (!course || typeof course !== 'object') {
                        throw new Error('Invalid course data');
                    }

                    const card = document.createElement('div');
                    card.className = 'course-card fade-in';
                    card.setAttribute('role', 'listitem');
                    card.setAttribute('data-semester', this.sanitizeHtml(course.semester || ''));
                    card.style.setProperty('--animation-delay', `${index * 50}ms`);
                    
                    // SECURITY: Safe content creation using DOM methods instead of innerHTML
                    const courseImage = document.createElement('div');
                    courseImage.className = 'course-image';
                    courseImage.style.backgroundImage = `url('${this.sanitizeHtml(course.image_url || '')}')`;
                    courseImage.setAttribute('role', 'img');
                    courseImage.setAttribute('aria-label', `${course.name} course image`);
                    
                    const infoIcon = document.createElement('div');
                    infoIcon.className = 'info-icon';
                    infoIcon.setAttribute('role', 'button');
                    infoIcon.setAttribute('tabindex', '0');
                    infoIcon.setAttribute('aria-label', 'Course information');
                    infoIcon.innerHTML = '<i class="fas fa-info" aria-hidden="true"></i>';
                    
                    const tooltip = document.createElement('div');
                    tooltip.className = 'info-tooltip';
                    tooltip.textContent = course.description || 'Course information';
                    infoIcon.appendChild(tooltip);
                    
                    const courseOverlay = document.createElement('div');
                    courseOverlay.className = 'course-overlay';
                    
                    const courseTitle = document.createElement('h3');
                    courseTitle.className = 'course-title';
                    courseTitle.textContent = course.name || 'Untitled Course';
                    
                    courseOverlay.appendChild(courseTitle);
                    courseImage.appendChild(infoIcon);
                    courseImage.appendChild(courseOverlay);
                    
                    const courseInfo = document.createElement('div');
                    courseInfo.className = 'course-info';
                    
                    const courseStats = document.createElement('div');
                    courseStats.className = 'course-stats';
                    
                    const topicsItem = document.createElement('div');
                    topicsItem.className = 'stat-item';
                    topicsItem.innerHTML = `
                        <i class="fas fa-book" aria-hidden="true"></i>
                        <span>${parseInt(course.topics_count) || 0} Topics</span>
                    `;
                    
                    const studentsItem = document.createElement('div');
                    studentsItem.className = 'stat-item';
                    studentsItem.innerHTML = `
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <span>${parseInt(course.enrolled_count) || 0} Students</span>
                    `;
                    
                    courseStats.appendChild(topicsItem);
                    courseStats.appendChild(studentsItem);
                    courseInfo.appendChild(courseStats);
                    
                    // Progress bar if applicable
                    if (course.progress > 0) {
                        const progressContainer = document.createElement('div');
                        progressContainer.className = 'progress-container';
                        progressContainer.innerHTML = `
                            <div class="progress-bar" role="progressbar" 
                                 aria-valuenow="${course.progress}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"
                                 aria-label="Course progress">
                                <div class="progress-fill" style="width: ${Math.min(100, Math.max(0, course.progress))}%;"></div>
                            </div>
                        `;
                        courseInfo.appendChild(progressContainer);
                    }
                    
                    // Action button with proper event handling
                    const actionBtn = document.createElement('button');
                    actionBtn.className = `action-btn ${course.enrolled ? 'continue' : 'enroll'}`;
                    actionBtn.type = 'button';
                    actionBtn.setAttribute('data-course-id', course.id);
                    actionBtn.setAttribute('data-action', course.enrolled ? 'continue' : 'enroll');
                    
                    const buttonIcon = document.createElement('i');
                    buttonIcon.className = `fas fa-${course.enrolled ? 'play' : 'plus'}`;
                    buttonIcon.setAttribute('aria-hidden', 'true');
                    
                    const buttonText = document.createTextNode(
                        course.enrolled ? 'Continue Learning' : 
                        (course.price > 0 ? `Enroll - ${course.price} EGP` : 'Enroll Free')
                    );
                    
                    actionBtn.appendChild(buttonIcon);
                    actionBtn.appendChild(buttonText);
                    
                    // SECURITY: Use proper event listeners instead of onclick
                    actionBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.handleCourseAction(course.id, course.enrolled ? 'continue' : 'enroll', actionBtn);
                    });
                    
                    // ACCESSIBILITY: Keyboard support
                    actionBtn.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.handleCourseAction(course.id, course.enrolled ? 'continue' : 'enroll', actionBtn);
                        }
                    });
                    
                    courseInfo.appendChild(actionBtn);
                    card.appendChild(courseImage);
                    card.appendChild(courseInfo);
                    
                    return card;
                    
                } catch (error) {
                    console.error('Error creating course card:', error);
                    return null;
                }
            },

            // PERFORMANCE: Instant filter with debouncing and error handling
            filterTimeout: null,
            handleSemesterFilter() {
                try {
                    clearTimeout(this.filterTimeout);
                    this.filterTimeout = setTimeout(() => {
                        const dropdown = this.safeQuerySelector('#semesterFilter');
                        if (!dropdown) return;
                        
                        const semester = dropdown.value;
                        
                        // PERFORMANCE: Check cache first
                        const cacheKey = `courses_${semester}`;
                        let filteredData = this.cache.get(cacheKey);
                        
                        if (!filteredData) {
                            filteredData = semester ? 
                                window.courseData.filter(course => course?.semester === semester) :
                                window.courseData;
                            this.cache.set(cacheKey, filteredData);
                        }
                        
                        this.renderCourses(filteredData);
                        this.updateScrollArrows();
                        
                    }, 100); // Instant debounce
                } catch (error) {
                    console.error('Filter error:', error);
                    this.showErrorNotification('Error filtering courses');
                }
            },

            // PERFORMANCE: Reset filters
            resetFilters() {
                try {
                    const dropdown = this.safeQuerySelector('#semesterFilter');
                    if (dropdown) {
                        dropdown.value = '';
                        this.handleSemesterFilter();
                    }
                } catch (error) {
                    console.error('Reset filters error:', error);
                }
            },

            // PERFORMANCE: Smooth scrolling with requestAnimationFrame and error handling
            scrollCourses(direction) {
                try {
                    const container = this.safeQuerySelector('#courses-grid');
                    if (!container) return;
                    
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
                    
                    const animateScroll = (currentTime) => {
                        try {
                            if (!startTime) startTime = currentTime;
                            const elapsed = currentTime - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            
                            // Easing function
                            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                            
                            container.scrollLeft = startScroll + (distance * easeOutCubic);
                            
                            if (progress < 1) {
                                requestAnimationFrame(animateScroll);
                            }
                        } catch (error) {
                            console.error('Scroll animation error:', error);
                        }
                    };
                    
                    requestAnimationFrame(animateScroll);
                } catch (error) {
                    console.error('Scroll error:', error);
                }
            },

            // PERFORMANCE: Throttled scroll arrow updates with error handling
            scrollTimeout: null,
            updateScrollArrows() {
                try {
                    clearTimeout(this.scrollTimeout);
                    this.scrollTimeout = setTimeout(() => {
                        const container = this.safeQuerySelector('#courses-grid');
                        const leftArrow = this.safeQuerySelector('.scroll-left');
                        const rightArrow = this.safeQuerySelector('.scroll-right');
                        
                        if (container && leftArrow && rightArrow) {
                            const canScrollLeft = container.scrollLeft > 0;
                            const canScrollRight = (container.scrollLeft + container.clientWidth) < container.scrollWidth;
                            
                            leftArrow.style.opacity = canScrollLeft ? '0.8' : '0';
                            rightArrow.style.opacity = canScrollRight ? '0.8' : '0';
                            
                            // ACCESSIBILITY: Update ARIA attributes
                            leftArrow.setAttribute('aria-disabled', !canScrollLeft);
                            rightArrow.setAttribute('aria-disabled', !canScrollRight);
                        }
                    }, 16); // ~60fps
                } catch (error) {
                    console.error('Update scroll arrows error:', error);
                }
            },

            // PERFORMANCE: Course action with instant feedback and error handling
            async handleCourseAction(courseId, action, button) {
                try {
                    if (!courseId || !action || !button) {
                        throw new Error('Invalid action parameters');
                    }
                    
                    const originalContent = button.innerHTML;
                    
                    // PERFORMANCE: Instant feedback
                    button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Processing...';
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    
                    if (action === 'continue') {
                        // SECURITY: Validate URL before navigation
                        const url = `/topics/${encodeURIComponent(courseId)}`;
                        if (url.match(/^\/topics\/\d+$/)) {
                            window.location.href = url;
                        } else {
                            throw new Error('Invalid navigation URL');
                        }
                    } else {
                        // PERFORMANCE: Load payment modal on demand
                        await this.loadPaymentModal(courseId);
                        
                        // Restore button state
                        button.innerHTML = originalContent;
                        button.disabled = false;
                        button.setAttribute('aria-busy', 'false');
                    }
                } catch (error) {
                    console.error('Course action error:', error);
                    this.showErrorNotification('Unable to process request. Please try again.');
                    
                    // Restore button state
                    if (button) {
                        button.innerHTML = button.dataset.originalContent || 'Try Again';
                        button.disabled = false;
                        button.setAttribute('aria-busy', 'false');
                    }
                }
            },

            // PERFORMANCE: Lazy load payment modal with comprehensive error handling
            async loadPaymentModal(courseId) {
                try {
                    if (!courseId) throw new Error('No course ID provided');
                    
                    const cacheKey = `payment_modal_${courseId}`;
                    let modalHtml = this.cache.get(cacheKey);
                    
                    if (!modalHtml) {
                        const response = await fetch(`/payments/${encodeURIComponent(courseId)}/options`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': window.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            // SECURITY: Include credentials for CSRF
                            credentials: 'same-origin'
                        });
                        
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success && data.html) {
                            modalHtml = data.html;
                            this.cache.set(cacheKey, modalHtml, 60000); // 1 minute cache
                        } else {
                            throw new Error(data.message || 'Failed to load payment options');
                        }
                    }
                    
                    // PERFORMANCE: Insert modal into container safely
                    const modalContainer = this.safeQuerySelector('#modal-container');
                    if (modalContainer && modalHtml) {
                        modalContainer.innerHTML = modalHtml;
                        
                        // Initialize modal if Bootstrap is available
                        if (window.bootstrap && window.bootstrap.Modal) {
                            const modalElement = modalContainer.querySelector('.modal');
                            if (modalElement) {
                                const modal = new bootstrap.Modal(modalElement);
                                modal.show();
                            }
                        }
                    }
                } catch (error) {
                    console.error('Load payment modal error:', error);
                    this.showErrorNotification('Unable to load payment options. Please try again.');
                }
            },

            // PERFORMANCE: Initialize animations with error handling
            initializeAnimations() {
                try {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            try {
                                if (entry.isIntersecting) {
                                    entry.target.classList.add('visible');
                                    observer.unobserve(entry.target);
                                }
                            } catch (error) {
                                console.error('Animation observer error:', error);
                            }
                        });
                    }, { 
                        threshold: 0.1,
                        rootMargin: '50px'
                    });

                    this.safeQuerySelectorAll('.fade-in').forEach(el => {
                        observer.observe(el);
                    });
                } catch (error) {
                    console.error('Initialize animations error:', error);
                    // Fallback: Add visible class immediately
                    this.safeQuerySelectorAll('.fade-in').forEach(el => {
                        el.classList.add('visible');
                    });
                }
            },

            // PERFORMANCE: Async user progress loading with error handling
            async loadUserProgress() {
                try {
                    const response = await fetch('/profile/progress', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    
                    const progress = await response.json();
                    
                    if (progress && typeof progress === 'object') {
                        this.updateProgressDisplay(progress);
                        this.updateProfileDisplay(progress);
                    }
                } catch (error) {
                    console.warn('Failed to load user progress:', error);
                    // Hide progress container on error
                    const progressContainer = this.safeQuerySelector('#user-progress');
                    if (progressContainer) {
                        progressContainer.style.display = 'none';
                    }
                }
            },

            // ROBUSTNESS: Update progress display safely
            updateProgressDisplay(progress) {
                try {
                    const progressContainer = this.safeQuerySelector('#user-progress');
                    if (progressContainer) {
                        const correctAnswers = parseInt(progress.correct_answers) || 0;
                        const goal = parseInt(progress.goal) || 100;
                        const percentage = Math.min(100, Math.max(0, (correctAnswers / goal) * 100));
                        
                        progressContainer.innerHTML = `
                            <div class="milestone-container">
                                <div class="milestone-text">
                                    <span id="correctCount">${correctAnswers}</span> / 
                                    <span id="goalCount">${goal}</span>
                                </div>
                                <div class="progress-bar-liquid" 
                                     role="progressbar" 
                                     aria-valuenow="${correctAnswers}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="${goal}"
                                     aria-label="Learning progress">
                                    <div class="liquid-fill" style="width: ${percentage}%;"></div>
                                </div>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Update progress display error:', error);
                }
            },

            // ROBUSTNESS: Update profile display safely
            updateProfileDisplay(progress) {
                try {
                    const profileContainer = this.safeQuerySelector('#profile-container');
                    if (profileContainer) {
                        const profilePicture = progress.profile_picture;
                        const initials = this.sanitizeHtml(progress.initials || 'U');
                        
                        profileContainer.innerHTML = `
                            <a href="/profile" 
                               class="profile-circle ${profilePicture ? '' : 'no-image'}"
                               ${profilePicture ? `style="background-image: url('${this.sanitizeHtml(profilePicture)}')"` : ''}
                               aria-label="View profile">
                                ${!profilePicture ? initials : ''}
                            </a>
                        `;
                    }
                } catch (error) {
                    console.error('Update profile display error:', error);
                }
            },

            // MEMORY: Cleanup function
            cleanup() {
                try {
                    clearTimeout(this.filterTimeout);
                    clearTimeout(this.scrollTimeout);
                    this.cache.clear();
                } catch (error) {
                    console.error('Cleanup error:', error);
                }
            }
        };

        // PERFORMANCE: DOM ready optimization with error handling
        function init() {
            try {
                // Validate required data
                if (!Array.isArray(window.courseData)) {
                    throw new Error('Invalid course data');
                }
                
                // PERFORMANCE: Initialize in priority order
                CourseManager.renderCourses(window.courseData);
                CourseManager.initializeAnimations();
                
                // Setup event listeners with error handling
                const semesterFilter = CourseManager.safeQuerySelector('#semesterFilter');
                if (semesterFilter) {
                    semesterFilter.addEventListener('change', () => {
                        CourseManager.handleSemesterFilter();
                    });
                }
                
                const coursesGrid = CourseManager.safeQuerySelector('#courses-grid');
                if (coursesGrid) {
                    coursesGrid.addEventListener('scroll', () => {
                        CourseManager.updateScrollArrows();
                    }, { passive: true });
                    
                    // ACCESSIBILITY: Keyboard navigation
                    coursesGrid.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            CourseManager.scrollCourses('left');
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            CourseManager.scrollCourses('right');
                        }
                    });
                }
                
                // PERFORMANCE: Load non-critical data after main content
                if (window.requestIdleCallback) {
                    requestIdleCallback(() => {
                        CourseManager.loadUserProgress();
                        CourseManager.updateScrollArrows();
                        
                        // Periodic cache cleanup
                        setInterval(() => {
                            CourseManager.cache.cleanup();
                        }, 60000); // Every minute
                    });
                } else {
                    // Fallback for browsers without requestIdleCallback
                    setTimeout(() => {
                        CourseManager.loadUserProgress();
                        CourseManager.updateScrollArrows();
                    }, 100);
                }
                
            } catch (error) {
                console.error('Initialization error:', error);
                CourseManager.renderErrorState();
            }
        }

        // ROBUSTNESS: Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

        // MEMORY: Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            CourseManager.cleanup();
        });

        // PERFORMANCE: Preload critical routes
        window.addEventListener('load', () => {
            if (window.requestIdleCallback) {
                requestIdleCallback(() => {
                    // Preload likely next pages
                    const preloadUrls = ['/profile', '/topics', '/review'];
                    preloadUrls.forEach(url => {
                        try {
                            const link = document.createElement('link');
                            link.rel = 'prefetch';
                            link.href = url;
                            document.head.appendChild(link);
                        } catch (error) {
                            console.warn('Preload error:', url, error);
                        }
                    });
                });
            }
        });
    </script>

    <!-- PERFORMANCE: Load heavy scripts after content with error handling -->
    <script>
        // Load Bootstrap with error handling
        (function() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
            script.defer = true;
            script.onerror = function() {
                console.warn('Failed to load Bootstrap JavaScript');
            };
            document.head.appendChild(script);
        })();
    </script>
</body>
</html>