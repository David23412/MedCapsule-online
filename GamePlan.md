# Project Game Plan

This document outlines the key tasks for the current development phase of the Medcapsule project.

## 1. Enhance Notification System in Course and Topic Blades

**Objective:** Replicate the full functionality of the homepage notification bell in the course and topic blade views, and remove the "Mark as read" text.

- [ ] **Task 1.1:** Identify the code responsible for the homepage notification bell functionality (HTML, CSS, JavaScript, and backend endpoint).
- [ ] **Task 1.2:** Locate the notification bell implementation in the course blade view.
- [ ] **Task 1.3:** Locate the notification bell implementation in the topics blade view.
- [ ] **Task 1.4:** Integrate the identified homepage notification bell code/mechanism into the course blade view, ensuring all features (fetching notifications, displaying, marking as read on click, etc.) are working correctly.
- [ ] **Task 1.5:** Integrate the identified homepage notification bell code/mechanism into the topics blade view, ensuring all features are working correctly.
- [ ] **Task 1.6:** Modify the relevant frontend code (likely JavaScript or the blade template) to remove the "Mark as read" text display from all notification bell implementations (homepage, course, and topics).
- [ ] **Task 1.7:** Test the notification bell functionality thoroughly in all three locations (homepage, course, and topics) to ensure consistent behavior and that notifications are fetched and displayed correctly.
- [ ] **Task 1.8:** Verify that clicking a notification correctly marks it as read without explicitly showing "Mark as read".

## 2. Test Local Payment System

**Objective:** Conduct a comprehensive test of the local payment system to ensure smooth operation, correct reference code generation, and successful course access upon payment simulation.

- [ ] **Task 2.1:** Review the payment flow documentation (e.g., `docs/payment_system.md`, `docs/payment_system_improvements.md`, files in `storage/archive old payment/`) to understand the intended process.
- [ ] **Task 2.2:** Identify the controllers and services involved in payment initiation, processing, and verification (e.g., `app/Http/Controllers/PaymentController.php`, `app/Services/PaymentVerificationService.php`).
- [ ] **Task 2.3:** Set up the local environment to simulate payment transactions. This might involve configuring `.env` or specific payment service mock data.
- [ ] **Task 2.4:** Simulate a student initiating a payment. Observe reference code generation and ensure it follows the expected format.
- [ ] **Task 2.5:** Simulate the payment completion (e.g., manual verification in the current system or a mocked callback).
- [ ] **Task 2.6:** Verify that upon simulated payment completion, the user is correctly enrolled in the paid course. Check the `course_user` table or user relationships.
- [ ] **Task 2.7:** Verify that payment success notifications are generated and delivered to the user and potentially admin notifications if configured for verification steps.
- [ ] **Task 2.8:** Test edge cases, such as incorrect reference codes, failed payments, or attempting to pay for an already enrolled course.
- [ ] **Task 2.9:** Document findings and any issues encountered during local testing.

## 3. Add New "Written questions" Type

**Objective:** Introduce a new question type that allows for written answers with forgiving grading, integrated into the random quiz functionality without affecting existing question types or grading.

- [ ] **Task 3.1:** Define the database schema changes required to support written questions (e.g., new column in `questions` table for question type, potentially a new table or column for expected answer variations or keywords).
- [ ] **Task 3.2:** Create a new database migration to implement the schema changes.
- [ ] **Task 3.3:** Update the `Question` model (`app/Models/Question.php`) to include the new question type attribute and potentially relationships for grading variations.
- [ ] **Task 3.4:** Develop or integrate a forgiving text comparison algorithm/function that can handle typos and filler words. This might involve libraries or custom logic.
- [ ] **Task 3.5:** Modify the quiz logic (likely in a controller or service handling quiz attempts) to recognize the new question type.
- [ ] **Task 3.6:** Implement the grading mechanism for written questions using the forgiving comparison logic. Ensure this logic is applied only to written questions and does not interfere with grading for existing question types.
- [ ] **Task 3.7:** Update the random quiz generation logic to include the new written question type (if desired) or ensure it can handle it if selected.
- [ ] **Task 3.8:** Modify the quiz attempt processing logic to correctly store user answers and grading results for written questions (e.g., in the `quiz_attempts` or `mistakes` table).
- [ ] **Task 3.9:** Develop frontend components (Blade templates, JavaScript) to display written questions and capture user input during a quiz.
- [ ] **Task 3.10:** Update the quiz results display to correctly show the question, the user's written answer, the expected answer, and the grading outcome for written questions.
- [ ] **Task 3.11:** Thoroughly test the new question type:
    - Create and save written questions.
    - Include written questions in quizzes (both specific and random).
    - Test grading with various inputs (correct, typos, filler words, completely wrong).
    - Verify that grading for multiple-choice/other types remains unaffected.
    - Check how mistakes on written questions are recorded and displayed.
- [ ] **Task 3.12:** Verify the integration with the random quiz feature, ensuring written questions appear correctly and are graded properly within a mixed quiz.
- [ ] **Task 3.13:** Monitor application logs for any errors related to the new question type or grading.

## General Tasks

- [ ] **Task 4.1:** After completing tasks for an objective, perform a full regression test to ensure no existing functionality (user registration, course access, other notification types, existing quizzes, etc.) has been negatively impacted.
- [ ] **Task 4.2:** Commit changes frequently with clear messages.
- [ ] **Task 4.3:** Before deploying, perform a final comprehensive test on a staging environment.
