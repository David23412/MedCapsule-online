<?php

namespace App\Services\Audit;

class RetryService
{
    public static function retry($callback, $maxAttempts = 3, $delay = 1000)
    {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempts++;
                
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                
                app('performance.async')->delayAsync($delay  * $attempts); // Exponential backoff
            }
        }
    }
}