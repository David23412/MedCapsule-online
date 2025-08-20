<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoProxyController;
use App\Events\LiveUserCountUpdated;

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

Route::middleware('auth')->get('/user', function (Request $request) {
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
        
        $totalCount = $visitorCount + $loggedInCount;

        // Broadcast the event with the total count
        LiveUserCountUpdated::dispatch($totalCount);

        return response()->json([
            'count' => $totalCount,
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
    Route::get('/topic/{topic}/lock-info', 'App\Http\Controllers\TopicLockingController@getLockInfo');
});

// Get question counts by source for a topic
Route::get('/topic/{topic}/question-counts', function($topicId) {
    try {
        // Get all questions for this topic
        $questions = Question::where('topic_id', $topicId)->get();
        
        // Count questions by source
        $counts = [
            'total' => $questions->count(),
            'sources' => $questions->groupBy('source')->map(function($group) {
                return $group->count();
            })
        ];
        
        return response()->json($counts);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to get question counts'], 500);
    }
});

// Get available sources for a topic (for assignment creation)
Route::get('/topics/{topicId}/sources', function($topicId) {
    try {
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
        return response()->json(['error' => 'Failed to get sources'], 500);
    }
});

// Test payment verification route (no auth, no CSRF)
Route::post('/test-payment-verify/{payment}', function($payment) {
    try {
        $payment = \App\Models\Payment::findOrFail($payment);
        
        // Update payment status
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'payment_data' => array_merge($payment->payment_data ?? [], [
                'verified_by' => 'api_test',
                'verified_at' => now()->toDateTimeString(),
                'admin_notes' => 'API test verification'
            ])
        ]);
        
        // Enroll user in course
        $user = \App\Models\User::find($payment->user_id);
        if ($user) {
            $user->courses()->syncWithoutDetaching([$payment->course_id]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully via API',
            'payment_id' => $payment->id,
            'status' => $payment->status
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}); 

Route::middleware('auth')->group(function () {
    // User progress endpoint for milestone bar updates
    Route::get('/user/progress', function () {
        return response()->json([
            'correct_answers_count' => auth()->user()->correct_answers_count ?? 0,
            'total_questions_attempted' => auth()->user()->total_questions_attempted ?? 0,
            'study_streak_days' => auth()->user()->study_streak_days ?? 0
        ]);
    });
    
    // Video management routes
    Route::post('/upload-video', [VideoController::class, 'uploadVideo']);
    Route::post('/video-token/{videoId}', [VideoController::class, 'getVideoAccess']);
    Route::get('/video-stream/{token}', [VideoController::class, 'getVideoStream']);
}); 

