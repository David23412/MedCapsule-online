<?php

namespace App\Services\Audit\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages the current state of the audit execution
 * 
 * Tracks which phases have been completed, current phase execution,
 * and maintains audit session state across the execution pipeline.
 */
class AuditState
{
    private string $auditSessionId;
    private array $completedPhases = [];
    private ?string $currentPhase = null;
    private array $phaseResults = [];
    private array $auditMetadata = [];

    public function __construct(?string $sessionId = null)
    {
        $this->auditSessionId = $sessionId ?? $this->generateSessionId();
        $this->loadStateFromCache();
    }

    /**
     * Set the currently executing phase
     */
    public function setCurrentPhase(string $phase): void
    {
        $this->currentPhase = $phase;
        $this->auditMetadata['current_phase_start_time'] = microtime(true);
        $this->saveStateToCache();
        
        Log::info("Audit state: Current phase set to {$phase}", [
            'session_id' => $this->auditSessionId,
            'phase' => $phase
        ]);
    }

    /**
     * Get the currently executing phase
     */
    public function getCurrentPhase(): ?string
    {
        return $this->currentPhase;
    }

    /**
     * Mark a phase as completed
     */
    public function markPhaseCompleted(string $phase): void
    {
        if (!in_array($phase, $this->completedPhases)) {
            $this->completedPhases[] = $phase;
            
            // Record completion time
            $this->auditMetadata['phase_completion_times'][$phase] = microtime(true);
            
            if ($this->currentPhase === $phase) {
                $startTime = $this->auditMetadata['current_phase_start_time'] ?? microtime(true);
                $executionTime = microtime(true) - $startTime;
                $this->auditMetadata['phase_execution_times'][$phase] = $executionTime;
            }
            
            $this->saveStateToCache();
            
            Log::info("Audit state: Phase marked as completed", [
                'session_id' => $this->auditSessionId,
                'phase' => $phase,
                'total_completed' => count($this->completedPhases)
            ]);
        }
    }

    /**
     * Check if a phase has been completed
     */
    public function isPhaseCompleted(string $phase): bool
    {
        return in_array($phase, $this->completedPhases);
    }

    /**
     * Get all completed phases
     */
    public function getCompletedPhases(): array
    {
        return $this->completedPhases;
    }

    /**
     * Store results for a specific phase
     */
    public function setPhaseResult(string $phase, array $result): void
    {
        $this->phaseResults[$phase] = $result;
        $this->saveStateToCache();
        
        Log::debug("Audit state: Phase result stored", [
            'session_id' => $this->auditSessionId,
            'phase' => $phase,
            'result_keys' => array_keys($result)
        ]);
    }

    /**
     * Get results for a specific phase
     */
    public function getPhaseResult(string $phase): ?array
    {
        return $this->phaseResults[$phase] ?? null;
    }

    /**
     * Get all phase results
     */
    public function getAllPhaseResults(): array
    {
        return $this->phaseResults;
    }

    /**
     * Set audit metadata
     */
    public function setMetadata(string $key, $value): void
    {
        $this->auditMetadata[$key] = $value;
        $this->saveStateToCache();
    }

    /**
     * Get audit metadata
     */
    public function getMetadata(?string $key = null)
    {
        if ($key === null) {
            return $this->auditMetadata;
        }
        
        return $this->auditMetadata[$key] ?? null;
    }

    /**
     * Get the audit session ID
     */
    public function getSessionId(): string
    {
        return $this->auditSessionId;
    }

    /**
     * Reset the audit state (start fresh)
     */
    public function reset(): void
    {
        $this->completedPhases = [];
        $this->currentPhase = null;
        $this->phaseResults = [];
        $this->auditMetadata = [
            'reset_timestamp' => microtime(true),
            'session_id' => $this->auditSessionId
        ];
        
        $this->saveStateToCache();
        
        Log::info("Audit state reset", [
            'session_id' => $this->auditSessionId
        ]);
    }

    /**
     * Get audit execution summary
     */
    public function getExecutionSummary(): array
    {
        $currentTime = microtime(true);
        $startTime = $this->auditMetadata['audit_start_time'] ?? $currentTime;
        
        return [
            'session_id' => $this->auditSessionId,
            'current_phase' => $this->currentPhase,
            'completed_phases' => count($this->completedPhases),
            'completed_phase_list' => $this->completedPhases,
            'total_execution_time' => $currentTime - $startTime,
            'phase_execution_times' => $this->auditMetadata['phase_execution_times'] ?? [],
            'audit_start_time' => $startTime,
            'last_activity' => $currentTime
        ];
    }

    /**
     * Check if audit is in progress
     */
    public function isAuditInProgress(): bool
    {
        return $this->currentPhase !== null && !empty($this->completedPhases);
    }

    /**
     * Generate a unique session ID for this audit run
     */
    private function generateSessionId(): string
    {
        return 'audit_' . date('Y-m-d_H-i-s') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * Save current state to cache for persistence
     */
    private function saveStateToCache(): void
    {
        $cacheKey = "audit_state_{$this->auditSessionId}";
        $state = [
            'session_id' => $this->auditSessionId,
            'completed_phases' => $this->completedPhases,
            'current_phase' => $this->currentPhase,
            'phase_results' => $this->phaseResults,
            'audit_metadata' => $this->auditMetadata,
            'last_updated' => microtime(true)
        ];
        
        Cache::put($cacheKey, $state, now()->addHours(24));
    }

    /**
     * Load state from cache if it exists
     */
    private function loadStateFromCache(): void
    {
        $cacheKey = "audit_state_{$this->auditSessionId}";
        $state = Cache::get($cacheKey);
        
        if ($state) {
            $this->completedPhases = $state['completed_phases'] ?? [];
            $this->currentPhase = $state['current_phase'] ?? null;
            $this->phaseResults = $state['phase_results'] ?? [];
            $this->auditMetadata = $state['audit_metadata'] ?? [];
            
            Log::info("Audit state loaded from cache", [
                'session_id' => $this->auditSessionId,
                'completed_phases' => count($this->completedPhases)
            ]);
        } else {
            // Initialize metadata for new audit
            $this->auditMetadata = [
                'audit_start_time' => microtime(true),
                'session_id' => $this->auditSessionId,
                'phase_execution_times' => [],
                'phase_completion_times' => []
            ];
        }
    }

    /**
     * Clear cached state
     */
    public function clearCache(): void
    {
        $cacheKey = "audit_state_{$this->auditSessionId}";
        Cache::forget($cacheKey);
        
        Log::info("Audit state cache cleared", [
            'session_id' => $this->auditSessionId
        ]);
    }

    /**
     * Get current phase number
     */
    public function getPhaseNumber(): int
    {
        // Return the next phase number based on completed phases
        // If no phases completed, we're on phase 1
        // If 2 phases completed, we're on phase 3
        return count($this->completedPhases) + 1;
    }

    /**
     * Set current phase number (for tracking purposes)
     */
    public function setPhaseNumber(int $phaseNumber): void
    {
        $this->auditMetadata['current_phase_number'] = $phaseNumber;
        $this->saveStateToCache();
    }

    /**
     * Update progress tracking
     */
    public function updateProgress(int $currentPhase, int $totalPhases): void
    {
        $this->auditMetadata['current_phase_number'] = $currentPhase;
        $this->auditMetadata['total_phases'] = $totalPhases;
        $this->auditMetadata['progress_percentage'] = ($currentPhase / $totalPhases) * 100;
        $this->saveStateToCache();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        $totalPhases = 23; // Total number of phases - matches AuditOrchestrator
        $completedPhases = count($this->completedPhases);
        
        return $totalPhases > 0 ? round(($completedPhases / $totalPhases) * 100, 2) : 0.0;
    }

    /**
     * Export state as array
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->auditSessionId,
            'completed_phases' => $this->completedPhases,
            'current_phase' => $this->currentPhase,
            'phase_results' => $this->phaseResults,
            'audit_metadata' => $this->auditMetadata,
            'execution_summary' => $this->getExecutionSummary()
        ];
    }
}