<?php

namespace App\Services\Audit;

class CircuitBreakerService
{
    private $failures = [];
    private $threshold = 5;
    private $timeout = 60;
    
    public function call($service, $method, $params = [])
    {
        $key = $service . '::' . $method;
        
        if ($this->isCircuitOpen($key)) {
            throw new \Exception('Circuit breaker is open');
        }
        
        try {
            $result = call_user_func_array([$service, $method], $params);
            $this->recordSuccess($key);
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure($key);
            throw $e;
        }
    }
    
    private function isCircuitOpen($key)
    {
        if (!isset($this->failures[$key])) {
            return false;
        }
        
        $failure = $this->failures[$key];
        return $failure['count'] >= $this->threshold && 
               (time() - $failure['last_failure']) < $this->timeout;
    }
    
    private function recordFailure($key)
    {
        if (!isset($this->failures[$key])) {
            $this->failures[$key] = ['count' => 0, 'last_failure' => 0];
        }
        
        $this->failures[$key]['count']++;
        $this->failures[$key]['last_failure'] = time();
    }
    
    private function recordSuccess($key)
    {
        unset($this->failures[$key]);
    }
}