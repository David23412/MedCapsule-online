document.addEventListener('DOMContentLoaded', function() {
    // Listen for source progress updates
    document.addEventListener('sourceProgressUpdated', function(e) {
        const { topicId, sourceProgress, availableSources, allSourcesMastered } = e.detail;
        
        // Update source progress bars
        Object.entries(sourceProgress).forEach(([source, progress]) => {
            const sourceCard = document.querySelector(`[data-source="${source}"]`);
            if (sourceCard) {
                const progressBar = sourceCard.querySelector('.source-progress-value');
                const statusText = sourceCard.querySelector('.source-status-text');
                const questionsCount = sourceCard.querySelector('.questions-count');
                
                if (progressBar) {
                    progressBar.style.width = `${progress.percentage}%`;
                    
                    // FIXED: Determine status class based on attempts and percentage
                    let statusClass;
                    if (progress.has_attempts) {
                        if (progress.percentage >= 90) {
                            statusClass = 'completed';
                        } else if (progress.percentage >= 50) {
                            statusClass = 'in-progress';
                        } else {
                            statusClass = 'failed';
                        }
                    } else {
                        statusClass = 'not-started';
                    }
                    
                    progressBar.className = `source-progress-value ${statusClass}`;
                }
                
                if (statusText) {
                    // FIXED: Determine status text and class based on attempts and percentage
                    let statusTextContent, statusClass;
                    
                    if (progress.has_attempts) {
                        if (progress.percentage >= 90) {
                            statusTextContent = '<i class="fas fa-check-circle me-1"></i>Completed';
                            statusClass = 'completed';
                        } else if (progress.percentage >= 50) {
                            statusTextContent = '<i class="fas fa-clock me-1"></i>In Progress';
                            statusClass = 'in-progress';
                        } else {
                            statusTextContent = '<i class="fas fa-times-circle me-1"></i>Failed';
                            statusClass = 'failed';
                        }
                    } else {
                        statusTextContent = '<i class="fas fa-circle me-1"></i>Not started yet';
                        statusClass = 'not-started';
                    }
                    
                    statusText.innerHTML = statusTextContent;
                    statusText.className = `source-status-text ${statusClass}`;
                }
                
                if (questionsCount) {
                    questionsCount.innerHTML = `<i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions`;
                }
            }
        });
        
        // Update topic mastery status
        const topicCard = document.querySelector(`[data-topic-id="${topicId}"]`);
        if (topicCard) {
            const gradeBadge = topicCard.querySelector('.grade-badge');
            if (gradeBadge) {
                gradeBadge.className = `grade-badge ${allSourcesMastered ? 'grade-mastered' : 'grade-good'}`;
                gradeBadge.innerHTML = allSourcesMastered ? 
                    '<i class="fas fa-star"></i> Mastered' : 
                    '<i class="fas fa-check"></i> Good';
            }
        }
    });
}); 