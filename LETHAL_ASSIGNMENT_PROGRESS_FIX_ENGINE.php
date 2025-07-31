<?php

/**
 * 🔥 LETHAL ASSIGNMENT PROGRESS FIX ENGINE 🔥
 * 
 * CRITICAL FIXES:
 * 1. Assignment-specific progress calculation
 * 2. Fix cross-contamination between assignments
 * 3. Fix store-update endpoint validation
 * 4. Sync assignment scores with quiz attempts
 * 5. Update progress display logic
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Assignment;
use App\Models\QuizAttempt;

class LethalAssignmentProgressFix
{
    public function __construct()
    {
        echo "🔥 LETHAL ASSIGNMENT PROGRESS FIX ENGINE ACTIVATED 🔥\n";
        echo "=====================================================\n\n";
    }

    public function executeAllFixes()
    {
        $this->fixQuizAttemptProgressCalculation();
        $this->fixAssignmentScoreSync();
        $this->fixStoreUpdateEndpoint();
        $this->fixProgressUpdateController();
        $this->fixTopicsBladeFile();
        
        echo "🎯 ALL FIXES APPLIED SUCCESSFULLY!\n";
        echo "==================================\n";
    }

    private function fixQuizAttemptProgressCalculation()
    {
        echo "🎯 FIXING QUIZ ATTEMPT PROGRESS CALCULATION...\n";
        echo "==============================================\n";

        // Update QuizAttempt model to support assignment-specific progress
        $quizAttemptModelPath = 'app/Models/QuizAttempt.php';
        
        // Read current content
        $content = file_get_contents($quizAttemptModelPath);
        
        // Add new method for assignment-specific progress
        $newMethod = '
    /**
     * Get assignment-specific topic progress for a user with error handling
     */
    public static function getAssignmentTopicProgress($userId, $topicId, $assignmentId = null, $studyMode = \'assignment\')
    {
        try {
            $query = self::where(\'user_id\', $userId)
                ->where(\'topic_id\', $topicId)
                ->where(\'study_mode\', $studyMode);
            
            // CRITICAL FIX: Filter by assignment_id if provided
            if ($assignmentId) {
                $query->where(\'assignment_id\', $assignmentId);
            }
            
            $attempts = $query->get();

            if ($attempts->isEmpty()) {
                return [
                    \'overall\' => [
                        \'score\' => 0,
                        \'total\' => 0,
                        \'percentage\' => 0
                    ],
                    \'sources\' => []
                ];
            }

            // Get the actual number of questions available in each source
            $sourceQuestionCounts = \\App\\Models\\Question::where(\'topic_id\', $topicId)
                ->whereNotNull(\'source\')
                ->selectRaw(\'source, COUNT(*) as count\')
                ->groupBy(\'source\')
                ->pluck(\'count\', \'source\')
                ->toArray();

            $totalScore = $attempts->sum(\'score\');
            $totalQuestions = $attempts->sum(\'total_questions\');
            $overallPercentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

            // Group by source and calculate correct answers per source
            $sourceProgress = [];
            foreach ($attempts as $attempt) {
                $source = $attempt->source ?: \'General\';
                if (!isset($sourceProgress[$source])) {
                    $sourceProgress[$source] = [
                        \'score\' => 0,
                        \'total\' => 0,
                        \'percentage\' => 0
                    ];
                }
                $sourceProgress[$source][\'score\'] += $attempt->score;
                $sourceProgress[$source][\'total\'] += $attempt->total_questions;
            }

            // Calculate percentages for each source based on available questions
            foreach ($sourceProgress as $source => $data) {
                $availableQuestions = $sourceQuestionCounts[$source] ?? 0;
                $completedQuestions = min($data[\'score\'], $availableQuestions); // Cap at available questions
                
                $sourceProgress[$source][\'percentage\'] = $availableQuestions > 0 
                    ? ($completedQuestions / $availableQuestions) * 100 
                    : 0;
            }

            return [
                \'overall\' => [
                    \'score\' => $totalScore,
                    \'total\' => $totalQuestions,
                    \'percentage\' => $overallPercentage
                ],
                \'sources\' => $sourceProgress
            ];
        } catch (\\Exception $e) {
            \\Log::error(\'Error getting assignment topic progress\', [
                \'user_id\' => $userId,
                \'topic_id\' => $topicId,
                \'assignment_id\' => $assignmentId,
                \'study_mode\' => $studyMode,
                \'error\' => $e->getMessage()
            ]);
            
            return [
                \'overall\' => [
                    \'score\' => 0,
                    \'total\' => 0,
                    \'percentage\' => 0
                ],
                \'sources\' => []
            ];
        }
    }';

        // Insert the new method before the last closing brace
        $content = str_replace(
            '    // Format duration as human-readable string',
            $newMethod . "\n\n    // Format duration as human-readable string",
            $content
        );

        file_put_contents($quizAttemptModelPath, $content);
        echo "✅ QuizAttempt model updated with assignment-specific progress calculation\n\n";
    }

    private function fixAssignmentScoreSync()
    {
        echo "🎯 FIXING ASSIGNMENT SCORE SYNCHRONIZATION...\n";
        echo "=============================================\n";

        // Update Assignment model to auto-sync scores
        $assignmentModelPath = 'app/Models/Assignment.php';
        $content = file_get_contents($assignmentModelPath);

        // Add method to sync assignment score with latest quiz attempt
        $syncMethod = '
    /**
     * 🚀 CRITICAL FIX: Sync assignment score with latest quiz attempt
     */
    public function syncScoreWithLatestAttempt(): bool
    {
        try {
            $latestAttempt = QuizAttempt::where(\'user_id\', $this->user_id)
                ->where(\'topic_id\', $this->topic_id)
                ->where(\'assignment_id\', $this->id)
                ->where(\'quiz_type\', \'assignment\')
                ->orderBy(\'created_at\', \'desc\')
                ->first();

            if ($latestAttempt) {
                $this->update([
                    \'score_percentage\' => $latestAttempt->percentage_grade
                ]);
                
                \\Log::info("Assignment score synced", [
                    \'assignment_id\' => $this->id,
                    \'old_score\' => $this->getOriginal(\'score_percentage\'),
                    \'new_score\' => $latestAttempt->percentage_grade
                ]);
                
                return true;
            }
            
            return false;
        } catch (\\Exception $e) {
            \\Log::error("Failed to sync assignment score", [
                \'assignment_id\' => $this->id,
                \'error\' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 🚀 CRITICAL FIX: Get assignment-specific progress
     */
    public function getAssignmentProgress(): array
    {
        return QuizAttempt::getAssignmentTopicProgress(
            $this->user_id,
            $this->topic_id,
            $this->id,
            \'assignment\'
        );
    }';

        // Insert the new methods before the timezone protection section
        $content = str_replace(
            '    /**
     * BULLETPROOF TIMEZONE PROTECTION',
            $syncMethod . "\n\n    /**
     * BULLETPROOF TIMEZONE PROTECTION",
            $content
        );

        file_put_contents($assignmentModelPath, $content);
        echo "✅ Assignment model updated with score synchronization\n\n";
    }

    private function fixStoreUpdateEndpoint()
    {
        echo "🎯 FIXING STORE-UPDATE ENDPOINT...\n";
        echo "==================================\n";

        // Update ProgressUpdateController to handle assignment-specific updates
        $controllerPath = 'app/Http/Controllers/ProgressUpdateController.php';
        $content = file_get_contents($controllerPath);

        // Fix the storeProgressUpdate method
        $fixedMethod = '    /**
     * Store progress update for real-time synchronization - ASSIGNMENT FIXED
     */
    public function storeProgressUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                \'topicId\' => \'required|integer\',
                \'assignmentId\' => \'nullable|integer\',
                \'progress\' => \'required|array\',
                \'availableSources\' => \'required|array\',
                \'allSourcesMastered\' => \'required|boolean\',
                \'userId\' => \'required|integer\'
            ]);

            // Store progress update with timestamp
            $progressData = array_merge($validated, [
                \'timestamp\' => now()->timestamp,
                \'created_at\' => now()->toISOString()
            ]);

            // CRITICAL FIX: Use assignment-specific cache key if assignment_id provided
            $cacheKey = $validated[\'assignmentId\'] 
                ? "assignment_progress_{$validated[\'userId\']}_{$validated[\'assignmentId\']}"
                : "progress_update_{$validated[\'userId\']}_{$validated[\'topicId\']}";
                
            Cache::put($cacheKey, $progressData, 300); // 5 minutes

            // Also store globally for this user
            $globalKey = "user_progress_updates_{$validated[\'userId\']}";
            $globalUpdates = Cache::get($globalKey, []);
            $globalUpdates[] = $progressData;
            
            // Keep only last 10 updates
            if (count($globalUpdates) > 10) {
                $globalUpdates = array_slice($globalUpdates, -10);
            }
            
            Cache::put($globalKey, $globalUpdates, 300);

            // CRITICAL FIX: Sync assignment score if this is an assignment update
            if ($validated[\'assignmentId\']) {
                $assignment = \\App\\Models\\Assignment::find($validated[\'assignmentId\']);
                if ($assignment) {
                    $assignment->syncScoreWithLatestAttempt();
                }
            }

            Log::info(\'Progress update stored for real-time sync\', [
                \'user_id\' => $validated[\'userId\'],
                \'topic_id\' => $validated[\'topicId\'],
                \'assignment_id\' => $validated[\'assignmentId\'],
                \'timestamp\' => $progressData[\'timestamp\']
            ]);

            return response()->json([
                \'success\' => true,
                \'message\' => \'Progress update stored successfully\',
                \'timestamp\' => $progressData[\'timestamp\']
            ]);

        } catch (\\Illuminate\\Validation\\ValidationException $e) {
            Log::error(\'Validation failed in store progress update\', [
                \'errors\' => $e->errors(),
                \'request_data\' => $request->all()
            ]);

            return response()->json([
                \'success\' => false,
                \'message\' => \'Validation failed\',
                \'errors\' => $e->errors()
            ], 422);

        } catch (\\Exception $e) {
            Log::error(\'Failed to store progress update\', [
                \'error\' => $e->getMessage(),
                \'request_data\' => $request->all(),
                \'trace\' => $e->getTraceAsString()
            ]);

            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to store progress update: \' . $e->getMessage()
            ], 500);
        }
    }';

        // Replace the existing method
        $content = preg_replace(
            '/public function storeProgressUpdate\(Request \$request\): JsonResponse\s*\{.*?\n    \}/s',
            $fixedMethod,
            $content
        );

        file_put_contents($controllerPath, $content);
        echo "✅ ProgressUpdateController fixed with assignment-specific handling\n\n";
    }

    private function fixProgressUpdateController()
    {
        echo "🎯 ADDING ASSIGNMENT PROGRESS ENDPOINT...\n";
        echo "=========================================\n";

        // Add new endpoint for assignment-specific progress
        $controllerPath = 'app/Http/Controllers/ProgressUpdateController.php';
        $content = file_get_contents($controllerPath);

        $newEndpoint = '
    /**
     * Get assignment-specific progress updates
     */
    public function getAssignmentProgress(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                \'assignmentId\' => \'required|integer\',
                \'userId\' => \'required|integer\'
            ]);

            $assignment = \\App\\Models\\Assignment::find($validated[\'assignmentId\']);
            if (!$assignment) {
                return response()->json([
                    \'success\' => false,
                    \'message\' => \'Assignment not found\'
                ], 404);
            }

            $progress = $assignment->getAssignmentProgress();

            return response()->json([
                \'success\' => true,
                \'progress\' => $progress,
                \'assignment\' => [
                    \'id\' => $assignment->id,
                    \'title\' => $assignment->title,
                    \'score_percentage\' => $assignment->score_percentage
                ]
            ]);

        } catch (\\Exception $e) {
            Log::error(\'Failed to get assignment progress\', [
                \'error\' => $e->getMessage(),
                \'request_data\' => $request->all()
            ]);

            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to get assignment progress\'
            ], 500);
        }
    }';

        // Add the new endpoint before the last closing brace
        $content = str_replace(
            '} 
',
            $newEndpoint . "\n} \n",
            $content
        );

        file_put_contents($controllerPath, $content);
        echo "✅ Assignment progress endpoint added\n\n";
    }

    private function fixTopicsBladeFile()
    {
        echo "🎯 FIXING TOPICS BLADE FILE PROGRESS DISPLAY...\n";
        echo "===============================================\n";

        // Update the JavaScript in topics_for_course.blade.php to handle assignment-specific progress
        $bladePath = 'resources/views/topics_for_course.blade.php';
        $content = file_get_contents($bladePath);

        // Fix the sourceProgressUpdated event handler
        $fixedEventHandler = '            // Handle source progress updates from quiz completion - ASSIGNMENT FIXED
            document.addEventListener(\'sourceProgressUpdated\', function(e) {
                const { topicId, progress, availableSources, allSourcesMastered, assignmentId } = e.detail;
                
                // Update source progress bars in the modal
                if (progress) {
                    updateSourcesContainer({
                        progress: progress,
                        availableSources: availableSources
                    });
                }
                
                // CRITICAL FIX: Update progress based on assignment context
                let targetCard = null;
                
                if (assignmentId) {
                    // This is an assignment completion - find the specific assignment card
                    targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
                    
                    // CRITICAL FIX: Also update the assignment score in the database
                    fetch(`/api/progress/assignment-sync`, {
                        method: \'POST\',
                        headers: {
                            \'Content-Type\': \'application/json\',
                            \'X-CSRF-TOKEN\': document.querySelector(\'meta[name="csrf-token"]\').content
                        },
                        body: JSON.stringify({
                            assignmentId: assignmentId,
                            userId: {{ auth()->id() }}
                        })
                    }).catch(error => {
                        console.error(\'Failed to sync assignment score:\', error);
                    });

                } else {
                    // This is regular quiz completion - find the regular topic card (not assignment cards)
                    targetCard = document.querySelector(`[data-topic-id="${topicId}"][data-has-assignment="false"]`);
                }
                
                if (targetCard) {
                    const gradeBadge = targetCard.querySelector(\'.grade-badge\');
                    if (gradeBadge) {
                        gradeBadge.className = `grade-badge ${allSourcesMastered ? \'grade-mastered\' : \'grade-good\'}`;
                        gradeBadge.innerHTML = allSourcesMastered ? 
                            \'<i class="fas fa-star"></i> Mastered\' : 
                            \'<i class="fas fa-check"></i> Good\';
                    }
                    
                    // CRITICAL FIX: Update assignment progress bar if this is an assignment
                    if (assignmentId) {
                        const progressBar = targetCard.querySelector(\'.progress-bar\');
                        if (progressBar && progress && progress.overall) {
                            progressBar.style.width = progress.overall.percentage + \'%\';
                        }
                        
                        const progressLabel = targetCard.querySelector(\'.progress-label\');
                        if (progressLabel && progress && progress.overall) {
                            progressLabel.innerHTML = `
                                <span>Assignment Progress</span>
                                <span>${Math.round(progress.overall.percentage)}%</span>
                            `;
                        }
                    }
                }
            });';

        // Replace the existing event handler
        $content = preg_replace(
            '/\/\/ Handle source progress updates from quiz completion.*?}\);/s',
            $fixedEventHandler,
            $content
        );

        file_put_contents($bladePath, $content);
        echo "✅ Topics blade file updated with assignment-specific progress handling\n\n";
    }

    public function addAssignmentSyncRoute()
    {
        echo "🎯 ADDING ASSIGNMENT SYNC ROUTE...\n";
        echo "==================================\n";

        // Add route for assignment score synchronization
        $routesPath = 'routes/web.php';
        $content = file_get_contents($routesPath);

        $newRoute = '    Route::post(\'/api/progress/assignment-sync\', [App\\Http\\Controllers\\ProgressUpdateController::class, \'syncAssignmentScore\'])->name(\'progress.assignment-sync\');';

        // Add the route after the existing progress routes
        $content = str_replace(
            'Route::post(\'/store-assignment\', [App\\Http\\Controllers\\ProgressUpdateController::class, \'storeAssignmentCompletion\'])->name(\'progress.store-assignment\');',
            'Route::post(\'/store-assignment\', [App\\Http\\Controllers\\ProgressUpdateController::class, \'storeAssignmentCompletion\'])->name(\'progress.store-assignment\');' . "\n" . $newRoute,
            $content
        );

        file_put_contents($routesPath, $content);
        echo "✅ Assignment sync route added\n\n";
    }

    public function addSyncAssignmentScoreMethod()
    {
        echo "🎯 ADDING SYNC ASSIGNMENT SCORE METHOD...\n";
        echo "=========================================\n";

        $controllerPath = 'app/Http/Controllers/ProgressUpdateController.php';
        $content = file_get_contents($controllerPath);

        $syncMethod = '
    /**
     * Sync assignment score with latest quiz attempt
     */
    public function syncAssignmentScore(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                \'assignmentId\' => \'required|integer\',
                \'userId\' => \'required|integer\'
            ]);

            $assignment = \\App\\Models\\Assignment::find($validated[\'assignmentId\']);
            if (!$assignment) {
                return response()->json([
                    \'success\' => false,
                    \'message\' => \'Assignment not found\'
                ], 404);
            }

            $synced = $assignment->syncScoreWithLatestAttempt();

            return response()->json([
                \'success\' => true,
                \'synced\' => $synced,
                \'score_percentage\' => $assignment->fresh()->score_percentage
            ]);

        } catch (\\Exception $e) {
            Log::error(\'Failed to sync assignment score\', [
                \'error\' => $e->getMessage(),
                \'request_data\' => $request->all()
            ]);

            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to sync assignment score\'
            ], 500);
        }
    }';

        // Add before the last closing brace
        $content = str_replace(
            '} 
',
            $syncMethod . "\n} \n",
            $content
        );

        file_put_contents($controllerPath, $content);
        echo "✅ Sync assignment score method added\n\n";
    }
}

// Execute fixes if run directly
if (php_sapi_name() === 'cli') {
    $fixer = new LethalAssignmentProgressFix();
    $fixer->executeAllFixes();
    $fixer->addAssignmentSyncRoute();
    $fixer->addSyncAssignmentScoreMethod();
    
    echo "\n🔥 LETHAL ASSIGNMENT PROGRESS FIX COMPLETE! 🔥\n";
    echo "==============================================\n";
    echo "FIXES APPLIED:\n";
    echo "1. ✅ Assignment-specific progress calculation\n";
    echo "2. ✅ Cross-contamination prevention\n";
    echo "3. ✅ Store-update endpoint validation fix\n";
    echo "4. ✅ Assignment score synchronization\n";
    echo "5. ✅ Progress display logic update\n";
    echo "6. ✅ Assignment sync route and method\n\n";
    echo "🎯 The assignment progress bug should now be ELIMINATED!\n";
}