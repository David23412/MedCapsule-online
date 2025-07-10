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

// Import our custom listeners
use App\Listeners\InvalidateCourseCache;
use App\Listeners\InvalidateTopicCache;
use App\Listeners\InvalidateQuestionCache;
use App\Listeners\InvalidateUserProgressCache;
use App\Listeners\InvalidateQuizCache;
use App\Listeners\InvalidateNotificationCache;
use App\Listeners\InvalidatePaymentCache;

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

        NotificationCreated::class => [
            InvalidateNotificationCache::class,
        ],

        PaymentCompleted::class => [
            InvalidatePaymentCache::class,
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