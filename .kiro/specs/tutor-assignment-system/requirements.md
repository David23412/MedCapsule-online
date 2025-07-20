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