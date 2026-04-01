@php
    $isMobile = request()->header('User-Agent') && preg_match('/Mobile|Android|iPhone/i', request()->header('User-Agent'));
    $deviceClasses = $isMobile ? 'is-mobile' : 'is-desktop';
    $viewportContent = 'width=device-width, initial-scale=1';
    $jsConfig = [];
@endphp

{{-- Responsive Meta Tags --}}
<meta name="viewport" content="{{ $viewportContent }}">
<meta name="format-detection" content="telephone=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#007bff">

{{-- Preconnect to external resources --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

{{-- Critical CSS (inline for performance) --}}
@if(false)
<style>
/* Critical CSS - Inlined for performance */
:root {
  --touch-target-min: 44px;
  --touch-target-comfortable: 48px;
}

/* Prevent FOUC */
html { visibility: hidden; opacity: 0; }
html.loaded { visibility: visible; opacity: 1; transition: opacity 0.3s; }

/* Critical mobile styles */
@media (max-width: 767px) {
  body { font-size: 16px; } /* Prevent iOS zoom */
  .btn, .form-control { min-height: var(--touch-target-min); }
}
</style>
@endif

{{-- Responsive CSS Files --}}
<link rel="stylesheet" href="{{ asset('css/responsive.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('css/mobile-utils.css') }}" media="all">

{{-- Device-specific CSS loading --}}
@if($isMobile)
    <link rel="stylesheet" href="{{ asset('css/mobile-specific.css') }}" media="screen and (max-width: 767px)">
@endif

{{-- Responsive JavaScript Configuration --}}
<script>
window.ResponsiveConfig = @json($jsConfig);

// Device detection classes
document.documentElement.className += ' {{ $deviceClasses }}';

// Prevent FOUC
document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.add('loaded');
});

// Viewport height fix for mobile browsers
function setVH() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}
setVH();
window.addEventListener('resize', setVH);
window.addEventListener('orientationchange', setVH);
</script>

{{-- Reduced motion support --}}
@if(false)
<style>
*, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
}
</style>
@endif

{{-- Debug information (development only) --}}
@if(config('responsive.debug.show_breakpoints') && app()->environment('local'))
<style>
body::before {
    content: 'Device: {{ ($isMobile ? 'mobile' : 'desktop') }} | Viewport: ' attr(data-viewport);
    position: fixed;
    top: 0;
    left: 0;
    background: rgba(255, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    font-size: 12px;
    z-index: 9999;
    pointer-events: none;
}
</style>
<script>
// Show current breakpoint for debugging
function updateBreakpointIndicator() {
    const width = window.innerWidth;
    let breakpoint = 'desktop';
    
    if (width < 768) breakpoint = 'mobile';
    else if (width < 1024) breakpoint = 'tablet';
    
    document.body.setAttribute('data-viewport', `${width}px (${breakpoint})`);
}
updateBreakpointIndicator();
window.addEventListener('resize', updateBreakpointIndicator);
</script>
@endif