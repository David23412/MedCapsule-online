<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Topic\Access\TopicAccessController as AccessController;
use App\Http\Controllers\Topic\Access\TopicSourceController;

/**
 * 🚀 PERFORMANCE BEAST: Topic Access Controller - Delegation Pattern
 * Delegates to specialized controllers for optimal performance
 * Lines: ~50 (well under 200 limit)
 */
class TopicAccessController extends Controller
{
    private AccessController $accessController;
    private TopicSourceController $sourceController;

    public function __construct()
    {
        $this->middleware(['auth']);
        $this->accessController = new AccessController();
        $this->sourceController = new TopicSourceController();
    }

    /**
     * Check if user has access to a topic - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function checkAccess(Topic $topic, ?User $user = null): array
    {
        return $this->accessController->checkAccess($topic, $user);
    }

    /**
     * Get topic sources for user - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function getTopicSources(Topic $topic, ?User $user = null): array
    {
        return $this->sourceController->getTopicSources($topic, $user);
    }

    /**
     * API endpoint for checking topic access - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function checkAccessApi(Request $request, int $topicId)
    {
        return $this->accessController->checkAccessApi($request, $topicId);
    }

    /**
     * API endpoint for getting topic sources - DELEGATES TO SPECIALIZED CONTROLLER
     */
    public function getSourcesApi(Request $request, int $topicId)
    {
        return $this->sourceController->getSourcesApi($request, $topicId);
    }
}