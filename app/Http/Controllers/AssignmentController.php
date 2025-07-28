<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function index()
    {
        // LETHAL FIX: SIMPLIFIED CACHE KEY - No more MD5 complexity
        $cacheKey = "assignments_data_" . auth()->id();
        
        $data = Cache::remember($cacheKey, 300, function () {
            $query = Assignment::with(['user:id,name,email', 'course:id,name', 'topic:id,name'])
                ->where('tutor_id', auth()->id())
                ->select(['id', 'user_id', 'course_id', 'topic_id', 'title', 'deadline', 'status', 'score_percentage', 'created_at']);

            // LETHAL FILTERING - Direct database queries only
            if ($status = request('status')) {
                $query->where('status', $status);
            }
            if ($courseId = request('course_id')) {
                $query->where('course_id', $courseId);
            }
            if ($studentId = request('student_id')) {
                $query->where('user_id', $studentId);
            }

            $assignments = $query->orderBy('deadline', 'asc')->get();
            
            // LETHAL DATA LOADING - Single queries only
            $courses = Cache::remember('courses_all', 3600, fn() => 
                Course::select(['id', 'name'])->get()
            );
            $students = Cache::remember('students_non_admin', 3600, fn() => 
                User::select(['id', 'name', 'email'])->where('role', '!=', 'admin')->get()
            );

            return compact('assignments', 'courses', 'students');
        });

        return view('assignments.index', $data);
    }

    public function bulkCreate()
    {
        // LETHAL CACHE - Preload everything in single queries
        $cacheKey = "bulk_create_data_" . auth()->id();
        
        $data = Cache::remember($cacheKey, 1800, function () {
            $courses = Course::select(['id', 'name'])
                ->withCount('topics')
                ->get();
            
            $students = User::select(['id', 'name', 'email'])
                ->where('role', '!=', 'admin')
                ->get();
            
            // LETHAL TOPIC LOADING - Single query with JSON aggregation
            $allTopics = Topic::select([
                    'id', 'name', 'course_id',
                    DB::raw('(SELECT COUNT(*) FROM questions WHERE topic_id = topics.id) as questions_count')
                ])
                ->get()
                ->groupBy('course_id');
            
            // LETHAL SOURCE LOADING - Single query with distinct
            $allSources = Question::select(['topic_id', 'source'])
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->distinct()
                ->get()
                ->groupBy('topic_id')
                ->map(function ($sources) {
                    // Convert to array of source strings for easier JavaScript handling
                    return $sources->pluck('source')->unique()->values()->toArray();
                });
            
            return compact('courses', 'students', 'allTopics', 'allSources');
        });

        // Ensure variables are always available
        $data['allTopics'] = $data['allTopics'] ?? collect();
        $data['allSources'] = $data['allSources'] ?? collect();
        
        return view('assignments.create', $data);
    }

    public function bulkStore(Request $request)
    {
        // LETHAL VALIDATION - Minimal rules only
        // NO DUPLICATE PREVENTION - TUTORS HAVE FULL FLEXIBILITY!
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic_id' => 'required|exists:topics,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'deadline' => 'required|date|after:now',
            'duration_minutes' => 'nullable|numeric|min:0',
            'assignment_mode' => 'required|in:complete_topic,source_specific',
            'assigned_source' => 'nullable|string',
            'title' => 'nullable|string|max:255',
        ]);

        // LETHAL BULK INSERT - Single database operation
        // CREATE UNLIMITED ASSIGNMENTS - NO CONSTRAINTS!
        $tutorId = auth()->id();
        $assignments = [];
        $now = now();
        
        foreach ($validated['student_ids'] as $studentId) {
            $assignments[] = [
                'tutor_id' => $tutorId,
                'user_id' => $studentId,
                'topic_id' => $validated['topic_id'],
                'course_id' => $validated['course_id'],
                'title' => $validated['title'] ?? null,
                'deadline' => $validated['deadline'],
                'duration_minutes' => $validated['duration_minutes'] === null || $validated['duration_minutes'] === '' ? 0.00 : (float)($validated['duration_minutes']),
                'assignment_mode' => $validated['assignment_mode'],
                'assigned_source' => $validated['assigned_source'] ?? null,
                'status' => Assignment::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }



        // LETHAL INSERT - Using explicit SQL to ensure 0 is stored as 0
        foreach ($assignments as $assignmentData) {
            DB::insert('
                INSERT INTO assignments (
                    tutor_id, user_id, topic_id, course_id, title, deadline, 
                    duration_minutes, assignment_mode, assigned_source, status, 
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ', [
                $assignmentData['tutor_id'],
                $assignmentData['user_id'],
                $assignmentData['topic_id'],
                $assignmentData['course_id'],
                $assignmentData['title'],
                $assignmentData['deadline'],
                $assignmentData['duration_minutes'],
                $assignmentData['assignment_mode'],
                $assignmentData['assigned_source'],
                $assignmentData['status'],
                $assignmentData['created_at'],
                $assignmentData['updated_at']
            ]);
        }
        
        // LETHAL CACHE INVALIDATION - FIXED!
        Cache::forget("assignments_data_" . auth()->id());
        Cache::forget("bulk_create_data_" . auth()->id());

        // LETHAL SUCCESS RESPONSE - PREVENTS BUTTON STUCK STATE
        return redirect()->route('assignments.index')
            ->with('success', 'Successfully created ' . count($assignments) . ' assignment(s)!')
            ->with('created_count', count($assignments));
    }

    public function show(Assignment $assignment)
    {
        // LETHAL LOADING - Only essential data
        $assignment->load(['user:id,name,email', 'course:id,name', 'topic:id,name']);
        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        // LETHAL LOADING - Only essential data
        $assignment->load(['user:id,name,email', 'course:id,name', 'topic:id,name']);
        
        // Check if this is an AJAX request (modal)
        if (request()->ajax()) {
            return view('assignments.edit_form', compact('assignment'));
        }
        
        return view('assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        // LETHAL UPDATE - Enhanced validation and processing
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'duration_minutes' => 'nullable|numeric|min:0|max:1440',
            'status' => 'nullable|in:active,overdue',
        ]);

        $updateData = [];
        
        // Handle deadline updates
        if (isset($validated['deadline'])) {
            $updateData['deadline'] = $validated['deadline'];
        }
        
        // Handle duration updates
        if (isset($validated['duration_minutes'])) {
            $updateData['duration_minutes'] = $validated['duration_minutes'];
        }
        
        // Handle status updates
        if (isset($validated['status']) && !empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        
        // Handle status changes that require additional updates
        if (isset($updateData['status'])) {
            switch ($updateData['status']) {
                case Assignment::STATUS_ACTIVE:
                    // When setting to active, clear completion data
                    $updateData['completed_at'] = null;
                    $updateData['score_percentage'] = null;
                    break;
                    
                case Assignment::STATUS_COMPLETED:
                    // When marking as completed, set completion timestamp
                    $updateData['completed_at'] = now();
                    break;
            }
        }
        
        // Add updated_at timestamp
        $updateData['updated_at'] = now();
        
        // Perform the update
        if (!empty($updateData)) {
            DB::table('assignments')
                ->where('id', $assignment->id)
                ->update($updateData);
        }

        // LETHAL CACHE INVALIDATION - FIXED!
        Cache::forget("assignments_data_" . auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully!',
            'updated_fields' => array_keys($updateData)
        ]);
    }

    public function destroy(Assignment $assignment)
    {
        // LETHAL DELETE - Direct database operation
        DB::table('assignments')->where('id', $assignment->id)->delete();
        
        // LETHAL CACHE INVALIDATION - FIXED!
        Cache::forget("assignments_data_" . auth()->id());

        return response()->json(['success' => true]);
    }

    public function getTopicsForCourse($courseId)
    {
        // LETHAL CACHE - Topics with question counts
        $cacheKey = "topics_course_{$courseId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($courseId) {
            return Topic::select(['id', 'name'])
                ->where('course_id', $courseId)
                ->withCount('questions')
                ->get();
        });
    }

    public function getStudentsForCourse($courseId)
    {
        // LETHAL CACHE - Students enrolled in course
        $cacheKey = "students_course_{$courseId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($courseId) {
            return DB::table('users')
                ->select(['id', 'name', 'email'])
                ->where('role', '!=', 'admin')
                ->whereExists(function ($query) use ($courseId) {
                    $query->select(DB::raw(1))
                          ->from('course_enrollments')
                          ->whereColumn('course_enrollments.user_id', 'users.id')
                          ->where('course_enrollments.course_id', $courseId);
                })
                ->get();
        });
    }



    public function extendDeadline(Request $request, Assignment $assignment)
    {
        // LETHAL UPDATE - Direct database operation
        $validated = $request->validate([
            'new_deadline' => 'required|date|after:now',
        ]);

        DB::table('assignments')
            ->where('id', $assignment->id)
            ->update([
                'deadline' => $validated['new_deadline'],
                'status' => Assignment::STATUS_ACTIVE,
                'updated_at' => now()
            ]);

        // LETHAL CACHE INVALIDATION - FIXED!
        Cache::forget("assignments_data_" . auth()->id());

        return response()->json(['success' => true]);
    }

    public function markCompleted(Request $request, Assignment $assignment)
    {
        // LETHAL UPDATE - Direct database operation
        $validated = $request->validate([
            'score_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::table('assignments')
            ->where('id', $assignment->id)
            ->update([
                'status' => Assignment::STATUS_COMPLETED,
                'score_percentage' => $validated['score_percentage'] ?? null,
                'updated_at' => now()
            ]);

        // LETHAL CACHE INVALIDATION
        Cache::forget("assignments_data_" . auth()->id() . "_*");

        return response()->json(['success' => true]);
    }

    public function getCounterData()
    {
        // LETHAL COUNTER - Single query with aggregation
        $cacheKey = "assignment_counters_" . auth()->id();
        
        $counts = Cache::remember($cacheKey, 300, function () {
            return DB::table('assignments')
                ->where('tutor_id', auth()->id())
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue
                ')
                ->first();
        });

        return response()->json([
            'total' => $counts->total ?? 0,
            'active' => $counts->active ?? 0,
            'overdue' => $counts->overdue ?? 0
        ]);
    }

    public function updateOverdue()
    {
        // LETHAL BULK UPDATE - Single query operation
        $updated = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->where('deadline', '<', now())
            ->update([
                'status' => Assignment::STATUS_OVERDUE,
                'updated_at' => now()
            ]);

        // LETHAL CACHE INVALIDATION
        Cache::forget("assignments_data_" . auth()->id() . "_*");
        Cache::forget("assignment_counters_" . auth()->id());

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    public function enforceRealTimeStatus()
    {
        // LETHAL STATUS ENFORCEMENT - Bulk operations only
        $now = now();
        
        // Update overdue assignments
        $overdueCount = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->where('deadline', '<', $now)
            ->update(['status' => Assignment::STATUS_OVERDUE, 'updated_at' => $now]);

        // Update expired time-limited assignments
        $expiredCount = DB::table('assignments')
            ->where('status', Assignment::STATUS_ACTIVE)
            ->whereNotNull('duration_minutes')
            ->whereRaw('created_at + INTERVAL duration_minutes MINUTE < ?', [$now])
            ->update(['status' => Assignment::STATUS_EXPIRED, 'updated_at' => $now]);

        // LETHAL CACHE INVALIDATION
        Cache::forget("assignments_data_" . auth()->id() . "_*");
        Cache::forget("assignment_counters_" . auth()->id());

        return response()->json([
            'success' => true, 
            'overdue_updated' => $overdueCount,
            'expired_updated' => $expiredCount
        ]);
    }
}