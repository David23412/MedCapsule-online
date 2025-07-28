<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\UserActivityLogService;

class TrackUserActivity
{
    protected $activityLogService;

    public function __construct(UserActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function handle(Request $request, Closure $next)
    {
        $fingerprint = $this->generateFingerprint($request);
        
        if (Auth::check()) {
            // For logged-in users, update their session
            DB::table('sessions')
                ->where('id', session()->getId())
                ->update([
                    'user_id' => Auth::id(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now()->timestamp,
                    'is_visitor' => false,
                    'fingerprint' => $fingerprint
                ]);

            // Log detailed activity for authenticated users
            $this->logUserActivity($request);
        } else {
            // For visitors, check if they already have a session with the same fingerprint
            $existingSession = DB::table('sessions')
                ->where('fingerprint', $fingerprint)
                ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                ->first();

            if (!$existingSession) {
                // Only create a new visitor session if no recent session exists with the same fingerprint
                DB::table('sessions')
                    ->where('id', session()->getId())
                    ->update([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_activity' => now()->timestamp,
                        'is_visitor' => true,
                        'fingerprint' => $fingerprint
                    ]);
            }
        }

        return $next($request);
    }

    private function generateFingerprint(Request $request)
    {
        // Create a unique fingerprint based on IP and user agent
        // This helps prevent duplicate counting from the same user on different tabs
        $data = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];
        
        return hash('sha256', json_encode($data));
    }

    /**
     * Log user activity based on the request
     */
    private function logUserActivity(Request $request): void
    {
        try {
            // Only log for non-admin routes and non-API routes
            if ($request->is('admin/*') || $request->is('api/*')) {
                return;
            }

            $user = Auth::user();
            $path = $request->path();
            $method = $request->method();

            // Determine action based on route
            $action = $this->determineAction($path, $method);
            
            if ($action) {
                $data = [
                    'path' => $path,
                    'method' => $method,
                    'referrer' => $request->header('referer'),
                ];

                // Add specific data based on action
                switch ($action) {
                    case 'course_view':
                        $courseId = $request->route('course') ?? $request->route('id');
                        if ($courseId) {
                            $data['course_id'] = $courseId;
                        }
                        break;
                        
                    case 'topic_view':
                        $topicId = $request->route('topic');
                        if ($topicId) {
                            $data['topic_id'] = $topicId;
                        }
                        break;
                        
                    case 'quiz_attempt':
                        $topicId = $request->route('topic');
                        if ($topicId) {
                            $data['topic_id'] = $topicId;
                        }
                        break;
                }

                $this->activityLogService->log($action, $data, $user);
            }
        } catch (\Exception $e) {
            // Don't let logging errors break the application
            \Log::warning('Failed to log user activity', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'url' => $request->fullUrl()
            ]);
        }
    }

    /**
     * Determine the action based on the request path and method
     */
    private function determineAction(string $path, string $method): ?string
    {
        // Course-related actions
        if (preg_match('/^courses\/\d+$/', $path) && $method === 'GET') {
            return 'course_view';
        }
        
        if (preg_match('/^courses\/\d+\/enroll$/', $path) && $method === 'POST') {
            return 'course_enroll';
        }

        // Topic-related actions
        if (preg_match('/^topics\/\d+$/', $path) && $method === 'GET') {
            return 'topic_view';
        }

        // Quiz-related actions
        if (preg_match('/^quiz/', $path)) {
            return 'quiz_attempt';
        }

        // Payment-related actions
        if (preg_match('/^payments/', $path)) {
            return 'payment_action';
        }

        // Profile-related actions
        if (preg_match('/^profile/', $path)) {
            return 'profile_view';
        }

        // Dashboard actions
        if ($path === 'dashboard' || $path === 'home') {
            return 'dashboard_view';
        }

        // Default to page view for other GET requests
        if ($method === 'GET') {
            return 'page_view';
        }

        return null;
    }
} 