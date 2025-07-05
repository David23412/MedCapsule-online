<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class EnvironmentService
{
    private static $environmentCache = null;
    
    /**
     * Detect the current environment type
     */
    public static function detectEnvironment(Request $request = null): string
    {
        if (self::$environmentCache) {
            return self::$environmentCache;
        }
        
        $request = $request ?: request();
        $host = $request ? $request->getHost() : '';
        
        // Check for ngrok
        if (str_contains($host, 'ngrok') || str_contains($host, 'ngrok.io')) {
            self::$environmentCache = 'ngrok';
            return 'ngrok';
        }
        
        // Check for production domains
        if (str_contains($host, 'herokuapp.com') || 
            str_contains($host, 'vercel.app') || 
            str_contains($host, '.com') && !str_contains($host, 'localhost')) {
            self::$environmentCache = 'production';
            return 'production';
        }
        
        // Default to local
        self::$environmentCache = 'local';
        return 'local';
    }
    
    /**
     * Check if running in ngrok environment
     */
    public static function isNgrok(Request $request = null): bool
    {
        return self::detectEnvironment($request) === 'ngrok';
    }
    
    /**
     * Check if running in production environment
     */
    public static function isProduction(Request $request = null): bool
    {
        return self::detectEnvironment($request) === 'production';
    }
    
    /**
     * Check if running in local environment
     */
    public static function isLocal(Request $request = null): bool
    {
        return self::detectEnvironment($request) === 'local';
    }
    
    /**
     * Get the appropriate URL scheme
     */
    public static function getUrlScheme(Request $request = null): string
    {
        if (self::isLocal($request)) {
            return 'http';
        }
        
        return 'https';
    }
    
    /**
     * Get the appropriate asset URL
     */
    public static function getAssetUrl(Request $request = null): string
    {
        $scheme = self::getUrlScheme($request);
        $host = $request ? $request->getHost() : config('app.url');
        
        if (str_contains($host, 'http')) {
            return $host;
        }
        
        return $scheme . '://' . $host;
    }
    
    /**
     * Configure environment-specific settings
     */
    public static function configureEnvironment(Request $request = null): void
    {
        $environment = self::detectEnvironment($request);
        
        switch ($environment) {
            case 'ngrok':
                self::configureNgrokEnvironment($request);
                break;
            case 'production':
                self::configureProductionEnvironment($request);
                break;
            case 'local':
                self::configureLocalEnvironment($request);
                break;
        }
    }
    
    /**
     * Configure settings for ngrok
     */
    private static function configureNgrokEnvironment(Request $request = null): void
    {
        URL::forceScheme('https');
        
        // Set secure session settings
        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');
        
        // Set CSRF settings
        Config::set('app.debug', true); // Keep debug on for ngrok testing
        
        // Set asset URL
        if ($request) {
            $ngrokUrl = 'https://' . $request->getHost();
            Config::set('app.url', $ngrokUrl);
            Config::set('app.asset_url', $ngrokUrl);
        }
    }
    
    /**
     * Configure settings for production
     */
    private static function configureProductionEnvironment(Request $request = null): void
    {
        URL::forceScheme('https');
        
        // Set secure session settings
        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'strict');
        
        // Disable debug in production
        Config::set('app.debug', false);
        
        // Set secure headers
        Config::set('app.force_https', true);
    }
    
    /**
     * Configure settings for local development
     */
    private static function configureLocalEnvironment(Request $request = null): void
    {
        URL::forceScheme('http');
        
        // Set local session settings
        Config::set('session.secure', false);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');
        
        // Enable debug for local development
        Config::set('app.debug', true);
    }
    
    /**
     * Get environment-specific configuration
     */
    public static function getEnvironmentConfig(Request $request = null): array
    {
        $environment = self::detectEnvironment($request);
        
        return [
            'environment' => $environment,
            'is_local' => $environment === 'local',
            'is_ngrok' => $environment === 'ngrok',
            'is_production' => $environment === 'production',
            'url_scheme' => self::getUrlScheme($request),
            'asset_url' => self::getAssetUrl($request),
            'secure_cookies' => $environment !== 'local',
            'force_https' => $environment !== 'local'
        ];
    }
    
    /**
     * Clear environment cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$environmentCache = null;
    }
}