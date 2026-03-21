Here's the full E2E logic map for your AI agent, synthesized from everything in this conversation:

---

## MedCapsule Study Planner — Complete AI Agent Context

### What It Is
A curriculum-ordered, source-aware, game-feel study task planner. Not a recommendation engine — a deterministic progression system. The student "clears" topics one by one like levels in a game.

---

### STEP 1: Resolve Student Context (Waterfall)

Before any task is generated, resolve per-course settings. Course override wins → global setting → infer from behavior → hard default.

**Resolved per course:**
- `exam_date`: `course_user.planner_exam_date` → `student.global_exam_date`
- `preferred_source`: student's college (Assiut, Cairo, Alexandria, etc.). Every question has exactly one source. No "mixed."
- `repair_mode`: course override → global → infer from 14-day SR activity → default `easy`
- `classic_repair_difficulty`: course override → global → default `easy`
- `has_sr_access`: if `false`, spaced repair is never available. Skip SR inference entirely.
- `focus_mode`: balanced / revision / exam

**SR inference rule:** if student completed spaced review in this course in the last 14 days → infer `spaced`. Otherwise → `classic`.

---

### STEP 2: Measure Pressure Signals

Kept as separate buckets, never combined:

| Signal | Description |
|---|---|
| `due_spaced_reviews` | SR cards due today |
| `active_open_backlog` | Unresolved mistakes, actively due |
| `deferred_skipped_backlog` | Ignored 14+ days — lighter pressure |
| `assignment_urgency` | Upcoming deadlines |
| `exam_urgency` | Days until resolved course exam |

**Exam urgency XP multipliers:**
- `< 3 days` → 2.0x, hard tutor block
- `≤ 7 days` → 1.5x
- `≤ 14 days` → 1.25x
- `otherwise` → 1.0x

---

### STEP 3: Build Topic Profiles

For every topic in the course, compute:

- `progression_stage`: which stage the student is on (see Step 4)
- `curriculum_unlocked`: predecessor topic (by `display_order`) is Cleared?
- `source_specific_accuracy`: accuracy on preferred source questions only. Falls back to overall only if zero preferred source questions exist.
- `last_interaction`: from `user_topic_progress.last_attempt_date`
- `weak_concepts`: array of strings from wrong answers. Array-shaped for future multi-tag support. Flag `concept_tagging_complete: false` until real tags fed.
- `due_mistake_ids`: unresolved mistake IDs, preferred source first

**Topic state classification:** `untouched` / `weak_stale` (>3 days) / `weak_recent` (≤3 days) / `secure` / `review_heavy`

---

### STEP 4: The Three-Stage Progression (Core Rule)

Every topic cleared in order. Non-negotiable.

**Stage 1 — Study (Tutor mode)**
- Pass: session finished. No score bar.
- Skip rule: if student already has ≥70% preferred-source accuracy → treat tutor as pre-cleared.

**Stage 2 — Solve (Quiz mode)**
- Pass: ≥70% correct on **preferred source questions specifically** (not overall).
- Outcome A: passed, zero mistakes → skip Stage 3, immediately **Cleared**
- Outcome B: passed, mistakes exist → Stage 3 unlocks
- Outcome C: failed → quiz re-queues, Stage 3 blocked. After 2+ failed attempts since last tutor → re-surface Stage 1 (Re-study)

**Stage 3 — Revise Mistakes (Repair mode)**
- Repair mode from resolved setting:
  - `spaced` (requires `has_sr_access = true`) → due SR cards. Pass = all assigned cards cleared.
  - `classic easy` → cycling batch, 2 consecutive correct per question. Pass = all graduated.
  - `classic hard` → cycling batch, 3 consecutive correct. Pass = all graduated.
- Difficulty locked at spawn via `difficulty_locked_at`. Never changes mid-session.
- Pass = all assigned mistakes graduated → topic is **Cleared**

**Cleared** = tutor done + quiz ≥70% preferred source + all mistakes resolved.

**Progression stage markers** (written by `TaskCompletionService`, never by the planner generator):
- `planner_tutor_cleared_at` — written when tutor session finishes
- `planner_quiz_cleared_at` — written when preferred-source quiz passes ≥70%
- `planner_cleared_at` — written when Stage 3 completes OR quiz passes with zero mistakes

---

### STEP 5: Curriculum Frontier Enforcement

- Active topic = lowest `display_order` topic that is `curriculum_unlocked = true` and not yet Cleared.
- New-work tasks (Study, Solve, Revise Mistakes) generated for **exactly one topic at a time** — the frontier.
- Mistake review, spaced review, repair for already-Cleared topics are **exempt** from frontier gate — always surface.
- Never generate `mixed_quiz`, `random_quiz`, or undefined task types.

---

### STEP 6: Task Generation — 3 Slots

**Slot 1 — always the frontier task (actionable now):**

| Topic State | Task Emitted |
|---|---|
| Untouched or Stage 1 | Study: [topic] (tutor) |
| Stage 2 | Solve: [topic] (quiz) |
| Stage 3 | Revise Mistakes: [topic] (repair) |
| Cleared, regressed >48h | Re-study: [topic] (re-opens at Stage 2) |

**Slots 2 & 3 — locked progression previews:**
- Slot 2 = next step in current topic's sequence (Study → Solve preview, Solve → Revise Mistakes preview, Revise Mistakes → next topic's Study preview)
- Slot 3 = step after slot 2

Preview tasks: `is_preview: true`, `unlocks_after_task_id: [parent]`. Visible but not interactable. Not stored as real tasks. No XP shown.

**Cleared-topic backfill** only activates when student is in full maintenance mode (no upcoming progression steps). Six-level hierarchy:
1. Mistake review for cleared topics
2. SR due cards for cleared topics
3. Quiz drill on cleared secure topic
4. Re-review cleared topic neglected 14+ days
5. Clear Early Mistakes for locked future topics (repairs mistakes, does NOT advance stage, labeled differently)
6. If nothing → show fewer than 3. Never invent a task type.

**Valid task types only:**
`Study` / `Solve` / `Revise Mistakes` / `Re-study` / `Quick Study` (untouched topic, <3 day exam gate, 10 min cap) / `concept_repair` (child task) / `Clear Early Mistakes` (locked topic backfill)

---

### STEP 7: Exam Gate

Inside `< 3 days` to exam:
- All tutor tasks hard-blocked. Not down-weighted — suppressed.
- Exception: untouched topics get `Quick Study` (10 min cap).
- `3–7 days`: simulation allowed as slots 2–3 backfill only (never slot 1).
- `< 3 days`: simulation replaces Stage 2 quiz on frontier topic.

---

### STEP 8: Completion Integrity (Absolute Rules)

| Scenario | Result |
|---|---|
| Wrong mode | No completion, no XP, task stays open |
| Partial score | Reduced XP proportional to score, task re-queues |
| Tutor / Quick Study | Binary — session finished = complete |
| Quiz pass | ≥70% preferred-source questions |
| Simulation pass | ≥60% |
| Classic repair pass | All assigned questions graduated |
| Spaced repair pass | All assigned SR card IDs cleared |
| Random quiz | Never completes a planner task |

**After parent task completes with weak concepts remaining:**
- Spawn `concept_repair` child task immediately in session-completion path (not next planner cycle)
- ID: `concept_repair_child_{parent_task_id}_{concept_key}` — idempotent
- Targets weak concepts only, not whole topic

**Regression:**
- `< 48h` after completion → noise. Concept repair child only. Topic stays Cleared.
- `> 48h` after completion → real regression. Re-opens at Stage 2 (not Stage 1). `is_regression: true`.

---

### STEP 9: Mistake Mastery Persistence

Table: `user_mistake_mastery` — one row per student per question (lightweight upsert).

Fields: `user_id`, `question_id`, `course_id`, `consecutive_correct` (resets on wrong), `graduated_at` (nullable), `last_attempt_at`, `last_attempt_result`, `total_attempts`.

Written by: repair sessions, quiz sessions, random quiz sessions, tutor sessions. All session types write to it.

**Key rules:**
- Mastery is a tracking layer only — never a gate. Students can revisit any mistake anytime.
- Classic graduation threshold lives in the task (locked at spawn), not in the mastery table.
- SR has separate existing fields (`next_review_at`, `retrievability`). Separate system, no interference.
- Random quiz graduation counts as real mastery but accuracy is downweighted in rolling source mastery signal.
- `session_type = random_quiz` flag on attempt records.

---

### STEP 10: XP Rules

XP snapshotted at task spawn. Never recalculated.

| Task Type | Base XP |
|---|---|
| Simulation | 24 |
| Repair / Revise Mistakes | 16 |
| Study / Tutor | 14 |
| Solve / Quiz | 12 |
| Quick Study | 6 |
| concept_repair child | 16 |

Multiplier = `priority_score` + exam urgency band. Hard cap 2.5x.
- Regression tasks: base XP only, no urgency multiplier.
- Partial completion: XP × score percentage.
- Wrong mode: 0 XP.
- Clear Early Mistakes (locked topic backfill): base XP only, no multiplier.

---

### STEP 11: Plan Stability

- 15-minute stability window during normal use.
- All tasks completed → plan freezes 6 hours before regenerating.
- Early regen triggers: new SR cards due, exam urgency band change, assignment deadline change, settings changed.
- Completed tasks visible in sidebar for 24 hours in done state, then removed.
- Plan version bumped on any behavioral deployment → forces stale plan regeneration.

---

### STEP 12: Admin/Tutor View

Read-only debug view only. Shows: current plan, task metadata (`score_components`, `trigger_reason`, `priority_score`), `progression_stage` per topic, `concept_tagging_complete` status.

No lock overrides. No task generation for admins. The only admin action (deferred): explicit topic progression reset that clears `user_topic_progress` + `user_mistake_mastery` for that topic and forces planner regeneration.

---

### Known Gaps (Not Yet Complete)

1. **Concept tags** — `target_concepts` falls back to topic name. `concept_tagging_complete: false` on all tasks. This is content work (tagging questions manually), not engineering.
2. **Per-course exam date UI** — backend resolver works, no UI surface yet.
3. **Repair mode/difficulty UI** — resolver works, no student-facing controls yet.
4. **Source-specific quiz pass condition** — must check preferred-source accuracy specifically, not `percentage_grade` overall.
5. **Legacy GradingService test cleanup** — old XP expectations drifting.

---

### One-Sentence Summary

The planner locks a student to one topic at a time in curriculum order, walks them through Study → Solve → Revise Mistakes using only their college source material, shows the next 3 steps as a roadmap, never lets the wrong mode count as completion, and remembers every mistake genuinely fixed versus temporarily gotten right.