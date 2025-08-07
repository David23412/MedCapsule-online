<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Requests\CoursePreviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use App\Services\QuizCacheService;

class CoursePreviewController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Controller initialized log ANNIHILATED
        $this->middleware(['auth']);
    }

    /**
     * Get preview data for a course - LETHAL CACHED
     *
     * @param Request $request
     * @param int $id Course ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreviewData(CoursePreviewRequest $request, $id)
    {
        try {
            $previewType = $request->input('type', 'medical');
            
            // LETHAL CACHING: Ultra-aggressive caching for course preview
            $cacheKey = QuizCacheService::generateCacheKey('course_preview', [
                'course_id' => $id,
                'type' => $previewType,
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            $data = Cache::remember($cacheKey, 3600, function () use ($id, $previewType) {
                $course = Course::select('id', 'name', 'description', 'price')
                    ->with([
                        'topics:id,course_id,name,case_type',
                        'topics.questions:id,topic_id,difficulty'
                    ])
                    ->findOrFail($id);
                
                // Get topic counts with eager loaded data
                $topicCount = $course->topics->count();
                
                if ($previewType === 'medical') {
                    // Medical course preview data
                    $casesCount = $course->topics->where('case_type', 'cases')->count();
                    $practicalCount = $course->topics->where('case_type', 'practical')->count();
                    $quizCount = $course->topics->where('case_type', 'quiz')->count();
                    
                    return [
                        'topicCount' => $topicCount,
                        'casesCount' => $casesCount,
                        'practicalCount' => $practicalCount,
                        'quizCount' => $quizCount
                    ];
                } else {
                    // Custom course preview data - FIXED N+1 QUERY
                    $categories = Topic::where('course_id', $course->id)
                        ->select('case_type')
                        ->distinct()
                        ->whereNotNull('case_type')
                        ->pluck('case_type')
                        ->toArray();
                    
                    // LETHAL FIX: Single query with groupBy instead of N+1 queries
                    $categoryCounts = Topic::where('course_id', $course->id)
                        ->whereIn('case_type', $categories)
                        ->groupBy('case_type')
                        ->selectRaw('case_type, COUNT(*) as count')
                        ->pluck('count', 'case_type')
                        ->toArray();
                    
                    // Ensure order matches categories array
                    $orderedCounts = [];
                    foreach ($categories as $category) {
                        $orderedCounts[] = $categoryCounts[$category] ?? 0;
                    }
            
                    return [
                        'topicCount' => $topicCount,
                        'categories' => $categories,
                        'categoryCounts' => $orderedCounts
                    ];
                }
            });
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error('Course preview failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}