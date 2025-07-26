<?php

namespace App\Traits;

use Carbon\Carbon;

trait BulletproofTimezone
{
    /**
     * Parse datetime input safely without timezone conversion
     */
    protected function parseDateTimeSafely($datetime, $timezone = null)
    {
        $timezone = $timezone ?: config('app.timezone');
        
        if ($datetime instanceof Carbon) {
            return $datetime->setTimezone($timezone);
        }
        
        return Carbon::parse($datetime, $timezone);
    }
    
    /**
     * Create assignment with safe deadline handling
     */
    protected function createAssignmentSafely(array $data)
    {
        if (isset($data['deadline'])) {
            $data['deadline'] = $this->parseDateTimeSafely($data['deadline'])->format('Y-m-d H:i:s');
        }
        
        return \App\Models\Assignment::create($data);
    }
    
    /**
     * Validate that deadline makes sense (not timezone-converted)
     */
    protected function validateDeadlineTimezone($deadline, $referenceTime = null)
    {
        $referenceTime = $referenceTime ?: now();
        $deadlineCarbon = $this->parseDateTimeSafely($deadline);
        
        $hoursDiff = abs($referenceTime->diffInHours($deadlineCarbon));
        
        // If deadline is more than 24 hours different from reference, might be timezone issue
        if ($hoursDiff > 24) {
            throw new \InvalidArgumentException("Deadline appears to have timezone conversion issue. Hours difference: {$hoursDiff}");
        }
        
        return true;
    }
}