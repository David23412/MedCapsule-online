<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        Log::info('Registration attempt', ['email' => $request->email]);

        try {
            // Validate the input data
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'university' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'terms' => ['required', 'accepted'],
            ]);

            Log::info('Validation passed', ['email' => $validated['email']]);

            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'university' => $validated['university'],
                'password' => Hash::make($validated['password']),
                'course_progress' => [],
                'is_admin' => false, // Explicitly set is_admin
            ]);

            Log::info('User created', ['user_id' => $user->id]);

            // Auto-enroll the user in ALL free courses
            try {
                $enrolledCount = $user->enrollInAllFreeCourses();
                if ($enrolledCount > 0) {
                    Log::info('User auto-enrolled in free courses', [
                        'user_id' => $user->id,
                        'courses_enrolled' => $enrolledCount
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to auto-enroll user in free courses', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with registration even if auto-enrollment fails
            }

            // Log the user in
            Auth::login($user);

            Log::info('User logged in', ['user_id' => $user->id]);

            if (Auth::check()) {
                Log::info('Auth check passed', ['user_id' => $user->id]);
                return redirect()->intended(route('courses.index'))
                    ->with('success', 'Welcome to MedCapsule!');
            } else {
                Log::error('Auth check failed after login', ['user_id' => $user->id]);
                throw new \Exception('Failed to authenticate user after creation');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput($request->except('password', 'password_confirmation'));

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
}