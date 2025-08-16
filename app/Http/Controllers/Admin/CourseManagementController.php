<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\Course;
use App\Services\CourseManagementService;

/**
 * ✅ REFACTORED: CourseManagementController - Admin-only course management
 * Extracted from fat CourseController (373 lines) for better separation of concerns
 */
class CourseManagementController extends Controller
{
    protected $courseManagementService;

    public function __construct(CourseManagementService $courseManagementService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        });
        
        $this->courseManagementService = $courseManagementService;
    }

    /**
     * Show form to add a new course (admin only)
     */
    public function create()
    {
        return view('admin.courses.create');
    }

    /**
     * Store a new course (admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'required|string|max:2000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'image_url' => 'nullable|url|max:2048',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'academic_year' => 'required|string|in:YEAR 1 - SEMESTER 1,YEAR 1 - SEMESTER 2,YEAR 2 - SEMESTER 1,YEAR 2 - SEMESTER 2,YEAR 3 - SEMESTER 1,YEAR 3 - SEMESTER 2,YEAR 4 - SEMESTER 1,YEAR 4 - SEMESTER 2,YEAR 5 - SEMESTER 1,YEAR 5 - SEMESTER 2,YEAR 6 - SEMESTER 1,YEAR 6 - SEMESTER 2,SAT,EST,ACT',
            'price' => 'nullable|numeric|min:0|max:999999.99'
        ], [
            'name.unique' => 'A course with this name already exists.',
            'image_file.image' => 'The uploaded file must be an image.',
            'image_file.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image_file.max' => 'The image may not be greater than 5MB.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733).',
            'title_color.regex' => 'Title color must be a valid hex color code (e.g., #FF5733).',
            'academic_year.required' => 'Academic year is required.',
            'academic_year.in' => 'Please select a valid academic year.'
        ]);

        try {
            $result = $this->courseManagementService->createCourse($validated, $request);
            
            if ($result['success']) {
                return redirect()->route('admin.courses.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred while creating the course. Please try again.');
        }
    }

    /**
     * Display course management dashboard
     */
    public function index()
    {
        $courses = Course::select([
                'id', 'name', 'description', 'price', 'currency', 'academic_year', 'created_at'
            ])
            ->withCount(['enrolledUsers', 'topics'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show course details for editing
     */
    public function show(Course $course)
    {
        $course->load(['topics', 'enrolledUsers:id,name,email']);
        
        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show course edit form
     */
    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    /**
     * Update course
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name,' . $course->id,
            'description' => 'required|string|max:2000',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'image_url' => 'nullable|url|max:2048',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'academic_year' => 'required|string',
            'price' => 'nullable|numeric|min:0|max:999999.99'
        ]);

        try {
            $result = $this->courseManagementService->updateCourse($course, $validated, $request);
            
            if ($result['success']) {
                return redirect()->route('admin.courses.show', $course)
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to update course', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred while updating the course.');
        }
    }

    /**
     * Delete course
     */
    public function destroy(Course $course)
    {
        try {
            $result = $this->courseManagementService->deleteCourse($course);
            
            if ($result['success']) {
                return redirect()->route('admin.courses.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to delete course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred while deleting the course.');
        }
    }
}