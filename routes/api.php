<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Register API endpoints for the application here.
|
*/

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

// Active users count API
Route::get('/active-users', function() {
    try {
        $data = Cache::remember('api:active_users_count', 30, function () {
            $fiveMinutesAgo = now()->subMinutes(5)->timestamp;

            $visitorCount = DB::table('sessions')
                ->where('last_activity', '>=', $fiveMinutesAgo)
                ->where('is_visitor', true)
                ->distinct('fingerprint')
                ->count();

            $loggedInCount = DB::table('sessions')
                ->where('last_activity', '>=', $fiveMinutesAgo)
                ->where('is_visitor', false)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count();

            return [
                'count' => $visitorCount + $loggedInCount,
                'visitors' => $visitorCount,
                'users' => $loggedInCount
            ];
        });

        return response()->json($data);
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
    Route::get('/my-locked-topics', 'App\Http\Controllers\TopicLocking\TopicLockingApiController@getMyLockedTopics');
    
    // Get lock information for a topic
    Route::get('/topic/{topic}/lock-info', 'App\Http\Controllers\TopicLocking\TopicLockingApiController@getLockInfo');
});

// Get question counts by source for a topic
Route::get('/topic/{topic}/question-counts', function($topicId) {
    try {
        $counts = Cache::remember("api:topic:{$topicId}:question_counts", 3600, function () use ($topicId) {
            $sourceCounts = Question::query()
                ->where('topic_id', $topicId)
                ->whereNotNull('source')
                ->selectRaw('source, COUNT(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source');

            return [
                'total' => (int) Question::where('topic_id', $topicId)->count(),
                'sources' => $sourceCounts,
            ];
        });

        return response()->json($counts);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to get question counts'], 500);
    }
});

// Get available sources for a topic (for assignment creation)
Route::get('/topics/{topicId}/sources', function($topicId) {
    try {
        $sources = Cache::remember("api:topic:{$topicId}:sources", 3600, function () use ($topicId) {
            return \DB::table('questions')
                ->where('topic_id', $topicId)
                ->select('source')
                ->distinct()
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->pluck('source')
                ->toArray();
        });

        return response()->json($sources);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to get sources'], 500);
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
}); 
