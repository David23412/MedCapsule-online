<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class TopicService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getAllTopicsWithCourses(): Collection
    {
        try {
            $topics = Topic::with('course')
                ->orderBy('course_id')
                ->orderBy('display_order')
                ->get();
            
            $this->logger->info('Retrieved all topics with courses', ['count' => $topics->count()]);
            return $topics;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get topics with courses', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getTopicsByCourse(int $courseId): Collection
    {
        try {
            $topics = Topic::where('course_id', $courseId)
                ->orderBy('display_order')
                ->get();
            
            $this->logger->info('Retrieved topics for course', ['course_id' => $courseId, 'count' => $topics->count()]);
            return $topics;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get topics for course', ['course_id' => $courseId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getTopicById(int $id): Topic
    {
        try {
            $topic = Topic::with('course')->findOrFail($id);
            return $topic;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get topic by ID', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
