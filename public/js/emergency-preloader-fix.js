// Emergency fix for stuck preloaders — runs silently in production
(function() {
    'use strict';

    // Force hide any stuck preloader
    const preloader = document.getElementById('lightningPreloader');
    if (preloader) {
        preloader.classList.add('hidden');

        setTimeout(() => {
            if (preloader.parentNode) {
                preloader.parentNode.removeChild(preloader);
            }
        }, 300);
    }

    // Restore body scrolling
    document.body.classList.remove('preloader-active');
    document.documentElement.style.overflow = '';

    // Show any hidden elements
    const hiddenElements = document.querySelectorAll('[style*="opacity: 0"]');
    hiddenElements.forEach(el => {
        el.style.opacity = '1';
    });
})();