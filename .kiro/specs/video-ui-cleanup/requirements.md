# Requirements Document

## Introduction

This feature involves cleaning up the topics_for_course.blade.php file by removing all existing video UI components (modals, overlays, YouTube integrations) and replacing them with a simple play button that links to the dedicated video.blade.php page. The play button implementation will be copied exactly from new_topics.blade.php, including its positioning, animation behavior, and lock logic.

## Requirements

### Requirement 1

**User Story:** As a developer, I want to remove all video modal/overlay UI from topics_for_course.blade.php, so that the page is cleaner and uses the dedicated video page instead.

#### Acceptance Criteria

1. WHEN examining topics_for_course.blade.php THEN all modal markup with classes like "modal", "video-modal" SHALL be removed
2. WHEN examining topics_for_course.blade.php THEN all modal triggers with attributes like "data-bs-toggle='modal'", "data-target" SHALL be removed
3. WHEN examining topics_for_course.blade.php THEN all YouTube/iframe blocks, x-secure-video-overlay, secure-player elements SHALL be removed
4. WHEN examining topics_for_course.blade.php THEN all JavaScript initializers for modal/overlay/YouTube functionality SHALL be removed
5. WHEN examining topics_for_course.blade.php THEN all CSS tied to video modals, overlays, and secure video components SHALL be removed

### Requirement 2

**User Story:** As a user, I want a clean play button next to each topic that has a video, so that I can easily access the video content.

#### Acceptance Criteria

1. WHEN a topic has an associated video THEN a play button SHALL be displayed next to the topic card
2. WHEN the play button is clicked THEN the user SHALL be redirected to video.blade.php?topic_id={id}
3. WHEN a topic is locked THEN no play button SHALL be displayed (using exact lock logic from new_topics.blade.php)
4. WHEN the play button is rendered THEN it SHALL use the exact HTML structure, classes, and CSS from new_topics.blade.php

### Requirement 3

**User Story:** As a user, I want the play button to maintain proper positioning when topic cards expand or contract, so that the UI remains stable and usable.

#### Acceptance Criteria

1. WHEN topic cards expand or contract THEN the play button SHALL maintain its fixed positioning relative to the card
2. WHEN categorization pills are clicked and topics are filtered THEN the play button SHALL hide appropriately (copying exact animation behavior from new_topics.blade.php)
3. WHEN the page is responsive on different screen sizes THEN the play button SHALL maintain proper positioning
4. WHEN hovering over the play button THEN it SHALL show the same hover effects as in new_topics.blade.php

### Requirement 4

**User Story:** As a developer, I want the lock logic to be identical to new_topics.blade.php, so that the behavior is consistent across the application.

#### Acceptance Criteria

1. WHEN determining if a topic is locked THEN the exact same variables and methods from new_topics.blade.php SHALL be used
2. WHEN a topic is locked THEN no play button SHALL be rendered for that topic
3. WHEN the lock status changes THEN the play button visibility SHALL update accordingly
4. WHEN examining the lock logic THEN it SHALL be identical to the implementation in new_topics.blade.php

### Requirement 5

**User Story:** As a user, I want the play button to link to the correct video page with the topic ID, so that I can view the appropriate video content.

#### Acceptance Criteria

1. WHEN the play button is clicked THEN it SHALL navigate to the route defined as video.blade.php with topic_id parameter
2. WHEN the video page loads THEN it SHALL receive the correct topic ID from the URL parameter
3. WHEN the video route is called THEN it SHALL use the existing route structure (video/{topic})
4. WHEN the link is generated THEN it SHALL use Laravel's route helper for proper URL generation