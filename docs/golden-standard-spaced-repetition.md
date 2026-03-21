# Golden-Standard Spaced Repetition

## Current Schema Reality

We did **not** add a `concept_tag` column directly to `questions`.

The concept model is currently:

- `concepts`
- `concept_question`
- `user_concept_memories`

That is the correct shape for this system because one question can map to multiple concepts, and one concept can appear in many questions.

## Concept Format

Each concept should have:

- `name`: human-readable label shown to staff and eventually users
- `slug`: machine-safe identifier used in code and matching

Recommended format:

- `name`: `Venous pressure`
- `slug`: `venous-pressure`

Do **not** use:

- `Venous_pressure`
- `venous_pressure`
- `VENOUS_PRESSURE`

Rules:

1. `name` should be clean educational language.
2. `slug` should be lowercase kebab-case.
3. A concept should represent one teachable idea, not a whole topic.
4. Use the same concept across sibling questions if they test the same underlying idea.

Examples:

| name | slug |
| --- | --- |
| Venous pressure | `venous-pressure` |
| Partial pressure gradient | `partial-pressure-gradient` |
| Starling forces | `starling-forces` |
| Osmotic pressure | `osmotic-pressure` |
| Alveolar gas exchange | `alveolar-gas-exchange` |

## Golden-Standard Target

The premium spaced repetition system should become:

1. **Card-aware**
Tracks the exact mistake card.

2. **Concept-aware**
Tracks the underlying concept across multiple cards.

3. **Confidence-aware**
Treats `guessed`, `unsure`, and `knew it` differently.

4. **Timing-aware**
Uses retrieval time as a real signal, not just correctness.

5. **Calibration-driven**
Learns scheduler parameters from actual future recall outcomes.

6. **Planner-integrated**
Tells the study planner exactly which concept, topic, and review mode should come next.

7. **Student-specialized**
Learns whether a specific student is overconfident, slow-but-accurate, fragile after gaps, or strong under pressure.

## Scientific Model We Are Building Toward

Each review decision should depend on:

- correctness
- confidence
- retrieval time
- hesitation before commit
- answer changes before submit
- confidence changes before submit
- prior interval
- prior lapses
- same-day relearn count
- estimated stability
- estimated retrievability
- estimated concept fragility
- user-level confidence calibration

The scheduler should maintain at least two levels of memory state:

- **card memory**
- **concept memory**

That allows the system to detect:

- the student forgot this exact card
- the student keeps failing the same idea across multiple cards

## What “A+” Looks Like

An A+ implementation means:

1. The next card is chosen for a clear reason.
2. The next interval is evidence-driven, not arbitrary.
3. The planner can say which concept is weak, not only which topic.
4. Same-day relearn is different from long-term review.
5. Confidence is calibrated per student.
6. Skip behavior is modeled intentionally, not ignored.
7. Historical review data can be backtested against alternative scheduler formulas.

## Immediate Design Principles

1. Never let UI convenience corrupt memory data.
2. Prefer normalized many-to-many concept mapping over question columns.
3. Never show fake precision in the UI if the model does not truly support it.
4. Keep classic cycling available, but let spaced mode be genuinely scheduler-driven.
5. Prefer explicit concept tagging over guessing when quality matters.

## Current Implementation Status

Already in place:

- adaptive mistake scheduler
- review-event logging
- calibration profiles
- spaced-mode confidence
- per-card timing capture
- concept tables
- concept memory aggregation
- planner concept signals
- planner task titles/reasons using weakest explicit concept
- planner concept repair tasks
- planner concept cluster repair tasks
- hesitation tracking for answer and confidence changes
- question-bank concept tagging workflow
- overdue-penalty interval scaling
- interval jitter
- sibling dispersion for related cards
- confidence reliability floor hardening
- stress-test personas for spaced and non-spaced failure paths
- dedicated spaced-repetition fixture seeder for algorithm validation

Not yet complete:

- live calibration depth still depends on enough real spaced-review history
- concept intelligence still depends on dense question tagging
- written-answer spaced timing still does not have the same first-selection signal as MCQ spaced mode
- concept-only study sessions are not yet a first-class review mode

## Hesitation Signals

Hesitation should be treated as a secondary but real memory signal.

We want to capture:

- `first_selected_answer`
- `final_selected_answer`
- `answer_change_count`
- `time_to_first_selection`
- `confidence_change_count`
- `first_confidence`
- `final_confidence`

Interpretation:

- correct with no changes: strong recall
- correct after answer changes: weaker recall
- correct after confidence changes: unstable certainty
- wrong after switching answers: confusion
- wrong with `knew it`: overconfidence

Hesitation should:

- slightly increase effective difficulty
- slightly lower effective retrievability
- slightly shorten the next interval
- be learned and recalibrated from future recall outcomes

It should not overpower:

- repeated lapses
- future recall failure
- long-term stability history

## Ordered Implementation Path

1. Completed: explicit question-to-concept tagging workflow.
2. Completed: hesitation capture in spaced mode.
3. Completed: spaced MCQ answers use select-then-submit so answer changes can exist.
4. Completed: hesitation fields persist on review events.
5. Completed: hesitation feeds into the scheduler with a calibrated penalty.
6. Completed: hesitation sensitivity is learned through calibration.
7. Completed: retrieval timing freezes at answer-selection intent for spaced MCQ.
8. Completed: explicit same-day relearn states with durable `relearn` step progression.
9. Completed: concept-level remediation tasks and concept-cluster repair tasks in the planner.
10. In progress: deeper backtesting dashboards and side-by-side scheduler variant comparisons.
11. Next high-value step: expand real concept tagging coverage and collect richer live spaced-review history.

## Current Validation Reality

The earlier analysis notes that flagged these issues are now out of date:

- persona contamination in the stress command
- relearn fast-forward stopping after one step
- non-spaced failure-path coverage missing
- ease-hell persona coverage being too weak
- undefined non-spaced cooldown variables

Current validation assets now include:

- the master feature harness in [SpacedRepetitionMasterStressTest.php](/Users/davidgeorge/Documents/Medcapsule-online/tests/Feature/SpacedRepetitionMasterStressTest.php)
- the CLI persona stress runner in [StressTestSpacedRepetitionCommand.php](/Users/davidgeorge/Documents/Medcapsule-online/app/Console/Commands/StressTestSpacedRepetitionCommand.php)
- the dedicated fixture seeder in [SpacedRepetitionFixtureSeeder.php](/Users/davidgeorge/Documents/Medcapsule-online/database/seeders/SpacedRepetitionFixtureSeeder.php)

This means the system is now structurally much closer to a real scientific scheduler, and the main remaining limits are evidence volume and concept-tag density rather than missing core mechanisms.
