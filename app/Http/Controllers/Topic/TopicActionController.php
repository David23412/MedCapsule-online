<?php

namespace App\Http\Controllers\Topic;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicRequest;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL OPTIMIZED TOPIC ACTION CONTROLLER
 * Handles topic creation and management operations
 */
class TopicActionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }
            if (!auth()->user()->isAdmin()) {
                return redirect()->route('courses.index')->with('error', 'Access denied. Only admins can manage topics.');
            }
            return $next($request);
        });
    }

    /**
     * Handle topic creation
     */
    public function addTopic(TopicRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }
        if (!auth()->user()->isAdmin()) {
            return redirect()->route('courses.index')->with('error', 'Access denied. Only admins can manage topics.');
        }

        $validated = $request->validated();

        try {
            $name = $validated['name'];
            $isExam = $request->has('is_exam') 
                ? $request->boolean('is_exam') 
                : (str_contains(strtolower($name), 'mid') || str_contains(strtolower($name), 'final') || str_contains(strtolower($name), 'exam'));

            $topicData = [
                'name' => $name,
                'course_id' => $validated['course_id'],
                'description' => $validated['description'] ?? '',
                'display_order' => $validated['display_order'] ?? 0,
                'is_exam' => $isExam,
                'created_by' => auth()->id()
            ];

            if (Topic::hasSubjectColumn()) {
                $topicData['subject'] = filled($validated['subject'] ?? null) ? trim($validated['subject']) : null;
            } else {
                Topic::logMissingSubjectColumn();
            }

            $topic = Topic::create($topicData);


            return redirect()->back()->with('success', 'Topic added successfully!');
            
        } catch (\Exception $e) {
            Log::error('Failed to create topic', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create topic. Please try again.');
        }
    }

    /**
     * Update topic data after quiz attempt - COMPLEXITY REDUCED
     */
    public function updateTopicAfterQuiz($topicId)
    {
        $topic = Topic::findOrFail($topicId);

        $userId = auth()->id();
        if ($userId === null) {
            return;
        }

        // Use the same per-student progress calculation as the topic listing.
        $topic->load(['userProgress' => fn ($query) => $query->where('user_id', $userId)]);
        app(\App\Services\TopicProgressService::class)
            ->hydrateTopicsWithFreshQuizProgress(collect([$topic]), $userId);
    }

    /**
     * Get cases for a course
     */
    public function casesForCourse(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->get();
    }

    /**
     * Get cases count for a course
     */
    public function casesCount(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->count();
    }

}
