<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSettingsController extends Controller
{
    private static ?bool $topicsHavePreviewEnabledColumn = null;

    /**
     * Update an admin setting.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'type' => 'required|in:string,boolean,integer,float,json'
        ]);

        AdminSetting::set($validated['key'], $validated['value'], $validated['type']);

        return response()->json(['success' => true, 'message' => 'Setting updated successfully']);
    }

    /**
     * Get a specific admin setting.
     */
    public function get(string $key): JsonResponse
    {
        $value = AdminSetting::get($key);
        return response()->json(['value' => $value]);
    }

    /**
     * Update fake enrolled settings for a single course.
     */
    public function updateFakeEnrolled(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'fake_enrolled_enabled' => 'required|boolean',
            'fake_enrolled_count' => 'required|integer|min:0|max:9999'
        ]);

        $course->update($validated);

        return response()->json(['success' => true, 'message' => 'Course fake enrolled settings updated']);
    }

    /**
     * Bulk update fake enrolled settings for multiple courses.
     */
    public function bulkUpdateFakeEnrolled(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'courses' => 'required|array',
            'courses.*.id' => 'required|exists:courses,id',
            'courses.*.fake_enrolled_enabled' => 'required|boolean',
            'courses.*.fake_enrolled_count' => 'required|integer|min:0|max:9999'
        ]);

        foreach ($validated['courses'] as $courseData) {
            Course::where('id', $courseData['id'])->update([
                'fake_enrolled_enabled' => $courseData['fake_enrolled_enabled'],
                'fake_enrolled_count' => $courseData['fake_enrolled_count']
            ]);
        }

        return response()->json(['success' => true, 'message' => 'All courses updated successfully']);
    }

    /**
     * Get topics for a specific course (admin API).
     */
    public function getCourseTopics(int $courseId): JsonResponse
    {
        $courseExists = Course::query()
            ->whereKey($courseId)
            ->exists();

        if (!$courseExists) {
            return response()->json(['success' => false, 'message' => 'Course not found'], 404);
        }

        $columns = ['id', 'name', 'case_type', 'display_order', 'is_locked'];
        $hasPreviewColumn = self::topicsHavePreviewEnabledColumn();

        if ($hasPreviewColumn) {
            $columns[] = 'is_preview_enabled';
        }

        $topics = DB::table('topics')
            ->where('course_id', $courseId)
            ->select($columns)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $explicitPreviewTopicIds = $hasPreviewColumn
            ? $topics
                ->filter(fn ($topic) => (bool) ($topic->is_preview_enabled ?? false))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $effectivePreviewTopicIds = $explicitPreviewTopicIds;
        $isUsingDefaultPreview = false;

        if ($effectivePreviewTopicIds === []) {
            $isUsingDefaultPreview = true;
            $previewLimit = (int) config('free_course_survey.preview_topics_count', 5);
            $effectivePreviewTopicIds = $previewLimit > 0
                ? $topics->take($previewLimit)->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];
        }

        $effectivePreviewLookup = array_fill_keys($effectivePreviewTopicIds, true);
        $topicsPayload = $topics
            ->map(function ($topic) use ($effectivePreviewLookup, $isUsingDefaultPreview): array {
                $isOpenToPreview = isset($effectivePreviewLookup[(int) $topic->id]);

                return [
                    'id' => (int) $topic->id,
                    'name' => (string) $topic->name,
                    'case_type' => (string) ($topic->case_type ?? 'quiz'),
                    'display_order' => (int) ($topic->display_order ?? 0),
                    'is_locked' => (bool) ($topic->is_locked ?? false),
                    'is_preview_enabled' => $isOpenToPreview,
                    'is_explicit_preview_enabled' => (bool) ($topic->is_preview_enabled ?? false),
                    'is_open_to_preview' => $isOpenToPreview,
                    'standard_user_state' => $isOpenToPreview ? 'open_to_preview' : 'not_open_to_preview',
                    'preview_source' => $isUsingDefaultPreview ? 'default_limit' : 'custom_selection',
                ];
            });

        return response()->json([
            'success' => true,
            'topics' => $topicsPayload,
            'meta' => [
                'total_topics' => $topicsPayload->count(),
                'open_to_preview_count' => $topicsPayload->where('is_open_to_preview', true)->count(),
                'not_open_to_preview_count' => $topicsPayload->where('is_open_to_preview', false)->count(),
                'using_default_preview' => $isUsingDefaultPreview,
                'preview_topics_column_available' => $hasPreviewColumn,
            ],
        ]);
    }

    public function updateCoursePreviewTopics(Request $request, Course $course): JsonResponse
    {
        if (! self::topicsHavePreviewEnabledColumn()) {
            return response()->json([
                'success' => false,
                'message' => 'Preview topic controls are not available until migrations run.',
            ], 409);
        }

        $validated = $request->validate([
            'topic_ids' => 'present|array',
            'topic_ids.*' => 'integer|exists:topics,id',
        ]);

        $topicIds = Topic::query()
            ->where('course_id', $course->id)
            ->whereIn('id', $validated['topic_ids'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        Topic::query()
            ->where('course_id', $course->id)
            ->update(['is_preview_enabled' => false]);

        if ($topicIds !== []) {
            Topic::query()
                ->where('course_id', $course->id)
                ->whereIn('id', $topicIds)
                ->update(['is_preview_enabled' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preview topics updated.',
            'topic_ids' => $topicIds,
        ]);
    }

    private static function topicsHavePreviewEnabledColumn(): bool
    {
        return self::$topicsHavePreviewEnabledColumn ??= Schema::hasColumn('topics', 'is_preview_enabled');
    }
}
