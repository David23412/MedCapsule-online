<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\CoursePreviewController;
use App\Http\Controllers\TopicAccessController;
use App\Http\Requests\CourseAccessRequest;
use App\Models\Assignment;
use App\Models\User;
use App\Services\HealthCheckService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductionReadinessRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_entry_points_render(): void
    {
        foreach (['/', '/login', '/register', '/up'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_controller_construction_keeps_authentication_middleware(): void
    {
        foreach ([CoursePreviewController::class, TopicAccessController::class] as $class) {
            $middleware = array_column(app($class)->getMiddleware(), 'middleware');
            $this->assertContains('auth', $middleware);
        }
    }

    public function test_validation_returns_submitted_data_and_supports_key_lookup(): void
    {
        $input = ['name' => 'Actual Student', 'email' => 'actual@example.test'];
        $request = new CourseAccessRequest($input);
        $request->setValidator(app('validator')->make($input, $request->rules()));
        $this->assertSame($input, $request->validated());
        $this->assertSame($input['email'], $request->validated('email'));
    }

    public function test_health_check_uses_available_database_and_cache_services(): void
    {
        $this->assertTrue(HealthCheckService::quickHealthCheck());
    }

    public function test_tutor_can_extend_assignment_without_obsolete_status_column(): void
    {
        $tutor = User::factory()->tutor()->create();
        $assignment = Assignment::factory()->create();
        $newDeadline = now()->addDays(40)->startOfMinute();
        $notifications = Mockery::mock(NotificationService::class)->makePartial();
        $notifications->shouldReceive('notifyDeadlineChanged')->once()->andReturn([]);
        $this->app->instance(NotificationService::class, $notifications);

        $this->actingAs($tutor)->postJson(route('assignments.extend-deadline', $assignment), [
            'new_deadline' => $newDeadline->toDateTimeString(),
        ])->assertOk();
        $this->assertTrue($assignment->fresh()->deadline->equalTo($newDeadline));
    }
}
