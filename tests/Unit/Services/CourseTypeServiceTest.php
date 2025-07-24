<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Course;
use App\Models\Topic;
use App\Services\CourseTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CourseTypeServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseTypeService();
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_determines_medical_course_type_when_explicitly_set()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        $result = $this->service->determineCourseType($course);
        
        $this->assertEquals('medical', $result);
    }

    /** @test */
    public function it_determines_custom_course_type_when_explicitly_set()
    {
        $course = Course::factory()->create(['type' => 'custom']);
        
        $result = $this->service->determineCourseType($course);
        
        $this->assertEquals('custom', $result);
    }

    /** @test */
    public function it_defaults_to_medical_for_backward_compatibility()
    {
        // Create course without specifying type (will use default 'medical')
        $course = Course::factory()->create();
        
        // Manually set type to null to test backward compatibility
        $course->update(['type' => null]);
        $course->refresh();
        
        $result = $this->service->determineCourseType($course);
        
        $this->assertEquals('medical', $result);
    }

    /** @test */
    public function it_returns_medical_categorizations_only_for_existing_topics()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        // Create topics with only Quiz and Cases, no Practical
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Cases']);
        
        $result = $this->service->getCategorizations($course);
        
        $this->assertEquals(['Quiz', 'Cases'], $result);
        $this->assertNotContains('Practical', $result);
    }

    /** @test */
    public function it_returns_empty_array_for_medical_course_with_no_topics()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        $result = $this->service->getCategorizations($course);
        
        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_returns_custom_categorizations_dynamically()
    {
        $course = Course::factory()->create(['type' => 'custom']);
        
        // Create topics with custom categorizations
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Homework']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Lab']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Reading']);
        
        $result = $this->service->getCategorizations($course);
        
        $this->assertContains('Homework', $result);
        $this->assertContains('Lab', $result);
        $this->assertContains('Reading', $result);
        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_returns_empty_array_for_custom_course_with_no_categorizations()
    {
        $course = Course::factory()->create(['type' => 'custom']);
        
        // Create topics without categorizations (use default 'quiz' then update to null/empty)
        $topic1 = Topic::factory()->create(['course_id' => $course->id]);
        $topic1->update(['case_type' => null]);
        
        $topic2 = Topic::factory()->create(['course_id' => $course->id]);
        $topic2->update(['case_type' => '']);
        
        $result = $this->service->getCategorizations($course);
        
        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_caches_medical_categorizations()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        
        // First call should hit database
        $result1 = $this->service->getCategorizations($course);
        
        // Second call should hit cache
        $result2 = $this->service->getCategorizations($course);
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has("course_medical_categorizations_{$course->id}"));
    }

    /** @test */
    public function it_caches_custom_categorizations()
    {
        $course = Course::factory()->create(['type' => 'custom']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Homework']);
        
        // First call should hit database
        $result1 = $this->service->getCategorizations($course);
        
        // Second call should hit cache
        $result2 = $this->service->getCategorizations($course);
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has("course_custom_categorizations_{$course->id}"));
    }

    /** @test */
    public function it_checks_if_course_has_categorizations()
    {
        $courseWithCategorizations = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->create(['course_id' => $courseWithCategorizations->id, 'case_type' => 'Quiz']);
        
        $courseWithoutCategorizations = Course::factory()->create(['type' => 'medical']);
        
        $this->assertTrue($this->service->hasCategorizations($courseWithCategorizations));
        $this->assertFalse($this->service->hasCategorizations($courseWithoutCategorizations));
    }

    /** @test */
    public function it_gets_categorization_counts()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        // Create multiple topics for each category
        Topic::factory()->count(3)->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        Topic::factory()->count(2)->create(['course_id' => $course->id, 'case_type' => 'Cases']);
        
        $result = $this->service->getCategorizationCounts($course);
        
        $this->assertEquals(3, $result['Quiz']);
        $this->assertEquals(2, $result['Cases']);
    }

    /** @test */
    public function it_excludes_locked_topics_from_counts()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        // Create regular and locked topics
        Topic::factory()->count(2)->create(['course_id' => $course->id, 'case_type' => 'Quiz', 'is_locked' => false]);
        Topic::factory()->count(1)->create(['course_id' => $course->id, 'case_type' => 'Quiz', 'is_locked' => true]);
        
        $result = $this->service->getCategorizationCounts($course);
        
        $this->assertEquals(2, $result['Quiz']); // Only unlocked topics counted
    }

    /** @test */
    public function it_gets_topics_for_categorization()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        
        $quizTopics = Topic::factory()->count(3)->create([
            'course_id' => $course->id, 
            'case_type' => 'Quiz',
            'is_locked' => false
        ]);
        
        $casesTopics = Topic::factory()->count(2)->create([
            'course_id' => $course->id, 
            'case_type' => 'Cases',
            'is_locked' => false
        ]);
        
        $result = $this->service->getTopicsForCategorization($course, 'Quiz');
        
        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($topic) => $topic->case_type === 'Quiz'));
    }

    /** @test */
    public function it_validates_categorization_exists()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        
        $this->assertTrue($this->service->isValidCategorization($course, 'Quiz'));
        $this->assertFalse($this->service->isValidCategorization($course, 'NonExistent'));
    }

    /** @test */
    public function it_invalidates_cache()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        
        // Populate cache
        $this->service->getCategorizations($course);
        $this->assertTrue(Cache::has("course_medical_categorizations_{$course->id}"));
        
        // Invalidate cache
        $this->service->invalidateCache($course);
        $this->assertFalse(Cache::has("course_medical_categorizations_{$course->id}"));
    }

    /** @test */
    public function it_gets_frontend_categorization_data()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->count(3)->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        Topic::factory()->count(2)->create(['course_id' => $course->id, 'case_type' => 'Cases']);
        
        $result = $this->service->getCategorizationDataForFrontend($course);
        
        $this->assertEquals('medical', $result['course_type']);
        $this->assertTrue($result['has_categorizations']);
        $this->assertCount(2, $result['categorizations']);
        
        $quizCategory = collect($result['categorizations'])->firstWhere('name', 'Quiz');
        $this->assertEquals(3, $quizCategory['count']);
        $this->assertEquals('quiz', $quizCategory['slug']);
    }

    /** @test */
    public function it_warms_cache()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->create(['course_id' => $course->id, 'case_type' => 'Quiz']);
        
        $this->assertFalse(Cache::has("course_medical_categorizations_{$course->id}"));
        
        $this->service->warmCache($course);
        
        $this->assertTrue(Cache::has("course_medical_categorizations_{$course->id}"));
    }

    /** @test */
    public function it_performance_test_with_large_dataset()
    {
        $course = Course::factory()->create(['type' => 'custom']);
        
        // Create 100 topics with various categorizations
        for ($i = 0; $i < 100; $i++) {
            Topic::factory()->create([
                'course_id' => $course->id,
                'case_type' => 'Category' . ($i % 10) // 10 different categories
            ]);
        }
        
        $startTime = microtime(true);
        $result = $this->service->getCategorizations($course);
        $endTime = microtime(true);
        
        $queryTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(50, $queryTime); // Should be under 50ms
        $this->assertCount(10, $result); // Should have 10 unique categories
    }
}