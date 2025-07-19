-- Rollback script for Assignment System Database Changes
-- Run this script to revert all assignment system changes

-- Drop new tables
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS skipped_questions;

-- Revert courses table changes
ALTER TABLE courses DROP COLUMN IF EXISTS year;
ALTER TABLE courses DROP COLUMN IF EXISTS cache_version;
DROP INDEX IF EXISTS idx_courses_year ON courses;
DROP INDEX IF EXISTS idx_year_price ON courses;

-- Revert topics table changes
ALTER TABLE topics DROP COLUMN IF EXISTS mastered;
ALTER TABLE topics DROP COLUMN IF EXISTS percentage_grade;
ALTER TABLE topics DROP COLUMN IF EXISTS last_attempt_date;
ALTER TABLE topics DROP COLUMN IF EXISTS is_locked;
ALTER TABLE topics DROP COLUMN IF EXISTS locked_by_tutor_id;
DROP INDEX IF EXISTS idx_topics_locked ON topics;
DROP INDEX IF EXISTS percentage_grade ON topics;
DROP INDEX IF EXISTS mastered ON topics;

-- Note: If you need to restore the removed tables (course_access_requests, question_explanations),
-- you will need to recreate them from backup or restore from version control.

-- Recreate course_access_requests table if needed
/*
CREATE TABLE course_access_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP NOT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_course (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
*/

-- Recreate question_explanations table if needed
/*
CREATE TABLE question_explanations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    detailed_explanation TEXT NOT NULL,
    key_points TEXT NULL,
    related_concepts TEXT NULL,
    answer_statistics JSON NULL,
    references TEXT NULL,
    images JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);
*/