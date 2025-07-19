# Random Quiz Grading Fix

## Issue
The random quiz grading was failing with a 500 error due to a cascade of issues related to event handling and cache invalidation.

## Root Cause Analysis
1. When a random quiz was completed, the `QuizAttempt` model was dispatching two events:
   - `QuizAttemptCompleted`
   - `UserProgressUpdated`

2. The `UserProgressUpdated` event required a topic ID to be an integer:
   ```php
   public function __construct(int $userId, int $topicId, float $percentage, string $studyMode = 'quiz')
   ```

3. However, random quizzes have `topic_id = null` since they're not tied to any specific topic.

4. This mismatch was causing a type error that was being caught by the error handler and returning a 500 response.

## Solution
1. Modified the `QuizAttempt` model's boot method to conditionally dispatch events:
   ```php
   static::saved(function ($quizAttempt) {
       if ($quizAttempt->score !== null && 
           $quizAttempt->total_questions !== null && 
           $quizAttempt->topic_id !== null && 
           $quizAttempt->quiz_type !== 'random') {
           
           $percentage = ($quizAttempt->score / $quizAttempt->total_questions) * 100;
           
           event(new \App\Events\UserProgressUpdated(
               $quizAttempt->user_id,
               $quizAttempt->topic_id,
               $percentage,
               $quizAttempt->study_mode ?? 'quiz'
           ));
       }
   });
   ```

2. Added checks to prevent the `UserProgressUpdated` event from being dispatched for random quizzes:
   - `$quizAttempt->topic_id !== null`
   - `$quizAttempt->quiz_type !== 'random'`

3. Updated related cache services to handle null topic IDs properly:
   - `CacheService::clearQuizCaches`
   - `CacheService::invalidateUserCaches`

## Impact
- Random quiz grading now works correctly
- No unnecessary cache invalidation for random quizzes
- Better error handling and logging for quiz attempts
- Cleaner separation between regular and random quiz handling

## Related Files
- `app/Models/QuizAttempt.php`
- `app/Services/CacheService.php`
- `app/Events/UserProgressUpdated.php`
- `app/Events/QuizAttemptCompleted.php` 