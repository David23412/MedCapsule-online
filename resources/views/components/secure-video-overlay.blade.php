    @props([
    'youtubeId' => '',
    'videoTitle' => '',
    'autoplay' => true,
    'showControls' => true
])

<div class="secure-video-wrapper" style="width: 100%; height: 100%; background: #000; position: relative; overflow: hidden;">
    <!-- SECURE YOUTUBE PLAYER - Privacy Enhanced Mode -->
    <iframe 
        id="hiddenYoutubePlayer"
        src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?autoplay={{ $autoplay ? '1' : '0' }}&rel=0&modestbranding=1&showinfo=0&controls=0&disablekb=1&fs=0&iv_load_policy=3&playsinline=1&enablejsapi=1&cc_load_policy=0&color=white&hl=en&origin={{ url('/') }}"
        style="width: 100%; height: 100%; border: none; display: block; position: absolute; top: 0; left: 0; z-index: 1; pointer-events: none;"
        frameborder="0"
        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
        sandbox="allow-scripts allow-same-origin"
        allowfullscreen
        title="{{ $videoTitle }}">
    </iframe>
    
    <!-- BULLETPROOF CLICK BLOCKER - Covers entire video area -->
    <div id="videoClickHandler" style="
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 50;
        background: transparent;
        cursor: pointer;
    "></div>
    
    <!-- EXTRA SECURITY - Block top area where YouTube logo/title appears -->
    <div style="
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100px;
        z-index: 999;
        background: transparent;
        pointer-events: auto;
    "></div>
    

    
    @if($showControls)
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
        <!-- SKIP BACKWARD BUTTON -->
        <button id="skipBackward" style="
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            padding: 0.4rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        " title="Skip backward 10 seconds">⏪</button>
        
        <!-- PLAY/PAUSE BUTTON -->
        <button id="playPauseBtn" style="
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            padding: 0.4rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        ">▶️</button>
        
        <!-- SKIP FORWARD BUTTON -->
        <button id="skipForward" style="
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            padding: 0.4rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        " title="Skip forward 10 seconds">⏩</button>

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
    @endif
    

    
    <!-- SECURITY WATERMARK -->
    <div class="security-watermark" style="
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
        font-stretch: expanded;
    ">
        <strong>MedCapsule</strong>
    </div>
    

    
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('hiddenYoutubePlayer');
    const videoClickHandler = document.getElementById('videoClickHandler');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const skipBackward = document.getElementById('skipBackward');
    const skipForward = document.getElementById('skipForward');
    
    let isPlaying = {{ $autoplay ? 'true' : 'false' }};
    
    // BULLETPROOF COPY LINK BLOCKING
    // Block all clicks on the iframe area
    videoClickHandler.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        togglePlayPause();
    });
    
    // Block right-click context menu
    videoClickHandler.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Block all mouse events that could trigger YouTube UI
    ['mousedown', 'mouseup', 'mousemove', 'mouseover', 'mouseout'].forEach(event => {
        videoClickHandler.addEventListener(event, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    // YouTube API functions
    function sendYouTubeCommand(command, args = '') {
        iframe.contentWindow.postMessage(
            JSON.stringify({
                event: 'command',
                func: command,
                args: args
            }), 
            '*'
        );
    }
    
    function togglePlayPause() {
        if (isPlaying) {
            sendYouTubeCommand('pauseVideo');
            playPauseBtn.innerHTML = '▶️';
            isPlaying = false;
        } else {
            sendYouTubeCommand('playVideo');
            playPauseBtn.innerHTML = '⏸️';
            isPlaying = true;
        }
    }
    
    function skipBackwardVideo() {
        sendYouTubeCommand('seekTo', [Math.max(0, getCurrentTime() - 10), true]);
    }
    
    function skipForwardVideo() {
        sendYouTubeCommand('seekTo', [getCurrentTime() + 10, true]);
    }
    
    function getCurrentTime() {
        // This would need to be tracked via YouTube API events
        // For now, we'll estimate based on when video started
        return 0; // Placeholder - would need proper time tracking
    }
    
    // Button event listeners
    playPauseBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        togglePlayPause();
    });
    
    skipBackward.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        skipBackwardVideo();
    });
    
    skipForward.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        skipForwardVideo();
    });
    
    // Listen for YouTube API events
    window.addEventListener('message', function(event) {
        if (event.origin !== 'https://www.youtube-nocookie.com') return;
        
        try {
            const data = JSON.parse(event.data);
            if (data.event === 'video-progress') {
                // Update progress bar and time display
                // Implementation would go here
            }
        } catch (e) {
            // Ignore invalid messages
        }
    });
    
    // Additional security: Block any attempts to navigate away
    window.addEventListener('beforeunload', function(e) {
        // This helps prevent any sneaky redirects
    });
    
    // Block keyboard shortcuts that might trigger YouTube features
    document.addEventListener('keydown', function(e) {
        // Block common YouTube shortcuts
        if (e.target === videoClickHandler || videoClickHandler.contains(e.target)) {
            const blockedKeys = ['c', 'C', 'l', 'L', 'k', 'K', 'j', 'J', 'f', 'F'];
            if (blockedKeys.includes(e.key)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });
});
</script>

<style>
/* SECURE VIDEO WRAPPER STYLES */
.secure-video-wrapper {
    width: 100%;
    height: 100%;
    background: #000;
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.secure-video-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1;
}

.secure-video-wrapper:hover .custom-youtube-controls {
    opacity: 1;
}

/* RESPONSIVE DESIGN */
@media (max-width: 768px) {
    .secure-video-wrapper {
        border-radius: 8px;
    }
    
    .custom-youtube-controls {
        padding: 1rem;
        gap: 0.75rem;
    }
    

    
    .time-display {
        min-width: 100px;
        font-size: 0.8rem;
    }
    
    .play-overlay {
        padding: 2rem 2.5rem;
        font-size: 1.4rem;
    }
    
    .play-overlay span:first-child {
        font-size: 2rem;
    }
}
</style>
