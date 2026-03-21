# Performance Task List

## Completed

- Replace per-request course enrollment preview loading with a bulk preview query in `CourseService`.
- Cache profile leaderboard and repeated profile aggregates in `ProfileController`.
- Replace tutor dashboard per-student quiz hydration with aggregate queries plus small trend samples.
- Remove hidden global serialization queries from the default serialized `User` payload.
- Replace Redis `KEYS` usage in active cache and rate-limit codepaths with bounded `SCAN` loops.
- Replace active `ORDER BY RAND()` / `inRandomOrder()` quiz selection paths with ID sampling.
- Fix logged-in course listing to bypass cache explicitly instead of relying on `TTL=0`.
- Trim course-show service work for the redirect-only route.
- Remove unused full-topic JSON serialization from the course topics page.
- Narrow active topic-page queries to only the fields and relations the view reads.
- Replace course preview API eager loading with grouped topic-count queries.
- Trim admin question form topic loading to a minimal cached topic list.
- Remove unused eager loads and production request logs from the student assignment AJAX endpoint.
- Replace assignment bulk-create source loading N+1 queries with a single distinct source query.
- Collapse topic-access permission checks into one minimal topic fetch and one user-specific assignment query.
- Trim video access to minimal topic/course columns and suppress success logs in production.

## In Progress

- Audit remaining model accessor/appends on `Course` and `Topic` and remove serialization-only overhead where safe.
- Refresh deployment audit artifacts with the fixes completed in this patch wave.

## Next

- Review dashboard and home-page queries for missing aggregate caching.
- Trim remaining media/access control endpoints that still hydrate full `Course` or `Topic` models.
- Replace any remaining non-production `KEYS` usage in console/test utilities if those tools are used operationally.
- Address existing PHP nullable deprecation warnings in `QuizService` during the next safe refactor pass.
