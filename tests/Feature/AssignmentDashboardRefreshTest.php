<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssignmentDashboardRefreshTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('refreshRoutes')]
    public function test_dashboard_endpoints_return_current_counts_without_changing_assignments(string $route, string $method): void
    {
        $admin = User::factory()->admin()->create();
        Assignment::factory()->active()->create();
        Assignment::factory()->overdue()->create();
        $before = Assignment::orderBy('id')->get()->map(fn (Assignment $assignment) => $assignment->getRawOriginal())->all();

        $this->actingAs($admin)->json($method, route($route))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('status_counts.active', 1)
            ->assertJsonPath('status_counts.overdue', 1);

        $this->assertSame($before, Assignment::orderBy('id')->get()->map(fn (Assignment $assignment) => $assignment->getRawOriginal())->all());
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public static function refreshRoutes(): array
    {
        return [
            ['assignments.counter.data', 'GET'],
            ['assignments.update-overdue', 'POST'],
            ['assignments.enforce-real-time-status', 'POST'],
        ];
    }

    public function test_tutor_counters_are_scoped_to_their_courses(): void
    {
        $tutor = User::factory()->tutor()->create();
        $course = Course::factory()->create();
        $tutor->courses()->attach($course);
        Assignment::factory()->active()->create(['course_id' => $course->id]);
        Assignment::factory()->active()->create();

        $this->actingAs($tutor)->getJson(route('assignments.counter.data'))
            ->assertOk()->assertJsonPath('status_counts.active', 1);
    }

    public function test_student_cannot_use_management_refresh(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student)->postJson(route('assignments.enforce-real-time-status'))->assertForbidden();
    }
}
