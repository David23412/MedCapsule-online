<script>
(function() {
    try {
        if (!document.querySelector('link[data-medcapsule-theme-stylesheet]')) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '{{ asset('css/admin-dark-mode.css') }}';
            link.setAttribute('data-medcapsule-theme-stylesheet', 'true');
            document.head.appendChild(link);
        }

        if (window.localStorage.getItem('medcapsule-theme') === 'dark') {
            document.documentElement.setAttribute('data-admin-theme', 'dark');
        }
    } catch (error) {
        console.warn('Unable to bootstrap theme styles.', error);
    }
})();
</script>

@include('partials.admin-dark-mode-script')

<!-- GOLDEN STANDARD FOUC ELIMINATOR PRELOADER - EXACT SAME DESIGN -->
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
/* GOLDEN STANDARD PRELOADER - ZERO FOUC GUARANTEE */
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

/* GOLDEN STANDARD FOUC PREVENTION */
body.preloader-active {
    overflow: hidden !important;
    position: fixed !important;
    width: 100% !important;
    height: 100% !important;
}

/* CRITICAL CSS TO PREVENT NOTIFICATION BELL FOUC */
.clean-notification-dropdown {
    visibility: hidden !important;
    opacity: 0 !important;
    display: none !important;
}

.clean-notification-dropdown.show {
    visibility: visible !important;
    opacity: 1 !important;
    display: flex !important;
}

/* PREVENT HEADER SHIFTS */
.navbar {
    min-height: 76px !important;
}

.navbar-nav {
    min-height: 40px !important;
}

/* MINIMAL COURSE CARD FOUC PREVENTION - NO HEIGHT INTERFERENCE */
.course-card {
    opacity: 1 !important;
    transform: none !important;
}

/* PREVENT NOTIFICATION BELL FOUC */
.clean-notification-bell,
#notificationBell,
.notification-bell {
    opacity: 0 !important;
    transition: opacity 0.2s ease !important;
}

.clean-notification-bell.loaded,
#notificationBell.loaded,
.notification-bell.loaded {
    opacity: 1 !important;
}

/* PREVENT COURSE IMAGE FOUC - NO HEIGHT INTERFERENCE */
.course-image {
    opacity: 1 !important;
}

/* PREVENT NAVBAR CONTENT SHIFT */
.navbar-nav {
    min-height: 40px !important;
    opacity: 0 !important;
    transition: opacity 0.3s ease !important;
}

.navbar-nav.loaded {
    opacity: 1 !important;
}

/* PREVENT FONT LOADING SHIFT */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

body.fonts-loaded {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif !important;
}
</style>

<script>
// BULLETPROOF PRELOADER - CANNOT GET STUCK
(function() {
    'use strict';
    
    console.log('🚀 BULLETPROOF PRELOADER STARTING...');
    
    // IMMEDIATELY LOCK BODY TO PREVENT FOUC
    document.body.classList.add('preloader-active');
    document.documentElement.style.overflow = 'hidden';
    
    let preloaderHidden = false;
    const startTime = performance.now();
    const currentPath = window.location.pathname;
    const isCoursesPage = currentPath.includes('/courses') || currentPath.includes('/dashboard');
    
    // BULLETPROOF HIDE FUNCTION
    function hidePreloader() {
        if (preloaderHidden) return;
        preloaderHidden = true;
        
        const preloader = document.getElementById('lightningPreloader');
        const totalTime = performance.now() - startTime;
        
        console.log(`⚡ PRELOADER HIDDEN: ${Math.round(totalTime)}ms - ${isCoursesPage ? 'COURSES' : 'HOME'} PAGE`);
        
        if (preloader) {
            preloader.classList.add('hidden');
        }
        
        // RESTORE BODY SCROLLING
        document.body.classList.remove('preloader-active');
        document.documentElement.style.overflow = '';
        
        // Show any hidden elements
        setTimeout(() => {
            const notificationBells = document.querySelectorAll('.clean-notification-bell, #notificationBell, .notification-bell');
            notificationBells.forEach(bell => bell.classList.add('loaded'));
            
            const navbarNav = document.querySelector('.navbar-nav');
            if (navbarNav) navbarNav.classList.add('loaded');
            
            document.body.classList.add('fonts-loaded');
        }, 100);
        
        // Remove preloader from DOM
        setTimeout(() => {
            if (preloader && preloader.parentNode) {
                preloader.parentNode.removeChild(preloader);
            }
        }, 300);
    }
    
    // BULLETPROOF TIMING - VISIBLE BUT SNAPPY
    if (isCoursesPage) {
        // COURSES PAGE: Wait longer to prevent FOUC
        setTimeout(hidePreloader, 800);  // Primary timeout
        setTimeout(hidePreloader, 1200); // Backup timeout
    } else {
        // HOME PAGE: Visible but still fast
        setTimeout(hidePreloader, 600);  // Primary timeout - now visible!
        setTimeout(hidePreloader, 800);  // Backup timeout
    }
    
    // EMERGENCY FALLBACK - ABSOLUTE MAXIMUM
    setTimeout(() => {
        if (!preloaderHidden) {
            console.log('🚨 EMERGENCY FALLBACK ACTIVATED!');
            hidePreloader();
        }
    }, 2000);
    
    // DOM READY OPTIMIZATION - STILL VISIBLE
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (!preloaderHidden && !isCoursesPage) {
                    hidePreloader();
                }
            }, 500); // Increased so it's visible
        });
    } else if (!isCoursesPage) {
        // DOM already ready and it's home page - but still show preloader
        setTimeout(hidePreloader, 600);
    }
    
    console.log(`⚡ PRELOADER CONFIGURED: ${isCoursesPage ? 'COURSES (800ms)' : 'HOME (400ms)'} MODE`);
    
})();
</script> 
