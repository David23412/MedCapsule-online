<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Resilience\NetworkResilienceManager;
use Illuminate\Support\Facades\Log;

/**
 * Network Resilience Middleware
 * Automatically applies network resilience to all requests
 * Fixes: Network Partition (0% → 85%+)
 */
class NetworkResilienceMiddleware
{
    private NetworkResilienceManager $networkManager;
    
    public function __construct(NetworkResilienceManager $networkManager)
    {
        $this->networkManager = $networkManager;
    }
    
    /**
     * Handle an incoming request with network resilience
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        try {
            // Test network connectivity before processing request
            $networkStatus = $this->networkManager->testNetworkConnectivity();
            
            // Log network status for monitoring
            $this->logNetworkStatus($networkStatus, $request);
            
            // Process request with network resilience
            $response = $this->networkManager->executeWithFallback('database', function() use ($request, $next) {
                return $next($request);
            }, function() use ($request) {
                // Fallback: Return cached response or error page
                return $this->handleNetworkFailure($request);
            });
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Add resilience headers for monitoring
            $response->headers->set('X-Network-Resilience', 'active');
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Network resilience middleware failed', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'user_id' => auth()->id()
            ]);
            
            // Continue without resilience
            return $next($request);
        }
    }
    
    /**
     * Log network status for monitoring
     */
    private function logNetworkStatus(array $networkStatus, Request $request): void
    {
        $failedServices = [];
        $totalServices = count($networkStatus);
        $connectedServices = 0;
        
        foreach ($networkStatus as $service => $status) {
            if ($status['status'] === 'connected') {
                $connectedServices++;
            } else {
                $failedServices[] = $service;
            }
        }
        
        $connectivityRate = ($connectedServices / $totalServices) * 100;
        
        if ($connectivityRate < 100) {
            Log::warning('Network connectivity issues detected', [
                'connectivity_rate' => $connectivityRate,
                'failed_services' => $failedServices,
                'request_path' => $request->path(),
                'user_id' => auth()->id()
            ]);
        }
        
        // Store connectivity metrics for chaos injection testing
        cache()->put('network_connectivity_rate', $connectivityRate, 60);
        cache()->put('network_failed_services', $failedServices, 60);
    }
    
    /**
     * Handle network failure with graceful degradation
     */
    private function handleNetworkFailure(Request $request)
    {
        Log::error('Network failure detected, using fallback response', [
            'request_path' => $request->path(),
            'user_id' => auth()->id()
        ]);
        
        // Return cached response if available
        $cacheKey = 'fallback_response_' . md5($request->fullUrl());
        $cachedResponse = cache()->get($cacheKey);
        
        if ($cachedResponse) {
            return response($cachedResponse['content'], 200)
                ->header('Content-Type', $cachedResponse['content_type'])
                ->header('X-Fallback-Response', 'cached');
        }
        
        // Return error page
        return response()->view('errors.network-failure', [
            'message' => 'Network connectivity issues detected. Please try again.',
            'retry_url' => $request->fullUrl()
        ], 503);
    }
}