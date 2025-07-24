<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $courses = Course::all();
        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.courses.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'is_published' => 'required|boolean',
            'type' => 'nullable|string|in:medical,custom',
        ]);
        
        $course = Course::create($validatedData);
        
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Course created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::findOrFail($id);
        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::findOrFail($id);
        return view('admin.courses.edit', compact('course'));
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
        $course = Course::findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'is_published' => 'required|boolean',
            'type' => 'nullable|string|in:medical,custom',
        ]);
        
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
        $course = Course::findOrFail($id);
        $course->delete();
        
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Course deleted successfully');
    }
}