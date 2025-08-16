<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TutorReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tutor']);
    }

    /**
     * Generate grade report for a specific student
     */
    public function generateStudentReport(Request $request, $studentId): JsonResponse
    {
        $user = auth()->user();
        // LETHAL EAGER LOADING: Load student with all related report data
        $student = User::select('id', 'name', 'email', 'created_at')
            ->with([
                'quizAttempts:id,user_id,topic_id,score,created_at,completed_at',
                'quizAttempts.topic:id,name,course_id',
                'quizAttempts.topic.course:id,name',
                'assignments:id,user_id,topic_id,status,score,created_at,deadline',
                'assignments.topic:id,name,course_id',
                'assignments.topic.course:id,name',
                'mistakes:id,user_id,question_id,mastered,created_at',
                'mistakes.question:id,topic_id,difficulty',
                'mistakes.question.topic:id,name,course_id'
            ])
            ->findOrFail($studentId);

        if (!$user->isAdmin() && !$this->canAccessStudent($user, $student)) {
            abort(403, 'You do not have permission to generate reports for this student.');
        }

        try {
            $reportData = Cache::remember("student_report_{$studentId}", 600, function() use ($student) {
                return $this->generateGradeReportData($student);
            });
            
            return response()->json([
                'success' => true,
                'student_name' => $student->name,
                'data' => $reportData
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating student report:', [
                'tutor_id' => $user->id,
                'user_id' => $studentId,
                'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to generate report for this student.'
            ], 500);
        }
    }

    /**
     * Export grade reports for all assigned students
     */
    public function exportAllReports(Request $request)
    {
        $user = auth()->user();
        
        try {
            if ($user->isAdmin()) {
                $students = User::where('role', User::ROLE_STUDENT)
                    ->with(['quizAttempts' => function($query) {
                        $query->select('user_id', 'score');
                    }])
                    ->get();
            } else {
                // Get students enrolled in tutor's courses
                $tutorCourseIds = $user->courses()->pluck('courses.id');
                $students = User::where('role', User::ROLE_STUDENT)
                    ->whereHas('courses', function ($q) use ($tutorCourseIds) {
                        $q->whereIn('courses.id', $tutorCourseIds)
                          ->where('course_user.is_enrolled', true);
                    })
                    ->with(['quizAttempts' => function($query) {
                        $query->select('user_id', 'score');
                    }])
                    ->get();
            }

            $csvData = $this->generateStudentsCSV($students);
            
            $filename = 'grade_reports_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('Error exporting all reports:', [
                'tutor_id' => $user->id,
                'error' => $e->getMessage()]);
            
            return redirect()->route('tutor.dashboard')
                ->with('error', 'Unable to export reports at this time.');
        }
    }

    /**
     * Check if tutor can access specific student
     * Tutors can only access students enrolled in their courses
     */
    private function canAccessStudent(User $tutor, User $student): bool
    {
        if ($tutor->isAdmin()) {
            return true;
        }
        
        // Get tutor's course IDs
        $tutorCourseIds = $tutor->courses()->pluck('courses.id');
        
        // Check if student is enrolled in any of the tutor's courses
        return $student->courses()
            ->whereIn('courses.id', $tutorCourseIds)
            ->where('course_user.is_enrolled', true)
            ->exists();
    }

    /**
     * Generate grade report data for a student - 🚀 CRITICAL N+1 FIX
     */
    private function generateGradeReportData(User $student)
    {
        // 🚀 CRITICAL FIX: Use already loaded relationships from eager loading above
        // No additional database queries needed since relationships are pre-loaded
        $quizAttempts = $student->quizAttempts;

        // SSOT: Get progress data from UserProgressService
        $userProgressService = app(\App\Support\State\UserProgressService::class);
        $progress = $userProgressService->getUserProgress($student->id);

        return [
            'student_name' => $student->name,
            'student_email' => $student->email,
            'university' => $student->university ?? 'Not specified',
            'total_quizzes' => $quizAttempts->count(),
            'average_score' => round($quizAttempts->avg('score') ?? 0, 1),
            'best_score' => $quizAttempts->max('score') ?? 0,
            'study_streak' => $progress['study_streak_days'],
            'total_xp' => $progress['xp'],
            'last_active' => $student->last_active_at?->format('Y-m-d H:i:s') ?? 'Never',
            'quiz_history' => $quizAttempts->take(10)->map(function ($attempt) {
                return [
                    'topic' => $attempt->topic->name ?? 'Unknown Topic',
                    'score' => $attempt->score,
                    'date' => $attempt->created_at->format('Y-m-d H:i:s')];
            })->toArray()];
    }

    /**
     * Generate CSV for students - 🚀 CRITICAL N+1 FIX
     */
    private function generateStudentsCSV($students)
    {
        $csv = "Student Name,Email,University,Quiz Average,Total Quizzes,Study Streak,Last Active\n";

        // 🚀 CRITICAL FIX: Relationships already loaded via eager loading above
        $userProgressService = app(\App\Support\State\UserProgressService::class);
        
        foreach ($students as $student) {
            // Use already loaded quizAttempts - no N+1 queries
            $quizAttempts = $student->quizAttempts;
            $avgScore = $quizAttempts->avg('score') ?? 0;
            $quizCount = $quizAttempts->count();
            $lastActive = $student->last_active_at ? $student->last_active_at->format('Y-m-d H:i:s') : 'Never';
            
            // SSOT: Get study streak from UserProgressService
            $progress = $userProgressService->getUserProgress($student->id);
            
            $csv .= "\"{$student->name}\",\"{$student->email}\",\"{$student->university}\"," . 
                    number_format($avgScore, 1) . ",{$quizCount},{$progress['study_streak_days']},\"{$lastActive}\"\n";
        }

        return $csv;
    }
}