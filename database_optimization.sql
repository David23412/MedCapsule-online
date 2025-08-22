-- 🚀 ULTRA-LETHAL PERFORMANCE BEAST OPTIMIZATION SCRIPT
-- This creates the ULTIMATE database performance configuration

-- PERFORMANCE BEAST MODE: Enable MySQL optimizations
SET GLOBAL innodb_buffer_pool_size = 134217728; -- 128MB buffer pool
SET GLOBAL query_cache_type = 1;
SET GLOBAL query_cache_size = 134217728; -- 128MB query cache
SET GLOBAL max_connections = 500; -- Increase connection limit
SET GLOBAL thread_cache_size = 50;
SET GLOBAL table_open_cache = 2000;

-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Quiz attempts table indexes (most queried table)
CREATE INDEX idx_quiz_attempts_user_id ON quiz_attempts(user_id);
CREATE INDEX idx_quiz_attempts_topic_id ON quiz_attempts(topic_id);
CREATE INDEX idx_quiz_attempts_quiz_type ON quiz_attempts(quiz_type);
CREATE INDEX idx_quiz_attempts_percentage_grade ON quiz_attempts(percentage_grade);
CREATE INDEX idx_quiz_attempts_created_at ON quiz_attempts(created_at);
CREATE INDEX idx_quiz_attempts_user_created ON quiz_attempts(user_id, created_at);
CREATE INDEX idx_quiz_attempts_topic_created ON quiz_attempts(topic_id, created_at);

-- Courses table indexes
CREATE INDEX idx_courses_academic_year ON courses(academic_year);
CREATE INDEX idx_courses_price ON courses(price);
CREATE INDEX idx_courses_created_at ON courses(created_at);

-- Topics table indexes
CREATE INDEX idx_topics_course_id ON topics(course_id);
CREATE INDEX idx_topics_display_order ON topics(display_order);
CREATE INDEX idx_topics_course_order ON topics(course_id, display_order);

-- Questions table indexes
CREATE INDEX idx_questions_topic_id ON questions(topic_id);
CREATE INDEX idx_questions_correct_answer ON questions(correct_answer);

-- Payments table indexes
CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_payments_course_id ON payments(course_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_created_at ON payments(created_at);

-- Composite indexes for complex queries
CREATE INDEX idx_quiz_attempts_performance ON quiz_attempts(user_id, percentage_grade, created_at);
CREATE INDEX idx_courses_enrollment ON courses(academic_year, price, created_at);

-- Optimize existing tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE quiz_attempts;
OPTIMIZE TABLE courses;
OPTIMIZE TABLE topics;
OPTIMIZE TABLE questions;
OPTIMIZE TABLE payments;

-- Show index information
SHOW INDEX FROM quiz_attempts;
SHOW INDEX FROM users;
SHOW INDEX FROM courses;
