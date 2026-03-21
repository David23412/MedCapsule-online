<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFreeCourseSurveyDraftRequest;
use App\Http\Requests\SubmitFreeCourseSurveyRequest;
use App\Services\FreeCourseSurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeCourseSurveyController extends Controller
{
    public function __construct(
        protected FreeCourseSurveyService $surveyService
    ) {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $draft = $this->surveyService->draftFor($user);
        $redirectTo = $this->surveyService->sanitizeRedirect($request->query('redirect_to'))
            ?? $draft?->redirect_to
            ?? route('courses.index', [], false);

        if ($user->hasUnlockedFreeCourses()) {
            return redirect($redirectTo)->with('success', config('free_course_survey.success_message'));
        }

        return view('surveys.free-course-unlock', [
            'survey' => $this->surveyService->definition(),
            'questions' => $this->surveyService->questions(),
            'freeCourses' => $this->surveyService->freeCourses(),
            'draft' => $draft,
            'redirectTo' => $redirectTo,
        ]);
    }

    public function saveDraft(SaveFreeCourseSurveyDraftRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasUnlockedFreeCourses()) {
            return response()->json([
                'saved' => true,
                'completed' => true,
            ]);
        }

        $draft = $this->surveyService->saveDraft($user, $request->validated());

        return response()->json([
            'saved' => true,
            'current_step' => $draft->current_step,
            'saved_at' => optional($draft->last_saved_at)->toIso8601String(),
        ]);
    }

    public function submit(SubmitFreeCourseSurveyRequest $request): JsonResponse
    {
        $result = $this->surveyService->complete(
            $request->user(),
            $request->validated('answers'),
            $request->validated('redirect_to')
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'redirect_to' => $result['redirect_to'],
        ]);
    }
}
