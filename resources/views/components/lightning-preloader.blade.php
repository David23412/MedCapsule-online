<!-- LIGHTNING-FAST FOUC ELIMINATOR PRELOADER -->
<div id="lightningPreloader" class="lightning-preloader">
    <div class="preloader-container">
        <div class="medcapsule-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <div class="preloader-brand">MedCapsule</div>
        <div class="preloader-subtitle">Loading...</div>
    </div>
</div>

<style>
/* LIGHTNING-FAST PRELOADER - ELIMINATES ALL FOUC */
.lightning-preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    will-change: opacity, visibility;
    backface-visibility: hidden;
    transform: translateZ(0);
}

.lightning-preloader.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.preloader-container {
    text-align: center;
    animation: fadeInUp 0.3s ease-out;
}

.medcapsule-spinner {
    position: relative;
    width: 60px;
    height: 60px;
    margin: 0 auto 1.5rem;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.spinner-ring:nth-child(1) {
    animation-delay: 0s;
    border-top-color: #007bff;
}

.spinner-ring:nth-child(2) {
    animation-delay: -0.33s;
    border-top-color: #0056b3;
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
}

.spinner-ring:nth-child(3) {
    animation-delay: -0.66s;
    border-top-color: #004085;
    width: 60%;
    height: 60%;
    top: 20%;
    left: 20%;
}

.preloader-brand {
    font-size: 1.8rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    text-shadow: 0 1px 3px rgba(0, 123, 255, 0.1);
}

.preloader-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
    opacity: 0.8;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* PERFORMANCE OPTIMIZATIONS */
.lightning-preloader * {
    will-change: auto;
    backface-visibility: hidden;
    transform: translateZ(0);
}

/* RESPONSIVE DESIGN */
@media (max-width: 768px) {
    .medcapsule-spinner {
        width: 50px;
        height: 50px;
    }
    
    .preloader-brand {
        font-size: 1.5rem;
    }
    
    .preloader-subtitle {
        font-size: 0.8rem;
    }
}
</style>

<script>
// LIGHTNING-FAST PRELOADER CONTROLLER
(function() {
    'use strict';
    
    let preloaderHidden = false;
    
    function hidePreloader() {
        if (preloaderHidden) return;
        
        const preloader = document.getElementById('lightningPreloader');
        if (preloader) {
            preloaderHidden = true;
            preloader.classList.add('hidden');
            
            // Remove from DOM after animation for memory optimization
            setTimeout(() => {
                if (preloader.parentNode) {
                    preloader.parentNode.removeChild(preloader);
                }
            }, 200);
        }
    }
    
    // Hide preloader when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hidePreloader);
    } else {
        // DOM already loaded
        setTimeout(hidePreloader, 50);
    }
    
    // Fallback timeout - never show preloader for more than 1 second
    setTimeout(hidePreloader, 1000);
    
    // Hide on window load as backup
    window.addEventListener('load', hidePreloader);
})();
</script> 