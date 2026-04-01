<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import our custom events
use App\Events\CourseUpdated;
use App\Events\TopicCompleted;
use App\Events\QuestionDeleted;
use App\Events\UserProgressUpdated;
use App\Events\QuizAttemptCompleted;
use App\Events\NotificationCreated;
use App\Events\PaymentCompleted;
use App\Events\SourceProgressUpdated;

// Import notification events
use App\Events\StudyStreakUpdated;
use App\Events\TopicMastered;
use App\Events\WeakTopicIdentified;
use App\Events\UserLeveledUp;
use App\Events\MistakesNeedReview;
use App\Events\CourseProgressMilestone;
use App\Events\SkippedQuestionsDetected;
use App\Events\TopicUnlockedByTutor;
use App\Events\AssignmentDeadlineChanged;
use App\Events\CorrectAnswersMilestoneReached;

// Import our custom listeners
use App\Listeners\InvalidateCourseCache;
use App\Listeners\InvalidateTopicCache;
use App\Listeners\InvalidateQuestionCache;
use App\Listeners\InvalidateUserProgressCache;
use App\Listeners\InvalidateQuizCache;
use App\Listeners\InvalidateNotificationCache;
use App\Listeners\InvalidatePaymentCache;
use App\Listeners\SourceProgressUpdatedListener;

// Import notification listeners
use App\Listeners\SendStudyStreakNotification;
use App\Listeners\SendTopicMasteryNotification;
use App\Listeners\SendWeakTopicNotification;
use App\Listeners\SendLevelUpNotification;
use App\Listeners\SendMistakeReviewNotification;
use App\Listeners\SendCourseProgressNotification;
use App\Listeners\SendSkippedQuestionNotification;
use App\Listeners\SendTopicUnlockNotification;
use App\Listeners\SendAssignmentDeadlineNotification;
use App\Listeners\SendCorrectAnswersMilestoneNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // PHASE 2: Cache invalidation event mappings
        CourseUpdated::class => [
            InvalidateCourseCache::class,
        ],

        TopicCompleted::class => [
            InvalidateTopicCache::class,
        ],

        QuestionDeleted::class => [
            InvalidateQuestionCache::class,
        ],

        UserProgressUpdated::class => [
            InvalidateUserProgressCache::class,
        ],

        QuizAttemptCompleted::class => [
            InvalidateQuizCache::class,
        ],

        SourceProgressUpdated::class => [
            SourceProgressUpdatedListener::class,
        ],

        NotificationCreated::class => [
            InvalidateNotificationCache::class,
        ],

        PaymentCompleted::class => [
            InvalidatePaymentCache::class,
        ],

        // NOTIFICATION SYSTEM: Event-driven notifications
        StudyStreakUpdated::class => [
            SendStudyStreakNotification::class,
        ],

        TopicMastered::class => [
            SendTopicMasteryNotification::class,
        ],

        WeakTopicIdentified::class => [
            SendWeakTopicNotification::class,
        ],

        UserLeveledUp::class => [
            SendLevelUpNotification::class,
        ],

        MistakesNeedReview::class => [
            SendMistakeReviewNotification::class,
        ],

        CourseProgressMilestone::class => [
            SendCourseProgressNotification::class,
        ],

        SkippedQuestionsDetected::class => [
            SendSkippedQuestionNotification::class,
        ],

        TopicUnlockedByTutor::class => [
            SendTopicUnlockNotification::class,
        ],

        AssignmentDeadlineChanged::class => [
            SendAssignmentDeadlineNotification::class,
        ],

        CorrectAnswersMilestoneReached::class => [
            SendCorrectAnswersMilestoneNotification::class,
        ],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
} 