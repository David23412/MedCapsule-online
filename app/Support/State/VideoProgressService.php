<?php

namespace App\Support\State;

use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Single Source of Truth for Video Progress State
 * 
 * Replaces split brain logic between:
 * - video_progress.completed (boolean)
 * - video_progress.progress_percentage (decimal)
 * - video_progress.last_watched_at (datetime)
 */
class VideoProgressService
{
    const CACHE_TTL = 300; // 5 minutes
    const COMPLETION_THRESHOLD = 95; // Consider 95%+ as completed

    /**
     * Get definitive video progress status
     * 
     * @param int $userId
     * @param int $videoId
     * @return array
     */
    public function getVideoProgress(int $userId, int $videoId): array
    {
        $cacheKey = "video_progress_{$userId}_{$videoId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $videoId) {
            $progress = DB::table('video_progress')
                ->where('user_id', $userId)
                ->where('video_id', $videoId)
                ->first();
                
            if (!$progress) {
                return [
                    'exists' => false,
                    'progress_percentage' => 0.0,
                    'is_completed' => false,
                    'watched_duration_seconds' => 0,
                    'total_duration_seconds' => null,
                    'last_watched_at' => null,
                    'completion_method' => null,
                    'computed_at' => now()
                ];
            }
            
            $progressPercentage = (float) $progress->progress_percentage;
            $isCompleted = $progressPercentage >= self::COMPLETION_THRESHOLD;
            
            return [
                'exists' => true,
                'progress_percentage' => $progressPercentage,
                'is_completed' => $isCompleted,
                'watched_duration_seconds' => (int) ($progress->watched_duration_seconds ?? 0),
                'total_duration_seconds' => (int) ($progress->total_duration_seconds ?? 0),
                'last_watched_at' => $progress->last_watched_at ? Carbon::parse($progress->last_watched_at) : null,
                'completion_method' => $isCompleted ? 'percentage_threshold' : null,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Check if video is completed (simple boolean)
     */
    public function isVideoCompleted(int $userId, int $videoId): bool
    {
        return $this->getVideoProgress($userId, $videoId)['is_completed'];
    }

    /**
     * Get video progress percentage
     */
    public function getProgressPercentage(int $userId, int $videoId): float
    {
        return $this->getVideoProgress($userId, $videoId)['progress_percentage'];
    }

    /**
     * Update video progress
     */
    public function updateVideoProgress(
        int $userId, 
        int $videoId, 
        float $progressPercentage, 
        int $watchedDurationSeconds = null,
        int $totalDurationSeconds = null
    ): array {
        return DB::transaction(function () use ($userId, $videoId, $progressPercentage, $watchedDurationSeconds, $totalDurationSeconds) {
            // Ensure progress is within valid range
            $progressPercentage = max(0, min(100, $progressPercentage));
            
            // Get video info if total duration not provided
            if ($totalDurationSeconds === null) {
                $video = Video::find($videoId);
                $totalDurationSeconds = $video?->duration ?? 0;
            }
            
            // Calculate watched duration if not provided
            if ($watchedDurationSeconds === null && $totalDurationSeconds > 0) {
                $watchedDurationSeconds = (int) (($progressPercentage / 100) * $totalDurationSeconds);
            }
            
            // Update or create progress record
            DB::table('video_progress')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'video_id' => $videoId
                ],
                [
                    'progress_percentage' => $progressPercentage,
                    'watched_duration_seconds' => $watchedDurationSeconds ?? 0,
                    'total_duration_seconds' => $totalDurationSeconds ?? 0,
                    'last_watched_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
            
            // Clear cache
            $this->clearVideoProgressCache($userId, $videoId);
            
            // Get updated progress
            $updatedProgress = $this->getVideoProgress($userId, $videoId);
            
            logger()->info('Video progress updated', [
                'user_id' => $userId,
                'video_id' => $videoId,
                'progress_percentage' => $progressPercentage,
                'is_completed' => $updatedProgress['is_completed'],
                'watched_duration' => $watchedDurationSeconds,
                'updated_at' => now()
            ]);
            
            return $updatedProgress;
        });
    }

    /**
     * Mark video as completed
     */
    public function markVideoCompleted(int $userId, int $videoId): array
    {
        return $this->updateVideoProgress($userId, $videoId, 100.0);
    }

    /**
     * Get user's completed videos count
     */
    public function getUserCompletedVideosCount(int $userId): int
    {
        $cacheKey = "user_completed_videos_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return DB::table('video_progress')
                ->where('user_id', $userId)
                ->where('progress_percentage', '>=', self::COMPLETION_THRESHOLD)
                ->count();
        });
    }

    /**
     * Get user's total watch time
     */
    public function getUserTotalWatchTime(int $userId): int
    {
        $cacheKey = "user_total_watch_time_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return (int) DB::table('video_progress')
                ->where('user_id', $userId)
                ->sum('watched_duration_seconds');
        });
    }

    /**
     * Get user's video progress summary
     */
    public function getUserVideoSummary(int $userId): array
    {
        $cacheKey = "user_video_summary_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $stats = DB::table('video_progress')
                ->where('user_id', $userId)
                ->selectRaw('
                    COUNT(*) as videos_started,
                    SUM(CASE WHEN progress_percentage >= ? THEN 1 ELSE 0 END) as videos_completed,
                    AVG(progress_percentage) as average_progress,
                    SUM(watched_duration_seconds) as total_watch_time,
                    MAX(last_watched_at) as last_activity
                ', [self::COMPLETION_THRESHOLD])
                ->first();
                
            $videosStarted = (int) ($stats->videos_started ?? 0);
            $videosCompleted = (int) ($stats->videos_completed ?? 0);
            $averageProgress = (float) ($stats->average_progress ?? 0);
            $totalWatchTime = (int) ($stats->total_watch_time ?? 0);
            $lastActivity = $stats->last_activity ? Carbon::parse($stats->last_activity) : null;
            
            return [
                'videos_started' => $videosStarted,
                'videos_completed' => $videosCompleted,
                'videos_in_progress' => $videosStarted - $videosCompleted,
                'completion_rate' => $videosStarted > 0 ? ($videosCompleted / $videosStarted) * 100 : 0,
                'average_progress_percentage' => round($averageProgress, 2),
                'total_watch_time_seconds' => $totalWatchTime,
                'total_watch_time_formatted' => $this->formatDuration($totalWatchTime),
                'last_activity' => $lastActivity,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Get videos in progress for user
     */
    public function getUserVideosInProgress(int $userId, int $limit = null): array
    {
        $cacheKey = "user_videos_in_progress_{$userId}_" . ($limit ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            $query = DB::table('video_progress')
                ->join('videos', 'video_progress.video_id', '=', 'videos.id')
                ->join('topics', 'videos.topic_id', '=', 'topics.id')
                ->where('video_progress.user_id', $userId)
                ->where('video_progress.progress_percentage', '<', self::COMPLETION_THRESHOLD)
                ->where('video_progress.progress_percentage', '>', 0)
                ->select([
                    'video_progress.*',
                    'videos.title as video_title',
                    'videos.youtube_video_id',
                    'topics.name as topic_name',
                    'topics.id as topic_id'
                ])
                ->orderBy('video_progress.last_watched_at', 'desc');
                
            if ($limit) {
                $query->limit($limit);
            }
            
            return $query->get()->map(function ($progress) {
                return [
                    'video_id' => $progress->video_id,
                    'video_title' => $progress->video_title,
                    'topic_name' => $progress->topic_name,
                    'topic_id' => $progress->topic_id,
                    'progress_percentage' => (float) $progress->progress_percentage,
                    'watched_duration_seconds' => (int) $progress->watched_duration_seconds,
                    'last_watched_at' => Carbon::parse($progress->last_watched_at),
                    'youtube_video_id' => $progress->youtube_video_id
                ];
            })->toArray();
        });
    }

    /**
     * Get topic video completion stats
     */
    public function getTopicVideoStats(int $userId, int $topicId): array
    {
        $cacheKey = "topic_video_stats_{$userId}_{$topicId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $topicId) {
            // Get all videos in topic
            $topicVideos = DB::table('videos')
                ->where('topic_id', $topicId)
                ->where('is_active', true)
                ->count();
                
            if ($topicVideos === 0) {
                return [
                    'total_videos' => 0,
                    'completed_videos' => 0,
                    'completion_percentage' => 0,
                    'has_videos' => false
                ];
            }
            
            // Get completed videos in topic
            $completedVideos = DB::table('video_progress')
                ->join('videos', 'video_progress.video_id', '=', 'videos.id')
                ->where('video_progress.user_id', $userId)
                ->where('videos.topic_id', $topicId)
                ->where('video_progress.progress_percentage', '>=', self::COMPLETION_THRESHOLD)
                ->count();
                
            return [
                'total_videos' => $topicVideos,
                'completed_videos' => $completedVideos,
                'completion_percentage' => ($completedVideos / $topicVideos) * 100,
                'has_videos' => true
            ];
        });
    }

    /**
     * Sync legacy completed field with computed completion
     */
    public function syncLegacyCompletedField(int $userId, int $videoId): bool
    {
        $progress = $this->getVideoProgress($userId, $videoId);
        
        if (!$progress['exists']) {
            return false;
        }
        
        $computedCompleted = $progress['is_completed'];
        
        // Get current legacy value
        $currentRecord = DB::table('video_progress')
            ->where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();
            
        if (!$currentRecord) {
            return false;
        }
        
        $legacyCompleted = (bool) $currentRecord->completed;
        
        if ($legacyCompleted !== $computedCompleted) {
            DB::table('video_progress')
                ->where('user_id', $userId)
                ->where('video_id', $videoId)
                ->update(['completed' => $computedCompleted]);
                
            logger()->warning('Legacy completed field synced with computed value', [
                'user_id' => $userId,
                'video_id' => $videoId,
                'old_completed' => $legacyCompleted,
                'new_completed' => $computedCompleted,
                'progress_percentage' => $progress['progress_percentage'],
                'synced_at' => now()
            ]);
            
            return true;
        }
        
        return false; // No sync needed
    }

    /**
     * Verify data consistency between legacy and computed completion
     */
    public function verifyCompletionConsistency(int $userId): array
    {
        $progressRecords = DB::table('video_progress')
            ->where('user_id', $userId)
            ->get();
            
        $inconsistencies = [];
        
        foreach ($progressRecords as $record) {
            $computedCompleted = $record->progress_percentage >= self::COMPLETION_THRESHOLD;
            $legacyCompleted = (bool) $record->completed;
            
            if ($legacyCompleted !== $computedCompleted) {
                $inconsistencies[] = [
                    'video_id' => $record->video_id,
                    'legacy_completed' => $legacyCompleted,
                    'computed_completed' => $computedCompleted,
                    'progress_percentage' => (float) $record->progress_percentage,
                    'threshold' => self::COMPLETION_THRESHOLD
                ];
            }
        }
        
        return [
            'consistent' => empty($inconsistencies),
            'total_checked' => $progressRecords->count(),
            'inconsistencies_count' => count($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'checked_at' => now()
        ];
    }

    /**
     * Format duration in human readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }

    /**
     * Clear video progress cache
     */
    public function clearVideoProgressCache(int $userId, int $videoId): void
    {
        Cache::forget("video_progress_{$userId}_{$videoId}");
        Cache::forget("user_completed_videos_{$userId}");
        Cache::forget("user_total_watch_time_{$userId}");
        Cache::forget("user_video_summary_{$userId}");
        
        // Clear topic-specific caches
        $video = Video::find($videoId);
        if ($video && $video->topic_id) {
            Cache::forget("topic_video_stats_{$userId}_{$video->topic_id}");
        }
        
        // Clear in-progress caches
        for ($limit = 5; $limit <= 20; $limit += 5) {
            Cache::forget("user_videos_in_progress_{$userId}_{$limit}");
        }
        Cache::forget("user_videos_in_progress_{$userId}_all");
    }

    /**
     * Bulk progress check for multiple videos
     */
    public function getBulkVideoProgress(int $userId, array $videoIds): array
    {
        $results = [];
        
        foreach ($videoIds as $videoId) {
            $results[$videoId] = $this->getVideoProgress($userId, $videoId);
        }
        
        return $results;
    }
}