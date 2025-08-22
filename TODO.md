# TODO: Implement Missing User Model Methods

## Steps to Complete:

1. [x] Add `hasAccessToCourses($courseIds)` method to User model
   - Bulk check access to multiple courses
   - Admins should have access to all courses
   - For students, check enrollment status in course_user pivot table
   - Return array mapping course IDs to boolean access status

2. [x] Add `hasPendingPaymentsForCourses($courseIds)` method to User model
   - Bulk check pending payments for multiple courses
   - Check Payment model for pending payments (status = 'pending')
   - Consider free courses (price = 0 or null) should not have pending payments
   - Return array mapping course IDs to boolean pending payment status

3. [ ] Add helper methods for optimization:
   - `getCourseAccessMap($courseIds)` - Efficient bulk access check
   - `getPaymentStatusMap($courseIds)` - Efficient bulk payment status check

4. [ ] Test the implementation
   - Verify methods work correctly for admins and students
   - Test with free and paid courses
   - Ensure performance is optimized for bulk operations

## Implementation Details:

### hasAccessToCourses($courseIds)
- Input: array of course IDs
- Output: array of [course_id => boolean]
- Logic: Admins always have access, students check enrollment

### hasPendingPaymentsForCourses($courseIds) 
- Input: array of course IDs
- Output: array of [course_id => boolean]
- Logic: Check Payment model for pending payments, exclude free courses

## Performance Optimization:
- Use single database queries for bulk operations
- Avoid N+1 query problems
- Cache results where appropriate
- Handle edge cases (empty arrays, invalid IDs)
