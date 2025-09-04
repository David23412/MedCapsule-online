<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\ResponsiveService;

class ResponsiveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ResponsiveService::class, function ($app) {
            return new ResponsiveService($app['request']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives for responsive utilities
        $this->registerBladeDirectives();
        
        // Register view composers
        $this->registerViewComposers();
    }

    /**
     * Register custom Blade directives for responsive design
     */
    protected function registerBladeDirectives(): void
    {
        // @mobile directive - only render content on mobile devices
        Blade::if('mobile', function () {
            return app(ResponsiveService::class)->isMobile();
        });

        // @tablet directive - only render content on tablet devices
        Blade::if('tablet', function () {
            return app(ResponsiveService::class)->isTablet();
        });

        // @desktop directive - only render content on desktop devices
        Blade::if('desktop', function () {
            return app(ResponsiveService::class)->getDeviceType() === 'desktop';
        });

        // @touch directive - only render content on touch devices
        Blade::if('touch', function () {
            return app(ResponsiveService::class)->hasTouchCapability();
        });

        // @feature directive - check if a responsive feature is enabled
        Blade::if('feature', function ($feature) {
            return app(ResponsiveService::class)->isFeatureEnabled($feature);
        });

        // @responsive directive - generate responsive image
        Blade::directive('responsive', function ($expression) {
            return "<?php echo app(\App\Services\ResponsiveService::class)->generateResponsiveImage({$expression}); ?>";
        });

        // @breakpoint directive - add CSS classes for current breakpoint
        Blade::directive('breakpoint', function () {
            return "<?php echo implode(' ', app(\App\Services\ResponsiveService::class)->getDeviceCssClasses()); ?>";
        });

        // @touchTarget directive - ensure minimum touch target size
        Blade::directive('touchTarget', function ($expression) {
            $touchSize = app(ResponsiveService::class)->getTouchTargetSize()['minimum'];
            return "<?php echo 'style=\"min-height: {$touchSize}px; min-width: {$touchSize}px;\"'; ?>";
        });

        // @viewport directive - generate viewport meta tag
        Blade::directive('viewport', function () {
            return "<?php echo '<meta name=\"viewport\" content=\"' . app(\App\Services\ResponsiveService::class)->getViewportMetaContent() . '\">'; ?>";
        });

        // @lazyImage directive - generate lazy loading image
        Blade::directive('lazyImage', function ($expression) {
            return "<?php 
                \$responsiveService = app(\App\Services\ResponsiveService::class);
                if (\$responsiveService->isLazyLoadingEnabled()) {
                    echo '<img data-src=\"' . {$expression} . '\" src=\"' . \$responsiveService->getLazyLoadingPlaceholder() . '\" class=\"lazy-load\" loading=\"lazy\">';
                } else {
                    echo '<img src=\"' . {$expression} . '\">';
                }
            ?>";
        });
    }

    /**
     * Register view composers to share responsive data
     */
    protected function registerViewComposers(): void
    {
        view()->composer('*', function ($view) {
            $responsiveService = app(ResponsiveService::class);
            
            $view->with([
                'isMobile' => $responsiveService->isMobile(),
                'isTablet' => $responsiveService->isTablet(),
                'isDesktop' => $responsiveService->getDeviceType() === 'desktop',
                'hasTouch' => $responsiveService->hasTouchCapability(),
                'deviceType' => $responsiveService->getDeviceType(),
                'deviceClasses' => implode(' ', $responsiveService->getDeviceCssClasses()),
                'responsiveConfig' => $responsiveService->getJsConfig(),
            ]);
        });
    }
}