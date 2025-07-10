<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Explore MedCapsule courses for medical education">
    <title>Medical Courses - MedCapsule</title>
    
    <!-- SECURITY: Enhanced Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; connect-src 'self'; worker-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self';">
    
    <!-- PERFORMANCE: Critical CSS with enhanced optimizations -->
    <style>
        html { 
            font-size: 12.8px; 
            scroll-behavior: smooth;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #f8f9fa; 
            color: #1a1a1a; 
            margin: 0; 
            padding: 0; 
            min-height: 100vh; 
            overflow-x: hidden; 
            line-height: 1.6;
            font-feature-settings: "kern" 1, "liga" 1;
        }
        .loading-skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); 
            background-size: 200% 100%; 
            animation: loading 1.5s infinite;
            border-radius: 8px;
            contain: layout style paint;
        }
        @keyframes loading { 
            0% { background-position: 200% 0; } 
            100% { background-position: -200% 0; } 
        }
        .course-card { 
            will-change: transform; 
            transform: translateZ(0); 
            backface-visibility: hidden;
            contain: layout style paint;
            isolation: isolate;
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
            contain: layout style;
        }
        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        /* PERFORMANCE: Critical loading states */
        .skeleton-pulse {
            animation: skeleton-pulse 2s ease-in-out infinite alternate;
        }
        @keyframes skeleton-pulse {
            0% { opacity: 1; }
            100% { opacity: 0.4; }
        }
    </style>

    <!-- PERFORMANCE: Enhanced resource preloading with error handling -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" onerror="this.onerror=null;this.rel='stylesheet'">
    
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <!-- FALLBACK: Critical resources without JavaScript -->
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <link rel="stylesheet" href="{{ asset('css/optimized-courses-ultra-robust.css') }}" media="print" onload="this.media='all'" onerror="this.media='all'">
</head>
<body>
    <!-- ACCESSIBILITY: Skip navigation -->
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    <!-- PERFORMANCE: Enhanced navbar -->
    <nav class="navbar navbar-expand-lg fixed-top" role="navigation" aria-label="Main navigation">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('courses') }}" aria-label="MedCapsule Home">MedCapsule</a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <div class="progress-container d-none d-md-block me-4" id="user-progress" aria-live="polite">
                    <div class="loading-skeleton skeleton-pulse" style="width: 200px; height: 20px;"></div>
                </div>
                
                <div class="profile-placeholder" id="profile-container">
                    <div class="loading-skeleton skeleton-pulse" style="width: 40px; height: 40px; border-radius: 50%;" aria-label="Loading profile"></div>
                </div>
            </div>
        </div>
    </nav>

    <main id="main-content" role="main">
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="container">
                <h1 id="hero-title" class="hero-title fade-in">Choose Your Course</h1>
                <p class="hero-subtitle fade-in">Select a course to begin your medical education journey</p>
            </div>
        </section>

        <section class="course-selection" aria-labelledby="course-section-title">
            <div class="container-fluid px-4">
                <h2 id="course-section-title" class="sr-only">Available Medical Courses</h2>
                
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

                <div class="courses-wrapper" role="region" aria-label="Course cards">
                    <div class="courses-grid" id="courses-grid" 
                         role="list" 
                         aria-live="polite" 
                         aria-label="Available courses"
                         tabindex="0">
                        <!-- Loading skeletons -->
                        @for($i = 0; $i < 6; $i++)
                            <div class="course-skeleton" role="listitem" aria-label="Loading course {{ $i + 1 }}">
                                <div class="loading-skeleton skeleton-pulse" style="height: 450px; border-radius: 12px;"></div>
                                <div class="p-3">
                                    <div class="loading-skeleton skeleton-pulse mb-2" style="height: 24px; width: 80%;"></div>
                                    <div class="loading-skeleton skeleton-pulse mb-2" style="height: 16px; width: 60%;"></div>
                                    <div class="loading-skeleton skeleton-pulse" style="height: 40px; width: 100%;"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    
                    <button class="scroll-arrow scroll-left" 
                            data-action="scroll-left"
                            aria-label="Scroll courses left"
                            type="button">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="scroll-arrow scroll-right" 
                            data-action="scroll-right"
                            aria-label="Scroll courses right"
                            type="button">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- PERFORMANCE: Modal container with focus trap -->
    <div id="modal-container" role="dialog" aria-hidden="true">
        <div class="focus-trap" tabindex="0"></div>
        <div class="focus-trap" tabindex="0"></div>
    </div>

    <!-- ACCESSIBILITY: Enhanced error notifications -->
    <div id="error-notifications" 
         role="alert" 
         aria-live="assertive" 
         aria-atomic="true"
         class="sr-only"></div>

    <!-- ROBUSTNESS: Network status indicator -->
    <div id="network-status" class="network-status" role="status" aria-live="polite"></div>

    <script>
        'use strict';
        
        // SECURITY: Enhanced data validation and sanitization
        const SAFE_COURSES_DATA = (() => {
            try {
                const rawData = @json($courses ?? []);
                if (!Array.isArray(rawData)) {
                    console.warn('Invalid courses data structure');
                    return [];
                }
                return rawData.filter(course => 
                    course && 
                    typeof course === 'object' && 
                    typeof course.id === 'number' && 
                    typeof course.name === 'string'
                );
            } catch (error) {
                console.error('Failed to parse courses data:', error);
                return [];
            }
        })();
        
        const SAFE_USER_PROGRESS = (() => {
            try {
                const rawData = @json($userProgress ?? {});
                return typeof rawData === 'object' ? rawData : {};
            } catch (error) {
                console.error('Failed to parse user progress:', error);
                return {};
            }
        })();
        
        const CSRF_TOKEN = '{{ csrf_token() }}';
        
        // ROBUSTNESS: Enhanced global error handling
        const GlobalErrorHandler = {
            init() {
                window.addEventListener('error', this.handleError.bind(this), { passive: true });
                window.addEventListener('unhandledrejection', this.handleRejection.bind(this), { passive: true });
                window.addEventListener('online', this.handleOnline.bind(this), { passive: true });
                window.addEventListener('offline', this.handleOffline.bind(this), { passive: true });
            },
            
            handleError(event) {
                console.error('Global error:', event.error);
                this.logError('JavaScript Error', event.error);
                CourseManager.showErrorNotification('An unexpected error occurred. Please refresh the page.');
            },
            
            handleRejection(event) {
                console.error('Unhandled promise rejection:', event.reason);
                this.logError('Promise Rejection', event.reason);
                CourseManager.showErrorNotification('A network error occurred. Please check your connection.');
            },
            
            handleOnline() {
                CourseManager.updateNetworkStatus(true);
            },
            
            handleOffline() {
                CourseManager.updateNetworkStatus(false);
            },
            
            logError(type, error) {
                try {
                    const errorData = {
                        type,
                        message: error?.message || String(error),
                        stack: error?.stack || '',
                        timestamp: new Date().toISOString(),
                        url: window.location.href,
                        userAgent: navigator.userAgent
                    };
                    
                    // Send to logging service if available
                    if (window.errorLogger) {
                        window.errorLogger.log(errorData);
                    }
                } catch (logError) {
                    console.error('Failed to log error:', logError);
                }
            }
        };

        // PERFORMANCE & SECURITY: Surgically precise Course Manager
        const CourseManager = {
            // MEMORY: WeakMap for better garbage collection
            _elementRefs: new WeakMap(),
            _abortControllers: new Map(),
            _intersectionObserver: null,
            _resizeObserver: null,
            _requestIdCallbacks: new Set(),
            
            // SECURITY: Enhanced input sanitization with URL validation
            sanitizeHtml(str) {
                if (!str || typeof str !== 'string') return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },
            
            // SECURITY: Comprehensive URL validation
            validateAndSanitizeUrl(url) {
                if (!url || typeof url !== 'string') return '/images/default-course.jpg';
                
                try {
                    const urlObj = new URL(url, window.location.origin);
                    const allowedProtocols = ['https:', 'http:', 'data:'];
                    const allowedHosts = [window.location.hostname, 'cdn.jsdelivr.net', 'cdnjs.cloudflare.com'];
                    
                    if (!allowedProtocols.includes(urlObj.protocol)) {
                        console.warn('Unsafe URL protocol:', url);
                        return '/images/default-course.jpg';
                    }
                    
                    // Additional validation for data URLs
                    if (urlObj.protocol === 'data:') {
                        if (!url.match(/^data:image\/(jpeg|jpg|png|gif|webp|svg\+xml);base64,/)) {
                            console.warn('Unsafe data URL:', url);
                            return '/images/default-course.jpg';
                        }
                    }
                    
                    // Validate external hosts
                    if (urlObj.hostname !== window.location.hostname && 
                        !allowedHosts.includes(urlObj.hostname)) {
                        console.warn('Unsafe external host:', url);
                        return '/images/default-course.jpg';
                    }
                    
                    return urlObj.href;
                } catch (error) {
                    console.warn('Invalid URL:', url, error);
                    return '/images/default-course.jpg';
                }
            },

            // ROBUSTNESS: Safe DOM operations with enhanced error handling
            safeQuerySelector(selector, context = document) {
                try {
                    if (!selector || typeof selector !== 'string') {
                        throw new Error('Invalid selector provided');
                    }
                    return context.querySelector(selector);
                } catch (error) {
                    console.error('Invalid selector:', selector, error);
                    return null;
                }
            },

            safeQuerySelectorAll(selector, context = document) {
                try {
                    if (!selector || typeof selector !== 'string') {
                        throw new Error('Invalid selector provided');
                    }
                    return Array.from(context.querySelectorAll(selector));
                } catch (error) {
                    console.error('Invalid selector:', selector, error);
                    return [];
                }
            },

            // ACCESSIBILITY: Enhanced error notifications with ARIA
            showErrorNotification(message, type = 'error', duration = 5000) {
                const errorContainer = this.safeQuerySelector('#error-notifications');
                if (!errorContainer) return;
                
                const sanitizedMessage = this.sanitizeHtml(message);
                errorContainer.textContent = sanitizedMessage;
                errorContainer.className = `alert alert-${type}`;
                errorContainer.classList.remove('sr-only');
                errorContainer.setAttribute('aria-live', 'assertive');
                
                const timeoutId = setTimeout(() => {
                    errorContainer.classList.add('sr-only');
                    errorContainer.textContent = '';
                    errorContainer.removeAttribute('aria-live');
                }, duration);
                
                this._requestIdCallbacks.add(timeoutId);
            },

            // PERFORMANCE: Enhanced caching with memory management
            cache: {
                data: new Map(),
                maxSize: 50, // Reduced for better memory management
                maxAge: 300000, // 5 minutes
                
                set(key, value, ttl = this.maxAge) {
                    try {
                        if (typeof key !== 'string') {
                            throw new Error('Cache key must be a string');
                        }
                        
                        // Cleanup old entries first
                        this.cleanup();
                        
                        // Prevent cache from growing too large
                        if (this.data.size >= this.maxSize) {
                            const oldestKey = this.data.keys().next().value;
                            this.data.delete(oldestKey);
                        }
                        
                        const item = {
                            value: structuredClone ? structuredClone(value) : JSON.parse(JSON.stringify(value)),
                            expires: Date.now() + ttl,
                            created: Date.now(),
                            accessed: Date.now()
                        };
                        
                        this.data.set(key, item);
                    } catch (error) {
                        console.error('Cache set error:', error);
                    }
                },
                
                get(key) {
                    try {
                        if (typeof key !== 'string') {
                            return null;
                        }
                        
                        const item = this.data.get(key);
                        if (!item || Date.now() > item.expires) {
                            this.data.delete(key);
                            return null;
                        }
                        
                        item.accessed = Date.now();
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

            // PERFORMANCE: Course rendering with comprehensive error handling
            renderCourses(courses, semester = '') {
                try {
                    const grid = this.safeQuerySelector('#courses-grid');
                    if (!grid) {
                        throw new Error('Courses grid not found');
                    }
                    
                    if (!Array.isArray(courses)) {
                        throw new Error('Invalid courses data: expected array');
                    }
                    
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
                            // Continue rendering other cards
                        }
                    });

                    // PERFORMANCE: Batch DOM updates
                    grid.innerHTML = '';
                    grid.appendChild(fragment);
                    
                    // PERFORMANCE: Intersection Observer for animations
                    this.initializeIntersectionObserver();
                    
                    // ACCESSIBILITY: Update screen reader
                    grid.setAttribute('aria-label', `${filteredCourses.length} courses available`);
                    
                } catch (error) {
                    console.error('Error rendering courses:', error);
                    this.renderErrorState();
                }
            },

            // SECURITY: Completely secure course card creation - NO innerHTML usage
            createCourseCard(course, index) {
                try {
                    if (!course || typeof course !== 'object' || !course.id || !course.name) {
                        throw new Error('Invalid course data structure');
                    }

                    const card = document.createElement('div');
                    card.className = 'course-card fade-in';
                    card.setAttribute('role', 'listitem');
                    card.setAttribute('data-semester', this.sanitizeHtml(course.semester || ''));
                    card.style.setProperty('--animation-delay', `${Math.min(index * 50, 2000)}ms`);
                    
                    // Course image with secure URL handling
                    const courseImage = document.createElement('div');
                    courseImage.className = 'course-image';
                    const sanitizedImageUrl = this.validateAndSanitizeUrl(course.image_url);
                    courseImage.style.backgroundImage = `url("${sanitizedImageUrl}")`;
                    courseImage.setAttribute('role', 'img');
                    courseImage.setAttribute('aria-label', `${this.sanitizeHtml(course.name)} course image`);
                    
                    // Info icon with secure content
                    const infoIcon = document.createElement('div');
                    infoIcon.className = 'info-icon';
                    infoIcon.setAttribute('role', 'button');
                    infoIcon.setAttribute('tabindex', '0');
                    infoIcon.setAttribute('aria-label', 'Course information');
                    
                    const infoIconElement = document.createElement('i');
                    infoIconElement.className = 'fas fa-info';
                    infoIconElement.setAttribute('aria-hidden', 'true');
                    infoIcon.appendChild(infoIconElement);
                    
                    const tooltip = document.createElement('div');
                    tooltip.className = 'info-tooltip';
                    tooltip.textContent = course.description || 'Course information';
                    infoIcon.appendChild(tooltip);
                    
                    // Course overlay with secure text
                    const courseOverlay = document.createElement('div');
                    courseOverlay.className = 'course-overlay';
                    
                    const courseTitle = document.createElement('h3');
                    courseTitle.className = 'course-title';
                    courseTitle.textContent = course.name || 'Untitled Course';
                    
                    courseOverlay.appendChild(courseTitle);
                    courseImage.appendChild(infoIcon);
                    courseImage.appendChild(courseOverlay);
                    
                    // Course info section
                    const courseInfo = document.createElement('div');
                    courseInfo.className = 'course-info';
                    
                    const courseStats = document.createElement('div');
                    courseStats.className = 'course-stats';
                    
                    // SECURITY FIX: No innerHTML - create elements securely
                    const topicsItem = document.createElement('div');
                    topicsItem.className = 'stat-item';
                    
                    const topicsIcon = document.createElement('i');
                    topicsIcon.className = 'fas fa-book';
                    topicsIcon.setAttribute('aria-hidden', 'true');
                    
                    const topicsText = document.createElement('span');
                    const topicsCount = Math.max(0, parseInt(course.topics_count) || 0);
                    topicsText.textContent = `${topicsCount} Topics`;
                    
                    topicsItem.appendChild(topicsIcon);
                    topicsItem.appendChild(topicsText);
                    
                    const studentsItem = document.createElement('div');
                    studentsItem.className = 'stat-item';
                    
                    const studentsIcon = document.createElement('i');
                    studentsIcon.className = 'fas fa-users';
                    studentsIcon.setAttribute('aria-hidden', 'true');
                    
                    const studentsText = document.createElement('span');
                    const enrolledCount = Math.max(0, parseInt(course.enrolled_count) || 0);
                    studentsText.textContent = `${enrolledCount} Students`;
                    
                    studentsItem.appendChild(studentsIcon);
                    studentsItem.appendChild(studentsText);
                    
                    courseStats.appendChild(topicsItem);
                    courseStats.appendChild(studentsItem);
                    courseInfo.appendChild(courseStats);
                    
                    // Progress bar with validation
                    const progress = Math.min(100, Math.max(0, parseFloat(course.progress) || 0));
                    if (progress > 0) {
                        const progressContainer = document.createElement('div');
                        progressContainer.className = 'progress-container';
                        
                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar';
                        progressBar.setAttribute('role', 'progressbar');
                        progressBar.setAttribute('aria-valuenow', String(progress));
                        progressBar.setAttribute('aria-valuemin', '0');
                        progressBar.setAttribute('aria-valuemax', '100');
                        progressBar.setAttribute('aria-label', 'Course progress');
                        
                        const progressFill = document.createElement('div');
                        progressFill.className = 'progress-fill';
                        progressFill.style.width = `${progress}%`;
                        
                        progressBar.appendChild(progressFill);
                        progressContainer.appendChild(progressBar);
                        courseInfo.appendChild(progressContainer);
                    }
                    
                    // Action button with secure event handling
                    const actionBtn = document.createElement('button');
                    actionBtn.className = `action-btn ${course.enrolled ? 'continue' : 'enroll'}`;
                    actionBtn.type = 'button';
                    actionBtn.setAttribute('data-course-id', String(course.id));
                    actionBtn.setAttribute('data-action', course.enrolled ? 'continue' : 'enroll');
                    
                    const buttonIcon = document.createElement('i');
                    buttonIcon.className = `fas fa-${course.enrolled ? 'play' : 'plus'}`;
                    buttonIcon.setAttribute('aria-hidden', 'true');
                    
                    const buttonText = document.createTextNode(' ');
                    const buttonLabel = document.createElement('span');
                    
                    if (course.enrolled) {
                        buttonLabel.textContent = 'Continue Learning';
                    } else {
                        const price = parseFloat(course.price) || 0;
                        buttonLabel.textContent = price > 0 ? `Enroll - ${price} EGP` : 'Enroll Free';
                    }
                    
                    actionBtn.appendChild(buttonIcon);
                    actionBtn.appendChild(buttonText);
                    actionBtn.appendChild(buttonLabel);
                    
                    // SECURITY: Event delegation with validation
                    this._elementRefs.set(actionBtn, {
                        courseId: course.id,
                        action: course.enrolled ? 'continue' : 'enroll',
                        originalContent: actionBtn.innerHTML
                    });
                    
                    courseInfo.appendChild(actionBtn);
                    card.appendChild(courseImage);
                    card.appendChild(courseInfo);
                    
                    return card;
                    
                } catch (error) {
                    console.error('Error creating course card:', error);
                    return this.createErrorCard(`Error loading course: ${error.message}`);
                }
            },

            // ROBUSTNESS: Error card creation
            createErrorCard(message) {
                const card = document.createElement('div');
                card.className = 'course-card error-card';
                card.setAttribute('role', 'listitem');
                
                const errorContent = document.createElement('div');
                errorContent.className = 'error-content';
                errorContent.textContent = this.sanitizeHtml(message);
                
                card.appendChild(errorContent);
                return card;
            },

            // SECURITY FIX: No innerHTML in empty state
            renderEmptyState(container) {
                container.innerHTML = ''; // Clear first
                
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.setAttribute('role', 'status');
                emptyState.setAttribute('aria-live', 'polite');
                
                const icon = document.createElement('i');
                icon.className = 'fas fa-search';
                icon.setAttribute('aria-hidden', 'true');
                
                const title = document.createElement('h3');
                title.textContent = 'No courses found';
                
                const description = document.createElement('p');
                description.textContent = 'Try adjusting your filter or check back later.';
                
                const resetButton = document.createElement('button');
                resetButton.type = 'button';
                resetButton.className = 'btn btn-primary';
                resetButton.setAttribute('data-action', 'reset-filters');
                
                const resetIcon = document.createElement('i');
                resetIcon.className = 'fas fa-refresh';
                resetIcon.setAttribute('aria-hidden', 'true');
                
                const resetText = document.createTextNode(' Reset Filters');
                
                resetButton.appendChild(resetIcon);
                resetButton.appendChild(resetText);
                
                emptyState.appendChild(icon);
                emptyState.appendChild(title);
                emptyState.appendChild(description);
                emptyState.appendChild(resetButton);
                
                container.appendChild(emptyState);
            },

            // SECURITY FIX: No innerHTML in error state
            renderErrorState() {
                const grid = this.safeQuerySelector('#courses-grid');
                if (!grid) return;
                
                grid.innerHTML = ''; // Clear first
                
                const errorBoundary = document.createElement('div');
                errorBoundary.className = 'error-boundary';
                errorBoundary.setAttribute('role', 'alert');
                
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-triangle';
                icon.setAttribute('aria-hidden', 'true');
                
                const title = document.createElement('h3');
                title.textContent = 'Unable to load courses';
                
                const description = document.createElement('p');
                description.textContent = 'Please check your connection and try again.';
                
                const retryButton = document.createElement('button');
                retryButton.type = 'button';
                retryButton.className = 'btn btn-primary';
                retryButton.setAttribute('data-action', 'retry');
                
                const retryIcon = document.createElement('i');
                retryIcon.className = 'fas fa-refresh';
                retryIcon.setAttribute('aria-hidden', 'true');
                
                const retryText = document.createTextNode(' Retry');
                
                retryButton.appendChild(retryIcon);
                retryButton.appendChild(retryText);
                
                errorBoundary.appendChild(icon);
                errorBoundary.appendChild(title);
                errorBoundary.appendChild(description);
                errorBoundary.appendChild(retryButton);
                
                grid.appendChild(errorBoundary);
            },

            // PERFORMANCE: Network status management
            updateNetworkStatus(isOnline) {
                const statusEl = this.safeQuerySelector('#network-status');
                if (!statusEl) return;
                
                if (isOnline) {
                    statusEl.textContent = 'Connection restored';
                    statusEl.className = 'network-status online visible';
                    setTimeout(() => {
                        statusEl.classList.remove('visible');
                    }, 3000);
                } else {
                    statusEl.textContent = 'No internet connection';
                    statusEl.className = 'network-status offline visible';
                }
            },

            // PERFORMANCE: Enhanced intersection observer
            initializeIntersectionObserver() {
                if (this._intersectionObserver) {
                    this._intersectionObserver.disconnect();
                }
                
                try {
                    this._intersectionObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            try {
                                if (entry.isIntersecting) {
                                    entry.target.classList.add('visible');
                                    this._intersectionObserver.unobserve(entry.target);
                                }
                            } catch (error) {
                                console.error('Intersection observer error:', error);
                            }
                        });
                    }, { 
                        threshold: 0.1,
                        rootMargin: '50px'
                    });

                    this.safeQuerySelectorAll('.fade-in').forEach(el => {
                        this._intersectionObserver.observe(el);
                    });
                } catch (error) {
                    console.error('Failed to initialize intersection observer:', error);
                    // Fallback: Add visible class immediately
                    this.safeQuerySelectorAll('.fade-in').forEach(el => {
                        el.classList.add('visible');
                    });
                }
            },

            // MEMORY: Comprehensive cleanup
            cleanup() {
                try {
                    // Clear timeouts and intervals
                    this._requestIdCallbacks.forEach(id => {
                        clearTimeout(id);
                        clearInterval(id);
                    });
                    this._requestIdCallbacks.clear();
                    
                    // Abort ongoing requests
                    this._abortControllers.forEach(controller => {
                        try {
                            controller.abort();
                        } catch (error) {
                            console.warn('Error aborting request:', error);
                        }
                    });
                    this._abortControllers.clear();
                    
                    // Disconnect observers
                    if (this._intersectionObserver) {
                        this._intersectionObserver.disconnect();
                        this._intersectionObserver = null;
                    }
                    
                    if (this._resizeObserver) {
                        this._resizeObserver.disconnect();
                        this._resizeObserver = null;
                    }
                    
                    // Clear cache
                    this.cache.clear();
                    
                    // Clear WeakMap references
                    this._elementRefs = new WeakMap();
                    
                } catch (error) {
                    console.error('Cleanup error:', error);
                }
            }
        };

        // PERFORMANCE: Initialize with proper error handling
        function initializeApp() {
            try {
                GlobalErrorHandler.init();
                
                // Validate data
                if (!Array.isArray(SAFE_COURSES_DATA)) {
                    throw new Error('Invalid courses data structure');
                }
                
                CourseManager.renderCourses(SAFE_COURSES_DATA);
                
                // Event delegation for better performance and security
                document.addEventListener('click', handleGlobalClick, { passive: false });
                document.addEventListener('change', handleGlobalChange, { passive: true });
                document.addEventListener('keydown', handleGlobalKeydown, { passive: false });
                
                // Setup cleanup on page unload
                window.addEventListener('beforeunload', () => {
                    CourseManager.cleanup();
                }, { passive: true });
                
                // Initialize periodic cache cleanup
                setInterval(() => {
                    CourseManager.cache.cleanup();
                }, 60000);
                
            } catch (error) {
                console.error('App initialization error:', error);
                CourseManager.renderErrorState();
            }
        }

        // SECURITY: Event delegation with validation
        function handleGlobalClick(event) {
            const target = event.target.closest('[data-action]');
            if (!target) return;
            
            const action = target.getAttribute('data-action');
            
            try {
                switch (action) {
                    case 'scroll-left':
                        event.preventDefault();
                        CourseManager.scrollCourses('left');
                        break;
                    case 'scroll-right':
                        event.preventDefault();
                        CourseManager.scrollCourses('right');
                        break;
                    case 'reset-filters':
                        event.preventDefault();
                        CourseManager.resetFilters();
                        break;
                    case 'retry':
                        event.preventDefault();
                        location.reload();
                        break;
                    default:
                        // Handle course action buttons
                        if (target.classList.contains('action-btn')) {
                            event.preventDefault();
                            const courseData = CourseManager._elementRefs.get(target);
                            if (courseData) {
                                CourseManager.handleCourseAction(
                                    courseData.courseId, 
                                    courseData.action, 
                                    target
                                );
                            }
                        }
                }
            } catch (error) {
                console.error('Click handler error:', error);
                CourseManager.showErrorNotification('Action failed. Please try again.');
            }
        }

        function handleGlobalChange(event) {
            if (event.target.id === 'semesterFilter') {
                try {
                    CourseManager.handleSemesterFilter();
                } catch (error) {
                    console.error('Filter change error:', error);
                    CourseManager.showErrorNotification('Filter error. Please try again.');
                }
            }
        }

        function handleGlobalKeydown(event) {
            // Handle keyboard navigation for courses grid
            if (event.target.id === 'courses-grid') {
                try {
                    if (event.key === 'ArrowLeft') {
                        event.preventDefault();
                        CourseManager.scrollCourses('left');
                    } else if (event.key === 'ArrowRight') {
                        event.preventDefault();
                        CourseManager.scrollCourses('right');
                    }
                } catch (error) {
                    console.error('Keyboard navigation error:', error);
                }
            }
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeApp, { passive: true });
        } else {
            initializeApp();
        }
    </script>

    <script defer>
        // Load Bootstrap with enhanced error handling
        (function() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
            script.defer = true;
            script.onload = function() {
                console.log('Bootstrap loaded successfully');
            };
            script.onerror = function() {
                console.warn('Failed to load Bootstrap JavaScript - fallback mode');
                document.body.classList.add('no-bootstrap');
            };
            document.head.appendChild(script);
        })();
    </script>
</body>
</html>