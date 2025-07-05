<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EnvironmentService;
use Illuminate\Support\Facades\Config;

class EnvironmentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:status {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current environment status and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = EnvironmentService::getEnvironmentConfig();
        
        if ($this->option('json')) {
            $this->line(json_encode($config, JSON_PRETTY_PRINT));
            return;
        }
        
        $this->info('🌐 MedCapsule Environment Status');
        $this->line('');
        
        // Environment Detection
        $this->info('📍 Environment Detection:');
        $this->line("   Environment: {$config['environment']}");
        $this->line("   URL Scheme: {$config['url_scheme']}");
        $this->line("   Asset URL: {$config['asset_url']}");
        $this->line('');
        
        // Configuration Status
        $this->info('⚙️  Configuration Status:');
        $this->line("   APP_ENV: " . config('app.env'));
        $this->line("   APP_DEBUG: " . (config('app.debug') ? 'true' : 'false'));
        $this->line("   APP_URL: " . config('app.url'));
        $this->line("   Force HTTPS: " . ($config['force_https'] ? 'true' : 'false'));
        $this->line("   Secure Cookies: " . ($config['secure_cookies'] ? 'true' : 'false'));
        $this->line('');
        
        // Database Status
        $this->info('🗄️  Database Status:');
        $this->line("   Connection: " . config('database.default'));
        $this->line("   Driver: " . config('database.connections.' . config('database.default') . '.driver'));
        $this->line('');
        
        // Session Configuration
        $this->info('🍪 Session Configuration:');
        $this->line("   Driver: " . config('session.driver'));
        $this->line("   Secure: " . (config('session.secure') ? 'true' : 'false'));
        $this->line("   HTTP Only: " . (config('session.http_only') ? 'true' : 'false'));
        $this->line("   Same Site: " . config('session.same_site'));
        $this->line('');
        
        // Security Headers
        $this->info('🔒 Security Status:');
        $this->line("   HTTPS Redirect: " . ($config['force_https'] ? 'Enabled' : 'Disabled'));
        $this->line("   Secure Headers: " . ($config['force_https'] ? 'Enabled' : 'Disabled'));
        $this->line('');
        
        // Environment-specific recommendations
        $this->info('💡 Recommendations:');
        switch ($config['environment']) {
            case 'local':
                $this->line('   ✅ Local development environment configured correctly');
                $this->line('   📝 To switch to ngrok: ./setup-ngrok.sh');
                break;
            case 'ngrok':
                $this->line('   ✅ Ngrok/HTTPS environment configured correctly');
                $this->line('   📝 To switch back to local: ./setup-local.sh');
                break;
            case 'production':
                $this->line('   ✅ Production environment detected');
                $this->line('   ⚠️  Ensure all security measures are in place');
                break;
        }
        
        $this->line('');
        $this->info('🚀 Environment status check complete!');
    }
}