<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Log};

class TutorManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tutor']);
    }

    /**
     * Display the tutor management dashboard.
     * Eager loads assignedStudents to prevent N+1 queries.
     */
    public function index()
    {
        // Eager load assignedStudents + withCount to avoid N+1 in view
        $recentTutors = User::where('role', User::ROLE_TUTOR)
            ->with(['assignedStudents:id,name,email'])
            ->withCount('assignedStudents')
            ->latest()
            ->take(10)
            ->get();

        $unassignedStudents = User::where('users.role', User::ROLE_STUDENT)
            ->whereDoesntHave('assignedTutors')
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();

        $stats = [
            'total_tutors'        => User::where('users.role', User::ROLE_TUTOR)->count(),
            'total_students'      => User::where('users.role', User::ROLE_STUDENT)->count(),
            'unassigned_students' => $unassignedStudents->count(),
            'active_relationships'=> DB::table('tutor_student_relationships')->count(),
        ];

        return view('admin.tutor-management.index', compact('recentTutors', 'unassignedStudents', 'stats'));
    }

    /**
     * Show the form to create a new tutor account.
     */
    public function createTutor()
    {
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        return view('admin.tutor-management.create-tutor', compact('courses'));
    }

    /**
     * Store a newly created tutor account.
     */
    public function storeTutor(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $tutor = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => User::ROLE_TUTOR,
            ]);

            Log::info('Tutor account created', ['tutor_id' => $tutor->id, 'by' => auth()->id()]);

            return redirect()->route('admin.tutors.index')
                ->with('success', "Tutor account for {$tutor->name} created successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to create tutor', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create tutor account. Please try again.');
        }
    }

    /**
     * Show the student-assignment form for a given tutor.
     */
    public function assignStudents(User $tutor)
    {
        abort_if($tutor->role !== User::ROLE_TUTOR, 404, 'User is not a tutor.');

        $assignedIds = $tutor->assignedStudents()->pluck('users.id');

        $availableStudents = User::where('role', User::ROLE_STUDENT)
            ->whereNotIn('id', $assignedIds)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.tutor-management.assign-students', compact('tutor', 'availableStudents'));
    }

    /**
     * Process bulk student assignment to a tutor.
     */
    public function processStudentAssignment(Request $request, User $tutor)
    {
        abort_if($tutor->role !== User::ROLE_TUTOR, 404, 'User is not a tutor.');

        $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $studentIds = $request->input('student_ids');

        // Verify all supplied IDs are actually students
        $validIds = User::whereIn('id', $studentIds)
            ->where('role', User::ROLE_STUDENT)
            ->pluck('id')
            ->toArray();

        try {
            // syncWithoutDetaching keeps existing assignments
            $tutor->assignedStudents()->syncWithoutDetaching(
                collect($validIds)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => now()]])->all()
            );

            Log::info('Students assigned to tutor', [
                'tutor_id'    => $tutor->id,
                'student_ids' => $validIds,
                'by'          => auth()->id(),
            ]);

            return redirect()->route('admin.tutors.index')
                ->with('success', count($validIds) . ' student(s) assigned to ' . $tutor->name . '.');
        } catch (\Exception $e) {
            Log::error('Student assignment failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Assignment failed. Please try again.');
        }
    }

    /**
     * Show the bulk operations page.
     */
    public function bulkOperations()
    {
        $tutors = User::where('role', User::ROLE_TUTOR)
            ->withCount('assignedStudents')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $students = User::where('role', User::ROLE_STUDENT)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.tutor-management.bulk-operations', compact('tutors', 'students'));
    }

    /**
     * Process a bulk assignment operation (many students → one tutor).
     */
    public function processBulkAssignment(Request $request)
    {
        $request->validate([
            'tutor_id'      => ['required', 'integer', 'exists:users,id'],
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $tutor = User::findOrFail($request->tutor_id);
        abort_if($tutor->role !== User::ROLE_TUTOR, 422, 'Selected user is not a tutor.');

        return $this->processStudentAssignment($request, $tutor);
    }

    /**
     * Create and assign a new course to a tutor.
     */
    public function createCourseForTutor(Request $request)
    {
        $request->validate([
            'tutor_id'    => ['required', 'integer', 'exists:users,id'],
            'course_name' => ['required', 'string', 'max:255'],
        ]);

        $tutor = User::findOrFail($request->tutor_id);
        abort_if($tutor->role !== User::ROLE_TUTOR, 422, 'Selected user is not a tutor.');

        try {
            $course = Course::create(['name' => $request->course_name]);
            $tutor->tutorCourses()->syncWithoutDetaching([$course->id => ['assigned_at' => now()]]);

            return redirect()->route('admin.tutors.index')
                ->with('success', "Course \"{$course->name}\" created and assigned to {$tutor->name}.");
        } catch (\Exception $e) {
            Log::error('Failed to create course for tutor', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to create course. Please try again.');
        }
    }

    /**
     * Return tutor details as JSON (used by the assignment modal).
     */
    public function getTutorDetails(User $tutor)
    {
        abort_if($tutor->role !== User::ROLE_TUTOR, 404, 'User is not a tutor.');

        $tutor->load(['assignedStudents:id,name,email', 'tutorCourses:id,name']);

        return response()->json([
            'id'               => $tutor->id,
            'name'             => $tutor->name,
            'email'            => $tutor->email,
            'students_count'   => $tutor->assignedStudents->count(),
            'students'         => $tutor->assignedStudents,
            'courses'          => $tutor->tutorCourses,
        ]);
    }

    /**
     * Remove a student from a tutor's assignment.
     */
    public function removeStudentFromTutor(Request $request)
    {
        $request->validate([
            'tutor_id'   => ['required', 'integer', 'exists:users,id'],
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $tutor   = User::findOrFail($request->tutor_id);
        $student = User::findOrFail($request->student_id);

        abort_if($tutor->role !== User::ROLE_TUTOR, 422, 'Selected user is not a tutor.');
        abort_if($student->role !== User::ROLE_STUDENT, 422, 'Selected user is not a student.');

        try {
            $tutor->assignedStudents()->detach($student->id);

            Log::info('Student removed from tutor', [
                'tutor_id'   => $tutor->id,
                'student_id' => $student->id,
                'by'         => auth()->id(),
            ]);

            return redirect()->back()->with('success', "{$student->name} has been removed from {$tutor->name}.");
        } catch (\Exception $e) {
            Log::error('Failed to remove student from tutor', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to remove student. Please try again.');
        }
    }
}