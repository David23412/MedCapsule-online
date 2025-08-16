<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Topic\Locking\TopicLockingController as LockingController;
use App\Http\Controllers\Topic\Locking\BulkLockingController;
use App\Http\Controllers\Topic\Locking\LockingDisplayController;

/**
 * 🚀 PERFORMANCE BEAST: Topic Locking Controller - Delegation Pattern
 * Delegates to specialized controllers for optimal performance
 * Lines: ~80 (well under 200 limit)
 */
class TopicLockingController extends Controller
{
    private LockingController $lockingController;
    private BulkLockingController $bulkController;
    private LockingDisplayController $displayController;

    public function __construct()
    {
        $this->middleware(['auth']);
        $this->lockingController = new LockingController();
        $this->bulkController = new BulkLockingController();
        $this->displayController = new LockingDisplayController();
    }

    // Display methods
    public function index()
    {
        return $this->displayController->index();
    }

    public function courseTopics($courseId)
    {
        return $this->displayController->courseTopics($courseId);
    }

    public function getTopicStatus($topic)
    {
        return $this->displayController->getTopicStatus($topic);
    }

    public function checkStudentAccess($topic)
    {
        return $this->displayController->checkStudentAccess($topic);
    }

    public function getAccessibleTopics()
    {
        return $this->displayController->getAccessibleTopics();
    }

    public function getStatistics()
    {
        return $this->displayController->getStatistics();
    }

    public function getLockedTopics()
    {
        return $this->displayController->getLockedTopics();
    }

    // Individual locking methods
    public function lock($topicId)
    {
        return $this->lockingController->lock($topicId);
    }

    public function unlock($topicId)
    {
        return $this->lockingController->unlock($topicId);
    }

    public function toggle($topicId)
    {
        return $this->lockingController->toggle($topicId);
    }

    // Bulk locking methods
    public function bulkLock(Request $request)
    {
        return $this->bulkController->bulkLock($request);
    }

    public function bulkUnlock(Request $request)
    {
        return $this->bulkController->bulkUnlock($request);
    }

    public function lockCourse($courseId)
    {
        return $this->bulkController->lockCourse($courseId);
    }

    public function unlockCourse($courseId)
    {
        return $this->bulkController->unlockCourse($courseId);
    }
}