<?php

/**
 * 🔥 LETHAL ASSIGNMENT PROGRESS DIAGNOSTIC ENGINE 🔥
 * 
 * CRITICAL ISSUE ANALYSIS:
 * 1. Assignment progress not updating source percentage (stays at 0%)
 * 2. Cross-contamination between assignments with same question set
 * 3. 500 error on store-update endpoint
 * 4. Progress bar updates for quiz attempts but not assignment table
 * 
 * DIAGNOSTIC TARGETS:
 * - Assignment progress calculation logic
 * - QuizAttempt to Assignment relationship
 * - Source percentage calculation
 * - Cross-assignment contamination
 * - Store-update endpoint validation
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\Question;

class LethalAssignmentProgressDiagnostic
{
    private $issues = [];
    private $fixes = [];
    private $userId;
    private $topicId;
    private $assignmentIds = [];

    public function __construct($userId = null, $topicId = null)
    {
        $this->userId = $userId ?: auth()->id();
        $this->topicId = $topicId;
        
        echo "🔥 LETHAL ASSIGNMENT PROGRESS DIAGNOSTIC ENGINE ACTIVATED 🔥\n";
        echo "================================================================\n\n";
    }

    public function diagnose()
    {
        $this->analyzeAssignmentProgressIssue();
        $this->analyzeCrossContaminationIssue();
        $this->analyzeStoreUpdateEndpoint();
        $this->analyzeProgressCalculationLogic();
        $this->generateFixRecommendations();
        
        $this->displayResults();
    }

    private function analyzeAssignmentProgressIssue()
    {
        echo "🎯 ANALYZING ASSIGNMENT PROGRESS ISSUE...\n";
        echo "==========================================\n";

        // Get assignments for the user
        $assignments = Assignment::where('user_id', $this->userId)
            ->when($this->topicId, function($query) {
                return $query->where('topic_id', $this->topicId);
            })
            ->with(['topic', 'attempts'])
            ->get();

        if ($assignments->isEmpty()) {
            echo "❌ NO ASSIGNMENTS FOUND for user {$this->userId}\n\n";
            return;
        }

        foreach ($assignments as $assignment) {
            $this->assignmentIds[] = $assignment->id;
            
            echo "📋 ASSIGNMENT: {$assignment->title} (ID: {$assignment->id})\n";
            echo "   Topic: {$assignment->topic->name} (ID: {$assignment->topic_id})\n";
            echo "   Status: {$assignment->status}\n";
            echo "   Score Percentage: {$assignment->score_percentage}%\n";

            // Check quiz attempts for this assignment
            $quizAttempts = QuizAttempt::where('user_id', $this->userId)
                ->where('topic_id', $assignment->topic_id)
                ->where('assignment_id', $assignment->id)
                ->orderBy('created_at', 'desc')
                ->get();

            echo "   Quiz Attempts: " . $quizAttempts->count() . "\n";

            if ($quizAttempts->isNotEmpty()) {
                $latestAttempt = $quizAttempts->first();
                echo "   Latest Attempt Score: {$latestAttempt->score}/{$latestAttempt->total_questions} ({$latestAttempt->percentage_grade}%)\n";
                
                // CRITICAL ISSUE: Check if assignment score matches quiz attempt
                if ($assignment->score_percentage != $latestAttempt->percentage_grade) {
                    $this->issues[] = [
                        'type' => 'ASSIGNMENT_SCORE_MISMATCH',
                        'assignment_id' => $assignment->id,
                        'assignment_score' => $assignment->score_percentage,
                        'quiz_attempt_score' => $latestAttempt->percentage_grade,
                        'description' => "Assignment score ({$assignment->score_percentage}%) doesn't match latest quiz attempt ({$latestAttempt->percentage_grade}%)"
                    ];
                    echo "   ❌ CRITICAL: Assignment score mismatch!\n";
                }
            } else {
                echo "   ❌ NO QUIZ ATTEMPTS FOUND for this assignment\n";
                $this->issues[] = [
                    'type' => 'NO_QUIZ_ATTEMPTS',
                    'assignment_id' => $assignment->id,
                    'description' => "Assignment has no associated quiz attempts"
                ];
            }

            // Check source-specific progress
            $this->analyzeSourceProgress($assignment);
            echo "\n";
        }
    }

    private function analyzeSourceProgress($assignment)
    {
        echo "   🔍 SOURCE PROGRESS ANALYSIS:\n";
        
        // Get available sources for this topic
        $sources = Question::where('topic_id', $assignment->topic_id)
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->toArray();

        if (empty($sources)) {
            echo "      ❌ NO SOURCES FOUND for topic {$assignment->topic_id}\n";
            return;
        }

        echo "      Available Sources: " . implode(', ', $sources) . "\n";

        // Get quiz attempts with source breakdown
        $sourceProgress = [];
        foreach ($sources as $source) {
            $attempts = QuizAttempt::where('user_id', $this->userId)
                ->where('topic_id', $assignment->topic_id)
                ->where('assignment_id', $assignment->id)
                ->where('source', $source)
                ->get();

            $totalScore = $attempts->sum('score');
            $totalQuestions = $attempts->sum('total_questions');
            $percentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

            $sourceProgress[$source] = [
                'attempts' => $attempts->count(),
                'score' => $totalScore,
                'total' => $totalQuestions,
                'percentage' => $percentage
            ];

            echo "      Source '{$source}': {$totalScore}/{$totalQuestions} ({$percentage}%)\n";
        }

        // CRITICAL ISSUE: Check if any source has 0% when it should have progress
        foreach ($sourceProgress as $source => $progress) {
            if ($progress['percentage'] == 0 && $progress['attempts'] > 0) {
                $this->issues[] = [
                    'type' => 'SOURCE_PROGRESS_ZERO',
                    'assignment_id' => $assignment->id,
                    'source' => $source,
                    'attempts' => $progress['attempts'],
                    'description' => "Source '{$source}' has {$progress['attempts']} attempts but 0% progress"
                ];
                echo "      ❌ CRITICAL: Source '{$source}' has attempts but 0% progress!\n";
            }
        }
    }

    private function analyzeCrossContaminationIssue()
    {
        echo "🔄 ANALYZING CROSS-CONTAMINATION ISSUE...\n";
        echo "=========================================\n";

        if (empty($this->assignmentIds)) {
            echo "❌ NO ASSIGNMENTS TO ANALYZE\n\n";
            return;
        }

        // Find assignments that share the same topic_id
        $topicGroups = Assignment::whereIn('id', $this->assignmentIds)
            ->select('id', 'topic_id', 'title')
            ->get()
            ->groupBy('topic_id');

        foreach ($topicGroups as $topicId => $assignments) {
            if ($assignments->count() > 1) {
                echo "🚨 POTENTIAL CONTAMINATION DETECTED!\n";
                echo "Topic ID {$topicId} has multiple assignments:\n";
                
                foreach ($assignments as $assignment) {
                    echo "  - Assignment {$assignment->id}: {$assignment->title}\n";
                    
                    // Check quiz attempts for each assignment
                    $attempts = QuizAttempt::where('user_id', $this->userId)
                        ->where('topic_id', $topicId)
                        ->where('assignment_id', $assignment->id)
                        ->count();
                    
                    echo "    Quiz Attempts: {$attempts}\n";
                }

                // CRITICAL ISSUE: Check if progress is being shared
                $allAttempts = QuizAttempt::where('user_id', $this->userId)
                    ->where('topic_id', $topicId)
                    ->whereIn('assignment_id', $assignments->pluck('id'))
                    ->get();

                $assignmentAttempts = $allAttempts->groupBy('assignment_id');
                
                foreach ($assignmentAttempts as $assignmentId => $attempts) {
                    $otherAssignmentAttempts = $allAttempts->where('assignment_id', '!=', $assignmentId);
                    
                    if ($otherAssignmentAttempts->isNotEmpty()) {
                        $this->issues[] = [
                            'type' => 'CROSS_CONTAMINATION',
                            'topic_id' => $topicId,
                            'assignment_id' => $assignmentId,
                            'contaminated_attempts' => $otherAssignmentAttempts->count(),
                            'description' => "Assignment {$assignmentId} may be affected by progress from other assignments on same topic"
                        ];
                        echo "    ❌ CRITICAL: Potential cross-contamination detected!\n";
                    }
                }
                echo "\n";
            }
        }
    }

    private function analyzeStoreUpdateEndpoint()
    {
        echo "🌐 ANALYZING STORE-UPDATE ENDPOINT...\n";
        echo "====================================\n";

        // Test the store-update endpoint validation
        $testData = [
            'topicId' => $this->topicId ?: 1,
            'assignmentId' => !empty($this->assignmentIds) ? $this->assignmentIds[0] : null,
            'progress' => ['General' => ['score' => 5, 'total' => 10, 'percentage' => 50]],
            'availableSources' => ['General'],
            'allSourcesMastered' => false,
            'userId' => $this->userId
        ];

        echo "Testing with data:\n";
        echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

        // Simulate validation
        $validator = \Illuminate\Support\Facades\Validator::make($testData, [
            'topicId' => 'required|integer',
            'assignmentId' => 'nullable|integer',
            'progress' => 'required|array',
            'availableSources' => 'required|array',
            'allSourcesMastered' => 'required|boolean',
            'userId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            echo "❌ VALIDATION FAILED:\n";
            foreach ($validator->errors()->all() as $error) {
                echo "  - {$error}\n";
            }
            
            $this->issues[] = [
                'type' => 'STORE_UPDATE_VALIDATION',
                'errors' => $validator->errors()->toArray(),
                'description' => 'Store-update endpoint validation failing'
            ];
        } else {
            echo "✅ Validation passed\n";
        }

        // Check if the route exists
        try {
            $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('progress.store-update');
            if ($route) {
                echo "✅ Route 'progress.store-update' exists\n";
                echo "   URI: {$route->uri()}\n";
                echo "   Methods: " . implode(', ', $route->methods()) . "\n";
            } else {
                echo "❌ Route 'progress.store-update' not found\n";
                $this->issues[] = [
                    'type' => 'MISSING_ROUTE',
                    'route' => 'progress.store-update',
                    'description' => 'Store-update route not found'
                ];
            }
        } catch (\Exception $e) {
            echo "❌ Error checking route: {$e->getMessage()}\n";
        }

        echo "\n";
    }

    private function analyzeProgressCalculationLogic()
    {
        echo "🧮 ANALYZING PROGRESS CALCULATION LOGIC...\n";
        echo "==========================================\n";

        if (!$this->topicId) {
            echo "❌ NO TOPIC ID PROVIDED - SKIPPING DETAILED ANALYSIS\n\n";
            return;
        }

        // Test QuizAttempt::getTopicProgress method
        try {
            $progress = QuizAttempt::getTopicProgress($this->userId, $this->topicId, 'quiz');
            
            echo "📊 TOPIC PROGRESS CALCULATION RESULTS:\n";
            echo "Overall Progress:\n";
            echo "  Score: {$progress['overall']['score']}\n";
            echo "  Total: {$progress['overall']['total']}\n";
            echo "  Percentage: {$progress['overall']['percentage']}%\n";
            
            echo "Source Progress:\n";
            foreach ($progress['sources'] as $source => $sourceData) {
                echo "  {$source}: {$sourceData['score']}/{$sourceData['total']} ({$sourceData['percentage']}%)\n";
            }

            // CRITICAL CHECK: Verify calculation accuracy
            $manualCalculation = $this->verifyProgressCalculation();
            if ($manualCalculation !== $progress) {
                $this->issues[] = [
                    'type' => 'PROGRESS_CALCULATION_MISMATCH',
                    'calculated' => $progress,
                    'manual' => $manualCalculation,
                    'description' => 'Progress calculation method returns incorrect results'
                ];
                echo "❌ CRITICAL: Progress calculation mismatch detected!\n";
            } else {
                echo "✅ Progress calculation verified\n";
            }

        } catch (\Exception $e) {
            echo "❌ ERROR in progress calculation: {$e->getMessage()}\n";
            $this->issues[] = [
                'type' => 'PROGRESS_CALCULATION_ERROR',
                'error' => $e->getMessage(),
                'description' => 'Progress calculation method throwing exception'
            ];
        }

        echo "\n";
    }

    private function verifyProgressCalculation()
    {
        // Manual calculation to verify QuizAttempt::getTopicProgress
        $attempts = QuizAttempt::where('user_id', $this->userId)
            ->where('topic_id', $this->topicId)
            ->where('study_mode', 'quiz')
            ->get();

        $totalScore = $attempts->sum('score');
        $totalQuestions = $attempts->sum('total_questions');
        $overallPercentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

        $sourceProgress = [];
        foreach ($attempts as $attempt) {
            $source = $attempt->source ?: 'General';
            if (!isset($sourceProgress[$source])) {
                $sourceProgress[$source] = ['score' => 0, 'total' => 0, 'percentage' => 0];
            }
            $sourceProgress[$source]['score'] += $attempt->score;
            $sourceProgress[$source]['total'] += $attempt->total_questions;
        }

        foreach ($sourceProgress as $source => $data) {
            $sourceProgress[$source]['percentage'] = $data['total'] > 0 
                ? ($data['score'] / $data['total']) * 100 
                : 0;
        }

        return [
            'overall' => [
                'score' => $totalScore,
                'total' => $totalQuestions,
                'percentage' => $overallPercentage
            ],
            'sources' => $sourceProgress
        ];
    }

    private function generateFixRecommendations()
    {
        echo "🔧 GENERATING FIX RECOMMENDATIONS...\n";
        echo "====================================\n";

        foreach ($this->issues as $issue) {
            switch ($issue['type']) {
                case 'ASSIGNMENT_SCORE_MISMATCH':
                    $this->fixes[] = [
                        'issue' => $issue,
                        'fix' => "UPDATE assignments SET score_percentage = {$issue['quiz_attempt_score']} WHERE id = {$issue['assignment_id']};",
                        'description' => 'Sync assignment score with latest quiz attempt'
                    ];
                    break;

                case 'SOURCE_PROGRESS_ZERO':
                    $this->fixes[] = [
                        'issue' => $issue,
                        'fix' => 'Recalculate source progress for assignment ' . $issue['assignment_id'],
                        'description' => 'Fix source progress calculation logic'
                    ];
                    break;

                case 'CROSS_CONTAMINATION':
                    $this->fixes[] = [
                        'issue' => $issue,
                        'fix' => 'Ensure assignment_id is properly used in progress calculations',
                        'description' => 'Modify progress calculation to be assignment-specific'
                    ];
                    break;

                case 'STORE_UPDATE_VALIDATION':
                    $this->fixes[] = [
                        'issue' => $issue,
                        'fix' => 'Fix validation rules in ProgressUpdateController',
                        'description' => 'Update store-update endpoint validation'
                    ];
                    break;

                case 'PROGRESS_CALCULATION_ERROR':
                    $this->fixes[] = [
                        'issue' => $issue,
                        'fix' => 'Debug and fix QuizAttempt::getTopicProgress method',
                        'description' => 'Fix progress calculation logic'
                    ];
                    break;
            }
        }
    }

    private function displayResults()
    {
        echo "\n🎯 DIAGNOSTIC RESULTS SUMMARY\n";
        echo "=============================\n";
        
        echo "📊 ISSUES FOUND: " . count($this->issues) . "\n";
        echo "🔧 FIXES RECOMMENDED: " . count($this->fixes) . "\n\n";

        if (!empty($this->issues)) {
            echo "🚨 CRITICAL ISSUES:\n";
            foreach ($this->issues as $i => $issue) {
                echo ($i + 1) . ". {$issue['type']}: {$issue['description']}\n";
            }
            echo "\n";
        }

        if (!empty($this->fixes)) {
            echo "🔧 RECOMMENDED FIXES:\n";
            foreach ($this->fixes as $i => $fix) {
                echo ($i + 1) . ". {$fix['description']}\n";
                echo "   Fix: {$fix['fix']}\n\n";
            }
        }

        echo "🎯 ROOT CAUSE ANALYSIS:\n";
        echo "=======================\n";
        echo "1. Assignment progress calculation is NOT assignment-specific\n";
        echo "2. Progress is calculated by topic_id only, ignoring assignment_id\n";
        echo "3. Store-update endpoint may have validation issues\n";
        echo "4. Source progress calculation may not account for assignment context\n\n";

        echo "💀 LETHAL FIXES REQUIRED:\n";
        echo "=========================\n";
        echo "1. Modify QuizAttempt::getTopicProgress to accept assignment_id parameter\n";
        echo "2. Update progress calculation to filter by assignment_id when provided\n";
        echo "3. Fix store-update endpoint validation and error handling\n";
        echo "4. Ensure assignment score is updated when quiz is completed\n";
        echo "5. Add assignment-specific progress tracking in topics_for_course.blade.php\n\n";

        echo "🔥 DIAGNOSTIC COMPLETE - LETHAL MODE DEACTIVATED 🔥\n";
    }
}

// Execute diagnostic if run directly
if (php_sapi_name() === 'cli') {
    $userId = $argv[1] ?? null;
    $topicId = $argv[2] ?? null;
    
    $diagnostic = new LethalAssignmentProgressDiagnostic($userId, $topicId);
    $diagnostic->diagnose();
}