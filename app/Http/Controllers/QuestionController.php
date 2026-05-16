<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\QuestionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Question;
use App\Models\Topic;
use App\Services\TsvQuestionImportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function showAddQuestionForm()
    {
        // Ultra-aggressive caching for topics
        $cacheKey = \App\Services\QuizUltraConsolidatedCacheService::generateCacheKey('admin_topics_all', ['timestamp' => now()->format('Y-m-d-H')]);
        $topics = Cache::remember($cacheKey, 3600, function () {
            return Topic::query()
                ->select('id', 'name', 'course_id')
                ->orderBy('name')
                ->get();
        });

        $conceptSuggestionsByTopic = Cache::remember('admin_question_concepts_by_topic_v2', 3600, function () {
            return Question::query()
                ->whereNotNull('concept')
                ->get(['topic_id', 'concept'])
                ->groupBy('topic_id')
                ->map(function (Collection $questions) {
                    return $questions
                        ->pluck('concept')
                        ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                        ->unique(fn ($name) => Str::lower(trim($name)))
                        ->sort()
                        ->values()
                        ->all();
                })
                ->all();
        });

        return view('add_question', compact('topics', 'conceptSuggestionsByTopic'));
    }

    public function storeQuestion(QuestionRequest $request)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                abort(403);
            }

        // Validate basic fields first
        // TODO: Create FormRequest class for validation

        // SECURITY FIX: Enhanced file upload security
        $imageUrl = null;
        if ($request->hasFile('question_image')) {
            $file = $request->file('question_image');

            // SECURITY FIX: Validate file integrity
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload');
            }

            // SECURITY FIX: Check for malicious filename patterns
            $originalName = $file->getClientOriginalName();
            if (preg_match('/[<>\"\'\/\\\\]/', $originalName)) {
                throw new \Exception('Invalid filename. Please use only alphanumeric characters, dots, and underscores.');
            }

            // SECURITY FIX: Check for double extensions
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());
            if (strpos($nameWithoutExt, '.') !== false) {
                throw new \Exception('Double extensions are not allowed for security reasons.');
            }

            // SECURITY FIX: Sanitize filename
            $sanitizedFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $originalName);
            $path = $file->storeAs('questions', $sanitizedFilename, 'public');
            $imageUrl = 'storage/' . $path;

        } elseif ($request->filled('image_url')) {
            // SECURITY FIX: Validate URL format and prevent malicious URLs
            $url = $request->image_url;
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid image URL format');
            }

            // SECURITY FIX: Only allow HTTP/HTTPS URLs
            if (!preg_match('/^https?:\/\//', $url)) {
                throw new \Exception('Only HTTP and HTTPS URLs are allowed');
            }

            $imageUrl = $url;
        }

        // Validate based on question type
        if ($request->question_type === 'multiple_choice') {
            // TODO: Create FormRequest class for validation
            
            $correctAnswer = $request->correct_answer;
            $alternativeAnswers = null;
            
        } else { // written question
            // TODO: Create FormRequest class for validation
            
            $correctAnswer = $request->written_correct_answer;
            
            // Process alternative answers if provided
            if ($request->filled('alternative_answers')) {
                try {
                    // Store as plain text - just clean it up
                    $alternativeAnswers = trim($request->alternative_answers);

                    // Basic validation - ensure it's not empty after trimming
                    if (empty($alternativeAnswers)) {
                        $alternativeAnswers = null;
                    }

                } catch (\Exception $e) {
                    \Log::error('Error processing alternative answers', [
                        'error' => $e->getMessage(),
                        'raw_input' => substr($request->alternative_answers, 0, 100)
                    ]);
                    return redirect()->back()->with('error', 'Invalid format in alternative answers. Please use only plain text.')->withInput();
                }
            } else {
                $alternativeAnswers = null;
            }
        }

        // Set empty values for options if it's a written question
        $optionA = $request->question_type === 'multiple_choice' ? $request->option_a : '';
        $optionB = $request->question_type === 'multiple_choice' ? $request->option_b : '';
        $optionC = $request->question_type === 'multiple_choice' ? $request->option_c : '';
        $optionD = $request->question_type === 'multiple_choice' ? $request->option_d : '';
        $optionE = $request->question_type === 'multiple_choice' ? $request->option_e : '';

        // Create the question
        $topic = Topic::query()->select('id', 'course_id', 'name')->findOrFail((int) $request->topic_id);

        $question = Question::create([
            'topic_id' => $request->topic_id,
            'question' => $request->question,
            'explanation' => $request->explanation,
            'question_type' => $request->question_type,
            'option_a' => $optionA,
            'option_b' => $optionB,
            'option_c' => $optionC,
            'option_d' => $optionD,
            'option_e' => $optionE,
            'correct_answer' => $correctAnswer,
            'alternative_answers' => $alternativeAnswers,
            'image_url' => $imageUrl,
            'source' => $request->source
        ]);

        $this->syncQuestionConcepts($question, $topic, $request->input('concept_names'));

        return redirect()->back()->with('success', 'Question added successfully!');
        
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function importTsv(Request $request, TsvQuestionImportService $importer)
    {
        $validated = $request->validate([
            'topic_id' => 'nullable|integer|exists:topics,id',
            'tsv' => 'required|string',
        ]);

        $fallbackTopicId = isset($validated['topic_id']) ? (int) $validated['topic_id'] : null;
        $result = $importer->import($validated['tsv'], $fallbackTopicId);

        if (!empty($result['errors'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('import_errors', $result['errors']);
        }

        return redirect()
            ->back()
            ->with('success', "Imported {$result['imported']} questions from TSV.");
    }

    private function syncQuestionConcepts(Question $question, Topic $topic, ?string $rawConceptNames): void
    {
        $conceptNames = collect(preg_split('/[\r\n,;]+/', (string) $rawConceptNames))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->values()
            ->all();

        $question->assignConceptTags($conceptNames);
        $question->save();
    }
}
