<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topic;

class DeleteTopic extends Command
{
    protected $signature = 'topic:delete {id}';
    protected $description = 'Delete a topic by ID';

    public function handle()
    {
        $topicId = $this->argument('id');
        
        try {
            $topic = Topic::findOrFail($topicId);
            $topicName = $topic->name;
            
            if ($this->confirm("Are you sure you want to delete topic: {$topicName}?")) {
                $topic->delete();
                $this->info("Topic '{$topicName}' has been deleted successfully.");
            } else {
                $this->info('Delete operation cancelled.');
            }
        } catch (\Exception $e) {
            $this->error("Error: Unable to delete topic. {$e->getMessage()}");
        }
    }
} 