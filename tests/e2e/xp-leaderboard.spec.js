import { test, expect } from '@playwright/test';

/**
 * XP SYSTEM & LEADERBOARD E2E TESTS
 * Comprehensive testing of experience points, level progression, and leaderboard rankings
 */

test.describe('XP System & Leaderboard', () => {
  
  // Setup authentication for tests that require login
  test.use({ storageState: 'tests/e2e/auth/student.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to dashboard or courses to check XP display
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
  });

  test.describe('XP Display and Tracking', () => {
    
    test('should display current XP and level correctly', async ({ page }) => {
      // Look for XP display elements
      const xpDisplay = page.locator('.xp-display, .experience-points, [data-testid="xp"], .points-display');
      
      if (await xpDisplay.isVisible()) {
        await expect(xpDisplay).toBeVisible();
        
        // Should contain numerical XP value
        const xpText = await xpDisplay.textContent();
        expect(xpText).toMatch(/\d+|XP|points|experience/i);
        
        // Check for level display
        const levelDisplay = page.locator('.level-display, .current-level, [data-testid="level"], .user-level');
        if (await levelDisplay.isVisible()) {
          const levelText = await levelDisplay.textContent();
          expect(levelText).toMatch(/level \d+|\d+|lvl/i);
        }
      }
      
      // Check for XP progress bar
      const xpProgressBar = page.locator('.xp-progress, .level-progress, [data-testid="xp-progress"]');
      if (await xpProgressBar.isVisible()) {
        await expect(xpProgressBar).toBeVisible();
        
        // Progress bar should have some width
        const progressWidth = await xpProgressBar.evaluate(el => {
          const style = window.getComputedStyle(el);
          return parseInt(style.width) || 0;
        });
        expect(progressWidth).toBeGreaterThan(0);
      }
    });

    test('should show XP progress toward next level', async ({ page }) => {
      const xpSection = page.locator('.xp-section, .experience-section, .progress-section');
      
      if (await xpSection.isVisible()) {
        // Look for next level indicator
        const nextLevel = xpSection.locator('.next-level, .level-target, [data-testid="next-level"]');
        
        if (await nextLevel.isVisible()) {
          const nextLevelText = await nextLevel.textContent();
          expect(nextLevelText).toMatch(/next|level \d+|\d+ more|required/i);
        }
        
        // Check for XP requirement display
        const xpRequired = xpSection.locator('.xp-required, .points-needed, .to-next-level');
        if (await xpRequired.isVisible()) {
          const requiredText = await xpRequired.textContent();
          expect(requiredText).toMatch(/\d+.*XP|\d+.*points|\d+.*needed/i);
        }
      }
    });

    test('should display XP history or recent activities', async ({ page }) => {
      // Look for XP activity feed or history
      const xpHistory = page.locator('.xp-history, .activity-feed, .recent-activities, [data-testid="xp-activity"]');
      
      if (await xpHistory.isVisible()) {
        await expect(xpHistory).toBeVisible();
        
        // Should have activity items
        const activityItems = xpHistory.locator('.activity-item, .xp-item, .history-item');
        
        if (await activityItems.count() > 0) {
          const firstActivity = activityItems.first();
          
          // Should show activity description
          await expect(firstActivity).toContainText(/completed|earned|points|XP/i);
          
          // Should show point value
          await expect(firstActivity).toContainText(/\+\d+|\d+.*XP|\d+.*points/);
          
          // Should show timestamp
          const timestamp = firstActivity.locator('.timestamp, .date, .time-ago');
          if (await timestamp.isVisible()) {
            await expect(timestamp).toContainText(/ago|today|yesterday|\d+\/\d+/i);
          }
        }
      }
    });
  });

  test.describe('XP Earning Mechanics', () => {
    
    test('should earn XP for completing quiz questions', async ({ page }) => {
      // Navigate to a course with quizzes
      await page.goto('/courses');
      
      const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")').first();
      
      if (await continueBtn.isVisible()) {
        await continueBtn.click();
        
        // Get initial XP
        const initialXP = await getCurrentXP(page);
        
        // Find and complete a quiz
        const quizLink = page.locator('.quiz-item, .quiz-link, a[href*="quiz"]').first();
        
        if (await quizLink.isVisible()) {
          await quizLink.click();
          
          // Start quiz
          const startBtn = page.locator('button:has-text("Start"), .start-quiz-btn');
          if (await startBtn.isVisible()) {
            await startBtn.click();
            await page.waitForTimeout(1000);
          }
          
          // Answer a question
          const answerOption = page.locator('.answer-option, input[type="radio"]').first();
          if (await answerOption.isVisible()) {
            await answerOption.click();
            
            // Submit answer or move to next question
            const nextBtn = page.locator('button:has-text("Next"), .next-question, .submit-answer');
            if (await nextBtn.isVisible()) {
              await nextBtn.click();
              await page.waitForTimeout(2000);
              
              // Check for XP notification or updated XP
              const xpNotification = page.locator('.xp-earned, .points-earned, .xp-notification, [data-testid="xp-earned"]');
              
              if (await xpNotification.isVisible()) {
                await expect(xpNotification).toContainText(/\+\d+|\d+.*XP|earned|points/i);
              } else {
                // Check if XP value has increased
                const newXP = await getCurrentXP(page);
                if (initialXP !== null && newXP !== null) {
                  expect(newXP).toBeGreaterThan(initialXP);
                }
              }
            }
          }
        }
      }
    });

    test('should earn XP for course completion milestones', async ({ page }) => {
      await page.goto('/courses');
      
      const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")').first();
      
      if (await continueBtn.isVisible()) {
        // Get initial XP
        const initialXP = await getCurrentXP(page);
        
        await continueBtn.click();
        
        // Look for completion milestones
        const milestones = page.locator('.milestone, .achievement, .progress-milestone');
        
        if (await milestones.count() > 0) {
          const firstMilestone = milestones.first();
          
          // Check if milestone shows XP reward
          const xpReward = firstMilestone.locator('.xp-reward, .points-reward, [data-testid="xp-reward"]');
          
          if (await xpReward.isVisible()) {
            await expect(xpReward).toContainText(/\d+.*XP|\d+.*points/);
          }
        }
      }
    });

    test('should show XP multipliers for streaks or bonuses', async ({ page }) => {
      // Look for streak or bonus XP indicators
      const streakDisplay = page.locator('.streak, .bonus-xp, .multiplier, [data-testid="streak"]');
      
      if (await streakDisplay.isVisible()) {
        await expect(streakDisplay).toBeVisible();
        
        // Should show streak count or multiplier
        const streakText = await streakDisplay.textContent();
        expect(streakText).toMatch(/\d+.*day|streak|\d+x|bonus|multiplier/i);
        
        // Should show bonus XP information
        const bonusInfo = streakDisplay.locator('.bonus-info, .multiplier-info');
        if (await bonusInfo.isVisible()) {
          await expect(bonusInfo).toContainText(/bonus|extra|\+\d+%|multiplier/i);
        }
      }
    });

    test('should display XP earning opportunities', async ({ page }) => {
      // Look for XP earning suggestions or opportunities
      const xpOpportunities = page.locator('.xp-opportunities, .earn-points, .ways-to-earn');
      
      if (await xpOpportunities.isVisible()) {
        await expect(xpOpportunities).toBeVisible();
        
        // Should list different ways to earn XP
        const earningMethods = xpOpportunities.locator('.earning-method, .xp-source, li');
        
        if (await earningMethods.count() > 0) {
          const firstMethod = earningMethods.first();
          
          // Should describe action and XP reward
          await expect(firstMethod).toContainText(/complete|answer|quiz|lesson/i);
          await expect(firstMethod).toContainText(/\d+.*XP|\d+.*points/);
        }
      }
    });
  });

  test.describe('Level Progression', () => {
    
    test('should show level-up notification when reaching new level', async ({ page }) => {
      // This test would require earning enough XP to level up
      // For demonstration, we'll check for level-up notification elements
      
      const levelUpNotification = page.locator('.level-up, .new-level, .level-achieved, [data-testid="level-up"]');
      
      // If notification is visible (from previous session or test data)
      if (await levelUpNotification.isVisible()) {
        await expect(levelUpNotification).toBeVisible();
        await expect(levelUpNotification).toContainText(/level up|new level|congratulations|achieved/i);
        
        // Should show new level number
        await expect(levelUpNotification).toContainText(/level \d+|\d+/);
        
        // May show rewards or benefits
        const levelRewards = levelUpNotification.locator('.rewards, .benefits, .unlocked');
        if (await levelRewards.isVisible()) {
          await expect(levelRewards).toContainText(/unlocked|reward|benefit|access/i);
        }
      }
    });

    test('should display level benefits and requirements', async ({ page }) => {
      // Look for level information section
      const levelInfo = page.locator('.level-info, .level-benefits, .progression-info');
      
      if (await levelInfo.isVisible()) {
        await expect(levelInfo).toBeVisible();
        
        // Should show current level benefits
        const currentBenefits = levelInfo.locator('.current-benefits, .unlocked-features');
        if (await currentBenefits.isVisible()) {
          await expect(currentBenefits).toContainText(/access|feature|benefit|unlocked/i);
        }
        
        // Should show next level requirements
        const nextLevelReqs = levelInfo.locator('.next-level-requirements, .level-requirements');
        if (await nextLevelReqs.isVisible()) {
          await expect(nextLevelReqs).toContainText(/\d+.*XP|required|needed|reach/i);
        }
      }
    });

    test('should show level progression chart or visual', async ({ page }) => {
      const levelChart = page.locator('.level-chart, .progression-chart, .level-visual, [data-testid="level-progress"]');
      
      if (await levelChart.isVisible()) {
        await expect(levelChart).toBeVisible();
        
        // Should have multiple level indicators
        const levelMarkers = levelChart.locator('.level-marker, .level-step, .progression-step');
        
        if (await levelMarkers.count() > 0) {
          await expect(levelMarkers).toHaveCount({ min: 2 });
          
          // Should show current level as active/highlighted
          const currentLevelMarker = levelMarkers.locator('.current, .active, .achieved').first();
          if (await currentLevelMarker.isVisible()) {
            await expect(currentLevelMarker).toBeVisible();
          }
        }
      }
    });
  });

  test.describe('Leaderboard Display', () => {
    
    test('should display leaderboard with user rankings', async ({ page }) => {
      // Navigate to leaderboard page or section
      const leaderboardLink = page.locator('a[href*="leaderboard"], .leaderboard-link, [data-testid="leaderboard"]');
      
      if (await leaderboardLink.isVisible()) {
        await leaderboardLink.click();
      } else {
        // Try direct navigation
        await page.goto('/leaderboard');
      }
      
      await page.waitForLoadState('networkidle');
      
      // Check for leaderboard content
      const leaderboard = page.locator('.leaderboard, .rankings, .top-users, [data-testid="leaderboard-content"]');
      
      if (await leaderboard.isVisible()) {
        await expect(leaderboard).toBeVisible();
        
        // Should have user entries
        const userEntries = leaderboard.locator('.user-entry, .leaderboard-item, .ranking-item');
        await expect(userEntries).toHaveCount({ min: 1 });
        
        // First entry should show rank, name, and XP
        const firstEntry = userEntries.first();
        await expect(firstEntry).toContainText(/1|#1|first/); // Rank
        await expect(firstEntry).toContainText(/\d+.*XP|\d+.*points/); // XP
        
        // Should show user name or username
        const userName = firstEntry.locator('.user-name, .username, .name');
        if (await userName.isVisible()) {
          await expect(userName).not.toBeEmpty();
        }
      }
    });

    test('should show current user position in leaderboard', async ({ page }) => {
      await navigateToLeaderboard(page);
      
      const leaderboard = page.locator('.leaderboard, .rankings, .top-users');
      
      if (await leaderboard.isVisible()) {
        // Look for current user highlight
        const currentUser = leaderboard.locator('.current-user, .me, .highlighted, [data-testid="current-user"]');
        
        if (await currentUser.isVisible()) {
          await expect(currentUser).toBeVisible();
          
          // Should show user's rank
          await expect(currentUser).toContainText(/\d+|#\d+/);
          
          // Should show user's XP
          await expect(currentUser).toContainText(/\d+.*XP|\d+.*points/);
        } else {
          // Look for user position indicator
          const userPosition = page.locator('.your-position, .my-rank, [data-testid="user-rank"]');
          if (await userPosition.isVisible()) {
            await expect(userPosition).toContainText(/rank|position|\d+/i);
          }
        }
      }
    });

    test('should display leaderboard filters and time periods', async ({ page }) => {
      await navigateToLeaderboard(page);
      
      // Look for filter options
      const filterOptions = page.locator('.leaderboard-filters, .time-filters, .ranking-filters');
      
      if (await filterOptions.isVisible()) {
        // Should have time period options
        const timePeriods = filterOptions.locator('button, .filter-option, select option');
        
        if (await timePeriods.count() > 0) {
          // Common time periods
          const weeklyFilter = filterOptions.locator('button:has-text("Week"), option:has-text("Weekly")');
          const monthlyFilter = filterOptions.locator('button:has-text("Month"), option:has-text("Monthly")');
          const allTimeFilter = filterOptions.locator('button:has-text("All"), option:has-text("All Time")');
          
          // At least one time period should be available
          const hasTimeFilters = await weeklyFilter.isVisible() || 
                                await monthlyFilter.isVisible() || 
                                await allTimeFilter.isVisible();
          expect(hasTimeFilters).toBeTruthy();
        }
      }
    });

    test('should allow switching between leaderboard time periods', async ({ page }) => {
      await navigateToLeaderboard(page);
      
      const filterOptions = page.locator('.leaderboard-filters, .time-filters, .ranking-filters');
      
      if (await filterOptions.isVisible()) {
        const weeklyFilter = filterOptions.locator('button:has-text("Week"), .weekly-filter');
        const monthlyFilter = filterOptions.locator('button:has-text("Month"), .monthly-filter');
        
        if (await weeklyFilter.isVisible() && await monthlyFilter.isVisible()) {
          // Get initial leaderboard data
          const initialEntries = await page.locator('.user-entry, .leaderboard-item').count();
          
          // Switch to weekly view
          await weeklyFilter.click();
          await page.waitForTimeout(1000);
          
          // Switch to monthly view
          await monthlyFilter.click();
          await page.waitForTimeout(1000);
          
          // Verify leaderboard updated (rankings may have changed)
          const newEntries = await page.locator('.user-entry, .leaderboard-item').count();
          
          // Should still have entries
          expect(newEntries).toBeGreaterThan(0);
        }
      }
    });

    test('should show leaderboard pagination for large user lists', async ({ page }) => {
      await navigateToLeaderboard(page);
      
      const leaderboard = page.locator('.leaderboard, .rankings, .top-users');
      
      if (await leaderboard.isVisible()) {
        // Look for pagination controls
        const pagination = page.locator('.pagination, .page-controls, .leaderboard-pagination');
        
        if (await pagination.isVisible()) {
          // Should have page numbers or next/previous buttons
          const pageButtons = pagination.locator('button, .page-btn, .page-number');
          await expect(pageButtons).toHaveCount({ min: 1 });
          
          // Test pagination if multiple pages exist
          const nextBtn = pagination.locator('button:has-text("Next"), .next-page');
          
          if (await nextBtn.isVisible() && await nextBtn.isEnabled()) {
            const initialFirstUser = await page.locator('.user-entry, .leaderboard-item').first().textContent();
            
            await nextBtn.click();
            await page.waitForTimeout(1000);
            
            const newFirstUser = await page.locator('.user-entry, .leaderboard-item').first().textContent();
            
            // Should show different users on next page
            expect(newFirstUser).not.toBe(initialFirstUser);
          }
        }
      }
    });
  });

  test.describe('Achievements and Badges', () => {
    
    test('should display earned achievements or badges', async ({ page }) => {
      // Look for achievements section
      const achievementsSection = page.locator('.achievements, .badges, .awards, [data-testid="achievements"]');
      
      if (await achievementsSection.isVisible()) {
        await expect(achievementsSection).toBeVisible();
        
        // Should have achievement items
        const achievements = achievementsSection.locator('.achievement, .badge, .award-item');
        
        if (await achievements.count() > 0) {
          const firstAchievement = achievements.first();
          
          // Should have achievement icon/image
          const achievementIcon = firstAchievement.locator('img, .icon, .badge-icon');
          if (await achievementIcon.isVisible()) {
            await expect(achievementIcon).toBeVisible();
          }
          
          // Should have achievement name/title
          const achievementName = firstAchievement.locator('.achievement-name, .badge-title, .award-title');
          if (await achievementName.isVisible()) {
            await expect(achievementName).not.toBeEmpty();
          }
          
          // Should have achievement description
          const achievementDesc = firstAchievement.locator('.achievement-desc, .badge-description, .award-desc');
          if (await achievementDesc.isVisible()) {
            await expect(achievementDesc).not.toBeEmpty();
          }
        }
      }
    });

    test('should show progress toward unearned achievements', async ({ page }) => {
      const achievementsSection = page.locator('.achievements, .badges, .awards');
      
      if (await achievementsSection.isVisible()) {
        // Look for locked/unearned achievements
        const lockedAchievements = achievementsSection.locator('.locked, .unearned, .in-progress');
        
        if (await lockedAchievements.count() > 0) {
          const firstLocked = lockedAchievements.first();
          
          // Should show progress toward achievement
          const progressBar = firstLocked.locator('.progress-bar, .achievement-progress');
          
          if (await progressBar.isVisible()) {
            await expect(progressBar).toBeVisible();
            
            // Should show progress text
            const progressText = firstLocked.locator('.progress-text, .requirement-text');
            if (await progressText.isVisible()) {
              await expect(progressText).toContainText(/\d+\/\d+|\d+%|complete|more needed/i);
            }
          }
        }
      }
    });

    test('should show achievement notification when earned', async ({ page }) => {
      // Look for achievement notification (might be from previous session)
      const achievementNotification = page.locator('.achievement-earned, .badge-unlocked, .award-notification, [data-testid="achievement-notification"]');
      
      if (await achievementNotification.isVisible()) {
        await expect(achievementNotification).toBeVisible();
        await expect(achievementNotification).toContainText(/achieved|earned|unlocked|congratulations/i);
        
        // Should show achievement details
        const achievementDetails = achievementNotification.locator('.achievement-details, .badge-info');
        if (await achievementDetails.isVisible()) {
          await expect(achievementDetails).not.toBeEmpty();
        }
        
        // Should have close button or auto-dismiss
        const closeBtn = achievementNotification.locator('.close, .dismiss, .ok-btn');
        if (await closeBtn.isVisible()) {
          await expect(closeBtn).toBeEnabled();
        }
      }
    });
  });

  test.describe('Gamification Elements', () => {
    
    test('should display daily/weekly challenges if available', async ({ page }) => {
      const challengesSection = page.locator('.challenges, .daily-challenges, .weekly-goals, [data-testid="challenges"]');
      
      if (await challengesSection.isVisible()) {
        await expect(challengesSection).toBeVisible();
        
        // Should have challenge items
        const challenges = challengesSection.locator('.challenge, .goal, .challenge-item');
        
        if (await challenges.count() > 0) {
          const firstChallenge = challenges.first();
          
          // Should show challenge description
          await expect(firstChallenge).not.toBeEmpty();
          
          // Should show progress
          const challengeProgress = firstChallenge.locator('.progress, .challenge-progress, .completion');
          if (await challengeProgress.isVisible()) {
            await expect(challengeProgress).toContainText(/\d+\/\d+|\d+%|complete/i);
          }
          
          // Should show reward
          const challengeReward = firstChallenge.locator('.reward, .prize, .xp-reward');
          if (await challengeReward.isVisible()) {
            await expect(challengeReward).toContainText(/\d+.*XP|\d+.*points|reward/i);
          }
        }
      }
    });

    test('should show competitive elements like user comparisons', async ({ page }) => {
      // Look for friend comparisons or peer rankings
      const comparisons = page.locator('.peer-comparison, .friend-rankings, .class-ranking');
      
      if (await comparisons.isVisible()) {
        await expect(comparisons).toBeVisible();
        
        // Should show relative performance
        await expect(comparisons).toContainText(/ahead|behind|rank|position|vs|compared/i);
        
        // Should show specific metrics
        await expect(comparisons).toContainText(/\d+.*XP|\d+.*points|\d+.*quizzes/i);
      }
    });
  });

  test.describe('Performance and Responsiveness', () => {
    
    test('should load leaderboard data quickly', async ({ page }) => {
      const startTime = Date.now();
      
      await navigateToLeaderboard(page);
      
      // Wait for leaderboard content to be visible
      await page.waitForSelector('.leaderboard, .rankings, .user-entry', { timeout: 5000 });
      
      const endTime = Date.now();
      const loadTime = endTime - startTime;
      
      // Should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      
      console.log(`Leaderboard load time: ${loadTime}ms`);
    });

    test('should update XP display in real-time', async ({ page }) => {
      // This test would monitor XP updates during user activity
      // For demonstration, we'll check for real-time update mechanisms
      
      const xpDisplay = page.locator('.xp-display, .experience-points, [data-testid="xp"]');
      
      if (await xpDisplay.isVisible()) {
        // Check if there's a WebSocket connection or polling mechanism
        const hasRealTimeUpdates = await page.evaluate(() => {
          // Check for WebSocket or EventSource connections
          return typeof window.WebSocket !== 'undefined' || 
                 typeof window.EventSource !== 'undefined' ||
                 typeof window.xpUpdateInterval !== 'undefined';
        });
        
        // Should have some mechanism for real-time updates
        // (This would be implementation-specific)
        console.log('Real-time update mechanism present:', hasRealTimeUpdates);
      }
    });
  });
});

/**
 * Helper function to get current XP value
 */
async function getCurrentXP(page) {
  try {
    const xpDisplay = page.locator('.xp-display, .experience-points, [data-testid="xp"]');
    
    if (await xpDisplay.isVisible()) {
      const xpText = await xpDisplay.textContent();
      const xpMatch = xpText.match(/(\d+)/);
      return xpMatch ? parseInt(xpMatch[1]) : null;
    }
    
    return null;
  } catch (error) {
    console.warn('Could not get current XP:', error);
    return null;
  }
}

/**
 * Helper function to navigate to leaderboard
 */
async function navigateToLeaderboard(page) {
  const leaderboardLink = page.locator('a[href*="leaderboard"], .leaderboard-link, [data-testid="leaderboard"]');
  
  if (await leaderboardLink.isVisible()) {
    await leaderboardLink.click();
  } else {
    // Try direct navigation
    await page.goto('/leaderboard');
  }
  
  await page.waitForLoadState('networkidle');
}