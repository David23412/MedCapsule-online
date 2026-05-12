<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $currentTopic->name ?? 'Video' }} - {{ $course->name ?? 'MedCapsule' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://www.youtube-nocookie.com">
    <link rel="preconnect" href="https://www.youtube.com">
    <link rel="preconnect" href="https://i.ytimg.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --header-height: 56px;
            --motion-quick: 140ms;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #000;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            color: #f8fafc;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .video-shell {
            width: 100vw;
            height: 100vh;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: #000;
            overflow: hidden;
        }

        .video-header {
            height: var(--header-height);
            flex: 0 0 var(--header-height);
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr);
            align-items: center;
            gap: 10px;
            padding: 8px 18px;
            background: #050505;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 20;
        }

        .back-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            background: transparent;
            text-decoration: none;
            transition: color var(--motion-quick) ease, transform var(--motion-quick) ease;
        }

        .back-btn:hover,
        .back-btn:focus {
            color: #60a5fa;
            transform: translateX(-2px);
        }

        .video-title-group {
            min-width: 0;
        }

        .video-title {
            margin: 0;
            overflow: hidden;
            color: #f8fafc;
            font-size: clamp(0.9rem, 1.35vw, 1.12rem);
            font-weight: 700;
            line-height: 1.1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .video-course {
            margin-top: 2px;
            overflow: hidden;
            color: rgba(248, 250, 252, 0.68);
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .video-main {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
            background: #000;
            overflow: hidden;
        }

        .video-player {
            position: relative;
            width: 100%;
            height: 100%;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
            background: #000;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .video-player iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            display: block;
            border: 0;
            background: #000;
            pointer-events: none;
        }

        .video-protection-layer {
            position: absolute;
            inset: 0;
            z-index: 4;
            overflow: hidden;
            pointer-events: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .video-protection-watermark-grid {
            position: absolute;
            inset: -18%;
            display: grid;
            grid-template-columns: repeat(4, minmax(190px, 1fr));
            gap: 76px 92px;
            opacity: 0.2;
            transform: rotate(-24deg);
        }

        .video-protection-watermark-item {
            color: rgba(255, 255, 255, 0.72);
            font-size: clamp(0.68rem, 0.94vw, 0.9rem);
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.5;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.68);
            text-transform: uppercase;
            white-space: nowrap;
        }

        .video-custom-controls {
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 20px;
            z-index: 7;
            display: grid;
            grid-template-columns: repeat(3, 30px) minmax(0, 1fr) auto;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.52);
            pointer-events: auto;
            backdrop-filter: blur(10px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.26);
            opacity: 1;
            transform: translateY(0);
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .video-player.controls-hidden .video-custom-controls {
            opacity: 0;
            pointer-events: none;
            transform: translateY(12px);
        }

        .video-control-button {
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: #2563eb;
            cursor: pointer;
            font-size: 0.92rem;
            transition: color var(--motion-quick) ease, transform var(--motion-quick) ease;
        }

        .video-skip-button {
            color: #2563eb;
            font-size: 1rem;
        }

        .video-control-button:hover,
        .video-control-button:focus {
            color: #60a5fa;
            transform: scale(1.08);
        }

        .video-control-progress {
            width: 100%;
            min-width: 0;
            accent-color: #ef4444;
            cursor: pointer;
        }

        .video-control-time {
            min-width: 82px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 0.8rem;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .video-placeholder {
            width: 100%;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #adb5bd;
            text-align: center;
        }

        .video-placeholder i {
            margin-bottom: 1rem;
            font-size: 4rem;
            opacity: 0.5;
        }

        .video-placeholder h4 {
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .video-placeholder p {
            color: #adb5bd;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: 1ms !important;
                animation-duration: 1ms !important;
                scroll-behavior: auto !important;
            }
        }

        @media (max-width: 768px) {
            :root {
                --header-height: 50px;
            }

            .video-header {
                grid-template-columns: 28px minmax(0, 1fr);
                gap: 8px;
                padding: 7px 12px;
            }

            .back-btn {
                width: 28px;
                height: 28px;
            }

            .video-title {
                font-size: 0.86rem;
            }

            .video-course {
                font-size: 0.66rem;
            }

            .video-protection-watermark-grid {
                grid-template-columns: repeat(2, minmax(160px, 1fr));
                gap: 62px 72px;
                opacity: 0.18;
            }

            .video-custom-controls {
                left: 12px;
                right: 12px;
                bottom: 12px;
                grid-template-columns: repeat(3, 28px) minmax(0, 1fr) auto;
                gap: 8px;
                padding: 8px;
            }

            .video-control-button {
                width: 26px;
                height: 26px;
            }

            .video-control-time {
                min-width: 68px;
                font-size: 0.72rem;
            }
        }

        @media (max-width: 900px) and (orientation: landscape) {
            :root {
                --header-height: 42px;
            }

            .video-header {
                padding: 6px 12px;
            }

            .video-course {
                display: none;
            }

            .video-custom-controls {
                bottom: max(8px, env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
    <div class="video-shell" id="videoShell">
        <header class="video-header">
            <a href="{{ route('topics.forCourse', $course->id ?? 1) }}" class="back-btn" title="Back to Topics" aria-label="Back to topics">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="video-title-group">
                <h1 class="video-title">{{ $video->title ?? $currentTopic->name ?? 'Video' }}</h1>
                <div class="video-course">{{ $course->name ?? 'MedCapsule' }}</div>
            </div>
        </header>

        <main class="video-main">
            @if(isset($video) && $video && $video->youtube_video_id)
                @php
                    $playerOrigin = request()->getSchemeAndHttpHost();
                    $playerQuery = http_build_query([
                        'autoplay' => 1,
                        'rel' => 0,
                        'modestbranding' => 1,
                        'controls' => 0,
                        'enablejsapi' => 1,
                        'playsinline' => 1,
                        'disablekb' => 1,
                        'fs' => 0,
                        'iv_load_policy' => 3,
                        'origin' => $playerOrigin,
                    ]);
                    $videoProtectionUser = auth()->user();
                    $videoWatermarkText = trim(implode(' | ', array_filter([
                        $videoProtectionUser ? 'User '.$videoProtectionUser->id : null,
                        $videoProtectionUser ? (string) $videoProtectionUser->number : null,
                    ])));
                    $videoWatermarkText = $videoWatermarkText !== '' ? $videoWatermarkText : 'MedCapsule';
                @endphp
                <div class="video-player" id="videoContainer" data-protected-video="true">
                    <iframe
                        id="videoFrame"
                        title="{{ $video->title ?: $currentTopic->name }} video"
                        src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_video_id }}?{{ $playerQuery }}"
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        loading="eager"
                        referrerpolicy="strict-origin-when-cross-origin">
                    </iframe>
                    <div class="video-protection-layer" aria-hidden="true">
                        <div class="video-protection-watermark-grid">
                            @for($watermarkIndex = 0; $watermarkIndex < 16; $watermarkIndex++)
                                <div class="video-protection-watermark-item">{{ $videoWatermarkText }}</div>
                            @endfor
                        </div>
                    </div>
                    <div class="video-custom-controls" data-video-controls>
                        <button type="button" class="video-control-button video-skip-button" data-video-skip="-10" aria-label="Back 10 seconds">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button type="button" class="video-control-button" data-video-play aria-label="Play or pause video">
                            <i class="fas fa-play"></i>
                        </button>
                        <button type="button" class="video-control-button video-skip-button" data-video-skip="10" aria-label="Forward 10 seconds">
                            <i class="fas fa-redo"></i>
                        </button>
                        <input type="range" class="video-control-progress" data-video-progress min="0" max="1000" value="0" step="1" aria-label="Video progress">
                        <div class="video-control-time" data-video-time>0:00 / 0:00</div>
                    </div>
                </div>
            @else
                <div class="video-placeholder">
                    <i class="fas fa-video-slash"></i>
                    <h4>No Video Available</h4>
                    <p>{{ isset($currentTopic) ? $currentTopic->name : 'This topic' }} doesn't have a video yet.</p>
                </div>
            @endif
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @if(isset($video) && $video && $video->youtube_video_id)
        <script src="https://www.youtube.com/iframe_api"></script>
    @endif

    <script>
        (function() {
            const videoContainer = document.getElementById('videoContainer');
            if (!videoContainer) {
                return;
            }

            const userAgent = navigator.userAgent || '';
            const platform = navigator.platform || '';
            const isTouchMac = platform === 'MacIntel' && Number(navigator.maxTouchPoints || 0) > 1;
            const isPhoneOrTabletBrowser =
                /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent) ||
                isTouchMac;

            function blockProtectedEvent(event) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') {
                    event.stopImmediatePropagation();
                }

                return false;
            }

            function isEditableTarget(target) {
                return target instanceof HTMLElement && Boolean(target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"]'));
            }

            ['contextmenu', 'auxclick'].forEach((eventName) => {
                window.addEventListener(eventName, blockProtectedEvent, true);
                document.documentElement.addEventListener(eventName, blockProtectedEvent, true);
                document.body?.addEventListener(eventName, blockProtectedEvent, true);
                videoContainer.addEventListener(eventName, blockProtectedEvent, true);
            });

            ['copy', 'cut', 'dragstart', 'selectstart'].forEach((eventName) => {
                document.addEventListener(eventName, function(event) {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    blockProtectedEvent(event);
                }, true);
            });

            if (!isPhoneOrTabletBrowser) {
                document.addEventListener('keydown', function(event) {
                    if (isEditableTarget(event.target)) {
                        return;
                    }

                    const key = String(event.key || '').toLowerCase();
                    const usesModifier = event.ctrlKey || event.metaKey;
                    const usesShiftModifier = usesModifier && event.shiftKey;
                    const isBlockedShortcut =
                        key === 'f12' ||
                        key === 'printscreen' ||
                        (usesShiftModifier && ['i', 'j', 'c', 'k', 's'].includes(key)) ||
                        (usesModifier && ['u', 's', 'p', 'c', 'x'].includes(key));

                    if (!isBlockedShortcut) {
                        return;
                    }

                    blockProtectedEvent(event);
                }, true);
            }
        })();

        (function() {
            const topicId = {{ $currentTopic->id ?? 'null' }};
            const hasVideo = Boolean(document.getElementById('videoFrame'));
            if (!topicId || !hasVideo) return;

            let player = null;
            let progressUpdateInterval = null;
            let lastSentTime = 0;
            let lastSentAt = 0;
            let pendingProgressRequest = null;
            let uiUpdateInterval = null;
            let isScrubbing = false;
            let isPlayerPlaying = false;
            let controlsHideTimer = null;
            const progressEndpoint = @json(route('video.progress.update'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const videoContainer = document.getElementById('videoContainer');
            const controls = document.querySelector('[data-video-controls]');
            const playButton = document.querySelector('[data-video-play]');
            const playIcon = playButton?.querySelector('i');
            const skipButtons = document.querySelectorAll('[data-video-skip]');
            const progressInput = document.querySelector('[data-video-progress]');
            const timeDisplay = document.querySelector('[data-video-time]');

            function initializePlayer() {
                if (!window.YT || !window.YT.Player || player) {
                    return;
                }

                player = new window.YT.Player('videoFrame', {
                    events: {
                        onReady: handlePlayerReady,
                        onStateChange: handlePlayerStateChange
                    }
                });
            }

            window.onYouTubeIframeAPIReady = initializePlayer;
            if (window.YT && window.YT.Player) {
                initializePlayer();
            }

            function handlePlayerReady() {
                refreshPlayerSize();
                bindCustomControls();
                bindControlsVisibility();
                showControls();
                startUiTimer();
                startProgressTimer();
            }

            function refreshPlayerSize() {
                if (!player || typeof player.setSize !== 'function') return;

                const container = document.getElementById('videoContainer');
                if (!container) return;

                const rect = container.getBoundingClientRect();
                if (rect.width <= 0 || rect.height <= 0) return;

                player.setSize(Math.round(rect.width), Math.round(rect.height));
            }

            function bindCustomControls() {
                if (playButton && !playButton.dataset.bound) {
                    playButton.dataset.bound = 'true';
                    playButton.addEventListener('click', function(event) {
                        event.preventDefault();
                        togglePlayback();
                    });
                }

                if (progressInput && !progressInput.dataset.bound) {
                    progressInput.dataset.bound = 'true';
                    progressInput.addEventListener('input', function() {
                        isScrubbing = true;
                        updateTimeDisplayFromProgress();
                    });
                    progressInput.addEventListener('change', function() {
                        seekFromProgress();
                        isScrubbing = false;
                    });
                    progressInput.addEventListener('pointerup', function() {
                        seekFromProgress();
                        isScrubbing = false;
                    });
                }

                skipButtons.forEach((button) => {
                    if (button.dataset.bound) {
                        return;
                    }

                    button.dataset.bound = 'true';
                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        skipBy(Number(button.dataset.videoSkip || 0));
                    });
                });

                document.addEventListener('keydown', handlePlaybackKeyboard, true);
            }

            function bindControlsVisibility() {
                if (!videoContainer || !controls || controls.dataset.visibilityBound) {
                    return;
                }

                controls.dataset.visibilityBound = 'true';
                ['pointermove', 'mousemove', 'wheel', 'touchstart'].forEach((eventName) => {
                    videoContainer.addEventListener(eventName, handleViewerActivity, { passive: true });
                    document.addEventListener(eventName, handleViewerActivity, { passive: true });
                });
                controls.addEventListener('pointerenter', showControls);
                controls.addEventListener('pointerdown', showControls);
                document.addEventListener('keydown', handleViewerActivity, true);
            }

            function handleViewerActivity() {
                showControls();
                scheduleControlsHide();
            }

            function showControls() {
                if (!videoContainer) return;
                videoContainer.classList.remove('controls-hidden');
            }

            function hideControls() {
                if (!videoContainer || !isPlayerPlaying || isScrubbing) return;
                videoContainer.classList.add('controls-hidden');
            }

            function scheduleControlsHide() {
                if (controlsHideTimer) {
                    window.clearTimeout(controlsHideTimer);
                    controlsHideTimer = null;
                }

                if (!isPlayerPlaying || isScrubbing) {
                    showControls();
                    return;
                }

                controlsHideTimer = window.setTimeout(hideControls, 1600);
            }

            function isEditableTarget(target) {
                return target instanceof HTMLElement && Boolean(target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"]'));
            }

            function togglePlayback() {
                if (!player || typeof player.getPlayerState !== 'function') return;

                const state = player.getPlayerState();
                if (window.YT && state === window.YT.PlayerState.PLAYING) {
                    player.pauseVideo();
                    return;
                }

                player.playVideo();
            }

            function skipBy(seconds) {
                if (!player || typeof player.seekTo !== 'function' || typeof player.getCurrentTime !== 'function') return;

                const duration = typeof player.getDuration === 'function' ? Number(player.getDuration() || 0) : 0;
                const currentTime = Number(player.getCurrentTime() || 0);
                const targetTime = duration > 0
                    ? Math.max(0, Math.min(duration, currentTime + seconds))
                    : Math.max(0, currentTime + seconds);

                player.seekTo(targetTime, true);
                updateControlsUi();
                sendCurrentProgress(true);
            }

            function handlePlaybackKeyboard(event) {
                if (isEditableTarget(event.target) || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
                    return;
                }

                const key = String(event.key || '').toLowerCase();
                if (key === ' ' || key === 'spacebar') {
                    event.preventDefault();
                    showControls();
                    togglePlayback();
                    return;
                }

                if (key === 'arrowleft') {
                    event.preventDefault();
                    showControls();
                    skipBy(-10);
                    scheduleControlsHide();
                    return;
                }

                if (key === 'arrowright') {
                    event.preventDefault();
                    showControls();
                    skipBy(10);
                    scheduleControlsHide();
                }
            }

            function handlePlayerStateChange(event) {
                if (!window.YT || !window.YT.PlayerState) return;

                if (event.data === window.YT.PlayerState.PLAYING) {
                    isPlayerPlaying = true;
                    setPlayIcon(true);
                    startUiTimer();
                    startProgressTimer();
                    scheduleControlsHide();
                    return;
                }

                if (event.data === window.YT.PlayerState.PAUSED || event.data === window.YT.PlayerState.ENDED) {
                    isPlayerPlaying = false;
                    setPlayIcon(false);
                    showControls();
                    sendCurrentProgress(true);
                    if (event.data === window.YT.PlayerState.ENDED) {
                        stopProgressTimer();
                    }
                }
            }

            function setPlayIcon(isPlaying) {
                if (!playIcon) return;
                playIcon.classList.toggle('fa-play', !isPlaying);
                playIcon.classList.toggle('fa-pause', isPlaying);
            }

            function startProgressTimer() {
                if (progressUpdateInterval) return;
                sendCurrentProgress(false);
                progressUpdateInterval = window.setInterval(() => sendCurrentProgress(false), 5000);
            }

            function stopProgressTimer() {
                if (!progressUpdateInterval) return;
                window.clearInterval(progressUpdateInterval);
                progressUpdateInterval = null;
            }

            function startUiTimer() {
                if (uiUpdateInterval) return;
                updateControlsUi();
                uiUpdateInterval = window.setInterval(updateControlsUi, 500);
            }

            function stopUiTimer() {
                if (!uiUpdateInterval) return;
                window.clearInterval(uiUpdateInterval);
                uiUpdateInterval = null;
            }

            function updateControlsUi() {
                if (!player || typeof player.getCurrentTime !== 'function' || typeof player.getDuration !== 'function') return;

                let currentTime = 0;
                let duration = 0;
                try {
                    currentTime = Number(player.getCurrentTime() || 0);
                    duration = Number(player.getDuration() || 0);
                } catch (e) {
                    return;
                }

                if (duration > 0 && progressInput && !isScrubbing) {
                    progressInput.value = String(Math.max(0, Math.min(1000, Math.round((currentTime / duration) * 1000))));
                }

                if (timeDisplay) {
                    timeDisplay.textContent = `${formatVideoTime(currentTime)} / ${formatVideoTime(duration)}`;
                }
            }

            function updateTimeDisplayFromProgress() {
                if (!timeDisplay || !player || typeof player.getDuration !== 'function' || !progressInput) return;
                const duration = Number(player.getDuration() || 0);
                const currentTime = duration * (Number(progressInput.value || 0) / 1000);
                timeDisplay.textContent = `${formatVideoTime(currentTime)} / ${formatVideoTime(duration)}`;
            }

            function seekFromProgress() {
                if (!player || typeof player.seekTo !== 'function' || !progressInput) return;
                const duration = Number(player.getDuration?.() || 0);
                if (duration <= 0) return;
                player.seekTo(duration * (Number(progressInput.value || 0) / 1000), true);
                updateControlsUi();
                sendCurrentProgress(true);
            }

            function formatVideoTime(seconds) {
                const totalSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
                const minutes = Math.floor(totalSeconds / 60);
                const remainingSeconds = totalSeconds % 60;
                return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`;
            }

            function sendCurrentProgress(force) {
                if (!player || typeof player.getCurrentTime !== 'function' || typeof player.getDuration !== 'function') {
                    return;
                }

                let currentTime = 0;
                let duration = 0;
                try {
                    currentTime = Number(player.getCurrentTime() || 0);
                    duration = Number(player.getDuration() || 0);
                } catch (e) {
                    return;
                }

                if (currentTime <= 0 || duration <= 0) return;

                const now = Date.now();
                const timeDelta = Math.abs(currentTime - lastSentTime);
                const waitedLongEnough = now - lastSentAt >= 5000;
                if (!force && (timeDelta < 5 || !waitedLongEnough || pendingProgressRequest)) {
                    return;
                }

                lastSentTime = currentTime;
                lastSentAt = now;
                updateVideoProgress(currentTime, duration);
            }

            function updateVideoProgress(currentTime, duration) {
                if (currentTime <= 0 || duration <= 0) return;

                pendingProgressRequest = fetch(progressEndpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        topic_id: topicId,
                        current_time: currentTime,
                        duration: duration
                    })
                })
                .then(response => response.json())
                .catch(error => {
                    console.error('Progress update failed:', error);
                })
                .finally(() => {
                    pendingProgressRequest = null;
                });
            }

            window.addEventListener('beforeunload', () => {
                sendCurrentProgress(true);
                stopProgressTimer();
                stopUiTimer();
            });

            window.addEventListener('resize', refreshPlayerSize);
        })();
    </script>
</body>
</html>
