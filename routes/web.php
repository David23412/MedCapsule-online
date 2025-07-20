<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    CourseController,
    TopicController,
    QuestionController,
    QuizController,
    MistakeController,
    LearnController,
    SettingsController,
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
use Illuminate\Support\Facades\DB;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth routes
require __DIR__.'/auth.php';

// Protected routes
Route::middleware('auth')->group(function () {
    // User progress route
    Route::get('/profile/progress', [ProfileController::class, 'getProgress'])->name('user.progress');
    
    // Course routes
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    
    // Review routes with CSRF and rate limiting
    Route::get('/review', [QuizController::class, 'showMistakes'])->name('review.index')->withoutMiddleware('auth');
    Route::get('/review/learn', [LearnController::class, 'learnMistakes'])->name('review.learn');
    Route::post('/review/remove/{question_id}', [MistakeController::class, 'removeMistake'])
        ->name('review.remove')
        ->middleware(['throttle:60,1']); // 60 requests per minute
    Route::post('/learn/review/{question_id}', [LearnController::class, 'reviewMistake'])
        ->name('learn.review')
        ->middleware(['throttle:120,1']); // 120 requests per minute
    Route::post('/learn/save-time', [LearnController::class, 'saveStudyTime'])
        ->name('learn.save-time')
        ->middleware(['throttle:60,1']); // 60 requests per minute
    
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
    Route::get('/topics/{course}', [TopicController::class, 'forCourse'])->name('topics.forCourse');
    Route::get('/topics/course/{course}', [TopicController::class, 'forCourse'])->name('topics.forCourseLegacy');
    Route::get('/courses/{course}/cases', [TopicController::class, 'casesForCourse'])->name('cases.forCourse');
    Route::get('/courses/{course}/cases-count', [TopicController::class, 'casesCount'])->name('cases.count');

    // Quiz routes with CSRF and rate limiting
    Route::get('/quiz/{course}/{topic}', [QuizController::class, 'startQuiz'])
        ->name('quiz.start')
        ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
    Route::post('/quiz/{course}/{topic}/grade', [QuizController::class, 'grade'])
        ->name('quiz.grade')
        ->where(['course' => '[0-9]+', 'topic' => '[0-9]+'])
        ->middleware(['throttle:30,1']); // 30 quiz submissions per minute

    // Tutor mode routes with CSRF and rate limiting
    Route::get('/quiz/{course}/{topic}/tutor', [QuizController::class, 'startTutorQuiz'])
        ->name('quiz.tutor')
        ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
    Route::post('/quiz/tutor/check/{question}', [QuizController::class, 'checkTutorAnswer'])
        ->name('quiz.tutor.check-answer')
        ->where(['question' => '[0-9]+'])
        ->middleware(['throttle:60,1']); // 60 tutor answer checks per minute

    // Random Quiz Routes with CSRF and rate limiting
    Route::post('/quiz/random', [App\Http\Controllers\RandomQuizController::class, 'generateRandomQuiz'])
        ->name('quiz.random')
        ->middleware(['throttle:10,1']); // 10 random quiz generations per minute
    Route::post('/quiz/grade-random', [App\Http\Controllers\RandomQuizController::class, 'gradeRandomQuiz'])
        ->name('quiz.grade-random')
        ->middleware(['throttle:30,1']); // 30 random quiz submissions per minute

    // Settings and profile routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/update-picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update-picture');

    // Dashboard redirect
    Route::get('/dashboard', function () {
        return redirect('/courses');
    })->name('dashboard');

    // Active users count API
    Route::get('/api/active-users', function() {
        // If not an AJAX request, redirect to home
        if (!request()->ajax()) {
            return redirect('/');
        }

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
    })->middleware('auth')->name('api.active-users');

    // Payment routes - must be added inside the auth middleware group
    Route::get('/payments/{course}/options', [PaymentController::class, 'getPaymentOptions'])->name('payments.options');
    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');

    // Manual payment processing routes with rate limiting
    Route::post('/payments/{course}/process-manual', [PaymentController::class, 'processManualPayment'])
        ->name('payments.process-manual')
        ->middleware(['throttle:5,1']); // 5 payment attempts per minute
    Route::post('/payments/{course}/submit-proof', [PaymentController::class, 'submitProof'])
        ->name('payments.submit-proof')
        ->middleware(['throttle:10,1']); // 10 proof submissions per minute
    Route::post('/payments/{course}/submit-proof-course', [PaymentController::class, 'submitProof'])
        ->name('payments.submit-proof-course')
        ->middleware(['throttle:10,1']); // 10 proof submissions per minute
    Route::post('/payments/{payment}/submit-proof', [PaymentController::class, 'submitPaymentProof'])
        ->name('payments.submit-payment-proof')
        ->middleware(['throttle:10,1']); // 10 proof submissions per minute
    
    // Bundle payment routes with rate limiting
    Route::post('/payments/bundle/options', [PaymentController::class, 'getBundlePaymentOptions'])
        ->name('payments.bundle.options')
        ->middleware(['throttle:20,1']); // 20 bundle option requests per minute
    Route::post('/payments/bundle/process-manual', [PaymentController::class, 'processBundleManualPayment'])
        ->name('payments.bundle.process-manual')
        ->middleware(['throttle:3,1']); // 3 bundle payment attempts per minute
    Route::post('/payments/bundle/submit-proof', [PaymentController::class, 'submitBundlePaymentProof'])
        ->name('payments.bundle.submit-proof')
        ->middleware(['throttle:5,1']); // 5 bundle proof submissions per minute

    // Admin payment routes
    Route::post('/admin/payments/{payment}/verify', [PaymentController::class, 'adminVerifyPayment'])->name('admin.payments.verify');
    Route::post('/admin/payments/{payment}/reject', [PaymentController::class, 'adminRejectPayment'])->name('admin.payments.reject');
    Route::get('/admin/payment-history', [PaymentController::class, 'adminPaymentHistory'])->name('admin.payment-history');
    Route::get('/admin/payment-details/{payment}', [PaymentController::class, 'adminPaymentDetails'])->name('admin.payment-details');

    // Source progress endpoint
    Route::get('/topics/{topic}/source-progress', [TopicController::class, 'getSourceProgress'])
        ->name('topics.source-progress')
        ->middleware('auth');

    // Assignment Management Routes (Admin & Tutor)
    Route::middleware(['auth'])->prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');
        Route::get('/create', [AssignmentController::class, 'create'])->name('create');
        Route::post('/', [AssignmentController::class, 'store'])->name('store');
        Route::get('/{assignment}', [AssignmentController::class, 'show'])->name('show');
        Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
        Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');
        Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
        
        // Bulk assignment routes
        Route::get('/bulk/create', [AssignmentController::class, 'bulkCreate'])->name('bulk.create');
        Route::post('/bulk/store', [AssignmentController::class, 'bulkStore'])->name('bulk.store');
        
        // Analytics and reporting
        Route::get('/analytics/dashboard', [AssignmentController::class, 'analytics'])->name('analytics');
        
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
    Route::middleware(['auth'])->prefix('topic-locking')->name('topic-locking.')->group(function () {
        Route::get('/', [TopicLockingController::class, 'index'])->name('index');
        Route::get('/courses/{course}', [TopicLockingController::class, 'courseTopics'])->name('course');
        
        // Individual topic locking
        Route::post('/topics/{topic}/lock', [TopicLockingController::class, 'lock'])
            ->name('lock')
            ->middleware(['throttle:30,1']);
        Route::post('/topics/{topic}/unlock', [TopicLockingController::class, 'unlock'])
            ->name('unlock')
            ->middleware(['throttle:30,1']);
        Route::post('/topics/{topic}/toggle', [TopicLockingController::class, 'toggle'])
            ->name('toggle')
            ->middleware(['throttle:30,1']);
        
        // Bulk operations
        Route::post('/bulk/lock', [TopicLockingController::class, 'bulkLock'])
            ->name('bulk.lock')
            ->middleware(['throttle:10,1']);
        Route::post('/bulk/unlock', [TopicLockingController::class, 'bulkUnlock'])
            ->name('bulk.unlock')
            ->middleware(['throttle:10,1']);
        
        // Course-wide operations
        Route::post('/courses/{course}/lock-all', [TopicLockingController::class, 'lockCourse'])
            ->name('course.lock')
            ->middleware(['throttle:5,1']);
        Route::post('/courses/{course}/unlock-all', [TopicLockingController::class, 'unlockCourse'])
            ->name('course.unlock')
            ->middleware(['throttle:5,1']);
        
        // AJAX endpoints
        Route::get('/topics/{topic}/status', [TopicLockingController::class, 'getTopicStatus'])
            ->name('topic.status');
        Route::get('/topics/{topic}/student-access', [TopicLockingController::class, 'checkStudentAccess'])
            ->name('student.access');
        Route::get('/students/accessible-topics', [TopicLockingController::class, 'getAccessibleTopics'])
            ->name('accessible.topics');
        Route::get('/statistics', [TopicLockingController::class, 'getStatistics'])
            ->name('statistics');
        Route::get('/locked-topics', [TopicLockingController::class, 'getLockedTopics'])
            ->name('locked.topics');
    });
});

// Notification routes - simplified implementation
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/delete-read', [NotificationController::class, 'deleteRead'])->name('notifications.delete-read'); 
    // Removed mark-all-read route for simplified implementation
});

// Keep this for backward compatibility, but we've moved the routes to standard auth middleware
Route::middleware('auth:sanctum')->group(function () {
    // API routes that require Sanctum auth would go here
});

// Healthcheck route for monitoring
Route::get('/health', function() {
    return response()->json(['status' => 'ok'], 200);
});

// Tutor Routes (Admin & Tutor Access)
Route::middleware(['auth', 'tutor'])->prefix('tutor')->name('tutor.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Tutor\TutorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/student/{student_id}/profile', [App\Http\Controllers\Tutor\TutorDashboardController::class, 'showStudentProfile'])->name('student.profile');
    Route::get('/student/{student_id}/progress', [App\Http\Controllers\Tutor\StudentProgressController::class, 'show'])->name('student.progress');
    Route::get('/student/{student_id}/updates', [App\Http\Controllers\Tutor\StudentProgressController::class, 'getRealtimeUpdates'])->name('student.updates');
    Route::post('/student/{student_id}/report', [App\Http\Controllers\Tutor\TutorDashboardController::class, 'generateStudentReport'])->name('student.report');
    Route::post('/reports/export', [App\Http\Controllers\Tutor\TutorDashboardController::class, 'exportAllReports'])->name('reports.export');
});
