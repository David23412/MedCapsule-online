# Implementation Plan

- [x] 1. Remove video modal markup from topics_for_course.blade.php
  - Delete the complete video modal HTML structure including modal-dialog, modal-content, modal-header, and modal-body
  - Remove all Bootstrap modal attributes like data-bs-toggle, data-bs-target, tabindex, aria-modal
  - Remove modal backdrop and close button elements
  - _Requirements: 1.1, 1.2_

- [x] 2. Remove video modal CSS styles from topics_for_course.blade.php
  - Delete all CSS rules targeting .video-modal classes and their variants
  - Remove video container, video loading, and iframe styling rules
  - Remove responsive video modal CSS and media queries
  - Remove video modal transition and animation CSS
  - _Requirements: 1.5_

- [x] 3. Remove video-related JavaScript from topics_for_course.blade.php
  - Delete playVideo() function and all modal initialization code
  - Remove secure video component script loading and error handling
  - Remove YouTube iframe API integration and custom controls JavaScript
  - Remove context menu prevention and keyboard shortcut blocking for video modals
  - _Requirements: 1.4, 1.5_

- [x] 4. Remove secure video overlay components from topics_for_course.blade.php
  - Delete x-secure-video-overlay elements and related markup
  - Remove secure-video-wrapper divs and custom YouTube controls
  - Remove any @include statements for secure video components
  - Remove enablejsapi and iframe_api script references
  - _Requirements: 1.3_

- [x] 5. Copy video play button CSS from new_topics.blade.php to topics_for_course.blade.php
  - Copy .video-play-btn CSS rules including positioning, sizing, and styling
  - Copy .video-play-btn:hover effects and transitions
  - Copy .topic-with-video-container CSS for proper layout
  - Copy animation rules for hiding buttons during filtering
  - _Requirements: 2.4, 3.1, 3.2, 3.3_

- [x] 6. Implement topic lock logic in topics_for_course.blade.php
  - Add the exact PHP lock logic from new_topics.blade.php using $isLocked variable
  - Copy the conditional check: $isLocked = isset($topic->is_locked) ? $topic->is_locked : false
  - Ensure lock logic is applied within the topic loop before rendering video buttons
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 7. Add video play button HTML structure to topics_for_course.blade.php
  - Wrap existing topic cards in .topic-with-video-container divs
  - Add conditional video play button outside topic cards using @if($topic->video && !$isLocked)
  - Implement proper link structure using Laravel route helper to video.blade.php
  - Copy exact HTML structure and classes from new_topics.blade.php
  - _Requirements: 2.1, 2.2, 2.3, 5.1, 5.2, 5.3, 5.4_

- [ ] 8. Test video button functionality and positioning
  - Verify play buttons appear only for topics with videos and are not locked
  - Test navigation to video.blade.php with correct topic_id parameter
  - Verify button positioning remains stable during topic card expansion
  - Test responsive behavior and hover effects match new_topics.blade.php
  - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.3, 4.2, 5.1, 5.2_

- [ ] 9. Test categorization filter animations
  - Verify video buttons hide appropriately when categorization pills are clicked
  - Test that animation behavior matches exactly with new_topics.blade.php implementation
  - Confirm buttons reappear when filters are cleared or changed
  - Test smooth transitions during filtering operations
  - _Requirements: 3.2_

- [ ] 10. Verify complete cleanup and functionality
  - Confirm all video modal, overlay, and YouTube integration code is completely removed
  - Test that no JavaScript errors occur after cleanup
  - Verify page performance improvement from reduced code
  - Test all existing topic functionality still works correctly after changes
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_