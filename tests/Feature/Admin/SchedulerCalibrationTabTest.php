<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchedulerCalibrationTabTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_scheduler_quality_inside_manage_access_tabs(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.user-course', ['tab' => 'scheduler-quality']));

        $response->assertOk();
        $response->assertSee('Scheduler Calibration Coverage');
        $response->assertSee('Users Needing More Evidence');
    }

    #[Test]
    public function non_admin_cannot_access_manage_access_scheduler_tab(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('admin.user-course', ['tab' => 'scheduler-quality']));

        $response->assertRedirect(route('home'));
    }
}
