{{-- Simple Navigation Component --}}
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="/">MedCapsule</a>
        <div class="nav-links">
            <a href="javascript:history.back()" class="nav-link">← Back</a>
            @auth
                <a href="{{ route('profile') }}" class="profile-circle">
                    <img src="{{ Auth::user()->profile_picture_url }}?v={{ time() }}" alt="Profile Picture" class="profile-image" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&size=200&background=007bff&color=ffffff'">
                </a>
            @endauth
        </div>
    </div>
</nav>
