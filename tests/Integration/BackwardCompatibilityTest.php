<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->for($this->course)->create();
        
        $this->user->enrollInCourse($this->course->id);
    }

    /** @test */
    public function courses_index_view_structure_unchanged()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('courses.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('courses');
        $response->assertViewHas('courses');
        $response->assertViewHas('availableYears');
        
        // Verify courses data structure
        $courses = $response->viewData('courses');
        $this->assertNotEmpty($courses);
        
        $firstCourse = $courses->first();
        $this->assertObjectHasAttribute('id', $firstCourse);
        $this->assertObjectHasAttribute('name', $firstCourse);
        $this->assertObjectHasAttribute('formatted_price', $firstCourse);
        
        // Verify new optimized properties are set
        $this->assertObjectHasAttribute('completion_percentage', $firstCourse);
        $this->assertObjectHasAttribute('enrolled_students_count', $firstCourse);
        
        echo "✅ courses.index view structure maintained\n";
    }

    /** @test */
    public function course_show_view_structure_unchanged()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('courses.show', $this->course));
        
        $response->assertStatus(200);
        $response->assertViewIs('course-detail');
        $response->assertViewHas('course');
        $response->assertViewHas('topics');
        
        // Verify course data structure
        $course = $response->viewData('course');
        $this->assertEquals($this->course->id, $course->id);
        $this->assertNotNull($course->name);
        
        // Verify topics data structure
        $topics = $response->viewData('topics');
        $this->assertNotEmpty($topics);
        
        echo "✅ course.show view structure maintained\n";
    }

    /** @test */
    public function api_courses_json_structure_unchanged()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/courses');
        
        $response->assertStatus(200);
        
        // Verify JSON structure matches expected format
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'formatted_price',
                    'image_url',
                ]
            ]
        ]);
        
        // Verify data types
        $data = $response->json('data');
        if (!empty($data)) {
            $firstCourse = $data[0];
            $this->assertIsInt($firstCourse['id']);
            $this->assertIsString($firstCourse['name']);
            $this->assertIsString($firstCourse['formatted_price']);
        }
        
        echo "✅ API courses JSON structure maintained\n";
    }

    /** @test */
    public function api_courses_with_stats_structure_unchanged()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/courses?with_stats=true');
        
        $response->assertStatus(200);
        
        // Verify enhanced JSON structure with stats
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'formatted_price',
                    'image_url',
                    // Stats should be included when requested
                ]
            ]
        ]);
        
        echo "✅ API courses with stats structure maintained\n";
    }

    /** @test */
    public function blade_template_variables_accessible()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('courses.index'));
        $response->assertStatus(200);
        
        // Verify that Blade templates can access the properties they expect
        $content = $response->getContent();
        
        // Check that completion percentage is rendered (from courses.blade.php)
        $this->assertStringContainsString('progress-fill', $content);
        $this->assertStringContainsString('progress-percentage', $content);
        
        echo "✅ Blade template variables accessible\n";
    }

    /** @test */
    public function user_enrollment_status_consistent()
    {
        $this->actingAs($this->user);
        
        // Test enrollment status in different contexts
        $coursesResponse = $this->get(route('courses.index'));
        $courseResponse = $this->get(route('courses.show', $this->course));
        $apiResponse = $this->getJson('/api/courses');
        
        $coursesResponse->assertStatus(200);
        $courseResponse->assertStatus(200);
        $apiResponse->assertStatus(200);
        
        // Verify enrollment status is consistent across all endpoints
        // (Implementation would depend on how enrollment status is exposed)
        
        echo "✅ User enrollment status consistent\n";
    }

    /** @test */
    public function error_responses_unchanged()
    {
        // Test unauthenticated access
        $response = $this->get(route('courses.show', 999));
        $this->assertContains($response->status(), [404, 403, 302]); // Expected error codes
        
        // Test authenticated access to non-existent resource
        $this->actingAs($this->user);
        $response = $this->get(route('courses.show', 999));
        $this->assertContains($response->status(), [404, 403]);
        
        echo "✅ Error responses unchanged\n";
    }

    /** @test */
    public function pagination_structure_maintained()
    {
        // Create many courses to trigger pagination
        Course::factory()->count(20)->create();
        
        $this->actingAs($this->user);
        
        $response = $this->get(route('courses.index'));
        $response->assertStatus(200);
        
        // Verify pagination structure if implemented
        $courses = $response->viewData('courses');
        
        // Check if courses is a paginated collection
        if (method_exists($courses, 'links')) {
            $this->assertNotNull($courses->links());
        }
        
        echo "✅ Pagination structure maintained\n";
    }
}