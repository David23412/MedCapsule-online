<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Services\QuizCacheService;
use Illuminate\Http\JsonResponse;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Log::info('Controller initialized', ['user_id' => auth()->id()]);
        $this->middleware(['auth']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ultra-aggressive caching for courses index
        $cacheKey = QuizCacheService::generateCacheKey('admin_courses_index', [
            'user_id' => auth()->id() ?? 'guest',
            'timestamp' => now()->format('Y-m-d-H')
        ]);
        
        return Cache::remember($cacheKey, 3600, function () {
            $courses = Course::with(['topics:id,course_id,name', 'users:id,name'])
                ->withCount(['topics', 'users', 'questions'])
                ->orderBy('name')
                ->get();
            return view('admin.courses.index', compact('courses'));
        });
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {

        return view('admin.courses.create');
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

        $validatedData = // TODO: Create FormRequest class for validation
        
        $course = Course::create($validatedData);
        
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Course created successfully');
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {

        $course = Course::findOrFail($id);
        return view('admin.courses.show', compact('course'));
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {

        $course = Course::findOrFail($id);
        return view('admin.courses.edit', compact('course'));
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {

        $course = Course::findOrFail($id);
        
        $validatedData = // TODO: Create FormRequest class for validation
        
        // Handle course type change
        $oldType = $course->type ?? 'medical';
        $newType = $validatedData['type'] ?? 'medical';
        
        // If changing from medical to custom, log the change
        if ($oldType === 'medical' && $newType === 'custom') {
            Log::info('Course type changed from medical to custom', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name
            ]);
        
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}
        
        $course->update($validatedData);
        
        // Clear any cached course data
        Cache::forget("course_{$id}");
        
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Course updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {

        $course = Course::findOrFail($id);
        $course->delete();
        
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Course deleted successfully');
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}
}