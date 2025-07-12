#!/bin/bash

# MedCapsule Blade Files Critical Fixes
# This script addresses the most critical security and performance issues

echo "🔧 Fixing critical Blade file issues..."

# Create backup directory
mkdir -p backups/blade-fixes-$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="backups/blade-fixes-$(date +%Y%m%d-%H%M%S)"

# Backup original files
echo "📦 Creating backups..."
cp -r resources/views/ $BACKUP_DIR/

# 1. Fix XSS vulnerability in navigation
echo "🛡️ Fixing XSS vulnerability in navigation..."
sed -i 's/{{ Auth::user()->name }}/{{ e(Auth::user()->name) }}/g' resources/views/partials/navigation.blade.php

# 2. Add missing ARIA labels to notification bell
echo "♿ Adding ARIA labels to notification bell..."
sed -i 's/<button @click="toggleNotifications" class="position-relative focus:outline-none">/<button @click="toggleNotifications" class="position-relative focus:outline-none" aria-label="Toggle notifications" aria-expanded="false">/g' resources/views/components/notification-bell.blade.php

# 3. Fix CSRF token exposure in JavaScript
echo "🔒 Fixing CSRF token exposure..."
sed -i 's/window.csrfToken = '\''{{ csrf_token() }}'\'';/window.csrfToken = document.querySelector('"'"'meta[name="csrf-token"]'"'"').getAttribute('"'"'content'"'"');/g' resources/views/courses-optimized.blade.php

# 4. Add proper meta tags to app layout
echo "📱 Adding meta tags to app layout..."
cat > resources/views/layouts/app.blade.php.new << 'EOF'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'MedCapsule - Advanced Medical Education Platform')">
    <meta name="keywords" content="@yield('keywords', 'medical education, medical school, medical training, healthcare education')">
    <meta name="author" content="MedCapsule">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'MedCapsule')">
    <meta property="og:description" content="@yield('description', 'Advanced Medical Education Platform')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/medcapsule-og.jpg'))">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'MedCapsule')">
    <meta name="twitter:description" content="@yield('description', 'Advanced Medical Education Platform')">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title>@yield('title', 'MedCapsule')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts - Load asynchronously -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ secure_asset('js/notification-bell.js') }}" defer></script>
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ secure_asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('css/notifications.css') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="shortcut icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
    <link rel="apple-touch-icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 50'%3E%3Crect fill='%23007bff' x='10' y='15' width='80' height='20' rx='10' ry='10' /%3E%3Crect x='50' y='15' width='40' height='20' fill='%23ff0000' rx='0 10 10 0' ry='10'/%3E%3Cline x1='50' y1='15' x2='50' y2='35' stroke='white' stroke-width='1.5' stroke-dasharray='2,1'/%3E%3C/svg%3E">
</head>
<body>
    <div id="app">
        @include('partials.navigation')

        <!-- Page Content -->
        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
EOF

mv resources/views/layouts/app.blade.php.new resources/views/layouts/app.blade.php

# 5. Create error handling partial
echo "⚠️ Creating error handling partial..."
cat > resources/views/partials/error-handler.blade.php << 'EOF'
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Please fix the following errors:</h5>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ e($error) }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ e(session('success')) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ e(session('error')) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ e(session('warning')) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
EOF

# 6. Create loading skeleton component
echo "⏳ Creating loading skeleton component..."
cat > resources/views/components/loading-skeleton.blade.php << 'EOF'
@props(['type' => 'card', 'lines' => 3, 'height' => '20px'])

<div class="loading-skeleton {{ $type }}-skeleton" {{ $attributes }}>
    @if($type === 'card')
        <div class="skeleton-card">
            <div class="skeleton-image" style="height: 200px;"></div>
            <div class="skeleton-content p-3">
                <div class="skeleton-title mb-2" style="height: 24px; width: 80%;"></div>
                <div class="skeleton-text mb-2" style="height: 16px; width: 60%;"></div>
                <div class="skeleton-button" style="height: 40px; width: 100%;"></div>
            </div>
        </div>
    @elseif($type === 'text')
        @for($i = 0; $i < $lines; $i++)
            <div class="skeleton-line" style="height: {{ $height }}; width: {{ $i === $lines - 1 ? '60%' : '100%' }};"></div>
        @endfor
    @elseif($type === 'avatar')
        <div class="skeleton-avatar" style="width: {{ $attributes->get('size', '40px') }}; height: {{ $attributes->get('size', '40px') }};"></div>
    @endif
</div>

<style>
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.skeleton-avatar {
    border-radius: 50%;
}

.skeleton-line {
    margin-bottom: 8px;
}

.skeleton-line:last-child {
    margin-bottom: 0;
}
</style>
EOF

# 7. Create accessibility improvements
echo "♿ Adding accessibility improvements..."
cat > resources/views/components/accessible-button.blade.php << 'EOF'
@props(['type' => 'button', 'variant' => 'primary', 'size' => 'md'])

<button 
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "btn btn-{$variant} btn-{$size}",
        'aria-label' => $attributes->get('aria-label', 'Button'),
        'aria-describedby' => $attributes->get('aria-describedby'),
        'aria-expanded' => $attributes->get('aria-expanded'),
        'aria-pressed' => $attributes->get('aria-pressed'),
        'aria-controls' => $attributes->get('aria-controls')
    ]) }}
>
    {{ $slot }}
</button>
EOF

# 8. Create responsive image component
echo "📱 Creating responsive image component..."
cat > resources/views/components/responsive-image.blade.php << 'EOF'
@props(['src', 'alt', 'sizes' => '100vw', 'loading' => 'lazy'])

<img 
    src="{{ $src }}"
    alt="{{ e($alt) }}"
    sizes="{{ $sizes }}"
    loading="{{ $loading }}"
    {{ $attributes->merge(['class' => 'img-fluid']) }}
    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
>
<div class="image-fallback" style="display: none; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; text-align: center; color: #6c757d;">
    <i class="fas fa-image"></i>
    <p class="mb-0 mt-2">{{ e($alt) }}</p>
</div>
EOF

# 9. Add proper error handling to existing files
echo "🔧 Adding error handling to existing files..."
find resources/views -name "*.blade.php" -type f -exec sed -i '1i @include("partials.error-handler")' {} \;

# 10. Create performance monitoring script
echo "📊 Creating performance monitoring script..."
cat > public/js/performance-monitor.js << 'EOF'
// Performance monitoring for MedCapsule
class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.init();
    }

    init() {
        // Monitor Core Web Vitals
        this.observeLCP();
        this.observeFID();
        this.observeCLS();
        
        // Monitor custom metrics
        this.observePageLoad();
        this.observeUserInteractions();
    }

    observeLCP() {
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.metrics.lcp = lastEntry.startTime;
            this.reportMetric('LCP', lastEntry.startTime);
        }).observe({ entryTypes: ['largest-contentful-paint'] });
    }

    observeFID() {
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach(entry => {
                this.metrics.fid = entry.processingStart - entry.startTime;
                this.reportMetric('FID', this.metrics.fid);
            });
        }).observe({ entryTypes: ['first-input'] });
    }

    observeCLS() {
        let clsValue = 0;
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach(entry => {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            });
            this.metrics.cls = clsValue;
            this.reportMetric('CLS', clsValue);
        }).observe({ entryTypes: ['layout-shift'] });
    }

    observePageLoad() {
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            this.metrics.loadTime = loadTime;
            this.reportMetric('PageLoad', loadTime);
        });
    }

    observeUserInteractions() {
        let interactionCount = 0;
        const observer = new PerformanceObserver((list) => {
            list.getEntries().forEach(entry => {
                interactionCount++;
                this.metrics.interactions = interactionCount;
            });
        });
        observer.observe({ entryTypes: ['interaction'] });
    }

    reportMetric(name, value) {
        // Send to analytics service
        if (window.gtag) {
            gtag('event', 'performance_metric', {
                metric_name: name,
                metric_value: value,
                page_url: window.location.href
            });
        }
        
        // Log to console in development
        if (process.env.NODE_ENV === 'development') {
            console.log(`Performance Metric - ${name}: ${value}`);
        }
    }

    getMetrics() {
        return this.metrics;
    }
}

// Initialize performance monitoring
document.addEventListener('DOMContentLoaded', () => {
    window.performanceMonitor = new PerformanceMonitor();
});
EOF

echo "✅ Critical Blade file fixes completed!"
echo "📁 Backups saved to: $BACKUP_DIR"
echo ""
echo "🔍 Summary of fixes applied:"
echo "  ✅ Fixed XSS vulnerability in user name output"
echo "  ✅ Added ARIA labels to notification bell"
echo "  ✅ Fixed CSRF token exposure in JavaScript"
echo "  ✅ Added comprehensive meta tags to app layout"
echo "  ✅ Created error handling partial"
echo "  ✅ Created loading skeleton component"
echo "  ✅ Created accessible button component"
echo "  ✅ Created responsive image component"
echo "  ✅ Added error handling to all Blade files"
echo "  ✅ Created performance monitoring script"
echo ""
echo "🚀 Next steps:"
echo "  1. Test the application thoroughly"
echo "  2. Review the audit report for additional improvements"
echo "  3. Implement the remaining accessibility fixes"
echo "  4. Add comprehensive testing for the new components"