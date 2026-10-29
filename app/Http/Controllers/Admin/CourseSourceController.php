<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Admin\CourseSourceService;
use Illuminate\Http\{JsonResponse, Request};

class CourseSourceController extends Controller
{
    public function index(Course $course, CourseSourceService $service): JsonResponse
    {
        return response()->json(['sources' => $service->sources($course)]);
    }

    public function rename(Request $request, Course $course, CourseSourceService $service): JsonResponse
    {
        $data = $request->validate([
            'source' => 'required|string|max:255',
            'new_name' => ['required', 'string', 'max:255', 'different:source', 'not_regex:/[\x00-\x1F\x7F]/', function ($attribute, $value, $fail) {
                if (strtolower(trim($value)) === 'all') $fail('All is reserved for selecting every source. Choose another name.');
            }],
        ]);
        $count = $service->rename($course, $data['source'], $data['new_name']);
        return response()->json(['success' => true, 'question_count' => $count, 'message' => "Renamed {$count} questions to {$data['new_name']}."]);
    }
}
