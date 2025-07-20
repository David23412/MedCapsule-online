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

- [ ] 6. Learn Mistakes Enhancement Service
  - Create LearnMistakesService for managing skipped questions
  - Implement skip question functionality during learn mistakes sessions
  - Add methods to retrieve skipped questions for review display
  - Integrate with existing mistakes system without breaking current functionality
  - Write unit tests for LearnMistakesService
  - _Requirements: 10.1, 10.2, 10.4, 10.5_

- [ ] 7. Assignment Controller Implementation
  - Create AssignmentController with full CRUD operations
  - Implement assignment creation form and validation
  - Add bulk assignment creation endpoint
  - Create assignment reports and analytics endpoints
  - Implement proper authorization using TutorStudentRelationship
  - Write controller tests with request validation
  - _Requirements: 1.1, 1.3, 9.1, 9.3, 11.1, 11.2_

- [ ] 8. Student Assignment Controller
  - Create StudentAssignmentController for student-facing assignment operations
  - Implement assignment listing and detail views for students
  - Add assignment completion functionality
  - Ensure proper authorization for student access
  - Write controller tests for student assignment workflows
  - _Requirements: 6.1, 6.2, 6.6, 11.3_

- [ ] 9. Enhanced Tutor Dashboard Backend
  - Update TutorDashboardController to include assignment counts and search functionality
  - Remove email display from student cards and class summary section
  - Implement efficient student search with username filtering
  - Add real-time assignment counter data endpoints
  - Optimize dashboard queries for performance
  - Write tests for enhanced dashboard functionality
  - _Requirements: 2.1, 2.2, 12.1, 12.2, 12.3_

- [ ] 10. Assignment Notification System
  - Extend existing NotificationService to handle assignment notifications
  - Create assignment creation, deadline change, and reminder notifications
  - Implement notification templates for assignment-related events
  - Add notification preferences for assignment updates
  - Write tests for assignment notification functionality
  - _Requirements: 1.3, 3.2, 7.1, 7.2, 7.3, 7.4_

- [ ] 11. Frontend Assignment Counter Component
  - Create JavaScript AssignmentCounter class for real-time updates
  - Implement eye-catching counter styling with attention-grabbing colors
  - Add automatic refresh functionality every 30 seconds
  - Handle zero state vs active assignments display
  - Write frontend tests for counter functionality
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 12. Assignment Countdown Timer Component
  - Create JavaScript AssignmentCountdown class for deadline tracking
  - Implement real-time countdown display with urgency styling
  - Add automatic assignment locking when deadline passes
  - Create visual indicators for approaching deadlines
  - Write frontend tests for countdown functionality
  - _Requirements: 1.1, 1.5, 6.2, 6.3, 6.4_

- [ ] 13. Assignment Categorization in Topics View
  - Modify topics display to include conditional "Assignment" categorization
  - Implement logic to show Assignment pill only when assignments exist for the course
  - Group assignments under dedicated category with countdown timers
  - Ensure assignments remain visible after completion for reference
  - Write tests for assignment categorization display
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 14. Learn Mistakes Skip Button Integration
  - Add skip button to learn mistakes question interface
  - Implement skip functionality that removes question from current session
  - Update question cycling logic to exclude skipped questions
  - Ensure skip action is properly recorded in database
  - Write tests for skip button functionality
  - _Requirements: 10.1, 10.2_

- [ ] 15. Mistakes Review Skip Indicators
  - Update mistakes.blade.php to display baby blue dot for skipped questions
  - Add "skipped" text next to baby blue dot indicator
  - Implement proper styling for skipped question indicators
  - Ensure skipped questions are clearly distinguishable from other states
  - Write tests for skip indicator display
  - _Requirements: 10.4, 10.5_

- [ ] 16. Dashboard Navigation and UI Improvements
  - Remove "Tutor Dashboard" and "Profile" options from dashboard dropdown
  - Implement back arrow button navigation like other tabs
  - Clean up student card display by removing email addresses
  - Remove class summary section from dashboard
  - Write tests for navigation improvements
  - _Requirements: 12.3, 12.4, 12.5_

- [ ] 17. Study Streak Calculation Fix
  - Review and fix study streak calculation logic in User model
  - Ensure proper timezone handling and date validation
  - Update profile.blade.php to display accurate streak information
  - Test streak calculation with various scenarios (consecutive days, missed days, timezone changes)
  - Write comprehensive tests for study streak functionality
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ] 18. Page Title Standardization
  - Update all blade templates to use "MedCapsule" as page title
  - Ensure consistent title tag implementation across all routes
  - Remove dynamic or route-specific titles
  - Verify title consistency in all application pages
  - Write tests to validate page title standardization
  - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_

- [ ] 19. Course Year Management
  - Update Course model to include year field with validation
  - Modify course creation and editing forms to include year selection
  - Add year filtering capabilities to course listings
  - Update existing courses with appropriate year values
  - Write tests for course year functionality
  - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [ ] 20. Assignment Management Interface
  - Create assignment creation and management forms for tutors
  - Implement assignment listing with filtering and search capabilities
  - Add assignment editing and deadline modification interfaces
  - Create bulk assignment creation interface
  - Implement assignment deletion with proper confirmations
  - Write integration tests for assignment management interface
  - _Requirements: 1.1, 3.1, 3.3, 9.1, 9.2_

- [ ] 21. Student Assignment Interface
  - Create student assignment dashboard showing active assignments
  - Implement assignment detail view with countdown timers
  - Add assignment completion interface
  - Create overdue assignment indicators and messaging
  - Implement assignment history view for completed assignments
  - Write integration tests for student assignment interface
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6_

- [ ] 22. Topic Locking Interface
  - Create topic locking management interface for tutors
  - Add lock/unlock controls to topic management pages
  - Implement bulk topic locking functionality
  - Create locked topic indicators for students
  - Add proper messaging when students encounter locked topics
  - Write integration tests for topic locking interface
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 23. Assignment Reporting Dashboard
  - Create comprehensive assignment reports for tutors
  - Implement filtering by student, topic, date range, and completion status
  - Add assignment completion statistics and analytics
  - Create overdue assignment highlighting and warnings
  - Implement export functionality for assignment reports
  - Write tests for assignment reporting functionality
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 24. Performance Optimization and Caching
  - Implement caching for assignment counts and student lists
  - Add database query optimization for assignment-related operations
  - Create efficient indexes for assignment and skip question tables
  - Optimize dashboard queries with proper eager loading
  - Implement cache invalidation strategies for assignment updates
  - Write performance tests to validate optimization improvements
  - _Requirements: 17.1, 17.2, 17.3_

- [ ] 25. Comprehensive Testing Suite
  - Create feature tests covering complete assignment workflow
  - Implement integration tests for tutor-student assignment interactions
  - Add regression tests to ensure existing functionality remains intact
  - Create performance tests for bulk operations and dashboard loading
  - Implement security tests for authorization and data validation
  - Run full test suite to ensure zero regression policy compliance
  - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

- [ ] 26. Final Integration and System Testing
  - Perform end-to-end testing of complete assignment system
  - Test all user roles (admin, tutor, student) with assignment functionality
  - Verify proper integration with existing course and topic systems
  - Test notification system with assignment events
  - Validate performance under load with multiple assignments
  - Conduct final security audit and penetration testing
  - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_
    # Requirements Document

## Introduction

This feature enables tutors to create homework-like assignments for their students with deadlines and countdown timers. Assignments appear as a unique categorization in the topics page (similar to "Practical", "Quiz", "Cases" but distinct as homework). The system provides tutors with enhanced oversight capabilities through assignment management, deadline control, and topic locking mechanisms. Students receive clear assignment notifications with visual indicators and deadline tracking. Additionally, the system includes a "skipped" feature for learn mistakes to help students manage difficult written questions.

## Requirements

### Requirement 1

**User Story:** As a tutor, I want to create homework assignments for my students with deadlines and countdown timers, so that I can assign structured learning tasks with time management.

#### Acceptance Criteria

1. WHEN a tutor creates an assignment THEN the system SHALL allow setting a deadline and duration countdown
2. WHEN a tutor creates an assignment THEN the system SHALL store the assignment with student, topic, deadline, duration, and status information
3. WHEN a tutor creates an assignment THEN the student SHALL receive a notification about the new assignment
4. WHEN an assignment is created THEN the system SHALL validate that the deadline is in the future
5. WHEN an assignment deadline passes THEN the assignment SHALL become locked and inaccessible to the student
6. IF a tutor tries to assign a topic already assigned to the same student THEN the system SHALL prevent duplicate assignments or allow updating the existing assignment

### Requirement 2

**User Story:** As a tutor, I want to see a visual counter of new assignments in my dashboard, so that I can quickly identify when students have new work to complete.

#### Acceptance Criteria

1. WHEN a tutor views their dashboard THEN the system SHALL display an eye-catching counter showing the number of active assignments
2. WHEN new assignments are created THEN the counter SHALL update in real-time without page refresh
3. WHEN assignments are completed or expire THEN the counter SHALL automatically decrease
4. WHEN the counter shows zero assignments THEN it SHALL display in a neutral state
5. WHEN the counter shows pending assignments THEN it SHALL use attention-grabbing colors and styling

### Requirement 3

**User Story:** As a tutor, I want to control assignment deadlines and modify them when necessary, so that I can adapt to changing circumstances and student needs.

#### Acceptance Criteria

1. WHEN a tutor views an existing assignment THEN the system SHALL allow editing the deadline
2. WHEN a tutor changes an assignment deadline THEN the student SHALL receive a notification about the change
3. WHEN a deadline is modified THEN the system SHALL validate the new deadline is appropriate
4. WHEN a tutor extends a deadline THEN the assignment status SHALL remain active
5. IF a tutor shortens a deadline to a past date THEN the system SHALL warn about immediate expiration

### Requirement 4

**User Story:** As a tutor, I want to lock topics until I decide to unlock them for students, so that I can control the learning sequence and ensure prerequisites are met.

#### Acceptance Criteria

1. WHEN a tutor views topic management THEN the system SHALL show lock/unlock controls for each topic
2. WHEN a topic is locked THEN students SHALL NOT be able to access or attempt quizzes for that topic
3. WHEN a tutor unlocks a topic THEN assigned students SHALL immediately gain access
4. WHEN a topic is locked THEN the system SHALL display a clear locked indicator to students
5. WHEN a tutor locks a previously accessible topic THEN current student progress SHALL be preserved but further attempts blocked

### Requirement 5

**User Story:** As a tutor, I want comprehensive assignment reports in my dashboard, so that I can monitor student progress and identify students who need additional support.

#### Acceptance Criteria

1. WHEN a tutor accesses assignment reports THEN the system SHALL display completion status for all assignments
2. WHEN viewing reports THEN the system SHALL show assignment creation date, deadline, and completion date
3. WHEN a student completes an assignment THEN the report SHALL update to reflect completion status and score
4. WHEN assignments are overdue THEN the system SHALL highlight them with warning indicators
5. WHEN generating reports THEN the system SHALL allow filtering by student, topic, date range, and completion status

### Requirement 6

**User Story:** As a student, I want to see my assigned topics with clear deadlines and visual indicators, so that I can prioritize my study time effectively.

#### Acceptance Criteria

1. WHEN a student logs in THEN the system SHALL display all active assignments prominently
2. WHEN viewing assignments THEN the system SHALL show topic name, deadline, and time remaining
3. WHEN an assignment deadline approaches THEN the system SHALL use visual cues to indicate urgency
4. WHEN an assignment is overdue THEN the system SHALL clearly mark it as overdue
5. WHEN a student completes an assigned topic THEN the assignment status SHALL update to completed

### Requirement 7

**User Story:** As a student, I want to receive notifications about new assignments and deadline changes, so that I stay informed about my learning requirements.

#### Acceptance Criteria

1. WHEN a tutor assigns a topic THEN the student SHALL receive an immediate notification
2. WHEN an assignment deadline changes THEN the student SHALL receive a notification with the new deadline
3. WHEN an assignment deadline approaches THEN the student SHALL receive reminder notifications
4. WHEN notifications are sent THEN they SHALL include topic name, deadline, and direct access links
5. IF a student has multiple overdue assignments THEN the system SHALL send consolidated reminder notifications

### Requirement 8

**User Story:** As a student, I want to see assignments grouped under a dedicated "Assignment" categorization in the topics page, so that I can easily identify and access my homework tasks.

#### Acceptance Criteria

1. WHEN a student views the topics page THEN assignments SHALL appear under a unique "Assignment" categorization pill
2. WHEN a course has no assignments THEN the "Assignment" categorization SHALL NOT be displayed
3. WHEN a tutor has not created assignments for a specific course THEN the "Assignment" categorization SHALL NOT appear for that course
4. WHEN assignments exist THEN they SHALL be grouped together and remain visible even after completion for reference
5. WHEN viewing the "Assignment" category THEN students SHALL see countdown timers and deadline information for each assignment

### Requirement 9

**User Story:** As a tutor, I want to bulk assign topics to multiple students, so that I can efficiently manage classroom assignments.

#### Acceptance Criteria

1. WHEN a tutor selects multiple students THEN the system SHALL allow assigning the same topic to all selected students
2. WHEN bulk assigning THEN the system SHALL allow setting the same deadline for all assignments
3. WHEN bulk assignments are created THEN each student SHALL receive individual notifications
4. WHEN bulk assigning THEN the system SHALL validate that all selected students are in the tutor's student list
5. IF some students already have the topic assigned THEN the system SHALL provide options to skip or update existing assignments

### Requirement 10

**User Story:** As a student, I want to skip difficult written questions in learn mistakes, so that I can continue my learning session without getting stuck on time-consuming questions.

#### Acceptance Criteria

1. WHEN a student is in a learn mistakes session THEN the system SHALL provide a "Skip" button for each question
2. WHEN a student skips a question THEN the question SHALL be removed from the current session
3. WHEN a question is skipped THEN it SHALL be marked as "skipped" in the system
4. WHEN a student views review mistakes (mistakes.blade.php) THEN skipped questions SHALL display with a baby blue dot indicator
5. WHEN viewing skipped questions in mistakes.blade.php THEN the text "skipped" SHALL appear next to the baby blue dot

### Requirement 11

**User Story:** As a system, I need to track tutor-student relationships using the existing TutorStudentRelationship model, so that assignments are properly scoped to authorized tutor-student pairs.

#### Acceptance Criteria

1. WHEN a tutor creates an assignment THEN the system SHALL verify the tutor-student relationship exists in TutorStudentRelationship table
2. WHEN displaying assignments THEN the system SHALL only show assignments for students assigned to the current tutor
3. WHEN a student views assignments THEN the system SHALL only show assignments from their assigned tutors
4. WHEN validating assignment access THEN the system SHALL use TutorStudentRelationship.hasAccess() method
5. IF a tutor-student relationship is removed THEN existing assignments SHALL remain but no new assignments can be created

### Requirement 12

**User Story:** As a tutor, I want an improved dashboard with clean user search and streamlined navigation, so that I can efficiently manage my students without clutter.

#### Acceptance Criteria

1. WHEN a tutor views the dashboard THEN the system SHALL provide easy search functionality for student usernames
2. WHEN displaying student cards THEN the system SHALL NOT show email addresses for a cleaner look
3. WHEN viewing the dashboard THEN the system SHALL NOT display the class summary section
4. WHEN a tutor is in the dashboard THEN the dropdown SHALL NOT contain "Tutor Dashboard" or "Profile" options
5. WHEN navigating the dashboard THEN the system SHALL provide a "back" arrow button like other tabs

### Requirement 13

**User Story:** As a user, I want the study streak mechanism to work properly and display correctly in my profile, so that I can track my learning consistency accurately.

#### Acceptance Criteria

1. WHEN a user views their profile THEN the study streak SHALL be calculated correctly based on consecutive study days
2. WHEN a user studies on consecutive days THEN the streak counter SHALL increment properly
3. WHEN a user misses a day THEN the streak SHALL reset to zero or one depending on current day activity
4. WHEN displaying the streak THEN the system SHALL show accurate streak information in profile.blade.php
5. WHEN calculating streaks THEN the system SHALL use proper timezone handling and date validation

### Requirement 14

**User Story:** As a user, I want all page titles to consistently show "MedCapsule" for brand consistency, so that the application maintains professional branding throughout.

#### Acceptance Criteria

1. WHEN any page loads THEN the browser title SHALL display "MedCapsule"
2. WHEN navigating between pages THEN all page titles SHALL consistently show "MedCapsule"
3. WHEN viewing any blade template THEN the title tag SHALL contain "MedCapsule"
4. WHEN accessing any route THEN the page title SHALL NOT show route-specific or dynamic titles
5. WHEN the application loads THEN all HTML title elements SHALL be standardized to "MedCapsule"

### Requirement 15

**User Story:** As a system administrator, I want courses to have specific years associated with them, so that content can be organized by academic year and remain relevant.

#### Acceptance Criteria

1. WHEN creating a course THEN the system SHALL require a year field
2. WHEN displaying courses THEN the system SHALL show the associated year
3. WHEN filtering courses THEN users SHALL be able to filter by year
4. WHEN migrating existing courses THEN the system SHALL add a year column to the courses table
5. WHEN validating course data THEN the system SHALL ensure the year is a valid four-digit year

### Requirement 16

**User Story:** As a system administrator, I want a clean database schema without unnecessary tables, so that the system maintains optimal performance and clarity.

#### Acceptance Criteria

1. WHEN reviewing the database THEN the system SHALL NOT contain a course_access_requests table if it's unused
2. WHEN reviewing the database THEN the system SHALL NOT contain a question_explanations table if it's unused
3. WHEN cleaning the database THEN all unused or redundant tables SHALL be removed
4. WHEN creating migrations THEN the system SHALL use single CREATE TABLE statements instead of separate ADD/MODIFY migrations
5. WHEN updating table structures THEN changes SHALL be made in the original CREATE TABLE migration and database refreshed

### Requirement 17

**User Story:** As a system, I need bulletproof implementation with comprehensive testing, so that all features are robust, stable, and regression-free.

#### Acceptance Criteria

1. WHEN implementing any feature THEN the system SHALL include comprehensive unit tests
2. WHEN adding functionality THEN the system SHALL include integration tests
3. WHEN deploying features THEN the system SHALL pass all existing tests without regression
4. WHEN testing features THEN the system SHALL include edge case testing and error handling
5. WHEN implementing code THEN the system SHALL follow best practices and maintain zero regression policy
