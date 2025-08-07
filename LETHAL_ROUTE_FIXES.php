
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
