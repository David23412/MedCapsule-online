{{-- Simple Navigation Component --}}
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="/">MedCapsule</a>
        <div class="nav-links">
            <a href="javascript:history.back()" class="nav-link">← Back</a>
            @auth
                @php
                    $navigationFallbackProfileImageUrl = 'https://ui-avatars.com/api/?name=' . urlencode($navigationUser?->name ?? Auth::user()->name) . '&size=200&background=007bff&color=ffffff';
                @endphp
                <a href="{{ route('profile') }}" class="profile-circle">
                    <img src="{{ $navigationProfileImageUrl }}" alt="Profile Picture" class="profile-image" onerror="this.src='{{ $navigationFallbackProfileImageUrl }}'">
                </a>
            @endauth
        </div>
    </div>
</nav>
