<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused Profile Update Controller
 * Handles: Profile updates and modifications
 * Lines: ~100 (well under 200 limit)
 */
class ProfileUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Handle profile picture upload - Using original optimized logic
     */
    public function updateProfilePicture(Request $request)
    {
        try {
            // Validate the profile picture upload
            $request->validate([
                'profile_picture' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120' // 5MB max
            ]);
            
            $user = auth()->user();
            
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                // Ensure file is valid
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                // Delete old profile picture if exists
                if ($user->profile_picture_url) {
                    $oldPath = str_replace('storage/', '', $user->profile_picture_url);
                    $oldFilePath = storage_path('app/public/' . $oldPath);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Create a unique filename
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());

                // Store the file in the public disk
                $path = $file->storeAs('profile_pictures', $filename, 'public');

                if (!$path) {
                    throw new \Exception('Failed to store file');
                }

                // Update path to include storage prefix
                $fullPath = 'storage/' . $path;

                // Update user's profile_picture_url
                $user->profile_picture_url = $fullPath;
                $user->save();

                // Check if it's an AJAX request
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Profile picture updated successfully!',
                        'new_url' => $fullPath,
                        'timestamp' => time()
                    ]);
                }
                
                return redirect()->back()
                    ->with('success', 'Profile picture updated successfully!')
                    ->with('cache_bust', time());
            }

            return redirect()->back()->with('error', 'No file uploaded.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->validator)->withInput();
            
        } catch (\Exception $e) {
            Log::error('Profile picture upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile picture: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update profile picture: ' . $e->getMessage());
        }
    }
}