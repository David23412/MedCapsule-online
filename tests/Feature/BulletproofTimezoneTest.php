<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulletproofTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_deadline_not_timezone_converted()
    {
        $topic = Topic::factory()->create();
        $course = Course::factory()->create();
        
        $now = Carbon::now(config('app.timezone'));
        $deadline = $now->copy()->addHours(2);
        
        $assignment = Assignment::create([
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Timezone Test',
            'deadline' => $deadline,
        ]);
        
        // Deadline should be exactly what we set
        $this->assertEquals(
            $deadline->format('Y-m-d H:i:s'),
            $assignment->fresh()->deadline->format('Y-m-d H:i:s')
        );
        
        // Should not have huge timezone offset
        $hoursDiff = abs($now->diffInHours($assignment->deadline));
        $this->assertLessThan(24, $hoursDiff, "Deadline has suspicious timezone conversion");
    }
    
    public function test_overdue_assignment_detection()
    {
        $topic = Topic::factory()->create();
        $course = Course::factory()->create();
        
        $pastDeadline = Carbon::now(config('app.timezone'))->subHour();
        
        $assignment = Assignment::create([
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'title' => 'Overdue Test',
            'deadline' => $pastDeadline,
        ]);
        
        // Should be detected as overdue based on deadline being in the past
        $this->assertTrue($assignment->isOverdue());
        
        // Deadline should be in the past
        $this->assertTrue($assignment->deadline < now());
    }
}