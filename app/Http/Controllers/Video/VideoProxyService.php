<?php

namespace App\Http\Controllers\Video;

use App\Models\Video;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 🚀 CRITICAL REFACTOR: Video Proxy Service
 * 
 * Extracted video proxy logic from fat VideoProxyController
 * Handles token generation, validation, and video access control
 */
class VideoProxyService
{
    private const TOKEN_EXPIRY_MINUTES = 60;
    private const CACHE_PREFIX = 'video_token:';

    /**
     * Generate secure video token
     */
    public function generateVideoToken(Video $video, User $user): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRY_MINUTES);
        
        $tokenData = [
            'video_id' => $video->id,
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'video_url' => $video->url,
            'video_title' => $video->title
        ];
        
        Cache::put(
            self::CACHE_PREFIX . $token,
            $tokenData,
            self::TOKEN_EXPIRY_MINUTES * 60
        );
        
        return [
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Validate token and get video data
     */
    public function validateAndGetVideoData(string $token): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $token);
    }

    /**
     * Check if user can access video
     */
    public function canUserAccessVideo(User $user, Video $video): bool
    {
        // Admin can access all videos
        if ($user->isAdmin()) {
            return true;
        }

        // Check if video is active
        if (!$video->is_active) {
            return false;
        }

        // Check if user has access to the video's topic/course
        if ($video->topic) {
            return $user->hasAccessToCourse($video->topic->course_id);
        }

        return false;
    }

    /**
     * Create video response data
     */
    public function createVideoResponse(array $videoData): array
    {
        return [
            'success' => true,
            'data' => [
                'video_url' => $videoData['video_url'],
                'video_title' => $videoData['video_title'],
                'video_id' => $videoData['video_id']
            ]
        ];
    }

    /**
     * Create stream response data
     */
    public function createStreamResponse(array $videoData): array
    {
        return [
            'success' => true,
            'data' => [
                'stream_url' => $videoData['video_url'],
                'title' => $videoData['video_title'],
                'video_id' => $videoData['video_id'],
                'type' => 'video/mp4'
            ]
        ];
    }

    /**
     * Revoke video token
     */
    public function revokeToken(string $token): bool
    {
        return Cache::forget(self::CACHE_PREFIX . $token);
    }
}