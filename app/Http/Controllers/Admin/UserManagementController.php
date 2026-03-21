<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCourseRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED USER MANAGEMENT CONTROLLER 💀
 * Handles user operations with F-16 RAPTOR SPEED
 */
class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Search users with aggressive caching
     */
    public function searchUsers(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255'
        ]);
        
        $query = $validated['query'] ?? '';
        
        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = Cache::remember("user_search_{$query}", 300, function() use ($query) {
            return User::select('id', 'name', 'email', 'role')
                ->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(20)
                ->get();
        });

        return response()->json(['users' => $users]);
    }

    /**
     * Toggle admin status
     */
    public function toggleAdmin(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'make_admin' => 'required|boolean'
            ]);

            // LETHAL EAGER LOADING: Load user with course relationships
            $user = User::select('id', 'name', 'email', 'role', 'created_at')
                ->with([
                    'courses:id,name,price',
                    'payments:id,user_id,course_id,status,amount',
                    'assignments:id,user_id,status,created_at'
                ])
                ->findOrFail($validated['user_id']);
            
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own admin status.'
                ], 400);
            }

        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            $user->role = $validated['make_admin'] ? 'admin' : 'student';
            $user->save();

        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            // Clear user cache
            Cache::forget("user_search_*");

            Log::info('Admin status toggled', [
                'target_user_id' => $user->id,
                'new_role' => $user->role,
                'admin_id' => auth()->id(),
                'authorized' => auth()->user()->can('assign-roles')
            ]);

            return response()->json([
                'success' => true,
                'message' => $validated['make_admin'] 
                    ? 'User promoted to admin successfully.' 
                    : 'Admin privileges revoked successfully.',
                'new_role' => $user->role
            ]);

        } catch (\Exception $e) {
            Log::error('Admin toggle failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update admin status.'
            ], 500);
        }
    }
}