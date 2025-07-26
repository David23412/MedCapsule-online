<?php

namespace App\Http\Controllers\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkAssignmentRequest;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED ASSIGNMENT BULK CONTROLLER 💀
 * Handles bulk operations with F-16 RAPTOR SPEED
 */
class AssignmentBulkController extends Controller
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->middleware(['auth']);
        $this->assignmentService = $assignmentService;
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (in_array($user->role, ['admin', 'tutor'])) {
                return $next($request);
            }
            abort(403, 'Unauthorized access.');
        });
    }

    /**
     * Show bulk assignment creation form - LETHAL CACHED
     */
    public function bulkCreate(): View
    {
        // LETHAL CACHING: Ultra-aggressive caching for bulk form data
        $cacheKey = 'bulk_assignment_form_' . auth()->id() . '_' . now()->format('Y-m-d-H');
        
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () {
            return [
                'courses' => $this->assignmentService->getAvailableCourses(),
                'topics' => $this->assignmentService->getAvailableTopics(),
                'students' => $this->assignmentService->getAvailableStudents()
            ];
        });
        
        return view('assignments.bulk-create', $data);
    }

    /**
     * Process bulk assignment creation
     */
    public function bulkStore(BulkAssignmentRequest $request): RedirectResponse
    {
        try {
            $result = $this->assignmentService->createBulkAssignments($request->validated());
            
            return redirect()->route('assignments.index')
                ->with('success', "Successfully created {$result['created']} assignments.");
        } catch (\Exception $e) {
            Log::error('Bulk assignment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()
                ->with('error', 'Failed to create bulk assignments. Please try again.');
        }
    }
}