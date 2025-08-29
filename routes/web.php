<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    CourseController,
    TopicController,
    QuestionController,
    QuizController,
    MistakeController,
    LearnController,
    HomeController,
    UserCourseController,
    ProfileController,
    StudySessionController,
    NotificationController,
    App\Http\Controllers\Admin\PaymentController,
    PaymentSettingsController,
    PaymentLogController,
    DeleteController,
    AssignmentController,
    StudentAssignmentController,
    TopicLockingController
};
use App\Http\Controllers\Notification\NotificationViewController;
use Illuminate\Support\Facades\DB;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

    // Profile picture serving route (PUBLIC ACCESS)
    Route::get('/uploads/profile-pictures/{path}', function ($path) {
        $fullPath = storage_path('app/uploads/profile-pictures/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    })->where('path', '.*');



// Real-time progress update routes
Route::prefix('api/progress')->middleware('auth')->group(function () {
    Route::post('/store-update', [App\Http\Controllers\ProgressUpdateController::class, 'storeProgressUpdate'])->name('progress.store-update');
    Route::get('/recent-updates', [App\Http\Controllers\ProgressUpdateController::class, 'getRecentUpdates'])->name('progress.recent-updates');
    Route::post('/store-assignment', [App\Http\Controllers\ProgressUpdateController::class, 'storeAssignmentCompletion'])->name('progress.store-assignment');
});

// Real-time enrollment status check
Route::post('/api/check-enrollment-status', function(Request $request) {
    $courseIds = $request->input('course_ids', []);
    $userId = auth()->id();
    
    if (!$userId || empty($courseIds)) {
        return response()->json(['success' => false]);
    }
    
    // Get current enrollment status for all courses
    $enrollments = DB::table('course_user')
        ->where('user_id', $userId)
        ->whereIn('course_id', $courseIds)
        ->whereNotNull('enrolled_at')
        ->pluck('enrolled_at', 'course_id')
        ->toArray();
    
    // Check for changes (this would need session storage in real implementation)
    $changes = [];
    foreach ($courseIds as $courseId) {
        $isEnrolled = isset($enrollments[$courseId]);
        // For now, we'll return all statuses - in production you'd compare with previous state
        $changes[] = [
            'course_id' => $courseId,
            'is_enrolled' => $isEnrolled
        ];
    }
    
    return response()->json([
        'success' => true,
        'enrollment_changes' => $changes
    ]);
})->middleware('auth');

// Real-time payment status check
Route::post('/api/check-payment-status', function(Request $request) {
    $courseIds = $request->input('course_ids', []);
    $userId = auth()->id();
    
    if (!$userId || empty($courseIds)) {
        return response()->json(['success' => false]);
    }
    
    // Check which courses have completed payments
    $completedCourses = \App\Models\Payment::where('user_id', $userId)
        ->whereIn('course_id', $courseIds)
        ->where('status', 'completed')
        ->pluck('course_id')
        ->toArray();
    
    return response()->json([
        'success' => true,
        'completed_courses' => $completedCourses
    ]);
})->middleware('auth');

// Assignment progress API
Route::prefix('api/assignments')->middleware('auth')->group(function () {
    Route::get('/{assignment}/progress', [App\Http\Controllers\AssignmentController::class, 'getProgress'])->name('assignment.progress');
});

// Active users count API - Public endpoint for all users
Route::get('/api/active-users', function() {
    try {
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
        
        // Get count of unique visitors based on fingerprint
        $visitorCount = DB::table('sessions')
            ->where('last_activity', '>=', $fiveMinutesAgo)
            ->where('is_visitor', true)
            ->distinct('fingerprint')
            ->count();

        // Get count of unique logged-in users
        $loggedInCount = DB::table('sessions')
            ->where('last_activity', '>=', $fiveMinutesAgo)
            ->where('is_visitor', false)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count();

        return response()->json([
            'count' => $visitorCount + $loggedInCount,
            'visitors' => $visitorCount,
            'users' => $loggedInCount
        ]);
    } catch (\Exception $e) {
        // Return a default response instead of exposing the error
        return response()->json([
            'count' => 1,
            'visitors' => 0,
            'users' => 1
        ]);
    }
})->name('api.active-users');

// ADMIN ROUTES - Protected with admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/manage-access', [UserCourseController::class, 'index'])->name('admin.user-course');
    
    // Manage Access Routes - SECURE with CSRF protection
    Route::post('/manage-access/enroll', [UserCourseController::class, 'enroll'])->name('admin.user-course.enroll');
    Route::post('/manage-access/revoke', [UserCourseController::class, 'revoke'])->name('admin.user-course.revoke');
});
// Route removed - restore functionality is handled by revoke method (toggle)

// Auth routes
require __DIR__.'/auth.php';

// 🔒 INTEGRATE SECURITY HEADERS MIDDLEWARE - MEGA LETHAL PROTECTION
Route::middleware(['web', SecurityHeaders::class])->group(function () {

    // Protected routes
    Route::middleware('auth')->group(function () {
    // User progress route
    Route::get('/profile/progress', [ProfileController::class, 'getProgress'])->name('user.progress');
    
    // Course routes
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Review routes with CSRF and rate limiting
    Route::get('/review', [App\Http\Controllers\Quiz\QuizMistakeController::class, 'showMistakes'])->name('review.index')->withoutMiddleware('auth');
    Route::get('/review/learn', [LearnController::class, 'learnMistakes'])->name('review.learn');
    Route::post('/review/remove/{question_id}', [MistakeController::class, 'removeMistake'])
        ->name('review.remove');
    Route::post('/review/skip/{question_id}', [MistakeController::class, 'skipMistake'])
        ->name('review.skip');
    Route::post('/learn/review/{question_id}', [LearnController::class, 'reviewMistake'])
        ->name('learn.review');
    Route::post('/learn/save-time', [LearnController::class, 'saveStudyTime'])
        ->name('learn.save-time');
    
    // Admin routes - Protected with admin middleware
    Route::middleware('admin')->group(function () {
        Route::get('/add-topic', [TopicController::class, 'showAddTopicForm'])->name('add-topic.form');
        Route::post('/add-topic', [TopicController::class, 'addTopic'])->name('add-topic');
        Route::get('/add-course', [CourseController::class, 'showAddCourseForm'])->name('add-course.form');
        Route::post('/add-course', [CourseController::class, 'addCourse'])->name('add-course');
        Route::get('/add-question', [QuestionController::class, 'showAddQuestionForm'])->name('add-question.form');
        Route::post('/add-question', [QuestionController::class, 'storeQuestion'])->name('add-question');
    });

    

// Search Users Route - Auth only (not admin)
Route::get('/manage-access/search-users', [UserCourseController::class, 'searchUsers'])->name('admin.user-course.search')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Debug route to check authentication status
Route::get('/debug/auth-status', function() {
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user' => auth()->user(),
        'session' => session()->all(),
        'headers' => request()->headers->all(),
        'cookies' => request()->cookies->all()
    ]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// TEST ROUTE: Completely bypass all middleware for testing
Route::get('/test/search-users-bypass', function() {
    try {
        // Test the UserService directly without User model serialization
        $users = \DB::table('users')
            ->where('name', 'like', '%' . 'Dr' . '%')
            ->select('id', 'name', 'email', 'role')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\Authenticate::class]);

    // Manage Access Routes - Admin only
    Route::middleware('admin')->group(function () {

        // Video Management Routes
        Route::get('/admin/videos', [App\Http\Controllers\VideoController::class, 'index'])->name('admin.videos.index');
        Route::post('/admin/videos', [App\Http\Controllers\VideoController::class, 'store'])->name('admin.videos.store');
        Route::post('/admin/videos/{video}/toggle', [App\Http\Controllers\VideoController::class, 'toggle'])->name('admin.videos.toggle');
        Route::delete('/admin/videos/{video}', [App\Http\Controllers\VideoController::class, 'destroy'])->name('admin.videos.destroy');

        // API route for getting course topics
        Route::get('/api/courses/{course}/topics', function($courseId) {
            $topics = \App\Models\Topic::where('course_id', $courseId)
                ->select('id', 'name')
                ->orderBy('display_order')
                ->get();
            return response()->json($topics);
        });
        Route::get('/manage-access/user-courses/{userId}', [UserCourseController::class, 'userCourses'])->name('admin.user-course.user-courses');
        Route::post('/manage-access/toggle-admin', [UserCourseController::class, 'toggleAdmin'])->name('admin.user-course.toggle-admin');
        // TEMPORARY: Move payments route outside admin middleware group for testing
        // Route::get('/manage-access/payments', [UserCourseController::class, 'payments'])->name('admin.user-course.payments')->withoutMiddleware([\App\Http\Middleware\AdminMiddleware::class]);
        Route::get('/manage-access/enrollments', [UserCourseController::class, 'getEnrollments'])->name('admin.user-course.enrollments');
        Route::get('/manage-access/user-enrollments', [UserCourseController::class, 'getUserEnrollments'])->name('admin.user-course.user-enrollments');
        Route::post('/manage-access/payment-settings', [UserCourseController::class, 'updatePaymentSettings'])->name('admin.payment-settings.update');
    });

    // 🔍 DEBUG: Temporary test route to check admin status (remove after debugging)
    Route::get('/debug/admin-status', function() {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role_raw' => $user->role,
            'role_type' => gettype($user->role),
            'role_cast_to_int' => (int)($user->role ?? 0),
            'expected_admin_role' => \App\Models\User::ROLE_ADMIN,
            'is_admin_method' => $user->isAdmin(),
            'role_comparison' => ((int)($user->role ?? 0) === \App\Models\User::ROLE_ADMIN),
            'session_data' => session()->all(),
            'middleware_check' => true
        ]);
    })->name('debug.admin-status');

    // ✅ FIXED: Payments route with proper admin protection
    Route::get('/manage-access/payments', [UserCourseController::class, 'payments'])
        ->name('admin.user-course.payments')
        ->middleware(['auth', 'admin']);
    
    // Delete Management Routes
    Route::get('/admin/delete', [DeleteController::class, 'index'])->name('admin.delete.index');
    Route::get('/admin/delete/courses', [DeleteController::class, 'showCourses'])->name('admin.delete.courses');
    Route::get('/admin/delete/topics', [DeleteController::class, 'showTopics'])->name('admin.delete.topics');
    Route::get('/admin/delete/questions', [DeleteController::class, 'showQuestions'])->name('admin.delete.questions');
    Route::get('/admin/delete/users', [DeleteController::class, 'showUsers'])->name('admin.delete.users');
    
    // Delete Action Routes
    Route::delete('/admin/delete/course/{id}', [DeleteController::class, 'deleteCourse'])->name('admin.delete.course');
    Route::delete('/admin/delete/topic/{id}', [DeleteController::class, 'deleteTopic'])->name('admin.delete.topic');
    Route::delete('/admin/delete/question/{id}', [DeleteController::class, 'deleteQuestion'])->name('admin.delete.question');
    Route::delete('/admin/delete/user/{id}', [DeleteController::class, 'deleteUser'])->name('admin.delete.user');
    Route::post('/admin/delete/questions/bulk', [DeleteController::class, 'bulkDeleteQuestions'])->name('admin.delete.questions.bulk');
    
    // Delete Preview API
    Route::get('/admin/delete/preview', [DeleteController::class, 'getDeletionPreview'])->name('admin.delete.preview');
    
    // Course enrollment and topic routes
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    
    // Handle legacy/invalid course IDs by redirecting to available course
    Route::get('/topics/{courseId}', function($courseId) {
        $courseId = (int) $courseId;
        
        // Check if course exists
        if (!\App\Models\Course::find($courseId)) {
            // Redirect to first available course
            $firstCourse = \App\Models\Course::first();
            if ($firstCourse) {
                return redirect("/topics/{$firstCourse->id}")
                    ->with('info', "Course {$courseId} not found. Redirected to available course.");
            }
            abort(404, "No courses available");
        }
        
        // Course exists, proceed normally
        return app(TopicController::class)->forCourse($courseId);
    })->name('topics.forCourse')->where('courseId', '[0-9]+');
    Route::get('/topics/course/{courseId}', [TopicController::class, 'forCourse'])->name('topics.forCourseLegacy')->where('courseId', '[0-9]+');
    Route::get('/courses/{course}/cases', [TopicController::class, 'casesForCourse'])->name('cases.forCourse');
    Route::get('/courses/{course}/cases-count', [TopicController::class, 'casesCount'])->name('cases.count');

    // 💀 NUCLEAR OPTIMIZED QUIZ ROUTES - SPLIT ARCHITECTURE 💀
    Route::prefix('quiz')->name('quiz.')->group(function () {
        // Quiz Execution - REFACTORED ARCHITECTURE
        Route::get('/{course}/{topic}', [App\Http\Controllers\Quiz\QuizReadController::class, 'show'])
            ->name('start')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
        Route::post('/{course}/{topic}/grade', [App\Http\Controllers\Quiz\QuizGradingController::class, 'submit'])
            ->name('grade')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            // THROTTLE REMOVED FOR F16 RAPTOR SPEED
            
        // Tutor Mode
        Route::get('/{course}/{topic}/tutor', [App\Http\Controllers\Quiz\QuizTutorController::class, 'startTutorQuiz'])
            ->name('tutor')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
        Route::post('/tutor/check/{question}', [App\Http\Controllers\Quiz\QuizTutorController::class, 'checkTutorAnswer'])
            ->name('tutor.check-answer')
            ->where(['question' => '[0-9]+'])
            ->withoutMiddleware(['web']); // BYPASS ALL MIDDLEWARE FOR SPEED
        Route::post('/tutor/complete-session', [App\Http\Controllers\Quiz\QuizTutorController::class, 'completeTutorSession'])
            ->name('tutor.complete-session');
    });

    // Random Quiz Routes with CSRF and rate limiting - OPTIMIZED VERSION
    Route::get('/quiz/random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'showRandomQuiz'])
        ->name('quiz.random.show');
    Route::post('/quiz/random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'generateRandomQuiz'])
        ->name('quiz.random');
    Route::post('/quiz/grade-random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'gradeRandomQuiz'])
        ->name('quiz.grade-random');





    // Optimized Random Quiz Routes - CRITICAL OPTIMIZATION
    Route::prefix('random-quiz')->name('random-quiz.')->group(function () {
        Route::post('/generate', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'generateRandomQuiz'])
            ->name('generate')
            ->middleware(['throttle:random-quiz,10,1']); // 10 random quiz generations per minute
        Route::post('/grade', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'gradeRandomQuiz'])
            ->name('grade')
            ->middleware(['throttle:random-quiz-grading,30,1']); // 30 random quiz gradings per minute
        Route::get('/stats', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'getRandomQuizStats'])
            ->name('stats')
            ->middleware(['throttle:30,1']); // 30 stats requests per minute
    });

        // Profile routes
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile/update-picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update-picture');
    });

    // Profile picture serving route (public access for uploaded images)
    Route::get('/uploads/profile-pictures/{path}', function ($path) {
        $fullPath = storage_path('app/uploads/profile-pictures/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        ]);
    })->where('path', '.*')->withoutMiddleware(['web', \App\Http\Middleware\SecurityHeaders::class]);

    // Dashboard redirect
    Route::get('/dashboard', function () {
        return redirect('/courses');
    })->name('dashboard');

    // Payment Routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/{course}/options', [App\Http\Controllers\Admin\PaymentController::class, 'getPaymentOptions'])->name('options');
        Route::post('/{course}/process', [App\Http\Controllers\Admin\PaymentController::class, 'processManualPayment'])->name('process');
    });

    // Admin Payment Routes
    Route::prefix('admin/payments')->name('admin.payments.')->middleware(['auth'])->group(function () {
        Route::get('/history', [App\Http\Controllers\Admin\PaymentController::class, 'adminPaymentHistory'])->name('history')->middleware('admin');
        Route::get('/details/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'adminPaymentDetails'])->name('details')->middleware('admin');
        Route::post('/accept/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'acceptPayment'])->name('accept')->middleware('admin');
        Route::post('/reject/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'rejectPayment'])->name('reject')->middleware('admin');
        Route::post('/verify/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'acceptPayment'])->name('verify')->middleware('admin');
    });



    // Source progress endpoint
    Route::get('/topics/{topic}/source-progress', [TopicController::class, 'getSourceProgress'])
        ->name('topics.source-progress')
        ->middleware('auth');

    // Video page route - NO CSP RESTRICTIONS for YouTube functionality
    Route::get('/video/{topic}', [TopicController::class, 'showVideo'])->name('video.show')->middleware('auth')->withoutMiddleware([
        \App\Http\Middleware\ContentSecurityPolicy::class,
        \App\Http\Middleware\SecurityHeaders::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class
    ]);
    
    // Video progress routes
    Route::post('/api/video/progress', [App\Http\Controllers\VideoProgressController::class, 'updateProgress'])->name('video.progress.update')->middleware('auth');
    Route::get('/api/video/progress/{topic}', [App\Http\Controllers\VideoProgressController::class, 'getProgress'])->name('video.progress.get')->middleware('auth');

    // Video access endpoint
    Route::get('/video-access/{video}', 'App\Http\Controllers\VideoController@getVideoAccess')
        ->name('video.access')
        ->middleware('auth');

    // Assignment Management Routes (Admin & Tutor)
    Route::middleware(['auth', 'tutor'])->prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');

        Route::get('/create', [AssignmentController::class, 'bulkCreate'])->name('create');
        Route::post('/store', [AssignmentController::class, 'bulkStore'])->name('store');
        Route::get('/{assignment}', [AssignmentController::class, 'show'])->name('show');
        Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
        Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');
        Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
        
        // AJAX endpoints for assignment creation
        Route::get('/courses/{course}/topics', [AssignmentController::class, 'getTopicsForCourse'])->name('course.topics');
        Route::get('/courses/{course}/students', [AssignmentController::class, 'getStudentsForCourse'])->name('course.students');
        Route::get('/topics/{topicId}/sources', function($topicId) {
            try {
                \Log::info('Loading sources for topic ID: ' . $topicId);
                $sources = \DB::table('questions')
                    ->where('topic_id', $topicId)
                    ->select('source')
                    ->distinct()
                    ->whereNotNull('source')
                    ->where('source', '!=', '')
                    ->pluck('source')
                    ->toArray();
                
                return response()->json($sources);
            } catch (\Exception $e) {
                \Log::error('Error fetching topic sources: ' . $e->getMessage(), [
                    'topic_id' => $topicId,
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([], 500);
            }
        })->name('topic.sources');
        

        
        // AJAX endpoints
        Route::post('/{assignment}/extend-deadline', [AssignmentController::class, 'extendDeadline'])
            ->name('extend-deadline')
            ->middleware(['throttle:10,1']);
        Route::post('/{assignment}/mark-completed', [AssignmentController::class, 'markCompleted'])
            ->name('mark-completed')
            ->middleware(['throttle:30,1']);
        Route::get('/counter/data', [AssignmentController::class, 'getCounterData'])
            ->name('counter.data');
        Route::get('/courses/{course}/topics', [AssignmentController::class, 'getTopicsForCourse'])
            ->name('course.topics');
        Route::get('/courses/{course}/students', [AssignmentController::class, 'getStudentsForCourse'])
            ->name('course.students');
        Route::post('/update-overdue', [AssignmentController::class, 'updateOverdue'])
            ->name('update-overdue')
            ->middleware(['throttle:5,1']);
        Route::post('/enforce-real-time-status', [AssignmentController::class, 'enforceRealTimeStatus'])
            ->name('enforce-real-time-status')
            ->middleware(['throttle:60,1']); // Allow more frequent calls for real-time updates
    });

    // Student Assignment Routes
    Route::middleware(['auth'])->prefix('student/assignments')->name('student.assignments.')->group(function () {
        Route::get('/', [StudentAssignmentController::class, 'index'])->name('index');
        Route::get('/{assignment}', [StudentAssignmentController::class, 'show'])->name('show');
        Route::get('/history/completed', [StudentAssignmentController::class, 'history'])->name('history');
        Route::post('/{assignment}/start', [StudentAssignmentController::class, 'start'])->name('start');
        
        // AJAX endpoints
        Route::post('/{assignment}/complete', [StudentAssignmentController::class, 'complete'])
            ->name('complete')
            ->middleware(['throttle:30,1']);
        Route::get('/{assignment}/countdown', [StudentAssignmentController::class, 'getCountdownData'])
            ->name('countdown');
        Route::get('/stats/performance', [StudentAssignmentController::class, 'getStats'])
            ->name('stats');
        Route::get('/course/{course_id}/assignments', [StudentAssignmentController::class, 'getAssignmentsForCourse'])
            ->name('course.assignments');
        Route::get('/widget/progress', [StudentAssignmentController::class, 'getProgressWidget'])
            ->name('widget.progress');
        Route::post('/{assignment}/request-extension', [StudentAssignmentController::class, 'requestExtension'])
            ->name('request-extension')
            ->middleware(['throttle:5,1']);
    });

    // Topic Locking Routes (Admin & Tutor)
    Route::middleware(['auth', 'tutor'])->prefix('topic-locking')->name('topic-locking.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TopicLocking\TopicLockingViewController::class, 'index'])->name('index');
        Route::get('/courses/{course}', [\App\Http\Controllers\TopicLocking\TopicLockingViewController::class, 'courseTopics'])->name('course');
        
        // Individual topic locking
        Route::post('/{topicId}/lock', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'lock'])
            ->name('lock')
            ->middleware(['throttle:30,1']);
        Route::post('/{topicId}/unlock', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'unlock'])
            ->name('unlock')
            ->middleware(['throttle:30,1']);
        Route::post('/topics/{topic}/toggle', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'toggle'])
            ->name('toggle')
            ->middleware(['throttle:30,1']);
        
        // Bulk operations
        Route::post('/bulk-lock', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'bulkLock'])
            ->name('bulk-lock')
            ->middleware(['throttle:10,1']);
        Route::post('/bulk-unlock', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'bulkUnlock'])
            ->name('bulk-unlock')
            ->middleware(['throttle:10,1']);
        
        // Course-wide operations
        Route::post('/courses/{course}/lock-all', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'lockCourse'])
            ->name('course.lock')
            ->middleware(['throttle:5,1']);
        Route::post('/courses/{course}/unlock-all', [\App\Http\Controllers\TopicLocking\TopicLockingActionController::class, 'unlockCourse'])
            ->name('course.unlock')
            ->middleware(['throttle:5,1']);
        
        // AJAX endpoints
        Route::get('/topics/{topic}/status', [\App\Http\Controllers\TopicLocking\TopicLockingApiController::class, 'getTopicStatus'])
            ->name('topic.status');
        Route::get('/topics/{topic}/student-access', [\App\Http\Controllers\TopicLocking\TopicLockingApiController::class, 'checkStudentAccess'])
            ->name('student.access');
        Route::get('/students/accessible-topics', [\App\Http\Controllers\TopicLocking\TopicLockingApiController::class, 'getAccessibleTopics'])
            ->name('accessible.topics');
        Route::get('/statistics', [\App\Http\Controllers\TopicLocking\TopicLockingApiController::class, 'getStatistics'])
            ->name('statistics');
        Route::get('/locked-topics', [\App\Http\Controllers\TopicLocking\TopicLockingApiController::class, 'getLockedTopics'])
            ->name('locked.topics');
    });
    });
});

// Notification routes - simplified implementation
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationViewController::class, 'index'])->name('notifications.index');
    // Additional notification routes can be added here if needed
});

// Keep this for backward compatibility, but we've moved the routes to standard auth middleware
Route::middleware('auth:sanctum')->group(function () {
    // API routes that require Sanctum auth would go here
});

// Healthcheck route for monitoring
Route::get('/health', function() {
    return response()->json(['status' => 'ok'], 200);
});

// DEBUG: Test CSP headers
Route::get('/test-csp', function() {
    $response = response()->json(['csp_test' => 'ok']);
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; " .
           "style-src 'self' 'unsafe-inline'; " .
           "img-src 'self' data: https: blob:; " .
           "font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; " .
           "frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com; " .
           "connect-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com https://www.googleapis.com https://fonts.googleapis.com https://fonts.gstatic.com;";
    $response->headers->set('Content-Security-Policy', $csp);
    return $response;
});

// Test route for debugging search-users endpoint
Route::get('/test-search-users', function() {
    try {
        $userService = app(\App\Services\UserService::class);
        $result = $userService->searchUsers('Dr');
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});



// Admin Tutor Management Routes (Admin & Tutor Access)
Route::middleware(['auth', 'tutor'])->prefix('admin/tutors')->name('admin.tutors.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\TutorManagementController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\TutorManagementController::class, 'createTutor'])->name('create');
    Route::post('/store', [App\Http\Controllers\Admin\TutorManagementController::class, 'storeTutor'])->name('store');
    Route::get('/{tutor}/assign-students', [App\Http\Controllers\Admin\TutorManagementController::class, 'assignStudents'])->name('assign-students');
    Route::post('/{tutor}/process-assignment', [App\Http\Controllers\Admin\TutorManagementController::class, 'processStudentAssignment'])->name('process-assignment');
    Route::get('/bulk-operations', [App\Http\Controllers\Admin\TutorManagementController::class, 'bulkOperations'])->name('bulk-operations');
    Route::post('/bulk-process', [App\Http\Controllers\Admin\TutorManagementController::class, 'processBulkAssignment'])->name('bulk-process');
    Route::post('/create-course', [App\Http\Controllers\Admin\TutorManagementController::class, 'createCourseForTutor'])->name('create-course');
    Route::get('/{tutor}/details', [App\Http\Controllers\Admin\TutorManagementController::class, 'getTutorDetails'])->name('details');
    Route::post('/remove-student', [App\Http\Controllers\Admin\TutorManagementController::class, 'removeStudentFromTutor'])->name('remove-student');
});

// Tutor Routes (Admin & Tutor Access)
Route::middleware(['auth', 'tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Tutor\TutorDashboardViewController::class, 'index'])->name('dashboard');
    Route::get('/student/{student_id}/profile', [App\Http\Controllers\Tutor\TutorDashboardViewController::class, 'showStudentProfile'])->name('student.profile');
    Route::get('/student/{student_id}/progress', [App\Http\Controllers\Tutor\StudentProgressController::class, 'show'])->name('student.progress');
    Route::get('/student/{student_id}/updates', [App\Http\Controllers\Tutor\StudentProgressController::class, 'getRealtimeUpdates'])->name('student.updates');
    Route::post('/student/{student_id}/report', [App\Http\Controllers\Tutor\TutorReportController::class, 'generateStudentReport'])->name('student.report');
    Route::post('/reports/export', [App\Http\Controllers\Tutor\TutorReportController::class, 'exportAllReports'])->name('reports.export');
});




// Video access endpoints (moved from API routes for session-based auth)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/video-access/{video}', [App\Http\Controllers\VideoController::class, 'getVideoAccess']);
    // Support both GET and POST for video token generation
    Route::get('/video-token/{video}', [App\Http\Controllers\VideoProxyController::class, 'generateSecureToken']);
    Route::post('/video-token/{video}', [App\Http\Controllers\VideoProxyController::class, 'generateSecureToken']);
    Route::get('/video-stream/{token}', [App\Http\Controllers\VideoProxyController::class, 'serveVideo']);
    
    // Simple video details endpoint for YouTube videos
    Route::get('/video/{video}', function($videoId) {
        $video = \App\Models\Video::find($videoId);
        if (!$video) {
            return response()->json(['success' => false, 'message' => 'Video not found']);
        }
        return response()->json([
            'success' => true,
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'youtube_video_id' => $video->youtube_video_id,
                'description' => $video->description,
                'duration' => $video->duration
            ]
        ]);
    });
    

});
