// EMERGENCY PRELOADER FIX - Run this if preloader gets stuck
(function() {
    'use strict';
    
// console.log('🚨 EMERGENCY PRELOADER FIX ACTIVATED'); // Removed for production
    
    // Force hide any stuck preloader
    const preloader = document.getElementById('lightningPreloader');
    if (preloader) {
        preloader.classList.add('hidden');
// console.log('✅ Preloader force hidden'); // Removed for production
        
        setTimeout(() => {
            if (preloader.parentNode) {
                preloader.parentNode.removeChild(preloader);
// console.log('✅ Preloader removed from DOM'); // Removed for production
            }
        }, 300);
    }
    
    // Restore body scrolling
    document.body.classList.remove('preloader-active');
    document.documentElement.style.overflow = '';
// console.log('✅ Body scrolling restored'); // Removed for production
    
    // Show any hidden elements
    const hiddenElements = document.querySelectorAll('[style*="opacity: 0"]');
    hiddenElements.forEach(el => {
        el.style.opacity = '1';
    });
// console.log('✅ Hidden elements restored'); // Removed for production
    
// console.log('🎉 EMERGENCY FIX COMPLETE - Page should be working now!'); // Removed for production
})();