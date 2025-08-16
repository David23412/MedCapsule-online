<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Question;
use App\Models\Payment;
use App\Models\User;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoProxyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Active users count API
Route::get('/active-users', function() {
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

// Course preview data for course type switching UI
Route::get('/courses/{id}/preview-data', 'App\Http\Controllers\Api\CoursePreviewController@getPreviewData');

// Topic Access Control Routes
Route::middleware(['auth'])->group(function () {
    // Check access for a topic
    Route::get('/topic/{topic}/access', 'App\Http\Controllers\TopicAccessController@checkAccessApi');
    
    // Get available sources for a topic
    Route::get('/topic/{topic}/sources', 'App\Http\Controllers\TopicAccessController@getSourcesApi');
});

// Topic Locking Routes
Route::middleware(['auth', 'role:admin,tutor'])->group(function () {
    // Lock a topic
    Route::post('/topic/{topic}/lock', 'App\Http\Controllers\TopicLockingController@lock');
    
    // Unlock a topic
    Route::post('/topic/{topic}/unlock', 'App\Http\Controllers\TopicLockingController@unlock');
    
    // Bulk lock topics
    Route::post('/topics/bulk-lock', 'App\Http\Controllers\TopicLockingController@bulkLock');
    
    // Bulk unlock topics
    Route::post('/topics/bulk-unlock', 'App\Http\Controllers\TopicLockingController@bulkUnlock');
    
    // Get locked topics for a course
    Route::get('/course/{course}/locked-topics', 'App\Http\Controllers\TopicLockingController@getLockedTopics');
    
    // Get topics locked by the current tutor
    Route::get('/my-locked-topics', 'App\Http\Controllers\TopicLockingController@getMyLockedTopics');
    
    // Get lock information for a topic
    Route::get('/topic/{topic}/lock-info', 'App\Http\Controllers\TopicLockingController@getLockInfo')
        ->where('topic', '[0-9]+');
});

// FIXED: Replaced vulnerable route with safe controller method
Route::get('/topic/{topic}/question-counts', 'App\Http\Controllers\Api\QuestionCountController@getQuestionCounts')
    ->where('topic', '[0-9]+');

// FIXED: Replaced vulnerable route with safe controller method  
Route::get('/topics/{topicId}/sources', 'App\Http\Controllers\Api\QuestionSourceController@getSources')
    ->where('topicId', '[0-9]+');

// FIXED: Replaced vulnerable route with safe controller method
Route::post('/test-payment-verify/{payment}', 'App\Http\Controllers\Api\PaymentVerificationController@verifyPayment')
    ->where('payment', '[0-9]+');

Route::middleware('auth')->group(function () {
    // User progress endpoint for milestone bar updates - SSOT
    Route::get('/user/progress', function (Request $request, ResponseFactory $responseFactory, LoggerInterface $logger) {
        try {
            $userProgressService = app(\App\Support\State\UserProgressService::class);
            $progress = $userProgressService->getUserProgress(auth()->id());
            
            return $responseFactory->json([
                'correct_answers_count' => $progress['total_correct_answers'],
                'total_questions_attempted' => $progress['total_questions_attempted'],
                'study_streak_days' => $progress['study_streak_days']
            ]);
        } catch (\Exception $e) {
            $logger->error('Failed to get user progress', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $responseFactory->json(['error' => 'Failed to get progress'], 500);
        }
    });
    
    // Video management routes
    Route::post('/upload-video', [VideoController::class, 'uploadVideo']);
    Route::post('/video-token/{videoId}', [VideoController::class, 'getVideoAccess']);
    Route::get('/video-stream/{token}', [VideoController::class, 'getVideoStream']);
}); 

