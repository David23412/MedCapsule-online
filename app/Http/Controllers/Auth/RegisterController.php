<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RegisterRequest;
use App\Services\SessionSecurityService;

class RegisterController extends Controller
{
    public function __construct()
    {
        // DON'T call parent::__construct() to avoid auth middleware
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        return view('auth.register', ['universities' => $this->getUniversitiesList()]);
    }

    private function getUniversitiesList(): array
    {
        return [
            [
                'value' => 'Assiut University',
                'label' => 'Assiut University - جامعة أسيوط',
            ],
            [
                'value' => 'Cairo University',
                'label' => 'Cairo University - جامعة القاهرة',
            ],
            [
                'value' => 'Al Kasr Al Ainy',
                'label' => 'Al Kasr Al Ainy - القصر العيني',
            ],
            [
                'value' => 'Alexandria University',
                'label' => 'Alexandria University - جامعة الإسكندرية',
            ],
        ];
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        try {
            // Account creation and required session setup must succeed together.
            $user = DB::transaction(function () use ($validated, $request) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'number' => $validated['number'],
                    'university' => $validated['university'],
                    'password' => Hash::make($validated['password']),
                    'survey_completed' => false,
                    'free_courses_unlocked' => false,
                ]);
                Auth::login($user);
                $request->session()->regenerate();
                $request->session()->put('auth.user_id', $user->id);
                $request->session()->put('auth.logged_in_at', now()->timestamp);
                app(SessionSecurityService::class)->handleNewLogin($user, $request);
                $request->session()->save();

                return $user;
            });
        } catch (\Throwable $e) {
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            Log::error('Registration failed', [
                'exception' => get_class($e),
                'ip' => $request->ip(),
            ]);

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Registration failed. Please try again.']);
        }

        try {
            \App\Services\NotificationTriggerService::onUserWelcome($user);
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome notification for new user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->intended(route('courses.index'))
            ->with('success', 'Welcome to MedCapsule!');
    }
}
