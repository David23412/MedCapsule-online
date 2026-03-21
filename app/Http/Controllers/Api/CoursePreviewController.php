<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\Request;
use App\Http\Requests\CoursePreviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('course_preview', [
                'course_id' => $id,
                'type' => $previewType,
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            $data = Cache::remember($cacheKey, 3600, function () use ($id, $previewType) {
                Course::query()
                    ->select('id')
                    ->findOrFail($id);

                $topicCounts = DB::table('topics')
                    ->where('course_id', $id)
                    ->selectRaw('case_type, COUNT(*) as count')
                    ->groupBy('case_type')
                    ->pluck('count', 'case_type');

                $topicCount = (int) $topicCounts->sum();
                
                if ($previewType === 'medical') {
                    return [
                        'topicCount' => $topicCount,
                        'casesCount' => (int) ($topicCounts->get('cases') ?? 0),
                        'practicalCount' => (int) ($topicCounts->get('practical') ?? 0),
                        'quizCount' => (int) ($topicCounts->get('quiz') ?? 0),
                    ];
                }

                $categories = $topicCounts
                    ->keys()
                    ->filter(fn ($category) => !is_null($category) && $category !== '')
                    ->values()
                    ->all();

                $orderedCounts = array_map(
                    fn ($category) => (int) ($topicCounts->get($category) ?? 0),
                    $categories
                );
        
                return [
                    'topicCount' => $topicCount,
                    'categories' => $categories,
                    'categoryCounts' => $orderedCounts
                ];
            });
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            \Log::error('Course preview failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}
