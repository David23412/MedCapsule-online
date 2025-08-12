/**
 * Unified Confetti Animation Manager
 * Provides consistent confetti animations across all quiz modes and grading interfaces
 */
class ConfettiManager {
    static isInitialized = false;
    static activeConfetti = [];

    /**
     * Initialize the confetti manager
     */
    static init() {
        if (this.isInitialized) return;
        
        // Check if confetti library is available
        if (typeof confetti === 'undefined') {
// console.warn('Confetti library not loaded - confetti animations will be disabled'); // Removed for production
            return;
        }

        this.isInitialized = true;
// console.log('ConfettiManager initialized'); // Removed for production
    }

    /**
     * Clear all existing confetti animations
     */
    static clearConfetti() {
        try {
            // Clear any active confetti animations
            this.activeConfetti.forEach(animation => {
                if (animation && typeof animation.reset === 'function') {
                    animation.reset();
                }
            });
            this.activeConfetti = [];
            
            // Remove any confetti DOM elements
            document.querySelectorAll('.confetti, .confetti-container').forEach(element => {
                element.remove();
            });
        } catch (error) {
// console.warn('Error clearing confetti:', error); // Removed for production
        }
    }

    /**
     * Trigger confetti animation with proper positioning and cleanup
     * @param {HTMLElement|string} container - Container element or selector
     * @param {string} intensity - Animation intensity: 'light', 'medium', 'heavy'
     * @param {Object} options - Additional confetti options
     */
    static triggerConfetti(container = null, intensity = 'medium', options = {}) {
        if (!this.isInitialized) {
            this.init();
        }

        if (typeof confetti === 'undefined') {
// console.warn('Confetti library not available'); // Removed for production
            return;
        }

        try {
            // Clear any existing confetti first
            this.clearConfetti();

            // Get container element
            let containerElement = container;
            if (typeof container === 'string') {
                containerElement = document.querySelector(container);
            }

            // Set default confetti options based on intensity
            const baseOptions = this.getIntensityOptions(intensity);
            
            // Merge with custom options
            const finalOptions = {
                ...baseOptions,
                ...options,
                // Ensure proper z-index for modals
                zIndex: options.zIndex || 9999
            };

            // If container is specified, position confetti relative to it
            if (containerElement) {
                const rect = containerElement.getBoundingClientRect();
                const centerX = (rect.left + rect.width / 2) / window.innerWidth;
                const centerY = (rect.top + rect.height / 2) / window.innerHeight;
                
                finalOptions.origin = {
                    x: centerX,
                    y: centerY
                };
            }

            // Trigger the confetti animation
            const animation = confetti(finalOptions);
            this.activeConfetti.push(animation);

            // Auto-cleanup after animation completes
            setTimeout(() => {
                this.clearConfetti();
            }, 3000);

// console.log(`Confetti triggered with ${intensity} intensity`); // Removed for production
            
        } catch (error) {
// console.warn('Error triggering confetti:', error); // Removed for production
        }
    }

    /**
     * Get confetti options based on intensity level
     * @param {string} intensity - 'light', 'medium', or 'heavy'
     * @returns {Object} Confetti options
     */
    static getIntensityOptions(intensity) {
        const options = {
            light: {
                particleCount: 50,
                spread: 45,
                startVelocity: 25,
                decay: 0.9,
                gravity: 0.8,
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98']
            },
            medium: {
                particleCount: 100,
                spread: 70,
                startVelocity: 30,
                decay: 0.9,
                gravity: 1,
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98', '#FF6B6B', '#4ECDC4']
            },
            heavy: {
                particleCount: 200,
                spread: 90,
                startVelocity: 35,
                decay: 0.85,
                gravity: 1.2,
                colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#98FB98', '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4']
            }
        };

        return options[intensity] || options.medium;
    }

    /**
     * Trigger celebration confetti for perfect scores
     * @param {HTMLElement|string} container - Container element or selector
     */
    static celebratePerfectScore(container = null) {
        // Safari-specific positioning fix
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        
        let originX = 0.5;
        if (isSafari) {
            // EXTREME left shift for Safari's fucked positioning
            originX = 0.25;
        }
        
        this.triggerConfetti(container, 'heavy', {
            origin: { x: originX, y: 0.6 },
            colors: ['#FFD700', '#FFA500', '#FF69B4'],
            shapes: ['star', 'circle']
        });
    }

    /**
     * Trigger confetti for good scores
     * @param {HTMLElement|string} container - Container element or selector
     */
    static celebrateGoodScore(container = null) {
        this.triggerConfetti(container, 'medium', {
            origin: { y: 0.7 },
            colors: ['#00CED1', '#98FB98', '#4ECDC4']
        });
    }

    /**
     * Trigger subtle confetti for achievements
     * @param {HTMLElement|string} container - Container element or selector
     */
    static celebrateAchievement(container = null) {
        this.triggerConfetti(container, 'light', {
            origin: { y: 0.8 },
            colors: ['#96CEB4', '#FFEAA7', '#DDA0DD']
        });
    }

    /**
     * Create a burst of confetti from multiple points
     * @param {Array} points - Array of {x, y} coordinates (0-1 range)
     * @param {string} intensity - Animation intensity
     */
    static createBurst(points = [], intensity = 'medium') {
        if (!Array.isArray(points) || points.length === 0) {
            points = [{ x: 0.5, y: 0.6 }]; // Default center position
        }

        points.forEach((point, index) => {
            setTimeout(() => {
                this.triggerConfetti(null, intensity, {
                    origin: point,
                    angle: 90 + (index * 30), // Vary angles for multiple bursts
                });
            }, index * 100); // Stagger the bursts
        });
    }

    /**
     * Create fireworks-style confetti
     * @param {HTMLElement|string} container - Container element or selector
     */
    static createFireworks(container = null) {
        const points = [
            { x: 0.2, y: 0.4 },
            { x: 0.5, y: 0.3 },
            { x: 0.8, y: 0.4 }
        ];

        this.createBurst(points, 'heavy');
    }

    /**
     * Trigger confetti specifically for modal popups with perfect center positioning
     * This method ensures confetti appears exactly in the center of the modal, especially for Safari
     * @param {string} type - 'perfect', 'good', 'achievement'
     */
    static triggerModalConfetti(type = 'good') {
        if (typeof confetti === 'undefined') {
            return;
        }

        try {
            // Clear any existing confetti first
            this.clearConfetti();

            // Safari-specific positioning fix
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            
            let centerX = 0.5;
            let centerY = 0.5;
            
            if (isSafari) {
                // Safari viewport compensation - EXTREME left shift to obliterate Safari's positioning bug
                centerX = 0.25; // EXTREME left shift - Safari's positioning is completely fucked
                centerY = 0.5;
            }

            const centerOptions = {
                origin: { 
                    x: centerX,
                    y: centerY
                },
                zIndex: 10001  // Above modal
            };

            let confettiOptions = {};

            switch (type) {
                case 'perfect':
                    confettiOptions = {
                        particleCount: 150,
                        spread: 80,
                        startVelocity: 35,
                        decay: 0.85,
                        gravity: 1.2,
                        colors: ['#FFD700', '#FFA500', '#FF69B4', '#00CED1'],
                        shapes: ['star', 'circle'],
                        ...centerOptions
                    };
                    break;
                    
                case 'good':
                    confettiOptions = {
                        particleCount: 100,
                        spread: 70,
                        startVelocity: 30,
                        decay: 0.9,
                        gravity: 1,
                        colors: ['#00CED1', '#98FB98', '#4ECDC4', '#45B7D1'],
                        ...centerOptions
                    };
                    break;
                    
                case 'achievement':
                    confettiOptions = {
                        particleCount: 75,
                        spread: 60,
                        startVelocity: 25,
                        decay: 0.9,
                        gravity: 0.8,
                        colors: ['#96CEB4', '#FFEAA7', '#DDA0DD', '#FFB6C1'],
                        ...centerOptions
                    };
                    break;
                    
                default:
                    confettiOptions = {
                        particleCount: 100,
                        spread: 70,
                        startVelocity: 30,
                        decay: 0.9,
                        gravity: 1,
                        colors: ['#00CED1', '#98FB98', '#4ECDC4'],
                        ...centerOptions
                    };
            }

            // Trigger the confetti animation
            const animation = confetti(confettiOptions);
            this.activeConfetti.push(animation);

            // Auto-cleanup after animation completes
            setTimeout(() => {
                this.clearConfetti();
            }, 3000);

        } catch (error) {
            // Silent fail for production
        }
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    ConfettiManager.init();
});

// Make it globally available
window.ConfettiManager = ConfettiManager;