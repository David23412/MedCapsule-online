<?php

/**
 * Split-Brain Detector Script
 * Detects dual-write patterns and inconsistent state in Laravel app
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class SplitBrainDetector
{
    private $issues = [];
    private $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function detect(): array
    {
        echo "🧠 SPLIT-BRAIN DETECTOR v1.0\n";
        echo "============================\n\n";

        $this->detectEnrollmentSplitBrain();
        $this->detectPaymentStatusSplitBrain();
        $this->detectVideoCompletionSplitBrain();
        $this->detectAssignmentStatusIssues();

        return $this->issues;
    }

    private function detectEnrollmentSplitBrain()
    {
        echo "🔍 Checking ENROLLMENT split-brain...\n";

        try {
            // Check if both columns exist (split-brain pattern)
            $hasIsEnrolled = Schema::hasColumn('course_user', 'is_enrolled');
            $hasEnrolledAt = Schema::hasColumn('course_user', 'enrolled_at');

            if ($hasIsEnrolled && $hasEnrolledAt) {
                $this->issues[] = [
                    'type' => 'CRITICAL',
                    'concept' => 'enrollment',
                    'description' => 'Split-brain pattern detected: both is_enrolled and enrolled_at exist',
                    'status' => '🔴 SPLIT-BRAIN PATTERN'
                ];
                echo "  🔴 CRITICAL: Split-brain pattern detected - both columns exist\n";
            } elseif ($hasEnrolledAt && !$hasIsEnrolled) {
                echo "  ✅ PERFECT: Using enrolled_at timestamp as SSOT\n";
            } elseif ($hasIsEnrolled && !$hasEnrolledAt) {
                $this->issues[] = [
                    'type' => 'HIGH',
                    'concept' => 'enrollment',
                    'description' => 'Using boolean instead of timestamp - not optimal',
                    'status' => '🟠 SUBOPTIMAL PATTERN'
                ];
                echo "  🟠 HIGH: Using boolean instead of timestamp - not optimal\n";
            } else {
                echo "  ❓ UNKNOWN: Enrollment structure not recognized\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
    }

    private function detectPaymentStatusSplitBrain()
    {
        echo "\n🔍 Checking PAYMENT STATUS split-brain...\n";

        try {
            $hasStatus = Schema::hasColumn('payments', 'status');
            $hasPaidAt = Schema::hasColumn('payments', 'paid_at');

            if ($hasStatus && $hasPaidAt) {
                // Check for inconsistencies
                $inconsistent = $this->db->table('payments')
                    ->where(function($q) {
                        $q->where('status', 'completed')->whereNull('paid_at');
                    })
                    ->orWhere(function($q) {
                        $q->where('status', '!=', 'completed')->whereNotNull('paid_at');
                    })
                    ->count();

                if ($inconsistent > 0) {
                    $this->issues[] = [
                        'type' => 'CRITICAL',
                        'concept' => 'payment_status',
                        'description' => "Found {$inconsistent} inconsistent payment records",
                        'status' => '🔴 ACTIVE SPLIT-BRAIN'
                    ];
                    echo "  🔴 CRITICAL: {$inconsistent} inconsistent payment records found\n";
                } else {
                    $this->issues[] = [
                        'type' => 'WARNING',
                        'concept' => 'payment_status',
                        'description' => 'Dual columns exist but data is consistent',
                        'status' => '🟡 POTENTIAL SPLIT-BRAIN'
                    ];
                    echo "  🟡 WARNING: Dual columns exist but data is consistent\n";
                }
            } elseif ($hasPaidAt && !$hasStatus) {
                echo "  ✅ GOOD: Using paid_at timestamp as SSOT\n";
            } else {
                echo "  ❓ UNKNOWN: Payment structure not recognized\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
    }

    private function detectVideoCompletionSplitBrain()
    {
        echo "\n🔍 Checking VIDEO COMPLETION split-brain...\n";

        try {
            if (Schema::hasTable('video_progress')) {
                $hasCompleted = Schema::hasColumn('video_progress', 'completed');
                $hasProgress = Schema::hasColumn('video_progress', 'progress_percentage');

                if ($hasCompleted && $hasProgress) {
                    // Check for inconsistencies (completed=true but progress<90%)
                    $inconsistent = $this->db->table('video_progress')
                        ->where('completed', true)
                        ->where('progress_percentage', '<', 90)
                        ->count();

                    if ($inconsistent > 0) {
                        $this->issues[] = [
                            'type' => 'HIGH',
                            'concept' => 'video_completion',
                            'description' => "Found {$inconsistent} inconsistent video completion records",
                            'status' => '🟠 ACTIVE SPLIT-BRAIN'
                        ];
                        echo "  🟠 HIGH: {$inconsistent} inconsistent video completion records\n";
                    } else {
                        echo "  🟡 WARNING: Dual columns exist but data is consistent\n";
                    }
                } else {
                    echo "  ✅ GOOD: Video completion structure looks clean\n";
                }
            } else {
                echo "  ℹ️  INFO: No video_progress table found\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
    }

    private function detectAssignmentStatusIssues()
    {
        echo "\n🔍 Checking ASSIGNMENT STATUS issues...\n";

        try {
            if (Schema::hasTable('assignments')) {
                $hasStatus = Schema::hasColumn('assignments', 'status');
                $hasStatusCached = Schema::hasColumn('assignments', 'status_cached');

                if (!$hasStatus && !$hasStatusCached) {
                    // Check if code properly uses computed status
                    $codeUsesComputedStatus = $this->checkAssignmentStatusCodeUsage();
                    
                    if ($codeUsesComputedStatus) {
                        echo "  ✅ PERFECT: Using computed status via AssignmentStatusService\n";
                    } else {
                        $this->issues[] = [
                            'type' => 'HIGH',
                            'concept' => 'assignment_status',
                            'description' => 'Assignment status column missing and code not using computed status',
                            'status' => '🟠 MISSING COLUMN'
                        ];
                        echo "  🟠 HIGH: Assignment status column missing and code not using computed status\n";
                    }
                } elseif ($hasStatusCached) {
                    echo "  ✅ GOOD: Using cached status column for performance\n";
                } elseif ($hasStatus) {
                    $this->issues[] = [
                        'type' => 'WARNING',
                        'concept' => 'assignment_status',
                        'description' => 'Assignment has status column - consider using computed status instead',
                        'status' => '🟡 SUBOPTIMAL PATTERN'
                    ];
                    echo "  🟡 WARNING: Assignment has status column - consider using computed status instead\n";
                }
            }
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
    }

    private function checkAssignmentStatusCodeUsage(): bool
    {
        // Check if controllers use getCurrentStatusAttribute() instead of status column
        $controllerFiles = [
            'app/Http/Controllers/Tutor/TutorDashboardViewController.php',
            'app/Http/Controllers/Student/StudentAssignmentViewController.php',
            'app/Http/Controllers/Student/StudentAssignmentApiController.php'
        ];

        foreach ($controllerFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                // Check if it uses computed status instead of direct status column
                if (strpos($content, 'getCurrentStatusAttribute()') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function generateSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 SPLIT-BRAIN DETECTION SUMMARY\n";
        echo str_repeat("=", 50) . "\n";

        $critical = array_filter($this->issues, fn($i) => $i['type'] === 'CRITICAL');
        $high = array_filter($this->issues, fn($i) => $i['type'] === 'HIGH');
        $warning = array_filter($this->issues, fn($i) => $i['type'] === 'WARNING');

        echo "🔴 CRITICAL: " . count($critical) . " issues\n";
        echo "🟠 HIGH: " . count($high) . " issues\n";
        echo "🟡 WARNING: " . count($warning) . " issues\n";

        if (count($critical) > 0) {
            echo "\n❌ RESULT: CRITICAL split-brain issues detected!\n";
            echo "🚨 ACTION REQUIRED: Fix critical issues immediately\n";
            exit(1);
        } elseif (count($high) > 0) {
            echo "\n⚠️  RESULT: HIGH priority issues detected\n";
            echo "📋 ACTION RECOMMENDED: Address high priority issues\n";
            exit(2);
        } else {
            echo "\n✅ RESULT: No critical split-brain issues detected\n";
            echo "🎉 STATUS: Application state appears consistent\n";
            exit(0);
        }
    }
}

// Run the detector
$detector = new SplitBrainDetector();
$issues = $detector->detect();
$detector->generateSummary();