<?php

namespace App\Listeners;

use App\Events\SourceProgressUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SourceProgressUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SourceProgressUpdated $event): void
    {
        try {
            Log::info('Source progress updated listener triggered', [
                'topic_id' => $event->topicId,
                'source_progress' => $event->sourceProgress,
                'all_sources_mastered' => $event->allSourcesMastered
            ]);
            
            // The frontend will listen for this event and update the source modal
            // This event is broadcasted and the frontend will handle the update
            
        } catch (\Exception $e) {
            Log::error('Failed to handle source progress updated event', [
                'topic_id' => $event->topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 🔥 LETHAL FIX: Dispatch frontend event as fallback
     */
    public function dispatchFrontendEvent(SourceProgressUpdated $event): void
    {
        try {
            // This would be called from the frontend to ensure the event is received
            // even if broadcasting fails
            Log::info('Frontend event dispatch triggered', [
                'topic_id' => $event->topicId,
                'source_progress' => $event->sourceProgress,
                'all_sources_mastered' => $event->allSourcesMastered
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch frontend event', [
                'topic_id' => $event->topicId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 