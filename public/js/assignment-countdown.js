/**
 * Assignment Countdown Module
 * Handles assignment countdown timers with exact original behavior
 */

const AssignmentCountdown = {
    init: function() {
        // Assignment countdown functionality
        function updateCountdowns() {
            const countdowns = document.querySelectorAll('.assignment-countdown-minimal');
            
            countdowns.forEach(countdown => {
                const deadline = new Date(countdown.dataset.deadline);
                const now = new Date();
                const timeLeft = deadline - now;
                
                if (timeLeft <= 0) {
                    // Assignment is overdue - lock the topic
                    const topicCard = countdown.closest('.topic-card');
                    if (topicCard) {
                        topicCard.classList.add('topic-locked');
                        
                        // Update the banner to show overdue status
                        const banner = topicCard.querySelector('.assignment-status-banner');
                        if (banner) {
                            banner.className = 'assignment-status-banner assignment-overdue';
                            banner.innerHTML = `
                                <div class="assignment-info overdue">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="assignment-label">ASSIGNMENT OVERDUE</span>
                                    <span class="overdue-text">Topic Locked</span>
                                </div>
                            `;
                        }
                        
                        // Disable the start quiz button
                        const startBtn = topicCard.querySelector('.start-quiz-btn');
                        if (startBtn) {
                            startBtn.style.pointerEvents = 'none';
                            startBtn.style.background = '#f1f3f4';
                            startBtn.style.color = '#9ca3af';
                            startBtn.style.border = '2px solid #e5e7eb';
                            startBtn.style.opacity = '0.6';
                            startBtn.style.display = 'flex';
                            startBtn.style.alignItems = 'center';
                            startBtn.style.justifyContent = 'center';
                            startBtn.innerHTML = '<i class="fas fa-lock lock-icon" style="color: #9ca3af; font-size: 1.2em; margin: 0;"></i>';
                        }
                    }
                    return;
                }
                
                // Calculate time components
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                // Format countdown display
                let countdownText = '';
                if (days > 0) {
                    countdownText = `${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    countdownText = `${hours}h ${minutes}m ${seconds}s`;
                } else if (minutes > 0) {
                    countdownText = `${minutes}m ${seconds}s`;
                } else {
                    countdownText = `${seconds}s`;
                    // Add urgent styling when less than 1 minute
                    countdown.classList.add('countdown-urgent');
                }
                
                // Update the countdown display
                const display = countdown.querySelector('.countdown-display');
                if (display) {
                    display.textContent = countdownText;
                }
                
                // Add warning styling when less than 1 hour
                if (timeLeft < 3600000) { // 1 hour in milliseconds
                    countdown.classList.add('countdown-urgent');
                    const banner = countdown.closest('.assignment-status-banner');
                    if (banner) {
                        banner.style.animation = 'urgentBanner 1s infinite';
                    }
                }
            });
        }
        
        // Start countdown updates
        updateCountdowns();
        setInterval(updateCountdowns, 1000); // Update every second
    }
};