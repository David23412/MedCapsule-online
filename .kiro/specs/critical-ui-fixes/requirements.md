# Requirements Document

## Introduction

The course categorization system is experiencing a critical UI issue where custom course categorization pills are not displaying properly on the topics cards. Instead, debug information is showing "DEBUG: medical | ID: 7" and the custom categorization pills are not appearing. Additionally, there's a mismatch between the course ID being accessed (TUNA course ID 1) and what's being displayed (course ID 8), which is incorrectly being treated as a medical course. This issue needs to be fixed urgently to restore proper course categorization functionality.

## Requirements

### Requirement 1

**User Story:** As a student viewing a custom course, I want to see the correct categorization pills without any debug information, so that I can filter topics by their proper categories.

#### Acceptance Criteria
1. WHEN viewing a custom course THEN the system SHALL NOT display any debug information on the page
2. WHEN viewing a custom course THEN the system SHALL display the correct categorization pills based on the actual topic categories in the course
3. WHEN viewing a custom course THEN the system SHALL correctly identify the course type as 'custom' and not 'medical'
4. WHEN viewing the TUNA course (ID 1) THEN the system SHALL correctly load course ID 1 and not course ID 8
5. WHEN viewing a course THEN the system SHALL NOT show "DEBUG: medical | ID: 7" or any similar debug output
6. WHEN filtering topics by category THEN the system SHALL correctly filter based on the selected category

### Requirement 2

**User Story:** As a developer, I want to ensure the course type detection is working correctly, so that the appropriate categorization pills are displayed for each course type.

#### Acceptance Criteria
1. WHEN loading a course THEN the system SHALL correctly determine if it's a medical or custom course based on the database value
2. WHEN the course type is 'custom' THEN the system SHALL generate categorization pills based on the unique topic categories in that course
3. WHEN the course type is 'medical' THEN the system SHALL display the standard medical categorization pills (Quiz, Cases, Practical)
4. WHEN the course type is null or empty THEN the system SHALL default to treating it as a 'medical' course for backward compatibility
5. WHEN the course type changes THEN the categorization pills SHALL update accordingly without requiring a page refresh
6. WHEN debugging is needed THEN the system SHALL log information to the server logs and NOT display it on the frontend

### Requirement 3

**User Story:** As a system administrator, I want to ensure the course ID resolution is working correctly, so that users always see the correct course content.

#### Acceptance Criteria
1. WHEN accessing a course by ID THEN the system SHALL load the correct course with that ID
2. WHEN accessing the TUNA course (ID 1) THEN the system SHALL correctly load course ID 1 and not any other course
3. WHEN a course is loaded THEN the system SHALL use the correct course ID for all related queries (topics, categorizations, etc.)
4. WHEN a course ID mismatch occurs THEN the system SHALL log an error but NOT display it to the user
5. WHEN a course is loaded THEN the system SHALL ensure all course attributes (including type) are correctly loaded from the database
6. WHEN debugging course loading THEN the system SHALL use server-side logging and NOT client-side console logs or visible debug output