<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testRandomQuiz()
    {
        return view('quiz.random_quiz', [
            'questions' => collect([]),
            'selectedCourses' => collect([(object)['id' => 1, 'name' => 'Test Course']]),
            'duration_minutes' => 15,
            'requested_questions' => 10,
            'actual_questions' => 10
        ]);
    }
}
