<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseTransactionService;
use App\Services\SecureFileUploadService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use App\Services\FreeCourseSurveyService;

/**
 * COURSE SERVICE - ZERO FUNCTIONALITY BREAKAGE
 *
 * This service extracts all business logic from CourseController
 * while preserving 100% of existing functionality and API contracts.
 *
 * PRESERVED FUNCTIONALITY:
 * ✅ Course listing with enrollment status
 * ✅ Course display with enrollment checks
 * ✅ User enrollment with transaction safety
 * ✅ Course creation with file uploads
 * ✅ All existing error messages and responses
 * ✅ All existing cache invalidation patterns
 * ✅ All existing notification triggers
 */
class CourseService
{
    public function __construct(
        protected DatabaseTransactionService $transactionService,
        protected SecureFileUploadService $fileUploadService,
        protected PerformanceMonitorService $performanceMonitor,
        protected FreeCourseSurveyService $freeCourseSurveyService
    ) {}

    /**
     * GET COURSES FOR INDEX PAGE
     * Preserves exact functionality of CourseController@index
     */
    public function getCoursesForIndex(array $params): array
    {
        $operationId = $this->performanceMonitor->startOperation('course_index_service');

        try {
            $user = auth()->user();
            $isLoggedIn = auth()->check();
            $selectedYear = $params['academic_year'] ?? null;

            // Use existing optimized bulk cache logic (PRESERVED)
            $cacheVersion = Cache::get('courses_cache_version', 1);
            $cacheKey = "courses_metadata_" . ($user?->id ?? 'guest') . "_" . ($selectedYear ?? 'all') . "_v{$cacheVersion}";

            // For logged-in users, bypass cache explicitly to avoid driver-specific TTL=0 behavior.
            $courses = $isLoggedIn
                ? $this->buildCourseQuery($user?->id, true, $selectedYear)->get()
                : Cache::remember($cacheKey, 30, function () use ($selectedYear) {
                    return $this->buildCourseQuery(null, false, $selectedYear)->get();
                });

            // PRESERVE existing formatting logic exactly
            $this->formatCoursesForDisplay($courses, $user);

            // PRESERVE existing academic years filter
            $availableYears = Cache::remember('courses_available_years_v1', 3600, function () {
                return Course::distinct()->pluck('academic_year')->filter()->sort()->values();
            });

            $currentUserView = $user ? [
                'name' => $user->name,
                'is_admin' => $user->isAdmin(),
                'is_tutor' => $user->isTutor(),
                'study_streak_days' => (int) ($user->study_streak_days ?? 0),
                'profile_image_url' => $user->getProfileImageUrl(),
                'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                'total_correct_answers' => $user->getTotalCorrectAnswers(),
                'survey_completed' => $user->hasCompletedFreeCourseSurvey(),
                'free_courses_unlocked' => $user->hasUnlockedFreeCourses(),
            ] : null;

            $freeCoursesForSurveyUnlock = $this->freeCourseSurveyService->freeCourses()
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                ]);

            $freeCourseSurveyDraftPayload = null;
            if ($user) {
                $draft = $this->freeCourseSurveyService->draftFor($user);
                if ($draft) {
                    $freeCourseSurveyDraftPayload = [
                        'answers' => $draft->answers ?? [],
                        'current_step' => $draft->current_step,
                        'redirect_to' => $draft->redirect_to,
                    ];
                }
            }

            return [
                'success' => true,
                'data' => compact('courses', 'availableYears', 'selectedYear', 'currentUserView', 'freeCoursesForSurveyUnlock', 'freeCourseSurveyDraftPayload')
            ];

        } finally {
            $this->performanceMonitor->endOperation($operationId);
        }
    }

    /**
     * GET COURSE FOR SHOW PAGE
     * Preserves exact functionality of CourseController@show
     */
    public function getCourseForShow(int $courseId, ?User $user): array
    {
        $operationId = $this->performanceMonitor->startOperation('course_show_service');

        try {
            $course = Course::query()
                ->select('id')
                ->findOrFail($courseId);

            $isEnrolled = $this->determineEnrollmentStatus($courseId, $user);

            return [
                'success' => true,
                'data' => compact('course', 'isEnrolled')
            ];

        } finally {
            $this->performanceMonitor->endOperation($operationId);
        }
    }

    /**
     * ENROLL USER IN COURSE
     * Preserves exact functionality of CourseController@enroll
     */
    public function enrollUserInCourse(User $user, int $courseId, array $params): array
    {
        $operationId = $this->performanceMonitor->startOperation('course_enroll_service');

        try {
            return $this->transactionService->executeEnrollmentTransaction(function() use ($user, $courseId) {
                // Use existing enrollment logic with enhanced error handling
                $course = Course::findOrFail($courseId);

                // Check enrollment status using optimized cache (PRESERVED)
                $cacheKey = "user_course_optimization_data_{$user->id}";
                $userCourseData = Cache::get($cacheKey);

                // Handle case where cache might be null
                if ($userCourseData && isset($userCourseData['enrolled_course_ids']) &&
                    in_array($courseId, $userCourseData['enrolled_course_ids'])) {
                    throw new \Exception('User is already enrolled in this course');
                }

                $user->enrollInCourse(
                    $courseId,
                    $course->isFree() ? User::COURSE_ACCESS_PREVIEW : User::COURSE_ACCESS_FULL
                );

                // Clear existing caches (PRESERVED)
                $this->clearCourseCaches();

                // Send notification using existing service (PRESERVED)
                try {
                    if (class_exists(\App\Services\NotificationService::class)) {
                        app(\App\Services\NotificationService::class)->createCourseWelcomeNotification(
                            $user,
                            htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8') // XSS protection
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to send welcome notification', [
                        'user_id' => $user->id,
                        'course_id' => $courseId,
                        'error' => $e->getMessage()
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'You have been enrolled in the course successfully.'
                ];
            });

        } finally {
            $this->performanceMonitor->endOperation($operationId);
        }
    }

    /**
     * CREATE NEW COURSE
     * Preserves exact functionality of CourseController@addCourse
     */
    public function createCourse(array $data): array
    {
        $operationId = $this->performanceMonitor->startOperation('course_create_service');

        try {
            // PRESERVE existing validation logic exactly
            $validated = validator($data, [
                'name' => 'required|string|max:255|unique:courses,name',
                'description' => 'required|string|max:2000',
                'image_file' => 'nullable|file|max:5120',
                'image_url' => 'nullable|url|max:2048',
                'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'academic_year' => 'required|string|in:YEAR 1 - SEMESTER 1,YEAR 1 - SEMESTER 2,YEAR 2 - SEMESTER 1,YEAR 2 - SEMESTER 2,YEAR 3 - SEMESTER 1,YEAR 3 - SEMESTER 2,YEAR 4 - SEMESTER 1,YEAR 4 - SEMESTER 2,YEAR 5 - SEMESTER 1,YEAR 5 - SEMESTER 2,YEAR 6 - SEMESTER 1,YEAR 6 - SEMESTER 2,SAT,EST,ACT',
                'price' => 'nullable|numeric|min:0|max:999999.99'
            ], [
                'name.unique' => 'A course with this name already exists.',
                'image_file.max' => 'The image may not be greater than 5MB.',
                'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733).',
                'title_color.regex' => 'Title color must be a valid hex color code (e.g., #FF5733).',
                'academic_year.required' => 'Academic year is required.',
                'academic_year.in' => 'Please select a valid academic year.'
            ])->validated();

            // PRESERVE existing image handling logic
            $imageUrl = null;

            if (isset($data['image_file']) && $data['image_file']) {
                // Use existing secure file upload service
                try {
                    $uploadResult = $this->fileUploadService->processSecureUpload(
                        $data['image_file'],
                        'images',
                        'course-images'
                    );

                    $imageUrl = $uploadResult['storage_path'];

                    Log::info('Secure course image upload completed', [
                        'course_name' => $validated['name'],
                        'secure_filename' => $uploadResult['secure_name'],
                        'original_filename' => $uploadResult['original_name']
                    ]);

                } catch (\Exception $e) {
                    Log::error('Secure file upload failed for course image', [
                        'course_name' => $validated['name'],
                        'error' => $e->getMessage(),
                        'original_filename' => $data['image_file']->getClientOriginalName()
                    ]);

                    return [
                        'success' => false,
                        'message' => 'File upload failed security validation: ' . $e->getMessage()
                    ];
                }
            } elseif ($validated['image_url']) {
                // Use provided URL (validate it's an image URL)
                if ($this->isValidImageUrl($validated['image_url'])) {
                    $imageUrl = $validated['image_url'];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid image URL provided.'
                    ];
                }
            }

            // PRESERVE existing course creation logic
            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image' => $imageUrl,
                'color' => $validated['color'],
                'title_color' => $validated['title_color'],
                'academic_year' => $validated['academic_year'],
                'price' => $validated['price'] ?? null
            ]);

            // Increment cache version to invalidate all course caches (PRESERVED)
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400);

            Log::info('Course created successfully', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'admin_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => compact('course'),
                'message' => 'Course "' . $course->name . '" created successfully!'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'data' => $validated ?? $data
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create course. Please try again.'
            ];
        } finally {
            $this->performanceMonitor->endOperation($operationId);
        }
    }

    /**
     * BUILD COURSE QUERY
     * Preserves exact query building logic from CourseController
     * Enhanced with CourseFilterService functionality
     */
    private function buildCourseQuery(?int $userId, bool $isLoggedIn, ?string $selectedYear): \Illuminate\Database\Eloquent\Builder
    {
        $query = Course::query()->withCount([
            'topics as topics_count' => function ($query) {
                $query->where('name', 'NOT LIKE', '%cases%');
            },
            'topics as cases_count' => function ($query) {
                $query->where('name', 'LIKE', '%cases%');
            },
            'enrolledUsers as enrolled_users_count'
        ]);

        // Apply academic year filter using CourseFilterService logic
        if ($selectedYear && $this->isValidAcademicYear($selectedYear)) {
            $query = $this->filterByAcademicYear($query, $selectedYear);
        }

        return $query;
    }

    /**
     * ACADEMIC YEAR METHODS - INTEGRATED FROM CourseFilterService
     * Academic year mapping for display and filtering
     */
    private const ACADEMIC_YEARS = [
        'YEAR 1 - SEMESTER 1' => 'YEAR 1 - SEMESTER 1',
        'YEAR 1 - SEMESTER 2' => 'YEAR 1 - SEMESTER 2',
        'YEAR 2 - SEMESTER 1' => 'YEAR 2 - SEMESTER 1',
        'YEAR 2 - SEMESTER 2' => 'YEAR 2 - SEMESTER 2',
        'YEAR 3 - SEMESTER 1' => 'YEAR 3 - SEMESTER 1',
        'YEAR 3 - SEMESTER 2' => 'YEAR 3 - SEMESTER 2',
        'YEAR 4 - SEMESTER 1' => 'YEAR 4 - SEMESTER 1',
        'YEAR 4 - SEMESTER 2' => 'YEAR 4 - SEMESTER 2',
        'YEAR 5 - SEMESTER 1' => 'YEAR 5 - SEMESTER 1',
        'YEAR 5 - SEMESTER 2' => 'YEAR 5 - SEMESTER 2',
        'YEAR 6 - SEMESTER 1' => 'YEAR 6 - SEMESTER 1',
        'YEAR 6 - SEMESTER 2' => 'YEAR 6 - SEMESTER 2',
        'SAT' => 'SAT',
        'EST' => 'EST',
        'ACT' => 'ACT'
    ];

    /**
     * Get all available academic years from courses
     */
    public function getAvailableAcademicYears(): array
    {
        return Course::select('academic_year')
            ->distinct()
            ->whereNotNull('academic_year')
            ->pluck('academic_year')
            ->map(function ($year) {
                return [
                    'value' => $year,
                    'label' => self::ACADEMIC_YEARS[$year] ?? $year,
                    'short_label' => $this->getShortLabel($year)
                ];
            })
            ->sortBy('value')
            ->values()
            ->toArray();
    }

    /**
     * Filter courses by academic year
     */
    private function filterByAcademicYear(\Illuminate\Database\Eloquent\Builder $query, string $academicYear): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Get short label for academic year (for pills/badges)
     */
    private function getShortLabel(string $academicYear): string
    {
        $shortLabels = [
            'YEAR 1 - SEMESTER 1' => 'Y1S1',
            'YEAR 1 - SEMESTER 2' => 'Y1S2',
            'YEAR 2 - SEMESTER 1' => 'Y2S1',
            'YEAR 2 - SEMESTER 2' => 'Y2S2',
            'YEAR 3 - SEMESTER 1' => 'Y3S1',
            'YEAR 3 - SEMESTER 2' => 'Y3S2',
            'YEAR 4 - SEMESTER 1' => 'Y4S1',
            'YEAR 4 - SEMESTER 2' => 'Y4S2',
            'YEAR 5 - SEMESTER 1' => 'Y5S1',
            'YEAR 5 - SEMESTER 2' => 'Y5S2',
            'YEAR 6 - SEMESTER 1' => 'Y6S1',
            'YEAR 6 - SEMESTER 2' => 'Y6S2',
            'SAT' => 'SAT',
            'EST' => 'EST',
            'ACT' => 'ACT'
        ];

        return $shortLabels[$academicYear] ?? strtoupper($academicYear);
    }

    /**
     * Validate academic year value
     */
    private function isValidAcademicYear(string $academicYear): bool
    {
        return array_key_exists($academicYear, self::ACADEMIC_YEARS);
    }

    /**
     * Get full label for academic year
     */
    private function getFullLabel(string $academicYear): string
    {
        return self::ACADEMIC_YEARS[$academicYear] ?? $academicYear;
    }

    /**
     * FORMAT COURSES FOR DISPLAY
     * Preserves exact formatting logic from CourseController
     */
    private function formatCoursesForDisplay(EloquentCollection $courses, ?User $user): void
    {
        $previewLimit = 3;
        $previewUsersByCourse = $this->loadEnrolledPreviewData($courses, $previewLimit);

        if (!$user) {
            // For guest users, set default values (PRESERVED)
            foreach ($courses as $course) {
                $course->is_enrolled = false;
                $course->has_pending_payment = false;
                $course->completion_percentage = 0;
                $course->mastered_topics_count = 0;
                $course->is_free_course = $course->isFree();
                $course->requires_survey_unlock = false;
                $course->access_level = null;
                $course->is_preview_access = false;
                $course->preview_topics_count = null;

                // Format enrolled users for display (PRESERVED)
                $enrolledUsers = $previewUsersByCourse->get($course->id, collect());
                $course->enrolled_preview = $enrolledUsers->map(function($previewUser) {
                    return [
                        'name' => $previewUser['name'],
                        'profile_picture_url' => $previewUser['profile_picture_url'],
                        'initial' => strtoupper(substr($previewUser['name'], 0, 1))
                    ];
                });
                $course->has_more = ($course->enrolled_users_count ?? 0) > $previewLimit;
                $course->enrolled_users_count = $course->enrolled_users_count ?? 0;
            }
            return;
        }

        // Use existing optimized bulk cache (PRESERVED)
        $cacheKey = "user_course_optimization_data_{$user->id}";
        $userCourseData = Cache::remember($cacheKey, 300, function() use ($user) {
            $enrolledCourseIds = $user->courses()->pluck('courses.id')->toArray();

            return [
                'enrolled_course_ids' => $enrolledCourseIds,
                'course_access_levels' => $user->getCourseAccessLevels($enrolledCourseIds),
                'pending_payment_course_ids' => DB::table('payments')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'pending_verification'])
                    ->pluck('course_id')
                    ->map(static fn ($courseId) => (int) $courseId)
                    ->toArray(),
                'is_admin' => $user->isAdmin()
            ];
        });

        // Use cached data for instant lookups (PRESERVED)
        foreach ($courses as $course) {
            // PRESERVE existing enrollment logic exactly
            if ($userCourseData['is_admin']) {
                $course->is_enrolled = true;
                $course->access_level = User::COURSE_ACCESS_FULL;
            } else {
                $course->access_level = $userCourseData['course_access_levels'][$course->id] ?? null;
                $course->is_enrolled = $course->access_level !== null;
            }

            $course->is_free_course = $course->isFree();
            $course->requires_survey_unlock = $course->is_free_course && $user->requiresSurveyUnlockForCourse($course);
            $course->is_preview_access = $course->access_level === User::COURSE_ACCESS_PREVIEW;
            $course->preview_topics_count = $course->is_preview_access
                ? (int) config('free_course_survey.preview_topics_count', 5)
                : null;

            // PRESERVE existing payment check logic
            $course->has_pending_payment = in_array($course->id, $userCourseData['pending_payment_course_ids'] ?? []);

            // Default values (PRESERVED)
            $course->completion_percentage = 0;
            $course->mastered_topics_count = 0;

            // Format enrolled users for display (PRESERVED)
            $enrolledUsers = $previewUsersByCourse->get($course->id, collect());
            $course->enrolled_preview = $enrolledUsers->map(function($previewUser) {
                return [
                    'name' => htmlspecialchars($previewUser['name'], ENT_QUOTES, 'UTF-8'), // XSS protection
                    'profile_picture_url' => $previewUser['profile_picture_url'],
                    'initial' => strtoupper(substr($previewUser['name'], 0, 1))
                ];
            });
            $course->has_more = ($course->enrolled_users_count ?? 0) > $previewLimit;
            $course->enrolled_users_count = $course->enrolled_users_count ?? 0;
        }
    }

    private function loadEnrolledPreviewData(EloquentCollection $courses, int $perCourseLimit): SupportCollection
    {
        $courseIds = $courses->pluck('id')->filter()->values();
        if ($courseIds->isEmpty()) {
            return collect();
        }

        try {
            $placeholders = implode(', ', array_fill(0, $courseIds->count(), '?'));
            $limit = max(1, $perCourseLimit);

            $rows = collect(DB::select(
                "
                    SELECT preview.course_id, preview.user_id, preview.name, preview.profile_picture_url
                    FROM (
                        SELECT
                            deduped.course_id,
                            deduped.user_id,
                            deduped.name,
                            deduped.profile_picture_url,
                            ROW_NUMBER() OVER (
                                PARTITION BY deduped.course_id
                                ORDER BY deduped.updated_at DESC, deduped.enrollment_id DESC
                            ) as row_num
                        FROM (
                            SELECT
                                cu.course_id,
                                cu.user_id,
                                u.name,
                                u.profile_picture_url,
                                cu.updated_at,
                                cu.id as enrollment_id,
                                ROW_NUMBER() OVER (
                                    PARTITION BY cu.course_id, cu.user_id
                                    ORDER BY cu.updated_at DESC, cu.id DESC
                                ) as user_row_num
                            FROM course_user cu
                            INNER JOIN users u ON u.id = cu.user_id
                            WHERE cu.is_enrolled = 1
                            AND cu.course_id IN ({$placeholders})
                        ) deduped
                        WHERE deduped.user_row_num = 1
                    ) preview
                    WHERE preview.row_num <= {$limit}
                    ORDER BY preview.course_id, preview.row_num
                ",
                $courseIds->all()
            ));

            return $rows->groupBy('course_id')->map(function (SupportCollection $group) {
                return $group->map(function ($row) {
                    return [
                        'name' => $row->name,
                        'profile_picture_url' => $row->profile_picture_url,
                    ];
                })->values();
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to bulk load enrolled preview data', [
                'course_count' => $courseIds->count(),
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * DETERMINE ENROLLMENT STATUS
     * Preserves exact enrollment logic from CourseController
     */
    private function determineEnrollmentStatus(int $courseId, ?User $user): bool
    {
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        $cacheKey = "user_course_optimization_data_{$user->id}";
        $userCourseData = Cache::get($cacheKey);

        // Handle case where cache might be null
        if (!$userCourseData || !isset($userCourseData['enrolled_course_ids'])) {
            return false;
        }

        return in_array($courseId, $userCourseData['enrolled_course_ids']);
    }

    /**
     * CLEAR COURSE CACHES
     * Preserves exact cache clearing logic from CourseController
     */
    private function clearCourseCaches(): void
    {
        Cache::increment('courses_cache_version');
        Cache::forget('courses_available_years_v1');
        Cache::forget("user_course_optimization_data_" . auth()->id());
    }

    /**
     * VALIDATE IMAGE URL
     * Preserves exact validation logic from CourseController
     */
    private function isValidImageUrl(string $url): bool
    {
        try {
            // Parse URL to ensure it's valid
            $parsedUrl = parse_url($url);
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return false;
            }

            // Only allow HTTP and HTTPS
            if (!in_array($parsedUrl['scheme'], ['http', 'https'])) {
                return false;
            }

            // Check if URL has image extension
            $path = $parsedUrl['path'] ?? '';
            $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
            $hasImageExtension = false;

            foreach ($imageExtensions as $ext) {
                if (str_ends_with(strtolower($path), $ext)) {
                    $hasImageExtension = true;
                    break;
                }
            }

            return $hasImageExtension;

        } catch (\Exception $e) {
            Log::warning('Image URL validation failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
