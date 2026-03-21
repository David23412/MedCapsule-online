<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class VideoProxyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Generate a temporary, secure video token
     */
    public function generateSecureToken(Video $video): JsonResponse
    {
        try {
            // Check user access
            $user = auth()->user();
            $video->loadMissing('topic.course');
            $course = $video->topic->course;
            
            if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.'
                ], 403);
            }

            if (!$user->isAdmin() && $user->isTopicPreviewLocked($video->topic)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This video is outside the free preview. Unlock the full course to continue.'
                ], 403);
            }

            // Generate secure token that expires in 30 minutes
            $token = Str::random(64);
            $expiresAt = now()->addMinutes(30);
            
            // Store token in cache with video data
            Cache::put("video_token_{$token}", [
                'video_id' => $video->id,
                'user_id' => $user->id,
                'youtube_video_id' => $video->youtube_video_id,
                'video_title' => $video->title,
                'expires_at' => $expiresAt
            ], $expiresAt);

            return response()->json([
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate video token.'
            ], 500);
        }
    }

    /**
     * Serve video through secure proxy
     */
    public function serveVideo(Request $request, $token)
    {
        try {
            // Get video data from cache
            $videoData = Cache::get("video_token_{$token}");
            
            if (!$videoData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired video token.'
                ], 403);
            }

            // Verify user
            if ($videoData['user_id'] !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not valid for this user.'
                ], 403);
            }

            // Check if token is expired
            if (now()->gt($videoData['expires_at'])) {
                Cache::forget("video_token_{$token}");
                return response()->json([
                    'success' => false,
                    'message' => 'Video token has expired.'
                ], 403);
            }

            // Return our own secure video player data instead of YouTube embed
            return response()->json([
                'success' => true,
                'video_id' => $videoData['youtube_video_id'],
                'video_title' => $videoData['video_title'] ?? 'Video Lecture',
                'expires_in' => now()->diffInMinutes($videoData['expires_at'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to serve video.'
            ], 500);
        }
    }

    /**
     * Get video stream data for our custom player
     */
    public function getVideoStream(Request $request, $token)
    {
        try {
            // Get video data from cache
            $videoData = Cache::get("video_token_{$token}");
            
            if (!$videoData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired video token.'
                ], 403);
            }

            // Verify user
            if ($videoData['user_id'] !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not valid for this user.'
                ], 403);
            }

            // Check if token is expired
            if (now()->gt($videoData['expires_at'])) {
                Cache::forget("video_token_{$token}");
                return response()->json([
                    'success' => false,
                    'message' => 'Video token has expired.'
                ], 403);
            }

            // Return video stream data for our custom player
            return response()->json([
                'success' => true,
                'video_id' => $videoData['youtube_video_id'],
                'video_title' => $videoData['video_title'] ?? 'Video Lecture',
                'expires_in' => now()->diffInMinutes($videoData['expires_at'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get video stream.'
            ], 500);
        }
    }

    /**
     * Proxy video content to hide YouTube completely
     */
    public function proxyVideo(Request $request, $youtubeId)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                abort(403, 'Unauthorized');
            }

            // Get user's active video token from session/cache
            $userId = auth()->id();
            $activeTokens = Cache::get("user_active_videos_{$userId}", []);
            
            // Check if user has access to this video
            $hasAccess = false;
            foreach ($activeTokens as $token) {
                $videoData = Cache::get("video_token_{$token}");
                if ($videoData && $videoData['youtube_video_id'] === $youtubeId) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                abort(403, 'Access denied to this video');
            }

            // Create a secure YouTube embed URL with maximum restrictions
            $embedUrl = "https://www.youtube.com/embed/{$youtubeId}?" . http_build_query([
                'rel' => 0,                    // No related videos
                'controls' => 0,               // Hide all controls
                'showinfo' => 0,              // Hide video info
                'modestbranding' => 1,        // Minimal YouTube branding
                'fs' => 0,                    // Disable fullscreen
                'cc_load_policy' => 0,        // No captions
                'iv_load_policy' => 3,        // No annotations
                'autohide' => 1,              // Hide controls automatically
                'disablekb' => 1,             // Disable keyboard controls
                'enablejsapi' => 0,           // Disable JavaScript API
                'origin' => request()->getSchemeAndHttpHost(),
                'playsinline' => 1,           // Prevent fullscreen on mobile
                'loop' => 0,                  // No looping
                'autoplay' => 0,              // No autoplay
                'mute' => 0,                  // Allow sound
                'start' => 0,                 // Start from beginning
                'end' => 0,                   // No end time
                'vq' => 'hd720'              // Force HD quality
            ]);

            // Return the secure embed URL
            return response()->json([
                'success' => true,
                'embed_url' => $embedUrl
            ]);
            
        } catch (\Exception $e) {
            abort(500, 'Failed to proxy video');
        }
    }
}
