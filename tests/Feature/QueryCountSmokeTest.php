<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Tests\Integration\Concerns\PerformanceTestHelpers;

class QueryCountSmokeTest extends TestCase
{
    use RefreshDatabase, PerformanceTestHelpers;

    /** @test */
    public function courses_index_query_count_is_optimized()
    {
        // Create test data
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Course::factory()->count(5)->create();
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('courses.index'));
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Use helper method for consistent threshold checking
        $this->assertQueryCountWithinThreshold('courses.index', $queries, 'Course listing with 5 courses');
    }

    /** @test */
    public function dashboard_home_query_count_is_optimized()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $courses = Course::factory()->count(3)->create();
        
        // Enroll user in courses
        foreach ($courses as $course) {
            $user->enrollInCourse($course->id);
        }
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('dashboard'));
        $queries = DB::getQueryLog();
        
        // OPTIMIZED TARGET: Should be <= 8 queries regardless of enrolled courses
        $this->assertLessThanOrEqual(8, count($queries),
            'dashboard should use <= 8 queries. Actual: ' . count($queries));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function course_show_query_count_is_optimized()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::factory()->create();
        Topic::factory()->count(5)->for($course)->create();
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('courses.show', $course));
        $queries = DB::getQueryLog();
        
        // OPTIMIZED TARGET: Should be <= 6 queries regardless of topic count
        $this->assertLessThanOrEqual(6, count($queries),
            'course.show should use <= 6 queries. Actual: ' . count($queries));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function user_profile_query_count_is_optimized()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $courses = Course::factory()->count(3)->create();
        
        foreach ($courses as $course) {
            $user->enrollInCourse($course->id);
        }
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('profile.show'));
        $queries = DB::getQueryLog();
        
        // OPTIMIZED TARGET: Should be <= 5 queries regardless of course count
        $this->assertLessThanOrEqual(5, count($queries),
            'profile.show should use <= 5 queries. Actual: ' . count($queries));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function topic_show_query_count_is_optimized()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->for($course)->create();
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('topics.show', $topic));
        $queries = DB::getQueryLog();
        
        // OPTIMIZED TARGET: Should be <= 4 queries regardless of question count
        $this->assertLessThanOrEqual(4, count($queries),
            'topic.show should use <= 4 queries. Actual: ' . count($queries));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function api_courses_with_stats_query_count_is_optimized()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Course::factory()->count(10)->create();
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->getJson('/api/courses?with_stats=true');
        $queries = DB::getQueryLog();
        
        // OPTIMIZED TARGET: Should be <= 15 queries for 10 courses with stats
        $this->assertLessThanOrEqual(15, count($queries),
            'API courses with stats should use <= 15 queries. Actual: ' . count($queries));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function query_count_scales_linearly_not_exponentially()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        
        // Test with 2 courses
        Course::factory()->count(2)->create();
        
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('courses.index'));
        $queries2 = count(DB::getQueryLog());
        
        // Clear and test with 10 courses
        Course::factory()->count(8)->create(); // Total 10
        
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('courses.index'));
        $queries10 = count(DB::getQueryLog());
        
        // Query count should NOT scale with number of courses (N+1 eliminated)
        // Allow small variance but should be roughly the same
        $this->assertLessThanOrEqual($queries2 + 3, $queries10,
            "Query count should not scale with data size. 2 courses: {$queries2}, 10 courses: {$queries10}");
    }
}