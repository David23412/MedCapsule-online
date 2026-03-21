# Spaced Repetition Mechanism of Action Manual

## Scope

This document explains the complete behavior of the MedCapsule mistake-learning system.

It covers:

- classic modes
- spaced repetition mode
- scheduling logic
- same-day relearn behavior
- confidence and hesitation handling
- skip behavior
- concept memory
- concept clustering
- planner integration
- routes
- controllers
- services
- models
- commands
- diagnostics
- tests

This is the implementation reference manual for developers.

---

## 1. High-Level System Model

The system has two user-facing learning families:

1. **Classic cycling**
   - `practice`
   - `quiz`

2. **Adaptive spaced repetition**
   - `spaced`

Classic mode preserves the familiar deck-loop feel.

Spaced mode is scheduler-driven and memory-state-driven.

The system is built on four layers:

1. **Mistake card layer**
   - one specific failed question for one user

2. **Concept layer**
   - one underlying idea shared across questions

3. **Calibration layer**
   - learned scheduler parameters from historical review outcomes

4. **Planner layer**
   - task generation based on weakness, due pressure, concept fragility, and forgetting risk

---

## 2. Main Data Entities

### 2.1 `mistakes`

Primary per-card memory state.

Important fields:

- `user_id`
- `question_id`
- `correct_streak`
- `mastered`
- `skipped`
- `difficulty`
- `stability`
- `retrievability`
- `interval_days`
- `next_review_at`
- `last_reviewed_at`
- `last_response_seconds`
- `last_review_rating`
- `last_review_confidence`
- `hesitation_score`
- `learning_state`
- `same_day_relearn_step`
- `same_day_relearn_count`
- `skip_count`
- `consecutive_skip_count`
- `skip_until`

### 2.2 `mistake_review_events`

Historical review-event log used for:

- calibration
- backtesting
- diagnostics

Important fields:

- `review_mode`
- `rating`
- `is_correct`
- `confidence_level`
- `response_seconds`
- `first_selected_answer`
- `final_selected_answer`
- `answer_change_count`
- `time_to_first_selection`
- `first_confidence_level`
- `confidence_change_count`
- `prior_*`
- `resulting_*`
- `target_retention`
- `decay_base`
- `calibration_snapshot`

### 2.3 `concepts`

Canonical educational ideas.

Format:

- `name`: human readable
- `slug`: lowercase kebab-case

Example:

- `name`: `Osmotic pressure`
- `slug`: `osmotic-pressure`

### 2.4 `concept_question`

Many-to-many pivot:

- one question can map to many concepts
- one concept can map to many questions

### 2.5 `user_concept_memories`

Per-user concept memory state.

Important fields:

- `review_count`
- `lapse_count`
- `low_confidence_correct_count`
- `overconfidence_error_count`
- `difficulty`
- `stability`
- `retrievability`
- `average_response_seconds`
- `last_review_rating`
- `last_review_confidence`
- `next_review_at`

### 2.6 `scheduler_calibration_profiles`

Global mode-level scheduler calibration.

Modes:

- `quiz`
- `quick`
- `spaced`

### 2.7 `user_scheduler_calibration_profiles`

User-specific calibration, currently most important for:

- spaced confidence reliability
- user overconfidence handling

---

## 3. Core Runtime Flow

### 3.1 Wrong answer created somewhere in the app

Entry:

- `Mistake::recordFailure(...)`

Flow:

1. find existing mistake or create a new one
2. set or update card failure state
3. adaptive scheduler initializes failure memory
4. concept memory sync runs

Primary code:

- [Mistake.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Mistake.php)
- [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)
- [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)

### 3.2 Learn Mistakes page opens

Entry:

- `GET /review/learn`
- `LearnController::learnMistakes()`

Flow:

1. validate request:
   - `course`
   - `mode`
   - `autostart`
   - `topic_ids`
2. store current learn mode in session
3. load deck through `MistakeManagementService`
4. reactivate expired skips
5. attach concept signals
6. render `mistakes.learn`

Primary code:

- [LearnController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/LearnController.php)
- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)

### 3.3 Review is submitted

Entry:

- `POST /learn/review/{question_id}`
- `LearnController::reviewMistake()`

Flow:

1. validate mode and answer
2. validate timing and interaction signals
3. require confidence if mode is `spaced`
4. call `MistakeManagementService::reviewMistake(...)`
5. scheduler updates card
6. review event is logged
7. concept memory sync runs
8. scheduler calibration may run

---

## 4. User-Facing Modes

## 4.1 Practice

Behavior:

- classic loop feel
- user can keep cycling through mistakes
- simpler than spaced mode

Purpose:

- fast deck repetition
- low cognitive overhead

## 4.2 Quiz

Behavior:

- classic harder loop
- still keeps the familiar grind-style review

Purpose:

- brute-force repeated review
- user-controlled repetition

## 4.3 Spaced

Behavior:

- due and fragile cards surface first
- confidence required
- correct cards can move out of the immediate run
- failed cards re-enter same-day relearn
- concept weakness helps prioritize ordering

Purpose:

- maximize useful repetition
- reduce wasted review on stable material
- personalize interval behavior

---

## 5. Classic vs Spaced Behavior

### Classic modes

Question:

- “How do we keep the student cycling this deck?”

Characteristics:

- session-driven
- repetition-heavy
- familiar
- less dependent on due-state

### Spaced mode

Question:

- “What is the highest-value card to show now based on memory state?”

Characteristics:

- scheduler-driven
- risk-aware
- concept-aware
- confidence-aware
- timing-aware
- relearn-aware

---

## 6. Scheduler Signals

The scheduler does not only use correctness.

It uses:

- correctness
- review rating
- confidence
- retrieval time
- hesitation
- answer changes
- confidence changes
- elapsed time since last review
- prior interval
- prior stability
- prior retrievability
- same-day relearn state
- repeated lapses
- skip history
- user confidence reliability

---

## 7. Meaning of Core Scheduler Variables

### 7.1 Difficulty

Interpretation:

- how resistant this card is for this user

High difficulty means:

- conservative scheduling
- weaker success reward

### 7.2 Stability

Interpretation:

- how long the memory can survive before recall decays

High stability means:

- longer intervals are acceptable

### 7.3 Retrievability

Interpretation:

- estimated chance of successful recall now

Low retrievability means:

- higher forgetting risk
- stronger urgency

### 7.4 Interval Days

Interpretation:

- next long-term spacing interval length

### 7.5 Next Review At

Interpretation:

- exact next due timestamp

### 7.6 Learning State

Current main state:

- `relearn`

Used to distinguish:

- same-day recovery flow
- long-term spacing flow

---

## 8. Confidence Handling

Confidence values:

- `guessed`
- `unsure`
- `knew`

### Why confidence exists

Correctness is too coarse.

Examples:

- correct + guessed should not be rewarded like strong knowledge
- wrong + knew should be penalized harder than wrong + guessed

### Current spaced behavior

Spaced mode requires confidence before submission.

The frontend blocks answer submission until confidence is selected.

Confidence currently affects:

- growth on success
- penalty on failure
- target retention
- interval size
- user confidence reliability calibration

---

## 9. Retrieval Timing

The system uses retrieval time as a real learning signal.

### Important design rule

Answer retrieval time should reflect answer recall, not UI friction.

So in spaced MCQ mode:

- timing is frozen at first answer-selection intent
- confidence interaction should not contaminate the retrieval-time signal

Timing influences:

- response factor
- scheduler growth
- scheduler penalty behavior
- calibration metrics

---

## 10. Hesitation Handling

Hesitation is a real but secondary signal.

Captured fields:

- `first_selected_answer`
- `final_selected_answer`
- `answer_change_count`
- `time_to_first_selection`
- `first_confidence`
- `confidence_change_count`

Interpretation:

- no changes + fast correct = strong recall
- answer changes = weaker certainty
- confidence changes = unstable metacognition
- wrong after switching = confusion

Current scheduler behavior:

- hesitation slightly increases effective difficulty
- hesitation slightly lowers effective retrievability
- hesitation can shorten intervals modestly
- hesitation is calibrated over time

It should not overpower:

- repeated lapses
- future recall failure
- strong multi-session success history

---

## 11. Same-Day Relearn

This is one of the most important scientific improvements.

### Why it exists

Without same-day relearn:

- a fresh lapse can be over-rewarded after one correct answer
- card recovery looks stronger than it really is

### Current step model

1. failure enters `relearn` step 1
2. first successful spaced recovery advances to step 2
3. second successful spaced recovery exits relearn
4. card returns to long-term interval scheduling

### Effects

- prevents fake “instant mastery”
- makes recovery behavior more realistic
- gives the UI and planner a durable state to inspect

---

## 12. Skip Semantics

Skip is not deletion.

Current meaning:

- “not now”

What happens:

- `skip_count` increases
- `consecutive_skip_count` increases
- `skip_until` is set
- `next_review_at` is shifted
- memory state is slightly weakened

When the skip expires:

- card is reactivated automatically

This preserves:

- user control
- deck continuity
- real pressure on repeatedly avoided material

---

## 13. Failure Scenarios and Expected Behavior

## 13.1 Wrong + guessed

Expected:

- card re-enters short-term relearn
- difficulty rises
- less overconfidence penalty

## 13.2 Wrong + knew

Expected:

- stronger penalty
- overconfidence trap signal
- more conservative future spacing

## 13.3 Right + guessed

Expected:

- smaller reward
- shorter interval than strong correct
- card remains relatively fragile

## 13.4 Right + knew

Expected:

- larger reward
- longer interval
- but only fully trusted if the student’s confidence reliability is good

## 13.5 Hesitant correct

Expected:

- success is real
- but not as strong as clean correct
- slightly conservative interval

## 13.6 Repeated failure

Expected:

- higher difficulty
- lower stability
- more same-day relearn
- card stays near front

## 13.7 Repeated successful recovery

Expected:

- relearn exit
- long-term scheduling resumes
- interval begins to grow normally

## 13.8 Skip-heavy student

Expected:

- card returns later
- repeated skip pressure rises
- card stays in mistake deck until explicit removal

---

## 14. Concept Memory Mechanism

Concept memory exists so weakness can generalize across sibling cards.

### Basic idea

The system should not only know:

- “you forgot this one question”

It should also know:

- “you keep struggling with this idea across multiple questions”

### Implementation

Service:

- [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)

Behavior:

1. load question concepts
2. if missing, optionally create generated topic fallback concept
3. update per-user concept memory from card outcome

Concept memory currently stores:

- difficulty
- stability
- retrievability
- lapse count
- low-confidence success count
- overconfidence error count
- response timing summaries

---

## 15. Concept Analytics and Failure Clustering

Concept memory is analyzed further through:

- [ConceptAnalyticsService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptAnalyticsService.php)

It derives:

- concept risk score
- weakest concepts
- per-topic concept groups
- failure clusters

Current cluster types include:

- repeated lapse
- overconfidence trap
- hesitation drift
- slow recall
- fragile recall

These clusters matter because not all weak concepts are weak for the same reason.

---

## 16. How the Learn Deck Is Built

Service:

- [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)

### Responsibilities

- build learning deck
- reactivate expired skips
- load question/topic/course relations
- load concepts
- attach concept risk
- sort spaced decks
- cache deck with fingerprinting

### Spaced ordering

Spaced ordering prefers roughly:

1. earlier `next_review_at`
2. higher concept risk
3. lower retrievability
4. higher difficulty
5. older attempts

This makes due relearn and weak-concept cards rise first.

---

## 17. Planner Integration

Planner orchestration:

- [StudyGamePlanService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/StudyGamePlanService.php)

Context build:

- [PlannerContextFactory.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerContextFactory.php)

Task build:

- [PlannerTaskBuilderService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/PlannerTaskBuilderService.php)

Task completion:

- [TaskCompletionService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Planner/TaskCompletionService.php)

### Planner signals currently consumed

- due mistakes by topic
- overdue age
- average forgetting risk
- weak concepts
- concept clusters
- open mistake counts

### Relevant planner task types

- `topic_mistake_review`
- `mistakes_cleanup`
- `concept_repair`
- `concept_cluster_repair`
- `repair_topic`

### Meaning

The planner can now do more than “topic is weak”.

It can recommend:

- specific concept repair
- repeated-failure pattern repair
- due mistake review in fragile topics

---

## 18. Calibration Layer

Main service:

- [SchedulerCalibrationService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationService.php)

### What it does

1. records review events
2. recalibrates mode-level scheduler parameters
3. recalibrates user-level spaced confidence reliability
4. smooths learned parameters over time
5. applies quality guardrails

### Parameters it learns

- `recall_bias`
- `stability_scale`
- `difficulty_scale`
- `response_sensitivity`
- `confidence_sensitivity`
- `overconfidence_penalty`
- `hesitation_sensitivity`
- `relearn_scale`
- `decay_base`

### User-level spaced profile

It learns whether a specific student is:

- overconfident
- underconfident
- reliable with `knew`
- weakly calibrated in confidence labels

---

## 19. Backtesting Layer

Service:

- [SchedulerBacktestService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerBacktestService.php)

Purpose:

- compare predicted recall against actual future recall

Current outputs:

- sample size
- MAE
- Brier score
- observed recall rate
- predicted recall rate
- confidence segments
- interval-state segments
- data quality

### Important point

Backtesting does not automatically mean the scheduler is scientifically trustworthy.

If the sample is tiny or one-sided, good metrics can still be misleading.

That is why data-quality reporting was added.

---

## 20. Calibration Coverage / Diagnostics

Service:

- [SchedulerCalibrationCoverageService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationCoverageService.php)

Purpose:

- tell admin whether the scheduler has enough evidence to trust calibration

Current UI location:

- `Manage Access`
- `?tab=scheduler-quality`

Controller:

- [UserCourseController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/UserCourseController.php)

Views:

- [manage-access.blade.php](/Users/davidgeorge/Documents/Medcapsule-online/resources/views/admin/manage-access.blade.php)
- [scheduler-calibration.blade.php](/Users/davidgeorge/Documents/Medcapsule-online/resources/views/admin/partials/scheduler-calibration.blade.php)

This tab shows:

- mode-level quality
- confidence coverage
- spaced coverage volume
- users lacking enough evidence

Its purpose is to stop false certainty.

---

## 21. Routes

Main routes:

- `GET /review/learn`
- `POST /learn/review/{question_id}`
- `POST /review/skip/{question_id}`
- `POST /review/remove/{question_id}`
- `GET /manage-access`

Route definitions:

- [routes/web.php](/Users/davidgeorge/Documents/Medcapsule-online/routes/web.php)

---

## 22. Controllers

### Learn flow

- [LearnController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/LearnController.php)

Responsibilities:

- load learn page
- validate review submissions
- enforce spaced confidence requirement
- forward interaction signals

### Mistake actions

- [MistakeController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/MistakeController.php)

Responsibilities:

- list mistakes
- remove mistake
- skip mistake
- legacy rating path

### Admin diagnostics tab

- [UserCourseController.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Http/Controllers/UserCourseController.php)

Responsibilities:

- render `Manage Access`
- now also inject scheduler calibration coverage data

---

## 23. Models

Main relevant models:

- [Mistake.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Mistake.php)
- [MistakeReviewEvent.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/MistakeReviewEvent.php)
- [Concept.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Concept.php)
- [UserConceptMemory.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/UserConceptMemory.php)
- [SchedulerCalibrationProfile.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/SchedulerCalibrationProfile.php)
- [UserSchedulerCalibrationProfile.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/UserSchedulerCalibrationProfile.php)

---

## 24. Commands

### Recalibration

- [CalibrateMistakeSchedulerCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/CalibrateMistakeSchedulerCommand.php)
- command:
  - `php artisan mistakes:calibrate-scheduler --mode=all --days=120`

Purpose:

- recalibrate scheduler parameters from review history

### Backtesting

- [BacktestMistakeSchedulerCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/BacktestMistakeSchedulerCommand.php)
- command:
  - `php artisan mistakes:backtest-scheduler --mode=all --days=180`

Purpose:

- validate predicted recall against actual later recall

### Long-run stress simulation

- [StressTestSpacedRepetitionCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/StressTestSpacedRepetitionCommand.php)
- command:
  - `php artisan mistakes:stress-test-spaced-repetition --days=365`

Purpose:

- simulate long-run personas over many days
- probe algorithmic edges such as:
  - ease hell
  - interval explosion
  - overconfidence rigidity
  - hesitation over-penalization

Current personas in that command include:

- The Perfectionist
- The Steady Learner
- The Guesser
- The Hesitator
- The Volatile
- Ease Hell Victim
- The Overconfident
- Long Interval Forgetter

### Scheduled calibration

Scheduler registration:

- [Kernel.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Kernel.php)

Current scheduled behavior:

- recalibration runs twice daily

---

## 25. Test Coverage

Main tests:

- [AdaptiveMistakeSchedulerTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/AdaptiveMistakeSchedulerTest.php)
- [SpacedRepetitionMasterStressTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/SpacedRepetitionMasterStressTest.php)
- [StudyGamePlanServiceTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/StudyGamePlanServiceTest.php)
- [SchedulerCalibrationTabTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/Admin/SchedulerCalibrationTabTest.php)

### Coverage areas already tested

- interval growth differences
- review endpoint persistence
- spaced short relearn failures
- relearn step progression
- skip reactivation
- confidence effects
- hesitation effects
- user-confidence calibration
- backtest metrics
- concept-aware deck ordering
- concept repair planner tasks
- concept cluster repair planner tasks
- admin coverage tab access
- master stress persona matrix

---

## 26. Current Strengths

The current system is strong in:

- adaptive per-card scheduling
- same-day relearn modeling
- confidence-aware scheduling
- hesitation-aware scheduling
- user-specific confidence calibration
- skip semantics
- concept-level memory
- concept failure clustering
- planner integration
- backtesting
- calibration coverage diagnostics
- stress testing

This is no longer a simple streak-based mistake loop.

---

## 27. Current Limitations

Important real limitations remain:

1. scientific trust still depends on enough real spaced-review history
2. concept intelligence is only as good as concept tagging quality
3. classic mode is intentionally less “smart” because it preserves the old experience
4. spaced mode is card-first, concept-aware second, not concept-only tutoring

So the system is structurally advanced, but empirical trust depends on coverage.

---

## 28. Practical Reading Guide

If you want to understand the system in the fastest order:

1. [Mistake.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Models/Mistake.php)
2. [AdaptiveMistakeScheduler.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/AdaptiveMistakeScheduler.php)
3. [MistakeManagementService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/MistakeManagementService.php)
4. [SchedulerCalibrationService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/SchedulerCalibrationService.php)
5. [ConceptMemoryService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptMemoryService.php)
6. [ConceptAnalyticsService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/Learning/ConceptAnalyticsService.php)
7. [StudyGamePlanService.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Services/StudyGamePlanService.php)
8. [SpacedRepetitionMasterStressTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/SpacedRepetitionMasterStressTest.php)

---

## 29. Final Summary

Classic mode is repetition-oriented.

Spaced mode is memory-state-oriented.

The system now:

- stores adaptive card memory
- stores adaptive concept memory
- learns from confidence, timing, hesitation, skips, and repeated outcomes
- calibrates itself from review history
- backtests its own predictions
- warns when evidence quality is too weak
- feeds mistake and concept fragility into the planner

That is the current mechanism of action of the MedCapsule spaced repetition system.
