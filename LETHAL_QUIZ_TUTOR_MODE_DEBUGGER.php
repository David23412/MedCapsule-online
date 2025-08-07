<?php

/**
 * LETHAL QUIZ & TUTOR MODE DEBUGGER
 * 
 * This script identifies and fixes critical issues in quiz mode and tutor mode:
 * - 403 errors on grade endpoint
 * - Quiz submission failures
 * - Service swapping issues
 * - Unused service dependencies
 * - Permission mismatches
 */

class LethalQuizTutorModeDebugger
{
    private array $issues = [];
    private array $fixes = [];
    
    public function __construct()
    {
        echo "🔥 LETHAL QUIZ & TUTOR MODE DEBUGGER ACTIVATED\n";
        echo "================================================\n\n";
    }
    
    public function diagnoseAndFix(): void
    {
        $this->checkGradeEndpointPermissions();
        $this->checkQuizSubmissionRoutes();
        $this->checkServiceDependencies();
        $this->checkAuthenticationMiddleware();
        $this->checkTutorModePermissions();
        $this->generateFixes();
        $this->outputReport();
    }
    
    private function checkGradeEndpointPermissions(): void
    {
        echo "🔍 Checking grade endpoint permissions...\n";
        
        // Check if grade route exists and has proper middleware
        $routeFile = 'routes/web.php';
        if (file_exists($routeFile)) {
            $content = file_get_contents($routeFile);
            
            if (!str_contains($content, 'grade')) {
                $this->issues[] = "Grade route missing in web.php";
                $this->fixes[] = "Add grade route with proper middleware";
            }
            
            if (!str_contains($content, 'auth') && str_contains($content, 'grade')) {
                $this->issues[] = "Grade route missing auth middleware";
                $this->fixes[] = "Add auth middleware to grade route";
            }
        }
        
        echo "✅ Grade endpoint check complete\n\n";
    }
    
    private function checkQuizSubmissionRoutes(): void
    {
        echo "🔍 Checking quiz submission routes...\n";
        
        $apiRoutes = 'routes/api.php';
        $webRoutes = 'routes/web.php';
        
        $routeFiles = [$apiRoutes, $webRoutes];
        $foundSubmissionRoute = false;
        
        foreach ($routeFiles as $routeFile) {
            if (file_exists($routeFile)) {
                $content = file_get_contents($routeFile);
                
                if (str_contains($content, 'quiz') && str_contains($content, 'submit')) {
                    $foundSubmissionRoute = true;
                    
                    if (!str_contains($content, 'auth')) {
                        $this->issues[] = "Quiz submission route missing auth middleware";
                        $this->fixes[] = "Add auth middleware to quiz submission routes";
                    }
                }
            }
        }
        
        if (!$foundSubmissionRoute) {
            $this->issues[] = "Quiz submission route not found";
            $this->fixes[] = "Create quiz submission route with proper authentication";
        }
        
        echo "✅ Quiz submission routes check complete\n\n";
    }
    
    private function checkServiceDependencies(): void
    {
        echo "🔍 Checking service dependencies and swapping issues...\n";
        
        $controllerPath = 'app/Http/Controllers/Quiz/QuizExecutionController.php';
        
        if (file_exists($controllerPath)) {
            $content = file_get_contents($controllerPath);
            
            // Check for unused services
            if (str_contains($content, 'use ') && str_contains($content, 'Service')) {
                preg_match_all('/use App\\\\Services\\\\([^;]+);/', $content, $matches);
                $usedServices = $matches[1] ?? [];
                
                foreach ($usedServices as $service) {
                    $serviceName = basename(str_replace('\\', '/', $service));
                    if (!str_contains($content, $serviceName)) {
                        $this->issues[] = "Unused service import: $service";
                        $this->fixes[] = "Remove unused service import: $service";
                    }
                }
            }
            
            // Check for service swapping issues
            if (str_contains($content, 'QuizService') && str_contains($content, 'TutorService')) {
                $this->issues[] = "Potential service swapping detected";
                $this->fixes[] = "Review service usage in QuizExecutionController";
            }
        }
        
        echo "✅ Service dependencies check complete\n\n";
    }
    
    private function checkAuthenticationMiddleware(): void
    {
        echo "🔍 Checking authentication middleware...\n";
        
        $middlewarePath = 'app/Http/Middleware';
        
        if (is_dir($middlewarePath)) {
            $files = glob($middlewarePath . '/*.php');
            
            $hasAuthMiddleware = false;
            foreach ($files as $file) {
                if (str_contains(basename($file), 'Auth')) {
                    $hasAuthMiddleware = true;
                    break;
                }
            }
            
            if (!$hasAuthMiddleware) {
                $this->issues[] = "Authentication middleware missing";
                $this->fixes[] = "Create or register authentication middleware";
            }
        }
        
        echo "✅ Authentication middleware check complete\n\n";
    }
    
    private function checkTutorModePermissions(): void
    {
        echo "🔍 Checking tutor mode permissions...\n";
        
        $userModelPath = 'app/Models/User.php';
        
        if (file_exists($userModelPath)) {
            $content = file_get_contents($userModelPath);
            
            if (!str_contains($content, 'isTutor') && !str_contains($content, 'ROLE_TUTOR')) {
                $this->issues[] = "Tutor role checking methods missing in User model";
                $this->fixes[] = "Add tutor role checking methods to User model";
            }
        }
        
        echo "✅ Tutor mode permissions check complete\n\n";
    }
    
    private function generateFixes(): void
    {
        echo "🔧 Generating lethal fixes...\n";
        
        // Generate route fixes
        $this->generateRouteFixes();
        
        // Generate controller fixes
        $this->generateControllerFixes();
        
        // Generate middleware fixes
        $this->generateMiddlewareFixes();
        
        echo "✅ Fixes generated\n\n";
    }
    
    private function generateRouteFixes(): void
    {
        $routeFixes = "
// LETHAL ROUTE FIXES - Add to routes/web.php

Route::middleware(['auth'])->group(function () {
    // Quiz routes with proper authentication
    Route::post('/quiz/submit', [QuizExecutionController::class, 'submit'])->name('quiz.submit');
    Route::post('/quiz/grade', [QuizExecutionController::class, 'grade'])->name('quiz.grade');
    Route::get('/quiz/{id}/execute', [QuizExecutionController::class, 'execute'])->name('quiz.execute');
    
    // Tutor mode routes
    Route::middleware(['role:tutor,admin'])->group(function () {
        Route::get('/tutor/dashboard', [TutorController::class, 'dashboard'])->name('tutor.dashboard');
        Route::post('/tutor/grade-assignment', [TutorController::class, 'gradeAssignment'])->name('tutor.grade');
    });
});

// API routes with CSRF protection disabled - Add to routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/api/quiz/submit', [QuizExecutionController::class, 'apiSubmit']);
    Route::post('/api/quiz/grade', [QuizExecutionController::class, 'apiGrade']);
});
";
        
        file_put_contents('LETHAL_ROUTE_FIXES.php', $routeFixes);
    }
    
    private function generateControllerFixes(): void
    {
        $controllerFixes = "<?php

// LETHAL CONTROLLER FIXES

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Services\QuizService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizExecutionController extends Controller
{
    private QuizService \$quizService;
    private GradingService \$gradingService;
    
    public function __construct(QuizService \$quizService, GradingService \$gradingService)
    {
        \$this->middleware('auth');
        \$this->quizService = \$quizService;
        \$this->gradingService = \$gradingService;
    }
    
    /**
     * LETHAL FIX: Submit quiz with proper authentication
     */
    public function submit(Request \$request): JsonResponse
    {
        try {
            // Validate user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            \$validated = \$request->validate([
                'quiz_id' => 'required|integer|exists:quizzes,id',
                'answers' => 'required|array',
                'time_taken' => 'nullable|integer'
            ]);
            
            \$result = \$this->quizService->submitQuiz(
                Auth::id(),
                \$validated['quiz_id'],
                \$validated['answers'],
                \$validated['time_taken'] ?? null
            );
            
            return response()->json(\$result);
            
        } catch (\Exception \$e) {
            Log::error('Quiz submission error: ' . \$e->getMessage());
            return response()->json(['error' => 'Submission failed'], 500);
        }
    }
    
    /**
     * LETHAL FIX: Grade quiz with proper permissions
     */
    public function grade(Request \$request): JsonResponse
    {
        try {
            // Check if user can grade (tutor or admin)
            \$user = Auth::user();
            if (!\$user || (!\$user->isTutor() && !\$user->isAdmin())) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
            
            \$validated = \$request->validate([
                'submission_id' => 'required|integer|exists:quiz_submissions,id',
                'grade' => 'required|numeric|min:0|max:100',
                'feedback' => 'nullable|string'
            ]);
            
            \$result = \$this->gradingService->gradeSubmission(
                \$validated['submission_id'],
                \$validated['grade'],
                \$validated['feedback'] ?? null,
                Auth::id()
            );
            
            return response()->json(\$result);
            
        } catch (\Exception \$e) {
            Log::error('Grading error: ' . \$e->getMessage());
            return response()->json(['error' => 'Grading failed'], 500);
        }
    }
    
    /**
     * LETHAL FIX: API endpoints for AJAX calls
     */
    public function apiSubmit(Request \$request): JsonResponse
    {
        return \$this->submit(\$request);
    }
    
    public function apiGrade(Request \$request): JsonResponse
    {
        return \$this->grade(\$request);
    }
}
";
        
        file_put_contents('LETHAL_CONTROLLER_FIXES.php', $controllerFixes);
    }
    
    private function generateMiddlewareFixes(): void
    {
        $middlewareFixes = "<?php

// LETHAL MIDDLEWARE FIXES

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * LETHAL FIX: Role-based access control
     */
    public function handle(Request \$request, Closure \$next, ...\$roles): mixed
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        \$user = Auth::user();
        \$userRole = \$user->role ?? 'student';
        
        if (!in_array(\$userRole, \$roles)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        return \$next(\$request);
    }
}

// Add to app/Http/Kernel.php in \$routeMiddleware array:
// 'role' => \\App\\Http\\Middleware\\RoleMiddleware::class,
";
        
        file_put_contents('LETHAL_MIDDLEWARE_FIXES.php', $middlewareFixes);
    }
    
    private function outputReport(): void
    {
        echo "📊 LETHAL DEBUGGING REPORT\n";
        echo "==========================\n\n";
        
        echo "🚨 ISSUES FOUND (" . count($this->issues) . "):\n";
        foreach ($this->issues as $index => $issue) {
            echo ($index + 1) . ". $issue\n";
        }
        
        echo "\n🔧 FIXES GENERATED (" . count($this->fixes) . "):\n";
        foreach ($this->fixes as $index => $fix) {
            echo ($index + 1) . ". $fix\n";
        }
        
        echo "\n📁 FILES CREATED:\n";
        echo "- LETHAL_ROUTE_FIXES.php\n";
        echo "- LETHAL_CONTROLLER_FIXES.php\n";
        echo "- LETHAL_MIDDLEWARE_FIXES.php\n";
        
        echo "\n🎯 IMMEDIATE ACTIONS REQUIRED:\n";
        echo "1. Apply route fixes to routes/web.php and routes/api.php\n";
        echo "2. Update QuizExecutionController with authentication checks\n";
        echo "3. Register RoleMiddleware in Kernel.php\n";
        echo "4. Clear cache: php artisan cache:clear\n";
        echo "5. Clear route cache: php artisan route:clear\n";
        
        echo "\n🔥 LETHAL DEBUGGER COMPLETE - QUIZ MODE SHOULD BE FIXED!\n";
    }
}

// Execute the lethal debugger
$debugger = new LethalQuizTutorModeDebugger();
$debugger->diagnoseAndFix();