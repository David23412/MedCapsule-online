<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $currentTopic->name ?? 'Video' }} - {{ $course->name ?? 'MedCapsule' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #000;
        }
        
        .video-layout {
            display: flex;
            height: 100vh;
            position: relative;
        }
        
        .topics-sidebar {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            background: white;
            overflow-y: auto;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            transition: all 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            z-index: 100;
            transform: translateX(0);
        }
        
        .topics-sidebar.collapsed {
            width: 0;
            min-width: 0;
            max-width: 0;
            border-right: none;
            overflow: hidden;
            transform: translateX(-100%);
            opacity: 0;
            visibility: hidden;
        }
        
        .sidebar-toggle {
            position: absolute;
            top: 50%;
            right: -15px;
            transform: translateY(-50%);
            width: 30px;
            height: 60px;
            background: white;
            border: 1px solid #e9ecef;
            border-left: none;
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 200;
            transition: all 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-toggle:hover {
            background: #f8f9fa;
            transform: translateY(-50%) scale(1.1);
        }
        
        .sidebar-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        .sidebar-toggle i {
            color: #007bff;
            font-size: 0.9rem;
            transition: transform 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .topics-sidebar.collapsed + .sidebar-toggle {
            right: -30px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transform: translateY(-50%) translateX(-100%);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .sidebar-header h3 {
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .course-name {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .back-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #007bff;
            text-decoration: none;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .back-btn:hover {
            background: white;
            color: #0056b3;
            transform: scale(1.05);
        }
        
        .topics-list {
            flex: 1;
            padding: 0;
        }
        
        .topic-item {
            border-bottom: 2px solid #e9ecef;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .topic-item::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
            opacity: 0.5;
        }
        
        .topic-item:hover {
            background: #f8f9fa;
        }
        
        .topic-item.active {
            background: #e3f2fd;
            border-left: 4px solid #007bff;
        }
        
        .topic-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .topic-item:hover .topic-link {
            color: #0056b3;
        }
        
        .topic-item.active .topic-link {
            color: #0056b3;
            font-weight: 600;
        }
        
        .progress-circle {
            width: 24px;
            height: 24px;
            margin-left: auto;
            margin-right: 0.5rem;
            flex-shrink: 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .progress-circle svg {
            width: 24px;
            height: 24px;
            transform: rotate(-90deg);
        }
        
        .progress-circle .progress-bg {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 2;
        }
        
        .progress-circle .progress-bar {
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            transition: stroke-dasharray 0.3s ease, stroke 0.3s ease;
        }
        
        /* Color coding based on progress */
        .progress-circle.not-started .progress-bar {
            stroke: #e9ecef;
        }
        
        .progress-circle.low-progress .progress-bar {
            stroke: #dc3545; /* Red for 1-30% */
        }
        
        .progress-circle.medium-progress .progress-bar {
            stroke: #ffc107; /* Yellow for 31-70% */
        }
        
        .progress-circle.high-progress .progress-bar {
            stroke: #28a745; /* Green for 71-99% */
        }
        
        .progress-circle.completed .progress-bar {
            stroke: #28a745; /* Green for 100% */
        }
        
        /* Small dot in center for completed */
        .progress-circle.completed::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: #28a745;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .video-main {
            flex: 1;
            background: #000;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            width: calc(100vw - 280px);
        }
        
        .video-layout.sidebar-collapsed .video-main {
            width: 100vw;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
        }
        
        .video-player {
            position: relative;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        
        .video-player iframe {
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: auto;
        }
        
        /* Security Overlay - Invisible but blocks right-click */
        .video-security-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
            background: transparent;
        }
        
        /* Disable text selection on video area */
        .video-main * {
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
        }
        
        .video-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            text-align: center;
            padding: 2rem;
        }
        
        .video-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .video-placeholder h4 {
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .video-placeholder p {
            color: #adb5bd;
        }
        
        /* Custom scrollbar for sidebar */
        .topics-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .topics-sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .topics-sidebar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .topics-sidebar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .video-icon {
            margin-right: 0.5rem;
            opacity: 0.7;
        }
        
        .topic-item.has-video .video-icon {
            color: #28a745;
            opacity: 1;
        }
        
        .topic-item.no-video .video-icon {
            color: #dc3545;
        }
        
        /* Smooth resize handle */
        .sidebar-resizer {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: transparent;
            cursor: col-resize;
            z-index: 150;
            transition: background 0.2s ease;
        }
        
        .sidebar-resizer:hover {
            background: #007bff;
        }
        
        .sidebar-resizer.resizing {
            background: #007bff;
        }
        
        /* Animation for content fade */
        .sidebar-content {
            opacity: 1;
            transition: opacity 0.05s ease;
            transform: translateX(0);
        }
        
        .topics-sidebar.collapsed .sidebar-content {
            opacity: 0;
            transform: translateX(-20px);
        }
        
        /* Floating toggle when collapsed */
        .floating-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1001;
            transition: all 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-100px) scale(0.8);
            pointer-events: none;
        }
        
        .floating-toggle:hover {
            background: white;
            transform: translateX(0) scale(1.1);
        }
        
        .floating-toggle:active {
            transform: translateX(0) scale(0.9);
        }
        
        .floating-toggle i {
            color: #007bff;
            font-size: 1.2rem;
        }
        
        .video-layout.sidebar-collapsed .floating-toggle {
            opacity: 1;
            visibility: visible;
            transform: translateX(0) scale(1);
            pointer-events: all;
        }
        
        /* Ultra-smooth transitions for all interactive elements */
        .topic-link, .progress-circle, .video-icon {
            transition: all 0.1s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        /* Enhanced hover effects */
        .topic-item:hover .progress-circle {
            transform: scale(1.15);
        }
        
        .topic-item:hover .video-icon {
            transform: scale(1.15);
        }
        
        .topic-item:active .topic-link {
            transform: scale(0.98);
        }
        
        /* Instant feedback */
        .topic-item {
            transition: all 0.05s ease;
        }
        
        .topic-item:hover {
            background: #f8f9fa;
            transform: translateX(2px);
        }
    </style>
</head>
<body>
    <div class="video-layout" id="videoLayout">
        <!-- Topics Sidebar -->
        <div class="topics-sidebar" id="topicsSidebar">
            <!-- Resize Handle -->
            <div class="sidebar-resizer" id="sidebarResizer"></div>
            
            <div class="sidebar-content">
                <div class="sidebar-header">
                    <h3>{{ $course->name ?? 'Course Topics' }}</h3>
                    <div class="course-name">{{ $topics->count() ?? 0 }} Topics</div>
                </div>
            
            <div class="topics-list">
                @if(isset($topics) && $topics->count() > 0)
                    @foreach($topics as $topic)
                        @php
                            // Get REAL video watching progress from database
                            $videoProgressRecord = $topic->videoProgress->first();
                            $videoProgress = $videoProgressRecord ? $videoProgressRecord->percentage : 0;
                            
                            // Determine progress class based on video watching percentage
                            $progressClass = 'not-started';
                            if ($videoProgress >= 100) {
                                $progressClass = 'completed';
                            } elseif ($videoProgress >= 71) {
                                $progressClass = 'high-progress';
                            } elseif ($videoProgress >= 31) {
                                $progressClass = 'medium-progress';
                            } elseif ($videoProgress >= 1) {
                                $progressClass = 'low-progress';
                            }
                            
                            // Calculate circle progress (circumference = 2 * π * radius)
                            $radius = 10;
                            $circumference = 2 * pi() * $radius;
                            $strokeDasharray = $circumference;
                            $strokeDashoffset = $circumference - ($videoProgress / 100) * $circumference;
                            
                            $hasVideo = $topic->video && $topic->video->youtube_video_id;
                            $isActive = isset($currentTopic) && $currentTopic->id == $topic->id;
                        @endphp
                        
                        <div class="topic-item {{ $isActive ? 'active' : '' }} {{ $hasVideo ? 'has-video' : 'no-video' }}" data-topic-id="{{ $topic->id }}">
                            @if($hasVideo)
                                <a href="{{ route('video.show', ['topic' => $topic->id]) }}" class="topic-link">
                                    <i class="fas fa-play-circle video-icon"></i>
                                    <span class="flex-grow-1">{{ $topic->name }}</span>
                                    <div class="progress-circle {{ $progressClass }}">
                                        <svg>
                                            <circle class="progress-bg" cx="12" cy="12" r="{{ $radius }}"></circle>
                                            <circle class="progress-bar" cx="12" cy="12" r="{{ $radius }}" 
                                                    stroke-dasharray="{{ $strokeDasharray }}" 
                                                    stroke-dashoffset="{{ $strokeDashoffset }}"></circle>
                                        </svg>
                                    </div>
                                </a>
                            @else
                                <div class="topic-link" style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-video-slash video-icon"></i>
                                    <span class="flex-grow-1">{{ $topic->name }}</span>
                                    <div class="progress-circle {{ $progressClass }}">
                                        <svg>
                                            <circle class="progress-bg" cx="12" cy="12" r="{{ $radius }}"></circle>
                                            <circle class="progress-bar" cx="12" cy="12" r="{{ $radius }}" 
                                                    stroke-dasharray="{{ $strokeDasharray }}" 
                                                    stroke-dashoffset="{{ $strokeDashoffset }}"></circle>
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-book-open mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p>No topics available</p>
                    </div>
                @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar Toggle Button -->
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </div>
        
        <!-- Floating Toggle (shown when sidebar is collapsed) -->
        <div class="floating-toggle" id="floatingToggle">
            <i class="fas fa-bars"></i>
        </div>
        
        <!-- Video Main Area -->
        <div class="video-main">
            <!-- Back Button -->
            <a href="{{ route('topics.forCourse', $course->id ?? 1) }}" class="back-btn" title="Back to Topics">
                <i class="fas fa-times"></i>
            </a>
            
            <!-- Video Player -->
            @php
                // DEBUG: Let's see what we have
                $debugInfo = [
                    'video_exists' => isset($video),
                    'video_is_object' => isset($video) && is_object($video),
                    'youtube_video_id_exists' => isset($video) && $video && isset($video->youtube_video_id),
                    'youtube_video_id_value' => isset($video) && $video ? ($video->youtube_video_id ?? 'NULL') : 'VIDEO_NOT_SET',
                    'current_topic_name' => isset($currentTopic) ? $currentTopic->name : 'NO_TOPIC',
                    'current_topic_id' => isset($currentTopic) ? $currentTopic->id : 'NO_ID'
                ];
            @endphp
            
            @if(isset($video) && $video && $video->youtube_video_id)
                <div class="video-player" id="videoContainer">
                    <iframe 
                        id="videoFrame"
                        src="https://www.youtube.com/embed/{{ $video->youtube_video_id }}?autoplay=1&rel=0&modestbranding=1&controls=1" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            @else
                <div class="video-placeholder">
                    <i class="fas fa-video-slash"></i>
                    <h4>No Video Available</h4>
                    <p>{{ isset($currentTopic) ? $currentTopic->name : 'This topic' }} doesn't have a video yet.</p>
                    
                    <!-- DEBUG INFO -->
                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 8px; font-size: 0.8rem; text-align: left;">
                        <strong>Debug Info:</strong><br>
                        @foreach($debugInfo as $key => $value)
                            {{ $key }}: {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}<br>
                        @endforeach
                    </div>
                    
                    <!-- TEMPORARY: Add a test video -->
                    <div style="margin-top: 1rem;">
                        <button onclick="loadTestVideo()" class="btn btn-warning">Load Test Video</button>
                        @if(isset($currentTopic))
                            <button onclick="addVideoToTopic()" class="btn btn-success">Add Video to This Topic</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function loadTestVideo() {
            // Replace the placeholder with a test YouTube video
            const videoMain = document.querySelector('.video-main');
            videoMain.innerHTML = `
                <a href="{{ route('topics.forCourse', $course->id ?? 1) }}" class="back-btn" title="Back to Topics">
                    <i class="fas fa-times"></i>
                </a>
                <div class="video-player">
                    <iframe 
                        src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0&modestbranding=1" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
            `;
        }
        
        function addVideoToTopic() {
            // Add a video to the current topic via AJAX
            const topicId = {{ $currentTopic->id ?? 'null' }};
            if (!topicId) {
                alert('No topic ID found');
                return;
            }
            
            const videoId = prompt('Enter YouTube Video ID (e.g., dQw4w9WgXcQ):');
            if (!videoId) return;
            
            fetch('/admin/videos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    topic_id: topicId,
                    youtube_video_id: videoId,
                    title: '{{ $currentTopic->name ?? "Video" }}',
                    is_active: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Video added successfully! Refreshing page...');
                    location.reload();
                } else {
                    alert('Error adding video: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding video. Check console for details.');
            });
        }
        
        // 🔒 SIMPLE SECURITY: Prevent right-click and link copying
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.video-player')) {
                e.preventDefault();
                return false;
            }
        });

        // 📊 REAL-TIME VIDEO PROGRESS TRACKING
        (function() {
            const topicId = {{ $currentTopic->id ?? 'null' }};
            if (!topicId) return;

            let progressUpdateInterval;
            let lastUpdateTime = 0;

            // YouTube API ready callback
            window.onYouTubeIframeAPIReady = function() {
                // This will be called when YouTube API is ready
                console.log('YouTube API Ready');
            };

            // Alternative: Use postMessage to communicate with iframe
            function trackVideoProgress() {
                const iframe = document.getElementById('videoFrame');
                if (!iframe) return;

                // Send message to YouTube iframe to get current time
                iframe.contentWindow.postMessage('{"event":"command","func":"getCurrentTime","args":""}', '*');
                iframe.contentWindow.postMessage('{"event":"command","func":"getDuration","args":""}', '*');
            }

            // Listen for messages from YouTube iframe
            window.addEventListener('message', function(event) {
                if (event.origin !== 'https://www.youtube.com') return;

                try {
                    const data = JSON.parse(event.data);
                    
                    if (data.event === 'video-progress') {
                        const currentTime = data.info;
                        const duration = data.duration || 0;
                        
                        // Only update every 5 seconds to avoid spam
                        if (Math.abs(currentTime - lastUpdateTime) >= 5) {
                            updateVideoProgress(currentTime, duration);
                            lastUpdateTime = currentTime;
                        }
                    }
                } catch (e) {
                    // Ignore parsing errors
                }
            });

            // Update progress via AJAX
            function updateVideoProgress(currentTime, duration) {
                if (currentTime <= 0 || duration <= 0) return;

                fetch('/api/video/progress', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        topic_id: topicId,
                        current_time: currentTime,
                        duration: duration
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Progress updated:', data.progress.percentage + '%');
                        
                        // Update progress circle in sidebar
                        updateProgressCircle(data.progress.percentage);
                    }
                })
                .catch(error => {
                    console.error('Progress update failed:', error);
                });
            }

            // Update progress circle visually
            function updateProgressCircle(percentage) {
                const progressCircle = document.querySelector(`[data-topic-id="${topicId}"] .progress-circle`);
                if (!progressCircle) return;

                const circle = progressCircle.querySelector('.progress-bar');
                if (!circle) return;

                const radius = 10;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (percentage / 100) * circumference;
                
                circle.style.strokeDashoffset = offset;

                // Update color based on percentage
                let colorClass = 'not-started';
                if (percentage >= 100) colorClass = 'completed';
                else if (percentage >= 71) colorClass = 'high-progress';
                else if (percentage >= 31) colorClass = 'medium-progress';
                else if (percentage >= 1) colorClass = 'low-progress';

                progressCircle.className = `progress-circle ${colorClass}`;
            }

            // Start tracking when page loads
            setTimeout(() => {
                progressUpdateInterval = setInterval(trackVideoProgress, 5000);
            }, 2000);

            // Stop tracking when page unloads
            window.addEventListener('beforeunload', () => {
                if (progressUpdateInterval) {
                    clearInterval(progressUpdateInterval);
                }
            });
        })();
        
        // Log debug info to console (only in development)
        @if(config('app.debug'))
            console.log('Video Debug Info:', @json($debugInfo ?? []));
            console.log('Current Topic:', @json($currentTopic ?? null));
            console.log('Video Object:', @json($video ?? null));
        @endif
        
        // Sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const videoLayout = document.getElementById('videoLayout');
            const sidebar = document.getElementById('topicsSidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const floatingToggle = document.getElementById('floatingToggle');
            const sidebarResizer = document.getElementById('sidebarResizer');
            
            let isResizing = false;
            let startX = 0;
            let startWidth = 0;
            
            // Toggle sidebar collapse/expand with ultra-smooth animation
            function toggleSidebar() {
                const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');
                
                if (isCurrentlyCollapsed) {
                    // Expanding
                    sidebar.classList.remove('collapsed');
                    videoLayout.classList.remove('sidebar-collapsed');
                    
                    // Reset any custom width when expanding
                    const savedWidth = localStorage.getItem('sidebarWidth') || '280px';
                    sidebar.style.width = savedWidth;
                    sidebar.style.minWidth = savedWidth;
                    sidebar.style.maxWidth = savedWidth;
                } else {
                    // Collapsing
                    sidebar.classList.add('collapsed');
                    videoLayout.classList.add('sidebar-collapsed');
                    
                    // Force immediate width change for snappy feel
                    setTimeout(() => {
                        sidebar.style.width = '0';
                        sidebar.style.minWidth = '0';
                        sidebar.style.maxWidth = '0';
                    }, 10);
                }
                
                // Save state to localStorage
                localStorage.setItem('sidebarCollapsed', !isCurrentlyCollapsed);
            }
            
            // Restore sidebar state from localStorage
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                videoLayout.classList.add('sidebar-collapsed');
                sidebar.style.width = '0';
                sidebar.style.minWidth = '0';
                sidebar.style.maxWidth = '0';
            } else {
                // Restore sidebar width from localStorage
                const savedWidth = localStorage.getItem('sidebarWidth') || '280px';
                sidebar.style.width = savedWidth;
                sidebar.style.minWidth = savedWidth;
                sidebar.style.maxWidth = savedWidth;
            }
            
            // Event listeners for toggle buttons
            sidebarToggle.addEventListener('click', toggleSidebar);
            floatingToggle.addEventListener('click', toggleSidebar);
            
            // Keyboard shortcut (Ctrl/Cmd + B)
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    toggleSidebar();
                }
            });
            
            // Resizer functionality with snap-to-collapse
            sidebarResizer.addEventListener('mousedown', function(e) {
                if (sidebar.classList.contains('collapsed')) return;
                
                isResizing = true;
                startX = e.clientX;
                startWidth = parseInt(document.defaultView.getComputedStyle(sidebar).width, 10);
                document.body.style.cursor = 'col-resize';
                sidebarResizer.classList.add('resizing');
                
                // Prevent text selection during resize
                document.body.style.userSelect = 'none';
                
                // Add temporary fast transition for resize
                sidebar.style.transition = 'none';
            });
            
            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;
                
                const width = startWidth + e.clientX - startX;
                const minWidth = 200;
                const maxWidth = 600;
                const collapseThreshold = 150;
                
                if (width >= minWidth && width <= maxWidth) {
                    sidebar.style.width = width + 'px';
                    sidebar.style.minWidth = width + 'px';
                    sidebar.style.maxWidth = width + 'px';
                }
                
                // Auto-collapse if dragged too small with snappy animation
                if (width < collapseThreshold) {
                    sidebar.style.transition = 'all 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    toggleSidebar();
                    isResizing = false;
                    document.body.style.cursor = '';
                    sidebarResizer.classList.remove('resizing');
                    document.body.style.userSelect = '';
                }
            });
            
            document.addEventListener('mouseup', function() {
                if (isResizing) {
                    isResizing = false;
                    document.body.style.cursor = '';
                    sidebarResizer.classList.remove('resizing');
                    document.body.style.userSelect = '';
                    
                    // Restore transition
                    sidebar.style.transition = 'all 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    
                    // Save width to localStorage
                    const currentWidth = sidebar.style.width || '320px';
                    localStorage.setItem('sidebarWidth', currentWidth);
                }
            });
            
            // Restore sidebar width from localStorage
            const savedWidth = localStorage.getItem('sidebarWidth');
            if (savedWidth && !sidebar.classList.contains('collapsed')) {
                sidebar.style.width = savedWidth;
            }
            
            // Double-click to reset width with snappy animation
            sidebarResizer.addEventListener('dblclick', function() {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.style.width = '280px';
                    sidebar.style.minWidth = '280px';
                    sidebar.style.maxWidth = '280px';
                    localStorage.setItem('sidebarWidth', '280px');
                }
            });
            
            // Smooth scroll to active topic
            const activeTopic = document.querySelector('.topic-item.active');
            if (activeTopic) {
                setTimeout(() => {
                    activeTopic.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300);
            }
        });
    </script>
</body>
</html>
