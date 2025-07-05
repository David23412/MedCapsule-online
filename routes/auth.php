<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    LoginController,
    RegisterController
};

// Public Authentication Routes (Guest Access)
Route::middleware('guest')->group(function () {
    // Show login form and login submission
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    // Show registration form and register submission
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});

// Authenticated Routes (Only for logged-in users)
Route::middleware('auth')->group(function () {
    // Logout route
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
