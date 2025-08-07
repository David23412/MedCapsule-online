/**
 * Quiz Modal Module
 * Handles source selection modal and quiz button functionality with exact original behavior
 */

// LETHAL ERROR SUPPRESSION - Import if not already loaded
if (typeof window.fetch === 'function' && !window.fetch.toString().includes('Network Error')) {
    // Load error suppression if not already active
    const script = document.createElement('script');
    script.src = '/js/lethal-error-suppression.js';
    document.head.appendChild(script);
}

const QuizModal = {
    sourceSelectionModal: null,
    
    init: function() {
        this.initModal();
        this.initQuizButtons();
        this.initModeChange();
        this.initFormSubmission();
        this.initRandomQuizModal();
    },
    
    initModal: function() {
        // Source selection modal
        try {
            const modalElement = document.getElementById('sourceSelectionModal');
// console.log('Modal element found:', modalElement !== null); // Removed for production
            if (modalElement) {
                this.sourceSelectionModal = new bootstrap.Modal(modalElement);
// console.log('✅ Modal initialized successfully'); // Removed for production
            } else {
// console.error('❌ Modal element not found'); // Removed for production
            }
        } catch (error) {
// console.error('❌ Modal initialization failed:', error); // Removed for production
        }
        
        // DEBUG: Test modal button
        const testModalBtn = document.getElementById('testModalBtn');
        if (testModalBtn) {
            testModalBtn.addEventListener('click', () => {
// console.log('🧪 Testing modal manually...'); // Removed for production
                if (this.sourceSelectionModal) {
                    this.sourceSelectionModal.show();
// console.log('✅ Manual modal test successful'); // Removed for production
                } else {
// console.error('❌ Modal not initialized for manual test'); // Removed for production
                }
            });
        }
    },
    
    initQuizButtons: function() {
        // ROBUST: Handle quiz button clicks with multiple prevention methods
// console.log('Setting up quiz button handlers...'); // Removed for production
        const quizButtons = document.querySelectorAll('.start-quiz-btn');
// console.log(`Found ${quizButtons.length} quiz buttons`); // Removed for production
        
        quizButtons.forEach((button, index) => {
// console.log(`Setting up button ${index + 1}:`, button); // Removed for production
            
            // Remove any existing href to prevent navigation
            const originalHref = button.href;
            button.removeAttribute('href');
            button.style.cursor = 'pointer';
            
            button.addEventListener('click', (e) => {
// console.log('🔥 BUTTON CLICKED!', button); // Removed for production
                
                // Multiple ways to prevent default
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Prevent any form submission
                if (e.target.form) {
                    e.target.form.onsubmit = function() { return false; };
                }
                
                const topicId = button.dataset.topicId;
                const courseId = button.dataset.courseId;
                
// console.log(`Topic ID: ${topicId}, Course ID: ${courseId}`); // Removed for production
                
                // Check if this topic is part of an assignment
                const topicCard = document.querySelector(`[data-topic-id="${topicId}"]`);
                const hasAssignment = topicCard && topicCard.dataset.hasAssignment === 'true';
                
                if (hasAssignment) {
                    // Topic is assigned - redirect directly without showing source modal
// console.log(`Assignment topic ${topicId}: Bypassing source modal`); // Removed for production
                    window.location.href = originalHref;
                    return false;
                }
                
                // Regular topic - show source selection modal
// console.log(`Regular topic ${topicId}: Showing source selection modal`); // Removed for production
// console.log('Modal object:', this.sourceSelectionModal); // Removed for production
// console.log('Modal element exists:', document.getElementById('sourceSelectionModal') !== null); // Removed for production
                
                if (!topicId || !courseId) {
// console.error('❌ Missing topic or course ID'); // Removed for production
                    return false;
                }
                
                if (!this.sourceSelectionModal) {
// console.error('❌ Modal not initialized, redirecting directly'); // Removed for production
                    window.location.href = originalHref;
                    return false;
                }
                
                try {
                    // Set form values
                    document.getElementById('source_topic_id').value = topicId;
                    document.getElementById('source_course_id').value = courseId;
                    document.getElementById('sourceSelectionForm').action = originalHref;
                    
                    // Show modal immediately
// console.log('About to show modal...'); // Removed for production
                    this.sourceSelectionModal.show();
// console.log('Modal show() called'); // Removed for production
                    
                    // Load sources in background
                    const sourcesContainer = document.querySelector('.sources-container');
                    if (sourcesContainer) {
                        sourcesContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading sources...</p></div>';
                    }
                    
                    const selectedMode = document.querySelector('input[name="quiz_mode"]:checked')?.value || 'quiz';
                    
                    fetch(`/topics/${topicId}/source-progress?mode=${selectedMode}`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
// console.log('ℹ️ Source progress request returned non-OK status, using fallback'); // Removed for production
                            return { success: false, progress: {}, availableSources: [] };
                        }
                        return response.json().catch(() => {
// console.log('ℹ️ Failed to parse source progress JSON, using fallback'); // Removed for production
                            return { success: false, progress: {}, availableSources: [] };
                        });
                    })
                    .then(data => {
                        // Sources loaded or fallback data
                        if (typeof this.updateSourcesContainer === 'function') {
                            this.updateSourcesContainer(data);
                        }
                    })
                    .catch(error => {
// console.log('ℹ️ Source loading encountered an issue, using fallback display'); // Removed for production
                        if (sourcesContainer) {
                            sourcesContainer.innerHTML = '<div class="alert alert-info">Source details unavailable. You can still start the quiz.</div>';
                        }
                    });
                    
                } catch (error) {
// console.error('❌ Handler error:', error); // Removed for production
                    // Fallback to direct navigation
                    window.location.href = originalHref;
                }
                
                return false;
            });
            
            // Also prevent any other click events
            button.onclick = function() { return false; };
        });
    },
    
    initModeChange: function() {
        // Handle mode change
        document.querySelectorAll('input[name="quiz_mode"]').forEach(radio => {
            radio.addEventListener('change', async function() {
                const topicId = document.getElementById('source_topic_id').value;
                if (!topicId) return;
                
                const sourcesContainer = document.querySelector('.sources-container');
                sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                try {
                    const response = await fetch(`/topics/${topicId}/source-progress?mode=${this.value}`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
// console.log('ℹ️ Mode change source progress request returned non-OK status, using fallback'); // Removed for production
                        // Don't throw, use fallback data
                        const fallbackData = { success: false, progress: {}, availableSources: [] };
                        if (typeof QuizModal.updateSourcesContainer === 'function') {
                            QuizModal.updateSourcesContainer(fallbackData);
                        }
                        return;
                    }
                    
                    const data = await response.json();
                    QuizModal.updateSourcesContainer(data);
                } catch (error) {
// console.log('ℹ️ Mode change source progress encountered an issue, using fallback'); // Removed for production
                    sourcesContainer.innerHTML = '<div class="alert alert-info">Source details unavailable. You can still start the quiz.</div>';
                }
            });
        });
    },
    
    initFormSubmission: function() {
        // Form submission
        const sourceSelectionForm = document.getElementById('sourceSelectionForm');
        if (sourceSelectionForm) {
            sourceSelectionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const checkedSources = this.querySelectorAll('input[name="sources[]"]:checked');
                if (checkedSources.length === 0) {
                    alert('Please select at least one source to start the quiz.');
                    return;
                }

                const selectedMode = this.querySelector('input[name="quiz_mode"]:checked').value;
                const courseId = this.querySelector('#source_course_id').value;
                const topicId = this.querySelector('#source_topic_id').value;
                
                const selectedSources = Array.from(checkedSources).map(cb => cb.value);
                const sourceParams = selectedSources.map(s => `sources[]=${s}`).join('&');
                
                const baseUrl = selectedMode === 'tutor' 
                    ? `/quiz/${courseId}/${topicId}/tutor`
                    : `/quiz/${courseId}/${topicId}`;
                    
                window.location.href = `${baseUrl}?${sourceParams}`;
            });
        }
    },
    
    initRandomQuizModal: function() {
        // Random Quiz Modal Validation
        const randomQuizForm = document.getElementById('randomQuizForm');
        if (randomQuizForm) {
            randomQuizForm.addEventListener('submit', function(e) {
                // Check if at least one course is selected
                const selectedCourses = this.querySelectorAll('input[name="selected_courses[]"]:checked');
                
                if (selectedCourses.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one course to start the random quiz.');
                    return false;
                }
                
                // Show loading state on submit button
                const submitBtn = document.querySelector('button[form="randomQuizForm"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Starting Quiz...';
                }
                
                return true;
            });
        }

        // Random Quiz Modal - Course Selection Handling
        const courseCheckboxes = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]');
        courseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Ensure at least one course remains selected
                const checkedCourses = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]:checked');
                const submitBtn = document.querySelector('button[form="randomQuizForm"]');
                
                if (checkedCourses.length === 0) {
                    // If no courses selected, disable submit button
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Select Courses';
                    }
                } else {
                    // Enable submit button if courses are selected
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-play me-2"></i>Start Quiz';
                    }
                }
            });
        });
    },
    
    // Helper function to update sources container
    updateSourcesContainer: function(data) {
        const sourcesContainer = document.querySelector('.sources-container');
        sourcesContainer.innerHTML = '';
        
        if (!data.availableSources || data.availableSources.length === 0) {
            sourcesContainer.innerHTML = '<div class="alert alert-info">No questions available for this topic.</div>';
            return;
        }
        
        data.availableSources.forEach(source => {
            const progress = data.progress[source] || { completed: 0, total: 0, percentage: 0 };
            const card = document.createElement('div');
            card.className = 'source-card';
            card.setAttribute('data-source', source);

            // Add selected class if there are questions
            if (progress.total_questions_available > 0) {
                card.classList.add('selected');
            }

            // Determine status class based on percentage
            const statusClass = progress.percentage >= 90 ? 'completed' : 
                      progress.percentage >= 50 ? 'in-progress' :
                      progress.percentage > 0 ? 'failed' :
                              'not-started';

            card.innerHTML = `
                <div class="source-status ${statusClass}"></div>
                <div class="source-content">
                    <div class="source-info-wrapper">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="sources[]" value="${source}" 
                               class="form-check-input" ${progress.total_questions_available > 0 ? 'checked' : ''}>
                                <span class="ms-2 source-name">${source}</span>
                            </div>
                    <div class="source-status-text ${statusClass}">
                                ${progress.percentage >= 90 ? 
                                    '<i class="fas fa-check-circle me-1"></i>Completed' : 
                                    progress.percentage >= 50 ? 
                                    '<i class="fas fa-clock me-1"></i>In Progress' :
                                    progress.percentage > 0 ?
                                    '<i class="fas fa-times-circle me-1"></i>Failed' :
                                    '<i class="fas fa-circle me-1"></i>Not Started'}
                            </div>
                        </div>
                        <div class="source-progress-bar">
                    <div class="source-progress-value ${statusClass}" 
                                 style="width: ${progress.percentage}%;">
                            </div>
                        </div>
                        <div class="source-stats">
                            <span class="questions-count">
                        <i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions
                            </span>
                        </div>
                    </div>
                </div>
            `;

            sourcesContainer.appendChild(card);

            // Add click handler for the entire card
            card.addEventListener('click', function(e) {
                if (e.target.type === 'checkbox') return;
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
                
                // Ensure at least one source is selected
                const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                if (!anyChecked) {
                    checkbox.checked = true;
                    this.classList.add('selected');
                }
            });
        });
    }
};