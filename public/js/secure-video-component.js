/**
 * SECURE VIDEO COMPONENT GENERATOR
 * Creates the most secure YouTube video player with complete interaction blocking
 * Blocks right-click, copy link, keyboard shortcuts, and YouTube UI access
 */

// Check if we're in a cross-origin environment
function isCrossOriginEnvironment() {
    return window.location.protocol === 'https:' && 
           (window.location.hostname.includes('ngrok') || 
            window.location.hostname.includes('localhost') ||
            window.location.hostname.includes('127.0.0.1'));
}

// Generate secure video overlay HTML
window.generateSecureVideoOverlay = function(youtubeId, videoTitle, autoplay = true, showControls = true) {
    const autoplayParam = autoplay ? '1' : '0';
    
    // Generate unique IDs for this instance
    const wrapperId = `secure-video-wrapper-${Date.now()}`;
    const iframeId = `hidden-youtube-player-${Date.now()}`;
    const overlayId = `secure-overlay-${Date.now()}`;
    const controlsId = `custom-controls-${Date.now()}`;
    
    return `
        <div id="${wrapperId}" class="secure-video-wrapper" style="
            width: 100%; 
            height: 100%; 
            background: #000; 
            position: relative; 
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        ">
            <!-- SECURE YOUTUBE PLAYER - Completely hidden iframe -->
            <iframe 
                id="${iframeId}"
                src="https://www.youtube-nocookie.com/embed/${youtubeId}?autoplay=${autoplayParam}&rel=0&modestbranding=1&showinfo=0&controls=0&disablekb=1&fs=0&iv_load_policy=3&playsinline=1&enablejsapi=1&cc_load_policy=0&color=white&hl=en&origin=${encodeURIComponent(window.location.origin)}&widget_referrer=${encodeURIComponent(window.location.href)}"
                style="
                    width: 100%; 
                    height: 100%; 
                    border: none; 
                    display: block; 
                    position: absolute; 
                    top: 0; 
                    left: 0; 
                    z-index: 1;
                    pointer-events: none;
                    opacity: 0.99;
                "
                frameborder="0"
                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                sandbox="allow-scripts allow-same-origin allow-forms"
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
            
            <!-- INVISIBLE SECURITY OVERLAY - Blocks ALL interactions -->
            <div id="${overlayId}" class="secure-overlay" style="
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10;
                background: transparent;
                pointer-events: auto;
                cursor: pointer;
            " 
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
            ></div>
            
            ${showControls ? `
            <!-- CUSTOM CONTROLS OVERLAY - Minimal, clean interface -->
            <div id="${controlsId}" class="custom-youtube-controls" style="
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(transparent, rgba(0,0,0,0.8));
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                opacity: 0;
                transition: opacity 0.3s ease;
                z-index: 30;
                pointer-events: auto;
            " 
            oncontextmenu="return false;"
            onmousedown="return false;"
            onselectstart="return false;"
            oncopy="return false;"
            oncut="return false;"
            onpaste="return false;"
            ondragstart="return false;"
            ondrop="return false;"
            >

                <!-- Play/Pause Button -->
                <button class="custom-play-pause-btn" style="
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
                " 
                oncontextmenu="return false;"
                onmousedown="return false;"
                onselectstart="return false;"
                oncopy="return false;"
                oncut="return false;"
                onpaste="return false;"
                ondragstart="return false;"
                ondrop="return false;"
                aria-label="Play/Pause video"
                >
                    <i class="fas fa-play"></i>
                </button>
                
                <div class="progress-bar" style="
                    flex: 1;
                    height: 6px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 3px;
                    cursor: pointer;
                    position: relative;
                    overflow: hidden;
                " 
                oncontextmenu="return false;"
                onmousedown="return false;"
                onselectstart="return false;"
                oncopy="return false;"
                oncut="return false;"
                onpaste="return false;"
                ondragstart="return false;"
                ondrop="return false;"
                >
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
                    user-select: none;
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
                    backdrop-filter: blur(10px);
                " 
                oncontextmenu="return false;"
                onmousedown="return false;"
                onselectstart="return false;"
                oncopy="return false;"
                oncut="return false;"
                onpaste="return false;"
                ondragstart="return false;"
                ondrop="return false;"
                aria-label="Toggle fullscreen"
                >⛶</button>
            </div>
            ` : ''}
            
            <!-- INVISIBLE COPY LINK BLOCKER - Blocks YouTube copy link button -->
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
            " 
            oncontextmenu="return false;"
            onmousedown="return false;"
            onselectstart="return false;"
            oncopy="return false;"
            oncut="return false;"
            onpaste="return false;"
            ondragstart="return false;"
            ondrop="return false;"
            ></div>
            
            <!-- KEYBOARD BLOCKER - Prevents all keyboard shortcuts -->
            <div class="keyboard-blocker" style="
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: transparent;
                z-index: 5;
                pointer-events: none;
            " 
            tabindex="-1"
            onkeydown="return false;"
            onkeyup="return false;"
            onkeypress="return false;"
            ></div>

        </div>
    `;
};

// Initialize secure video player with maximum security
window.initializeSecureVideoPlayer = function(container, youtubeId) {
    if (!container || !youtubeId) {
        console.error('❌ Missing container or YouTube ID for secure video player');
        return;
    }
    
    // Load YouTube API if not already loaded
    if (!window.YT) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
    
    // Wait for YouTube API to be ready
    if (window.YT && window.YT.Player) {
        createSecurePlayer();
    } else {
        window.onYouTubeIframeAPIReady = createSecurePlayer;
    }
    
    function createSecurePlayer() {
        const wrapper = container.querySelector('.secure-video-wrapper');
        const iframe = container.querySelector('iframe[id^="hidden-youtube-player"]');
        const overlay = container.querySelector('div[id^="secure-overlay"]');
        const controls = container.querySelector('div[id^="custom-youtube-controls"]');
        const playPauseBtn = controls ? controls.querySelector('.custom-play-pause-btn') : null;
        const progressBar = controls ? controls.querySelector('.progress-bar') : null;
        const progressFill = controls ? controls.querySelector('.progress-fill') : null;
        const timeDisplay = controls ? controls.querySelector('.time-display') : null;
        const fullscreenBtn = controls ? controls.querySelector('.fullscreen-btn') : null;
        
        let player = null;
        let isPlaying = false;
        let updateInterval = null;
        
        // Store interval globally for cleanup
        window.videoProgressInterval = null;
        
        // Create YouTube player instance with maximum security
        player = new YT.Player(iframe.id, {
            height: '100%',
            width: '100%',
            videoId: youtubeId,
            playerVars: {
                'autoplay': 0,
                'controls': 0,
                'rel': 0,
                'modestbranding': 1,
                'showinfo': 0,
                'fs': 0,
                'iv_load_policy': 3,
                'playsinline': 1,
                'disablekb': 1,
                'enablejsapi': 1,
                'origin': window.location.origin,
                'widget_referrer': window.location.href,
                'cc_load_policy': 0,
                'color': 'white',
                'hl': 'en'
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange,
                'onError': onPlayerError
            }
        });
        
        function onPlayerReady(event) {
            console.log('🔒 Secure YouTube player ready');
            
            // Set up custom controls
            setupCustomControls();
            
            // Apply maximum security measures
            applyMaximumSecurityMeasures();
        }
        
        function onPlayerStateChange(event) {
            const state = event.data;
            isPlaying = (state === YT.PlayerState.PLAYING);
            
            // Update play/pause button
            if (playPauseBtn) {
                const icon = playPauseBtn.querySelector('i');
                if (icon) {
                    icon.className = isPlaying ? 'fas fa-pause' : 'fas fa-play';
                }
            }
            
            // Show/hide controls on play/pause
            if (controls) {
                if (isPlaying) {
                    controls.style.opacity = '0';
                    setTimeout(() => {
                        if (!isPlaying) return;
                        controls.style.opacity = '0';
                    }, 3000);
                } else {
                    controls.style.opacity = '1';
                }
            }
            
            // Start/stop progress updates
            if (isPlaying) {
                startProgressUpdates();
            } else {
                stopProgressUpdates();
            }
        }
        
        function onPlayerError(event) {
            console.error('❌ YouTube player error:', event.data);
        }
        
        function setupCustomControls() {
            // Play/Pause button
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    togglePlayPause();
                });
            }
            
            // Progress bar
            if (progressBar) {
                progressBar.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const rect = this.getBoundingClientRect();
                    const clickX = e.clientX - rect.left;
                    const percentage = (clickX / rect.width) * 100;
                    seekTo(percentage);
                });
            }
            
            // Fullscreen button
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleFullscreen();
                });
            }
            
            // Overlay click to play/pause
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    togglePlayPause();
                });
            }
            
            // Show controls on hover
            if (wrapper && controls) {
                wrapper.addEventListener('mouseenter', function() {
                    controls.style.opacity = '1';
                });
                
                wrapper.addEventListener('mouseleave', function() {
                    if (isPlaying) {
                        controls.style.opacity = '0';
                    }
                });
            }
        }
        
        function togglePlayPause() {
            if (!player || !player.playVideo || !player.pauseVideo) return;
            
            try {
                if (isPlaying) {
                    player.pauseVideo();
                } else {
                    player.playVideo();
                }
            } catch (error) {
                console.error('Error toggling video:', error);
            }
        }
        
        function seekTo(percentage) {
            if (!player || !player.getDuration || !player.seekTo) return;
            
            try {
                const duration = player.getDuration();
                const seekTime = (duration * percentage) / 100;
                player.seekTo(seekTime, true);
            } catch (error) {
                console.error('Error seeking video:', error);
            }
        }
        
        function toggleFullscreen() {
            const videoContainer = container.closest('.video-container') || container;
            
            if (!document.fullscreenElement) {
                if (videoContainer.requestFullscreen) {
                    videoContainer.requestFullscreen();
                } else if (videoContainer.webkitRequestFullscreen) {
                    videoContainer.webkitRequestFullscreen();
                } else if (videoContainer.msRequestFullscreen) {
                    videoContainer.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }
        
        function startProgressUpdates() {
            if (updateInterval) return;
            
            updateInterval = setInterval(() => {
                if (!player || !isPlaying) return;
                
                try {
                    const currentTime = player.getCurrentTime();
                    const duration = player.getDuration();
                    
                    if (progressFill && duration > 0) {
                        const percentage = (currentTime / duration) * 100;
                        progressFill.style.width = percentage + '%';
                    }
                    
                    if (timeDisplay) {
                        const currentFormatted = formatTime(currentTime);
                        const durationFormatted = formatTime(duration);
                        timeDisplay.textContent = `${currentFormatted} / ${durationFormatted}`;
                    }
                } catch (error) {
                    console.error('Error updating progress:', error);
                }
            }, 100);
            
            // Store interval globally for cleanup
            window.videoProgressInterval = updateInterval;
        }
        
        function stopProgressUpdates() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
                window.videoProgressInterval = null;
            }
        }
        
        function formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        function applyMaximumSecurityMeasures() {
            // Block ALL keyboard shortcuts globally
            document.addEventListener('keydown', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block right-click context menu globally
            document.addEventListener('contextmenu', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block text selection globally
            document.addEventListener('selectstart', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block drag and drop globally
            document.addEventListener('dragstart', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block copy operations globally
            document.addEventListener('copy', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block cut operations globally
            document.addEventListener('cut', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block paste operations globally
            document.addEventListener('paste', function(e) {
                const videoArea = e.target.closest('.secure-video-wrapper');
                if (videoArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
            
            // Block iframe focus to prevent keyboard shortcuts
            if (iframe) {
                iframe.addEventListener('focus', function() {
                    this.blur();
                });
                
                iframe.addEventListener('load', function() {
                    // Ensure iframe stays unfocused
                    setTimeout(() => {
                        this.blur();
                    }, 100);
                });
            }
        }
    }
};

// Also define as a regular function for backward compatibility
function generateSecureVideoOverlay(youtubeId, videoTitle, autoplay = true, showControls = true) {
    return window.generateSecureVideoOverlay(youtubeId, videoTitle, autoplay, showControls);
}

// Global cleanup function for stopping videos
window.stopAllVideos = function() {
    console.log('🛑 Stopping all videos globally...');
    
    // Clear any running intervals
    if (window.videoProgressInterval) {
        clearInterval(window.videoProgressInterval);
        window.videoProgressInterval = null;
    }
    
    // Stop all YouTube players
    if (window.YT && window.YT.get) {
        try {
            const players = document.querySelectorAll('iframe[id^="hidden-youtube-player"]');
            players.forEach(iframe => {
                if (iframe.id) {
                    const player = window.YT.get(iframe.id);
                    if (player && typeof player.stopVideo === 'function') {
                        player.stopVideo();
                    }
                }
            });
        } catch (error) {
            console.log('⚠️ Error stopping YouTube players:', error);
        }
    }
    
    console.log('✅ All videos stopped');
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        generateSecureVideoOverlay, 
        initializeSecureVideoPlayer,
        stopAllVideos
    };
}

// Log successful loading
console.log('🔒 Secure video component loaded successfully');
console.log('🌐 Environment check:', {
    protocol: window.location.protocol,
    hostname: window.location.hostname,
    isCrossOrigin: isCrossOriginEnvironment()
});
