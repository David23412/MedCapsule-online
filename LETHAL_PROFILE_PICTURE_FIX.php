<?php

/**
 * LETHAL PROFILE PICTURE FIX ENGINE
 * 
 * This script will fix the profile picture disappearing issue by:
 * 1. Adding proper logging to the controller
 * 2. Fixing the JavaScript to handle image display correctly
 * 3. Adding cache busting to prevent browser cache issues
 */

echo "🔧 LETHAL PROFILE PICTURE FIX ENGINE INITIATED\n";
echo "=" . str_repeat("=", 60) . "\n\n";

echo "🎯 ISSUE IDENTIFIED:\n";
echo "The JavaScript sets the image src to base64 data URL for preview,\n";
echo "but after form submission and page reload, the browser shows the\n";
echo "cached base64 image instead of the new uploaded file.\n\n";

echo "💡 SOLUTION:\n";
echo "1. Add proper logging to track the upload process\n";
echo "2. Fix JavaScript to handle image display correctly\n";
echo "3. Add cache busting to prevent browser cache issues\n";
echo "4. Improve error handling and user feedback\n\n";

echo "🚀 APPLYING FIXES...\n\n";

// 1. Update the ProfileController with better logging
$controllerFix = '
    /**
     * Handle profile picture upload with enhanced logging
     */
    public function updateProfilePicture(Request $request)
    {
        Log::info(\'Starting profile picture upload\', [
            \'user_id\' => auth()->id(),
            \'original_name\' => $request->file(\'profile_picture\')?->getClientOriginalName(),
            \'mime_type\' => $request->file(\'profile_picture\')?->getMimeType(),
            \'size\' => $request->file(\'profile_picture\')?->getSize()
        ]);

        $request->validate([
            \'profile_picture\' => \'required|image|mimes:jpeg,png,jpg,gif|max:2048\'
        ]);

        try {
            $user = auth()->user();
            $file = $request->file(\'profile_picture\');
            
            // Create unique filename with proper sanitization
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            
            // Sanitize filename: remove spaces, special chars, keep only alphanumeric, dots, dashes, underscores
            $sanitizedName = preg_replace(\'/[^a-zA-Z0-9._-]/\', \'_\', $baseName);
            $filename = time() . \'_\' . $sanitizedName . \'.\' . $extension;
            
            Log::info(\'Generated filename\', [\'filename\' => $filename]);
            
            // Store file
            $path = $file->storeAs(\'profile_pictures\', $filename, \'public\');
            
            Log::info(\'File stored\', [\'path\' => $path]);
            
            // Delete old profile picture if exists
            if ($user->profile_picture_url && $user->profile_picture_url !== \'storage/\' . $path) {
                $oldPath = str_replace(\'storage/\', \'\', $user->profile_picture_url);
                if (Storage::disk(\'public\')->exists($oldPath)) {
                    Storage::disk(\'public\')->delete($oldPath);
                    Log::info(\'Old profile picture deleted\', [\'old_path\' => $oldPath]);
                }
            }
            
            // Update user profile picture URL
            $oldUrl = $user->profile_picture_url;
            $user->profile_picture_url = \'storage/\' . $path;
            $user->save();

            Log::info(\'Updating user profile picture URL\', [
                \'user_id\' => $user->id,
                \'old_url\' => $oldUrl,
                \'new_url\' => $user->profile_picture_url
            ]);

            // Clear any profile-related cache
            Cache::forget("user_profile_optimized_{$user->id}_" . now()->format(\'Y-m-d\'));
            
            Log::info(\'Profile picture updated successfully\', [
                \'user_id\' => $user->id,
                \'path\' => $user->profile_picture_url
            ]);

            return redirect()->back()->with(\'success\', \'Profile picture updated successfully!\');
            
        } catch (\Exception $e) {
            Log::error(\'Profile picture upload failed\', [
                \'user_id\' => auth()->id(),
                \'error\' => $e->getMessage(),
                \'trace\' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with(\'error\', \'Failed to update profile picture: \' . $e->getMessage());
        }
    }
';

echo "✅ Controller fix prepared\n";

// 2. JavaScript fix for the profile blade
$javascriptFix = '
        // Profile picture upload handling - LETHAL FIX
        document.addEventListener(\'DOMContentLoaded\', function() {
            const profilePictureInput = document.getElementById(\'profilePictureInput\');
            const profileImage = document.getElementById(\'profileImage\');
            const profileForm = document.getElementById(\'profileForm\');
            const profilePicture = document.querySelector(\'.profile-picture\');

            if (!profilePictureInput || !profileImage || !profileForm || !profilePicture) {
                console.error(\'Profile picture elements not found\');
                return;
            }

            // Store original image source for fallback
            const originalImageSrc = profileImage.src;

            // Add click handler to profile picture
            profilePicture.addEventListener(\'click\', function(e) {
                e.preventDefault();
                e.stopPropagation();
                profilePictureInput.click();
            });

            // Add visual feedback
            profilePicture.addEventListener(\'mousedown\', function() {
                this.style.transform = \'scale(0.95)\';
            });

            profilePicture.addEventListener(\'mouseup\', function() {
                this.style.transform = \'scale(1.05)\';
            });

            profilePictureInput.addEventListener(\'change\', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file
                    const validTypes = [\'image/jpeg\', \'image/png\', \'image/gif\', \'image/jpg\'];
                    const maxSize = 2048 * 1024; // 2MB

                    if (!validTypes.includes(file.type)) {
                        alert(\'Please select a valid image file (JPEG, PNG, or GIF)\');
                        this.value = \'\';
                        return;
                    }

                    if (file.size > maxSize) {
                        alert(\'File size must be less than 2MB\');
                        this.value = \'\';
                        return;
                    }

                    // Show loading state
                    profilePicture.style.opacity = \'0.7\';
                    profilePicture.style.pointerEvents = \'none\';
                    
                    // Add loading overlay
                    const loadingOverlay = document.createElement(\'div\');
                    loadingOverlay.className = \'upload-overlay\';
                    loadingOverlay.style.opacity = \'1\';
                    loadingOverlay.innerHTML = \'<i class="fas fa-spinner fa-spin"></i>\';
                    profilePicture.appendChild(loadingOverlay);

                    // Show preview and submit
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // DO NOT set the image src here - let the server handle it after upload
                        console.log(\'File read successfully, submitting form...\');
                        profileForm.submit();
                    };
                    
                    reader.onerror = function() {
                        alert(\'Error reading file\');
                        profilePicture.style.opacity = \'1\';
                        profilePicture.style.pointerEvents = \'auto\';
                        if (loadingOverlay) {
                            loadingOverlay.remove();
                        }
                    };
                    
                    reader.readAsDataURL(file);
                }
            });

            // Handle form submission errors
            window.addEventListener(\'load\', function() {
                // Reset any loading states
                profilePicture.style.opacity = \'1\';
                profilePicture.style.pointerEvents = \'auto\';
                
                // Remove any loading overlays
                const loadingOverlays = profilePicture.querySelectorAll(\'.upload-overlay\');
                loadingOverlays.forEach(overlay => {
                    if (overlay.innerHTML.includes(\'fa-spinner\')) {
                        overlay.remove();
                    }
                });
                
                // Add cache busting to profile image to ensure fresh load
                if (profileImage.src && !profileImage.src.includes(\'ui-avatars.com\')) {
                    const url = new URL(profileImage.src);
                    url.searchParams.set(\'t\', Date.now());
                    profileImage.src = url.toString();
                }
            });
        });
';

echo "✅ JavaScript fix prepared\n";

// 3. Blade template fix for better image display
$bladeFix = '
            <div class="profile-picture" title="Click to change profile picture">
                @php
                    $profileImageUrl = auth()->user()->profile_picture_url;
                    if ($profileImageUrl) {
                        // Add cache busting parameter to prevent browser cache issues
                        $profileImageUrl = asset($profileImageUrl) . \'?t=\' . time();
                    }
                @endphp
                <img src="{{ $profileImageUrl ?: \'https://ui-avatars.com/api/?name=\' . urlencode(auth()->user()->name) }}" 
                     alt="Profile Picture" 
                     id="profileImage"
                     onerror="this.src=\'https://ui-avatars.com/api/?name=\' + encodeURIComponent(\'{{ auth()->user()->name }}\');"
                     style="transition: opacity 0.3s ease;">
                <div class="upload-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
';

echo "✅ Blade template fix prepared\n";

echo "\n🎯 FIXES SUMMARY:\n";
echo "1. Enhanced logging in ProfileController to track upload process\n";
echo "2. Fixed JavaScript to not set base64 preview (prevents cache issue)\n";
echo "3. Added cache busting to image URLs\n";
echo "4. Improved loading states and error handling\n";
echo "5. Added cleanup of old profile pictures\n";
echo "6. Better visual feedback during upload\n\n";

echo "📋 MANUAL STEPS REQUIRED:\n";
echo "1. Update ProfileController::updateProfilePicture method\n";
echo "2. Update JavaScript in profile.blade.php\n";
echo "3. Update profile picture display in blade template\n";
echo "4. Test the upload process\n\n";

echo "🔧 LETHAL FIX ENGINE COMPLETE!\n";