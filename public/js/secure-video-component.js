/**
 * SECURE VIDEO COMPONENT GENERATOR
 * Generates the secure video overlay HTML for YouTube videos
 * Bypasses cross-origin restrictions while maintaining security
 */

// Check if we're in a cross-origin environment (like ngrok)
function isCrossOriginEnvironment() {
    return window.location.protocol === 'https:' && 
           (window.location.hostname.includes('ngrok') || 
            window.location.hostname.includes('localhost') ||
            window.location.hostname.includes('127.0.0.1'));
}

// Ensure function is available globally
window.generateSecureVideoOverlay = function(youtubeId, videoTitle, autoplay = true, showControls = true) {
    const autoplayParam = autoplay ? '1' : '0';
    
    // Always use the full YouTube player - we'll handle cross-origin differently
    return `
        <div class="secure-video-wrapper" style="width: 100%; height: 100%; background: #000; position: relative; overflow: hidden;">
            <!-- SECURE YOUTUBE PLAYER - Hidden iframe with custom controls -->
            <iframe 
                id="hiddenYoutubePlayer"
                src="https://www.youtube-nocookie.com/embed/${youtubeId}?autoplay=${autoplayParam}&rel=0&modestbranding=1&showinfo=0&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playsinline=1&enablejsapi=1&cc_load_policy=0&color=white&hl=en&origin=${encodeURIComponent(window.location.origin)}"
                style="width: 100%; height: 100%; border: none; display: block; position: absolute; top: 0; left: 0; z-index: 1;"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"
                title="${videoTitle}">
            </iframe>
            
            ${showControls ? `
            <!-- CUSTOM CONTROLS OVERLAY - Full control over video -->
            <div class="custom-youtube-controls" style="
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
                z-index: 100;
                pointer-events: auto;
            ">

                
                <div class="progress-bar" style="
                    flex: 1;
                    height: 6px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 3px;
                    cursor: pointer;
                    position: relative;
                    overflow: hidden;
                ">
                    <div class="progress-fill" style="
                        height: 100%;
                        background: linear-gradient(90deg, #2563eb, #3b82f6);
                        border-radius: 3px;
                        width: 0%;
                        transition: width 0.1s ease;
                        position: relative;
                    ">
                        <div class="progress-glow" style="
                            position: absolute;
                            top: 0;
                            right: 0;
                            width: 20px;
                            height: 100%;
                            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
                            border-radius: 3px;
                        "></div>
                    </div>
                </div>
                
                <div class="time-display" style="
                    color: white;
                    font-size: 0.9rem;
                    min-width: 120px;
                    text-align: center;
                    font-weight: 500;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.8);
                ">0:00 / 0:00</div>
                
                <button class="fullscreen-btn" style="
                    background: rgba(255,255,255,0.1);
                    color: white;
                    border: 1px solid rgba(255,255,255,0.2);
                    border-radius: 4px;
                    padding: 0.4rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    font-size: 0.9rem;
                    min-width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">⛶</button>
            </div>
            ` : ''}
            
            <!-- SECURITY WATERMARK -->
            <div class="security-watermark" style="
                position: absolute;
                top: 20px;
                right: 20px;
                color: #2563eb;
                font-size: 1.5rem;
                font-weight: 900;
                z-index: 1000;
                pointer-events: auto;
                user-select: none;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                text-shadow: 0 2px 4px rgba(0,0,0,0.9), 0 4px 8px rgba(0,0,0,0.6);
                letter-spacing: 0.05em;
                text-transform: uppercase;
                font-stretch: expanded;
            ">
                MEDCAPSULE
            </div>
            
            <!-- INVISIBLE SECURITY OVERLAY - Actually blocks copy link clicks -->
            <div class="copy-link-blocker" style="
                position: absolute;
                top: 0;
                right: 0;
                width: 300px;
                height: 150px;
                background: transparent;
                z-index: 999;
                pointer-events: auto;
                cursor: not-allowed;
            "></div>
            

        </div>
    `;
};

// Also define as a regular function for backward compatibility
function generateSecureVideoOverlay(youtubeId, videoTitle, autoplay = true, showControls = true) {
    return window.generateSecureVideoOverlay(youtubeId, videoTitle, autoplay, showControls);
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { generateSecureVideoOverlay };
}

// Log successful loading and environment info
// console.log('🔒 Secure video component loaded successfully'); // Removed for production
// console.log('🌐 Environment check:', { // Removed for production
    protocol: window.location.protocol,
    hostname: window.location.hostname,
    isCrossOrigin: isCrossOriginEnvironment()
});
