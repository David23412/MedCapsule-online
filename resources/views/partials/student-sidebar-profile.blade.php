@props([
    'profileUrl' => null,
    'profileName' => '',
    'profileMeta' => '',
    'profileImageUrl' => null,
    'profileFallbackUrl' => null,
    'logoutAction' => null,
])

<div class="sidebar-footer">
    <a href="{{ $profileUrl }}" class="sidebar-profile-link">
        <span class="sidebar-profile-avatar">
            <img src="{{ $profileImageUrl }}" alt="Profile Picture" onerror="this.src='{{ $profileFallbackUrl }}'">
        </span>
        <span class="sidebar-profile-copy">
            <span class="sidebar-profile-name">{{ $profileName }}</span>
            <span class="sidebar-profile-meta">{{ $profileMeta }}</span>
        </span>
    </a>
    <form method="POST" action="{{ $logoutAction }}">
        @csrf
        <button type="submit" class="sidebar-logout-btn">
            <i class="fas fa-right-from-bracket"></i>
            <span>Logout</span>
        </button>
    </form>
</div>
