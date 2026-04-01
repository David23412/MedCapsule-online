/**
 * Unified Grading Audio Controller
 * Provides consistent audio feedback across all quiz modes
 */
class GradingAudioController {
    static SOUNDS = {
        PERFECT: '/sounds/quiz-perfect.mp3',
        PARTIAL: '/sounds/quiz-partial.mp3',
        CORRECT: '/sounds/Single-correct.mp3',
        WRONG: '/sounds/quiz-wrong.mp3'
    };

    static audioPool = new Map();
    static isInitialized = false;
    static POOL_SIZE = 10; // Number of audio instances per sound (increased for rapid answering)

    /**
     * Initialize the audio controller
     */
    static init() {
        if (this.isInitialized) return;
        
        // Preload all audio files into a pool
        Object.values(this.SOUNDS).forEach(soundPath => {
            const pool = [];
            for (let i = 0; i < this.POOL_SIZE; i++) {
                const audio = new Audio(soundPath);
                audio.preload = 'auto';
                audio.volume = 0.6; // Set default volume to 60%
                pool.push(audio);
            }
            this.audioPool.set(soundPath, pool);
        });

        this.isInitialized = true;
    }

    /**
     * Play grading sound based on score percentage
     * @param {number} scorePercentage - Score as percentage (0-100)
     * @param {number} totalQuestions - Total number of questions (optional, for context)
     */
    static playGradingSound(scorePercentage, totalQuestions = null) {
        if (!this.isInitialized) {
            this.init();
        }

        const sound = this.determineSound(scorePercentage);
        this.playAudio(sound); // Fire-and-forget for final grading sounds too

        // Log for debugging
    }

    /**
     * Determine which sound to play based on score
     * @param {number} percentage - Score percentage
     * @returns {string} Sound file path
     */
    static determineSound(percentage) {
        if (percentage === 100) {
            return this.SOUNDS.PERFECT;
        } else if (percentage >= 50) {
            return this.SOUNDS.PARTIAL;
        } else {
            return this.SOUNDS.WRONG;
        }
    }

    /**
     * Play audio with error handling (fire-and-forget for all sounds)
     * @param {string} soundPath - Path to sound file
     */
    static playAudio(soundPath) {
        try {
            const pool = this.audioPool.get(soundPath);
            if (!pool) {
                return;
            }

            // Find an available audio object in the pool
            let audio = pool.find(a => a.paused || a.ended);

            if (audio) {
                audio.currentTime = 0;
                // Fire-and-forget: don't wait for completion
                audio.play().catch(e => {});
            } else {
                // If all are busy, just play the first one, cutting it off.
                // This is a fallback and shouldn't happen with a large enough pool.
                audio = pool[0];
                audio.currentTime = 0;
                // Fire-and-forget: don't wait for completion
                audio.play().catch(e => {});
            }
        } catch (error) {
            // Fallback for safety
            this.playAudioFallback(soundPath);
        }
    }

    /**
     * Fallback audio playback method
     * @param {string} soundPath - Path to sound file
     */
    static playAudioFallback(soundPath) {
        try {
            const audio = new Audio(soundPath);
            audio.volume = 0.6;
            audio.play().catch(e => {
// console.warn('Fallback audio playback also failed:', e); // Removed for production
            });
        } catch (error) {
// console.warn('Fallback audio creation failed:', error); // Removed for production
        }
    }

    /**
     * Set volume for all audio files
     * @param {number} volume - Volume level (0.0 to 1.0)
     */
    static setVolume(volume) {
        const clampedVolume = Math.max(0, Math.min(1, volume));
        this.audioPool.forEach(pool => {
            pool.forEach(audio => {
                audio.volume = clampedVolume;
            });
        });
    }

    /**
     * Mute/unmute all audio
     * @param {boolean} muted - Whether to mute audio
     */
    static setMuted(muted) {
        this.audioPool.forEach(pool => {
            pool.forEach(audio => {
                audio.muted = muted;
            });
        });
    }

    /**
     * Play sound for perfect score (100%)
     */
    static playPerfectSound() {
        this.playAudio(this.SOUNDS.PERFECT);
    }

    /**
     * Play sound for partial score (50-99%)
     */
    static playPartialSound() {
        this.playAudio(this.SOUNDS.PARTIAL);
    }

    /**
     * Play sound for correct answer
     */
    static playCorrectSound() {
        this.playAudio(this.SOUNDS.CORRECT);
    }

    /**
     * Play sound for wrong/low score (0-49%)
     */
    static playWrongSound() {
        this.playAudio(this.SOUNDS.WRONG);
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    GradingAudioController.init();
});

// Make it globally available
window.GradingAudioController = GradingAudioController;
