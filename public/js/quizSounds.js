/**
 * Quiz Sound Manager
 * Handles loading and playing quiz completion sounds with robust error handling
 */
class QuizSoundManager {
    constructor() {
        // Initialize sound paths with correct URLs
        this.soundPaths = {
            perfect: '/sounds/quiz-perfect.mp3',
            partial: '/sounds/quiz-partial.mp3',
            wrong: '/sounds/quiz-wrong.mp3'
        };

        // Initialize audio elements
        this.sounds = {};
        
        // Load sounds with error handling
        this.loadSounds();
        
        // Bind methods
        this.playSound = this.playSound.bind(this);
        this.playPerfect = this.playPerfect.bind(this);
        this.playPartial = this.playPartial.bind(this);
        this.playWrong = this.playWrong.bind(this);

        // Log initialization
        console.log('QuizSoundManager initialized');
    }

    loadSounds() {
        Object.entries(this.soundPaths).forEach(([key, path]) => {
            console.log(`Loading sound: ${key} from ${path}`);
            const audio = new Audio(path);
            
            // Configure audio settings
            audio.preload = 'auto';
            
            // Add error handling
            audio.onerror = (e) => {
                console.warn(`Failed to load ${key} sound from ${path}:`, e);
                // Create a silent fallback
                this.sounds[key] = {
                    play: () => {
                        console.log(`Silent fallback for ${key} sound`);
                        return Promise.resolve();
                    },
                    pause: () => {},
                    currentTime: 0
                };
            };
            
            // Add load handler
            audio.oncanplaythrough = () => {
                console.log(`Successfully loaded ${key} sound from ${path}`);
            };
            
            // Store the audio element
            this.sounds[key] = audio;
            
            // Start loading
            audio.load();
        });
    }

    async playSound(type) {
        try {
            const sound = this.sounds[type];
            if (!sound) {
                console.warn(`Sound type ${type} not found`);
                return;
            }
            
            // Stop any currently playing sounds
            Object.values(this.sounds).forEach(audio => {
                if (audio.pause) {
                    audio.pause();
                    audio.currentTime = 0;
                }
            });

            // Check if we can play audio
            if (document.visibilityState === 'visible') {
                const playPromise = sound.play();
                
                if (playPromise !== undefined) {
                    await playPromise;
                    console.log(`${type} sound played successfully`);
                }
            } else {
                console.log(`${type} sound skipped - page not visible`);
            }
        } catch (error) {
            console.warn('Sound playback failed:', error);
            // Fail silently - don't break quiz functionality
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
}

// Create global instance
window.quizSoundManager = new QuizSoundManager(); 