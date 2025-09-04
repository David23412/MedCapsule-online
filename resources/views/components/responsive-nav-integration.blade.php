{{--
    Responsive Navigation Integration Component
    
    This component provides an easy way to integrate the responsive navigation
    into existing pages without modifying the original navigation files.
    
    Usage:
    <x-responsive-nav-integration mode="replace" />
    <x-responsive-nav-integration mode="alongside" />
    
    Modes:
    - replace: Hides existing navigation and shows responsive navigation
    - alongside: Shows both navigations (for testing/comparison)
--}}

@props([
    'mode' => 'replace', // 'replace' or 'alongside'
    'showDemo' => false
])

@php
    $responsiveService = app(\App\Services\ResponsiveService::class);
    $isMobile = $responsiveService->isMobile();
@endphp

{{-- Include responsive navigation --}}
<x-responsive-navigation />

{{-- Integration styles and scripts --}}
<style>
/* Integration-specific styles */
.responsive-nav-integration {
    position: relative;
}

/* Replace mode - hide existing navigation on mobile */
@if($mode === 'replace')
    @media (max-width: 767px) {
        /* Hide existing Bootstrap navbar on mobile */
        .navbar:not(.responsive-navbar) {
            display: none !important;
        }
        
        /* Adjust body padding for responsive navbar */
        body {
            padding-top: 70px !important;
        }
    }
    
    @media (min-width: 768px) {
        /* Hide responsive navbar on desktop when in replace mode */
        .responsive-navbar {
            display: none;
        }
    }
@endif

/* Alongside mode - show both navigations with different positioning */
@if($mode === 'alongside')
    .responsive-navbar {
        top: 0;
        z-index: 1031;
    }
    
    .navbar:not(.responsive-navbar) {
        top: 70px;
        z-index: 1030;
    }
    
    body {
        padding-top: 140px !important; /* Account for both navbars */
    }
    
    /* Add visual distinction for demo */
    .responsive-navbar::before {
        content: "NEW RESPONSIVE NAV";
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        background: #28a745;
        color: white;
        padding: 2px 8px;
        font-size: 10px;
        border-radius: 4px;
        z-index: 1;
    }
    
    .navbar:not(.responsive-navbar)::before {
        content: "EXISTING NAV";
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        background: #ffc107;
        color: #000;
        padding: 2px 8px;
        font-size: 10px;
        border-radius: 4px;
        z-index: 1;
    }
@endif

/* Demo mode styles */
@if($showDemo)
    .responsive-nav-demo-banner {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        z-index: 1060;
        font-size: 14px;
        font-weight: 500;
        max-width: 300px;
    }
    
    .responsive-nav-demo-banner .close-demo {
        background: none;
        border: none;
        color: white;
        font-size: 16px;
        cursor: pointer;
        float: right;
        margin-left: 10px;
        padding: 0;
        line-height: 1;
    }
    
    @media (max-width: 767px) {
        .responsive-nav-demo-banner {
            bottom: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
@endif
</style>

@if($showDemo)
    {{-- Demo banner --}}
    <div class="responsive-nav-demo-banner" id="responsive-demo-banner">
        <button class="close-demo" onclick="closeDemoBanner()">&times;</button>
        <strong>Responsive Navigation Demo</strong><br>
        Mode: {{ ucfirst($mode) }} | Device: {{ $isMobile ? 'Mobile' : 'Desktop' }}
        <br><small>Resize window to test responsiveness</small>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Integration-specific JavaScript
    const ResponsiveNavIntegration = {
        mode: '{{ $mode }}',
        isMobile: {{ $isMobile ? 'true' : 'false' }},
        
        init() {
            this.handleModeSpecificLogic();
            this.bindEvents();
            console.log(`Responsive Navigation Integration initialized in ${this.mode} mode`);
        },
        
        handleModeSpecificLogic() {
            if (this.mode === 'replace') {
                this.handleReplaceMode();
            } else if (this.mode === 'alongside') {
                this.handleAlongsideMode();
            }
        },
        
        handleReplaceMode() {
            // In replace mode, ensure smooth transition between navigations
            const existingNav = document.querySelector('.navbar:not(.responsive-navbar)');
            const responsiveNav = document.querySelector('.responsive-navbar');
            
            if (existingNav && responsiveNav) {
                // Sync any dynamic content if needed
                this.syncNavigationContent(existingNav, responsiveNav);
            }
        },
        
        handleAlongsideMode() {
            // In alongside mode, add visual indicators
            console.log('Running in alongside mode - both navigations visible');
        },
        
        syncNavigationContent(existingNav, responsiveNav) {
            // Sync notification counts, user info, etc.
            try {
                // Example: Sync notification badge
                const existingBadge = existingNav.querySelector('.notification-badge');
                const responsiveBadge = responsiveNav.querySelector('.notification-badge');
                
                if (existingBadge && responsiveBadge) {
                    responsiveBadge.textContent = existingBadge.textContent;
                    responsiveBadge.style.display = existingBadge.style.display;
                }
            } catch (error) {
                console.warn('Error syncing navigation content:', error);
            }
        },
        
        bindEvents() {
            // Listen for responsive navigation events
            document.addEventListener('nav:opened', () => {
                console.log('Responsive navigation opened');
            });
            
            document.addEventListener('nav:closed', () => {
                console.log('Responsive navigation closed');
            });
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.handleResize();
                }, 150);
            });
        },
        
        handleResize() {
            const newIsMobile = window.innerWidth < 768;
            
            if (newIsMobile !== this.isMobile) {
                this.isMobile = newIsMobile;
                console.log(`Device state changed to: ${this.isMobile ? 'Mobile' : 'Desktop'}`);
                
                // Update demo banner if present
                const demoBanner = document.getElementById('responsive-demo-banner');
                if (demoBanner) {
                    const deviceText = demoBanner.querySelector('br').nextSibling;
                    if (deviceText) {
                        deviceText.textContent = `Mode: {{ ucfirst($mode) }} | Device: ${this.isMobile ? 'Mobile' : 'Desktop'}`;
                    }
                }
            }
        }
    };
    
    // Initialize integration
    ResponsiveNavIntegration.init();
    
    // Make available globally for debugging
    window.ResponsiveNavIntegration = ResponsiveNavIntegration;
});

@if($showDemo)
// Demo banner functions
function closeDemoBanner() {
    const banner = document.getElementById('responsive-demo-banner');
    if (banner) {
        banner.style.display = 'none';
    }
}
@endif
</script>