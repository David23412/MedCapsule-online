<div class="profile-picture-container">
    <div class="current-profile-picture">
        @if(Auth::user()->profile_picture_url)
            <img src="{{ asset(Auth::user()->profile_picture_url) }}" alt="Profile picture" class="current-profile-img">
        @else
            <div class="profile-placeholder">
                <span>{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
        @endif
    </div>
    
    <h5 class="mt-3">Profile Picture</h5>
    
    <form action="{{ route('profile.update-picture') }}" method="POST" enctype="multipart/form-data" class="profile-picture-form mt-3">
        @csrf
        <div class="mb-3">
            <label for="profile_picture" class="form-label">Select new profile picture</label>
            <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/jpeg,image/png,image/gif,image/jpg" required>
            <div class="form-text">Supported formats: JPEG, PNG, GIF. Max size: 2MB.</div>
        </div>
        
        @if ($errors->has('profile_picture'))
            <div class="alert alert-danger mt-2">
                {{ $errors->first('profile_picture') }}
            </div>
        @endif
        
        <button type="submit" class="btn btn-primary upload-btn">
            <i class="fas fa-upload me-2"></i>
            Upload Picture
        </button>
    </form>
</div>

<style>
.profile-picture-container {
    background-color: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.current-profile-picture {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.current-profile-img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f8f9fa;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.current-profile-img:hover {
    transform: scale(1.05);
}

.profile-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #2196f3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    font-weight: bold;
    color: white;
    border: 4px solid #f8f9fa;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.upload-btn {
    transition: all 0.3s ease;
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
}
</style> 