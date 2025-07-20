/**
 * Unified Grading Audio Controller
 * Provides consistent audio feedback across all quiz modes
 */
class GradingAudioController {
    static SOUNDS = {
        PERFECT: '/sounds/quiz-perfect.mp3',
        PARTIAL: '/sounds/quiz-partial.mp3',
        WRONG: '/sounds/quiz-wrong.mp3'
    };

    static audioCache = new Map();
    static isInitialized = false;

    /**
     * Initialize the audio controller
     */
    static init() {
        if (this.isInitialized) return;
        
        // Preload all audio files
        Object.values(this.SOUNDS).forEach(soundPath => {
            const audio = new Audio(soundPath);
            audio.preload = 'auto';
            audio.volume = 0.6; // Set default volume to 60%
            this.audioCache.set(soundPath, audio);
        });

        this.isInitialized = true;
        console.log('GradingAudioController initialized');
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
        this.playAudio(sound);
        
        // Log for debugging
        console.log(`Playing grading sound: ${sound} for score: ${scorePercentage}%`);
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
     * Play audio with error handling
     * @param {string} soundPath - Path to sound file
     */
    static async playAudio(soundPath) {
        try {
            const audio = this.audioCache.get(soundPath);
            if (!audio) {
                console.warn(`Audio not found in cache: ${soundPath}`);
                return;
            }

            // Stop any currently playing audio first
            this.audioCache.forEach(cachedAudio => {
                if (!cachedAudio.paused) {
                    cachedAudio.pause();
                    cachedAudio.currentTime = 0;
                }
            });

            // Reset audio to beginning
            audio.currentTime = 0;
            
            // Ensure audio is ready to play
            if (audio.readyState >= 2) {
                // Play with promise handling for better browser compatibility
                const playPromise = audio.play();
                
                if (playPromise !== undefined) {
                    await playPromise;
                }
            } else {
                // Wait for audio to load then play
                audio.addEventListener('canplay', () => {
                    audio.play().catch(e => console.warn('Delayed audio play failed:', e));
                }, { once: true });
            }
        } catch (error) {
            console.warn('Audio playback failed:', error);
            // Fallback: try creating new audio instance
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
                console.warn('Fallback audio playback also failed:', e);
            });
        } catch (error) {
            console.warn('Fallback audio creation failed:', error);
        }
    }

    /**
     * Set volume for all audio files
     * @param {number} volume - Volume level (0.0 to 1.0)
     */
    static setVolume(volume) {
        const clampedVolume = Math.max(0, Math.min(1, volume));
        this.audioCache.forEach(audio => {
            audio.volume = clampedVolume;
        });
    }

    /**
     * Mute/unmute all audio
     * @param {boolean} muted - Whether to mute audio
     */
    static setMuted(muted) {
        this.audioCache.forEach(audio => {
            audio.muted = muted;
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