<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MedCapsule - Advanced Medical Education Platform</title>
    
    <!-- ULTRA PERFORMANCE: Critical meta tags -->
    <meta name="description" content="MedCapsule - Advanced Medical Education Platform for medical students. Interactive quizzes, progress tracking, and comprehensive learning materials.">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#007bff">
    
    <!-- ULTRA PERFORMANCE: DNS prefetch and preconnect -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- ULTRA PERFORMANCE: Critical CSS inline with ultra-optimized selectors -->
    <style>
        /* ULTRA CRITICAL CSS - Minimal, optimized selectors */
        :root{--p:#007bff;--s:#6c757d;--g:#28a745;--r:#dc3545;--w:#ffc107;--i:#17a2b8;--l:#f8f9fa;--d:#343a40;--f:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
        *{box-sizing:border-box}
        html{font-size:12px;scroll-behavior:smooth;-webkit-font-smoothing:antialiased;text-rendering:optimizeSpeed}
        body{font-family:var(--f);background:var(--l);margin:0;padding:0;overflow-x:hidden;line-height:1.5;color:var(--d)}
        
        /* ULTRA PERFORMANCE: Hardware-accelerated animations */
        .f{opacity:0;transform:translateY(15px) translateZ(0);transition:all .25s cubic-bezier(.4,0,.2,1);will-change:transform,opacity}
        .f.v{opacity:1;transform:translateY(0) translateZ(0)}
        
        /* ULTRA PERFORMANCE: Critical navbar - minimal styles */
        .n{background:rgba(255,255,255,.95);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);box-shadow:0 1px 3px rgba(0,0,0,.1);padding:.75rem 0;position:fixed;top:0;left:0;right:0;z-index:1000;transform:translateZ(0)}
        .n-b{font-weight:700;font-size:1.75rem;background:linear-gradient(90deg,#2A6DFF,#7B3FE4,#E63946);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;transition:transform .2s ease;will-change:transform}
        
        /* ULTRA PERFORMANCE: Critical hero section */
        .h{min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:1.5rem;position:relative;overflow:hidden;background:linear-gradient(135deg,#f8f9fa,#e9ecef)}
        .h h1{font-size:3rem;font-weight:700;color:var(--p);margin-bottom:.75rem;text-shadow:1px 1px 2px rgba(0,0,0,.1)}
        .btn-p{padding:12px 32px;font-size:1.25rem;border-radius:40px;background:var(--p);border:none;box-shadow:0 3px 8px rgba(33,150,243,.3);font-weight:600;transition:all .2s ease-in-out;will-change:transform;color:white;text-decoration:none;display:inline-block}
        
        /* ULTRA PERFORMANCE: Optimized card styles */
        .c,.f-b{will-change:transform;transform:translateZ(0);transition:transform .2s ease,box-shadow .2s ease}
        .c:hover,.f-b:hover{transform:translateY(-3px) translateZ(0);box-shadow:0 6px 15px rgba(0,0,0,.1)}
        
        /* ULTRA PERFORMANCE: Loading skeleton - minimal */
        .l{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:ld 1.2s infinite}
        @keyframes ld{0%{background-position:200% 0}100%{background-position:-200% 0}}
        
        /* ULTRA PERFORMANCE: Reduced motion support */
        @media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
        
        /* ULTRA PERFORMANCE: Mobile optimizations */
        @media (max-width:768px){html{font-size:13px}.h h1{font-size:2.25rem}.btn-p{padding:10px 24px;font-size:1.1rem}}
        
        /* ULTRA PERFORMANCE: Container and layout */
        .ct{max-width:1200px;margin:0 auto;padding:0 1rem}
        .m{margin-top:70px;padding:1rem}
        .s{text-align:center;padding:3rem 0;background:white}
        .s h2{color:var(--p);font-weight:600;margin-bottom:1.5rem;font-size:1.75rem}
        .r{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;margin-top:2rem}
        .f-b{padding:1.5rem;border-radius:8px;background:white;box-shadow:0 2px 4px rgba(0,0,0,.05)}
        .f-b i{font-size:2.5rem;color:var(--p);margin-bottom:1rem}
        .f-b h4{margin:.5rem 0;color:var(--d);font-weight:600}
        .f-b p{color:#666;margin:0;font-size:.9rem}
        
        /* ULTRA PERFORMANCE: Statistics section */
        .st{padding:3rem 0;background:linear-gradient(135deg,#f6f9fc,#ffffff)}
        .st .r{grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem}
        .st-i{background:white;padding:1.5rem;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.05);transition:transform .2s ease,box-shadow .2s ease;text-align:center}
        .st-i:hover{transform:translateY(-5px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
        .st-i i{color:var(--p);margin-bottom:.75rem;font-size:1.5rem}
        .st-i h4{color:var(--p);font-size:2rem;margin:.5rem 0;font-weight:700}
        .st-i p{color:#666;margin:0;font-weight:500;font-size:.9rem}
        
        /* ULTRA PERFORMANCE: Footer */
        .ft{background:#343a40;color:white;padding:2rem 0;text-align:center}
        .ft a{color:var(--p);text-decoration:none;transition:color .2s ease}
        .ft a:hover{color:white}
        
        /* ULTRA PERFORMANCE: Accessibility */
        .sr{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .sr-f:focus{position:static;width:auto;height:auto;padding:.5rem;margin:0;overflow:visible;clip:auto;white-space:normal;background:var(--p);color:white;text-decoration:none}
    </style>

    <!-- ULTRA PERFORMANCE: Preload critical fonts with font-display:swap -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- ULTRA PERFORMANCE: Preload critical CSS with high priority -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <!-- ULTRA PERFORMANCE: Fallback for noscript -->
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>

    <!-- ULTRA PERFORMANCE: Preload critical JavaScript -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" as="script">
    
    <!-- ULTRA PERFORMANCE: Structured data for SEO -->
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebApplication","name":"MedCapsule","description":"Advanced Medical Education Platform","url":"{{ url('/') }}","applicationCategory":"EducationalApplication","operatingSystem":"Web Browser","offers":{"@type":"Offer","price":"0","priceCurrency":"USD"}}
    </script>
</head>
<body>
    <!-- ULTRA PERFORMANCE: Skip to main content for accessibility -->
    <a href="#main" class="sr sr-f">Skip to main content</a>
    
    <!-- ULTRA PERFORMANCE: Minimal interactive background -->
    <div id="bg" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);pointer-events:none"></div>

    <!-- ULTRA PERFORMANCE: Optimized Navigation -->
    <nav class="n">
        <div class="ct">
            <a class="n-b" href="{{ route('home') }}">
                <svg style="width:45px;height:25px;display:inline-block;margin-right:8px;vertical-align:middle" viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="15" width="80" height="20" rx="10" ry="10" fill="#007bff"/>
                    <rect x="50" y="15" width="40" height="20" fill="#ff0000" rx="0 10 10 0" ry="10"/>
                    <line x1="50" y1="15" x2="50" y2="35" stroke="white" stroke-width="1.5" stroke-dasharray="2,1"/>
                </svg>
                MedCapsule
            </a>
            
            <button style="display:none" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-expanded="false" aria-label="Toggle navigation">
                <span></span>
            </button>
            
            <div id="nav">
                <ul style="list-style:none;margin:0;padding:0;display:flex;align-items:center;justify-content:flex-end;gap:1rem">
                    @guest
                        <li><a href="{{ route('login') }}" style="color:#333;text-decoration:none;padding:.5rem 1rem;border-radius:4px;transition:background .2s ease">Login</a></li>
                        <li><a href="{{ route('register') }}" style="color:#333;text-decoration:none;padding:.5rem 1rem;border-radius:4px;transition:background .2s ease">Sign Up</a></li>
                    @else
                        <!-- ULTRA PERFORMANCE: Minimal user dropdown -->
                        <li style="position:relative">
                            <a href="#" style="color:#333;text-decoration:none;padding:.5rem 1rem;border-radius:4px;transition:background .2s ease">
                                {{ Auth::user()->name }}
                                @if(Auth::user()->is_admin)
                                    <span style="background:var(--p);color:white;padding:.2rem .4rem;border-radius:3px;font-size:.7rem;margin-left:.5rem">Admin</span>
                                @endif
                            </a>
                        </li>
                        
                        <!-- ULTRA PERFORMANCE: Minimal notification bell -->
                        <li>
                            <x-notification-bell />
                        </li>
                        
                        <!-- ULTRA PERFORMANCE: Profile circle -->
                        <li>
                            <a href="{{ route('profile') }}" style="text-decoration:none">
                                <div style="width:32px;height:32px;background:var(--p);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:.8rem;transition:transform .2s ease" 
                                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . secure_url(Auth::user()->profile_picture_url) . ');background-size:cover;' : '' }}">
                                    @if(!Auth::user()->profile_picture_url)
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- ULTRA PERFORMANCE: Live activity banner - minimal -->
    <div id="act" style="background:linear-gradient(135deg,#f6f9fc,#ffffff);border-bottom:1px solid rgba(0,0,0,.05);overflow:hidden;padding:2px 0;position:relative;height:24px;margin-top:70px;display:none">
        <div id="act-s" style="display:flex;animation:scroll 20s linear infinite;white-space:nowrap">
            <!-- Activity items loaded via JavaScript -->
        </div>
    </div>

    <!-- ULTRA PERFORMANCE: Main Content -->
    <main id="main" class="m">
        <!-- ULTRA PERFORMANCE: Hero Section -->
        <section class="h f">
            <div class="ct">
                <h1>Welcome to MedCapsule</h1>
                <p style="font-size:1.1rem;margin-bottom:1.5rem;color:#666">Your Advanced Medical Education Platform</p>
                <p style="margin-bottom:2rem;color:#666">Master medical knowledge with interactive quizzes, track your progress, and excel in your studies.</p>
                <a href="{{ route('courses.index') }}" class="btn-p">
                    <i class="fas fa-play" style="margin-right:.5rem"></i>
                    Start Learning
                </a>
            </div>
        </section>

        <!-- ULTRA PERFORMANCE: Features Section -->
        <section class="s f">
            <div class="ct">
                <h2>Why Choose MedCapsule?</h2>
                <div class="r">
                    <div class="f-b">
                        <i class="fas fa-brain"></i>
                        <h4>Interactive Learning</h4>
                        <p>Engage with comprehensive quizzes and interactive content designed for medical students.</p>
                    </div>
                    <div class="f-b">
                        <i class="fas fa-chart-line"></i>
                        <h4>Progress Tracking</h4>
                        <p>Monitor your learning progress with detailed analytics and performance insights.</p>
                    </div>
                    <div class="f-b">
                        <i class="fas fa-users"></i>
                        <h4>Community Learning</h4>
                        <p>Connect with fellow medical students and learn together in a supportive environment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ULTRA PERFORMANCE: Courses Section -->
        <section class="s f">
            <div class="ct">
                <h2>Featured Courses</h2>
                
                <!-- ULTRA PERFORMANCE: Course grid with lazy loading -->
                <div id="c-grid" class="r">
                    <!-- Course cards loaded via JavaScript for performance -->
                    <div class="l" style="height:200px;border-radius:8px"></div>
                    <div class="l" style="height:200px;border-radius:8px"></div>
                    <div class="l" style="height:200px;border-radius:8px"></div>
                </div>
            </div>
        </section>

        <!-- ULTRA PERFORMANCE: Statistics Section -->
        <section class="st f">
            <div class="ct">
                <div class="r">
                    <div class="st-i">
                        <i class="fas fa-users"></i>
                        <h4 class="c">5000</h4>
                        <p>Active Students</p>
                    </div>
                    <div class="st-i">
                        <i class="fas fa-book"></i>
                        <h4 class="c">150</h4>
                        <p>Courses Available</p>
                    </div>
                    <div class="st-i">
                        <i class="fas fa-question"></i>
                        <h4 class="c">10000</h4>
                        <p>Practice Questions</p>
                    </div>
                    <div class="st-i">
                        <i class="fas fa-trophy"></i>
                        <h4 class="c">95</h4>
                        <p>Success Rate %</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ULTRA PERFORMANCE: Footer -->
    <footer class="ft f">
        <div class="ct">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:center">
                <p style="margin:0">&copy; 2025 MedCapsule. All Rights Reserved.</p>
                <p style="margin:0;text-align:right">
                    <a href="/about">About</a> | 
                    <a href="/contact">Contact</a> | 
                    <a href="/faq">FAQ</a> | 
                    <a href="/privacy">Privacy Policy</a> |
                    <a href="/demo"><strong>Try 40 Free Questions</strong></a>
                </p>
            </div>
        </div>
    </footer>

    <!-- ULTRA PERFORMANCE: Critical JavaScript inline for instant functionality -->
    <script>
        // ULTRA PERFORMANCE: Minimal global object
        window.MC={c:'{{ csrf_token() }}',u:{{ Auth::check() ? Auth::id() : 'null' }},a:{{ Auth::check() ? 'true' : 'false' }},p:performance.now()};

        // ULTRA PERFORMANCE: Instant intersection observer for animations
        if('IntersectionObserver' in window){
            const o=new IntersectionObserver(e=>{e.forEach(e=>{if(e.isIntersecting){e.target.classList.add('v');o.unobserve(e.target)}})},{threshold:.1,rootMargin:'0px 0px -25px 0px'});
            
            function i(){document.querySelectorAll('.f').forEach(e=>o.observe(e))}
            document.readyState==='loading'?document.addEventListener('DOMContentLoaded',i):i();
        }

        // ULTRA PERFORMANCE: Lazy load non-critical components
        function l(){
            // Load course data
            const g=document.getElementById('c-grid');
            if(g){
                const c=[
                    {id:1,name:'Anatomy Fundamentals',d:'Basic anatomy concepts',t:25,q:500},
                    {id:2,name:'Physiology Basics',d:'Human physiology',t:30,q:600},
                    {id:3,name:'Pathology Essentials',d:'Disease mechanisms',t:20,q:400}
                ];
                
                g.innerHTML=c.map(c=>`
                    <div class="c f" style="background:white;border-radius:8px;padding:1rem;box-shadow:0 2px 4px rgba(0,0,0,.1);transition:transform .2s ease,box-shadow .2s ease">
                        <h4 style="margin:0 0 .5rem 0;color:var(--p);font-weight:600">${c.name}</h4>
                        <p style="margin:0 0 1rem 0;color:#666;font-size:.9rem">${c.d}</p>
                        <div style="display:flex;gap:1rem;margin-bottom:1rem;font-size:.8rem;color:#666">
                            <span><i class="fas fa-book" style="color:var(--p);margin-right:.25rem"></i>${c.t} Topics</span>
                            <span><i class="fas fa-question" style="color:var(--p);margin-right:.25rem"></i>${c.q} Questions</span>
                        </div>
                        <a href="/courses/${c.id}" style="background:var(--p);color:white;padding:.5rem 1rem;border-radius:4px;text-decoration:none;font-size:.9rem;display:inline-block">View Course</a>
                    </div>
                `).join('');
                
                requestAnimationFrame(()=>{g.querySelectorAll('.c').forEach(c=>c.classList.add('v'))});
            }
            
            // Initialize counters
            const cs=document.querySelectorAll('.c');
            const co=new IntersectionObserver(e=>{e.forEach(e=>{if(e.isIntersecting){a(e.target);co.unobserve(e.target)}})},{threshold:.5});
            cs.forEach(c=>co.observe(c));
        }

        function a(e){
            const t=parseInt(e.textContent);
            let c=0;
            const i=t/30;
            
            const u=()=>{
                c+=i;
                if(c<t){
                    e.textContent=Math.ceil(c);
                    requestAnimationFrame(u);
                }else{
                    e.textContent=t;
                }
            };
            requestAnimationFrame(u);
        }

        // ULTRA PERFORMANCE: Load components after critical rendering
        document.readyState==='loading'?document.addEventListener('DOMContentLoaded',()=>{requestIdleCallback(l)}):requestIdleCallback(l);

        // ULTRA PERFORMANCE: Performance monitoring
        window.addEventListener('load',()=>{
            const t=performance.now()-window.MC.p;
            window.MC.l=t;
            
            if(navigator.sendBeacon){
                const d=JSON.stringify({l:t,u:window.location.href,a:navigator.userAgent});
                navigator.sendBeacon('/api/p',d);
            }
        });
    </script>

    <!-- ULTRA PERFORMANCE: Load external scripts asynchronously -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ULTRA PERFORMANCE: Load CSS after critical rendering -->
    <link rel="stylesheet" href="{{ asset('css/home-ultra-optimized.css') }}" media="print" onload="this.media='all'">
    
    <!-- ULTRA PERFORMANCE: Service Worker for caching -->
    <script>
        if('serviceWorker' in navigator){
            window.addEventListener('load',()=>{
                navigator.serviceWorker.register('/sw.js')
                    .then(r=>console.log('SW registered:',r))
                    .catch(e=>console.log('SW registration failed:',e));
            });
        }
    </script>
</body>
</html>