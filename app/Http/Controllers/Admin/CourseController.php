<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CourseService;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->middleware(['auth', 'admin']);
        $this->courseService = $courseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::with(['topics:id,course_id,name', 'users:users.id,name'])
            ->withCount(['topics', 'users', 'questions'])
            ->orderBy('name')
            ->get();
        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {

        return redirect()->route('add-course.form');
    
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

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'access_type' => 'required|string|in:free,preview,paid',
            'price' => 'nullable|numeric|min:0',
            'type' => 'required|string|in:medical,custom',
        ]);

        $validatedData['access_type'] = Course::normalizeAccessType($validatedData['access_type'] ?? null);
        $validatedData['price'] = Course::normalizePriceForAccessType(
            $validatedData['access_type'],
            $validatedData['price'] ?? null
        );

        if ($policyValidationMessage = Course::accessPolicyValidationMessage($validatedData['access_type'], $validatedData['price'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['price' => $policyValidationMessage]);
        }
        
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
        return redirect()->route('topics.forCourse', $course->id);
    
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
            $result = $this->courseService->updateCourse((int)$id, $request->all());

            if (!$result['success']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors($result['errors'] ?? [])
                    ->with('error', $result['message']);
            }

            return redirect()->route('admin.courses.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error("Course update endpoint error", [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to update course.'], 500);
            }

            return redirect()->back()->with('error', 'An unexpected error occurred.');
        }
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
