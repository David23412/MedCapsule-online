/**
 * OptimizedTopicFilter.js
 * 
 * A high-performance topic filtering system with debouncing and requestAnimationFrame
 * optimization for smooth animations and minimal layout shifts.
 */
class OptimizedTopicFilter {
    /**
     * Constructor
     * 
     * @param {Object} options - Configuration options
     */
    constructor(options = {}) {
        this.options = {
            debounceTime: 100, // Debounce time in ms
            animationDuration: 300, // Animation duration in ms
            staggerDelay: 10, // Delay between animations in ms
            useSkeletons: true, // Whether to use skeleton screens during loading
            ...options
        };
        
        this.debounceTimer = null;
        this.animationFrame = null;
        this.isFiltering = false;
    }
    
    /**
     * Filter elements with optimized performance
     * 
     * @param {NodeList|Array} elements - Elements to filter
     * @param {Function} filterFn - Function that returns true for elements to show
     * @param {Function} callback - Optional callback after filtering completes
     */
    filter(elements, filterFn, callback = null) {
        // Cancel any pending operations
        this.cancelPending();
        
        // Start filtering
        this.isFiltering = true;
        
        // Use requestAnimationFrame for smooth animations
        this.animationFrame = requestAnimationFrame(() => {
            // Clear previous debounce timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Debounce filtering to improve performance
            this.debounceTimer = setTimeout(() => {
                let visibleCount = 0;
                const elementsArray = Array.from(elements);
                
                // First pass: determine visibility
                const visibilityMap = elementsArray.map(element => {
                    const shouldShow = filterFn(element);
                    if (shouldShow) visibleCount++;
                    return shouldShow;
                });
                
                // Second pass: apply visibility with staggered animations
                elementsArray.forEach((element, index) => {
                    const shouldShow = visibilityMap[index];
                    
                    if (shouldShow) {
                        // Prepare for animation
                        element.style.opacity = '0';
                        element.style.display = '';
                        
                        // Stagger animations for smoother appearance
                        setTimeout(() => {
                            element.style.opacity = '1';
                            element.style.transform = 'translateY(0)';
                        }, this.options.staggerDelay * (index % 20)); // Limit stagger to avoid too long delays
                    } else {
                        // Hide element
                        element.style.display = 'none';
                    }
                });
                
                // Filtering complete
                this.isFiltering = false;
                
                // Execute callback if provided
                if (callback && typeof callback === 'function') {
                    callback(visibleCount);
                }
            }, this.options.debounceTime);
        });
    }
    
    /**
     * Cancel any pending filter operations
     */
    cancelPending() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }
        
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }
    
    /**
     * Show loading state with skeleton screens
     * 
     * @param {HTMLElement} container - Container element
     * @param {number} count - Number of skeleton items to show
     * @param {string} skeletonClass - CSS class for skeleton items
     */
    showLoadingState(container, count = 3, skeletonClass = 'topic-skeleton') {
        if (!this.options.useSkeletons) return;
        
        // Clear container
        container.innerHTML = '';
        
        // Add skeleton items
        for (let i = 0; i < count; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = skeletonClass;
            container.appendChild(skeleton);
        }
    }
    
    /**
     * Hide loading state
     * 
     * @param {HTMLElement} container - Container element
     * @param {string} skeletonClass - CSS class for skeleton items
     */
    hideLoadingState(container, skeletonClass = 'topic-skeleton') {
        if (!this.options.useSkeletons) return;
        
        // Remove skeleton items
        const skeletons = container.querySelectorAll(`.${skeletonClass}`);
        skeletons.forEach(skeleton => skeleton.remove());
    }
    
    /**
     * Check if filtering is in progress
     * 
     * @returns {boolean} Whether filtering is in progress
     */
    isActive() {
        return this.isFiltering;
    }
}

// Export for module bundlers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OptimizedTopicFilter;
}