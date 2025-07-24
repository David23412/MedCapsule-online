use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Question;
use Illuminate\Support\Facades\Log;

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
            'sources' => []
        ];
        
        // Initialize all known sources with zero counts
        $knownSources = ['Assuit', 'Cairo', 'Alexandria'];
        foreach ($knownSources as $source) {
            $counts['sources'][$source] = 0;
        }
        
        // Count questions by source
        foreach ($questions as $question) {
            $source = $question->source;
            if ($source && isset($counts['sources'][$source])) {
                $counts['sources'][$source]++;
            }
        }
        
        Log::info('API: Question counts fetched', [
            'topic_id' => $topicId,
            'total' => $counts['total'],
            'sources' => $counts['sources']
        ]);
        
        return response()->json($counts);
    } catch (\Exception $e) {
        Log::error('Error fetching question counts: ' . $e->getMessage(), [
            'topic_id' => $topicId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return a valid JSON response even in case of error
        return response()->json([
            'error' => 'Failed to fetch question counts',
            'total' => 0,
            'sources' => [
                'Assuit' => 0,
                'Cairo' => 0,
                'Alexandria' => 0
            ]
        ], 500);
    }
})->middleware('auth');

// Get available sources for a topic (for assignment creation)
Route::get('/topics/{topicId}/sources', function($topicId) {
    try {
        \Log::info('API: Loading sources for topic ID: ' . $topicId);
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
        Log::error('Error fetching topic sources: ' . $e->getMessage(), [
            'topic_id' => $topicId,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([], 500);
    }
})->middleware('auth'); 