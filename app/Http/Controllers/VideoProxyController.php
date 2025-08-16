<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Video\VideoProxyService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 🚀 CRITICAL REFACTOR: Video Proxy Controller
 * 
 * Lightweight controller that delegates to VideoProxyService
 * Replaces fat controller with clean separation of concerns
 */
class VideoProxyController extends Controller
{
    private VideoProxyService $videoProxyService;

    public function __construct(VideoProxyService $videoProxyService)
    {
        $this->middleware('auth');
        $this->videoProxyService = $videoProxyService;
    }

    /**
     * Generate secure video token
     */
    public function generateSecureToken(Video $video): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$this->videoProxyService->canUserAccessVideo($user, $video)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $token = $this->videoProxyService->generateVideoToken($video, $user);
            
            Log::info('Video token generated', ['video_id' => $video->id, 'user_id' => $user->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Token generated successfully',
                'data' => [
                    'token' => $token['token'],
                    'expires_at' => $token['expires_at']->toISOString()
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to generate video token', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to generate video token'], 500);
        }
    }

    /**
     * Serve video through secure proxy
     */
    public function serveVideo(Request $request, $token): JsonResponse
    {
        try {
            $videoData = $this->videoProxyService->validateAndGetVideoData($token);
            
            if (!$videoData) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired video token'], 403);
            }

            Log::info('Video served', ['video_id' => $videoData['video_id'], 'user_id' => $videoData['user_id']]);
            
            return response()->json($this->videoProxyService->createVideoResponse($videoData));
            
        } catch (Exception $e) {
            Log::error('Failed to serve video', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to serve video'], 500);
        }
    }

    /**
     * Get video stream data for custom player
     */
    public function getVideoStream(Request $request, $token): JsonResponse
    {
        try {
            $videoData = $this->videoProxyService->validateAndGetVideoData($token);
            
            if (!$videoData) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired video token'], 403);
            }

            Log::info('Video stream requested', ['video_id' => $videoData['video_id'], 'user_id' => $videoData['user_id']]);
            
            return response()->json($this->videoProxyService->createStreamResponse($videoData));
            
        } catch (Exception $e) {
            Log::error('Failed to get video stream', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to get video stream'], 500);
        }
    }
}
            return $this->createVideoResponse($videoData);
            
        } catch (Exception $e) {
            $this->logError('Failed to get video stream', $e);
            return $this->createErrorResponse('Failed to get video stream.');
        }
    }

    /**
     * Proxy video content to hide YouTube completely
     * FIXED: Reduced complexity and added proper validation
     */
    public function proxyVideo(Request $request, $youtubeId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();
            
            if (!$this->hasUserAccessToVideo($user, $youtubeId)) {
                return $this->createErrorResponse('Access denied to this video', 403);
            }

            $embedUrl = $this->createSecureEmbedUrl($youtubeId);
            
            $this->logVideoOperation('video_proxied', null, $user->id, ['youtube_id' => $youtubeId]);
            
            return $this->createSuccessResponse('Video proxy created successfully', [
                'embed_url' => $embedUrl
            ]);
            
        } catch (Exception $e) {
            $this->logError('Failed to proxy video', $e);
            return $this->createErrorResponse('Failed to proxy video', 500);
        }
    }

    /**
     * FIXED: Extract authentication logic to eliminate duplication
     */
    private function getAuthenticatedUser()
    {
        return $this->auth->user();
    }

    /**
     * FIXED: Extract access control logic to eliminate duplication
     */
    private function canUserAccessVideo($user, Video $video): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $video->topic->course;
        
        if (!$course->price || $course->price <= 0) {
            return true;
        }

        return $user->hasAccessToCourse($course->id);
    }

    /**
     * FIXED: Extract token generation logic to eliminate duplication
     */
    private function generateVideoToken(Video $video, $user): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(30);
        
        $tokenData = [
            'video_id' => $video->id,
            'user_id' => $user->id,
            'youtube_video_id' => $video->youtube_video_id,
            'video_title' => $video->title,
            'expires_at' => $expiresAt
        ];
        
        $this->cache->put("video_token_{$token}", $tokenData, $expiresAt);
        
        return [
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * FIXED: Extract token validation logic to eliminate duplication
     */
    private function validateAndGetVideoData($token): ?array
    {
        $videoData = $this->cache->get("video_token_{$token}");
        
        if (!$videoData) {
            return null;
        }

        $user = $this->getAuthenticatedUser();
        
        if ($videoData['user_id'] !== $user->id) {
            return null;
        }

        if ($this->isTokenExpired($videoData['expires_at'])) {
            $this->cache->forget("video_token_{$token}");
            return null;
        }

        return $videoData;
    }

    /**
     * FIXED: Extract token expiration check to eliminate duplication
     */
    private function isTokenExpired($expiresAt): bool
    {
        return now()->gt($expiresAt);
    }

    /**
     * FIXED: Extract video access check to eliminate duplication
     */
    private function hasUserAccessToVideo($user, $youtubeId): bool
    {
        $activeTokens = $this->cache->get("user_active_videos_{$user->id}", []);
        
        foreach ($activeTokens as $token) {
            $videoData = $this->cache->get("video_token_{$token}");
            if ($videoData && $videoData['youtube_video_id'] === $youtubeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * FIXED: Extract secure embed URL creation to eliminate duplication
     */
    private function createSecureEmbedUrl($youtubeId): string
    {
        $params = [
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
        ];

        return "https://www.youtube.com/embed/{$youtubeId}?" . http_build_query($params);
    }

    /**
     * FIXED: Extract video response creation to eliminate duplication
     */
    private function createVideoResponse(array $videoData): JsonResponse
    {
        return $this->createSuccessResponse('Video data retrieved successfully', [
            'video_id' => $videoData['youtube_video_id'],
            'video_title' => $videoData['video_title'] ?? 'Video Lecture',
            'expires_in' => now()->diffInMinutes($videoData['expires_at'])
        ]);
    }

    /**
     * FIXED: Extract success response creation to eliminate duplication
     */
    private function createSuccessResponse(string $message, array $data = []): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        return $this->responseFactory->json($response, 200);
    }

    /**
     * FIXED: Extract error response creation to eliminate duplication
     */
    private function createErrorResponse(string $message, int $status = 500): JsonResponse
    {
        return $this->responseFactory->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logVideoOperation(string $operation, ?int $videoId, int $userId, array $context = []): void
    {
        $this->logger->info("Video operation: {$operation}", array_merge([
            'video_id' => $videoId,
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * FIXED: Extract error logging logic to eliminate duplication
     */
    private function logError(string $message, Exception $exception): void
    {
        $this->logger->error($message, [
            'error' => $exception->getMessage(),
            'user_id' => $this->auth->id(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}