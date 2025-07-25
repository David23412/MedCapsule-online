# Implementation Plan

## Phase 1: CSS Extraction and Organization

- [x] 1. Extract inline styles from topics_for_course.blade.php to dedicated CSS files
  - Move all `<style>` blocks from the main file to organized CSS files
  - Create `public/css/topics-page.css` for main page styles
  - Create `public/css/topic-filters.css` for filter pill styles
  - Create `public/css/topic-cards.css` for topic card styling
  - Create `public/css/assignment-cards.css` for assignment-specific styling
  - Ensure exact visual preservation with no style changes
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 5.6_

- [x] 2. Organize existing CSS files and eliminate duplication
  - Review existing `public/css/topics.css` and merge with new organization
  - Remove any duplicate CSS rules between files
  - Ensure component-specific styles are co-located properly
  - Test visual output remains identical across all browsers
  - _Requirements: 2.4, 6.3_

## Phase 2: JavaScript Modularization

- [x] 3. Extract inline JavaScript to dedicated modules
  - Move all `<script>` content from topics_for_course.blade.php to separate JS files
  - Create `public/js/topics-page.js` for main page coordination
  - Create `public/js/topic-filters.js` for filter functionality
  - Create `public/js/assignment-countdown.js` for countdown timers
  - Create `public/js/quiz-modal.js` for source selection modal
  - Preserve all existing functionality and timing
  - _Requirements: 3.1, 3.2, 3.3, 5.7_

- [x] 4. Implement proper JavaScript module structure
  - Follow modern ES6+ patterns for module organization
  - Ensure proper event handling and DOM manipulation preservation
  - Maintain exact behavior for all interactive elements
  - Test all JavaScript functionality works identically
  - _Requirements: 3.2, 3.3, 5.7, 5.8_

## Phase 3: Component Creation

- [ ] 5. Create topics header component
  - Create `resources/views/components/topics/topics-header.blade.php`
  - Extract page title, breadcrumbs, and header elements
  - Define props interface: `$course`, `$user`
  - Preserve exact header styling and positioning
  - _Requirements: 1.2, 4.1, 4.2, 5.5_

- [ ] 6. Create course progress bar component
  - Create `resources/views/components/topics/course-progress-bar.blade.php`
  - Extract progress display logic and styling
  - Define props: `$topics`, `$completedTopics`, `$totalTopics`
  - Maintain exact progress calculation and visual appearance
  - _Requirements: 1.2, 4.1, 4.2, 5.3_

- [ ] 7. Create topic filters component
  - Create `resources/views/components/topics/topic-filters.blade.php`
  - Extract filter pills generation and styling logic
  - Define props: `$topics`, `$assignments`, `$courseType`
  - Preserve dynamic filter generation for custom categories
  - Maintain exact pill colors and active states
  - _Requirements: 1.2, 4.1, 4.2, 5.1, 5.5_

- [ ] 8. Create assignment card component
  - Create `resources/views/components/topics/assignment-card.blade.php`
  - Extract assignment-specific topic card logic
  - Define props: `$topic`, `$assignment`, `$progress`, `$latestAttempt`
  - Preserve assignment status bar, countdown, and locking logic
  - Maintain exact assignment styling and behavior
  - _Requirements: 1.2, 4.1, 4.2, 5.2, 5.4_

- [ ] 9. Create regular topic card component
  - Create `resources/views/components/topics/topic-card.blade.php`
  - Extract regular topic card display logic
  - Define props: `$topic`, `$progress`, `$percentageGrade`, `$isLocked`
  - Preserve topic information, progress, and grade display
  - Maintain exact topic card styling and interactions
  - _Requirements: 1.2, 4.1, 4.2, 5.3, 5.5_

- [ ] 10. Create quiz source modal component
  - Create `resources/views/components/topics/quiz-source-modal.blade.php`
  - Extract source selection modal HTML and logic
  - Define props: `$topics`
  - Preserve modal functionality and form handling
  - Maintain exact modal styling and behavior
  - _Requirements: 1.2, 4.1, 4.2, 5.4, 5.7_

- [ ] 11. Create topics container component
  - Create `resources/views/components/topics/topics-container.blade.php`
  - Extract main topics container logic
  - Define props: `$topics`, `$assignments`, `$userProgress`
  - Orchestrate assignment and regular topic cards
  - Preserve exact container layout and responsive behavior
  - _Requirements: 1.2, 4.1, 4.2, 5.9_

## Phase 4: Main File Refactoring

- [ ] 12. Refactor main topics_for_course.blade.php file
  - Reduce main file to data preparation and component orchestration
  - Implement component inclusion with proper props
  - Remove all inline CSS and JavaScript
  - Achieve target of under 200 lines
  - Preserve exact page structure and functionality
  - _Requirements: 1.1, 1.2, 4.1, 4.3, 5.10_

- [ ] 13. Implement proper data preparation and prop passing
  - Organize data preparation logic in main file
  - Ensure all components receive correct props
  - Maintain exact data flow and processing
  - Test all component interactions work correctly
  - _Requirements: 4.2, 4.3, 5.1, 5.2, 5.3_

## Phase 5: Integration and Testing

- [ ] 14. Comprehensive visual regression testing
  - Test pixel-perfect preservation across all browsers
  - Verify responsive behavior on all device sizes
  - Ensure no visual changes in any component
  - Test all CSS animations and transitions
  - _Requirements: 5.5, 5.6, 5.8, 5.9_

- [ ] 15. Functional testing of all interactions
  - Test topic filtering functionality works identically
  - Verify assignment countdown and status updates
  - Test quiz modal interactions and form submissions
  - Ensure all JavaScript functionality preserved
  - _Requirements: 5.1, 5.2, 5.4, 5.7_

- [ ] 16. Performance validation and optimization
  - Measure page load time before and after refactoring
  - Ensure performance is maintained or improved
  - Test component lazy loading if implemented
  - Verify no performance regressions
  - _Requirements: 6.1, 6.2, 6.4_

- [ ] 17. Cross-browser compatibility testing
  - Test on Chrome, Firefox, Safari, and Edge
  - Verify all functionality works across browsers
  - Ensure visual consistency on all platforms
  - Test mobile and desktop responsive behavior
  - _Requirements: 5.9, 5.10_

- [ ] 18. Final integration testing and documentation
  - Test complete page functionality end-to-end
  - Verify zero regression in all features
  - Document component interfaces and usage
  - Create maintenance guidelines for future development
  - _Requirements: 1.3, 4.3, 6.4_