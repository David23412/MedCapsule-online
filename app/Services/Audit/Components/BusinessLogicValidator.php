<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/**
 * Business Logic Validator with Deep Analysis
 * 
 * Validates core business logic including quiz system, grading mechanics,
 * user management, notification system, and payment processing
 */
class BusinessLogicValidator
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $businessMetrics = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive business logic validation
     */
    public function executeBusinessLogicValidation(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting Business Logic Validation');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. CONCRETE EVIDENCE: Quiz System Logic Validation
            $this->validateQuizSystemLogic();
            
            // 2. CONCRETE EVIDENCE: Grading Mechanics Verification
            $this->verifyGradingMechanics();
            
            // 3. CONCRETE EVIDENCE: User Management Flow Testing
            $this->testUserManagementFlow();
            
            // 4. CONCRETE EVIDENCE: Notification System Review
            $this->reviewNotificationSystem();
            
            // 5. CONCRETE EVIDENCE: Payment Processing Analysis
            $this->analyzePaymentProcessing();
            
            // 6. CONCRETE EVIDENCE: State Management Analysis
            $this->analyzeStateManagement();
            
            // 7. CONCRETE EVIDENCE: Edge Case Handling Assessment
            $this->assessEdgeCaseHandling();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'business_logic_validation',
                $this->findings,
                $this->generateBusinessMetrics(),
                $executionTime
            );
            
            Log::info('✅ Business Logic Validation Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Business Logic Validation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate quiz system logic with CONCRETE EVIDENCE
     */
    public function validateQuizSystemLogic(): void
    {
        Log::info('🔍 Validating Quiz System Logic');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $quizIssues = 0;
        
        // Find quiz-related files
        $quizFiles = array_filter($phpFiles, function($file) {
            return stripos($file['path'], 'quiz') !== false || 
                   stripos($file['path'], 'question') !== false ||
                   stripos($file['path'], 'answer') !== false;
        });
        
        foreach ($quizFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            // 1. Check for quiz scoring logic
            foreach ($lines as $lineNumber => $line) {
                if (preg_match('/score.*=.*\$|points.*=.*\$|grade.*=.*\$/', $line)) {
                    // Check if scoring logic has validation
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/validate|check|verify|min|max/', $context)) {
                        $quizIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'Quiz scoring logic without validation detected',
                            'business_logic',
                            'quiz_scoring'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unvalidated Scoring', 'business_risk');
                        $finding->addMetric('risk_score', 8.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'scoring_line' => trim($line),
                            'file_path' => $file['path'],
                            'validation_present' => false
                        ], 'Quiz scoring without validation detected');
                        
                        $finding->setRecommendation('Add validation to quiz scoring logic to prevent manipulation');
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for question randomization logic
                if (preg_match('/shuffle|random|rand\(/', $line) && stripos($line, 'question') !== false) {
                    // Check if randomization is seeded properly
                    if (!preg_match('/seed|srand|mt_srand/', $content)) {
                        $quizIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Question randomization without proper seeding',
                            'business_logic',
                            'quiz_randomization'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unseeded Randomization', 'reliability');
                        $finding->addMetric('reliability_impact', 6.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'randomization_line' => trim($line),
                            'file_path' => $file['path'],
                            'seeding_present' => false
                        ], 'Question randomization without seeding detected');
                        
                        $finding->setRecommendation('Add proper seeding to ensure consistent randomization');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 3. Check for time limit enforcement
                if (preg_match('/time.*limit|duration|timeout/', $line)) {
                    // Check if time validation is present
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/Carbon|DateTime|time\(\)|now\(\)/', $context)) {
                        $quizIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'Quiz time limit without proper time validation',
                            'business_logic',
                            'quiz_timing'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Time Validation Missing', 'business_risk');
                        $finding->addMetric('risk_score', 7.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'timing_line' => trim($line),
                            'file_path' => $file['path'],
                            'time_validation_present' => false
                        ], 'Quiz timing without proper validation detected');
                        
                        $finding->setRecommendation('Add proper time validation to enforce quiz time limits');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['quiz_logic_issues'] = $quizIssues;
        Log::info("Found {$quizIssues} quiz system logic issues");
    }

    /**
     * Verify grading mechanics with CONCRETE EVIDENCE
     */
    public function verifyGradingMechanics(): void
    {
        Log::info('🔍 Verifying Grading Mechanics');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $gradingIssues = 0;
        
        // Find grading-related files
        $gradingFiles = array_filter($phpFiles, function($file) {
            return stripos($file['path'], 'grad') !== false || 
                   stripos($file['path'], 'score') !== false ||
                   stripos($file['path'], 'assessment') !== false;
        });
        
        foreach ($gradingFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for grade calculation logic
                if (preg_match('/grade.*=.*\(|percentage.*=.*\(|final.*score/', $line)) {
                    // Check for division by zero protection
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (preg_match('/\/|\bdiv\b/', $context) && !preg_match('/!==?\s*0|>\s*0|if.*0/', $context)) {
                        $gradingIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Grade calculation without division by zero protection',
                            'business_logic',
                            'grading_calculation'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Division by Zero Risk', 'critical_error');
                        $finding->addMetric('risk_score', 9.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'calculation_line' => trim($line),
                            'file_path' => $file['path'],
                            'zero_protection' => false
                        ], 'Grade calculation without zero protection detected');
                        
                        $finding->setRecommendation('Add division by zero protection to prevent calculation errors');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for grade boundary validation
                if (preg_match('/grade.*[<>]=?|score.*[<>]=?/', $line)) {
                    // Check if boundaries are properly defined
                    if (!preg_match('/100|0\.0|1\.0/', $line)) {
                        $gradingIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Grade boundary without clear limits',
                            'business_logic',
                            'grade_boundaries'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unclear Boundaries', 'business_risk');
                        $finding->addMetric('risk_score', 5.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'boundary_line' => trim($line),
                            'file_path' => $file['path'],
                            'clear_limits' => false
                        ], 'Grade boundary without clear limits detected');
                        
                        $finding->setRecommendation('Define clear grade boundaries (0-100 or 0.0-1.0)');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 3. Check for weighted grading logic
                if (preg_match('/weight.*\*|coefficient.*\*|\*.*weight/', $line)) {
                    // Check if weights sum to 1.0 or 100%
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/sum.*weight|total.*weight|array_sum/', $context)) {
                        $gradingIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'Weighted grading without weight validation',
                            'business_logic',
                            'weighted_grading'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Weight Validation Missing', 'business_risk');
                        $finding->addMetric('risk_score', 7.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'weighting_line' => trim($line),
                            'file_path' => $file['path'],
                            'weight_validation' => false
                        ], 'Weighted grading without validation detected');
                        
                        $finding->setRecommendation('Add validation to ensure weights sum to 100% or 1.0');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['grading_mechanics_issues'] = $gradingIssues;
        Log::info("Found {$gradingIssues} grading mechanics issues");
    }

    /**
     * Test user management flow with CONCRETE EVIDENCE
     */
    public function testUserManagementFlow(): void
    {
        Log::info('🔍 Testing User Management Flow');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $userMgmtIssues = 0;
        
        // Find user management files
        $userFiles = array_filter($phpFiles, function($file) {
            return stripos($file['path'], 'user') !== false || 
                   stripos($file['path'], 'auth') !== false ||
                   stripos($file['path'], 'registration') !== false;
        });
        
        foreach ($userFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for user creation without validation
                if (preg_match('/User::create|new User|user.*save\(\)/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/validate|rules|required/', $context)) {
                        $userMgmtIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'User creation without proper validation',
                            'business_logic',
                            'user_creation'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Validation Missing', 'business_risk');
                        $finding->addMetric('risk_score', 8.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'creation_line' => trim($line),
                            'file_path' => $file['path'],
                            'validation_present' => false
                        ], 'User creation without validation detected');
                        
                        $finding->setRecommendation('Add proper validation to user creation process');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for role assignment logic
                if (preg_match('/role.*=|assignRole|syncRoles/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/auth.*check|can\(|authorize/', $context)) {
                        $userMgmtIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Role assignment without authorization check',
                            'business_logic',
                            'role_assignment'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Authorization Missing', 'security_risk');
                        $finding->addMetric('risk_score', 9.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'role_line' => trim($line),
                            'file_path' => $file['path'],
                            'authorization_check' => false
                        ], 'Role assignment without authorization detected');
                        
                        $finding->setRecommendation('Add authorization check before role assignment');
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 3. Check for password reset logic
                if (preg_match('/password.*reset|reset.*password/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/token|verify|expire/', $context)) {
                        $userMgmtIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Password reset without proper token validation',
                            'business_logic',
                            'password_reset'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Token Validation Missing', 'security_risk');
                        $finding->addMetric('risk_score', 9.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'reset_line' => trim($line),
                            'file_path' => $file['path'],
                            'token_validation' => false
                        ], 'Password reset without token validation detected');
                        
                        $finding->setRecommendation('Add proper token validation to password reset flow');
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['user_management_issues'] = $userMgmtIssues;
        Log::info("Found {$userMgmtIssues} user management issues");
    }

    /**
     * Review notification system with CONCRETE EVIDENCE
     */
    public function reviewNotificationSystem(): void
    {
        Log::info('🔍 Reviewing Notification System');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $notificationIssues = 0;
        
        // Find notification-related files
        $notificationFiles = array_filter($phpFiles, function($file) {
            return stripos($file['path'], 'notification') !== false || 
                   stripos($file['path'], 'mail') !== false ||
                   stripos($file['path'], 'email') !== false;
        });
        
        foreach ($notificationFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for email sending without queue
                if (preg_match('/Mail::send|mail\(|->send\(\)/', $line)) {
                    if (!preg_match('/queue|dispatch|job/', $line)) {
                        $notificationIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Email sending without queue - performance impact',
                            'business_logic',
                            'email_queue'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Synchronous Email', 'performance');
                        $finding->addMetric('performance_impact', 6.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'email_line' => trim($line),
                            'file_path' => $file['path'],
                            'queued' => false
                        ], 'Synchronous email sending detected');
                        
                        $finding->setRecommendation('Use queued email sending for better performance');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for notification without failure handling
                if (preg_match('/notify|notification|alert/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 15);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/try.*catch|rescue|fail|error/', $context)) {
                        $notificationIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Notification without failure handling',
                            'business_logic',
                            'notification_failure'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'No Failure Handling', 'reliability');
                        $finding->addMetric('reliability_impact', 5.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'notification_line' => trim($line),
                            'file_path' => $file['path'],
                            'failure_handling' => false
                        ], 'Notification without failure handling detected');
                        
                        $finding->setRecommendation('Add try-catch blocks for notification failure handling');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['notification_issues'] = $notificationIssues;
        Log::info("Found {$notificationIssues} notification system issues");
    }

    /**
     * Analyze payment processing with CONCRETE EVIDENCE
     */
    public function analyzePaymentProcessing(): void
    {
        Log::info('🔍 Analyzing Payment Processing');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $paymentIssues = 0;
        
        // Find payment-related files
        $paymentFiles = array_filter($phpFiles, function($file) {
            return stripos($file['path'], 'payment') !== false || 
                   stripos($file['path'], 'billing') !== false ||
                   stripos($file['path'], 'transaction') !== false ||
                   stripos($file['path'], 'stripe') !== false ||
                   stripos($file['path'], 'paypal') !== false;
        });
        
        foreach ($paymentFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for payment processing without transaction
                if (preg_match('/charge|payment|bill|invoice/', $line) && preg_match('/create|process|execute/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/DB::transaction|beginTransaction|commit|rollback/', $context)) {
                        $paymentIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Payment processing without database transaction',
                            'business_logic',
                            'payment_transaction'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'No Transaction', 'critical_error');
                        $finding->addMetric('risk_score', 9.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'payment_line' => trim($line),
                            'file_path' => $file['path'],
                            'transaction_wrapped' => false
                        ], 'Payment processing without transaction detected');
                        
                        $finding->setRecommendation('Wrap payment processing in database transaction');
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for amount validation
                if (preg_match('/amount.*=|\$amount|price.*=|\$price/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/validate|is_numeric|floatval|intval|>.*0/', $context)) {
                        $paymentIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'critical',
                            'Payment amount without validation',
                            'business_logic',
                            'amount_validation'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Amount Validation Missing', 'security_risk');
                        $finding->addMetric('risk_score', 9.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'amount_line' => trim($line),
                            'file_path' => $file['path'],
                            'validation_present' => false
                        ], 'Payment amount without validation detected');
                        
                        $finding->setRecommendation('Add proper validation for payment amounts');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 3. Check for refund logic
                if (preg_match('/refund|cancel.*payment|reverse/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 10), 20);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/log|audit|record|history/', $context)) {
                        $paymentIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'Refund processing without audit trail',
                            'business_logic',
                            'refund_audit'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'No Audit Trail', 'compliance_risk');
                        $finding->addMetric('risk_score', 7.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'refund_line' => trim($line),
                            'file_path' => $file['path'],
                            'audit_trail' => false
                        ], 'Refund processing without audit trail detected');
                        
                        $finding->setRecommendation('Add audit logging to refund processing');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['payment_processing_issues'] = $paymentIssues;
        Log::info("Found {$paymentIssues} payment processing issues");
    }

    /**
     * Analyze state management with CONCRETE EVIDENCE
     */
    public function analyzeStateManagement(): void
    {
        Log::info('🔍 Analyzing State Management');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $stateIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for state transitions without validation
                if (preg_match('/status.*=|state.*=|->update.*status|->update.*state/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/validate|check|verify|can.*transition/', $context)) {
                        $stateIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'State transition without validation',
                            'business_logic',
                            'state_transition'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unvalidated Transition', 'business_risk');
                        $finding->addMetric('risk_score', 7.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'transition_line' => trim($line),
                            'file_path' => $file['path'],
                            'validation_present' => false
                        ], 'State transition without validation detected');
                        
                        $finding->setRecommendation('Add validation to state transitions');
                        $finding->setEstimatedHours(3);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for concurrent state modification
                if (preg_match('/update.*where.*id|save\(\)|->update\(/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/lockForUpdate|version|updated_at/', $context)) {
                        $stateIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'State modification without concurrency protection',
                            'business_logic',
                            'concurrent_modification'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Race Condition Risk', 'reliability');
                        $finding->addMetric('risk_score', 6.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'modification_line' => trim($line),
                            'file_path' => $file['path'],
                            'concurrency_protection' => false
                        ], 'State modification without concurrency protection detected');
                        
                        $finding->setRecommendation('Add optimistic locking or version control to prevent race conditions');
                        $finding->setEstimatedHours(4);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['state_management_issues'] = $stateIssues;
        Log::info("Found {$stateIssues} state management issues");
    }

    /**
     * Assess edge case handling with CONCRETE EVIDENCE
     */
    public function assessEdgeCaseHandling(): void
    {
        Log::info('🔍 Assessing Edge Case Handling');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $edgeCaseIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                // 1. Check for array access without bounds checking
                if (preg_match('/\$\w+\[\d+\]|\$\w+\[\$\w+\]/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 3), 7);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/isset|array_key_exists|count|empty|!empty/', $context)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Array access without bounds checking',
                            'business_logic',
                            'array_bounds'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Array Bounds Risk', 'reliability');
                        $finding->addMetric('risk_score', 6.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'array_access_line' => trim($line),
                            'file_path' => $file['path'],
                            'bounds_check' => false
                        ], 'Array access without bounds checking detected');
                        
                        $finding->setRecommendation('Add isset() or array_key_exists() check before array access');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 2. Check for null pointer dereference risk
                if (preg_match('/\$\w+->\w+|\$\w+\[\w+\]->\w+/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 3), 7);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/!is_null|!== null|is_null.*return|if.*null/', $context)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'high',
                            'Potential null pointer dereference',
                            'business_logic',
                            'null_dereference'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Null Dereference Risk', 'reliability');
                        $finding->addMetric('risk_score', 7.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'dereference_line' => trim($line),
                            'file_path' => $file['path'],
                            'null_check' => false
                        ], 'Potential null pointer dereference detected');
                        
                        $finding->setRecommendation('Add null check before object method calls');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 3. Check for empty string/collection handling
                if (preg_match('/foreach\s*\(\s*\$\w+/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 3), 7);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/empty|count|is_array/', $context)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'low',
                            'Foreach loop without empty collection check',
                            'business_logic',
                            'empty_collection'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Empty Collection Risk', 'reliability');
                        $finding->addMetric('risk_score', 4.0, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'foreach_line' => trim($line),
                            'file_path' => $file['path'],
                            'empty_check' => false
                        ], 'Foreach without empty collection check detected');
                        
                        $finding->setRecommendation('Add empty() check before foreach loops');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 4. Check for integer overflow in calculations
                if (preg_match('/\*|\+|\-|\//', $line) && preg_match('/\$\w+.*\$\w+/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (preg_match('/PHP_INT_MAX|bcmath|gmp/', $context) === false && 
                        preg_match('/price|amount|total|sum|count/', $line)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Arithmetic operation without overflow protection',
                            'business_logic',
                            'integer_overflow'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Overflow Risk', 'reliability');
                        $finding->addMetric('risk_score', 5.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'calculation_line' => trim($line),
                            'file_path' => $file['path'],
                            'overflow_protection' => false
                        ], 'Arithmetic operation without overflow protection detected');
                        
                        $finding->setRecommendation('Consider using bcmath for large number calculations');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 5. Check for unhandled exceptions in critical paths
                if (preg_match('/throw new|throw \$/', $line)) {
                    $contextLines = array_slice($lines, $lineNumber, 20);
                    $context = implode("\n", $contextLines);
                    
                    // Look for the calling context
                    $callingContext = array_slice($lines, max(0, $lineNumber - 20), 20);
                    $callingContextStr = implode("\n", $callingContext);
                    
                    if (!preg_match('/try.*catch|rescue/', $callingContextStr)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'medium',
                            'Exception thrown without surrounding try-catch',
                            'business_logic',
                            'unhandled_exception'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Unhandled Exception', 'reliability');
                        $finding->addMetric('risk_score', 6.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'exception_line' => trim($line),
                            'file_path' => $file['path'],
                            'try_catch_present' => false
                        ], 'Exception without try-catch context detected');
                        
                        $finding->setRecommendation('Ensure exceptions are properly caught and handled');
                        $finding->setEstimatedHours(2);
                        
                        $this->findings[] = $finding;
                    }
                }
                
                // 6. Check for timezone handling in date operations
                if (preg_match('/date\(|strtotime|DateTime|Carbon/', $line)) {
                    $contextLines = array_slice($lines, max(0, $lineNumber - 5), 10);
                    $context = implode("\n", $contextLines);
                    
                    if (!preg_match('/timezone|UTC|setTimezone/', $context)) {
                        $edgeCaseIssues++;
                        
                        $finding = new ConcreteFinding(
                            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                            'low',
                            'Date operation without explicit timezone handling',
                            'business_logic',
                            'timezone_handling'
                        );
                        
                        $finding->addFileReference($file['path'], $lineNumber + 1, trim($line));
                        $finding->addMetric('issue_type', 'Timezone Risk', 'reliability');
                        $finding->addMetric('risk_score', 4.5, 'score_out_of_10');
                        
                        $finding->addEvidence('static_analysis', [
                            'date_line' => trim($line),
                            'file_path' => $file['path'],
                            'timezone_explicit' => false
                        ], 'Date operation without timezone handling detected');
                        
                        $finding->setRecommendation('Explicitly handle timezones in date operations');
                        $finding->setEstimatedHours(1);
                        
                        $this->findings[] = $finding;
                    }
                }
            }
        }
        
        $this->businessMetrics['edge_case_issues'] = $edgeCaseIssues;
        Log::info("Found {$edgeCaseIssues} edge case handling issues");
    }

    /**
     * Generate business logic metrics
     */
    private function generateBusinessMetrics(): array
    {
        return [
            'total_business_findings' => count($this->findings),
            'quiz_logic_issues' => $this->businessMetrics['quiz_logic_issues'] ?? 0,
            'grading_mechanics_issues' => $this->businessMetrics['grading_mechanics_issues'] ?? 0,
            'user_management_issues' => $this->businessMetrics['user_management_issues'] ?? 0,
            'notification_issues' => $this->businessMetrics['notification_issues'] ?? 0,
            'payment_processing_issues' => $this->businessMetrics['payment_processing_issues'] ?? 0,
            'state_management_issues' => $this->businessMetrics['state_management_issues'] ?? 0,
            'edge_case_issues' => $this->businessMetrics['edge_case_issues'] ?? 0,
            'critical_business_risks' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'critical';
            })),
            'high_business_risks' => count(array_filter($this->findings, function($finding) {
                return $finding->getSeverity() === 'high';
            })),
            'business_logic_coverage' => [
                'quiz_system' => true,
                'grading_mechanics' => true,
                'user_management' => true,
                'notifications' => true,
                'payments' => true,
                'state_management' => true,
                'edge_cases' => true
            ]
        ];
    }
}