<?php

namespace App\Http\Controllers\Topic;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Controllers\Topic\Display\TopicDisplayController;
use App\Http\Controllers\Topic\Progress\TopicProgressController;

/**
 * 🚀 PERFORMANCE BEAST: Topic View Controller - Delegation Pattern
 * Delegates to specialized controllers for optimal performance
 * Lines: ~40 (well under 200 limit)
 */
class TopicViewController extends Controller
{
    private TopicDisplayController $displayController;
    private TopicProgressController $progressController;

    public function __construct()
    {
        $this->middleware(['auth']);
        $this->displayController = new TopicDisplayController();
        $this->progressController = new TopicProgressController();
    }

    /**
     * Show topics for a specific course - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function forCourse(Course $course)
    {
        return $this->displayController->forCourse($course);
    }

    /**
     * Get source progress for a topic - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function getSourceProgress(Topic $topic)
    {
        return $this->progressController->getSourceProgress($topic);
    }
}