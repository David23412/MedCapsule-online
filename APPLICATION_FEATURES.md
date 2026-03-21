# MedCapsule Application Features

This document summarizes the key features currently present in the MedCapsule application, based on routes, configuration, services, and project documentation.

> Snapshot source: Laravel route list (`169` routes), `config/*.php`, and `docs/*.md` files.

---

## 1) Authentication & Access Control

- User registration, login, and logout
- Auth-protected routes for learners
- Role-based access layers:
  - **Admin**
  - **Tutor**
  - **Student/User**
- Middleware protections for:
  - authentication
  - admin-only sections
  - tutor-access sections
  - throttling on sensitive endpoints

---

## 2) Course Management

- Course catalog/listing page
- Course details page
- Course creation (admin)
- Course enrollment flow
- Course case listings and case counts
- Academic/course filtering support
- Course-level fake enrolled count controls (admin simulation settings)

---

## 3) Topic Management

- Add topics (admin)
- View topics by course
- Legacy topic route compatibility handling
- Topic source progress tracking endpoint
- Topic sources retrieval APIs
- Course-to-topics APIs for assignment/admin flows

---

## 4) Question Bank Management

- Add question form + submit (admin)
- Question deletion (single + bulk)
- Question image support and uploads
- Source-based question grouping/counting APIs
- Question bank image workflow documentation (Excel/image filename based process)

---

## 5) Quiz Engine (Core)

- Start quiz by course + topic
- Submit/grade topic quiz
- Tutor-mode quiz sessions
- Tutor answer checking and tutor session completion
- Quiz architecture split into read/execute/grading concerns
- Configurable quiz parameters (max questions, time limit, passing score)
- **Source categorization + filtering in quizzes**:
  - quizzes can be started with selected `sources[]`
  - per-topic available source discovery
  - validation when selected source has no questions
  - source-based quiz statistics and source breakdown


---

## 6) Random Quiz System

- Random quiz generation UI and endpoints
- Random tutor quiz mode
- Random quiz grading
- Random quiz stats endpoint
- Rate-limited random quiz routes for abuse control/performance

---

## 7) Mistake Review & Learning Loop

- Mistakes index/review pages
- Learn from mistakes flow
- Remove mistake / skip mistake actions
- Review-specific learning actions
- Study time save endpoint from learning flow
- **Repetition / spaced-repetition behavior for mistakes**:
  - mistake tracking with `correct_streak` and mastery states
  - Anki-like rating/update logic (`updateAnki` path)
  - skipped-mistake queue and skipped filtering
  - “needs review” reminders and mistake-review notifications
  - dedicated `mistake_review` study mode/session tracking

---

## 8) Profile, Progress, and Study Goals

- Profile page
- Profile picture update/upload flow
- Weekly goal update endpoint
- User progress endpoint for profile widgets
- **Study streak tracking**:
  - persistent `study_streak_days`
  - automatic increment/reset based on consecutive study days
  - streak milestone and streak-reset notifications
- **Study time tracking**:
  - stored as `study_time_seconds`
  - per-mode tracking (quiz, random quiz, mistake review)
  - dedicated save-study-time flow in learn/mistake sessions
- Study-goals widget features (from docs):
  - weekly attempted-question tracking
  - color-coded progress states
  - goal-achieved visual celebration effects

---

## 9) Assignment System (Tutor/Admin + Student)

- Assignment listing, creation (bulk), editing, updating, deletion
- Assignment detail view
- Assignment deadline extension
- Assignment status updates (complete, overdue, real-time enforcement)
- Assignment counter/stat APIs
- Course/topic/student lookup APIs for assignment creation
- Student assignment dashboard:
  - assigned items list
  - start assignment
  - complete assignment
  - assignment history
  - countdown/status endpoints
  - progress widget endpoint
  - extension request flow

---

## 10) Tutor Monitoring & Reporting

- Tutor dashboard
- Student profile view for tutors
- Student progress view for tutors
- Real-time update feed per student
- Tutor report generation per student
- Bulk report export
- Tutor management by admin/tutor roles:
  - create tutor
  - assign/remove students
  - bulk operations
  - tutor details

---

## 11) Topic Locking System

- Topic locking dashboard
- Lock/unlock individual topics
- Toggle topic lock state
- Bulk lock/unlock
- Course-wide lock all / unlock all
- Student-access checking endpoints for locked topics
- Locked-topic listing + statistics endpoints

---

## 11.5) Source Progress & Categorization (Explicit)

- Per-topic **source progress endpoint** with mode-aware output (`quiz` vs `tutor`)
- Source-specific completion percentages and attempt-aware progress
- Source question counting/grouping APIs (`question-counts`, source lists)
- UI support for source selection modal, source status, and source completion badges
- “All sources mastered” event-driven support in backend events/listeners
- Source-specific assignment mode with validation that source exists for topic

---

## 12) Video Learning System

- Admin video management:
  - list/upload/toggle/delete videos
- Topic video page route
- Video access endpoint checks
- Secure video token generation (GET/POST)
- Tokenized video stream endpoint
- Video progress tracking APIs (update + fetch)
- Video details endpoint

---

## 13) Payment & Enrollment Verification

- Manual payment options support:
  - Vodafone Cash
  - Fawry
  - Instapay
- Payment options retrieval per course
- Payment submission/process endpoint
- Admin payment center:
  - payment history
  - payment details
  - accept/reject/verify actions
- Real-time payment status check endpoint
- Payment configuration capabilities:
  - transaction pattern validation
  - auto-verification toggles
  - OCR-based verification options
  - confidence thresholds and weighted verification methods
  - storage/retention and cleanup controls

---

## 14) Notifications

- Notification center/list view
- Notification services in backend (`NotificationService`, `UniversalNotificationService`, trigger services)
- Event-driven notification architecture (multiple domain events available)
- Notification bell UI component support

---

## 15) Real-Time / Live Metrics Features

- Active users API endpoint
- Visitor vs logged-in user live breakdown
- Optional fake live-user mode (admin controlled min/max range)
- Progress update APIs for real-time client updates
- Real-time enrollment status check endpoint

---

## 16) Admin Operations & Governance

- Access management dashboard (`manage-access`)
- Enroll/revoke user course access
- Search users for assignment/access operations
- View user enrollments and user-course mappings
- Toggle admin privileges
- Payment settings management
- System/admin settings key-value update/get APIs
- Delete management module for:
  - courses
  - topics
  - questions
  - users
  - deletion preview tools

---

## 17) Security Features

- Security headers middleware integration
- CSP testing route and CSP-aware handling for video routes
- CSRF protection (and selective bypass for specific high-throughput endpoints)
- Request throttling on sensitive write APIs
- Role-based middleware + auth guards
- Secure upload-serving routes for protected files
- Payment security and transaction validation service layer
- Session security and environment/security utility services

---

## 18) Performance & Reliability Features

- Feature flags for controlled rollout (`config/features.php`), including:
  - event-driven grading
  - smart cache manager
  - memory-efficient quiz mode
  - memory monitor routes
  - health check toggles
  - fallback and rollout strategies
- Redis-oriented performance configuration (`config/performance.php`)
- Performance optimization work for course listing (documented)
- Advanced service layer for:
  - cache invalidation
  - performance/query monitoring
  - health checks
  - emergency fallback handling

---

## 19) Responsive & UX Optimization Features

- Mobile-first responsive navigation system (documented and implemented files)
- Touch target sizing and mobile breakpoints (`config/responsive.php`)
- Accessibility settings (reduced motion, keyboard nav support)
- Responsive image and lazy-loading optimization settings
- Profile page aesthetic enhancement package (glassmorphism, gradients, motion effects)

---

## 20) Monitoring, Health & Debug Utilities

- `/health` healthcheck endpoint
- Auth/admin debug status endpoints
- CSP test endpoint
- Search-user test endpoints
- Background task/broadcast auth support routes

---

## 21) AI Feature Roadmap (Documented)

- A full implementation plan exists for a **MedCapsule AI chatbot**
- Includes UI/UX design, safety rules, performance targets, and phased rollout plan
- Currently documented as a plan (roadmap artifact)

---

## 22) Summary

MedCapsule currently includes a broad feature set across:

- learner workflows (courses, quizzes, review, profile, study goals)
- tutor operations (monitoring, assignments, reporting)
- admin governance (access, payments, deletion tools, settings)
- platform infrastructure (security, performance flags, health checks, responsiveness)

This makes the application a full-stack medical learning platform with role-based operations, rich assessment tooling, and operational controls.

### Explicit checklist for your requested items

- ✅ Source categorization (selection, progress, per-source stats, source-specific assignments)
- ✅ Repetition/learn mistakes flow (review loop, streak-based mastery, skip/review mechanisms)
- ✅ Study streak tracking (daily streak logic + milestone/reset notifications)
- ✅ Study time tracking (session capture + accumulated study time + mode breakdown)
