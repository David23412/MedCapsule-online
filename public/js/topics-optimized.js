/**
 * 🚀 OPTIMIZED TOPICS PAGE JAVASCRIPT - PERFORMANCE CRITICAL FIXES
 * 
 * CRITICAL OPTIMIZATIONS APPLIED:
 * 1. Event delegation instead of multiple event listeners
 * 2. Debounced and throttled operations
 * 3. Efficient DOM queries with caching
 * 4. Optimized assignment countdown updates
 * 5. Memory leak prevention with proper cleanup
 */

class OptimizedTopicsPage {
    constructor() {
        this.init();
        this.setupEventDelegation();
        this.setupAssignmentCountdowns();
        this.setupModalOptimizations();
    }

    /**
     * 🚀 PERFORMANCE: Initialize with minimal DOM queries
     */
    init() {
        // Cache frequently used DOM elements
        this.topicsContainer = document.querySelector('.topics-container');
        this.caseFilters = document.querySelector('.case-filters');
        this.assignmentCountdowns = new Map(); // Cache countdown elements
        this.filterState = { active: 'all' };
        
        // Performance monitoring
        this.performanceMetrics = {
            startTime: performance.now(),
            domQueries: 0,
            eventHandlers: 0
        };
        
    }

    /**
     * 🚀 EVENT DELEGATION: Single event listener for all topic interactions
     */
    setupEventDelegation() {
        if (!this.topicsContainer) return;

        // Single delegated event listener for all topic card interactions
        this.topicsContainer.addEventListener('click', this.handleTopicInteraction.bind(this), {
            passive: false,
            capture: false
        });

        // Single delegated event listener for filter interactions
        if (this.caseFilters) {
            this.caseFilters.addEventListener('click', this.handleFilterInteraction.bind(this), {
                passive: false,
                capture: false
            });
        }

        this.performanceMetrics.eventHandlers += 2;
    }

    /**
     * 🚀 OPTIMIZED: Handle all topic card interactions with delegation
     */
    handleTopicInteraction(event) {
        const target = event.target;
        const topicCard = target.closest('.topic-card');
        
        if (!topicCard) return;

        // NOTE: Quiz button handling is now unified in the main page script
        // This prevents conflicts and ensures consistent behavior

        // Handle assignment interactions
        const assignmentBtn = target.closest('.assignment-start-btn');
        if (assignmentBtn && !assignmentBtn.disabled) {
            event.preventDefault();
            this.handleAssignmentStart(assignmentBtn, topicCard);
            return;
        }
    }

    /**
     * 🚀 OPTIMIZED: Handle filter interactions with delegation
     */
    handleFilterInteraction(event) {
        const target = event.target;
        const filter = target.closest('.case-filter, .assignment-icon-circle');
        
        if (!filter) return;

        event.preventDefault();
        
        // Get filter type
        const filterType = filter.dataset.filter || 'all';
        
        // Update filter state
        this.updateFilterState(filterType);
        
        // Apply filter with optimized DOM manipulation
        this.applyFilter(filterType);
    }

    /**
     * 🚀 PERFORMANCE: Optimized filter application using CSS classes
     */
    applyFilter(filterType) {
        // Use CSS classes instead of DOM manipulation for better performance
        const body = document.body;
        
        // Remove all filter classes
        body.classList.remove('assignments-filter-active', 'cases-filter-active', 'practical-filter-active', 'quiz-filter-active');
        
        // Add specific filter class
        if (filterType !== 'all') {
            body.classList.add(`${filterType}-filter-active`);
        }

        // Update active filter UI
        this.updateFilterUI(filterType);
        
        // Update URL without page reload (for bookmarking)
        this.updateURL(filterType);
    }

    /**
     * 🚀 OPTIMIZED: Update filter UI with minimal DOM queries
     */
    updateFilterUI(activeFilter) {
        if (!this.caseFilters) return;

        // Use single query to get all filters
        const filters = this.caseFilters.querySelectorAll('.case-filter, .assignment-icon-circle');
        
        filters.forEach(filter => {
            const filterType = filter.dataset.filter || 'all';
            const isActive = filterType === activeFilter;
            
            // Use classList toggle for better performance
            filter.classList.toggle('active', isActive);
            
            // Handle assignment circle specifically
            if (filter.classList.contains('assignment-circle')) {
                filter.classList.toggle('active', isActive);
            }
        });
    }

    /**
     * 🚀 ASSIGNMENT COUNTDOWNS: Optimized with lazy updates and visibility check
     */
    setupAssignmentCountdowns() {
        // Find all countdown elements and cache them
        const countdownElements = document.querySelectorAll('[data-deadline]');
        
        countdownElements.forEach(element => {
            const deadline = element.dataset.deadline;
            if (deadline) {
                this.assignmentCountdowns.set(element, {
                    deadline: new Date(deadline),
                    element: element,
                    lastUpdate: 0
                });
            }
        });

        if (this.assignmentCountdowns.size > 0) {
            // Start optimized countdown updates
            this.startOptimizedCountdowns();
        }
    }

    /**
     * 🚀 PERFORMANCE: Optimized countdown updates with visibility check
     */
    startOptimizedCountdowns() {
        const updateInterval = 5000; // Update every 5 seconds instead of 1 second
        
        const updateCountdowns = () => {
            // Only update if page is visible
            if (document.visibilityState !== 'visible') {
                return;
            }

            const now = Date.now();
            
            this.assignmentCountdowns.forEach((data, element) => {
                // Skip if updated recently (debouncing)
                if (now - data.lastUpdate < 4000) {
                    return;
                }

                const timeRemaining = data.deadline.getTime() - now;
                
                if (timeRemaining <= 0) {
                    // Assignment is overdue
                    this.handleOverdueAssignment(element);
                } else {
                    // Update countdown display
                    this.updateCountdownDisplay(element, timeRemaining);
                }
                
                data.lastUpdate = now;
            });
        };

        // Use setInterval with visibility check
        this.countdownInterval = setInterval(updateCountdowns, updateInterval);
        
        // Initial update
        updateCountdowns();
    }

    /**
     * 🚀 OPTIMIZED: Update countdown display with minimal DOM manipulation
     */
    updateCountdownDisplay(element, timeRemaining) {
        const countdownDisplay = element.querySelector('.countdown-display');
        if (!countdownDisplay) return;

        const hours = Math.floor(timeRemaining / (1000 * 60 * 60));
        const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

        let displayText;
        if (hours > 0) {
            displayText = `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            displayText = `${minutes}m ${seconds}s`;
        } else {
            displayText = `${seconds}s`;
        }

        // Only update if text has changed (avoid unnecessary DOM writes)
        if (countdownDisplay.textContent !== displayText) {
            countdownDisplay.textContent = displayText;
            
            // Add urgency styling for last 10 minutes
            if (timeRemaining < 600000) { // 10 minutes
                element.classList.add('countdown-urgent');
            }
        }
    }

    /**
     * 🚀 PERFORMANCE: Handle overdue assignments efficiently
     */
    handleOverdueAssignment(element) {
        const assignmentCard = element.closest('.topic-card');
        if (assignmentCard) {
            // Update assignment status
            assignmentCard.classList.add('assignment-overdue');
            assignmentCard.classList.remove('assignment-active');
            
            // Update countdown display
            const countdownDisplay = element.querySelector('.countdown-display');
            if (countdownDisplay) {
                countdownDisplay.textContent = 'Overdue';
                countdownDisplay.parentElement.classList.add('overdue');
            }
            
            // Remove from active countdowns
            this.assignmentCountdowns.delete(element);
        }
    }

    /**
     * 🚀 ASSIGNMENT START: Optimized assignment start handling
     */
    handleAssignmentStart(button, topicCard) {
        const assignmentId = topicCard.dataset.assignmentId;
        const topicId = topicCard.dataset.topicId;
        const courseId = topicCard.dataset.courseId;
        const quizUrl = button.dataset.quizUrl;

        if (quizUrl) {
            // Direct URL navigation (fastest)
            window.location.href = quizUrl;
        } else if (assignmentId && topicId && courseId) {
            // Construct assignment URL
            window.location.href = `/quiz/${courseId}/${topicId}?assignment_id=${assignmentId}`;
        } else {
        }
    }

    /**
     * 🚀 MODAL OPTIMIZATIONS: Lightweight modal handling
     */
    setupModalOptimizations() {
        // Optimize Bootstrap modals
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Prevent backdrop issues
            modal.addEventListener('show.bs.modal', () => {
                document.body.style.overflow = 'hidden';
            });
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.style.overflow = '';
            });
        });
    }

    /**
     * 🚀 URL MANAGEMENT: Update URL without page reload
     */
    updateURL(filterType) {
        if (filterType === 'all') {
            // Remove filter parameter
            const url = new URL(window.location);
            url.searchParams.delete('filter');
            window.history.replaceState({}, '', url);
        } else {
            // Add filter parameter
            const url = new URL(window.location);
            url.searchParams.set('filter', filterType);
            window.history.replaceState({}, '', url);
        }
    }

    /**
     * 🚀 FILTER STATE: Update internal filter state
     */
    updateFilterState(filterType) {
        this.filterState.active = filterType;
        
        // Store in sessionStorage for persistence
        try {
            sessionStorage.setItem('topicsFilter', filterType);
        } catch (e) {
            // Ignore storage errors
        }
    }

    /**
     * 🚀 CLEANUP: Prevent memory leaks
     */
    destroy() {
        // Clear countdown interval
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        // Clear cached elements
        this.assignmentCountdowns.clear();
        
        // Remove event listeners (if needed for SPA)
        if (this.topicsContainer) {
            this.topicsContainer.removeEventListener('click', this.handleTopicInteraction);
        }
        
        if (this.caseFilters) {
            this.caseFilters.removeEventListener('click', this.handleFilterInteraction);
        }

    }

    /**
     * 🚀 PERFORMANCE METRICS: Get performance data
     */
    getPerformanceMetrics() {
        return {
            ...this.performanceMetrics,
            totalTime: performance.now() - this.performanceMetrics.startTime,
            activeCountdowns: this.assignmentCountdowns.size,
            filterState: this.filterState.active
        };
    }
}

/**
 * 🚀 INITIALIZATION: Initialize when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize optimized topics page
    window.optimizedTopicsPage = new OptimizedTopicsPage();
    
    // Performance monitoring
    if (window.performance && window.performance.mark) {
        window.performance.mark('topics-page-ready');
    }
});

/**
 * 🚀 CLEANUP: Handle page unload
 */
window.addEventListener('beforeunload', function() {
    if (window.optimizedTopicsPage) {
        window.optimizedTopicsPage.destroy();
    }
});

/**
 * 🚀 VISIBILITY API: Pause/resume countdowns based on page visibility
 */
document.addEventListener('visibilitychange', function() {
    if (window.optimizedTopicsPage && window.optimizedTopicsPage.assignmentCountdowns.size > 0) {
        if (document.visibilityState === 'visible') {
            // Resume countdowns when page becomes visible
        } else {
            // Pause countdowns when page is hidden
        }
    }
});

/**
 * 🚀 PERFORMANCE DEBUGGING: Expose performance metrics in development
 */
if (typeof window !== 'undefined' && window.location.hostname === 'localhost') {
    window.getTopicsPerformance = function() {
        if (window.optimizedTopicsPage) {
            return window.optimizedTopicsPage.getPerformanceMetrics();
        }
        return null;
    };
}
