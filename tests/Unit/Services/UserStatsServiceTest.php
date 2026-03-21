<?php

namespace Tests\Unit\Services;

use App\Events\StudyStreakUpdated;
use App\Models\User;
use App\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dispatches_study_streak_updated_event_when_streak_changes(): void
    {
        Event::fake([StudyStreakUpdated::class]);

        $user = User::factory()->create([
            'study_streak_days' => 3,
            'last_study_date' => Carbon::yesterday(),
            'correct_answers_count' => 10,
            'total_questions_attempted' => 20,
        ]);

        $service = app(UserStatsService::class);
        $service->updateAfterQuizAttempt($user, 5, 2, 120);

        Event::assertDispatched(StudyStreakUpdated::class, function (StudyStreakUpdated $event) use ($user) {
            return $event->user->id === $user->id
                && $event->previousStreak === 3
                && $event->newStreak === 4
                && $event->wasReset === false;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_dispatch_streak_event_when_user_already_studied_today(): void
    {
        Event::fake([StudyStreakUpdated::class]);

        $user = User::factory()->create([
            'study_streak_days' => 5,
            'last_study_date' => Carbon::today(),
            'correct_answers_count' => 10,
            'total_questions_attempted' => 20,
        ]);

        $service = app(UserStatsService::class);
        $service->updateAfterQuizAttempt($user, 3, 1, 90);

        Event::assertNotDispatched(StudyStreakUpdated::class);
    }
}
