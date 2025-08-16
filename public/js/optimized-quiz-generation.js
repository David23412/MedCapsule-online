/**
 * OPTIMIZED QUIZ GENERATION ENGINE
 * High-performance quiz generation with intelligent caching, prefetching, and performance optimizations
 */

class OptimizedQuizGenerator {
    constructor() {
        this.cache = new Map();
        this.prefetchQueue = new Set();
        this.isGenerating = false;
        this.retryAttempts = 3;
        this.baseUrl = '/quiz-generation';
        
        this.initializeEventListeners();
        this.startPrefetchEngine();
    }

    /**
     * Initialize event listeners for quiz generation
     */
    initializeEventListeners() {
        // Auto-prefetch when hovering over quiz buttons
        document.addEventListener('mouseenter', (e) => {
            if (e.target.matches('[data-topic-id]')) {
                const topicId = e.target.dataset.topicId;
                this.prefetchQuizData(topicId);
            }
        }, true);

        // Handle quiz generation forms
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.quiz-generation-form')) {
                e.preventDefault();
                this.handleQuizGeneration(e.target);
            }
        });
    }

    /**
     * Start the prefetch engine for instant loading
     */
    startPrefetchEngine() {
        // Prefetch visible topic options
        const visibleTopics = document.querySelectorAll('[data-topic-id]');
        visibleTopics.forEach(element => {
            const topicId = element.dataset.topicId;
            if (topicId) {
                this.prefetchQueue.add(topicId);
            }
        });

        // Process prefetch queue
        this.processPrefetchQueue();
    }

    /**
     * Process the prefetch queue with F-16 speed
     */
    async processPrefetchQueue() {
        const promises = Array.from(this.prefetchQueue).map(topicId => 
            this.prefetchQuizData(topicId)
        );

        try {
            await Promise.allSettled(promises);
// // console.log('🚀 OPTIMIZED: Prefetch complete - Ready for instant quiz generation'); // Removed for production // Removed for production
        } catch (error) {
// // console.warn('⚠️ F-16 RAPTOR: Some prefetch operations failed', error); // Removed for production // Removed for production
        }
    }

    /**
     * Prefetch quiz data for instant loading
     */
    async prefetchQuizData(topicId) {
        if (this.cache.has(`options_${topicId}`)) {
            return; // Already cached
        }

        try {
            const [optionsResponse, prefetchResponse] = await Promise.all([
                fetch(`${this.baseUrl}/topics/${topicId}/options`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }),
                fetch(`${this.baseUrl}/topics/${topicId}/prefetch`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
            ]);

            if (optionsResponse.ok) {
                const optionsData = await optionsResponse.json();
                this.cache.set(`options_${topicId}`, optionsData);
            }

// // console.log(`🎯 OPTIMIZED: Prefetched data for topic ${topicId}`); // Removed for production // Removed for production
        } catch (error) {
// // console.warn(`⚠️ F-16 RAPTOR: Prefetch failed for topic ${topicId}`, error); // Removed for production // Removed for production
        }
    }

    /**
     * Handle quiz generation with F-16 RAPTOR speed
     */
    async handleQuizGeneration(form) {
        if (this.isGenerating) {
            return; // Prevent double submissions
        }

        this.isGenerating = true;
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton?.textContent;
        
        try {
            // Show loading state
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '🚀 Generating with optimized performance...';
            }

            const formData = new FormData(form);
            const topicId = formData.get('topic_id') || form.dataset.topicId;
            
            const response = await this.generateQuizWithRetry(topicId, formData);
            
            if (response.success) {
                this.handleGenerationSuccess(response, topicId);
            } else {
                this.handleGenerationError(response.message || 'Generation failed');
            }

        } catch (error) {
            this.handleGenerationError(error.message);
        } finally {
            this.isGenerating = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    }

    /**
     * Generate quiz with retry mechanism
     */
    async generateQuizWithRetry(topicId, formData, attempt = 1) {
        try {
            const response = await fetch(`${this.baseUrl}/topics/${topicId}/generate`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: formData
            });

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;

        } catch (error) {
            if (attempt < this.retryAttempts) {
// // console.warn(`🔄 F-16 RAPTOR: Retry attempt ${attempt + 1} for topic ${topicId}`); // Removed for production // Removed for production
                await this.delay(1000 * attempt); // Exponential backoff
                return this.generateQuizWithRetry(topicId, formData, attempt + 1);
            }
            throw error;
        }
    }

    /**
     * Handle successful quiz generation
     */
    handleGenerationSuccess(response, topicId) {
// // console.log('✅ F-16 RAPTOR: Quiz generated successfully', response); // Removed for production // Removed for production
        
        // Cache the result for instant future access
        this.cache.set(`quiz_${topicId}`, response.data);
        
        // Trigger success event
        document.dispatchEvent(new CustomEvent('quizGenerated', {
            detail: { response, topicId }
        }));

        // Show success notification
        this.showNotification('🚀 Quiz generated with optimized performance!', 'success');
        
        // Redirect or update UI based on response
        if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
        } else if (response.data.quiz_html) {
            this.updateQuizContainer(response.data.quiz_html);
        }
    }

    /**
     * Handle quiz generation errors
     */
    handleGenerationError(message) {
// // console.error('❌ F-16 RAPTOR: Quiz generation failed', message); // Removed for production // Removed for production
        
        // Trigger error event
        document.dispatchEvent(new CustomEvent('quizGenerationError', {
            detail: { message }
        }));

        this.showNotification(`❌ Generation failed: ${message}`, 'error');
    }

    /**
     * Update quiz container with generated content
     */
    updateQuizContainer(html) {
        const container = document.querySelector('#quiz-container') || 
                         document.querySelector('.quiz-content') ||
                         document.querySelector('main');
        
        if (container) {
            container.innerHTML = html;
            
            // Reinitialize any JavaScript components
            if (window.initializeQuizComponents) {
                window.initializeQuizComponents();
            }
        }
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Try to use existing notification system
        if (window.showNotification) {
            window.showNotification(message, type);
            return;
        }

        // Fallback notification
        const notification = document.createElement('div');
        notification.className = `f16-notification f16-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Utility delay function
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Get cached quiz options
     */
    getCachedOptions(topicId) {
        return this.cache.get(`options_${topicId}`);
    }

    /**
     * Clear cache for topic
     */
    clearCache(topicId = null) {
        if (topicId) {
            this.cache.delete(`options_${topicId}`);
            this.cache.delete(`quiz_${topicId}`);
        } else {
            this.cache.clear();
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .f16-notification {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
    }
`;
document.head.appendChild(style);

// Initialize Optimized Quiz Generator
document.addEventListener('DOMContentLoaded', () => {
    window.optimizedQuizGenerator = new OptimizedQuizGenerator();
// // console.log('🚀 OPTIMIZED QUIZ GENERATION ENGINE INITIALIZED'); // Removed for production // Removed for production
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OptimizedQuizGenerator;
}