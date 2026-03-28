<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

// TODO: Inject services in constructor
    // TODO: Inject services in constructor
    // TODO: Inject services in constructor
    class TopicAccessController extends Controller
{
    public function __construct()
    {
        // Only call parent constructor if not in unit testing environment
        if (!app()->runningUnitTests()) {
            parent::__construct();
            $this->middleware(['auth']);
        }
    }

    /**
     * Check access permissions for a topic and user - LETHAL CACHED
     */
    public function checkAccess(Topic $topic, ?User $user = null): array
    {
        try {
            // Default to authenticated user if none provided with eager loading
            $user = $user ?? Auth::user();
            
            if (!$user) {
                return [
                    'can_access' => false,
                    'reason' => 'Authentication required',
                    'status_code' => Response::HTTP_UNAUTHORIZED
                ];
            }

            // LETHAL OPTIMIZATION: Ultra-aggressive caching for topic access
            $cacheKey = \App\Services\QuizUltraConsolidatedCacheService::generateCacheKey('topic_access', [
                'topic_id' => $topic->id,
                'user_id' => $user->id,
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            return Cache::remember($cacheKey, 3600, function () use ($topic, $user) {
                $topic = $this->loadTopicForAccess($topic->id);

                // Admin users always have access
                if ($user->isAdmin()) {
                    return [
                        'can_access' => true,
                        'is_admin' => true,
                        'sources' => $this->getAllTopicSources($topic),
                        'status_code' => Response::HTTP_OK
                    ];
                }
                
                // Check if topic is locked
                if ($topic->is_locked) {
                    return [
                        'can_access' => false,
                        'reason' => $topic->lock_reason ?? 'Topic is locked by tutor',
                        'locked_by' => $topic->lockedByTutor ? $topic->lockedByTutor->name : 'A tutor',
                        'locked_at' => $topic->locked_at ? $topic->locked_at->format('Y-m-d H:i:s') : null,
                        'status_code' => Response::HTTP_FORBIDDEN
                    ];
                }
                
                // Check if user is enrolled in the course
                $isEnrolled = $this->isUserEnrolledInTopicCourse($topic, $user);
                if (!$isEnrolled) {
                    return [
                        'can_access' => false,
                        'reason' => 'User is not enrolled in this course',
                        'status_code' => Response::HTTP_FORBIDDEN
                    ];
                }

                if ($user->isTopicPreviewLocked($topic)) {
                    return [
                        'can_access' => false,
                        'reason' => 'This topic is locked in preview mode. Upgrade to unlock the full course.',
                        'status_code' => Response::HTTP_FORBIDDEN
                    ];
                }
                
                [$activeAssignment, $inactiveAssignment] = $this->resolveTopicAssignments(
                    $this->getAssignmentsForTopicAndUser($topic, $user),
                    $user
                );

                if ($activeAssignment) {
                    // User has an active assignment for this topic
                    return [
                        'can_access' => true,
                        'has_assignment' => true,
                        'assignment_id' => $activeAssignment->id,
                        'assignment_mode' => $activeAssignment->assignment_mode,
                        'assignment_deadline' => $activeAssignment->deadline->format('Y-m-d H:i:s'),
                        'time_limit' => $activeAssignment->time_limit ?? $activeAssignment->duration_minutes,
                        'sources' => $this->getAssignmentSources($activeAssignment, $topic),
                        'status_code' => Response::HTTP_OK
                    ];
                }

                if ($inactiveAssignment) {
                    // User has an inactive assignment but can still access in practice mode
                    return [
                        'can_access' => true,
                        'has_inactive_assignment' => true,
                        'assignment_id' => $inactiveAssignment->id,
                        'practice_mode' => true,
                        'sources' => $this->getAllTopicSources($topic),
                        'status_code' => Response::HTTP_OK
                    ];
                }
                
                // Regular access - no assignments
                return [
                    'can_access' => true,
                    'practice_mode' => true,
                    'sources' => $this->getAllTopicSources($topic),
                    'status_code' => Response::HTTP_OK
                ];
            });
        } catch (\Exception $e) {
            Log::error('Topic access check failed', [
                'topic_id' => $topic->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'can_access' => false,
                'reason' => 'Access check failed',
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ];
        }
    }
    
    /**
     * Get topic sources based on user access mode
     *
     * @param Topic $topic The topic to get sources for
     * @param User|null $user The user to check access for (defaults to authenticated user)
     * @return array List of available sources
     */
    public function getTopicSources(Topic $topic, ?User $user = null): array
    {
        try {
            $user = $user ?? Auth::user();
            
            if (!$user) {
                return [];
            }
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
        
        $accessResult = $this->checkAccess($topic, $user);
        
        if (!$accessResult['can_access']) {
            return [];
        }
        
        return $accessResult['sources'] ?? $this->getAllTopicSources($topic);
    }
    
    /**
     * Get all sources available for a topic
     *
     * @param Topic $topic The topic to get sources for
     * @return array List of all sources
     */
    private function getAllTopicSources(Topic $topic): array
    {
        return \App\Models\Question::select('source')
            ->where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->pluck('source')
            ->toArray();
    }
    
    /**
     * Get sources available for an assignment
     *
     * @param Assignment $assignment The assignment to get sources for
     * @param Topic $topic The topic associated with the assignment
     * @return array List of available sources
     */
    private function getAssignmentSources(Assignment $assignment, Topic $topic): array
    {
        if ($assignment->isSourceSpecific()) {
            // For source-specific assignments, only return the assigned source
            return [$assignment->assigned_source];
        } else {
            // For complete-topic assignments, return all sources
            return $this->getAllTopicSources($topic);
        }
    }

    private function loadTopicForAccess(int $topicId): Topic
    {
        return Topic::query()
            ->select([
                'id',
                'course_id',
                'display_order',
                'cache_version',
                'is_locked',
                'locked_by_tutor_id',
                'lock_reason',
                'locked_at',
            ])
            ->with([
                'lockedByTutor:id,name',
            ])
            ->findOrFail($topicId);
    }

    private function isUserEnrolledInTopicCourse(Topic $topic, User $user): bool
    {
        return $user->hasAccessToCourse($topic->course_id);
    }

    private function getAssignmentsForTopicAndUser(Topic $topic, User $user)
    {
        return Assignment::query()
            ->select([
                'id',
                'topic_id',
                'deadline',
                'duration_minutes',
                'assignment_mode',
                'assigned_source',
                'submitted_at',
                'created_at',
            ])
            ->where('topic_id', $topic->id)
            ->whereExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('assignment_user')
                    ->whereColumn('assignment_user.assignment_id', 'assignments.id')
                    ->where('assignment_user.user_id', $user->id);
            })
            ->with([
                'attempts' => function ($query) use ($user) {
                    $query->select([
                        'id',
                        'assignment_id',
                        'user_id',
                        'score',
                        'total_questions',
                        'created_at',
                    ])->where('user_id', $user->id);
                }
            ])
            ->orderBy('deadline')
            ->get();
    }

    private function resolveTopicAssignments($assignments, User $user): array
    {
        $activeAssignment = null;
        $inactiveAssignment = null;

        foreach ($assignments as $assignment) {
            $status = $assignment->getStatusForUser($user);

            if ($status === Assignment::STATUS_ACTIVE && !$activeAssignment) {
                $activeAssignment = $assignment;
                continue;
            }

            if (
                !$inactiveAssignment &&
                in_array($status, [Assignment::STATUS_LOCKED, Assignment::STATUS_COMPLETED], true)
            ) {
                $inactiveAssignment = $assignment;
            }

            if ($activeAssignment && $inactiveAssignment) {
                break;
            }
        }

        return [$activeAssignment, $inactiveAssignment];
    }
    
    /**
     * Get active assignment for a topic and user
     *
     * @param Topic $topic The topic to check
     * @param User $user The user to check
     * @return Assignment|null The active assignment or null
     */
    private function getActiveAssignment(Topic $topic, User $user): ?Assignment
    {
        [$activeAssignment] = $this->resolveTopicAssignments(
            $this->getAssignmentsForTopicAndUser($topic, $user),
            $user
        );

        return $activeAssignment;
    }
    
    /**
     * Get inactive (locked or completed) assignment for a topic and user.
     *
     * @param Topic $topic The topic to check
     * @param User $user The user to check
     * @return Assignment|null The inactive assignment or null
     */
    private function getInactiveAssignment(Topic $topic, User $user): ?Assignment
    {
        [, $inactiveAssignment] = $this->resolveTopicAssignments(
            $this->getAssignmentsForTopicAndUser($topic, $user),
            $user
        );

        return $inactiveAssignment;
    }
    
    /**
     * API endpoint to check access for the current user
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAccessApi(Request $request, int $topicId)
    {
        try {

        $topic = Topic::query()->select('id')->findOrFail($topicId);
        $accessResult = $this->checkAccess($topic);
        
        return response()->json($accessResult, $accessResult['status_code']);
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}
    
    /**
     * API endpoint to get available sources for the current user
     *
     * @param Request $request
     * @param int $topicId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSourcesApi(Request $request, int $topicId)
    {
        try {
            $topic = Topic::query()->select('id')->findOrFail($topicId);
            $accessResult = $this->checkAccess($topic);
            
            if (!$accessResult['can_access']) {
                return response()->json([
                    'error' => $accessResult['reason'],
                    'sources' => []
                ], $accessResult['status_code']);
            }
            
            return response()->json([
                'sources' => $accessResult['sources'] ?? []
            ]);
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }
}
