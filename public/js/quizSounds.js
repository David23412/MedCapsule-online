/**
 * DEPRECATED: Legacy Quiz Sound Manager
 * This file is kept for backward compatibility.
 * New implementations should use GradingAudioController instead.
 */

// Import the new unified grading audio controller
if (typeof GradingAudioController === 'undefined') {
    // Load the grading audio controller if not already loaded
    const script = document.createElement('script');
    script.src = '/js/grading-audio-controller.js';
    document.head.appendChild(script);
}

/**
 * Legacy QuizSoundManager - now uses GradingAudioController internally
 * Maintained for backward compatibility with existing code
 */
class QuizSoundManager {
    constructor() {
    }

    async playSound(type) {
        // Wait for GradingAudioController to be available
        if (typeof GradingAudioController === 'undefined') {
            setTimeout(() => this.playSound(type), 100);
            return;
        }

        try {
            switch(type) {
                case 'perfect':
                    GradingAudioController.playPerfectSound();
                    break;
                case 'partial':
                    GradingAudioController.playPartialSound();
                    break;
                case 'wrong':
                    GradingAudioController.playWrongSound();
                    break;
                default:
// console.warn(`Unknown sound type: ${type}`); // Removed for production
            }
        } catch (error) {
// console.warn('Legacy sound playback failed:', error); // Removed for production
        }
    }

    playPerfect() {
        return this.playSound('perfect');
    }

    playPartial() {
        return this.playSound('partial');
    }

    playWrong() {
        return this.playSound('wrong');
    }

    // New method to play sound based on score percentage
    playGradingSound(scorePercentage) {
        if (typeof GradingAudioController !== 'undefined') {
            GradingAudioController.playGradingSound(scorePercentage);
        } else {
            // Fallback to legacy method
            if (scorePercentage === 100) {
                this.playPerfect();
            } else if (scorePercentage >= 50) {
                this.playPartial();
            } else {
                this.playWrong();
            }
        }
    }
}

// Create global instance for backward compatibility
window.quizSoundManager = new QuizSoundManager();

// Also expose the new unified controller when available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof GradingAudioController !== 'undefined') {
        window.gradingAudio = GradingAudioController;
    }
}); 