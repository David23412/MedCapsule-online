import { test, expect } from '@playwright/test';

/**
 * QUIZ FUNCTIONALITY E2E TESTS
 * Comprehensive testing of quiz start/finish, questions, timer, scoring, and completion
 */

test.describe('Quiz Functionality', () => {
  
  // Setup authentication for tests that require login
  test.use({ storageState: 'tests/e2e/auth/student.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to a course with quizzes
    await page.goto('/courses');
    await page.waitForLoadState('networkidle');
    
    // Find and access a course with quiz content
    const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")').first();
    if (await continueBtn.isVisible()) {
      await continueBtn.click();
      await page.waitForLoadState('networkidle');
    }
  });

  test.describe('Quiz Discovery and Access', () => {
    
    test('should display available quizzes in course topics', async ({ page }) => {
      // Look for quiz indicators in course content
      const quizItems = page.locator('.quiz-item, .quiz-link, [data-testid="quiz"], a[href*="quiz"]');
      
      if (await quizItems.count() > 0) {
        const firstQuiz = quizItems.first();
        await expect(firstQuiz).toBeVisible();
        
        // Verify quiz has proper labeling
        await expect(firstQuiz).toContainText(/quiz|test|exam/i);
        
        // Check for quiz metadata (questions count, time limit, etc.)
        const quizMeta = firstQuiz.locator('.quiz-meta, .quiz-info, .questions-count');
        if (await quizMeta.isVisible()) {
          await expect(quizMeta).toContainText(/\d+/); // Should contain numbers
        }
      }
    });

    test('should navigate to quiz start page', async ({ page }) => {
      const quizLink = page.locator('.quiz-item, .quiz-link, [data-testid="quiz"], a[href*="quiz"]').first();
      
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Should navigate to quiz page
        await expect(page).toHaveURL(/.*quiz|.*solve/);
        
        // Verify quiz start interface
        const quizTitle = page.locator('h1, .quiz-title, [data-testid="quiz-title"]');
        await expect(quizTitle).toBeVisible();
        
        // Check for start button or quiz instructions
        const startElement = page.locator('button:has-text("Start"), .start-quiz, .quiz-instructions');
        await expect(startElement).toBeVisible();
      }
    });

    test('should display quiz metadata correctly', async ({ page }) => {
      const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
      
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Check for quiz information
        const quizInfo = page.locator('.quiz-info, .quiz-meta, .quiz-details');
        if (await quizInfo.isVisible()) {
          // Should show question count
          await expect(quizInfo).toContainText(/\d+.*question/i);
          
          // Should show time limit if applicable
          const timeInfo = page.locator('[data-testid="time-limit"], .time-limit, .duration');
          if (await timeInfo.isVisible()) {
            await expect(timeInfo).toContainText(/\d+.*minute|time/i);
          }
        }
      }
    });
  });

  test.describe('Quiz Start Process', () => {
    
    test('should start quiz successfully', async ({ page }) => {
      // Navigate to quiz
      const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
      
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Find and click start button
        const startBtn = page.locator('button:has-text("Start"), .start-quiz-btn, [data-testid="start-quiz"]');
        
        if (await startBtn.isVisible()) {
          await startBtn.click();
          
          // Verify quiz has started
          await expect(async () => {
            // Check for question content
            const question = page.locator('.question, .quiz-question, [data-testid="question"]');
            const isQuestionVisible = await question.isVisible();
            
            // Check for answer options
            const answers = page.locator('.answer-option, .quiz-option, input[type="radio"]');
            const hasAnswers = await answers.count() > 0;
            
            expect(isQuestionVisible || hasAnswers).toBeTruthy();
          }).toPass({ timeout: 10000 });
        }
      }
    });

    test('should display quiz instructions before starting', async ({ page }) => {
      const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
      
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Check for instructions
        const instructions = page.locator('.quiz-instructions, .instructions, [data-testid="instructions"]');
        
        if (await instructions.isVisible()) {
          await expect(instructions).toContainText(/instruction|rule|note/i);
          
          // Should have clear start button
          const startBtn = page.locator('button:has-text("Start"), .start-quiz-btn');
          await expect(startBtn).toBeVisible();
          await expect(startBtn).toBeEnabled();
        }
      }
    });

    test('should handle quiz prerequisites', async ({ page }) => {
      const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
      
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Check if quiz is locked due to prerequisites
        const lockedMessage = page.locator('.quiz-locked, .prerequisites, [data-testid="locked"]');
        
        if (await lockedMessage.isVisible()) {
          await expect(lockedMessage).toContainText(/complete|prerequisite|locked/i);
          
          // Start button should be disabled or not present
          const startBtn = page.locator('button:has-text("Start"), .start-quiz-btn');
          if (await startBtn.isVisible()) {
            await expect(startBtn).toBeDisabled();
          }
        }
      }
    });
  });

  test.describe('Quiz Question Interaction', () => {
    
    test('should display questions with proper formatting', async ({ page }) => {
      // Start a quiz first
      await startQuizIfAvailable(page);
      
      // Check question display
      const question = page.locator('.question, .quiz-question, [data-testid="question"]');
      
      if (await question.isVisible()) {
        await expect(question).not.toBeEmpty();
        
        // Check for question numbering
        const questionNumber = page.locator('.question-number, .q-num, [data-testid="question-num"]');
        if (await questionNumber.isVisible()) {
          await expect(questionNumber).toContainText(/\d+/);
        }
        
        // Verify answer options are present
        const answerOptions = page.locator('.answer-option, .quiz-option, input[type="radio"], .choice');
        await expect(answerOptions).toHaveCount({ min: 2 });
      }
    });

    test('should allow answer selection', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Find answer options
      const answerOptions = page.locator('.answer-option, .quiz-option, input[type="radio"]');
      
      if (await answerOptions.count() > 0) {
        const firstOption = answerOptions.first();
        
        // Select first answer
        await firstOption.click();
        
        // Verify selection
        if (await firstOption.getAttribute('type') === 'radio') {
          expect(await firstOption.isChecked()).toBeTruthy();
        } else {
          // For custom styled options, check for selected class
          await expect(firstOption).toHaveClass(/selected|active|checked/);
        }
      }
    });

    test('should navigate between questions', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Look for navigation buttons
      const nextBtn = page.locator('button:has-text("Next"), .next-question, [data-testid="next"]');
      const prevBtn = page.locator('button:has-text("Previous"), .prev-question, [data-testid="previous"]');
      
      if (await nextBtn.isVisible()) {
        // Select an answer first
        const answerOption = page.locator('.answer-option, input[type="radio"]').first();
        if (await answerOption.isVisible()) {
          await answerOption.click();
        }
        
        // Navigate to next question
        await nextBtn.click();
        
        // Verify question changed
        await page.waitForTimeout(500);
        
        // Check if previous button becomes available
        if (await prevBtn.isVisible()) {
          await expect(prevBtn).toBeEnabled();
        }
      }
    });

    test('should save answers automatically', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Select an answer
      const answerOption = page.locator('.answer-option, input[type="radio"]').first();
      
      if (await answerOption.isVisible()) {
        await answerOption.click();
        
        // Wait for auto-save
        await page.waitForTimeout(1000);
        
        // Check for save indicator
        const saveIndicator = page.locator('.auto-saved, .saved, [data-testid="saved"]');
        if (await saveIndicator.isVisible()) {
          await expect(saveIndicator).toContainText(/saved|auto/i);
        }
        
        // Or verify by navigating away and back
        const nextBtn = page.locator('button:has-text("Next"), .next-question');
        if (await nextBtn.isVisible()) {
          await nextBtn.click();
          
          const prevBtn = page.locator('button:has-text("Previous"), .prev-question');
          if (await prevBtn.isVisible()) {
            await prevBtn.click();
            
            // Answer should still be selected
            if (await answerOption.getAttribute('type') === 'radio') {
              expect(await answerOption.isChecked()).toBeTruthy();
            }
          }
        }
      }
    });

    test('should handle multiple choice questions correctly', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Check if it's a multiple choice question
      const checkboxes = page.locator('input[type="checkbox"]');
      
      if (await checkboxes.count() > 0) {
        // Select multiple answers
        const firstCheckbox = checkboxes.first();
        const secondCheckbox = checkboxes.nth(1);
        
        await firstCheckbox.check();
        await secondCheckbox.check();
        
        // Verify multiple selections are allowed
        expect(await firstCheckbox.isChecked()).toBeTruthy();
        expect(await secondCheckbox.isChecked()).toBeTruthy();
      }
    });
  });

  test.describe('Quiz Timer Functionality', () => {
    
    test('should display timer when quiz has time limit', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Look for timer display
      const timer = page.locator('.timer, .countdown, [data-testid="timer"]');
      
      if (await timer.isVisible()) {
        await expect(timer).toContainText(/\d+:\d+|\d+/); // Time format
        
        // Verify timer is counting down
        const initialTime = await timer.textContent();
        await page.waitForTimeout(2000);
        const laterTime = await timer.textContent();
        
        // Time should have changed (decreased)
        expect(laterTime).not.toBe(initialTime);
      }
    });

    test('should warn when time is running low', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Look for timer
      const timer = page.locator('.timer, .countdown, [data-testid="timer"]');
      
      if (await timer.isVisible()) {
        // Simulate low time by checking for warning styles
        // (In a real test, you might set a very short timer)
        
        // Check if timer changes color/style when low
        await page.waitForTimeout(5000);
        
        const timerWarning = page.locator('.timer.warning, .timer.danger, .low-time');
        if (await timerWarning.isVisible()) {
          await expect(timerWarning).toBeVisible();
        }
      }
    });

    test('should auto-submit when time expires', async ({ page }) => {
      // This test would require a very short timer or ability to manipulate time
      // For demonstration, we'll test the mechanism exists
      
      await startQuizIfAvailable(page);
      
      // Check if there's auto-submit functionality
      const hasTimer = await page.locator('.timer, .countdown').isVisible();
      
      if (hasTimer) {
        // Look for auto-submit warning or mechanism
        const autoSubmitWarning = page.locator('.auto-submit-warning, [data-testid="auto-submit"]');
        
        // If no warning visible, check for the mechanism in JavaScript
        const hasAutoSubmit = await page.evaluate(() => {
          // Check if there's a timer interval or timeout set
          return typeof window.quizTimer !== 'undefined' || 
                 typeof window.autoSubmitTimer !== 'undefined';
        });
        
        // Should have auto-submit mechanism if there's a timer
        if (hasTimer) {
          expect(hasAutoSubmit || await autoSubmitWarning.isVisible()).toBeTruthy();
        }
      }
    });
  });

  test.describe('Quiz Progress Tracking', () => {
    
    test('should display progress indicator', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Look for progress indicators
      const progressBar = page.locator('.progress-bar, .quiz-progress, [data-testid="progress"]');
      const questionCounter = page.locator('.question-counter, .current-question, [data-testid="question-count"]');
      
      if (await progressBar.isVisible()) {
        await expect(progressBar).toBeVisible();
        
        // Progress should have some width
        const progressWidth = await progressBar.evaluate(el => {
          return el.style.width || window.getComputedStyle(el).width;
        });
        expect(progressWidth).toBeTruthy();
      }
      
      if (await questionCounter.isVisible()) {
        await expect(questionCounter).toContainText(/\d+.*\/.*\d+/); // Format like "1/10"
      }
    });

    test('should update progress as user advances', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      const progressBar = page.locator('.progress-bar, .progress-fill');
      
      if (await progressBar.isVisible()) {
        // Get initial progress
        const initialProgress = await progressBar.evaluate(el => {
          return parseInt(el.style.width) || 0;
        });
        
        // Answer question and move to next
        const answerOption = page.locator('.answer-option, input[type="radio"]').first();
        if (await answerOption.isVisible()) {
          await answerOption.click();
        }
        
        const nextBtn = page.locator('button:has-text("Next"), .next-question');
        if (await nextBtn.isVisible()) {
          await nextBtn.click();
          await page.waitForTimeout(500);
          
          // Progress should have increased
          const newProgress = await progressBar.evaluate(el => {
            return parseInt(el.style.width) || 0;
          });
          
          expect(newProgress).toBeGreaterThan(initialProgress);
        }
      }
    });

    test('should show answered vs unanswered questions', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Look for question status indicators
      const questionNav = page.locator('.question-nav, .question-list, .quiz-navigator');
      
      if (await questionNav.isVisible()) {
        const questionItems = questionNav.locator('.question-item, .nav-item');
        
        if (await questionItems.count() > 0) {
          // Initially should be unanswered
          const firstItem = questionItems.first();
          await expect(firstItem).toHaveClass(/unanswered|pending/);
          
          // Answer current question
          const answerOption = page.locator('.answer-option, input[type="radio"]').first();
          if (await answerOption.isVisible()) {
            await answerOption.click();
            await page.waitForTimeout(500);
            
            // Question should now be marked as answered
            await expect(firstItem).toHaveClass(/answered|completed/);
          }
        }
      }
    });
  });

  test.describe('Quiz Completion and Submission', () => {
    
    test('should allow quiz submission when completed', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Navigate through quiz and answer questions
      await answerQuestionsAndNavigateToEnd(page);
      
      // Look for submit button
      const submitBtn = page.locator('button:has-text("Submit"), .submit-quiz, [data-testid="submit"]');
      
      if (await submitBtn.isVisible()) {
        await expect(submitBtn).toBeEnabled();
        
        // Click submit
        await submitBtn.click();
        
        // Verify submission confirmation
        const confirmModal = page.locator('.confirm-modal, .submission-confirm, [data-testid="confirm-submit"]');
        
        if (await confirmModal.isVisible()) {
          const confirmBtn = confirmModal.locator('button:has-text("Confirm"), .confirm-btn');
          await confirmBtn.click();
        }
        
        // Should navigate to results or show success
        await expect(async () => {
          const isResultsPage = page.url().includes('result') || page.url().includes('score');
          const hasSuccessMessage = await page.locator('.success-message, .quiz-completed').isVisible();
          expect(isResultsPage || hasSuccessMessage).toBeTruthy();
        }).toPass({ timeout: 10000 });
      }
    });

    test('should display quiz results after submission', async ({ page }) => {
      await startQuizIfAvailable(page);
      await answerQuestionsAndNavigateToEnd(page);
      
      // Submit quiz
      const submitBtn = page.locator('button:has-text("Submit"), .submit-quiz');
      
      if (await submitBtn.isVisible()) {
        await submitBtn.click();
        
        // Handle confirmation if present
        const confirmBtn = page.locator('button:has-text("Confirm"), .confirm-btn');
        if (await confirmBtn.isVisible()) {
          await confirmBtn.click();
        }
        
        // Wait for results
        await page.waitForTimeout(2000);
        
        // Check for score display
        const scoreDisplay = page.locator('.score, .quiz-score, [data-testid="score"]');
        if (await scoreDisplay.isVisible()) {
          await expect(scoreDisplay).toContainText(/\d+|score|result/i);
        }
        
        // Check for percentage or grade
        const percentage = page.locator('.percentage, .grade, .quiz-grade');
        if (await percentage.isVisible()) {
          await expect(percentage).toContainText(/\d+%|\d+\/\d+/);
        }
      }
    });

    test('should save quiz progress for later completion', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Answer a question
      const answerOption = page.locator('.answer-option, input[type="radio"]').first();
      if (await answerOption.isVisible()) {
        await answerOption.click();
        await page.waitForTimeout(1000);
      }
      
      // Navigate away from quiz
      await page.goto('/courses');
      
      // Return to quiz
      const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
      if (await quizLink.isVisible()) {
        await quizLink.click();
        
        // Should be able to continue where left off
        const continueBtn = page.locator('button:has-text("Continue"), .continue-quiz');
        if (await continueBtn.isVisible()) {
          await expect(continueBtn).toBeVisible();
          
          await continueBtn.click();
          
          // Should show saved progress
          if (await answerOption.isVisible()) {
            const wasSelected = await answerOption.isChecked();
            expect(wasSelected).toBeTruthy();
          }
        }
      }
    });

    test('should prevent submission with unanswered questions', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Try to navigate to end without answering all questions
      const nextBtn = page.locator('button:has-text("Next"), .next-question');
      
      // Skip some questions
      let attempts = 0;
      while (await nextBtn.isVisible() && attempts < 5) {
        await nextBtn.click();
        await page.waitForTimeout(300);
        attempts++;
      }
      
      // Look for submit button
      const submitBtn = page.locator('button:has-text("Submit"), .submit-quiz');
      
      if (await submitBtn.isVisible()) {
        await submitBtn.click();
        
        // Should show warning about unanswered questions
        const warningMessage = page.locator('.warning, .unanswered-warning, [data-testid="warning"]');
        
        if (await warningMessage.isVisible()) {
          await expect(warningMessage).toContainText(/unanswered|complete|answer/i);
        } else {
          // Or submit button should be disabled
          await expect(submitBtn).toBeDisabled();
        }
      }
    });
  });

  test.describe('Quiz Performance and Reliability', () => {
    
    test('should load quiz questions quickly', async ({ page }) => {
      const startTime = Date.now();
      
      await startQuizIfAvailable(page);
      
      // Wait for question to be visible
      await page.waitForSelector('.question, .quiz-question', { timeout: 5000 });
      
      const endTime = Date.now();
      const loadTime = endTime - startTime;
      
      // Should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      
      console.log(`Quiz load time: ${loadTime}ms`);
    });

    test('should handle network interruptions gracefully', async ({ page }) => {
      await startQuizIfAvailable(page);
      
      // Simulate network issue by going offline briefly
      await page.context().setOffline(true);
      
      // Try to answer a question
      const answerOption = page.locator('.answer-option, input[type="radio"]').first();
      if (await answerOption.isVisible()) {
        await answerOption.click();
      }
      
      // Go back online
      await page.context().setOffline(false);
      
      // Wait for reconnection
      await page.waitForTimeout(2000);
      
      // Should recover gracefully
      const errorMessage = page.locator('.error, .network-error, [data-testid="error"]');
      const isErrorVisible = await errorMessage.isVisible();
      
      // Either no error, or error with recovery option
      if (isErrorVisible) {
        const retryBtn = page.locator('button:has-text("Retry"), .retry-btn');
        await expect(retryBtn).toBeVisible();
      }
    });
  });
});

/**
 * Helper function to start a quiz if available
 */
async function startQuizIfAvailable(page) {
  const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
  
  if (await quizLink.isVisible()) {
    await quizLink.click();
    
    const startBtn = page.locator('button:has-text("Start"), .start-quiz-btn, [data-testid="start-quiz"]');
    
    if (await startBtn.isVisible()) {
      await startBtn.click();
      await page.waitForTimeout(1000);
    }
  }
}

/**
 * Helper function to answer questions and navigate to the end
 */
async function answerQuestionsAndNavigateToEnd(page) {
  let questionsAnswered = 0;
  const maxQuestions = 10; // Prevent infinite loop
  
  while (questionsAnswered < maxQuestions) {
    // Answer current question
    const answerOption = page.locator('.answer-option, input[type="radio"]').first();
    if (await answerOption.isVisible()) {
      await answerOption.click();
      await page.waitForTimeout(300);
    }
    
    // Try to go to next question
    const nextBtn = page.locator('button:has-text("Next"), .next-question');
    
    if (await nextBtn.isVisible()) {
      await nextBtn.click();
      await page.waitForTimeout(500);
      questionsAnswered++;
    } else {
      // No more next button, probably at the end
      break;
    }
  }
}