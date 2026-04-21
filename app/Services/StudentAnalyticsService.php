<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAnalyticsService
{
    /**
     * Calculate Discipline Status (Green/Yellow/Red) based on activity and tasks.
     */
    public function calculateDisciplineStatus(User $student, Collection $plannerOutcomes): array
    {
        $lastActive = $student->last_active_at;
        $daysSinceActive = $lastActive ? now()->diffInDays($lastActive) : 99;
        
        $recentTasks = $plannerOutcomes->where('plan_date', '>=', now()->subDays(3));
        $missingTasks = $recentTasks->where('is_completed', false)->count();
        $totalTasks = $recentTasks->count();
        
        $status = 'active';
        $reason = 'Student is following the plan.';
        $color = 'success';

        if ($daysSinceActive >= 3 || ($totalTasks > 0 && ($missingTasks / $totalTasks) > 0.5)) {
            $status = 'inactive';
            $reason = $daysSinceActive >= 3 ? 'No activity for 3+ days.' : 'More than 50% of recent tasks missed.';
            $color = 'danger';
        } elseif ($daysSinceActive >= 1 || $missingTasks > 0) {
            $status = 'at_risk';
            $reason = $daysSinceActive >= 1 ? 'No activity in 24h.' : 'Some tasks were missed recently.';
            $color = 'warning';
        }

        // Prepare WhatsApp message
        $waMessage = "Hi {$student->name}, this is your mentor from MedCapsule. I noticed that [REASON]. Is everything okay? How can I help you get back to your study schedule?";
        $waMessage = str_replace('[REASON]', str_replace('.', '', strtolower($reason)), $waMessage);
        
        return [
            'status' => $status,
            'reason' => $reason,
            'color' => $color,
            'whatsapp_link' => "https://wa.me/" . preg_replace('/[^0-9]/', '', $student->number) . "?text=" . urlencode($waMessage)
        ];
    }

    /**
     * Determine performance trend (improving/declining/stable).
     */
    public function calculatePerformanceTrend(Collection $recentAttempts): string
    {
        if ($recentAttempts->count() < 4) return 'stable';

        $sorted = $recentAttempts->sortBy('created_at')->values();
        $mid = floor($sorted->count() / 2);
        
        $olderAvg = $sorted->take($mid)->avg('percentage_grade') ?? 0;
        $newerAvg = $sorted->skip($mid)->avg('percentage_grade') ?? 0;

        if ($newerAvg > $olderAvg + 5) return 'improving';
        if ($newerAvg < $olderAvg - 5) return 'declining';
        
        return 'stable';
    }

    /**
     * Enhance a student model with performance metrics.
     */
    public function enhanceStudentMetrics(User $student, Collection $plannerOutcomes): User
    {
        $recentQuizzes = $student->quizAttempts->take(10);
        $avgScore = $recentQuizzes->avg('percentage_grade') ?? 0;
        
        // Topic Coverage
        $attemptedTopicIds = $student->quizAttempts->pluck('topic_id')->unique()->count();
        $totalTopics = Cache::remember('total_topics_count', 3600, function() {
            return DB::table('topics')->count();
        });

        // Task Progress (Last 7 days)
        $completedTasks = $plannerOutcomes->where('is_completed', true)->count();
        $totalTasks = $plannerOutcomes->count();
        $taskProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 100;

        $student->enhanced_metrics = [
            'average_score' => round($avgScore, 1),
            'total_quizzes' => $student->quizAttempts->count(),
            'performance_trend' => $this->calculatePerformanceTrend($recentQuizzes),
            'score_class' => $avgScore >= 80 ? 'excellent' : ($avgScore >= 65 ? 'good' : 'poor'),
            'discipline' => $this->calculateDisciplineStatus($student, $plannerOutcomes),
            'task_progress' => $taskProgress,
            'topic_coverage' => [
                'attempted' => $attemptedTopicIds,
                'total' => $totalTopics,
                'percentage' => $totalTopics > 0 ? round(($attemptedTopicIds / $totalTopics) * 100) : 0
            ]
        ];
        
        return $student;
    }

    /**
     * Get summary analytics for a group of enhanced students.
     */
    public function getSummaryAnalytics(Collection $students): array
    {
        $total = $students->count();
        if ($total === 0) {
            return [
                'total_students' => 0,
                'active_students' => 0,
                'average_score' => 0,
                'struggling_students' => 0
            ];
        }

        $active = $students->filter(fn($s) => $s->last_active_at && $s->last_active_at->gte(now()->subDays(3)))->count();
        $avgScore = $students->avg('enhanced_metrics.average_score') ?? 0;
        $struggling = $students->filter(fn($s) => 
            $s->enhanced_metrics['score_class'] === 'poor' || 
            $s->enhanced_metrics['discipline']['status'] === 'inactive'
        )->count();

        $avgTaskProgress = $students->avg('enhanced_metrics.task_progress') ?? 0;

        return [
            'total_students' => $total,
            'active_students' => $active,
            'average_score' => round($avgScore, 1),
            'struggling_students' => $struggling,
            'average_task_progress' => round($avgTaskProgress, 1)
        ];
    }

    /**
     * Get the most active day of the week from recent attempts.
     */
    public function getMostActiveDay(Collection $recentAttempts): string
    {
        if ($recentAttempts->isEmpty()) return 'N/A';

        return $recentAttempts->groupBy(fn($a) => $a->created_at->format('l'))
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first() ?? 'N/A';
    }

    /**
     * Calculate improvement rate from quiz attempts.
     * Compares average of recent half vs older half of attempts.
     */
    public function calculateImprovementRate(Collection $quizAttempts): float
    {
        if ($quizAttempts->count() < 4) {
            return 0;
        }

        $sorted = $quizAttempts->sortBy('created_at')->values();
        $midpoint = (int) floor($sorted->count() / 2);

        $olderAvg = $sorted->take($midpoint)->avg('percentage_grade') ?: 0;
        $recentAvg = $sorted->skip($midpoint)->avg('percentage_grade') ?: 0;

        return round($recentAvg - $olderAvg, 1);
    }

    /**
     * Get strength areas (topics with highest average scores).
     */
    public function getStrengthAreas(Collection $quizAttempts): array
    {
        if ($quizAttempts->isEmpty()) {
            return [];
        }

        return $quizAttempts->groupBy(fn($a) => $a->topic->name ?? 'Unknown')
            ->map(fn($attempts) => round($attempts->avg('percentage_grade'), 1))
            ->sortDesc()
            ->take(3)
            ->map(fn($avg, $topic) => ['topic' => $topic, 'average_score' => $avg])
            ->values()
            ->toArray();
    }

    /**
     * Get weakness areas (topics with lowest average scores, min 2 attempts).
     */
    public function getWeaknessAreas(Collection $quizAttempts): array
    {
        if ($quizAttempts->isEmpty()) {
            return [];
        }

        return $quizAttempts->groupBy(fn($a) => $a->topic->name ?? 'Unknown')
            ->filter(fn($attempts) => $attempts->count() >= 2)
            ->map(fn($attempts) => round($attempts->avg('percentage_grade'), 1))
            ->sort()
            ->take(3)
            ->map(fn($avg, $topic) => ['topic' => $topic, 'average_score' => $avg])
            ->values()
            ->toArray();
    }
}
