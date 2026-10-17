# Written answer grading

The canonical grader evaluates the reference answer and any administrator-supplied alternative answers. Question wording informs list and blank handling. It uses deterministic matching, not a language model; it cannot reliably infer every valid paraphrase or clinical context. Add accepted alternatives to the question when its meaning cannot be represented by the reference alone.

Version 3.2 improves contractions (including curly apostrophes), absence wording, and negation attached to individual concepts. Explicit contrasts such as “insulin, not glucagon” are accepted only when the asserted part covers the full reference and the excluded part shares no reference concepts. Typos and ordinary list reordering remain supported. Wrong anatomy, changed direction, and mismatched units retain their existing safeguards.

For a list of short reference items, students may separate their answers with “and”, commas, semicolons, or newlines. Compound reference items retain their internal conjunctions.

## Parameters

Defaults are in `config/written_grading.php`:

| Environment setting | Default | Purpose |
| --- | --- | --- |
| `WRITTEN_GRADING_PASS_THRESHOLD` | 0.72 | Overall concept grading threshold |
| `WRITTEN_GRADING_REQUIRED_CONCEPT_THRESHOLD` | 0.74 | Minimum required concept coverage |
| `WRITTEN_GRADING_ENUMERATION_THRESHOLD` | 0.70 | Coverage for lists without an explicit requested count |
| `WRITTEN_GRADING_MINIMUM_REQUESTED_ITEM_COVERAGE` | 0.75 | Minimum coverage when accepting one missing requested point; set to 1.0 for strict counts |

Explicit counts require distinct matched items. With the default, three of four can pass, but one of two cannot. The existing precision check must also pass. Feedback identifies the matched and requested counts when points are missing. These settings apply to their respective grading branches; a numeric score is a matching score, not a calibrated probability of correctness.

After environment changes, rebuild the application configuration cache. The production deployment already does this on startup.

## Verification

`WrittenAnswerContextTest` covers phrasing, contractions, scoped negation, contrasts, spelling, and list context. `WrittenGradingContextTest` verifies real quiz submission and configurable list strictness. Run the full grading suite as well to protect existing alternatives, comparisons, numeric answers, and domain safeguards.
