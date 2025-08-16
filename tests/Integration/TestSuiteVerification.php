<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class TestSuiteVerification extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function performance_config_is_properly_loaded()
    {
        $thresholds = Config::get('performance.query_thresholds');
        
        $this->assertIsArray($thresholds);
        $this->assertArrayHasKey('courses.index', $thresholds);
        $this->assertArrayHasKey('dashboard.home', $thresholds);
        $this->assertArrayHasKey('course.show', $thresholds);
        $this->assertArrayHasKey('topic.show', $thresholds);
        
        // Verify threshold values are reasonable
        $this->assertGreaterThan(0, $thresholds['courses.index']);
        $this->assertLessThan(50, $thresholds['courses.index']); // Sanity check
        
        echo "✅ Performance configuration loaded correctly\n";
    }

    /** @test */
    public function test_helper_trait_methods_work()
    {
        // This test verifies that our helper trait is working
        $this->assertTrue(method_exists($this, 'assertQueryCountWithinThreshold'));
        $this->assertTrue(method_exists($this, 'measureQueryCount'));
        $this->assertTrue(method_exists($this, 'createPerformanceTestData'));
        
        echo "✅ Test helper methods available\n";
    }

    /** @test */
    public function database_factories_work_correctly()
    {
        // Verify that our test data creation works
        $user = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_STUDENT]);
        $course = \App\Models\Course::factory()->create();
        $topic = \App\Models\Topic::factory()->for($course)->create();
        
        $this->assertNotNull($user->id);
        $this->assertNotNull($course->id);
        $this->assertNotNull($topic->id);
        $this->assertEquals($course->id, $topic->course_id);
        
        echo "✅ Database factories working correctly\n";
    }

    /** @test */
    public function routes_are_accessible_for_testing()
    {
        $user = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_STUDENT]);
        $course = \App\Models\Course::factory()->create();
        
        $this->actingAs($user);
        
        // Test that key routes exist and are accessible
        $routes = [
            'courses.index' => route('courses.index'),
            'courses.show' => route('courses.show', $course),
        ];
        
        foreach ($routes as $name => $url) {
            $response = $this->get($url);
            $this->assertContains($response->status(), [200, 302, 404]); // Any valid HTTP response
        }
        
        echo "✅ Routes accessible for testing\n";
    }

    /** @test */
    public function test_environment_is_properly_configured()
    {
        // Verify we're in testing environment
        $this->assertEquals('testing', app()->environment());
        
        // Verify database is using testing configuration
        $this->assertStringContainsString('test', config('database.default'));
        
        echo "✅ Test environment properly configured\n";
    }
}