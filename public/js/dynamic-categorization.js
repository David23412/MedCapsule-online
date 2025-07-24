/**
 * Dynamic Categorization System
 * 
 * This class handles the dynamic generation and filtering of categorization pills
 * based on course type and available topics.
 */
class DynamicCategorization {
    /**
     * Initialize the dynamic categorization system
     * 
     * @param {string} courseType - The type of course ('medical' or 'custom')
     * @param {Array} topics - Array of topic objects
     * @param {string} containerId - ID of the container element for the pills
     */
    constructor(courseType, topics, containerId) {
        this.courseType = courseType || 'medical'; // Default to medical for backward compatibility
        this.topics = topics || [];
        this.container = document.getElementById(containerId);
        this.activeFilter = 'all';

        // Fixed pills that should always be present
        this.fixedPills = ['random', 'review-mistakes'];

        // Initialize the system
        this.init();
    }

    /**
     * Initialize the categorization system
     */
    init() {
        // Generate pills based on course type
        if (this.courseType === 'medical') {
            this.generateMedicalPills();
        } else {
            this.generateCustomPills();
        }

        // Set up event listeners
        this.setupEventListeners();

        // Load filter from URL if present
        this.loadFromURL();
    }

    /**
     * Generate pills for medical courses
     * Only show pills for categories that have topics
     */
    generateMedicalPills() {
        // Clear existing pills except fixed ones
        this.clearDynamicPills();

        // Create "All" pill
        this.createAllPill();

        // Check which medical categories have topics
        const categories = ['quiz', 'cases', 'practical'];
        const categoryCounts = {};

        // Count topics per category
        categories.forEach(category => {
            categoryCounts[category] = this.topics.filter(topic =>
                topic.case_type && topic.case_type.toLowerCase() === category
            ).length;
        });

        // Create pills only for categories with topics
        categories.forEach(category => {
            if (categoryCounts[category] > 0) {
                this.createCategoryPill(category, categoryCounts[category]);
            }
        });

        // Add assignment pill if needed
        this.addAssignmentPillIfNeeded();

        // Add fixed pills (Random and Review Mistakes)
        this.addFixedPills();
    }

    /**
     * Generate pills for custom courses
     * Dynamically based on what exists in the database
     */
    generateCustomPills() {
        // Clear existing pills except fixed ones
        this.clearDynamicPills();

        // Create "All" pill
        this.createAllPill();

        // Get unique categories from topics
        const categories = [...new Set(
            this.topics
                .map(topic => topic.case_type)
                .filter(Boolean) // Remove null/undefined
        )];

        // Count topics per category
        const categoryCounts = {};
        categories.forEach(category => {
            categoryCounts[category] = this.topics.filter(topic =>
                topic.case_type === category
            ).length;
        });

        // Create pills for each category
        categories.forEach(category => {
            this.createCategoryPill(category, categoryCounts[category]);
        });

        // Add assignment pill if needed
        this.addAssignmentPillIfNeeded();

        // Add fixed pills (Random and Review Mistakes)
        this.addFixedPills();
    }

    /**
     * Clear all dynamic pills from the container
     * Preserves fixed pills like Random and Review Mistakes
     */
    clearDynamicPills() {
        if (!this.container) return;

        // Remove all pills except fixed ones
        const pills = this.container.querySelectorAll('.case-filter:not([data-fixed="true"])');
        pills.forEach(pill => pill.remove());
    }

    /**
     * Create the "All" pill
     */
    createAllPill() {
        if (!this.container) return;

        const allPill = document.createElement('div');
        allPill.className = 'case-filter active';
        allPill.dataset.filter = 'all';
        allPill.innerHTML = `
            <i class="fas fa-th-large"></i>
            All
            <span class="count">${this.topics.length}</span>
        `;

        // Insert at the beginning
        this.container.prepend(allPill);
    }

    /**
     * Create a pill for a specific category
     * 
     * @param {string} category - The category name
     * @param {number} count - Number of topics in this category
     */
    createCategoryPill(category, count) {
        if (!this.container) return;

        const pill = document.createElement('div');
        pill.className = 'case-filter';
        pill.dataset.filter = category.toLowerCase();

        // Choose appropriate icon based on category
        let icon = 'fa-bookmark';
        if (category.toLowerCase() === 'quiz') icon = 'fa-question-circle';
        if (category.toLowerCase() === 'cases') icon = 'fa-file-alt';
        if (category.toLowerCase() === 'practical') icon = 'fa-flask';

        pill.innerHTML = `
            <i class="fas ${icon}"></i>
            ${category.charAt(0).toUpperCase() + category.slice(1)}
            <span class="count">${count}</span>
        `;

        // Add after the "All" pill but before fixed pills
        const fixedPills = this.container.querySelectorAll('[data-fixed="true"]');
        if (fixedPills.length > 0) {
            this.container.insertBefore(pill, fixedPills[0]);
        } else {
            this.container.appendChild(pill);
        }
    }

    /**
     * Add assignment pill if there are assignments
     */
    addAssignmentPillIfNeeded() {
        // Check if assignments exist
        const hasAssignments = this.topics.some(topic => topic.has_assignment);

        if (hasAssignments) {
            const assignmentCount = this.topics.filter(topic => topic.has_assignment).length;

            const pill = document.createElement('div');
            pill.className = 'case-filter assignment-filter';
            pill.dataset.filter = 'assignments';
            pill.innerHTML = `
                <i class="fas fa-tasks"></i>
                Assignments
                <span class="count assignment-count">${assignmentCount}</span>
            `;

            // Add before fixed pills
            const fixedPills = this.container.querySelectorAll('[data-fixed="true"]');
            if (fixedPills.length > 0) {
                this.container.insertBefore(pill, fixedPills[0]);
            } else {
                this.container.appendChild(pill);
            }
        }
    }

    /**
     * Add fixed pills (Random and Review Mistakes)
     * These pills are always present regardless of course type
     */
    addFixedPills() {
        // Check if fixed pills already exist
        const existingRandom = this.container.querySelector('.random-link');
        const existingMistakes = this.container.querySelector('.mistakes-link');

        // Don't add if they already exist
        if (existingRandom && existingMistakes) return;

        // If they don't exist, add them
        if (!existingRandom) {
            const randomPill = document.createElement('a');
            randomPill.className = 'random-link';
            randomPill.dataset.fixed = 'true';
            randomPill.href = '#';
            randomPill.dataset.bsToggle = 'modal';
            randomPill.dataset.bsTarget = '#randomQuizModal';
            randomPill.innerHTML = `
                <i class="fas fa-random"></i>
                Random
            `;
            this.container.appendChild(randomPill);
        }

        if (!existingMistakes) {
            const mistakesPill = document.createElement('a');
            mistakesPill.className = 'mistakes-link';
            mistakesPill.dataset.fixed = 'true';
            mistakesPill.href = '/review'; // Adjust as needed
            mistakesPill.innerHTML = `
                <i class="fas fa-times-circle"></i>
                Review Mistakes
            `;
            this.container.appendChild(mistakesPill);
        }
    }

    /**
     * Set up event listeners for pill clicking
     */
    setupEventListeners() {
        if (!this.container) return;

        // Add click event listeners to all pills
        this.container.addEventListener('click', (event) => {
            const pill = event.target.closest('.case-filter');
            if (!pill) return;

            // Skip if it's a fixed pill
            if (pill.dataset.fixed === 'true') return;

            // Remove active class from all pills
            this.container.querySelectorAll('.case-filter').forEach(p => {
                p.classList.remove('active');
            });

            // Add active class to clicked pill
            pill.classList.add('active');

            // Get filter type
            const filterType = pill.dataset.filter || pill.textContent.trim().toLowerCase();
            this.activeFilter = filterType;

            // Filter topics
            this.filterTopics(filterType);

            // Update URL
            this.updateURL(filterType);
        });
    }

    /**
     * Filter topics based on selected pill
     * 
     * @param {string} filterType - The filter to apply
     */
    filterTopics(filterType) {
        const topicCards = document.querySelectorAll('.topic-card');

        // Use requestAnimationFrame for smooth filtering
        requestAnimationFrame(() => {
            topicCards.forEach(card => {
                const caseType = card.dataset.caseType;
                const hasAssignment = card.dataset.hasAssignment === 'true';

                let shouldShow = false;

                if (filterType === 'assignments') {
                    shouldShow = hasAssignment;
                } else if (filterType === 'all') {
                    shouldShow = true;
                } else {
                    // Dynamic filtering based on case type
                    shouldShow = caseType && caseType.toLowerCase() === filterType.toLowerCase();
                }

                // Apply display change with smooth transition
                if (shouldShow) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300); // Match transition duration
                }
            });
        });
    }

    /**
     * Update URL with current filter for bookmarking
     * 
     * @param {string} filterType - The active filter
     */
    updateURL(filterType) {
        if (history.pushState) {
            const url = new URL(window.location.href);

            if (filterType === 'all') {
                url.searchParams.delete('filter');
            } else {
                url.searchParams.set('filter', filterType);
            }

            window.history.pushState({ path: url.toString() }, '', url.toString());
        }
    }

    /**
     * Load filter from URL parameters
     */
    loadFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const filter = urlParams.get('filter');

        if (filter) {
            // Find the pill with this filter
            const pill = this.container.querySelector(`.case-filter[data-filter="${filter}"]`);

            if (pill) {
                // Remove active class from all pills
                this.container.querySelectorAll('.case-filter').forEach(p => {
                    p.classList.remove('active');
                });

                // Add active class to this pill
                pill.classList.add('active');

                // Apply the filter
                this.filterTopics(filter);
                this.activeFilter = filter;
            }
        }
    }
}

// DISABLED - This was interfering with Blade template logic
// The categorization is now handled directly in the Blade template
console.log('Dynamic categorization JavaScript disabled - using Blade template logic instead');