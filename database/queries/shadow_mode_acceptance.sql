-- ============================================================
-- Shadow Mode Acceptance Queries
-- Run these after 7 days of telemetry to evaluate the v2.0 grader
-- ============================================================

-- 1. Disagreement rate by day
--    Shows the percentage of shadow evaluations where old and new graders disagreed.
SELECT
    DATE(created_at) AS evaluation_date,
    COUNT(*) AS total_evaluations,
    SUM(CASE WHEN disagreement = 1 THEN 1 ELSE 0 END) AS disagreements,
    ROUND(
        SUM(CASE WHEN disagreement = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*),
        2
    ) AS disagreement_pct
FROM grading_diagnostics
WHERE grader_version = 'v2.0'
GROUP BY DATE(created_at)
ORDER BY evaluation_date;

-- 2. Top reasonCode on disagreements
--    Identifies the most common failure modes of the new grader.
SELECT
    reason_code,
    COUNT(*) AS occurrences,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM grading_diagnostics WHERE disagreement = 1 AND grader_version = 'v2.0'), 2) AS pct_of_disagreements
FROM grading_diagnostics
WHERE disagreement = 1
  AND grader_version = 'v2.0'
GROUP BY reason_code
ORDER BY occurrences DESC;

-- 3. Top questions with repeated disagreements
--    Flags questions the new grader consistently scores differently from the old one.
--    These may need question-level review or a grader bug fix.
SELECT
    question_id,
    COUNT(*) AS disagreement_count,
    ROUND(AVG(CASE WHEN disagreement = 1 THEN 1.0 ELSE 0.0 END) * 100, 2) AS disagreement_rate_pct
FROM grading_diagnostics
WHERE grader_version = 'v2.0'
  AND question_id IS NOT NULL
GROUP BY question_id
HAVING disagreement_count >= 3
ORDER BY disagreement_count DESC, disagreement_rate_pct DESC
LIMIT 20;