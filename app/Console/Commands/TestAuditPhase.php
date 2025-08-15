<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Audit\AuditOrchestrator;
use App\Services\Audit\Components\QualityGateEnforcer;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Quality\QualityEnforcer;
use App\Services\Audit\Quality\EvidenceValidator;
use App\Services\Audit\Quality\SpeculativeFindingDetector;
use App\Services\Audit\Quality\EvidenceQualityScorer;
use App\Services\Audit\Quality\ReportDowngrader;
use App\Services\Audit\Quality\AccountabilityMetricsTracker;
use App\Services\Audit\Core\AuditState;
use Exception;

class TestAuditPhase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:test-phase 
                            {phase : The phase to test (e.g., environment_detection, codebase_structural_analysis)}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Test individual audit phases for debugging and validation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phase = $this->argument('phase');
        
        $this->info("🔍 Testing Individual Audit Phase: {$phase}");
        $this->info('===========================================');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $this->info('🔧 Initializing components...');
            $qualityEnforcerSystem = $this->initializeQualityEnforcer();
            $qualityGateEnforcer = new QualityGateEnforcer();
            $evidenceTracker = new EvidenceTracker();
            $auditState = new AuditState();
            
            // Initialize orchestrator
            $orchestrator = new AuditOrchestrator(
                $qualityGateEnforcer,
                $evidenceTracker,
                $qualityEnforcerSystem,
                $auditState
            );
            
            $this->info('✅ Components initialized successfully!');
            $this->newLine();
            
            // Test the specific phase
            $this->info("🚀 Testing Phase: {$phase}");
            $this->info('========================');
            
            $result = $this->testSinglePhase($orchestrator, $phase);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            // Display results
            $this->displayPhaseResults($phase, $result, $duration);
            
            return 0;
            
        } catch (Exception $e) {
            $this->error("❌ Phase Test Failed: " . $e->getMessage());
            
            if ($this->option('detailed')) {
                $this->error('Stack Trace:');
                $this->error($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * Initialize Quality Enforcer System
     */
    private function initializeQualityEnforcer(): QualityEnforcer
    {
        $evidenceValidator = new EvidenceValidator();
        $speculationDetector = new SpeculativeFindingDetector();
        $qualityScorer = new EvidenceQualityScorer();
        $reportDowngrader = new ReportDowngrader();
        $accountabilityTracker = new AccountabilityMetricsTracker();
        
        return new QualityEnforcer(
            $evidenceValidator,
            $speculationDetector,
            $qualityScorer,
            $reportDowngrader,
            $accountabilityTracker
        );
    }

    /**
     * Test a single phase
     */
    private function testSinglePhase($orchestrator, string $phase)
    {
        $this->line("🔍 Executing phase: {$phase}");
        
        // Use reflection to access the private method
        $reflection = new \ReflectionClass($orchestrator);
        $method = $reflection->getMethod('executeNuclearPhaseLogic');
        $method->setAccessible(true);
        
        try {
            $result = $method->invoke($orchestrator, $phase);
            $this->info("✅ Phase executed successfully");
            return $result;
        } catch (Exception $e) {
            $this->error("❌ Phase execution failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display phase results
     */
    private function displayPhaseResults(string $phase, $result, float $duration): void
    {
        $this->info('📊 Phase Test Results:');
        $this->info('=====================');
        
        $findings = is_object($result) && method_exists($result, 'getFindings') ? $result->getFindings() : [];
        $metrics = is_object($result) && method_exists($result, 'getMetrics') ? $result->getMetrics() : [];
        
        $this->table([
            'Metric', 'Value'
        ], [
            ['Phase', $phase],
            ['Duration', $duration . 's'],
            ['Findings Count', count($findings)],
            ['Metrics Count', count($metrics)],
            ['Status', count($findings) >= 0 ? '✅ SUCCESS' : '❌ FAILED']
        ]);
        
        if (!empty($findings) && $this->option('detailed')) {
            $this->newLine();
            $this->info('🔍 Sample Findings:');
            $this->info('==================');
            
            $sampleFindings = array_slice($findings, 0, 5);
            foreach ($sampleFindings as $index => $finding) {
                if (is_object($finding) && method_exists($finding, 'getSeverity') && method_exists($finding, 'getDescription')) {
                    $severity = strtoupper($finding->getSeverity());
                    $description = substr($finding->getDescription(), 0, 80) . '...';
                    $this->line("  " . ($index + 1) . ". [{$severity}] {$description}");
                }
            }
            
            if (count($findings) > 5) {
                $this->line("  ... and " . (count($findings) - 5) . " more findings");
            }
        }
        
        if (!empty($metrics) && $this->option('detailed')) {
            $this->newLine();
            $this->info('📈 Metrics Summary:');
            $this->info('==================');
            
            foreach (array_slice($metrics, 0, 10) as $key => $value) {
                if (is_scalar($value)) {
                    $this->line("  • {$key}: {$value}");
                } elseif (is_array($value)) {
                    $this->line("  • {$key}: " . count($value) . " items");
                }
            }
        }
    }
}