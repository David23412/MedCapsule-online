<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Responsive Navigation Demo - MedCapsule</title>
    
    {{-- Include responsive meta component --}}
    <x-responsive-meta />
    
    {{-- Demo-specific styles --}}
    <style>
        /* Demo page styles */
        body {
            margin: 0;
            padding-top: 70px; /* Account for fixed navbar */
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .demo-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .demo-title {
            color: #007bff;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .demo-subtitle {
            color: #6c757d;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .feature-card h3 {
            color: #007bff;
            margin-bottom: 1rem;
        }
        
        .feature-card ul {
            list-style: none;
            padding: 0;
        }
        
        .feature-card li {
            padding: 0.25rem 0;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .feature-card li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }
        
        .demo-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 2rem 0;
        }
        
        .demo-btn {
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .demo-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .demo-btn.secondary {
            background: #6c757d;
        }
        
        .demo-btn.secondary:hover {
            background: #545b62;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: currentColor;
            border-radius: 50%;
        }
        
        @media (max-width: 767px) {
            .demo-container {
                padding: 1rem;
            }
            
            .demo-title {
                font-size: 2rem;
            }
            
            .demo-controls {
                flex-direction: column;
            }
            
            .demo-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    {{-- Include the responsive navigation component --}}
    <x-responsive-navigation />
    
    <div class="demo-container">
        <div class="demo-section">
            <h1 class="demo-title">Responsive Navigation Demo</h1>
            <p class="demo-subtitle">
                Experience the new mobile-first navigation system for MedCapsule
            </p>
            
            <div class="status-indicator">
                <span class="status-dot"></span>
                <span id="nav-status">Navigation Ready</span>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>Mobile-First Design</h3>
                    <ul>
                        <li>Hamburger menu for mobile devices</li>
                        <li>Touch-friendly 44px minimum targets</li>
                        <li>Smooth slide-in animations</li>
                        <li>Responsive breakpoints</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h3>Accessibility</h3>
                    <ul>
                        <li>Full keyboard navigation support</li>
                        <li>ARIA labels and states</li>
                        <li>Focus trapping in mobile menu</li>
                        <li>Screen reader compatibility</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h3>Performance</h3>
                    <ul>
                        <li>GPU-accelerated animations</li>
                        <li>Debounced resize handling</li>
                        <li>Minimal DOM manipulation</li>
                        <li>Reduced motion support</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h3>User Experience</h3>
                    <ul>
                        <li>Backdrop blur overlay</li>
                        <li>Auto-close on link clicks</li>
                        <li>Escape key support</li>
                        <li>Consistent branding</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>Demo Controls</h2>
            <p>Test the navigation functionality with these controls:</p>
            
            <div class="demo-controls">
                <button class="demo-btn" onclick="toggleNavigation()">Toggle Menu</button>
                <button class="demo-btn secondary" onclick="simulateMobile()">Simulate Mobile</button>
                <button class="demo-btn secondary" onclick="simulateDesktop()">Simulate Desktop</button>
                <button class="demo-btn secondary" onclick="showNavState()">Show State</button>
            </div>
            
            <div id="demo-output" class="code-block" style="margin-top: 1rem; display: none;">
                <pre id="demo-output-content"></pre>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>Implementation</h2>
            <p>To use this responsive navigation in your existing pages:</p>
            
            <h3>1. Include the Component</h3>
            <div class="code-block">
&lt;x-responsive-navigation /&gt;
            </div>
            
            <h3>2. Include Required CSS</h3>
            <div class="code-block">
&lt;link rel="stylesheet" href="{{ asset('css/responsive.css') }}"&gt;
&lt;link rel="stylesheet" href="{{ asset('css/responsive-navigation.css') }}"&gt;
            </div>
            
            <h3>3. Include JavaScript</h3>
            <div class="code-block">
&lt;script src="{{ asset('js/responsive-navigation.js') }}"&gt;&lt;/script&gt;
            </div>
            
            <h3>4. Add Body Padding</h3>
            <div class="code-block">
body {
    padding-top: 70px; /* Account for fixed navbar */
}
            </div>
        </div>
        
        <div class="demo-section">
            <h2>Browser Support</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>Modern Browsers</h3>
                    <ul>
                        <li>Chrome 60+</li>
                        <li>Firefox 55+</li>
                        <li>Safari 12+</li>
                        <li>Edge 79+</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <h3>Mobile Browsers</h3>
                    <ul>
                        <li>iOS Safari 12+</li>
                        <li>Chrome Mobile 60+</li>
                        <li>Samsung Internet 8+</li>
                        <li>Firefox Mobile 55+</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Demo JavaScript
        let navInstance = null;
        
        // Wait for navigation to initialize
        document.addEventListener('nav:initialized', function(event) {
            navInstance = event.detail.instance;
            updateStatus('Navigation Initialized');
        });
        
        // Listen for navigation events
        document.addEventListener('nav:opened', function() {
            updateStatus('Menu Opened');
        });
        
        document.addEventListener('nav:closed', function() {
            updateStatus('Menu Closed');
        });
        
        document.addEventListener('nav:resize', function(event) {
            updateStatus(`Resized - Mobile: ${event.detail.isMobile}`);
        });
        
        // Demo functions
        function toggleNavigation() {
            if (navInstance) {
                navInstance.toggle();
            } else {
                alert('Navigation not initialized yet');
            }
        }
        
        function simulateMobile() {
            // Resize window to mobile size
            if (window.innerWidth > 767) {
                alert('Resize your browser window to less than 768px to see mobile navigation');
            } else {
                alert('Already in mobile view! Try the hamburger menu.');
            }
        }
        
        function simulateDesktop() {
            if (window.innerWidth <= 767) {
                alert('Resize your browser window to more than 768px to see desktop navigation');
            } else {
                alert('Already in desktop view!');
            }
        }
        
        function showNavState() {
            if (navInstance) {
                const state = navInstance.getState();
                const output = document.getElementById('demo-output');
                const content = document.getElementById('demo-output-content');
                
                content.textContent = JSON.stringify(state, null, 2);
                output.style.display = 'block';
                
                setTimeout(() => {
                    output.style.display = 'none';
                }, 5000);
            } else {
                alert('Navigation not initialized yet');
            }
        }
        
        function updateStatus(message) {
            const statusElement = document.getElementById('nav-status');
            if (statusElement) {
                statusElement.textContent = message;
                
                // Reset to default after 3 seconds
                setTimeout(() => {
                    statusElement.textContent = 'Navigation Ready';
                }, 3000);
            }
        }
        
        // Show current viewport size
        function updateViewportInfo() {
            const width = window.innerWidth;
            const isMobile = width < 768;
            console.log(`Viewport: ${width}px (${isMobile ? 'Mobile' : 'Desktop'})`);
        }
        
        window.addEventListener('resize', updateViewportInfo);
        updateViewportInfo();
    </script>
</body>
</html>