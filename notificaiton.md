# 🔔 COMPLETE NOTIFICATION ICONS REFERENCE

## Overview
This document lists ALL notification icons in the system, their colors, border colors, actual messages, and when they trigger. Use this to identify redundant notifications and clean up the system.

---

## 🎓 ACADEMIC & LEARNING NOTIFICATIONS

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 🎓 `fas fa-graduation-cap` | `course_welcome` | 🟢 `#10b981` | ⚪ None | When you enroll in any course | "You have enrolled in the {courseName} course" |
| ✅ `fas fa-check-circle` | `quiz_completed` | 🔵 `#3b82f6` | ⚪ None | ✅ **FIXED**: Only triggers for actual quiz completion | "You scored {percentage}% on {courseName}" |
| 🎓 `fas fa-graduation-cap` | `topic_mastery` | 🟢 `#10b981` | 🟢 Green | ✅ **FIXED**: When you master a topic (90%+ accuracy) | "Congratulations! You've mastered {topicName} in {courseName}" |
| ⚡ `fas fa-bolt` | `level_up` | 🟡 `#fbbf24` | 🔥 Fiery | ✅ **FIXED**: Level up notification with fiery border | "Amazing! You've reached level {newLevel}!" |

---

## 🎯 TUTOR MODE NOTIFICATIONS (Simplified)

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ✅ `fas fa-check-circle` | `tutor_quiz_completed` | 🔵 `#3b82f6` | ⚪ None | ✅ **SIMPLIFIED**: Shows score like regular quiz | "You scored {percentage}% in tutor mode for {courseName}" |
| ⭐ `fas fa-star` | `tutor_perfect` | 🟣 `#8b5cf6` | 🟣 Purple | ✅ **NEW**: Purple star with purple border for 100% in tutor mode | "Outstanding! You got 100% in tutor mode for {courseName}" |
| 🔄 `fas fa-redo-alt` | `tutor_needs_practice` | 🔴 `#ef4444` | ⚪ None | ✅ **NEW**: When you get <50% in tutor mode | "You scored {percentage}% in tutor mode. Try again!" |

---

## 📝 ASSIGNMENT SYSTEM NOTIFICATIONS (Simplified)

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 📋 `fas fa-clipboard-check` | `assignment_completed` | 🔵 `#3b82f6` | ⚪ None | ✅ **FIXED**: Blue color like quiz system, shows score | "You scored {percentage}% on '{assignmentTitle}'" |
| ⭐ `fas fa-star` | `assignment_perfect` | 🟣 `#8b5cf6` | 🟣 Purple | ✅ **FIXED**: Purple star with purple border for 100% | "Amazing! You got 100% on '{assignmentTitle}'" |

### Assignment Management
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 📅 `fas fa-calendar` | `assignment_assigned` | 🔵 `#3b82f6` | 🔵 Blue | When assignment is assigned to you | "New assignment '{assignmentTitle}' is due on {dueDate}" or "New assignment '{assignmentTitle}' has been assigned to you" |

### Assignment Due Warnings (Simplified - 3-1 Hours Only)
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ❗ `fas fa-exclamation-circle` | `assignment_due_3h` | 🔴 `#ef4444` | 🔴 Red | Assignment due in 3 hours | "Hurry up! '{assignmentTitle}' is due in {hours} hours - Time to focus!" |
| ❗ `fas fa-exclamation-circle` | `assignment_due_1h` | 🔴 `#ef4444` | 🔴 Red | Assignment due in 1 hour | "URGENT: '{assignmentTitle}' is due in {hours} hours - Finish now!" |

### Assignment Overdue (Single Notification)
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ❗ `fas fa-exclamation-circle` | `assignment_overdue` | 🔴 `#ef4444` | 🔴 Red | Assignment is overdue | "You missed '{assignmentTitle}' deadline!" |

---

## 🏆 PROGRESS & MILESTONES

### Comprehensive Study Streak System
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 🌱 `fas fa-seedling` | `streak_day_1` | 🟢 `#10b981` | 🟢 Green | First quiz completed | "GREAT JOB! This is just the beginning!" |
| 🚀 `fas fa-rocket` | `streak_day_2` | 🔵 `#3b82f6` | 🟡 Amber | 2 days in a row | "Two days! You are on a roll now. I can feel it already. Keep it up!" |
| ⚡ `fas fa-bolt` | `streak_day_3` | 🟡 `#fbbf24` | 🟡 Amber | 3 days in a row | "Three days! Your brain is getting used to this. It is like training - you get stronger every day." |
| ⚡ `fas fa-bolt` | `streak_week_1` | 🟡 `#fbbf24` | 🟡 Amber | 7 days (1 week) | "A whole week! You showed up every day. That is what heroes do - they show up." |
| ⚡ `fas fa-bolt` | `streak_day_10` | 🟡 `#fbbf24` | 🟡 Amber | 10 days | "Ten days! Double digits now! You are in the top 10% now. That is pretty cool." |
| ⚡ `fas fa-bolt` | `streak_week_2` | 🟡 `#fbbf24` | 🟡 Amber | 14 days (2 weeks) | "Two weeks! You are getting really good at this. Your brain loves this now." |
| ⚡ `fas fa-bolt` | `streak_day_15` | 🟡 `#fbbf24` | 🟡 Amber | 15 days | "Fifteen days! You are halfway to a month. Your future self will thank you for this." |
| ⚡ `fas fa-bolt` | `streak_day_21` | 🟡 `#fbbf24` | 🟡 Amber | 21 days (habit formation) | "Twenty-one days! They say it takes 21 days to make a habit. Well, you just did it!" |
| ⚡ `fas fa-bolt` | `streak_month_1` | 🟡 `#fbbf24` | 🟡 Amber | 30 days (1 month) | "A whole month! You are in the top 1% now. That is pretty special, you know." |
| ⚡ `fas fa-bolt` | `streak_month_2` | 🟡 `#fbbf24` | 🟡 Amber | 60 days (2 months) | "Two months! You are not just learning anymore - you are becoming something great." |
| ⚡ `fas fa-bolt` | `streak_month_3` | 🟡 `#fbbf24` | 🟡 Amber | 90 days (3 months) | "Three months! You have totally changed how you learn. You cannot be stopped now!" |
| ⚡ `fas fa-bolt` | `streak_month_6` | 🟡 `#fbbf24` | 🟡 Amber | 180 days (6 months) | "Half a year! Your mind is like a diamond now - strong, bright, and super good." |
| ⚡ `fas fa-bolt` | `streak_year_1` | 🟡 `#fbbf24` | 🟡 Amber | 365 days (1 year) | "A FULL YEAR! Only 0.1% of people do this. You are now a learning hero!" |

### Course Mastery Achievement
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 💎 `fas fa-gem` | `course_mastery` | 🔷 `#1e40af` | 🔷 Deep Blue | When you master a complete course | "WOW! You just MASTERED {courseName}! Every single topic conquered. You are now officially a legend!" |

### Other Milestones
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ✨ `fas fa-star-of-life` | `correct_answers_milestone` | 🟣 `#7c3aed` | 🟣 Deep Purple | Every 100 correct answers | "WOW! {correctAnswers} correct answers! Your brain is on fire right now. This is some serious skill!" |
| 🔥 `fas fa-fire` | `streak_reset` | 🟠 `#f97316` | ⚪ None | When study streak is broken | "Your study streak was reset. Start again!" |

---

## ⚠️ WARNINGS & REVIEWS

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ⚠️ `fas fa-exclamation-triangle` | `review_mistakes` | 🔴 `#ef4444` | ⚪ None | ✅ **FIXED**: Simple reminder when you have mistakes to review | "You have {mistakeCount} mistakes to review. Take a few minutes to strengthen your knowledge!" |
| ❗ `fas fa-exclamation-circle` | `weak_topic_identified` | 🔴 `#ef4444` | ⚪ None | ✅ **SIMPLIFIED**: Reminder based on weak topics in profile progress table | "You need work on: {topicNames}. Time to practice these!" |
| ⏭️ `fas fa-skip-forward` | `skipped_questions_detected` | 🔵 `#3b82f6` | ⚪ None | ✅ **SIMPLIFIED**: When you skip questions during learn mistakes session | "You skipped {skippedCount} questions. Go back and try them when you are ready!" |

---

## 💳 PAYMENT & ACCESS

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 💳 `fas fa-credit-card` | `payment_success` | 🟢 `#10b981` | ⚪ None | ✅ **FIXED**: When single payment is successful | "Payment of ${amount} for {courseName} was successful" |
| ⚠️ `fas fa-exclamation-triangle` | `payment_failed` | 🔴 `#ef4444` | 🔴 Red | When payment fails | "Your payment could not be processed. Please try again." |
| 🔓 `fas fa-unlock` | `course_access_granted` | 🟢 `#10b981` | ⚪ None | ✅ **FIXED**: When admin grants course access in manage access | "You now have access to {courseName}. Start learning!" |
| 🔒 `fas fa-lock` | `course_access_denied` | 🔴 `#ef4444` | ⚪ None | When course access is denied | "Payment required for {courseName}" |

---

## 🔔 SYSTEM & GENERAL

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 🔔 `fas fa-bell` | `system_update` | ⚫ `#6b7280` | ⚪ None | System maintenance updates | "{updateMessage}" |
| 📢 `fas fa-bullhorn` | `announcement` | 🔵 `#3b82f6` | ⚪ None | Important announcements | *Generic announcement message* |
| ✅ `fas fa-check` | `success` | 🟢 `#10b981` | ⚪ None | Success messages | "{message}" |
| ⚠️ `fas fa-exclamation-triangle` | `warning` | 🟡 `#f59e0b` | ⚪ None | Warning messages | *Generic warning message* |
| ❗ `fas fa-exclamation-circle` | `error` | 🔴 `#ef4444` | ⚪ None | Error messages | *Generic error message* |

---

## 🎯 SPECIAL ACHIEVEMENTS

| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| ⭐ `fas fa-star` | `perfect_score` | 🟣 `#8b5cf6` | 🟣 Purple | ✅ **FIXED**: Purple star with purple border for 100% (quiz/assignment) | "Amazing! You got 100% on {courseName/assignmentTitle}" |
| ⭐ `fas fa-star` | `tutor_perfect` | 🟣 `#8b5cf6` | 🟣 Purple | ✅ **NEW**: Purple star with purple border for 100% in tutor mode | "Outstanding! You got 100% in tutor mode for {courseName}" |

---

## 📚 SUBJECT-SPECIFIC COURSE NOTIFICATIONS

### Medical Courses (Custom Colors & Messages)
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 🩸 `fas fa-tint` | `anatomy_enrolled` | 🔴 `#dc2626` | ⚪ None | Anatomy course enrollment | "You have enrolled in the anatomy course" |
| 🩸 `fas fa-tint` | `anatomy_halfway` | 🔴 `#dc2626` | ⚪ None | Anatomy course 50% progress | "You are halfway through the anatomy course" |
| 🩸 `fas fa-tint` | `anatomy_completed` | 🔴 `#dc2626` | ⚪ None | Anatomy course completion | "You have completed the anatomy course" |
| 🧪 `fas fa-flask` | `biochemistry_enrolled` | 🟢 `#10b981` | ⚪ None | Biochemistry course enrollment | "You have enrolled in the biochemistry course" |
| 🧪 `fas fa-flask` | `biochemistry_halfway` | 🟢 `#10b981` | ⚪ None | Biochemistry course 50% progress | "You are halfway through the biochemistry course" |
| 🧪 `fas fa-flask` | `biochemistry_completed` | 🟢 `#10b981` | ⚪ None | Biochemistry course completion | "You have completed the biochemistry course" |
| 💓 `fas fa-heartbeat` | `physiology_enrolled` | 🔴 `#ef4444` | ⚪ None | Physiology course enrollment | "You have enrolled in the physiology course" |
| 💓 `fas fa-heartbeat` | `physiology_halfway` | 🔴 `#ef4444` | ⚪ None | Physiology course 50% progress | "You are halfway through the physiology course" |
| 💓 `fas fa-heartbeat` | `physiology_completed` | 🔴 `#ef4444` | ⚪ None | Physiology course completion | "You have completed the physiology course" |
| 🔬 `fas fa-microscope` | `histology_enrolled` | 🟣 `#8b5cf6` | ⚪ None | Histology course enrollment | "You have enrolled in the histology course" |
| 🔬 `fas fa-microscope` | `histology_halfway` | 🟣 `#8b5cf6` | ⚪ None | Histology course 50% progress | "You are halfway through the histology course" |
| 🔬 `fas fa-microscope` | `histology_completed` | 🟣 `#8b5cf6` | ⚪ None | Histology course completion | "You have completed the histology course" |
| 🦠 `fas fa-bacteria` | `microbiology_enrolled` | 🟡 `#f59e0b` | ⚪ None | Microbiology course enrollment | "You have enrolled in the microbiology course" |
| 🦠 `fas fa-bacteria` | `microbiology_halfway` | 🟡 `#f59e0b` | ⚪ None | Microbiology course 50% progress | "You are halfway through the microbiology course" |
| 🦠 `fas fa-bacteria` | `microbiology_completed` | 🟡 `#f59e0b` | ⚪ None | Microbiology course completion | "You have completed the microbiology course" |
| 🐛 `fas fa-bug` | `parasitology_enrolled` | 🟢 `#84cc16` | ⚪ None | Parasitology course enrollment | "You have enrolled in the parasitology course" |
| 🐛 `fas fa-bug` | `parasitology_halfway` | 🟢 `#84cc16` | ⚪ None | Parasitology course 50% progress | "You are halfway through the parasitology course" |
| 🐛 `fas fa-bug` | `parasitology_completed` | 🟢 `#84cc16` | ⚪ None | Parasitology course completion | "You have completed the parasitology course" |
| ⚖️ `fas fa-balance-scale` | `ethics_enrolled` | 🔵 `#6366f1` | ⚪ None | Ethics course enrollment | "You have enrolled in the ethics course" |
| ⚖️ `fas fa-balance-scale` | `ethics_halfway` | 🔵 `#6366f1` | ⚪ None | Ethics course 50% progress | "You are halfway through the ethics course" |
| ⚖️ `fas fa-balance-scale` | `ethics_completed` | 🔵 `#6366f1` | ⚪ None | Ethics course completion | "You have completed the ethics course" |
| 🧠 `fas fa-brain` | `psychology_enrolled` | 🩷 `#ec4899` | ⚪ None | Psychology course enrollment | "You have enrolled in the psychology course" |
| 🧠 `fas fa-brain` | `psychology_halfway` | 🩷 `#ec4899` | ⚪ None | Psychology course 50% progress | "You are halfway through the psychology course" |
| 🧠 `fas fa-brain` | `psychology_completed` | 🩷 `#ec4899` | ⚪ None | Psychology course completion | "You have completed the psychology course" |
| 👨‍⚕️ `fas fa-user-md` | `patient_care_enrolled` | 🔵 `#06b6d4` | ⚪ None | Patient Care course enrollment | "You have enrolled in the patient care course" |
| 👨‍⚕️ `fas fa-user-md` | `patient_care_halfway` | 🔵 `#06b6d4` | ⚪ None | Patient Care course 50% progress | "You are halfway through the patient care course" |
| 👨‍⚕️ `fas fa-user-md` | `patient_care_completed` | 🔵 `#06b6d4` | ⚪ None | Patient Care course completion | "You have completed the patient care course" |
| 💊 `fas fa-pills` | `pharma_enrolled` | 🔵 `#3b82f6` | ⚪ None | Pharmacy course enrollment | "You have enrolled in the pharma course" |
| 💊 `fas fa-pills` | `pharma_halfway` | 🔵 `#3b82f6` | ⚪ None | Pharmacy course 50% progress | "You are halfway through the pharma course" |
| 💊 `fas fa-pills` | `pharma_completed` | 🔵 `#3b82f6` | ⚪ None | Pharmacy course completion | "You have completed the pharma course" |

### Special Welcome Messages
| Icon | Type | Icon Color | Border | When It Triggers | Actual Message |
|------|------|------------|--------|------------------|----------------|
| 💊 `fas fa-capsules` | `medcapsule_welcome` | 🔵 `#3b82f6` | 🔵 Blue | ✅ **NEW**: Welcome message when user logs into MedCapsule | "Welcome to the MedCapsule learning platform! Start your medical education journey." |

---

## 🎨 NOTIFICATION BORDER PRIORITY SYSTEM

Each notification card has a colored left border based on its priority level:

### 🔴 **URGENT PRIORITY** - Red Border (`#ef4444`)
**When**: Critical deadlines and failures
- `assignment_overdue` (assignment is overdue)
- `assignment_due_1h` & `assignment_due_3h` (urgent deadlines)
- `payment_failed` (payment processing failed)

### 🟡 **HIGH PRIORITY** - Amber Border (`#f59e0b`)
**When**: Achievements, completions, and important events
- `assignment_assigned` (new assignments)
- `study_streak`, `streak_milestone`, `correct_answers_milestone` (milestones)

### 🔵 **BLUE PRIORITY** - Blue Border (`#3b82f6`)
**When**: Welcome and platform messages
- `medcapsule_welcome` (welcome message)

### 🟢 **MASTERY PRIORITY** - Green Border (`#10b981`)
**When**: Topic mastery achievements and first steps
- `topic_mastery` (when you master a topic with 90%+ accuracy)
- `streak_day_1` (first quiz completed - special encouragement)

### 🟣 **PERFECT PRIORITY** - Purple Border (`#8b5cf6`)
**When**: Perfect score achievements (100%)
- `perfect_score` (100% on quiz or assignment)
- `tutor_perfect` (100% in tutor mode)
- `assignment_perfect` (100% on assignment)

### 🔥 **FIERY PRIORITY** - Fiery Orange Border (`#f97316`)
**When**: Level up achievements
- `level_up` (when you reach a new level)

### 🔷 **PREMIUM PRIORITY** - Deep Blue Border (`#1e40af`)
**When**: Ultimate course achievements
- `course_mastery` (when you master a complete course)

### 🟣 **EXOTIC PRIORITY** - Deep Purple Border (`#7c3aed`)
**When**: Special milestone achievements
- `correct_answers_milestone` (every 100 correct answers)

### ⚪ **NORMAL PRIORITY** - Transparent Border (No Color)
**When**: Regular activities and standard notifications
- `quiz_completed`, `tutor_quiz_completed` (regular completions)
- `assignment_completed` (standard scores)
- `review_mistakes`, `weak_topic_identified`, `skipped_questions_detected` (reminders)
- `payment_success`, `course_access_granted`, `course_access_denied` (payment/access)
- `system_update`, `success` (general messages)
- All course enrollment and progress notifications

---

## ❌ REMOVED/NOT IMPLEMENTED NOTIFICATIONS

### Removed for Redundancy
| Type | Reason | Replaced By |
|------|--------|-------------|
| `topic_needs_review` | ❌ **REMOVED**: Redundant | `weak_topic_identified` |
| `first_attempt` | ❌ **REMOVED**: As requested by user | N/A |
| `speed_demon` | ❌ **REMOVED**: As requested by user | N/A |
| `tutor_session_completed` | ❌ **REMOVED**: Redundant | `topic_mastery` |
| `tutor_mastery_achieved` | ❌ **REMOVED**: Same functionality | `topic_mastery` |
| `tutor_perfect_mastery` | ❌ **REMOVED**: Redundant | `perfect_score` |
| `tutor_strong_performance` | ❌ **REMOVED**: Repetitive | Show score in `tutor_quiz_completed` |
| `assignment_exceptional` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |
| `assignment_excellent` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |
| `assignment_good` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |
| `assignment_passed` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |
| `assignment_needs_review` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |
| `assignment_needs_improvement` | ❌ **REMOVED**: Repetitive | Show score in `assignment_completed` |

### Not Implemented Features
| Type | Status | Notes |
|------|--------|-------|
| `energy_boost` | ❌ **NOT IMPLEMENTED** | Defined in icon mapping but no trigger |
| `power_up` | ❌ **NOT IMPLEMENTED** | Defined in icon mapping but no trigger |
| `momentum` | ❌ **NOT IMPLEMENTED** | Defined in icon mapping but no trigger |
| `burning_streak` | ❌ **NOT IMPLEMENTED** | Defined in icon mapping but no trigger |
| `tutor_needs_practice` | ❌ **NOT IMPLEMENTED** | Listed in tables but no trigger code |

---

## 🧹 CLEANUP RECOMMENDATIONS

### High Priority Issues
1. **Consolidate redundant bell icons** - Multiple notifications use `fas fa-bell`
2. **Remove unimplemented energy notifications** from icon mapping
3. **Standardize warning icons** - Too many use `fas fa-exclamation-triangle`

### Medium Priority Issues
1. **Review assignment notification count** - 20+ different types may overwhelm users
2. **Consolidate similar warning notifications**
3. **Consider reducing subject-specific course notifications** (3 per course)

### Low Priority Issues
1. **Implement missing special achievements** if needed
2. **Add more distinct icons for system notifications**
3. **Review color-coding consistency**

---

## 📊 SYSTEM STATISTICS

### Current State
- **Total Active Notification Types**: ~65 (after cleanup)
- **Unique FontAwesome Icons**: 35+
- **Color Themes**: 8 main color schemes
- **Border Priority Levels**: 6 (Red, Amber, Green, Purple, Fiery, None)
- **Most Overused Icon**: `fas fa-bell` (5+ notifications)
- **Most Overused Color**: Red variants (15+ notifications)

### Recent Changes
- **Removed Types**: 13 redundant notifications
- **Fixed Triggers**: 6 major trigger fixes implemented
- **New Features**: Level up (fiery border), Topic mastery (green border)
- **Simplified Systems**: Assignment and tutor notifications streamlined

### 🎯 Quick Reference Legend
- **🔴 Red Border**: Urgent (deadlines, failures)
- **🟡 Amber Border**: High priority (achievements, important events)
- **🔵 Blue Border**: Welcome (platform messages)
- **🟢 Green Border**: Mastery (topic mastery achievements)
- **🟣 Purple Border**: Perfect scores (100% achievements)
- **🔥 Fiery Border**: Level up (new level achievements)
- **🔷 Deep Blue Border**: Premium (course mastery achievements)
- **🟣 Deep Purple Border**: Exotic (special milestones like 100 correct answers)
- **⚪ No Border**: Normal priority (regular activities)

---

*Last Updated: February 2025*  
*Total Active Notifications: 65+*  
*Documentation Status: Complete with actual messages*  
*Format: Organized by function with actual message content*