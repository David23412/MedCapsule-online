# Spaced Repetition Reference Manual

## Purpose

This document is the single reference manual for the MedCapsule mistake-learning system.

It explains:

- the difference between classic mistake cycling and spaced repetition
- how cards move through the system
- what data is recorded
- how scheduling decisions are made
- how concept weakness is tracked
- how the study planner uses mistake and concept signals
- how the validation harness proves the scheduler is behaving sanely
- which classes, services, controllers, routes, commands, and admin diagnostics are involved

This is intended as an implementation manual, not marketing copy.

---

## 1. System Overview

The mistake system now has two distinct learning models:

1. **Classic modes**
   - `practice`
   - `quiz`
   - user loops through the loaded mistake deck using the legacy repetition feel

2. **Spaced repetition mode**
   - `spaced`
   - scheduler-driven
   - prioritizes due, fragile, relearn, and weak-concept cards
   - records richer signals and adapts future scheduling

The user can still practice mistakes any time. A mistake is not considered “gone” just because it was reviewed successfully. It remains in the mistake deck until explicitly removed.

---

## 2. Core Mental Model

There are three memory layers in the current design:

1. **Card memory**
   - the exact mistake card
   - stored on `mistakes`

2. **Concept memory**
   - the underlying concept shared across multiple questions
   - stored on `user_concept_memories`

3. **Calibration memory**
   - learned parameter trust for scheduling
   - stored on:
     - `scheduler_calibration_profiles`
     - `user_scheduler_calibration_profiles`

So the system is not just asking:

- “Did the user get this card right?”

It is also asking:

- “How stable is this card for this user?”
- “Was the answer fast, slow, hesitant, overconfident, fragile?”
- “Is the user repeatedly weak in this concept?”
- “Do we actually have enough data to trust the learned scheduler adjustments?”

There is also a fourth operational layer:

4. **Validation memory**
   - stress tests, seeded fixtures, and backtests that prove the algorithm is behaving consistently

---

## 3. User-Facing Modes

### 3.1 Classic Easy / Practice

Purpose:

- maintain the original quick deck-cycling experience
- good for brute repetition

Behavior:

- the user loads mistakes for a course or topic filter
- cards stay in the session loop
- emphasis is on repeated passes through the deck

System role:

- still records outcomes
- still updates the mistake model
- but the session feel is intentionally simpler than spaced mode

### 3.2 Classic Hard / Quiz

Purpose:

- keep the old tougher cycling model
- more repetition pressure than easy/practice

Behavior:

- similar to classic easy, but with a stricter session feel
- useful for users who want deck grinding instead of scheduler-driven removal

### 3.3 Spaced Repetition

Purpose:

- prioritize what is most fragile, overdue, relearning, or conceptually weak
- stop wasting time on cards that currently look stable

Behavior:

- due and relearn cards surface first
- correct answers can move cards out of the immediate run
- failed cards come back on short relearn steps
- confidence is required before answering
- hesitation, confidence, speed, and errors all influence future scheduling

Spaced repetition is the premium “scientific” mode.

---

## 4. Student Flow

### 4.1 When a user gets a question wrong anywhere

Entry point:

- `Mistake::recordFailure(...)`

What happens:

1. the question is recorded as a mistake or updates an existing mistake
2. adaptive scheduler initializes or resets the card
3. concept memory is synchronized

Immediate effect:

- card becomes fragile
- card becomes due quickly
- concept weakness may increase

Relevant code:

- [Mistake.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Mistake.php)
- [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)
- [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)

### 4.2 When the user opens Learn Mistakes

Entry point:

- `GET /review/learn`
- `LearnController::learnMistakes()`

What happens:

1. request validates course, mode, autostart, and topic filters
2. `MistakeManagementService::getMistakesForLearning(...)` builds the active deck
3. expired skips are reactivated
4. cards are loaded with question, topic, course, and concept data
5. concept signals are attached for ordering and display

Relevant code:

- [LearnController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/LearnController.php)
- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)

### 4.3 When the user answers in spaced mode

Entry point:

- `POST /learn/review/{question_id}`
- `LearnController::reviewMistake()`

Required spaced-mode signals:

- `selected_answer`
- `mode=spaced`
- `time_spent`
- `confidence`

Optional interaction signals:

- `first_selected_answer`
- `answer_change_count`
- `time_to_first_selection`
- `first_confidence`
- `confidence_change_count`

What happens:

1. controller validates request
2. confidence is required in spaced mode
3. `MistakeManagementService::reviewMistake(...)` normalizes the review
4. scheduler updates card memory
5. review event is recorded
6. concept memory is synchronized
7. calibration may run

Relevant code:

- [LearnController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/LearnController.php)
- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)
- [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)
- [SchedulerCalibrationService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationService.php)

---

## 5. Classic vs Spaced: Practical Difference

### Classic

Classic mode is session-centric.

The question is:

- “How do I keep the learner cycling this deck?”

### Spaced

Spaced mode is memory-centric.

The question is:

- “Which card is most worth reviewing now, based on memory risk?”

Classic is useful for:

- mass repetition
- brute-force review
- users who want simple deck cycling

Spaced is useful for:

- long-term retention
- prioritizing weak material
- avoiding wasted repeats of already-stable cards

---

## 6. Card Memory Fields

The mistake model currently uses fields like:

- `difficulty`
- `stability`
- `retrievability`
- `next_review_at`
- `interval_days`
- `last_reviewed_at`
- `last_review_rating`
- `last_review_confidence`
- `last_response_seconds`
- `hesitation_score`
- `learning_state`
- `same_day_relearn_step`
- `same_day_relearn_count`
- `skip_count`
- `consecutive_skip_count`
- `skip_until`

### Meaning

#### Difficulty

Estimated resistance of the card for this user.

High means:

- this card is hard for this student
- it should be treated more conservatively

#### Stability

Estimated durability of memory.

High means:

- this card can survive longer intervals

#### Retrievability

Estimated probability of successful recall right now.

Low means:

- higher forgetting risk

#### Next Review At

The next due timestamp the scheduler wants.

#### Learning State

Same-day learning state, especially in spaced relearn.

Current important state:

- `relearn`

#### Same-Day Relearn Step

Tracks same-day recovery progression.

Current model:

- step `1`: fresh lapse
- step `2`: first recovery
- then exit relearn after the next successful recovery

---

## 7. Signals the Scheduler Uses

The scheduler uses a bundle of signals, not just right/wrong.

### 7.1 Correctness

Base signal:

- right answer
- wrong answer

### 7.2 Confidence

Spaced mode requires one of:

- `guessed`
- `unsure`
- `knew`

Interpretation:

- correct + guessed = weaker memory
- correct + knew = stronger memory only if confidence has historically been reliable
- wrong + knew = overconfidence trap

### 7.3 Retrieval Time

The system uses answer retrieval time.

Important rule:

- in spaced MCQ mode, timing is frozen at first answer-selection intent
- confidence tapping should not contaminate retrieval time

### 7.4 Hesitation

Hesitation is secondary but real.

Captured signals:

- answer change count
- first selected answer
- final selected answer
- time to first selection
- first confidence
- confidence change count

Meaning:

- changes imply weaker certainty
- repeated hesitation slightly increases effective difficulty
- repeated hesitation slightly shortens intervals

### 7.5 Same-Day Relearn State

Spaced mode distinguishes:

- fresh lapse
- first recovery
- second recovery
- long-term scheduling return

This prevents “one correct answer after a fresh miss” from being over-rewarded.

### 7.8 Overdue Penalty

If a card is reviewed well after it was due, the next interval is scaled down.

Why:

- a late successful recall can overstate true memory strength
- the scheduler should not reward overdue success as aggressively as on-time success

### 7.9 Interval Jitter and Sibling Dispersion

The scheduler now deliberately avoids review storms.

It does this by:

- applying small jitter to longer intervals
- spreading related cards across nearby days

Why:

- reduce same-day clustering
- reduce cueing and sibling interference
- improve long-run session quality

### 7.6 Skip Behavior

Skip is not permanent removal.

It means:

- defer for now
- card stays in the deck
- `skip_until` controls return
- repeated skips raise pressure

### 7.7 User-Specific Confidence Reliability

The system learns whether this user’s confidence labels are trustworthy.

Example:

- if this student often says `knew` and is wrong, future `knew` answers are trusted less

---

## 8. Spaced Scheduling Behavior by Scenario

### Scenario A: wrong + guessed

Interpretation:

- weak recall
- low overconfidence

Expected effect:

- card enters relearn
- comes back soon
- penalty is real, but less harsh than overconfident failure

### Scenario B: wrong + knew

Interpretation:

- overconfidence
- bad calibration

Expected effect:

- stronger difficulty increase
- more conservative future scheduling
- stronger concept penalty if repeated

### Scenario C: right + guessed

Interpretation:

- weak success

Expected effect:

- success is recorded
- interval grows less
- card is treated as fragile

### Scenario D: right + knew

Interpretation:

- strong success
- only fully rewarded if user confidence has proven reliable

Expected effect:

- larger stability growth
- longer interval
- unless the user has poor confidence reliability

### Scenario E: hesitant correct

Interpretation:

- right answer, but shaky recall

Expected effect:

- smaller reward than clean correct
- difficulty remains higher than a smooth correct

### Scenario F: repeated failure

Interpretation:

- this card is unstable

Expected effect:

- repeated relearn
- higher difficulty
- lower stability
- stays near the front

### Scenario G: repeated successful recovery

Interpretation:

- memory is consolidating

Expected effect:

- exits relearn
- returns to long-term spacing
- interval starts to grow materially

### Scenario H: skipping

Interpretation:

- user is deferring, not deleting

Expected effect:

- card is postponed
- returns later automatically
- repeated skips matter

---

## 9. Same-Day Relearn Mechanism

This is one of the most important differences from naive spaced repetition.

### Why it exists

Without same-day relearn:

- a failed card can be marked “better” too quickly
- one correct answer after a lapse looks too strong

### Current behavior

1. failure enters `relearn` step 1
2. first successful spaced recovery advances to step 2
3. second successful spaced recovery exits relearn
4. card returns to long-term interval scheduling

This is implemented in:

- [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)

---

## 10. Concept Memory

Concept tracking exists so the system can detect weakness across multiple cards, not only on one exact card.

### Schema

- `concepts`
- `concept_question`
- `user_concept_memories`

### Concept format

- `name`: human-readable
- `slug`: lowercase kebab-case

Example:

- `name`: `Venous pressure`
- `slug`: `venous-pressure`

### What concept memory stores

- review count
- lapse count
- low-confidence correct count
- overconfidence error count
- difficulty
- stability
- retrievability
- response timing summary
- next review data

### Concept fallback

If a question has no explicit concepts, a generated topic-level fallback concept may be attached.

That fallback is useful for continuity, but explicit concept tags are preferred.

Implemented in:

- [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)

---

## 11. Concept Analytics and Failure Clustering

Concept memory is not only a flat risk list anymore.

The system also derives **failure patterns** such as:

- repeated lapse
- overconfidence trap
- hesitation drift
- slow recall
- fragile recall

### Why this matters

Two concepts can both be weak for different reasons:

- one because the student keeps forgetting
- another because the student is overconfident

That distinction matters for planner decisions and diagnostics.

Implemented in:

- [ConceptAnalyticsService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptAnalyticsService.php)

This service produces:

- concept risk signals
- weakest concepts
- cluster summaries
- per-topic cluster grouping

---

## 12. How Spaced Deck Ordering Works

The deck builder is in:

- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)

### Current ordering logic in spaced mode

Cards are prioritized roughly by:

1. `next_review_at`
2. concept risk
3. lower retrievability
4. higher difficulty
5. older attempt timing

This means:

- relearn and overdue cards surface early
- within similar due timing, weaker concepts rise
- already-stable cards drift down

---

## 13. Planner Integration

The study planner is no longer independent of the mistake scheduler.

Planner orchestration:

- [StudyGamePlanService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/StudyGamePlanService.php)

Planner context building:

- [PlannerContextFactory.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerContextFactory.php)

Task creation:

- [PlannerTaskBuilderService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerTaskBuilderService.php)

Task completion:

- [TaskCompletionService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/TaskCompletionService.php)

### Planner uses

The planner currently consumes:

- due mistakes by topic
- overdue age
- forgetting risk
- weak concepts
- concept failure clusters

### Planner task types related to mistakes/concepts

- `topic_mistake_review`
- `mistakes_cleanup`
- `concept_repair`
- `concept_cluster_repair`
- `repair_topic`

### What this enables

The planner can now say:

- review due mistakes in this topic
- repair this weak concept
- repair this repeated failure pattern across linked concepts

That is much more specialized than only saying “weak topic”.

---

## 14. Manage Access Diagnostics Tab

There is now a scheduler-quality reference tab inside:

- `Manage Access`

Purpose:

- show whether scheduler calibration is actually trustworthy
- show mode-level sample quality
- show confidence coverage
- show which users still lack enough spaced evidence

Data source:

- [SchedulerCalibrationCoverageService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationCoverageService.php)

Loaded by:

- [UserCourseController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/UserCourseController.php)

Rendered in:

- [manage-access.blade.php](/Users/davidgeorge/Documents/Medcapsule-online/resources/views/admin/manage-access.blade.php)
- [scheduler-calibration.blade.php](/Users/davidgeorge/Documents/Medcapsule-online/resources/views/admin/partials/scheduler-calibration.blade.php)

### Why this tab exists

Because a scheduler can look mathematically “accurate” on tiny or biased data.

This tab is there to stop false confidence.

---

## 15. Calibration and Backtesting

### Calibration

Service:

- [SchedulerCalibrationService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationService.php)

It learns:

- recall bias
- stability scale
- difficulty scale
- response sensitivity
- confidence sensitivity
- overconfidence penalty
- hesitation sensitivity
- relearn scale
- decay base

It also learns user-specific spaced confidence behavior:

- confidence reliability
- user-level overconfidence penalty
- alignment between confidence labels and outcomes

### Guardrails

Calibration is now quality-aware.

It will not overfit narrow, weak, or one-sided evidence too aggressively.

### Backtesting

Service:

- [SchedulerBacktestService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerBacktestService.php)

Reports:

- sample size
- mean absolute error
- Brier score
- observed recall
- predicted recall
- confidence segments
- interval-state segments
- data quality

### Data quality layer

Backtesting explicitly reports:

- whether evidence is strong, moderate, weak, or insufficient
- whether confidence coverage is narrow
- whether sample size is too small

This is critical because “good-looking” metrics on tiny data are not enough.

---

## 16. Commands

### Recalibrate scheduler

Command:

- `php artisan mistakes:calibrate-scheduler --mode=all --days=120`

Purpose:

- recalibrate scheduler parameters from accumulated review history

Optional:

- `--user={id}` for spaced user-confidence calibration

Command class:

- [CalibrateMistakeSchedulerCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/CalibrateMistakeSchedulerCommand.php)

### Backtest scheduler

Command:

- `php artisan mistakes:backtest-scheduler --mode=all --days=180`

Purpose:

- compare predicted recall against actual later recall

Optional:

- `--user={id}`

Command class:

- [BacktestMistakeSchedulerCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/BacktestMistakeSchedulerCommand.php)

### Stress-test the scheduler

Command:

- `php artisan mistakes:stress-test-spaced-repetition --days=365`

Purpose:

- run persona-based simulation against the live scheduler math
- pressure-test relearn behavior, difficulty drift, interval growth, ease-hell patterns, and non-spaced failure paths

Command class:

- [StressTestSpacedRepetitionCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/StressTestSpacedRepetitionCommand.php)

### Seed realistic spaced-review fixtures

Command:

- `php artisan db:seed --class=SpacedRepetitionFixtureSeeder`

Purpose:

- create dedicated fixture users
- create realistic mistake/review-event history
- seed concept-linked spaced decks for validation and manual UI testing

Seeder class:

- [SpacedRepetitionFixtureSeeder.php](/Users/davidgeorge/Documents/Medcapsule-online/database/seeders/SpacedRepetitionFixtureSeeder.php)

### Scheduled recalibration

Kernel schedule:

- runs recalibration twice daily

Scheduler definition:

- [Kernel.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Kernel.php)

---

## 17. Main Routes

### Learn Mistakes page

- `GET /review/learn`
- route name: `review.learn`

### Submit mistake review

- `POST /learn/review/{question_id}`
- route name: `learn.review`

### Skip mistake

- `POST /review/skip/{question_id}`
- route name: `review.skip`

### Remove mistake

- `POST /review/remove/{question_id}`
- route name: `review.remove`

### Manage Access page

- `GET /manage-access`
- route name: `admin.user-course`

The scheduler-quality diagnostics are inside `Manage Access` using:

- `?tab=scheduler-quality`

Routes are defined in:

- [routes/web.php](/Users/davidgeorge/Documents/Medcapsule-online/routes/web.php)

---

## 18. Main Classes and Services

### Core scheduling

- [Mistake.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Mistake.php)
  - mistake model
  - record failure
  - apply review
  - skip
  - due scopes

- [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)
  - main scheduling logic
  - success/failure transitions
  - relearn stages
  - skip behavior

### Learning orchestration

- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)
  - build learn deck
  - review mistake
  - reactivate expired skips
  - attach concept signals

- [MistakeService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/MistakeService.php)
  - legacy mistake endpoints and operations

### Concept intelligence

- [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)
  - sync concept memory from card outcomes

- [ConceptAnalyticsService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptAnalyticsService.php)
  - concept risk analytics
  - failure clustering

### Calibration / scientific layer

- [SchedulerCalibrationService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationService.php)
  - parameter learning
  - user confidence calibration
  - review-event logging

- [SchedulerBacktestService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerBacktestService.php)
  - post-hoc predictive validation

- [SchedulerCalibrationCoverageService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationCoverageService.php)
  - admin coverage diagnostics

### Planner

- [StudyGamePlanService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/StudyGamePlanService.php)
- [PlannerContextFactory.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerContextFactory.php)
- [PlannerTaskBuilderService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerTaskBuilderService.php)
- [TaskCompletionService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/TaskCompletionService.php)

### Controllers

- [LearnController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/LearnController.php)
- [MistakeController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/MistakeController.php)
- [UserCourseController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/UserCourseController.php)

---

## 19. Current Strengths

The current system is strong in these areas:

- card-level adaptive scheduling
- same-day relearn modeling
- confidence-aware scheduling
- hesitation-aware scheduling
- user-level confidence calibration
- skip semantics
- concept memory
- concept failure clustering
- planner integration
- backtesting
- seeded validation fixtures
- persona-based stress testing
- calibration quality diagnostics

This is no longer a simple streak cycler.

---

## 20. Current Limitations

The system is advanced, but not “finished forever”.

Important current limits:

1. Real-world calibration quality depends on enough live data.
2. If question concept tagging is sparse, concept intelligence is weaker.
3. Card-level scheduling is stronger than full concept-only remediation sessions.
4. The scheduler is adaptive and quality-guarded, but still depends on observed review history building up over time.
5. Spaced MCQ timing is cleaner than written-answer timing today.

In plain terms:

- the algorithm can be very smart structurally
- but scientific trust still depends on evidence volume and coverage

---

## 21. Operational Guidance

If you want the strongest real-world performance:

1. tag more questions with precise concepts
2. push real students to use spaced mode, not only classic
3. make sure confidence coverage includes:
   - `knew`
   - `unsure`
   - `guessed`
4. watch the `Manage Access -> Scheduler Quality` tab
5. do not treat low-sample backtests as strong scientific proof
6. use the fixture seeder and stress command before making major scheduler changes

---

## 22. Short Summary

Classic mode is deck cycling.

Spaced mode is adaptive memory scheduling.

The current system:

- learns from correctness, confidence, timing, hesitation, relearn state, skips, and concepts
- updates both card memory and concept memory
- feeds those signals into the study planner
- backtests its own predictions
- reports when the evidence is not yet strong enough to trust learned calibration

That is the current operating model of the MedCapsule mistake-learning system.
