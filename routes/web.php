<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    CourseController,
    TopicController,
    QuestionController,
    MistakeController,
    LearnController,
    UserCourseController,
    ProfileController,
    Admin\PaymentController,
    AssignmentController,
    StudentAssignmentController
};
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\FreeCourseSurveyController;
use App\Http\Controllers\Notification\NotificationViewController;
use App\Http\Controllers\Notification\NotificationActionController;

// Public routes
Route::view('/', 'landing')->name('landing');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::redirect('/home', '/courses', 301);
Route::view('/terms', 'terms')->name('terms');
Route::view('/privacy', 'privacy')->name('privacy');




// Real-time progress update routes
Route::prefix('api/progress')->middleware('auth')->group(function () {
    Route::post('/store-update', [App\Http\Controllers\ProgressUpdateController::class, 'storeProgressUpdate'])->name('progress.store-update');
    Route::get('/recent-updates', [App\Http\Controllers\ProgressUpdateController::class, 'getRecentUpdates'])->name('progress.recent-updates');
    Route::post('/store-assignment', [App\Http\Controllers\ProgressUpdateController::class, 'storeAssignmentCompletion'])->name('progress.store-assignment');
});

// Real-time enrollment status check
Route::post('/api/check-enrollment-status', [StatusController::class, 'checkEnrollmentStatus'])->middleware('auth');

// Real-time payment status check
Route::post('/api/check-payment-status', [StatusController::class, 'checkPaymentStatus'])->middleware('auth');

// Assignment progress API
Route::prefix('api/assignments')->middleware('auth')->group(function () {
    Route::get('/{assignment}/progress', [App\Http\Controllers\AssignmentController::class, 'getProgress'])->name('assignment.progress');
});

// Active users count API - Public endpoint for all users
Route::get('/api/active-users', [StatusController::class, 'activeUsers'])
    ->middleware('throttle:public-status')
    ->name('api.active-users');

// ADMIN ROUTES - Protected with admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/manage-access', [UserCourseController::class, 'index'])->name('admin.user-course');
    // FIXED: Add admin.dashboard route for navigation
    Route::get('/admin/dashboard', [UserCourseController::class, 'index'])->name('admin.dashboard');

    // Manage Access Routes - SECURE with CSRF protection
    Route::post('/manage-access/enroll', [UserCourseController::class, 'enroll'])->name('admin.user-course.enroll');
    Route::post('/manage-access/revoke', [UserCourseController::class, 'revoke'])->name('admin.user-course.revoke');
    
    // Admin Settings Routes
    Route::post('/admin/settings/update', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('admin.settings.update');
    
    Route::get('/admin/settings/{key}', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'get'])->name('admin.settings.get');
    
    // Course Fake Enrolled Routes
    Route::post('/admin/courses/{course}/fake-enrolled', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateFakeEnrolled'])->name('admin.courses.fake-enrolled.update');
    
    Route::post('/admin/courses/fake-enrolled/bulk', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'bulkUpdateFakeEnrolled'])->name('admin.courses.fake-enrolled.bulk');
});
// Route removed - restore functionality is handled by revoke method (toggle)

// Auth routes
require __DIR__.'/auth.php';

Route::group([], function () {

    // Protected routes
    Route::middleware('auth')->group(function () {
    // User progress route
    Route::get('/profile/progress', [ProfileController::class, 'getProgress'])->name('user.progress');

    Route::get('/free-courses/unlock', [FreeCourseSurveyController::class, 'show'])->name('free-course-survey.show');
    Route::post('/free-courses/unlock/draft', [FreeCourseSurveyController::class, 'saveDraft'])->name('free-course-survey.draft');
    Route::post('/free-courses/unlock/submit', [FreeCourseSurveyController::class, 'submit'])->name('free-course-survey.submit');
    
    // Course routes
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Review routes with CSRF and rate limiting
    Route::get('/review', [App\Http\Controllers\Quiz\QuizMistakeController::class, 'showMistakes'])->middleware('secure.study')->name('review.index');
    Route::get('/review/learn', [LearnController::class, 'learnMistakes'])->middleware('secure.study')->name('review.learn');
    Route::post('/review/remove/{question_id}', [MistakeController::class, 'removeMistake'])
        ->name('review.remove');
    Route::post('/review/skip/{question_id}', [MistakeController::class, 'skipMistake'])
        ->name('review.skip');

    // Mistakes routes - FIXED: Add missing mistakes.index route
    Route::resource('mistakes', MistakeController::class)->only(['index']);
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
        Route::post('/questions/import-tsv', [QuestionController::class, 'importTsv'])->name('questions.import-tsv');
    });

    
    // FIXED: Search Users Route - Must be inside auth middleware
    Route::get('/manage-access/search-users', [UserCourseController::class, 'searchUsers'])
        ->name('admin.user-course.search');

    // Manage Access Routes - Admin only
    Route::middleware('admin')->group(function () {

        // Video Management Routes
        Route::get('/admin/videos', [App\Http\Controllers\VideoController::class, 'index'])->name('admin.videos.index');
        Route::post('/admin/videos', [App\Http\Controllers\VideoController::class, 'store'])->name('admin.videos.store');
        Route::delete('/admin/videos/{video}', [App\Http\Controllers\VideoController::class, 'destroy'])->name('admin.videos.destroy');

        // API route for getting course topics
        Route::get('/api/courses/{course}/topics', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'getCourseTopics']);
        Route::post('/admin/courses/{course}/preview-topics', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'updateCoursePreviewTopics'])
            ->name('admin.courses.preview-topics.update');
        Route::get('/manage-access/user-courses/{userId}', [UserCourseController::class, 'userCourses'])->name('admin.user-course.user-courses');
        Route::post('/manage-access/toggle-admin', [UserCourseController::class, 'toggleAdmin'])->name('admin.user-course.toggle-admin');
        Route::get('/manage-access/security-users', [UserCourseController::class, 'securityUsers'])->name('admin.user-course.security-users');
        Route::post('/manage-access/security-lock/clear', [UserCourseController::class, 'clearSecurityLock'])->name('admin.user-course.security-lock.clear');
        // TEMPORARY: Move payments route outside admin middleware group for testing
        // Route::get('/manage-access/payments', [UserCourseController::class, 'payments'])->name('admin.user-course.payments')->withoutMiddleware([\App\Http\Middleware\AdminMiddleware::class]);
        Route::get('/manage-access/enrollments', [UserCourseController::class, 'getEnrollments'])->name('admin.user-course.enrollments');
        Route::get('/manage-access/user-enrollments', [UserCourseController::class, 'getUserEnrollments'])->name('admin.user-course.user-enrollments');
        Route::post('/manage-access/payment-settings', [UserCourseController::class, 'updatePaymentSettings'])->name('admin.payment-settings.update');

        // Survey detail endpoints
        Route::get('/manage-access/survey/written-answers', [UserCourseController::class, 'surveyWrittenAnswers'])->name('admin.survey.written-answers');
        Route::get('/manage-access/survey/student-responses', [UserCourseController::class, 'surveyStudentResponses'])->name('admin.survey.student-responses');
        Route::get('/manage-access/survey/student/{userId}', [UserCourseController::class, 'surveyStudentDetail'])->name('admin.survey.student-detail')->where('userId', '[0-9]+');

        // Last active users endpoint
        Route::get('/manage-access/last-active-users', [UserCourseController::class, 'getLastActiveUsers'])->name('admin.last-active-users');

        // Real-time system stats endpoint
        Route::get('/manage-access/system-stats', [UserCourseController::class, 'getSystemStats'])->name('admin.system-stats');
        Route::get('/manage-access/mentorship-data', [UserCourseController::class, 'getMentorshipData'])->name('admin.mentorship-data');
        Route::post('/manage-access/toggle-mentorship', [UserCourseController::class, 'toggleMentorship'])->name('admin.toggle-mentorship');

        // Course Management Resource Routes
        Route::resource('admin/courses', \App\Http\Controllers\Admin\CourseController::class)->names('admin.courses');
    });

    // ✅ FIXED: Payments route with proper admin protection
    Route::get('/manage-access/payments', [UserCourseController::class, 'payments'])
        ->name('admin.user-course.payments')
        ->middleware(['auth', 'admin']);
    
    // Delete Management Routes
    Route::get('/admin/delete', [\App\Http\Controllers\Admin\DeleteViewController::class, 'index'])->name('admin.delete.index');
    Route::get('/admin/delete/courses', [\App\Http\Controllers\Admin\DeleteViewController::class, 'showCourses'])->name('admin.delete.courses');
    Route::get('/admin/delete/topics', [\App\Http\Controllers\Admin\DeleteViewController::class, 'showTopics'])->name('admin.delete.topics');
    Route::get('/admin/delete/questions', [\App\Http\Controllers\Admin\DeleteViewController::class, 'showQuestions'])->name('admin.delete.questions');
    Route::get('/admin/delete/users', [\App\Http\Controllers\Admin\DeleteViewController::class, 'showUsers'])->name('admin.delete.users');
    
    // Delete Action Routes
    Route::delete('/admin/delete/course/{id}', [\App\Http\Controllers\Admin\DeleteActionController::class, 'deleteCourse'])->name('admin.delete.course');
    Route::delete('/admin/delete/topic/{id}', [\App\Http\Controllers\Admin\DeleteActionController::class, 'deleteTopic'])->name('admin.delete.topic');
    Route::delete('/admin/delete/question/{id}', [\App\Http\Controllers\Admin\DeleteActionController::class, 'deleteQuestion'])->name('admin.delete.question');
    Route::delete('/admin/delete/user/{id}', [\App\Http\Controllers\Admin\DeleteActionController::class, 'deleteUser'])->name('admin.delete.user');
    Route::post('/admin/delete/questions/bulk', [\App\Http\Controllers\Admin\DeleteActionController::class, 'bulkDeleteQuestions'])->name('admin.delete.questions.bulk');
    
    // Delete Preview API
    Route::get('/admin/delete/preview', [\App\Http\Controllers\Admin\DeleteApiController::class, 'getDeletionPreview'])->name('admin.delete.preview');
    
    // Course enrollment and topic routes
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    
    // Handle legacy/invalid course IDs by redirecting to available course
    Route::get('/topics/{courseId}', [TopicController::class, 'forCourse'])->name('topics.forCourse')->where('courseId', '[0-9]+')->middleware('free.course.survey');
    Route::get('/topics/course/{courseId}', [TopicController::class, 'forCourse'])->name('topics.forCourseLegacy')->where('courseId', '[0-9]+')->middleware('free.course.survey');
    Route::get('/courses/{course}/cases', [TopicController::class, 'casesForCourse'])->name('cases.forCourse')->middleware('free.course.survey');
    Route::get('/courses/{course}/cases-count', [TopicController::class, 'casesCount'])->name('cases.count')->middleware('free.course.survey');
    Route::get('/courses/{course}/search-questions', [TopicController::class, 'searchCourseQuestions'])->name('courses.search-questions');
    Route::post('/questions/{question}/add-mistake', [TopicController::class, 'addMistake'])->name('questions.add-mistake');

    // 💀 NUCLEAR OPTIMIZED QUIZ ROUTES - SPLIT ARCHITECTURE 💀
    Route::prefix('quiz')->middleware('secure.study')->name('quiz.')->group(function () {
        // Quiz Execution - REFACTORED ARCHITECTURE
        Route::get('/{course}/{topic}', [App\Http\Controllers\Quiz\QuizReadController::class, 'show'])
            ->name('start')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+'])
            ->middleware('free.course.survey');
        Route::get('/{course}/{topic}/read', [App\Http\Controllers\Quiz\QuizReadController::class, 'showReadMode'])
            ->name('read')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+'])
            ->middleware('free.course.survey');
        Route::post('/{course}/{topic}/grade', [App\Http\Controllers\Quiz\QuizGradingController::class, 'submit'])
            ->name('grade')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+']);
            // THROTTLE REMOVED FOR F16 RAPTOR SPEED
            
        // Tutor Mode
        Route::get('/{course}/{topic}/tutor', [App\Http\Controllers\Quiz\QuizTutorController::class, 'startTutorQuiz'])
            ->name('tutor')
            ->where(['course' => '[0-9]+', 'topic' => '[0-9]+'])
            ->middleware('free.course.survey');
        Route::post('/tutor/check/{question}', [App\Http\Controllers\Quiz\QuizTutorController::class, 'checkTutorAnswer'])
            ->name('tutor.check-answer')
            ->where(['question' => '[0-9]+']);
        Route::post('/tutor/complete-session', [App\Http\Controllers\Quiz\QuizTutorController::class, 'completeTutorSession'])
            ->name('tutor.complete-session');

        // Admin inline question editing from tutor/quiz mode
        Route::put('/tutor/question/{question}', [App\Http\Controllers\Quiz\QuizTutorController::class, 'updateQuestion'])
            ->name('tutor.update-question')
            ->where(['question' => '[0-9]+'])
            ->middleware('admin');
        Route::delete('/tutor/question/{question}', [App\Http\Controllers\Quiz\QuizTutorController::class, 'deleteQuestion'])
            ->name('tutor.delete-question')
            ->where(['question' => '[0-9]+'])
            ->middleware('admin');
    });

    // Random Quiz Routes with CSRF and rate limiting - OPTIMIZED VERSION
    Route::get('/quiz/random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'showRandomQuiz'])
        ->middleware('secure.study')
        ->name('quiz.random.show');
    Route::get('/quiz/random/tutor', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'showRandomTutorQuiz'])
        ->middleware('secure.study')
        ->name('quiz.random.tutor');
    Route::post('/quiz/generate-random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'generateRandomQuiz'])
        ->name('quiz.random.generate');
    Route::post('/quiz/grade-random', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'gradeRandomQuiz'])
        ->name('quiz.grade-random');





    // Optimized Random Quiz Routes - CRITICAL OPTIMIZATION
    Route::prefix('random-quiz')->name('random-quiz.')->group(function () {
        Route::post('/generate', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'generateRandomQuiz'])
            ->name('generate')
            ->middleware(['throttle:random-quiz']); // 10 random quiz generations per minute
        Route::post('/grade', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'gradeRandomQuiz'])
            ->name('grade')
            ->middleware(['throttle:random-quiz-grading']); // 30 random quiz gradings per minute
        Route::get('/stats', [App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'getRandomQuizStats'])
            ->name('stats')
            ->middleware(['throttle:30,1']); // 30 stats requests per minute
    });

        // Profile routes
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile/update-picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update-picture');
        Route::post('/profile/update-goal', [ProfileController::class, 'updateWeeklyGoal'])->name('profile.update-goal');
        Route::post('/profile/update-privacy', [ProfileController::class, 'updatePrivacy'])->name('profile.update-privacy');
        Route::post('/profile/update-course-planner-settings', [ProfileController::class, 'updateCoursePlannerSettings'])->name('profile.update-course-planner-settings');
        
        // Study Planner routes

    });

    // Profile picture serving route (FIXED: Use correct storage location for secure_uploads disk)
    Route::get('/uploads/profile-pictures/{path}', [\App\Http\Controllers\FileServeController::class, 'profilePicture'])->where('path', '.*');

    // Course image serving route (public access)
    Route::get('/uploads/course-images/{path}', [\App\Http\Controllers\FileServeController::class, 'courseImage'])->where('path', '.*');

    // Question image serving route (public access)
    Route::get('/uploads/questions/{path}', [\App\Http\Controllers\FileServeController::class, 'questionImage'])->where('path', '.*');

    // Dashboard redirect
    Route::redirect('/dashboard', '/courses')->name('dashboard');

    // Payment Routes
    Route::prefix('payments')->name('payments.')->middleware('auth')->group(function () {
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

    // Courses sources endpoint for random quiz filtering
    Route::get('/api/courses-sources', [TopicController::class, 'getCoursesSources'])
        ->name('api.courses-sources')
        ->middleware('auth');

    // Question counts per topic type for random quiz modal
    Route::get('/api/random-quiz-question-counts', [\App\Http\Controllers\Quiz\OptimizedRandomQuizController::class, 'getQuestionCountsByType'])
        ->name('api.random-quiz-question-counts')
        ->middleware('auth');

    // Video page route
    Route::get('/video/{topic}', [TopicController::class, 'showVideo'])->name('video.show')->middleware('auth');
    
    // Video progress routes
    Route::post('/api/video/progress', [App\Http\Controllers\VideoProgressController::class, 'updateProgress'])->name('video.progress.update')->middleware('auth');
    Route::get('/api/video/progress/{topic}', [App\Http\Controllers\VideoProgressController::class, 'getProgress'])->name('video.progress.get')->middleware('auth');

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
        Route::get('/topics/{topicId}/sources', [AssignmentController::class, 'getTopicSources'])->name('topic.sources');
        

        
        // AJAX endpoints
        Route::post('/{assignment}/extend-deadline', [AssignmentController::class, 'extendDeadline'])
            ->name('extend-deadline')
            ->middleware(['throttle:10,1']);
        Route::post('/{assignment}/mark-completed', [AssignmentController::class, 'markCompleted'])
            ->name('mark-completed')
            ->middleware(['throttle:30,1']);
        Route::get('/counter/data', [AssignmentController::class, 'getCounterData'])
            ->name('counter.data');
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
    Route::post('/notifications/mark-read', [NotificationActionController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationActionController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});


// Healthcheck route for monitoring
Route::get('/health', [App\Http\Controllers\Api\StatusController::class, 'health'])->name('health');

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
    Route::get('/student/{student_id}/report', [App\Http\Controllers\Tutor\TutorReportController::class, 'generateStudentReport'])->name('student.report');
    Route::post('/reports/export', [App\Http\Controllers\Tutor\TutorReportController::class, 'exportAllReports'])->name('reports.export');
});




// Video access endpoints (moved from API routes for session-based auth)
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/video-access/{video}', [App\Http\Controllers\VideoController::class, 'getVideoAccess'])
        ->middleware('throttle:video-tokens');
    // Support both GET and POST for video token generation
    Route::get('/video-token/{video}', [App\Http\Controllers\VideoProxyController::class, 'generateSecureToken'])
        ->middleware('throttle:video-tokens');
    Route::post('/video-token/{video}', [App\Http\Controllers\VideoProxyController::class, 'generateSecureToken'])
        ->middleware('throttle:video-tokens');
    Route::get('/video-stream/{token}', [App\Http\Controllers\VideoProxyController::class, 'serveVideo'])
        ->middleware('throttle:video-tokens');
    
    // Simple video details endpoint for YouTube videos
    Route::get('/video/{video}', [App\Http\Controllers\VideoController::class, 'show'])
        ->middleware('throttle:video-tokens');
    

});
