# Spaced Repetition Golden-Standard Tasks

## Current Task

- [x] Task 1: Add hesitation tracking to spaced mode review events and scheduler decisions.
- [x] Task 2: Build the concept-tagging workflow for questions.
- [x] Task 3: Freeze retrieval timing before confidence interaction.
- [x] Task 4: Add explicit same-day relearn states for spaced mode.
- [x] Task 5: Learn user-level confidence calibration.
- [x] Task 6: Add skip semantics to the scheduler model.
- [x] Task 7: Backtest scheduler formulas against historical review outcomes.
- [x] Task 8: Add concept-level remediation tasks in the planner.

Success criteria:

- weak concepts now generate dedicated `concept_repair` planner tasks
- those tasks complete from concept-memory recovery, not only topic grade changes
- historical review outcomes produce MAE / Brier-style accuracy metrics
- backtests can run by mode and optional user
- weak concept risk now helps decide spaced card order when due times tie

## Ordered Roadmap

- [ ] Task 9: Add concept-level analytics and failure clustering.
- [ ] Task 10: Add staff tooling to review and merge duplicate concepts.
- [ ] Task 11: Add premium user-facing explanations for why a card or concept is being shown now.

## Why Task 4 Was High ROI

Without explicit same-day relearn states, spaced mode still had a fuzzy gap between:

- failing a card
- recovering it once
- actually stabilizing it enough to leave the same-day loop

That matters because:

- “one correct answer” should not instantly look stable after a fresh lapse
- spaced mode needs real same-day recovery steps, not only minute delays
- the UI and planner both need a durable state they can inspect

## Notes

- Use human-readable concept names.
- Use lowercase kebab-case slugs.
- Prefer one precise concept over one giant vague concept.
- A question can map to multiple concepts when needed, but avoid tagging everything too broadly.
