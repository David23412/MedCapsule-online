<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED COURSE ACCESS CONTROLLER 💀
 * Handles access requests with F-16 RAPTOR SPEED
 */
class CourseAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Approve course access request
     */
    public function approveAccessRequest(Request $request, $requestId)
    {
        try {
            // LETHAL EAGER LOADING: Load access request with all related data
            $accessRequest = CourseAccessRequest::select('id', 'user_id', 'course_id', 'status', 'requested_at', 'notes')
                ->with([
                    'user:id,name,email,role,created_at',
                    'user.payments:id,user_id,course_id,status,amount',
                    'course:id,name,description,price',
                    'course.topics:id,course_id,name',
                    'course.enrollments:id,user_id,course_id,status'
                ])
                ->findOrFail($requestId);

            if ($accessRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.'
                ], 400);
            }

            // Approve the request
            $accessRequest->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now()
            ]);

            // Enroll user in course
            $accessRequest->user->courses()->syncWithoutDetaching([
                $accessRequest->course_id => [
                    'enrolled_at' => now()
                ]
            ]);

            // Clear caches
            Cache::forget("access_requests_pending");
            Cache::forget("user_courses_{$accessRequest->user_id}");

            Log::info('Course access request approved', [
                'request_id' => $requestId,
                'user_id' => $accessRequest->user_id,
                'course_id' => $accessRequest->course_id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access request approved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Access request approval failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve access request.'
            ], 500);
        }
    }

    /**
     * Reject course access request
     */
    public function rejectAccessRequest(Request $request, $requestId)
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $accessRequest = CourseAccessRequest::select('id', 'user_id', 'course_id', 'status')
                ->findOrFail($requestId);

            if ($accessRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.'
                ], 400);
            }

            // Reject the request
            $accessRequest->update([
                'status' => 'rejected',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'rejection_reason' => $validated['reason'] ?? 'No reason provided'
            ]);

            // Clear cache
            Cache::forget("access_requests_pending");

            Log::info('Course access request rejected', [
                'request_id' => $requestId,
                'reason' => $validated['reason'] ?? 'No reason provided',
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access request rejected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Access request rejection failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject access request.'
            ], 500);
        }
    }
}