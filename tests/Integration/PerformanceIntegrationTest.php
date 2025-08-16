<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class PerformanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data for realistic scenarios
        $this->createTestData();
    }

    /** @test */
    public function courses_index_meets_query_threshold()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $threshold = Config::get('performance.query_thresholds.courses.index', 10);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('courses.index'));
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThanOrEqual(
            $threshold,
            $queryCount,
            "❌ N+1 DETECTED in courses.index — {$queryCount} queries > {$threshold} limit. Fix eager loading!\n" .
            "Queries executed:\n" . $this->formatQueries($queries)
        );
        
        // Verify response structure hasn't changed
        $response->assertViewIs('courses');
        $response->assertViewHas('courses');
        
        echo "✅ courses.index: {$queryCount}/{$threshold} queries\n";
    }

    /** @test */
    public function dashboard_home_meets_query_threshold()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $courses = Course::factory()->count(3)->create();
        
        // Enroll user in courses
        foreach ($courses as $course) {
            $user->enrollInCourse($course->id);
        }
        
        $threshold = Config::get('performance.query_thresholds.dashboard.home', 8);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('dashboard'));
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThanOrEqual(
            $threshold,
            $queryCount,
            "❌ N+1 DETECTED in dashboard.home — {$queryCount} queries > {$threshold} limit. Fix eager loading!\n" .
            "Queries executed:\n" . $this->formatQueries($queries)
        );
        
        echo "✅ dashboard.home: {$queryCount}/{$threshold} queries\n";
    }

    /** @test */
    public function course_show_meets_query_threshold()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::factory()->create();
        Topic::factory()->count(5)->for($course)->create();
        
        $user->enrollInCourse($course->id);
        $threshold = Config::get('performance.query_thresholds.course.show', 6);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('courses.show', $course));
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThanOrEqual(
            $threshold,
            $queryCount,
            "❌ N+1 DETECTED in course.show — {$queryCount} queries > {$threshold} limit. Fix eager loading!\n" .
            "Queries executed:\n" . $this->formatQueries($queries)
        );
        
        // Verify response structure
        $response->assertViewIs('course-detail');
        $response->assertViewHas('course');
        $response->assertViewHas('topics');
        
        echo "✅ course.show: {$queryCount}/{$threshold} queries\n";
    }

    /** @test */
    public function topic_show_meets_query_threshold()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->for($course)->create();
        Question::factory()->count(10)->for($topic)->create();
        
        $user->enrollInCourse($course->id);
        $threshold = Config::get('performance.query_thresholds.topic.show', 4);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->get(route('topics.show', $topic));
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThanOrEqual(
            $threshold,
            $queryCount,
            "❌ N+1 DETECTED in topic.show — {$queryCount} queries > {$threshold} limit. Fix eager loading!\n" .
            "Queries executed:\n" . $this->formatQueries($queries)
        );
        
        echo "✅ topic.show: {$queryCount}/{$threshold} queries\n";
    }

    /** @test */
    public function api_courses_meets_query_threshold()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Course::factory()->count(5)->create();
        
        $threshold = Config::get('performance.query_thresholds.api.courses', 15);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        $response = $this->getJson('/api/courses?with_stats=true');
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        
        $this->assertLessThanOrEqual(
            $threshold,
            $queryCount,
            "❌ N+1 DETECTED in api.courses — {$queryCount} queries > {$threshold} limit. Fix eager loading!\n" .
            "Queries executed:\n" . $this->formatQueries($queries)
        );
        
        // Verify JSON structure hasn't changed
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'price',
                    'formatted_price',
                ]
            ]
        ]);
        
        echo "✅ api.courses: {$queryCount}/{$threshold} queries\n";
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
        $maxAllowedIncrease = 3; // Allow small variance
        
        $this->assertLessThanOrEqual(
            $queries2 + $maxAllowedIncrease,
            $queries10,
            "❌ EXPONENTIAL SCALING DETECTED — Query count should not scale with data size.\n" .
            "2 courses: {$queries2} queries, 10 courses: {$queries10} queries.\n" .
            "This indicates N+1 queries are still present!"
        );
        
        echo "✅ Linear scaling: 2 courses={$queries2}q, 10 courses={$queries10}q\n";
    }

    /** @test */
    public function backward_compatibility_maintained()
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $course = Course::factory()->create();
        $topic = Topic::factory()->for($course)->create();
        
        $user->enrollInCourse($course->id);
        
        $this->actingAs($user);
        
        // Test courses.index response structure
        $coursesResponse = $this->get(route('courses.index'));
        $coursesResponse->assertStatus(200);
        $coursesResponse->assertViewIs('courses');
        $coursesResponse->assertViewHas('courses');
        
        // Test course.show response structure
        $courseResponse = $this->get(route('courses.show', $course));
        $courseResponse->assertStatus(200);
        $courseResponse->assertViewIs('course-detail');
        $courseResponse->assertViewHas('course');
        $courseResponse->assertViewHas('topics');
        
        // Test API response structure
        $apiResponse = $this->getJson('/api/courses');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                    'formatted_price',
                ]
            ]
        ]);
        
        echo "✅ Backward compatibility maintained\n";
    }

    /** @test */
    public function performance_under_load_simulation()
    {
        // Create realistic data load
        $users = User::factory()->count(10)->create(['role' => User::ROLE_STUDENT]);
        $courses = Course::factory()->count(20)->create();
        $topics = Topic::factory()->count(100)->create();
        
        // Enroll users in multiple courses
        foreach ($users as $user) {
            foreach ($courses->random(5) as $course) {
                $user->enrollInCourse($course->id);
            }
        }
        
        $testUser = $users->first();
        $this->actingAs($testUser);
        
        // Test multiple endpoints under load
        $endpoints = [
            'courses.index' => route('courses.index'),
            'dashboard' => route('dashboard'),
        ];
        
        foreach ($endpoints as $name => $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            
            $startTime = microtime(true);
            $response = $this->get($url);
            $endTime = microtime(true);
            
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            $response->assertStatus(200);
            
            // Performance assertions
            $this->assertLessThan(
                1000, // 1 second
                $responseTime,
                "❌ SLOW RESPONSE in {$name} — {$responseTime}ms > 1000ms limit"
            );
            
            echo "✅ {$name}: {$queryCount} queries, {$responseTime}ms\n";
        }
    }

    /**
     * Create realistic test data
     */
    private function createTestData(): void
    {
        // Create courses with topics
        $courses = Course::factory()->count(5)->create();
        
        foreach ($courses as $course) {
            Topic::factory()->count(3)->for($course)->create();
        }
        
        // Create some users
        User::factory()->count(3)->create(['role' => User::ROLE_STUDENT]);
        User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /**
     * Format queries for debugging output
     */
    private function formatQueries(array $queries): string
    {
        if (!Config::get('performance.testing.log_query_details', false)) {
            return "Query details hidden (set PERFORMANCE_LOG_QUERY_DETAILS=true to show)";
        }
        
        $formatted = [];
        foreach ($queries as $i => $query) {
            $formatted[] = sprintf(
                "%d. %s [%sms]",
                $i + 1,
                $query['query'],
                $query['time'] ?? 'N/A'
            );
        }
        
        return implode("\n", $formatted);
    }
}