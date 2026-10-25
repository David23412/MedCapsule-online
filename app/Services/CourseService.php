<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    private const CACHE_TTL_AVAILABLE_YEARS = 3600;   // 1 hour
    private const CACHE_TTL_VERSION = 86400;           // 1 day

    public function __construct(
        protected SecureFileUploadService $fileUploadService,
        protected PerformanceMonitorService $performanceMonitor,
        protected FreeCourseSurveyService $freeCourseSurveyService,
        protected StudentViewDataService $studentViewDataService
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
            $availableYears = Cache::remember('courses_available_years_v1', self::CACHE_TTL_AVAILABLE_YEARS, function () {
                return Course::distinct()->pluck('academic_year')->filter()->sort()->values();
            });

            $academicYearOptions = $availableYears
                ->map(fn ($year) => [
                    'value' => $year,
                    'label' => $this->getFullLabel($year),
                ])
                ->values();

            $preloadedImageUrls = $courses
                ->take(2)
                ->pluck('optimized_image_url')
                ->filter()
                ->values();

            $imageDomains = $courses
                ->pluck('image_url')
                ->map(fn ($url) => parse_url((string) $url, PHP_URL_HOST))
                ->filter()
                ->unique()
                ->take(2)
                ->values();

            $currentUserView = $user
                ? $this->studentViewDataService->buildCurrentUserView($user, [
                    'survey_completed' => $user->hasCompletedFreeCourseSurvey(),
                ])
                : null;

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
                'data' => compact(
                    'courses',
                    'availableYears',
                    'academicYearOptions',
                    'selectedYear',
                    'currentUserView',
                    'freeCoursesForSurveyUnlock',
                    'freeCourseSurveyDraftPayload',
                    'preloadedImageUrls',
                    'imageDomains'
                )
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
            return DB::transaction(function() use ($user, $courseId) {
                // Use existing enrollment logic with enhanced error handling
                $course = Course::findOrFail($courseId);

                // Check enrollment status using optimized cache (PRESERVED)
                $userCourseData = $this->getUserCourseOptimizationData($user);

                // Handle case where cache might be null
                if (($userCourseData['course_access_levels'][$courseId] ?? null) !== null) {
                    throw new \Exception('User is already enrolled in this course');
                }

                if (!$user->isAdmin()) {
                    if ($course->isSurveyUnlockable()) {
                        if ($user->requiresSurveyUnlockForCourse($course)) {
                            return [
                                'success' => false,
                                'message' => config('free_course_survey.description'),
                                'redirect_to' => $user->freeCourseSurveyRedirectFor($course),
                            ];
                        }
                    } else {
                        $hasCompletedPayment = DB::table('payments')
                            ->where('user_id', $user->id)
                            ->where('course_id', $course->id)
                            ->where('status', 'completed')
                            ->exists();

                        if (!$hasCompletedPayment) {
                            return [
                                'success' => false,
                                'message' => 'You need to purchase this course to access its content.',
                            ];
                        }
                    }
                }

                $user->enrollInCourse(
                    $courseId,
                    $course->defaultEnrollmentAccessLevel()
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
                'image_file' => 'nullable|file|max:51200',
                'image_url' => 'nullable|url|max:2048',
                'access_type' => 'required|string|in:free,preview,paid',
                'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'academic_year' => 'required|string|max:255',
                'price' => 'nullable|numeric|min:0|max:999999.99'
            ], [
                'name.unique' => 'A course with this name already exists.',
                'image_file.uploaded' => 'The image is larger than the server upload limit. Please use an image under 50MB.',
                'image_file.max' => 'The image must be under 50MB.',
                'access_type.required' => 'Choose how students should access this course.',
                'access_type.in' => 'Choose a valid access policy for this course.',
                'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733).',
                'title_color.regex' => 'Title color must be a valid hex color code (e.g., #FF5733).',
                'academic_year.required' => 'Academic year is required.',
                'academic_year.in' => 'Please select a valid academic year.'
            ])->validated();

            $validated['access_type'] = Course::normalizeAccessType($validated['access_type'] ?? null);
            $validated['price'] = Course::normalizePriceForAccessType(
                $validated['access_type'],
                $validated['price'] ?? null
            );

            if ($policyValidationMessage = Course::accessPolicyValidationMessage($validated['access_type'], $validated['price'])) {
                return [
                    'success' => false,
                    'message' => $policyValidationMessage,
                    'errors' => [
                        'price' => [$policyValidationMessage],
                    ],
                ];
            }

            // PRESERVE existing image handling logic
            $imageUrl = null;

            if (isset($data['image_file']) && $data['image_file']) {
                // Use existing secure file upload service
                try {
                    $uploadResult = $this->fileUploadService->processSecureUpload(
                        $data['image_file'],
                        'images',
                        'course-images',
                        'public'
                    );

                    $imageUrl = $uploadResult['storage_path'];


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
                'price' => $validated['price'] ?? null,
                'access_type' => $validated['access_type'],
            ]);

            // Increment cache version to invalidate all course caches (PRESERVED)
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, self::CACHE_TTL_VERSION);


            return [
                'success' => true,
                'data' => compact('course'),
                'message' => 'Course "' . $course->name . '" created successfully!'
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Please fix the highlighted course fields.',
                'errors' => $e->errors(),
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
     * UPDATE EXISTING COURSE
     */
    public function updateCourse(int $courseId, array $data): array
    {
        $operationId = $this->performanceMonitor->startOperation('course_update_service');

        try {
            $course = Course::findOrFail($courseId);

            $validated = validator($data, [
                'name' => 'required|string|max:255|unique:courses,name,' . $courseId,
                'description' => 'required|string|max:2000',
                'image_file' => 'nullable|file|max:51200',
                'image_url' => 'nullable|url|max:2048',
                'access_type' => 'required|string|in:free,preview,paid',
                'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
                'academic_year' => 'required|string|max:255',
                'price' => 'nullable|numeric|min:0|max:999999.99',
                'type' => 'required|string|in:medical,custom',
                'is_published' => 'required|boolean'
            ], [
                'name.unique' => 'A course with this name already exists.',
                'image_file.uploaded' => 'The image is larger than the server upload limit. Please use an image under 50MB.',
                'image_file.max' => 'The image must be under 50MB.',
                'color.regex' => 'Color must be a valid hex color code.',
                'title_color.regex' => 'Title color must be a valid hex color code.'
            ])->validated();

            $validated['access_type'] = Course::normalizeAccessType($validated['access_type'] ?? null);
            $validated['price'] = Course::normalizePriceForAccessType(
                $validated['access_type'],
                $validated['price'] ?? null
            );

            if ($policyValidationMessage = Course::accessPolicyValidationMessage($validated['access_type'], $validated['price'])) {
                return [
                    'success' => false,
                    'message' => $policyValidationMessage,
                    'errors' => ['price' => [$policyValidationMessage]],
                ];
            }

            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'color' => $validated['color'],
                'title_color' => $validated['title_color'],
                'academic_year' => $validated['academic_year'],
                'price' => $validated['price'] ?? null,
                'access_type' => $validated['access_type'],
                'type' => $validated['type'],
                'is_published' => $validated['is_published']
            ];

            if (isset($data['image_file']) && $data['image_file']) {
                try {
                    $uploadResult = $this->fileUploadService->processSecureUpload(
                        $data['image_file'],
                        'images',
                        'course-images',
                        'public'
                    );
                    $updateData['image'] = $uploadResult['storage_path'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'File upload failed: ' . $e->getMessage()];
                }
            } elseif (isset($validated['image_url']) && $validated['image_url']) {
                if ($this->isValidImageUrl($validated['image_url'])) {
                    $updateData['image'] = $validated['image_url'];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid image URL provided.',
                        'errors' => ['image_url' => ['Invalid image URL provided.']],
                    ];
                }
            }

            $originalAccessType = Course::normalizeAccessType($course->access_type);
            $newAccessType = Course::normalizeAccessType($updateData['access_type'] ?? null);

            DB::transaction(function () use ($course, $updateData, $originalAccessType, $newAccessType) {
                $course->updateQuietly($updateData);

                if ($originalAccessType !== Course::ACCESS_TYPE_PAID && $newAccessType === Course::ACCESS_TYPE_PAID) {
                    $this->revokeSurveyEnrollmentsForPaidCourse((int) $course->id);
                }
            });

            $course->refresh();
            $this->clearCourseCaches();
            $this->clearCourseSpecificCaches((int) $course->id);

            return [
                'success' => true,
                'data' => compact('course'),
                'message' => 'Course "' . $course->name . '" updated successfully!'
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Please fix the highlighted course fields.',
                'errors' => $e->errors(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
            ]);
            return ['success' => false, 'message' => 'Failed to update course.'];
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
            'topics as topics_count',
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

        foreach ($courses as $course) {
            $course->displayed_enrollment_count = $course->fake_enrolled_enabled
                ? max(0, (int) $course->fake_enrolled_count)
                : (int) ($course->enrolled_users_count ?? 0);
            $previewUsersByCourse->put($course->id, $previewUsersByCourse->get($course->id, collect())
                ->take(min($previewLimit, $course->displayed_enrollment_count)));
        }

        if (!$user) {
            // For guest users, set default values (PRESERVED)
            foreach ($courses as $course) {
                $course->is_enrolled = false;
                $course->has_pending_payment = false;
                $course->completion_percentage = 0;
                $course->mastered_topics_count = 0;
                $course->optimized_image_url = $this->optimizeCourseImageUrl($course->image_url);
                $course->is_free_course = $course->isSurveyUnlockable();
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
                $course->has_more = $course->displayed_enrollment_count > $course->enrolled_preview->count();
                $course->enrolled_users_count = $course->enrolled_users_count ?? 0;
            }
            return;
        }

        $completionPercentages = $this->loadCourseCompletionPercentages($courses, $user->id);

        // Include public previews on paid courses, including students with no enrollment row.
        $userCourseData = [
            'is_admin' => $user->isAdmin(),
            'course_access_levels' => $user->getCourseAccessLevels($courses->modelKeys()),
        ];
        // Payment status can change independently of cached enrollment metadata.
        $pendingPaymentCourseIds = DB::table('payments')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'pending_verification'])
            ->whereIn('course_id', $courses->pluck('id'))
            ->whereIn('id', function ($query) use ($user) {
                $query->selectRaw('MAX(id)')->from('payments')
                    ->where('user_id', $user->id)->groupBy('course_id');
            })
            ->pluck('course_id')->all();

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

            $course->is_free_course = $course->isSurveyUnlockable();
            $course->requires_survey_unlock = $user->requiresSurveyUnlockForCourse($course);
            $course->is_preview_access = $course->access_level === User::COURSE_ACCESS_PREVIEW;
            $course->preview_topics_count = $course->is_preview_access
                ? $course->getPreviewTopicLimit()
                : null;

            // PRESERVE existing payment check logic
            $course->has_pending_payment = in_array($course->id, $pendingPaymentCourseIds);

            $course->completion_percentage = $completionPercentages[$course->id] ?? 0;
            $course->mastered_topics_count = 0;
            $course->optimized_image_url = $this->optimizeCourseImageUrl($course->image_url);

            // Format enrolled users for display (PRESERVED)
            $enrolledUsers = $previewUsersByCourse->get($course->id, collect());
            $course->enrolled_preview = $enrolledUsers->map(function($previewUser) {
                return [
                    'name' => htmlspecialchars($previewUser['name'], ENT_QUOTES, 'UTF-8'), // XSS protection
                    'profile_picture_url' => $previewUser['profile_picture_url'],
                    'initial' => strtoupper(substr($previewUser['name'], 0, 1))
                ];
            });
            $course->has_more = $course->displayed_enrollment_count > $course->enrolled_preview->count();
            $course->enrolled_users_count = $course->enrolled_users_count ?? 0;
        }
    }

    private function optimizeCourseImageUrl(?string $imageUrl): ?string
    {
        if (!$imageUrl) {
            return $imageUrl;
        }

        $host = strtolower((string) parse_url($imageUrl, PHP_URL_HOST));
        if ($host === 'images.unsplash.com') {
            $parts = parse_url($imageUrl);
            parse_str($parts['query'] ?? '', $query);
            $query = array_replace($query, [
                'w' => 465, 'h' => 670, 'fit' => 'crop',
                'auto' => 'format,compress', 'q' => 75, 'fm' => 'webp',
            ]);
            $base = explode('?', explode('#', $imageUrl, 2)[0], 2)[0];
            return $base . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
                . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        }

        if ($host === 'picsum.photos') {
            return preg_replace('~/(?:\d+)(?:/\d+)?(?=\.(?:jpg|webp)(?:[?#]|$)|[?#]|$)~', '/465/670', $imageUrl) ?? $imageUrl;
        }

        return $imageUrl;
    }

    private function loadCourseCompletionPercentages(EloquentCollection $courses, int $userId): array
    {
        $courseIds = $courses->pluck('id')->filter()->values();
        if ($courseIds->isEmpty()) {
            return [];
        }

        $totalTopicsByCourse = DB::table('topics')
            ->select('course_id', DB::raw('COUNT(*) as total_topics'))
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->pluck('total_topics', 'course_id');

        $completedTopicsByCourse = DB::query()
            ->fromSub(function ($query) use ($userId, $courseIds) {
                $query->from('user_topic_progress')
                    ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
                    ->where('user_topic_progress.user_id', $userId)
                    ->whereIn('topics.course_id', $courseIds)
                    ->select(
                        'topics.course_id',
                        'topics.id as topic_id',
                        DB::raw('MAX(user_topic_progress.percentage_grade) as max_percentage')
                    )
                    ->groupBy('topics.course_id', 'topics.id');
            }, 'topic_progress')
            ->select(
                'course_id',
                DB::raw('SUM(CASE WHEN max_percentage >= 90 THEN 1 ELSE 0 END) as completed_topics')
            )
            ->groupBy('course_id')
            ->pluck('completed_topics', 'course_id');

        return $courseIds
            ->mapWithKeys(function ($courseId) use ($totalTopicsByCourse, $completedTopicsByCourse) {
                $totalTopics = (int) ($totalTopicsByCourse[$courseId] ?? 0);
                $completedTopics = (int) ($completedTopicsByCourse[$courseId] ?? 0);
                $percentage = $totalTopics > 0
                    ? (int) round(($completedTopics / $totalTopics) * 100)
                    : 0;

                return [$courseId => $percentage];
            })
            ->all();
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
                            AND u.show_on_leaderboard = 1
                            AND cu.course_id IN ({$placeholders})
                        ) deduped
                        WHERE deduped.user_row_num = 1
                    ) preview
                    WHERE preview.row_num <= ?
                    ORDER BY preview.course_id, preview.row_num
                ",
                array_merge($courseIds->all(), [$limit])
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

        return $user->getCourseAccessLevel($courseId) !== null;
    }

    /**
     * CLEAR COURSE CACHES
     * Preserves exact cache clearing logic from CourseController
     */
    private function clearCourseCaches(): void
    {
        Cache::increment('courses_cache_version');
        Cache::forget('courses_available_years_v1');

        if ($user = auth()->user()) {
            Cache::forget($this->userCourseOptimizationCacheKey($user));
        }
    }

    private function clearCourseSpecificCaches(int $courseId): void
    {
        Cache::forget("course_{$courseId}_enrolled_count");
        Cache::forget("course_{$courseId}_enrolled_students");
        Cache::forget("course_{$courseId}_enrolled_students_limit_3");
        Cache::forget('home_data_v4_bulletproof_2025');
        Cache::forget('home_statistics_v4_bulletproof_2025');
        Cache::forget('home_featured_courses_v4_bulletproof_2025');
        Cache::forget('home_health_check_v4_bulletproof_2025');
        Cache::forget('courses_list');
        Cache::forget('courses_with_stats');
    }

    private function revokeSurveyEnrollmentsForPaidCourse(int $courseId): void
    {
        $adminUserIds = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->pluck('id')
            ->all();

        DB::table('course_user')
            ->where('course_id', $courseId)
            ->where('is_enrolled', true)
            ->whereNotIn('user_id', $adminUserIds)
            ->update(['is_enrolled' => false, 'updated_at' => now()]);
    }

    private function getUserCourseOptimizationData(User $user): array
    {
        $cacheKey = $this->userCourseOptimizationCacheKey($user);
        $expectedSignature = $user->courseAccessStateSignature();
        $cachedData = Cache::get($cacheKey);

        if (
            is_array($cachedData)
            && ($cachedData['access_state_signature'] ?? null) === $expectedSignature
        ) {
            return $cachedData;
        }

        $freshData = $this->buildUserCourseOptimizationData($user, $expectedSignature);
        Cache::put($cacheKey, $freshData, 300);

        return $freshData;
    }

    private function buildUserCourseOptimizationData(User $user, string $accessStateSignature): array
    {
        $enrolledCourseIds = $user->courses()
            ->pluck('courses.id')
            ->map(static fn ($courseId) => (int) $courseId)
            ->all();

        return [
            'access_state_signature' => $accessStateSignature,
            'course_access_levels' => $user->getCourseAccessLevels($enrolledCourseIds),
            'is_admin' => $user->isAdmin(),
        ];
    }

    private function userCourseOptimizationCacheKey(User $user): string
    {
        return "user_course_optimization_data_{$user->id}";
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

            return true;

        } catch (\Exception $e) {
            Log::warning('Image URL validation failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
