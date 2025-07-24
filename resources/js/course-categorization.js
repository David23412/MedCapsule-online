/**
 * Course Categorization System
 * 
 * This script initializes the dynamic categorization system for course topics.
 */

// Import components
import DynamicCategorization from './components/DynamicCategorization';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeCategorization();
});

/**
 * Initialize the categorization system
 */
function initializeCategorization() {
    // Get course data from the page
    const courseDataElement = document.getElementById('course-data');
    if (!courseDataElement) {
        console.error('Course data element not found');
        return;
    }

    try {
        // Parse course data
        const courseData = JSON.parse(courseDataElement.textContent);
        const { courseType, topics } = courseData;

        // Initialize categorization system
        const categorization = new DynamicCategorization(
            courseType,
            topics,
            'categorization-pills',
            'topics-container',
            {
                debounceTime: 100,
                animationDuration: 300,
                allCategoryText: 'All Topics',
                emptyStateMessage: 'No topics found in this category',
                urlParam: 'category'
            }
        );

        // Make categorization instance available globally for debugging
        window.courseCategorization = categorization;

        // Add scroll behavior for pills container
        addScrollBehavior();

        // Add ripple effect to pills
        addRippleEffect();

    } catch (error) {
        console.error('Error initializing categorization:', error);
    }
}

/**
 * Add scroll behavior to pills container
 */
function addScrollBehavior() {
    const pillsContainer = document.getElementById('categorization-pills');
    if (!pillsContainer) return;

    const containerTop = pillsContainer.offsetTop;

    window.addEventListener('scroll', () => {
        if (window.scrollY > containerTop) {
            pillsContainer.classList.add('scrolled');
        } else {
            pillsContainer.classList.remove('scrolled');
        }
    });
}

/**
 * Add ripple effect to pills
 */
function addRippleEffect() {
    document.addEventListener('click', function (e) {
        const target = e.target;

        if (target.classList.contains('categorization-pill')) {
            const rect = target.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;

            target.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
    });
}

/**
 * Refresh categorization with new data
 * 
 * @param {Array} topics - New topics array
 * @param {string} courseType - New course type
 */
function refreshCategorization(topics, courseType) {
    if (window.courseCategorization) {
        window.courseCategorization.refresh(topics, courseType);
    }
}

// Export for module bundlers
export { refreshCategorization };