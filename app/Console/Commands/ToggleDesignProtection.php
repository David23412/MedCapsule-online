<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class ToggleDesignProtection extends Command
{
    protected $signature = 'design-protection {state : on, off, or status}';

    protected $description = 'Enable, disable, or inspect the browser design-protection deterrent';

    public function handle(): int
    {
        $state = strtolower((string) $this->argument('state'));

        return match ($state) {
            'on', 'enable', 'enabled', 'true', '1' => $this->setProtectionState(true),
            'off', 'disable', 'disabled', 'false', '0' => $this->setProtectionState(false),
            'status' => $this->showStatus(),
            default => $this->invalidState($state),
        };
    }

    private function setProtectionState(bool $enabled): int
    {
        $envPath = base_path('.env');

        if (! is_file($envPath) || ! is_readable($envPath) || ! is_writable($envPath)) {
            throw new RuntimeException('The .env file is missing or not writable.');
        }

        $contents = (string) file_get_contents($envPath);
        $line = 'DESIGN_PROTECTION_ENABLED='.($enabled ? 'true' : 'false');

        if (preg_match('/^DESIGN_PROTECTION_ENABLED=.*/m', $contents) === 1) {
            $updatedContents = (string) preg_replace('/^DESIGN_PROTECTION_ENABLED=.*/m', $line, $contents, 1);
        } else {
            $updatedContents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        if ($updatedContents === $contents) {
            $this->line('Design protection is already '.($enabled ? 'enabled' : 'disabled').'.');
        } else {
            file_put_contents($envPath, $updatedContents);
            $this->info('Design protection '.($enabled ? 'enabled' : 'disabled').' in .env.');
        }

        config(['features.design_protection_enabled' => $enabled]);
        Artisan::call('config:clear');
        $this->line(trim(Artisan::output()));

        return self::SUCCESS;
    }

    private function showStatus(): int
    {
        $current = config('features.design_protection_enabled', true);

        $this->line('Design protection is currently '.($current ? 'enabled' : 'disabled').'.');

        return self::SUCCESS;
    }

    private function invalidState(string $state): int
    {
        $this->error("Unsupported state [{$state}]. Use on, off, or status.");

        return self::INVALID;
    }
}
