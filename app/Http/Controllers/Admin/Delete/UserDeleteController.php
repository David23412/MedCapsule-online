<?php

namespace App\Http\Controllers\Admin\Delete;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Cache};
use App\Models\{User, QuizAttempt, Mistake, UserTopicProgress};
use App\Services\Core\UltraConsolidatedCacheService;
use Carbon\Carbon;

/**
 * 🚀 PERFORMANCE BEAST: User Deletion Controller
 * Handles: User deletion with all related data cleanup
 * Lines: ~70 (well under 200 limit)
 */
class UserDeleteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Delete a user with all related data - LETHAL OPTIMIZED
     */
    public function deleteUser(Request $request, $id)
    {
        try {
            // Prevent self-deletion
            if ($id == auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 400);
            }

            DB::beginTransaction();

            $user = User::select('id', 'name', 'email', 'role')
                ->findOrFail($id);

            // Prevent deletion of other admins
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            if ($user->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete admin users'
                ], 400);
            }

            $userName = $user->name;
            $userEmail = $user->email;

            Log::warning('User deletion initiated', [
                'user_id' => $id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'deleted_by' => auth()->id(),
                'timestamp' => Carbon::now()
            ]);

            // NUCLEAR CASCADE DELETE: Optimized deletion order
            $this->performUserCascadeDelete($user);

            // NUCLEAR CACHE INVALIDATION
            $this->invalidateUserCaches($id);

            DB::commit();

            Log::info('User deleted successfully', [
                'user_id' => $id,
                'user_name' => $userName,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "User '{$userName}' and all related data deleted successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    private function performUserCascadeDelete(User $user): void
    {
        // Delete in optimized order to avoid foreign key constraints
        DB::table('quiz_attempts')->where('user_id', $user->id)->delete();
        DB::table('mistakes')->where('user_id', $user->id)->delete();
        DB::table('user_topic_progress')->where('user_id', $user->id)->delete();
        DB::table('course_user')->where('user_id', $user->id)->delete();
        DB::table('payments')->where('user_id', $user->id)->delete();
        DB::table('notifications')->where('notifiable_id', $user->id)->delete();
        $user->delete();
    }

    private function invalidateUserCaches(int $userId): void
    {
        $cacheService = app(UltraConsolidatedCacheService::class);
        $cacheService->invalidateUserCache($userId);
        Cache::tags(['users', "user_{$userId}"])->flush();
    }
}