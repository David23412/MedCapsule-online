💀 Split-Brain & Boolean Chaos — Surgical Refactor (Zero-Deviation, Fail-Closed)
Role: You are the Chief Refactor Surgeon for a Laravel (PHP 8.1+) monorepo. Your job is to eliminate all split-brain logic and multi-boolean state, implement one single source of truth per domain, and prove it via tests, constraints, and repo-wide codemods.
Input: There is an MD report (the one the author created) that lists confirmed targets and inconsistencies. Read it end-to-end and treat it as ground truth. (Search for a file named split_brain_analysis.md or similar in /docs or repo root. If none found, create it from the content below, then proceed.)
Datastore: MySQL 8+. Assume InnoDB, strict mode. Prefer native CHECK constraints; if not supported or ignored, use BEFORE INSERT/UPDATE triggers to enforce invariants.
0) HARD REQUIREMENTS (MUST FOLLOW)
SSoT Only: After refactor, no business state may be represented by more than one persisted field. Any duplicates must be removed, deprecated, or converted to computed accessors.
Zero-downtime Migration Plan: Use two-phase rollout:
Phase A (compat): Add new schema (nullable), dual-write and read-prefer-new. Backfill.
Phase B (cutover): Flip reads fully to new, validate parity, drop old columns, add constraints (NOT NULL, CHECK, DEFAULT).
DB-Level Invariants: Add CHECK constraints or triggers to prevent invalid states (mutually exclusive flags, impossible combos). Fail closed on violations.
Codemod Completeness: Replace all legacy checks project-wide (PHP, Blade, JS/TS). Fail the job if any matches remain.
Tests Gate: Add/adjust tests. CI must fail if:
Any legacy fields/methods are referenced.
Invariants are violated.
Status/SSoT helpers return inconsistent results vs fixtures.
Idempotent Migrations: All migrations must be re-runnable without side effects. Include guards (check existence before create/drop).
Observability: Log warnings for any legacy write paths during Phase A; the log must go silent before Phase B.
Docs: Update docs/ARCHITECTURE.md and create docs/STATE_INVARIANTS.md listing final SSoT, allowed transitions, constraints, and deprecation schedule.
Rollback Plan: Provide a scripted rollback for each Phase B migration.
1) CONFIRMED TARGETS (Refactor Objectives)
Use the MD report you have (author’s findings). If missing, treat the following as the targets:
Course Enrollment (HIGH)
Duplicates: course_user.is_enrolled (bool), course_user.enrolled_at (dt), payments.status='completed' (str), payments.paid_at (dt).
Inconsistent checks across User::hasAccessToCourse, User::isEnrolledInCourse, Course::isUserEnrolled, PaymentService.
Final SSoT: Enrollment is true iff there exists a completed payment for the course and an enrollment record with enrolled_at set.
API: User::getEnrollmentStatus(int $courseId): EnrollmentStatus + User::isEnrolledIn(int $courseId): bool (delegates to SSoT).
Deprecate: all direct reads of is_enrolled.
Assignment Status (HIGH)
Duplicates: isOverdue/isActive/isExpired/getCurrentStatusAttribute/getDisplayStatusAttribute.
Final SSoT: Assignment::computeStatus() → one canonical function using deadline, duration_minutes, and now().
API: status accessor + scopeWithStatus($status); optional generated virtual column for indexing if needed.
Cache: Optional cached column status_cached with invalidation on relevant updates.
User Progress (MEDIUM)
Duplicates: users.correct_answers_count, quiz_attempts.correct_answers, quiz_attempts.score.
Final SSoT: quiz_attempts.score only.
User total: materialized via users.correct_answers_count updated by events/triggers; reads use user column; recompute job available.
Mistake Mastery (MEDIUM)
Duplicates: mistakes.mastered (bool) vs correct_streak logic.
Final SSoT: computed mastery from correct_streak with config thresholds; no persisted boolean.
API: Mistake::isMasteredForMode(Mode $mode): bool.
Video Progress (LOW)
Duplicates: completed (bool) vs progress_percentage.
Final SSoT: complete iff progress_percentage >= 100.
Deprecate: completed.
Topic Locking (LOW)
Duplicates: is_locked (bool) vs locked_at/unlocked_at/locked_by_tutor_id.
Final SSoT: locked iff locked_at IS NOT NULL AND (unlocked_at IS NULL OR locked_at > unlocked_at).
API: Topic::isLocked(): bool.
2) EXECUTION PLAN (Step-by-Step, Non-Negotiable)
PHASE A — Compatibility (dual-write, read-prefer-new)
Create Safety Harness
Add app/Support/State/ with final helpers:
EnrollmentService, AssignmentStatusService, UserProgressService, MistakeService, VideoProgressService, TopicLockService.
All controllers/services must call these helpers only. No direct column checks allowed.
Schema Additions
Add new columns if needed (e.g., status_cached, enums) as nullable with defaults.
For booleans slated for removal, do not drop yet.
Dual-Write Bridges
On write paths, update both old and new representations.
Add Observers/Events (or domain services) to keep parity during Phase A.
Emit warning logs when old columns are written.
Backfill Scripts
Write safe, chunked backfills (id ranges, 1k rows/chunk) with retries and metrics:
Enrollment: set enrolled_at for any valid paid enrollment; flag inconsistencies to a CSV.
Assignments: compute and populate status_cached if used.
Progress: copy correct_answers → score (if both existed); recompute users.correct_answers_count from attempts.
Mistakes: remove reliance on mastered by backfilling queries to use streak.
Videos: set completed = (progress_percentage >= 100) for audit, but keep as legacy.
Topics: compute is_locked from timestamps and verify parity.
Read Path Flip
Change all reads to the new helpers (SSoT).
Deprecate old methods with @deprecated docblock and trigger a runtime deprecation warning if invoked.
Constraints (Soft)
Add CHECK constraints that allow both representations but block impossible combos (e.g., completed=1 while progress_percentage < 100).
If MySQL version ignores CHECK, add BEFORE INSERT/UPDATE triggers to SIGNAL SQLSTATE '45000' on violations.
Parity Tests
Golden tests compare old vs new logic on a seeded dataset; must match for all cases (except explicitly corrected inconsistencies).
PHASE B — Cutover (single-write, strict invariants)
Flip Writes to New
Remove dual-write; writes only hit SSoT fields.
Old columns become read-only (guard with triggers preventing changes).
Drop/Alter Columns
Drop legacy booleans (is_enrolled, completed, is_locked, mastered, etc.) and redundant fields (quiz_attempts.correct_answers).
Set SSoT columns to NOT NULL with strict DEFAULTs.
Add final CHECK constraints.
Kill Legacy APIs
Delete deprecated methods and scopes.
Remove any conditionals checking legacy fields in Blade/JS.
Code search must return zero hits for legacy identifiers.
Silence Logs
Ensure no Phase-A “legacy write” warnings are emitted in test/QA.
Docs & Rollback
Document exact diffs, migration hashes, and rollback scripts.
3) CONCRETE IMPLEMENTATIONS (Exact Orders)
A) Enrollment (HIGH)
Eloquent API
enum EnrollmentStatus: string { case NONE='none'; case ACTIVE='active'; }
final class EnrollmentService {
    public static function statusFor(User $u, int $courseId): EnrollmentStatus {
        $paid = Payment::where('user_id',$u->id)->where('course_id',$courseId)->where('status','completed')->exists();
        $enrolled = DB::table('course_user')->where('user_id',$u->id)->where('course_id',$courseId)->whereNotNull('enrolled_at')->exists();
        return ($paid && $enrolled) ? EnrollmentStatus::ACTIVE : EnrollmentStatus::NONE;
    }
    public static function isEnrolled(User $u, int $courseId): bool {
        return self::statusFor($u, $courseId) === EnrollmentStatus::ACTIVE;
    }
}
Migrations (Phase A)
Ensure course_user.enrolled_at exists; if not, add.
Add soft CHECK (or trigger) to prevent is_enrolled=1 when enrolled_at IS NULL.
Codemod: Replace all hasAccessToCourse() and isEnrolledInCourse() direct boolean checks with EnrollmentService::isEnrolled().
Phase B: Drop is_enrolled, add final CHECK tying enrollment semantics to timestamp (or keep timestamp only).
B) Assignment Status (HIGH)
Canonical Status
enum AssignmentStatus: string { case UPCOMING='upcoming'; case ACTIVE='active'; case EXPIRED='expired'; case OVERDUE='overdue'; }
final class AssignmentStatusService {
    public static function compute(Assignment $a, Carbon $now=null): AssignmentStatus {
        $now ??= now();
        if ($a->duration_minutes > 0 && $a->created_at->addMinutes($a->duration_minutes)->lt($now)) return AssignmentStatus::EXPIRED;
        if ($a->deadline && $a->deadline->lt($now)) return AssignmentStatus::OVERDUE;
        if ($a->deadline && $now->lt($a->deadline)) return AssignmentStatus::ACTIVE;
        return AssignmentStatus::UPCOMING;
    }
}
Remove duplicates: delete getCurrentStatusAttribute, getDisplayStatusAttribute, and is*() methods; replace with one accessor using AssignmentStatusService::compute().
Optional cache: assignments.status_cached with invalidation on created_at, deadline, duration_minutes updates.
Tests: Exhaustive boundary tests (exact minute boundaries).
C) User Progress (MEDIUM)
Source of Truth: quiz_attempts.score.
Migration (Phase A): If correct_answers exists, backfill score = GREATEST(score, correct_answers).
User total sync:
Add DB trigger or App event on quiz_attempts insert/update/delete to update users.correct_answers_count.
Add php artisan progress:recompute --user=* command for reconciliation.
Phase B: Drop quiz_attempts.correct_answers. Ensure reads use user column + recompute job.
D) Mistake Mastery (MEDIUM)
SSoT: correct_streak, thresholds in config (config/mistakes.php):
return ['thresholds'=>['quick'=>2,'quiz'=>4]];
API: Mistake::isMasteredForMode(Mode $m) uses thresholds.
Phase B: Drop mistakes.mastered.
Queries: update scopes to use streak predicate; add tests for both modes.
E) Video Progress (LOW)
SSoT: progress_percentage >= 100.
Phase B: Drop completed.
Constraint: CHECK (progress_percentage BETWEEN 0 AND 100).
F) Topic Locking (LOW)
SSoT: locked iff locked_at IS NOT NULL AND (unlocked_at IS NULL OR locked_at > unlocked_at).
Accessor: Topic::isLocked() returns above.
Phase B: Drop is_locked.
Constraint/Trigger: enforce unlocked_at >= locked_at when both present.
4) STATIC ANALYSIS & CODEMOD (MANDATORY)
Create script: scripts/verify_state_ssot.sh generating a non-zero exit if any legacy identifiers remain.
Blocked identifiers (grep -R, case-insensitive):
\bis_enrolled\b, \bhas_access_to_course\b, \bisEnrolledInCourse\b
\bgetCurrentStatusAttribute\b, \bgetDisplayStatusAttribute\b, \bisOverdue\b, \bisExpired\b, \bisActive\b
\bcorrect_answers\b (in quiz_attempts)
\bmastered\b (in mistakes table/models/scopes)
\bcompleted\b (in video_progress)
\bis_locked\b (in topics)
Also flag: any direct where('status', 'completed') enrollment checks outside EnrollmentService.
Tools:
Run PHP-CS-Fixer + Larastan (phpstan) at level max.
Add Rector rules (or code mods) to replace legacy calls with new services.
Add Psalm optional pass if configured.
5) DB CONSTRAINTS / TRIGGERS (EXACT)
Example CHECK (MySQL 8+):
ALTER TABLE video_progress
  ADD CONSTRAINT chk_progress_pct CHECK (progress_percentage >= 0 AND progress_percentage <= 100);
Legacy guard (if CHECK unsupported):
CREATE TRIGGER trg_video_progress_check BEFORE INSERT ON video_progress
FOR EACH ROW
BEGIN
  IF NEW.progress_percentage < 0 OR NEW.progress_percentage > 100 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='progress_percentage out of range';
  END IF;
END;
Mutual-exclusion during transition (example):
ALTER TABLE course_user
  ADD CONSTRAINT chk_enrollment_consistency
  CHECK (is_enrolled = 0 OR enrolled_at IS NOT NULL);
6) CI GATES (MUST ADD)
Job 1: composer test with coverage thresholds ≥ previous baseline.
Job 2: scripts/verify_state_ssot.sh (must exit 0).
Job 3: Run migrations up/down against a throwaway DB, then seed, then run tests again.
Job 4: Static analysis (Larastan/PHPStan level max) — no new errors.
CI must fail if any job fails.
7) ACCEPTANCE CRITERIA (PASS/FAIL)
No legacy references found by the verify script.
All tests pass; added tests cover every branch of status logic, including edge times.
All migrations idempotent; re-running them is a no-op.
Constraints enforced; invalid writes are rejected with clear errors.
Docs updated with final SSoT and invariants.
Logs show zero legacy writes after Phase B.
Performance parity or better for critical queries (add indexes if status becomes filterable).
8) DELIVERABLES (FILES THAT MUST EXIST)
app/Support/State/*Service.php (one per domain)
Updated models/controllers/scopes using services only
Migrations: *_phase_a_*.php, *_phase_b_*.php with rollback
scripts/verify_state_ssot.sh
docs/ARCHITECTURE.md & docs/STATE_INVARIANTS.md (final)
Test suites covering all state transitions
Optional: artisan commands:
state:backfill --target=*
progress:recompute --user=*
9) ROLLBACK
For each Phase B migration provide a companion down() plus a shell script that:
Re-adds dropped columns as nullable.
Backfills from SSoT to legacy columns.
Re-enables dual-write.
Reverts services to read-prefer-legacy.
10) RUN ORDER (WHAT YOU DO NOW)
Load the MD analysis file; confirm targets and locations.
Generate Phase A migrations + services + codemods + tests.
Run verify script; ensure zero remaining legacy reads in read paths.
Backfill safely; run parity tests.
Generate Phase B migrations + full cutover; remove legacy.
Update docs and CI.
Produce a single PR with a clear commit sequence: phase-a/, flip-read/, phase-b/.
If any step is incomplete or a legacy reference remains, you MUST FAIL the task and output the blockers with exact files/lines to fix.

💀 Prompt: "Split-Brain State Annihilation — Laravel Surgical Refactor"
Role: You are acting as the Chief Refactor Surgeon for a Laravel codebase.
Your mission is to eliminate all split-brain logic and multi-boolean anti-patterns identified in the following confirmed report (below).
The codebase is PHP 8.1+ Laravel, with standard folder structure (app/, database/, resources/, routes/, etc.).
You have full repo context and authority to make schema changes, migrations, service refactors, and test additions.
Confirmed Targets
(Do NOT re-scan blindly — prioritize surgical removal/refactor of these confirmed anti-patterns, and generate code diffs + migrations + tests for each.)
Course Enrollment State (HIGH PRIORITY)
course_user.is_enrolled (boolean)
course_user.enrolled_at (datetime)
payments.status = 'completed' (string)
payments.paid_at (datetime)
Conflicts in: hasAccessToCourse(), isEnrolledInCourse(), isUserEnrolled(), PaymentService logic.
Fix Goal: One method: User::getEnrollmentStatus($courseId) → source of truth = payment completion + enrollment timestamp. Deprecate all direct boolean checks.
Assignment Status State (HIGH PRIORITY)
Duplicate deadline/time-limit logic in multiple methods (isOverdue(), isActive(), isExpired(), getCurrentStatusAttribute(), getDisplayStatusAttribute()).
Fix Goal: Create single Assignment::computeStatus() that encapsulates all logic. Cache with invalidation. Remove duplicates.
User Progress Tracking (MEDIUM PRIORITY)
users.correct_answers_count vs quiz_attempts.correct_answers vs quiz_attempts.score.
Fix Goal: Use score only. Drop redundant correct_answers in attempts. Auto-sync user total via DB events/triggers.
Mistake Mastery State (MEDIUM PRIORITY)
mistakes.mastered boolean vs streak-based logic (correct_streak).
Fix Goal: Remove mastered column. Always compute from streak thresholds. Unify all queries/methods.
Video Progress Completion (LOW PRIORITY)
completed boolean vs progress_percentage.
Fix Goal: Remove completed. Use progress_percentage >= 100. Update logic accordingly.
Topic Locking State (LOW PRIORITY)
is_locked boolean vs locked_at / unlocked_at timestamps.
Fix Goal: Remove boolean. Determine locked state from timestamps. Update all queries/methods.
Execution Instructions
For each target above, you must:
Source of Truth Declaration: Decide the single authoritative field/method to represent the state.
Schema Refactor:
Create migrations to remove redundant columns.
Add DB-level constraints (CHECK, NOT NULL, DEFAULT) to enforce the single source of truth.
Include data backfill/update migrations for safe transition (no data loss).
Code Refactor:
Replace all legacy state checks with calls to the unified method/enum.
Deprecate old methods with clear comments and @deprecated tags until removal.
Update service, controller, policy, request, and view logic to use new unified approach.
Tests:
Add/modify Feature and Unit tests to verify new unified logic matches old behavior (minus inconsistencies).
Add negative tests to ensure conflicting states throw exceptions or fail validation.
Documentation:
Update docs/ARCHITECTURE.md with new state definitions and constraints.
Include migration/rollout instructions.
Output Format
Produce:
PR-ready patch set: migrations, model/service changes, tests, docs.
Migration plan: for each change, a BEFORE → TRANSITION → AFTER sequence.
Test run command with confirmation all tests pass.
Deprecation notice list for all removed columns/methods.
Strict Rules
No state may be represented by >1 persisted field after refactor.
No boolean may coexist with a timestamp/enum encoding the same meaning.
Enforce exclusivity at the DB level where possible.
Prevent partial rollout conflicts by making all changes backward-compatible until final migration step.
Final Deliverable: A fully refactored Laravel project, schema-migrated, tested, and documented, with all split-brain/multi-boolean logic eradicated and replaced by clean, single-source-of-truth state management.