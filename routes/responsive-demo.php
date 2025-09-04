<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Responsive Design Demo Routes
|--------------------------------------------------------------------------
|
| These routes are for demonstrating the responsive design components
| without interfering with the existing application routes.
|
*/

Route::prefix('demo')->name('demo.')->group(function () {
    
    // Responsive Navigation Demo
    Route::get('/responsive-navigation', function () {
        return view('demo.responsive-navigation');
    })->name('responsive-navigation');
    
    // Future demo routes can be added here
    // Route::get('/responsive-forms', ...)->name('responsive-forms');
    // Route::get('/responsive-quiz', ...)->name('responsive-quiz');
    
});