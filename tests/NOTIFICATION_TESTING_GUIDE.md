# Notification System Testing Guide

## Overview

This comprehensive test suite ensures the notification system meets all requirements for accessibility, performance, and visual consistency. The tests cover all aspects of the notification UI overhaul as specified in the requirements.

## Requirements Coverage

### 7.1 - Screen Reader Support and ARIA Compliance
- **Tests**: `notification-accessibility.test.js`
- **Coverage**: ARIA attributes, screen reader announcements, semantic HTML
- **Validation**: All interactive elements have proper labels and roles

### 7.2 - Keyboard Navigation and Focus Management  
- **Tests**: `notification-bell.test.js`, `notification-accessibility.test.js`
- **Coverage**: Tab navigation, Enter/Space activation, Escape closing, Arrow navigation
- **Validation**: Focus trap, focus return, keyboard shortcuts

### 7.3 - Performance Optimization and Fast Loading
- **Tests**: `notification-integration.test.js`, performance metrics
- **Coverage**: Load times < 100ms, memory usage, bundle size optimization
- **Validation**: Debounced interactions, efficient DOM updates, memory cleanup

### 7.4 - Reduced Motion and Accessibility Preferences
- **Tests**: `notification-accessibility.test.js`, visual regression tests
- **Coverage**: `prefers-reduced-motion` support, alternative feedback methods
- **Validation**: Animation disabling, immediate state changes

### 7.5 - Visual Consistency and Design System Compliance
- **Tests**: `notification-visual-regression.spec.js`
- **Coverage**: Cross-browser consistency, responsive design, theme support
- **Validation**: Screenshot comparisons, design token usage

## Test Structure

```
tests/
├── js/                          # Unit and integration tests
│   ├── notification-bell.test.js        # Bell component tests
│   ├── notification-dropdown.test.js    # Dropdown component tests  
│   ├── notification-accessibility.test.js # Accessibility tests
│   ├── notification-integration.test.js  # Integration tests
│   ├── jest.config.js                   # Jest configuration
│   └── setup.js                         # Test setup and mocks
├── e2e/                         # End-to-end tests
│   └── notification-visual-regression.spec.js # Visual regression tests
├── reports/                     # Generated test reports
├── coverage/                    # Code coverage reports
└── run-notification-tests.js   # Test runner script
```

## Running Tests

### Quick Start
```bash
# Run all notification tests
node tests/run-notification-tests.js

# Run specific test suites
npm run test:jest          # Unit tests only
npm run test:playwright    # E2E tests only
npm run test:accessibility # Accessibility audit
```

### Individual Test Files
```bash
# Unit tests
npx jest tests/js/notification-bell.test.js
npx jest tests/js/notification-dropdown.test.js
npx jest tests/js/notification-accessibility.test.js
npx jest tests/js/notification-integration.test.js

# E2E tests
npx playwright test tests/e2e/notification-visual-regression.spec.js
```

### With Coverage
```bash
npx jest --coverage --config=tests/js/jest.config.js
```

## Test Categories

### 1. Unit Tests

#### Bell Component Tests (`notification-bell.test.js`)
- ✅ Button interactions (click, hover, focus)
- ✅ Badge management (count updates, animations)
- ✅ ARIA attributes and accessibility
- ✅ Keyboard navigation
- ✅ Performance optimization
- ✅ Error handling

#### Dropdown Component Tests (`notification-dropdown.test.js`)
- ✅ Positioning logic (desktop, mobile, edge cases)
- ✅ Responsive behavior across screen sizes
- ✅ Content rendering (notifications, empty state)
- ✅ Scrolling behavior with many items
- ✅ Loading and error states
- ✅ Animation and transitions

#### Accessibility Tests (`notification-accessibility.test.js`)
- ✅ ARIA attributes and roles
- ✅ Keyboard navigation workflows
- ✅ Screen reader support
- ✅ Focus management
- ✅ Color and contrast compliance
- ✅ Reduced motion support
- ✅ Touch accessibility

#### Integration Tests (`notification-integration.test.js`)
- ✅ Complete user workflows
- ✅ Performance tracking
- ✅ Caching integration
- ✅ Network state handling
- ✅ Error recovery workflows

### 2. End-to-End Tests

#### Visual Regression Tests (`notification-visual-regression.spec.js`)
- ✅ Bell component visual states
- ✅ Dropdown rendering across viewports
- ✅ Loading and error state visuals
- ✅ Responsive design validation
- ✅ Animation consistency
- ✅ Cross-browser compatibility
- ✅ Theme and color accuracy

## Test Configuration

### Jest Configuration (`jest.config.js`)
```javascript
{
  testEnvironment: 'jsdom',
  coverageThreshold: {
    global: {
      branches: 80,
      functions: 80,
      lines: 80,
      statements: 80
    }
  },
  setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js']
}
```

### Playwright Configuration
```javascript
{
  testDir: './tests/e2e',
  timeout: 30000,
  use: {
    screenshot: 'only-on-failure',
    video: 'retain-on-failure'
  },
  projects: [
    { name: 'chromium' },
    { name: 'firefox' },
    { name: 'webkit' }
  ]
}
```

## Mocking Strategy

### DOM Environment
- JSDOM for realistic browser environment
- Mock performance APIs, localStorage, fetch
- Custom accessibility matchers

### API Responses
```javascript
// Mock successful notification load
mockFetch.mockResolvedValueOnce({
  ok: true,
  json: () => Promise.resolve({
    success: true,
    notifications: [...],
    unread_count: 3
  })
});
```

### User Interactions
```javascript
// Keyboard events
testUtils.triggerKeyboardEvent(element, 'Enter');

// Mouse events  
testUtils.triggerMouseEvent(element, 'click');
```

## Accessibility Testing

### Screen Reader Testing
- Automated ARIA validation
- Screen reader announcement testing
- Focus management verification

### Keyboard Navigation
- Tab order validation
- Keyboard shortcut testing
- Focus trap verification

### Visual Accessibility
- Color contrast validation
- Reduced motion support
- High contrast mode testing

## Performance Testing

### Metrics Tracked
- Load time: < 100ms target
- Render time: < 50ms target  
- Memory usage: < 5MB target
- Bundle size: < 10KB target

### Performance Validation
```javascript
test('should load within performance budget', () => {
  const loadTime = endPerformanceTracking(true);
  expect(loadTime).toBeLessThan(100); // 100ms
});
```

## Visual Regression Testing

### Screenshot Comparison
- Baseline screenshots for all states
- Cross-browser consistency validation
- Responsive design verification

### Test Scenarios
- Default states
- Hover and focus states
- Loading and error states
- Different viewport sizes
- Theme variations

## Continuous Integration

### GitHub Actions Integration
```yaml
- name: Run Notification Tests
  run: node tests/run-notification-tests.js

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./tests/coverage/lcov.info
```

### Test Reports
- HTML coverage reports
- JUnit XML for CI integration
- Visual regression reports
- Accessibility audit results

## Troubleshooting

### Common Issues

#### Test Timeouts
```javascript
// Increase timeout for slow operations
jest.setTimeout(10000);
```

#### Mock Issues
```javascript
// Clear mocks between tests
beforeEach(() => {
  jest.clearAllMocks();
});
```

#### DOM Cleanup
```javascript
// Clean DOM after each test
afterEach(() => {
  document.body.innerHTML = '';
});
```

### Debug Mode
```bash
# Run tests in debug mode
npx jest --detectOpenHandles --verbose

# Debug Playwright tests
npx playwright test --debug
```

## Best Practices

### Test Organization
- Group related tests in describe blocks
- Use descriptive test names
- Follow AAA pattern (Arrange, Act, Assert)

### Accessibility Testing
- Test with actual screen readers when possible
- Validate keyboard navigation workflows
- Check color contrast programmatically

### Performance Testing
- Set realistic performance budgets
- Test on various device capabilities
- Monitor memory usage and cleanup

### Visual Testing
- Use consistent test data
- Account for timing and animations
- Test across multiple browsers

## Maintenance

### Updating Tests
- Update tests when requirements change
- Maintain screenshot baselines
- Review and update performance budgets

### Adding New Tests
- Follow existing patterns and structure
- Include accessibility considerations
- Add visual regression coverage

### Monitoring
- Track test execution times
- Monitor flaky tests
- Review coverage reports regularly

## Success Criteria

### Test Coverage
- ✅ 80%+ code coverage across all metrics
- ✅ All requirements (7.1-7.5) validated
- ✅ Cross-browser compatibility confirmed

### Performance
- ✅ Load times under 100ms
- ✅ Memory usage optimized
- ✅ Bundle size minimized

### Accessibility
- ✅ WCAG AA compliance
- ✅ Screen reader compatibility
- ✅ Keyboard navigation support

### Visual Consistency
- ✅ Design system compliance
- ✅ Responsive behavior validated
- ✅ Animation consistency confirmed

This comprehensive test suite ensures the notification system meets the highest standards for accessibility, performance, and user experience.