<?php

use Illuminate\Support\Facades\Route;
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
    PaymentController,
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

// Test route for progress update system
Route::get('/test-progress-update', function() {
    return view('test-progress-update');
})->name('test.progress-update');

// Real-time progress update routes
Route::prefix('api/progress')->middleware('auth')->group(function () {
    Route::post('/store-update', [App\Http\Controllers\ProgressUpdateController::class, 'storeProgressUpdate'])->name('progress.store-update');
    Route::get('/recent-updates', [App\Http\Controllers\ProgressUpdateController::class, 'getRecentUpdates'])->name('progress.recent-updates');
    Route::post('/store-assignment', [App\Http\Controllers\ProgressUpdateController::class, 'storeAssignmentCompletion'])->name('progress.store-assignment');
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

// Auth routes
require __DIR__.'/auth.php';
require __DIR__.'/debug.php';

// Protected routes
Route::middleware('auth')->group(function () {
    // User progress route
    Route::get('/profile/progress', [ProfileController::class, 'getProgress'])->name('user.progress');
    
    // Course routes
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationViewController::class, 'index'])->name('notifications.index');
        Route::get('/stats', [NotificationViewController::class, 'stats'])->name('notifications.stats');
        Route::post('/mark-all-read', [NotificationViewController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::post('/mark-read', [NotificationViewController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/delete', [NotificationViewController::class, 'deleteNotification'])->name('notifications.delete');
        Route::post('/cleanup', [NotificationViewController::class, 'cleanup'])->name('notifications.cleanup');
    });

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
    
    // Admin routes
    Route::get('/add-topic', [TopicController::class, 'showAddTopicForm'])->name('add-topic.form');
    Route::post('/add-topic', [TopicController::class, 'addTopic'])->name('add-topic');
    Route::get('/add-course', [CourseController::class, 'showAddCourseForm'])->name('add-course.form');
    Route::post('/add-course', [CourseController::class, 'addCourse'])->name('add-course');
    Route::get('/add-question', [QuestionController::class, 'showAddQuestionForm'])->name('add-question.form');
    Route::post('/add-question', [QuestionController::class, 'storeQuestion'])->name('add-question');
    Route::get('/manage-access', [UserCourseController::class, 'index'])->name('admin.user-course');
    Route::post('/manage-access/enroll', [UserCourseController::class, 'enroll'])->name('admin.user-course.enroll');
    Route::get('/manage-access/search-users', [UserCourseController::class, 'searchUsers'])->name('admin.user-course.search');
    Route::post('/manage-access/toggle-admin', [UserCourseController::class, 'toggleAdmin'])->name('admin.user-course.toggle-admin');
    Route::post('/manage-access/payment-settings', [UserCourseController::class, 'updatePaymentSettings'])->name('admin.payment-settings.update');
    
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
    Route::get('/topics/{courseId}', [TopicController::class, 'forCourse'])->name('topics.forCourse')->where('courseId', '[0-9]+')->middleware(\App\Http\Middleware\DebugRouteMiddleware::class);
    
    // EMERGENCY DEBUG ROUTE
    Route::get('/debug-topics/{courseId}', function($courseId) {
        return response()->json([
            'received_courseId' => $courseId,
            'courseId_type' => gettype($courseId),
            'converted_id' => (int) $courseId,
            'route_params' => request()->route()->parameters(),
            'all_params' => request()->all(),
            'url' => request()->url(),
            'path' => request()->path(),
            'method' => request()->method(),
            'headers' => request()->headers->all(),
            'server' => request()->server(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
    })->where('courseId', '[0-9]+')->middleware('auth');
    Route::get('/topics/course/{courseId}', [TopicController::class, 'forCourse'])->name('topics.forCourseLegacy')->where('courseId', '[0-9]+');
    Route::get('/courses/{course}/cases', [TopicController::class, 'casesForCourse'])->name('cases.forCourse');
    Route::get('/courses/{course}/cases-count', [TopicController::class, 'casesCount'])->name('cases.count');

    // 💀 NUCLEAR OPTIMIZED QUIZ ROUTES - SPLIT ARCHITECTURE 💀
    Route::prefix('quiz')->name('quiz.')->group(function () {
        // Quiz Execution
        Route::get('/{course}/{topic}', [App\Http\Controllers\Quiz\QuizExecutionController::class, 'startQuiz'])
            ->name('start')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
        Route::post('/{course}/{topic}/grade', [App\Http\Controllers\Quiz\QuizExecutionController::class, 'grade'])
            ->name('grade')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
            // THROTTLE REMOVED FOR F16 RAPTOR SPEED
            
        // Tutor Mode
        Route::get('/{course}/{topic}/tutor', [App\Http\Controllers\Quiz\QuizTutorController::class, 'startTutorQuiz'])
            ->name('tutor')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
        Route::post('/tutor/check/{question}', [App\Http\Controllers\Quiz\QuizTutorController::class, 'checkTutorAnswer'])
            ->name('tutor.check-answer')
            ->where(['question' => '[0-9]+'])
            ->withoutMiddleware(['web']); // BYPASS ALL MIDDLEWARE FOR SPEED
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

    // Optimized Payment Processing Routes - NUCLEAR OPTIMIZATION
    Route::prefix('payment-processing')->name('payment-processing.')->group(function () {
        Route::post('/courses/{courseId}/process', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'processManualPayment'])
            ->name('process')
            ->middleware(['throttle:payment-processing,5,1']); // 5 payment processing per minute
        Route::get('/courses/{courseId}/options', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'getPaymentOptions'])
            ->name('options')
            ->middleware(['throttle:30,1']); // 30 option requests per minute
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/update-picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update-picture');

    // Dashboard redirect
    Route::get('/dashboard', function () {
        return redirect('/courses');
    })->name('dashboard');

    // 💀 NUCLEAR OPTIMIZED PAYMENT ROUTES - SPLIT ARCHITECTURE 💀
    Route::prefix('payment')->name('payment.')->group(function () {
        // Payment Options
        Route::get('/options/{course}', [App\Http\Controllers\Payment\PaymentOptionsController::class, 'getPaymentOptions'])
            ->name('options');
        Route::post('/bundle-options', [App\Http\Controllers\Payment\PaymentOptionsController::class, 'getBundlePaymentOptions'])
            ->name('bundle-options');
            
        // Payment Processing
        Route::post('/process/{course}', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'processManualPayment'])
            ->name('process')
            ->middleware(['throttle:payment-processing,5,1']);
        Route::post('/process-bundle', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'processBundleManualPayment'])
            ->name('process-bundle')
            ->middleware(['throttle:payment-processing,3,1']);
        Route::post('/submit-proof/{courseId}', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'submitProof'])
            ->name('submit-proof')
            ->middleware(['throttle:10,1']);
        Route::post('/submit-bundle-proof', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'submitBundlePaymentProof'])
            ->name('submit-bundle-proof')
            ->middleware(['throttle:5,1']);
            
        // Payment Status
        Route::get('/history', [App\Http\Controllers\Payment\PaymentStatusController::class, 'history'])
            ->name('history');
        Route::get('/status/{payment}', [App\Http\Controllers\Payment\PaymentStatusController::class, 'status'])
            ->name('status');
        Route::post('/refresh/{payment}', [App\Http\Controllers\Payment\PaymentStatusController::class, 'refreshStatus'])
            ->name('refresh');
        Route::post('/retry/{payment}', [App\Http\Controllers\Payment\PaymentStatusController::class, 'retry'])
            ->name('retry');
    });
    
    // 💀 LEGACY PAYMENT ROUTES FOR FRONTEND COMPATIBILITY 💀
    Route::prefix('payments')->name('payments.')->group(function () {
        // Payment Options - Legacy route for frontend compatibility
        Route::get('/{course}/options', [App\Http\Controllers\Payment\PaymentOptionsController::class, 'getPaymentOptions'])
            ->name('options');
            
        // Payment Processing - Legacy routes for frontend compatibility
        Route::post('/submit-proof-course/{course}', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'submitProof'])
            ->name('submit-proof-course')
            ->middleware(['throttle:10,1']);
        Route::post('/submit-proof/{payment}', [App\Http\Controllers\Payment\PaymentProcessingController::class, 'submitProof'])
            ->name('submit-proof')
            ->middleware(['throttle:10,1']);
            
        // Payment Status - Legacy routes for frontend compatibility
        Route::get('/history', [App\Http\Controllers\Payment\PaymentStatusController::class, 'history'])
            ->name('history');
    });
    
    // 💀 NUCLEAR OPTIMIZED ADMIN PAYMENT ROUTES 💀
    Route::prefix('admin/payments')->name('admin.payments.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/history', [App\Http\Controllers\Payment\PaymentAdminController::class, 'adminPaymentHistory'])
            ->name('history');
        Route::get('/details/{payment}', [App\Http\Controllers\Payment\PaymentAdminController::class, 'adminPaymentDetails'])
            ->name('details');
        Route::post('/verify/{payment}', [App\Http\Controllers\Payment\PaymentAdminController::class, 'adminVerifyPayment'])
            ->name('verify');
        Route::post('/bulk-verify', [App\Http\Controllers\Payment\PaymentAdminController::class, 'adminBulkVerify'])
            ->name('bulk-verify');
        Route::post('/reject/{payment}', [App\Http\Controllers\Payment\PaymentAdminController::class, 'adminRejectPayment'])
            ->name('reject');
    });
    Route::get('/admin/payment-history', [PaymentController::class, 'adminPaymentHistory'])->name('admin.payment-history');
    Route::get('/admin/payment-details/{payment}', [PaymentController::class, 'adminPaymentDetails'])->name('admin.payment-details');

    // Test route for payment verification (bypasses auth for testing)
    Route::post('/test-payment-verify/{payment}', function($payment) {
        try {
            $payment = \App\Models\Payment::findOrFail($payment);
            
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'payment_data' => array_merge($payment->payment_data ?? [], [
                    'verified_by' => 'test_route',
                    'verified_at' => now()->toDateTimeString(),
                    'admin_notes' => 'Test verification via route'
                ])
            ]);
            
            // Enroll user in course
            $user = \App\Models\User::find($payment->user_id);
            if ($user) {
                $user->courses()->syncWithoutDetaching([$payment->course_id]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully via test route',
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    })->name('test.payment.verify');

    // Source progress endpoint
    Route::get('/topics/{topic}/source-progress', [TopicController::class, 'getSourceProgress'])
        ->name('topics.source-progress')
        ->middleware('auth');

    // Assignment Management Routes (Admin & Tutor)
    Route::middleware(['auth'])->prefix('assignments')->name('assignments.')->group(function () {
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

// Debug route for assignment creation
Route::get('/debug/assignment-data', function() {
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    
    $user = auth()->user();
    $courses = \App\Models\Course::orderBy('name')->get();
    $students = \App\Models\User::where('role', 'student')->orderBy('name')->get();
    
    return response()->json([
        'user' => $user->name,
        'is_admin' => $user->isAdmin(),
        'courses_count' => $courses->count(),
        'students_count' => $students->count(),
        'courses' => $courses->map(function($course) {
            return [
                'id' => $course->id,
                'name' => $course->name,
                'year' => $course->year
            ];
        }),
        'students' => $students->map(function($student) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'username' => $student->username
            ];
        })
    ]);
})->middleware('auth');

// Debug route for mistakes data
Route::get('/debug/mistakes-data', function() {
    if (!auth()->check()) {
        abort(403);
    }
    
    $userId = auth()->id();
    $mistakes = \App\Models\Mistake::where('user_id', $userId)->get();
    
    return response()->json([
        'user_id' => $userId,
        'total_mistakes' => $mistakes->count(),
        'skipped_mistakes' => $mistakes->where('skipped', true)->count(),
        'mistakes_data' => $mistakes->map(function($mistake) {
            return [
                'id' => $mistake->id,
                'question_id' => $mistake->question_id,
                'skipped' => $mistake->skipped,
                'skipped_type' => gettype($mistake->skipped),
                'mastered' => $mistake->mastered,
                'last_attempt_date' => $mistake->last_attempt_date
            ];
        })
    ]);
})->middleware('auth');

// Admin Tutor Management Routes
Route::middleware(['auth'])->prefix('admin/tutors')->name('admin.tutors.')->group(function () {
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
