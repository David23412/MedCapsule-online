<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Review Mistakes - MedCapsule</title>
    
    <!-- PERFORMANCE: Critical CSS first -->
    <style>
        /* CRITICAL PATH CSS - Instant rendering */
        html { font-size: 12.8px; }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f6f9fc, #f1f4f8);
            margin: 0; padding: 0; min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeSpeed;
        }
        .main-content { margin-top: 80px; padding: 2rem; }
        .loading-skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%; animation: loading 1.5s infinite;
        }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .fade-in { opacity: 0; transform: translateY(20px) translateZ(0); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .fade-in.visible { opacity: 1; transform: translateY(0) translateZ(0); }
        .mistake-card { will-change: transform; transform: translateZ(0); }
    </style>

    <!-- PERFORMANCE: Async font loading -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- PERFORMANCE: Defer non-critical CSS -->
    <link rel="stylesheet" href="{{ asset('css/optimized-mistakes.css') }}" media="print" onload="this.media='all'">
</head>
<body>
    <!-- PERFORMANCE: Instant navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="{{ route('courses') }}">MedCapsule</a>
            
            <!-- PERFORMANCE: User profile with lazy loading -->
            <div class="navbar-nav ms-auto">
                <div class="profile-placeholder" id="profile-container">
                    <div class="loading-skeleton" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="main-content">
        <div class="container">
            <!-- Header section -->
            <div class="header-section fade-in">
                <h1>Review Your Mistakes</h1>
                <a href="{{ route('course.mistakes.practice') }}" class="learn-mistakes-btn">
                    <i class="fas fa-graduation-cap"></i>
                    Practice Mistakes
                </a>
            </div>

            <!-- PERFORMANCE: Filter controls -->
            <div class="filter-controls fade-in" id="filter-controls">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <select id="courseFilter" class="form-select">
                            <option value="">All Courses</option>
                            <!-- Options loaded via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="topicFilter" class="form-select">
                            <option value="">All Topics</option>
                            <!-- Options loaded via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="difficultyFilter" class="form-select">
                            <option value="">All Difficulties</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                
                <!-- PERFORMANCE: Quick stats -->
                <div class="stats-row mb-4" id="stats-row">
                    <div class="stat-card">
                        <i class="fas fa-times-circle"></i>
                        <span class="stat-number" id="total-mistakes">0</span>
                        <span class="stat-label">Total Mistakes</span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <span class="stat-number" id="improvement-rate">0%</span>
                        <span class="stat-label">Improvement</span>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <span class="stat-number" id="avg-time">0s</span>
                        <span class="stat-label">Avg Time</span>
                    </div>
                </div>
            </div>

            <!-- PERFORMANCE: Mistakes container -->
            <div class="mistakes-container" id="mistakes-container">
                <!-- Initial loading skeleton -->
                @for($i = 0; $i < 3; $i++)
                    <div class="mistake-skeleton">
                        <div class="loading-skeleton" style="height: 200px; border-radius: 24px; margin-bottom: 2rem;"></div>
                    </div>
                @endfor
            </div>

            <!-- PERFORMANCE: Load more button -->
            <div class="load-more-container text-center" id="load-more-container" style="display: none;">
                <button class="btn btn-primary load-more-btn" id="load-more-btn">
                    <i class="fas fa-plus"></i>
                    Load More Mistakes
                </button>
            </div>

            <!-- PERFORMANCE: Empty state -->
            <div class="empty-state" id="empty-state" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <h3>No Mistakes Found!</h3>
                <p>You haven't made any mistakes yet, or you've learned from all of them.</p>
                <a href="{{ route('courses') }}" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Continue Learning
                </a>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE: Toast notifications -->
    <div class="toast-container" id="toast-container"></div>

    <!-- PERFORMANCE: Load JavaScript after critical content -->
    <script>
        // PERFORMANCE: Global data and caching
        window.mistakesData = {
            mistakes: @json($mistakes ?? []),
            courses: @json($courses ?? []),
            currentPage: 1,
            hasMore: true,
            isLoading: false,
            filters: {
                course: '',
                topic: '',
                difficulty: ''
            }
        };
        
        window.csrfToken = '{{ csrf_token() }}';

        // PERFORMANCE: Smart cache with TTL
        const MistakeCache = {
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

        // PERFORMANCE: Instant mistake rendering
        function renderMistakes(mistakes, append = false) {
            const container = document.getElementById('mistakes-container');
            
            if (!append) {
                container.innerHTML = '';
            }

            if (!mistakes || mistakes.length === 0) {
                if (!append) {
                    document.getElementById('empty-state').style.display = 'block';
                    document.getElementById('load-more-container').style.display = 'none';
                }
                return;
            }

            document.getElementById('empty-state').style.display = 'none';

            // PERFORMANCE: Use DocumentFragment for batch DOM updates
            const fragment = document.createDocumentFragment();

            mistakes.forEach((mistake, index) => {
                const mistakeCard = createMistakeCard(mistake);
                mistakeCard.style.setProperty('--animation-delay', `${index * 100}ms`);
                fragment.appendChild(mistakeCard);
            });

            // PERFORMANCE: Single reflow
            container.appendChild(fragment);

            // PERFORMANCE: Trigger animations with RAF
            requestAnimationFrame(() => {
                container.querySelectorAll('.mistake-card:not(.visible)').forEach(card => {
                    card.classList.add('visible');
                });
            });

            // Update load more button
            const loadMoreContainer = document.getElementById('load-more-container');
            loadMoreContainer.style.display = window.mistakesData.hasMore ? 'block' : 'none';
        }

        // PERFORMANCE: Optimized mistake card creation
        function createMistakeCard(mistake) {
            const card = document.createElement('div');
            card.className = 'mistake-card fade-in';
            card.setAttribute('data-mistake-id', mistake.id);
            card.style.animationDelay = 'var(--animation-delay)';

            // PERFORMANCE: Template string for better performance
            card.innerHTML = `
                <button class="remove-question" onclick="removeMistake(${mistake.id})" title="Remove from mistakes">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="question-section">
                    <div class="question-text">${mistake.question}</div>
                    
                    ${mistake.image_url ? `
                        <img src="${mistake.image_url}" 
                             alt="Question image" 
                             class="question-image"
                             loading="lazy"
                             onerror="this.style.display='none'">
                    ` : ''}

                    <div class="options">
                        ${['A', 'B', 'C', 'D'].map(option => {
                            const optionText = mistake[`option_${option.toLowerCase()}`];
                            const isCorrect = mistake.correct_answer === option;
                            const isSelected = mistake.user_answer === option;
                            
                            let className = 'form-check-label';
                            if (isCorrect) className += ' correct';
                            else if (isSelected) className += ' incorrect';
                            
                            return `
                                <div class="form-check">
                                    <input type="radio" disabled>
                                    <label class="${className}">
                                        ${option}. ${optionText}
                                    </label>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <div class="info-boxes">
                    <div class="info-box topic-badge">
                        <i class="fas fa-tag"></i>
                        <span class="info-box-label">${mistake.course_name}</span>
                    </div>
                    <div class="info-box time-badge">
                        <i class="fas fa-clock"></i>
                        <span class="info-box-label">${formatTimeAgo(mistake.created_at)}</span>
                    </div>
                </div>
            `;

            return card;
        }

        // PERFORMANCE: Instant filtering with debouncing
        let filterTimeout;
        function handleFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(async () => {
                // Update filters
                window.mistakesData.filters = {
                    course: document.getElementById('courseFilter').value,
                    topic: document.getElementById('topicFilter').value,
                    difficulty: document.getElementById('difficultyFilter').value
                };

                // Reset pagination
                window.mistakesData.currentPage = 1;
                window.mistakesData.hasMore = true;

                // Show loading
                showLoadingState();

                try {
                    const filteredMistakes = await loadMistakes(false);
                    renderMistakes(filteredMistakes);
                    updateStats(filteredMistakes);
                } catch (error) {
                    console.error('Filter error:', error);
                    showToast('Error filtering mistakes', 'error');
                }
            }, 300); // Instant debounce
        }

        // PERFORMANCE: Async mistake loading
        async function loadMistakes(append = false) {
            if (window.mistakesData.isLoading) return [];
            
            window.mistakesData.isLoading = true;
            
            // Check cache first
            const cacheKey = `mistakes_${JSON.stringify(window.mistakesData.filters)}_page_${window.mistakesData.currentPage}`;
            let cachedData = MistakeCache.get(cacheKey);
            
            if (cachedData) {
                window.mistakesData.isLoading = false;
                return cachedData.mistakes;
            }

            try {
                const params = new URLSearchParams({
                    page: window.mistakesData.currentPage,
                    ...window.mistakesData.filters
                });

                const response = await fetch(`/mistakes/api?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    // Cache the result
                    MistakeCache.set(cacheKey, data, 60000); // 1 minute cache
                    
                    // Update state
                    window.mistakesData.hasMore = data.has_more;
                    
                    return data.mistakes;
                } else {
                    throw new Error(data.message || 'Failed to load mistakes');
                }
            } catch (error) {
                console.error('Load mistakes error:', error);
                showToast('Failed to load mistakes', 'error');
                return [];
            } finally {
                window.mistakesData.isLoading = false;
            }
        }

        // PERFORMANCE: Instant mistake removal
        async function removeMistake(mistakeId) {
            const card = document.querySelector(`[data-mistake-id="${mistakeId}"]`);
            if (!card) return;

            // PERFORMANCE: Instant feedback
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';

            try {
                const response = await fetch(`/mistakes/${mistakeId}/remove`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // PERFORMANCE: Smooth animation out
                    card.style.transform = 'translateX(100%) scale(0.8)';
                    card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    
                    setTimeout(() => {
                        card.remove();
                        updateStats();
                        showToast('Mistake removed successfully', 'success');
                    }, 300);

                    // Clear relevant cache
                    MistakeCache.clear();
                } else {
                    throw new Error(data.message || 'Failed to remove mistake');
                }
            } catch (error) {
                console.error('Remove mistake error:', error);
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                showToast('Failed to remove mistake', 'error');
            }
        }

        // PERFORMANCE: Load more with smooth pagination
        async function loadMore() {
            if (window.mistakesData.isLoading || !window.mistakesData.hasMore) return;

            const loadMoreBtn = document.getElementById('load-more-btn');
            const originalContent = loadMoreBtn.innerHTML;
            
            // Show loading state
            loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            loadMoreBtn.disabled = true;

            window.mistakesData.currentPage++;
            
            try {
                const mistakes = await loadMistakes(true);
                renderMistakes(mistakes, true);
            } catch (error) {
                window.mistakesData.currentPage--; // Rollback
            } finally {
                loadMoreBtn.innerHTML = originalContent;
                loadMoreBtn.disabled = false;
            }
        }

        // PERFORMANCE: Update stats instantly
        function updateStats(mistakes = null) {
            const totalElement = document.getElementById('total-mistakes');
            const improvementElement = document.getElementById('improvement-rate');
            const avgTimeElement = document.getElementById('avg-time');

            if (!mistakes) {
                // Count from current DOM
                const currentMistakes = document.querySelectorAll('.mistake-card').length;
                totalElement.textContent = currentMistakes;
                return;
            }

            // Update with actual data
            totalElement.textContent = mistakes.length;
            
            // Calculate improvement rate (mock calculation)
            const improvementRate = Math.max(0, 100 - (mistakes.length * 2));
            improvementElement.textContent = `${improvementRate}%`;
            
            // Calculate average time (mock calculation)
            const avgTime = Math.floor(Math.random() * 30) + 15;
            avgTimeElement.textContent = `${avgTime}s`;
        }

        // PERFORMANCE: Show loading state
        function showLoadingState() {
            const container = document.getElementById('mistakes-container');
            container.innerHTML = `
                <div class="mistake-skeleton">
                    <div class="loading-skeleton" style="height: 200px; border-radius: 24px; margin-bottom: 2rem;"></div>
                </div>
                <div class="mistake-skeleton">
                    <div class="loading-skeleton" style="height: 200px; border-radius: 24px; margin-bottom: 2rem;"></div>
                </div>
                <div class="mistake-skeleton">
                    <div class="loading-skeleton" style="height: 200px; border-radius: 24px; margin-bottom: 2rem;"></div>
                </div>
            `;
        }

        // PERFORMANCE: Toast notifications
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                <span>${message}</span>
            `;
            
            toastContainer.appendChild(toast);
            
            // Show with animation
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            });
            
            // Auto remove
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // PERFORMANCE: Format time ago
        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'Today';
            if (diffDays === 1) return 'Yesterday';
            if (diffDays < 7) return `${diffDays} days ago`;
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
            return `${Math.floor(diffDays / 30)} months ago`;
        }

        // PERFORMANCE: Load filters
        async function loadFilters() {
            try {
                const response = await fetch('/mistakes/filters', {
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Populate course filter
                    const courseFilter = document.getElementById('courseFilter');
                    data.courses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.name;
                        courseFilter.appendChild(option);
                    });
                    
                    // Populate topic filter
                    const topicFilter = document.getElementById('topicFilter');
                    data.topics.forEach(topic => {
                        const option = document.createElement('option');
                        option.value = topic.id;
                        option.textContent = topic.name;
                        topicFilter.appendChild(option);
                    });
                }
            } catch (error) {
                console.warn('Failed to load filters:', error);
            }
        }

        // PERFORMANCE: Initialize animations
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

        async function init() {
            // PERFORMANCE: Initialize in priority order
            initializeAnimations();
            
            // Setup event listeners
            document.getElementById('courseFilter').addEventListener('change', handleFilter);
            document.getElementById('topicFilter').addEventListener('change', handleFilter);
            document.getElementById('difficultyFilter').addEventListener('change', handleFilter);
            document.getElementById('load-more-btn').addEventListener('click', loadMore);
            
            // Load initial data
            try {
                const mistakes = await loadMistakes();
                renderMistakes(mistakes);
                updateStats(mistakes);
            } catch (error) {
                console.error('Initial load failed:', error);
                showToast('Failed to load mistakes', 'error');
            }
            
            // PERFORMANCE: Load non-critical data after main content
            requestIdleCallback(() => {
                loadFilters();
            });
        }
    </script>

    <!-- PERFORMANCE: Load heavy scripts after content -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>