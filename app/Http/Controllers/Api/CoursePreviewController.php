<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\Request;

class CoursePreviewController extends Controller
{
    /**
     * Get preview data for a course
     *
     * @param Request $request
     * @param int $id Course ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreviewData(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $previewType = $request->input('type', 'medical');
        
        // Get topic counts
        $topicCount = Topic::where('course_id', $course->id)->count();
        
        if ($previewType === 'medical') {
            // Medical course preview data
            $casesCount = Topic::where('course_id', $course->id)
                ->where('case_type', 'cases')
                ->count();
                
            $practicalCount = Topic::where('course_id', $course->id)
                ->where('case_type', 'practical')
                ->count();
                
            $quizCount = Topic::where('course_id', $course->id)
                ->where('case_type', 'quiz')
                ->count();
            
            return response()->json([
                'topicCount' => $topicCount,
                'casesCount' => $casesCount,
                'practicalCount' => $practicalCount,
                'quizCount' => $quizCount
            ]);
        } else {
            // Custom course preview data
            $categories = Topic::where('course_id', $course->id)
                ->select('case_type')
                ->distinct()
                ->whereNotNull('case_type')
                ->pluck('case_type')
                ->toArray();
            
            $categoryCounts = [];
            foreach ($categories as $category) {
                $categoryCounts[] = Topic::where('course_id', $course->id)
                    ->where('case_type', $category)
                    ->count();
            }
            
            return response()->json([
                'topicCount' => $topicCount,
                'categories' => $categories,
                'categoryCounts' => $categoryCounts
            ]);
        }
    }
}