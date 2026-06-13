<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionPdfController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $courses = Course::query()
            ->select(['id', 'name'])
            ->withCount(['topics', 'questions'])
            ->orderBy('name')
            ->get();

        return view('admin.question-pdf.index', compact('courses'));
    }

    public function topics(Course $course)
    {
        $hasSubjectColumn = Schema::hasColumn('topics', 'subject');
        $columns = ['id', 'name', 'display_order'];

        if ($hasSubjectColumn) {
            $columns[] = 'subject';
        } else {
            Log::warning('Topics table does not contain subject column; using fallback behavior.');
        }

        $topics = Topic::query()
            ->where('course_id', $course->id)
            ->withCount('questions')
            ->orderByRaw('COALESCE(display_order, 999999), id')
            ->get($columns)
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'name' => $topic->name,
                'subject' => $hasSubjectColumn ? ($topic->subject ?? '') : '',
                'questions_count' => $topic->questions_count,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'topics' => $topics,
        ]);
    }

    public function sources(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'topic_ids' => ['required', 'array', 'min:1'],
            'topic_ids.*' => ['integer'],
        ]);

        $topicIds = $this->validTopicIdsForCourse((int) $validated['course_id'], $validated['topic_ids']);

        $sources = Question::query()
            ->whereIn('topic_id', $topicIds)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->select('source', DB::raw('COUNT(*) as questions_count'))
            ->groupBy('source')
            ->orderBy('source')
            ->get()
            ->map(fn ($source) => [
                'name' => (string) $source->source,
                'questions_count' => (int) $source->questions_count,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'sources' => $sources,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'topic_ids' => ['required', 'array', 'min:1'],
            'topic_ids.*' => ['integer'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['string', 'max:255'],
            'include_answer_key' => ['nullable', 'boolean'],
        ]);

        $course = Course::query()->findOrFail($validated['course_id']);
        $topicIds = $this->validTopicIdsForCourse((int) $course->id, $validated['topic_ids']);
        $sources = collect($validated['sources'])
            ->map(fn ($source) => trim((string) $source))
            ->filter()
            ->unique()
            ->values();

        if ($topicIds->isEmpty() || $sources->isEmpty()) {
            throw ValidationException::withMessages([
                'topics' => 'Select at least one valid topic and source.',
            ]);
        }

        $hasSubjectColumn = Schema::hasColumn('topics', 'subject');
        $topicColumns = ['id', 'name'];

        if ($hasSubjectColumn) {
            $topicColumns[] = 'subject';
        } else {
            Log::warning('Topics table does not contain subject column; using fallback behavior.');
        }

        $topics = Topic::query()
            ->whereIn('id', $topicIds)
            ->where('course_id', $course->id)
            ->orderByRaw('COALESCE(display_order, 999999), id')
            ->get($topicColumns);

        $questionsByTopic = Question::query()
            ->whereIn('topic_id', $topics->pluck('id'))
            ->whereIn('source', $sources)
            ->orderBy('topic_id')
            ->orderBy('source')
            ->orderBy('id')
            ->get()
            ->groupBy('topic_id');

        if ($questionsByTopic->flatten(1)->isEmpty()) {
            throw ValidationException::withMessages([
                'sources' => 'No questions matched the selected topics and sources.',
            ]);
        }

        $html = view('admin.question-pdf.pdf', [
            'course' => $course,
            'topics' => $topics,
            'sources' => $sources,
            'questionsByTopic' => $questionsByTopic,
            'includeAnswerKey' => (bool) ($validated['include_answer_key'] ?? false),
            'generatedAt' => now(),
            'logoSvg' => $this->logoSvg(),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $fileName = Str::slug($course->name) . '-question-pack-' . now()->format('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    private function validTopicIdsForCourse(int $courseId, array $topicIds): Collection
    {
        return Topic::query()
            ->where('course_id', $courseId)
            ->whereIn('id', collect($topicIds)->map(fn ($id) => (int) $id)->unique()->values())
            ->pluck('id');
    }

    private function logoSvg(): string
    {
        return base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 144 48">' .
            '<rect x="4" y="6" width="86" height="36" rx="18" fill="#0ea5e9"/>' .
            '<path d="M47 6h25a18 18 0 0 1 0 36H47z" fill="#2563eb"/>' .
            '<path d="M47 8v32" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".9"/>' .
            '<text x="98" y="30" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#1e5f9f">MedCapsule</text>' .
            '</svg>'
        );
    }
}
