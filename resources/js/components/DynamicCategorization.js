/**
 * DynamicCategorization.js
 * 
 * A JavaScript class for dynamically generating and managing course topic categorization pills
 * with instant filtering capabilities.
 */
class DynamicCategorization {
    /**
     * Constructor
     * 
     * @param {string} courseType - The type of course ('medical' or 'custom')
     * @param {Array} topics - Array of topic objects
     * @param {string} containerId - The ID of the container element for pills
     * @param {string} topicsContainerId - The ID of the container element for topics
     * @param {Object} options - Additional options
     */
    constructor(courseType, topics, containerId, topicsContainerId, options = {}) {
        this.courseType = courseType || 'medical'; // Default to medical for backward compatibility
        this.topics = topics || [];
        this.containerId = containerId;
        this.topicsContainerId = topicsContainerId;
        this.container = document.getElementById(containerId);
        this.topicsContainer = document.getElementById(topicsContainerId);
        this.activePill = null;
        this.debounceTimer = null;
        this.options = {
            debounceTime: 100, // Debounce time in ms
            animationDuration: 300, // Animation duration in ms
            allCategoryText: 'All', // Text for the "All" pill
            emptyStateMessage: 'No topics found for this category', // Message when no topics are found
            urlParam: 'category', // URL parameter name for bookmarking
            ...options
        };
        
        // Initialize
        this.init();
    }
    
    /**
     * Initialize the categorization system
     */
    init() {
        if (!this.container || !this.topicsContainer) {
            console.error('Container elements not found');
            return;
        }
        
        // Generate pills based on course type
        this.generatePills();
        
        // Load from URL if applicable
        this.loadFromURL();
        
        // Add event listener for URL changes
        window.addEventListener('popstate', () => this.loadFromURL());
    }
    
    /**
     * Generate categorization pills based on course type
     */
    generatePills() {
        // Clear existing pills
        this.container.innerHTML = '';
        
        // Create pills based on course type
        if (this.courseType === 'medical') {
            this.generateMedicalPills();
        } else {
            this.generateCustomPills();
        }
    }
    
    /**
     * Generate pills for medical courses
     * Only shows pills for categories that have topics
     */
    generateMedicalPills() {
        const medicalCategories = ['Quiz', 'Cases', 'Practical'];
        const existingCategories = this.getExistingCategories();
        
        // Create "All" pill first
        this.createPill(this.options.allCategoryText, true);
        
        // Create pills only for categories that have topics
        medicalCategories.forEach(category => {
            if (existingCategories.includes(category)) {
                this.createPill(category);
            }
        });
    }
    
    /**
     * Generate pills for custom courses
     * Dynamically generates pills based on existing categorizations
     */
    generateCustomPills() {
        const existingCategories = this.getExistingCategories();
        
        // If no categories exist, don't show any pills
        if (existingCategories.length === 0) {
            return;
        }
        
        // Create "All" pill first
        this.createPill(this.options.allCategoryText, true);
        
        // Create pills for each unique category
        existingCategories.forEach(category => {
            this.createPill(category);
        });
    }
    
    /**
     * Get existing categories from topics
     * 
     * @returns {Array} Array of unique categories
     */
    getExistingCategories() {
        // Extract unique categories from topics
        const categories = this.topics
            .filter(topic => !topic.is_locked) // Exclude locked topics
            .map(topic => topic.case_type)
            .filter(category => category && category.trim() !== ''); // Filter out empty categories
        
        // Return unique categories
        return [...new Set(categories)].sort();
    }
    
    /**
     * Create a pill element
     * 
     * @param {string} category - The category name
     * @param {boolean} isActive - Whether the pill should be active by default
     */
    createPill(category, isActive = false) {
        const pill = document.createElement('button');
        pill.className = 'categorization-pill';
        pill.dataset.category = category;
        pill.textContent = category;
        
        // Add count badge if not "All"
        if (category !== this.options.allCategoryText) {
            const count = this.getTopicCountForCategory(category);
            if (count > 0) {
                const badge = document.createElement('span');
                badge.className = 'pill-count';
                badge.textContent = count;
                pill.appendChild(badge);
            } else {
                // Don't create pills for empty categories
                return;
            }
        }
        
        // Set active state
        if (isActive) {
            pill.classList.add('active');
            this.activePill = pill;
        }
        
        // Add click event
        pill.addEventListener('click', () => this.handlePillClick(pill));
        
        // Add to container
        this.container.appendChild(pill);
    }
    
    /**
     * Handle pill click event
     * 
     * @param {HTMLElement} pill - The clicked pill element
     */
    handlePillClick(pill) {
        // Don't do anything if the pill is already active
        if (pill === this.activePill) {
            return;
        }
        
        // Update active pill
        if (this.activePill) {
            this.activePill.classList.remove('active');
        }
        pill.classList.add('active');
        this.activePill = pill;
        
        // Filter topics
        const category = pill.dataset.category;
        this.filterTopics(category);
        
        // Update URL
        this.updateURL(category);
    }
    
    /**
     * Filter topics based on selected category
     * 
     * @param {string} category - The selected category
     */
    filterTopics(category) {
        // Use requestAnimationFrame for smooth animations
        requestAnimationFrame(() => {
            // Clear previous debounce timer
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Debounce filtering to improve performance
            this.debounceTimer = setTimeout(() => {
                const topicElements = this.topicsContainer.querySelectorAll('.topic-card');
                let visibleCount = 0;
                
                topicElements.forEach(topicElement => {
                    const topicCategory = topicElement.dataset.category;
                    const isLocked = topicElement.dataset.locked === 'true';
                    
                    // Skip locked topics
                    if (isLocked) {
                        topicElement.style.display = 'none';
                        return;
                    }
                    
                    // Show all topics or only those matching the category
                    if (category === this.options.allCategoryText || topicCategory === category) {
                        // Animate showing
                        topicElement.style.opacity = '0';
                        topicElement.style.display = 'block';
                        
                        // Stagger animations for smoother appearance
                        setTimeout(() => {
                            topicElement.style.opacity = '1';
                        }, 10 * visibleCount);
                        
                        visibleCount++;
                    } else {
                        // Hide non-matching topics
                        topicElement.style.display = 'none';
                    }
                });
                
                // Show empty state message if no topics are visible
                this.toggleEmptyState(visibleCount === 0);
            }, this.options.debounceTime);
        });
    }
    
    /**
     * Update URL with selected category for bookmarking
     * 
     * @param {string} category - The selected category
     */
    updateURL(category) {
        // Don't update URL for "All" category
        if (category === this.options.allCategoryText) {
            // Remove parameter from URL
            const url = new URL(window.location);
            url.searchParams.delete(this.options.urlParam);
            window.history.pushState({}, '', url);
            return;
        }
        
        // Update URL with category parameter
        const url = new URL(window.location);
        url.searchParams.set(this.options.urlParam, this.slugify(category));
        window.history.pushState({}, '', url);
    }
    
    /**
     * Load category from URL parameters
     */
    loadFromURL() {
        const url = new URL(window.location);
        const categorySlug = url.searchParams.get(this.options.urlParam);
        
        if (!categorySlug) {
            // No category in URL, select "All" pill
            const allPill = this.container.querySelector(`[data-category="${this.options.allCategoryText}"]`);
            if (allPill) {
                this.handlePillClick(allPill);
            }
            return;
        }
        
        // Find pill matching the slug
        const pills = this.container.querySelectorAll('.categorization-pill');
        let found = false;
        
        pills.forEach(pill => {
            const category = pill.dataset.category;
            if (this.slugify(category) === categorySlug) {
                this.handlePillClick(pill);
                found = true;
            }
        });
        
        // If no matching pill found, default to "All"
        if (!found) {
            const allPill = this.container.querySelector(`[data-category="${this.options.allCategoryText}"]`);
            if (allPill) {
                this.handlePillClick(allPill);
            }
        }
    }
    
    /**
     * Get topic count for a specific category
     * 
     * @param {string} category - The category to count topics for
     * @returns {number} The number of topics in the category
     */
    getTopicCountForCategory(category) {
        return this.topics.filter(topic => 
            topic.case_type === category && !topic.is_locked
        ).length;
    }
    
    /**
     * Toggle empty state message
     * 
     * @param {boolean} isEmpty - Whether to show the empty state
     */
    toggleEmptyState(isEmpty) {
        // Remove existing empty state
        const existingEmptyState = this.topicsContainer.querySelector('.empty-state');
        if (existingEmptyState) {
            existingEmptyState.remove();
        }
        
        // Add empty state if needed
        if (isEmpty) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.textContent = this.options.emptyStateMessage;
            this.topicsContainer.appendChild(emptyState);
        }
    }
    
    /**
     * Convert a string to a URL-friendly slug
     * 
     * @param {string} text - The text to slugify
     * @returns {string} The slugified text
     */
    slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }
    
    /**
     * Refresh the categorization system with new data
     * 
     * @param {Array} topics - New topics array
     * @param {string} courseType - New course type
     */
    refresh(topics, courseType) {
        this.topics = topics || this.topics;
        this.courseType = courseType || this.courseType;
        this.generatePills();
        
        // Re-apply current filter if any
        if (this.activePill) {
            this.filterTopics(this.activePill.dataset.category);
        } else {
            // Default to "All"
            const allPill = this.container.querySelector(`[data-category="${this.options.allCategoryText}"]`);
            if (allPill) {
                this.handlePillClick(allPill);
            }
        }
    }
}

// Export for module bundlers
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DynamicCategorization;
}