<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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
        // LETHAL CACHING: Ultra-aggressive caching for registration form data
        $cacheKey = 'registration_form_data_' . now()->format('Y-m-d');
        
        $formData = Cache::remember($cacheKey, 86400, function () {
            return [
                'universities' => $this->getUniversitiesList(),
                'countries' => $this->getCountriesList(),
                'terms_version' => config('app.terms_version', '1.0')
            ];
        });
        
        return view('auth.register', $formData);
    }

    /**
     * LETHAL CACHING: Get universities list
     */
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

    /**
     * LETHAL CACHING: Get countries list
     */
    private function getCountriesList(): array
    {
        return [
            'Ghana', 'Nigeria', 'Kenya', 'South Africa', 'Other'
        ];
    }

    public function register(\App\Http\Requests\RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'number' => $validated['number'],
                'university' => $validated['university'],
                'password' => Hash::make($validated['password']),
                'survey_completed' => false,
                'free_courses_unlocked' => false,
            ]);

            // Log the user in
            Auth::login($user);
            
            // CRITICAL FIX: Regenerate session after registration
            request()->session()->regenerate();
            request()->session()->save();
            
            // CRITICAL FIX: Store authentication flag in session
            request()->session()->put('auth.user_id', $user->id);
            request()->session()->put('auth.logged_in_at', now()->timestamp);
            request()->session()->save();

            app(SessionSecurityService::class)->handleNewLogin($user, $request);

            if (Auth::check()) {
                // 🔔 TRIGGER MEDCAPSULE WELCOME NOTIFICATION FOR NEW USER
                try {
                    \App\Services\NotificationTriggerService::onUserWelcome($user);
                } catch (\Exception $e) {
                    \Log::warning('Failed to send welcome notification for new user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with registration even if notification fails
                }
                
                return redirect()->intended(route('courses.index'))
                    ->with('success', 'Welcome to MedCapsule!');
            } else {
                Log::error('Auth check failed after login', ['user_id' => $user->id]);
                throw new \Exception('Failed to authenticate user after creation');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Registration validation failed', [
                'fields' => array_keys($e->errors()),
                'ip' => $request->ip(),
            ]);

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput($request->except('password', 'password_confirmation'));

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'ip' => $request->ip(),
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }
}
