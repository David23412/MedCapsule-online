<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ResponsiveService
{
    protected $request;
    protected $config;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->config = Config::get('responsive');
    }

    /**
     * Detect if the current request is from a mobile device
     */
    public function isMobile(): bool
    {
        if (!$this->config['device_detection']['enabled']) {
            return false;
        }

        $cacheKey = 'device_detection_' . md5($this->getUserAgent());
        
        return Cache::remember($cacheKey, $this->config['device_detection']['cache_duration'], function () {
            $userAgent = $this->getUserAgent();
            $mobilePatterns = $this->config['device_detection']['user_agent_patterns']['mobile'];
            
            foreach ($mobilePatterns as $pattern) {
                if (stripos($userAgent, $pattern) !== false) {
                    return true;
                }
            }
            
            return false;
        });
    }

    /**
     * Detect if the current request is from a tablet device
     */
    public function isTablet(): bool
    {
        if (!$this->config['device_detection']['enabled']) {
            return false;
        }

        $cacheKey = 'tablet_detection_' . md5($this->getUserAgent());
        
        return Cache::remember($cacheKey, $this->config['device_detection']['cache_duration'], function () {
            $userAgent = $this->getUserAgent();
            $tabletPatterns = $this->config['device_detection']['user_agent_patterns']['tablet'];
            
            foreach ($tabletPatterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $userAgent)) {
                    return true;
                }
            }
            
            return false;
        });
    }

    /**
     * Detect if the device has touch capability
     */
    public function hasTouchCapability(): bool
    {
        return $this->isMobile() || $this->isTablet();
    }

    /**
     * Get the device type
     */
    public function getDeviceType(): string
    {
        if ($this->isMobile()) {
            return 'mobile';
        }
        
        if ($this->isTablet()) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    /**
     * Get the appropriate viewport size for the device
     */
    public function getViewportSize(): array
    {
        $deviceType = $this->getDeviceType();
        $breakpoints = $this->config['breakpoints'];
        
        switch ($deviceType) {
            case 'mobile':
                return [
                    'width' => $breakpoints['mobile'],
                    'max_width' => $breakpoints['mobile_large'] - 1
                ];
            case 'tablet':
                return [
                    'width' => $breakpoints['tablet'],
                    'max_width' => $breakpoints['desktop'] - 1
                ];
            default:
                return [
                    'width' => $breakpoints['desktop'],
                    'max_width' => null
                ];
        }
    }

    /**
     * Check if mobile optimizations should be applied
     */
    public function shouldOptimizeForMobile(): bool
    {
        return $this->isMobile() && $this->config['mobile_optimizations']['reduce_animations'];
    }

    /**
     * Get responsive image sizes for the current device
     */
    public function getResponsiveImageSizes(): array
    {
        $deviceType = $this->getDeviceType();
        $imageSizes = $this->config['images']['responsive_sizes'];
        
        switch ($deviceType) {
            case 'mobile':
                return [
                    'small' => $imageSizes['mobile'],
                    'medium' => $imageSizes['mobile_large'],
                    'large' => $imageSizes['tablet']
                ];
            case 'tablet':
                return [
                    'small' => $imageSizes['tablet'],
                    'medium' => $imageSizes['desktop'],
                    'large' => $imageSizes['desktop']
                ];
            default:
                return [
                    'small' => $imageSizes['desktop'],
                    'medium' => $imageSizes['desktop'],
                    'large' => $imageSizes['desktop']
                ];
        }
    }

    /**
     * Get image quality setting for the current device
     */
    public function getImageQuality(): int
    {
        $deviceType = $this->getDeviceType();
        $qualities = $this->config['images']['quality'];
        
        return $qualities[$deviceType] ?? $qualities['desktop'];
    }

    /**
     * Generate responsive image srcset attribute
     */
    public function generateImageSrcset(string $imagePath, array $sizes = null): string
    {
        if (!$sizes) {
            $sizes = $this->getResponsiveImageSizes();
        }
        
        $srcset = [];
        foreach ($sizes as $size) {
            $srcset[] = $this->generateResponsiveImageUrl($imagePath, $size) . " {$size}w";
        }
        
        return implode(', ', $srcset);
    }

    /**
     * Generate responsive image URL with size parameter
     */
    public function generateResponsiveImageUrl(string $imagePath, int $width): string
    {
        // This would integrate with your image processing service
        // For now, return the original path
        return $imagePath;
    }

    /**
     * Get CSS classes for the current device
     */
    public function getDeviceCssClasses(): array
    {
        $classes = [];
        $deviceType = $this->getDeviceType();
        
        $classes[] = "device-{$deviceType}";
        
        if ($this->hasTouchCapability()) {
            $classes[] = 'touch-device';
        } else {
            $classes[] = 'no-touch';
        }
        
        if ($this->shouldOptimizeForMobile()) {
            $classes[] = 'mobile-optimized';
        }
        
        return $classes;
    }

    /**
     * Get breakpoint information for JavaScript
     */
    public function getBreakpointsForJs(): array
    {
        return $this->config['breakpoints'];
    }

    /**
     * Check if a specific feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return $this->config['features'][$feature] ?? false;
    }

    /**
     * Get touch target size configuration
     */
    public function getTouchTargetSize(): array
    {
        return $this->config['touch_targets'];
    }

    /**
     * Check if lazy loading is enabled
     */
    public function isLazyLoadingEnabled(): bool
    {
        return $this->config['images']['lazy_loading']['enabled'] ?? false;
    }

    /**
     * Get lazy loading placeholder
     */
    public function getLazyLoadingPlaceholder(): string
    {
        return $this->config['images']['lazy_loading']['placeholder'] ?? '';
    }

    /**
     * Get performance settings for the current device
     */
    public function getPerformanceSettings(): array
    {
        $settings = $this->config['performance'];
        
        // Adjust settings based on device type
        if ($this->isMobile()) {
            $settings['minify_css'] = true;
            $settings['minify_js'] = true;
            $settings['defer_non_critical_css'] = true;
        }
        
        return $settings;
    }

    /**
     * Generate meta viewport tag content
     */
    public function getViewportMetaContent(): string
    {
        $content = 'width=device-width, initial-scale=1.0';
        
        // Add additional viewport settings for mobile
        if ($this->isMobile()) {
            $content .= ', viewport-fit=cover';
            
            // Prevent zoom on form inputs for better UX
            if ($this->config['mobile_optimizations']['optimize_fonts']) {
                $content .= ', user-scalable=no';
            }
        }
        
        return $content;
    }

    /**
     * Get critical CSS for the current page
     */
    public function getCriticalCss(string $page = 'default'): string
    {
        // This would return critical CSS based on the page and device type
        // For now, return empty string
        return '';
    }

    /**
     * Check if the user prefers reduced motion
     */
    public function prefersReducedMotion(): bool
    {
        // This would check the user's system preferences
        // For now, return the configuration setting
        return $this->config['accessibility']['respect_reduced_motion'];
    }

    /**
     * Get accessibility settings
     */
    public function getAccessibilitySettings(): array
    {
        return $this->config['accessibility'];
    }

    /**
     * Log device detection for debugging
     */
    public function logDeviceDetection(): void
    {
        if ($this->config['debug']['log_device_detection']) {
        }
    }

    /**
     * Get the user agent string
     */
    protected function getUserAgent(): string
    {
        return $this->request->header('User-Agent', '');
    }

    /**
     * Generate responsive configuration for JavaScript
     */
    public function getJsConfig(): array
    {
        return [
            'breakpoints' => $this->getBreakpointsForJs(),
            'deviceType' => $this->getDeviceType(),
            'isMobile' => $this->isMobile(),
            'isTablet' => $this->isTablet(),
            'hasTouchCapability' => $this->hasTouchCapability(),
            'touchTargetSize' => $this->getTouchTargetSize(),
            'features' => $this->config['features'],
            'lazyLoading' => $this->isLazyLoadingEnabled(),
            'prefersReducedMotion' => $this->prefersReducedMotion(),
        ];
    }
}