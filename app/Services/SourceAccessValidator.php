<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use App\Models\Assignment;
use App\Http\Controllers\TopicAccessController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class SourceAccessValidator
{
    protected $topicAccessController;
    
    public function __construct(TopicAccessController $topicAccessController)
    {
        $this->topicAccessController = $topicAccessController;
    }
    
    /**
     * Validate if a user can access a specific source for a topic
     *
     * @param Topic $topic The topic to check
     * @param string $source The source to validate
     * @param User|null $user The user to check (defaults to authenticated user)
     * @return array Validation result with access status and reason
     */
    public function validateSourceAccess(Topic $topic, string $source, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [
                'can_access' => false,
                'reason' => 'Authentication required',
                'status_code' => Response::HTTP_UNAUTHORIZED
            ];
        }
        
        // Get access permissions for the topic
        $accessResult = $this->topicAccessController->checkAccess($topic, $user);
        
        if (!$accessResult['can_access']) {
            return $accessResult;
        }
        
        // Check if the source is available for this user
        $availableSources = $accessResult['sources'] ?? [];
        
        if (!in_array($source, $availableSources)) {
            return [
                'can_access' => false,
                'reason' => 'Source not available for this topic or restricted by assignment',
                'available_sources' => $availableSources,
                'status_code' => Response::HTTP_FORBIDDEN
            ];
        }
        
        // Check if there's an active assignment with source restrictions
        if (isset($accessResult['has_assignment']) && $accessResult['has_assignment']) {
            $assignment = Assignment::find($accessResult['assignment_id']);
            
            if ($assignment && $assignment->isSourceSpecific() && $assignment->assigned_source !== $source) {
                return [
                    'can_access' => false,
                    'reason' => 'This assignment requires you to complete a specific source',
                    'required_source' => $assignment->assigned_source,
                    'status_code' => Response::HTTP_FORBIDDEN
                ];
            }
        }
        
        // Access granted
        return [
            'can_access' => true,
            'status_code' => Response::HTTP_OK
        ];
    }
    
    /**
     * Validate if a user can access multiple sources for a topic
     *
     * @param Topic $topic The topic to check
     * @param array $sources The sources to validate
     * @param User|null $user The user to check (defaults to authenticated user)
     * @return array Validation results for each source
     */
    public function validateMultipleSources(Topic $topic, array $sources, ?User $user = null): array
    {
        $results = [];
        
        foreach ($sources as $source) {
            $results[$source] = $this->validateSourceAccess($topic, $source, $user);
        }
        
        return $results;
    }
    
    /**
     * Get all accessible sources for a user and topic
     *
     * @param Topic $topic The topic to check
     * @param User|null $user The user to check (defaults to authenticated user)
     * @return array List of accessible sources
     */
    public function getAccessibleSources(Topic $topic, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [];
        }
        
        $accessResult = $this->topicAccessController->checkAccess($topic, $user);
        
        if (!$accessResult['can_access']) {
            return [];
        }
        
        return $accessResult['sources'] ?? [];
    }
    
    /**
     * Check if a user has an active assignment for a topic
     *
     * @param Topic $topic The topic to check
     * @param User|null $user The user to check (defaults to authenticated user)
     * @return Assignment|null The active assignment or null
     */
    public function getActiveAssignment(Topic $topic, ?User $user = null): ?Assignment
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return null;
        }
        
        return Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', Assignment::STATUS_ACTIVE)
            ->first();
    }
    
    /**
     * Check if a source exists for a topic
     *
     * @param Topic $topic The topic to check
     * @param string $source The source to check
     * @return bool Whether the source exists
     */
    public function sourceExists(Topic $topic, string $source): bool
    {
        return \DB::table('questions')
            ->where('topic_id', $topic->id)
            ->where('source', $source)
            ->exists();
    }
}