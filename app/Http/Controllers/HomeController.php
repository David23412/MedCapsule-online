<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Get total counts for each category
        $statistics = [
            'quizzes' => Topic::where('case_type', 'quiz')->count(),
            'cases' => Topic::where('case_type', 'cases')->count(),
            'practical' => Topic::where('case_type', 'practical')->count(),
            'questions' => DB::table('questions')->count(),
            'enrolled_students' => DB::table('course_user')
                ->where('enrollment_status', 'active')
                ->distinct('user_id')
                ->count('user_id')
        ];

        // Get ALL courses with their counts
        $featuredCourses = Course::select('courses.*')
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN topics.case_type = "quiz" THEN topics.id END) as quiz_count,
                COUNT(DISTINCT CASE WHEN topics.case_type = "cases" THEN topics.id END) as case_count,
                COUNT(DISTINCT CASE WHEN topics.case_type = "practical" THEN topics.id END) as practical_count,
                COUNT(DISTINCT questions.id) as question_count,
                COUNT(DISTINCT CASE WHEN course_user.enrollment_status = "active" THEN course_user.user_id END) as enrolled_count
            ')
            ->leftJoin('topics', 'courses.id', '=', 'topics.course_id')
            ->leftJoin('questions', 'topics.id', '=', 'questions.topic_id')
            ->leftJoin('course_user', function($join) {
                $join->on('courses.id', '=', 'course_user.course_id')
                     ->where('course_user.enrollment_status', '=', 'active');
            })
            ->groupBy('courses.id', 'courses.name', 'courses.description', 'courses.image', 'courses.color', 'courses.price', 'courses.currency', 'courses.created_at', 'courses.updated_at', 'courses.academic_year')
            ->orderBy('courses.name')
            ->get();

        return view('home', compact('statistics', 'featuredCourses'));
    }
}
