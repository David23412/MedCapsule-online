<?php

namespace App\Console\Commands;

use App\Services\Audit\Deployment\MonitoringDeployment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Deploy Monitoring Command
 * 
 * Artisan command to deploy the live monitoring system
 */
class DeployMonitoringCommand extends Command
{
    protected $signature = 'audit:deploy-monitoring 
                           {--force : Force deployment even in production}
                           {--skip-tests : Skip deployment tests}';

    protected $description = 'Deploy the live monitoring system with dashboard and alerts';

    private MonitoringDeployment $deployment;

    public function __construct()
    {
        parent::__construct();
        $this->deployment = new MonitoringDeployment();
    }

    public function handle(): int
    {
        $force = $this->option('force');
        $skipTests = $this->option('skip-tests');

        $this->info("🚀 Deploying Live Monitoring System");
        $this->info("📊 Task 7.2: Live Monitoring Deployment");

        // Production safety check
        if (app()->environment('production') && !$force) {
            if (!$this->confirm('You are deploying to PRODUCTION. Are you sure?')) {
                $this->warn('Deployment cancelled by user');
                return Command::SUCCESS;
            }
        }

        try {
            // Execute deployment
            $result = $this->deployment->deployToProduction();

            if ($result['success']) {
                $this->info("✅ Live Monitoring Deployment completed successfully");
                $this->info("🖥️ Dashboard URL: " . $result['dashboard_url']);
                $this->info("📁 Deployment Path: " . $result['deployment_path']);
                $this->info("📊 Monitoring Status: " . $result['monitoring_status']);

                // Display deployment summary
                $this->displayDeploymentSummary($result['deployment_results']);

                return Command::SUCCESS;
            } else {
                $this->error("❌ Live Monitoring Deployment failed");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Deployment failed: " . $e->getMessage());
            Log::error('Monitoring deployment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    private function displayDeploymentSummary(array $results): void
    {
        $this->info("\n📊 DEPLOYMENT SUMMARY");
        $this->info("====================");

        foreach ($results as $component => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $componentName = ucwords(str_replace('_', ' ', $component));
            $this->info("{$status} {$componentName}");
        }
    }
}