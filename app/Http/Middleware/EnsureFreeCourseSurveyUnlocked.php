<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Topic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFreeCourseSurveyUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->hasUnlockedFreeCourses() || $user->isAdmin()) {
            return $next($request);
        }

        $course = $this->resolveCourseFromRequest($request);

        if (!$course || !$course->isFree()) {
            return $next($request);
        }

        $surveyUrl = route('courses.index', [
            'survey' => 1,
            'redirect_to' => $request->getRequestUri(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => config('free_course_survey.description'),
                'survey_url' => $surveyUrl,
            ], 423);
        }

        return redirect($surveyUrl);
    }

    private function resolveCourseFromRequest(Request $request): ?Course
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        $courseParameter = $route->parameter('course');
        if ($courseParameter instanceof Course) {
            return $courseParameter;
        }

        if (is_numeric($courseParameter)) {
            return Course::query()->select('id', 'price')->find((int) $courseParameter);
        }

        $courseIdParameter = $route->parameter('courseId');
        if (is_numeric($courseIdParameter)) {
            return Course::query()->select('id', 'price')->find((int) $courseIdParameter);
        }

        $topicParameter = $route->parameter('topic');
        if ($topicParameter instanceof Topic) {
            return $topicParameter->course()->select('id', 'price')->first();
        }

        if (is_numeric($topicParameter)) {
            $topic = Topic::query()->select('id', 'course_id')->find((int) $topicParameter);

            if ($topic) {
                return Course::query()->select('id', 'price')->find($topic->course_id);
            }
        }

        return null;
    }
}
