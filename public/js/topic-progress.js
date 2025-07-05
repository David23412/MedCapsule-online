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
                    progressBar.className = `source-progress-value ${progress.percentage >= 90 ? 'completed' : 
                                          progress.percentage >= 50 ? 'in-progress' : 'failed'}`;
                }
                
                if (statusText) {
                    if (progress.percentage >= 90) {
                        statusText.innerHTML = '<i class="fas fa-check-circle me-1"></i>Completed';
                        statusText.className = 'source-status-text completed';
                    } else if (progress.percentage >= 50) {
                        statusText.innerHTML = '<i class="fas fa-clock me-1"></i>In Progress';
                        statusText.className = 'source-status-text in-progress';
                    } else {
                        statusText.innerHTML = '<i class="fas fa-times-circle me-1"></i>Failed';
                        statusText.className = 'source-status-text failed';
                    }
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