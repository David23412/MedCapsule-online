# Deployment Audit Summary

Current readiness score: 4/10

Why it is not deployable as-is:

- Critical security bypasses remain in production codepaths.
- Tutor analytics contain schema mismatches that can fail at runtime.
- Several hot paths still carry avoidable query and cache-scan costs.

Top blockers in order:

1. Remove test/debug/payment-bypass endpoints.
2. Restore real CSRF protection for all state-changing web routes.
3. Fix StudentProgressService to match the actual quiz_attempts schema.
4. Remove wildcard CORS/security-header exceptions on sensitive routes.
5. Trim hot-path N+1 and cache-scan behavior before scale testing.

Remediation progress since the initial audit:

- Course listing now uses bulk enrollment preview loading instead of per-course relationship hydration.
- Profile leaderboard reads are cached.
- Tutor dashboard student cards now use aggregate quiz metrics instead of eager loading recent quiz attempts for every student.
- Logged-in course listing now bypasses cache explicitly instead of relying on `TTL=0`.
- Active Redis invalidation and rate-limit stats paths now use bounded `SCAN` loops instead of `KEYS`.
- Active random quiz selection paths now sample IDs instead of using SQL random ordering on full rows.
- Default serialized `User` payload no longer appends `course_progress`, removing a hidden query from user serialization hot paths.
- The course topics page no longer embeds an unused full-topic JSON blob in HTML.
- Course preview counts now come from grouped topic queries instead of eager loading topics and questions.
- Assignment bulk-create source preloading no longer issues one source query per topic.
- Topic access checks now use one minimal topic fetch, a direct enrollment existence check, and one user-specific assignment query instead of broad relationship hydration plus duplicate assignment scans.
- Video access now loads only the topic/course fields needed for authorization and no longer writes success logs on every production hit.

Audit artifacts:

- `artifacts/deployment_audit_findings.csv`
- `artifacts/deployment_readiness_plan.csv`
