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