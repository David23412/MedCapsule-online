<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TutorReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tutor']);
    }

    /**
     * Generate accountability report for a specific student
     */
    public function generateStudentReport(Request $request, $studentId)
    {
        $user = auth()->user();

        // 1. Permission check
        if (!$user->isAdmin() && !$this->canAccessStudent($user, (int)$studentId)) {
            return redirect()->route('tutor.dashboard')->with('error', 'Unauthorized access to student report.');
        }

        // 2. Fetch all data needed for the high-fidelity report
        // We reuse the same logic as the Profile view to ensure consistency
        $data = $this->getAccountabilityMetrics((int)$studentId);

        // 3. Return printable view
        return view('tutor.report', [
            'student' => $data['student'],
            'data' => $data['analytics'],
            'timeline' => $data['timeline'],
            'discipline' => $data['discipline'],
            'planner' => $data['planner'],
            'generated_at' => now(),
            'tutor' => $user
        ]);
    }

    /**
     * Optimized data fetching for accountability reports
     */
    private function getAccountabilityMetrics(int $studentId): array
    {
        $student = User::with([
            'quizAttempts' => function ($query) {
                $query->select([
                    'quiz_attempts.id', 'quiz_attempts.user_id', 'quiz_attempts.topic_id', 
                    'quiz_attempts.percentage_grade', 'quiz_attempts.created_at',
                    'quiz_attempts.duration_seconds', 'quiz_attempts.total_questions', 
                    'quiz_attempts.score', 'quiz_attempts.study_mode'
                ])
                ->with('topic:id,name')
                ->orderBy('quiz_attempts.created_at', 'desc')
                ->take(100);
            },
            'courses:id,name'
        ])
        ->select([
            'users.id', 'users.name', 'users.email', 'users.number', 'users.university', 
            'users.created_at', 'users.last_active_at', 'users.total_questions_attempted', 
            'users.correct_answers_count', 'users.study_streak_days', 'users.xp'
        ])
        ->findOrFail($studentId);

        $videoProgress = DB::table('video_user_progress')
            ->where('video_user_progress.user_id', $studentId)
            ->get(['video_user_progress.topic_id', 'video_user_progress.percentage', 'video_user_progress.last_watched_at'])
            ->keyBy('topic_id');

        $plannerOutcomes = DB::table('planner_task_outcomes')
            ->where('planner_task_outcomes.user_id', $studentId)
            ->where('planner_task_outcomes.plan_date', '>=', now()->subDays(14))
            ->orderBy('planner_task_outcomes.plan_date', 'desc')
            ->get();

        // Use calculations from TutorDashboardViewController context
        $discipline = $this->calculateDisciplineStatus($student, $plannerOutcomes);
        $timeline = $this->buildTopicTimeline($student->quizAttempts, $videoProgress);
        
        // Basic analytics summary
        $avgScore = $student->quizAttempts->count() > 0 ? round($student->quizAttempts->avg('percentage_grade'), 1) : 0;
        
        return [
            'student' => $student,
            'timeline' => $timeline,
            'discipline' => $discipline,
            'planner' => [
                'completion_rate' => $plannerOutcomes->count() > 0 
                    ? round(($plannerOutcomes->where('is_completed', true)->count() / $plannerOutcomes->count()) * 100, 1) 
                    : 100
            ],
            'analytics' => [
                'average_score' => $avgScore,
                'accuracy_rate' => $student->total_questions_attempted > 0 
                    ? round(($student->correct_answers_count / $student->total_questions_attempted) * 100, 1) : 0,
                'total_quizzes' => $student->quizAttempts->count(),
                'study_streak' => $student->study_streak_days,
            ]
        ];
    }

    private function calculateDisciplineStatus($student, $plannerOutcomes): array
    {
        $lastActive = $student->last_active_at;
        $daysSinceActive = $lastActive ? now()->diffInDays($lastActive) : 99;
        
        $recentTasks = $plannerOutcomes->where('plan_date', '>=', now()->subDays(3));
        $missingTasks = $recentTasks->where('is_completed', false)->count();
        $totalTasks = $recentTasks->count();
        
        $status = 'active';
        $reason = 'Student is following the plan.';
        if ($daysSinceActive >= 3 || ($totalTasks > 0 && ($missingTasks / $totalTasks) > 0.5)) {
            $status = 'inactive';
            $reason = $daysSinceActive >= 3 ? 'No activity for 3+ days.' : 'Most recent tasks missed.';
        } elseif ($daysSinceActive >= 1 || $missingTasks > 0) {
            $status = 'at_risk';
            $reason = $daysSinceActive >= 1 ? 'No activity in 24h.' : 'Some tasks missed.';
        }

        return ['status' => $status, 'reason' => $reason];
    }

    private function buildTopicTimeline($quizAttempts, $videoProgress): array
    {
        $topics = [];
        foreach ($quizAttempts as $attempt) {
            $topicId = $attempt->topic_id;
            if (!isset($topics[$topicId])) {
                $topics[$topicId] = [
                    'name' => $attempt->topic->name ?? 'Unknown',
                    'quiz_score' => $attempt->percentage_grade,
                    'quiz_mode' => $attempt->study_mode ?: 'quiz',
                    'video_percentage' => 0,
                    'last_activity' => $attempt->created_at
                ];
            } else if ($attempt->percentage_grade > $topics[$topicId]['quiz_score']) {
                $topics[$topicId]['quiz_score'] = $attempt->percentage_grade;
            }
        }
        foreach ($videoProgress as $topicId => $progress) {
            if (!isset($topics[$topicId])) {
                $topics[$topicId] = [
                    'name' => \App\Models\Topic::where('id', $topicId)->value('name') ?? 'Unknown',
                    'quiz_score' => null,
                    'quiz_mode' => null,
                    'video_percentage' => $progress->percentage,
                    'last_activity' => $progress->last_watched_at
                ];
            } else {
                $topics[$topicId]['video_percentage'] = $progress->percentage;
            }
        }
        return collect($topics)->sortByDesc('last_activity')->values()->toArray();
    }

    private function canAccessStudent(User $tutor, int $studentId): bool
    {
        if ($tutor->isAdmin()) return true;

        return DB::table('tutor_student_relationships')
            ->where('tutor_student_relationships.tutor_id', $tutor->id)
            ->where('tutor_student_relationships.user_id', $studentId)
            ->exists();
    }

    /**
     * CSV Export (Legacy support but kept for tutors)
     */
    public function exportAllReports(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            $studentIds = DB::table('tutor_student_relationships')
                ->where('tutor_student_relationships.tutor_id', $user->id)
                ->pluck('tutor_student_relationships.user_id');
            $students = User::whereIn('users.id', $studentIds)->get();
        } else {
            $students = User::where('users.role', User::ROLE_STUDENT)->get();
        }

        $csv = "Name,Email,Quiz Avg,Total Quizzes,Last Active\n";
        foreach ($students as $student) {
            $avg = round($student->quizAttempts()->avg('percentage_grade') ?? 0, 1);
            $csv .= "\"{$student->name}\",\"{$student->email}\",{$avg},{$student->quizAttempts()->count()},\"{$student->last_active_at}\"\n";
        }
        
        return response($csv)->header('Content-Type', 'text/csv')->header('Content-Disposition', "attachment; filename=reports.csv");
    }
}