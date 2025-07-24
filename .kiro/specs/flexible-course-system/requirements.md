# Requirements Document

## Introduction

The current course system is too rigid for different educational contexts. Medical courses use "Quiz", "Cases", and "Practical" categorizations, but tutors creating custom courses need different categorizations. Additionally, the assignment system has usability issues where students can bypass the intended assignment flow by accessing source selection modals, and assignment timers aren't properly integrated into the topic interface. This feature redesigns the course and assignment system to be flexible, intuitive, and properly integrated.

## Requirements

### Requirement 1

**User Story:** As a student or tutor viewing course topics, I want to filter topics by categorization using clickable pills, so that I can focus on specific types of content.

#### Acceptance Criteria

1. WHEN viewing a course topics page THEN the system SHALL display clickable categorization pills for filtering
2. WHEN a categorization pill is clicked THEN the system SHALL show only topics belonging to that category using client-side JavaScript filtering
3. WHEN "All" or no pill is selected THEN the system SHALL display all topics in the course
4. WHEN a pill is active THEN it SHALL have distinct visual styling to indicate the current filter
5. WHEN topics are filtered THEN the URL SHALL update to reflect the current filter state for bookmarking
6. WHEN the page loads with a filter parameter THEN the system SHALL automatically apply that filter and highlight the corresponding pill
7. IF a category has no topics THEN its pill SHALL not be displayed in the interface

### Requirement 2

**User Story:** As a system administrator, I want to flag courses as different types (medical vs custom) so that the interface can adapt appropriately to each course's needs.

#### Acceptance Criteria

1. WHEN creating a course THEN the system SHALL allow setting a course type field (medical or custom) in the database
2. WHEN the frontend loads a course page THEN the system SHALL pass the course type to the frontend via the course data or API response
3. WHEN a course is flagged as "medical" THEN the frontend SHALL check for topics in predefined medical categorizations ("Quiz", "Cases", "Practical") and only show pills for categories that actually have topics
4. WHEN a medical course has no topics in a specific category THEN that category's pill SHALL not be displayed (e.g., no "Quiz" topics = no "Quiz" pill, no "Cases" topics = no "Cases" pill, no "Practical" topics = no "Practical" pill)
5. WHEN a course is flagged as "custom" THEN the frontend SHALL dynamically generate filtering pills based on what topic categorizations actually exist in the database for that course
6. WHEN a custom course has topics with categorizations like "Homework", "Lab", "Reading" THEN those pills SHALL be displayed dynamically
7. WHEN a custom course has no topic categorizations THEN the system SHALL show all topics without any filtering pills
8. WHEN the course type is available in JavaScript THEN the frontend logic SHALL use it to determine which categorization approach to use
9. IF course type is undefined THEN the system SHALL default to medical course behavior for backward compatibility

### Requirement 3

**User Story:** As a student working on an assignment, I want to go directly into the assignment without seeing source selection options, so that I follow the intended assignment flow without distractions.

#### Acceptance Criteria

1. WHEN a student clicks on an assigned topic THEN the system SHALL bypass the source selection modal completely
2. WHEN starting a "Source-Specific" assignment THEN the system SHALL automatically use the source specified by the tutor
3. WHEN starting a "Complete Topic" assignment THEN the system SHALL present all topic questions in a single quiz session without source categorization
4. WHEN an assignment topic is clicked THEN the system SHALL immediately redirect to the quiz interface based on the assignment mode
5. WHEN starting an assignment quiz THEN the countdown timer SHALL start automatically if a time limit was set
6. WHEN a topic is both assigned and available for free practice THEN the system SHALL provide separate buttons/links for "Start Assignment" vs "Practice Mode"
7. WHEN in assignment mode THEN the system SHALL prevent access to source selection or switching sources
8. WHEN assignment mode is active THEN the interface SHALL clearly indicate this is an assignment with any time constraints
9. IF "Complete Topic" mode is used THEN the system SHALL ensure students can complete the entire topic within the assignment timeframe

### Requirement 4

**User Story:** As a student with an active assignment, I want to see the assignment timer countdown prominently displayed on the topic card and during quiz solving, so that I can manage my time effectively.

#### Acceptance Criteria

1. WHEN a tutor creates an assignment with a time limit THEN the system SHALL store the time limit duration with the assignment
2. WHEN a student first accesses an assigned topic THEN the countdown timer SHALL start automatically based on the tutor-specified time limit
3. WHEN viewing a topic with an active assignment THEN the topic card SHALL display a prominent countdown timer showing remaining time
4. WHEN the assignment timer is running THEN the countdown SHALL update in real-time without page refresh
5. WHEN solving an assigned topic THEN the timer SHALL remain visible throughout the quiz session
6. WHEN assignment time expires THEN the system SHALL automatically submit the current progress and lock further access
7. WHEN an assignment timer shows less than 10% remaining time THEN the system SHALL use urgent visual styling
8. IF the assignment has no time limit THEN the system SHALL show deadline information instead of a countdown

### Requirement 5

**User Story:** As a tutor creating assignments, I want to choose between source-specific assignments or comprehensive topic assignments, so that I can control whether students work on specific materials or complete entire topics.

#### Acceptance Criteria

1. WHEN creating an assignment THEN the system SHALL provide fields for setting time limit duration (hours/minutes) and deadline date
2. WHEN creating an assignment THEN the system SHALL offer two assignment modes: "Source-Specific" or "Complete Topic"
3. WHEN "Source-Specific" mode is selected THEN the system SHALL allow selecting specific sources from the available topic sources
4. WHEN "Complete Topic" mode is selected THEN the assignment SHALL include all questions from all sources in that topic, bypassing source selection entirely
5. WHEN a time limit is set THEN the system SHALL store it with the assignment and use it to start countdown timers when students access the topic
6. WHEN "Complete Topic" mode is used THEN students SHALL see all topic questions in a single quiz session without source categorization
7. WHEN "Source-Specific" mode is used THEN students SHALL only have access to the specified sources during assignment completion
8. WHEN a tutor updates assignment settings THEN active assignments SHALL reflect the changes immediately
9. IF a specified source becomes unavailable THEN the system SHALL notify the tutor and provide fallback options

### Requirement 6

**User Story:** As a student, I want clear visual distinction between assignment topics and regular practice topics, so that I can easily identify my required work versus optional practice.

#### Acceptance Criteria

1. WHEN viewing the topics page THEN assignment topics SHALL have distinct visual styling from regular topics
2. WHEN a topic has both assignment and practice modes available THEN the system SHALL show both options clearly
3. WHEN an assignment is overdue THEN the topic card SHALL display urgent visual indicators
4. WHEN an assignment is completed THEN the topic card SHALL show completion status and score
5. WHEN assignment deadline approaches THEN the visual urgency SHALL increase progressively
6. IF a topic is locked by tutor THEN the system SHALL show lock status clearly distinct from assignment status

### Requirement 7

**User Story:** As a tutor, I want to see completion status that accurately reflects whether students completed the full assignment requirements, and I want students to retain access to complete topics even after assignment deadlines, so that learning can continue.

#### Acceptance Criteria

1. WHEN a student completes an assignment THEN the system SHALL only mark the assignment as complete if all required sources were attempted
2. WHEN an assignment deadline passes THEN the assignment SHALL be locked but the topic SHALL remain accessible for completion in practice mode
3. WHEN viewing assignment reports THEN the system SHALL show detailed completion status for each required source
4. WHEN a student partially completes an assignment THEN the system SHALL show partial completion percentage
5. WHEN assignment requirements change THEN existing completion status SHALL be recalculated accordingly
6. WHEN generating reports THEN the system SHALL distinguish between assignment completion and overall topic mastery
7. WHEN a source-specific assignment expires THEN students SHALL still be able to access non-assigned sources to complete the topic
8. WHEN calculating assignment scores THEN the system SHALL use only the assigned source questions (correct answers / total assigned source questions) not the entire topic question count
9. WHEN recording assignment attempts THEN the system SHALL store it as a single quiz attempt with the score from only the assigned source
10. IF a student attempts non-assignment sources THEN those SHALL NOT count toward assignment completion but SHALL count toward topic completion

### Requirement 8

**User Story:** As a course creator, I want to define default source priorities for my course topics, so that the most important learning materials are emphasized consistently.

#### Acceptance Criteria

1. WHEN creating course topics THEN the system SHALL allow setting default source priority order
2. WHEN students access topics THEN sources SHALL be presented in the defined priority order
3. WHEN creating assignments THEN the system SHALL suggest sources based on the defined priorities
4. WHEN no priority is set THEN the system SHALL use alphabetical ordering as fallback
5. WHEN source priorities are updated THEN the changes SHALL apply to new topic attempts immediately
6. IF a high-priority source becomes unavailable THEN the system SHALL automatically promote the next priority source

### Requirement 9

**User Story:** As a student, I want assignment mode to have a streamlined interface focused on completing the required work, so that I can concentrate on learning without interface distractions.

#### Acceptance Criteria

1. WHEN in assignment mode THEN the interface SHALL hide non-essential navigation and options
2. WHEN solving assignment questions THEN the system SHALL focus on the core quiz functionality
3. WHEN assignment mode is active THEN the system SHALL disable features like source switching or topic browsing
4. WHEN assignment is completed THEN the system SHALL provide clear completion confirmation and next steps
5. WHEN assignment time is running out THEN the interface SHALL prioritize time awareness over other features
6. IF technical issues occur during assignment THEN the system SHALL provide emergency contact options

### Requirement 10

**User Story:** As a tutor creating custom courses, I want the flexibility to use topic categorizations or not use them at all, so that I can structure my course content in the way that makes most sense for my teaching.

#### Acceptance Criteria

1. WHEN a custom course has topics with categorizations THEN the system SHALL display categorization pills dynamically based on what exists
2. WHEN a custom course has topics without any categorizations THEN the system SHALL display all topics in a flat list without pills
3. WHEN a custom course has mixed categorized and uncategorized topics THEN uncategorized topics SHALL appear in a default section
4. WHEN viewing a custom course THEN the interface SHALL adapt to show the most appropriate layout for the content structure
5. WHEN topics are added or categorizations change THEN the pill display SHALL update automatically
6. IF a tutor prefers no categorization system THEN they can simply not assign categories to topics

### Requirement 11

**User Story:** As a frontend developer, I need access to course type and categorization data so that I can render the appropriate interface for each course.

#### Acceptance Criteria

1. WHEN a course page loads THEN the backend SHALL include course type in the data passed to the Blade template
2. WHEN topics are loaded THEN the system SHALL include each topic's categorization field in the data
3. WHEN the frontend needs to build categorization pills THEN it SHALL have access to both course type and all unique categorizations used in that course
4. WHEN using JavaScript for filtering THEN the course type SHALL be available as a JavaScript variable or data attribute
5. WHEN an API endpoint provides course data THEN it SHALL include the course type field in the response
6. IF the course type is missing from the database THEN the system SHALL treat it as "medical" type for backward compatibility

### Requirement 12

**User Story:** As a student, I want to continue working on topic completion even after assignment deadlines pass, so that I can achieve full mastery of the material.

#### Acceptance Criteria

1. WHEN an assignment deadline passes THEN the assignment mode SHALL be locked but practice mode SHALL remain available
2. WHEN accessing a topic after assignment expiry THEN the system SHALL clearly indicate "Assignment Expired - Practice Mode"
3. WHEN in post-assignment practice mode THEN students SHALL have access to all sources including those not part of the original assignment
4. WHEN a student completes remaining sources after assignment expiry THEN the topic completion status SHALL update accordingly
5. WHEN viewing topic progress THEN the system SHALL show both assignment completion status and overall topic completion status separately
6. WHEN tutors view reports THEN they SHALL see both assignment performance and post-assignment topic completion
7. IF a topic was never assigned THEN it SHALL function normally with full source access

### Requirement 13

**User Story:** As a tutor, I want to lock topics until I decide to unlock them for students, so that I can control the learning sequence and ensure prerequisites are met.

#### Acceptance Criteria

1. WHEN a tutor views topic management THEN the system SHALL show lock/unlock controls for each topic
2. WHEN a topic is locked THEN students SHALL NOT be able to access or attempt quizzes for that topic
3. WHEN a tutor unlocks a topic THEN assigned students SHALL immediately gain access
4. WHEN a topic is locked THEN the system SHALL display a clear locked indicator to students
5. WHEN a tutor locks a previously accessible topic THEN current student progress SHALL be preserved but further attempts blocked
6. WHEN viewing locked topics THEN students SHALL see the lock reason if provided by the tutor
7. WHEN topics are locked THEN they SHALL not appear in categorization pill counts or filtering

### Requirement 14

**User Story:** As a user, I want the system to be lightning-fast, responsive, and completely reliable, so that I can focus on learning without any technical distractions.

#### Acceptance Criteria

1. WHEN loading course topics THEN the page SHALL render within 200ms with proper loading states
2. WHEN filtering topics by categorization THEN the filtering SHALL be instant with smooth animations
3. WHEN starting an assignment THEN the transition to quiz mode SHALL be seamless without page refresh delays
4. WHEN countdown timers are running THEN they SHALL update smoothly without causing UI lag or flicker
5. WHEN switching between assignment and practice modes THEN the interface SHALL respond immediately
6. WHEN accessing topics after assignment expiry THEN the mode switch SHALL be instant and clear
7. WHEN categorization pills are generated dynamically THEN they SHALL appear without layout shifts or loading delays
8. WHEN assignment scores are calculated THEN the results SHALL display immediately after quiz completion
9. WHEN topics are locked/unlocked THEN the UI changes SHALL be instant across all student interfaces
10. WHEN handling large courses with 100+ topics THEN performance SHALL remain consistent and responsive

### Requirement 15

**User Story:** As a system administrator, I want the course and assignment system to be completely secure and stable, so that student data and academic integrity are protected.

#### Acceptance Criteria

1. WHEN students access assignments THEN the system SHALL validate permissions at every step to prevent unauthorized access
2. WHEN assignment timers expire THEN the system SHALL automatically and securely submit progress without data loss
3. WHEN source restrictions are applied THEN the system SHALL enforce them at the API level, not just frontend
4. WHEN assignment scores are calculated THEN the system SHALL prevent tampering and ensure accurate recording
5. WHEN topics are locked THEN the system SHALL block all access attempts including direct URL access
6. WHEN course types are changed THEN the system SHALL validate data integrity and prevent corruption
7. WHEN handling concurrent users THEN the system SHALL maintain data consistency without race conditions
8. WHEN processing assignment submissions THEN the system SHALL use database transactions to prevent partial saves
9. WHEN categorization data is cached THEN the system SHALL invalidate caches immediately when changes occur
10. WHEN errors occur THEN the system SHALL fail gracefully with clear user feedback and automatic recovery
11. WHEN database schema changes are needed THEN the system SHALL modify existing table structures instead of creating additional tables
12. WHEN optimizing database performance THEN the system SHALL eliminate all redundant data and ensure maximum efficiency
13. WHEN adding new fields THEN they SHALL be added to existing tables with proper indexing for optimal query performance
14. WHEN database migrations are created THEN they SHALL be designed for zero-downtime deployment and rollback capability

### Requirement 16

**User Story:** As a user, I want the interface to be intuitive and error-free, so that I never get confused or stuck during my learning experience.

#### Acceptance Criteria

1. WHEN viewing assignment topics THEN the visual distinction SHALL be immediately obvious without reading text
2. WHEN assignment deadlines approach THEN the urgency indicators SHALL be progressive and non-intrusive
3. WHEN switching between course types THEN the interface changes SHALL be smooth and contextually appropriate
4. WHEN categorization pills have no topics THEN they SHALL never appear to avoid empty states
5. WHEN assignment mode is active THEN all non-essential UI elements SHALL be hidden to reduce cognitive load
6. WHEN timers are counting down THEN they SHALL use clear, readable formatting with appropriate color coding
7. WHEN errors occur THEN the system SHALL provide specific, actionable error messages with recovery options
8. WHEN loading states are needed THEN they SHALL use skeleton screens and progress indicators, never blank screens
9. WHEN assignments expire THEN the transition message SHALL be clear and guide users to next steps
10. WHEN topics are completed THEN the success feedback SHALL be immediate and satisfying

### Requirement 17

**User Story:** As a system, I need to maintain backward compatibility with existing medical courses while supporting new flexible course structures, so that current users are not disrupted.

#### Acceptance Criteria

1. WHEN existing medical courses are accessed THEN they SHALL continue functioning with current categorizations
2. WHEN legacy data is encountered THEN the system SHALL handle it gracefully without errors
3. WHEN migrating to flexible system THEN existing course data SHALL be preserved and mapped appropriately
4. WHEN new features are added THEN they SHALL not break existing course functionality
5. WHEN system updates occur THEN medical course users SHALL experience no disruption
6. IF conflicts arise between old and new systems THEN the system SHALL prioritize data integrity and user experience