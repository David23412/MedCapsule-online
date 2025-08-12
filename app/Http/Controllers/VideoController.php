<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get secure video access for authenticated users
     */
    public function getVideoAccess(Video $video): JsonResponse
    {
        try {
            // Check if user has access to the course containing this video
            $user = auth()->user();
            $course = $video->topic->course;
            
            // Allow access if user is admin, enrolled, or if course is free
            if ($user->role !== 'admin' && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You need to purchase this course to access its videos.'
                ], 403);
            }
            
            // Check if video is active
            if (!$video->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This video is currently unavailable.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'youtube_video_id' => $video->youtube_video_id,
                'title' => $video->title,
                'description' => $video->description,
                'expires_at' => now()->addHours(2)->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load video access.'
            ], 500);
        }
    }

    /**
     * Get all videos for admin management
     */
    public function index(): JsonResponse
    {
        try {
            $videos = Video::with(['topic:id,name,course_id', 'topic.course:id,name'])
                ->select('id', 'topic_id', 'title', 'youtube_video_id', 'is_active', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($video) {
                    return [
                        'id' => $video->id,
                        'title' => $video->title,
                        'youtube_video_id' => $video->youtube_video_id,
                        'is_active' => $video->is_active,
                        'topic_name' => $video->topic->name,
                        'course_name' => $video->topic->course->name,
                        'created_at' => $video->created_at->format('M d, Y')
                    ];
                });

            return response()->json([
                'success' => true,
                'videos' => $videos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load videos.'
            ], 500);
        }
    }

    /**
     * Store a new video
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'topic_id' => 'required|exists:topics,id',
                'youtube_url' => 'required|url',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            // Extract YouTube video ID from URL
            $videoId = $this->extractYouTubeId($request->youtube_url);
            if (!$videoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid YouTube URL. Please provide a valid YouTube video URL.'
                ], 400);
            }

            // Check if topic already has a video
            $existingVideo = Video::where('topic_id', $request->topic_id)->first();
            if ($existingVideo) {
                return response()->json([
                    'success' => false,
                    'message' => 'This topic already has a video. Delete the existing video first.'
                ], 400);
            }

            $video = Video::create([
                'topic_id' => $request->topic_id,
                'youtube_video_id' => $videoId,
                'title' => $request->title,
                'description' => $request->description,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video added successfully!',
                'video' => $video
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle video active status
     */
    public function toggle(Video $video): JsonResponse
    {
        try {
            $video->update(['is_active' => !$video->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Video status updated successfully!',
                'is_active' => $video->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update video status.'
            ], 500);
        }
    }

    /**
     * Upload a video file
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'video' => 'required|file|mimes:mp4,webm,ogg,quicktime|max:512000', // 500MB max
                'topic_id' => 'nullable|exists:topics,id',
                'title' => 'nullable|string|max:255'
            ]);

            $file = $request->file('video');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('videos', $fileName, 'public');
            
            // Create video record
            $video = Video::create([
                'topic_id' => $request->topic_id,
                'title' => $request->title ?: $file->getClientOriginalName(),
                'description' => 'Uploaded video file',
                'youtube_video_id' => null, // Not a YouTube video
                'video_url' => '/storage/' . $filePath, // Store the file path
                'is_active' => true,
                'video_type' => 'uploaded' // New field to distinguish from YouTube
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully!',
                'video_id' => $video->id,
                'video_url' => $video->video_url,
                'title' => $video->title
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a video
     */
    public function destroy(Video $video): JsonResponse
    {
        try {
            $video->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video.'
            ], 500);
        }
    }

    /**
     * Get video stream data using secure token
     */
    public function getVideoStream($token): JsonResponse
    {
        try {
            // For now, return a simple response - you can implement token validation later
            // This is where you'd validate the token and return the actual video data
            
            return response()->json([
                'success' => true,
                'video_id' => 'sample_video_123', // This would come from token validation
                'video_url' => '/storage/videos/sample_video.mp4', // This would be the actual video URL
                'title' => 'Sample Video',
                'expires_at' => now()->addHours(2)->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get video stream.'
            ], 500);
        }
    }

    /**
     * Extract YouTube video ID from various URL formats
     */
    private function extractYouTubeId($url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}