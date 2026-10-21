<?php

namespace App\Http\Controllers;

use App\Models\{Assignment, User};
use Illuminate\Support\Facades\DB;
use App\Services\UniversalNotificationService;
use Illuminate\Http\Request;

class StudentAssignmentController extends Controller
{
    private function assignments()
    {
        $userId = auth()->id();
        return Assignment::whereHas('assignedUsers', fn ($query) => $query->where('users.id', $userId))
            ->with(['course', 'topic',
                'assignedUsers' => fn ($query) => $query->where('users.id', $userId),
                'quizAttempts' => fn ($query) => $query->where('user_id', $userId),
            ]);
    }

    private function ownedAssignment(Assignment $assignment): Assignment
    {
        return $this->assignments()->findOrFail($assignment->id);
    }

    private function prepareForDisplay(Assignment $assignment): Assignment
    {
        $progress = $assignment->getUserProgress(auth()->id());
        $assignment->deadline = $assignment->getDeadlineForUser(auth()->id());
        $assignment->status = $progress['completed'] ? 'completed' : ($assignment->deadline->isPast() ? 'overdue' : 'active');
        $assignment->score_percentage = $progress['percentage'];
        $assignment->completed_at = $progress['latest_attempt'];
        $assignment->setAttribute('student_progress', $progress);
        return $assignment;
    }

    public function index()
    {
        $assignments = $this->assignments()->orderBy('deadline')->get()->map(fn ($assignment) => $this->prepareForDisplay($assignment));
        $groupedAssignments = collect(['urgent', 'active', 'overdue', 'completed'])
            ->mapWithKeys(fn ($status) => [$status => collect()])->all();
        foreach ($assignments as $assignment) {
            $status = $assignment->status;
            if ($status === 'active' && $assignment->deadline->lte(now()->addDay())) $status = 'urgent';
            $groupedAssignments[$status]->push($assignment);
        }
        $analytics = ['completion_rate' => $assignments->isEmpty() ? 0 : 100 * $groupedAssignments['completed']->count() / $assignments->count()];
        return view('student.assignments.index', compact('groupedAssignments', 'analytics'));
    }

    public function show(Assignment $assignment)
    {
        $assignment = $this->prepareForDisplay($this->ownedAssignment($assignment));
        return view('student.assignments.show', compact('assignment'));
    }

    public function history()
    {
        $assignments = $this->assignments()->whereHas('quizAttempts', fn ($query) => $query->where('user_id', auth()->id()))
            ->orderByDesc('id')->paginate(20);
        $assignments->getCollection()->transform(fn ($assignment) => $this->prepareForDisplay($assignment));
        return view('student.assignments.history', compact('assignments'));
    }

    public function start(Request $request, Assignment $assignment)
    {
        $assignment = $this->ownedAssignment($assignment);
        $access = app(TopicAccessController::class)->checkAccess($assignment->topic);
        abort_unless($access['can_access'], $access['status_code'], $access['reason'] ?? 'Access denied.');
        if (!$assignment->canUserStart(auth()->user())) {
            return response()->json(['success' => false, 'message' => $assignment->getLockReasonForUser(auth()->user()) ?? 'This assignment is unavailable.'], 422);
        }
        return redirect()->route('quiz.start', [
            'course' => $assignment->course_id, 'topic' => $assignment->topic_id,
            'assignment_id' => $assignment->id,
            'sources' => [$assignment->isSourceSpecific() ? $assignment->assigned_source : 'all'],
        ]);
    }

    public function complete(Request $request, Assignment $assignment)
    {
        $assignment = $this->ownedAssignment($assignment);
        $progress = $assignment->getUserProgress(auth()->id());
        return response()->json([
            'success' => $progress['attempts_count'] > 0, 'progress' => $progress,
            'message' => $progress['attempts_count'] > 0 ? 'Your assignment quiz result is recorded.' : 'Complete the assignment quiz to record your result.',
        ], $progress['attempts_count'] > 0 ? 200 : 422);
    }

    public function getCountdownData(Assignment $assignment)
    {
        $assignment = $this->ownedAssignment($assignment);
        $deadline = $assignment->getDeadlineForUser(auth()->id());
        return response()->json(['countdown' => max(0, (int) now()->diffInSeconds($deadline, false)), 'deadline' => $deadline->toISOString()]);
    }

    public function getStats()
    {
        $total = $this->assignments()->count();
        $completed = $this->assignments()->whereIn('assignments.id', DB::table('quiz_attempts')
            ->select('assignment_id')->where('user_id', auth()->id())->whereNotNull('assignment_id')
            ->groupBy('assignment_id')->havingRaw('SUM(total_questions) > 0 AND SUM(score) * 100.0 / SUM(total_questions) >= 50'))->count();
        return response()->json(['stats' => ['total' => $total, 'completed' => $completed, 'completion_rate' => $total ? 100 * $completed / $total : 0]]);
    }

    public function getAssignmentsForCourse($courseId)
    {
        return response()->json($this->assignments()->where('course_id', $courseId)->orderBy('deadline')->get()
            ->map(fn ($assignment) => $this->prepareForDisplay($assignment)));
    }

    public function getProgressWidget()
    {
        $stats = $this->getStats()->getData(true)['stats'];
        return response()->json(['progress' => $stats['completion_rate'], 'total' => $stats['total'], 'completed' => $stats['completed']]);
    }

    public function requestExtension(Request $request, Assignment $assignment)
    {
        $assignment = $this->ownedAssignment($assignment);
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'requested_deadline' => 'required|date|after:now',
        ]);
        $recipients = auth()->user()->assignedTutors()->whereHas('courses', fn ($query) => $query->where('courses.id', $assignment->course_id))->get();
        if ($recipients->isEmpty()) $recipients = User::where('role', User::ROLE_ADMIN)->get();
        if ($recipients->isEmpty()) return response()->json(['success' => false, 'message' => 'No tutor is available to receive this request.'], 422);
        foreach ($recipients as $recipient) {
            UniversalNotificationService::create($recipient, 'general_announcement',
                'Extension request: assignment #'.$assignment->id.' · student #'.auth()->id(),
                htmlspecialchars(auth()->user()->name.' requests a deadline of '.$validated['requested_deadline'].': '.$validated['reason'], ENT_QUOTES, 'UTF-8'),
                ['assignment_id' => $assignment->id, 'student_id' => auth()->id(), 'requested_deadline' => $validated['requested_deadline'],
                    'url' => route('assignments.edit', $assignment)], UniversalNotificationService::PRIORITY_HIGH);
        }
        return response()->json(['success' => true, 'message' => 'Your extension request was sent for review. Your deadline changes only after approval.']);
    }
}
