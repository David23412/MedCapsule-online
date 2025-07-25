/**
 * Topic Filters Module
 * Handles filter pill functionality with exact original behavior
 */

const TopicFilters = {
    init: function() {
        // Case filter functionality
        const caseFilters = document.querySelectorAll('.case-filter');
        const topicCards = document.querySelectorAll('.topic-card');
        
        // Assignment cards are now always visible - no hiding on page load

        caseFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Remove active class from all filters
                caseFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked filter
                this.classList.add('active');
                
                // Get the filter type
                const filterType = this.dataset.filter || this.textContent.trim().toLowerCase();
                
                // Get the topics container
                const topicsContainer = document.querySelector('.topics-container');
                
                // Add/remove assignments filter class
                if (filterType === 'assignments') {
                    topicsContainer.classList.add('assignments-filter-active');
                } else {
                    topicsContainer.classList.remove('assignments-filter-active');
                }
                
                // Filter topic cards
                topicCards.forEach(card => {
                    const caseType = card.dataset.caseType;
                    const hasAssignment = card.dataset.hasAssignment === 'true';
                    
                    let shouldShow = false;
                    
                    if (filterType === 'assignments') {
                        // Only show assignment cards
                        shouldShow = hasAssignment;
                    } else if (filterType === 'all') {
                        // Show ALL cards - both regular topics AND assignment cards
                        shouldShow = true;
                    } else {
                        // Dynamic filtering based on case type (show both regular and assignment cards that match)
                        shouldShow = caseType && caseType.toLowerCase() === filterType.toLowerCase();
                    }
                    
                    card.style.display = shouldShow ? 'block' : 'none';
                });
            });
        });
        
        // Handle assignment icon circle clicks - exact same functionality as assignment pill
        const assignmentIconCircle = document.querySelector('.assignment-icon-circle');
        const assignmentCircle = document.querySelector('.assignment-circle');
        
        if (assignmentIconCircle && assignmentCircle) {
            assignmentIconCircle.addEventListener('click', function() {
                // Remove active class from all filters
                caseFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to the assignment circle
                assignmentCircle.classList.add('active');
                
                // Get the topics container
                const topicsContainer = document.querySelector('.topics-container');
                topicsContainer.classList.add('assignments-filter-active');
                
                // Filter topic cards to show only assignments
                topicCards.forEach(card => {
                    const hasAssignment = card.dataset.hasAssignment === 'true';
                    card.style.display = hasAssignment ? 'block' : 'none';
                });
            });
        }
        
        // Also handle clicks on other filters to remove active state from assignment circle
        caseFilters.forEach(filter => {
            const originalClickHandler = filter.onclick;
            filter.addEventListener('click', function() {
                if (assignmentCircle) {
                    assignmentCircle.classList.remove('active');
                }
            });
        });
    }
};