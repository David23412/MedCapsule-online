@props([
    'youtubeId' => '',
    'videoTitle' => ''
])

<style>
    /* Base modal styles */
    #secureVideoModal {
        transition: all 0.3s ease;
    }
    
    #secureVideoModal .modal-xl {
        max-width: 95vw;
        width: 90%;
        height: 90vh;
        margin: 1rem auto;
        transition: all 0.3s ease;
    }
    
    #secureVideoModal .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #000;
        border: none;
        border-radius: 0;
    }
    
    #secureVideoModal .modal-body {
        flex: 1;
        padding: 0;
        display: flex;
        background: #000;
    }
    
    #secureVideoModal .ratio {
        flex: 1;
        min-height: 0;
    }
    
    #player {
        width: 100%;
        height: 100%;
        border: none;
        position: relative;
        z-index: 1;
    }
    
    .video-overlay {
        z-index: 1; /* Lower than modal controls */
        background: transparent;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: auto;
    }
    
    .modal-header {
        position: relative;
        z-index: 10; /* Higher than overlay */
    }
    
    .btn-close, .btn-fullscreen {
        position: relative;
        z-index: 20; /* Highest z-index */
    }
    
    .modal-header {
        padding: 1rem;
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        color: #212529;
        transition: all 0.3s ease;
    }
    
    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-close {
        margin: 0;
        opacity: 0.8;
    }
    
    .btn-fullscreen {
        color: #6c757d;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .btn-fullscreen:hover {
        opacity: 1;
        color: #fff;
    }
    
    /* Fullscreen styles */
    #secureVideoModal.fullscreen-mode {
        padding: 0;
    }
    
    #secureVideoModal.fullscreen-mode .modal-dialog {
        max-width: 100%;
        width: 100%;
        height: 100vh;
        margin: 0;
        padding: 0;
    }
    
    #secureVideoModal.fullscreen-mode .modal-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1055;
        background: rgba(255, 255, 255, 0.95);
        opacity: 0;
        transition: opacity 0.3s;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    #secureVideoModal.fullscreen-mode .modal-header:hover {
        opacity: 1;
    }
    
    #secureVideoModal.fullscreen-mode .modal-content {
        border-radius: 0;
    }

    /* Secure Video Overlay Styles */
    .secure-video-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        background: #000;
        overflow: hidden;
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .secure-video-wrapper iframe {
        pointer-events: none !important;
    }

    .secure-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10;
        background: transparent;
        pointer-events: auto;
        cursor: pointer;
    }

    .custom-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        background: rgba(0, 0, 0, 0.8);
        border: 3px solid rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 20;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    }

    .custom-play-overlay:hover {
        background: rgba(0, 0, 0, 0.9);
        border-color: #3b82f6;
        transform: translate(-50%, -50%) scale(1.1);
    }

    .custom-youtube-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.9));
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 30;
        pointer-events: auto;
    }

    .secure-video-wrapper:hover .custom-youtube-controls {
        opacity: 1;
    }

    .custom-play-pause-btn {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 4px;
        padding: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1rem;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }

    .custom-play-pause-btn:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
    }

    .security-watermark {
        position: absolute;
        top: 20px;
        right: 20px;
        color: #2563eb;
        font-size: 1.5rem;
        font-weight: 900;
        z-index: 1000;
        pointer-events: none;
        user-select: none;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        text-shadow: 0 2px 4px rgba(0,0,0,0.9), 0 4px 8px rgba(0,0,0,0.6);
        letter-spacing: 0.05em;
        text-transform: uppercase;
        font-stretch: expanded;
    }

    /* Video Modal Styles */
    .video-modal .modal-dialog {
        max-width: 90vw;
        width: 90vw;
        margin: 1rem auto;
    }

    .video-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        background: #000;
    }

    .video-modal .modal-header {
        background: #ffffff;
        color: #333;
        border: none;
        border-bottom: 1px solid #e9ecef;
        padding: 0.5rem 1rem;
        box-shadow: none;
        min-height: 40px;
    }

    .video-modal .modal-title {
        font-weight: 400;
        font-size: 0.95rem;
        color: #2563eb;
        margin: 0;
    }

    .video-modal .btn-close {
        color: #6c757d;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }

    .video-modal .btn-close:hover {
        opacity: 1;
        color: #495057;
    }

    .video-modal .header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .video-modal .modal-header .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        line-height: 1;
    }

    .video-modal .btn-outline-secondary {
        border-color: #dee2e6;
        color: #6c757d;
        background: transparent;
        transition: all 0.2s ease;
    }

    .video-modal .btn-outline-secondary:hover {
        border-color: #adb5bd;
        color: #495057;
        background-color: #f8f9fa;
    }

    .video-modal .modal-body {
        padding: 0;
        background: #000;
    }

    .video-container {
        position: relative;
        width: 100%;
        height: 500px;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-loading {
        color: white;
        font-size: 1.1rem;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .video-container iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    /* Responsive video sizing */
    @media (max-width: 768px) {
        .video-modal .modal-dialog {
            max-width: 98vw;
            width: 98vw;
            margin: 0.5rem auto;
        }
    }

    @media (min-width: 1200px) {
        .video-modal .modal-dialog {
            max-width: 90vw;
            width: 90vw;
        }
    }

    /* Ensure modal opens with proper dimensions */
    .video-modal.show .modal-dialog {
        transform: none !important;
    }

    /* Smooth modal transitions */
    .video-modal .modal-dialog {
        transition: all 0.3s ease-out;
    }

    /* Prevent modal from jumping during open */
    .video-modal .modal-content {
        transform: none !important;
    }

    /* Fullscreen modal on smaller screens */
    @media (max-width: 991.98px) {
        .video-modal .modal-dialog {
            max-width: 100vw;
            width: 100vw;
            height: 100vh;
            margin: 0;
        }
        
        .video-modal .modal-content {
            height: 100vh;
            border-radius: 0;
        }
        
        .video-modal .modal-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .video-container {
            flex: 1;
            padding-bottom: 0;
            height: 100%;
        }
    }
    
    /* SMART ZOOM - Modal expansion (not iframe) */
    .video-modal.video-expanded .modal-dialog {
        max-width: 100vw !important;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .video-modal.video-expanded .modal-content {
        height: 100vh !important;
        border-radius: 0 !important;
        background: #000 !important;
    }
    
    .video-modal.video-expanded .modal-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1055 !important;
        background: rgba(255, 255, 255, 0.95) !important;
        opacity: 0 !important;
        transition: opacity 0.3s !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
    }
    
    .video-modal.video-expanded .modal-header:hover {
        opacity: 1 !important;
    }
    
    .video-modal.video-expanded .modal-body {
        padding: 0 !important;
        height: 100vh !important;
        background: #000 !important;
    }
    
    .video-modal.video-expanded .video-container {
        height: 100vh !important;
        background: #000 !important;
    }
    
    /* Ensure security overlay covers entire expanded modal */
    .video-modal.video-expanded .secure-overlay {
        width: 100% !important;
        height: 100% !important;
        z-index: 10 !important;
    }
    
    /* Smooth transitions for zoom */
    .video-modal .modal-dialog,
    .video-modal .modal-content,
    .video-modal .video-container {
        transition: all 0.3s ease-out !important;
    }

    /* Disable text selection globally on video modal */
    .video-modal * {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
</style>

<!-- Video Modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalTitle">Video Lecture</h5>
                <div class="header-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="resizeVideoBtn" title="Toggle video size">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="video-container" id="videoContainer">
                    <div class="video-loading">Loading video...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global video player functions
window.playVideo = function(videoId, videoTitle) {
    console.log('🎬 Starting video playback for:', videoId, videoTitle);
    
    const modal = document.getElementById('videoModal');
    const modalTitle = document.getElementById('videoModalTitle');
    const videoContainer = document.getElementById('videoContainer');
    
    // Set modal title
    modalTitle.textContent = videoTitle;
    
    // Show loading state
    videoContainer.innerHTML = '<div class="video-loading">🎬 Loading video...</div>';
    
    // Show modal with proper sizing
    const bsModal = new bootstrap.Modal(modal, {
        backdrop: 'static',
        keyboard: false
    });
    bsModal.show();
    
    // Ensure modal opens with proper dimensions
    setTimeout(() => {
        modal.classList.add('show');
    }, 150);
    
    // Initialize resize functionality - SMART ZOOM (modal only, not iframe)
    const resizeBtn = document.getElementById('resizeVideoBtn');
    if (resizeBtn) {
        resizeBtn.addEventListener('click', function() {
            const isExpanded = modal.classList.contains('video-expanded');
            if (isExpanded) {
                // Compress modal back to normal size
                modal.classList.remove('video-expanded');
                this.innerHTML = '<i class="fas fa-expand"></i>';
                this.title = 'Expand video';
                
                // Ensure security overlay covers the entire modal area
                const overlay = videoContainer.querySelector('.secure-overlay');
                if (overlay) {
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';
                }
            } else {
                // Expand modal to full screen (NOT the iframe)
                modal.classList.add('video-expanded');
                this.innerHTML = '<i class="fas fa-compress"></i>';
                this.title = 'Compress video';
                
                // Ensure security overlay covers the entire expanded modal
                const overlay = videoContainer.querySelector('.secure-overlay');
                if (overlay) {
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';
                }
            }
        });
    }
    
    // AUTO-STOP VIDEO ON CLOSE - Enhanced close button functionality
    const closeBtn = modal.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            // Stop any running video
            stopVideoAndCleanup();
        });
    }
    
    // Also handle modal hidden event for additional cleanup
    modal.addEventListener('hidden.bs.modal', function() {
        stopVideoAndCleanup();
    });
    
    // Function to stop video and cleanup
    function stopVideoAndCleanup() {
        console.log('🛑 Stopping video and cleaning up...');
        
        // Use global cleanup function if available
        if (typeof window.stopAllVideos === 'function') {
            window.stopAllVideos();
        } else {
            // Fallback cleanup
            const iframe = videoContainer.querySelector('iframe[id^="hidden-youtube-player"]');
            if (iframe && iframe.id) {
                try {
                    if (window.YT && window.YT.get) {
                        const player = window.YT.get(iframe.id);
                        if (player && typeof player.stopVideo === 'function') {
                            player.stopVideo();
                            console.log('✅ YouTube player stopped');
                        }
                    }
                } catch (error) {
                    console.log('⚠️ Could not stop YouTube player directly:', error);
                }
            }
            
            // Clear any running intervals
            if (window.videoProgressInterval) {
                clearInterval(window.videoProgressInterval);
                window.videoProgressInterval = null;
            }
        }
        
        // Reset video container
        videoContainer.innerHTML = '<div class="video-loading">🎬 Loading video...</div>';
        
        console.log('✅ Video cleanup completed');
    }
    
    // Get the YouTube video ID from the database
    console.log('🎥 Creating YouTube video player for video ID:', videoId);
    
    // Fetch the video details to get the YouTube video ID
    fetch(`/api/video/${videoId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(videoData => {
        if (videoData.success && videoData.video.youtube_video_id) {
            const youtubeId = videoData.video.youtube_video_id;
            console.log('✅ YouTube video ID found:', youtubeId);
            
                // Create SECURE YouTube custom player using component function
    if (typeof window.generateSecureVideoOverlay === 'function') {
        videoContainer.innerHTML = window.generateSecureVideoOverlay(youtubeId, videoTitle, true, true);
        
        // Initialize secure video player with maximum security
        if (typeof window.initializeSecureVideoPlayer === 'function') {
            window.initializeSecureVideoPlayer(videoContainer, youtubeId);
        } else {
            console.error('❌ initializeSecureVideoPlayer function not found');
        }
    } else {
        console.error('❌ generateSecureVideoOverlay function not found. Loading fallback...');
        // Fallback to secure iframe with maximum security
        videoContainer.innerHTML = `
            <div class="secure-video-wrapper" style="width: 100%; height: 100%; background: #000; position: relative; overflow: hidden; user-select: none;">
                <iframe 
                    src="https://www.youtube-nocookie.com/embed/${youtubeId}?autoplay=1&rel=0&modestbranding=1&showinfo=0&controls=0&disablekb=1&fs=0&iv_load_policy=3&playsinline=1&origin=${encodeURIComponent(window.location.origin)}"
                    style="width: 100%; height: 100%; border: none; display: block; pointer-events: none;"
                    frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    title="${videoTitle}"
                    oncontextmenu="return false;"
                    onmousedown="return false;"
                    onselectstart="return false;"
                    oncopy="return false;"
                    oncut="return false;"
                    onpaste="return false;"
                    ondragstart="return false;"
                    ondrop="return false;"
                    onkeydown="return false;"
                    onkeyup="return false;"
                    onkeypress="return false;"
                ></iframe>
                <!-- Security overlay to block all interactions -->
                <div class="secure-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; background: transparent; pointer-events: auto; cursor: pointer;" oncontextmenu="return false;" onmousedown="return false;" onselectstart="return false;" oncopy="return false;" oncut="return false;" onpaste="return false;" ondragstart="return false;" ondrop="return false;" onkeydown="return false;" onkeyup="return false;" onkeypress="return false;"></div>
            </div>
        `;
    }
            
            console.log('✅ Secure YouTube custom player created successfully');
        } else {
            throw new Error('Video not found or no YouTube ID available');
        }
    })
    .catch(error => {
        console.error('❌ Failed to load video details:', error);
        videoContainer.innerHTML = '<div class="video-loading text-danger">❌ Failed to load video. Please check the video configuration.</div>';
    });
};

// Ensure secure video component is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load secure video component if not already loaded
    if (typeof window.generateSecureVideoOverlay === 'undefined') {
        const script = document.createElement('script');
        script.src = '/js/secure-video-component.js';
        script.onload = function() {
            console.log('✅ Secure video component loaded successfully');
        };
        script.onerror = function() {
            console.error('❌ Failed to load secure video component');
        };
        document.head.appendChild(script);
    }
    
    // 🔒 ENHANCED SECURITY: Prevent developer tools access
    let devtools = { open: false, orientation: null };
    
    // Detect developer tools opening
    setInterval(() => {
        const threshold = 160;
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if (widthThreshold || heightThreshold) {
            if (!devtools.open) {
                devtools.open = true;
                console.warn('🔒 Security: Developer tools detected');
            }
        } else {
            devtools.open = false;
        }
    }, 500);

            // 🔒 MAXIMUM SECURITY: Block ALL interactions on video modal
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK ALL KEYBOARD SHORTCUTS
        document.addEventListener('keydown', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK DRAG AND DROP
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK COPY OPERATIONS
        document.addEventListener('copy', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK CUT OPERATIONS
        document.addEventListener('cut', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK PASTE OPERATIONS
        document.addEventListener('paste', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // 🔒 BLOCK TEXT SELECTION
        document.addEventListener('selectstart', function(e) {
            if (e.target.closest('.video-modal') || e.target.closest('.secure-video-wrapper')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

    // Suppress CORS warnings from YouTube tracking (these don't affect functionality)
    const originalError = console.error;
    console.error = function(...args) {
        if (args[0] && typeof args[0] === 'string' && 
            (args[0].includes('Cross-origin redirection') || 
             args[0].includes('Cross-Origin Resource Sharing') ||
             args[0].includes('access control checks'))) {
            // Suppress YouTube tracking CORS warnings
            return;
        }
        originalError.apply(console, args);
    };
});
</script>

<!-- Load secure video component script -->
<script src="/js/secure-video-component.js"></script>

<!-- Test script to verify implementation -->
<script src="/js/test-secure-video.js"></script>
