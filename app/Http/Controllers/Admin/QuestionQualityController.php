<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\QuestionQualityService;
use Illuminate\Http\Request;

class QuestionQualityController extends Controller
{
    public function index(Request $request, QuestionQualityService $service)
    {
        $data = $request->validate([
            'course_id' => 'nullable|integer|exists:courses,id',
            'issue' => 'nullable|in:all,broken_answer,repeated_explanation',
            'page' => 'nullable|integer|min:1|max:100000',
        ]);

        return response()->json($service->scan(
            isset($data['course_id']) ? (int) $data['course_id'] : null,
            $data['issue'] ?? 'all',
            (int) ($data['page'] ?? 1)
        ))->header('Cache-Control', 'no-store');
    }
}
