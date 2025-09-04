{{-- MINIMAL & CLEAN RESPONSIVE NAVIGATION --}}
<div class="minimal-nav">
    <!-- Brand -->
    <div class="minimal-nav-brand">
        <a href="{{ route('home') }}">MedCapsule</a>
    </div>

    <!-- Hamburger Toggle -->
    <button class="hamburger-toggle" id="hamburger-toggle" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>

    <!-- Navigation Menu -->
    <nav class="nav-menu" id="nav-menu">
        @guest
            <div class="nav-section">
                <a href="{{ route('login') }}" class="nav-link">Login</a>
                <a href="{{ route('register') }}" class="nav-link primary-btn">Sign Up</a>
            </div>
        @else
            <div class="nav-section">
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="{{ route('courses.index') }}" class="nav-link">
                    <i class="fas fa-book"></i> Courses
                </a>
            </div>

            @if(Auth::user()->isAdmin())
                <div class="nav-section">
                    <div class="nav-section-title">Admin</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link">
                        <i class="fas fa-cog"></i> Admin Dashboard
                    </a>
                </div>
            @endif

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <div class="user-profile">
                    <div class="user-avatar">
                        @if(Auth::user()->profile_picture_url)
                            <img src="{{ Auth::user()->profile_picture_url }}" alt="Profile">
                        @else
                            {{ substr(Auth::user()->name, 0, 1) }}
                        @endif
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-email">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <a href="{{ route('profile') }}" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="nav-link logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        @endguest
    </nav>

    <!-- Overlay -->
    <div class="nav-overlay" id="nav-overlay"></div>
</div>

<style>
/* ============================================================================
   MINIMAL RESPONSIVE NAVIGATION - CLEAN & SIMPLE
   ============================================================================ */

.minimal-nav {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    padding: 0 1rem;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

/* Brand */
.minimal-nav-brand a {
    font-weight: 700;
    font-size: 1.3rem;
    color: #007bff;
    text-decoration: none;
    transition: color 0.2s;
}

.minimal-nav-brand a:hover {
    color: #0056b3;
}

/* Hamburger Menu */
.hamburger-toggle {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background 0.2s;
}

.hamburger-toggle:hover {
    background: rgba(0,0,0,0.05);
}

.hamburger-toggle span {
    width: 22px;
    height: 2px;
    background: #333;
    margin: 3px 0;
    transition: 0.3s;
    border-radius: 1px;
}

.hamburger-toggle.active span:nth-child(1) {
    transform: rotate(-45deg) translate(-6px, 6px);
}

.hamburger-toggle.active span:nth-child(2) {
    opacity: 0;
}

.hamburger-toggle.active span:nth-child(3) {
    transform: rotate(45deg) translate(-5px, -5px);
}

/* Navigation Menu */
.nav-menu {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.nav-section {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: #666;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: inherit;
}

.nav-link:hover {
    color: #007bff;
    background: rgba(0,123,255,0.1);
}

.nav-link i {
    width: 16px;
    text-align: center;
}

.primary-btn {
    background: #007bff !important;
    color: white !important;
}

.primary-btn:hover {
    background: #0056b3 !important;
    color: white !important;
    transform: translateY(-1px);
}

/* Navigation Sections */
.nav-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 0;
    margin: 0.5rem 0;
    border-top: 1px solid #e5e7eb;
}

/* User Profile */
.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    min-width: 0;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Logout Button */
.logout-btn {
    color: #dc3545 !important;
}

.logout-btn:hover {
    background: rgba(220,53,69,0.1) !important;
    color: #dc3545 !important;
}

.inline-form {
    margin: 0;
    padding: 0;
}

/* Mobile Styles */
@media (max-width: 768px) {
    .hamburger-toggle {
        display: flex;
    }

    .nav-menu {
        position: fixed;
        top: 0;
        right: 0;
        width: 280px;
        max-width: 80vw;
        height: 100vh;
        background: white;
        flex-direction: column;
        align-items: stretch;
        padding: 4rem 1rem 2rem;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 1000;
        box-shadow: -4px 0 20px rgba(0,0,0,0.15);
        overflow-y: auto;
    }

    .nav-menu.active {
        transform: translateX(0);
    }

    .nav-section {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
    }

    .nav-link {
        justify-content: flex-start;
        padding: 0.75rem 1rem;
        border-radius: 0;
        width: 100%;
    }

    .primary-btn {
        background: #007bff !important;
        color: white !important;
        margin-top: 0.5rem;
    }

    .nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.3);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease;
        z-index: 999;
    }

    .nav-overlay.active {
        display: block;
        opacity: 1;
        visibility: visible;
    }

    .user-profile {
        margin: 1rem 0;
        padding: 1rem;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}
</style>

<script>
// MINIMAL RESPONSIVE NAVIGATION SCRIPT
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-toggle');
    const menu = document.getElementById('nav-menu');
    const overlay = document.getElementById('nav-overlay');

    if (!hamburger || !menu) return;

    function toggleMenu() {
        hamburger.classList.toggle('active');
        menu.classList.toggle('active');
        overlay.classList.toggle('active');

        // Prevent body scroll
        if (menu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    function closeMenu() {
        hamburger.classList.remove('active');
        menu.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', closeMenu);

    // Close menu on window resize (desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });

    // Accessibility: Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menu.classList.contains('active')) {
            closeMenu();
        }
    });
});
</script>
