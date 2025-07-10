<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class EnvironmentSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:switch {environment : The environment to switch to (local|ngrok|production)} {--url= : Custom URL for the environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch between different environment configurations (local, ngrok, production)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $environment = $this->argument('environment');
        $customUrl = $this->option('url');

        $this->info("🔄 Switching to {$environment} environment...");

        switch ($environment) {
            case 'local':
                $this->switchToLocal();
                break;
            case 'ngrok':
                $this->switchToNgrok($customUrl);
                break;
            case 'production':
                $this->switchToProduction();
                break;
            default:
                $this->error("❌ Invalid environment: {$environment}");
                $this->info("Available environments: local, ngrok, production");
                return 1;
        }

        $this->info("✅ Successfully switched to {$environment} environment!");
        $this->showCurrentConfig();
        
        return 0;
    }

    /**
     * Switch to local environment
     */
    private function switchToLocal()
    {
        $this->updateEnvironmentFile([
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_URL' => 'http://localhost:8000',
            'LOG_LEVEL' => 'debug',
            'CACHE_DRIVER' => 'file',
            'SESSION_DRIVER' => 'file',
            'QUEUE_CONNECTION' => 'sync'
        ]);

        $this->clearCaches();
        $this->info("🌐 Local environment configured at: http://localhost:8000");
    }

    /**
     * Switch to ngrok environment
     */
    private function switchToNgrok($customUrl = null)
    {
        $ngrokUrl = $customUrl ?: 'https://d54130044c29.ngrok-free.app';
        
        $this->updateEnvironmentFile([
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_URL' => $ngrokUrl,
            'LOG_LEVEL' => 'debug',
            'CACHE_DRIVER' => 'file',
            'SESSION_DRIVER' => 'file',
            'QUEUE_CONNECTION' => 'sync'
        ]);

        $this->clearCaches();
        $this->info("🌐 Ngrok environment configured at: {$ngrokUrl}");
    }

    /**
     * Switch to production environment
     */
    private function switchToProduction()
    {
        $this->updateEnvironmentFile([
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://your-production-domain.com',
            'LOG_LEVEL' => 'error',
            'CACHE_DRIVER' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'QUEUE_CONNECTION' => 'redis'
        ]);

        $this->clearCaches();
        $this->info("🚀 Production environment configured");
    }

    /**
     * Update the .env file with new values
     */
    private function updateEnvironmentFile(array $updates)
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->error("❌ .env file not found!");
            return;
        }

        $envContent = File::get($envPath);
        
        foreach ($updates as $key => $value) {
            // Check if the key already exists
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
        $this->info("📝 Updated .env file");
    }

    /**
     * Clear application caches
     */
    private function clearCaches()
    {
        $this->info("🧹 Clearing caches...");
        
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        $this->info("✅ Caches cleared");
    }

    /**
     * Show current configuration
     */
    private function showCurrentConfig()
    {
        $this->newLine();
        $this->info("📋 Current Configuration:");
        $this->line("Environment: " . config('app.env'));
        $this->line("Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF'));
        $this->line("App URL: " . config('app.url'));
        $this->line("Cache Driver: " . config('cache.default'));
        $this->line("Session Driver: " . config('session.driver'));
        $this->newLine();
        
        $this->info("🚀 To start the server:");
        $this->line("php artisan serve");
        $this->newLine();
        
        $this->info("🔍 To test tutor mode:");
        $this->line("Visit: " . config('app.url') . "/quiz/1/1/tutor?sources[]=Assuit&sources[]=Cairo");
    }
}
