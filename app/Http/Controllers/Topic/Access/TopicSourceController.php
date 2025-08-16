<?php

namespace App\Http\Controllers\Topic\Access;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 PERFORMANCE BEAST: Topic Source Controller
 * Handles: Topic source management and retrieval
 * Lines: ~80 (well under 200 limit)
 */
class TopicSourceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Get topic sources for user
     */
    public function getTopicSources(Topic $topic, ?User $user = null): array
    {
        $user = $user ?? auth()->user();
        
        // Admin gets all sources
        if ($user->isAdmin()) {
            return $this->getAllTopicSources($topic);
        }

        // Check for active assignment
        $activeAssignment = $this->getActiveAssignment($topic, $user);
        if ($activeAssignment) {
            return $this->getAssignmentSources($activeAssignment, $topic);
        }

        // Default: all sources
        return $this->getAllTopicSources($topic);
    }

    /**
     * API endpoint for getting topic sources
     */
    public function getSourcesApi(Request $request, int $topicId)
    {
        try {
            $topic = Topic::findOrFail($topicId);
            $sources = $this->getTopicSources($topic);
            
            return response()->json([
                'success' => true,
                'sources' => $sources
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sources: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all sources for a topic
     */
    private function getAllTopicSources(Topic $topic): array
    {
        return DB::table('questions')
            ->where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->pluck('source')
            ->toArray();
    }

    /**
     * Get sources for assignment
     */
    private function getAssignmentSources(Assignment $assignment, Topic $topic): array
    {
        if (empty($assignment->sources)) {
            return $this->getAllTopicSources($topic);
        }

        return is_array($assignment->sources) ? $assignment->sources : json_decode($assignment->sources, true) ?? [];
    }

    /**
     * Get active assignment for topic and user
     */
    private function getActiveAssignment(Topic $topic, User $user): ?Assignment
    {
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->where('deadline', '>', now())
            ->first();
    }
}