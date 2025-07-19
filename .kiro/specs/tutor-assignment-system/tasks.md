# Implementation Plan

- [ ] 1. Database Foundation and Cleanup
  - Create database migrations for new assignment and skipped question tables
  - Add year column to courses table and locking fields to topics table
  - Remove unused tables (course_access_requests, question_explanations)
  - Create comprehensive database indexes for performance
  - Write migration rollback scripts for safety
  - _Requirements: 15.4, 16.1, 16.2, 16.4, 16.5_

- [ ] 2. Core Assignment Model and Relationships
  - Create Assignment model with proper fillable fields, casts, and relationships
  - Implement Assignment business logic methods (isOverdue, getTimeRemaining, canBeCompleted, markAsCompleted)
  - Create Assignment query scopes (active, overdue, forTutor, forStudent)
  - Write comprehensive unit tests for Assignment model
  - _Requirements: 1.1, 1.2, 1.5, 11.1_

- [ ] 3. Skipped Question Model and Functionality
  - Create SkippedQuestion model with relationships to User and Question
  - Implement static methods for marking questions as skipped and checking skip status
  - Create database constraints to prevent duplicate skips
  - Write unit tests for SkippedQuestion model functionality
  - _Requirements: 10.1, 10.2, 10.3_

- [ ] 4. Assignment Service Layer
  - Create AssignmentService with methods for creating, updating, and managing assignments
  - Implement bulk assignment creation functionality for multiple students
  - Add assignment deadline management and automatic overdue locking
  - Create assignment reporting and analytics methods
  - Write comprehensive unit tests for AssignmentService
  - _Requirements: 1.1, 1.2, 1.6, 9.1, 9.2_

- [ ] 5. Topic Locking Service
  - Create TopicLockingService for managing topic access control
  - Implement lock/unlock functionality with tutor authorization
  - Add methods to check topic lock status for students
  - Create bulk topic locking capabilities
  - Write unit tests for TopicLockingService
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_